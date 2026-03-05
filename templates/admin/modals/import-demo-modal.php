<?php
/**
 * Admin Modal Template - Import Demo Data
 *
 * Rendered inside the dwm-import-demo-modal modal body.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="dwm-demo-import-intro">
	<p><?php esc_html_e( 'Import sample data to explore what Dashboard Widget Manager can do. Demo data is clearly marked and can be removed at any time.', 'dashboard-widget-manager' ); ?></p>
</div>

<div class="dwm-export-toggle-group">
	<div class="dwm-export-toggle-row">
		<label class="dwm-toggle-switch">
			<input type="checkbox" id="dwm-import-demo-widgets" checked />
			<span class="dwm-toggle-slider"></span>
		</label>
		<div class="dwm-export-toggle-content">
			<span class="dwm-export-toggle-label"><?php esc_html_e( 'Widgets', 'dashboard-widget-manager' ); ?></span>
			<span class="dwm-export-toggle-desc"><?php esc_html_e( 'Import sample dashboard widgets with real WordPress data queries', 'dashboard-widget-manager' ); ?></span>
		</div>
	</div>

	<div class="dwm-export-toggle-row">
		<label class="dwm-toggle-switch">
			<input type="checkbox" id="dwm-import-demo-notifications" checked />
			<span class="dwm-toggle-slider"></span>
		</label>
		<div class="dwm-export-toggle-content">
			<span class="dwm-export-toggle-label"><?php esc_html_e( 'Notifications', 'dashboard-widget-manager' ); ?></span>
			<span class="dwm-export-toggle-desc"><?php esc_html_e( 'Import sample notifications with tips, reminders, and getting-started guides', 'dashboard-widget-manager' ); ?></span>
		</div>
	</div>
</div>

<div id="dwm-demo-import-error" class="dwm-notice dwm-notice-error" style="display:none;margin-top:12px;"></div>
