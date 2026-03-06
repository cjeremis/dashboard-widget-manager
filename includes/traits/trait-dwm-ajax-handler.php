<?php
/**
 * AJAX Handler Trait
 *
 * Provides utility methods for handling AJAX requests.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX Handler trait.
 *
 * Provides common methods for AJAX request handling.
 */
trait DWM_AJAX_Handler {

	/**
	 * Verify AJAX nonce.
	 *
	 * @param string $nonce_field The nonce field name.
	 * @param string $nonce_action The nonce action name.
	 * @return bool True if nonce is valid, false otherwise.
	 */
	protected function verify_nonce( $nonce_field = 'nonce', $nonce_action = 'dwm_admin_nonce' ) {
		$nonce = isset( $_POST[ $nonce_field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate user capability.
	 *
	 * @param string $capability The capability to check.
	 * @return bool True if user has capability, false otherwise.
	 */
	protected function validate_capability( $capability = 'manage_options' ) {
		if ( ! current_user_can( $capability ) ) {
			return false;
		}

		if ( class_exists( 'DWM_Access_Control' ) && ! DWM_Access_Control::current_user_can_access_plugin() ) {
			return false;
		}

		return true;
	}

	/**
	 * Send AJAX success response.
	 *
	 * @param string $message Success message.
	 * @param array  $data Additional data to send.
	 */
	protected function send_success( $message = '', $data = array() ) {
		wp_send_json_success(
			array_merge(
				array( 'message' => $message ),
				$data
			)
		);
	}

	/**
	 * Send AJAX error response.
	 *
	 * @param string $message Error message.
	 * @param int    $code HTTP status code.
	 * @param array  $data Additional data to send.
	 */
	protected function send_error( $message = '', $code = 400, $data = array() ) {
		wp_send_json_error(
			array(
				'message' => $message,
				'code'    => $code,
				'data'    => $data,
			),
			$code
		);
	}

	/**
	 * Verify AJAX request security.
	 *
	 * Combines nonce verification and capability check.
	 *
	 * @param string $nonce_field The nonce field name.
	 * @param string $nonce_action The nonce action name.
	 * @param string $capability The capability to check.
	 * @return bool True if request is valid, sends error and dies otherwise.
	 */
	protected function verify_ajax_request( $nonce_field = 'nonce', $nonce_action = 'dwm_admin_nonce', $capability = 'manage_options' ) {
		if ( ! $this->verify_nonce( $nonce_field, $nonce_action ) ) {
			$this->send_error( __( 'Security check failed.', 'dashboard-widget-manager' ), 403 );
			return false;
		}

		if ( ! $this->validate_capability( $capability ) ) {
			$this->send_error( __( 'You do not have permission to perform this action.', 'dashboard-widget-manager' ), 403 );
			return false;
		}

		return true;
	}
}
