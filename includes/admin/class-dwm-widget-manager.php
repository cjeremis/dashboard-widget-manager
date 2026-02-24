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

		// Create widget.
		$data      = DWM_Data::get_instance();
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

		// Update widget.
		$data   = DWM_Data::get_instance();
		$result = $data->update_widget( $widget_id, $widget_data );

		if ( ! $result ) {
			$this->send_error( __( 'Failed to update widget.', 'dashboard-widget-manager' ), 500 );
			return;
		}

		$this->send_success( __( 'Widget updated successfully.', 'dashboard-widget-manager' ) );
	}

	/**
	 * Delete widget via AJAX.
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
		$result = $data->delete_widget( $widget_id );

		if ( ! $result ) {
			$this->send_error( __( 'Failed to delete widget.', 'dashboard-widget-manager' ), 500 );
			return;
		}

		$this->send_success( __( 'Widget deleted successfully.', 'dashboard-widget-manager' ) );
	}

	/**
	 * Toggle widget status via AJAX.
	 */
	public function ajax_toggle_widget() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$widget_id = isset( $_POST['widget_id'] ) ? absint( $_POST['widget_id'] ) : 0;
		$enabled   = isset( $_POST['enabled'] ) ? (bool) $_POST['enabled'] : false;

		if ( ! $widget_id ) {
			$this->send_error( __( 'Widget ID is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$data   = DWM_Data::get_instance();
		$result = $data->toggle_widget_status( $widget_id, $enabled );

		if ( ! $result ) {
			$this->send_error( __( 'Failed to toggle widget status.', 'dashboard-widget-manager' ), 500 );
			return;
		}

		$this->send_success(
			$enabled ? __( 'Widget enabled successfully.', 'dashboard-widget-manager' ) : __( 'Widget disabled successfully.', 'dashboard-widget-manager' )
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
