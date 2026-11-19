<?php
/**
 * Lệnh WP-CLI để kiểm tra và gỡ lỗi.
 *
 *   wp socialsync config            Xem cấu hình (không in mã bí mật)
 *   wp socialsync test              Gửi gói "test" sang n8n
 *   wp socialsync jobs [--post_id=] Xem các lượt đồng bộ
 *   wp socialsync reset <post_id>   Xoá lượt đồng bộ của một bài (để thử lại)
 *   wp socialsync log [--lines=]    Xem log cuối
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_CLI {

	/**
	 * Xem cấu hình hiện tại. Mã bí mật chỉ hiện nguồn và độ dài.
	 */
	public function config(): void {
		$s = SocialSync_Settings::all();
		WP_CLI::line( wp_json_encode(
			array(
				'settings'      => $s,
				'secret_source' => SocialSync_Settings::secret_source(),
				'secret_chars'  => strlen( SocialSync_Settings::secret() ),
				'missing'       => SocialSync_Settings::missing(),
				'table'         => SocialSync_DB::table(),
				'table_exists'  => SocialSync_DB::table_exists(),
				'log_file'      => SocialSync_Logger::log_file(),
				'callback_url'  => SocialSync_Settings::callback_url(),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		) );
	}

	/**
	 * Gửi gói "test" sang n8n.
	 */
	public function test(): void {
		$r = SocialSync_Dispatcher::send_test();
		WP_CLI::line( wp_json_encode( SocialSync_Logger::mask( $r ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		$r['ok'] ? WP_CLI::success( 'n8n đã nhận gói test.' ) : WP_CLI::error( (string) $r['error'], false );
	}

	/**
	 * Xem các lượt đồng bộ.
	 *
	 * [--post_id=<id>]
	 * : Chỉ xem lượt của bài này.
	 *
	 * [--limit=<n>]
	 * : Số dòng. Mặc định 20.
	 */
	public function jobs( array $args, array $assoc ): void {
		$rows = SocialSync_DB::jobs( (int) ( $assoc['post_id'] ?? 0 ), (int) ( $assoc['limit'] ?? 20 ) );
		if ( ! $rows ) {
			WP_CLI::line( '(chưa có lượt nào)' );
			return;
		}
		WP_CLI\Utils\format_items( 'table', $rows, array( 'id', 'post_id', 'post_type', 'channel', 'status', 'attempts', 'execution_id', 'error', 'created_at', 'updated_at' ) );
	}

	/**
	 * Xoá các lượt đồng bộ của một bài, để lần xuất bản sau được gửi lại.
	 *
	 * <post_id>
	 * : ID bài viết.
	 */
	public function reset( array $args ): void {
		$rows = SocialSync_DB::delete_for_post( (int) $args[0] );
		WP_CLI::success( "Đã xoá {$rows} lượt của bài #{$args[0]}." );
	}

	/**
	 * Xem log cuối.
	 *
	 * [--lines=<n>]
	 * : Số dòng. Mặc định 40.
	 */
	public function log( array $args, array $assoc ): void {
		foreach ( SocialSync_Logger::tail( (int) ( $assoc['lines'] ?? 40 ) ) as $line ) {
			WP_CLI::line( $line );
		}
	}
}
