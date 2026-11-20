<?php
/**
 * Duyệt nội dung trước khi đăng. Giao diện nằm trong Trung tâm SocialSync (class-socialsync-hub.php);
 * file này giữ logic duyệt/từ chối và thẻ duyệt từng kênh.
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

	/**
	 * Các cụm từ quảng cáo sức khỏe rủi ro: cảnh báo người duyệt (giống bộ lọc trong n8n).
	 * BANNED_JS là cùng biểu thức, không có dấu phân cách, để assets/hub.js tô cụm từ ngay khi người duyệt gõ.
	 */
	public const BANNED_JS = '(chữa\s+(khỏi|bệnh|trị|dứt)|điều\s+trị|đặc\s+trị|khỏi\s+hẳn|khỏi\s+bệnh|hết\s+bệnh|dứt\s+điểm|thay\s+thế\s+thuốc|100\s*%|cam\s+kết|thần\s+dược|số\s+1(?!\d)|tốt\s+nhất|duy\s+nhất|giảm\s+cân)';
	private const BANNED   = '/' . self::BANNED_JS . '/iu';

	/** Mã lý do bỏ qua: bài không có ảnh (Pinterest). */
	public const NO_IMAGE_CODE = 'no_image';

	public static function init(): void {
		add_action( 'admin_post_socialsync_review', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_socialsync_schedule', array( __CLASS__, 'handle_schedule' ) );
	}

	/** Lượt đã lên lịch mà n8n đã "nhận" để đăng (đang đăng): không đổi giờ / hủy được nữa. */
	public static function is_claimed( array $job ): bool {
		return 0 === strpos( (string) ( $job['dispatch_token'] ?? '' ), 'go:' );
	}

	/**
	 * Đổi giờ / đăng ngay / hủy lịch một lượt ĐÃ DUYỆT (trạng thái scheduled).
	 * - Hủy: scheduled -> needs_review, giữ nguyên nội dung đã duyệt (không gọi AI). Lần chạy n8n đang chờ sẽ hỏi lại
	 *   WordPress trước khi đăng, thấy lượt không còn scheduled nên bỏ.
	 * - Đổi giờ / đăng ngay: mã lệnh mới + gửi lại lệnh đăng sang n8n. Lần chạy cũ thức dậy thấy mã lệnh không khớp nên bỏ.
	 * Mọi thay đổi là UPDATE có điều kiện theo mã lệnh đã đọc: nếu n8n vừa nhận lượt (đang đăng) thì thao tác bị từ chối.
	 */
	public static function handle_schedule(): void {
		$job_id = isset( $_POST['job_id'] ) ? absint( $_POST['job_id'] ) : 0;
		check_admin_referer( 'socialsync_schedule_' . $job_id );
		if ( ! current_user_can( SocialSync_Log_Page::CAP ) ) {
			wp_die( 'Không có quyền.', 403 );
		}
		$op      = isset( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
		$at_raw  = isset( $_POST['at'] ) ? sanitize_text_field( wp_unslash( $_POST['at'] ) ) : '';
		$job     = SocialSync_DB::get_job( $job_id );
		$post_id = $job ? (int) $job['post_id'] : 0;
		$user    = wp_get_current_user();
		$label   = $job ? ( SocialSync_Settings::CHANNELS[ $job['channel'] ] ?? $job['channel'] ) : '';
		SocialSync_Logger::info( 'schedule.request', 'Người dùng đổi lịch lượt đã duyệt', array( 'job_id' => $job_id, 'op' => $op, 'at' => $at_raw, 'status' => $job['status'] ?? null, 'claimed' => $job ? self::is_claimed( $job ) : null, 'by_user' => $user->ID ) );

		if ( ! $job || 'scheduled' !== $job['status'] ) {
			self::back( $post_id, 'error', 'Lượt không còn ở trạng thái Đã lên lịch (có thể vừa đăng xong hoặc người khác vừa đổi). Xem trạng thái mới trên trang.' );
		}
		if ( self::is_claimed( $job ) ) {
			self::back( $post_id, 'error', $label . ': n8n đã nhận lượt và đang đăng, không đổi giờ hay hủy được nữa.' );
		}
		$token = empty( $job['dispatch_token'] ) ? null : (string) $job['dispatch_token'];

		if ( 'cancel' === $op ) {
			$ok = SocialSync_DB::transition_token(
				$job_id,
				'scheduled',
				$token,
				array(
					'status'         => 'needs_review',
					'dispatch_token' => null,
					'error'          => null,
					// Ghi người + giờ hủy vào reviewed_* để dòng thời gian hiện đúng lúc hủy (duyệt lại sẽ ghi đè).
					'reviewed_by'    => $user->ID,
					'reviewed_at'    => SocialSync_DB::now(),
					'review_note'    => sprintf( 'Hủy lịch bởi %s lúc %s', $user->display_name, wp_date( 'H:i d/m' ) ),
				)
			);
			if ( ! $ok ) {
				self::back( $post_id, 'error', $label . ': không hủy được vì lượt vừa đổi trạng thái (có thể n8n vừa nhận lượt để đăng).' );
			}
			$kept = SocialSync_Settings::approval_required() ? 'nội dung đã duyệt được giữ nguyên' : 'nội dung AI đã viết được giữ nguyên';
			self::back( $post_id, 'success', $label . ': đã hủy lịch. Lượt trở về Chờ duyệt, ' . $kept . '; duyệt khi muốn đăng.' );
		}

		if ( ! in_array( $op, array( 'move', 'now' ), true ) ) {
			self::back( $post_id, 'error', 'Thao tác không hợp lệ.' );
		}
		$post  = get_post( $post_id );
		$unpub = $post ? SocialSync_Log_Page::unpublished_reason( $post ) : 'Bài gốc đã bị xoá';
		if ( null !== $unpub ) {
			self::back( $post_id, 'error', $unpub . '.' );
		}
		if ( 'pinterest' === $job['channel'] && ! get_post_thumbnail_id( $post_id ) ) {
			self::back( $post_id, 'error', 'Bài không có ảnh đại diện nên không đăng được Pin. Thêm ảnh hoặc Hủy lịch.' );
		}
		$ts = time();
		if ( 'move' === $op ) {
			try {
				$ts = ( new DateTimeImmutable( str_replace( 'T', ' ', $at_raw ), wp_timezone() ) )->getTimestamp();
			} catch ( Exception $e ) {
				self::back( $post_id, 'error', 'Giờ đăng không hợp lệ: ' . $at_raw );
			}
			if ( '' === $at_raw || $ts < time() + 60 ) {
				self::back( $post_id, 'error', 'Giờ mới phải sau hiện tại ít nhất 1 phút. Muốn đăng luôn thì bấm “Đăng ngay”.' );
			}
		}
		$old_at    = (string) $job['scheduled_at'];
		$new_token = SocialSync_DB::new_token();
		$note      = trim( (string) $job['review_note'] . ' · ' . sprintf( '%s bởi %s lúc %s', 'now' === $op ? 'Chuyển sang đăng ngay' : 'Đổi giờ sang ' . wp_date( 'H:i d/m', $ts ), $user->display_name, wp_date( 'H:i d/m' ) ), ' ·' );
		$ok        = SocialSync_DB::transition_token( $job_id, 'scheduled', $token, array( 'scheduled_at' => gmdate( 'Y-m-d H:i:s', $ts ), 'dispatch_token' => $new_token, 'review_note' => $note ) );
		if ( ! $ok ) {
			self::back( $post_id, 'error', $label . ': không đổi được vì lượt vừa đổi trạng thái (có thể n8n vừa nhận lượt để đăng).' );
		}
		$content = json_decode( (string) $job['content'], true );
		$result  = self::send_to_n8n( $post_id, array( $job + array( '_content' => is_array( $content ) ? $content : array(), '_ts' => $ts, '_token' => $new_token ) ) );
		if ( ! $result['ok'] ) {
			// Trả lại giờ + mã lệnh cũ: lần chạy n8n cũ vẫn hợp lệ, lượt đăng theo giờ cũ như trước.
			SocialSync_DB::transition_token( $job_id, 'scheduled', $new_token, array( 'scheduled_at' => $old_at, 'dispatch_token' => $token, 'review_note' => $job['review_note'] ) );
			self::back( $post_id, 'error', 'Không gửi được lệnh sang n8n nên giữ giờ cũ: ' . $result['error'] );
		}
		$warn = '';
		$hm   = wp_date( 'H:i', $ts );
		if ( $hm < (string) SocialSync_Settings::get( 'window_start' ) || $hm > (string) SocialSync_Settings::get( 'window_end' ) ) {
			$warn = ' Lưu ý: giờ này nằm ngoài khung đăng ' . SocialSync_Settings::get( 'window_start' ) . '–' . SocialSync_Settings::get( 'window_end' ) . ' (vẫn đăng vì người duyệt chọn tay).';
		}
		SocialSync_Logger::info( 'schedule.moved', 'Đổi giờ lượt đã duyệt', array( 'job_id' => $job_id, 'from' => $old_at, 'to' => gmdate( 'Y-m-d H:i:s', $ts ), 'op' => $op, 'outside_window' => '' !== $warn ) );
		self::back( $post_id, 'success', $label . ( 'now' === $op ? ': đã chuyển sang đăng ngay.' : ': đã đổi giờ đăng sang ' . wp_date( 'H:i d/m/Y', $ts ) . '.' ) . $warn );
	}

	/**
	 * Cụm từ rủi ro trong nội dung một lượt (bỏ qua câu khuyến cáo do hệ thống gắn).
	 *
	 * @return string[]
	 */
	public static function banned_hits( array $job ): array {
		$c    = json_decode( (string) $job['content'], true );
		$c    = is_array( $c ) ? $c : array();
		$text = 'facebook' === $job['channel'] ? (string) ( $c['message'] ?? '' ) : implode( ' ', array( $c['title'] ?? '', $c['description'] ?? '', $c['alt_text'] ?? '' ) );
		return preg_match_all( self::BANNED, str_replace( (string) SocialSync_Settings::get( 'product_disclaimer' ), ' ', $text ), $m ) ? array_values( array_unique( $m[0] ) ) : array();
	}

	/**
	 * Bài chờ duyệt kế tiếp (cũ nhất trước), trừ bài vừa xử lý. 0 nếu hết.
	 */
	public static function next_pending( int $exclude_post_id ): int {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT post_id FROM ' . SocialSync_DB::table() . " WHERE status = 'needs_review' AND post_id <> %d ORDER BY id ASC LIMIT 1", $exclude_post_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function skip_code( array $job ): string {
		return false !== mb_stripos( (string) $job['error'], 'không có ảnh' ) ? self::NO_IMAGE_CODE : 'other';
	}

	public static function pending_count(): int {
		global $wpdb;
		if ( ! SocialSync_DB::table_exists() ) {
			return 0;
		}
		return (int) $wpdb->get_var( 'SELECT COUNT(DISTINCT post_id) FROM ' . SocialSync_DB::table() . " WHERE status = 'needs_review'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/** Màn Chờ duyệt riêng đã gộp vào Trung tâm SocialSync. */
	public static function url( int $post_id = 0 ): string {
		return SocialSync_Hub::url( $post_id, $post_id ? '' : 'review' );
	}

	/**
	 * Thẻ duyệt một kênh (dùng trong Trung tâm SocialSync). Tên trường form giữ nguyên jobs[{id}][...] để handle() không đổi.
	 * Ô có data-ss-max: hub.js đếm ký tự; ô có data-ss-scan: hub.js tô cụm từ rủi ro khi gõ.
	 */
	public static function render_channel( array $job, string $thumb_url, ?array $draft = null ): void {
		$id      = (int) $job['id'];
		$channel = (string) $job['channel'];
		// Pinterest bắt buộc có ảnh: bài không có ảnh thì bỏ chọn sẵn kênh này và cảnh báo.
		$no_pin = 'pinterest' === $channel && '' === $thumb_url;
		$c      = json_decode( (string) $job['content'], true );
		$c      = is_array( $c ) ? $c : array();
		$when   = $job['scheduled_at'] ? get_date_from_gmt( (string) $job['scheduled_at'], 'Y-m-d\TH:i' ) : '';
		$d      = is_array( $draft['jobs'][ $id ] ?? null ) ? $draft['jobs'][ $id ] : null;
		if ( $d ) {
			// Điền lại phần người duyệt đã gõ trước khi bị trả lỗi.
			foreach ( array( 'message', 'title', 'description', 'alt_text' ) as $k ) {
				if ( isset( $d[ $k ] ) ) {
					$c[ $k ] = (string) $d[ $k ];
				}
			}
			$when = (string) ( $d['at'] ?? '' );
		}
		$checked = $d ? ! empty( $d['enabled'] ) : ! $no_pin;
		$name   = static fn( string $f ) => esc_attr( "jobs[{$id}][{$f}]" );
		$hits   = self::banned_hits( $job );
		?>
		<article class="ss-card ss-st-needs_review<?php echo $checked ? '' : ' is-off'; ?>" data-ss-card data-ss-channel="<?php echo esc_attr( $channel ); ?>"<?php echo 'pinterest' === $channel && '' === $thumb_url ? ' data-ss-noimage="1"' : ''; ?>>
			<header>
				<label class="ss-toggle"><input type="checkbox" name="<?php echo $name( 'enabled' ); ?>" value="1"<?php echo $checked ? ' checked' : ''; ?> data-ss-enable> <strong><?php echo esc_html( SocialSync_Settings::CHANNELS[ $channel ] ?? $channel ); ?></strong></label>
				<?php echo SocialSync_Hub::chip( $job ); // phpcs:ignore WordPress.Security.EscapeOutput -- đã escape trong chip(). ?>
			</header>
			<p class="ss-meta ss-off-hint">Bỏ chọn = không đăng kênh này (lượt chuyển sang Đã từ chối).</p>
			<?php if ( $no_pin ) : ?>
				<p class="ss-alert ss-alert-red"><strong>Không đăng được Pin:</strong> bài không có ảnh đại diện, mà mỗi Pin bắt buộc có ảnh. Kênh đã được bỏ chọn sẵn. Thêm ảnh đại diện cho bài rồi tải lại trang để đăng Pin.</p>
			<?php endif; ?>
			<p class="ss-alert ss-alert-warn" data-ss-banned<?php echo $hits ? '' : ' hidden'; ?>>⚠ Cụm từ quảng cáo sức khỏe rủi ro: <span><?php echo esc_html( implode( ', ', $hits ) ); ?></span></p>
			<?php if ( 'facebook' === $channel ) : ?>
				<label class="ss-field">Nội dung bài Facebook <span class="ss-meta">(gồm link và hashtag)</span>
					<textarea name="<?php echo $name( 'message' ); ?>" rows="12" data-ss-max="5000" data-ss-scan><?php echo esc_textarea( (string) ( $c['message'] ?? '' ) ); ?></textarea></label>
			<?php else : ?>
				<label class="ss-field">Tiêu đề Pin
					<input type="text" maxlength="100" name="<?php echo $name( 'title' ); ?>" value="<?php echo esc_attr( (string) ( $c['title'] ?? '' ) ); ?>" data-ss-max="100" data-ss-scan></label>
				<label class="ss-field">Mô tả Pin
					<textarea maxlength="500" name="<?php echo $name( 'description' ); ?>" rows="5" data-ss-max="500" data-ss-scan><?php echo esc_textarea( (string) ( $c['description'] ?? '' ) ); ?></textarea></label>
				<label class="ss-field">Mô tả ảnh (alt text)
					<input type="text" maxlength="500" name="<?php echo $name( 'alt_text' ); ?>" value="<?php echo esc_attr( (string) ( $c['alt_text'] ?? '' ) ); ?>" data-ss-max="500" data-ss-scan></label>
				<p class="ss-meta">Board: <?php echo esc_html( (string) ( $c['board'] ?? SocialSync_Settings::get( 'default_board' ) ) ); ?> · Ảnh Pin = ảnh đại diện của bài · Link: <?php echo esc_html( (string) ( $c['link'] ?? '' ) ); ?></p>
			<?php endif; ?>
			<label class="ss-field ss-when">Giờ đăng <span class="ss-meta">(giờ website; do bộ xếp lịch đề xuất, giờ đã qua thì đăng ngay)</span>
				<input type="datetime-local" name="<?php echo $name( 'at' ); ?>" value="<?php echo esc_attr( $when ); ?>"></label>
			<?php if ( $job['scheduled_at'] && strtotime( (string) $job['scheduled_at'] . ' UTC' ) < time() ) : ?>
				<p class="ss-alert ss-alert-warn">Giờ đề xuất đã qua: bấm duyệt thì kênh này đăng ngay. Muốn đăng sau, chọn giờ mới rồi bấm “Duyệt và đăng theo giờ đã chọn”.</p>
			<?php endif; ?>
		</article>
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
			self::back( $post_id, 'error', 'Bài không còn lượt nào chờ duyệt' . self::handled_by( $post_id ) . '.' );
		}

		// Bài đã vào thùng rác / về nháp / riêng tư: link bài không mở được nên không cho duyệt đăng (từ chối vẫn được).
		$post  = get_post( $post_id );
		$unpub = $post ? SocialSync_Log_Page::unpublished_reason( $post ) : 'Bài gốc đã bị xoá';
		if ( null !== $unpub && 'reject' !== $decision ) {
			SocialSync_Logger::warn( 'review.unpublished', 'Chặn duyệt: bài không còn xuất bản', array( 'post_id' => $post_id, 'post_status' => $post ? $post->post_status : null ) );
			self::back( $post_id, 'error', $unpub . '. Xuất bản lại bài rồi duyệt, hoặc Từ chối.' );
		}

		if ( 'reject' === $decision ) {
			if ( '' === $note ) {
				self::save_draft( $post_id, $form, $note );
				self::back( $post_id, 'error', 'Cần ghi lý do khi từ chối.' );
			}
			$done = 0;
			foreach ( $jobs as $job ) {
				$done += SocialSync_DB::transition( (int) $job['id'], 'needs_review', array( 'status' => 'rejected', 'review_note' => $note, 'reviewed_by' => $user, 'reviewed_at' => $now, 'error' => 'Bị từ chối: ' . $note ) ) ? 1 : 0;
			}
			SocialSync_Logger::info( 'review.rejected', 'Từ chối nội dung', array( 'post_id' => $post_id, 'note' => $note, 'rejected' => $done ) );
			self::back( $post_id, 'success', sprintf( 'Đã từ chối %d lượt của bài “%s”.', $done, html_entity_decode( (string) get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ) ), true );
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
				$content['message'] = self::clean_text( (string) ( $f['message'] ?? '' ), true );
				if ( '' === $content['message'] || mb_strlen( $content['message'] ) > 5000 ) {
					$errors[] = 'Nội dung Facebook rỗng hoặc dài quá 5.000 ký tự.';
				}
			} else {
				$content['title']       = self::clean_text( (string) ( $f['title'] ?? '' ), false );
				$content['description'] = self::clean_text( (string) ( $f['description'] ?? '' ), true );
				$content['alt_text']    = self::clean_text( (string) ( $f['alt_text'] ?? '' ), false );
				if ( ! get_post_thumbnail_id( $post_id ) ) {
					SocialSync_Logger::warn( 'review.pinterest_no_image', 'Người duyệt chọn đăng Pin nhưng bài không có ảnh đại diện', array( 'post_id' => $post_id, 'job_id' => $id ) );
					$errors[] = 'Bài không có ảnh đại diện nên không đăng được Pin. Bỏ chọn Pinterest, hoặc thêm ảnh đại diện cho bài rồi duyệt lại.';
				}
				if ( '' === $content['title'] || mb_strlen( $content['title'] ) > 100 || mb_strlen( $content['description'] ) > 500 || mb_strlen( $content['alt_text'] ) > 500 ) {
					$errors[] = 'Nội dung Pinterest sai độ dài (tiêu đề 1–100, mô tả ≤ 500, alt ≤ 500 ký tự).';
				}
			}
			$ts = time();
			if ( 'approve' === $decision && empty( $f['at'] ) ) {
				// Bấm "theo giờ đã chọn" mà ô giờ trống: không âm thầm đăng ngay.
				$errors[] = 'Chọn giờ đăng cho ' . ( SocialSync_Settings::CHANNELS[ $job['channel'] ] ?? $job['channel'] ) . ', hoặc bấm “Duyệt và đăng ngay”.';
			} elseif ( 'approve' === $decision ) {
				try {
					$ts = max( time(), ( new DateTimeImmutable( str_replace( 'T', ' ', (string) $f['at'] ), wp_timezone() ) )->getTimestamp() );
				} catch ( Exception $e ) {
					$errors[] = 'Giờ đăng không hợp lệ: ' . (string) $f['at'];
				}
			}
			$changed = array();
			foreach ( array( 'message', 'title', 'description', 'alt_text' ) as $k ) {
				// So với bản gốc đã chuẩn hoá xuống dòng + khoảng trắng đầu/cuối, để chỉ ghi "đã sửa" khi người duyệt sửa thật.
				$orig = isset( $before[ $k ] ) ? trim( str_replace( array( "\r\n", "\r" ), "\n", (string) $before[ $k ] ) ) : null;
				if ( array_key_exists( $k, $content ) && $orig !== $content[ $k ] ) {
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
			self::save_draft( $post_id, $form, $note );
			self::back( $post_id, 'error', implode( ' ', array_unique( $errors ) ) . ' Phần bạn vừa sửa vẫn được giữ trên form.' );
		}
		if ( ! array_filter( $plans, static fn( $p ) => ! $p['skip'] ) ) {
			self::save_draft( $post_id, $form, $note );
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
			$token = SocialSync_DB::new_token();
			$ok    = SocialSync_DB::transition(
				$id,
				'needs_review',
				array(
					'status'         => 'scheduled',
					'dispatch_token' => $token,
					'content'      => wp_json_encode( $plan['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
					'scheduled_at' => gmdate( 'Y-m-d H:i:s', $plan['ts'] ),
					'reviewed_by'  => $user,
					'reviewed_at'  => $now,
					'review_note'  => $plan['changed'] ? 'Người duyệt sửa: ' . implode( ', ', $plan['changed'] ) : 'Duyệt nguyên văn',
					'error'        => null,
				)
			);
			if ( $ok ) {
				$approved[] = $job + array( '_content' => $plan['content'], '_ts' => $plan['ts'], '_token' => $token );
			}
			SocialSync_Logger::info( 'review.approved', $ok ? 'Duyệt lượt' : 'Bỏ qua: lượt vừa được người khác xử lý', array( 'job_id' => $id, 'post_id' => $post_id, 'channel' => $job['channel'], 'edited' => $plan['changed'], 'at' => SocialSync_Scheduler::fmt( $plan['ts'] ), 'mode' => $decision ) );
		}
		if ( ! $approved ) {
			self::back( $post_id, 'error', 'Không duyệt được lượt nào' . self::handled_by( $post_id ) . '.' );
		}

		// 3. Gửi lệnh đăng sang n8n. Lỗi thì trả lượt về chờ duyệt để bấm lại.
		$result = self::send_to_n8n( $post_id, $approved );
		if ( ! $result['ok'] ) {
			foreach ( $approved as $job ) {
				SocialSync_DB::transition( (int) $job['id'], 'scheduled', array( 'status' => 'needs_review', 'dispatch_token' => null, 'error' => 'Không gửi được lệnh đăng sang n8n: ' . $result['error'] ) );
			}
			self::back( $post_id, 'error', 'Không gửi được lệnh đăng sang n8n, lượt đã trả về Chờ duyệt: ' . $result['error'] );
		}
		$labels = array_map( static fn( $j ) => ( SocialSync_Settings::CHANNELS[ $j['channel'] ] ?? $j['channel'] ) . ' lúc ' . SocialSync_Scheduler::fmt( (int) $j['_ts'] ), $approved );
		self::back( $post_id, 'success', 'Đã duyệt bài “' . html_entity_decode( (string) get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ) . '”: ' . implode( '; ', $labels ) . '. Kết quả hiện ở thẻ kênh của bài (trang tự cập nhật).', true );
	}

	/**
	 * Làm sạch chữ người duyệt gửi lên. Trình duyệt gửi xuống dòng dạng \r\n còn nội dung AI lưu dạng \n: không chuẩn hoá thì
	 * mọi lần duyệt đều bị ghi "Người duyệt sửa: message" (sai bằng chứng duyệt) và bài đăng lẫn \r (lỗi L8, 20/11).
	 */
	private static function clean_text( string $raw, bool $multiline ): string {
		$raw = str_replace( array( "\r\n", "\r" ), "\n", $raw );
		return trim( $multiline ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw ) );
	}

	/** Form duyệt bị trả lại vì lỗi: giữ phần người duyệt vừa gõ (10 phút) để điền lại, không bắt gõ lại từ đầu. */
	private static function save_draft( int $post_id, array $form, string $note ): void {
		set_transient( 'socialsync_review_draft_' . get_current_user_id() . '_' . $post_id, array( 'jobs' => $form, 'note' => $note ), 600 );
		SocialSync_Logger::debug( 'review.draft_saved', 'Giữ bản sửa của người duyệt sau lỗi kiểm tra', array( 'post_id' => $post_id, 'job_ids' => array_keys( $form ), 'has_note' => '' !== $note ) );
	}

	/** Lấy (và xoá) bản sửa đã giữ. @return array{jobs: array, note: string}|null */
	public static function take_draft( int $post_id ): ?array {
		$key   = 'socialsync_review_draft_' . get_current_user_id() . '_' . $post_id;
		$draft = get_transient( $key );
		if ( ! is_array( $draft ) ) {
			return null;
		}
		delete_transient( $key );
		SocialSync_Logger::debug( 'review.draft_restored', 'Điền lại bản sửa của người duyệt', array( 'post_id' => $post_id ) );
		return array( 'jobs' => (array) ( $draft['jobs'] ?? array() ), 'note' => (string) ( $draft['note'] ?? '' ) );
	}

	/** " — Lan đã duyệt lúc 20:03" nếu bài vừa được người khác xử lý (để người đến sau biết ai, lúc nào). */
	private static function handled_by( int $post_id ): string {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT status, reviewed_by, reviewed_at FROM ' . SocialSync_DB::table() . ' WHERE post_id = %d AND reviewed_at IS NOT NULL ORDER BY reviewed_at DESC LIMIT 1', $post_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $row ) {
			return ' (có thể người khác vừa xử lý)';
		}
		$who = get_userdata( (int) $row['reviewed_by'] );
		return sprintf( ': %s đã %s lúc %s', $who ? $who->display_name : '#' . $row['reviewed_by'], 'rejected' === $row['status'] ? 'từ chối' : 'duyệt', get_date_from_gmt( (string) $row['reviewed_at'], 'H:i d/m' ) );
	}

	/** @param array<int, array<string, mixed>> $approved */
	public static function send_to_n8n( int $post_id, array $approved ): array {
		$thumb = (int) get_post_thumbnail_id( $post_id );
		$img   = $thumb ? wp_get_attachment_image_src( $thumb, 'full' ) : false;
		$jobs  = array();
		foreach ( $approved as $job ) {
			$jobs[] = array(
				'job_id'                => (int) $job['id'],
				'channel'               => (string) $job['channel'],
				'scheduled_at'          => gmdate( 'c', (int) $job['_ts'] ),
				// n8n gửi lại mã này khi hỏi "còn đăng không?" sau lúc chờ; đổi giờ / hủy lịch làm mã cũ hết hiệu lực.
				'dispatch_token'        => $job['_token'] ?? null,
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

	/**
	 * Quay về Trung tâm. $done = vừa duyệt/từ chối xong: nếu người duyệt để tick "mở bài tiếp theo" thì chọn
	 * bài chờ duyệt kế tiếp (cũ nhất trước), không thì ở lại bài vừa xử lý để xem kết quả.
	 */
	private static function back( int $post_id, string $type, string $message, bool $done = false ): void {
		$target = $post_id;
		$tab    = '';
		$link   = null;
		if ( $done ) {
			$want_next = ! empty( $_POST['next'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- đã check_admin_referer trong handle().
			$next      = $want_next ? self::next_pending( $post_id ) : 0;
			if ( $next ) {
				$target   = $next;
				$tab      = 'review';
				$message .= ' Đang mở bài chờ duyệt tiếp theo.';
				$link     = array( 'url' => SocialSync_Hub::url( $post_id ) . '#ss-detail', 'text' => 'Xem lại bài vừa xử lý' );
			} elseif ( $want_next ) {
				$message .= ' Đã hết bài chờ duyệt.';
			}
			SocialSync_Logger::debug( 'review.next', 'Chọn bài hiển thị sau khi xử lý', array( 'post_id' => $post_id, 'want_next' => $want_next, 'next_post_id' => $next, 'target' => $target ) );
		}
		set_transient( 'socialsync_review_notice_' . get_current_user_id(), array( 'type' => $type, 'message' => $message, 'link' => $link ), 120 );
		wp_safe_redirect( SocialSync_Hub::url( $target, $tab ) . '#ss-detail' );
		exit;
	}

	public static function render_notice(): void {
		$key    = 'socialsync_review_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		if ( ! is_array( $notice ) ) {
			return;
		}
		delete_transient( $key );
		$link = is_array( $notice['link'] ?? null ) ? ' <a href="' . esc_url( (string) $notice['link']['url'] ) . '">' . esc_html( (string) $notice['link']['text'] ) . ' →</a>' : '';
		printf( '<div class="notice notice-%s"><p>%s%s</p></div>', 'success' === $notice['type'] ? 'success' : 'error', esc_html( (string) $notice['message'] ), $link ); // phpcs:ignore WordPress.Security.EscapeOutput -- $link đã escape.
	}
}
