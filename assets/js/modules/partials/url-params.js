/**
 * Dashboard Widget Manager - URL Params Handler
 *
 * Reads ?modal=, ?tab=, and ?widget= URL params on page load to auto-open
 * modals to specific tabs/pages/widgets for shareable links. Also writes
 * params to the address bar whenever a modal opens, navigates, or closes.
 *
 * Features modal:       ?modal=features[&tab={category-slug}]
 * Docs modal:           ?modal=docs[&tab={docs-page-id}]
 * Widget editor modal:  ?modal=widget-editor[&widget={widget-id}]
 * Any other modal:      ?modal={modal-id}  (full element ID)
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

(function($) {
	'use strict';

	// Friendly slug → modal element ID
	var MODAL_MAP = {
		'features':      'dwm-features-modal',
		'docs':          'dwm-docs-modal',
		'support':       'dwm-support-modal',
		'notifications': 'dwm-notifications-panel',
		'filters':       'dwm-filters-modal',
		'widget-editor': 'dwm-widget-editor-modal',
	};

	// All URL params this module manages — always cleared together on close
	var ALL_PARAMS = [ 'modal', 'tab', 'widget' ];
	var isPageUnloading = false;

	// Prevent cleanAllParams from firing during page unload/refresh
	window.addEventListener( 'pagehide',     function() { isPageUnloading = true; } );
	window.addEventListener( 'beforeunload', function() { isPageUnloading = true; } );

	function isTrackedModalId( modalId ) {
		return Object.keys( MODAL_MAP ).some( function( k ) {
			return MODAL_MAP[ k ] === modalId;
		} );
	}

	function getParam( key ) {
		return new URLSearchParams( window.location.search ).get( key );
	}

	function isDwmPageContext() {
		var page = new URLSearchParams( window.location.search ).get( 'page' ) || '';
		var bodyClass = document.body ? ( document.body.className || '' ) : '';

		if ( page === 'dashboard-widget-manager' || page.indexOf( 'dwm-' ) === 0 ) {
			return true;
		}

		return (
			bodyClass.indexOf( 'toplevel_page_dashboard-widget-manager' ) !== -1 ||
			bodyClass.indexOf( 'widget-manager_page_dwm-' ) !== -1 ||
			bodyClass.indexOf( 'index-php' ) !== -1
		);
	}

	function setParams( params ) {
		var url = new URL( window.location.href );
		Object.keys( params ).forEach( function( key ) {
			var val = params[ key ];
			if ( val === null || val === undefined || val === '' ) {
				url.searchParams.delete( key );
			} else {
				url.searchParams.set( key, val );
			}
		} );
		history.replaceState( null, '', url.toString() );
	}

	function cleanAllParams() {
		var obj = {};
		ALL_PARAMS.forEach( function( k ) { obj[ k ] = null; } );
		setParams( obj );
	}

	function openDocsModal( page ) {
		var docsModal = window.DWMDocsModal;
		if ( ! docsModal ) return;

		docsModal.collapseAllAccordions();
		docsModal.showPage( page );

		var $link = $( '#dwm-docs-modal [data-docs-page="' + page + '"]' ).first();
		docsModal.setActiveLink(
			$link.length ? $link : $( '#dwm-docs-modal .dwm-docs-welcome-link' )
		);

		if ( window.dwmModalAPI && typeof window.dwmModalAPI.open === 'function' ) {
			window.dwmModalAPI.open( '#dwm-docs-modal', { inheritState: false } );
		} else {
			$( '#dwm-docs-modal' ).addClass( 'active' );
			$( 'body' ).addClass( 'dwm-modal-open' );
		}
	}

	// ─────────────────────────────────────────────────────────────────────────
	// MutationObserver: write URL params when a modal opens or closes
	//
	// getExtraParams() → returns an object of extra params to set on open,
	//                    e.g. { tab: 'integrations' } or { widget: '42' }
	//                    null/undefined values cause that param to be deleted.
	// ─────────────────────────────────────────────────────────────────────────

	var TRACKED_SELECTORS = '#dwm-features-modal, #dwm-docs-modal, #dwm-widget-editor-modal';

	function watchModal( $el, slug, getExtraParams ) {
		if ( ! $el.length ) return;

		var observer = new MutationObserver( function() {
			if ( ! $el.hasClass( 'active' ) ) return;

			// Build full param set: start with all cleared, then apply extras
			var params = { modal: slug };
			ALL_PARAMS.forEach( function( k ) { if ( k !== 'modal' ) params[ k ] = null; } );

			if ( getExtraParams ) {
				var extra = getExtraParams();
				if ( extra ) {
					Object.keys( extra ).forEach( function( k ) { params[ k ] = extra[ k ]; } );
				}
			}

			setParams( params );
		} );

		observer.observe( $el[ 0 ], { attributes: true, attributeFilter: [ 'class' ] } );
	}

	$( function() {
		if ( ! isDwmPageContext() ) {
			return;
		}

		// ── Read params on load ───────────────────────────────────────────────
		var modal    = getParam( 'modal' );
		var tab      = getParam( 'tab' );
		var widgetId = getParam( 'widget' );

		if ( modal ) {
			var modalId = MODAL_MAP[ modal ] || modal;

			if ( modal === 'features' ) {
				setTimeout( function() {
					if ( window.dwmModalAPI && typeof window.dwmModalAPI.open === 'function' ) {
						window.dwmModalAPI.open( '#' + modalId, { inheritState: false } );
					} else {
						$( '#' + modalId ).addClass( 'active' );
						$( 'body' ).addClass( 'dwm-modal-open' );
						$( document ).trigger( 'dwmModalOpened', [ $( '#' + modalId ), modalId ] );
					}
					if ( tab ) {
						var $btn = $( '[data-dwm-features-page="' + tab + '"]' ).first();
						if ( $btn.length ) $btn.trigger( 'click' );
					}
				}, 100 );

			} else if ( modal === 'docs' ) {
				var page = tab || 'welcome';
				setTimeout( function() {
					if ( window.DWMDocsModal ) {
						openDocsModal( page );
					} else {
						$( document ).on( 'dwm-docs-modal-ready', function() {
							openDocsModal( page );
						} );
					}
				}, 100 );

			} else if ( modal === 'widget-editor' ) {
				setTimeout( function() {
					if ( widgetId ) {
						// Edit existing widget — click its edit button
						var $btn = $( '.dwm-edit-widget[data-widget-id="' + widgetId + '"]' );
						if ( $btn.length ) $btn.trigger( 'click' );
					} else {
						// New widget — click the create button
						var $create = $( '.dwm-create-widget' );
						if ( $create.length ) $create.trigger( 'click' );
					}
				}, 300 );

			} else {
				// Generic: open any modal by ID
				setTimeout( function() {
					if ( window.dwmModalAPI && typeof window.dwmModalAPI.open === 'function' ) {
						window.dwmModalAPI.open( '#' + modalId, { inheritState: false } );
					} else if ( typeof window.openModal === 'function' ) {
						window.openModal( modalId );
					} else {
						$( '#' + modalId ).addClass( 'active' );
						$( 'body' ).addClass( 'dwm-modal-open' );
					}
				}, 100 );
			}
		}

		// ── Watch features modal ─────────────────────────────────────────────
		watchModal( $( '#dwm-features-modal' ), 'features', function() {
			var $active = $( '#dwm-features-modal [data-dwm-features-page].is-active' ).first();
			var activeTab = $active.length ? $active.data( 'dwm-features-page' ) : null;
			return { tab: activeTab };
		} );

		// ── Watch docs modal ─────────────────────────────────────────────────
		watchModal( $( '#dwm-docs-modal' ), 'docs', function() {
			var page = window.DWMDocsModal ? window.DWMDocsModal.currentPage : null;
			return { tab: ( page && page !== 'welcome' ) ? page : null };
		} );

		// ── Watch widget editor modal ────────────────────────────────────────
		watchModal( $( '#dwm-widget-editor-modal' ), 'widget-editor', function() {
			var id = $( '#dwm-widget-id' ).val();
			return { widget: id || null };
		} );

		// ── Update tab param when features modal page changes ─────────────────
		$( document ).on( 'click', '#dwm-features-modal [data-dwm-features-page]', function() {
			setParams( { modal: 'features', tab: $( this ).data( 'dwm-features-page' ), widget: null } );
		} );

		// ── Update tab param when docs modal page changes ─────────────────────
		$( document ).on( 'click', '#dwm-docs-modal [data-docs-page]', function() {
			var page = $( this ).data( 'docs-page' );
			if ( page ) {
				setParams( { modal: 'docs', tab: page !== 'welcome' ? page : null, widget: null } );
			}
		} );

	// ── Update tab param when docs modal accordion expands (auto-nav) ─────
		$( document ).on( 'click', '#dwm-docs-modal .dwm-docs-accordion-trigger', function() {
			setTimeout( function() {
				var page = window.DWMDocsModal ? window.DWMDocsModal.currentPage : null;
				if ( page ) {
					setParams( { modal: 'docs', tab: page !== 'welcome' ? page : null, widget: null } );
				}
			}, 0 );
		} );

		// ── Clear params only on explicit user-close actions ──────────────────
		// X button or overlay click — only for tracked modals
		$( document ).on( 'click', '.dwm-modal-close, .dwm-modal-overlay', function() {
			if ( isPageUnloading ) return;
			var modalId = $( this ).closest( '.dwm-modal' ).attr( 'id' );
			if ( isTrackedModalId( modalId ) ) {
				cleanAllParams();
			}
		} );

		// ESC key — only when the topmost active modal is tracked
		$( document ).on( 'keydown', function( e ) {
			if ( isPageUnloading || e.key !== 'Escape' ) return;
			var $top = $( '.dwm-modal.active' ).last();
			if ( $top.length && isTrackedModalId( $top.attr( 'id' ) ) ) {
				cleanAllParams();
			}
		} );

	} );

} )( jQuery );
