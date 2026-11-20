<?php
/**
 * Plugin Name:       SocialSync
 * Description:       Báo cho n8n mỗi khi có bài viết hoặc sản phẩm mới được xuất bản, để n8n đồng bộ lên Facebook Fanpage và Pinterest. Ghi log chi tiết từng bước.
 * Version:           0.4.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            SocialSync demo
 * License:           GPL-2.0-or-later
 * Text Domain:       social-sync
 *
 * Nấc 1: bắt sự kiện xuất bản, chống đăng trùng, gửi dữ liệu bài sang n8n, log chi tiết, trang cấu hình.
 * Nấc 1b: gửi sang n8n bằng tiến trình nền, địa chỉ REST nhận kết quả từ n8n, trang Nhật ký đồng bộ.
 * 0.2.1: ô "Mã xác minh Pinterest", in thẻ p:domain_verify vào <head> để claim website trên Pinterest.
 * 0.3.0: duyệt trước khi đăng, phân loại bài + chặn bài không nên đăng, lịch đăng tự rải + giờ chọn tay,
 *        trang Báo cáo + báo cáo tuần, chạy thử với bài thật của doppelherz.vn.
 * 0.4.0: Trung tâm SocialSync: một trang chia đôi gộp Chờ duyệt + Xem và duyệt + Nhật ký; cột "Mạng xã hội"
 *        ở danh sách bài; panel trình soạn hiện trạng thái kênh; "Đăng lại nội dung đã duyệt" khi đăng lỗi.
 */

defined( 'ABSPATH' ) || exit;

define( 'SOCIALSYNC_VERSION', '0.4.0' );
define( 'SOCIALSYNC_DB_VERSION', '3' ); // 3: cột dispatch_token (đổi giờ / hủy lịch sau khi duyệt)
define( 'SOCIALSYNC_FILE', __FILE__ );
define( 'SOCIALSYNC_DIR', __DIR__ );

require_once SOCIALSYNC_DIR . '/includes/class-socialsync-logger.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-settings.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-db.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-payload.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-dispatcher.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-publish-hook.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-diagnostics.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-rest.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-admin.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-log-page.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-site-meta.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-scheduler.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-schedule-ui.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-review.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-hub.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-report.php';
require_once SOCIALSYNC_DIR . '/includes/class-socialsync-preview.php';

register_activation_hook( __FILE__, 'socialsync_activate' );
register_deactivation_hook( __FILE__, 'socialsync_deactivate' );
add_action( 'plugins_loaded', 'socialsync_boot' );

function socialsync_activate(): void {
	SocialSync_Logger::info( 'plugin.activate', 'Kích hoạt plugin', array( 'version' => SOCIALSYNC_VERSION, 'php' => PHP_VERSION ) );
	SocialSync_DB::install();
}

function socialsync_deactivate(): void {
	SocialSync_Logger::info( 'plugin.deactivate', 'Tắt plugin (bảng dữ liệu được giữ lại)', array( 'version' => SOCIALSYNC_VERSION ) );
}

function socialsync_boot(): void {
	add_action( 'add_option_' . SocialSync_Settings::OPTION, array( 'SocialSync_Settings', 'flush_cache' ) );
	add_action( 'update_option_' . SocialSync_Settings::OPTION, array( 'SocialSync_Settings', 'flush_cache' ) );

	SocialSync_DB::maybe_upgrade();
	SocialSync_Diagnostics::init();
	SocialSync_Publish_Hook::init();
	SocialSync_REST::init();
	SocialSync_Site_Meta::init();
	SocialSync_Schedule_UI::init();

	if ( is_admin() ) {
		SocialSync_Admin::init();
		SocialSync_Log_Page::init();
		SocialSync_Review::init();
		SocialSync_Hub::init();
		SocialSync_Report::init();
		SocialSync_Preview::init();
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		require_once SOCIALSYNC_DIR . '/includes/class-socialsync-cli.php';
		WP_CLI::add_command( 'socialsync', 'SocialSync_CLI' );
	}
}
