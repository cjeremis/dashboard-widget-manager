/**
 * Dashboard Widget Manager - WP Dashboard Bundle
 *
 * Loaded only on the WordPress admin dashboard (index.php).
 * Initializes the widget initializer registry, then widget auto-refresh
 * and manual refresh behavior.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import '../modules/partials/widget-initializer.js';
import { init as initWidgetRefresh } from '../modules/partials/widget-refresh.js';

( function() {
	'use strict';

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initWidgetRefresh );
	} else {
		initWidgetRefresh();
	}
} )();
