/**
 * Dashboard Widget Manager - Settings Entry Point
 *
 * Entry point for the settings admin page.
 * Imports settings dependencies and initializes settings interactions.
 *
 * Output: /assets/js/minimized/admin/settings.min.js
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { initSettings } from '../modules/forms/settings-form.js';
import { initLicense } from '../modules/partials/license-manager.js';

jQuery(document).ready(function() {
	initSettings();
	initLicense();
});
