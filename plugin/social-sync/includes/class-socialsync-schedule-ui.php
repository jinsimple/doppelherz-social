<?php
/**
 * Ô "Lịch đăng mạng xã hội" trong màn hình viết bài.
 *
 * - Trình soạn khối (bài viết): bảng bên phải (assets/schedule-panel.js). Giá trị là post meta có show_in_rest,
 *   lưu cùng request REST với bài, nên đã có sẵn khi hook wp_after_insert_post chạy.
 * - Trình soạn cổ điển (sản phẩm WooCommerce): hộp meta, lưu ở save_post (chạy trước wp_after_insert_post).
 * Để trống = bộ xếp lịch tự chọn giờ (khung giờ, số bài tối đa/ngày, khoảng cách tối thiểu).
 * Chỉ có tác dụng ở lần xuất bản đầu tiên; sau đó đổi giờ khi duyệt trong Trung tâm SocialSync.
 * Sau khi xuất bản, panel/hộp meta hiện trạng thái từng kênh và link mở đúng bài trong Trung tâm.
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
			SocialSync_Hub::asset_ver( 'schedule-panel.js' ),
			true
		);
		$post_id = isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof WP_Post ? (int) $GLOBALS['post']->ID : 0;
		$can_hub = current_user_can( SocialSync_Log_Page::CAP );
		wp_localize_script(
			'socialsync-schedule-panel',
			'SocialSyncSchedule',
			array(
				'channels' => SocialSync_Settings::CHANNELS,
				'prefix'   => SocialSync_Scheduler::META_PREFIX,
				'enabled'  => SocialSync_Settings::enabled_channels(),
				'window'   => SocialSync_Settings::get( 'window_start' ) . '–' . SocialSync_Settings::get( 'window_end' ),
				'approval' => SocialSync_Settings::approval_required(),
				'postId'   => $post_id,
				'status'   => SocialSync_Hub::status_for_editor( $post_id ),
				'hubUrl'   => $can_hub ? SocialSync_Hub::url() : '',
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'socialsync_post_status' ),
			)
		);
		SocialSync_Logger::debug( 'schedule.panel_enqueue', 'Nạp panel lịch đăng trong trình soạn', array( 'post_id' => $post_id, 'can_hub' => $can_hub, 'jobs' => count( SocialSync_DB::table_exists() ? SocialSync_DB::jobs( $post_id, 10 ) : array() ) ) );
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
		$status = SocialSync_Hub::status_for_editor( (int) $post->ID );
		if ( $status ) {
			// Đã có lượt đồng bộ: giờ ở đây không còn tác dụng, hiện trạng thái kênh và đường sang Trung tâm.
			echo '<ul style="margin:0 0 8px">';
			foreach ( $status as $s ) {
				printf( '<li><strong>%s:</strong> %s%s</li>', esc_html( $s['label'] ), esc_html( $s['text'] ), $s['url'] ? ' · <a href="' . esc_url( $s['url'] ) . '" target="_blank" rel="noopener">mở ↗</a>' : '' );
			}
			echo '</ul>';
			if ( current_user_can( SocialSync_Log_Page::CAP ) ) {
				printf( '<p><a class="button" href="%s">Mở trong Trung tâm SocialSync →</a></p>', esc_url( SocialSync_Hub::url( (int) $post->ID ) ) );
			}
			echo '<p class="description">Đổi giờ đăng khi duyệt trong Trung tâm.</p>';
			return;
		}
		echo '<p class="description">Để trống: hệ thống tự chọn giờ trong khung ' . esc_html( SocialSync_Settings::get( 'window_start' ) . '–' . SocialSync_Settings::get( 'window_end' ) ) . '. Chỉ áp dụng khi xuất bản lần đầu.';
		echo SocialSync_Settings::approval_required() ? ' Sau khi đăng, AI viết nội dung (10–30 giây) rồi chờ duyệt trong Trung tâm SocialSync.' : ' Sau khi đăng, AI viết nội dung rồi tự đăng theo giờ.';
		echo '</p>';
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
