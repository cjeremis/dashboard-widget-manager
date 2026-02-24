<?php
/**
 * Widget Manager Template
 *
 * Displays the widget management interface.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data    = DWM_Data::get_instance();
$widgets = $data->get_widgets();
?>

<div class="wrap dwm-widget-manager dwm-page-wrapper">
	<h1>
		<?php echo esc_html( get_admin_page_title() ); ?>
		<a href="#" class="page-title-action dwm-create-widget"><?php esc_html_e( 'Create New Widget', 'dashboard-widget-manager' ); ?></a>
	</h1>

	<div class="dwm-widgets-list">
		<?php if ( ! empty( $widgets ) ) : ?>
			<table class="widefat fixed striped" id="dwm-widgets-table">
				<thead>
					<tr>
						<th class="column-order"><?php esc_html_e( 'Order', 'dashboard-widget-manager' ); ?></th>
						<th class="column-name"><?php esc_html_e( 'Name', 'dashboard-widget-manager' ); ?></th>
						<th class="column-description"><?php esc_html_e( 'Description', 'dashboard-widget-manager' ); ?></th>
						<th class="column-status"><?php esc_html_e( 'Status', 'dashboard-widget-manager' ); ?></th>
						<th class="column-actions"><?php esc_html_e( 'Actions', 'dashboard-widget-manager' ); ?></th>
					</tr>
				</thead>
				<tbody class="dwm-widgets-sortable">
					<?php foreach ( $widgets as $widget ) : ?>
						<tr data-widget-id="<?php echo esc_attr( $widget['id'] ); ?>">
							<td class="column-order">
								<span class="dashicons dashicons-menu handle"></span>
								<span class="order-number"><?php echo esc_html( $widget['widget_order'] ); ?></span>
							</td>
							<td class="column-name">
								<strong><?php echo esc_html( $widget['name'] ); ?></strong>
							</td>
							<td class="column-description">
								<?php echo esc_html( $widget['description'] ); ?>
							</td>
							<td class="column-status">
								<label class="dwm-toggle">
									<input type="checkbox" class="dwm-toggle-widget"
										data-widget-id="<?php echo esc_attr( $widget['id'] ); ?>"
										<?php checked( $widget['enabled'], 1 ); ?>>
									<span class="dwm-toggle-slider"></span>
								</label>
							</td>
							<td class="column-actions">
								<button class="button dwm-edit-widget" data-widget-id="<?php echo esc_attr( $widget['id'] ); ?>">
									<?php esc_html_e( 'Edit', 'dashboard-widget-manager' ); ?>
								</button>
								<button class="button dwm-preview-widget" data-widget-id="<?php echo esc_attr( $widget['id'] ); ?>">
									<?php esc_html_e( 'Preview', 'dashboard-widget-manager' ); ?>
								</button>
								<button class="button dwm-delete-widget" data-widget-id="<?php echo esc_attr( $widget['id'] ); ?>">
									<?php esc_html_e( 'Delete', 'dashboard-widget-manager' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<div class="dwm-empty-state">
				<p><?php esc_html_e( 'No widgets found. Create your first widget to get started!', 'dashboard-widget-manager' ); ?></p>
				<p><a href="#" class="button button-primary dwm-create-widget"><?php esc_html_e( 'Create New Widget', 'dashboard-widget-manager' ); ?></a></p>
			</div>
		<?php endif; ?>
	</div>

	<!-- Widget Editor Modal -->
	<div id="dwm-widget-editor-modal" class="dwm-modal" style="display: none;">
		<div class="dwm-modal-content">
			<div class="dwm-modal-header">
				<h2 id="dwm-editor-title"><?php esc_html_e( 'Widget Editor', 'dashboard-widget-manager' ); ?></h2>
				<button class="dwm-modal-close">&times;</button>
			</div>
			<div class="dwm-modal-body">
				<?php require_once DWM_PLUGIN_DIR . 'templates/admin/widget-editor.php'; ?>
			</div>
		</div>
	</div>

	<!-- Preview Modal -->
	<div id="dwm-preview-modal" class="dwm-modal" style="display: none;">
		<div class="dwm-modal-content">
			<div class="dwm-modal-header">
				<h2><?php esc_html_e( 'Widget Preview', 'dashboard-widget-manager' ); ?></h2>
				<button class="dwm-modal-close">&times;</button>
			</div>
			<div class="dwm-modal-body">
				<div id="dwm-preview-content"></div>
			</div>
		</div>
	</div>
</div>
