/**
 * Dashboard Widget Manager - Widget Refresh
 *
 * Handles auto-refresh polling and manual refresh for dashboard widgets.
 * Uses a controlled, event-driven reinitialization system — no dynamic
 * <script> tag execution or eval().
 *
 * Events dispatched on `document` after widget DOM changes:
 *   - `dwm:widget:mounted`   — fired once per widget on initial page load.
 *   - `dwm:widget:refreshed` — fired after each widget HTML replacement.
 *   Both carry `detail: { widgetId, element }`.
 *
 * Optional global callback (no eval):
 *   window.DWM.onWidgetRefreshed({ widgetId, element })
 *   Called after reinit events fire; safe for external custom widget scripts.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

const MIN_POLL_INTERVAL = 30; // seconds
const pollingTimers = new Map();

/**
 * Initialize widget refresh behavior on the WP admin dashboard.
 */
export function init() {
	$( '.dwm-widget-content' ).each( function() {
		const $widget     = $( this );
		const widgetId    = $widget.data( 'widget-id' );
		const autoRefresh = $widget.data( 'auto-refresh' ) == 1;

		if ( ! widgetId ) return;

		// Dispatch mounted event for initial page load.
		dispatchWidgetEvent( 'dwm:widget:mounted', widgetId, this );

		if ( autoRefresh ) {
			const cacheDuration = parseInt( $widget.data( 'cache-duration' ), 10 ) || 300;
			const interval      = Math.max( cacheDuration, MIN_POLL_INTERVAL ) * 1000;
			startPolling( $widget, widgetId, interval );
		} else {
			const widgetKey = String( widgetId );
			if ( pollingTimers.has( widgetKey ) ) {
				clearInterval( pollingTimers.get( widgetKey ) );
				pollingTimers.delete( widgetKey );
			}
		}
	} );

	// Manual refresh button.
	$( document ).on( 'click', '.dwm-manual-refresh-btn', function() {
		const $btn    = $( this );
		const $widget = $btn.closest( '.dwm-widget-content' );
		const widgetId = $btn.data( 'widget-id' );

		if ( ! widgetId || $btn.hasClass( 'dwm-refreshing' ) ) return;

		$btn.addClass( 'dwm-refreshing' );
		refreshWidget( $widget, widgetId, function() {
			$btn.removeClass( 'dwm-refreshing' );
		} );
	} );
}

/**
 * Start polling a widget for data changes.
 */
function startPolling( $widget, widgetId, interval ) {
	const widgetKey = String( widgetId );
	if ( pollingTimers.has( widgetKey ) ) {
		clearInterval( pollingTimers.get( widgetKey ) );
	}

	const timerId = setInterval( function() {
		const currentHash = $widget.data( 'result-hash' ) || '';
		pollWidget( $widget, widgetId, currentHash );
	}, interval );

	pollingTimers.set( widgetKey, timerId );
}

/**
 * Poll for data changes; replace widget HTML if changed.
 */
function pollWidget( $widget, widgetId, currentHash ) {
	if ( typeof dwmAdminVars === 'undefined' ) return;

	$.ajax( {
		url:    dwmAdminVars.ajaxUrl,
		type:   'POST',
		data:   {
			action:    'dwm_refresh_widget',
			nonce:     dwmAdminVars.nonce,
			widget_id: widgetId,
			hash:      currentHash,
		},
		success: function( response ) {
			if ( response && response.success && response.data && response.data.changed ) {
				replaceWidgetContent( $widget, response.data.html, response.data.hash );
			}
		}
	} );
}

/**
 * Manually refresh a widget regardless of hash.
 */
function refreshWidget( $widget, widgetId, callback ) {
	if ( typeof dwmAdminVars === 'undefined' ) {
		if ( callback ) callback();
		return;
	}

	$.ajax( {
		url:    dwmAdminVars.ajaxUrl,
		type:   'POST',
		data:   {
			action:    'dwm_refresh_widget',
			nonce:     dwmAdminVars.nonce,
			widget_id: widgetId,
			hash:      '',  // empty hash forces a changed response
		},
		success: function( response ) {
			if ( response && response.success && response.data && response.data.html ) {
				replaceWidgetContent( $widget, response.data.html, response.data.hash );
			}
		},
		complete: function() {
			if ( callback ) callback();
		}
	} );
}

/**
 * Replace widget container content and re-attach polling state.
 * No <script> tag text is executed. Reinitialization goes through the
 * DWM initializer registry and the dwm:widget:refreshed event.
 */
function replaceWidgetContent( $widget, html, newHash ) {
	const autoRefresh   = $widget.data( 'auto-refresh' );
	const cacheDuration = $widget.data( 'cache-duration' );

	const $newWidget = $( html );
	$widget.replaceWith( $newWidget );

	const newElement = $newWidget[ 0 ];
	const widgetId   = $newWidget.data( 'widget-id' );

	// Run registered initializers on the fresh DOM element.
	if ( window.DWM && typeof window.DWM.runInitializers === 'function' ) {
		window.DWM.runInitializers( newElement, widgetId, true );
	}

	// Dispatch the refreshed event for any listeners.
	dispatchWidgetEvent( 'dwm:widget:refreshed', widgetId, newElement );

	// Call optional global callback (no eval, no script text).
	if ( window.DWM && typeof window.DWM.onWidgetRefreshed === 'function' ) {
		try {
			window.DWM.onWidgetRefreshed( { widgetId: widgetId, element: newElement } );
		} catch ( err ) {
			if ( window.DWM_DEBUG ) {
				console.warn( '[DWM] onWidgetRefreshed callback threw:', err.message );
			}
		}
	}

	// If auto-refresh, continue polling the new element.
	if ( autoRefresh == 1 ) {
		const interval = Math.max( parseInt( cacheDuration, 10 ) || 300, MIN_POLL_INTERVAL ) * 1000;
		startPolling( $newWidget, widgetId, interval );
	} else if ( widgetId ) {
		const widgetKey = String( widgetId );
		if ( pollingTimers.has( widgetKey ) ) {
			clearInterval( pollingTimers.get( widgetKey ) );
			pollingTimers.delete( widgetKey );
		}
	}
}

/**
 * Dispatch a DWM widget lifecycle event on the document.
 *
 * @param {string}  eventName  The event name (e.g. 'dwm:widget:refreshed').
 * @param {string}  widgetId   The widget ID.
 * @param {Element} element    The widget DOM element.
 */
function dispatchWidgetEvent( eventName, widgetId, element ) {
	try {
		document.dispatchEvent( new CustomEvent( eventName, {
			detail: { widgetId: widgetId, element: element },
			bubbles: false,
		} ) );
	} catch ( err ) {
		if ( window.DWM_DEBUG ) {
			console.warn( '[DWM] Failed to dispatch ' + eventName + ':', err.message );
		}
	}
}
