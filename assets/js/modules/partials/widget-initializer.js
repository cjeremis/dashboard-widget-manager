/**
 * Dashboard Widget Manager - Widget Initializer Registry
 *
 * Provides a controlled, event-driven reinitialization system for DWM
 * dashboard widgets. Replaces dynamic <script> tag execution with named,
 * idempotent initializers that run on mount and after each refresh.
 *
 * Events dispatched on `document`:
 *   - `dwm:widget:mounted`   — fires once on initial page load for each widget
 *   - `dwm:widget:refreshed` — fires after each widget HTML replacement
 *     Both carry `detail: { widgetId, element }`.
 *
 * Optional global callback (no eval, no script text):
 *   window.DWM.onWidgetRefreshed({ widgetId, element })
 *   Called after reinit events fire, safe for external custom widget scripts.
 *
 * @package Dashboard_Widget_Manager
 * @since   1.0.0
 */

( function() {
	'use strict';

	// ── Registry storage ─────────────────────────────────────────────

	/** @type {Array<{ name: string, fn: function }>} */
	const _initializers = [];

	/**
	 * Guard set — tracks which widgetId+name pairs have already been initialized
	 * to prevent duplicate binding on idempotent-unsafe initializers.
	 * @type {Set<string>}
	 */
	const _bound = new Set();

	// ── Conditional debug logger ─────────────────────────────────────

	function _debug( message ) {
		if ( window.DWM_DEBUG ) {
			console.warn( '[DWM]', message );
		}
	}

	// ── Public API ───────────────────────────────────────────────────

	const DWM = window.DWM || {};

	/**
	 * Register a named initializer function.
	 * Initializers MUST be idempotent: the registry tracks first-run per
	 * widgetId+name and skips re-runs unless the element is new (post-refresh).
	 *
	 * @param {string}   name  Unique initializer name.
	 * @param {function} fn    Receives (element, widgetId). Must not use eval.
	 */
	DWM.registerInitializer = function( name, fn ) {
		if ( typeof name !== 'string' || ! name ) {
			_debug( 'registerInitializer: invalid name' );
			return;
		}
		if ( typeof fn !== 'function' ) {
			_debug( 'registerInitializer: fn must be a function' );
			return;
		}
		_initializers.push( { name: name, fn: fn } );
	};

	/**
	 * Run all registered initializers against a context element.
	 * Each initializer is wrapped in try/catch. On refresh the guard key
	 * includes a timestamp so re-runs are always allowed on fresh elements.
	 *
	 * @param {Element} element   The widget DOM element.
	 * @param {string}  widgetId  The widget ID from data-widget-id.
	 * @param {boolean} isRefresh True when called after a refresh (clears guard for element).
	 */
	DWM.runInitializers = function( element, widgetId, isRefresh ) {
		_initializers.forEach( function( initializer ) {
			const guardKey = widgetId + ':' + initializer.name;

			// On refresh, always allow re-run (element is a fresh DOM node).
			// On mount, skip if already bound to avoid duplicate setup.
			if ( ! isRefresh && _bound.has( guardKey ) ) {
				return;
			}

			try {
				initializer.fn( element, widgetId );
				_bound.add( guardKey );
			} catch ( err ) {
				_debug( 'Initializer "' + initializer.name + '" failed for widget ' + widgetId + ': ' + err.message );
			}
		} );
	};

	// ── Built-in initializer: chart reinit ───────────────────────────

	/**
	 * Chart reinit initializer.
	 * Reads chart config from data attributes on the widget element — no eval.
	 * The renderer is expected to write `data-chart-config` and `data-chart-type`
	 * attributes onto the `.dwm-widget-content` element when serving chart widgets.
	 */
	DWM.registerInitializer( 'chart', function( element, widgetId ) {
		const chartConfigAttr = element.getAttribute( 'data-chart-config' );
		const chartType       = element.getAttribute( 'data-chart-type' );

		if ( ! chartConfigAttr || ! chartType ) {
			return; // Not a chart widget — skip silently.
		}

		let chartConfig;
		try {
			chartConfig = JSON.parse( chartConfigAttr );
		} catch ( e ) {
			_debug( 'Chart config JSON parse failed for widget ' + widgetId );
			return;
		}

		const canvas = element.querySelector( '.dwm-chart-canvas' );
		if ( ! canvas ) {
			return;
		}

		// Destroy any existing Chart instance on this canvas before reinit.
		if ( canvas._dwmChartInstance ) {
			try {
				canvas._dwmChartInstance.destroy();
			} catch ( e ) {}
			canvas._dwmChartInstance = null;
		}

		if ( typeof window.Chart === 'undefined' ) {
			_debug( 'Chart.js not loaded; skipping chart init for widget ' + widgetId );
			return;
		}

		try {
			canvas._dwmChartInstance = new window.Chart( canvas, {
				type: chartType,
				data: chartConfig.data || {},
				options: chartConfig.options || {},
			} );
		} catch ( err ) {
			_debug( 'Chart init failed for widget ' + widgetId + ': ' + err.message );
		}
	} );

	// ── Expose on window ─────────────────────────────────────────────

	window.DWM = DWM;

} )();
