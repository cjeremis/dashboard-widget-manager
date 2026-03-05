<?php
/**
 * Wizard Step 10 (List) - Theme Selection
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="dwm-step10-list-themes-grid dwm-step9-themes-grid">

	<label class="dwm-theme-option dwm-list-theme-option">
		<input type="radio" name="dwm_wizard_list_theme" value="clean" checked>
		<div class="dwm-theme-preview dwm-list-theme-preview dwm-list-theme-clean">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Clean', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-list-theme-items"><span></span><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-list-theme-option">
		<input type="radio" name="dwm_wizard_list_theme" value="bordered">
		<div class="dwm-theme-preview dwm-list-theme-preview dwm-list-theme-bordered">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Bordered', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-list-theme-items"><span></span><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-list-theme-option">
		<input type="radio" name="dwm_wizard_list_theme" value="striped">
		<div class="dwm-theme-preview dwm-list-theme-preview dwm-list-theme-striped">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Striped', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-list-theme-items"><span></span><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-list-theme-option">
		<input type="radio" name="dwm_wizard_list_theme" value="compact">
		<div class="dwm-theme-preview dwm-list-theme-preview dwm-list-theme-compact">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Compact', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-list-theme-items"><span></span><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-list-theme-option">
		<input type="radio" name="dwm_wizard_list_theme" value="dark">
		<div class="dwm-theme-preview dwm-list-theme-preview dwm-list-theme-dark">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Dark', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-list-theme-items"><span></span><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-list-theme-option">
		<input type="radio" name="dwm_wizard_list_theme" value="accent">
		<div class="dwm-theme-preview dwm-list-theme-preview dwm-list-theme-accent">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Accent', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-list-theme-items"><span></span><span></span><span></span></div>
		</div>
	</label>

</div>
