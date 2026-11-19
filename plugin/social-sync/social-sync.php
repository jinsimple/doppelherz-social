<?php
/**
 * Plugin Name:       SocialSync
 * Description:       Báo cho n8n mỗi khi có bài viết hoặc sản phẩm mới được xuất bản, để n8n đồng bộ lên Facebook Fanpage và Pinterest. Ghi log chi tiết từng bước.
 * Version:           0.2.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            SocialSync demo
 * License:           GPL-2.0-or-later
 * Text Domain:       social-sync
 *
 * Nấc 1: bắt sự kiện xuất bản, chống đăng trùng, gửi dữ liệu bài sang n8n, log chi tiết, trang cấu hình.
 * Nấc 1b: gửi sang n8n bằng tiến trình nền, địa chỉ REST nhận kết quả từ n8n, trang Nhật ký đồng bộ.
 */

defined( 'ABSPATH' ) || exit;

define( 'SOCIALSYNC_VERSION', '0.2.0' );
define( 'SOCIALSYNC_DB_VERSION', '1' );
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

	if ( is_admin() ) {
		SocialSync_Admin::init();
		SocialSync_Log_Page::init();
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		require_once SOCIALSYNC_DIR . '/includes/class-socialsync-cli.php';
		WP_CLI::add_command( 'socialsync', 'SocialSync_CLI' );
	}
}
