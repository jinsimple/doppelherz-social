<?php
/**
 * Trang Cài đặt → SocialSync (chỉ Administrator) và cảnh báo khi thiếu cấu hình.
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_Admin {

	private const PAGE = 'social-sync';

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_socialsync_test', array( __CLASS__, 'handle_test' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	public static function menu(): void {
		add_menu_page( 'SocialSync', 'SocialSync', SocialSync_Log_Page::CAP, SocialSync_Log_Page::SLUG, array( 'SocialSync_Log_Page', 'render' ), 'dashicons-share', 58 );
		add_submenu_page( SocialSync_Log_Page::SLUG, 'Nhật ký đồng bộ', 'Nhật ký đồng bộ', SocialSync_Log_Page::CAP, SocialSync_Log_Page::SLUG, array( 'SocialSync_Log_Page', 'render' ) );
		add_submenu_page( SocialSync_Log_Page::SLUG, 'Cấu hình SocialSync', 'Cấu hình', 'manage_options', self::PAGE, array( __CLASS__, 'render' ) );
	}

	public static function settings_url( array $args = array() ): string {
		return add_query_arg( array_merge( array( 'page' => self::PAGE ), $args ), admin_url( 'admin.php' ) );
	}

	public static function register(): void {
		register_setting(
			'socialsync',
			SocialSync_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'SocialSync_Settings', 'sanitize' ),
				'default'           => SocialSync_Settings::defaults(),
			)
		);
	}

	public static function notices(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$problems = SocialSync_Settings::missing();
		if ( ! SocialSync_DB::table_exists() ) {
			$problems[] = 'chưa có bảng lượt đồng bộ (thử tắt rồi bật lại plugin)';
		}
		if ( ! $problems ) {
			return;
		}
		printf(
			'<div class="notice notice-warning"><p><strong>SocialSync chưa đồng bộ được:</strong> %s. <a href="%s">Mở cấu hình</a></p></div>',
			esc_html( implode( '; ', $problems ) ),
			esc_url( self::settings_url() )
		);
	}

	public static function handle_test(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Không có quyền.', 403 );
		}
		check_admin_referer( 'socialsync_test' );
		$result = SocialSync_Dispatcher::send_test();
		set_transient( 'socialsync_test_' . get_current_user_id(), $result, 300 );
		wp_safe_redirect( self::settings_url( array( 'socialsync_tested' => 1 ) ) );
		exit;
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = SocialSync_Settings::all();
		?>
		<div class="wrap">
			<h1>Cấu hình SocialSync</h1>
			<p>Khi một bài viết hoặc sản phẩm được xuất bản lần đầu, plugin gửi dữ liệu bài sang n8n. n8n tạo nội dung, đăng lên Facebook Fanpage, Pinterest rồi báo kết quả về <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . SocialSync_Log_Page::SLUG ) ); ?>">Nhật ký đồng bộ</a>.</p>

			<?php settings_errors(); ?>
			<?php self::render_test_result(); ?>

			<h2>Trạng thái</h2>
			<?php self::render_status(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:12px 0 24px">
				<input type="hidden" name="action" value="socialsync_test">
				<?php wp_nonce_field( 'socialsync_test' ); ?>
				<?php submit_button( 'Gửi thử sang n8n', 'secondary', 'submit', false ); ?>
				<span class="description">Gửi một gói "test" để kiểm tra địa chỉ webhook và mã bí mật. Không đăng gì lên mạng xã hội.</span>
			</form>

			<h2>Cấu hình</h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'socialsync' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ss-webhook">Địa chỉ webhook n8n</label></th>
						<td>
							<input id="ss-webhook" type="url" class="regular-text code" name="<?php echo esc_attr( SocialSync_Settings::OPTION ); ?>[webhook_url]" value="<?php echo esc_attr( (string) $s['webhook_url'] ); ?>">
							<p class="description">Mặc định gọi n8n qua mạng nội bộ Docker: <code>http://n8n:5678/webhook/socialsync/publish</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ss-secret">Mã bí mật dùng chung</label></th>
						<td><?php self::render_secret_field(); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="ss-callback">Địa chỉ WordPress cho n8n gọi về</label></th>
						<td>
							<input id="ss-callback" type="url" class="regular-text code" name="<?php echo esc_attr( SocialSync_Settings::OPTION ); ?>[callback_base]" value="<?php echo esc_attr( (string) $s['callback_base'] ); ?>">
							<p class="description">n8n báo kết quả về <code><?php echo esc_html( SocialSync_Settings::callback_url() ); ?>/{mã lượt}</code>. Tiến trình nền gửi bài cũng dùng địa chỉ này.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Loại nội dung đồng bộ</th>
						<td>
							<?php foreach ( SocialSync_Settings::POST_TYPES as $type => $label ) : ?>
								<label style="margin-right:16px">
									<input type="checkbox" name="<?php echo esc_attr( SocialSync_Settings::OPTION ); ?>[post_types][]" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, (array) $s['post_types'], true ) ); ?>>
									<?php echo esc_html( $label ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">Kênh và lịch đăng</th>
						<td>
							<?php foreach ( SocialSync_Settings::CHANNELS as $channel => $label ) : ?>
								<p>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( SocialSync_Settings::OPTION ); ?>[channels][<?php echo esc_attr( $channel ); ?>]" value="1" <?php checked( ! empty( $s['channels'][ $channel ] ) ); ?>>
										<?php echo esc_html( $label ); ?>
									</label>
									— đăng sau
									<input type="number" min="0" max="1440" style="width:80px" name="<?php echo esc_attr( SocialSync_Settings::OPTION ); ?>[delays][<?php echo esc_attr( $channel ); ?>]" value="<?php echo esc_attr( (string) ( $s['delays'][ $channel ] ?? 0 ) ); ?>">
									phút kể từ lúc xuất bản (0 = đăng ngay)
								</p>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ss-timeout">Thời gian chờ n8n (giây)</label></th>
						<td><input id="ss-timeout" type="number" min="2" max="30" name="<?php echo esc_attr( SocialSync_Settings::OPTION ); ?>[timeout]" value="<?php echo esc_attr( (string) $s['timeout'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row">Cách gửi sang n8n</th>
						<td>
							<label style="margin-right:16px"><input type="radio" name="<?php echo esc_attr( SocialSync_Settings::OPTION ); ?>[dispatch_mode]" value="async" <?php checked( SocialSync_Settings::dispatch_mode(), 'async' ); ?>> Chạy nền (khuyến nghị): nút Đăng không phải chờ n8n</label>
							<label><input type="radio" name="<?php echo esc_attr( SocialSync_Settings::OPTION ); ?>[dispatch_mode]" value="sync" <?php checked( SocialSync_Settings::dispatch_mode(), 'sync' ); ?>> Gửi ngay trong lúc đăng (dùng khi gỡ lỗi)</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ss-n8n-editor">Địa chỉ trình soạn n8n</label></th>
						<td>
							<input id="ss-n8n-editor" type="url" class="regular-text code" name="<?php echo esc_attr( SocialSync_Settings::OPTION ); ?>[n8n_editor]" value="<?php echo esc_attr( (string) $s['n8n_editor'] ); ?>">
							<p class="description">Dùng để tạo link tới từng lượt chạy n8n trong trang Nhật ký.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Log</th>
						<td>
							<p>
								Mức tối thiểu:
								<select name="<?php echo esc_attr( SocialSync_Settings::OPTION ); ?>[log_level]">
									<?php foreach ( SocialSync_Settings::LOG_LEVELS as $level ) : ?>
										<option value="<?php echo esc_attr( $level ); ?>" <?php selected( $s['log_level'], $level ); ?>><?php echo esc_html( $level ); ?></option>
									<?php endforeach; ?>
								</select>
							</p>
							<p><label><input type="checkbox" name="<?php echo esc_attr( SocialSync_Settings::OPTION ); ?>[log_requests]" value="1" <?php checked( ! empty( $s['log_requests'] ) ); ?>> Ghi mọi request vào website (header Cloudflare, nhận diện HTTPS)</label></p>
							<p><label><input type="checkbox" name="<?php echo esc_attr( SocialSync_Settings::OPTION ); ?>[log_http]" value="1" <?php checked( ! empty( $s['log_http'] ) ); ?>> Ghi mọi HTTP mà WordPress gọi ra ngoài</label></p>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Lưu cấu hình' ); ?>
			</form>

			<h2>40 dòng log cuối</h2>
			<p class="description">File: <code><?php echo esc_html( SocialSync_Logger::log_file() ); ?></code></p>
			<pre style="background:#1d2327;color:#f0f0f1;padding:12px;max-height:420px;overflow:auto;white-space:pre-wrap;font-size:12px"><?php echo esc_html( implode( "\n", SocialSync_Logger::tail( 40 ) ) ?: '(chưa có log)' ); ?></pre>
		</div>
		<?php
	}

	private static function render_secret_field(): void {
		$source = SocialSync_Settings::secret_source();
		$labels = array(
			'constant' => 'Đang lấy từ hằng số SOCIALSYNC_SECRET trong wp-config.php.',
			'env'      => 'Đang lấy từ biến môi trường SOCIALSYNC_SECRET (file .env của Docker).',
			'option'   => 'Đã nhập. Để trống nếu không muốn đổi.',
			'none'     => 'Chưa có. Nhập mã bí mật giống với credential "SocialSync - mã bí mật từ WordPress" trong n8n.',
		);
		if ( in_array( $source, array( 'constant', 'env' ), true ) ) {
			printf( '<p>%s (%d ký tự)</p>', esc_html( $labels[ $source ] ), (int) strlen( SocialSync_Settings::secret() ) );
			return;
		}
		printf(
			'<input id="ss-secret" type="password" class="regular-text" autocomplete="new-password" name="%s[secret]" value=""><p class="description">%s</p>',
			esc_attr( SocialSync_Settings::OPTION ),
			esc_html( $labels[ $source ] )
		);
	}

	private static function render_status(): void {
		$log_file  = SocialSync_Logger::log_file();
		$log_dir   = dirname( $log_file );
		$rows      = array(
			'Phiên bản plugin'          => SOCIALSYNC_VERSION,
			'Bảng lượt đồng bộ'         => SocialSync_DB::table_exists() ? 'có (' . SocialSync_DB::table() . ')' : 'CHƯA CÓ',
			'Mã bí mật'                 => array(
				'constant' => 'có, từ wp-config.php',
				'env'      => 'có, từ biến môi trường',
				'option'   => 'có, nhập trong trang này',
				'none'     => 'CHƯA CÓ',
			)[ SocialSync_Settings::secret_source() ],
			'Kênh đang bật'             => implode( ', ', SocialSync_Settings::enabled_channels() ) ?: 'không có',
			'Loại nội dung đồng bộ'     => implode( ', ', SocialSync_Settings::post_types() ) ?: 'không có',
			'WooCommerce'               => class_exists( 'WooCommerce' ) ? 'đang chạy' : 'không chạy',
			'Thư mục log ghi được'      => is_writable( $log_dir ) ? 'có' : 'KHÔNG (' . $log_dir . ')',
			'Dung lượng file log'       => is_file( $log_file ) ? size_format( (int) filesize( $log_file ) ) : 'chưa có file',
		);
		echo '<table class="widefat striped" style="max-width:720px"><tbody>';
		foreach ( $rows as $label => $value ) {
			printf( '<tr><th style="width:240px">%s</th><td>%s</td></tr>', esc_html( $label ), esc_html( (string) $value ) );
		}
		echo '</tbody></table>';
	}

	private static function render_test_result(): void {
		if ( empty( $_GET['socialsync_tested'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$key    = 'socialsync_test_' . get_current_user_id();
		$result = get_transient( $key );
		delete_transient( $key );
		if ( ! is_array( $result ) ) {
			return;
		}
		$class = $result['ok'] ? 'notice-success' : 'notice-error';
		$text  = $result['ok']
			? sprintf( 'Gửi thử thành công: n8n phản hồi HTTP %d sau %d ms, mã lượt chạy n8n: %s.', $result['code'], $result['ms'], (string) ( $result['execution_id'] ?? '?' ) )
			: sprintf( 'Gửi thử thất bại sau %d ms: %s', $result['ms'], (string) $result['error'] );
		printf( '<div class="notice %s"><p>%s</p></div>', esc_attr( $class ), esc_html( $text ) );
	}

}
