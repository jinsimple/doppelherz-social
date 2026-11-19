<?php
/**
 * Ghi log chi tiết ra file nằm ngoài webroot.
 *
 * Mỗi dòng có dạng:
 *   <thời gian ms, giờ site> <MỨC> req=<mã request> step=<bước> post=<id> job=<id> ch=<kênh> | <thông điệp> <dữ liệu JSON>
 *
 * Quy tắc an toàn: mọi khoá có tên giống secret / token / password / authorization / api_key / cookie
 * đều được thay bằng "***" trước khi ghi. Chuỗi quá dài được cắt bớt và ghi rõ tổng độ dài.
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_Logger {

	private const LEVELS             = array( 'debug' => 10, 'info' => 20, 'warn' => 30, 'error' => 40 );
	private const MAX_BYTES          = 5242880; // Quá 5 MB thì đổi tên file cũ thành .1.
	private const MAX_STRING         = 2000;    // Chuỗi dài hơn thì cắt trong log.
	private const SECRET_KEY_PATTERN = '/(^|[_\-])(secret|token|password|passwd|authorization|api[_\-]?key|cookie)($|[_\-])/i';

	private static ?string $request_id          = null;
	private static bool $write_failure_reported = false;

	/** Mã ngẫu nhiên cho request hiện tại, để gom các dòng log cùng một lượt xử lý. */
	public static function request_id(): string {
		if ( null === self::$request_id ) {
			self::$request_id = bin2hex( random_bytes( 6 ) );
		}
		return self::$request_id;
	}

	/**
	 * Dùng lại mã request của request cha (ví dụ tiến trình nền do chính plugin mở),
	 * để log của cả chuỗi xử lý mang cùng một mã.
	 */
	public static function adopt_request_id( string $id ): bool {
		if ( 1 === preg_match( '/^[a-f0-9]{12}$/', $id ) ) {
			self::$request_id = $id;
			return true;
		}
		return false;
	}

	public static function log_dir(): string {
		if ( defined( 'SOCIALSYNC_LOG_DIR' ) && SOCIALSYNC_LOG_DIR ) {
			return rtrim( (string) SOCIALSYNC_LOG_DIR, '/' );
		}
		$env = getenv( 'SOCIALSYNC_LOG_DIR' );
		if ( is_string( $env ) && '' !== $env ) {
			return rtrim( $env, '/' );
		}
		return '/var/log/socialsync';
	}

	public static function log_file(): string {
		return self::log_dir() . '/social-sync.log';
	}

	public static function debug( string $step, string $message, array $context = array() ): void {
		self::write( 'debug', $step, $message, $context );
	}

	public static function info( string $step, string $message, array $context = array() ): void {
		self::write( 'info', $step, $message, $context );
	}

	public static function warn( string $step, string $message, array $context = array() ): void {
		self::write( 'warn', $step, $message, $context );
	}

	public static function error( string $step, string $message, array $context = array() ): void {
		self::write( 'error', $step, $message, $context );
	}

	public static function write( string $level, string $step, string $message, array $context = array() ): void {
		$min = class_exists( 'SocialSync_Settings' ) ? SocialSync_Settings::log_level() : 'debug';
		if ( ( self::LEVELS[ $level ] ?? 20 ) < ( self::LEVELS[ $min ] ?? 10 ) ) {
			return;
		}

		$post_id = $context['post_id'] ?? '-';
		$job_id  = $context['job_id'] ?? ( isset( $context['job_ids'] ) ? implode( ',', (array) $context['job_ids'] ) : '-' );
		$channel = $context['channel'] ?? '-';
		unset( $context['post_id'], $context['job_id'], $context['channel'] );

		$line = sprintf(
			'%s %-5s req=%s step=%s post=%s job=%s ch=%s | %s',
			self::now(),
			strtoupper( $level ),
			self::request_id(),
			$step,
			is_scalar( $post_id ) ? $post_id : '?',
			is_scalar( $job_id ) ? $job_id : '?',
			is_scalar( $channel ) ? $channel : '?',
			$message
		);

		if ( $context ) {
			$json = json_encode(
				self::mask( $context ),
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
			);
			$line .= ' ' . ( false === $json ? '{"log_error":"không mã hoá được dữ liệu kèm theo"}' : $json );
		}

		self::append( str_replace( array( "\r", "\n" ), array( '', ' ⏎ ' ), $line ) . "\n" );
	}

	/** Thời điểm hiện tại theo giờ của site, chính xác tới mili giây. */
	public static function now(): string {
		$dt = DateTimeImmutable::createFromFormat( 'U.u', sprintf( '%.6F', microtime( true ) ) );
		if ( false === $dt ) {
			return gmdate( 'Y-m-d\TH:i:s\Z' );
		}
		return $dt->setTimezone( wp_timezone() )->format( 'Y-m-d\TH:i:s.vP' );
	}

	/**
	 * Che khoá bí mật và cắt chuỗi dài, đệ quy trên mảng.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public static function mask( $value, string $key = '' ) {
		if ( '' !== $key && preg_match( self::SECRET_KEY_PATTERN, $key ) ) {
			return ( null === $value || '' === $value ) ? $value : '***';
		}
		if ( $value instanceof WP_Error ) {
			return array(
				'wp_error_codes'    => $value->get_error_codes(),
				'wp_error_messages' => $value->get_error_messages(),
			);
		}
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $k => $v ) {
				$out[ $k ] = self::mask( $v, (string) $k );
			}
			return $out;
		}
		if ( is_string( $value ) && mb_strlen( $value ) > self::MAX_STRING ) {
			return mb_substr( $value, 0, self::MAX_STRING ) . sprintf( '…(cắt bớt, tổng %d ký tự)', mb_strlen( $value ) );
		}
		return $value;
	}

	/** Rút gọn URL để log không lộ đường dẫn webhook đầy đủ và không lộ query string. */
	public static function mask_url( string $url ): string {
		$p = wp_parse_url( $url );
		if ( ! is_array( $p ) || empty( $p['host'] ) ) {
			return '(url không hợp lệ)';
		}
		$path  = $p['path'] ?? '';
		$shown = strlen( $path ) > 12 ? substr( $path, 0, 12 ) . '***' : $path;
		return ( $p['scheme'] ?? 'http' ) . '://' . $p['host'] . ( isset( $p['port'] ) ? ':' . $p['port'] : '' ) . $shown;
	}

	/** URL bỏ query string, giữ nguyên host và path (dùng cho HTTP gọi ra ngoài). */
	public static function strip_query( string $url ): string {
		$pos = strpos( $url, '?' );
		return false === $pos ? $url : substr( $url, 0, $pos ) . '?…';
	}

	/**
	 * Đọc N dòng cuối của file log (để hiện trong wp-admin).
	 *
	 * @return string[]
	 */
	public static function tail( int $lines = 40 ): array {
		$file = self::log_file();
		if ( ! is_readable( $file ) ) {
			return array();
		}
		clearstatcache( true, $file );
		$size = (int) filesize( $file );
		if ( $size <= 0 ) {
			return array();
		}
		$read = min( $size, 65536 );
		$fh   = fopen( $file, 'rb' );
		if ( false === $fh ) {
			return array();
		}
		fseek( $fh, -$read, SEEK_END );
		$chunk = (string) fread( $fh, $read );
		fclose( $fh );

		$all = explode( "\n", rtrim( $chunk, "\n" ) );
		if ( $read < $size ) {
			array_shift( $all ); // Dòng đầu có thể bị cắt giữa chừng.
		}
		return array_slice( $all, -$lines );
	}

	private static function append( string $line ): void {
		$file = self::log_file();
		$dir  = dirname( $file );
		if ( ! is_dir( $dir ) ) {
			@mkdir( $dir, 0775, true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		clearstatcache( true, $file );
		if ( is_file( $file ) && filesize( $file ) > self::MAX_BYTES ) {
			@rename( $file, $file . '.1' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		$ok = @file_put_contents( $file, $line, FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( false === $ok && ! self::$write_failure_reported ) {
			self::$write_failure_reported = true;
			error_log( '[SocialSync] Không ghi được file log ' . $file . ' . Dòng log: ' . $line ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}
	}
}
