<?php
/**
 * Admin Partial Template - Widget Filters
 *
 * Renders the filter form fields inside the filters modal.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="dwm-cta-filters-wrapper">

	<div class="dwm-form-group">
		<label for="dwm-status-filter"><?php esc_html_e( 'Status', 'dashboard-widget-manager' ); ?></label>
		<select id="dwm-status-filter" name="status" class="dwm-select">
			<option value="all"><?php esc_html_e( 'All', 'dashboard-widget-manager' ); ?></option>
			<option value="publish"><?php esc_html_e( 'Active', 'dashboard-widget-manager' ); ?></option>
			<option value="draft"><?php esc_html_e( 'Draft', 'dashboard-widget-manager' ); ?></option>
			<option value="archived"><?php esc_html_e( 'Archived', 'dashboard-widget-manager' ); ?></option>
			<option value="trash"><?php esc_html_e( 'Trash', 'dashboard-widget-manager' ); ?></option>
		</select>
	</div>

	<div class="dwm-form-group" id="dwm-empty-trash-wrapper-modal" style="display: none;">
		<button type="button" id="dwm-empty-trash-btn-modal" class="dwm-button dwm-button-danger" style="width: 100%;">
			<span class="dashicons dashicons-trash"></span>
			<?php esc_html_e( 'Empty Trash', 'dashboard-widget-manager' ); ?>
		</button>
	</div>

	<div class="dwm-form-group">
		<label for="dwm-display-filter"><?php esc_html_e( 'Display Mode', 'dashboard-widget-manager' ); ?></label>
		<select id="dwm-display-filter" name="display" class="dwm-select">
			<option value=""><?php esc_html_e( 'All', 'dashboard-widget-manager' ); ?></option>
			<option value="table"><?php esc_html_e( 'Table', 'dashboard-widget-manager' ); ?></option>
			<option value="list"><?php esc_html_e( 'List', 'dashboard-widget-manager' ); ?></option>
			<option value="button"><?php esc_html_e( 'Button', 'dashboard-widget-manager' ); ?></option>
			<option value="card-list"><?php esc_html_e( 'Card List', 'dashboard-widget-manager' ); ?></option>
			<option value="bar"><?php esc_html_e( 'Bar Chart', 'dashboard-widget-manager' ); ?></option>
			<option value="line"><?php esc_html_e( 'Line Chart', 'dashboard-widget-manager' ); ?></option>
			<option value="pie"><?php esc_html_e( 'Pie Chart', 'dashboard-widget-manager' ); ?></option>
			<option value="doughnut"><?php esc_html_e( 'Doughnut Chart', 'dashboard-widget-manager' ); ?></option>
			<option value="manual"><?php esc_html_e( 'Manual', 'dashboard-widget-manager' ); ?></option>
		</select>
	</div>

</div>
