<?php
/**
 * Bắt sự kiện xuất bản lần đầu của bài viết / sản phẩm.
 *
 * Dùng hook wp_after_insert_post (chạy sau khi WordPress đã lưu bài, meta, danh mục, ảnh đại diện,
 * kể cả khi lưu qua REST API của trình soạn thảo khối). Mọi quyết định "có tạo lượt hay không"
 * đều được ghi log kèm lý do.
 *
 * Việc gửi sang n8n dồn lại tới cuối request (hook shutdown), để WooCommerce kịp lưu giá và ảnh
 * khi sản phẩm được tạo qua REST API / WP-CLI.
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_Publish_Hook {

	/** @var array<int, array{jobs:array, queued_at:float}> */
	private static array $queue = array();

	public static function init(): void {
		add_action( 'wp_after_insert_post', array( __CLASS__, 'on_after_insert_post' ), 20, 4 );
		add_action( 'shutdown', array( __CLASS__, 'flush_queue' ), 1 );
	}

	/** Request hiện tại đến từ đâu: rest | cron | cli | ajax | admin | front. */
	public static function context(): string {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return 'cli';
		}
		if ( wp_doing_cron() ) {
			return 'cron';
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return 'rest';
		}
		if ( wp_doing_ajax() ) {
			return 'ajax';
		}
		return is_admin() ? 'admin' : 'front';
	}

	/**
	 * @param int          $post_id
	 * @param WP_Post      $post
	 * @param bool         $update
	 * @param WP_Post|null $post_before
	 */
	public static function on_after_insert_post( $post_id, $post, $update, $post_before ): void {
		$post_id = (int) $post_id;
		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post_id );
		}
		if ( ! $post instanceof WP_Post ) {
			SocialSync_Logger::warn( 'hook.no_post', 'Hook chạy nhưng không đọc được bài', array( 'post_id' => $post_id ) );
			return;
		}

		$before = $post_before instanceof WP_Post ? $post_before->post_status : '(chưa có)';
		$ctx    = array(
			'post_id'       => $post_id,
			'post_type'     => $post->post_type,
			'status_before' => $before,
			'status_after'  => $post->post_status,
			'update'        => (bool) $update,
			'context'       => self::context(),
			'user_id'       => get_current_user_id(),
		);

		$skip = self::skip_reason( $post, $before );
		if ( null !== $skip ) {
			// Bản sửa đổi và lưu tự động xảy ra liên tục: chỉ ghi ở mức debug.
			$level = in_array( $post->post_type, SocialSync_Settings::post_types(), true ) ? 'info' : 'debug';
			SocialSync_Logger::write( $level, 'hook.skip', 'Không tạo lượt đồng bộ: ' . $skip, $ctx );
			return;
		}

		$missing = SocialSync_Settings::missing();
		if ( $missing ) {
			SocialSync_Logger::warn( 'hook.skip', 'Không tạo lượt đồng bộ: thiếu cấu hình (' . implode( '; ', $missing ) . ')', $ctx );
			return;
		}

		$channels = SocialSync_Settings::enabled_channels();
		if ( ! $channels ) {
			SocialSync_Logger::info( 'hook.skip', 'Không tạo lượt đồng bộ: không bật kênh nào', $ctx );
			return;
		}

		SocialSync_Logger::info( 'hook.publish_detected', 'Phát hiện bài xuất bản lần đầu', $ctx + array( 'channels' => $channels ) );

		$created = SocialSync_DB::create_jobs( $post_id, $post->post_type, $channels );
		if ( ! $created ) {
			SocialSync_Logger::info( 'hook.skip', 'Không gửi sang n8n: mọi kênh đã có lượt đồng bộ từ trước (chống trùng)', $ctx );
			return;
		}

		if ( isset( self::$queue[ $post_id ] ) ) {
			self::$queue[ $post_id ]['jobs'] = array_merge( self::$queue[ $post_id ]['jobs'], $created );
		} else {
			self::$queue[ $post_id ] = array(
				'jobs'      => $created,
				'queued_at' => microtime( true ),
			);
		}
		SocialSync_Logger::info(
			'hook.queued',
			'Đưa vào hàng gửi, sẽ gửi sang n8n ở cuối request',
			$ctx + array( 'job_ids' => array_column( $created, 'job_id' ) )
		);
	}

	/** Lý do bỏ qua, hoặc null nếu cần tạo lượt đồng bộ. */
	private static function skip_reason( WP_Post $post, string $before ): ?string {
		if ( wp_is_post_revision( $post ) ) {
			return 'đây là bản sửa đổi (revision)';
		}
		if ( wp_is_post_autosave( $post ) ) {
			return 'đây là bản lưu tự động';
		}
		if ( ! in_array( $post->post_type, SocialSync_Settings::post_types(), true ) ) {
			return 'loại nội dung "' . $post->post_type . '" không bật đồng bộ';
		}
		if ( 'publish' !== $post->post_status ) {
			return 'trạng thái là "' . $post->post_status . '", chưa xuất bản công khai';
		}
		if ( 'publish' === $before ) {
			return 'bài đã xuất bản từ trước, đây chỉ là cập nhật';
		}
		if ( '' !== (string) $post->post_password ) {
			return 'bài được đặt mật khẩu';
		}
		return null;
	}

	/** Chuyển các bài trong hàng sang tiến trình nền để gửi sang n8n. Chạy ở cuối request. */
	public static function flush_queue(): void {
		if ( ! self::$queue ) {
			return;
		}
		ignore_user_abort( true );

		$queue       = self::$queue;
		self::$queue = array();

		foreach ( $queue as $post_id => $item ) {
			SocialSync_Logger::info(
				'dispatch.start',
				'Bắt đầu chuyển bài sang n8n',
				array(
					'post_id'   => $post_id,
					'job_ids'   => array_column( $item['jobs'], 'job_id' ),
					'waited_ms' => (int) round( ( microtime( true ) - $item['queued_at'] ) * 1000 ),
					'context'   => self::context(),
					'mode'      => SocialSync_Settings::dispatch_mode(),
				)
			);
			try {
				SocialSync_Dispatcher::kick( (int) $post_id, $item['jobs'] );
			} catch ( Throwable $e ) {
				SocialSync_Logger::error(
					'dispatch.exception',
					'Lỗi không lường trước khi gửi bài',
					array(
						'post_id'   => $post_id,
						'exception' => get_class( $e ),
						'message'   => $e->getMessage(),
						'at'        => $e->getFile() . ':' . $e->getLine(),
					)
				);
			}
		}
	}
}
