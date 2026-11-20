<?php
/**
 * "Trung tâm SocialSync": một trang chia đôi gộp Chờ duyệt + Xem và duyệt + Nhật ký.
 *
 * - Trái: danh sách BÀI (không phải lượt), mỗi bài có chip trạng thái từng kênh; tab lọc + ô tìm theo tên.
 * - Phải: chi tiết bài đang chọn: phân loại, ảnh, form duyệt (nếu còn lượt chờ duyệt), thẻ theo dõi từng kênh
 *   (giờ, link bài, tương tác, lỗi đầy đủ, nút đồng bộ lại), dòng thời gian.
 * - Duyệt / từ chối vẫn đi qua SocialSync_Review::handle (không đổi logic chuyển trạng thái, payload n8n).
 * - Cửa vào từ chỗ biên tập viên làm việc: cột "Mạng xã hội" ở danh sách Bài viết / Sản phẩm.
 *
 * Đường dẫn cũ ?page=socialsync-review được chuyển về đây để không gãy link trong thông báo và tài liệu.
 */

defined( 'ABSPATH' ) || exit;

final class SocialSync_Hub {

	public const SLUG      = 'socialsync';
	private const PER_PAGE = 25;

	/** Tab => nhãn. Một bài có thể thuộc nhiều tab (ví dụ Facebook đã đăng, Pinterest lỗi). */
	public const TABS = array(
		'review'     => 'Cần duyệt',
		'processing' => 'AI đang viết',
		'scheduled'  => 'Đã lên lịch',
		'published'  => 'Đã đăng',
		'failed'     => 'Lỗi',
		'closed'     => 'Bỏ qua / Từ chối',
		'all'        => 'Tất cả',
	);

	/** Chip trạng thái: nhãn ngắn + màu. Đỏ chỉ dành cho Lỗi; Bị từ chối dùng tím xám vì là quyết định có chủ đích. */
	public const CHIPS = array(
		'pending'      => array( 'Đang chuẩn bị', '#646970', '◌' ),
		'generating'   => array( 'AI đang viết', '#2271b1', '◌' ),
		'needs_review' => array( 'Chờ duyệt', '#996800', '◐' ),
		'scheduled'    => array( 'Đã lên lịch', '#1d5fa8', '🕑' ),
		'published'    => array( 'Đã đăng', '#007017', '●' ),
		'failed'       => array( 'Lỗi', '#b32d2e', '✕' ),
		'skipped'      => array( 'Không đăng', '#787c82', '—' ),
		'rejected'     => array( 'Đã từ chối', '#6b5b95', '⊘' ),
	);

	/** Lý do từ chối chọn nhanh (người duyệt vẫn sửa được). */
	private const QUICK_REASONS = array( 'Bài nội bộ, không đăng mạng xã hội', 'Sai thông tin sản phẩm', 'Cần duyệt pháp lý', 'Nội dung AI viết chưa đạt' );

