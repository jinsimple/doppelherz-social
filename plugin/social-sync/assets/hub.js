/**
 * Trung tâm SocialSync: hành vi nhỏ phía trình duyệt (JS thuần, không cần bước build).
 * - Tự tải lại trang khi có lượt đang xử lý, NHƯNG không tải lại nếu người duyệt đang sửa form.
 * - Đếm ký tự, tô cụm từ quảng cáo sức khỏe rủi ro ngay khi gõ, bật nút Từ chối khi đã có lý do.
 * Mọi bước ghi console.debug với tiền tố [SocialSync hub] để lần lỗi.
 */
( function ( cfg ) {
	'use strict';
	var TAG = '[SocialSync hub]';
	function log() {
		try {
			console.debug.apply( console, [ TAG ].concat( [].slice.call( arguments ) ) );
		} catch ( e ) {}
	}
	cfg = cfg || {};
	var root = document.querySelector( '.ss-hub' );
	if ( ! root ) {
		log( 'không thấy .ss-hub, bỏ qua' );
		return;
	}
	var form = root.querySelector( 'form[data-ss-form="review"]' );
	var dirty = false;
	var submitting = false;
	var banned = null;
	try {
		banned = cfg.banned ? new RegExp( cfg.banned, 'giu' ) : null;
	} catch ( e ) {
		log( 'không dựng được regex từ cấm', e && e.message );
	}
	log( 'khởi động', { busy: root.getAttribute( 'data-ss-busy' ), hasReviewForm: !! form, bannedRegex: !! banned } );

	// ---- Đếm ký tự
	[].forEach.call( root.querySelectorAll( '[data-ss-max]' ), function ( field ) {
		var max = parseInt( field.getAttribute( 'data-ss-max' ), 10 );
		var out = document.createElement( 'span' );
		out.className = 'ss-counter';
		field.insertAdjacentElement( 'afterend', out );
		function update() {
			var n = Array.from( field.value || '' ).length;
			out.textContent = n + ' / ' + max + ' ký tự';
			out.classList.toggle( 'is-over', n > max );
		}
		field.addEventListener( 'input', update );
		update();
	} );

	// ---- Tô cụm từ rủi ro theo từng thẻ kênh
	function scanCard( card ) {
		if ( ! banned ) {
			return;
		}
		var box = card.querySelector( '[data-ss-banned]' );
		if ( ! box ) {
			return;
		}
		var text = [].map.call( card.querySelectorAll( '[data-ss-scan]' ), function ( f ) {
			return f.value || '';
		} ).join( ' ' );
		if ( cfg.disclaimer ) {
			text = text.split( cfg.disclaimer ).join( ' ' );
		}
		var hits = {};
		( text.match( banned ) || [] ).forEach( function ( h ) {
			hits[ h.toLowerCase() ] = h;
		} );
		var list = Object.keys( hits ).map( function ( k ) {
			return hits[ k ];
		} );
		box.hidden = ! list.length;
		var span = box.querySelector( 'span' );
		if ( span ) {
			span.textContent = list.join( ', ' );
		}
		return list;
	}
	[].forEach.call( root.querySelectorAll( '[data-ss-card]' ), function ( card ) {
		card.addEventListener( 'input', function () {
			var list = scanCard( card );
			if ( list && list.length ) {
				log( 'cụm từ rủi ro khi gõ', list );
			}
		} );
		scanCard( card );
		var toggle = card.querySelector( '[data-ss-enable]' );
		if ( toggle ) {
			toggle.addEventListener( 'change', function () {
				card.classList.toggle( 'is-off', ! toggle.checked );
				log( 'bật/tắt kênh', toggle.name, toggle.checked );
			} );
		}
	} );

	// ---- Từ chối: lý do chọn nhanh + chỉ bật nút khi có lý do (máy chủ vẫn kiểm tra lại)
	var note = root.querySelector( '#ss-note' );
	var reject = root.querySelector( '[data-ss-reject]' );
	function syncReject() {
		if ( reject && note ) {
			reject.disabled = ! note.value.trim();
		}
	}
	[].forEach.call( root.querySelectorAll( '[data-ss-reason]' ), function ( btn ) {
		btn.addEventListener( 'click', function () {
			if ( note ) {
				note.value = btn.getAttribute( 'data-ss-reason' );
				note.focus();
				syncReject();
				log( 'chọn lý do nhanh', note.value );
			}
		} );
	} );
	if ( note ) {
		note.addEventListener( 'input', syncReject );
	}
	syncReject();

	// ---- Theo dõi form đang sửa: không tự tải lại, cảnh báo khi rời trang
	if ( form ) {
		form.addEventListener( 'input', function () {
			if ( ! dirty ) {
				log( 'form duyệt đã được sửa: tạm dừng tự cập nhật' );
			}
			dirty = true;
		} );
		form.addEventListener( 'submit', function ( ev ) {
			var btn = ev.submitter;
			var decision = btn ? btn.value : '';
			var problems = decision === 'reject' ? [] : validate( decision );
			if ( problems.length ) {
				// Chặn gửi để không mất phần đã sửa; máy chủ vẫn kiểm tra lại lần nữa.
				ev.preventDefault();
				showProblems( problems );
				log( 'chặn gửi form: chưa hợp lệ', problems.map( function ( p ) {
					return p.msg;
				} ) );
				return;
			}
			submitting = true;
			log( 'gửi form duyệt', decision || '(không rõ nút)' );
		} );
	}

	/** Kiểm tra giống máy chủ (Review::handle) để báo ngay tại ô, trước khi gửi. */
	function validate( decision ) {
		var problems = [];
		var enabled = 0;
		[].forEach.call( form.querySelectorAll( '[data-ss-card]' ), function ( card ) {
			var on = card.querySelector( '[data-ss-enable]' );
			if ( ! on || ! on.checked ) {
				return;
			}
			enabled++;
			var label = ( card.querySelector( '.ss-toggle strong' ) || {} ).textContent || '';
			function len( sel ) {
				var f = card.querySelector( sel );
				return f ? Array.from( f.value.trim() ).length : 0;
			}
			function add( sel, msg ) {
				problems.push( { field: sel ? card.querySelector( sel ) : on, msg: label + ': ' + msg } );
			}
			if ( card.getAttribute( 'data-ss-channel' ) === 'facebook' ) {
				if ( ! len( 'textarea[name$="[message]"]' ) ) {
					add( 'textarea[name$="[message]"]', 'nội dung đang trống.' );
				} else if ( len( 'textarea[name$="[message]"]' ) > 5000 ) {
					add( 'textarea[name$="[message]"]', 'nội dung dài quá 5.000 ký tự.' );
				}
			} else {
				if ( card.getAttribute( 'data-ss-noimage' ) === '1' ) {
					add( null, 'bài không có ảnh đại diện nên không đăng được Pin. Bỏ chọn Pinterest hoặc thêm ảnh cho bài.' );
				}
				var t = len( 'input[name$="[title]"]' );
				if ( ! t || t > 100 ) {
					add( 'input[name$="[title]"]', 'tiêu đề Pin cần 1–100 ký tự.' );
				}
				if ( len( 'textarea[name$="[description]"]' ) > 500 ) {
					add( 'textarea[name$="[description]"]', 'mô tả Pin tối đa 500 ký tự.' );
				}
				if ( len( 'input[name$="[alt_text]"]' ) > 500 ) {
					add( 'input[name$="[alt_text]"]', 'alt text tối đa 500 ký tự.' );
				}
			}
			if ( decision === 'approve' && ! len( 'input[name$="[at]"]' ) ) {
				add( 'input[name$="[at]"]', 'chưa chọn giờ đăng (hoặc bấm “Duyệt và đăng ngay”).' );
			}
		} );
		if ( ! enabled ) {
			problems.push( { field: null, msg: 'Chưa chọn kênh nào để đăng. Muốn bỏ cả bài thì dùng Từ chối.' } );
		}
		return problems;
	}

	function showProblems( problems ) {
		var box = root.querySelector( '#ss-client-errors' );
		if ( ! box ) {
			box = document.createElement( 'div' );
			box.id = 'ss-client-errors';
			box.className = 'notice notice-error';
			box.setAttribute( 'role', 'alert' );
			form.insertAdjacentElement( 'afterbegin', box );
		}
		box.innerHTML = '';
		var ul = document.createElement( 'ul' );
		problems.forEach( function ( p ) {
			var li = document.createElement( 'li' );
			li.textContent = p.msg;
			ul.appendChild( li );
		} );
		var head = document.createElement( 'p' );
		head.textContent = 'Chưa gửi được, cần sửa:';
		box.appendChild( head );
		box.appendChild( ul );
		box.scrollIntoView( { block: 'center' } );
		var first = problems.filter( function ( p ) {
			return p.field;
		} )[ 0 ];
		if ( first ) {
			first.field.focus();
		}
	}

	// Form đổi giờ / hủy lịch: đang gõ thì cũng không tự tải lại.
	[].forEach.call( root.querySelectorAll( 'form.ss-reschedule' ), function ( f ) {
		f.addEventListener( 'input', function () {
			dirty = true;
			log( 'đang sửa giờ của lượt đã duyệt: tạm dừng tự cập nhật' );
		} );
		f.addEventListener( 'submit', function ( ev ) {
			submitting = true;
			log( 'gửi đổi lịch', ev.submitter ? ev.submitter.value : '' );
		} );
	} );
	window.addEventListener( 'beforeunload', function ( ev ) {
		if ( dirty && ! submitting ) {
			ev.preventDefault();
			ev.returnValue = '';
		}
	} );

	// ---- Tự cập nhật
	var auto = root.querySelector( '#ss-auto' );
	var KEY = 'socialsync-hub-auto';
	try {
		if ( auto && window.localStorage.getItem( KEY ) === '0' ) {
			auto.checked = false;
		}
	} catch ( e ) {}
	if ( auto ) {
		auto.addEventListener( 'change', function () {
			try {
				window.localStorage.setItem( KEY, auto.checked ? '1' : '0' );
			} catch ( e ) {}
			log( 'tự cập nhật', auto.checked );
		} );
	}
	var busy = root.getAttribute( 'data-ss-busy' ) === '1';
	var sec = parseInt( cfg.refreshSec, 10 ) || 15;
	if ( busy ) {
		log( 'có lượt đang xử lý: hẹn tải lại sau', sec, 'giây' );
		window.setTimeout( function tick() {
			if ( auto && ! auto.checked ) {
				log( 'tự cập nhật đang tắt' );
				return;
			}
			if ( dirty || submitting || document.hidden ) {
				log( 'hoãn tải lại', { dirty: dirty, submitting: submitting, hidden: document.hidden } );
				window.setTimeout( tick, sec * 1000 );
				return;
			}
			log( 'tải lại trang để lấy trạng thái mới' );
			window.location.reload();
		}, sec * 1000 );
	}
} )( window.SocialSyncHub );
