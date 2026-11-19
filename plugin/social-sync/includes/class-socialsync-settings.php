<?php
/**
 * Cấu hình của plugin.
 *
 * - Lưu trong option "socialsync_settings" (trừ mã bí mật).
 * - Mã bí mật dùng chung với n8n lấy theo thứ tự: hằng số SOCIALSYNC_SECRET trong wp-config.php,
 *   biến môi trường SOCIALSYNC_SECRET (file .env của Docker), rồi mới tới option "socialsync_secret".
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_Settings {

	public const OPTION        = 'socialsync_settings';
	public const SECRET_OPTION = 'socialsync_secret';
	public const CHANNELS      = array(
		'facebook'  => 'Facebook Fanpage',
		'pinterest' => 'Pinterest',
	);
	public const POST_TYPES    = array(
		'post'    => 'Bài viết',
		'product' => 'Sản phẩm (WooCommerce)',
	);
	public const LOG_LEVELS    = array( 'debug', 'info', 'warn', 'error' );

	private static ?array $cache = null;

	public static function defaults(): array {
		return array(
			// WordPress gọi n8n qua mạng nội bộ Docker.
			'webhook_url'   => 'http://n8n:5678/webhook/socialsync/publish',
			// Địa chỉ n8n dùng để gọi ngược về WordPress (nấc 2), cũng qua mạng nội bộ.
			'callback_base' => 'http://wordpress',
			'post_types'    => array( 'post', 'product' ),
			'channels'      => array(
				'facebook'  => 1,
				'pinterest' => 1,
			),
			'delays'        => array(
				'facebook'  => 0,
				'pinterest' => 0,
			),
			'timeout'       => 10,
			// async: gửi sang n8n trong một tiến trình nền, nút Publish không phải chờ n8n.
			// sync:  gửi ngay trong request xuất bản (dùng khi cần gỡ lỗi).
			'dispatch_mode' => 'async',
			// Địa chỉ trình soạn n8n, để tạo link tới từng lượt chạy trong trang Nhật ký.
			'n8n_editor'    => 'https://n8n.adaptivealgo.uk',
			'log_level'     => 'debug',
			'log_requests'  => 0,
			'log_http'      => 0,
		);
	}

	public static function all(): array {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPTION, array() );
			self::$cache = array_replace( self::defaults(), is_array( $saved ) ? $saved : array() );
		}
		return self::$cache;
	}

	public static function flush_cache(): void {
		self::$cache = null;
	}

	/** @return mixed */
	public static function get( string $key ) {
		return self::all()[ $key ] ?? null;
	}

	public static function webhook_url(): string {
		return trim( (string) self::get( 'webhook_url' ) );
	}

	/** Địa chỉ REST của plugin qua mạng nội bộ Docker, ví dụ http://wordpress/wp-json/social-sync/v1/jobs */
	public static function internal_url( string $route ): string {
		return rtrim( (string) self::get( 'callback_base' ), '/' ) . '/wp-json/' . SocialSync_REST::NS . '/' . ltrim( $route, '/' );
	}

	/** n8n báo kết quả về đây, nối thêm /{job_id}. */
	public static function callback_url(): string {
		return self::internal_url( 'jobs' );
	}

	public static function dispatch_mode(): string {
		return 'sync' === self::get( 'dispatch_mode' ) ? 'sync' : 'async';
	}

	/** Link tới một lượt chạy trong trình soạn n8n. */
	public static function n8n_execution_url( string $execution_id ): string {
		$base = rtrim( (string) self::get( 'n8n_editor' ), '/' );
		if ( '' === $base || '' === $execution_id ) {
			return '';
		}
		return $base . '/workflow/' . rawurlencode( SocialSync_REST::N8N_WORKFLOW_ID ) . '/executions/' . rawurlencode( $execution_id );
	}

	/** @return string[] */
	public static function post_types(): array {
		return array_values( array_intersect( (array) self::get( 'post_types' ), array_keys( self::POST_TYPES ) ) );
	}

	/** @return string[] */
	public static function enabled_channels(): array {
		$out = array();
		foreach ( self::CHANNELS as $channel => $label ) {
			if ( ! empty( self::get( 'channels' )[ $channel ] ) ) {
				$out[] = $channel;
			}
		}
		return $out;
	}

	public static function delay( string $channel ): int {
		return max( 0, (int) ( self::get( 'delays' )[ $channel ] ?? 0 ) );
	}

	public static function timeout(): int {
		return max( 2, min( 30, (int) self::get( 'timeout' ) ) );
	}

	public static function log_level(): string {
		$level = (string) self::get( 'log_level' );
		return in_array( $level, self::LOG_LEVELS, true ) ? $level : 'debug';
	}

	public static function secret(): string {
		if ( defined( 'SOCIALSYNC_SECRET' ) && SOCIALSYNC_SECRET ) {
			return (string) SOCIALSYNC_SECRET;
		}
		$env = getenv( 'SOCIALSYNC_SECRET' );
		if ( is_string( $env ) && '' !== $env ) {
			return $env;
		}
		return (string) get_option( self::SECRET_OPTION, '' );
	}

	/** Mã bí mật đang lấy từ đâu: constant | env | option | none. */
	public static function secret_source(): string {
		if ( defined( 'SOCIALSYNC_SECRET' ) && SOCIALSYNC_SECRET ) {
			return 'constant';
		}
		$env = getenv( 'SOCIALSYNC_SECRET' );
		if ( is_string( $env ) && '' !== $env ) {
			return 'env';
		}
		return '' !== (string) get_option( self::SECRET_OPTION, '' ) ? 'option' : 'none';
	}

	/** @return string[] Danh sách thiếu sót cấu hình, rỗng nếu đủ. */
	public static function missing(): array {
		$missing = array();
		if ( '' === self::webhook_url() ) {
			$missing[] = 'chưa có địa chỉ webhook của n8n';
		}
		if ( '' === self::secret() ) {
			$missing[] = 'chưa có mã bí mật dùng chung với n8n';
		}
		return $missing;
	}

	public static function is_configured(): bool {
		return array() === self::missing();
	}

	/**
	 * Làm sạch dữ liệu từ form cấu hình.
	 *
	 * @param mixed $input
	 */
	public static function sanitize( $input ): array {
		$in  = is_array( $input ) ? $input : array();
		$def = self::defaults();
		$out = array();

		$out['webhook_url']   = esc_url_raw( trim( (string) ( $in['webhook_url'] ?? '' ) ), array( 'http', 'https' ) );
		$callback             = esc_url_raw( trim( (string) ( $in['callback_base'] ?? '' ) ), array( 'http', 'https' ) );
		$out['callback_base'] = '' !== $callback ? $callback : $def['callback_base'];

		$out['post_types'] = array_values( array_intersect( (array) ( $in['post_types'] ?? array() ), array_keys( self::POST_TYPES ) ) );

		$out['channels'] = array();
		$out['delays']   = array();
		foreach ( self::CHANNELS as $channel => $label ) {
			$out['channels'][ $channel ] = empty( $in['channels'][ $channel ] ) ? 0 : 1;
			$out['delays'][ $channel ]   = max( 0, min( 1440, (int) ( $in['delays'][ $channel ] ?? 0 ) ) );
		}

		$out['timeout']       = max( 2, min( 30, (int) ( $in['timeout'] ?? $def['timeout'] ) ) );
		$out['dispatch_mode'] = 'sync' === ( $in['dispatch_mode'] ?? '' ) ? 'sync' : 'async';
		$out['n8n_editor']    = esc_url_raw( trim( (string) ( $in['n8n_editor'] ?? '' ) ), array( 'http', 'https' ) );
		$level               = (string) ( $in['log_level'] ?? 'debug' );
		$out['log_level']    = in_array( $level, self::LOG_LEVELS, true ) ? $level : 'debug';
		$out['log_requests'] = empty( $in['log_requests'] ) ? 0 : 1;
		$out['log_http']     = empty( $in['log_http'] ) ? 0 : 1;

		$secret_changed = false;
		$new_secret     = trim( (string) ( $in['secret'] ?? '' ) );
		if ( '' !== $new_secret && in_array( self::secret_source(), array( 'option', 'none' ), true ) ) {
			update_option( self::SECRET_OPTION, sanitize_text_field( $new_secret ), false );
			$secret_changed = true;
		}

		SocialSync_Logger::info(
			'settings.save',
			'Lưu cấu hình',
			array(
				'settings'       => $out,
				'secret_changed' => $secret_changed,
				'secret_source'  => self::secret_source(),
				'by_user'        => get_current_user_id(),
			)
		);
		self::flush_cache();
		return $out;
	}
}
