<?php
/**
 * Gửi dữ liệu sang webhook của n8n và ghi kết quả vào bảng lượt đồng bộ.
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_Dispatcher {

	public const SECRET_HEADER = 'X-SocialSync-Secret';

	/**
	 * Chuyển việc gửi bài sang một tiến trình nền, để request xuất bản không phải chờ n8n.
	 *
	 * Tiến trình nền là một request REST nội bộ (qua mạng Docker, không qua Cloudflare) tới
	 * /wp-json/social-sync/v1/dispatch, gửi kiểu "bắn rồi quên". Nếu không mở được tiến trình nền
	 * thì gửi ngay trong request hiện tại.
	 *
	 * @param array<int, array{job_id:int, channel:string, delay_minutes:int}> $jobs
	 * @param int|null $delay_override Số phút trễ dùng thay cấu hình (ví dụ 0 khi bấm Đồng bộ lại).
	 */
	public static function kick( int $post_id, array $jobs, ?int $delay_override = null ): void {
		$job_ids = array_column( $jobs, 'job_id' );

		if ( 'sync' === SocialSync_Settings::dispatch_mode() ) {
			SocialSync_Logger::info( 'async.disabled', 'Chế độ gửi đồng bộ: gửi ngay trong request này', array( 'post_id' => $post_id, 'job_ids' => $job_ids ) );
			self::dispatch_post( $post_id, self::apply_delay( $jobs, $delay_override ) );
			return;
		}

		$url  = SocialSync_Settings::internal_url( 'dispatch' );
		$data = array(
			'post_id'   => $post_id,
			'job_ids'   => $job_ids,
			'kicked_at' => microtime( true ),
		);
		if ( null !== $delay_override ) {
			$data['delay_override'] = $delay_override;
		}

		$t0     = microtime( true );
		$result = self::fire_and_forget( $url, (string) wp_json_encode( $data ) );
		$ms     = (int) round( ( microtime( true ) - $t0 ) * 1000 );

		if ( true !== $result ) {
			SocialSync_Logger::warn(
				'async.kick_failed',
				'Không mở được tiến trình nền, gửi ngay trong request này',
				array(
					'post_id' => $post_id,
					'job_ids' => $job_ids,
					'url'     => SocialSync_Logger::mask_url( $url ),
					'ms'      => $ms,
					'error'   => $result,
				)
			);
			self::dispatch_post( $post_id, self::apply_delay( $jobs, $delay_override ) );
			return;
		}

		SocialSync_Logger::info(
			'async.kicked',
			'Đã chuyển việc gửi bài sang tiến trình nền',
			array(
				'post_id'        => $post_id,
				'job_ids'        => $job_ids,
				'url'            => SocialSync_Logger::mask_url( $url ),
				'ms'             => $ms,
				'delay_override' => $delay_override,
			)
		);
	}

	/**
	 * Gửi một POST rồi đóng kết nối ngay, không chờ phản hồi.
	 *
	 * Không dùng wp_remote_post( blocking=false ) vì thư viện Requests của WordPress ép thời gian chờ
	 * tối thiểu 1 giây (wp-includes/Requests/src/Transport/Curl.php: $timeout = max($timeout, 1)),
	 * nên nút xuất bản vẫn bị giữ tới 1 giây. Đo ngày 2026-11-19: 1009–1015 ms mỗi lần.
	 *
	 * @return true|string true nếu đã ghi xong yêu cầu, ngược lại là mô tả lỗi.
	 */
	private static function fire_and_forget( string $url, string $body ) {
		$p = wp_parse_url( $url );
		if ( ! is_array( $p ) || empty( $p['host'] ) ) {
			return 'URL tiến trình nền không hợp lệ';
		}
		$https  = 'https' === ( $p['scheme'] ?? 'http' );
		$port   = (int) ( $p['port'] ?? ( $https ? 443 : 80 ) );
		$target = ( $https ? 'ssl://' : '' ) . $p['host'];
		$path   = ( $p['path'] ?? '/' ) . ( isset( $p['query'] ) ? '?' . $p['query'] : '' );

		$errno  = 0;
		$errstr = '';
		$fp     = @fsockopen( $target, $port, $errno, $errstr, 1.0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions
		if ( false === $fp ) {
			return sprintf( 'Không mở được kết nối tới %s:%d (%d: %s)', $p['host'], $port, $errno, $errstr );
		}

		$host_header = $p['host'] . ( isset( $p['port'] ) ? ':' . $p['port'] : '' );
		$request     = "POST {$path} HTTP/1.1\r\n"
			. "Host: {$host_header}\r\n"
			. "Content-Type: application/json; charset=utf-8\r\n"
			. 'Content-Length: ' . strlen( $body ) . "\r\n"
			. self::SECRET_HEADER . ': ' . SocialSync_Settings::secret() . "\r\n"
			. 'X-SocialSync-Request-Id: ' . SocialSync_Logger::request_id() . "\r\n"
			. "Connection: close\r\n\r\n"
			. $body;

		$written = @fwrite( $fp, $request ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions
		fflush( $fp );
		fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		if ( strlen( $request ) !== $written ) {
			return sprintf( 'Chỉ ghi được %s / %d byte', var_export( $written, true ), strlen( $request ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}
		return true;
	}

	/** @param array<int, array{job_id:int, channel:string, delay_minutes:int}> $jobs */
	private static function apply_delay( array $jobs, ?int $delay_override ): array {
		if ( null === $delay_override ) {
			return $jobs;
		}
		foreach ( $jobs as $i => $job ) {
			$jobs[ $i ]['delay_minutes'] = max( 0, $delay_override );
			$jobs[ $i ]['immediate']     = true;
		}
		return $jobs;
	}

	/**
	 * Chọn giờ đăng cho từng lượt (SocialSync_Scheduler), ghi vào bảng và vào dữ liệu gửi n8n.
	 * Lượt "immediate" (bấm Đồng bộ lại) đăng ngay, không tự rải.
	 */
	private static function plan_jobs( int $post_id, array $jobs ): array {
		global $wpdb;
		// Nhiều bài xuất bản cùng lúc chạy song song ở các tiến trình nền: khóa để lần xếp lịch sau
		// thấy giờ mà lần trước vừa giữ chỗ (đo ngày 19/11/2026: không khóa thì 2 bài cùng nhận 20:04).
		$t0     = microtime( true );
		$locked = (int) $wpdb->get_var( "SELECT GET_LOCK('socialsync_schedule', 10)" );
		SocialSync_Logger::debug( 'schedule.lock', $locked ? 'Đã lấy khóa xếp lịch' : 'KHÔNG lấy được khóa xếp lịch sau 10 giây, vẫn xếp', array( 'post_id' => $post_id, 'wait_ms' => (int) round( ( microtime( true ) - $t0 ) * 1000 ) ) );
		try {
			return self::plan_jobs_locked( $post_id, $jobs );
		} finally {
			if ( $locked ) {
				$wpdb->query( "SELECT RELEASE_LOCK('socialsync_schedule')" );
			}
		}
	}

	private static function plan_jobs_locked( int $post_id, array $jobs ): array {
		foreach ( $jobs as $i => $job ) {
			if ( ! empty( $job['immediate'] ) ) {
				$plan = array(
					'ts'     => time(),
					'source' => 'immediate',
					'steps'  => array( 'Đồng bộ lại: đăng ngay' ),
				);
			} else {
				$plan = SocialSync_Scheduler::plan( $post_id, (string) $job['channel'], (int) $job['job_id'] );
			}
			// Ghi giờ dự kiến ngay để lượt sau của cùng kênh tính được khoảng cách.
			SocialSync_DB::update_jobs( array( (int) $job['job_id'] ), array( 'scheduled_at' => gmdate( 'Y-m-d H:i:s', $plan['ts'] ) ) );
			$jobs[ $i ]['scheduled_at']    = gmdate( 'c', $plan['ts'] );
			$jobs[ $i ]['schedule_source'] = $plan['source'];
			$jobs[ $i ]['delay_minutes']   = max( 0, (int) round( ( $plan['ts'] - time() ) / 60 ) );
			unset( $jobs[ $i ]['immediate'] );
			SocialSync_Logger::info(
				'schedule.planned',
				'Chọn giờ đăng ' . SocialSync_Scheduler::fmt( $plan['ts'] ),
				array(
					'post_id' => $post_id,
					'job_id'  => (int) $job['job_id'],
					'channel' => (string) $job['channel'],
					'source'  => $plan['source'],
					'steps'   => $plan['steps'],
				)
			);
		}
		return $jobs;
	}

	/**
	 * Dựng dữ liệu bài và gửi một lần cho tất cả lượt mới của bài.
	 *
	 * @param array<int, array{job_id:int, channel:string, delay_minutes:int}> $jobs
	 */
	public static function dispatch_post( int $post_id, array $jobs ): void {
		$job_ids = array_column( $jobs, 'job_id' );

		try {
			$jobs    = self::plan_jobs( $post_id, $jobs );
			$payload = SocialSync_Payload::build( $post_id, $jobs );
		} catch ( Throwable $e ) {
			SocialSync_Logger::error(
				'payload.failed',
				'Không dựng được dữ liệu bài',
				array(
					'post_id'   => $post_id,
					'job_ids'   => $job_ids,
					'exception' => get_class( $e ),
					'message'   => $e->getMessage(),
					'at'        => $e->getFile() . ':' . $e->getLine(),
				)
			);
			SocialSync_DB::update_jobs(
				$job_ids,
				array(
					'status' => 'failed',
					'error'  => 'Không dựng được dữ liệu bài: ' . $e->getMessage(),
				)
			);
			return;
		}

		$result = self::send( $payload, array( 'post_id' => $post_id, 'job_ids' => $job_ids ) );

		if ( $result['ok'] ) {
			SocialSync_DB::update_jobs(
				$job_ids,
				array(
					'execution_id' => null === $result['execution_id'] ? null : (string) $result['execution_id'],
					'error'        => null,
				),
				true
			);
			SocialSync_Logger::info(
				'dispatch.ok',
				'n8n đã nhận bài',
				array(
					'post_id'      => $post_id,
					'job_ids'      => $job_ids,
					'execution_id' => $result['execution_id'],
					'http_code'    => $result['code'],
					'ms'           => $result['ms'],
				)
			);
			return;
		}

		SocialSync_DB::update_jobs(
			$job_ids,
			array(
				'status'       => 'failed',
				'error'        => $result['error'],
				'execution_id' => null === $result['execution_id'] ? null : (string) $result['execution_id'],
			),
			true
		);
		SocialSync_Logger::error(
			'dispatch.failed',
			'Gửi sang n8n thất bại',
			array(
				'post_id'   => $post_id,
				'job_ids'   => $job_ids,
				'http_code' => $result['code'],
				'ms'        => $result['ms'],
				'error'     => $result['error'],
			)
		);
	}

	/** Gửi gói "test" để kiểm tra kết nối và mã bí mật. */
	public static function send_test(): array {
		SocialSync_Logger::info( 'test.start', 'Bắt đầu gửi thử sang n8n', array( 'by_user' => get_current_user_id() ) );
		$result = self::send( SocialSync_Payload::test(), array() );
		SocialSync_Logger::write(
			$result['ok'] ? 'info' : 'error',
			'test.done',
			$result['ok'] ? 'Gửi thử thành công' : 'Gửi thử thất bại',
			array(
				'http_code'    => $result['code'],
				'ms'           => $result['ms'],
				'execution_id' => $result['execution_id'],
				'error'        => $result['error'],
			)
		);
		return $result;
	}

	/**
	 * @param array<string, mixed> $log_ids post_id / job_ids để gắn vào log.
	 * @return array{ok:bool, code:int, ms:int, execution_id:mixed, json:mixed, error:?string}
	 */
	public static function send( array $payload, array $log_ids, ?string $url = null ): array {
		$url    = null !== $url ? $url : SocialSync_Settings::webhook_url();
		$secret = SocialSync_Settings::secret();
		$result = array(
			'ok'           => false,
			'code'         => 0,
			'ms'           => 0,
			'execution_id' => null,
			'json'         => null,
			'error'        => null,
		);

		$missing = SocialSync_Settings::missing();
		if ( $missing ) {
			$result['error'] = 'Thiếu cấu hình: ' . implode( '; ', $missing );
			SocialSync_Logger::error( 'http.not_configured', $result['error'], $log_ids );
			return $result;
		}

		$body = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( false === $body ) {
			$result['error'] = 'Không mã hoá được dữ liệu thành JSON: ' . json_last_error_msg();
			SocialSync_Logger::error( 'http.encode_failed', $result['error'], $log_ids );
			return $result;
		}

		$timeout = SocialSync_Settings::timeout();
		SocialSync_Logger::debug(
			'http.request',
			'Gửi request sang n8n',
			$log_ids + array(
				'url'          => SocialSync_Logger::mask_url( $url ),
				'method'       => 'POST',
				'bytes'        => strlen( $body ),
				'timeout_s'    => $timeout,
				'event'        => $payload['event'] ?? null,
				'payload'      => $payload,
				'secret_set'   => '' !== $secret,
				'secret_chars' => strlen( $secret ),
			)
		);

		$t0       = microtime( true );
		$response = wp_remote_post(
			$url,
			array(
				'timeout'     => $timeout,
				'redirection' => 0,
				'headers'     => array(
					'Content-Type'             => 'application/json; charset=utf-8',
					'Accept'                   => 'application/json',
					self::SECRET_HEADER        => $secret,
					'X-SocialSync-Request-Id'  => SocialSync_Logger::request_id(),
					'X-SocialSync-Version'     => SOCIALSYNC_VERSION,
				),
				'body'        => $body,
				'data_format' => 'body',
			)
		);
		$result['ms'] = (int) round( ( microtime( true ) - $t0 ) * 1000 );

		if ( is_wp_error( $response ) ) {
			$result['error'] = 'Không kết nối được n8n: ' . $response->get_error_message();
			SocialSync_Logger::error(
				'http.transport_error',
				$result['error'],
				$log_ids + array(
					'url'        => SocialSync_Logger::mask_url( $url ),
					'ms'         => $result['ms'],
					'error_code' => $response->get_error_code(),
					'hint'       => 'Kiểm tra container shop-n8n-1 có chạy không và địa chỉ webhook có đúng không.',
				)
			);
			return $result;
		}

		$code            = (int) wp_remote_retrieve_response_code( $response );
		$raw             = (string) wp_remote_retrieve_body( $response );
		$json            = json_decode( $raw, true );
		$result['code']  = $code;
		$result['json']  = $json;
		$is_json_object  = is_array( $json );
		$result['execution_id'] = $is_json_object ? ( $json['execution_id'] ?? null ) : null;

		SocialSync_Logger::info(
			'http.response',
			'n8n phản hồi',
			$log_ids + array(
				'http_code'    => $code,
				'ms'           => $result['ms'],
				'content_type' => wp_remote_retrieve_header( $response, 'content-type' ),
				'bytes'        => strlen( $raw ),
				'is_json'      => $is_json_object,
				'body'         => $is_json_object ? $json : $raw,
			)
		);

		// Webhook n8n kiểu "trả lời ngay khi nhận" (lệnh đăng sau duyệt, báo cáo) trả {"message":"Workflow was started"}.
		if ( $code >= 200 && $code < 300 && $is_json_object && ( ! empty( $json['ok'] ) || 'Workflow was started' === ( $json['message'] ?? '' ) ) ) {
			$result['ok'] = true;
			return $result;
		}

		$result['error'] = self::explain( $code, $is_json_object ? $json : null, $raw );
		return $result;
	}

	/** Diễn giải mã lỗi thường gặp thành câu dễ hiểu, kèm nguyên văn phản hồi. */
	private static function explain( int $code, ?array $json, string $raw ): string {
		$detail = $json['error'] ?? $json['message'] ?? mb_substr( trim( wp_strip_all_tags( $raw ) ), 0, 300 );
		switch ( true ) {
			case 401 === $code || 403 === $code:
				$why = 'n8n từ chối mã bí mật (mã bí mật trong WordPress và trong credential n8n không khớp)';
				break;
			case 404 === $code:
				$why = 'Không tìm thấy webhook (luồng n8n chưa được publish, hoặc sai đường dẫn webhook)';
				break;
			case 422 === $code:
				$why = 'n8n nhận được nhưng dữ liệu không hợp lệ';
				break;
			case $code >= 500:
				$why = 'n8n gặp lỗi khi xử lý';
				break;
			case $code >= 200 && $code < 300:
				$why = 'n8n trả về thành công nhưng phản hồi không đúng định dạng (thiếu "ok": true)';
				break;
			default:
				$why = 'n8n trả mã HTTP không mong đợi';
		}
		return sprintf( '%s. HTTP %d. Phản hồi: %s', $why, $code, '' === (string) $detail ? '(rỗng)' : (string) $detail );
	}
}
