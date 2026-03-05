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
$excluded_tables = $data->get_excluded_tables();

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

$dwm_display_mode_labels = array(
	'table'    => __( 'Table', 'dashboard-widget-manager' ),
	'list'     => __( 'List', 'dashboard-widget-manager' ),
	'button'   => __( 'Button', 'dashboard-widget-manager' ),
	'card-list'=> __( 'Card List', 'dashboard-widget-manager' ),
	'bar'      => __( 'Bar Chart', 'dashboard-widget-manager' ),
	'line'     => __( 'Line Chart', 'dashboard-widget-manager' ),
	'pie'      => __( 'Pie Chart', 'dashboard-widget-manager' ),
	'doughnut' => __( 'Doughnut Chart', 'dashboard-widget-manager' ),
	'manual'   => __( 'Manual', 'dashboard-widget-manager' ),
);

$dwm_format_slug = static function( $slug ) {
	$slug = trim( (string) $slug );
	if ( '' === $slug ) {
		return __( 'Default', 'dashboard-widget-manager' );
	}
	return ucwords( str_replace( array( '-', '_' ), ' ', $slug ) );
};

$dwm_is_generated_template = static function( $template, $mode ) {
	$template = trim( (string) $template );
	if ( '' === $template ) {
		return true;
	}

	$mode_markers = array(
		'table'    => array( 'class="dwm-table"', '<?php if ( ! empty( $query_results ) ) : ?>' ),
		'list'     => array( 'class="dwm-list"', '<?php if ( ! empty( $query_results ) ) : ?>' ),
		'button'   => array( 'class="dwm-button-grid"', '<?php if ( ! empty( $query_results ) ) : ?>' ),
		'card-list'=> array( 'class="dwm-card-list"', '<?php if ( ! empty( $query_results ) ) : ?>' ),
		'bar'      => array( 'class="dwm-chart-summary"', 'data-chart-mode="Bar"' ),
		'line'     => array( 'class="dwm-chart-summary"', 'data-chart-mode="Line"' ),
		'pie'      => array( 'class="dwm-chart-summary"', 'data-chart-mode="Pie"' ),
		'doughnut' => array( 'class="dwm-chart-summary"', 'data-chart-mode="Doughnut"' ),
	);

	if ( ! isset( $mode_markers[ $mode ] ) ) {
		return false;
	}

	foreach ( $mode_markers[ $mode ] as $marker ) {
		if ( false === strpos( $template, $marker ) ) {
			return false;
		}
	}

	return true;
};

// Page wrapper configuration.
$current_page       = 'dashboard-widget-manager';
$header_title       = __( 'Widget Manager', 'dashboard-widget-manager' );
$header_description = __( 'Build widgets with the visual builder or wizard, filter and search your library, preview results, and publish updates to the WordPress dashboard.', 'dashboard-widget-manager' );
$topbar_actions     = [];

include __DIR__ . '/partials/page-wrapper-start.php';
?>

