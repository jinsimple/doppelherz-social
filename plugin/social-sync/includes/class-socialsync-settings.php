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
			// Mã xác minh website của Pinterest (thẻ meta p:domain_verify). Không phải bí mật: ai xem mã nguồn trang cũng thấy.
			'pinterest_verify' => '',
			// required: AI viết xong thì chờ người duyệt. auto: đăng theo lịch, không cần duyệt.
			'approval'         => 'required',
			// Webhook n8n nhận lệnh đăng sau khi duyệt, và webhook chạy báo cáo.
			'approve_url'      => 'http://n8n:5678/webhook/socialsync/approved',
			'report_url'       => 'http://n8n:5678/webhook/socialsync/report',
			// Lịch đăng tự rải: chỉ đăng trong khung giờ, tối đa N bài/ngày/kênh, cách nhau ít nhất X phút.
			'window_start'     => '07:00',
			'window_end'       => '22:00',
			'max_per_day'      => array(
				'facebook'  => 10,
				'pinterest' => 10,
			),
			'min_gap'          => 30,
			// Bài thuộc các danh mục này (tên, phân cách bằng dấu phẩy) không đồng bộ.
			'blocked_categories' => 'Tin nội bộ, Thông báo đại lý',
			'block_pdf_title'  => 1,
			// Ghép danh mục website sang board Pinterest, mỗi dòng: Tên danh mục = Tên board.
			'board_map'        => '',
			'default_board'    => 'Sức khoẻ & Dinh dưỡng',
			// Câu khuyến cáo code tự gắn vào bài loại Sản phẩm (P). Để trống nếu cửa hàng không bán thực phẩm bảo vệ sức khỏe.
			'product_disclaimer' => 'Thực phẩm này không phải là thuốc, không có tác dụng thay thế thuốc chữa bệnh.',
			// Nguồn bài thật cho trang "Chạy thử".
			'preview_source'   => 'https://doppelherz.vn',
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

	public static function approval_required(): bool {
		return 'auto' !== self::get( 'approval' );
	}

	public static function min_gap(): int {
		return max( 0, (int) self::get( 'min_gap' ) );
	}

	public static function max_per_day( string $channel ): int {
		return max( 1, (int) ( self::get( 'max_per_day' )[ $channel ] ?? 10 ) );
	}

	/** Phút trong ngày, ví dụ "07:30" -> 450. */
	public static function window(): array {
		$to_min = static function ( string $hm, int $fallback ): int {
			return preg_match( '/^(\d{1,2}):(\d{2})$/', $hm, $m ) ? min( 1439, (int) $m[1] * 60 + (int) $m[2] ) : $fallback;
		};
		$start = $to_min( (string) self::get( 'window_start' ), 420 );
		$end   = $to_min( (string) self::get( 'window_end' ), 1320 );
		return $end > $start ? array( $start, $end ) : array( 0, 1439 );
	}

	/** @return string[] tên danh mục bị chặn, viết thường. */
	public static function blocked_categories(): array {
		$items = array_map( 'trim', explode( ',', (string) self::get( 'blocked_categories' ) ) );
		return array_values( array_filter( array_map( static fn( $s ) => mb_strtolower( $s ), $items ) ) );
	}

	/** Board Pinterest cho một bài theo danh mục, theo bảng ghép trong cấu hình. */
	public static function board_for( array $categories ): string {
		$map = array();
		foreach ( preg_split( '/\R/', (string) self::get( 'board_map' ) ) as $line ) {
			if ( false !== strpos( $line, '=' ) ) {
				[ $cat, $board ] = array_map( 'trim', explode( '=', $line, 2 ) );
				if ( '' !== $cat && '' !== $board ) {
					$map[ mb_strtolower( $cat ) ] = $board;
				}
			}
		}
		foreach ( $categories as $cat ) {
			$key = mb_strtolower( trim( (string) $cat ) );
			if ( isset( $map[ $key ] ) ) {
				return $map[ $key ];
			}
		}
		return (string) self::get( 'default_board' );
	}

	public static function pinterest_verify(): string {
		return (string) self::get( 'pinterest_verify' );
	}

	/**
	 * Nhận cả thẻ meta dán nguyên khối lẫn riêng phần mã, trả về mã đã làm sạch.
	 * Ví dụ: <meta name="p:domain_verify" content="abc123"/>  →  abc123
	 *
	 * @return array{code:string, note:string} note khác rỗng khi đầu vào có vấn đề.
	 */
	public static function parse_pinterest_verify( string $raw ): array {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return array( 'code' => '', 'note' => '' );
		}
		$candidate = $raw;
		if ( preg_match( '/content\s*=\s*["\']([^"\']*)["\']/i', $raw, $m ) ) {
			$candidate = $m[1];
		} elseif ( false !== strpos( $raw, '<' ) ) {
			return array( 'code' => '', 'note' => 'Đã dán thẻ HTML nhưng không tìm thấy thuộc tính content="...".' );
		}
		$code = preg_replace( '/[^A-Za-z0-9_\-]/', '', $candidate );
		if ( '' === $code ) {
			return array( 'code' => '', 'note' => 'Mã xác minh rỗng sau khi bỏ ký tự lạ.' );
		}
		$note = $code !== $candidate ? 'Đã bỏ các ký tự không hợp lệ khỏi mã.' : '';
		return array( 'code' => $code, 'note' => $note );
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

		$pin_old                 = self::pinterest_verify();
		$pin                     = self::parse_pinterest_verify( (string) ( $in['pinterest_verify'] ?? '' ) );
		$out['pinterest_verify'] = $pin['code'];
		if ( '' !== $pin['note'] ) {
			SocialSync_Logger::warn( 'settings.pinterest_verify', $pin['note'], array( 'raw_len' => strlen( (string) ( $in['pinterest_verify'] ?? '' ) ), 'code_len' => strlen( $pin['code'] ) ) );
			if ( function_exists( 'add_settings_error' ) ) {
				add_settings_error( self::OPTION, 'pinterest_verify', 'Mã xác minh Pinterest: ' . $pin['note'], '' === $pin['code'] ? 'error' : 'warning' );
			}
		}
		if ( $pin_old !== $pin['code'] ) {
			SocialSync_Logger::info( 'settings.pinterest_verify', 'Đổi mã xác minh Pinterest', array( 'old' => $pin_old, 'new' => $pin['code'] ) );
		}

		$out['approval']    = 'auto' === ( $in['approval'] ?? '' ) ? 'auto' : 'required';
		$out['approve_url'] = esc_url_raw( trim( (string) ( $in['approve_url'] ?? '' ) ), array( 'http', 'https' ) ) ?: $def['approve_url'];
		$out['report_url']  = esc_url_raw( trim( (string) ( $in['report_url'] ?? '' ) ), array( 'http', 'https' ) ) ?: $def['report_url'];
		foreach ( array( 'window_start', 'window_end' ) as $key ) {
			$v           = trim( (string) ( $in[ $key ] ?? '' ) );
			$out[ $key ] = preg_match( '/^([01]?\d|2[0-3]):[0-5]\d$/', $v ) ? $v : $def[ $key ];
		}
		$out['max_per_day'] = array();
		foreach ( self::CHANNELS as $channel => $label ) {
			$out['max_per_day'][ $channel ] = max( 1, min( 100, (int) ( $in['max_per_day'][ $channel ] ?? $def['max_per_day'][ $channel ] ) ) );
		}
		$out['min_gap']            = max( 0, min( 1440, (int) ( $in['min_gap'] ?? $def['min_gap'] ) ) );
		$out['blocked_categories'] = sanitize_text_field( (string) ( $in['blocked_categories'] ?? '' ) );
		$out['block_pdf_title']    = empty( $in['block_pdf_title'] ) ? 0 : 1;
		$out['board_map']          = sanitize_textarea_field( (string) ( $in['board_map'] ?? '' ) );
		$out['default_board']      = sanitize_text_field( (string) ( $in['default_board'] ?? '' ) );
		$out['product_disclaimer'] = sanitize_text_field( (string) ( $in['product_disclaimer'] ?? '' ) );
		$out['preview_source']     = esc_url_raw( trim( (string) ( $in['preview_source'] ?? '' ) ), array( 'https' ) ) ?: $def['preview_source'];

		$level              = (string) ( $in['log_level'] ?? 'debug' );
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
