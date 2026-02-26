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

		// Check table whitelist.
		$data           = DWM_Data::get_instance();
		$allowed_tables = $data->get_allowed_tables();
		if ( ! empty( $allowed_tables ) && ! empty( $widget_data['sql_query'] ) ) {
			$table_errors = DWM_Validator::validate_query_tables( $widget_data['sql_query'], $allowed_tables );
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

		// Check table whitelist.
		$data           = DWM_Data::get_instance();
		$allowed_tables = $data->get_allowed_tables();
		if ( ! empty( $allowed_tables ) && ! empty( $widget_data['sql_query'] ) ) {
			$table_errors = DWM_Validator::validate_query_tables( $widget_data['sql_query'], $allowed_tables );
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
	 * Reorder widgets via AJAX.
	 */
	public function ajax_reorder_widgets() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$order_map = isset( $_POST['order'] ) ? $_POST['order'] : array();

		if ( empty( $order_map ) ) {
			$this->send_error( __( 'Order data is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$data   = DWM_Data::get_instance();
		$result = $data->reorder_widgets( $order_map );

		if ( ! $result ) {
			$this->send_error( __( 'Failed to reorder widgets.', 'dashboard-widget-manager' ), 500 );
			return;
		}

		$this->send_success( __( 'Widgets reordered successfully.', 'dashboard-widget-manager' ) );
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
		$renderer->render_widget( $widget_id );
		$output = ob_get_clean();

		$this->send_success(
			__( 'Preview generated successfully.', 'dashboard-widget-manager' ),
			array( 'html' => $output )
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

		// Use query builder to build SQL from config.
		$query_builder = DWM_Query_Builder::get_instance();
		$result        = $query_builder->build_query_from_config( $config );

		if ( is_wp_error( $result ) ) {
			$this->send_error( $result->get_error_message(), 400 );
			return;
		}

		$sql = $result['sql'];

		// Execute the query to get preview data.
		global $wpdb;
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		if ( $wpdb->last_error ) {
			$this->send_error( $wpdb->last_error, 400 );
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
	 * @param array $rows   Query results.
	 * @param array $config Widget configuration.
	 * @return string HTML output.
	 */
	private function render_preview_html( $rows, $config ) {
		if ( empty( $rows ) ) {
			return '<div class="dwm-widget-no-data"><p>' . esc_html__( 'No data found.', 'dashboard-widget-manager' ) . '</p></div>';
		}

		$display_mode = isset( $config['display_mode'] ) ? $config['display_mode'] : 'table';

		// For now, just render as a simple table.
		// Chart rendering can be added later if needed.
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
	 * Validate query via AJAX.
	 */
	public function ajax_validate_query() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$query = isset( $_POST['query'] ) ? wp_unslash( $_POST['query'] ) : '';

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

		// Check table whitelist against the parsed query (real table names after variable substitution).
		$data           = DWM_Data::get_instance();
		$allowed_tables = $data->get_allowed_tables();
		if ( ! empty( $allowed_tables ) ) {
			$table_errors = DWM_Validator::validate_query_tables( $parsed_query, $allowed_tables );
			if ( ! empty( $table_errors ) ) {
				$this->send_error( implode( ' ', $table_errors ), 400 );
				return;
			}
		}

		// Test query execution.
		$result = $executor->test_query( $query );

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
				'results'        => $result, // Full results for preview.
			)
		);
	}
}
