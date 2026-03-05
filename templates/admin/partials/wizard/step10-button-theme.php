<?php
/**
 * Wizard Step 10 (Button) - Theme Selection
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="dwm-step10-button-themes-grid dwm-step9-themes-grid">

	<label class="dwm-theme-option dwm-button-theme-option">
		<input type="radio" name="dwm_wizard_button_theme" value="solid" checked>
		<div class="dwm-theme-preview dwm-button-theme-preview dwm-button-theme-solid">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Solid', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-button-theme-btns"><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-button-theme-option">
		<input type="radio" name="dwm_wizard_button_theme" value="outline">
		<div class="dwm-theme-preview dwm-button-theme-preview dwm-button-theme-outline">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Outline', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-button-theme-btns"><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-button-theme-option">
		<input type="radio" name="dwm_wizard_button_theme" value="pill">
		<div class="dwm-theme-preview dwm-button-theme-preview dwm-button-theme-pill">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Pill', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-button-theme-btns"><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-button-theme-option">
		<input type="radio" name="dwm_wizard_button_theme" value="flat">
		<div class="dwm-theme-preview dwm-button-theme-preview dwm-button-theme-flat">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Flat', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-button-theme-btns"><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-button-theme-option">
		<input type="radio" name="dwm_wizard_button_theme" value="gradient">
		<div class="dwm-theme-preview dwm-button-theme-preview dwm-button-theme-gradient">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Gradient', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-button-theme-btns"><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-button-theme-option">
		<input type="radio" name="dwm_wizard_button_theme" value="dark">
		<div class="dwm-theme-preview dwm-button-theme-preview dwm-button-theme-dark">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Dark', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-button-theme-btns"><span></span><span></span></div>
		</div>
	</label>

</div>
