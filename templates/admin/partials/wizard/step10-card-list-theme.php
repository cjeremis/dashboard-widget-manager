<?php
/**
 * Wizard Step 10 (Card List) - Theme Selection
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="dwm-step10-card-list-themes-grid dwm-step9-themes-grid">

	<label class="dwm-theme-option dwm-card-list-theme-option">
		<input type="radio" name="dwm_wizard_card_list_theme" value="elevated" checked>
		<div class="dwm-theme-preview dwm-card-list-theme-preview dwm-card-list-theme-elevated">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Elevated', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-card-list-theme-cards"><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-card-list-theme-option">
		<input type="radio" name="dwm_wizard_card_list_theme" value="flat">
		<div class="dwm-theme-preview dwm-card-list-theme-preview dwm-card-list-theme-flat">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Flat', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-card-list-theme-cards"><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-card-list-theme-option">
		<input type="radio" name="dwm_wizard_card_list_theme" value="bordered">
		<div class="dwm-theme-preview dwm-card-list-theme-preview dwm-card-list-theme-bordered">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Bordered', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-card-list-theme-cards"><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-card-list-theme-option">
		<input type="radio" name="dwm_wizard_card_list_theme" value="minimal">
		<div class="dwm-theme-preview dwm-card-list-theme-preview dwm-card-list-theme-minimal">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Minimal', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-card-list-theme-cards"><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-card-list-theme-option">
		<input type="radio" name="dwm_wizard_card_list_theme" value="dark">
		<div class="dwm-theme-preview dwm-card-list-theme-preview dwm-card-list-theme-dark">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Dark', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-card-list-theme-cards"><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-card-list-theme-option">
		<input type="radio" name="dwm_wizard_card_list_theme" value="colorful">
		<div class="dwm-theme-preview dwm-card-list-theme-preview dwm-card-list-theme-colorful">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Colorful', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-card-list-theme-cards"><span></span><span></span></div>
		</div>
	</label>

</div>
