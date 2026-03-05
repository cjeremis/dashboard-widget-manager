<?php
/**
 * Widget Editor Template
 *
 * Tabbed editor interface for creating/editing widgets.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<form id="dwm-widget-form">
	<input type="hidden" id="dwm-widget-id" name="widget_id" value="">
	<input type="hidden" id="dwm-creation-method" name="creation_method" value="">
	<input type="hidden" id="dwm-builder-config" name="builder_config" value="">
	<input type="hidden" id="dwm-chart-type" name="chart_type" value="">
	<input type="hidden" id="dwm-chart-config" name="chart_config" value="">

	<div class="dwm-tabs">
		<nav class="dwm-tab-nav">
			<button type="button" class="dwm-tab-link active" data-tab="builder">
				<span class="dashicons dashicons-layout"></span>
				<?php esc_html_e( 'Display', 'dashboard-widget-manager' ); ?>
			</button>
			<button type="button" class="dwm-tab-link" data-tab="data">
				<span class="dashicons dashicons-database-view"></span>
				<?php esc_html_e( 'Data', 'dashboard-widget-manager' ); ?>
				<span class="dwm-tab-dependency-hint"><?php esc_html_e( 'Enter a widget name first', 'dashboard-widget-manager' ); ?></span>
			</button>
			<button type="button" class="dwm-tab-link" data-tab="query">
				<span class="dashicons dashicons-database"></span>
				<?php esc_html_e( 'Query', 'dashboard-widget-manager' ); ?>
				<span class="dwm-query-tab-spinner" style="display:none"><span class="dashicons dashicons-update dwm-spin"></span></span>
				<span class="dwm-tab-dependency-hint"><?php esc_html_e( 'Select a table and columns first', 'dashboard-widget-manager' ); ?></span>
			</button>
			<button type="button" class="dwm-tab-link" data-tab="caching">
				<span class="dashicons dashicons-performance"></span>
				<?php esc_html_e( 'Caching', 'dashboard-widget-manager' ); ?>
				<span class="dwm-tab-dependency-hint"><?php esc_html_e( 'Select a table and columns first', 'dashboard-widget-manager' ); ?></span>
			</button>
			<button type="button" class="dwm-tab-link" data-tab="output">
				<span class="dashicons dashicons-visibility"></span>
				<span id="dwm-tab-label-output"><?php esc_html_e( 'Output', 'dashboard-widget-manager' ); ?></span>
				<span class="dwm-tab-dependency-hint"><?php esc_html_e( 'Select a table and columns first', 'dashboard-widget-manager' ); ?></span>
			</button>
			<button type="button" class="dwm-tab-link" data-tab="template">
				<span class="dashicons dashicons-editor-code"></span>
				<span id="dwm-tab-label-template"><?php esc_html_e( 'Template', 'dashboard-widget-manager' ); ?></span>
				<span class="dwm-tab-dependency-hint"><?php esc_html_e( 'Select a table and columns first', 'dashboard-widget-manager' ); ?></span>
			</button>
			<button type="button" class="dwm-tab-link" data-tab="styles">
				<span class="dashicons dashicons-art"></span>
				<span id="dwm-tab-label-styles"><?php esc_html_e( 'Styles', 'dashboard-widget-manager' ); ?></span>
				<span class="dwm-tab-dependency-hint"><?php esc_html_e( 'Select a table and columns first', 'dashboard-widget-manager' ); ?></span>
			</button>
			<button type="button" class="dwm-tab-link" data-tab="scripts">
				<span class="dashicons dashicons-media-code"></span>
				<span id="dwm-tab-label-scripts"><?php esc_html_e( 'Scripts', 'dashboard-widget-manager' ); ?></span>
				<span class="dwm-tab-dependency-hint"><?php esc_html_e( 'Select a table and columns first', 'dashboard-widget-manager' ); ?></span>
			</button>
		</nav>

		<!-- Builder Tab -->
		<div class="dwm-tab-content active" data-tab="builder">

			<!-- Widget name & description -->
			<div class="dwm-builder-section">
				<div class="dwm-builder-label"><span class="dashicons dashicons-id-alt"></span> <?php esc_html_e( 'Widget Details', 'dashboard-widget-manager' ); ?> <button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-create-widgets" aria-label="<?php esc_attr_e( 'Widget Details help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button></div>
				<p class="description"><?php esc_html_e( 'Give your widget a name and optional description. Enable "Display on Widget" to include it with your widget.', 'dashboard-widget-manager' ); ?></p>
				<div class="dwm-form-row no-bottom-border">
					<div class="dwm-form-group no-bottom-border">
						<label for="dwm-widget-name"><?php esc_html_e( 'Widget Name', 'dashboard-widget-manager' ); ?> *</label>
						<input type="text" id="dwm-widget-name" name="name" class="dwm-input-text" required placeholder="<?php esc_attr_e( 'e.g. Recent Posts', 'dashboard-widget-manager' ); ?>">
					</div>
					<div class="dwm-form-group no-bottom-border">
						<label for="dwm-widget-description"><?php esc_html_e( 'Description', 'dashboard-widget-manager' ); ?><span class="dwm-desc-required-asterisk" style="display:none"> *</span></label>
						<textarea id="dwm-widget-description" name="description" class="dwm-input-text" rows="3" placeholder="<?php esc_attr_e( 'Optional description', 'dashboard-widget-manager' ); ?>"></textarea>
						<span class="dwm-desc-display-toggle">
							<label class="dwm-toggle-switch dwm-toggle-switch--sm">
								<input type="checkbox" id="dwm-show-description" class="dwm-show-description-toggle">
								<span class="dwm-toggle-slider"></span>
							</label>
							<span class="dwm-desc-display-label"><?php esc_html_e( 'Display on Widget', 'dashboard-widget-manager' ); ?></span>
						</span>
					</div>
				</div>
			</div>

				<!-- Display Mode -->
				<div class="dwm-builder-section">
					<div class="dwm-builder-label"><span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Display Mode', 'dashboard-widget-manager' ); ?> <button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="display-modes-overview" aria-label="<?php esc_attr_e( 'Display Mode help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button></div>
					<p class="description"><?php esc_html_e( 'Choose how widget data is presented on the dashboard.', 'dashboard-widget-manager' ); ?></p>
					<div class="dwm-builder-display-modes">
					<label class="dwm-display-mode-option">
						<input type="radio" name="dwm_display_mode" value="table" checked>
						<span class="dwm-display-mode-card">
							<span class="dashicons dashicons-editor-table"></span>
							<span><?php esc_html_e( 'Table', 'dashboard-widget-manager' ); ?></span>
						</span>
					</label>
					<label class="dwm-display-mode-option">
						<input type="radio" name="dwm_display_mode" value="bar">
						<span class="dwm-display-mode-card">
							<span class="dashicons dashicons-chart-bar"></span>
							<span><?php esc_html_e( 'Bar Chart', 'dashboard-widget-manager' ); ?></span>
						</span>
					</label>
					<label class="dwm-display-mode-option">
						<input type="radio" name="dwm_display_mode" value="line">
						<span class="dwm-display-mode-card">
							<span class="dashicons dashicons-chart-line"></span>
							<span><?php esc_html_e( 'Line Chart', 'dashboard-widget-manager' ); ?></span>
						</span>
					</label>
					<label class="dwm-display-mode-option">
						<input type="radio" name="dwm_display_mode" value="pie">
						<span class="dwm-display-mode-card">
							<span class="dashicons dashicons-chart-pie"></span>
							<span><?php esc_html_e( 'Pie Chart', 'dashboard-widget-manager' ); ?></span>
						</span>
					</label>
					<label class="dwm-display-mode-option">
						<input type="radio" name="dwm_display_mode" value="doughnut">
						<span class="dwm-display-mode-card">
							<span class="dashicons dashicons-chart-pie"></span>
							<span><?php esc_html_e( 'Doughnut', 'dashboard-widget-manager' ); ?></span>
						</span>
					</label>
					<label class="dwm-display-mode-option">
						<input type="radio" name="dwm_display_mode" value="list">
						<span class="dwm-display-mode-card">
							<span class="dashicons dashicons-list-view"></span>
							<span><?php esc_html_e( 'List', 'dashboard-widget-manager' ); ?></span>
						</span>
					</label>
					<label class="dwm-display-mode-option">
						<input type="radio" name="dwm_display_mode" value="button">
						<span class="dwm-display-mode-card">
							<span class="dashicons dashicons-button"></span>
							<span><?php esc_html_e( 'Button', 'dashboard-widget-manager' ); ?></span>
						</span>
					</label>
					<label class="dwm-display-mode-option">
						<input type="radio" name="dwm_display_mode" value="card-list">
						<span class="dwm-display-mode-card">
							<span class="dashicons dashicons-grid-view"></span>
							<span><?php esc_html_e( 'Card List', 'dashboard-widget-manager' ); ?></span>
						</span>
					</label>
				</div>
			</div>

			</div><!-- /.dwm-tab-content[builder] -->

		<!-- Data Tab -->
		<div class="dwm-tab-content" data-tab="data">

			<!-- Primary Table -->
			<div class="dwm-builder-section">
				<label class="dwm-builder-label" for="dwm-builder-table">
					<span class="dashicons dashicons-database"></span>
					<?php esc_html_e( 'Primary Table', 'dashboard-widget-manager' ); ?>
					<button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-visual-builder-table" aria-label="<?php esc_attr_e( 'Primary Table help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
				</label>
				<p class="description"><?php esc_html_e( 'The main database table your widget will query. All columns, joins, and filters are built from this table.', 'dashboard-widget-manager' ); ?></p>
				<select id="dwm-builder-table" class="dwm-select">
					<option value=""><?php esc_html_e( 'Loading tables…', 'dashboard-widget-manager' ); ?></option>
				</select>
			</div>

			<!-- Columns -->
			<div class="dwm-builder-section" id="dwm-builder-columns-section" style="display:none">
				<div class="dwm-builder-label">
					<span class="dashicons dashicons-list-view"></span>
					<?php esc_html_e( 'Columns to Select', 'dashboard-widget-manager' ); ?>
					<button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-visual-builder-table" aria-label="<?php esc_attr_e( 'Columns to Select help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
				</div>
				<p class="description"><?php esc_html_e( 'Pick which columns appear in your widget. Leave all unchecked to select every column.', 'dashboard-widget-manager' ); ?></p>
				<div id="dwm-builder-columns-list" class="dwm-builder-checkboxes"></div>
			</div>

			<!-- Joins -->
			<div class="dwm-builder-section" id="dwm-builder-joins-section" style="display:none">
				<div class="dwm-builder-label">
					<span class="dashicons dashicons-networking"></span>
					<?php esc_html_e( 'Joins', 'dashboard-widget-manager' ); ?>
					<button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-visual-builder-joins" aria-label="<?php esc_attr_e( 'Joins help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
				</div>
				<p class="description"><?php esc_html_e( 'Link additional tables to combine related data. Each join adds columns from another table to your query.', 'dashboard-widget-manager' ); ?></p>
				<div id="dwm-builder-joins-list"></div>
				<button type="button" class="dwm-button dwm-button-primary" id="dwm-builder-add-join">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Add Join', 'dashboard-widget-manager' ); ?>
				</button>
			</div>

			<!-- Conditions (WHERE) -->
			<div class="dwm-builder-section" id="dwm-builder-conditions-section" style="display:none">
				<div class="dwm-builder-label">
					<span class="dashicons dashicons-filter"></span>
					<?php esc_html_e( 'Filters (WHERE)', 'dashboard-widget-manager' ); ?>
					<button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-visual-builder-filters" aria-label="<?php esc_attr_e( 'Filters help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
				</div>
				<p class="description"><?php esc_html_e( 'Narrow down results by applying conditions to any column. Only rows that match all filters will be included.', 'dashboard-widget-manager' ); ?></p>
				<div id="dwm-builder-conditions-list"></div>
				<button type="button" class="dwm-button dwm-button-primary" id="dwm-builder-add-condition">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Add Filter', 'dashboard-widget-manager' ); ?>
				</button>
			</div>

			<!-- Order By -->
			<div class="dwm-builder-section" id="dwm-builder-order-section" style="display:none">
				<div class="dwm-builder-label">
					<span class="dashicons dashicons-sort"></span>
					<?php esc_html_e( 'Order By', 'dashboard-widget-manager' ); ?>
					<button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-visual-builder-sort" aria-label="<?php esc_attr_e( 'Order By help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
				</div>
				<p class="description"><?php esc_html_e( 'Control the sort order of your results. Add multiple orders to sort by more than one column.', 'dashboard-widget-manager' ); ?></p>
				<div id="dwm-builder-orders-list"></div>
				<button type="button" class="dwm-button dwm-button-primary" id="dwm-builder-add-order">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Add Order', 'dashboard-widget-manager' ); ?>
				</button>
			</div>

			<!-- Limit -->
			<div class="dwm-builder-section" id="dwm-builder-limit-section" style="display:none">
				<div class="dwm-builder-label">
					<span class="dashicons dashicons-controls-repeat"></span>
					<?php esc_html_e( 'Limit Results', 'dashboard-widget-manager' ); ?>
					<button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-sql-queries" aria-label="<?php esc_attr_e( 'Limit Results help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
				</div>
				<p class="description"><?php esc_html_e( 'Optionally restrict how many rows your widget returns.', 'dashboard-widget-manager' ); ?></p>
				<div class="dwm-builder-limit-controls">
					<label class="dwm-toggle">
						<input type="checkbox" id="dwm-builder-limit-toggle">
						<span class="dwm-toggle-slider"></span>
					</label>
					<div class="dwm-builder-limit-toggle-label">
						<span><?php esc_html_e( 'Enable Row Limit', 'dashboard-widget-manager' ); ?></span>
					</div>
				</div>
				<div class="dwm-builder-limit-input" style="display:none">
					<label for="dwm-builder-limit"><?php esc_html_e( 'Number of rows', 'dashboard-widget-manager' ); ?></label>
					<input type="number" id="dwm-builder-limit" class="dwm-input-number" value="10" min="1" max="100">
				</div>
			</div>

			<!-- Widget Settings + Apply -->
			<div class="dwm-builder-section" id="dwm-builder-apply-section" style="display:none"></div>

		</div><!-- /.dwm-tab-content[data] -->

		<!-- Query Tab -->
		<div class="dwm-tab-content" data-tab="query">

			<!-- Query Settings -->
			<div class="dwm-builder-section">
				<label class="dwm-builder-label"><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Query Settings', 'dashboard-widget-manager' ); ?> <button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="category-overview-query-engine" aria-label="<?php esc_attr_e( 'Query Settings help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button></label>
				<p class="description"><?php esc_html_e( 'Configure query logging and execution time limits. Logs can be found in debug.log.', 'dashboard-widget-manager' ); ?></p>

			<!-- Logging & execution time — compact row above the editor -->
			<div class="dwm-form-row dwm-query-meta-row">
				<div class="dwm-form-group">
					<span class="dwm-form-label">
						<?php esc_html_e( 'Enable Query Logging', 'dashboard-widget-manager' ); ?>
						<button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-query-logging" aria-label="<?php esc_attr_e( 'Query Logging help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
					</span>
					<label class="dwm-toggle">
						<input type="checkbox" id="dwm-enable-query-logging" name="enable_query_logging" value="1">
						<span class="dwm-toggle-slider"></span>
					</label>
				</div>
				<div class="dwm-form-group">
					<label for="dwm-max-execution-time">
						<?php esc_html_e( 'Max Execution Time (seconds)', 'dashboard-widget-manager' ); ?>
						<button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-execution-time" aria-label="<?php esc_attr_e( 'Execution Time Limit help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
					</label>
					<input type="number" id="dwm-max-execution-time" name="max_execution_time" class="dwm-input-number" value="30" min="1" max="60">
				</div>
			</div>
			</div><!-- /.dwm-builder-section[query-settings] -->

				<!-- SQL query editor -->
			<div class="dwm-builder-section">
				<label class="dwm-builder-label" for="dwm-widget-query"><span class="dashicons dashicons-database"></span> <?php esc_html_e( 'SQL Query', 'dashboard-widget-manager' ); ?> * <button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-sql-queries" aria-label="<?php esc_attr_e( 'SQL Query help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button></label>
				<p class="description"><?php esc_html_e( 'The SELECT query auto-builds as you fill in the Data tab. You can also write it manually, or edit the auto-built query by enabling Allow Editing.', 'dashboard-widget-manager' ); ?></p>
				<div class="dwm-form-group">
					<div class="dwm-query-preview-section">
						<div class="dwm-query-preview-tabs">
							<button type="button" class="dwm-query-preview-tab active" data-tab="input">
								<span class="dashicons dashicons-edit"></span>
								<?php esc_html_e( 'SQL Preview', 'dashboard-widget-manager' ); ?>
							</button>
							<button type="button" class="dwm-query-preview-tab" data-tab="output" disabled>
								<span class="dashicons dashicons-list-view"></span>
								<?php esc_html_e( 'Output Preview', 'dashboard-widget-manager' ); ?>
							</button>
						</div>

							<div class="dwm-query-preview-pane active" data-pane="input">
									<div class="dwm-query-editor-header">
									<div class="dwm-query-edit-toggle">
										<label class="dwm-toggle" for="dwm-query-edit-toggle">
											<input type="checkbox" id="dwm-query-edit-toggle">
											<span class="dwm-toggle-slider"></span>
										</label>
										<span class="dwm-query-edit-toggle-text"><?php esc_html_e( 'Allow Editing', 'dashboard-widget-manager' ); ?></span>
									</div>
									<div class="dwm-query-actions" style="display:none">
										<button type="button" id="dwm-validate-query" class="button">
											<?php esc_html_e( 'Validate Query', 'dashboard-widget-manager' ); ?>
										</button>
									</div>
									<button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-sql-queries" aria-label="<?php esc_attr_e( 'SQL Query Editor help', 'dashboard-widget-manager' ); ?>">
										<span class="dashicons dashicons-editor-help"></span>
									</button>
									</div>
								<div class="dwm-query-editor-wrapper">
								<div id="dwm-query-editor-validation-status" class="dwm-query-editor-validation-status" style="display:none"></div>
								<div class="dwm-query-editor-loading" style="display:none"><span class="dashicons dashicons-update dwm-spin"></span></div>
								<textarea id="dwm-widget-query" name="sql_query" class="dwm-code-editor" rows="10" required></textarea>
							</div>
						</div>


						<div class="dwm-query-preview-pane" data-pane="output">
							<div id="dwm-query-output-preview-content" class="dwm-order-output-preview-content">
								<p class="dwm-output-empty"><?php esc_html_e( 'Validate your query to preview output.', 'dashboard-widget-manager' ); ?></p>
							</div>
						</div>
					</div>
				</div>
			</div><!-- /.dwm-builder-section[sql-editor] -->
		</div>

		<!-- Caching Tab -->
		<div class="dwm-tab-content" data-tab="caching">

			<div class="dwm-builder-section">
				<div class="dwm-builder-label"><span class="dashicons dashicons-performance"></span> <?php esc_html_e( 'Cache Settings', 'dashboard-widget-manager' ); ?> <button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-caching" aria-label="<?php esc_attr_e( 'Cache Settings help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button></div>
				<p class="description"><?php esc_html_e( 'Cache query results to reduce database load and improve dashboard performance.', 'dashboard-widget-manager' ); ?></p>

				<div class="dwm-form-row">
					<div class="dwm-form-group">
						<span class="dwm-form-label"><?php esc_html_e( 'Enable Caching', 'dashboard-widget-manager' ); ?></span>
						<p class="description"><?php esc_html_e( 'Store query results to improve performance.', 'dashboard-widget-manager' ); ?></p>
						<label class="dwm-toggle">
							<input type="checkbox" id="dwm-enable-caching" name="enable_caching" value="1">
							<span class="dwm-toggle-slider"></span>
						</label>
					</div>
					<div class="dwm-form-group">
						<label for="dwm-cache-duration"><?php esc_html_e( 'Cache Duration (seconds)', 'dashboard-widget-manager' ); ?></label>
						<p class="description"><?php esc_html_e( 'Set how long cached results are kept before refresh.', 'dashboard-widget-manager' ); ?></p>
						<input type="number" id="dwm-cache-duration" name="cache_duration" class="dwm-input-number" value="300" min="0" max="3600">
					</div>
				</div>
			</div><!-- /.dwm-builder-section[cache-settings] -->
			<input type="hidden" id="dwm-auto-refresh" name="auto_refresh" value="0">
		</div>

		<!-- Output Tab -->
		<div class="dwm-tab-content" data-tab="output">

			<!-- Hidden field to persist output_config JSON -->
			<input type="hidden" id="dwm-output-config" name="output_config" value="">

			<!-- Column Aliases (table mode only) -->
			<div class="dwm-builder-section" id="dwm-output-column-aliases-section">
				<div class="dwm-builder-label">
					<span class="dashicons dashicons-editor-table"></span>
					<?php esc_html_e( 'Column Aliases', 'dashboard-widget-manager' ); ?>
					<button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-output-aliases" aria-label="<?php esc_attr_e( 'Column Aliases help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
				</div>
				<p class="description"><?php esc_html_e( 'Rename column headers displayed in the widget. These aliases replace the raw database column names in the auto-generated template.', 'dashboard-widget-manager' ); ?></p>
				<div id="dwm-output-column-aliases-list" class="dwm-output-aliases-list">
					<p class="dwm-no-columns-message"><?php esc_html_e( 'Select columns on the Data tab first.', 'dashboard-widget-manager' ); ?></p>
				</div>
			</div>

			<!-- Link Builder (table mode only) -->
			<div class="dwm-builder-section" id="dwm-output-link-builder-section">
				<div class="dwm-builder-label">
					<span class="dashicons dashicons-admin-links"></span>
					<?php esc_html_e( 'Link Builder', 'dashboard-widget-manager' ); ?>
					<button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-output-links" aria-label="<?php esc_attr_e( 'Link Builder help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
				</div>
				<p class="description"><?php esc_html_e( 'Make column values clickable links. Use template variables like {column_name} in the URL to insert row data dynamically.', 'dashboard-widget-manager' ); ?></p>
				<div id="dwm-output-link-builder-list" class="dwm-output-link-builder-list">
					<p class="dwm-no-columns-message"><?php esc_html_e( 'Select columns on the Data tab first.', 'dashboard-widget-manager' ); ?></p>
				</div>
			</div>

			<!-- Formatter Builder (table/list/card-list modes) -->
			<div class="dwm-builder-section" id="dwm-output-formatter-section">
				<div class="dwm-builder-label">
					<span class="dashicons dashicons-editor-textcolor"></span>
					<?php esc_html_e( 'Column Formatters', 'dashboard-widget-manager' ); ?>
					<button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-output-formatters" aria-label="<?php esc_attr_e( 'Column Formatters help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
				</div>
				<p class="description"><?php esc_html_e( 'Apply formatting rules to column values. Choose a data type to control how values are displayed in the widget.', 'dashboard-widget-manager' ); ?></p>
				<div id="dwm-output-formatter-list" class="dwm-output-formatter-list">
					<p class="dwm-no-columns-message"><?php esc_html_e( 'Select columns on the Data tab first.', 'dashboard-widget-manager' ); ?></p>
				</div>
			</div>

			<!-- Chart Configuration (chart modes only) -->
			<div class="dwm-builder-section" id="dwm-builder-chart-options" style="display:none">
				<div class="dwm-builder-label">
					<span class="dashicons dashicons-chart-bar"></span>
					<span id="dwm-builder-chart-config-title"><?php esc_html_e( 'Chart Configuration', 'dashboard-widget-manager' ); ?></span>
					<button type="button" id="dwm-builder-chart-config-help" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-visual-builder-chart-config" aria-label="<?php esc_attr_e( 'Chart Configuration help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
				</div>
				<p class="description" id="dwm-builder-chart-config-desc"><?php esc_html_e( 'Configure chart columns and display options for the selected chart mode.', 'dashboard-widget-manager' ); ?></p>
				<div class="dwm-form-row">
					<div class="dwm-form-group">
						<label for="dwm-builder-chart-label" id="dwm-builder-chart-label-title"><?php esc_html_e( 'Label Column', 'dashboard-widget-manager' ); ?> *</label>
						<select id="dwm-builder-chart-label" class="dwm-select">
							<option value=""><?php esc_html_e( '— select column —', 'dashboard-widget-manager' ); ?></option>
						</select>
						<p class="description" id="dwm-builder-chart-label-desc"><?php esc_html_e( 'Column used for X-axis labels or pie slice names.', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-form-group">
						<label id="dwm-builder-chart-data-title"><?php esc_html_e( 'Data Column(s)', 'dashboard-widget-manager' ); ?> *</label>
						<div id="dwm-builder-chart-data-list" class="dwm-builder-checkboxes"></div>
						<p class="description" id="dwm-builder-chart-data-desc"><?php esc_html_e( 'Numeric column(s) to plot as datasets.', 'dashboard-widget-manager' ); ?></p>
					</div>
				</div>
				<div class="dwm-form-row">
					<div class="dwm-form-group">
						<label for="dwm-builder-chart-title"><?php esc_html_e( 'Chart Title', 'dashboard-widget-manager' ); ?></label>
						<input type="text" id="dwm-builder-chart-title" class="dwm-input-text" placeholder="<?php esc_attr_e( 'Optional title shown above the chart', 'dashboard-widget-manager' ); ?>">
					</div>
					<div class="dwm-form-group">
						<label for="dwm-builder-chart-theme"><?php esc_html_e( 'Chart Theme', 'dashboard-widget-manager' ); ?></label>
						<select id="dwm-builder-chart-theme" class="dwm-select">
							<option value="classic"><?php esc_html_e( 'Classic', 'dashboard-widget-manager' ); ?></option>
							<option value="sunset"><?php esc_html_e( 'Sunset', 'dashboard-widget-manager' ); ?></option>
							<option value="forest"><?php esc_html_e( 'Forest', 'dashboard-widget-manager' ); ?></option>
							<option value="oceanic"><?php esc_html_e( 'Oceanic', 'dashboard-widget-manager' ); ?></option>
							<option value="monochrome"><?php esc_html_e( 'Monochrome', 'dashboard-widget-manager' ); ?></option>
							<option value="candy"><?php esc_html_e( 'Candy', 'dashboard-widget-manager' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Choose the color palette used for chart datasets.', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-form-group dwm-form-group-checkbox">
						<label>
							<input type="checkbox" id="dwm-builder-chart-legend" checked>
							<?php esc_html_e( 'Show legend', 'dashboard-widget-manager' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Display dataset labels below the chart.', 'dashboard-widget-manager' ); ?></p>
					</div>
				</div>
			</div>

			<!-- No Results Template -->
			<div class="dwm-builder-section">
				<div class="dwm-builder-label"><span class="dashicons dashicons-dismiss"></span> <?php esc_html_e( 'No Results Template', 'dashboard-widget-manager' ); ?> <button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-no-results-template" aria-label="<?php esc_attr_e( 'No Results Template help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button></div>
				<p class="description"><?php esc_html_e( 'This template is displayed when your query returns zero results. Enable editing to customize the message.', 'dashboard-widget-manager' ); ?></p>
				<div class="dwm-form-group">
					<div class="dwm-query-editor-header">
						<div class="dwm-query-edit-toggle">
							<label class="dwm-toggle" for="dwm-no-results-template-edit-toggle">
								<input type="checkbox" id="dwm-no-results-template-edit-toggle">
								<span class="dwm-toggle-slider"></span>
							</label>
							<span class="dwm-query-edit-toggle-text"><?php esc_html_e( 'Allow Editing', 'dashboard-widget-manager' ); ?></span>
						</div>
					</div>
					<textarea id="dwm-widget-no-results-template" name="no_results_template" class="dwm-code-editor" rows="8"></textarea>
				</div>
			</div>

		</div><!-- /.dwm-tab-content[output] -->

		<!-- Template Tab -->
		<div class="dwm-tab-content" data-tab="template">

			<div class="dwm-builder-intro dwm-chart-mode-notice" style="display:none">
				<p><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e( 'Chart mode is active. The chart is rendered automatically from your query results — a template is not required. You can add HTML above the chart here if needed.', 'dashboard-widget-manager' ); ?></p>
			</div>

			<div class="dwm-builder-section" id="dwm-editor-theme-section">
				<div class="dwm-builder-label">
					<span class="dashicons dashicons-art"></span>
					<span id="dwm-editor-theme-title"><?php esc_html_e( 'Theme', 'dashboard-widget-manager' ); ?></span>
					<button type="button" id="dwm-editor-theme-help" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-editor-theme-presets" aria-label="<?php esc_attr_e( 'Theme help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
				</div>
				<p class="description" id="dwm-editor-theme-desc"><?php esc_html_e( 'Choose a theme preset to auto-populate Template, Styles, and Scripts.', 'dashboard-widget-manager' ); ?></p>

				<div id="dwm-editor-theme-options-table" class="dwm-editor-theme-options">
					<div class="dwm-step9-themes-grid">
						<label class="dwm-theme-option">
							<input type="radio" name="dwm_editor_theme_table" value="default" checked>
							<div class="dwm-theme-preview">
								<div class="dwm-theme-preview-header">
									<span class="dwm-theme-name"><?php esc_html_e( 'Default', 'dashboard-widget-manager' ); ?></span>
									<span class="dashicons dashicons-saved dwm-theme-check"></span>
								</div>
								<div class="dwm-theme-preview-table dwm-theme-default">
									<div class="dwm-theme-preview-row dwm-theme-preview-header-row"><span><?php esc_html_e( 'Name', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Status', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Count', 'dashboard-widget-manager' ); ?></span></div>
									<div class="dwm-theme-preview-row"><span><?php esc_html_e( 'Posts', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Live', 'dashboard-widget-manager' ); ?></span><span>24</span></div>
									<div class="dwm-theme-preview-row"><span><?php esc_html_e( 'Users', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Active', 'dashboard-widget-manager' ); ?></span><span>12</span></div>
								</div>
							</div>
						</label>
						<label class="dwm-theme-option">
							<input type="radio" name="dwm_editor_theme_table" value="minimal">
							<div class="dwm-theme-preview">
								<div class="dwm-theme-preview-header">
									<span class="dwm-theme-name"><?php esc_html_e( 'Minimal', 'dashboard-widget-manager' ); ?></span>
									<span class="dashicons dashicons-saved dwm-theme-check"></span>
								</div>
								<div class="dwm-theme-preview-table dwm-theme-minimal">
									<div class="dwm-theme-preview-row dwm-theme-preview-header-row"><span><?php esc_html_e( 'Name', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Status', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Count', 'dashboard-widget-manager' ); ?></span></div>
									<div class="dwm-theme-preview-row"><span><?php esc_html_e( 'Posts', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Live', 'dashboard-widget-manager' ); ?></span><span>24</span></div>
									<div class="dwm-theme-preview-row"><span><?php esc_html_e( 'Users', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Active', 'dashboard-widget-manager' ); ?></span><span>12</span></div>
								</div>
							</div>
						</label>
						<label class="dwm-theme-option">
							<input type="radio" name="dwm_editor_theme_table" value="dark">
							<div class="dwm-theme-preview">
								<div class="dwm-theme-preview-header">
									<span class="dwm-theme-name"><?php esc_html_e( 'Dark', 'dashboard-widget-manager' ); ?></span>
									<span class="dashicons dashicons-saved dwm-theme-check"></span>
								</div>
								<div class="dwm-theme-preview-table dwm-theme-dark">
									<div class="dwm-theme-preview-row dwm-theme-preview-header-row"><span><?php esc_html_e( 'Name', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Status', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Count', 'dashboard-widget-manager' ); ?></span></div>
									<div class="dwm-theme-preview-row"><span><?php esc_html_e( 'Posts', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Live', 'dashboard-widget-manager' ); ?></span><span>24</span></div>
									<div class="dwm-theme-preview-row"><span><?php esc_html_e( 'Users', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Active', 'dashboard-widget-manager' ); ?></span><span>12</span></div>
								</div>
							</div>
						</label>
						<label class="dwm-theme-option">
							<input type="radio" name="dwm_editor_theme_table" value="striped">
							<div class="dwm-theme-preview">
								<div class="dwm-theme-preview-header">
									<span class="dwm-theme-name"><?php esc_html_e( 'Striped', 'dashboard-widget-manager' ); ?></span>
									<span class="dashicons dashicons-saved dwm-theme-check"></span>
								</div>
								<div class="dwm-theme-preview-table dwm-theme-striped">
									<div class="dwm-theme-preview-row dwm-theme-preview-header-row"><span><?php esc_html_e( 'Name', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Status', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Count', 'dashboard-widget-manager' ); ?></span></div>
									<div class="dwm-theme-preview-row"><span><?php esc_html_e( 'Posts', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Live', 'dashboard-widget-manager' ); ?></span><span>24</span></div>
									<div class="dwm-theme-preview-row"><span><?php esc_html_e( 'Users', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Active', 'dashboard-widget-manager' ); ?></span><span>12</span></div>
								</div>
							</div>
						</label>
						<label class="dwm-theme-option">
							<input type="radio" name="dwm_editor_theme_table" value="bordered">
							<div class="dwm-theme-preview">
								<div class="dwm-theme-preview-header">
									<span class="dwm-theme-name"><?php esc_html_e( 'Bordered', 'dashboard-widget-manager' ); ?></span>
									<span class="dashicons dashicons-saved dwm-theme-check"></span>
								</div>
								<div class="dwm-theme-preview-table dwm-theme-bordered">
									<div class="dwm-theme-preview-row dwm-theme-preview-header-row"><span><?php esc_html_e( 'Name', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Status', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Count', 'dashboard-widget-manager' ); ?></span></div>
									<div class="dwm-theme-preview-row"><span><?php esc_html_e( 'Posts', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Live', 'dashboard-widget-manager' ); ?></span><span>24</span></div>
									<div class="dwm-theme-preview-row"><span><?php esc_html_e( 'Users', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Active', 'dashboard-widget-manager' ); ?></span><span>12</span></div>
								</div>
							</div>
						</label>
						<label class="dwm-theme-option">
							<input type="radio" name="dwm_editor_theme_table" value="ocean">
							<div class="dwm-theme-preview">
								<div class="dwm-theme-preview-header">
									<span class="dwm-theme-name"><?php esc_html_e( 'Ocean', 'dashboard-widget-manager' ); ?></span>
									<span class="dashicons dashicons-saved dwm-theme-check"></span>
								</div>
								<div class="dwm-theme-preview-table dwm-theme-ocean">
									<div class="dwm-theme-preview-row dwm-theme-preview-header-row"><span><?php esc_html_e( 'Name', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Status', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Count', 'dashboard-widget-manager' ); ?></span></div>
									<div class="dwm-theme-preview-row"><span><?php esc_html_e( 'Posts', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Live', 'dashboard-widget-manager' ); ?></span><span>24</span></div>
									<div class="dwm-theme-preview-row"><span><?php esc_html_e( 'Users', 'dashboard-widget-manager' ); ?></span><span><?php esc_html_e( 'Active', 'dashboard-widget-manager' ); ?></span><span>12</span></div>
								</div>
							</div>
						</label>
					</div>
				</div>

				<div id="dwm-editor-theme-options-bar" class="dwm-editor-theme-options" style="display:none">
					<div class="dwm-step10-bar-themes-grid">
						<?php foreach ( array( 'classic' => 'Classic', 'sunset' => 'Sunset', 'forest' => 'Forest', 'oceanic' => 'Oceanic', 'monochrome' => 'Monochrome', 'candy' => 'Candy' ) as $key => $label ) : ?>
							<label class="dwm-theme-option dwm-bar-theme-option">
								<input type="radio" name="dwm_editor_theme_bar" value="<?php echo esc_attr( $key ); ?>"<?php checked( 'classic', $key ); ?>>
								<div class="dwm-theme-preview dwm-bar-theme-preview dwm-bar-theme-<?php echo esc_attr( $key ); ?>">
									<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php echo esc_html( $label ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
									<div class="dwm-bar-theme-bars"><span></span><span></span><span></span><span></span></div>
								</div>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div id="dwm-editor-theme-options-line" class="dwm-editor-theme-options" style="display:none">
					<div class="dwm-step10-line-themes-grid">
						<?php foreach ( array( 'classic' => 'Classic', 'sunset' => 'Sunset', 'forest' => 'Forest', 'oceanic' => 'Oceanic', 'monochrome' => 'Monochrome', 'candy' => 'Candy' ) as $key => $label ) : ?>
							<label class="dwm-theme-option dwm-line-theme-option">
								<input type="radio" name="dwm_editor_theme_line" value="<?php echo esc_attr( $key ); ?>"<?php checked( 'classic', $key ); ?>>
								<div class="dwm-theme-preview dwm-line-theme-preview dwm-line-theme-<?php echo esc_attr( $key ); ?>">
									<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php echo esc_html( $label ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
									<div class="dwm-line-theme-lines"><span></span><span></span><span></span></div>
								</div>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div id="dwm-editor-theme-options-pie" class="dwm-editor-theme-options" style="display:none">
					<div class="dwm-step10-pie-themes-grid">
						<?php foreach ( array( 'classic' => 'Classic', 'sunset' => 'Sunset', 'forest' => 'Forest', 'oceanic' => 'Oceanic', 'monochrome' => 'Monochrome', 'candy' => 'Candy' ) as $key => $label ) : ?>
							<label class="dwm-theme-option dwm-pie-theme-option">
								<input type="radio" name="dwm_editor_theme_pie" value="<?php echo esc_attr( $key ); ?>"<?php checked( 'classic', $key ); ?>>
								<div class="dwm-theme-preview dwm-pie-theme-preview dwm-pie-theme-<?php echo esc_attr( $key ); ?>">
									<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php echo esc_html( $label ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
									<div class="dwm-pie-theme-circle"></div>
								</div>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div id="dwm-editor-theme-options-doughnut" class="dwm-editor-theme-options" style="display:none">
					<div class="dwm-step10-doughnut-themes-grid">
						<?php foreach ( array( 'classic' => 'Classic', 'sunset' => 'Sunset', 'forest' => 'Forest', 'oceanic' => 'Oceanic', 'monochrome' => 'Monochrome', 'candy' => 'Candy' ) as $key => $label ) : ?>
							<label class="dwm-theme-option dwm-doughnut-theme-option">
								<input type="radio" name="dwm_editor_theme_doughnut" value="<?php echo esc_attr( $key ); ?>"<?php checked( 'classic', $key ); ?>>
								<div class="dwm-theme-preview dwm-doughnut-theme-preview dwm-doughnut-theme-<?php echo esc_attr( $key ); ?>">
									<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php echo esc_html( $label ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
									<div class="dwm-doughnut-theme-circle"></div>
								</div>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div id="dwm-editor-theme-options-list" class="dwm-editor-theme-options" style="display:none">
					<div class="dwm-step10-list-themes-grid">
						<?php foreach ( array( 'clean' => 'Clean', 'bordered' => 'Bordered', 'striped' => 'Striped', 'compact' => 'Compact', 'dark' => 'Dark', 'accent' => 'Accent' ) as $key => $label ) : ?>
							<label class="dwm-theme-option dwm-list-theme-option">
								<input type="radio" name="dwm_editor_theme_list" value="<?php echo esc_attr( $key ); ?>"<?php checked( 'clean', $key ); ?>>
								<div class="dwm-theme-preview dwm-list-theme-preview dwm-list-theme-<?php echo esc_attr( $key ); ?>">
									<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php echo esc_html( $label ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
									<div class="dwm-list-theme-items"><span></span><span></span><span></span></div>
								</div>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div id="dwm-editor-theme-options-button" class="dwm-editor-theme-options" style="display:none">
					<div class="dwm-step10-button-themes-grid">
						<?php foreach ( array( 'solid' => 'Solid', 'outline' => 'Outline', 'pill' => 'Pill', 'flat' => 'Flat', 'gradient' => 'Gradient', 'dark' => 'Dark' ) as $key => $label ) : ?>
							<label class="dwm-theme-option dwm-button-theme-option">
								<input type="radio" name="dwm_editor_theme_button" value="<?php echo esc_attr( $key ); ?>"<?php checked( 'solid', $key ); ?>>
								<div class="dwm-theme-preview dwm-button-theme-preview dwm-button-theme-<?php echo esc_attr( $key ); ?>">
									<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php echo esc_html( $label ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
									<div class="dwm-button-theme-btns"><span></span><span></span></div>
								</div>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div id="dwm-editor-theme-options-card-list" class="dwm-editor-theme-options" style="display:none">
					<div class="dwm-step10-card-list-themes-grid">
						<?php foreach ( array( 'elevated' => 'Elevated', 'flat' => 'Flat', 'bordered' => 'Bordered', 'minimal' => 'Minimal', 'dark' => 'Dark', 'colorful' => 'Colorful' ) as $key => $label ) : ?>
							<label class="dwm-theme-option dwm-card-list-theme-option">
								<input type="radio" name="dwm_editor_theme_card-list" value="<?php echo esc_attr( $key ); ?>"<?php checked( 'elevated', $key ); ?>>
								<div class="dwm-theme-preview dwm-card-list-theme-preview dwm-card-list-theme-<?php echo esc_attr( $key ); ?>">
									<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php echo esc_html( $label ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
									<div class="dwm-card-list-theme-cards"><span></span><span></span></div>
								</div>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

				<div class="dwm-builder-section">
				<div class="dwm-builder-label"><span class="dashicons dashicons-editor-code"></span> <?php esc_html_e( 'Widget Template', 'dashboard-widget-manager' ); ?> <button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-html-php-templates" aria-label="<?php esc_attr_e( 'Widget Template help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button></div>
				<p class="description"><?php esc_html_e( 'The HTML/PHP template is auto-genrated, but can be manually modified to customize how your widget renders on the dashboard.', 'dashboard-widget-manager' ); ?></p>
				<div class="dwm-form-group">
					<div class="dwm-query-editor-header">
						<div class="dwm-query-edit-toggle">
							<label class="dwm-toggle" for="dwm-template-edit-toggle">
								<input type="checkbox" id="dwm-template-edit-toggle">
								<span class="dwm-toggle-slider"></span>
							</label>
							<span class="dwm-query-edit-toggle-text"><?php esc_html_e( 'Allow Editing', 'dashboard-widget-manager' ); ?></span>
						</div>
						<button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-variable-interpolation" aria-label="<?php esc_attr_e( 'Template variables help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button>
					</div>
					<textarea id="dwm-widget-template" name="template" class="dwm-code-editor" rows="15"></textarea>
				</div>
				</div><!-- /.dwm-builder-section[widget-template] -->

		</div>

		<!-- Styles Tab -->
		<div class="dwm-tab-content" data-tab="styles">

			<div class="dwm-builder-intro dwm-chart-mode-notice" style="display:none">
				<p><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e( 'Chart mode is active. Chart.js handles styling automatically. Add CSS here only to style custom template content above the chart.', 'dashboard-widget-manager' ); ?></p>
			</div>
				<div class="dwm-builder-section">
				<div class="dwm-builder-label"><span class="dashicons dashicons-art"></span> <?php esc_html_e( 'Widget Styles', 'dashboard-widget-manager' ); ?> <button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-custom-css" aria-label="<?php esc_attr_e( 'Widget Styles help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button></div>
				<p class="description"><?php esc_html_e( 'CSS will be automatically generated based on your chosen Display Type and Theme. Enable Editing to add custom styling to this widget.', 'dashboard-widget-manager' ); ?></p>
				<div class="dwm-form-group">
					<div class="dwm-query-editor-header">
						<div class="dwm-query-edit-toggle">
							<label class="dwm-toggle" for="dwm-styles-edit-toggle">
								<input type="checkbox" id="dwm-styles-edit-toggle">
								<span class="dwm-toggle-slider"></span>
							</label>
							<span class="dwm-query-edit-toggle-text"><?php esc_html_e( 'Allow Editing', 'dashboard-widget-manager' ); ?></span>
						</div>
					</div>
					<textarea id="dwm-widget-styles" name="styles" class="dwm-code-editor" rows="15"></textarea>
				</div>
				</div><!-- /.dwm-builder-section[widget-styles] -->
		</div>

		<!-- Scripts Tab -->
		<div class="dwm-tab-content" data-tab="scripts">

			<div class="dwm-builder-intro dwm-chart-mode-notice" style="display:none">
				<p><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e( 'Chart mode is active. Chart.js is loaded automatically. Add JavaScript here only for extra interactivity beyond the chart.', 'dashboard-widget-manager' ); ?></p>
			</div>
				<div class="dwm-builder-section">
				<div class="dwm-builder-label"><span class="dashicons dashicons-media-code"></span> <?php esc_html_e( 'Widget Scripts', 'dashboard-widget-manager' ); ?> <button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-custom-js" aria-label="<?php esc_attr_e( 'Widget Scripts help', 'dashboard-widget-manager' ); ?>"><span class="dashicons dashicons-editor-help"></span></button></div>
				<p class="description"><?php esc_html_e( 'Add custom JavaScript for this widget. jQuery is available and runs when the widget loads.', 'dashboard-widget-manager' ); ?></p>
				<p class="description"><strong><?php esc_html_e( 'Custom JS runs for all users who can see this widget.', 'dashboard-widget-manager' ); ?></strong></p>
				<div class="dwm-form-group">
					<div class="dwm-query-editor-header">
						<div class="dwm-query-edit-toggle">
							<label class="dwm-toggle" for="dwm-scripts-edit-toggle">
								<input type="checkbox" id="dwm-scripts-edit-toggle">
								<span class="dwm-toggle-slider"></span>
							</label>
							<span class="dwm-query-edit-toggle-text"><?php esc_html_e( 'Allow Editing', 'dashboard-widget-manager' ); ?></span>
						</div>
					</div>
					<textarea id="dwm-widget-scripts" name="scripts" class="dwm-code-editor" rows="15"></textarea>
				</div>
				</div><!-- /.dwm-builder-section[widget-scripts] -->
		</div>
	</div>

</form>

<!-- Query Results Preview Modal -->
<div class="dwm-results-modal" id="dwm-results-modal">
	<div class="dwm-results-modal-content">
		<div class="dwm-results-modal-header">
			<h3><?php esc_html_e( 'Query Results Preview', 'dashboard-widget-manager' ); ?></h3>
			<button type="button" class="dwm-results-modal-close" id="dwm-results-modal-close">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-results-modal-body">
			<div class="dwm-results-query-section">
				<strong><?php esc_html_e( 'Executed Query:', 'dashboard-widget-manager' ); ?></strong>
				<pre id="dwm-results-query"></pre>
			</div>
			<div class="dwm-results-controls">
				<div class="dwm-results-search">
					<input type="text" id="dwm-results-search" placeholder="<?php esc_attr_e( 'Search results...', 'dashboard-widget-manager' ); ?>">
					<span class="dashicons dashicons-search"></span>
				</div>
				<div class="dwm-results-info">
					<span id="dwm-results-showing"></span>
				</div>
			</div>
			<div class="dwm-results-table-wrapper">
				<table class="dwm-results-table" id="dwm-results-table">
					<thead id="dwm-results-thead"></thead>
					<tbody id="dwm-results-tbody"></tbody>
				</table>
				<div class="dwm-results-empty" id="dwm-results-empty" style="display:none;">
					<?php esc_html_e( 'No results found.', 'dashboard-widget-manager' ); ?>
				</div>
			</div>
			<div class="dwm-results-pagination" id="dwm-results-pagination"></div>
		</div>
	</div>
</div>
