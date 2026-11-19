<?php
/**
 * Duyệt nội dung trước khi đăng ("SocialSync → Chờ duyệt").
 *
 * Luồng: n8n viết nội dung xong báo lượt về trạng thái needs_review và dừng. Người duyệt (quyền edit_others_posts:
 * Administrator, Editor, Shop manager) xem, sửa nội dung, chọn giờ, rồi:
 *   - Duyệt: lượt chuyển needs_review -> scheduled bằng câu UPDATE có điều kiện (chống hai người duyệt cùng lúc),
 *     sau đó WordPress gửi lệnh đăng sang webhook n8n "socialsync/approved". Gửi lỗi thì trả lượt về needs_review.
 *   - Từ chối: needs_review -> rejected kèm ghi chú.
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_Review {

	public const SLUG = 'socialsync-review';

	/** Các cụm từ quảng cáo sức khỏe rủi ro: cảnh báo người duyệt (giống bộ lọc trong n8n). */
	private const BANNED = '/(chữa\s+(khỏi|bệnh|trị|dứt)|điều\s+trị|đặc\s+trị|khỏi\s+hẳn|khỏi\s+bệnh|hết\s+bệnh|dứt\s+điểm|thay\s+thế\s+thuốc|100\s*%|cam\s+kết|thần\s+dược|số\s+1(?!\d)|tốt\s+nhất|duy\s+nhất|giảm\s+cân)/iu';

	public static function init(): void {
		add_action( 'admin_post_socialsync_review', array( __CLASS__, 'handle' ) );
	}

	public static function pending_count(): int {
		global $wpdb;
		if ( ! SocialSync_DB::table_exists() ) {
			return 0;
		}
		return (int) $wpdb->get_var( 'SELECT COUNT(DISTINCT post_id) FROM ' . SocialSync_DB::table() . " WHERE status = 'needs_review'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function url( int $post_id = 0 ): string {
		$args = array( 'page' => self::SLUG );
		if ( $post_id ) {
			$args['post_id'] = $post_id;
		}
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	public static function render(): void {
		if ( ! current_user_can( SocialSync_Log_Page::CAP ) ) {
			return;
		}
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="wrap">';
		self::render_notice();
		if ( $post_id ) {
			self::render_form( $post_id );
		} else {
			self::render_list();
		}
		echo '</div>';
	}

	private static function render_list(): void {
		global $wpdb;
		$rows  = (array) $wpdb->get_results( 'SELECT * FROM ' . SocialSync_DB::table() . " WHERE status = 'needs_review' ORDER BY id ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$posts = array();
		foreach ( $rows as $r ) {
			$posts[ (int) $r['post_id'] ][] = $r;
		}
		echo '<h1>Chờ duyệt</h1>';
		echo '<p>AI đã viết nội dung cho các bài dưới đây. Nội dung chỉ được đăng sau khi có người duyệt. Đổi sang tự đăng trong SocialSync → Cấu hình.</p>';
		if ( ! $posts ) {
			echo '<p><strong>Không có bài nào chờ duyệt.</strong></p>';
			return;
		}
		echo '<table class="widefat striped" style="max-width:1000px"><thead><tr><th>Bài</th><th>Phân loại</th><th>Kênh và giờ đề xuất</th><th>Nội dung</th><th></th></tr></thead><tbody>';
		foreach ( $posts as $post_id => $jobs ) {
			$first    = $jobs[0];
			$content  = json_decode( (string) $first['content'], true );
			$channels = array();
			foreach ( $jobs as $j ) {
				$channels[] = ( SocialSync_Settings::CHANNELS[ $j['channel'] ] ?? $j['channel'] ) . ': ' . ( $j['scheduled_at'] ? get_date_from_gmt( (string) $j['scheduled_at'], 'H:i d/m' ) : '—' );
			}
			printf(
				'<tr><td><strong>%s</strong><br><span class="description">%s #%d</span></td><td>%s%s</td><td>%s</td><td>%s</td><td><a class="button button-primary" href="%s">Xem và duyệt</a></td></tr>',
				esc_html( (string) get_the_title( $post_id ) ),
				esc_html( 'product' === $first['post_type'] ? 'Sản phẩm' : 'Bài viết' ),
				(int) $post_id,
				esc_html( SocialSync_Report::CLASS_LABELS[ $first['classification'] ?? '' ] ?? '—' ),
				! empty( $content['sensitive'] ) ? ' <span style="color:#b32d2e">· nhóm nhạy cảm</span>' : '',
				esc_html( implode( ' · ', $channels ) ),
				esc_html( 'ai' === $first['content_source'] ? 'Claude viết' : 'Mẫu cố định' ),
				esc_url( self::url( $post_id ) )
			);
		}
		echo '</tbody></table>';
	}

	private static function render_form( int $post_id ): void {
		global $wpdb;
		$jobs = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SocialSync_DB::table() . " WHERE post_id = %d AND status = 'needs_review' ORDER BY channel ASC", $post_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		printf( '<h1>Duyệt nội dung: %s</h1>', esc_html( (string) get_the_title( $post_id ) ) );
		printf( '<p><a href="%s">← Danh sách chờ duyệt</a> · <a href="%s" target="_blank" rel="noopener">Xem bài trên website</a> · <a href="%s">Mọi lượt của bài</a></p>', esc_url( self::url() ), esc_url( (string) get_permalink( $post_id ) ), esc_url( add_query_arg( array( 'page' => SocialSync_Log_Page::SLUG, 'post_id' => $post_id ), admin_url( 'admin.php' ) ) ) );
		if ( ! $jobs ) {
			echo '<p><strong>Bài này không còn lượt nào chờ duyệt.</strong> Có thể đã có người duyệt hoặc từ chối.</p>';
			return;
		}
		$first   = $jobs[0];
		$content = json_decode( (string) $first['content'], true );
		$content = is_array( $content ) ? $content : array();
		?>
		<table class="widefat" style="max-width:1000px;margin-bottom:16px"><tbody>
			<tr><th style="width:200px">Phân loại</th><td>
				<strong><?php echo esc_html( SocialSync_Report::CLASS_LABELS[ $first['classification'] ?? '' ] ?? 'Chưa phân loại' ); ?></strong>
				<?php if ( ! empty( $content['classification_reason'] ) ) : ?> — <?php echo esc_html( (string) $content['classification_reason'] ); ?><?php endif; ?>
				<?php if ( ! empty( $content['sensitive'] ) ) : ?>
					<p style="color:#b32d2e;margin:4px 0 0"><strong>Nhóm nhạy cảm</strong> (trẻ em, phụ nữ mang thai hoặc nhắc tới nhân viên y tế): ở bản chính thức cần thêm pháp chế duyệt.</p>
				<?php endif; ?>
			</td></tr>
			<tr><th>Nguồn nội dung</th><td><?php echo esc_html( ( 'ai' === $first['content_source'] ? 'Claude viết' : 'Mẫu cố định' ) . ( $first['content_source_reason'] ? ' — ' . $first['content_source_reason'] : '' ) ); ?></td></tr>
		</tbody></table>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="socialsync_review">
			<input type="hidden" name="post_id" value="<?php echo (int) $post_id; ?>">
			<?php wp_nonce_field( 'socialsync_review_' . $post_id ); ?>
			<?php foreach ( $jobs as $job ) : ?>
				<?php self::render_channel( $job ); ?>
			<?php endforeach; ?>

			<p>
				<button type="submit" name="decision" value="approve" class="button button-primary button-large">Duyệt và đăng theo giờ đã chọn</button>
				<button type="submit" name="decision" value="approve_now" class="button button-large">Duyệt và đăng ngay</button>
			</p>
			<p style="margin-top:24px">
				<label>Lý do từ chối (bắt buộc khi từ chối)<br><input type="text" name="note" class="large-text" placeholder="Ví dụ: bài nội bộ, không đăng mạng xã hội"></label>
			</p>
			<p><button type="submit" name="decision" value="reject" class="button" onclick="return confirm('Từ chối: nội dung sẽ không được đăng lên các kênh. Tiếp tục?');">Từ chối toàn bộ</button></p>
		</form>
		<?php
	}

	private static function render_channel( array $job ): void {
		$id      = (int) $job['id'];
		$channel = (string) $job['channel'];
		$c       = json_decode( (string) $job['content'], true );
		$c       = is_array( $c ) ? $c : array();
		$when    = $job['scheduled_at'] ? get_date_from_gmt( (string) $job['scheduled_at'], 'Y-m-d\TH:i' ) : '';
		$name    = static fn( string $f ) => esc_attr( "jobs[{$id}][{$f}]" );
		$text    = 'facebook' === $channel ? (string) ( $c['message'] ?? '' ) : implode( ' ', array( $c['title'] ?? '', $c['description'] ?? '', $c['alt_text'] ?? '' ) );
		$hits    = preg_match_all( self::BANNED, str_replace( (string) SocialSync_Settings::get( 'product_disclaimer' ), ' ', $text ), $m ) ? array_unique( $m[0] ) : array();
		?>
		<div style="background:#fff;border:1px solid #dcdcde;padding:12px 16px;margin-bottom:16px;max-width:1000px">
			<h2 style="margin-top:0">
				<label><input type="checkbox" name="<?php echo $name( 'enabled' ); ?>" value="1" checked> <?php echo esc_html( SocialSync_Settings::CHANNELS[ $channel ] ?? $channel ); ?></label>
				<span class="description" style="font-size:13px;font-weight:normal"> — bỏ chọn để không đăng kênh này (lượt sẽ chuyển sang Bị từ chối)</span>
			</h2>
			<?php if ( $hits ) : ?>
				<p style="color:#b32d2e"><strong>Cảnh báo:</strong> có cụm từ quảng cáo sức khỏe rủi ro: <?php echo esc_html( implode( ', ', $hits ) ); ?></p>
			<?php endif; ?>
			<?php if ( 'facebook' === $channel ) : ?>
				<p><label>Nội dung bài Facebook (gồm cả link và hashtag)<br>
					<textarea name="<?php echo $name( 'message' ); ?>" rows="12" class="large-text"><?php echo esc_textarea( (string) ( $c['message'] ?? '' ) ); ?></textarea></label></p>
			<?php else : ?>
				<p><label>Tiêu đề Pin (tối đa 100 ký tự)<br><input type="text" maxlength="100" name="<?php echo $name( 'title' ); ?>" class="large-text" value="<?php echo esc_attr( (string) ( $c['title'] ?? '' ) ); ?>"></label></p>
				<p><label>Mô tả Pin (tối đa 500 ký tự)<br><textarea maxlength="500" name="<?php echo $name( 'description' ); ?>" rows="5" class="large-text"><?php echo esc_textarea( (string) ( $c['description'] ?? '' ) ); ?></textarea></label></p>
				<p><label>Mô tả ảnh (alt text)<br><input type="text" maxlength="500" name="<?php echo $name( 'alt_text' ); ?>" class="large-text" value="<?php echo esc_attr( (string) ( $c['alt_text'] ?? '' ) ); ?>"></label></p>
				<p class="description">Board: <?php echo esc_html( (string) ( $c['board'] ?? SocialSync_Settings::get( 'default_board' ) ) ); ?> · Link: <?php echo esc_html( (string) ( $c['link'] ?? '' ) ); ?></p>
			<?php endif; ?>
			<p><label>Giờ đăng (giờ website) <input type="datetime-local" name="<?php echo $name( 'at' ); ?>" value="<?php echo esc_attr( $when ); ?>"></label>
				<span class="description">Giờ đề xuất do bộ xếp lịch chọn. Giờ đã qua thì đăng ngay.</span></p>
		</div>
		<?php
	}

	public static function handle(): void {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		check_admin_referer( 'socialsync_review_' . $post_id );
		if ( ! current_user_can( SocialSync_Log_Page::CAP ) ) {
			wp_die( 'Không có quyền.', 403 );
		}
		$decision = isset( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';
		$note     = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '';
		$form     = isset( $_POST['jobs'] ) && is_array( $_POST['jobs'] ) ? wp_unslash( $_POST['jobs'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$user     = get_current_user_id();
		$now      = SocialSync_DB::now();

		global $wpdb;
		$jobs = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SocialSync_DB::table() . " WHERE post_id = %d AND status = 'needs_review'", $post_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		SocialSync_Logger::info( 'review.submit', 'Người duyệt gửi quyết định', array( 'post_id' => $post_id, 'decision' => $decision, 'by_user' => $user, 'job_ids' => array_column( $jobs, 'id' ) ) );

		if ( ! $jobs ) {
			self::back( $post_id, 'error', 'Bài không còn lượt nào chờ duyệt (có thể người khác vừa duyệt).' );
		}

		if ( 'reject' === $decision ) {
			if ( '' === $note ) {
				self::back( $post_id, 'error', 'Cần ghi lý do khi từ chối.' );
			}
			$done = 0;
			foreach ( $jobs as $job ) {
				$done += SocialSync_DB::transition( (int) $job['id'], 'needs_review', array( 'status' => 'rejected', 'review_note' => $note, 'reviewed_by' => $user, 'reviewed_at' => $now, 'error' => 'Bị từ chối: ' . $note ) ) ? 1 : 0;
			}
			SocialSync_Logger::info( 'review.rejected', 'Từ chối nội dung', array( 'post_id' => $post_id, 'note' => $note, 'rejected' => $done ) );
			self::back( 0, 'success', sprintf( 'Đã từ chối %d lượt của bài #%d.', $done, $post_id ) );
		}

		if ( ! in_array( $decision, array( 'approve', 'approve_now' ), true ) ) {
			self::back( $post_id, 'error', 'Thao tác không hợp lệ.' );
		}

		// 1. Kiểm tra dữ liệu form trước khi đổi bất kỳ lượt nào.
		$plans  = array();
		$errors = array();
		foreach ( $jobs as $job ) {
			$id      = (int) $job['id'];
			$f       = is_array( $form[ $id ] ?? null ) ? $form[ $id ] : array();
			$content = json_decode( (string) $job['content'], true );
			$content = is_array( $content ) ? $content : array();
			$before  = $content;
			if ( empty( $f['enabled'] ) ) {
				$plans[ $id ] = array( 'skip' => true );
				continue;
			}
			if ( 'facebook' === $job['channel'] ) {
				$content['message'] = trim( sanitize_textarea_field( (string) ( $f['message'] ?? '' ) ) );
				if ( '' === $content['message'] || mb_strlen( $content['message'] ) > 5000 ) {
					$errors[] = 'Nội dung Facebook rỗng hoặc dài quá 5.000 ký tự.';
				}
			} else {
				$content['title']       = trim( sanitize_text_field( (string) ( $f['title'] ?? '' ) ) );
				$content['description'] = trim( sanitize_textarea_field( (string) ( $f['description'] ?? '' ) ) );
				$content['alt_text']    = trim( sanitize_text_field( (string) ( $f['alt_text'] ?? '' ) ) );
				if ( '' === $content['title'] || mb_strlen( $content['title'] ) > 100 || mb_strlen( $content['description'] ) > 500 || mb_strlen( $content['alt_text'] ) > 500 ) {
					$errors[] = 'Nội dung Pinterest sai độ dài (tiêu đề 1–100, mô tả ≤ 500, alt ≤ 500 ký tự).';
				}
			}
			$ts = time();
			if ( 'approve' === $decision && ! empty( $f['at'] ) ) {
				try {
					$ts = max( time(), ( new DateTimeImmutable( str_replace( 'T', ' ', (string) $f['at'] ), wp_timezone() ) )->getTimestamp() );
				} catch ( Exception $e ) {
					$errors[] = 'Giờ đăng không hợp lệ: ' . (string) $f['at'];
				}
			}
			$changed = array();
			foreach ( array( 'message', 'title', 'description', 'alt_text' ) as $k ) {
				if ( array_key_exists( $k, $content ) && ( $before[ $k ] ?? null ) !== $content[ $k ] ) {
					$changed[] = $k;
				}
			}
			$content['edited_fields'] = $changed;
			$plans[ $id ]             = array(
				'skip'    => false,
				'content' => $content,
				'ts'      => $ts,
				'changed' => $changed,
			);
		}
		if ( $errors ) {
			SocialSync_Logger::warn( 'review.invalid', 'Form duyệt không hợp lệ', array( 'post_id' => $post_id, 'errors' => $errors ) );
			self::back( $post_id, 'error', implode( ' ', array_unique( $errors ) ) );
		}
		if ( ! array_filter( $plans, static fn( $p ) => ! $p['skip'] ) ) {
			self::back( $post_id, 'error', 'Chưa chọn kênh nào để đăng. Muốn bỏ cả bài thì dùng Từ chối.' );
		}

		// 2. Chuyển trạng thái có điều kiện.
		$approved = array();
		foreach ( $jobs as $job ) {
			$id   = (int) $job['id'];
			$plan = $plans[ $id ];
			if ( $plan['skip'] ) {
				SocialSync_DB::transition( $id, 'needs_review', array( 'status' => 'rejected', 'review_note' => 'Người duyệt bỏ kênh này', 'reviewed_by' => $user, 'reviewed_at' => $now, 'error' => 'Người duyệt bỏ kênh này' ) );
				continue;
			}
			$ok = SocialSync_DB::transition(
				$id,
				'needs_review',
				array(
					'status'       => 'scheduled',
					'content'      => wp_json_encode( $plan['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
					'scheduled_at' => gmdate( 'Y-m-d H:i:s', $plan['ts'] ),
					'reviewed_by'  => $user,
					'reviewed_at'  => $now,
					'review_note'  => $plan['changed'] ? 'Người duyệt sửa: ' . implode( ', ', $plan['changed'] ) : 'Duyệt nguyên văn',
					'error'        => null,
				)
			);
			if ( $ok ) {
				$approved[] = $job + array( '_content' => $plan['content'], '_ts' => $plan['ts'] );
			}
			SocialSync_Logger::info( 'review.approved', $ok ? 'Duyệt lượt' : 'Bỏ qua: lượt vừa được người khác xử lý', array( 'job_id' => $id, 'post_id' => $post_id, 'channel' => $job['channel'], 'edited' => $plan['changed'], 'at' => SocialSync_Scheduler::fmt( $plan['ts'] ), 'mode' => $decision ) );
		}
		if ( ! $approved ) {
			self::back( 0, 'error', 'Không duyệt được lượt nào (có thể người khác vừa duyệt).' );
		}

		// 3. Gửi lệnh đăng sang n8n. Lỗi thì trả lượt về chờ duyệt để bấm lại.
		$result = self::send_to_n8n( $post_id, $approved );
		if ( ! $result['ok'] ) {
			foreach ( $approved as $job ) {
				SocialSync_DB::transition( (int) $job['id'], 'scheduled', array( 'status' => 'needs_review', 'error' => 'Không gửi được lệnh đăng sang n8n: ' . $result['error'] ) );
			}
			self::back( $post_id, 'error', 'Không gửi được lệnh đăng sang n8n, lượt đã trả về Chờ duyệt: ' . $result['error'] );
		}
		$labels = array_map( static fn( $j ) => ( SocialSync_Settings::CHANNELS[ $j['channel'] ] ?? $j['channel'] ) . ' lúc ' . SocialSync_Scheduler::fmt( (int) $j['_ts'] ), $approved );
		self::back( 0, 'success', 'Đã duyệt bài #' . $post_id . ': ' . implode( '; ', $labels ) . '. Xem kết quả ở Nhật ký đồng bộ.' );
	}

	/** @param array<int, array<string, mixed>> $approved */
	private static function send_to_n8n( int $post_id, array $approved ): array {
		$thumb = (int) get_post_thumbnail_id( $post_id );
		$img   = $thumb ? wp_get_attachment_image_src( $thumb, 'full' ) : false;
		$jobs  = array();
		foreach ( $approved as $job ) {
			$jobs[] = array(
				'job_id'                => (int) $job['id'],
				'channel'               => (string) $job['channel'],
				'scheduled_at'          => gmdate( 'c', (int) $job['_ts'] ),
				'content'               => $job['_content'],
				'content_source'        => $job['content_source'],
				'content_source_reason' => $job['content_source_reason'],
				'model'                 => $job['model'],
				'tokens_in'             => null === $job['tokens_in'] ? null : (int) $job['tokens_in'],
				'tokens_out'            => null === $job['tokens_out'] ? null : (int) $job['tokens_out'],
				'ai_ms'                 => null === $job['ai_ms'] ? null : (int) $job['ai_ms'],
				'classification'        => $job['classification'],
			);
		}
		$payload = array(
			'event'        => 'approved',
			'request_id'   => SocialSync_Logger::request_id(),
			'sent_at'      => SocialSync_Logger::now(),
			'post_id'      => $post_id,
			'post_type'    => (string) get_post_type( $post_id ),
			'title'        => html_entity_decode( (string) get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ),
			'source_url'   => (string) get_permalink( $post_id ),
			'image_url'    => is_array( $img ) ? (string) $img[0] : '',
			'approved_by'  => (string) wp_get_current_user()->display_name,
			'callback_url' => SocialSync_Settings::callback_url(),
			'jobs'         => $jobs,
		);
		return SocialSync_Dispatcher::send( $payload, array( 'post_id' => $post_id, 'job_ids' => array_column( $jobs, 'job_id' ) ), (string) SocialSync_Settings::get( 'approve_url' ) );
	}

	private static function back( int $post_id, string $type, string $message ): void {
		set_transient( 'socialsync_review_notice_' . get_current_user_id(), array( 'type' => $type, 'message' => $message ), 120 );
		wp_safe_redirect( self::url( $post_id ) );
		exit;
	}

	private static function render_notice(): void {
		$key    = 'socialsync_review_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		if ( ! is_array( $notice ) ) {
			return;
		}
		delete_transient( $key );
		printf( '<div class="notice notice-%s"><p>%s</p></div>', 'success' === $notice['type'] ? 'success' : 'error', esc_html( (string) $notice['message'] ) );
	}
}
