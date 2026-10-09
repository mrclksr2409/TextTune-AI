/**
 * WP-Backend UI — shared admin behaviours.
 *
 * Everything is opt-in via data attributes, so plugins need no JS of their own
 * for the common cases:
 *
 *   data-wpb-tabs                 on a .nav-tab-wrapper → client-side tab panels
 *   data-wpb-modal-open="id"      opens <dialog id="id" class="wpb-modal">
 *   data-wpb-modal-close          closes the surrounding dialog
 *   data-wpb-reveal="input-id"    toggles a password field between hidden/visible
 *   data-wpb-copy="text"          copies the given text
 *   data-wpb-copy-target="id"     copies the value/text of an element
 *   data-wpb-confirm="message"    asks for confirmation on click/submit
 *   data-wpb-dismiss              removes the closest .wpb-alert
 *
 * Public API: window.wpbAdminUI.{ toast, openModal, closeModal, setLoading }.
 *
 * @package WP_Backend_UI
 */
( function () {
	'use strict';

	var i18n = Object.assign(
		{
			copied: 'Copied to clipboard.',
			copyFailed: 'Copy failed.',
			show: 'Show',
			hide: 'Hide',
			close: 'Close',
		},
		window.wpbAdminUIL10n || {}
	);

	/* ------------------------------------------------------------------
	 * Toasts
	 * ------------------------------------------------------------------ */

	function getToastRegion() {
		var region = document.querySelector( '.wpb-toasts' );
		if ( ! region ) {
			region = document.createElement( 'div' );
			region.className = 'wpb-toasts';
			region.setAttribute( 'role', 'status' );
			region.setAttribute( 'aria-live', 'polite' );
			document.body.appendChild( region );
		}
		return region;
	}

	/**
	 * Show a short, auto-dismissing message.
	 *
	 * @param {string} message Plain text.
	 * @param {string} [type]  success|warning|error|info.
	 * @param {number} [ms]    Visible duration, 0 keeps it until clicked.
	 */
	function toast( message, type, ms ) {
		var el = document.createElement( 'div' );
		el.className = 'wpb-toast wpb-toast--' + ( type || 'info' );
		el.textContent = message;
		getToastRegion().appendChild( el );

		function remove() {
			el.classList.add( 'is-leaving' );
			setTimeout( function () {
				el.remove();
			}, 180 );
		}

		el.addEventListener( 'click', remove );
		if ( 0 !== ms ) {
			setTimeout( remove, ms || 4000 );
		}
		return el;
	}

	/* ------------------------------------------------------------------
	 * Modal
	 * ------------------------------------------------------------------ */

	function openModal( id ) {
		var dialog = document.getElementById( id );
		if ( dialog && 'function' === typeof dialog.showModal && ! dialog.open ) {
			dialog.showModal();
		}
		return dialog;
	}

	function closeModal( idOrEl ) {
		var dialog = 'string' === typeof idOrEl ? document.getElementById( idOrEl ) : idOrEl;
		if ( dialog && dialog.open ) {
			dialog.close();
		}
	}

	/* ------------------------------------------------------------------
	 * Loading state for buttons/containers
	 * ------------------------------------------------------------------ */

	function setLoading( el, isLoading ) {
		if ( ! el ) {
			return;
		}
		el.classList.toggle( 'is-loading', !! isLoading );
		el.setAttribute( 'aria-busy', isLoading ? 'true' : 'false' );
		if ( 'BUTTON' === el.tagName || 'INPUT' === el.tagName ) {
			el.disabled = !! isLoading;
		}
	}

	/* ------------------------------------------------------------------
	 * Client-side tabs
	 * ------------------------------------------------------------------ */

	function initTabs( nav ) {
		var tabs = Array.prototype.slice.call( nav.querySelectorAll( '.nav-tab[href^="#"]' ) );
		if ( ! tabs.length ) {
			return;
		}

		function activate( tab, updateHash ) {
			tabs.forEach( function ( t ) {
				var panel = document.getElementById( t.getAttribute( 'href' ).slice( 1 ) );
				var active = t === tab;
				t.classList.toggle( 'nav-tab-active', active );
				t.setAttribute( 'aria-selected', active ? 'true' : 'false' );
				if ( panel ) {
					panel.hidden = ! active;
				}
			} );
			if ( updateHash && window.history && window.history.replaceState ) {
				window.history.replaceState( null, '', tab.getAttribute( 'href' ) );
			}
		}

		nav.setAttribute( 'role', 'tablist' );
		tabs.forEach( function ( tab ) {
			tab.setAttribute( 'role', 'tab' );
			tab.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				activate( tab, true );
			} );
		} );

		var fromHash = tabs.filter( function ( t ) {
			return t.getAttribute( 'href' ) === window.location.hash;
		} )[ 0 ];
		var preset = nav.querySelector( '.nav-tab-active[href^="#"]' );
		activate( fromHash || preset || tabs[ 0 ], false );
	}

	/* ------------------------------------------------------------------
	 * Clipboard
	 * ------------------------------------------------------------------ */

	function copyText( text ) {
		if ( navigator.clipboard && window.isSecureContext ) {
			return navigator.clipboard.writeText( text );
		}
		return new Promise( function ( resolve, reject ) {
			var area = document.createElement( 'textarea' );
			area.value = text;
			area.setAttribute( 'readonly', '' );
			area.style.position = 'fixed';
			area.style.opacity = '0';
			document.body.appendChild( area );
			area.select();
			var ok = document.execCommand( 'copy' );
			area.remove();
			( ok ? resolve : reject )();
		} );
	}

	/* ------------------------------------------------------------------
	 * Delegated handlers
	 * ------------------------------------------------------------------ */

	document.addEventListener( 'click', function ( event ) {
		var target = event.target;
		if ( ! ( target instanceof Element ) ) {
			return;
		}

		var confirmEl = target.closest( '[data-wpb-confirm]' );
		if ( confirmEl && 'FORM' !== confirmEl.tagName ) {
			// eslint-disable-next-line no-alert
			if ( ! window.confirm( confirmEl.getAttribute( 'data-wpb-confirm' ) ) ) {
				event.preventDefault();
				event.stopImmediatePropagation();
				return;
			}
		}

		var opener = target.closest( '[data-wpb-modal-open]' );
		if ( opener ) {
			event.preventDefault();
			openModal( opener.getAttribute( 'data-wpb-modal-open' ) );
			return;
		}

		var closer = target.closest( '[data-wpb-modal-close]' );
		if ( closer ) {
			event.preventDefault();
			closeModal( closer.closest( 'dialog' ) );
			return;
		}

		// Click on the backdrop (the dialog element itself) closes it.
		if ( 'DIALOG' === target.tagName && target.classList.contains( 'wpb-modal' ) ) {
			var rect = target.getBoundingClientRect();
			var inside = event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom;
			if ( ! inside ) {
				closeModal( target );
			}
			return;
		}

		var reveal = target.closest( '[data-wpb-reveal]' );
		if ( reveal ) {
			event.preventDefault();
			var input = document.getElementById( reveal.getAttribute( 'data-wpb-reveal' ) );
			if ( input ) {
				var hidden = 'password' === input.type;
				input.type = hidden ? 'text' : 'password';
				reveal.textContent = hidden ? i18n.hide : i18n.show;
				reveal.setAttribute( 'aria-pressed', hidden ? 'true' : 'false' );
			}
			return;
		}

		var copier = target.closest( '[data-wpb-copy], [data-wpb-copy-target]' );
		if ( copier ) {
			event.preventDefault();
			var text = copier.getAttribute( 'data-wpb-copy' );
			if ( null === text ) {
				var source = document.getElementById( copier.getAttribute( 'data-wpb-copy-target' ) );
				text = source ? ( 'value' in source ? source.value : source.textContent ) : '';
			}
			copyText( text ).then(
				function () {
					toast( i18n.copied, 'success', 2000 );
				},
				function () {
					toast( i18n.copyFailed, 'error' );
				}
			);
			return;
		}

		var dismiss = target.closest( '[data-wpb-dismiss]' );
		if ( dismiss ) {
			var alert = dismiss.closest( '.wpb-alert' );
			if ( alert ) {
				alert.remove();
			}
		}
	} );

	document.addEventListener(
		'submit',
		function ( event ) {
			var form = event.target;
			if ( form instanceof HTMLFormElement && form.hasAttribute( 'data-wpb-confirm' ) ) {
				// eslint-disable-next-line no-alert
				if ( ! window.confirm( form.getAttribute( 'data-wpb-confirm' ) ) ) {
					event.preventDefault();
				}
			}
		},
		true
	);

	function init() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-wpb-tabs]' ), initTabs );

		Array.prototype.forEach.call( document.querySelectorAll( '.wpb-modal__close:not([aria-label])' ), function ( btn ) {
			btn.setAttribute( 'aria-label', i18n.close );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	window.wpbAdminUI = {
		toast: toast,
		openModal: openModal,
		closeModal: closeModal,
		setLoading: setLoading,
		initTabs: initTabs,
	};
} )();
