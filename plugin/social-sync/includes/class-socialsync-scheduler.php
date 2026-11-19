<?php
/**
 * Xếp lịch đăng cho từng lượt.
 *
 * Thứ tự ưu tiên:
 *  1. Giờ người viết chọn cho bài (ô "Lịch đăng mạng xã hội" trong màn hình viết bài): dùng đúng giờ đó.
 *  2. Tự rải: bắt đầu từ lúc xuất bản + số phút trễ của kênh, rồi dời cho tới khi thỏa mọi điều kiện:
 *     - nằm trong khung giờ đăng (ví dụ 07:00–22:00 theo giờ website);
 *     - kênh chưa đủ số bài tối đa của ngày đó;
 *     - cách mọi bài khác của cùng kênh ít nhất "min_gap" phút.
 *  Mỗi lần dời đều ghi lý do để giải thích được vì sao bài đăng lúc đó.
 *
 * Website Doppelherz có ngày đăng 9–10 bài cùng lúc (research/01 mục 3): tự rải tránh việc mạng xã hội bị dồn bài.
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_Scheduler {

	public const META_PREFIX = '_socialsync_at_';

	/** Các trạng thái đã "giữ chỗ" một giờ đăng. */
	private const HOLDING = array( 'pending', 'generating', 'needs_review', 'scheduled', 'published' );

	/**
	 * @param int $exclude_job Không tính lượt này khi đếm (lượt đang được xếp).
	 * @return array{ts:int, source:string, steps:string[]}
	 */
	public static function plan( int $post_id, string $channel, int $exclude_job = 0, ?int $earliest = null ): array {
		$manual = self::manual_time( $post_id, $channel );
		if ( null !== $manual ) {
			$ts = max( time(), $manual );
			return array(
				'ts'     => $ts,
				'source' => 'manual',
				'steps'  => array( 'Người viết chọn giờ đăng ' . self::fmt( $manual ) . ( $ts !== $manual ? ', đã qua nên đăng ngay' : '' ) ),
			);
		}

		$tz       = wp_timezone();
		$delay    = SocialSync_Settings::delay( $channel );
		$ts       = null !== $earliest ? $earliest : time() + $delay * 60;
		$steps    = array( 'Bắt đầu từ ' . self::fmt( $ts ) . ( $delay ? " (xuất bản + {$delay} phút trễ của kênh)" : '' ) );
		[ $win_start, $win_end ] = SocialSync_Settings::window();
		$max      = SocialSync_Settings::max_per_day( $channel );
		$gap      = SocialSync_Settings::min_gap() * 60;

		for ( $i = 0; $i < 200; $i++ ) {
			$local   = ( new DateTimeImmutable( '@' . $ts ) )->setTimezone( $tz );
			$minutes = (int) $local->format( 'G' ) * 60 + (int) $local->format( 'i' );
			$day0    = $local->setTime( 0, 0 );

			if ( $minutes < $win_start ) {
				$ts      = $day0->getTimestamp() + $win_start * 60;
				$steps[] = 'Trước khung giờ đăng, dời tới ' . self::fmt( $ts );
				continue;
			}
			if ( $minutes > $win_end ) {
				$ts      = $day0->modify( '+1 day' )->getTimestamp() + $win_start * 60;
				$steps[] = 'Sau khung giờ đăng, dời sang ' . self::fmt( $ts );
				continue;
			}

			$count = self::count_on_day( $channel, $day0->getTimestamp(), $day0->modify( '+1 day' )->getTimestamp(), $exclude_job );
			if ( $count >= $max ) {
				$ts      = $day0->modify( '+1 day' )->getTimestamp() + $win_start * 60;
				$steps[] = "Ngày {$local->format('d/m')} đã có {$count}/{$max} bài trên kênh, dời sang " . self::fmt( $ts );
				continue;
			}

			if ( $gap > 0 ) {
				$near = self::nearest( $channel, $ts - $gap + 1, $ts + $gap - 1, $exclude_job );
				if ( null !== $near ) {
					$ts      = $near + $gap;
					$steps[] = 'Cách bài khác (' . self::fmt( $near ) . ') chưa đủ ' . SocialSync_Settings::min_gap() . ' phút, dời tới ' . self::fmt( $ts );
					continue;
				}
			}
			$steps[] = 'Chốt ' . self::fmt( $ts );
			return array(
				'ts'     => $ts,
				'source' => 'auto',
				'steps'  => $steps,
			);
		}

		SocialSync_Logger::warn( 'schedule.give_up', 'Không tìm được giờ trống sau 200 lần dời, dùng giờ hiện tại', array( 'post_id' => $post_id, 'channel' => $channel, 'steps' => $steps ) );
		return array(
			'ts'     => time(),
			'source' => 'fallback',
			'steps'  => $steps,
		);
	}

	/** Giờ người viết chọn cho bài (giờ website, dạng 2026-11-20T08:30), đổi ra timestamp. */
	public static function manual_time( int $post_id, string $channel ): ?int {
		$raw = trim( (string) get_post_meta( $post_id, self::META_PREFIX . $channel, true ) );
		if ( '' === $raw ) {
			return null;
		}
		try {
			$dt = new DateTimeImmutable( str_replace( 'T', ' ', $raw ), wp_timezone() );
			return $dt->getTimestamp();
		} catch ( Exception $e ) {
			SocialSync_Logger::warn( 'schedule.bad_manual', 'Giờ đăng người viết nhập không đọc được, bỏ qua', array( 'post_id' => $post_id, 'channel' => $channel, 'raw' => $raw ) );
			return null;
		}
	}

	private static function count_on_day( string $channel, int $from, int $to, int $exclude_job ): int {
		global $wpdb;
		$in = "'" . implode( "','", self::HOLDING ) . "'";
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . SocialSync_DB::table() . " WHERE channel = %s AND id <> %d AND status IN ({$in}) AND COALESCE(published_at, scheduled_at) >= %s AND COALESCE(published_at, scheduled_at) < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$channel,
				$exclude_job,
				gmdate( 'Y-m-d H:i:s', $from ),
				gmdate( 'Y-m-d H:i:s', $to )
			)
		);
	}

	/** Giờ đăng muộn nhất của kênh nằm trong khoảng [from, to], hoặc null. */
	private static function nearest( string $channel, int $from, int $to, int $exclude_job ): ?int {
		global $wpdb;
		$in  = "'" . implode( "','", self::HOLDING ) . "'";
		$val = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT MAX(COALESCE(published_at, scheduled_at)) FROM ' . SocialSync_DB::table() . " WHERE channel = %s AND id <> %d AND status IN ({$in}) AND COALESCE(published_at, scheduled_at) BETWEEN %s AND %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$channel,
				$exclude_job,
				gmdate( 'Y-m-d H:i:s', $from ),
				gmdate( 'Y-m-d H:i:s', $to )
			)
		);
		return null === $val ? null : (int) strtotime( $val . ' UTC' );
	}

	public static function fmt( int $ts ): string {
		return wp_date( 'H:i d/m/Y', $ts );
	}
}
