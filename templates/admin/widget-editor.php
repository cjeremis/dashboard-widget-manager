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

	<div class="dwm-tabs">
		<nav class="dwm-tab-nav">
			<button type="button" class="dwm-tab-link active" data-tab="query">
				<?php esc_html_e( 'Query', 'dashboard-widget-manager' ); ?>
			</button>
			<button type="button" class="dwm-tab-link" data-tab="template">
				<?php esc_html_e( 'Template', 'dashboard-widget-manager' ); ?>
			</button>
			<button type="button" class="dwm-tab-link" data-tab="styles">
				<?php esc_html_e( 'Styles', 'dashboard-widget-manager' ); ?>
			</button>
			<button type="button" class="dwm-tab-link" data-tab="scripts">
				<?php esc_html_e( 'Scripts', 'dashboard-widget-manager' ); ?>
			</button>
		</nav>

		<!-- Query Tab -->
		<div class="dwm-tab-content active" data-tab="query">
			<div class="dwm-form-group">
				<label for="dwm-widget-name"><?php esc_html_e( 'Widget Name', 'dashboard-widget-manager' ); ?> *</label>
				<input type="text" id="dwm-widget-name" name="name" class="dwm-input-text" required>
			</div>

			<div class="dwm-form-group">
				<label for="dwm-widget-description"><?php esc_html_e( 'Description', 'dashboard-widget-manager' ); ?></label>
				<textarea id="dwm-widget-description" name="description" class="dwm-textarea" rows="3"></textarea>
			</div>

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

			<div class="dwm-form-row">
				<div class="dwm-form-group">
					<label for="dwm-cache-duration"><?php esc_html_e( 'Cache Duration (seconds)', 'dashboard-widget-manager' ); ?></label>
					<input type="number" id="dwm-cache-duration" name="cache_duration" class="dwm-input-number" value="300" min="0" max="3600">
					<p class="description"><?php esc_html_e( '0 = disabled, max 3600 (1 hour)', 'dashboard-widget-manager' ); ?></p>
				</div>

				<div class="dwm-form-group">
					<label for="dwm-widget-order"><?php esc_html_e( 'Display Order', 'dashboard-widget-manager' ); ?></label>
					<input type="number" id="dwm-widget-order" name="widget_order" class="dwm-input-number" value="0" min="0">
				</div>
			</div>

			<div class="dwm-form-group">
				<label>
					<input type="checkbox" id="dwm-widget-enabled" name="enabled" value="1">
					<?php esc_html_e( 'Enable widget on dashboard', 'dashboard-widget-manager' ); ?>
				</label>
			</div>
		</div>

		<!-- Template Tab -->
		<div class="dwm-tab-content" data-tab="template">
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
			<div class="dwm-form-group">
				<label for="dwm-widget-scripts"><?php esc_html_e( 'Custom JavaScript', 'dashboard-widget-manager' ); ?></label>
				<textarea id="dwm-widget-scripts" name="scripts" class="dwm-code-editor" rows="15"></textarea>
				<p class="description">
					<?php esc_html_e( 'JavaScript will be loaded when the widget is displayed. jQuery is available.', 'dashboard-widget-manager' ); ?>
				</p>
			</div>
		</div>
	</div>

	<div class="dwm-form-actions">
		<button type="submit" class="button button-primary button-large" id="dwm-save-widget" disabled>
			<?php esc_html_e( 'Save Widget', 'dashboard-widget-manager' ); ?>
		</button>
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
