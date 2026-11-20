<?php
/**
 * Bảng lượt đồng bộ: mỗi dòng là một cặp (bài, kênh).
 *
 * Ràng buộc UNIQUE (post_id, channel) là lớp chống đăng trùng cuối cùng ở tầng dữ liệu:
 * dù hook chạy nhiều lần, mỗi bài chỉ có tối đa một lượt cho mỗi kênh.
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_DB {

	public const STATUSES = array( 'pending', 'generating', 'needs_review', 'scheduled', 'published', 'failed', 'skipped', 'rejected' );

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'socialsync_jobs';
	}

	public static function previews_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'socialsync_previews';
	}

	/**
	 * Chuyển trạng thái có điều kiện: chỉ đổi khi lượt đang ở đúng trạng thái mong đợi.
	 * Dùng cho nút Duyệt / Từ chối để hai người (hoặc hai lần bấm) không cùng duyệt một lượt.
	 *
	 * @param array<string, mixed> $fields
	 * @return bool true nếu đã đổi (đúng 1 dòng).
	 */
	public static function transition( int $job_id, string $from, array $fields ): bool {
		global $wpdb;
		$fields['updated_at'] = self::now();
		$sets                 = array();
		$values               = array();
		foreach ( $fields as $column => $value ) {
			if ( ! preg_match( '/^[a-z_]+$/', (string) $column ) ) {
				continue;
			}
			if ( null === $value ) {
				$sets[] = "`{$column}` = NULL";
				continue;
			}
			$sets[]   = "`{$column}` = " . ( is_int( $value ) ? '%d' : '%s' );
			$values[] = is_scalar( $value ) ? $value : wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}
		$sql  = 'UPDATE ' . self::table() . ' SET ' . implode( ', ', $sets ) . ' WHERE id = %d AND status = %s';
		$rows = $wpdb->query( $wpdb->prepare( $sql, array_merge( $values, array( $job_id, $from ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$ok   = 1 === (int) $rows;
		SocialSync_Logger::write(
			$ok ? 'info' : 'warn',
			'db.transition',
			$ok ? "Chuyển lượt từ {$from}" : "Không chuyển được lượt: lượt không còn ở trạng thái {$from}",
			array(
				'job_id'   => $job_id,
				'from'     => $from,
				'to'       => $fields['status'] ?? null,
				'fields'   => array_keys( $fields ),
				'rows'     => $rows,
				'db_error' => false === $rows ? $wpdb->last_error : null,
			)
		);
		return $ok;
	}

	/**
	 * Như transition() nhưng thêm điều kiện mã lệnh gửi n8n (dispatch_token) phải còn đúng như đã đọc.
	 * Dùng cho: n8n "nhận lượt" trước khi đăng, người duyệt đổi giờ / hủy lịch. Hai bên tranh nhau thì chỉ một bên thắng.
	 *
	 * @param string|null          $token Mã lệnh đã đọc (null = lượt chưa có mã, ví dụ đường Tự đăng).
	 * @param array<string, mixed> $fields
	 */
	public static function transition_token( int $job_id, string $from, ?string $token, array $fields ): bool {
		global $wpdb;
		$fields['updated_at'] = self::now();
		$sets                 = array();
		$values               = array();
		foreach ( $fields as $column => $value ) {
			if ( ! preg_match( '/^[a-z_]+$/', (string) $column ) ) {
				continue;
			}
			if ( null === $value ) {
				$sets[] = "`{$column}` = NULL";
				continue;
			}
			$sets[]   = "`{$column}` = " . ( is_int( $value ) ? '%d' : '%s' );
			$values[] = is_scalar( $value ) ? $value : wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}
		$cond   = null === $token ? 'dispatch_token IS NULL' : 'dispatch_token = %s';
		$args   = array_merge( $values, array( $job_id, $from ), null === $token ? array() : array( $token ) );
		$sql    = 'UPDATE ' . self::table() . ' SET ' . implode( ', ', $sets ) . " WHERE id = %d AND status = %s AND {$cond}";
		$rows   = $wpdb->query( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$ok     = 1 === (int) $rows;
		SocialSync_Logger::write(
			$ok ? 'info' : 'warn',
			'db.transition_token',
			$ok ? "Chuyển lượt từ {$from} (đúng mã lệnh)" : "Không chuyển được lượt: không còn ở {$from} hoặc mã lệnh đã đổi",
			array(
				'job_id'    => $job_id,
				'from'      => $from,
				'to'        => $fields['status'] ?? null,
				'token_set' => null !== $token,
				'fields'    => array_keys( $fields ),
				'rows'      => $rows,
				'db_error'  => false === $rows ? $wpdb->last_error : null,
			)
		);
		return $ok;
	}

	/** Mã lệnh mới cho một lần gửi lượt sang n8n. */
	public static function new_token(): string {
		return wp_generate_password( 24, false, false );
	}

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  post_id bigint(20) unsigned NOT NULL,
  post_type varchar(20) NOT NULL,
  channel varchar(20) NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'pending',
  scheduled_at datetime NULL,
  published_at datetime NULL,
  attempts smallint(5) unsigned NOT NULL DEFAULT 0,
  content_source varchar(20) NULL,
  content_source_reason varchar(255) NULL,
  model varchar(64) NULL,
  tokens_in int(10) unsigned NULL,
  tokens_out int(10) unsigned NULL,
  ai_ms int(10) unsigned NULL,
  content longtext NULL,
  remote_id varchar(191) NULL,
  remote_url text NULL,
  visibility varchar(20) NULL,
  error text NULL,
  execution_id varchar(64) NULL,
  classification varchar(2) NULL,
  reviewed_by bigint(20) unsigned NULL,
  reviewed_at datetime NULL,
  review_note text NULL,
  dispatch_token varchar(40) NULL,
  metrics longtext NULL,
  metrics_at datetime NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY post_channel (post_id,channel),
  KEY status (status),
  KEY channel_sched (channel,scheduled_at)
) {$charset};";

		// Kết quả "Chạy thử với bài thật của doppelherz.vn": AI viết + kiểm tra, KHÔNG đăng.
		$previews = self::previews_table();
		$sql2     = "CREATE TABLE {$previews} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  batch_id varchar(32) NOT NULL,
  source_id bigint(20) unsigned NULL,
  source_url text NULL,
  title text NULL,
  categories text NULL,
  status varchar(20) NOT NULL DEFAULT 'pending',
  classification varchar(2) NULL,
  result longtext NULL,
  error text NULL,
  tokens_in int(10) unsigned NULL,
  tokens_out int(10) unsigned NULL,
  ai_ms int(10) unsigned NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY batch_id (batch_id)
) {$charset};";

		$result = array_merge( dbDelta( $sql ), dbDelta( $sql2 ) );
		$exists = self::table_exists();
		update_option( 'socialsync_db_version', SOCIALSYNC_DB_VERSION, false );

		$context = array(
			'table'    => $table,
			'dbdelta'  => $result,
			'db_error' => $wpdb->last_error,
		);
		if ( $exists ) {
			SocialSync_Logger::info( 'db.install', 'Bảng lượt đồng bộ sẵn sàng', $context );
		} else {
			SocialSync_Logger::error( 'db.install', 'KHÔNG tạo được bảng lượt đồng bộ', $context );
		}
	}

	public static function maybe_upgrade(): void {
		$have = get_option( 'socialsync_db_version' );
		if ( SOCIALSYNC_DB_VERSION !== $have ) {
			SocialSync_Logger::info(
				'db.upgrade',
				'Phiên bản bảng khác với code, chạy lại cài đặt bảng',
				array(
					'have' => $have,
					'want' => SOCIALSYNC_DB_VERSION,
				)
			);
			self::install();
		}
	}

	public static function table_exists(): bool {
		global $wpdb;
		$table = self::table();
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	}

	/** Giờ UTC dạng MySQL. */
	public static function now(): string {
		return current_time( 'mysql', true );
	}

	/**
	 * Tạo lượt đồng bộ cho các kênh. Kênh nào đã có lượt thì bỏ qua (chống trùng).
	 *
	 * @param string[] $channels
	 * @return array<int, array{job_id:int, channel:string, delay_minutes:int}> Chỉ các lượt mới tạo.
	 */
	public static function create_jobs( int $post_id, string $post_type, array $channels ): array {
		global $wpdb;
		$table   = self::table();
		$created = array();
		$now     = self::now();

		foreach ( $channels as $channel ) {
			$delay = SocialSync_Settings::delay( $channel );
			$ok    = $wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$table} (post_id, post_type, channel, status, attempts, created_at, updated_at) VALUES (%d, %s, %s, 'pending', 0, %s, %s)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$post_id,
					$post_type,
					$channel,
					$now,
					$now
				)
			);

			if ( false === $ok ) {
				SocialSync_Logger::error(
					'db.job_create_failed',
					'Lỗi cơ sở dữ liệu khi tạo lượt đồng bộ',
					array(
						'post_id'  => $post_id,
						'channel'  => $channel,
						'db_error' => $wpdb->last_error,
					)
				);
				continue;
			}

			if ( 1 === (int) $wpdb->rows_affected ) {
				$job_id    = (int) $wpdb->insert_id;
				$created[] = array(
					'job_id'        => $job_id,
					'channel'       => $channel,
					'delay_minutes' => $delay,
				);
				SocialSync_Logger::info(
					'db.job_created',
					'Tạo lượt đồng bộ mới',
					array(
						'post_id'       => $post_id,
						'job_id'        => $job_id,
						'channel'       => $channel,
						'delay_minutes' => $delay,
					)
				);
			} else {
				$existing = $wpdb->get_row(
					$wpdb->prepare( "SELECT id, status, attempts, created_at FROM {$table} WHERE post_id = %d AND channel = %s", $post_id, $channel ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					ARRAY_A
				);
				SocialSync_Logger::info(
					'db.job_exists',
					null === $existing
						? 'Không chèn được dòng mới nhưng cũng không thấy dòng cũ (kiểm tra db_warning)'
						: 'Bài này đã có lượt cho kênh này, không tạo thêm (chống trùng)',
					array(
						'post_id'    => $post_id,
						'channel'    => $channel,
						'existing'   => $existing,
						'db_warning' => $wpdb->last_error,
					)
				);
			}
		}
		return $created;
	}

	/**
	 * Cập nhật nhiều lượt cùng lúc.
	 *
	 * @param int[]                $job_ids
	 * @param array<string, mixed> $fields  Tên cột => giá trị (null = NULL).
	 */
	public static function update_jobs( array $job_ids, array $fields, bool $bump_attempts = false ): void {
		global $wpdb;
		$job_ids = array_values( array_filter( array_map( 'intval', $job_ids ) ) );
		if ( ! $job_ids ) {
			return;
		}
		$fields['updated_at'] = self::now();

		$sets   = array();
		$values = array();
		foreach ( $fields as $column => $value ) {
			if ( ! preg_match( '/^[a-z_]+$/', (string) $column ) ) {
				continue;
			}
			if ( null === $value ) {
				$sets[] = "`{$column}` = NULL";
				continue;
			}
			$sets[]   = "`{$column}` = " . ( is_int( $value ) ? '%d' : '%s' );
			$values[] = is_scalar( $value ) ? $value : wp_json_encode( $value );
		}
		if ( $bump_attempts ) {
			$sets[] = '`attempts` = `attempts` + 1';
		}

		$in   = implode( ',', array_fill( 0, count( $job_ids ), '%d' ) );
		$sql  = 'UPDATE ' . self::table() . ' SET ' . implode( ', ', $sets ) . " WHERE id IN ({$in})";
		$rows = $wpdb->query( $wpdb->prepare( $sql, array_merge( $values, $job_ids ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$context = array(
			'job_ids'       => $job_ids,
			'fields'        => $fields,
			'bump_attempts' => $bump_attempts,
			'rows'          => $rows,
		);
		if ( false === $rows ) {
			$context['db_error'] = $wpdb->last_error;
			SocialSync_Logger::error( 'db.job_update_failed', 'Lỗi cập nhật lượt đồng bộ', $context );
			return;
		}
		SocialSync_Logger::debug( 'db.job_update', 'Cập nhật lượt đồng bộ', $context );
	}

	/** @return array<int, array<string, mixed>> */
	public static function jobs( int $post_id = 0, int $limit = 20 ): array {
		global $wpdb;
		$table = self::table();
		$limit = max( 1, min( 500, $limit ) );
		if ( $post_id > 0 ) {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE post_id = %d ORDER BY id DESC LIMIT %d", $post_id, $limit ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		return (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/** @return array<string, mixed>|null */
	public static function get_job( int $job_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $job_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $row ) ? $row : null;
	}

	/**
	 * @param int[] $job_ids
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_jobs( array $job_ids ): array {
		global $wpdb;
		$job_ids = array_values( array_filter( array_map( 'intval', $job_ids ) ) );
		if ( ! $job_ids ) {
			return array();
		}
		$in = implode( ',', array_fill( 0, count( $job_ids ), '%d' ) );
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE id IN ({$in})", $job_ids ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Điều kiện lọc cho trang Nhật ký.
	 *
	 * @param array{channel?:string, status?:string, from?:string, to?:string, post_id?:int} $filters
	 *        from / to là ngày theo giờ site, dạng Y-m-d.
	 * @return array{0:string, 1:array}
	 */
	private static function where( array $filters ): array {
		$where = array( '1=1' );
		$args  = array();
		if ( ! empty( $filters['channel'] ) ) {
			$where[] = 'channel = %s';
			$args[]  = $filters['channel'];
		}
		if ( ! empty( $filters['status'] ) ) {
			$where[] = 'status = %s';
			$args[]  = $filters['status'];
		}
		if ( ! empty( $filters['post_id'] ) ) {
			$where[] = 'post_id = %d';
			$args[]  = (int) $filters['post_id'];
		}
		if ( ! empty( $filters['from'] ) ) {
			$where[] = 'created_at >= %s';
			$args[]  = get_gmt_from_date( $filters['from'] . ' 00:00:00' );
		}
		if ( ! empty( $filters['to'] ) ) {
			$where[] = 'created_at <= %s';
			$args[]  = get_gmt_from_date( $filters['to'] . ' 23:59:59' );
		}
		return array( implode( ' AND ', $where ), $args );
	}

	/**
	 * @return array{rows: array<int, array<string, mixed>>, total: int}
	 */
	public static function query( array $filters, int $page, int $per_page ): array {
		global $wpdb;
		$table           = self::table();
		[ $where, $args ] = self::where( $filters );
		$offset          = max( 0, ( $page - 1 ) * $per_page );

		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
		$total     = (int) $wpdb->get_var( $args ? $wpdb->prepare( $count_sql, $args ) : $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$rows_sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
		$rows     = (array) $wpdb->get_results( $wpdb->prepare( $rows_sql, array_merge( $args, array( $per_page, $offset ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'rows'  => $rows,
			'total' => $total,
		);
	}

	/**
	 * Đếm số lượt theo kênh và trạng thái (cho bảng tổng hợp).
	 *
	 * @return array<string, array<string, int>> kênh => trạng thái => số lượt
	 */
	public static function counts( array $filters ): array {
		global $wpdb;
		$table            = self::table();
		[ $where, $args ] = self::where( $filters );
		$sql              = "SELECT channel, status, COUNT(*) AS n FROM {$table} WHERE {$where} GROUP BY channel, status";
		$rows             = (array) $wpdb->get_results( $args ? $wpdb->prepare( $sql, $args ) : $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$out              = array();
		foreach ( $rows as $row ) {
			$out[ (string) $row['channel'] ][ (string) $row['status'] ] = (int) $row['n'];
		}
		return $out;
	}

	public static function delete_for_post( int $post_id ): int {
		global $wpdb;
		$rows = (int) $wpdb->delete( self::table(), array( 'post_id' => $post_id ), array( '%d' ) );
		SocialSync_Logger::warn(
			'db.jobs_deleted',
			'Xoá các lượt đồng bộ của bài',
			array(
				'post_id' => $post_id,
				'rows'    => $rows,
			)
		);
		return $rows;
	}
}
