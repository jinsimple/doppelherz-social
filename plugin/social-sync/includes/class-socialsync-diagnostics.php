<?php
/**
 * Log chẩn đoán, bật/tắt trong trang cấu hình:
 * - log_requests: mọi request vào website (header Cloudflare, cách nhận diện HTTPS).
 * - log_http:     mọi HTTP mà WordPress gọi ra ngoài (URL, mã trạng thái, thời gian, giới hạn tốc độ, body khi lỗi).
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_Diagnostics {

	/** @var array<string, float[]> */
	private static array $http_started = array();

	public static function init(): void {
		if ( SocialSync_Settings::get( 'log_requests' ) ) {
			add_action( 'init', array( __CLASS__, 'log_request' ), 0 );
		}
		if ( SocialSync_Settings::get( 'log_http' ) ) {
			add_filter( 'pre_http_request', array( __CLASS__, 'http_start' ), 0, 3 );
			add_action( 'http_api_debug', array( __CLASS__, 'http_done' ), 10, 5 );
		}
	}

	public static function log_request(): void {
		$server = static function ( string $key ): ?string {
			return isset( $_SERVER[ $key ] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER[ $key ] ) ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		};
		$uri = (string) $server( 'REQUEST_URI' );

		SocialSync_Logger::debug(
			'request.in',
			'Request vào website',
			array(
				'method'            => $server( 'REQUEST_METHOD' ),
				'path'              => strtok( $uri, '?' ),
				'has_query'         => false !== strpos( $uri, '?' ),
				'host'              => $server( 'HTTP_HOST' ),
				'cf_visitor'        => $server( 'HTTP_CF_VISITOR' ),
				'cf_ray'            => $server( 'HTTP_CF_RAY' ),
				'x_forwarded_proto' => $server( 'HTTP_X_FORWARDED_PROTO' ),
				'server_https'      => $server( 'HTTPS' ),
				'is_ssl'            => is_ssl(),
				'client_ip'         => self::mask_ip( $server( 'HTTP_CF_CONNECTING_IP' ) ?? $server( 'REMOTE_ADDR' ) ),
				'user_agent'        => mb_substr( (string) $server( 'HTTP_USER_AGENT' ), 0, 120 ),
				'user_id'           => get_current_user_id(),
				'context'           => SocialSync_Publish_Hook::context(),
			)
		);
	}

	/**
	 * @param false|array|WP_Error $pre
	 * @return false|array|WP_Error
	 */
	public static function http_start( $pre, $args, $url ) {
		self::$http_started[ (string) $url ][] = microtime( true );
		return $pre;
	}

	/**
	 * @param array|WP_Error $response
	 */
	public static function http_done( $response, $context, $class, $args, $url ): void {
		$url   = (string) $url;
		$start = null;
		if ( ! empty( self::$http_started[ $url ] ) ) {
			$start = array_pop( self::$http_started[ $url ] );
		}
		$ms = null === $start ? null : (int) round( ( microtime( true ) - $start ) * 1000 );

		$data = array(
			'method' => is_array( $args ) ? ( $args['method'] ?? 'GET' ) : null,
			'url'    => SocialSync_Logger::strip_query( $url ),
			'ms'     => $ms,
		);

		if ( is_wp_error( $response ) ) {
			$data['error'] = $response;
			SocialSync_Logger::warn( 'http.out', 'HTTP gọi ra ngoài lỗi kết nối', $data );
			return;
		}

		$code               = (int) wp_remote_retrieve_response_code( $response );
		$data['http_code']  = $code;
		$data['rate_limit'] = array_filter(
			array(
				'retry-after'           => wp_remote_retrieve_header( $response, 'retry-after' ),
				'x-ratelimit-limit'     => wp_remote_retrieve_header( $response, 'x-ratelimit-limit' ),
				'x-ratelimit-remaining' => wp_remote_retrieve_header( $response, 'x-ratelimit-remaining' ),
				'x-ratelimit-reset'     => wp_remote_retrieve_header( $response, 'x-ratelimit-reset' ),
			)
		);
		if ( $code >= 400 ) {
			$data['body'] = mb_substr( (string) wp_remote_retrieve_body( $response ), 0, 1000 );
		}
		SocialSync_Logger::write( $code >= 400 ? 'warn' : 'debug', 'http.out', 'HTTP gọi ra ngoài', $data );
	}

	private static function mask_ip( ?string $ip ): ?string {
		if ( null === $ip || '' === $ip ) {
			return $ip;
		}
		if ( false !== strpos( $ip, ':' ) ) {
			$parts = explode( ':', $ip );
			return implode( ':', array_slice( $parts, 0, 3 ) ) . ':…';
		}
		return (string) preg_replace( '/\.\d+$/', '.x', $ip );
	}
}
