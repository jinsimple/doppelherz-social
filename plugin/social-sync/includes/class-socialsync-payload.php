<?php
/**
 * Dựng dữ liệu bài gửi sang n8n (PRD mục 7.2).
 *
 * Dữ liệu được dựng ở cuối request (hook shutdown), không phải lúc bài vừa chuyển trạng thái,
 * vì lúc đó WooCommerce có thể chưa lưu giá và ảnh sản phẩm.
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_Payload {

	public const SCHEMA_VERSION = 1;

	/**
	 * @param array<int, array{job_id:int, channel:string, delay_minutes:int}> $jobs
	 */
	public static function build( int $post_id, array $jobs ): array {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			throw new RuntimeException( 'Không tìm thấy bài viết #' . $post_id );
		}

		$is_product   = 'product' === $post->post_type;
		$content_text = self::to_text( (string) $post->post_content );
		$excerpt      = has_excerpt( $post )
			? self::to_text( (string) $post->post_excerpt )
			: wp_trim_words( $content_text, 55, '…' );

		$thumb_id = (int) get_post_thumbnail_id( $post );
		$image    = self::image( $thumb_id );

		$taxonomy   = $is_product ? 'product_cat' : 'category';
		$categories = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
		if ( is_wp_error( $categories ) ) {
			SocialSync_Logger::warn(
				'payload.categories_error',
				'Không đọc được danh mục',
				array(
					'post_id'  => $post_id,
					'taxonomy' => $taxonomy,
					'error'    => $categories,
				)
			);
			$categories = array();
		}

		$payload = array(
			'event'          => 'post_published',
			'schema_version' => self::SCHEMA_VERSION,
			'request_id'     => SocialSync_Logger::request_id(),
			'sent_at'        => SocialSync_Logger::now(),
			'site'           => array(
				'name'     => html_entity_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ),
				'home_url' => home_url( '/' ),
				'language' => get_bloginfo( 'language' ),
			),
			'post_id'        => $post_id,
			'post_type'      => $post->post_type,
			'source_url'     => (string) get_permalink( $post ),
			'title'          => html_entity_decode( (string) get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
			'content_text'   => $content_text,
			'excerpt'        => $excerpt,
			'price'          => $is_product ? self::price( $post_id ) : null,
			'image_url'      => $image['url'],
			'image_alt'      => $image['alt'],
			'image'          => $image,
			'categories'     => array_values( array_map( 'strval', (array) $categories ) ),
			'author'         => (string) get_the_author_meta( 'display_name', (int) $post->post_author ),
			'published_at'   => (string) get_post_time( 'c', false, $post ),
			'jobs'           => array_values( $jobs ),
			'callback_url'   => SocialSync_Settings::callback_url(),
		);

		SocialSync_Logger::info(
			'payload.built',
			'Dựng xong dữ liệu bài',
			array(
				'post_id'       => $post_id,
				'job_ids'       => array_column( $jobs, 'job_id' ),
				'post_type'     => $post->post_type,
				'title'         => $payload['title'],
				'source_url'    => $payload['source_url'],
				'content_chars' => mb_strlen( $content_text ),
				'excerpt_chars' => mb_strlen( $excerpt ),
				'excerpt_from'  => has_excerpt( $post ) ? 'post_excerpt' : 'cắt từ nội dung',
				'has_image'     => '' !== $image['url'],
				'image'         => $image,
				'categories'    => $payload['categories'],
				'price'         => $payload['price'],
			)
		);

		if ( '' === $image['url'] ) {
			SocialSync_Logger::warn(
				'payload.no_image',
				'Bài không có ảnh đại diện: Facebook sẽ đăng dạng link, Pinterest sẽ bỏ qua',
				array( 'post_id' => $post_id )
			);
		}
		if ( '' === trim( $content_text ) ) {
			SocialSync_Logger::warn( 'payload.empty_content', 'Nội dung bài rỗng sau khi bỏ HTML', array( 'post_id' => $post_id ) );
		}

		return $payload;
	}

	/** Dữ liệu cho nút "Gửi thử". */
	public static function test(): array {
		return array(
			'event'          => 'test',
			'schema_version' => self::SCHEMA_VERSION,
			'request_id'     => SocialSync_Logger::request_id(),
			'sent_at'        => SocialSync_Logger::now(),
			'site'           => array(
				'name'     => html_entity_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ),
				'home_url' => home_url( '/' ),
			),
			'callback_url'   => SocialSync_Settings::callback_url(),
		);
	}

	/** Đổi HTML (kể cả block Gutenberg, shortcode) thành văn bản thuần, giữ xuống dòng giữa các đoạn. */
	public static function to_text( string $html ): string {
		$text = strip_shortcodes( $html );
		$text = (string) preg_replace( '#</(p|h[1-6]|li|div|blockquote|figcaption|tr|ul|ol|table)>|<br\s*/?>#i', "$0\n", $text );
		$text = wp_strip_all_tags( $text, false );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = str_replace( "\xC2\xA0", ' ', $text ); // Khoảng trắng không ngắt dòng.
		$text = (string) preg_replace( "/[ \t]+/u", ' ', $text );
		$text = (string) preg_replace( "/ *\n */u", "\n", $text );
		$text = (string) preg_replace( "/\n{3,}/u", "\n\n", $text );
		return trim( $text );
	}

	/** @return array{id:int, url:string, alt:string, width:int, height:int, mime:string} */
	private static function image( int $thumb_id ): array {
		$empty = array(
			'id'     => 0,
			'url'    => '',
			'alt'    => '',
			'width'  => 0,
			'height' => 0,
			'mime'   => '',
		);
		if ( $thumb_id <= 0 ) {
			return $empty;
		}
		$src = wp_get_attachment_image_src( $thumb_id, 'full' );
		if ( ! is_array( $src ) ) {
			SocialSync_Logger::warn( 'payload.image_missing', 'Có ảnh đại diện nhưng không đọc được file ảnh', array( 'attachment_id' => $thumb_id ) );
			return $empty;
		}
		return array(
			'id'     => $thumb_id,
			'url'    => (string) $src[0],
			'alt'    => trim( (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) ),
			'width'  => (int) $src[1],
			'height' => (int) $src[2],
			'mime'   => (string) get_post_mime_type( $thumb_id ),
		);
	}

	/** @return array<string, mixed>|null */
	private static function price( int $post_id ): ?array {
		if ( ! function_exists( 'wc_get_product' ) ) {
			SocialSync_Logger::warn( 'payload.no_woocommerce', 'Bài là sản phẩm nhưng WooCommerce không chạy, bỏ qua giá', array( 'post_id' => $post_id ) );
			return null;
		}
		$product = wc_get_product( $post_id );
		if ( ! $product ) {
			SocialSync_Logger::warn( 'payload.product_missing', 'wc_get_product không trả về sản phẩm', array( 'post_id' => $post_id ) );
			return null;
		}
		$price    = (string) $product->get_price();
		$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';
		$text     = '' !== $price && function_exists( 'wc_price' )
			? trim( html_entity_decode( wp_strip_all_tags( wc_price( (float) $price ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) )
			: '';
		return array(
			'price'         => $price,
			'regular_price' => (string) $product->get_regular_price(),
			'sale_price'    => (string) $product->get_sale_price(),
			'currency'      => $currency,
			'price_text'    => str_replace( "\xC2\xA0", ' ', $text ),
			'product_type'  => $product->get_type(),
			'sku'           => (string) $product->get_sku(),
			'stock_status'  => (string) $product->get_stock_status(),
		);
	}
}
