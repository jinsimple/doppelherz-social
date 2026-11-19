<?php
/**
 * Báo cáo kết quả đồng bộ (trang "SocialSync → Báo cáo" và dữ liệu cho luồng báo cáo tuần của n8n).
 *
 * Số liệu lấy từ bảng lượt đồng bộ. Tương tác Facebook (lượt cảm xúc, bình luận, chia sẻ) do luồng
 * "SocialSync - Báo cáo tuần" của n8n đọc từ Graph API rồi ghi vào cột metrics.
 * Chi phí AI là ƯỚC TÍNH theo bảng giá Claude Opus 5.5 ($4 / $20 cho 1 triệu token vào / ra).
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_Report {

	public const SLUG          = 'socialsync-report';
	private const USD_IN_PER_M  = 4.0;
	private const USD_OUT_PER_M = 20.0;

	public const CLASS_LABELS = array(
		'E' => 'Kiến thức',
		'P' => 'Sản phẩm',
		'N' => 'Tin thương hiệu',
		'X' => 'Không đồng bộ',
	);

	public static function init(): void {
		add_action( 'admin_post_socialsync_report_run', array( __CLASS__, 'handle_run' ) );
	}

	/** @return array<string, mixed> */
	public static function stats( int $days ): array {
		global $wpdb;
		$t    = SocialSync_DB::table();
		$from = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );

		$rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE created_at >= %s", $from ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$by_channel = array();
		$by_class   = array();
		$by_source  = array( 'ai' => 0, 'template' => 0 );
		$per_day    = array();
		$tokens_in  = 0;
		$tokens_out = 0;
		$ai_calls   = array();
		$lead       = array();
		$top        = array();
		$fb_posts   = array();

		foreach ( $rows as $r ) {
			$ch                                   = (string) $r['channel'];
			$st                                   = (string) $r['status'];
			$by_channel[ $ch ][ $st ]             = ( $by_channel[ $ch ][ $st ] ?? 0 ) + 1;
			$by_channel[ $ch ]['total']           = ( $by_channel[ $ch ]['total'] ?? 0 ) + 1;
			if ( ! empty( $r['classification'] ) ) {
				$by_class[ $r['classification'] ] = ( $by_class[ $r['classification'] ] ?? 0 ) + 1;
			}
			if ( isset( $by_source[ $r['content_source'] ?? '' ] ) ) {
				++$by_source[ $r['content_source'] ];
			}
			// Một lần gọi AI viết cho mọi kênh của bài: chỉ tính token một lần cho mỗi lần chạy n8n.
			$call_key = $r['post_id'] . '|' . $r['execution_id'];
			if ( 'ai' === ( $r['content_source'] ?? '' ) && ! isset( $ai_calls[ $call_key ] ) ) {
				$ai_calls[ $call_key ] = true;
				$tokens_in            += (int) $r['tokens_in'];
				$tokens_out           += (int) $r['tokens_out'];
			}
			if ( 'published' === $st && ! empty( $r['published_at'] ) ) {
				$day                    = get_date_from_gmt( (string) $r['published_at'], 'Y-m-d' );
				$per_day[ $day ][ $ch ] = ( $per_day[ $day ][ $ch ] ?? 0 ) + 1;
				$lead[ $ch ][]          = max( 0, strtotime( $r['published_at'] . ' UTC' ) - strtotime( $r['created_at'] . ' UTC' ) );
				$m                      = json_decode( (string) $r['metrics'], true );
				if ( is_array( $m ) ) {
					$top[] = array(
						'job_id'   => (int) $r['id'],
						'post_id'  => (int) $r['post_id'],
						'title'    => html_entity_decode( (string) get_the_title( (int) $r['post_id'] ), ENT_QUOTES, 'UTF-8' ),
						'url'      => (string) $r['remote_url'],
						'metrics'  => $m,
						'total'    => array_sum( array_map( 'intval', $m ) ),
						'measured' => (string) $r['metrics_at'],
					);
				}
				if ( 'facebook' === $ch && ! empty( $r['remote_id'] ) ) {
					$fb_posts[] = array(
						'job_id'    => (int) $r['id'],
						'remote_id' => (string) $r['remote_id'],
						'title'     => html_entity_decode( (string) get_the_title( (int) $r['post_id'] ), ENT_QUOTES, 'UTF-8' ),
					);
				}
			}
		}
		ksort( $per_day );
		usort( $top, static fn( $a, $b ) => $b['total'] <=> $a['total'] );

		$channels = array();
		foreach ( SocialSync_Settings::CHANNELS as $ch => $label ) {
			$c        = $by_channel[ $ch ] ?? array();
			$pub      = (int) ( $c['published'] ?? 0 );
			$fail     = (int) ( $c['failed'] ?? 0 );
			$channels[ $ch ] = array(
				'label'        => $label,
				'total'        => (int) ( $c['total'] ?? 0 ),
				'by_status'    => array_diff_key( $c, array( 'total' => 1 ) ),
				'published'    => $pub,
				'failed'       => $fail,
				'success_rate' => $pub + $fail > 0 ? round( 100 * $pub / ( $pub + $fail ), 1 ) : null,
				'avg_lead_min' => ! empty( $lead[ $ch ] ) ? round( array_sum( $lead[ $ch ] ) / count( $lead[ $ch ] ) / 60, 1 ) : null,
			);
		}

		return array(
			'days'           => $days,
			'from'           => $from,
			'jobs'           => count( $rows ),
			'channels'       => $channels,
			'classification' => $by_class,
			'content_source' => $by_source,
			'ai'             => array(
				'calls'       => count( $ai_calls ),
				'tokens_in'   => $tokens_in,
				'tokens_out'  => $tokens_out,
				'usd_estimate' => round( $tokens_in / 1e6 * self::USD_IN_PER_M + $tokens_out / 1e6 * self::USD_OUT_PER_M, 4 ),
			),
			'per_day'        => $per_day,
			'top'            => array_slice( $top, 0, 10 ),
			'facebook_posts' => array_slice( $fb_posts, 0, 50 ),
		);
	}

	public static function render(): void {
		if ( ! current_user_can( SocialSync_Log_Page::CAP ) ) {
			return;
		}
		$days    = isset( $_GET['days'] ) && 30 === absint( $_GET['days'] ) ? 30 : 7; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$s       = self::stats( $days );
		$reports = get_option( 'socialsync_reports', array() );
		$latest  = is_array( $reports ) && $reports ? $reports[0] : null;
		$notice  = get_transient( 'socialsync_report_notice_' . get_current_user_id() );
		if ( $notice ) {
			delete_transient( 'socialsync_report_notice_' . get_current_user_id() );
		}
		$url = static fn( int $d ) => add_query_arg( array( 'page' => self::SLUG, 'days' => $d ), admin_url( 'admin.php' ) );
		?>
		<div class="wrap">
			<h1>Báo cáo đồng bộ</h1>
			<?php if ( $notice ) : ?>
				<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?>"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
			<?php endif; ?>
			<p>
				Khoảng thời gian:
				<a href="<?php echo esc_url( $url( 7 ) ); ?>" class="button <?php echo 7 === $days ? 'button-primary' : ''; ?>">7 ngày</a>
				<a href="<?php echo esc_url( $url( 30 ) ); ?>" class="button <?php echo 30 === $days ? 'button-primary' : ''; ?>">30 ngày</a>
				<span class="description">Tính theo lượt tạo từ <?php echo esc_html( get_date_from_gmt( $s['from'], 'H:i d/m/Y' ) ); ?>. Tổng <?php echo (int) $s['jobs']; ?> lượt.</span>
			</p>

			<h2>Kết quả theo kênh</h2>
			<table class="widefat striped" style="max-width:900px">
				<thead><tr><th>Kênh</th><th>Tổng lượt</th><th>Đã đăng</th><th>Lỗi</th><th>Tỉ lệ thành công</th><th>TB từ lúc xuất bản tới lúc lên kênh</th><th>Khác</th></tr></thead>
				<tbody>
				<?php foreach ( $s['channels'] as $c ) : ?>
					<tr>
						<th><?php echo esc_html( $c['label'] ); ?></th>
						<td><?php echo (int) $c['total']; ?></td>
						<td><?php echo (int) $c['published']; ?></td>
						<td><?php echo (int) $c['failed']; ?></td>
						<td><?php echo null === $c['success_rate'] ? '—' : esc_html( $c['success_rate'] . '%' ); ?></td>
						<td><?php echo null === $c['avg_lead_min'] ? '—' : esc_html( $c['avg_lead_min'] . ' phút' ); ?></td>
						<td class="description">
							<?php
							$other = array_diff_key( $c['by_status'], array( 'published' => 1, 'failed' => 1 ) );
							$parts = array();
							foreach ( $other as $st => $n ) {
								$parts[] = ( SocialSync_Log_Page::STATUS_LABELS[ $st ] ?? $st ) . ': ' . (int) $n;
							}
							echo esc_html( $parts ? implode( ' · ', $parts ) : '—' );
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description">Tỉ lệ thành công = đã đăng ÷ (đã đăng + lỗi). Lượt bỏ qua, chờ duyệt, bị từ chối không tính.</p>

			<h2>Nội dung và AI</h2>
			<table class="widefat striped" style="max-width:900px">
				<tbody>
					<tr><th style="width:280px">Phân loại bài</th><td>
						<?php
						$parts = array();
						foreach ( self::CLASS_LABELS as $k => $label ) {
							$parts[] = $label . ': ' . (int) ( $s['classification'][ $k ] ?? 0 );
						}
						echo esc_html( implode( ' · ', $parts ) );
						?>
					</td></tr>
					<tr><th>Nguồn nội dung (theo lượt)</th><td><?php echo esc_html( sprintf( 'Claude viết: %d · Mẫu cố định: %d', $s['content_source']['ai'], $s['content_source']['template'] ) ); ?></td></tr>
					<tr><th>Lần gọi Claude</th><td><?php echo esc_html( sprintf( '%d lần · %s token vào · %s token ra', $s['ai']['calls'], number_format_i18n( $s['ai']['tokens_in'] ), number_format_i18n( $s['ai']['tokens_out'] ) ) ); ?></td></tr>
					<tr><th>Chi phí AI ước tính</th><td><?php echo esc_html( sprintf( '$%s (khoảng %s đ, tỷ giá giả định 26.500)', number_format( $s['ai']['usd_estimate'], 3 ), number_format_i18n( $s['ai']['usd_estimate'] * 26500 ) ) ); ?></td></tr>
				</tbody>
			</table>

			<h2>Số bài lên kênh theo ngày</h2>
			<?php self::render_per_day( $s['per_day'] ); ?>

			<h2>Tương tác Facebook cao nhất</h2>
			<?php if ( ! $s['top'] ) : ?>
				<p>Chưa có số liệu tương tác. Bấm nút bên dưới để n8n đọc số liệu từ Facebook.</p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:900px">
					<thead><tr><th>Bài</th><th>Cảm xúc</th><th>Bình luận</th><th>Chia sẻ</th><th>Đo lúc</th></tr></thead>
					<tbody>
					<?php foreach ( $s['top'] as $t ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( $t['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $t['title'] ); ?></a></td>
							<td><?php echo (int) ( $t['metrics']['reactions'] ?? 0 ); ?></td>
							<td><?php echo (int) ( $t['metrics']['comments'] ?? 0 ); ?></td>
							<td><?php echo (int) ( $t['metrics']['shares'] ?? 0 ); ?></td>
							<td><?php echo esc_html( get_date_from_gmt( $t['measured'], 'H:i d/m' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<p class="description">Pinterest: số lượt xem/lưu Pin cần quyền API riêng của Pinterest (đang chờ duyệt), chưa đo.</p>

			<h2>Báo cáo tuần (Claude tóm tắt)</h2>
			<?php if ( $latest ) : ?>
				<p class="description">Tạo lúc <?php echo esc_html( get_date_from_gmt( (string) $latest['created_at'], 'H:i d/m/Y' ) ); ?> · <?php echo (int) $latest['days']; ?> ngày · <?php echo esc_html( 'ai' === $latest['source'] ? 'Claude tóm tắt (' . $latest['model'] . ')' : 'số liệu thô (AI không chạy được)' ); ?></p>
				<div style="background:#fff;border:1px solid #dcdcde;padding:12px 16px;max-width:900px;white-space:pre-wrap"><?php echo esc_html( (string) $latest['summary'] ); ?></div>
				<?php if ( ! empty( $latest['error'] ) ) : ?><p style="color:#b32d2e"><?php echo esc_html( (string) $latest['error'] ); ?></p><?php endif; ?>
			<?php else : ?>
				<p>Chưa có báo cáo. n8n tự tạo mỗi sáng thứ Hai 08:00, hoặc bấm nút bên dưới.</p>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px">
				<input type="hidden" name="action" value="socialsync_report_run">
				<input type="hidden" name="days" value="<?php echo (int) $days; ?>">
				<?php wp_nonce_field( 'socialsync_report_run' ); ?>
				<?php submit_button( 'Cập nhật tương tác Facebook và tạo báo cáo ' . $days . ' ngày', 'secondary', 'submit', false ); ?>
				<span class="description">n8n chạy nền khoảng 20–40 giây, sau đó tải lại trang.</span>
			</form>
		</div>
		<?php
	}

	private static function render_per_day( array $per_day ): void {
		if ( ! $per_day ) {
			echo '<p>Chưa có bài nào lên kênh trong khoảng này.</p>';
			return;
		}
		$max = 1;
		foreach ( $per_day as $row ) {
			$max = max( $max, array_sum( $row ) );
		}
		echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th style="width:110px">Ngày</th><th style="width:90px">Facebook</th><th style="width:90px">Pinterest</th><th></th></tr></thead><tbody>';
		foreach ( $per_day as $day => $row ) {
			$fb  = (int) ( $row['facebook'] ?? 0 );
			$pin = (int) ( $row['pinterest'] ?? 0 );
			printf(
				'<tr><td>%s</td><td>%d</td><td>%d</td><td><span style="display:inline-block;height:10px;background:#2271b1;width:%dpx"></span><span style="display:inline-block;height:10px;background:#b32d2e;width:%dpx"></span></td></tr>',
				esc_html( wp_date( 'd/m/Y', strtotime( $day . ' 12:00' ) ) ),
				$fb,
				$pin,
				(int) round( 300 * $fb / $max ),
				(int) round( 300 * $pin / $max )
			);
		}
		echo '</tbody></table><p class="description">Xanh: Facebook · Đỏ: Pinterest.</p>';
	}

	public static function handle_run(): void {
		check_admin_referer( 'socialsync_report_run' );
		if ( ! current_user_can( SocialSync_Log_Page::CAP ) ) {
			wp_die( 'Không có quyền.', 403 );
		}
		$days = isset( $_POST['days'] ) && 30 === absint( $_POST['days'] ) ? 30 : 7;
		$user = get_current_user_id();
		$back = add_query_arg( array( 'page' => self::SLUG, 'days' => $days ), admin_url( 'admin.php' ) );

		if ( get_transient( 'socialsync_report_lock' ) ) {
			SocialSync_Logger::warn( 'report.run_throttled', 'Bấm tạo báo cáo quá dày, bỏ qua', array( 'by_user' => $user ) );
			set_transient( 'socialsync_report_notice_' . $user, array( 'type' => 'warning', 'message' => 'Vừa có một lần tạo báo cáo trong 2 phút qua. Đợi một chút rồi tải lại trang.' ), 120 );
			wp_safe_redirect( $back );
			exit;
		}
		set_transient( 'socialsync_report_lock', 1, 120 );

		$payload = array(
			'event'      => 'report',
			'days'       => $days,
			'request_id' => SocialSync_Logger::request_id(),
			'by_user'    => $user,
		);
		SocialSync_Logger::info( 'report.run', 'Người dùng yêu cầu tạo báo cáo', $payload );
		$result = SocialSync_Dispatcher::send( $payload, array( 'event' => 'report' ), (string) SocialSync_Settings::get( 'report_url' ) );
		set_transient(
			'socialsync_report_notice_' . $user,
			$result['ok']
				? array( 'type' => 'success', 'message' => 'n8n đang tạo báo cáo. Tải lại trang sau khoảng 30 giây.' )
				: array( 'type' => 'error', 'message' => 'Không gọi được luồng báo cáo của n8n: ' . $result['error'] ),
			120
		);
		wp_safe_redirect( $back );
		exit;
	}
}
