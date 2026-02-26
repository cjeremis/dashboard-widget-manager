<?php
/**
 * Export Import Handler
 *
 * Handles AJAX export and import operations for Dashboard Widget Manager data.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export Import class.
 *
 * Manages data export and import operations.
 */
class DWM_Export_Import {

	use DWM_Singleton;
	use DWM_AJAX_Handler;

	/**
	 * Export all data via AJAX.
	 */
	public function ajax_export() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$data   = DWM_Data::get_instance();
		$export = $data->export_all();

		$this->send_success( '', $export );
	}

	/**
	 * Validate import file via AJAX.
	 */
	public function ajax_validate_import() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		if ( ! isset( $_FILES['file'] ) ) {
			$this->send_error( __( 'No file provided.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$max_size = 5 * 1024 * 1024;
		if ( $_FILES['file']['size'] > $max_size ) {
			$this->send_error( __( 'File size exceeds 5MB limit.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$finfo     = finfo_open( FILEINFO_MIME_TYPE );
		$mime_type = finfo_file( $finfo, $_FILES['file']['tmp_name'] );
		finfo_close( $finfo );

		if ( 'application/json' !== $mime_type && 'text/plain' !== $mime_type ) {
			$this->send_error( __( 'Invalid file type. Only JSON files are allowed.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$file_contents = file_get_contents( $_FILES['file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data_raw      = json_decode( $file_contents, true );

		if ( null === $data_raw ) {
			$this->send_error( __( 'Invalid JSON file.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$errors = DWM_Validator::validate_import_data( $data_raw );

		if ( ! empty( $errors ) ) {
			$this->send_error( implode( ' ', $errors ), 400, array( 'errors' => $errors ) );
			return;
		}

		$this->send_success(
			__( 'File is valid and ready to import.', 'dashboard-widget-manager' ),
			array( 'data' => $data_raw )
		);
	}

	/**
	 * Import data via AJAX.
	 */
	public function ajax_import() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$result = $this->process_import_data();

		if ( is_wp_error( $result ) ) {
			$this->send_error( $result->get_error_message(), 400 );
			return;
		}

		if ( $result ) {
			$this->send_success( __( 'Data imported successfully.', 'dashboard-widget-manager' ) );
		} else {
			$this->send_error( __( 'Failed to import data.', 'dashboard-widget-manager' ), 500 );
		}
	}

	/**
	 * Reset selected data via AJAX.
	 */
	public function ajax_reset_data() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$reset = isset( $_POST['reset'] ) && is_array( $_POST['reset'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['reset'] ) )
			: array();

		if ( empty( $reset ) ) {
			$this->send_error( __( 'Nothing selected to reset.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$data    = DWM_Data::get_instance();
		$success = true;

		if ( ! empty( $reset['widgets'] ) ) {
			$widgets = $data->get_widgets();
			foreach ( $widgets as $widget ) {
				$result  = $data->delete_widget( (int) $widget['id'] );
				$success = $success && $result;
			}
		}

		if ( ! empty( $reset['settings'] ) ) {
			$default_settings = DWM_Settings::get_instance()->get_default_settings();
			$result           = $data->update_settings( $default_settings );
			$success          = $success && $result;
		}

		if ( $success ) {
			$this->send_success( __( 'Data reset successfully.', 'dashboard-widget-manager' ) );
		} else {
			$this->send_error( __( 'Failed to reset data.', 'dashboard-widget-manager' ), 500 );
		}
	}

	/**
	 * Process import data from file or POST.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	private function process_import_data() {
		$data_raw = null;

		if ( ! empty( $_FILES['file']['tmp_name'] ) ) {
			$contents = file_get_contents( $_FILES['file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$data_raw = json_decode( $contents, true );
		} elseif ( isset( $_POST['data'] ) ) {
			$data_raw = json_decode( stripslashes( $_POST['data'] ), true );
		}

		if ( null === $data_raw ) {
			return new WP_Error( 'invalid_data', __( 'Invalid data provided.', 'dashboard-widget-manager' ) );
		}

		$errors = DWM_Validator::validate_import_data( $data_raw );

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'validation_failed', implode( ' ', $errors ) );
		}

		$data = DWM_Data::get_instance();
		return $data->import_all( $data_raw );
	}
}
