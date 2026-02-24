<?php
/**
 * Settings Template
 *
 * Plugin settings page.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data     = DWM_Data::get_instance();
$settings = $data->get_settings();
?>

<div class="wrap dwm-settings dwm-page-wrapper">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form id="dwm-settings-form">
		<?php wp_nonce_field( 'dwm_admin_nonce', 'dwm_settings_nonce' ); ?>

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="enable_caching"><?php esc_html_e( 'Enable Query Caching', 'dashboard-widget-manager' ); ?></label>
					</th>
					<td>
						<label class="dwm-toggle">
							<input type="checkbox" name="settings[enable_caching]" id="enable_caching" value="1"
								<?php checked( $settings['enable_caching'], true ); ?>>
							<span class="dwm-toggle-slider"></span>
						</label>
						<p class="description">
							<?php esc_html_e( 'Cache query results to improve performance.', 'dashboard-widget-manager' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="default_cache_duration"><?php esc_html_e( 'Default Cache Duration', 'dashboard-widget-manager' ); ?></label>
					</th>
					<td>
						<input type="number" name="settings[default_cache_duration]" id="default_cache_duration"
							value="<?php echo esc_attr( $settings['default_cache_duration'] ); ?>"
							min="0" max="3600" class="small-text"> <?php esc_html_e( 'seconds', 'dashboard-widget-manager' ); ?>
						<p class="description">
							<?php esc_html_e( 'Default cache duration for widgets (max 3600 seconds / 1 hour).', 'dashboard-widget-manager' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="max_execution_time"><?php esc_html_e( 'Maximum Execution Time', 'dashboard-widget-manager' ); ?></label>
					</th>
					<td>
						<input type="number" name="settings[max_execution_time]" id="max_execution_time"
							value="<?php echo esc_attr( $settings['max_execution_time'] ); ?>"
							min="1" max="60" class="small-text"> <?php esc_html_e( 'seconds', 'dashboard-widget-manager' ); ?>
						<p class="description">
							<?php esc_html_e( 'Maximum time allowed for query execution (max 60 seconds).', 'dashboard-widget-manager' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="enable_query_logging"><?php esc_html_e( 'Enable Query Logging', 'dashboard-widget-manager' ); ?></label>
					</th>
					<td>
						<label class="dwm-toggle">
							<input type="checkbox" name="settings[enable_query_logging]" id="enable_query_logging" value="1"
								<?php checked( $settings['enable_query_logging'], true ); ?>>
							<span class="dwm-toggle-slider"></span>
						</label>
						<p class="description">
							<?php esc_html_e( 'Log query execution times to debug log (requires WP_DEBUG_LOG).', 'dashboard-widget-manager' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="allowed_tables"><?php esc_html_e( 'Allowed Tables', 'dashboard-widget-manager' ); ?></label>
					</th>
					<td>
						<textarea name="settings[allowed_tables]" id="allowed_tables" rows="5" class="large-text code"><?php echo esc_textarea( $settings['allowed_tables'] ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Whitelist of allowed table names (one per line). Leave empty to allow all WordPress tables.', 'dashboard-widget-manager' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'dashboard-widget-manager' ); ?>
			</button>
			<button type="button" id="dwm-reset-settings" class="button">
				<?php esc_html_e( 'Reset to Defaults', 'dashboard-widget-manager' ); ?>
			</button>
		</p>
	</form>

	<div class="dwm-settings-info">
		<h2><?php esc_html_e( 'Security Information', 'dashboard-widget-manager' ); ?></h2>
		<ul>
			<li><?php esc_html_e( 'Only SELECT queries are allowed for security.', 'dashboard-widget-manager' ); ?></li>
			<li><?php esc_html_e( 'Only administrators can create and manage widgets.', 'dashboard-widget-manager' ); ?></li>
			<li><?php esc_html_e( 'All queries are validated before execution.', 'dashboard-widget-manager' ); ?></li>
			<li><?php esc_html_e( 'Custom SQL statements are restricted to read-only operations.', 'dashboard-widget-manager' ); ?></li>
		</ul>
	</div>
</div>
