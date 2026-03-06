<?php
/**
 * Widget Manager Class
 *
 * Handles widget CRUD operations via AJAX.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget Manager class.
 *
 * Manages widget CRUD operations.
 */
class DWM_Widget_Manager {

	use DWM_Singleton;
	use DWM_AJAX_Handler;

	/**
	 * Get all widgets via AJAX.
	 */
	public function ajax_get_widgets() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$data    = DWM_Data::get_instance();
		$widgets = $data->get_widgets();

		$this->send_success( __( 'Widgets retrieved successfully.', 'dashboard-widget-manager' ), array( 'widgets' => $widgets ) );
	}

	/**
	 * Get single widget via AJAX.
	 */
	public function ajax_get_widget() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$widget_id = isset( $_POST['widget_id'] ) ? absint( $_POST['widget_id'] ) : 0;

		if ( ! $widget_id ) {
			$this->send_error( __( 'Widget ID is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$data   = DWM_Data::get_instance();
		$widget = $data->get_widget( $widget_id );

		if ( ! $widget ) {
			$this->send_error( __( 'Widget not found.', 'dashboard-widget-manager' ), 404 );
			return;
		}

		$this->send_success( __( 'Widget retrieved successfully.', 'dashboard-widget-manager' ), array( 'widget' => $widget ) );
	}

	/**
	 * Create new widget via AJAX.
	 */
	public function ajax_create_widget() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$widget_data = isset( $_POST['widget'] ) ? wp_unslash( $_POST['widget'] ) : array();

		if ( empty( $widget_data ) ) {
			$this->send_error( __( 'Widget data is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		// Sanitize widget data.
		$widget_data = DWM_Sanitizer::sanitize_widget_data( $widget_data );

		// Validate widget data.
		$errors = DWM_Validator::validate_widget_data( $widget_data );
		if ( ! empty( $errors ) ) {
			$this->send_error( implode( ' ', $errors ), 400, array( 'errors' => $errors ) );
			return;
		}

		// Check table blacklist.
		$data            = DWM_Data::get_instance();
		$excluded_tables = $data->get_excluded_tables();
		if ( ! empty( $excluded_tables ) && ! empty( $widget_data['sql_query'] ) ) {
			$table_errors = DWM_Validator::validate_query_tables( $widget_data['sql_query'], $excluded_tables );
			if ( ! empty( $table_errors ) ) {
				$this->send_error( implode( ' ', $table_errors ), 400, array( 'errors' => $table_errors ) );
				return;
			}
		}

		// Create widget.
		$widget_id = $data->create_widget( $widget_data );

		if ( ! $widget_id ) {
			$this->send_error( __( 'Failed to create widget.', 'dashboard-widget-manager' ), 500 );
			return;
		}

		$this->send_success(
			__( 'Widget created successfully.', 'dashboard-widget-manager' ),
			array( 'widget_id' => $widget_id )
		);
	}

	/**
	 * Update existing widget via AJAX.
	 */
	public function ajax_update_widget() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$widget_id   = isset( $_POST['widget_id'] ) ? absint( $_POST['widget_id'] ) : 0;
		$widget_data = isset( $_POST['widget'] ) ? wp_unslash( $_POST['widget'] ) : array();

		if ( ! $widget_id ) {
			$this->send_error( __( 'Widget ID is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		if ( empty( $widget_data ) ) {
			$this->send_error( __( 'Widget data is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		// Sanitize widget data.
		$widget_data = DWM_Sanitizer::sanitize_widget_data( $widget_data );

		// Validate widget data.
		$errors = DWM_Validator::validate_widget_data( $widget_data );
		if ( ! empty( $errors ) ) {
			$this->send_error( implode( ' ', $errors ), 400, array( 'errors' => $errors ) );
			return;
		}

		// Check table blacklist.
		$data            = DWM_Data::get_instance();
		$excluded_tables = $data->get_excluded_tables();
		if ( ! empty( $excluded_tables ) && ! empty( $widget_data['sql_query'] ) ) {
			$table_errors = DWM_Validator::validate_query_tables( $widget_data['sql_query'], $excluded_tables );
			if ( ! empty( $table_errors ) ) {
				$this->send_error( implode( ' ', $table_errors ), 400, array( 'errors' => $table_errors ) );
				return;
			}
		}

		// Update widget.
		$result = $data->update_widget( $widget_id, $widget_data );

		if ( ! $result ) {
			$this->send_error( __( 'Failed to update widget.', 'dashboard-widget-manager' ), 500 );
			return;
		}

		$this->send_success( __( 'Widget updated successfully.', 'dashboard-widget-manager' ) );
	}

	/**
	 * Soft-delete widget (move to trash) via AJAX.
	 */
	public function ajax_delete_widget() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$widget_id = isset( $_POST['widget_id'] ) ? absint( $_POST['widget_id'] ) : 0;

		if ( ! $widget_id ) {
			$this->send_error( __( 'Widget ID is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$data   = DWM_Data::get_instance();
		$result = $data->trash_widget( $widget_id );

		if ( ! $result ) {
			$this->send_error( __( 'Failed to trash widget.', 'dashboard-widget-manager' ), 500 );
			return;
		}

		$this->send_success( __( 'Widget moved to trash.', 'dashboard-widget-manager' ) );
	}

	/**
	 * Permanently delete widget via AJAX.
	 */
	public function ajax_permanent_delete() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$widget_id = isset( $_POST['widget_id'] ) ? absint( $_POST['widget_id'] ) : 0;

		if ( ! $widget_id ) {
			$this->send_error( __( 'Widget ID is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$data   = DWM_Data::get_instance();
		$widget = $data->get_widget( $widget_id );

		if ( ! $widget || 'trash' !== $widget['status'] ) {
			$this->send_error( __( 'Only trashed widgets can be permanently deleted.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$result = $data->delete_widget( $widget_id );

		if ( ! $result ) {
			$this->send_error( __( 'Failed to delete widget.', 'dashboard-widget-manager' ), 500 );
			return;
		}

		$this->send_success( __( 'Widget permanently deleted.', 'dashboard-widget-manager' ) );
	}

	/**
	 * Empty trash (permanently delete all trashed widgets) via AJAX.
	 */
	public function ajax_empty_trash() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$data    = DWM_Data::get_instance();
		$widgets = $data->get_widgets();
		$count   = 0;

		foreach ( $widgets as $widget ) {
			if ( 'trash' === ( $widget['status'] ?? '' ) ) {
				$data->delete_widget( (int) $widget['id'] );
				$count++;
			}
		}

		$this->send_success(
			sprintf( __( '%d widget(s) permanently deleted.', 'dashboard-widget-manager' ), $count ),
			array( 'count' => $count )
		);
	}

	/**
	 * Toggle widget status via AJAX (publish/draft toggle).
	 */
	public function ajax_toggle_widget() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$widget_id = isset( $_POST['widget_id'] ) ? absint( $_POST['widget_id'] ) : 0;
		$status    = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

		if ( ! $widget_id ) {
			$this->send_error( __( 'Widget ID is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		if ( ! in_array( $status, array( 'publish', 'draft' ), true ) ) {
			$this->send_error( __( 'Invalid status for toggle.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$data   = DWM_Data::get_instance();
		$result = $data->update_widget_status( $widget_id, $status );

		if ( ! $result ) {
			$this->send_error( __( 'Failed to update widget status.', 'dashboard-widget-manager' ), 500 );
			return;
		}

		$widget        = $data->get_widget( $widget_id );
		$response_data = array( 'status' => $status );
		if ( $widget && ! empty( $widget['first_published_at'] ) ) {
			$response_data['first_published_at'] = date_i18n( 'M j, y', strtotime( $widget['first_published_at'] ) );
		}

		$this->send_success(
			'publish' === $status
				? __( 'Widget published.', 'dashboard-widget-manager' )
				: __( 'Widget set to draft.', 'dashboard-widget-manager' ),
			$response_data
		);
	}

	/**
	 * Set widget enabled/disabled (dashboard visibility) independently of status.
	 */
	public function ajax_set_widget_enabled() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$widget_id = isset( $_POST['widget_id'] ) ? absint( $_POST['widget_id'] ) : 0;
		$enabled   = isset( $_POST['enabled'] ) ? (bool) absint( $_POST['enabled'] ) : false;

		if ( ! $widget_id ) {
			$this->send_error( __( 'Widget ID is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$data   = DWM_Data::get_instance();
		$result = $data->set_widget_enabled( $widget_id, $enabled );

		if ( ! $result ) {
			$this->send_error( __( 'Failed to update widget visibility.', 'dashboard-widget-manager' ), 500 );
			return;
		}

		$this->send_success(
			$enabled
				? __( 'Widget added to dashboard.', 'dashboard-widget-manager' )
				: __( 'Widget removed from dashboard.', 'dashboard-widget-manager' ),
			array( 'enabled' => $enabled ? 1 : 0 )
		);
	}

	/**
	 * Preview widget via AJAX.
	 */
	public function ajax_preview_widget() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$widget_id = isset( $_POST['widget_id'] ) ? absint( $_POST['widget_id'] ) : 0;

		if ( ! $widget_id ) {
			$this->send_error( __( 'Widget ID is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		// Get widget renderer.
		$renderer = DWM_Widget_Renderer::get_instance();

		// Capture output.
		ob_start();
		$renderer->render_widget( $widget_id, true );
		$output = ob_get_clean();

		// Also return the raw SQL query for the SQL Query tab.
		$data   = DWM_Data::get_instance();
		$widget = $data->get_widget( $widget_id );
		$query  = $widget ? ( $widget['sql_query'] ?? '' ) : '';

		$this->send_success(
			__( 'Preview generated successfully.', 'dashboard-widget-manager' ),
			array(
				'html'  => $output,
				'query' => $query,
			)
		);
	}

	/**
	 * Preview wizard configuration via AJAX.
	 */
	public function ajax_preview_wizard() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$wizard_data = isset( $_POST['wizard_data'] ) ? wp_unslash( $_POST['wizard_data'] ) : array();

		if ( empty( $wizard_data ) || ! is_array( $wizard_data ) ) {
			$this->send_error( __( 'Wizard data is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		// Build configuration from wizard data.
		$config = array();

		// Step config contains table and columns.
		if ( isset( $wizard_data['stepConfig'] ) && is_array( $wizard_data['stepConfig'] ) ) {
			$step_config = $wizard_data['stepConfig'];
			if ( isset( $step_config['table'] ) ) {
				$config['table'] = $step_config['table'];
			}
			if ( isset( $step_config['columns'] ) && is_array( $step_config['columns'] ) ) {
				$config['columns'] = $step_config['columns'];
			}
		}

		// Joins.
		if ( isset( $wizard_data['joins'] ) && is_array( $wizard_data['joins'] ) ) {
			$config['joins'] = $wizard_data['joins'];
		}

		// Conditions.
		if ( isset( $wizard_data['conditions'] ) && is_array( $wizard_data['conditions'] ) ) {
			$config['conditions'] = $wizard_data['conditions'];
		}

		// Sort columns and limit.
		if ( isset( $wizard_data['sorts'] ) && is_array( $wizard_data['sorts'] ) ) {
			$config['sorts'] = $wizard_data['sorts'];
		}
		if ( isset( $wizard_data['limit'] ) ) {
			$config['limit'] = $wizard_data['limit'];
		}

		// Display mode.
		if ( isset( $wizard_data['displayMode'] ) ) {
			$config['display_mode'] = $wizard_data['displayMode'];
		}

		// Output config for new display modes.
		if ( isset( $wizard_data['outputConfig'] ) ) {
			$raw_oc = is_string( $wizard_data['outputConfig'] )
				? $wizard_data['outputConfig']
				: wp_json_encode( $wizard_data['outputConfig'] );
			$config['output_config'] = DWM_Sanitizer::sanitize_output_config( $raw_oc );
		}

		// Use query builder to build SQL from config.
		$query_builder = DWM_Query_Builder::get_instance();
		$result        = $query_builder->build_query_from_config( $config );

		if ( is_wp_error( $result ) ) {
			$this->send_error( $result->get_error_message(), 400 );
			return;
		}

		$sql = $result['sql'];

		// Route through DWM_Query_Executor::test_query() for full validation pipeline
		// (validate_sql_query, parse_query_variables, excluded-table check).
		$executor = DWM_Query_Executor::get_instance();
		$rows     = $executor->test_query( $sql );

		if ( is_wp_error( $rows ) ) {
			$this->send_error( $rows->get_error_message(), 400 );
			return;
		}

		// Render preview HTML.
		$html = $this->render_preview_html( $rows, $config );

		$this->send_success(
			__( 'Preview generated successfully.', 'dashboard-widget-manager' ),
			array(
				'html'  => $html,
				'query' => $sql,
			)
		);
	}

	/**
	 * Render preview HTML from query results.
	 *
	 * Uses the output_config render pipeline when output_config is present
	 * in the config, otherwise falls back to a basic table.
	 *
	 * @param array $rows   Query results.
	 * @param array $config Widget configuration.
	 * @return string HTML output.
	 */
	private function render_preview_html( $rows, $config ) {
		if ( empty( $rows ) ) {
			return '<div class="dwm-widget-no-data"><p>' . esc_html__( 'No data found.', 'dashboard-widget-manager' ) . '</p></div>';
		}

		// If output_config is provided, use the new render pipeline.
		if ( ! empty( $config['output_config'] ) ) {
			$output_config = is_string( $config['output_config'] )
				? json_decode( $config['output_config'], true )
				: $config['output_config'];

			if ( is_array( $output_config ) ) {
				$renderer = DWM_Widget_Renderer::get_instance();
				return $renderer->render_with_output_config( $rows, $output_config, 0 );
			}
		}

		$display_mode = isset( $config['display_mode'] ) ? $config['display_mode'] : 'table';

		ob_start();
		?>
		<div class="dwm-widget-preview-table">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<?php foreach ( array_keys( $rows[0] ) as $col ) : ?>
							<th><?php echo esc_html( $col ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<?php foreach ( $row as $value ) : ?>
								<td><?php echo esc_html( $value ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Normalize a widget's output_config from its builder_config.
	 *
	 * Produces a default output_config from existing builder_config/template
	 * data. Used for migration of legacy widgets.
	 *
	 * @param array $widget Widget data array (from DB).
	 * @return array|null Normalized output_config array, or null if not applicable.
	 */
	public static function normalize_output_config( $widget ) {
		// If output_config already exists, decode and return it.
		if ( ! empty( $widget['output_config'] ) ) {
			$decoded = json_decode( $widget['output_config'], true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		// Try to derive from builder_config.
		$builder_config = isset( $widget['builder_config'] ) ? json_decode( (string) $widget['builder_config'], true ) : null;
		if ( ! is_array( $builder_config ) ) {
			return null;
		}

		$display_mode = isset( $builder_config['display_mode'] ) ? $builder_config['display_mode'] : 'table';

		// Chart modes are handled by their own pipeline, not output_config.
		$chart_modes = array( 'bar', 'line', 'pie', 'doughnut' );
		if ( in_array( $display_mode, $chart_modes, true ) ) {
			return null;
		}

		// Map builder_config display_mode to output_config display_mode.
		$valid_output_modes = DWM_Sanitizer::get_valid_display_modes();
		if ( ! in_array( $display_mode, $valid_output_modes, true ) ) {
			$display_mode = 'table';
		}

		$output_config = array(
			'display_mode' => $display_mode,
			'theme'        => 'theme-1',
			'columns'      => array(),
		);

		// Build columns from builder_config columns list.
		$columns = isset( $builder_config['columns'] ) && is_array( $builder_config['columns'] ) ? $builder_config['columns'] : array();
		foreach ( $columns as $col_name ) {
			$col_name = is_string( $col_name ) ? $col_name : '';
			if ( '' === $col_name ) {
				continue;
			}

			// Strip table prefix from column name (e.g. "wp_terms.name" -> "name").
			$display_name = $col_name;
			if ( false !== strpos( $col_name, '.' ) ) {
				$parts        = explode( '.', $col_name );
				$display_name = end( $parts );
			}

			$output_config['columns'][] = array(
				'key'       => $display_name,
				'alias'     => ucwords( str_replace( '_', ' ', $display_name ) ),
				'visible'   => true,
				'link'      => array(
					'enabled'         => false,
					'url'             => '',
					'open_in_new_tab' => true,
				),
				'formatter' => array(
					'type'    => 'text',
					'options' => array(),
				),
			);
		}

		return $output_config;
	}

	/**
	 * Validate query via AJAX.
	 */
	public function ajax_validate_query() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$query = isset( $_POST['query'] ) ? wp_unslash( $_POST['query'] ) : '';
		$max_execution_time = isset( $_POST['max_execution_time'] ) ? absint( $_POST['max_execution_time'] ) : 30;
		$max_execution_time = min( max( 1, $max_execution_time ), 60 );

		if ( empty( $query ) ) {
			$this->send_error( __( 'Query is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		// Sanitize query.
		$query = DWM_Sanitizer::sanitize_sql_query( $query );

		// Parse query variables to show the actual executed query.
		$executor     = DWM_Query_Executor::get_instance();
		$parsed_query = $executor->parse_query_variables( $query );

		// Track execution time.
		$start_time = microtime( true );

		// Validate query structure first.
		$validation_errors = $executor->validate_query( $query );
		if ( ! empty( $validation_errors ) ) {
			$this->send_error( implode( ' ', $validation_errors ), 400 );
			return;
		}

		// Check table blacklist against the parsed query (real table names after variable substitution).
		$data            = DWM_Data::get_instance();
		$excluded_tables = $data->get_excluded_tables();
		if ( ! empty( $excluded_tables ) ) {
			$table_errors = DWM_Validator::validate_query_tables( $parsed_query, $excluded_tables );
			if ( ! empty( $table_errors ) ) {
				$this->send_error( implode( ' ', $table_errors ), 400 );
				return;
			}
		}

		// Test query execution.
		$result = $executor->test_query( $query, $max_execution_time );

		$execution_time = microtime( true ) - $start_time;

		if ( is_wp_error( $result ) ) {
			$this->send_error( $result->get_error_message(), 400 );
			return;
		}

		$this->send_success(
			__( 'Query is valid and executed successfully.', 'dashboard-widget-manager' ),
			array(
				'row_count'      => count( $result ),
				'execution_time' => round( $execution_time * 1000, 2 ), // Convert to milliseconds.
				'parsed_query'   => $parsed_query,
				'results'        => array_slice( $result, 0, 50 ),
			)
		);
	}

	/**
	 * Validate a filter config by building SQL server-side and running it through test_query().
	 *
	 * Accepts a structured config JSON (table, columns, joins, conditions, orders, limit) so
	 * no raw SQL is sent from the client. Uses DWM_Query_Builder to build the SQL.
	 */
	public function ajax_validate_filter_config() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$raw_config         = isset( $_POST['config'] ) ? wp_unslash( $_POST['config'] ) : '';
		$max_execution_time = isset( $_POST['max_execution_time'] ) ? absint( $_POST['max_execution_time'] ) : 30;
		$max_execution_time = min( max( 1, $max_execution_time ), 60 );

		if ( empty( $raw_config ) ) {
			$this->send_error( __( 'Filter config is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$config = json_decode( $raw_config, true );
		if ( ! is_array( $config ) ) {
			$this->send_error( __( 'Invalid filter config format.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$query_builder = DWM_Query_Builder::get_instance();
		$build_result  = $query_builder->build_query_from_config( $config );

		if ( is_wp_error( $build_result ) ) {
			$this->send_error( $build_result->get_error_message(), 400 );
			return;
		}

		$executor    = DWM_Query_Executor::get_instance();
		$test_result = $executor->test_query( $build_result['sql'], $max_execution_time );

		if ( is_wp_error( $test_result ) ) {
			$this->send_error( $test_result->get_error_message(), 400 );
			return;
		}

		$this->send_success(
			__( 'Query is valid and executed successfully.', 'dashboard-widget-manager' ),
			array(
				'row_count' => count( $test_result ),
				'results'   => array_slice( $test_result, 0, 50 ),
			)
		);
	}

	/**
	 * Generate default template and styles for an output_config.
	 *
	 * Returns empty strings for template/styles when the output_config
	 * pipeline handles rendering (list, button, card-list modes). For table
	 * mode, returns a basic PHP template. This lets the wizard pre-populate
	 * fields, and supports regeneration when the mode/theme changes.
	 *
	 * @param array $output_config Decoded output_config array.
	 * @param array $query_columns Optional column names from query results.
	 * @return array { 'template' => string, 'styles' => string }
	 */
	public static function generate_defaults_from_output_config( $output_config, $query_columns = array() ) {
		$display_mode = isset( $output_config['display_mode'] ) ? $output_config['display_mode'] : 'table';
		$columns      = isset( $output_config['columns'] ) && is_array( $output_config['columns'] ) ? $output_config['columns'] : array();

		// For new output_config modes, template + styles are generated at render time.
		// Return empty so we don't store static HTML that would conflict.
		$dynamic_modes = array( 'list', 'button', 'card-list' );
		if ( in_array( $display_mode, $dynamic_modes, true ) ) {
			return array(
				'template' => '',
				'styles'   => '',
			);
		}

		// For table mode with output_config, also return empty (render pipeline handles it).
		if ( 'table' === $display_mode && ! empty( $columns ) ) {
			return array(
				'template' => '',
				'styles'   => '',
			);
		}

		// Fallback: no output_config or unknown mode — return empty.
		return array(
			'template' => '',
			'styles'   => '',
		);
	}

	/**
	 * Change widget status via AJAX.
	 */
	public function ajax_change_status(): void {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$widget_id = isset( $_POST['widget_id'] ) ? absint( $_POST['widget_id'] ) : 0;
		if ( ! $widget_id ) {
			$this->send_error( __( 'Invalid widget ID.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$new_status     = isset( $_POST['new_status'] ) ? sanitize_text_field( wp_unslash( $_POST['new_status'] ) ) : '';
		$valid_statuses = array( 'publish', 'draft', 'archived', 'trash' );
		if ( ! in_array( $new_status, $valid_statuses, true ) ) {
			$this->send_error( __( 'Invalid status.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$data   = DWM_Data::get_instance();
		$widget = $data->get_widget( $widget_id );
		if ( ! $widget ) {
			$this->send_error( __( 'Widget not found.', 'dashboard-widget-manager' ), 404 );
			return;
		}

		$result = $data->update_widget_status( $widget_id, $new_status, true );
		if ( ! $result ) {
			$this->send_error( __( 'Failed to update status.', 'dashboard-widget-manager' ), 500 );
			return;
		}

		$status_labels = array(
			'publish'  => __( 'Active', 'dashboard-widget-manager' ),
			'draft'    => __( 'Draft', 'dashboard-widget-manager' ),
			'archived' => __( 'Archived', 'dashboard-widget-manager' ),
			'trash'    => __( 'Trashed', 'dashboard-widget-manager' ),
		);

		$this->send_success(
			sprintf(
				/* translators: %s: new status label */
				__( 'Widget status changed to %s.', 'dashboard-widget-manager' ),
				$status_labels[ $new_status ] ?? $new_status
			),
			array( 'status' => $new_status )
		);
	}
}
