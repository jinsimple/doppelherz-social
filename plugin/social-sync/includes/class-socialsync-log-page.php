<?php
/**
 * Trang wp-admin "SocialSync → Nhật ký đồng bộ" (US-011, US-012, US-013).
 *
 * Ai xem được: người có quyền edit_others_posts (Administrator, Editor, Shop manager).
 * Đồng bộ lại: lượt lỗi / bị bỏ qua / treo quá lâu. Đăng lại lượt đã đăng: chỉ Administrator.
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_Log_Page {

	public const SLUG     = 'socialsync';
	public const CAP      = 'edit_others_posts';
	private const PER_PAGE = 20;

	public const STATUS_LABELS = array(
		'pending'      => 'Chờ xử lý',
		'generating'   => 'Đang tạo nội dung',
		'needs_review' => 'Chờ duyệt',
		'scheduled'    => 'Đã lên lịch',
		'published'    => 'Đã đăng',
		'failed'       => 'Lỗi',
		'skipped'      => 'Bỏ qua',
		'rejected'     => 'Bị từ chối',
	);

	private const STATUS_COLORS = array(
		'pending'      => '#646970',
		'generating'   => '#2271b1',
		'needs_review' => '#996800',
		'scheduled'    => '#2271b1',
		'published'    => '#007017',
		'failed'       => '#b32d2e',
		'skipped'      => '#646970',
		'rejected'     => '#b32d2e',
	);

	public static function init(): void {
		add_action( 'admin_post_socialsync_resync', array( __CLASS__, 'handle_resync' ) );
	}

	/** @return array{channel:string, status:string, from:string, to:string, post_id:int} */
	private static function filters(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- chỉ đọc bộ lọc.
		$channel = isset( $_GET['channel'] ) ? sanitize_key( wp_unslash( $_GET['channel'] ) ) : '';
		$status  = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$from    = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
		$to      = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		// phpcs:enable
		$is_date = static fn( string $d ): bool => 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d );
		return array(
			'channel' => isset( SocialSync_Settings::CHANNELS[ $channel ] ) ? $channel : '',
			'status'  => isset( self::STATUS_LABELS[ $status ] ) ? $status : '',
			'from'    => $is_date( $from ) ? $from : '',
			'to'      => $is_date( $to ) ? $to : '',
			'post_id' => $post_id,
		);
	}

	public static function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$filters = self::filters();
		$page    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$result  = SocialSync_DB::table_exists()
			? SocialSync_DB::query( $filters, $page, self::PER_PAGE )
			: array( 'rows' => array(), 'total' => 0 );
		?>
		<div class="wrap">
			<h1>Nhật ký đồng bộ</h1>
			<p>Mỗi dòng là một lượt đưa một bài lên một kênh. Lượt mới tạo khi bài viết hoặc sản phẩm được xuất bản lần đầu. Giờ hiển thị theo giờ của website.</p>
			<?php self::render_notice(); ?>
			<?php $waiting = SocialSync_Review::pending_count(); ?>
			<?php if ( $waiting ) : ?>
				<div class="notice notice-warning inline"><p><strong><?php echo (int) $waiting; ?> bài đang chờ duyệt.</strong> <a href="<?php echo esc_url( SocialSync_Review::url() ); ?>">Mở danh sách chờ duyệt</a></p></div>
			<?php endif; ?>

			<?php self::render_filters( $filters ); ?>

			<h2>Tổng hợp<?php echo ( $filters['from'] || $filters['to'] ) ? esc_html( ' từ ' . ( $filters['from'] ?: '…' ) . ' đến ' . ( $filters['to'] ?: '…' ) ) : ' (mọi thời gian)'; ?></h2>
			<?php self::render_summary( $filters ); ?>

			<h2>Danh sách lượt (<?php echo (int) $result['total']; ?>)</h2>
			<?php self::render_table( $result['rows'] ); ?>
			<?php self::render_pagination( (int) $result['total'], $page ); ?>
		</div>
		<?php
	}

	private static function render_filters( array $f ): void {
		?>
		<form method="get" style="margin:12px 0">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
			<label>Từ ngày <input type="date" name="from" value="<?php echo esc_attr( $f['from'] ); ?>"></label>
			<label>Đến ngày <input type="date" name="to" value="<?php echo esc_attr( $f['to'] ); ?>"></label>
			<select name="channel">
				<option value="">Mọi kênh</option>
				<?php foreach ( SocialSync_Settings::CHANNELS as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $f['channel'], $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="status">
				<option value="">Mọi trạng thái</option>
				<?php foreach ( self::STATUS_LABELS as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $f['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php if ( $f['post_id'] ) : ?>
				<input type="hidden" name="post_id" value="<?php echo (int) $f['post_id']; ?>">
				<span>Bài #<?php echo (int) $f['post_id']; ?></span>
			<?php endif; ?>
			<?php submit_button( 'Lọc', 'secondary', '', false ); ?>
			<a class="button-link" style="margin-left:8px" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ); ?>">Bỏ lọc</a>
		</form>
		<?php
	}

	private static function render_summary( array $f ): void {
		$counts = SocialSync_DB::table_exists()
			? SocialSync_DB::counts( array( 'from' => $f['from'], 'to' => $f['to'] ) )
			: array();
		$shown  = array( 'published', 'scheduled', 'pending', 'generating', 'needs_review', 'failed', 'skipped', 'rejected' );
		echo '<table class="widefat striped" style="max-width:980px"><thead><tr><th>Kênh</th>';
		foreach ( $shown as $status ) {
			printf( '<th>%s</th>', esc_html( self::STATUS_LABELS[ $status ] ) );
		}
		echo '<th>Tổng</th></tr></thead><tbody>';
		$grand = array();
		foreach ( SocialSync_Settings::CHANNELS as $channel => $label ) {
			$row_total = 0;
			echo '<tr><th>' . esc_html( $label ) . '</th>';
			foreach ( $shown as $status ) {
				$n                  = $counts[ $channel ][ $status ] ?? 0;
				$row_total         += $n;
				$grand[ $status ]   = ( $grand[ $status ] ?? 0 ) + $n;
				$url                = add_query_arg(
					array(
						'page'    => self::SLUG,
						'channel' => $channel,
						'status'  => $status,
						'from'    => $f['from'],
						'to'      => $f['to'],
					),
					admin_url( 'admin.php' )
				);
				printf( '<td>%s</td>', $n ? '<a href="' . esc_url( $url ) . '">' . (int) $n . '</a>' : '0' );
			}
			printf( '<td><strong>%d</strong></td></tr>', (int) $row_total );
		}
		echo '<tr><th>Tổng</th>';
		foreach ( $shown as $status ) {
			printf( '<td><strong>%d</strong></td>', (int) ( $grand[ $status ] ?? 0 ) );
		}
		printf( '<td><strong>%d</strong></td></tr>', (int) array_sum( $grand ) );
		echo '</tbody></table>';
	}

	/** @param array<int, array<string, mixed>> $rows */
	private static function render_table( array $rows ): void {
		if ( ! $rows ) {
			echo '<p>Chưa có lượt nào khớp bộ lọc.</p>';
			return;
		}
		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th style="width:48px">#</th>
					<th>Bài gốc</th>
					<th>Kênh</th>
					<th>Trạng thái</th>
					<th>Thời gian</th>
					<th>Kết quả</th>
					<th style="width:140px">Thao tác</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $rows as $job ) : ?>
				<?php self::render_row( $job ); ?>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private static function render_row( array $job ): void {
		$post_id  = (int) $job['post_id'];
		$status   = (string) $job['status'];
		$title    = get_the_title( $post_id );
		$exists   = (bool) get_post( $post_id );
		$warning  = self::warning( $job );
		$exec_url = SocialSync_Settings::n8n_execution_url( (string) ( $job['execution_id'] ?? '' ) );
		?>
		<tr>
			<td><?php echo (int) $job['id']; ?></td>
			<td>
				<?php if ( $exists ) : ?>
					<a href="<?php echo esc_url( (string) get_edit_post_link( $post_id ) ); ?>"><strong><?php echo esc_html( '' !== $title ? $title : '(không có tiêu đề)' ); ?></strong></a>
					<br><span class="description"><?php echo esc_html( ( 'product' === $job['post_type'] ? 'Sản phẩm' : 'Bài viết' ) . ' #' . $post_id ); ?></span>
					· <a href="<?php echo esc_url( (string) get_permalink( $post_id ) ); ?>" target="_blank" rel="noopener">xem</a>
					· <a href="<?php echo esc_url( add_query_arg( array( 'page' => self::SLUG, 'post_id' => $post_id ), admin_url( 'admin.php' ) ) ); ?>">mọi lượt của bài</a>
				<?php else : ?>
					<em>(bài #<?php echo (int) $post_id; ?> đã bị xoá)</em>
				<?php endif; ?>
			</td>
			<td><?php echo esc_html( SocialSync_Settings::CHANNELS[ $job['channel'] ] ?? (string) $job['channel'] ); ?></td>
			<td>
				<span style="display:inline-block;padding:2px 8px;border-radius:10px;color:#fff;background:<?php echo esc_attr( self::STATUS_COLORS[ $status ] ?? '#646970' ); ?>"><?php echo esc_html( self::STATUS_LABELS[ $status ] ?? $status ); ?></span>
				<?php if ( $warning ) : ?>
					<p style="color:#996800;margin:4px 0 0">⚠ <?php echo esc_html( $warning ); ?></p>
				<?php endif; ?>
			</td>
			<td>
				Tạo: <?php echo esc_html( self::local( $job['created_at'] ) ); ?>
				<?php if ( ! empty( $job['scheduled_at'] ) ) : ?><br>Dự kiến đăng: <?php echo esc_html( self::local( $job['scheduled_at'] ) ); ?><?php endif; ?>
				<?php if ( ! empty( $job['published_at'] ) ) : ?><br>Đã đăng: <?php echo esc_html( self::local( $job['published_at'] ) ); ?><?php endif; ?>
				<br><span class="description">Số lần gửi: <?php echo (int) $job['attempts']; ?></span>
			</td>
			<td>
				<?php if ( ! empty( $job['remote_url'] ) ) : ?>
					<a href="<?php echo esc_url( (string) $job['remote_url'] ); ?>" target="_blank" rel="noopener">Mở bài trên <?php echo esc_html( SocialSync_Settings::CHANNELS[ $job['channel'] ] ?? '' ); ?></a>
					<?php if ( ! empty( $job['visibility'] ) && 'public' !== $job['visibility'] ) : ?>
						<br><span class="description">Hiển thị: <?php echo esc_html( 'sandbox' === $job['visibility'] || 'private' === $job['visibility'] ? 'chỉ chủ tài khoản thấy' : (string) $job['visibility'] ); ?></span>
					<?php endif; ?>
				<?php endif; ?>
				<?php if ( ! empty( $job['error'] ) ) : ?>
					<p style="color:#b32d2e;margin:0"><?php echo esc_html( mb_substr( (string) $job['error'], 0, 220 ) ); ?></p>
				<?php endif; ?>
				<?php self::render_details( $job, $exec_url ); ?>
			</td>
			<td><?php self::render_actions( $job ); ?></td>
		</tr>
		<?php
	}

	private static function render_details( array $job, string $exec_url ): void {
		$content = (string) ( $job['content'] ?? '' );
		$decoded = '' !== $content ? json_decode( $content, true ) : null;
		$pretty  = is_array( $decoded ) ? (string) wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : $content;
		?>
		<details>
			<summary>Chi tiết</summary>
			<ul style="margin:6px 0">
				<?php if ( ! empty( $job['classification'] ) ) : ?>
					<li>Phân loại: <?php echo esc_html( SocialSync_Report::CLASS_LABELS[ $job['classification'] ] ?? (string) $job['classification'] ); ?><?php echo is_array( $decoded ) && ! empty( $decoded['classification_reason'] ) ? esc_html( ' — ' . $decoded['classification_reason'] ) : ''; ?></li>
				<?php endif; ?>
				<?php if ( ! empty( $job['reviewed_at'] ) ) : ?>
					<li>Duyệt: <?php echo esc_html( sprintf( '%s lúc %s — %s', (string) ( get_userdata( (int) $job['reviewed_by'] )->display_name ?? '#' . $job['reviewed_by'] ), self::local( $job['reviewed_at'] ), (string) $job['review_note'] ) ); ?></li>
				<?php endif; ?>
				<?php if ( ! empty( $job['metrics'] ) ) : ?>
					<?php $m = json_decode( (string) $job['metrics'], true ); ?>
					<li>Tương tác (<?php echo esc_html( self::local( $job['metrics_at'] ) ); ?>): <?php echo esc_html( sprintf( '%d cảm xúc · %d bình luận · %d chia sẻ', $m['reactions'] ?? 0, $m['comments'] ?? 0, $m['shares'] ?? 0 ) ); ?></li>
				<?php endif; ?>
				<li>Nguồn nội dung: <?php echo esc_html( (string) ( $job['content_source'] ?? '—' ) ); ?><?php echo ! empty( $job['content_source_reason'] ) ? esc_html( ' (' . $job['content_source_reason'] . ')' ) : ''; ?></li>
				<?php if ( ! empty( $job['model'] ) ) : ?>
					<li>AI: <?php echo esc_html( sprintf( '%s · %s token vào · %s token ra · %s ms', $job['model'], $job['tokens_in'] ?? '?', $job['tokens_out'] ?? '?', $job['ai_ms'] ?? '?' ) ); ?></li>
				<?php endif; ?>
				<li>Mã bài trên kênh: <?php echo esc_html( (string) ( $job['remote_id'] ?? '—' ) ); ?></li>
				<li>Lượt chạy n8n:
					<?php if ( $exec_url ) : ?>
						<a href="<?php echo esc_url( $exec_url ); ?>" target="_blank" rel="noopener">#<?php echo esc_html( (string) $job['execution_id'] ); ?></a>
					<?php else : ?>
						—
					<?php endif; ?>
				</li>
				<li>Cập nhật lần cuối: <?php echo esc_html( self::local( $job['updated_at'] ) ); ?></li>
			</ul>
			<?php if ( ! empty( $job['error'] ) ) : ?>
				<p><strong>Lỗi đầy đủ:</strong></p>
				<pre style="white-space:pre-wrap;background:#fcf0f1;padding:8px"><?php echo esc_html( (string) $job['error'] ); ?></pre>
			<?php endif; ?>
			<p><strong>Nội dung đã đăng:</strong></p>
			<pre style="white-space:pre-wrap;background:#f6f7f7;padding:8px;max-height:320px;overflow:auto"><?php echo esc_html( '' !== $pretty ? $pretty : '(chưa có — n8n chưa báo nội dung về)' ); ?></pre>
		</details>
		<?php
	}

	private static function render_actions( array $job ): void {
		if ( 'needs_review' === $job['status'] ) {
			printf( '<a class="button button-primary button-small" href="%s">Xem và duyệt</a>', esc_url( SocialSync_Review::url( (int) $job['post_id'] ) ) );
			return;
		}
		$check = self::can_resync( $job );
		if ( true !== $check ) {
			printf( '<span class="description">%s</span>', esc_html( $check ) );
			return;
		}
		$status  = (string) $job['status'];
		$confirm = '';
		if ( 'published' === $status ) {
			$confirm = 'Bài này ĐÃ được đăng lên kênh. Đồng bộ lại sẽ tạo thêm một bài nữa. Tiếp tục?';
		} elseif ( in_array( $status, array( 'pending', 'generating', 'scheduled' ), true ) ) {
			$confirm = 'Lượt này có thể đã được đăng nhưng chưa báo về. Hãy kiểm tra trên kênh trước. Vẫn đồng bộ lại?';
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" <?php echo $confirm ? 'onsubmit="return confirm(' . esc_attr( wp_json_encode( $confirm ) ) . ');"' : ''; ?>>
			<input type="hidden" name="action" value="socialsync_resync">
			<input type="hidden" name="job_id" value="<?php echo (int) $job['id']; ?>">
			<?php wp_nonce_field( 'socialsync_resync_' . (int) $job['id'] ); ?>
			<button type="submit" class="button button-small"><?php echo 'published' === $status ? 'Đăng lại' : 'Đồng bộ lại'; ?></button>
		</form>
		<?php
	}

	/** @return true|string true nếu được đồng bộ lại, ngược lại là lý do. */
	private static function can_resync( array $job ) {
		$status = (string) $job['status'];
		if ( ! get_post( (int) $job['post_id'] ) ) {
			return 'Bài gốc đã bị xoá';
		}
		switch ( $status ) {
			case 'failed':
			case 'skipped':
				return true;
			case 'published':
			case 'rejected':
				return current_user_can( 'manage_options' ) ? true : 'Chỉ Administrator được đăng lại';
			default:
				return null !== self::warning( $job ) ? true : 'Đang xử lý';
		}
	}

	/** Cảnh báo khi một lượt đứng quá lâu ở một trạng thái (PRD mục 11). */
	private static function warning( array $job ): ?string {
		$now     = time();
		$status  = (string) $job['status'];
		$created = strtotime( (string) $job['created_at'] . ' UTC' );
		$updated = strtotime( (string) $job['updated_at'] . ' UTC' );
		$sched   = empty( $job['scheduled_at'] ) ? null : strtotime( (string) $job['scheduled_at'] . ' UTC' );

		if ( 'pending' === $status && 0 === (int) $job['attempts'] && $created && $now - $created > 120 ) {
			return 'Hơn 2 phút chưa gửi được sang n8n (tiến trình nền có thể đã không chạy).';
		}
		if ( in_array( $status, array( 'pending', 'generating' ), true ) && (int) $job['attempts'] > 0 && $updated && $now - $updated > 900 ) {
			return 'n8n đã nhận hơn 15 phút nhưng chưa báo kết quả. Kiểm tra kênh trước khi đồng bộ lại.';
		}
		if ( 'scheduled' === $status && $sched && $now - $sched > 900 ) {
			return 'Quá giờ đăng dự kiến 15 phút chưa có kết quả. Kiểm tra kênh trước khi đồng bộ lại.';
		}
		return null;
	}

	public static function handle_resync(): void {
		$job_id = isset( $_POST['job_id'] ) ? absint( $_POST['job_id'] ) : 0;
		check_admin_referer( 'socialsync_resync_' . $job_id );
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Không có quyền.', 403 );
		}

		$job   = SocialSync_DB::get_job( $job_id );
		$check = null === $job ? 'Không tìm thấy lượt #' . $job_id : self::can_resync( $job );
		if ( true !== $check ) {
			SocialSync_Logger::warn( 'resync.denied', 'Từ chối đồng bộ lại: ' . $check, array( 'job_id' => $job_id, 'by_user' => get_current_user_id() ) );
			self::redirect_with_notice( 'error', 'Không đồng bộ lại được: ' . $check );
		}

		SocialSync_Logger::info(
			'resync.request',
			'Người dùng bấm Đồng bộ lại',
			array(
				'job_id'        => $job_id,
				'post_id'       => (int) $job['post_id'],
				'channel'       => (string) $job['channel'],
				'status_before' => (string) $job['status'],
				'error_before'  => $job['error'],
				'by_user'       => get_current_user_id(),
			)
		);

		SocialSync_DB::update_jobs(
			array( $job_id ),
			array(
				'status'                => 'pending',
				'error'                 => null,
				'remote_id'             => null,
				'remote_url'            => null,
				'visibility'            => null,
				'published_at'          => null,
				'scheduled_at'          => null,
				'execution_id'          => null,
				'content'               => null,
				'content_source'        => null,
				'content_source_reason' => null,
				'classification'        => null,
				'reviewed_by'           => null,
				'reviewed_at'           => null,
				'review_note'           => null,
			)
		);

		// Đồng bộ lại thì đăng ngay, không chờ số phút trễ trong cấu hình.
		SocialSync_Dispatcher::kick(
			(int) $job['post_id'],
			array(
				array(
					'job_id'        => $job_id,
					'channel'       => (string) $job['channel'],
					'delay_minutes' => 0,
				),
			),
			0
		);

		self::redirect_with_notice( 'success', sprintf( 'Đã gửi lại lượt #%d sang n8n. Tải lại trang sau vài giây để xem kết quả.', $job_id ) );
	}

	private static function redirect_with_notice( string $type, string $message ): void {
		set_transient(
			'socialsync_notice_' . get_current_user_id(),
			array(
				'type'    => $type,
				'message' => $message,
			),
			120
		);
		$back = wp_get_referer();
		wp_safe_redirect( $back ? $back : admin_url( 'admin.php?page=' . self::SLUG ) );
		exit;
	}

	private static function render_notice(): void {
		$key    = 'socialsync_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		if ( ! is_array( $notice ) ) {
			return;
		}
		delete_transient( $key );
		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			'success' === $notice['type'] ? 'success' : 'error',
			esc_html( (string) $notice['message'] )
		);
	}

	private static function render_pagination( int $total, int $page ): void {
		$pages = (int) ceil( $total / self::PER_PAGE );
		if ( $pages <= 1 ) {
			return;
		}
		$links = paginate_links(
			array(
				'base'    => add_query_arg( 'paged', '%#%' ),
				'format'  => '',
				'current' => $page,
				'total'   => $pages,
			)
		);
		echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( (string) $links ) . '</div></div>';
	}

	/** Giờ UTC trong DB -> giờ site. */
	private static function local( $utc ): string {
		if ( empty( $utc ) ) {
			return '—';
		}
		return get_date_from_gmt( (string) $utc, 'H:i:s d/m/Y' );
	}
}