<div class="dwm-page-content">

		<div class="dwm-section" id="dwm-list-section">
			<div class="dwm-section-header">
				<?php if ( ! empty( $widgets ) ) : ?>
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
				<?php endif; ?>
				<a href="#" class="dwm-button dwm-button-primary dwm-create-widget"><span class="dashicons dashicons-plus-alt2"></span><?php esc_html_e( 'Create New Widget', 'dashboard-widget-manager' ); ?></a>
			</div>

			<?php if ( ! empty( $widgets ) ) : ?>
			<div class="dwm-manage-controls-row">
			<div class="dwm-manage-controls-row__search">
				<div class="dwm-search-wrapper">
					<input type="text" id="dwm-search-input" class="dwm-search-input" placeholder="<?php esc_attr_e( 'Search widgets...', 'dashboard-widget-manager' ); ?>" />
					<button type="button" id="dwm-search-icon" class="dwm-search-icon" aria-label="<?php esc_attr_e( 'Search', 'dashboard-widget-manager' ); ?>">
						<span class="dashicons dashicons-search"></span>
					</button>
				</div>
			</div>
			<button type="button" class="dwm-button dwm-button-primary" data-open-modal="dwm-filters-modal">
				<span class="dashicons dashicons-filter"></span>
				<?php esc_html_e( 'Filters', 'dashboard-widget-manager' ); ?>
			</button>
		</div>
		<?php endif; ?>

		<div class="dwm-widgets-list">
			<?php if ( ! empty( $widgets ) ) : ?>
					<div class="dwm-widget-cards" id="dwm-widgets-table">
					<?php foreach ( $widgets as $widget ) :
						$_valid_statuses = array( 'publish', 'draft', 'archived', 'trash' );
					$widget_status   = in_array( $widget['status'] ?? '', $_valid_statuses, true ) ? $widget['status'] : 'draft';
						$is_demo        = (int) $widget['is_demo'] === 1;
						$created_date   = date_i18n( 'M j, y', strtotime( $widget['created_at'] ) );
						$modified_date  = date_i18n( 'M j, y', strtotime( $widget['updated_at'] ) );
						$first_active   = ! empty( $widget['first_published_at'] ) ? date_i18n( 'M j, y', strtotime( $widget['first_published_at'] ) ) : '';
						$author_name    = __( 'Demo Data', 'dashboard-widget-manager' );
						$chart_type_raw = $widget['chart_type'] ?? '';
						$builder_config = array();
						$output_config  = array();
						if ( ! empty( $widget['builder_config'] ) ) {
							$decoded_builder = json_decode( (string) $widget['builder_config'], true );
							if ( is_array( $decoded_builder ) ) {
								$builder_config = $decoded_builder;
							}
						}
						if ( ! empty( $widget['output_config'] ) ) {
							$decoded_output = json_decode( (string) $widget['output_config'], true );
							if ( is_array( $decoded_output ) ) {
								$output_config = $decoded_output;
							}
						} elseif ( ! empty( $builder_config['output_config'] ) && is_array( $builder_config['output_config'] ) ) {
							$output_config = $builder_config['output_config'];
						}

						$display_mode = $output_config['display_mode']
							?? $builder_config['display_mode']
							?? ( $chart_type_raw !== '' ? $chart_type_raw : 'manual' );
						if ( ! isset( $dwm_display_mode_labels[ $display_mode ] ) ) {
							$display_mode = 'manual';
						}
						$display_mode_label = $dwm_display_mode_labels[ $display_mode ] ?? $dwm_format_slug( $display_mode );
						$cache_status_label = ! empty( $widget['enable_caching'] )
							? __( 'Enabled', 'dashboard-widget-manager' )
							: __( 'Disabled', 'dashboard-widget-manager' );
						$is_chart_mode = in_array( $display_mode, array( 'bar', 'line', 'pie', 'doughnut' ), true );
						$resolved_theme = $is_chart_mode
							? ( $output_config['theme'] ?? ( $builder_config['chart_theme'] ?? 'classic' ) )
							: ( $output_config['theme'] ?? ( $builder_config['table_theme'] ?? 'default' ) );
						$is_custom_theme = ! $dwm_is_generated_template( $widget['template'] ?? '', $display_mode );
						$theme_type_label = $is_custom_theme
							? __( 'Custom', 'dashboard-widget-manager' )
							: $dwm_format_slug( $resolved_theme );
						$search_text    = strtolower( $widget['name'] );
						if ( ! $is_demo ) {
							$created_by  = get_userdata( $widget['created_by'] );
							$author_name = $created_by ? $created_by->user_login : __( 'Unknown', 'dashboard-widget-manager' );
						}
					?>
						<div class="dwm-widget-card"
							data-widget-id="<?php echo esc_attr( $widget['id'] ); ?>"
							data-widget-status="<?php echo esc_attr( $widget_status ); ?>"
							data-widget-display="<?php echo esc_attr( $display_mode ); ?>"
							data-widget-search="<?php echo esc_attr( $search_text ); ?>"
							<?php if ( 'archived' === $widget_status || 'trash' === $widget_status ) echo ' style="display:none"'; ?>>

							<?php
							switch ( $widget_status ) {
								case 'publish':
									$status_badge_class = 'dwm-badge-success';
									$status_badge_label = esc_html__( 'Active', 'dashboard-widget-manager' );
									break;
								case 'archived':
									$status_badge_class = 'dwm-badge-archived';
									$status_badge_label = esc_html__( 'Archived', 'dashboard-widget-manager' );
									break;
								case 'trash':
									$status_badge_class = 'dwm-badge-trashed';
									$status_badge_label = esc_html__( 'Trashed', 'dashboard-widget-manager' );
									break;
								default:
									$status_badge_class = 'dwm-badge-disabled';
									$status_badge_label = esc_html__( 'Draft', 'dashboard-widget-manager' );
							}
							$table_issues = [];
							if ( ! empty( $excluded_tables ) && ! empty( $widget['sql_query'] ) ) {
								$table_issues = DWM_Validator::validate_query_tables( $widget['sql_query'], $excluded_tables );
							}
							?>

							<span class="dwm-badge dwm-widget-status-badge <?php echo esc_attr( $status_badge_class ); ?>">
							<span class="dwm-badge-overlay" style="display:none;"><span class="dashicons dashicons-edit"></span></span>
							<?php echo esc_html( $status_badge_label ); ?>
						</span>

							<?php if ( $is_demo ) : ?>
								<span class="dwm-badge dwm-widget-demo-badge"><?php esc_html_e( 'Demo', 'dashboard-widget-manager' ); ?></span>
							<?php endif; ?>

							<div class="dwm-widget-card-header">
								<strong class="dwm-widget-card-name"><?php echo esc_html( $widget['name'] ); ?></strong>
								<?php if ( ! empty( $table_issues ) ) : ?>
									<span class="dwm-table-warning" title="<?php echo esc_attr( implode( ' | ', $table_issues ) ); ?>">
										<span class="dashicons dashicons-warning"></span><?php esc_html_e( 'Table not whitelisted', 'dashboard-widget-manager' ); ?>
									</span>
								<?php endif; ?>
							</div>

							<div class="dwm-widget-card-body">
								<div class="dwm-widget-card-actions">
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

							<div class="dwm-widget-meta-footer">
								<div class="dwm-widget-meta-footer-left">
									<div class="dwm-widget-meta-item">
										<span class="dwm-widget-meta-label"><?php esc_html_e( 'Created:', 'dashboard-widget-manager' ); ?></span>
										<span class="dwm-widget-meta-value"><?php echo esc_html( $created_date ); ?></span>
									</div>
									<div class="dwm-widget-meta-item">
										<span class="dwm-widget-meta-label"><?php esc_html_e( 'Updated:', 'dashboard-widget-manager' ); ?></span>
										<span class="dwm-widget-meta-value"><?php echo esc_html( $modified_date ); ?></span>
									</div>
									<div class="dwm-widget-meta-item">
										<span class="dwm-widget-meta-label"><?php esc_html_e( 'Activated:', 'dashboard-widget-manager' ); ?></span>
										<span class="dwm-widget-meta-value"><?php echo esc_html( $first_active ? $first_active : '—' ); ?></span>
									</div>
									<div class="dwm-widget-meta-item">
										<span class="dwm-widget-meta-label"><?php esc_html_e( 'By:', 'dashboard-widget-manager' ); ?></span>
										<span class="dwm-widget-meta-value"><?php echo esc_html( $author_name ); ?></span>
									</div>
									<div class="dwm-widget-meta-item">
										<span class="dwm-widget-meta-label"><?php esc_html_e( 'Display:', 'dashboard-widget-manager' ); ?></span>
										<span class="dwm-widget-meta-value"><?php echo esc_html( $display_mode_label ); ?></span>
									</div>
									<div class="dwm-widget-meta-item">
										<span class="dwm-widget-meta-label"><?php esc_html_e( 'Cache:', 'dashboard-widget-manager' ); ?></span>
										<span class="dwm-widget-meta-value"><?php echo esc_html( $cache_status_label ); ?></span>
									</div>
									<div class="dwm-widget-meta-item">
										<span class="dwm-widget-meta-label"><?php esc_html_e( 'Theme:', 'dashboard-widget-manager' ); ?></span>
										<span class="dwm-widget-meta-value"><?php echo esc_html( $theme_type_label ); ?></span>
									</div>
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
				<div class="dwm-filter-empty-state" id="dwm-filter-empty-state" style="display: none;">
					<div class="dwm-filter-empty-state-content">
						<span class="dashicons dashicons-filter"></span>
						<p><?php esc_html_e( 'No widgets match your current filter.', 'dashboard-widget-manager' ); ?></p>
						<button type="button" class="dwm-button dwm-button-primary" id="dwm-clear-filters-btn">
							<?php esc_html_e( 'Clear Filters', 'dashboard-widget-manager' ); ?>
						</button>
					</div>
				</div>
			<?php else : ?>
				<div class="dwm-empty-state">
					<p><?php esc_html_e( 'No widgets found. Create your first widget to get started!', 'dashboard-widget-manager' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>

</div>

<!-- Filters Modal -->
<div id="dwm-filters-modal" class="dwm-modal">
	<div class="dwm-modal-overlay"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-filter"></span>
				<?php esc_html_e( 'Filters', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<?php include __DIR__ . '/partials/widget-filters.php'; ?>
		</div>
		<div class="dwm-modal-footer">
			<div class="dwm-modal-footer-buttons">
				<button type="button" class="dwm-button dwm-button-secondary" id="dwm-reset-widget-filters">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Reset', 'dashboard-widget-manager' ); ?>
				</button>
				<button type="button" class="dwm-button dwm-button-primary" id="dwm-apply-widget-filters">
					<span class="dashicons dashicons-yes-alt"></span>
					<?php esc_html_e( 'Apply Filters', 'dashboard-widget-manager' ); ?>
				</button>
			</div>
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
					<p><?php esc_html_e( 'Select the Wizard or Manual method, for advanced users.', 'dashboard-widget-manager' ); ?></p>
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
							<span class="dwm-creation-method-name"><?php esc_html_e( 'Build Manually', 'dashboard-widget-manager' ); ?></span>
							<span class="dwm-creation-method-subtitle"><?php esc_html_e( 'Advanced', 'dashboard-widget-manager' ); ?></span>
						</span>
					</label>
				</div>
			</div>

			<!-- Wizard Steps Container -->
			<div id="dwm-wizard-container" class="dwm-wizard-container">

				<!-- Wizard Step 1: Widget Name -->
				<div class="dwm-wizard-step" data-wizard-step="1">
					<button type="button" class="dwm-wizard-step-help dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-create-widgets" aria-label="<?php esc_attr_e( 'View help for creating widgets', 'dashboard-widget-manager' ); ?>" title="<?php esc_attr_e( 'View help for creating widgets', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
					<div class="dwm-wizard-step-header">
						<div class="dwm-wizard-step-header-title-row">
							<h3><?php esc_html_e( 'Name Your Widget', 'dashboard-widget-manager' ); ?></h3>
						</div>
						<p><?php esc_html_e( 'Give your widget a name that will appear on the dashboard.', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-wizard-step-body">
						<div class="dwm-form-group">
							<label for="dwm-wizard-widget-name"><?php esc_html_e( 'Widget Name', 'dashboard-widget-manager' ); ?> *</label>
							<input type="text" id="dwm-wizard-widget-name" class="dwm-input-text" placeholder="<?php esc_attr_e( 'e.g. Recent Posts Overview', 'dashboard-widget-manager' ); ?>">
						</div>
						<div class="dwm-form-group">
							<label for="dwm-wizard-widget-description"><?php esc_html_e( 'Description', 'dashboard-widget-manager' ); ?><span class="dwm-desc-required-asterisk" style="display:none"> *</span></label>
							<textarea id="dwm-wizard-widget-description" class="dwm-input-text" rows="5" placeholder="<?php esc_attr_e( 'Briefly describe what this widget displays', 'dashboard-widget-manager' ); ?>"></textarea>
							<span class="dwm-desc-display-toggle">
								<label class="dwm-toggle-switch dwm-toggle-switch--sm">
									<input type="checkbox" id="dwm-wizard-show-description" class="dwm-show-description-toggle">
									<span class="dwm-toggle-slider"></span>
								</label>
								<span class="dwm-desc-display-label"><?php esc_html_e( 'Display on Widget', 'dashboard-widget-manager' ); ?></span>
							</span>
						</div>
					</div>
					</div>

				<!-- Wizard Step 2: Display Mode -->
				<div class="dwm-wizard-step" data-wizard-step="2">
					<button type="button" class="dwm-wizard-step-help dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="display-modes-overview" aria-label="<?php esc_attr_e( 'View help for choosing a widget display', 'dashboard-widget-manager' ); ?>" title="<?php esc_attr_e( 'View help for choosing a widget display', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
					<div class="dwm-wizard-step-header">
						<div class="dwm-wizard-step-header-title-row">
							<h3><?php esc_html_e( 'Choose a Display for', 'dashboard-widget-manager' ); ?> <span id="dwm-wizard-step2-widget-name"></span></h3>
						</div>
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
							<label class="dwm-display-mode-option">
								<input type="radio" name="dwm_wizard_display_mode" value="list">
								<span class="dwm-display-mode-card">
									<span class="dashicons dashicons-list-view"></span>
									<span><?php esc_html_e( 'List', 'dashboard-widget-manager' ); ?></span>
								</span>
							</label>
							<label class="dwm-display-mode-option">
								<input type="radio" name="dwm_wizard_display_mode" value="button">
								<span class="dwm-display-mode-card">
									<span class="dashicons dashicons-button"></span>
									<span><?php esc_html_e( 'Buttons', 'dashboard-widget-manager' ); ?></span>
								</span>
							</label>
							<label class="dwm-display-mode-option">
								<input type="radio" name="dwm_wizard_display_mode" value="card-list">
								<span class="dwm-display-mode-card">
									<span class="dashicons dashicons-screenoptions"></span>
									<span><?php esc_html_e( 'Card List', 'dashboard-widget-manager' ); ?></span>
								</span>
							</label>
						</div>
					</div>
					</div>

				<!-- Wizard Step 3: Display Type Configuration (dynamic content) -->
				<div class="dwm-wizard-step" data-wizard-step="3">
					<button type="button" class="dwm-wizard-step-help dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-visual-builder-table" aria-label="<?php esc_attr_e( 'View help for widget query configuration', 'dashboard-widget-manager' ); ?>" title="<?php esc_attr_e( 'View help for widget query configuration', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
					<div class="dwm-wizard-step-header">
						<div class="dwm-wizard-step-header-title-row">
							<h3 id="dwm-wizard-step3-title"><?php esc_html_e( 'Configure Your Widget', 'dashboard-widget-manager' ); ?></h3>
						</div>
						<p id="dwm-wizard-step3-desc"><?php esc_html_e( 'Set up the options for your chosen display type.', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-wizard-step-body dwm-wizard-step-body-wide" id="dwm-wizard-step3-content">
					</div>
					</div>

				<!-- Wizard Step 4: Join Tables (optional) -->
				<div class="dwm-wizard-step" data-wizard-step="4">
					<button type="button" class="dwm-wizard-step-help dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-visual-builder-joins" aria-label="<?php esc_attr_e( 'View help for joining tables', 'dashboard-widget-manager' ); ?>" title="<?php esc_attr_e( 'View help for joining tables', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
					<div class="dwm-wizard-step-header">
						<div class="dwm-wizard-step-header-title-row">
							<h3 id="dwm-wizard-step4-title"><?php esc_html_e( 'Join Tables', 'dashboard-widget-manager' ); ?></h3>
						</div>
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
					<button type="button" class="dwm-wizard-step-help dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-visual-builder-filters" aria-label="<?php esc_attr_e( 'View help for filtering query data', 'dashboard-widget-manager' ); ?>" title="<?php esc_attr_e( 'View help for filtering query data', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
					<div class="dwm-wizard-step-header">
						<div class="dwm-wizard-step-header-title-row">
							<h3><?php esc_html_e( 'Filter Your Data', 'dashboard-widget-manager' ); ?></h3>
						</div>
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
					<button type="button" class="dwm-wizard-step-help dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-visual-builder-sort" aria-label="<?php esc_attr_e( 'View help for ordering query results', 'dashboard-widget-manager' ); ?>" title="<?php esc_attr_e( 'View help for ordering query results', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
					<div class="dwm-wizard-step-header">
						<div class="dwm-wizard-step-header-title-row">
							<h3><?php esc_html_e( 'Order Results', 'dashboard-widget-manager' ); ?></h3>
						</div>
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
					<button type="button" class="dwm-wizard-step-help dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-sql-queries" aria-label="<?php esc_attr_e( 'View help for limiting query results', 'dashboard-widget-manager' ); ?>" title="<?php esc_attr_e( 'View help for limiting query results', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
					<div id="dwm-wizard-step7-content"></div>
				</div>

				<!-- Wizard Step 8: Caching Configuration -->
				<div class="dwm-wizard-step" data-wizard-step="8">
					<button type="button" class="dwm-wizard-step-help dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-caching" aria-label="<?php esc_attr_e( 'View help for caching configuration', 'dashboard-widget-manager' ); ?>" title="<?php esc_attr_e( 'View help for caching configuration', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
					<div id="dwm-wizard-step8-content"></div>
				</div>

				<!-- Wizard Step 9: Column Aliases & Ordering -->
				<div class="dwm-wizard-step" data-wizard-step="9">
					<button type="button" id="dwm-wizard-step9-help" class="dwm-wizard-step-help dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-output-aliases" aria-label="<?php esc_attr_e( 'View help for column names and ordering', 'dashboard-widget-manager' ); ?>" title="<?php esc_attr_e( 'View help for column names and ordering', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
					<div class="dwm-wizard-step-header">
						<div class="dwm-wizard-step-header-title-row">
							<h3 id="dwm-wizard-step9-title"><?php esc_html_e( 'Column Names & Ordering', 'dashboard-widget-manager' ); ?></h3>
						</div>
						<p id="dwm-wizard-step9-desc"><?php esc_html_e( 'Customize column display names and reorder columns in your widget output.', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-wizard-step-body">
						<div id="dwm-wizard-step9-table">
							<div class="dwm-step8-section">
								<div id="dwm-wizard-columns-config-list" class="dwm-wizard-columns-config-list">
									<!-- Will be populated dynamically with selected columns -->
								</div>
							</div>
						</div>

						<div id="dwm-wizard-step9-bar" style="display:none">
							<?php require __DIR__ . '/partials/wizard/step9-bar-axis.php'; ?>
						</div>
						<div id="dwm-wizard-step9-line" style="display:none">
							<?php require __DIR__ . '/partials/wizard/step9-line-axis.php'; ?>
						</div>
						<div id="dwm-wizard-step9-pie" style="display:none">
							<?php require __DIR__ . '/partials/wizard/step9-pie-config.php'; ?>
						</div>
						<div id="dwm-wizard-step9-doughnut" style="display:none">
							<?php require __DIR__ . '/partials/wizard/step9-doughnut-config.php'; ?>
						</div>

						<div id="dwm-wizard-step9-list" style="display:none">
							<div class="dwm-step8-section">
								<div id="dwm-wizard-list-columns-config-list" class="dwm-wizard-columns-config-list">
									<!-- Will be populated dynamically with selected columns -->
								</div>
							</div>
						</div>
						<div id="dwm-wizard-step9-button" style="display:none">
							<div class="dwm-step8-section">
								<div id="dwm-wizard-button-columns-config-list" class="dwm-wizard-columns-config-list">
									<!-- Will be populated dynamically with selected columns -->
								</div>
							</div>
						</div>
						<div id="dwm-wizard-step9-card-list" style="display:none">
							<div class="dwm-step8-section">
								<div id="dwm-wizard-card-list-columns-config-list" class="dwm-wizard-columns-config-list">
									<!-- Will be populated dynamically with selected columns -->
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Wizard Step 10: Theme Selection -->
				<div class="dwm-wizard-step" data-wizard-step="10">
					<button type="button" id="dwm-wizard-step10-help" class="dwm-wizard-step-help dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-editor-theme-presets" aria-label="<?php esc_attr_e( 'View help for widget table themes', 'dashboard-widget-manager' ); ?>" title="<?php esc_attr_e( 'View help for widget table themes', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
					<div class="dwm-wizard-step-header">
						<div class="dwm-wizard-step-header-title-row">
							<h3 id="dwm-wizard-step10-title"><?php esc_html_e( 'Choose Table Theme', 'dashboard-widget-manager' ); ?></h3>
						</div>
						<p id="dwm-wizard-step10-desc"><?php esc_html_e( 'Select a pre-designed theme to style your widget table.', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-wizard-step-body">
						<div id="dwm-wizard-step10-table">

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

							<!-- Ocean Theme -->
							<label class="dwm-theme-option">
								<input type="radio" name="dwm_wizard_theme" value="ocean">
								<div class="dwm-theme-preview">
									<div class="dwm-theme-preview-header">
										<span class="dwm-theme-name"><?php esc_html_e( 'Ocean', 'dashboard-widget-manager' ); ?></span>
										<span class="dashicons dashicons-saved dwm-theme-check"></span>
									</div>
									<div class="dwm-theme-preview-table dwm-theme-ocean">
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

						<div id="dwm-wizard-step10-bar" style="display:none">
							<?php require __DIR__ . '/partials/wizard/step10-bar-theme.php'; ?>
						</div>
						<div id="dwm-wizard-step10-line" style="display:none">
							<?php require __DIR__ . '/partials/wizard/step10-line-theme.php'; ?>
						</div>
						<div id="dwm-wizard-step10-pie" style="display:none">
							<?php require __DIR__ . '/partials/wizard/step10-pie-theme.php'; ?>
						</div>
						<div id="dwm-wizard-step10-doughnut" style="display:none">
							<?php require __DIR__ . '/partials/wizard/step10-doughnut-theme.php'; ?>
						</div>

						<div id="dwm-wizard-step10-list" style="display:none">
							<?php require __DIR__ . '/partials/wizard/step10-list-theme.php'; ?>
						</div>
						<div id="dwm-wizard-step10-button" style="display:none">
							<?php require __DIR__ . '/partials/wizard/step10-button-theme.php'; ?>
						</div>
						<div id="dwm-wizard-step10-card-list" style="display:none">
							<?php require __DIR__ . '/partials/wizard/step10-card-list-theme.php'; ?>
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
			<p><?php esc_html_e( 'Switch to Wizard mode? Your current form data will be carried over.', 'dashboard-widget-manager' ); ?></p>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-secondary dwm-confirm-wizard-close"><?php esc_html_e( 'Cancel', 'dashboard-widget-manager' ); ?></button>
			<button type="button" class="dwm-button dwm-button-primary" id="dwm-confirm-wizard-yes"><?php esc_html_e( 'Switch to Wizard', 'dashboard-widget-manager' ); ?></button>
		</div>
	</div>
</div>

<!-- Confirm Switch to Scratch Modal -->
<div id="dwm-confirm-scratch-modal" class="dwm-modal dwm-modal-sm">
	<div class="dwm-modal-overlay dwm-confirm-scratch-close"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-editor-code"></span>
				<?php esc_html_e( 'Switch to Manual mode', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-confirm-scratch-close dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<p><?php esc_html_e( 'Switch to Manual mode? Your wizard data will be used to pre-fill the editor.', 'dashboard-widget-manager' ); ?></p>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-secondary dwm-confirm-scratch-close"><?php esc_html_e( 'Cancel', 'dashboard-widget-manager' ); ?></button>
			<button type="button" class="dwm-button dwm-button-primary" id="dwm-confirm-scratch-yes"><?php esc_html_e( 'Switch to Manual mode', 'dashboard-widget-manager' ); ?></button>
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

<!-- Confirm SQL Editing Modal -->
<div id="dwm-confirm-sql-edit-modal" class="dwm-modal dwm-modal-sm">
	<div class="dwm-modal-overlay dwm-confirm-sql-edit-close"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Advanced SQL Editing', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-confirm-sql-edit-close dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<p><?php esc_html_e( 'This is an advanced feature. You can build your query automatically using the Data tab without writing SQL manually.', 'dashboard-widget-manager' ); ?></p>
			<p><?php esc_html_e( 'If you disable SQL editing later, the query will revert to the auto-built version.', 'dashboard-widget-manager' ); ?></p>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-secondary dwm-confirm-sql-edit-close"><?php esc_html_e( 'Use Data Tab Instead', 'dashboard-widget-manager' ); ?></button>
			<button type="button" class="dwm-button dwm-button-primary" id="dwm-confirm-sql-edit-yes"><?php esc_html_e( 'Enable SQL Editing', 'dashboard-widget-manager' ); ?></button>
		</div>
	</div>
</div>

<!-- Confirm Template/CSS/JS Editing Modal -->
<div id="dwm-confirm-code-edit-modal" class="dwm-modal dwm-modal-sm">
	<div class="dwm-modal-overlay dwm-confirm-code-edit-close"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Advanced Code Editing', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-confirm-code-edit-close dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<p><?php esc_html_e( 'These fields are auto-generated from your display mode, data, and selected theme. Enabling editing stops automatic updates for this field.', 'dashboard-widget-manager' ); ?></p>
			<p><?php esc_html_e( 'If you disable editing later, this field will revert to the latest auto-generated content.', 'dashboard-widget-manager' ); ?></p>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-secondary dwm-confirm-code-edit-close"><?php esc_html_e( 'Keep Auto-Generated', 'dashboard-widget-manager' ); ?></button>
			<button type="button" class="dwm-button dwm-button-primary" id="dwm-confirm-code-edit-yes"><?php esc_html_e( 'Enable Editing', 'dashboard-widget-manager' ); ?></button>
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
						<div id="dwm-join-query-preview-content" class="dwm-join-query-preview-content empty"></div>
					</div>
					<div id="dwm-join-preview-output-pane" class="dwm-join-preview-pane">
						<div id="dwm-join-output-preview-content" class="dwm-join-output-preview-content"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-secondary dwm-join-config-cancel"><?php esc_html_e( 'Cancel', 'dashboard-widget-manager' ); ?></button>
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

				<div id="dwm-filter-duplicate-error" class="dwm-validation-error" style="display: none;"></div>

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
						<div id="dwm-filter-query-preview-content" class="dwm-filter-query-preview-content empty"></div>
					</div>
					<div id="dwm-filter-preview-output-pane" class="dwm-filter-preview-pane">
						<div id="dwm-filter-output-preview-content" class="dwm-filter-output-preview-content"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-secondary dwm-filter-config-cancel"><?php esc_html_e( 'Cancel', 'dashboard-widget-manager' ); ?></button>
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
						<div id="dwm-order-query-preview-content" class="dwm-order-query-preview-content empty"></div>
					</div>
					<div id="dwm-order-preview-output-pane" class="dwm-order-preview-pane">
						<div id="dwm-order-output-preview-content" class="dwm-order-output-preview-content"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-secondary dwm-order-config-cancel"><?php esc_html_e( 'Cancel', 'dashboard-widget-manager' ); ?></button>
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

<!-- Post-Save Widget Modal (new widgets only) -->
<div id="dwm-widget-saved-modal" class="dwm-modal dwm-modal-sm" style="display:none;">
	<div class="dwm-modal-overlay"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-yes-alt"></span>
				<?php esc_html_e( 'Widget Saved!', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-modal-close" id="dwm-widget-saved-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<p class="dwm-widget-saved-intro"><?php esc_html_e( 'Your widget has been saved and published to your dashboard. You can adjust the settings below.', 'dashboard-widget-manager' ); ?></p>
			<div class="dwm-export-toggle-group">
				<div class="dwm-export-toggle-row">
					<label class="dwm-toggle-switch">
						<input type="checkbox" id="dwm-saved-publish-toggle" checked />
						<span class="dwm-toggle-slider"></span>
					</label>
					<div class="dwm-export-toggle-content">
						<span class="dwm-export-toggle-label"><?php esc_html_e( 'Publish', 'dashboard-widget-manager' ); ?></span>
						<span class="dwm-export-toggle-desc"><?php esc_html_e( 'Widget is active and visible in the plugin.', 'dashboard-widget-manager' ); ?></span>
					</div>
				</div>
				<div class="dwm-export-toggle-row" id="dwm-saved-dashboard-row">
					<label class="dwm-toggle-switch">
						<input type="checkbox" id="dwm-saved-dashboard-toggle" checked />
						<span class="dwm-toggle-slider"></span>
					</label>
					<div class="dwm-export-toggle-content">
						<span class="dwm-export-toggle-label"><?php esc_html_e( 'Add to Dashboard', 'dashboard-widget-manager' ); ?></span>
						<span class="dwm-export-toggle-desc"><?php esc_html_e( 'Show this widget on the WordPress admin dashboard.', 'dashboard-widget-manager' ); ?></span>
					</div>
				</div>
			</div>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" class="dwm-button dwm-button-primary" id="dwm-widget-saved-done">
				<?php esc_html_e( 'Done', 'dashboard-widget-manager' ); ?>
			</button>
		</div>
	</div>
</div>

<?php include __DIR__ . '/partials/page-wrapper-end.php'; ?>
