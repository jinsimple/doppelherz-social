<?php
/**
 * Địa chỉ REST của plugin. Mọi route đều yêu cầu header X-SocialSync-Secret đúng.
 *
 *   GET  /wp-json/social-sync/v1/status      n8n kiểm tra kết nối và mã bí mật.
 *   POST /wp-json/social-sync/v1/jobs/{id}   n8n báo trạng thái / kết quả của một lượt (PRD mục 7.3).
 *   POST /wp-json/social-sync/v1/dispatch    Tiến trình nền do chính plugin mở để gửi bài sang n8n.
 *   POST /wp-json/social-sync/v1/executions/{id}/failed   Luồng báo lỗi của n8n: một lần chạy bị lỗi giữa chừng.
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_REST {

	public const NS              = 'social-sync/v1';
	public const N8N_WORKFLOW_ID = 'SocialSyncMain01';
	public const VISIBILITIES    = array( 'public', 'private', 'sandbox', 'unknown' );
	public const CONTENT_SOURCES = array( 'ai', 'template' );

	/**
	 * Chuyển trạng thái n8n được phép báo về (PRD mục 4).
	 * Cho phép bỏ qua bước trung gian, ví dụ pending -> published khi đăng ngay bằng mẫu.
	 * failed -> pending chỉ do WordPress làm (nút Đồng bộ lại), không nhận từ n8n.
	 */
	private const TRANSITIONS = array(
		'pending'      => array( 'generating', 'needs_review', 'scheduled', 'published', 'failed', 'skipped' ),
		'generating'   => array( 'needs_review', 'scheduled', 'published', 'failed', 'skipped' ),
		'needs_review' => array( 'scheduled', 'rejected', 'failed' ),
		'scheduled'    => array( 'published', 'failed', 'skipped' ),
		'failed'       => array(),
		'published'    => array(),
		'skipped'      => array(),
		'rejected'     => array(),
	);

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes(): void {
		register_rest_route(
			self::NS,
			'/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'status' ),
				'permission_callback' => array( __CLASS__, 'auth' ),
			)
		);
		register_rest_route(
			self::NS,
			'/jobs/(?P<id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'update_job' ),
				'permission_callback' => array( __CLASS__, 'auth' ),
			)
		);
		register_rest_route(
			self::NS,
			'/executions/(?P<id>\d+)/failed',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'execution_failed' ),
				'permission_callback' => array( __CLASS__, 'auth' ),
			)
		);
		register_rest_route(
			self::NS,
			'/previews/(?P<id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'preview_result' ),
				'permission_callback' => array( __CLASS__, 'auth' ),
			)
		);
		register_rest_route(
			self::NS,
			'/report',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'report_data' ),
				'permission_callback' => array( __CLASS__, 'auth' ),
			)
		);
		register_rest_route(
			self::NS,
			'/metrics',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'save_metrics' ),
				'permission_callback' => array( __CLASS__, 'auth' ),
			)
		);
		register_rest_route(
			self::NS,
			'/reports',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'save_report' ),
				'permission_callback' => array( __CLASS__, 'auth' ),
			)
		);
		register_rest_route(
			self::NS,
			'/dispatch',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'dispatch' ),
				'permission_callback' => array( __CLASS__, 'auth' ),
			)
		);
	}

	/** @return true|WP_Error */
	public static function auth( WP_REST_Request $req ) {
		$given    = (string) $req->get_header( 'X-SocialSync-Secret' );
		$expected = SocialSync_Settings::secret();

		if ( '' !== $expected && '' !== $given && hash_equals( $expected, $given ) ) {
			SocialSync_Logger::adopt_request_id( (string) $req->get_header( 'X-SocialSync-Request-Id' ) );
			return true;
		}

		SocialSync_Logger::warn(
			'rest.auth_failed',
			'Từ chối request: sai hoặc thiếu mã bí mật',
			array(
				'route'             => $req->get_route(),
				'method'            => $req->get_method(),
				'has_secret_header' => '' !== $given,
				'given_chars'       => strlen( $given ),
				'server_has_secret' => '' !== $expected,
				'remote_addr'       => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : null, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			)
		);
		return new WP_Error( 'socialsync_forbidden', 'Sai hoặc thiếu mã bí mật', array( 'status' => 401 ) );
	}

	public static function status( WP_REST_Request $req ): WP_REST_Response {
		SocialSync_Logger::info( 'rest.status', 'n8n kiểm tra kết nối về WordPress', array( 'route' => $req->get_route() ) );
		return new WP_REST_Response(
			array(
				'ok'             => true,
				'plugin_version' => SOCIALSYNC_VERSION,
				'time'           => SocialSync_Logger::now(),
				'table_exists'   => SocialSync_DB::table_exists(),
				'channels'       => SocialSync_Settings::enabled_channels(),
			),
			200
		);
	}

	/** n8n báo trạng thái hoặc kết quả của một lượt đồng bộ. */
	public static function update_job( WP_REST_Request $req ): WP_REST_Response {
		$job_id = (int) $req['id'];
		$body   = $req->get_json_params();
		$body   = is_array( $body ) ? $body : array();
		$job    = SocialSync_DB::get_job( $job_id );

		$log = array(
			'job_id'  => $job_id,
			'post_id' => $job['post_id'] ?? null,
			'channel' => $job['channel'] ?? null,
			'body'    => $body,
		);
		SocialSync_Logger::info( 'callback.in', 'n8n báo kết quả về', $log );

		if ( null === $job ) {
			SocialSync_Logger::warn( 'callback.not_found', 'Không có lượt đồng bộ này', $log );
			return self::reply( 404, array( 'ok' => false, 'error' => "Không có lượt đồng bộ #{$job_id}" ) );
		}

		$problems = array();
		$fields   = array();
		$before   = (string) $job['status'];
		$after    = $before;

		if ( array_key_exists( 'status', $body ) ) {
			$after = (string) $body['status'];
			if ( ! in_array( $after, SocialSync_DB::STATUSES, true ) ) {
				$problems[] = 'status không hợp lệ: ' . $after;
			} elseif ( $after !== $before && ! in_array( $after, self::TRANSITIONS[ $before ] ?? array(), true ) ) {
				SocialSync_Logger::warn(
					'callback.bad_transition',
					"Không cho phép chuyển trạng thái {$before} -> {$after}",
					$log + array( 'allowed' => self::TRANSITIONS[ $before ] ?? array() )
				);
				return self::reply(
					409,
					array(
						'ok'      => false,
						'error'   => "Không cho phép chuyển trạng thái {$before} -> {$after}",
						'allowed' => self::TRANSITIONS[ $before ] ?? array(),
					)
				);
			} else {
				$fields['status'] = $after;
			}
		}

		// Các trường văn bản ngắn.
		$text_fields = array(
			'content_source_reason' => 255,
			'model'                 => 64,
			'remote_id'             => 191,
			'execution_id'          => 64,
		);
		foreach ( $text_fields as $key => $max ) {
			if ( array_key_exists( $key, $body ) ) {
				$fields[ $key ] = null === $body[ $key ] ? null : mb_substr( sanitize_text_field( (string) $body[ $key ] ), 0, $max );
			}
		}

		if ( array_key_exists( 'content_source', $body ) ) {
			if ( in_array( $body['content_source'], self::CONTENT_SOURCES, true ) ) {
				$fields['content_source'] = $body['content_source'];
			} else {
				$problems[] = 'content_source phải là ai hoặc template';
			}
		}
		if ( array_key_exists( 'classification', $body ) ) {
			if ( null === $body['classification'] || in_array( $body['classification'], array( 'E', 'P', 'N', 'X' ), true ) ) {
				$fields['classification'] = $body['classification'];
			} else {
				$problems[] = 'classification phải là E, P, N hoặc X';
			}
		}
		if ( array_key_exists( 'visibility', $body ) ) {
			if ( in_array( $body['visibility'], self::VISIBILITIES, true ) ) {
				$fields['visibility'] = $body['visibility'];
			} else {
				$problems[] = 'visibility phải là một trong: ' . implode( ', ', self::VISIBILITIES );
			}
		}
		foreach ( array( 'tokens_in', 'tokens_out', 'ai_ms' ) as $key ) {
			if ( array_key_exists( $key, $body ) ) {
				if ( null === $body[ $key ] ) {
					$fields[ $key ] = null;
				} elseif ( is_numeric( $body[ $key ] ) && (int) $body[ $key ] >= 0 ) {
					$fields[ $key ] = (int) $body[ $key ];
				} else {
					$problems[] = "{$key} phải là số không âm";
				}
			}
		}
		foreach ( array( 'scheduled_at', 'published_at' ) as $key ) {
			if ( array_key_exists( $key, $body ) ) {
				$time = self::to_utc( $body[ $key ] );
				if ( false === $time ) {
					$problems[] = "{$key} không phải thời gian hợp lệ";
				} else {
					$fields[ $key ] = $time;
				}
			}
		}
		if ( array_key_exists( 'remote_url', $body ) ) {
			$fields['remote_url'] = null === $body['remote_url'] ? null : esc_url_raw( (string) $body['remote_url'], array( 'http', 'https' ) );
		}
		if ( array_key_exists( 'error', $body ) ) {
			$fields['error'] = null === $body['error'] ? null : mb_substr( wp_strip_all_tags( is_scalar( $body['error'] ) ? (string) $body['error'] : (string) wp_json_encode( $body['error'] ) ), 0, 5000 );
		}
		if ( array_key_exists( 'content', $body ) ) {
			$fields['content'] = null === $body['content'] ? null : ( is_string( $body['content'] ) ? $body['content'] : wp_json_encode( $body['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		}

		if ( $problems ) {
			SocialSync_Logger::warn( 'callback.invalid', 'Dữ liệu n8n báo về không hợp lệ', $log + array( 'problems' => $problems ) );
			return self::reply(
				422,
				array(
					'ok'       => false,
					'error'    => implode( '; ', $problems ),
					'problems' => $problems,
				)
			);
		}

		if ( 'published' === $after && ! array_key_exists( 'published_at', $fields ) && empty( $job['published_at'] ) ) {
			$fields['published_at'] = SocialSync_DB::now();
		}
		if ( 'published' === $after && ! array_key_exists( 'error', $fields ) ) {
			$fields['error'] = null;
		}

		if ( $fields ) {
			SocialSync_DB::update_jobs( array( $job_id ), $fields );
		}

		$level = 'failed' === $after ? 'error' : 'info';
		SocialSync_Logger::write(
			$level,
			'callback.applied',
			$before === $after ? "Cập nhật lượt, giữ trạng thái {$after}" : "Đổi trạng thái {$before} -> {$after}",
			array(
				'job_id'         => $job_id,
				'post_id'        => (int) $job['post_id'],
				'channel'        => (string) $job['channel'],
				'status_before'  => $before,
				'status_after'   => $after,
				'updated_fields' => array_keys( $fields ),
				'remote_url'     => $fields['remote_url'] ?? null,
				'error'          => $fields['error'] ?? null,
			)
		);

		return self::reply(
			200,
			array(
				'ok'            => true,
				'job_id'        => $job_id,
				'status_before' => $before,
				'status_after'  => $after,
			)
		);
	}

	/** Tiến trình nền: gửi các lượt "pending" của một bài sang n8n. */
	public static function dispatch( WP_REST_Request $req ): WP_REST_Response {
		$body      = $req->get_json_params();
		$body      = is_array( $body ) ? $body : array();
		$post_id   = (int) ( $body['post_id'] ?? 0 );
		$job_ids   = array_values( array_filter( array_map( 'intval', (array) ( $body['job_ids'] ?? array() ) ) ) );
		$kicked_at = isset( $body['kicked_at'] ) ? (float) $body['kicked_at'] : null;

		SocialSync_Logger::info(
			'async.received',
			'Tiến trình nền nhận việc gửi bài',
			array(
				'post_id' => $post_id,
				'job_ids' => $job_ids,
				'lag_ms'  => null === $kicked_at ? null : (int) round( ( microtime( true ) - $kicked_at ) * 1000 ),
			)
		);

		if ( $post_id <= 0 || ! $job_ids ) {
			return self::reply( 422, array( 'ok' => false, 'error' => 'Thiếu post_id hoặc job_ids' ) );
		}

		ignore_user_abort( true );
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}

		$jobs = array();
		foreach ( SocialSync_DB::get_jobs( $job_ids ) as $row ) {
			if ( (int) $row['post_id'] !== $post_id || 'pending' !== $row['status'] ) {
				SocialSync_Logger::warn(
					'async.job_skipped',
					'Bỏ qua lượt không còn ở trạng thái chờ gửi',
					array(
						'post_id' => (int) $row['post_id'],
						'job_id'  => (int) $row['id'],
						'channel' => (string) $row['channel'],
						'status'  => (string) $row['status'],
					)
				);
				continue;
			}
			$jobs[] = array(
				'job_id'        => (int) $row['id'],
				'channel'       => (string) $row['channel'],
				'delay_minutes' => isset( $body['delay_override'] ) ? max( 0, (int) $body['delay_override'] ) : SocialSync_Settings::delay( (string) $row['channel'] ),
				// Bấm "Đồng bộ lại" gửi delay_override = 0: đăng ngay, không tự rải lịch.
				'immediate'     => isset( $body['delay_override'] ),
			);
		}

		if ( ! $jobs ) {
			return self::reply( 200, array( 'ok' => false, 'error' => 'Không còn lượt nào chờ gửi' ) );
		}

		SocialSync_Dispatcher::dispatch_post( $post_id, $jobs );
		return self::reply( 200, array( 'ok' => true, 'dispatched' => array_column( $jobs, 'job_id' ) ) );
	}

	/**
	 * Luồng báo lỗi của n8n (SocialSync - Báo lỗi) gọi vào đây khi một lần chạy của luồng chính bị lỗi giữa chừng.
	 * Mọi lượt còn đang mở (pending, generating, scheduled) gắn với lần chạy đó chuyển sang "failed" kèm lý do,
	 * thay vì đứng yên tới khi trang Nhật ký cảnh báo sau 15 phút.
	 */
	public static function execution_failed( WP_REST_Request $req ): WP_REST_Response {
		global $wpdb;
		$exec_id = (string) (int) $req['id'];
		$body    = $req->get_json_params();
		$body    = is_array( $body ) ? $body : array();
		$node    = mb_substr( sanitize_text_field( (string) ( $body['node'] ?? '' ) ), 0, 120 );
		$message = mb_substr( wp_strip_all_tags( (string) ( $body['error'] ?? '' ) ), 0, 2000 );
		$log     = array(
			'execution_id' => $exec_id,
			'node'         => $node,
			'error'        => $message,
			'workflow'     => $body['workflow'] ?? null,
		);
		SocialSync_Logger::error( 'n8n.execution_failed', 'n8n báo một lần chạy bị lỗi', $log );

		$table = SocialSync_DB::table();
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT id, post_id, channel, status FROM {$table} WHERE execution_id = %s", $exec_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$open   = array();
		$closed = array();
		foreach ( (array) $rows as $row ) {
			if ( in_array( $row['status'], array( 'pending', 'generating', 'scheduled' ), true ) ) {
				$open[] = (int) $row['id'];
			} else {
				$closed[] = array( 'job_id' => (int) $row['id'], 'status' => (string) $row['status'] );
			}
		}

		if ( ! $open ) {
			SocialSync_Logger::warn( 'n8n.execution_failed.none_open', 'Không có lượt nào còn mở của lần chạy này', $log + array( 'found' => count( (array) $rows ), 'closed' => $closed ) );
			return self::reply( 200, array( 'ok' => true, 'failed_jobs' => array(), 'closed' => $closed ) );
		}

		$reason = sprintf(
			'n8n lỗi ở khối "%s" (lần chạy #%s): %s',
			'' !== $node ? $node : 'không rõ',
			$exec_id,
			'' !== $message ? $message : 'không có thông báo lỗi'
		);
		SocialSync_DB::update_jobs( $open, array( 'status' => 'failed', 'error' => $reason ) );
		SocialSync_Logger::error( 'n8n.execution_failed.applied', 'Chuyển các lượt còn mở sang failed', $log + array( 'failed_jobs' => $open, 'closed' => $closed ) );

		return self::reply( 200, array( 'ok' => true, 'failed_jobs' => $open, 'closed' => $closed ) );
	}

	/** n8n gửi kết quả chạy thử (AI viết + kiểm tra, không đăng) của một bài nguồn. */
	public static function preview_result( WP_REST_Request $req ): WP_REST_Response {
		global $wpdb;
		$id   = (int) $req['id'];
		$body = $req->get_json_params();
		$body = is_array( $body ) ? $body : array();
		$cls  = in_array( $body['classification'] ?? null, array( 'E', 'P', 'N', 'X' ), true ) ? $body['classification'] : null;
		$ok   = ! empty( $body['ok'] );
		$data = array(
			'status'         => $ok ? 'done' : 'failed',
			'classification' => $cls,
			'result'         => wp_json_encode( $body['result'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
			'error'          => isset( $body['error'] ) ? mb_substr( wp_strip_all_tags( (string) $body['error'] ), 0, 2000 ) : null,
			'tokens_in'      => isset( $body['tokens_in'] ) ? absint( $body['tokens_in'] ) : null,
			'tokens_out'     => isset( $body['tokens_out'] ) ? absint( $body['tokens_out'] ) : null,
			'ai_ms'          => isset( $body['ai_ms'] ) ? absint( $body['ai_ms'] ) : null,
			'updated_at'     => SocialSync_DB::now(),
		);
		$rows = $wpdb->update( SocialSync_DB::previews_table(), $data, array( 'id' => $id ) );
		SocialSync_Logger::write(
			false === $rows || 0 === $rows ? 'warn' : 'info',
			'preview.result',
			false === $rows || 0 === $rows ? 'Không ghi được kết quả chạy thử (không có dòng này?)' : 'Nhận kết quả chạy thử',
			array(
				'preview_id'     => $id,
				'ok'             => $ok,
				'classification' => $cls,
				'tokens'         => array( $data['tokens_in'], $data['tokens_out'] ),
				'ai_ms'          => $data['ai_ms'],
				'error'          => $data['error'],
				'rows'           => $rows,
				'db_error'       => false === $rows ? $wpdb->last_error : null,
			)
		);
		return self::reply( $rows ? 200 : 404, array( 'ok' => (bool) $rows ) );
	}

	/** Số liệu cho luồng báo cáo của n8n. */
	public static function report_data( WP_REST_Request $req ): WP_REST_Response {
		$days = max( 1, min( 90, (int) ( $req->get_param( 'days' ) ?? 7 ) ) );
		$data = SocialSync_Report::stats( $days );
		SocialSync_Logger::info( 'report.data', 'n8n lấy số liệu báo cáo', array( 'days' => $days, 'fb_posts' => count( $data['facebook_posts'] ) ) );
		return self::reply( 200, array( 'ok' => true ) + $data );
	}

	/** n8n gửi số liệu tương tác: {"items":[{"job_id":1,"metrics":{...}}]} */
	public static function save_metrics( WP_REST_Request $req ): WP_REST_Response {
		$body  = $req->get_json_params();
		$items = is_array( $body['items'] ?? null ) ? $body['items'] : array();
		$saved = 0;
		foreach ( $items as $item ) {
			$job_id = (int) ( $item['job_id'] ?? 0 );
			if ( $job_id <= 0 || ! is_array( $item['metrics'] ?? null ) ) {
				continue;
			}
			$m = array_map( 'intval', array_intersect_key( $item['metrics'], array_flip( array( 'reactions', 'comments', 'shares' ) ) ) );
			SocialSync_DB::update_jobs(
				array( $job_id ),
				array(
					'metrics'    => wp_json_encode( $m ),
					'metrics_at' => SocialSync_DB::now(),
				)
			);
			++$saved;
		}
		SocialSync_Logger::info( 'report.metrics', 'Lưu số liệu tương tác', array( 'received' => count( $items ), 'saved' => $saved ) );
		return self::reply( 200, array( 'ok' => true, 'saved' => $saved ) );
	}

	/** n8n gửi báo cáo tuần đã tóm tắt. Giữ 10 báo cáo gần nhất. */
	public static function save_report( WP_REST_Request $req ): WP_REST_Response {
		$body   = $req->get_json_params();
		$body   = is_array( $body ) ? $body : array();
		$report = array(
			'created_at' => SocialSync_DB::now(),
			'days'       => (int) ( $body['days'] ?? 7 ),
			'summary'    => mb_substr( wp_strip_all_tags( (string) ( $body['summary'] ?? '' ) ), 0, 5000 ),
			'source'     => sanitize_text_field( (string) ( $body['source'] ?? '' ) ),
			'model'      => sanitize_text_field( (string) ( $body['model'] ?? '' ) ),
			'error'      => isset( $body['error'] ) ? mb_substr( wp_strip_all_tags( (string) $body['error'] ), 0, 1000 ) : null,
			'execution'  => sanitize_text_field( (string) ( $body['execution_id'] ?? '' ) ),
		);
		$all = get_option( 'socialsync_reports', array() );
		$all = is_array( $all ) ? $all : array();
		array_unshift( $all, $report );
		update_option( 'socialsync_reports', array_slice( $all, 0, 10 ), false );
		SocialSync_Logger::info( 'report.saved', 'Lưu báo cáo tuần', array_diff_key( $report, array( 'summary' => 1 ) ) + array( 'summary_chars' => mb_strlen( $report['summary'] ) ) );
		return self::reply( 200, array( 'ok' => true ) );
	}

	/**
	 * @param mixed $value
	 * @return string|null|false Giờ UTC dạng MySQL, null nếu rỗng, false nếu sai định dạng.
	 */
	private static function to_utc( $value ) {
		if ( null === $value || '' === $value ) {
			return null;
		}
		$ts = is_numeric( $value ) ? (int) $value : strtotime( (string) $value );
		return false === $ts ? false : gmdate( 'Y-m-d H:i:s', $ts );
	}

	private static function reply( int $code, array $data ): WP_REST_Response {
		return new WP_REST_Response( $data, $code );
	}
}
