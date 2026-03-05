<?php
/**
 * Admin Page Template - Tools
 *
 * Handles markup rendering for the tools admin page template.
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
$current_page       = 'dwm-tools';
$header_title       = __( 'Tools', 'dashboard-widget-manager' );
$header_description = __( 'Use maintenance tools to export or import widget data, load demo content, clear caches, and reset plugin data safely when needed.', 'dashboard-widget-manager' );
$topbar_actions     = [];

include __DIR__ . '/partials/page-wrapper-start.php';
?>

<div class="dwm-page-content">

	<?php $all_widgets = $data->get_widgets(); ?>

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

		<?php $has_export_data = ! empty( $all_widgets ); ?>

		<?php if ( $has_export_data ) : ?>
			<div class="dwm-export-actions dwm-export-actions--tools-parity">
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
						<span class="dwm-pro-badge dwm-pro-badge-inline"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
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
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dwm-tools' ) ); ?>" class="dwm-button dwm-button-secondary">
					<?php esc_html_e( 'Cancel', 'dashboard-widget-manager' ); ?>
				</a>
			</div>
		</form>

	</div>

	<!-- Clear Caches -->
	<div class="dwm-section">
		<?php
		$title             = __( 'Clear Caches', 'dashboard-widget-manager' );
		$help_modal_target = 'dwm-docs-modal';
		$help_icon_label   = __( 'View clear cache help', 'dashboard-widget-manager' );
		$attrs             = 'data-docs-page="feature-caching"';
		include __DIR__ . '/partials/section-header-with-actions.php';
		unset( $title, $help_modal_target, $help_icon_label, $attrs, $actions_html );
		?>

		<div class="dwm-reset-row">
			<button type="button" id="dwm-open-clear-caches-modal" class="dwm-button dwm-button-primary">
				<span class="dashicons dashicons-update"></span>
				<?php esc_html_e( 'Clear All Caches', 'dashboard-widget-manager' ); ?>
			</button>
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

				<?php if ( ! $has_demo_widgets ) : ?>
					<button type="button" id="dwm-open-import-demo-modal" class="dwm-button dwm-button-primary">
						<span class="dashicons dashicons-database-import"></span>
						<?php esc_html_e( 'Import Demo Data', 'dashboard-widget-manager' ); ?>
					</button>
				<?php endif; ?>
				<?php if ( $has_demo_widgets ) : ?>
					<button type="button" id="dwm-delete-demo-data" class="dwm-button dwm-button-danger">
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Delete Demo Data', 'dashboard-widget-manager' ); ?>
					</button>
				<?php endif; ?>
			</div>
			<?php if ( $has_demo_widgets ) : ?>
				<p class="dwm-demo-active-notice">
					<span class="dashicons dashicons-yes-alt" style="color:#00a32a;"></span>
					<?php esc_html_e( 'Demo widgets are currently installed.', 'dashboard-widget-manager' ); ?>
				</p>
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

		<?php $has_any_resetable_data = ! empty( $all_widgets ) || ! empty( $settings['excluded_tables'] ); ?>

		<div class="dwm-reset-row">
			<button type="button" id="dwm-open-reset-modal" class="dwm-button dwm-button-danger"<?php echo ! $has_any_resetable_data ? ' disabled' : ''; ?>>
				<span class="dashicons dashicons-trash"></span>
				<?php esc_html_e( 'Reset Data', 'dashboard-widget-manager' ); ?>
			</button>
		</div>
		<?php if ( ! $has_any_resetable_data ) : ?>
			<p class="dwm-demo-active-notice">
				<span class="dashicons dashicons-info" style="color:var(--dwm-text-muted);"></span>
				<?php esc_html_e( 'No data to reset. The plugin is using only default settings.', 'dashboard-widget-manager' ); ?>
			</p>
		<?php endif; ?>
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

		<div class="dwm-export-toggle-row">
			<label class="dwm-toggle-switch">
				<input type="checkbox" id="dwm-export-notifications" checked />
				<span class="dwm-toggle-slider"></span>
			</label>
			<div class="dwm-export-toggle-content">
				<span class="dwm-export-toggle-label"><?php esc_html_e( 'Notifications', 'dashboard-widget-manager' ); ?></span>
				<span class="dwm-export-toggle-desc"><?php esc_html_e( 'Include your current notification inbox', 'dashboard-widget-manager' ); ?></span>
			</div>
		</div>
	</div>
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
		<div class="dwm-view-toggle-item">
			<label class="dwm-toggle-switch dwm-toggle-switch--sm">
				<input type="checkbox" id="dwm-view-toggle-notifications" class="dwm-view-toggle" data-key="notifications" checked />
				<span class="dwm-toggle-slider"></span>
			</label>
			<span class="dwm-view-toggle-label"><?php esc_html_e( 'Notifications', 'dashboard-widget-manager' ); ?></span>
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
// Clear Caches Modal
ob_start();
?>
<div class="dwm-reset-warning">
	<span class="dashicons dashicons-warning"></span>
	<div>
		<p class="dwm-reset-warning-title"><?php esc_html_e( 'Clear all Widget Manager cache?', 'dashboard-widget-manager' ); ?></p>
		<p class="dwm-reset-warning-body"><?php esc_html_e( 'This will purge query cache entries and all plugin cache artifacts so fresh table/column/query data is loaded next time.', 'dashboard-widget-manager' ); ?></p>
	</div>
</div>
<?php
$clear_caches_body = ob_get_clean();

ob_start();
?>
<div style="display:flex;justify-content:flex-end;gap:var(--dwm-spacing-sm);">
	<button type="button" class="dwm-button dwm-button-secondary" data-close-modal>
		<?php esc_html_e( 'Cancel', 'dashboard-widget-manager' ); ?>
	</button>
	<button type="button" id="dwm-clear-caches-confirm" class="dwm-button dwm-button-primary">
		<span class="dashicons dashicons-update"></span>
		<?php esc_html_e( 'Clear All Caches', 'dashboard-widget-manager' ); ?>
	</button>
</div>
<?php
$clear_caches_footer = ob_get_clean();
$modal               = [
	'id'          => 'dwm-clear-caches-modal',
	'title_html'  => '<span class="dashicons dashicons-update"></span>' . esc_html__( 'Clear Caches', 'dashboard-widget-manager' ),
	'body_html'   => $clear_caches_body,
	'footer_html' => $clear_caches_footer,
	'size_class'  => 'dwm-modal-md',
];
include DWM_PLUGIN_DIR . 'templates/admin/partials/modal.php';
unset( $modal, $clear_caches_body, $clear_caches_footer );
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

		<div class="dwm-reset-toggle-row">
			<div class="dwm-reset-toggle-content">
				<span class="dwm-reset-toggle-label"><?php esc_html_e( 'Notifications', 'dashboard-widget-manager' ); ?></span>
				<span class="dwm-reset-toggle-desc"><?php esc_html_e( 'Delete all notifications from your inbox', 'dashboard-widget-manager' ); ?></span>
			</div>
			<label class="dwm-toggle-switch">
				<input type="checkbox" name="reset[notifications]" id="dwm-reset-notifications" value="1" form="dwm-reset-data-form" />
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
		<?php esc_html_e( 'Import Demo Data', 'dashboard-widget-manager' ); ?>
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

<?php
// Delete Demo Data Modal
ob_start();
?>
<div class="dwm-reset-warning">
	<span class="dashicons dashicons-warning"></span>
	<div>
		<p class="dwm-reset-warning-title"><?php esc_html_e( 'Delete all demo data?', 'dashboard-widget-manager' ); ?></p>
		<p class="dwm-reset-warning-body"><?php esc_html_e( 'This permanently removes imported demo data.', 'dashboard-widget-manager' ); ?></p>
	</div>
</div>
<?php
$delete_demo_body = ob_get_clean();

ob_start();
?>
<div style="display:flex;justify-content:flex-end;gap:var(--dwm-spacing-sm);">
	<button type="button" class="dwm-button dwm-button-secondary" data-close-modal>
		<?php esc_html_e( 'Cancel', 'dashboard-widget-manager' ); ?>
	</button>
	<button type="button" id="dwm-delete-demo-confirm" class="dwm-button dwm-button-danger">
		<span class="dashicons dashicons-trash"></span>
		<?php esc_html_e( 'Delete Demo Data', 'dashboard-widget-manager' ); ?>
	</button>
</div>
<?php
$delete_demo_footer = ob_get_clean();
$modal              = [
	'id'          => 'dwm-delete-demo-modal',
	'title_html'  => '<span class="dashicons dashicons-trash"></span>' . esc_html__( 'Delete Demo Data', 'dashboard-widget-manager' ),
	'body_html'   => $delete_demo_body,
	'footer_html' => $delete_demo_footer,
	'size_class'  => 'dwm-modal-md',
];
include DWM_PLUGIN_DIR . 'templates/admin/partials/modal.php';
unset( $modal, $delete_demo_body, $delete_demo_footer );
?>

<?php include __DIR__ . '/partials/page-wrapper-end.php'; ?>