	public static function init(): void {
		// admin_menu (không phải admin_init): wp-admin kiểm tra quyền vào trang ngay sau admin_menu, trước admin_init,
		// nên slug cũ không còn đăng ký sẽ bị 403 nếu chuyển hướng muộn hơn.
		add_action( 'admin_menu', array( __CLASS__, 'redirect_legacy' ), 1 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_ajax_socialsync_post_status', array( __CLASS__, 'ajax_post_status' ) );
		foreach ( array( 'post', 'product' ) as $type ) {
			// Ưu tiên 20: WooCommerce dựng lại cột Sản phẩm ở ưu tiên 10, chạy sau thì mới chèn được ngay sau cột Tên.
			add_filter( "manage_{$type}_posts_columns", array( __CLASS__, 'add_column' ), 20 );
			add_action( "manage_{$type}_posts_custom_column", array( __CLASS__, 'render_column' ), 10, 2 );
		}
	}

	public static function url( int $post_id = 0, string $tab = '' ): string {
		$args = array( 'page' => self::SLUG );
		if ( $tab ) {
			$args['tab'] = $tab;
		}
		if ( $post_id ) {
			$args['post_id'] = $post_id;
		}
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/** ?page=socialsync-review (màn Chờ duyệt cũ) -> Trung tâm, giữ post_id. */
	public static function redirect_legacy(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- chỉ chuyển hướng GET.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( SocialSync_Review::SLUG !== $page ) {
			return;
		}
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		// phpcs:enable
		SocialSync_Logger::debug( 'hub.legacy_redirect', 'Đường dẫn Chờ duyệt cũ, chuyển sang Trung tâm', array( 'post_id' => $post_id ) );
		wp_safe_redirect( self::url( $post_id, $post_id ? '' : 'review' ) );
		exit;
	}

	public static function enqueue( string $hook ): void {
		$is_hub  = 'toplevel_page_' . self::SLUG === $hook;
		$is_list = 'edit.php' === $hook;
		if ( ! $is_hub && ! $is_list ) {
			return;
		}
		wp_enqueue_style( 'socialsync-hub', plugins_url( 'assets/hub.css', SOCIALSYNC_FILE ), array(), self::asset_ver( 'hub.css' ) );
		if ( $is_hub ) {
			wp_enqueue_script( 'socialsync-hub', plugins_url( 'assets/hub.js', SOCIALSYNC_FILE ), array(), self::asset_ver( 'hub.js' ), true );
			wp_localize_script(
				'socialsync-hub',
				'SocialSyncHub',
				array(
					'banned'     => SocialSync_Review::BANNED_JS,
					'disclaimer' => (string) SocialSync_Settings::get( 'product_disclaimer' ),
					'refreshSec' => 15,
				)
			);
		}
	}

	/**
	 * Phiên bản file tĩnh = phiên bản plugin + thời điểm sửa file. Site đứng sau Cloudflare: nếu chỉ dùng số phiên bản
	 * plugin thì sửa JS/CSS mà không tăng phiên bản sẽ bị trả bản cũ trong cache (đã gặp 20/11: hub.js cũ, cf-cache-status HIT).
	 */
	public static function asset_ver( string $file ): string {
		$path = SOCIALSYNC_DIR . '/assets/' . $file;
		return SOCIALSYNC_VERSION . '.' . ( file_exists( $path ) ? (string) filemtime( $path ) : '0' );
	}

	// ------------------------------------------------------------------ Dữ liệu

	/**
	 * Gom lượt theo bài. Mỗi (bài, kênh) chỉ có một lượt (UNIQUE post_channel).
	 *
	 * @return array<int, array{post_id:int, post_type:string, jobs:array<string, array>, tabs:string[], updated:string, first_id:int, busy:bool, flags:string[]}>
	 */
	private static function load_groups(): array {
		global $wpdb;
		if ( ! SocialSync_DB::table_exists() ) {
			return array();
		}
		$rows   = (array) $wpdb->get_results( 'SELECT * FROM ' . SocialSync_DB::table() . ' ORDER BY id DESC LIMIT 2000', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$groups = array();
		foreach ( $rows as $r ) {
			$pid = (int) $r['post_id'];
			if ( ! isset( $groups[ $pid ] ) ) {
				$groups[ $pid ] = array(
					'post_id'   => $pid,
					'post_type' => (string) $r['post_type'],
					'jobs'      => array(),
					'updated'   => '',
					'first_id'  => PHP_INT_MAX,
				);
			}
			$groups[ $pid ]['jobs'][ (string) $r['channel'] ] = $r;
			$groups[ $pid ]['updated']                        = max( $groups[ $pid ]['updated'], (string) $r['updated_at'] );
			$groups[ $pid ]['first_id']                       = min( $groups[ $pid ]['first_id'], (int) $r['id'] );
		}
		foreach ( $groups as $pid => $g ) {
			ksort( $g['jobs'] ); // facebook trước pinterest, ổn định ở mọi chỗ hiển thị.
			$groups[ $pid ]['jobs'] = $g['jobs'];
			$statuses = array_column( $g['jobs'], 'status' );
			$tabs     = array( 'all' );
			if ( in_array( 'needs_review', $statuses, true ) ) {
				$tabs[] = 'review';
			}
			if ( array_intersect( array( 'pending', 'generating' ), $statuses ) ) {
				$tabs[] = 'processing';
			}
			if ( in_array( 'scheduled', $statuses, true ) ) {
				$tabs[] = 'scheduled';
			}
			if ( in_array( 'published', $statuses, true ) ) {
				$tabs[] = 'published';
			}
			$stuck = false;
			foreach ( $g['jobs'] as $j ) {
				if ( null !== SocialSync_Log_Page::warning( $j ) ) {
					$stuck = true;
				}
			}
			if ( in_array( 'failed', $statuses, true ) || $stuck ) {
				$tabs[] = 'failed';
			}
			if ( ! array_diff( $statuses, array( 'skipped', 'rejected' ) ) ) {
				$tabs[] = 'closed';
			}
			$groups[ $pid ]['tabs']  = $tabs;
			$groups[ $pid ]['busy']  = self::is_busy( $g['jobs'] );
			$groups[ $pid ]['flags'] = self::flags( $g['jobs'] );
		}
		return $groups;
	}

	/** Có lượt sắp đổi trạng thái (AI đang viết, sắp tới giờ đăng) -> trang nên tự cập nhật. */
	private static function is_busy( array $jobs ): bool {
		foreach ( $jobs as $j ) {
			if ( in_array( $j['status'], array( 'pending', 'generating' ), true ) && null === SocialSync_Log_Page::warning( $j ) ) {
				return true;
			}
			if ( 'scheduled' === $j['status'] && ! empty( $j['scheduled_at'] ) ) {
				$ts = strtotime( (string) $j['scheduled_at'] . ' UTC' );
				if ( $ts && $ts - time() < 120 && time() - $ts < 900 ) {
					return true;
				}
			}
		}
		return false;
	}

	/** Cờ cảnh báo hiện ngay ở dòng danh sách. @return string[] */
	private static function flags( array $jobs ): array {
		$flags = array();
		foreach ( $jobs as $j ) {
			$c = json_decode( (string) $j['content'], true );
			if ( is_array( $c ) && ! empty( $c['sensitive'] ) ) {
				$flags['sensitive'] = 'Nhóm nhạy cảm';
			}
			if ( 'needs_review' === $j['status'] && 'ai' !== $j['content_source'] && ! empty( $j['content_source'] ) ) {
				$flags['template'] = 'Nội dung mẫu (AI lỗi)';
			}
			if ( 'needs_review' === $j['status'] && SocialSync_Review::banned_hits( $j ) ) {
				$flags['banned'] = 'Có từ rủi ro';
			}
		}
		return array_values( $flags );
	}

	// ------------------------------------------------------------------ Trang

	public static function render(): void {
		if ( ! current_user_can( SocialSync_Log_Page::CAP ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- chỉ đọc bộ lọc.
		$groups  = self::load_groups();
		$counts  = array_fill_keys( array_keys( self::TABS ), 0 );
		foreach ( $groups as $g ) {
			foreach ( $g['tabs'] as $t ) {
				++$counts[ $t ];
			}
		}
		$tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		$tab     = isset( self::TABS[ $tab ] ) ? $tab : ( $counts['review'] ? 'review' : 'all' );
		$search  = isset( $_GET['s'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : '';
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		$paged   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		// phpcs:enable

		$list = array_filter( $groups, static fn( $g ) => in_array( $tab, $g['tabs'], true ) );
		if ( '' !== $search ) {
			// Tìm không phân biệt dấu ("suc khoe" khớp "sức khỏe") và theo #ID.
			$needle = mb_strtolower( remove_accents( $search ) );
			$list   = array_filter( $list, static fn( $g ) => false !== mb_strpos( mb_strtolower( remove_accents( html_entity_decode( (string) get_the_title( $g['post_id'] ), ENT_QUOTES, 'UTF-8' ) ) ), $needle ) || (string) $g['post_id'] === ltrim( $search, '#' ) );
		}
		// Hàng đợi duyệt: bài cũ nhất lên đầu (xử lý theo thứ tự đến). Các tab khác: mới cập nhật lên đầu.
		uasort( $list, 'review' === $tab ? static fn( $a, $b ) => $a['first_id'] <=> $b['first_id'] : static fn( $a, $b ) => strcmp( $b['updated'], $a['updated'] ) );
		$total = count( $list );
		// Trang vượt quá số trang (link cũ, gõ tay ?paged=999): đưa về trang cuối thay vì báo "không có bài".
		$paged = min( $paged, max( 1, (int) ceil( $total / self::PER_PAGE ) ) );
		$list  = array_slice( $list, ( $paged - 1 ) * self::PER_PAGE, self::PER_PAGE, true );
		if ( ! $post_id && $list ) {
			$post_id = (int) array_key_first( $list );
		}
		$busy = ( $post_id && ! empty( $groups[ $post_id ]['busy'] ) ) || (bool) array_filter( $list, static fn( $g ) => $g['busy'] );

		SocialSync_Logger::debug(
			'hub.view',
			'Mở Trung tâm SocialSync',
			array(
				'tab'      => $tab,
				'search'   => $search,
				'post_id'  => $post_id,
				'paged'    => $paged,
				'shown'    => count( $list ),
				'total'    => $total,
				'counts'   => $counts,
				'busy'     => $busy,
				'by_user'  => get_current_user_id(),
			)
		);

		echo '<div class="wrap ss-hub" data-ss-busy="' . ( $busy ? '1' : '0' ) . '">';
		self::render_header( $counts );
		SocialSync_Review::render_notice();
		SocialSync_Log_Page::render_notice();
		self::render_tabs( $tab, $counts, $search, $post_id );
		echo '<div class="ss-split">';
		echo '<section class="ss-list" aria-label="Danh sách bài">';
		self::render_list( $list, $tab, $post_id, $search, $total, $paged );
		echo '</section><section class="ss-detail" id="ss-detail" aria-label="Chi tiết bài">';
		if ( $post_id ) {
			self::render_detail( $post_id, $groups[ $post_id ]['jobs'] ?? array(), $tab, $counts['review'] );
		} else {
			echo '<div class="ss-empty"><p><strong>Chưa có bài nào ở mục này.</strong></p><p>Bài viết hoặc sản phẩm mới xuất bản sẽ tự xuất hiện ở đây sau 10–30 giây (AI phân loại và viết nội dung).</p></div>';
		}
		echo '</section></div></div>';
	}

	private static function render_header( array $counts ): void {
		$approval = SocialSync_Settings::approval_required();
		$max      = (array) SocialSync_Settings::get( 'max_per_day' );
		?>
		<div class="ss-head">
			<div>
				<h1>Trung tâm SocialSync</h1>
				<p class="ss-sub">Mọi việc với mạng xã hội sau khi bấm Đăng bài: duyệt, sửa, chọn giờ, theo dõi, đọc lỗi, đồng bộ lại.</p>
			</div>
			<div class="ss-mode">
				<span class="ss-badge <?php echo $approval ? 'ss-badge-warn' : 'ss-badge-ok'; ?>" title="Chế độ do Quản trị đặt trong Cấu hình"><?php echo $approval ? 'Chế độ: Duyệt bắt buộc' : 'Chế độ: Tự đăng'; ?></span>
				<span class="ss-meta">Khung giờ <?php echo esc_html( SocialSync_Settings::get( 'window_start' ) . '–' . SocialSync_Settings::get( 'window_end' ) ); ?> · tối đa <?php echo (int) max( $max ?: array( 10 ) ); ?> bài/ngày/kênh · cách nhau ≥ <?php echo (int) SocialSync_Settings::min_gap(); ?> phút</span>
				<span class="ss-links">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . SocialSync_Report::SLUG ) ); ?>">Báo cáo</a> ·
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . SocialSync_Log_Page::SLUG ) ); ?>">Nhật ký chi tiết</a> ·
					<a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>">Viết bài mới</a>
					<?php if ( current_user_can( 'manage_options' ) ) : ?> · <a href="<?php echo esc_url( SocialSync_Admin::settings_url() ); ?>">Cấu hình</a><?php endif; ?>
				</span>
			</div>
		</div>
		<?php
	}

	private static function render_tabs( string $tab, array $counts, string $search, int $post_id ): void {
		echo '<div class="ss-toolbar"><nav class="ss-tabs" aria-label="Lọc theo trạng thái">';
		foreach ( self::TABS as $key => $label ) {
			if ( 'processing' === $key && ! $counts[ $key ] && $tab !== $key ) {
				continue; // Không có bài đang viết thì ẩn tab cho gọn.
			}
			if ( 'review' === $key && ! $counts[ $key ] && $tab !== $key && ! SocialSync_Settings::approval_required() ) {
				continue; // Chế độ Tự đăng: không có bài nào chờ duyệt thì không cần tab Cần duyệt.
			}
			printf(
				'<a class="ss-tab%s%s" href="%s"%s>%s <span class="ss-count">%d</span></a>',
				$tab === $key ? ' is-active' : '',
				'failed' === $key && $counts[ $key ] ? ' has-error' : '',
				esc_url( add_query_arg( array( 'page' => self::SLUG, 'tab' => $key, 's' => $search ?: null ), admin_url( 'admin.php' ) ) ),
				$tab === $key ? ' aria-current="page"' : '',
				esc_html( $label ),
				(int) $counts[ $key ]
			);
		}
		echo '</nav>';
		?>
		<form method="get" class="ss-search" role="search">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
			<label class="screen-reader-text" for="ss-s">Tìm theo tên bài</label>
			<input type="search" id="ss-s" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Tìm tên bài hoặc #ID">
			<button class="button">Tìm</button>
			<label class="ss-auto"><input type="checkbox" id="ss-auto" checked> Tự cập nhật</label>
		</form>
		</div>
		<?php
	}

	private static function render_list( array $list, string $tab, int $selected, string $search, int $total, int $paged ): void {
		if ( ! $list ) {
			echo '<p class="ss-none">' . ( '' !== $search ? 'Không có bài nào khớp “' . esc_html( $search ) . '”.' : ( 'review' === $tab ? 'Không còn bài nào chờ duyệt. 🎉' : 'Chưa có bài nào ở mục này.' ) ) . '</p>';
			return;
		}
		echo '<ul class="ss-rows">';
		foreach ( $list as $pid => $g ) {
			$title = (string) get_the_title( $pid );
			$first = reset( $g['jobs'] );
			$class = (string) ( $first['classification'] ?? '' );
			foreach ( $g['jobs'] as $j ) {
				$class = $class ?: (string) ( $j['classification'] ?? '' );
			}
			printf(
				'<li class="ss-row%s"><a href="%s"><span class="ss-title">%s</span><span class="ss-sub">%s #%d%s</span><span class="ss-chips">%s</span>%s</a></li>',
				$pid === $selected ? ' is-selected' : '',
				esc_url( add_query_arg( array( 'page' => self::SLUG, 'tab' => $tab, 'post_id' => $pid, 's' => $search ?: null, 'paged' => $paged > 1 ? $paged : null ), admin_url( 'admin.php' ) ) . '#ss-detail' ),
				esc_html( '' !== $title ? $title : '(bài #' . $pid . ' không có tiêu đề hoặc đã xoá)' ),
				esc_html( 'product' === $g['post_type'] ? 'Sản phẩm' : 'Bài viết' ),
				(int) $pid,
				$class ? ' · ' . esc_html( SocialSync_Report::CLASS_LABELS[ $class ] ?? $class ) : '',
				self::chips_html( $g['jobs'] ),
				$g['flags'] ? '<span class="ss-flags">' . implode( '', array_map( static fn( $f ) => '<span class="ss-flag">⚠ ' . esc_html( $f ) . '</span>', $g['flags'] ) ) . '</span>' : ''
			);
		}
		echo '</ul>';
		$pages = (int) ceil( $total / self::PER_PAGE );
		if ( $pages > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post(
				(string) paginate_links(
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'current' => $paged,
						'total'   => $pages,
					)
				)
			) . '</div></div>';
		}
	}

	/** Chip từng kênh của một bài: dùng ở danh sách Trung tâm và cột danh sách bài. */
	public static function chips_html( array $jobs ): string {
		$out = '';
		foreach ( $jobs as $j ) {
			$out .= self::chip( $j );
		}
		return $out;
	}

	public static function chip( array $j ): string {
		$status = (string) $j['status'];
		[ $label, $color, $icon ] = self::CHIPS[ $status ] ?? array( $status, '#646970', '·' );
		$short  = 'facebook' === $j['channel'] ? 'FB' : ( 'pinterest' === $j['channel'] ? 'Pin' : (string) $j['channel'] );
		$extra  = '';
		if ( 'scheduled' === $status && ! empty( $j['scheduled_at'] ) ) {
			$extra = ' ' . get_date_from_gmt( (string) $j['scheduled_at'], 'H:i d/m' );
		} elseif ( 'published' === $status && ! empty( $j['published_at'] ) ) {
			$extra = ' ' . get_date_from_gmt( (string) $j['published_at'], 'H:i d/m' );
		} elseif ( 'skipped' === $status ) {
			$extra = SocialSync_Review::NO_IMAGE_CODE === SocialSync_Review::skip_code( $j ) ? ': thiếu ảnh' : '';
		}
		$warn = null !== SocialSync_Log_Page::warning( $j );
		return sprintf(
			'<span class="ss-chip" style="--c:%s" title="%s"><b>%s</b> %s %s%s%s</span>',
			esc_attr( $warn ? '#b32d2e' : $color ),
			esc_attr( ( SocialSync_Settings::CHANNELS[ $j['channel'] ] ?? $j['channel'] ) . ': ' . $label . ( empty( $j['error'] ) ? '' : ' — ' . mb_substr( (string) $j['error'], 0, 160 ) ) ),
			esc_html( $short ),
			esc_html( $icon ),
			esc_html( $label ),
			esc_html( $extra ),
			$warn ? ' ⚠' : ''
		);
	}

	// ------------------------------------------------------------------ Chi tiết

	private static function render_detail( int $post_id, array $jobs, string $tab, int $review_count ): void {
		$post = get_post( $post_id );
		if ( ! $jobs ) {
			$jobs = array();
			foreach ( SocialSync_DB::jobs( $post_id, 10 ) as $j ) {
				$jobs[ (string) $j['channel'] ] = $j;
			}
		}
		$title     = $post ? (string) get_the_title( $post_id ) : '';
		$thumb_id  = $post ? (int) get_post_thumbnail_id( $post_id ) : 0;
		$thumb     = $thumb_id ? wp_get_attachment_image_src( $thumb_id, 'medium' ) : false;
		$thumb_url = is_array( $thumb ) ? (string) $thumb[0] : '';
		$review    = array_values( array_filter( $jobs, static fn( $j ) => 'needs_review' === $j['status'] ) );
		$others    = array_values( array_filter( $jobs, static fn( $j ) => 'needs_review' !== $j['status'] ) );
		$content   = array();
		$class     = '';
		$source    = null;
		foreach ( $jobs as $j ) {
			$c = json_decode( (string) $j['content'], true );
			if ( is_array( $c ) && ! $content ) {
				$content = $c;
			}
			$class  = $class ?: (string) ( $j['classification'] ?? '' );
			$source = $source ?? ( empty( $j['content_source'] ) ? null : $j );
		}
		SocialSync_Logger::debug(
			'hub.detail',
			'Mở chi tiết bài trong Trung tâm',
			array(
				'post_id'      => $post_id,
				'post_exists'  => (bool) $post,
				'review_jobs'  => array_column( $review, 'id' ),
				'other_jobs'   => array_map( static fn( $j ) => array( 'id' => (int) $j['id'], 'channel' => $j['channel'], 'status' => $j['status'] ), $others ),
				'has_image'    => '' !== $thumb_url,
				'class'        => $class,
			)
		);
		?>
		<header class="ss-dhead">
			<div>
				<h2><?php echo esc_html( '' !== $title ? $title : '(bài #' . $post_id . ' đã xoá hoặc không có tiêu đề)' ); ?></h2>
				<p class="ss-sub"><?php echo esc_html( ( $post && 'product' === $post->post_type ? 'Sản phẩm' : 'Bài viết' ) . ' #' . $post_id ); ?>
					<?php if ( $post ) : ?>
						· <a href="<?php echo esc_url( (string) get_edit_post_link( $post_id ) ); ?>">Sửa bài</a>
						· <a href="<?php echo esc_url( (string) get_permalink( $post_id ) ); ?>" target="_blank" rel="noopener">Xem trên website ↗</a>
					<?php endif; ?>
				</p>
			</div>
			<a class="ss-back button" href="#ss-s">← Danh sách</a>
		</header>

		<?php if ( ! $jobs ) : ?>
			<div class="ss-empty"><p>Bài này chưa có lượt đồng bộ nào. Lượt được tạo khi bài/sản phẩm được <strong>xuất bản lần đầu</strong> (bài thuộc danh mục bị chặn vẫn có lượt “Không đăng” kèm lý do).</p></div>
			<?php return; ?>
		<?php endif; ?>

		<div class="ss-facts">
			<div class="ss-fact"><span>Phân loại</span>
				<strong><?php echo esc_html( SocialSync_Report::CLASS_LABELS[ $class ] ?? 'Chưa phân loại' ); ?></strong>
				<?php if ( ! empty( $content['classification_reason'] ) ) : ?><em><?php echo esc_html( (string) $content['classification_reason'] ); ?></em><?php endif; ?>
			</div>
			<div class="ss-fact"><span>Nguồn nội dung</span>
				<?php if ( $source ) : ?>
					<strong><?php echo esc_html( 'ai' === $source['content_source'] ? 'Claude viết' : 'Mẫu cố định' ); ?></strong>
					<?php if ( ! empty( $source['content_source_reason'] ) ) : ?><em><?php echo esc_html( (string) $source['content_source_reason'] ); ?></em><?php endif; ?>
					<?php if ( ! empty( $source['model'] ) ) : // Lý do đã nêu model thì chỉ thêm số token + thời gian. ?><em><?php echo esc_html( sprintf( '%s token vào / %s token ra · AI viết trong %s giây', $source['tokens_in'] ?? '?', $source['tokens_out'] ?? '?', null === $source['ai_ms'] ? '?' : number_format_i18n( (int) $source['ai_ms'] / 1000, 1 ) ) ); ?></em><?php endif; ?>
				<?php else : ?>
					<strong>Chưa có</strong><em>AI chưa viết (hoặc bài bị chặn trước khi gọi AI)</em>
				<?php endif; ?>
			</div>
			<div class="ss-fact ss-fact-img"><span>Ảnh đại diện</span>
				<?php if ( $thumb_url ) : ?>
					<img src="<?php echo esc_url( $thumb_url ); ?>" alt="">
				<?php else : ?>
					<strong class="ss-red">Không có</strong><em>Facebook vẫn đăng dạng bài kèm link; Pinterest không đăng được.</em>
					<?php if ( $post ) : ?><a href="<?php echo esc_url( (string) get_edit_post_link( $post_id ) ); ?>" target="_blank" rel="noopener">Thêm ảnh đại diện ↗</a><?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php $unpub = $post ? SocialSync_Log_Page::unpublished_reason( $post ) : null; ?>
		<?php if ( $unpub ) : ?>
			<p class="ss-alert ss-alert-red"><strong><?php echo esc_html( $unpub ); ?>.</strong> Xuất bản lại bài trên website rồi mới duyệt hoặc đồng bộ lại được; vẫn có thể Từ chối.</p>
		<?php endif; ?>
		<?php if ( $post && 'publish' === $post->post_status && null !== ( $block = SocialSync_Publish_Hook::block_reason( $post ) ) ) : // phpcs:ignore ?>
			<p class="ss-alert ss-alert-warn"><strong>Bị chặn ở WordPress:</strong> <?php echo esc_html( $block ); ?>. Bài này không được gửi sang AI và không đăng mạng xã hội.</p>
		<?php endif; ?>
		<?php if ( ! empty( $content['sensitive'] ) ) : ?>
			<p class="ss-alert ss-alert-red"><strong>Nhóm nhạy cảm</strong> (trẻ em, phụ nữ mang thai hoặc nhắc tới nhân viên y tế): ở bản chính thức cần thêm người phụ trách pháp lý duyệt.</p>
		<?php endif; ?>

		<?php
		if ( $review ) {
			self::render_review_form( $post_id, $review, $thumb_url, $review_count, null === $unpub );
		}
		if ( $others ) {
			echo '<h3 class="ss-h3">' . ( $review ? 'Kênh khác của bài' : 'Kết quả từng kênh' ) . '</h3><div class="ss-cards">';
			foreach ( $others as $j ) {
				self::render_track_card( $j );
			}
			echo '</div>';
		}
		self::render_timeline( $post, $jobs );
	}

	private static function render_review_form( int $post_id, array $jobs, string $thumb_url, int $review_count, bool $can_approve = true ): void {
		?>
		<form method="post" class="ss-review" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-ss-form="review">
			<input type="hidden" name="action" value="socialsync_review">
			<input type="hidden" name="post_id" value="<?php echo (int) $post_id; ?>">
			<?php wp_nonce_field( 'socialsync_review_' . $post_id ); ?>
			<h3 class="ss-h3">Chờ duyệt <span class="ss-meta">— sửa trực tiếp, bỏ chọn kênh không muốn đăng, đổi giờ rồi duyệt</span></h3>
			<?php $draft = SocialSync_Review::take_draft( $post_id ); ?>
			<?php if ( $draft ) : ?>
				<p class="ss-alert ss-alert-warn">Đã điền lại phần bạn vừa sửa (chưa được lưu). Sửa theo thông báo lỗi phía trên rồi bấm lại.</p>
			<?php endif; ?>
			<div class="ss-cards">
				<?php foreach ( $jobs as $job ) : ?>
					<?php SocialSync_Review::render_channel( $job, $thumb_url, $draft ); ?>
				<?php endforeach; ?>
			</div>
			<div class="ss-actions">
				<div class="ss-approve">
					<?php if ( ! $can_approve ) : ?>
						<p class="ss-meta">Bài không còn xuất bản trên website nên chưa duyệt được (xem băng đỏ phía trên). Vẫn có thể Từ chối.</p>
					<?php else : ?>
					<button type="submit" name="decision" value="approve" class="button button-primary button-large">Duyệt và đăng theo giờ đã chọn</button>
					<button type="submit" name="decision" value="approve_now" class="button button-large">Duyệt và đăng ngay</button>
					<?php endif; ?>
					<label class="ss-next"><input type="checkbox" name="next" value="1" checked> Xong thì mở bài chờ duyệt tiếp theo<?php echo $review_count > 1 ? ' (còn ' . (int) ( $review_count - 1 ) . ')' : ''; ?></label>
				</div>
				<div class="ss-reject">
					<label for="ss-note">Lý do từ chối <span class="ss-meta">(bắt buộc khi từ chối)</span></label>
					<div class="ss-reasons">
						<?php foreach ( self::QUICK_REASONS as $r ) : ?>
							<button type="button" class="button button-small" data-ss-reason="<?php echo esc_attr( $r ); ?>"><?php echo esc_html( $r ); ?></button>
						<?php endforeach; ?>
					</div>
					<input type="text" id="ss-note" name="note" class="large-text" placeholder="Hoặc tự ghi lý do" value="<?php echo esc_attr( (string) ( $draft['note'] ?? '' ) ); ?>">
					<button type="submit" name="decision" value="reject" class="button ss-btn-reject" data-ss-reject onclick="return confirm('Từ chối: nội dung sẽ không được đăng lên các kênh. Tiếp tục?');">Từ chối toàn bộ</button>
				</div>
			</div>
		</form>
		<?php
	}

	private static function render_track_card( array $j ): void {
		$status = (string) $j['status'];
		$c      = json_decode( (string) $j['content'], true );
		$c      = is_array( $c ) ? $c : array();
		$warn   = SocialSync_Log_Page::warning( $j );
		$exec   = SocialSync_Settings::n8n_execution_url( (string) ( $j['execution_id'] ?? '' ) );
		?>
		<article class="ss-card ss-st-<?php echo esc_attr( $status ); ?>">
			<header><strong><?php echo esc_html( SocialSync_Settings::CHANNELS[ $j['channel'] ] ?? $j['channel'] ); ?></strong> <?php echo self::chip( $j ); // phpcs:ignore WordPress.Security.EscapeOutput -- đã escape trong chip(). ?></header>
			<?php if ( $warn ) : ?>
				<p class="ss-alert ss-alert-red">⚠ <?php echo esc_html( $warn ); ?></p>
			<?php endif; ?>
			<?php if ( 'skipped' === $status && SocialSync_Review::NO_IMAGE_CODE === SocialSync_Review::skip_code( $j ) ) : ?>
				<p class="ss-meta">Muốn đăng Pin: thêm ảnh đại diện cho bài, rồi bấm “Đồng bộ lại” bên dưới.</p>
			<?php endif; ?>
			<ul class="ss-kv">
				<?php if ( 'skipped' === $status ) : // Lượt không đăng: giờ/người duyệt cũ (nếu có) không còn ý nghĩa, ẩn để khỏi hiểu nhầm. ?>
				<?php elseif ( ! empty( $j['scheduled_at'] ) ) : ?><li><span>Giờ đăng</span><?php echo esc_html( SocialSync_Log_Page::local( $j['scheduled_at'] ) ); ?></li><?php endif; ?>
				<?php if ( ! empty( $j['published_at'] ) ) : ?><li><span>Đã đăng lúc</span><?php echo esc_html( SocialSync_Log_Page::local( $j['published_at'] ) ); ?></li><?php endif; ?>
				<?php if ( ! empty( $j['reviewed_at'] ) && 'skipped' !== $status ) : ?><li><span>Người duyệt</span><?php echo esc_html( sprintf( '%s · %s · %s', (string) ( get_userdata( (int) $j['reviewed_by'] )->display_name ?? '#' . $j['reviewed_by'] ), SocialSync_Log_Page::local( $j['reviewed_at'] ), (string) $j['review_note'] ) ); ?></li><?php endif; ?>
				<?php if ( ! empty( $j['metrics'] ) ) : ?>
					<?php $m = json_decode( (string) $j['metrics'], true ); ?>
					<li><span>Tương tác</span><?php echo esc_html( sprintf( '%d cảm xúc · %d bình luận · %d chia sẻ (đo %s)', $m['reactions'] ?? 0, $m['comments'] ?? 0, $m['shares'] ?? 0, SocialSync_Log_Page::local( $j['metrics_at'] ) ) ); ?></li>
				<?php endif; ?>
				<?php if ( $exec ) : ?><li><span>Lượt chạy n8n</span><a href="<?php echo esc_url( $exec ); ?>" target="_blank" rel="noopener">#<?php echo esc_html( (string) $j['execution_id'] ); ?> ↗</a></li><?php endif; ?>
			</ul>
			<?php if ( ! empty( $j['remote_url'] ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( (string) $j['remote_url'] ); ?>" target="_blank" rel="noopener">Mở bài trên <?php echo esc_html( SocialSync_Settings::CHANNELS[ $j['channel'] ] ?? '' ); ?> ↗</a>
					<?php if ( ! empty( $j['visibility'] ) && 'public' !== $j['visibility'] ) : ?><span class="ss-meta">Hiển thị: chỉ chủ tài khoản thấy</span><?php endif; ?></p>
			<?php endif; ?>
			<?php if ( 'scheduled' === $status && SocialSync_Review::is_claimed( $j ) ) : ?>
				<p class="ss-meta">⏳ n8n đã nhận lượt và đang đăng: không đổi giờ hay hủy được nữa.</p>
			<?php elseif ( 'scheduled' === $status ) : ?>
				<form method="post" class="ss-reschedule" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="socialsync_schedule">
					<input type="hidden" name="job_id" value="<?php echo (int) $j['id']; ?>">
					<?php wp_nonce_field( 'socialsync_schedule_' . (int) $j['id'] ); ?>
					<label class="ss-field">Đổi giờ đăng <span class="ss-meta">(giờ website)</span>
						<input type="datetime-local" name="at" value="<?php echo esc_attr( get_date_from_gmt( (string) $j['scheduled_at'], 'Y-m-d\TH:i' ) ); ?>"></label>
					<div class="ss-resync">
						<button type="submit" name="op" value="move" class="button button-small">Lưu giờ mới</button>
						<button type="submit" name="op" value="now" class="button button-small" onclick="return confirm('Đăng ngay lượt này lên kênh?');">Đăng ngay</button>
						<button type="submit" name="op" value="cancel" class="button button-small ss-btn-reject" onclick="return confirm('Hủy lịch: lượt trở về Chờ duyệt, nội dung đã duyệt được giữ. Tiếp tục?');">Hủy lịch</button>
					</div>
				</form>
			<?php endif; ?>
			<?php if ( ! empty( $j['error'] ) && 'rejected' !== $status ) : ?>
				<pre class="<?php echo 'failed' === $status ? 'ss-err' : 'ss-note'; ?>"><?php echo esc_html( (string) $j['error'] ); ?></pre>
			<?php elseif ( 'rejected' === $status ) : ?>
				<p class="ss-note"><?php echo esc_html( (string) ( $j['review_note'] ?: $j['error'] ) ); ?></p>
			<?php endif; ?>
			<?php if ( $c ) : ?>
				<details><summary><?php echo 'published' === $status ? 'Nội dung đã đăng' : 'Nội dung'; ?></summary>
					<?php if ( 'facebook' === $j['channel'] ) : ?>
						<pre class="ss-content"><?php echo esc_html( (string) ( $c['message'] ?? '' ) ); ?></pre>
					<?php else : ?>
						<pre class="ss-content"><?php echo esc_html( sprintf( "Tiêu đề: %s\n\n%s\n\nAlt: %s\nBoard: %s", $c['title'] ?? '', $c['description'] ?? '', $c['alt_text'] ?? '', $c['board'] ?? SocialSync_Settings::get( 'default_board' ) ) ); ?></pre>
					<?php endif; ?>
				</details>
			<?php endif; ?>
			<?php if ( 'scheduled' !== $status || $warn ) : // Lượt đã lên lịch bình thường: dùng form đổi giờ / hủy ở trên. ?>
				<div class="ss-card-actions"><?php SocialSync_Log_Page::render_actions( $j ); ?></div>
			<?php endif; ?>
		</article>
		<?php
	}

	private static function render_timeline( ?WP_Post $post, array $jobs ): void {
		$events = array();
		if ( $post && 'publish' === $post->post_status ) {
			$events[] = array( (string) $post->post_date_gmt, 'Xuất bản trên website (' . (string) ( get_userdata( (int) $post->post_author )->display_name ?? '' ) . ')' );
		}
		foreach ( $jobs as $j ) {
			$ch = SocialSync_Settings::CHANNELS[ $j['channel'] ] ?? $j['channel'];
			$events[] = array( (string) $j['created_at'], $ch . ': tạo lượt đồng bộ' );
			if ( ! empty( $j['reviewed_at'] ) ) {
				$who      = (string) ( get_userdata( (int) $j['reviewed_by'] )->display_name ?? '#' . $j['reviewed_by'] );
				$note     = (string) $j['review_note'];
				$verb     = 'rejected' === $j['status'] ? 'từ chối bởi ' . $who : ( 0 === strpos( $note, 'Hủy lịch' ) ? '' : 'duyệt bởi ' . $who );
				$events[] = array( (string) $j['reviewed_at'], $ch . ': ' . trim( $verb . ( $note ? ( $verb ? ' — ' : '' ) . $note : '' ) ) );
			}
			if ( ! empty( $j['published_at'] ) ) {
				$events[] = array( (string) $j['published_at'], $ch . ': đã đăng' );
			}
			if ( in_array( $j['status'], array( 'failed', 'skipped' ), true ) ) {
				$events[] = array( (string) $j['updated_at'], $ch . ': ' . ( 'failed' === $j['status'] ? 'lỗi' : 'không đăng' ) . ' — ' . mb_substr( (string) $j['error'], 0, 140 ) );
			}
		}
		usort( $events, static fn( $a, $b ) => strcmp( $a[0], $b[0] ) );
		echo '<details class="ss-timeline"><summary>Dòng thời gian (' . count( $events ) . ')</summary><ol>';
		foreach ( $events as $e ) {
			printf( '<li><time>%s</time> %s</li>', esc_html( SocialSync_Log_Page::local( $e[0] ) ), esc_html( $e[1] ) );
		}
		echo '</ol></details>';
	}

	// ------------------------------------------------------------------ Cột ở danh sách Bài viết / Sản phẩm

	public static function add_column( array $cols ): array {
		if ( ! current_user_can( SocialSync_Log_Page::CAP ) ) {
			return $cols;
		}
		$out = array();
		foreach ( $cols as $k => $v ) {
			$out[ $k ] = $v;
			if ( 'title' === $k || 'name' === $k ) {
				$out['socialsync'] = 'Mạng xã hội';
			}
		}
		$out['socialsync'] = $out['socialsync'] ?? 'Mạng xã hội';
		return $out;
	}

	public static function render_column( string $col, int $post_id ): void {
		if ( 'socialsync' !== $col ) {
			return;
		}
		static $cache = null;
		if ( null === $cache ) {
			// Một truy vấn cho cả trang danh sách thay vì một truy vấn mỗi dòng.
			global $wp_query, $wpdb;
			$ids   = array_map( 'intval', wp_list_pluck( (array) ( $wp_query->posts ?? array() ), 'ID' ) );
			$cache = array();
			if ( $ids && SocialSync_DB::table_exists() ) {
				$in   = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				$rows = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SocialSync_DB::table() . " WHERE post_id IN ({$in}) ORDER BY channel", $ids ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
				foreach ( $rows as $r ) {
					$cache[ (int) $r['post_id'] ][ (string) $r['channel'] ] = $r;
				}
			}
			SocialSync_Logger::debug( 'hub.column', 'Hiện cột Mạng xã hội ở danh sách bài', array( 'post_ids' => count( $ids ), 'with_jobs' => count( $cache ) ) );
		}
		$jobs = $cache[ $post_id ] ?? array();
		if ( ! $jobs ) {
			echo '<span class="ss-meta">—</span>';
			return;
		}
		printf( '<a class="ss-colchips" href="%s" title="Mở trong Trung tâm SocialSync">%s</a>', esc_url( self::url( $post_id ) ), self::chips_html( $jobs ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/** Panel trong trình soạn hỏi trạng thái kênh sau khi bấm Đăng (không tải lại trang). */
	public static function ajax_post_status(): void {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		check_ajax_referer( 'socialsync_post_status', 'nonce' );
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			SocialSync_Logger::warn( 'hub.ajax_denied', 'Hỏi trạng thái kênh: thiếu quyền hoặc thiếu post_id', array( 'post_id' => $post_id, 'by_user' => get_current_user_id() ) );
			wp_send_json_error( array( 'message' => 'Không có quyền' ), 403 );
		}
		$status = self::status_for_editor( $post_id );
		SocialSync_Logger::debug( 'hub.ajax_status', 'Trình soạn hỏi trạng thái kênh', array( 'post_id' => $post_id, 'statuses' => array_column( $status, 'status', 'channel' ) ) );
		wp_send_json_success( array( 'status' => $status ) );
	}

	/**
	 * Trạng thái từng kênh của một bài, cho panel trong trình soạn.
	 *
	 * @return array<int, array{channel:string, label:string, status:string, text:string}>
	 */
	public static function status_for_editor( int $post_id ): array {
		$out = array();
		if ( $post_id <= 0 || ! SocialSync_DB::table_exists() ) {
			return $out;
		}
		$jobs = SocialSync_DB::jobs( $post_id, 10 );
		usort( $jobs, static fn( $a, $b ) => strcmp( (string) $a['channel'], (string) $b['channel'] ) ); // facebook trước pinterest
		foreach ( $jobs as $j ) {
			[ $label ] = self::CHIPS[ $j['status'] ] ?? array( $j['status'] );
			$text      = $label;
			if ( 'scheduled' === $j['status'] && ! empty( $j['scheduled_at'] ) ) {
				$text .= ' ' . get_date_from_gmt( (string) $j['scheduled_at'], 'H:i d/m' );
			} elseif ( 'published' === $j['status'] && ! empty( $j['published_at'] ) ) {
				$text .= ' ' . get_date_from_gmt( (string) $j['published_at'], 'H:i d/m' );
			} elseif ( in_array( $j['status'], array( 'skipped', 'failed' ), true ) && ! empty( $j['error'] ) ) {
				$text .= ': ' . mb_substr( (string) $j['error'], 0, 90 );
			}
			$out[] = array(
				'channel' => (string) $j['channel'],
				'label'   => SocialSync_Settings::CHANNELS[ $j['channel'] ] ?? (string) $j['channel'],
				'status'  => (string) $j['status'],
				'text'    => $text,
				'url'     => (string) ( $j['remote_url'] ?? '' ),
			);
		}
		return $out;
	}
}
