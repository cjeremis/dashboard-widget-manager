<?php
/**
 * Admin Page Template - Settings
 *
 * Handles markup rendering for the settings admin page template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data     = DWM_Data::get_instance();
$settings = $data->get_settings();

// Page wrapper configuration.
$current_page       = 'dwm-settings';
$header_title       = __( 'Settings', 'dashboard-widget-manager' );
$header_description = __( 'Configure security settings for your dashboard widgets.', 'dashboard-widget-manager' );
$topbar_actions     = [];

include __DIR__ . '/partials/page-wrapper-start.php';
?>

<div class="dwm-page-content">

	<?php
	// Pro License section - shown as the first section on the settings page.
	$is_pro_enabled_via_filter = class_exists( 'DWM_Pro_Feature_Gate' ) && DWM_Pro_Feature_Gate::is_pro_enabled();
	$license_key_min_length    = 19;

	if ( $is_pro_enabled_via_filter && ! class_exists( 'DWM_License_Manager' ) ) :
		// Dev bypass mode (filter/cookie override) but license manager not yet available.
		$license_status    = 'active';
		$license_key       = 'DEV-MODE-LICENSE';
		$is_license_active = true;
		$has_saved_license = true;
		$section_title     = __( 'Pro License (Development Mode)', 'dashboard-widget-manager' );
		include DWM_PLUGIN_DIR . 'templates/admin/partials/license-manager.php';
		unset( $license_status, $license_key, $is_license_active, $has_saved_license, $section_title );

	elseif ( class_exists( 'DWM_License_Manager' ) ) :
		// License manager available - show the full license form.
		$license_data      = DWM_License_Manager::get_instance()->get_license_data();
		$license_status    = $license_data['status'] ?? 'inactive';
		$license_key       = $license_data['key'] ?? '';
		$is_license_active = 'active' === $license_status;
		$has_saved_license = ! empty( $license_key ) && $is_license_active;

		include DWM_PLUGIN_DIR . 'templates/admin/partials/license-manager.php';
		unset( $license_data, $license_status, $license_key, $is_license_active, $has_saved_license );

	endif;
	unset( $is_pro_enabled_via_filter, $license_key_min_length );
	?>

	<form id="dwm-settings-form">
		<?php wp_nonce_field( 'dwm_admin_nonce', 'dwm_settings_nonce' ); ?>

		<!-- Security -->
		<div class="dwm-section">
			<?php
			$title             = __( 'Security', 'dashboard-widget-manager' );
			$help_modal_target = 'dwm-docs-modal';
			$help_icon_label   = __( 'View security settings help', 'dashboard-widget-manager' );
			$attrs             = 'data-docs-page="settings-security"';
			include __DIR__ . '/partials/section-header-with-actions.php';
			unset( $title, $help_modal_target, $help_icon_label, $attrs, $actions_html );
			?>

			<div class="dwm-form-group">
				<div class="dwm-form-control">
					<?php
					global $wpdb;
					$all_tables         = $wpdb->get_col( 'SHOW TABLES' );
					sort( $all_tables );
					$allowed_tables_raw = $settings['allowed_tables'];
					if ( empty( $allowed_tables_raw ) ) {
						$checked_tables = $all_tables;
					} else {
						$checked_tables = array_filter( array_map( 'trim', explode( "\n", $allowed_tables_raw ) ) );
					}
					?>
					<input type="hidden" name="settings[allowed_tables]" id="dwm-allowed-tables-value" value="<?php echo esc_attr( $allowed_tables_raw ); ?>">
					<div class="dwm-table-controls">
						<a href="#" id="dwm-select-all-tables"><?php esc_html_e( 'Select All', 'dashboard-widget-manager' ); ?></a>
						<span>/</span>
						<a href="#" id="dwm-deselect-all-tables"><?php esc_html_e( 'Deselect All', 'dashboard-widget-manager' ); ?></a>
					</div>
					<div class="dwm-tables-grid">
						<?php foreach ( $all_tables as $table ) : ?>
							<label class="dwm-table-checkbox-label" title="<?php echo esc_attr( $table ); ?>">
								<input type="checkbox" class="dwm-table-checkbox" value="<?php echo esc_attr( $table ); ?>"
									<?php checked( in_array( $table, $checked_tables, true ) ); ?>>
								<?php echo esc_html( $table ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<div class="dwm-section-actions">
				<button type="submit" class="dwm-button dwm-button-primary">
					<?php esc_html_e( 'Save Security', 'dashboard-widget-manager' ); ?>
				</button>
			</div>
		</div>

	</form>

	<!-- Export Data -->
	<div class="dwm-section">
		<?php
		$title             = __( 'Export Data', 'dashboard-widget-manager' );
		$help_modal_target = 'dwm-docs-modal';
		$help_icon_label   = __( 'View export data help', 'dashboard-widget-manager' );
		$attrs             = 'data-docs-page="settings-export"';
		include __DIR__ . '/partials/section-header-with-actions.php';
		unset( $title, $help_modal_target, $help_icon_label, $attrs, $actions_html );
		?>

		<?php
		$all_widgets     = $data->get_widgets();
		$has_export_data = ! empty( $all_widgets );
		?>

		<?php if ( $has_export_data ) : ?>
			<div class="dwm-export-actions">
				<button type="button" id="dwm-export-button" class="dwm-button dwm-button-primary">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Download JSON File', 'dashboard-widget-manager' ); ?>
				</button>
				<button type="button" id="dwm-view-data" class="dwm-button dwm-button-secondary">
					<span class="dashicons dashicons-visibility"></span>
					<?php esc_html_e( 'View Data', 'dashboard-widget-manager' ); ?>
				</button>
			</div>
		<?php else : ?>
			<div class="dwm-empty-state">
				<span class="dashicons dashicons-download" style="font-size:20px;width:20px;height:20px;color:#c3c4c7;margin-bottom:4px;display:block;"></span>
				<p><strong><?php esc_html_e( 'No Data to Export', 'dashboard-widget-manager' ); ?></strong></p>
				<p><?php esc_html_e( 'There are no widgets available to export.', 'dashboard-widget-manager' ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<!-- Import Data -->
	<div class="dwm-section">
		<?php
		$title             = __( 'Import Data', 'dashboard-widget-manager' );
		$help_modal_target = 'dwm-docs-modal';
		$help_icon_label   = __( 'View import data help', 'dashboard-widget-manager' );
		$attrs             = 'data-docs-page="settings-import"';
		include __DIR__ . '/partials/section-header-with-actions.php';
		unset( $title, $help_modal_target, $help_icon_label, $attrs, $actions_html );
		?>

		<form method="post" enctype="multipart/form-data" action="#" class="dwm-import-form" id="dwm-import-form">
			<?php wp_nonce_field( 'dwm_admin_nonce', 'nonce' ); ?>

			<div class="dwm-import-settings-row">
				<!-- File Input Column -->
				<div class="dwm-form-group dwm-import-setting">
					<label for="dwm-import-file">
						<?php esc_html_e( 'File', 'dashboard-widget-manager' ); ?>
						<span style="color: var(--dwm-danger);">*</span>
					</label>
					<?php
					$input_id         = 'dwm-import-file';
					$input_name       = 'file';
					$wrapper_id       = 'dwm-file-input-wrapper';
					$selected_id      = 'dwm-file-selected';
					$file_name_id     = 'dwm-file-name';
					$file_size_id     = 'dwm-file-size';
					$remove_button_id = 'dwm-file-remove';
					$label_text       = __( 'Drop or Choose JSON File', 'dashboard-widget-manager' );
					include DWM_PLUGIN_DIR . 'templates/admin/partials/file-input-wrapper.php';
					unset( $input_id, $input_name, $wrapper_id, $selected_id, $file_name_id, $file_size_id, $remove_button_id, $label_text );
					?>
				</div>

				<!-- Import Mode Column -->
				<div class="dwm-form-group dwm-import-setting">
					<label for="dwm-import-mode">
						<?php esc_html_e( 'Mode', 'dashboard-widget-manager' ); ?>
					</label>
					<input type="hidden" name="import[mode]" value="replace" />
					<select id="dwm-import-mode" class="dwm-select" disabled>
						<option value="replace" selected><?php esc_html_e( 'Replace all data', 'dashboard-widget-manager' ); ?></option>
					</select>
				</div>

				<!-- Backup Column -->
				<div class="dwm-form-group dwm-import-setting">
					<label class="dwm-import-backup-label" for="dwm-import-backup">
						<?php esc_html_e( 'Backup', 'dashboard-widget-manager' ); ?>
						<span class="dwm-pro-badge dwm-pro-badge-inline"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
					</label>
					<label class="dwm-toggle-switch dwm-toggle-disabled">
						<input type="checkbox" name="import[backup]" id="dwm-import-backup" value="1" disabled="disabled">
						<span class="dwm-toggle-slider"></span>
					</label>
				</div>
			</div>

			<div class="dwm-import-actions" id="dwm-import-actions" style="display: none;">
				<button type="button" class="dwm-button dwm-button-primary" id="dwm-import-button" disabled>
					<?php esc_html_e( 'Import Data', 'dashboard-widget-manager' ); ?>
				</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dwm-settings' ) ); ?>" class="dwm-button dwm-button-secondary">
					<?php esc_html_e( 'Cancel', 'dashboard-widget-manager' ); ?>
				</a>
			</div>
		</form>

		<!-- Import Preview (shown after file selection) -->
		<div id="dwm-import-preview" class="dwm-import-preview" style="display: none;">
			<h3><?php esc_html_e( 'Import Preview', 'dashboard-widget-manager' ); ?></h3>
			<pre id="dwm-preview-content" class="dwm-json-preview"><code></code></pre>
		</div>
	</div>

	<!-- Demo Data -->
	<div class="dwm-section">
		<?php
		$title             = __( 'Demo Data', 'dashboard-widget-manager' );
		$help_modal_target = 'dwm-docs-modal';
		$help_icon_label   = __( 'View demo data help', 'dashboard-widget-manager' );
		$attrs             = 'data-docs-page="settings-demo-data"';
		include __DIR__ . '/partials/section-header-with-actions.php';
		unset( $title, $help_modal_target, $help_icon_label, $attrs, $actions_html );

		$demo_data_handler = DWM_Demo_Data::get_instance();
		$has_demo_file     = $demo_data_handler->demo_data_exists();
		$has_demo_widgets  = $data->has_demo_widgets();
		?>

		<?php if ( $has_demo_file ) : ?>
			<div class="dwm-demo-actions">
				<button type="button" id="dwm-open-import-demo-modal" class="dwm-button dwm-button-primary">
					<span class="dashicons dashicons-database-import"></span>
					<?php esc_html_e( 'Import Demo Widgets', 'dashboard-widget-manager' ); ?>
				</button>
				<?php if ( $has_demo_widgets ) : ?>
					<button type="button" id="dwm-delete-demo-data" class="dwm-button dwm-button-danger">
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Delete Demo Widgets', 'dashboard-widget-manager' ); ?>
					</button>
				<?php endif; ?>
			</div>
			<?php if ( $has_demo_widgets ) : ?>
				<p class="dwm-demo-active-notice">
					<span class="dashicons dashicons-yes-alt" style="color:#00a32a;"></span>
					<?php esc_html_e( 'Demo widgets are currently installed on your dashboard.', 'dashboard-widget-manager' ); ?>
				</p>
				<div class="dwm-export-actions">
					<button type="button" id="dwm-demo-export-button" class="dwm-button dwm-button-primary">
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Download JSON File', 'dashboard-widget-manager' ); ?>
					</button>
					<button type="button" id="dwm-demo-view-data" class="dwm-button dwm-button-secondary">
						<span class="dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'View Data', 'dashboard-widget-manager' ); ?>
					</button>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<div class="dwm-empty-state">
				<span class="dashicons dashicons-database" style="font-size:20px;width:20px;height:20px;color:#c3c4c7;margin-bottom:4px;display:block;"></span>
				<p><strong><?php esc_html_e( 'Demo data file not found.', 'dashboard-widget-manager' ); ?></strong></p>
			</div>
		<?php endif; ?>
	</div>

	<!-- Reset Data -->
	<div class="dwm-section">
		<?php
		$title             = __( 'Reset Data', 'dashboard-widget-manager' );
		$help_modal_target = 'dwm-docs-modal';
		$help_icon_label   = __( 'View reset data help', 'dashboard-widget-manager' );
		$attrs             = 'data-docs-page="settings-reset"';
		include __DIR__ . '/partials/section-header-with-actions.php';
		unset( $title, $help_modal_target, $help_icon_label, $attrs, $actions_html );
		?>

		<div class="dwm-reset-row">
			<button type="button" id="dwm-open-reset-modal" class="dwm-button dwm-button-danger"<?php echo empty( $all_widgets ) ? ' disabled' : ''; ?>>
				<span class="dashicons dashicons-trash"></span>
				<?php esc_html_e( 'Reset Data', 'dashboard-widget-manager' ); ?>
			</button>
		</div>
	</div>

</div>

<?php
// Export Options Modal
ob_start();
?>
<div class="dwm-export-options">
	<p class="dwm-export-intro">
		<?php esc_html_e( 'Select which data to include in your export file.', 'dashboard-widget-manager' ); ?>
	</p>

	<div class="dwm-export-toggle-group">
		<div class="dwm-export-toggle-row">
			<label class="dwm-toggle-switch">
				<input type="checkbox" id="dwm-export-widgets" checked />
				<span class="dwm-toggle-slider"></span>
			</label>
			<div class="dwm-export-toggle-content">
				<span class="dwm-export-toggle-label"><?php esc_html_e( 'Widgets', 'dashboard-widget-manager' ); ?></span>
				<span class="dwm-export-toggle-desc"><?php esc_html_e( 'Include all widget configurations', 'dashboard-widget-manager' ); ?></span>
			</div>
		</div>

		<div class="dwm-export-toggle-row">
			<label class="dwm-toggle-switch">
				<input type="checkbox" id="dwm-export-settings" checked />
				<span class="dwm-toggle-slider"></span>
			</label>
			<div class="dwm-export-toggle-content">
				<span class="dwm-export-toggle-label"><?php esc_html_e( 'Settings', 'dashboard-widget-manager' ); ?></span>
				<span class="dwm-export-toggle-desc"><?php esc_html_e( 'Include global plugin configuration', 'dashboard-widget-manager' ); ?></span>
			</div>
		</div>
	</div>

	<p class="dwm-export-hint">
		<span class="dashicons dashicons-info"></span>
		<?php esc_html_e( 'Exports are JSON only and can be imported back into Dashboard Widget Manager.', 'dashboard-widget-manager' ); ?>
	</p>
</div>
<?php
$export_options_body = ob_get_clean();

ob_start();
?>
<div style="display: flex; justify-content: flex-end; gap: var(--dwm-spacing-sm);">
	<button type="button" id="dwm-export-download-btn" class="dwm-button dwm-button-primary">
		<span class="dashicons dashicons-download"></span>
		<?php esc_html_e( 'Download JSON', 'dashboard-widget-manager' ); ?>
	</button>
</div>
<?php
$export_options_footer = ob_get_clean();
$modal                 = [
	'id'          => 'dwm-export-options-modal',
	'title_html'  => '<span class="dashicons dashicons-upload"></span>' . esc_html__( 'Export Data', 'dashboard-widget-manager' ),
	'body_html'   => $export_options_body,
	'footer_html' => $export_options_footer,
	'size_class'  => 'dwm-modal-md',
];
include DWM_PLUGIN_DIR . 'templates/admin/partials/modal.php';
unset( $modal, $export_options_body, $export_options_footer );
?>

<?php
// View Data Modal
ob_start();
?>
<textarea id="dwm-export-json-preview" class="dwm-json-textarea" readonly></textarea>
<?php
$view_body = ob_get_clean();

ob_start();
?>
<div class="dwm-view-data-footer">
	<div class="dwm-view-data-toggles">
		<div class="dwm-view-toggle-item">
			<label class="dwm-toggle-switch dwm-toggle-switch--sm">
				<input type="checkbox" id="dwm-view-toggle-widgets" class="dwm-view-toggle" data-key="widgets" checked />
				<span class="dwm-toggle-slider"></span>
			</label>
			<span class="dwm-view-toggle-label"><?php esc_html_e( 'Widgets', 'dashboard-widget-manager' ); ?></span>
		</div>
		<div class="dwm-view-toggle-item">
			<label class="dwm-toggle-switch dwm-toggle-switch--sm">
				<input type="checkbox" id="dwm-view-toggle-settings" class="dwm-view-toggle" data-key="settings" checked />
				<span class="dwm-toggle-slider"></span>
			</label>
			<span class="dwm-view-toggle-label"><?php esc_html_e( 'Settings', 'dashboard-widget-manager' ); ?></span>
		</div>
	</div>
	<div class="dwm-view-data-actions">
		<button type="button" id="dwm-modal-copy-json" class="dwm-button dwm-button-secondary">
			<span class="dashicons dashicons-clipboard"></span>
			<?php esc_html_e( 'Copy to Clipboard', 'dashboard-widget-manager' ); ?>
		</button>
		<button type="button" id="dwm-modal-download-json" class="dwm-button dwm-button-primary">
			<span class="dashicons dashicons-download"></span>
			<?php esc_html_e( 'Download JSON', 'dashboard-widget-manager' ); ?>
		</button>
	</div>
</div>
<?php
$view_footer = ob_get_clean();
$modal       = [
	'id'          => 'dwm-view-data-modal',
	'title_html'  => '<span class="dashicons dashicons-visibility"></span>' . esc_html__( 'View Data', 'dashboard-widget-manager' ),
	'body_html'   => $view_body,
	'footer_html' => $view_footer,
	'size_class'  => 'dwm-modal-xl',
];
include DWM_PLUGIN_DIR . 'templates/admin/partials/modal.php';
unset( $modal, $view_body, $view_footer );
?>

<?php
// Reset Data Modal
ob_start();
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="dwm-reset-data-form">
	<?php wp_nonce_field( 'dwm_admin_nonce', 'nonce' ); ?>
	<input type="hidden" name="action" value="dwm_reset_data" />
</form>
<div class="dwm-reset-options">
	<p class="dwm-reset-intro"><?php esc_html_e( 'Select which data to permanently delete.', 'dashboard-widget-manager' ); ?></p>

	<div class="dwm-reset-warning">
		<span class="dashicons dashicons-warning"></span>
		<div>
			<p class="dwm-reset-warning-title"><?php esc_html_e( 'Warning: This action cannot be undone', 'dashboard-widget-manager' ); ?></p>
			<p class="dwm-reset-warning-body"><?php esc_html_e( 'Reset removes data immediately. Export first if you may need it later.', 'dashboard-widget-manager' ); ?></p>
		</div>
	</div>

	<div class="dwm-reset-toggle-group">
		<div class="dwm-reset-toggle-row">
			<div class="dwm-reset-toggle-content">
				<span class="dwm-reset-toggle-label"><?php esc_html_e( 'Widgets', 'dashboard-widget-manager' ); ?></span>
				<span class="dwm-reset-toggle-desc"><?php esc_html_e( 'Delete all widget configurations', 'dashboard-widget-manager' ); ?></span>
			</div>
			<label class="dwm-toggle-switch">
				<input type="checkbox" name="reset[widgets]" id="dwm-reset-widgets" value="1" form="dwm-reset-data-form" />
				<span class="dwm-toggle-slider"></span>
			</label>
		</div>

		<div class="dwm-reset-toggle-row">
			<div class="dwm-reset-toggle-content">
				<span class="dwm-reset-toggle-label"><?php esc_html_e( 'Settings', 'dashboard-widget-manager' ); ?></span>
				<span class="dwm-reset-toggle-desc"><?php esc_html_e( 'Reset all plugin settings to defaults', 'dashboard-widget-manager' ); ?></span>
			</div>
			<label class="dwm-toggle-switch">
				<input type="checkbox" name="reset[settings]" id="dwm-reset-settings-check" value="1" form="dwm-reset-data-form" />
				<span class="dwm-toggle-slider"></span>
			</label>
		</div>
	</div>
</div>
<?php
$reset_body = ob_get_clean();

ob_start();
?>
<div style="display: flex; justify-content: space-between; align-items: center; width: 100%; gap: var(--dwm-spacing-sm);">
	<button type="button" id="dwm-reset-export-first" class="dwm-button dwm-button-secondary">
		<span class="dashicons dashicons-download"></span>
		<?php esc_html_e( 'Export First', 'dashboard-widget-manager' ); ?>
	</button>
	<button type="button" id="dwm-reset-confirm-button" class="dwm-button dwm-button-danger">
		<span class="dashicons dashicons-trash"></span>
		<?php esc_html_e( 'Reset Data', 'dashboard-widget-manager' ); ?>
	</button>
</div>
<?php
$reset_footer = ob_get_clean();
$modal        = [
	'id'          => 'dwm-reset-data-modal',
	'title_html'  => '<span class="dashicons dashicons-trash"></span>' . esc_html__( 'Reset Data', 'dashboard-widget-manager' ),
	'body_html'   => $reset_body,
	'footer_html' => $reset_footer,
	'size_class'  => 'dwm-modal-md',
];
include DWM_PLUGIN_DIR . 'templates/admin/partials/modal.php';
unset( $modal, $reset_body, $reset_footer );
?>

<?php
// Import Demo Data Modal
ob_start();
include DWM_PLUGIN_DIR . 'templates/admin/modals/import-demo-modal.php';
$import_demo_body = ob_get_clean();

ob_start();
?>
<div style='display:flex;justify-content:flex-end;gap:var(--dwm-spacing-sm);'>
	<button type='button' id='dwm-import-demo-confirm' class='dwm-button dwm-button-primary'>
		<span class='dashicons dashicons-database-import'></span>
		<?php esc_html_e( 'Import Demo Widgets', 'dashboard-widget-manager' ); ?>
	</button>
</div>
<?php
$import_demo_footer = ob_get_clean();
$modal              = [
	'id'          => 'dwm-import-demo-modal',
	'title_html'  => '<span class="dashicons dashicons-database-import"></span>' . esc_html__( 'Import Demo Data', 'dashboard-widget-manager' ),
	'body_html'   => $import_demo_body,
	'footer_html' => $import_demo_footer,
	'size_class'  => 'dwm-modal-md',
];
include DWM_PLUGIN_DIR . 'templates/admin/partials/modal.php';
unset( $modal, $import_demo_body, $import_demo_footer );
?>

<?php include __DIR__ . '/partials/page-wrapper-end.php'; ?>
