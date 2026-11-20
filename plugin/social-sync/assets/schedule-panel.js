/**
 * Bảng "Mạng xã hội (SocialSync)" trong trình soạn khối.
 * - Trước khi xuất bản: chọn giờ đăng từng kênh. Ghi vào post meta _socialsync_at_{kênh} (giờ website, dạng 2026-11-20T08:30);
 *   để trống = hệ thống tự xếp lịch.
 * - Sau khi xuất bản: hiện trạng thái từng kênh (hỏi lại máy chủ mỗi 10 giây khi còn đang xử lý) và nút mở đúng bài
 *   trong Trung tâm SocialSync. Không cần tải lại trang sau khi bấm Đăng.
 * Mọi bước ghi console.debug với tiền tố [SocialSync panel].
 */
( function ( wp, cfg ) {
	if ( ! wp || ! wp.plugins || ! cfg ) {
		return;
	}
	var TAG = '[SocialSync panel]';
	function log() {
		try {
			console.debug.apply( console, [ TAG ].concat( [].slice.call( arguments ) ) );
		} catch ( e ) {}
	}
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var Panel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || ( wp.editPost && wp.editPost.PluginDocumentSettingPanel );
	if ( ! Panel ) {
		log( 'không có PluginDocumentSettingPanel, bỏ qua' );
		return;
	}
	var useSelect = wp.data.useSelect;
	var useEntityProp = wp.coreData.useEntityProp;
	var TextControl = wp.components.TextControl;
	var Button = wp.components.Button;

	var COLORS = {
		pending: '#646970', generating: '#2271b1', needs_review: '#996800', scheduled: '#1d5fa8',
		published: '#007017', failed: '#b32d2e', skipped: '#787c82', rejected: '#6b5b95',
	};
	var BUSY = [ 'pending', 'generating' ];

	function fetchStatus( postId ) {
		var url = cfg.ajaxUrl + '?action=socialsync_post_status&post_id=' + encodeURIComponent( postId ) + '&nonce=' + encodeURIComponent( cfg.nonce );
		return window.fetch( url, { credentials: 'same-origin' } ).then( function ( r ) {
			return r.json();
		} ).then( function ( body ) {
			if ( ! body || ! body.success ) {
				throw new Error( ( body && body.data && body.data.message ) || 'Không đọc được trạng thái' );
			}
			return body.data.status || [];
		} );
	}

	function StatusList( props ) {
		if ( ! props.status.length ) {
			return el( 'p', null, cfg.approval
				? 'Đã gửi sang AI. Khoảng 10–30 giây nữa nội dung sẽ chờ duyệt trong Trung tâm SocialSync.'
				: 'Đã gửi sang AI. Khoảng 10–30 giây nữa nội dung sẽ được xếp lịch đăng.' );
		}
		return el( 'ul', { style: { margin: '0 0 8px' } }, props.status.map( function ( s ) {
			return el( 'li', { key: s.channel, style: { marginBottom: '6px' } },
				el( 'strong', null, s.label + ': ' ),
				el( 'span', { style: { color: COLORS[ s.status ] || '#1d2327' } }, s.text ),
				s.url ? el( 'span', null, ' · ', el( 'a', { href: s.url, target: '_blank', rel: 'noopener' }, 'mở ↗' ) ) : null
			);
		} ) );
	}

	function SchedulePanel() {
		var postType = useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostType();
		}, [] );
		var postId = useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostId();
		}, [] );
		// Trạng thái ĐÃ LƯU (không phải trạng thái đang sửa): chỉ coi là đã xuất bản sau khi lưu thành công.
		var saved = useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostAttribute( 'status' );
		}, [] );
		var entity = useEntityProp( 'postType', postType, 'meta' );
		var meta = entity[ 0 ] || {};
		var setMeta = entity[ 1 ];
		var initial = postId === cfg.postId ? ( cfg.status || [] ) : [];
		var st = useState( initial );
		var status = st[ 0 ];
		var setStatus = st[ 1 ];
		var er = useState( '' );
		var published = saved === 'publish';

		useEffect( function () {
			if ( ! published || ! postId ) {
				return;
			}
			var stop = false;
			var timer = null;
			var tries = 0;
			function poll() {
				tries++;
				fetchStatus( postId ).then( function ( list ) {
					if ( stop ) {
						return;
					}
					setStatus( list );
					er[ 1 ]( '' );
					var busy = ! list.length || list.some( function ( s ) {
						return BUSY.indexOf( s.status ) !== -1;
					} );
					log( 'trạng thái kênh', { postId: postId, tries: tries, busy: busy, list: list.map( function ( s ) {
						return s.channel + ':' + s.status;
					} ) } );
					// Hỏi lại tối đa ~5 phút khi AI còn đang viết.
					if ( busy && tries < 30 ) {
						timer = window.setTimeout( poll, 10000 );
					}
				} ).catch( function ( e ) {
					log( 'lỗi hỏi trạng thái', e && e.message );
					er[ 1 ]( 'Không đọc được trạng thái: ' + ( e && e.message ) );
				} );
			}
			var needPoll = ! initial.length || initial.some( function ( s ) {
				return BUSY.indexOf( s.status ) !== -1;
			} );
			if ( needPoll ) {
				// Vừa bấm Đăng: chờ 3 giây cho hook xuất bản tạo lượt rồi mới hỏi.
				timer = window.setTimeout( poll, initial.length ? 10000 : 3000 );
			}
			return function () {
				stop = true;
				window.clearTimeout( timer );
			};
		}, [ published, postId ] );

		var hubLink = cfg.hubUrl && postId
			? el( Button, { variant: 'secondary', href: cfg.hubUrl + '&post_id=' + postId, style: { marginTop: '4px' } }, 'Mở trong Trung tâm SocialSync →' )
			: null;

		if ( published ) {
			return el(
				Panel,
				{ name: 'socialsync-schedule', title: 'Mạng xã hội (SocialSync)' },
				el( StatusList, { status: status } ),
				er[ 0 ] ? el( 'p', { style: { color: '#b32d2e' } }, er[ 0 ] ) : null,
				hubLink,
				el( 'p', { className: 'description', style: { marginTop: '8px' } }, cfg.approval
					? 'Duyệt, sửa nội dung và đổi giờ đăng trong Trung tâm SocialSync.'
					: 'Chế độ Tự đăng: nội dung lên kênh theo giờ đã xếp. Theo dõi trong Trung tâm SocialSync.' )
			);
		}

		var fields = Object.keys( cfg.channels ).map( function ( channel ) {
			var key = cfg.prefix + channel;
			if ( ! ( key in meta ) ) {
				return null;
			}
			var off = cfg.enabled.indexOf( channel ) === -1;
			return el( TextControl, {
				key: key,
				type: 'datetime-local',
				label: 'Giờ đăng ' + cfg.channels[ channel ] + ( off ? ' (kênh đang tắt)' : '' ),
				value: meta[ key ] || '',
				onChange: function ( value ) {
					var next = {};
					next[ key ] = value || '';
					log( 'chọn giờ đăng', key, value || '(tự xếp)' );
					setMeta( Object.assign( {}, meta, next ) );
				},
			} );
		} );
		return el(
			Panel,
			{ name: 'socialsync-schedule', title: 'Mạng xã hội (SocialSync)' },
			el( 'p', { style: { marginTop: 0 } }, 'Để trống: hệ thống tự chọn giờ trong khung ' + cfg.window + ', rải đều để không dồn bài.' ),
			fields,
			el( 'p', { className: 'description' }, cfg.approval
				? 'Chế độ Duyệt bắt buộc: sau khi bấm Đăng, AI viết nội dung (10–30 giây) rồi chờ duyệt. Trạng thái sẽ hiện ngay tại đây.'
				: 'Chế độ Tự đăng: sau khi bấm Đăng, AI viết nội dung rồi tự đăng theo giờ.' )
		);
	}

	wp.plugins.registerPlugin( 'socialsync-schedule', { render: SchedulePanel, icon: 'share' } );
	log( 'đăng ký panel', { postId: cfg.postId, initialStatus: ( cfg.status || [] ).length, approval: cfg.approval } );
} )( window.wp, window.SocialSyncSchedule );
