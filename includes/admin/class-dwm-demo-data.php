<?php
/**
 * Demo Data Handler
 *
 * Handles demo data import and cleanup operations.
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
 * Demo Data class.
 *
 * Manages demo data import and deletion for the plugin.
 */
class DWM_Demo_Data {

	use DWM_Singleton;
	use DWM_AJAX_Handler;

	/**
	 * Load demo data from file.
	 *
	 * @return array|null Decoded demo data or null if not found/invalid.
	 */
	private function load_demo_data() {
		$demo_file = DWM_PLUGIN_DIR . 'data/demo-data.json';

		if ( ! file_exists( $demo_file ) ) {
			return null;
		}

		$contents  = file_get_contents( $demo_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$demo_data = json_decode( $contents, true );

		return null === $demo_data ? null : $demo_data;
	}

	/**
	 * Check whether the demo data file exists.
	 *
	 * @return bool
	 */
	public function demo_data_exists(): bool {
		return file_exists( DWM_PLUGIN_DIR . 'data/demo-data.json' );
	}

	/**
	 * AJAX handler to import demo widgets.
	 *
	 * Accepts optional POST toggle: import_widgets (bool, default true).
	 *
	 * @return void
	 */
	public function ajax_import_demo_data(): void {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$import_widgets = ! isset( $_POST['import_widgets'] ) || filter_var( wp_unslash( $_POST['import_widgets'] ), FILTER_VALIDATE_BOOLEAN );

		if ( ! $import_widgets ) {
			$this->send_error( __( 'Please select at least one data type to import.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$demo_data = $this->load_demo_data();
		if ( null === $demo_data ) {
			$this->send_error( __( 'Demo data file not found.', 'dashboard-widget-manager' ), 404 );
			return;
		}

		$count = 0;

		if ( $import_widgets && isset( $demo_data['widgets'] ) && is_array( $demo_data['widgets'] ) ) {
			$count = $this->import_demo_widgets( $demo_data['widgets'] );
		}

		$message = $count > 0
			/* translators: %d: number of widgets imported */
			? sprintf( _n( '%d widget imported.', '%d widgets imported.', $count, 'dashboard-widget-manager' ), $count )
			: __( 'No widgets were imported.', 'dashboard-widget-manager' );

		$this->send_success( $message, array( 'count' => $count ) );
	}

	/**
	 * AJAX handler to delete all demo widgets.
	 *
	 * @return void
	 */
	public function ajax_delete_demo_data(): void {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$data    = DWM_Data::get_instance();
		$deleted = $data->delete_demo_widgets();

		$message = $deleted > 0
			/* translators: %d: number of widgets deleted */
			? sprintf( _n( '%d demo widget deleted.', '%d demo widgets deleted.', $deleted, 'dashboard-widget-manager' ), $deleted )
			: __( 'No demo widgets found to delete.', 'dashboard-widget-manager' );

		$this->send_success( $message, array( 'deleted' => $deleted ) );
	}

	/**
	 * Import widgets from demo data, marking each as is_demo = 1.
	 *
	 * Skips widgets that already exist with the same name to avoid duplicates.
	 *
	 * @param array $widgets_data Array of widget objects from demo JSON.
	 * @return int Count of widgets created.
	 */
	private function import_demo_widgets( array $widgets_data ): int {
		$data  = DWM_Data::get_instance();
		$count = 0;

		foreach ( $widgets_data as $widget ) {
			if ( ! is_array( $widget ) || empty( $widget['name'] ) ) {
				continue;
			}

			// Skip if a widget with this name already exists.
			$existing = $data->get_widget_by_name( sanitize_text_field( $widget['name'] ) );
			if ( $existing ) {
				continue;
			}

			// Strip any existing ID fields.
			unset( $widget['id'], $widget['created_at'], $widget['updated_at'], $widget['created_by'] );

			// Mark as demo.
			$widget['is_demo'] = 1;

			// Sanitize key fields.
			$widget['name']        = sanitize_text_field( $widget['name'] );
			$widget['description'] = isset( $widget['description'] ) ? sanitize_text_field( $widget['description'] ) : '';
			$widget['enabled']     = isset( $widget['enabled'] ) ? (int) $widget['enabled'] : 1;
			$widget['widget_order'] = isset( $widget['widget_order'] ) ? (int) $widget['widget_order'] : 0;

			$new_id = $data->create_widget( $widget );

			if ( $new_id ) {
				$count++;
			}
		}

		return $count;
	}
}
