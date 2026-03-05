/**
 * Dashboard Widget Manager - Tools Page
 *
 * Entry point for the Tools admin page.
 * Imports tools dependencies and initializes tools interactions.
 *
 * Output: /assets/minimized/js/tools.min.js
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { initExportImport } from '../modules/partials/export-import.js';
import { initResetModal } from '../modules/modals/reset-data.js';
import { initDemoData } from '../modules/partials/demo-data.js';
import { initCacheTools } from '../modules/partials/cache-tools.js';

jQuery(document).ready(function() {
	initExportImport();
	initResetModal();
	initDemoData();
	initCacheTools();
});
