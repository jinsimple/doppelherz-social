<?php
/**
 * In thẻ meta ra phần <head> của website (phía khách xem).
 *
 * Hiện có: thẻ xác minh website của Pinterest  <meta name="p:domain_verify" content="..."/>
 *
 * Log:
 * - Máy dò của Pinterest ghé trang (User-Agent chứa "pinterest"): ghi mức info, kèm có in thẻ hay không,
 *   để biết Pinterest đã thấy thẻ chưa khi bấm "Xác minh".
 * - Khách thường: ghi mức debug.
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_Site_Meta {

	public static function init(): void {
		add_action( 'wp_head', array( __CLASS__, 'print_meta' ), 1 );
	}

	public static function print_meta(): void {
		$code = SocialSync_Settings::pinterest_verify();
		$ua   = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 200 ) : '';
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? SocialSync_Logger::strip_query( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : '';
		$bot  = false !== stripos( $ua, 'pinterest' );

		if ( '' !== $code ) {
			printf( "<meta name=\"p:domain_verify\" content=\"%s\"/>\n", esc_attr( $code ) );
		}

		$context = array(
			'printed'  => '' !== $code,
			'code_len' => strlen( $code ),
			'uri'      => $uri,
			'ua'       => $ua,
			'cf_ip'    => isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) : '',
		);
		if ( $bot ) {
			SocialSync_Logger::info( 'site_meta.pinterest_bot', '' !== $code ? 'Máy dò Pinterest ghé trang, đã in thẻ xác minh' : 'Máy dò Pinterest ghé trang nhưng CHƯA có mã xác minh', $context );
		} else {
			SocialSync_Logger::debug( 'site_meta.head', '' !== $code ? 'In thẻ xác minh Pinterest' : 'Chưa có mã xác minh Pinterest, không in thẻ', $context );
		}
	}
}
