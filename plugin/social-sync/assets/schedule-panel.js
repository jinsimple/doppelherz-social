/**
 * Bảng "Lịch đăng mạng xã hội" trong trình soạn khối.
 * Ghi vào post meta _socialsync_at_{kênh} (giờ website, dạng 2026-11-20T08:30); để trống = hệ thống tự xếp lịch.
 */
( function ( wp, cfg ) {
	if ( ! wp || ! wp.plugins || ! cfg ) {
		return;
	}
	var el = wp.element.createElement;
	var Panel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || ( wp.editPost && wp.editPost.PluginDocumentSettingPanel );
	if ( ! Panel ) {
		return;
	}
	var useSelect = wp.data.useSelect;
	var useEntityProp = wp.coreData.useEntityProp;
	var TextControl = wp.components.TextControl;

	function SchedulePanel() {
		var postType = useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostType();
		}, [] );
		var status = useSelect( function ( select ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'status' );
		}, [] );
		var entity = useEntityProp( 'postType', postType, 'meta' );
		var meta = entity[ 0 ] || {};
		var setMeta = entity[ 1 ];
		var fields = Object.keys( cfg.channels ).map( function ( channel ) {
			var key = cfg.prefix + channel;
			if ( ! ( key in meta ) ) {
				return null;
			}
			var off = cfg.enabled.indexOf( channel ) === -1;
			return el( TextControl, {
				key: key,
				type: 'datetime-local',
				label: cfg.channels[ channel ] + ( off ? ' (kênh đang tắt)' : '' ),
				value: meta[ key ] || '',
				onChange: function ( value ) {
					var next = {};
					next[ key ] = value || '';
					setMeta( Object.assign( {}, meta, next ) );
				},
			} );
		} );
		return el(
			Panel,
			{ name: 'socialsync-schedule', title: 'Lịch đăng mạng xã hội' },
			el( 'p', { style: { marginTop: 0 } }, status === 'publish'
				? 'Bài đã xuất bản: đổi giờ đăng trong SocialSync → Chờ duyệt.'
				: 'Để trống: hệ thống tự chọn giờ trong khung ' + cfg.window + ', rải đều để không dồn bài.' ),
			fields,
			cfg.approval ? el( 'p', { className: 'description' }, 'Nội dung AI viết sẽ chờ duyệt trước khi đăng.' ) : null
		);
	}

	wp.plugins.registerPlugin( 'socialsync-schedule', { render: SchedulePanel, icon: 'share' } );
} )( window.wp, window.SocialSyncSchedule );
