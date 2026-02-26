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
				<?php esc_html_e( 'Builder', 'dashboard-widget-manager' ); ?>
			</button>
			<button type="button" class="dwm-tab-link" data-tab="query">
				<span class="dashicons dashicons-database"></span>
				<?php esc_html_e( 'Query', 'dashboard-widget-manager' ); ?>
				<span class="dwm-tab-badge"><?php esc_html_e( 'Advanced', 'dashboard-widget-manager' ); ?></span>
			</button>
			<button type="button" class="dwm-tab-link" data-tab="caching">
				<span class="dashicons dashicons-performance"></span>
				<?php esc_html_e( 'Caching', 'dashboard-widget-manager' ); ?>
			</button>
			<button type="button" class="dwm-tab-link" data-tab="template" data-pro-locked="true">
				<span class="dashicons dashicons-editor-code"></span>
				<?php esc_html_e( 'Template', 'dashboard-widget-manager' ); ?>
				<span class="dwm-tab-badge dwm-tab-badge--pro"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
			</button>
			<button type="button" class="dwm-tab-link" data-tab="styles" data-pro-locked="true">
				<span class="dashicons dashicons-art"></span>
				<?php esc_html_e( 'Styles', 'dashboard-widget-manager' ); ?>
				<span class="dwm-tab-badge dwm-tab-badge--pro"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
			</button>
			<button type="button" class="dwm-tab-link" data-tab="scripts" data-pro-locked="true">
				<span class="dashicons dashicons-media-code"></span>
				<?php esc_html_e( 'Scripts', 'dashboard-widget-manager' ); ?>
				<span class="dwm-tab-badge dwm-tab-badge--pro"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
			</button>
		</nav>

		<!-- Builder Tab -->
		<div class="dwm-tab-content active" data-tab="builder">

			<!-- Widget name & description -->
			<div class="dwm-builder-section no-padding-bottom">
				<div class="dwm-form-row no-bottom-border">
					<div class="dwm-form-group no-bottom-border">
						<label for="dwm-widget-name"><?php esc_html_e( 'Widget Name', 'dashboard-widget-manager' ); ?> *</label>
						<input type="text" id="dwm-widget-name" name="name" class="dwm-input-text" required placeholder="<?php esc_attr_e( 'e.g. Recent Posts', 'dashboard-widget-manager' ); ?>">
					</div>
					<div class="dwm-form-group no-bottom-border">
						<label for="dwm-widget-description"><?php esc_html_e( 'Description', 'dashboard-widget-manager' ); ?></label>
						<textarea id="dwm-widget-description" name="description" class="dwm-input-text" rows="3" placeholder="<?php esc_attr_e( 'Optional description', 'dashboard-widget-manager' ); ?>"></textarea>
					</div>
				</div>
			</div>

			<!-- Display Mode -->
			<div class="dwm-builder-section">
				<label class="dwm-builder-label"><?php esc_html_e( 'Display Mode', 'dashboard-widget-manager' ); ?></label>
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
				</div>
			</div>

			<!-- Primary Table -->
			<div class="dwm-builder-section">
				<label class="dwm-builder-label" for="dwm-builder-table">
					<span class="dashicons dashicons-database"></span>
					<?php esc_html_e( 'Primary Table', 'dashboard-widget-manager' ); ?>
				</label>
				<select id="dwm-builder-table" class="dwm-select">
					<option value=""><?php esc_html_e( 'Loading tables…', 'dashboard-widget-manager' ); ?></option>
				</select>
			</div>

			<!-- Columns -->
			<div class="dwm-builder-section" id="dwm-builder-columns-section" style="display:none">
				<label class="dwm-builder-label">
					<span class="dashicons dashicons-list-view"></span>
					<?php esc_html_e( 'Columns to Select', 'dashboard-widget-manager' ); ?>
					<span class="description"><?php esc_html_e( '(leave all unchecked to select all)', 'dashboard-widget-manager' ); ?></span>
				</label>
				<div id="dwm-builder-columns-list" class="dwm-builder-checkboxes"></div>
			</div>

			<!-- Joins -->
			<div class="dwm-builder-section" id="dwm-builder-joins-section" style="display:none">
				<label class="dwm-builder-label">
					<span class="dashicons dashicons-networking"></span>
					<?php esc_html_e( 'Joins', 'dashboard-widget-manager' ); ?>
					<span class="description"><?php esc_html_e( '(optional)', 'dashboard-widget-manager' ); ?></span>
				</label>
				<div id="dwm-builder-joins-list"></div>
				<button type="button" class="dwm-button dwm-button-secondary" id="dwm-builder-add-join">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Add Join', 'dashboard-widget-manager' ); ?>
				</button>
			</div>

			<!-- Conditions (WHERE) -->
			<div class="dwm-builder-section" id="dwm-builder-conditions-section" style="display:none">
				<label class="dwm-builder-label">
					<span class="dashicons dashicons-filter"></span>
					<?php esc_html_e( 'Filters (WHERE)', 'dashboard-widget-manager' ); ?>
					<span class="description"><?php esc_html_e( '(optional)', 'dashboard-widget-manager' ); ?></span>
				</label>
				<div id="dwm-builder-conditions-list"></div>
				<button type="button" class="dwm-button dwm-button-secondary" id="dwm-builder-add-condition">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Add Filter', 'dashboard-widget-manager' ); ?>
				</button>
			</div>

			<!-- Order & Limit -->
			<div class="dwm-builder-section" id="dwm-builder-order-section" style="display:none">
				<div class="dwm-builder-row">
					<div class="dwm-form-group">
						<label class="dwm-builder-label" for="dwm-builder-order-col">
							<span class="dashicons dashicons-sort"></span>
							<?php esc_html_e( 'Order By', 'dashboard-widget-manager' ); ?>
						</label>
						<div class="dwm-input-row">
							<select id="dwm-builder-order-col" class="dwm-select">
								<option value=""><?php esc_html_e( '— none —', 'dashboard-widget-manager' ); ?></option>
							</select>
							<select id="dwm-builder-order-dir" class="dwm-select">
								<option value="DESC"><?php esc_html_e( 'Newest First (DESC)', 'dashboard-widget-manager' ); ?></option>
								<option value="ASC"><?php esc_html_e( 'Oldest First (ASC)', 'dashboard-widget-manager' ); ?></option>
							</select>
						</div>
					</div>
					<div class="dwm-form-group">
						<label class="dwm-builder-label" for="dwm-builder-limit">
							<span class="dashicons dashicons-controls-repeat"></span>
							<?php esc_html_e( 'Limit', 'dashboard-widget-manager' ); ?>
						</label>
						<input type="number" id="dwm-builder-limit" class="dwm-input-number" value="10" min="1" max="100">
					</div>
				</div>
			</div>

			<!-- Chart Options -->
			<div class="dwm-builder-section" id="dwm-builder-chart-options" style="display:none">
				<label class="dwm-builder-label">
					<span class="dashicons dashicons-chart-bar"></span>
					<?php esc_html_e( 'Chart Configuration', 'dashboard-widget-manager' ); ?>
				</label>
				<div class="dwm-form-row">
					<div class="dwm-form-group">
						<label for="dwm-builder-chart-label"><?php esc_html_e( 'Label Column', 'dashboard-widget-manager' ); ?> *</label>
						<select id="dwm-builder-chart-label" class="dwm-select">
							<option value=""><?php esc_html_e( '— select column —', 'dashboard-widget-manager' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Column used for X-axis labels or pie slice names.', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-form-group">
						<label><?php esc_html_e( 'Data Column(s)', 'dashboard-widget-manager' ); ?> *</label>
						<div id="dwm-builder-chart-data-list" class="dwm-builder-checkboxes"></div>
						<p class="description"><?php esc_html_e( 'Numeric column(s) to plot as datasets.', 'dashboard-widget-manager' ); ?></p>
					</div>
				</div>
				<div class="dwm-form-row">
					<div class="dwm-form-group">
						<label for="dwm-builder-chart-title"><?php esc_html_e( 'Chart Title', 'dashboard-widget-manager' ); ?></label>
						<input type="text" id="dwm-builder-chart-title" class="dwm-input-text" placeholder="<?php esc_attr_e( 'Optional title shown above the chart', 'dashboard-widget-manager' ); ?>">
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

			<!-- Widget Settings + Apply -->
			<div class="dwm-builder-section" id="dwm-builder-apply-section" style="display:none">
				<div class="dwm-form-row">
					<div class="dwm-form-group">
						<label for="dwm-widget-order"><?php esc_html_e( 'Display Order', 'dashboard-widget-manager' ); ?></label>
						<input type="number" id="dwm-widget-order" name="widget_order" class="dwm-input-number" value="0" min="0">
					</div>
					<div class="dwm-form-group">
						<label for="dwm-widget-status"><?php esc_html_e( 'Status', 'dashboard-widget-manager' ); ?></label>
						<select id="dwm-widget-status" name="status" class="dwm-select">
							<option value="draft"><?php esc_html_e( 'Draft', 'dashboard-widget-manager' ); ?></option>
							<option value="publish"><?php esc_html_e( 'Publish', 'dashboard-widget-manager' ); ?></option>
							<option value="archived"><?php esc_html_e( 'Archived', 'dashboard-widget-manager' ); ?></option>
							<option value="trash"><?php esc_html_e( 'Trash', 'dashboard-widget-manager' ); ?></option>
						</select>
					</div>
				</div>

				<div class="dwm-builder-actions">
					<button type="button" class="dwm-button dwm-button-primary" id="dwm-builder-apply">
						<span class="dashicons dashicons-database-view"></span>
						<?php esc_html_e( 'Build & Validate Query', 'dashboard-widget-manager' ); ?>
					</button>
					<p class="description"><?php esc_html_e( 'Generates SQL + template, then validates. After validation, Save Widget becomes available.', 'dashboard-widget-manager' ); ?></p>
				</div>
			</div>

		</div><!-- /.dwm-tab-content[builder] -->

		<!-- Query Tab -->
		<div class="dwm-tab-content" data-tab="query">

			<!-- Logging & execution time — compact row above the editor -->
			<div class="dwm-form-row dwm-query-meta-row">
				<div class="dwm-form-group">
					<label for="dwm-enable-query-logging"><?php esc_html_e( 'Enable Query Logging', 'dashboard-widget-manager' ); ?></label>
					<label class="dwm-toggle">
						<input type="checkbox" id="dwm-enable-query-logging" name="enable_query_logging" value="1">
						<span class="dwm-toggle-slider"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Log execution times to the debug log (requires WP_DEBUG_LOG).', 'dashboard-widget-manager' ); ?></p>
				</div>
				<div class="dwm-form-group">
					<label for="dwm-max-execution-time"><?php esc_html_e( 'Max Execution Time (seconds)', 'dashboard-widget-manager' ); ?></label>
					<input type="number" id="dwm-max-execution-time" name="max_execution_time" class="dwm-input-number" value="30" min="1" max="60">
					<p class="description"><?php esc_html_e( 'Abort query if it exceeds this limit (1–60 seconds).', 'dashboard-widget-manager' ); ?></p>
				</div>
			</div>

			<!-- SQL query editor -->
			<div class="dwm-form-group">
				<label for="dwm-widget-query"><?php esc_html_e( 'SQL Query', 'dashboard-widget-manager' ); ?> *</label>
				<div class="dwm-query-editor-wrapper">
					<textarea id="dwm-widget-query" name="sql_query" class="dwm-code-editor" rows="10" required></textarea>
					<button type="button" class="dwm-query-info-btn" id="dwm-query-info-btn" title="<?php esc_attr_e( 'Query Help', 'dashboard-widget-manager' ); ?>">
						<span class="dashicons dashicons-info"></span>
					</button>
					<div class="dwm-query-info-popup" id="dwm-query-info-popup">
						<div class="dwm-query-info-section">
							<strong><?php esc_html_e( 'Allowed Queries', 'dashboard-widget-manager' ); ?></strong>
							<p><?php esc_html_e( 'Only SELECT queries are allowed. JOINs, WHERE, GROUP BY, ORDER BY, and LIMIT clauses are supported.', 'dashboard-widget-manager' ); ?></p>
						</div>
						<div class="dwm-query-info-section">
							<strong><?php esc_html_e( 'Available Variables', 'dashboard-widget-manager' ); ?></strong>
							<ul class="dwm-variables-list">
								<li><code>{{current_user_id}}</code> - <?php esc_html_e( 'Current user ID', 'dashboard-widget-manager' ); ?></li>
								<li><code>{{site_url}}</code> - <?php esc_html_e( 'Site URL', 'dashboard-widget-manager' ); ?></li>
								<li><code>{{table_prefix}}</code> - <?php esc_html_e( 'Database table prefix', 'dashboard-widget-manager' ); ?></li>
								<li><code>{{posts}}</code> - <?php esc_html_e( 'Posts table name', 'dashboard-widget-manager' ); ?></li>
								<li><code>{{users}}</code> - <?php esc_html_e( 'Users table name', 'dashboard-widget-manager' ); ?></li>
								<li><code>{{comments}}</code> - <?php esc_html_e( 'Comments table name', 'dashboard-widget-manager' ); ?></li>
							</ul>
						</div>
					</div>
				</div>
				<div class="dwm-query-stats">
					<span class="dwm-char-count"><?php esc_html_e( '0 characters', 'dashboard-widget-manager' ); ?></span>
					<span class="dwm-line-count"><?php esc_html_e( '0 lines', 'dashboard-widget-manager' ); ?></span>
				</div>
				<div class="dwm-query-actions">
					<button type="button" id="dwm-validate-query" class="button">
						<?php esc_html_e( 'Validate Query', 'dashboard-widget-manager' ); ?>
					</button>
					<span id="dwm-validation-result"></span>
				</div>
			</div>
		</div>

		<!-- Caching Tab -->
		<div class="dwm-tab-content" data-tab="caching">
			<div class="dwm-form-row">
				<div class="dwm-form-group">
					<label for="dwm-enable-caching"><?php esc_html_e( 'Enable Caching', 'dashboard-widget-manager' ); ?></label>
					<label class="dwm-toggle">
						<input type="checkbox" id="dwm-enable-caching" name="enable_caching" value="1" checked>
						<span class="dwm-toggle-slider"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Cache query results to improve performance and reduce database load.', 'dashboard-widget-manager' ); ?></p>
				</div>
				<div class="dwm-form-group">
					<label for="dwm-cache-duration"><?php esc_html_e( 'Cache Duration (seconds)', 'dashboard-widget-manager' ); ?></label>
					<input type="number" id="dwm-cache-duration" name="cache_duration" class="dwm-input-number" value="300" min="0" max="3600">
					<p class="description"><?php esc_html_e( '0 = no cache, max 3600 (1 hour).', 'dashboard-widget-manager' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Template Tab -->
		<div class="dwm-tab-content" data-tab="template">
			<div class="dwm-builder-intro dwm-chart-mode-notice" style="display:none">
				<p><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e( 'Chart mode is active. The chart is rendered automatically from your query results — a template is not required. You can add HTML above the chart here if needed.', 'dashboard-widget-manager' ); ?></p>
			</div>
			<div class="dwm-form-group">
				<label for="dwm-widget-template"><?php esc_html_e( 'HTML/PHP Template', 'dashboard-widget-manager' ); ?></label>
				<textarea id="dwm-widget-template" name="template" class="dwm-code-editor" rows="15"></textarea>
				<p class="description">
					<?php esc_html_e( 'Use {{variable_name}} to display query results. Access $query_results array for advanced templating.', 'dashboard-widget-manager' ); ?>
					<br>
					<?php esc_html_e( 'Escaping: {{esc_html:var}}, {{esc_url:var}}, {{esc_attr:var}}', 'dashboard-widget-manager' ); ?>
				</p>
			</div>

			<div class="dwm-template-example">
				<strong><?php esc_html_e( 'Example Template:', 'dashboard-widget-manager' ); ?></strong>
				<pre>&lt;ul&gt;
	&lt;?php foreach ( $query_results as $row ) : ?&gt;
		&lt;li&gt;{{esc_html:column_name}}&lt;/li&gt;
	&lt;?php endforeach; ?&gt;
&lt;/ul&gt;</pre>
			</div>
		</div>

		<!-- Styles Tab -->
		<div class="dwm-tab-content" data-tab="styles">
			<div class="dwm-builder-intro dwm-chart-mode-notice" style="display:none">
				<p><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e( 'Chart mode is active. Chart.js handles styling automatically. Add CSS here only to style custom template content above the chart.', 'dashboard-widget-manager' ); ?></p>
			</div>
			<div class="dwm-form-group">
				<label for="dwm-widget-styles"><?php esc_html_e( 'Custom CSS', 'dashboard-widget-manager' ); ?></label>
				<textarea id="dwm-widget-styles" name="styles" class="dwm-code-editor" rows="15"></textarea>
				<p class="description">
					<?php esc_html_e( 'CSS will be automatically scoped to this widget. No need for wrapper selectors.', 'dashboard-widget-manager' ); ?>
				</p>
			</div>
		</div>

		<!-- Scripts Tab -->
		<div class="dwm-tab-content" data-tab="scripts">
			<div class="dwm-builder-intro dwm-chart-mode-notice" style="display:none">
				<p><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e( 'Chart mode is active. Chart.js is loaded automatically. Add JavaScript here only for extra interactivity beyond the chart.', 'dashboard-widget-manager' ); ?></p>
			</div>
			<div class="dwm-form-group">
				<label for="dwm-widget-scripts"><?php esc_html_e( 'Custom JavaScript', 'dashboard-widget-manager' ); ?></label>
				<textarea id="dwm-widget-scripts" name="scripts" class="dwm-code-editor" rows="15"></textarea>
				<p class="description">
					<?php esc_html_e( 'JavaScript will be loaded when the widget is displayed. jQuery is available.', 'dashboard-widget-manager' ); ?>
				</p>
			</div>
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
