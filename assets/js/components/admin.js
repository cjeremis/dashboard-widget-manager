/**
 * Dashboard Widget Manager - Admin Global Entry Point
 *
 * This is the main entry point for all Dashboard Widget Manager admin pages.
 * It imports and initializes global modules needed across the admin interface.
 *
 * Output: /assets/js/minimized/admin/admin.min.js
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import '../modules/partials/ajax.js';
import '../modules/partials/notifications.js';
import '../modules/partials/toast.js';
import { initModals } from '../modules/modals/modals.js';
import '../modules/modals/notifications-panel.js';
import '../modules/modals/docs-modal.js';
import '../modules/modals/support-modal.js';
import '../modules/partials/url-params.js';

// Initialize global modal handlers
jQuery(document).ready(function() {
	initModals();
});
