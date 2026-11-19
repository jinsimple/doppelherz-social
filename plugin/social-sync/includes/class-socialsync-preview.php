<?php
/**
 * "SocialSync → Chạy thử doppelherz.vn": đưa bài THẬT của website khách qua đúng luồng AI của n8n,
 * ở chế độ chạy thử: Claude phân loại + viết nội dung + bộ kiểm tra chạy như thật, nhưng KHÔNG đăng gì.
 *
 * - Đọc bài qua REST API công khai (chỉ đọc, 1 request cho cả lô, không cần đăng nhập vào website khách).
 * - Mỗi bài gửi sang n8n một gói "post_published" có dry_run = true; n8n gửi kết quả về /previews/{id}.
 * - Mục đích: cho khách thấy hệ thống xử lý nội dung của chính họ ra sao trước khi kết nối kênh thật.
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_Preview {

	public const SLUG = 'socialsync-preview';

	public static function init(): void {
		add_action( 'admin_post_socialsync_preview_run', array( __CLASS__, 'handle_run' ) );
	}

	public static function render(): void {
		global $wpdb;
		if ( ! current_user_can( SocialSync_Log_Page::CAP ) ) {
			return;
		}
		$t      = SocialSync_DB::previews_table();
		$batch  = isset( $_GET['batch'] ) ? sanitize_key( wp_unslash( $_GET['batch'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$latest = $batch ? $batch : (string) $wpdb->get_var( "SELECT batch_id FROM {$t} ORDER BY id DESC LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows   = $latest ? (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE batch_id = %s ORDER BY id ASC", $latest ), ARRAY_A ) : array(); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$notice = get_transient( 'socialsync_preview_notice_' . get_current_user_id() );
		if ( $notice ) {
			delete_transient( 'socialsync_preview_notice_' . get_current_user_id() );
		}
		$source  = (string) SocialSync_Settings::get( 'preview_source' );
		$pending = count( array_filter( $rows, static fn( $r ) => 'pending' === $r['status'] ) );
		?>
		<div class="wrap">
			<h1>Chạy thử với bài thật của <?php echo esc_html( (string) wp_parse_url( $source, PHP_URL_HOST ) ); ?></h1>
			<?php if ( $notice ) : ?><div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?>"><p><?php echo esc_html( $notice['message'] ); ?></p></div><?php endif; ?>
			<p>Lấy các bài mới nhất từ website nguồn, cho Claude phân loại và viết nội dung Facebook và Pinterest, chạy bộ kiểm tra từ ngữ, <strong>nhưng không đăng gì lên mạng xã hội</strong>. Đây đúng là luồng n8n dùng cho bài thật, chỉ dừng trước bước đăng.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="socialsync_preview_run">
				<?php wp_nonce_field( 'socialsync_preview_run' ); ?>
				<label>Số bài <select name="count"><?php foreach ( array( 3, 5, 10 ) as $n ) : ?><option value="<?php echo (int) $n; ?>" <?php selected( 5, $n ); ?>><?php echo (int) $n; ?></option><?php endforeach; ?></select></label>
				<label style="margin-left:12px">Bỏ qua <input type="number" name="offset" value="0" min="0" max="200" style="width:70px"> bài mới nhất</label>
				<?php submit_button( 'Chạy thử', 'primary', 'submit', false, array( 'style' => 'margin-left:12px' ) ); ?>
				<span class="description">Mỗi bài tốn 1 lần gọi Claude (khoảng $0,05). Kết quả có sau 20–40 giây.</span>
			</form>

			<?php if ( $rows ) : ?>
				<h2>Lô <?php echo esc_html( $latest ); ?> (<?php echo count( $rows ); ?> bài<?php echo $pending ? ', ' . (int) $pending . ' bài đang xử lý — tải lại trang sau ít giây' : ''; ?>)</h2>
				<?php foreach ( $rows as $r ) : ?>
					<?php self::render_row( $r ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_row( array $r ): void {
		$res  = json_decode( (string) $r['result'], true );
		$res  = is_array( $res ) ? $res : array();
		$cls  = (string) ( $r['classification'] ?? '' );
		$fb   = $res['facebook'] ?? null;
		$pin  = $res['pinterest'] ?? null;
		$prob = (array) ( $res['problems'] ?? array() );
		$colors = array( 'E' => '#007017', 'P' => '#2271b1', 'N' => '#996800', 'X' => '#b32d2e' );
		?>
		<div style="background:#fff;border:1px solid #dcdcde;padding:12px 16px;margin:12px 0;max-width:1100px">
			<h3 style="margin:0 0 6px">
				<a href="<?php echo esc_url( (string) $r['source_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( (string) $r['title'] ); ?></a>
			</h3>
			<p class="description" style="margin:0 0 8px">Danh mục: <?php echo esc_html( (string) $r['categories'] ); ?></p>
			<?php if ( 'pending' === $r['status'] ) : ?>
				<p><em>Đang xử lý…</em></p>
			<?php elseif ( 'failed' === $r['status'] ) : ?>
				<p style="color:#b32d2e">Lỗi: <?php echo esc_html( (string) $r['error'] ); ?></p>
			<?php else : ?>
				<p>
					<span style="display:inline-block;padding:2px 10px;border-radius:10px;color:#fff;background:<?php echo esc_attr( $colors[ $cls ] ?? '#646970' ); ?>"><?php echo esc_html( SocialSync_Report::CLASS_LABELS[ $cls ] ?? 'Chưa rõ' ); ?></span>
					<?php if ( ! empty( $res['classification_reason'] ) ) : ?> <?php echo esc_html( (string) $res['classification_reason'] ); ?><?php endif; ?>
					<?php if ( ! empty( $res['sensitive'] ) ) : ?> <strong style="color:#b32d2e">· Nhóm nhạy cảm: cần pháp chế duyệt</strong><?php endif; ?>
				</p>
				<p class="description">Quyết định: <?php echo esc_html( (string) ( $res['decision_text'] ?? '' ) ); ?> · <?php echo esc_html( sprintf( '%s · %s token vào / %s ra · %s ms', $res['content_source'] ?? '?', $r['tokens_in'] ?? '?', $r['tokens_out'] ?? '?', $r['ai_ms'] ?? '?' ) ); ?></p>
				<?php if ( $prob ) : ?><p style="color:#b32d2e">Kiểm tra không đạt: <?php echo esc_html( implode( '; ', $prob ) ); ?></p><?php endif; ?>
				<div style="display:flex;gap:16px;flex-wrap:wrap">
					<?php if ( $fb ) : ?>
						<div style="flex:1;min-width:320px"><strong>Facebook</strong><?php echo ! empty( $fb['skip'] ) ? ' — ' . esc_html( (string) $fb['skip'] ) : ''; ?>
							<pre style="white-space:pre-wrap;background:#f6f7f7;padding:8px;max-height:360px;overflow:auto"><?php echo esc_html( (string) ( $fb['message'] ?? '' ) ); ?></pre></div>
					<?php endif; ?>
					<?php if ( $pin ) : ?>
						<div style="flex:1;min-width:320px"><strong>Pinterest</strong><?php echo ! empty( $pin['skip'] ) ? ' — ' . esc_html( (string) $pin['skip'] ) : ''; ?>
							<pre style="white-space:pre-wrap;background:#f6f7f7;padding:8px;max-height:360px;overflow:auto"><?php echo esc_html( "Tiêu đề: " . ( $pin['title'] ?? '' ) . "\n\nMô tả: " . ( $pin['description'] ?? '' ) . "\n\nAlt: " . ( $pin['alt_text'] ?? '' ) . "\n\nBoard: " . ( $pin['board'] ?? '' ) ); ?></pre></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function handle_run(): void {
		global $wpdb;
		check_admin_referer( 'socialsync_preview_run' );
		if ( ! current_user_can( SocialSync_Log_Page::CAP ) ) {
			wp_die( 'Không có quyền.', 403 );
		}
		$user   = get_current_user_id();
		$count  = isset( $_POST['count'] ) ? max( 1, min( 10, absint( $_POST['count'] ) ) ) : 5;
		$offset = isset( $_POST['offset'] ) ? max( 0, min( 200, absint( $_POST['offset'] ) ) ) : 0;
		$back   = add_query_arg( array( 'page' => self::SLUG ), admin_url( 'admin.php' ) );

		// Website nguồn là website thật của doanh nghiệp: không cho chạy dồn dập.
		if ( get_transient( 'socialsync_preview_lock' ) ) {
			self::notice( 'warning', 'Vừa có một lô chạy thử trong 2 phút qua. Đợi lô đó xong rồi chạy tiếp.' );
			wp_safe_redirect( $back );
			exit;
		}
		set_transient( 'socialsync_preview_lock', 1, 120 );

		$source = rtrim( (string) SocialSync_Settings::get( 'preview_source' ), '/' );
		$url    = $source . '/wp-json/wp/v2/posts?' . http_build_query(
			array(
				'per_page' => $count,
				'offset'   => $offset,
				'_embed'   => 'wp:featuredmedia,wp:term',
				'_fields'  => 'id,link,title,excerpt,content,_links,_embedded',
			)
		);
		$t0   = microtime( true );
		$resp = wp_remote_get( $url, array( 'timeout' => 20, 'user-agent' => 'SocialSync-preview/' . SOCIALSYNC_VERSION ) );
		$ms   = (int) round( ( microtime( true ) - $t0 ) * 1000 );
		$code = is_wp_error( $resp ) ? 0 : (int) wp_remote_retrieve_response_code( $resp );
		$list = is_wp_error( $resp ) ? null : json_decode( (string) wp_remote_retrieve_body( $resp ), true );
		SocialSync_Logger::info( 'preview.fetch', 'Đọc bài từ website nguồn', array( 'url' => $url, 'http_code' => $code, 'ms' => $ms, 'count' => is_array( $list ) ? count( $list ) : null, 'error' => is_wp_error( $resp ) ? $resp->get_error_message() : null, 'by_user' => $user ) );
		if ( ! is_array( $list ) || ! $list ) {
			self::notice( 'error', 'Không đọc được bài từ ' . $source . ' (HTTP ' . $code . ').' );
			wp_safe_redirect( $back );
			exit;
		}

		$batch = gmdate( 'ymdHis' ) . wp_rand( 10, 99 );
		$sent  = 0;
		foreach ( $list as $p ) {
			$emb   = $p['_embedded'] ?? array();
			$terms = array();
			foreach ( (array) ( $emb['wp:term'] ?? array() ) as $group ) {
				foreach ( (array) $group as $term ) {
					if ( 'category' === ( $term['taxonomy'] ?? '' ) ) {
						$terms[] = html_entity_decode( (string) $term['name'], ENT_QUOTES, 'UTF-8' );
					}
				}
			}
			$img   = (string) ( $emb['wp:featuredmedia'][0]['source_url'] ?? '' );
			$title = html_entity_decode( wp_strip_all_tags( (string) ( $p['title']['rendered'] ?? '' ) ), ENT_QUOTES, 'UTF-8' );
			$wpdb->insert(
				SocialSync_DB::previews_table(),
				array(
					'batch_id'   => $batch,
					'source_id'  => (int) ( $p['id'] ?? 0 ),
					'source_url' => (string) ( $p['link'] ?? '' ),
					'title'      => $title,
					'categories' => implode( ', ', $terms ),
					'status'     => 'pending',
					'created_at' => SocialSync_DB::now(),
					'updated_at' => SocialSync_DB::now(),
				)
			);
			$preview_id = (int) $wpdb->insert_id;
			$blocked    = self::block_reason( $title, $terms );
			$payload    = array(
				'event'             => 'post_published',
				'schema_version'    => SocialSync_Payload::SCHEMA_VERSION,
				'dry_run'           => true,
				'preview_id'        => $preview_id,
				'request_id'        => SocialSync_Logger::request_id(),
				'sent_at'           => SocialSync_Logger::now(),
				'site'              => array( 'name' => (string) wp_parse_url( $source, PHP_URL_HOST ), 'home_url' => $source ),
				'post_id'           => (int) ( $p['id'] ?? 0 ),
				'post_type'         => 'post',
				'source_url'        => (string) ( $p['link'] ?? '' ),
				'title'             => $title,
				'content_text'      => SocialSync_Payload::to_text( (string) ( $p['content']['rendered'] ?? '' ) ),
				'excerpt'           => SocialSync_Payload::to_text( (string) ( $p['excerpt']['rendered'] ?? '' ) ),
				'price'             => null,
				'image_url'         => $img,
				'image_alt'         => '',
				'categories'        => $terms,
				'jobs'              => array(
					array( 'job_id' => 0, 'channel' => 'facebook', 'delay_minutes' => 0 ),
					array( 'job_id' => 0, 'channel' => 'pinterest', 'delay_minutes' => 0 ),
				),
				'callback_url'      => SocialSync_Settings::internal_url( 'previews/' . $preview_id ),
				'approval_required' => true,
				'pinterest_board'   => SocialSync_Settings::board_for( $terms ),
				'rules'             => array( 'product_disclaimer' => (string) SocialSync_Settings::get( 'product_disclaimer' ) ),
			);
			if ( null !== $blocked ) {
				// Bài bị chặn ngay ở WordPress: không tốn lần gọi AI, giống bài thật.
				$wpdb->update( SocialSync_DB::previews_table(), array( 'status' => 'done', 'classification' => 'X', 'result' => wp_json_encode( array( 'classification_reason' => 'Chặn ở WordPress: ' . $blocked, 'decision_text' => 'Không đồng bộ', 'content_source' => 'không gọi AI' ), JSON_UNESCAPED_UNICODE ), 'updated_at' => SocialSync_DB::now() ), array( 'id' => $preview_id ) );
				SocialSync_Logger::info( 'preview.blocked', 'Bài nguồn bị chặn ở WordPress, không gọi AI', array( 'preview_id' => $preview_id, 'title' => $title, 'reason' => $blocked ) );
				continue;
			}
			$result = SocialSync_Dispatcher::send( $payload, array( 'preview_id' => $preview_id ) );
			if ( $result['ok'] ) {
				++$sent;
			} else {
				$wpdb->update( SocialSync_DB::previews_table(), array( 'status' => 'failed', 'error' => (string) $result['error'], 'updated_at' => SocialSync_DB::now() ), array( 'id' => $preview_id ) );
			}
		}
		SocialSync_Logger::info( 'preview.batch', 'Gửi lô chạy thử sang n8n', array( 'batch' => $batch, 'posts' => count( $list ), 'sent' => $sent ) );
		self::notice( 'success', sprintf( 'Đã gửi %d/%d bài sang n8n. Tải lại trang sau 20–40 giây để xem kết quả.', $sent, count( $list ) ) );
		wp_safe_redirect( add_query_arg( 'batch', $batch, $back ) );
		exit;
	}

	/** Cùng quy tắc chặn như bài thật (SocialSync_Publish_Hook::block_reason), áp lên dữ liệu bài nguồn. */
	private static function block_reason( string $title, array $categories ): ?string {
		if ( SocialSync_Settings::get( 'block_pdf_title' ) && preg_match( '/\.pdf\b/i', $title ) ) {
			return 'tiêu đề là tên file PDF (thường là thông báo cho đại lý)';
		}
		$blocked = SocialSync_Settings::blocked_categories();
		foreach ( $categories as $name ) {
			if ( in_array( mb_strtolower( trim( (string) $name ) ), $blocked, true ) ) {
				return 'thuộc danh mục bị chặn "' . $name . '"';
			}
		}
		return null;
	}

	private static function notice( string $type, string $message ): void {
		set_transient( 'socialsync_preview_notice_' . get_current_user_id(), array( 'type' => $type, 'message' => $message ), 120 );
	}
}
