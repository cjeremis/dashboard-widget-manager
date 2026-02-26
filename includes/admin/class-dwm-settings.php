<?php
/**
 * Settings Class
 *
 * Handles plugin settings.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 *
 * Manages plugin settings.
 */
class DWM_Settings {

	use DWM_Singleton;
	use DWM_AJAX_Handler;

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			'dwm_settings_group',
			'dwm_settings',
			array( $this, 'sanitize_settings' )
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $settings Settings array.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $settings ) {
		return DWM_Sanitizer::sanitize_settings( $settings );
	}

	/**
	 * Save settings via AJAX.
	 */
	public function ajax_save_settings() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$settings = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array();

		if ( empty( $settings ) ) {
			$this->send_error( __( 'Settings data is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		// Sanitize settings.
		$settings = DWM_Sanitizer::sanitize_settings( $settings );

		// Validate settings.
		$errors = DWM_Validator::validate_settings( $settings );
		if ( ! empty( $errors ) ) {
			$this->send_error( implode( ' ', $errors ), 400, array( 'errors' => $errors ) );
			return;
		}

		// Save settings.
		$data   = DWM_Data::get_instance();
		$result = $data->update_settings( $settings );

		if ( ! $result ) {
			$this->send_error( __( 'Failed to save settings.', 'dashboard-widget-manager' ), 500 );
			return;
		}

		// Check if any active widgets are now using tables outside the new whitelist.
		$allowed_tables = $data->get_allowed_tables();
		$affected       = array();

		if ( ! empty( $allowed_tables ) ) {
			$active_widgets = $data->get_widgets( true );
			foreach ( $active_widgets as $widget ) {
				if ( ! empty( $widget['sql_query'] ) ) {
					$table_errors = DWM_Validator::validate_query_tables( $widget['sql_query'], $allowed_tables );
					if ( ! empty( $table_errors ) ) {
						$affected[] = $widget['name'];
					}
				}
			}
		}

		$response = array();
		if ( ! empty( $affected ) ) {
			$response['warning']          = sprintf(
				/* translators: 1: number of widgets, 2: comma-separated widget names */
				_n(
					'%1$d active widget is now using a table outside the whitelist and will fail to execute: %2$s',
					'%1$d active widgets are now using tables outside the whitelist and will fail to execute: %2$s',
					count( $affected ),
					'dashboard-widget-manager'
				),
				count( $affected ),
				implode( ', ', array_map( function( $n ) { return '"' . $n . '"'; }, $affected ) )
			);
			$response['affected_widgets'] = $affected;
		}

		$this->send_success( __( 'Settings saved successfully.', 'dashboard-widget-manager' ), $response );
	}

	/**
	 * Reset settings via AJAX.
	 */
	public function ajax_reset_settings() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$default_settings = $this->get_default_settings();

		$data   = DWM_Data::get_instance();
		$result = $data->update_settings( $default_settings );

		if ( ! $result ) {
			$this->send_error( __( 'Failed to reset settings.', 'dashboard-widget-manager' ), 500 );
			return;
		}

		$this->send_success(
			__( 'Settings reset successfully.', 'dashboard-widget-manager' ),
			array( 'settings' => $default_settings )
		);
	}

	/**
	 * Get default settings.
	 *
	 * @return array Default settings.
	 */
	public function get_default_settings() {
		return array(
			'allowed_tables' => '',
		);
	}
}
