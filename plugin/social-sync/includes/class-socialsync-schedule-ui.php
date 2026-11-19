<?php
/**
 * Ô "Lịch đăng mạng xã hội" trong màn hình viết bài.
 *
 * - Trình soạn khối (bài viết): bảng bên phải (assets/schedule-panel.js). Giá trị là post meta có show_in_rest,
 *   lưu cùng request REST với bài, nên đã có sẵn khi hook wp_after_insert_post chạy.
 * - Trình soạn cổ điển (sản phẩm WooCommerce): hộp meta, lưu ở save_post (chạy trước wp_after_insert_post).
 * Để trống = bộ xếp lịch tự chọn giờ (khung giờ, số bài tối đa/ngày, khoảng cách tối thiểu).
 * Chỉ có tác dụng ở lần xuất bản đầu tiên; sau đó đổi giờ trong màn hình Chờ duyệt.
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_Schedule_UI {

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 5, 2 );
	}

	public static function register_meta(): void {
		foreach ( SocialSync_Settings::post_types() as $type ) {
			foreach ( array_keys( SocialSync_Settings::CHANNELS ) as $channel ) {
				register_post_meta(
					$type,
					SocialSync_Scheduler::META_PREFIX . $channel,
					array(
						'type'              => 'string',
						'single'            => true,
						'default'           => '',
						'show_in_rest'      => true,
						'sanitize_callback' => array( __CLASS__, 'sanitize' ),
						'auth_callback'     => static fn( $allowed, $key, $post_id ) => current_user_can( 'edit_post', (int) $post_id ),
					)
				);
			}
		}
	}

	/** Chỉ nhận dạng 2026-11-20T08:30 (giờ website) hoặc rỗng. */
	public static function sanitize( $value ): string {
		$v = trim( (string) $value );
		return preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $v ) ? $v : '';
	}

	public static function enqueue(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && ! in_array( $screen->post_type, SocialSync_Settings::post_types(), true ) ) {
			return;
		}
		wp_enqueue_script(
			'socialsync-schedule-panel',
			plugins_url( 'assets/schedule-panel.js', SOCIALSYNC_FILE ),
			array( 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data' ),
			SOCIALSYNC_VERSION,
			true
		);
		wp_localize_script(
			'socialsync-schedule-panel',
			'SocialSyncSchedule',
			array(
				'channels' => SocialSync_Settings::CHANNELS,
				'prefix'   => SocialSync_Scheduler::META_PREFIX,
				'enabled'  => SocialSync_Settings::enabled_channels(),
				'window'   => SocialSync_Settings::get( 'window_start' ) . '–' . SocialSync_Settings::get( 'window_end' ),
				'approval' => SocialSync_Settings::approval_required(),
			)
		);
	}

	public static function meta_box(): void {
		foreach ( SocialSync_Settings::post_types() as $type ) {
			add_meta_box(
				'socialsync-schedule',
				'Lịch đăng mạng xã hội',
				array( __CLASS__, 'render_box' ),
				$type,
				'side',
				'default',
				array( '__back_compat_meta_box' => true )
			);
		}
	}

	public static function render_box( WP_Post $post ): void {
		wp_nonce_field( 'socialsync_schedule_' . $post->ID, 'socialsync_schedule_nonce' );
		echo '<p class="description">Để trống: hệ thống tự chọn giờ trong khung ' . esc_html( SocialSync_Settings::get( 'window_start' ) . '–' . SocialSync_Settings::get( 'window_end' ) ) . '. Chỉ áp dụng khi xuất bản lần đầu.</p>';
		foreach ( SocialSync_Settings::CHANNELS as $channel => $label ) {
			$key = SocialSync_Scheduler::META_PREFIX . $channel;
			printf(
				'<p><label>%s<br><input type="datetime-local" name="%s" value="%s" style="width:100%%"></label></p>',
				esc_html( $label ),
				esc_attr( $key ),
				esc_attr( (string) get_post_meta( $post->ID, $key, true ) )
			);
		}
	}

	public static function save( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST['socialsync_schedule_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['socialsync_schedule_nonce'] ) ), 'socialsync_schedule_' . $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		$saved = array();
		foreach ( array_keys( SocialSync_Settings::CHANNELS ) as $channel ) {
			$key = SocialSync_Scheduler::META_PREFIX . $channel;
			if ( isset( $_POST[ $key ] ) ) {
				$val = self::sanitize( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				update_post_meta( $post_id, $key, $val );
				$saved[ $channel ] = $val;
			}
		}
		SocialSync_Logger::debug( 'schedule.meta_saved', 'Lưu giờ đăng người viết chọn (hộp meta)', array( 'post_id' => $post_id, 'values' => $saved ) );
	}
}
