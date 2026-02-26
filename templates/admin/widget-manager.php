<?php
/**
 * Admin Page Template - Widget Manager
 *
 * Handles markup rendering for the widget manager admin page template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data           = DWM_Data::get_instance();
$widgets        = $data->get_widgets();
$allowed_tables = $data->get_allowed_tables();

// Count widgets per status.
$publish_count  = 0;
$draft_count    = 0;
$archived_count = 0;
$trash_count    = 0;

foreach ( $widgets as $w ) {
	$s = $w['status'] ?? 'draft';
	switch ( $s ) {
		case 'publish':  $publish_count++;  break;
		case 'archived': $archived_count++; break;
		case 'trash':    $trash_count++;    break;
		default:         $draft_count++;    break;
	}
}

// Page wrapper configuration.
$current_page       = 'dashboard-widget-manager';
$header_title       = __( 'Widget Manager', 'dashboard-widget-manager' );
$header_description = __( 'Create and manage custom dashboard widgets powered by SQL queries, PHP, HTML, CSS, and JavaScript.', 'dashboard-widget-manager' );
$topbar_actions     = [];

include __DIR__ . '/partials/page-wrapper-start.php';
?>

<div class="dwm-page-content">

	<div class="dwm-section">
		<?php
		ob_start();
		echo esc_html__( 'Widgets', 'dashboard-widget-manager' );
		?>
		<span class="dwm-status-filters">
			<button type="button" class="dwm-status-filter is-active" data-filter="all"><?php esc_html_e( 'All', 'dashboard-widget-manager' ); ?></button>
			<?php if ( $publish_count > 0 ) : ?>
				<span class="dwm-status-separator">|</span>
				<button type="button" class="dwm-status-filter" data-filter="publish"><span class="dwm-status-count"><?php echo esc_html( $publish_count ); ?></span> <?php esc_html_e( 'Active', 'dashboard-widget-manager' ); ?></button>
			<?php endif; ?>
			<?php if ( $draft_count > 0 ) : ?>
				<span class="dwm-status-separator">|</span>
				<button type="button" class="dwm-status-filter" data-filter="draft"><span class="dwm-status-count"><?php echo esc_html( $draft_count ); ?></span> <?php esc_html_e( 'Draft', 'dashboard-widget-manager' ); ?></button>
			<?php endif; ?>
			<?php if ( $archived_count > 0 ) : ?>
				<span class="dwm-status-separator">|</span>
				<button type="button" class="dwm-status-filter" data-filter="archived"><span class="dwm-status-count"><?php echo esc_html( $archived_count ); ?></span> <?php esc_html_e( 'Archive', 'dashboard-widget-manager' ); ?></button>
			<?php endif; ?>
			<?php if ( $trash_count > 0 ) : ?>
				<span class="dwm-status-separator">|</span>
				<button type="button" class="dwm-status-filter" data-filter="trash"><span class="dwm-status-count"><?php echo esc_html( $trash_count ); ?></span> <?php esc_html_e( 'Trash', 'dashboard-widget-manager' ); ?></button>
			<?php endif; ?>
		</span>
		<?php
		$title_raw       = ob_get_clean();
		$actions_html    = '<a href="#" class="dwm-button dwm-button-primary dwm-create-widget"><span class="dashicons dashicons-plus-alt2"></span>' . esc_html__( 'Create New Widget', 'dashboard-widget-manager' ) . '</a>';
		$help_icon_label = __( 'View widget manager help', 'dashboard-widget-manager' );
		include __DIR__ . '/partials/section-header-with-actions.php';
		unset( $title_raw, $actions_html, $help_icon_label );
		?>

		<div class="dwm-widgets-list">
			<?php if ( ! empty( $widgets ) ) : ?>
				<div class="dwm-widget-cards dwm-widgets-sortable" id="dwm-widgets-table">
					<?php foreach ( $widgets as $widget ) :
						$widget_status = $widget['status'] ?? 'draft';
						$is_demo       = (int) $widget['is_demo'] === 1;
						$created_date  = date_i18n( 'M j, y', strtotime( $widget['created_at'] ) );
						$modified_date = date_i18n( 'M j, y', strtotime( $widget['updated_at'] ) );
						$first_active  = ! empty( $widget['first_published_at'] ) ? date_i18n( 'M j, y', strtotime( $widget['first_published_at'] ) ) : '';
						$author_name   = __( 'Demo Data', 'dashboard-widget-manager' );
						if ( ! $is_demo ) {
							$created_by  = get_userdata( $widget['created_by'] );
							$author_name = $created_by ? $created_by->user_login : __( 'Unknown', 'dashboard-widget-manager' );
						}
					?>
						<div class="dwm-widget-card" data-widget-id="<?php echo esc_attr( $widget['id'] ); ?>" data-widget-status="<?php echo esc_attr( $widget_status ); ?>"<?php if ( 'archived' === $widget_status || 'trash' === $widget_status ) echo ' style="display:none"'; ?>>
							<div class="dwm-widget-card-left">
								<span class="dashicons dashicons-menu handle"></span>
								<span class="dwm-widget-card-order"><?php echo esc_html( $widget['widget_order'] ); ?></span>
								<div class="dwm-widget-card-info">
									<div class="dwm-widget-card-title-row">
										<strong class="dwm-widget-card-name"><?php echo esc_html( $widget['name'] ); ?></strong>
									</div>
									<div class="dwm-widget-card-meta">
										<span><?php esc_html_e( 'Created:', 'dashboard-widget-manager' ); ?> <strong><?php echo esc_html( $created_date ); ?></strong></span>
										<span><?php esc_html_e( 'Modified:', 'dashboard-widget-manager' ); ?> <strong><?php echo esc_html( $modified_date ); ?></strong></span>
										<?php if ( $first_active ) : ?>
											<span><?php esc_html_e( 'Activated:', 'dashboard-widget-manager' ); ?> <strong><?php echo esc_html( $first_active ); ?></strong></span>
										<?php endif; ?>
										<span><?php esc_html_e( 'By:', 'dashboard-widget-manager' ); ?> <strong><?php echo esc_html( $author_name ); ?></strong></span>
									</div>
								</div>
							</div>
							<div class="dwm-widget-card-right">
								<div class="dwm-widget-card-badges">
									<?php
									switch ( $widget_status ) {
										case 'publish':
											echo '<span class="dwm-badge dwm-badge-success">' . esc_html__( 'Active', 'dashboard-widget-manager' ) . '</span>';
											break;
										case 'archived':
											echo '<span class="dwm-badge dwm-badge-archived">' . esc_html__( 'Archived', 'dashboard-widget-manager' ) . '</span>';
											break;
										case 'trash':
											echo '<span class="dwm-badge dwm-badge-trashed">' . esc_html__( 'Trashed', 'dashboard-widget-manager' ) . '</span>';
											break;
										default:
											echo '<span class="dwm-badge dwm-badge-disabled">' . esc_html__( 'Draft', 'dashboard-widget-manager' ) . '</span>';
											break;
									}
									?>
									<?php if ( $is_demo ) : ?>
										<span class="dwm-badge dwm-badge-demo"><?php esc_html_e( 'Demo', 'dashboard-widget-manager' ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $allowed_tables ) && ! empty( $widget['sql_query'] ) ) : ?>
										<?php $table_issues = DWM_Validator::validate_query_tables( $widget['sql_query'], $allowed_tables ); ?>
										<?php if ( ! empty( $table_issues ) ) : ?>
											<span class="dwm-table-warning" title="<?php echo esc_attr( implode( ' | ', $table_issues ) ); ?>">
												<span class="dashicons dashicons-warning"></span><?php esc_html_e( 'Table not whitelisted', 'dashboard-widget-manager' ); ?>
											</span>
										<?php endif; ?>
									<?php endif; ?>
								</div>
								<div class="dwm-widget-card-actions">
									<label class="dwm-toggle">
										<input type="checkbox" class="dwm-toggle-widget"
											data-widget-id="<?php echo esc_attr( $widget['id'] ); ?>"
											<?php checked( $widget['enabled'], 1 ); ?>>
										<span class="dwm-toggle-slider"></span>
									</label>
									<button class="dwm-icon-button dwm-icon-button-edit dwm-edit-widget" data-widget-id="<?php echo esc_attr( $widget['id'] ); ?>" title="<?php esc_attr_e( 'Edit', 'dashboard-widget-manager' ); ?>">
										<span class="dashicons dashicons-edit"></span>
									</button>
									<button class="dwm-icon-button dwm-icon-button-preview dwm-preview-widget" data-widget-id="<?php echo esc_attr( $widget['id'] ); ?>" title="<?php esc_attr_e( 'Preview', 'dashboard-widget-manager' ); ?>">
										<span class="dashicons dashicons-visibility"></span>
									</button>
									<button class="dwm-icon-button dwm-icon-button-danger dwm-delete-widget" data-widget-id="<?php echo esc_attr( $widget['id'] ); ?>" title="<?php esc_attr_e( 'Delete', 'dashboard-widget-manager' ); ?>">
										<span class="dashicons dashicons-trash"></span>
									</button>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<div id="dwm-empty-trash-wrapper" style="display:none; padding: 12px 20px;">
					<button type="button" id="dwm-empty-trash-btn" class="dwm-button dwm-button-danger">
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Empty Trash', 'dashboard-widget-manager' ); ?>
					</button>
				</div>
			<?php else : ?>
				<div class="dwm-empty-state">
					<p><?php esc_html_e( 'No widgets found. Create your first widget to get started!', 'dashboard-widget-manager' ); ?></p>
					<p><a href="#" class="dwm-button dwm-button-primary dwm-create-widget"><?php esc_html_e( 'Create New Widget', 'dashboard-widget-manager' ); ?></a></p>
				</div>
			<?php endif; ?>
		</div>
	</div>

</div>

<!-- Widget Editor Modal -->
<div id="dwm-widget-editor-modal" class="dwm-modal dwm-modal-lg">
	<div class="dwm-modal-overlay"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2 id="dwm-editor-title">
				<span class="dashicons dashicons-edit"></span>
				<?php esc_html_e( 'Widget Editor', 'dashboard-widget-manager' ); ?>
				<button type="button" class="dwm-switch-to-wizard" id="dwm-switch-to-wizard" style="display:none" title="<?php esc_attr_e( 'Switch to Wizard', 'dashboard-widget-manager' ); ?>">
					<span class="dashicons dashicons-lightbulb"></span>
				</button>
			</h2>
			<button type="button" class="dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">

			<!-- Step 1: Creation Method (create mode only) -->
			<div id="dwm-creation-method-step" class="dwm-creation-method-step" style="display:none">
				<div class="dwm-creation-method-intro">
					<h3><?php esc_html_e( 'Choose a Widget creation method.', 'dashboard-widget-manager' ); ?></h3>
					<p><?php esc_html_e( 'Select the Wizard or From Scratch method, for advanced users.', 'dashboard-widget-manager' ); ?></p>
				</div>
				<div class="dwm-creation-method-options">
					<label class="dwm-creation-method-option">
						<input type="radio" name="dwm_creation_method" value="wizard">
						<span class="dwm-creation-method-card">
							<span class="dashicons dashicons-lightbulb"></span>
							<span class="dwm-creation-method-name"><?php esc_html_e( 'Widget Wizard', 'dashboard-widget-manager' ); ?></span>
							<span class="dwm-creation-method-subtitle"><?php esc_html_e( 'No Code', 'dashboard-widget-manager' ); ?></span>
						</span>
					</label>
					<label class="dwm-creation-method-option">
						<input type="radio" name="dwm_creation_method" value="scratch">
						<span class="dwm-creation-method-card">
							<span class="dashicons dashicons-editor-code"></span>
							<span class="dwm-creation-method-name"><?php esc_html_e( 'From Scratch', 'dashboard-widget-manager' ); ?></span>
							<span class="dwm-creation-method-subtitle"><?php esc_html_e( 'Advanced', 'dashboard-widget-manager' ); ?></span>
						</span>
					</label>
				</div>
			</div>

			<!-- Wizard Steps Container -->
			<div id="dwm-wizard-container" class="dwm-wizard-container">

				<!-- Wizard Step 1: Widget Name -->
				<div class="dwm-wizard-step" data-wizard-step="1">
					<div class="dwm-wizard-step-header">
						<h3><?php esc_html_e( 'Name Your Widget', 'dashboard-widget-manager' ); ?></h3>
						<p><?php esc_html_e( 'Give your widget a name that will appear on the dashboard.', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-wizard-step-body">
						<div class="dwm-form-group">
							<label for="dwm-wizard-widget-name"><?php esc_html_e( 'Widget Name', 'dashboard-widget-manager' ); ?> *</label>
							<input type="text" id="dwm-wizard-widget-name" class="dwm-input-text" placeholder="<?php esc_attr_e( 'e.g. Recent Posts Overview', 'dashboard-widget-manager' ); ?>">
						</div>
						<div class="dwm-form-group">
							<label for="dwm-wizard-widget-description"><?php esc_html_e( 'Description', 'dashboard-widget-manager' ); ?></label>
							<textarea id="dwm-wizard-widget-description" class="dwm-input-text" rows="5" placeholder="<?php esc_attr_e( 'Briefly describe what this widget displays', 'dashboard-widget-manager' ); ?>"></textarea>
						</div>
					</div>
					</div>

				<!-- Wizard Step 2: Display Mode -->
				<div class="dwm-wizard-step" data-wizard-step="2">
					<div class="dwm-wizard-step-header">
						<h3><?php esc_html_e( 'Choose a Display for', 'dashboard-widget-manager' ); ?> <span id="dwm-wizard-step2-widget-name"></span></h3>
						<p><?php esc_html_e( 'How would you like your widget data to be displayed?', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-wizard-step-body dwm-wizard-step-body-wide">
						<div class="dwm-wizard-display-modes">
							<label class="dwm-display-mode-option">
								<input type="radio" name="dwm_wizard_display_mode" value="table">
								<span class="dwm-display-mode-card">
									<span class="dashicons dashicons-editor-table"></span>
									<span><?php esc_html_e( 'Table', 'dashboard-widget-manager' ); ?></span>
								</span>
							</label>
							<label class="dwm-display-mode-option">
								<input type="radio" name="dwm_wizard_display_mode" value="bar">
								<span class="dwm-display-mode-card">
									<span class="dashicons dashicons-chart-bar"></span>
									<span><?php esc_html_e( 'Bar Chart', 'dashboard-widget-manager' ); ?></span>
								</span>
							</label>
							<label class="dwm-display-mode-option">
								<input type="radio" name="dwm_wizard_display_mode" value="line">
								<span class="dwm-display-mode-card">
									<span class="dashicons dashicons-chart-line"></span>
									<span><?php esc_html_e( 'Line Chart', 'dashboard-widget-manager' ); ?></span>
								</span>
							</label>
							<label class="dwm-display-mode-option">
								<input type="radio" name="dwm_wizard_display_mode" value="pie">
								<span class="dwm-display-mode-card">
									<span class="dashicons dashicons-chart-pie"></span>
									<span><?php esc_html_e( 'Pie Chart', 'dashboard-widget-manager' ); ?></span>
								</span>
							</label>
							<label class="dwm-display-mode-option">
								<input type="radio" name="dwm_wizard_display_mode" value="doughnut">
								<span class="dwm-display-mode-card">
									<span class="dashicons dashicons-chart-pie"></span>
									<span><?php esc_html_e( 'Doughnut', 'dashboard-widget-manager' ); ?></span>
								</span>
							</label>
						</div>
					</div>
					</div>

				<!-- Wizard Step 3: Display Type Configuration (dynamic content) -->
				<div class="dwm-wizard-step" data-wizard-step="3">
					<div class="dwm-wizard-step-header">
						<h3 id="dwm-wizard-step3-title"><?php esc_html_e( 'Configure Your Widget', 'dashboard-widget-manager' ); ?></h3>
						<p id="dwm-wizard-step3-desc"><?php esc_html_e( 'Set up the options for your chosen display type.', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-wizard-step-body dwm-wizard-step-body-wide" id="dwm-wizard-step3-content">
					</div>
					</div>

				<!-- Wizard Step 4: Join Tables (optional) -->
				<div class="dwm-wizard-step" data-wizard-step="4">
					<div class="dwm-wizard-step-header">
						<h3 id="dwm-wizard-step4-title"><?php esc_html_e( 'Join Tables', 'dashboard-widget-manager' ); ?></h3>
						<p><?php esc_html_e( 'Optionally join additional tables to enrich your widget data.', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-wizard-step-body dwm-wizard-step-body-wide">
						<div id="dwm-wizard-joins-list"></div>
							<button type="button" class="dwm-button dwm-button-primary" id="dwm-wizard-add-join">
								<span class="dashicons dashicons-plus-alt2"></span>
								<?php esc_html_e( 'Add Join', 'dashboard-widget-manager' ); ?>
							</button>
					</div>
					</div>

				<!-- Wizard Step 5: Filters (WHERE) -->
				<div class="dwm-wizard-step dwm-wizard-conditions-step" data-wizard-step="5">
					<div class="dwm-wizard-step-header">
						<h3><?php esc_html_e( 'Filter Your Data', 'dashboard-widget-manager' ); ?></h3>
						<p><?php esc_html_e( 'Optionally add WHERE conditions to narrow down the results.', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-wizard-step-body dwm-wizard-step-body-wide">
						<div id="dwm-wizard-conditions-list"></div>
						<button type="button" class="dwm-button dwm-button-primary" id="dwm-wizard-add-condition">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php esc_html_e( 'Add Filter', 'dashboard-widget-manager' ); ?>
						</button>
					</div>
					</div>

				<!-- Wizard Step 6: Order Results -->
				<div class="dwm-wizard-step dwm-wizard-orders-step" data-wizard-step="6">
					<div class="dwm-wizard-step-header">
						<h3><?php esc_html_e( 'Order Results', 'dashboard-widget-manager' ); ?></h3>
						<p><?php esc_html_e( 'Optionally order your results by one or more columns.', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-wizard-step-body dwm-wizard-step-body-wide">
						<div id="dwm-wizard-orders-list"></div>
						<button type="button" class="dwm-button dwm-button-primary" id="dwm-wizard-add-order">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php esc_html_e( 'Add Order', 'dashboard-widget-manager' ); ?>
						</button>
					</div>
				</div>

				<!-- Wizard Step 7: Limit Results -->
				<div class="dwm-wizard-step" data-wizard-step="7">
					<div id="dwm-wizard-step7-content"></div>
				</div>

				<!-- Wizard Step 8: Caching Configuration -->
				<div class="dwm-wizard-step" data-wizard-step="8">
					<div id="dwm-wizard-step8-content"></div>
				</div>

				<!-- Wizard Step 9: Column Aliases & Ordering -->
				<div class="dwm-wizard-step" data-wizard-step="9">
					<div class="dwm-wizard-step-header">
						<h3><?php esc_html_e( 'Column Names & Ordering', 'dashboard-widget-manager' ); ?></h3>
						<p><?php esc_html_e( 'Customize column display names and reorder columns in your widget output.', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-wizard-step-body">

						<!-- Column Configuration List -->
						<div class="dwm-step8-section">
							<div id="dwm-wizard-columns-config-list" class="dwm-wizard-columns-config-list">
								<!-- Will be populated dynamically with selected columns -->
							</div>
						</div>

					</div>
				</div>

				<!-- Wizard Step 10: Theme Selection -->
				<div class="dwm-wizard-step" data-wizard-step="10">
					<div class="dwm-wizard-step-header">
						<h3><?php esc_html_e( 'Choose Table Theme', 'dashboard-widget-manager' ); ?></h3>
						<p><?php esc_html_e( 'Select a pre-designed theme to style your widget table.', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-wizard-step-body">

						<!-- Theme Selection Grid -->
						<div class="dwm-step9-themes-grid">

							<!-- Default Theme -->
							<label class="dwm-theme-option">
								<input type="radio" name="dwm_wizard_theme" value="default" checked>
								<div class="dwm-theme-preview">
									<div class="dwm-theme-preview-header">
										<span class="dwm-theme-name"><?php esc_html_e( 'Default', 'dashboard-widget-manager' ); ?></span>
										<span class="dashicons dashicons-saved dwm-theme-check"></span>
									</div>
									<div class="dwm-theme-preview-table dwm-theme-default">
										<div class="dwm-theme-preview-row dwm-theme-preview-header-row">
											<span><?php esc_html_e( 'Column 1', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Column 2', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Column 3', 'dashboard-widget-manager' ); ?></span>
										</div>
										<div class="dwm-theme-preview-row">
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
										</div>
										<div class="dwm-theme-preview-row">
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
										</div>
									</div>
								</div>
							</label>

							<!-- Minimal Theme -->
							<label class="dwm-theme-option">
								<input type="radio" name="dwm_wizard_theme" value="minimal">
								<div class="dwm-theme-preview">
									<div class="dwm-theme-preview-header">
										<span class="dwm-theme-name"><?php esc_html_e( 'Minimal', 'dashboard-widget-manager' ); ?></span>
										<span class="dashicons dashicons-saved dwm-theme-check"></span>
									</div>
									<div class="dwm-theme-preview-table dwm-theme-minimal">
										<div class="dwm-theme-preview-row dwm-theme-preview-header-row">
											<span><?php esc_html_e( 'Column 1', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Column 2', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Column 3', 'dashboard-widget-manager' ); ?></span>
										</div>
										<div class="dwm-theme-preview-row">
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
										</div>
										<div class="dwm-theme-preview-row">
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
										</div>
									</div>
								</div>
							</label>

							<!-- Dark Theme -->
							<label class="dwm-theme-option">
								<input type="radio" name="dwm_wizard_theme" value="dark">
								<div class="dwm-theme-preview">
									<div class="dwm-theme-preview-header">
										<span class="dwm-theme-name"><?php esc_html_e( 'Dark', 'dashboard-widget-manager' ); ?></span>
										<span class="dashicons dashicons-saved dwm-theme-check"></span>
									</div>
									<div class="dwm-theme-preview-table dwm-theme-dark">
										<div class="dwm-theme-preview-row dwm-theme-preview-header-row">
											<span><?php esc_html_e( 'Column 1', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Column 2', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Column 3', 'dashboard-widget-manager' ); ?></span>
										</div>
										<div class="dwm-theme-preview-row">
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
										</div>
										<div class="dwm-theme-preview-row">
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
										</div>
									</div>
								</div>
							</label>

							<!-- Striped Theme -->
							<label class="dwm-theme-option">
								<input type="radio" name="dwm_wizard_theme" value="striped">
								<div class="dwm-theme-preview">
									<div class="dwm-theme-preview-header">
										<span class="dwm-theme-name"><?php esc_html_e( 'Striped', 'dashboard-widget-manager' ); ?></span>
										<span class="dashicons dashicons-saved dwm-theme-check"></span>
									</div>
									<div class="dwm-theme-preview-table dwm-theme-striped">
										<div class="dwm-theme-preview-row dwm-theme-preview-header-row">
											<span><?php esc_html_e( 'Column 1', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Column 2', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Column 3', 'dashboard-widget-manager' ); ?></span>
										</div>
										<div class="dwm-theme-preview-row">
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
										</div>
										<div class="dwm-theme-preview-row">
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
										</div>
									</div>
								</div>
							</label>

							<!-- Bordered Theme -->
							<label class="dwm-theme-option">
								<input type="radio" name="dwm_wizard_theme" value="bordered">
								<div class="dwm-theme-preview">
									<div class="dwm-theme-preview-header">
										<span class="dwm-theme-name"><?php esc_html_e( 'Bordered', 'dashboard-widget-manager' ); ?></span>
										<span class="dashicons dashicons-saved dwm-theme-check"></span>
									</div>
									<div class="dwm-theme-preview-table dwm-theme-bordered">
										<div class="dwm-theme-preview-row dwm-theme-preview-header-row">
											<span><?php esc_html_e( 'Column 1', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Column 2', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Column 3', 'dashboard-widget-manager' ); ?></span>
										</div>
										<div class="dwm-theme-preview-row">
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
										</div>
										<div class="dwm-theme-preview-row">
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
											<span><?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?></span>
										</div>
									</div>
								</div>
							</label>

						</div>

					</div>
				</div>

			</div>

			<?php require_once DWM_PLUGIN_DIR . 'templates/admin/widget-editor.php'; ?>
		</div>
		<!-- Shared Wizard Footer (sibling of modal body, shown only during wizard) -->
		<div id="dwm-wizard-footer" class="dwm-wizard-footer" style="display:none">
			<div class="dwm-wizard-footer-left">
				<button type="button" class="dwm-wizard-start-over"><?php esc_html_e( 'Start Over', 'dashboard-widget-manager' ); ?></button>
				<button type="button" class="dwm-wizard-preview" title="<?php esc_attr_e( 'Preview widget', 'dashboard-widget-manager' ); ?>">
					<span class="dashicons dashicons-visibility"></span>
					<?php esc_html_e( 'Preview', 'dashboard-widget-manager' ); ?>
				</button>
			</div>
			<div class="dwm-wizard-footer-right">
				<button type="button" class="dwm-wizard-back"><?php esc_html_e( 'Back', 'dashboard-widget-manager' ); ?></button>
				<button type="button" class="dwm-wizard-next"><?php esc_html_e( 'Next', 'dashboard-widget-manager' ); ?></button>
			</div>
		</div>
		<div class="dwm-modal-footer">
			<button type="submit" form="dwm-widget-form" class="dwm-button dwm-button-primary" id="dwm-save-widget" disabled>
				<?php esc_html_e( 'Save Widget', 'dashboard-widget-manager' ); ?>
			</button>
		</div>
	</div>
</div>

<!-- Preview Modal -->
<div id="dwm-preview-modal" class="dwm-modal dwm-modal-lg">
	<div class="dwm-modal-overlay"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-visibility"></span>
				<?php esc_html_e( 'Widget Preview', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-preview-tabs">
			<button type="button" class="dwm-preview-tab active" data-tab="ui">
				<span class="dashicons dashicons-admin-appearance"></span>
				<?php esc_html_e( 'Widget UI', 'dashboard-widget-manager' ); ?>
			</button>
			<button type="button" class="dwm-preview-tab" data-tab="query">
				<span class="dashicons dashicons-database"></span>
				<?php esc_html_e( 'SQL Query', 'dashboard-widget-manager' ); ?>
			</button>
			<button type="button" class="dwm-preview-tab" data-tab="output">
				<span class="dashicons dashicons-list-view"></span>
				<?php esc_html_e( 'Output', 'dashboard-widget-manager' ); ?>
			</button>
		</div>
		<div class="dwm-modal-body">
			<div id="dwm-preview-ui-content" class="dwm-preview-tab-content active"></div>
			<div id="dwm-preview-query-content" class="dwm-preview-tab-content"></div>
			<div id="dwm-preview-output-content" class="dwm-preview-tab-content"></div>
		</div>
	</div>
</div>

<!-- Confirm Switch to Wizard Modal -->
<div id="dwm-confirm-wizard-modal" class="dwm-modal dwm-modal-sm">
	<div class="dwm-modal-overlay dwm-confirm-wizard-close"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-lightbulb"></span>
				<?php esc_html_e( 'Switch to Wizard', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-confirm-wizard-close dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<p><?php esc_html_e( 'Switch to Wizard mode? This will reset your current form data.', 'dashboard-widget-manager' ); ?></p>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-secondary dwm-confirm-wizard-close"><?php esc_html_e( 'Cancel', 'dashboard-widget-manager' ); ?></button>
			<button type="button" class="dwm-button dwm-button-primary" id="dwm-confirm-wizard-yes"><?php esc_html_e( 'Switch to Wizard', 'dashboard-widget-manager' ); ?></button>
		</div>
	</div>
</div>

<!-- Confirm Table Change Modal -->
<div id="dwm-confirm-table-change-modal" class="dwm-modal dwm-modal-sm">
	<div class="dwm-modal-overlay dwm-confirm-table-change-close"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Change Primary Table?', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-confirm-table-change-close dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<p><?php esc_html_e( 'Changing the primary table will clear your selected columns and any other configuration. You will start over from the table selection stage.', 'dashboard-widget-manager' ); ?></p>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-secondary dwm-confirm-table-change-close"><?php esc_html_e( 'Cancel', 'dashboard-widget-manager' ); ?></button>
			<button type="button" class="dwm-button dwm-button-primary" id="dwm-confirm-table-change-yes"><?php esc_html_e( 'Change Table', 'dashboard-widget-manager' ); ?></button>
		</div>
	</div>
</div>

<!-- Confirm Display Type Change Modal -->
<div id="dwm-confirm-display-type-change-modal" class="dwm-modal dwm-modal-sm">
	<div class="dwm-modal-overlay dwm-confirm-display-type-change-close"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Change Display Type?', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-confirm-display-type-change-close dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<p><?php esc_html_e( 'Changing the display type will clear all configuration for the current display type, including table selection, columns, joins, conditions, and sorting. Are you sure you want to continue?', 'dashboard-widget-manager' ); ?></p>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-secondary dwm-confirm-display-type-change-close"><?php esc_html_e( 'Cancel', 'dashboard-widget-manager' ); ?></button>
			<button type="button" class="dwm-button dwm-button-primary" id="dwm-confirm-display-type-change-yes"><?php esc_html_e( 'Change Display Type', 'dashboard-widget-manager' ); ?></button>
		</div>
	</div>
</div>

<!-- No Compatible Columns Warning Modal -->
<div id="dwm-no-compatible-columns-modal" class="dwm-modal dwm-modal-sm">
	<div class="dwm-modal-overlay dwm-no-compatible-columns-close"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'No Compatible Columns', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-no-compatible-columns-close dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<p id="dwm-no-compatible-columns-message"><?php esc_html_e( 'The selected join table has no columns that are compatible with the columns from your primary table.', 'dashboard-widget-manager' ); ?></p>
			<p><?php esc_html_e( 'Would you like to:', 'dashboard-widget-manager' ); ?></p>
			<ul style="margin-left: 20px;">
				<li><?php esc_html_e( 'Go back to select different columns for your primary table', 'dashboard-widget-manager' ); ?></li>
				<li><?php esc_html_e( 'Choose a different table to join with', 'dashboard-widget-manager' ); ?></li>
			</ul>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-secondary" id="dwm-no-compatible-choose-different"><?php esc_html_e( 'Choose Different Table', 'dashboard-widget-manager' ); ?></button>
			<button type="button" class="dwm-button dwm-button-primary" id="dwm-no-compatible-go-back"><?php esc_html_e( 'Go Back to Column Selection', 'dashboard-widget-manager' ); ?></button>
		</div>
	</div>
</div>

<!-- Confirm Close Editor Modal -->
<div id="dwm-confirm-close-editor-modal" class="dwm-modal dwm-modal-sm">
	<div class="dwm-modal-overlay dwm-confirm-close-editor-cancel"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Close Widget Editor?', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-confirm-close-editor-cancel dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<p><?php esc_html_e( 'Are you sure you want to close the widget editor? All unsaved changes will be lost.', 'dashboard-widget-manager' ); ?></p>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-secondary dwm-confirm-close-editor-cancel"><?php esc_html_e( 'Cancel', 'dashboard-widget-manager' ); ?></button>
			<button type="button" class="dwm-button dwm-button-danger" id="dwm-confirm-close-editor-yes"><?php esc_html_e( 'Close Editor', 'dashboard-widget-manager' ); ?></button>
		</div>
	</div>
</div>

<!-- Confirm Start Over Modal -->
<div id="dwm-confirm-start-over-modal" class="dwm-modal dwm-modal-sm">
	<div class="dwm-modal-overlay dwm-confirm-start-over-cancel"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Start Over?', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-confirm-start-over-cancel dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<p><?php esc_html_e( 'Are you sure you want to start over? All progress in this wizard will be lost and you will return to the beginning.', 'dashboard-widget-manager' ); ?></p>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-secondary dwm-confirm-start-over-cancel"><?php esc_html_e( 'Cancel', 'dashboard-widget-manager' ); ?></button>
			<button type="button" class="dwm-button dwm-button-danger" id="dwm-confirm-start-over-yes"><?php esc_html_e( 'Start Over', 'dashboard-widget-manager' ); ?></button>
		</div>
	</div>
</div>

<!-- Join Table Configuration Modal -->
<div id="dwm-join-config-modal" class="dwm-modal dwm-modal-lg">
	<div class="dwm-modal-overlay"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-networking"></span>
				<span id="dwm-join-config-title"><?php esc_html_e( 'Configure Join Table', 'dashboard-widget-manager' ); ?></span>
			</h2>
			<button type="button" class="dwm-modal-close dwm-join-config-cancel" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<div class="dwm-join-config-form">
				<!-- Row 1: Join Type and Join Table -->
				<div class="dwm-join-config-grid">
					<div class="dwm-form-group">
						<label for="dwm-join-config-type"><?php esc_html_e( 'Join Type', 'dashboard-widget-manager' ); ?> *</label>
						<select id="dwm-join-config-type" class="dwm-select">
							<option value="">— <?php esc_html_e( 'Select Join', 'dashboard-widget-manager' ); ?> —</option>
							<option value="LEFT">Left Join</option>
							<option value="RIGHT">Right Join</option>
							<option value="INNER">Inner Join</option>
						</select>
					</div>
					<div class="dwm-join-spacer"></div>
					<div class="dwm-form-group">
						<label for="dwm-join-config-table"><?php esc_html_e( 'Join Table', 'dashboard-widget-manager' ); ?> *</label>
						<select id="dwm-join-config-table" class="dwm-select">
							<option value="">— <?php esc_html_e( 'select table', 'dashboard-widget-manager' ); ?> —</option>
						</select>
					</div>
				</div>

				<!-- ON Separator -->
				<div class="dwm-join-on-separator">
					<span class="dwm-join-on-text">ON</span>
				</div>

				<!-- Row 2: Column matching with = -->
				<div class="dwm-join-config-columns-row">
					<div class="dwm-form-group">
						<label for="dwm-join-config-local"><?php esc_html_e( 'Primary Table Column', 'dashboard-widget-manager' ); ?> *</label>
						<select id="dwm-join-config-local" class="dwm-select">
							<option value="">— <?php esc_html_e( 'select column', 'dashboard-widget-manager' ); ?> —</option>
						</select>
					</div>
					<div class="dwm-join-equals-sign">=</div>
					<div class="dwm-form-group">
						<label for="dwm-join-config-foreign"><?php esc_html_e( 'Join Table Column', 'dashboard-widget-manager' ); ?> *</label>
						<select id="dwm-join-config-foreign" class="dwm-select">
							<option value="">— <?php esc_html_e( 'select column', 'dashboard-widget-manager' ); ?> —</option>
						</select>
					</div>
				</div>
				<div class="dwm-join-preview-section">
					<div class="dwm-join-preview-tabs">
						<button type="button" class="dwm-join-preview-tab active" data-tab="query">
							<span class="dashicons dashicons-database"></span>
							<?php esc_html_e( 'Query Preview', 'dashboard-widget-manager' ); ?>
						</button>
						<button type="button" class="dwm-join-preview-tab" data-tab="output" disabled>
							<span class="dashicons dashicons-list-view"></span>
							<?php esc_html_e( 'Output Preview', 'dashboard-widget-manager' ); ?>
						</button>
					</div>
					<div id="dwm-join-validation-status" class="dwm-join-validation-status" style="display:none"></div>
					<div id="dwm-join-preview-query-pane" class="dwm-join-preview-pane active">
						<div id="dwm-join-query-preview-content" class="dwm-join-query-preview-content empty">
							<?php esc_html_e( 'Preview will appear after selections are made above.', 'dashboard-widget-manager' ); ?>
						</div>
					</div>
					<div id="dwm-join-preview-output-pane" class="dwm-join-preview-pane">
						<div id="dwm-join-output-preview-content" class="dwm-join-output-preview-content"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-primary" id="dwm-join-config-save" disabled><?php esc_html_e( 'Save Join', 'dashboard-widget-manager' ); ?></button>
		</div>
	</div>
</div>

<!-- Filter Configuration Modal -->
<div id="dwm-filter-config-modal" class="dwm-modal dwm-modal-lg">
	<div class="dwm-modal-overlay"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-filter"></span>
				<span id="dwm-filter-config-title"><?php esc_html_e( 'Configure Filter', 'dashboard-widget-manager' ); ?></span>
			</h2>
			<button type="button" class="dwm-modal-close dwm-filter-config-cancel" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<div class="dwm-filter-config-form">
				<!-- WHERE Separator at Top -->
				<div class="dwm-filter-where-separator dwm-filter-where-top">
					<span class="dwm-filter-where-text">WHERE</span>
				</div>

				<!-- Row: Column, Operator, Value -->
				<div class="dwm-filter-config-grid">
					<div class="dwm-form-group">
						<label for="dwm-filter-config-column"><?php esc_html_e( 'Column', 'dashboard-widget-manager' ); ?> *</label>
						<select id="dwm-filter-config-column" class="dwm-select">
							<option value="">— <?php esc_html_e( 'select column', 'dashboard-widget-manager' ); ?> —</option>
						</select>
					</div>
					<div class="dwm-form-group">
						<label for="dwm-filter-config-operator"><?php esc_html_e( 'Operator', 'dashboard-widget-manager' ); ?> *</label>
						<select id="dwm-filter-config-operator" class="dwm-select" disabled>
							<option value="">— <?php esc_html_e( 'select operator', 'dashboard-widget-manager' ); ?> —</option>
							<option value="=">=</option>
							<option value="!=">!=</option>
							<option value="<">&lt;</option>
							<option value=">">&gt;</option>
							<option value="<=">&lt;=</option>
							<option value=">=">&gt;=</option>
							<option value="LIKE">LIKE</option>
							<option value="NOT LIKE">NOT LIKE</option>
							<option value="IN">IN</option>
							<option value="NOT IN">NOT IN</option>
							<option value="IS NULL">IS NULL</option>
							<option value="IS NOT NULL">IS NOT NULL</option>
						</select>
					</div>
					<div class="dwm-form-group" id="dwm-filter-value-group">
						<label for="dwm-filter-config-value"><?php esc_html_e( 'Value', 'dashboard-widget-manager' ); ?> *</label>
						<div id="dwm-filter-value-container">
							<input type="text" id="dwm-filter-config-value" class="dwm-input-text" placeholder="<?php esc_attr_e( 'Enter value', 'dashboard-widget-manager' ); ?>" disabled>
						</div>
						<div id="dwm-filter-value-error" class="dwm-validation-error" style="display: none;"></div>
					</div>
				</div>

				<div class="dwm-filter-preview-section">
					<div class="dwm-filter-preview-tabs">
						<button type="button" class="dwm-filter-preview-tab active" data-tab="query">
							<span class="dashicons dashicons-database"></span>
							<?php esc_html_e( 'Query Preview', 'dashboard-widget-manager' ); ?>
						</button>
						<button type="button" class="dwm-filter-preview-tab" data-tab="output" disabled>
							<span class="dashicons dashicons-list-view"></span>
							<?php esc_html_e( 'Output Preview', 'dashboard-widget-manager' ); ?>
						</button>
					</div>
					<div id="dwm-filter-validation-status" class="dwm-filter-validation-status" style="display:none"></div>
					<div id="dwm-filter-preview-query-pane" class="dwm-filter-preview-pane active">
						<div id="dwm-filter-query-preview-content" class="dwm-filter-query-preview-content empty">
							<?php esc_html_e( 'Preview will appear after selections are made above.', 'dashboard-widget-manager' ); ?>
						</div>
					</div>
					<div id="dwm-filter-preview-output-pane" class="dwm-filter-preview-pane">
						<div id="dwm-filter-output-preview-content" class="dwm-filter-output-preview-content"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-primary" id="dwm-filter-config-save" disabled><?php esc_html_e( 'Save Filter', 'dashboard-widget-manager' ); ?></button>
		</div>
	</div>
</div>

<!-- Order Configuration Modal -->
<div id="dwm-order-config-modal" class="dwm-modal dwm-modal-lg">
	<div class="dwm-modal-overlay"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-sort"></span>
				<span id="dwm-order-config-title"><?php esc_html_e( 'Configure Order', 'dashboard-widget-manager' ); ?></span>
			</h2>
			<button type="button" class="dwm-modal-close dwm-order-config-cancel" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<div class="dwm-order-config-form">
				<!-- ORDER BY Separator at Top -->
				<div class="dwm-order-by-separator dwm-order-by-top">
					<span class="dwm-order-by-text">ORDER BY</span>
				</div>

				<!-- Row: Column and Direction -->
				<div class="dwm-order-config-grid">
					<div class="dwm-form-group">
						<label for="dwm-order-config-column"><?php esc_html_e( 'Column', 'dashboard-widget-manager' ); ?> *</label>
						<select id="dwm-order-config-column" class="dwm-select">
							<option value="">— <?php esc_html_e( 'select column', 'dashboard-widget-manager' ); ?> —</option>
						</select>
					</div>
					<div class="dwm-form-group">
						<label for="dwm-order-config-direction"><?php esc_html_e( 'Direction', 'dashboard-widget-manager' ); ?> *</label>
						<select id="dwm-order-config-direction" class="dwm-select">
							<option value="DESC"><?php esc_html_e( 'Newest First (DESC)', 'dashboard-widget-manager' ); ?></option>
							<option value="ASC"><?php esc_html_e( 'Oldest First (ASC)', 'dashboard-widget-manager' ); ?></option>
						</select>
					</div>
				</div>

				<div class="dwm-order-preview-section">
					<div class="dwm-order-preview-tabs">
						<button type="button" class="dwm-order-preview-tab active" data-tab="query">
							<span class="dashicons dashicons-database"></span>
							<?php esc_html_e( 'Query Preview', 'dashboard-widget-manager' ); ?>
						</button>
						<button type="button" class="dwm-order-preview-tab" data-tab="output" disabled>
							<span class="dashicons dashicons-list-view"></span>
							<?php esc_html_e( 'Output Preview', 'dashboard-widget-manager' ); ?>
						</button>
					</div>
					<div id="dwm-order-validation-status" class="dwm-order-validation-status" style="display:none"></div>
					<div id="dwm-order-preview-query-pane" class="dwm-order-preview-pane active">
						<div id="dwm-order-query-preview-content" class="dwm-order-query-preview-content empty">
							<?php esc_html_e( 'Select column and direction to preview ORDER BY clause.', 'dashboard-widget-manager' ); ?>
						</div>
					</div>
					<div id="dwm-order-preview-output-pane" class="dwm-order-preview-pane">
						<div id="dwm-order-output-preview-content" class="dwm-order-output-preview-content"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-primary" id="dwm-order-config-save" disabled><?php esc_html_e( 'Save Order', 'dashboard-widget-manager' ); ?></button>
		</div>
	</div>
</div>

<!-- Validation Error Modal -->
<div id="dwm-validation-error-modal" class="dwm-modal dwm-modal-md">
	<div class="dwm-modal-overlay dwm-validation-error-close"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Validation Error', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-validation-error-close dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<div class="dwm-validation-error-content">
				<p id="dwm-validation-error-message" class="dwm-validation-error-text"></p>
				<div id="dwm-validation-error-suggestion" class="dwm-validation-suggestion" style="display: none;">
					<strong><?php esc_html_e( 'Suggestion:', 'dashboard-widget-manager' ); ?></strong>
					<p id="dwm-validation-error-suggestion-text"></p>
				</div>
				<div id="dwm-validation-error-details" class="dwm-validation-details" style="display: none;">
					<strong><?php esc_html_e( 'Details:', 'dashboard-widget-manager' ); ?></strong>
					<pre id="dwm-validation-error-details-text"></pre>
				</div>
			</div>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-primary dwm-validation-error-close"><?php esc_html_e( 'OK', 'dashboard-widget-manager' ); ?></button>
		</div>
	</div>
</div>

<?php include __DIR__ . '/partials/page-wrapper-end.php'; ?>
