<?php
/**
 * Wizard Step 9 (Bar) - Axis Mapping
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="dwm-step9-bar-axis-wrap">
	<div class="dwm-form-group">
		<label for="dwm-wizard-bar-label-column"><?php esc_html_e( 'X-Axis Label Column', 'dashboard-widget-manager' ); ?> *</label>
		<select id="dwm-wizard-bar-label-column" class="dwm-select">
			<option value=""><?php esc_html_e( 'Select a label column', 'dashboard-widget-manager' ); ?></option>
		</select>
	</div>

	<div class="dwm-form-group">
		<label><?php esc_html_e( 'Y-Axis Data Columns', 'dashboard-widget-manager' ); ?> *</label>
		<div id="dwm-wizard-bar-data-columns-list" class="dwm-wizard-columns-list dwm-wizard-bar-data-columns-list"></div>
	</div>

	<div class="dwm-form-group">
		<label for="dwm-wizard-bar-chart-title"><?php esc_html_e( 'Chart Title', 'dashboard-widget-manager' ); ?></label>
		<input type="text" id="dwm-wizard-bar-chart-title" class="dwm-input-text" placeholder="<?php esc_attr_e( 'Optional title shown above chart', 'dashboard-widget-manager' ); ?>">
	</div>

	<div class="dwm-form-group">
		<label class="dwm-toggle">
			<input type="checkbox" id="dwm-wizard-bar-show-legend" checked>
			<span class="dwm-toggle-slider"></span>
		</label>
		<span class="dwm-step-section-hint"><?php esc_html_e( 'Show chart legend', 'dashboard-widget-manager' ); ?></span>
	</div>

	<div id="dwm-wizard-bar-axis-error" class="dwm-validation-error" style="display:none;"></div>
</div>
