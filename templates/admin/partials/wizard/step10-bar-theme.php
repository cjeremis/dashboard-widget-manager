<?php
/**
 * Wizard Step 10 (Bar) - Theme Selection
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="dwm-step10-bar-themes-grid">
	<label class="dwm-theme-option dwm-bar-theme-option">
		<input type="radio" name="dwm_wizard_bar_theme" value="classic" checked>
		<div class="dwm-theme-preview dwm-bar-theme-preview dwm-bar-theme-classic">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Classic', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-bar-theme-bars"><span></span><span></span><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-bar-theme-option">
		<input type="radio" name="dwm_wizard_bar_theme" value="sunset">
		<div class="dwm-theme-preview dwm-bar-theme-preview dwm-bar-theme-sunset">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Sunset', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-bar-theme-bars"><span></span><span></span><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-bar-theme-option">
		<input type="radio" name="dwm_wizard_bar_theme" value="forest">
		<div class="dwm-theme-preview dwm-bar-theme-preview dwm-bar-theme-forest">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Forest', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-bar-theme-bars"><span></span><span></span><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-bar-theme-option">
		<input type="radio" name="dwm_wizard_bar_theme" value="oceanic">
		<div class="dwm-theme-preview dwm-bar-theme-preview dwm-bar-theme-oceanic">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Oceanic', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-bar-theme-bars"><span></span><span></span><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-bar-theme-option">
		<input type="radio" name="dwm_wizard_bar_theme" value="monochrome">
		<div class="dwm-theme-preview dwm-bar-theme-preview dwm-bar-theme-monochrome">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Monochrome', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-bar-theme-bars"><span></span><span></span><span></span><span></span></div>
		</div>
	</label>

	<label class="dwm-theme-option dwm-bar-theme-option">
		<input type="radio" name="dwm_wizard_bar_theme" value="candy">
		<div class="dwm-theme-preview dwm-bar-theme-preview dwm-bar-theme-candy">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php esc_html_e( 'Candy', 'dashboard-widget-manager' ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-bar-theme-bars"><span></span><span></span><span></span><span></span></div>
		</div>
	</label>
</div>
