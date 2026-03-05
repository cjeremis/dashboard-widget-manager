<?php
/**
 * License Manager
 *
 * Handles Pro license activation, deactivation, and storage.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DWM_License_Manager {

	use DWM_Singleton;
	use DWM_AJAX_Handler;

	/**
	 * Settings key for license data storage
	 */
	const SETTING_KEY = 'pro_license';

	/**
	 * Get stored license data
	 *
	 * @return array
	 */
	public function get_license_data(): array {
		$data = DWM_Settings_Repository::get_instance()->get( self::SETTING_KEY, [] );

		return is_array( $data ) ? $data : [];
	}

	/**
	 * Activate a license key
	 *
	 * @param string $key License key to activate
	 * @return array Result with success bool and message
	 */
	public function activate( string $key ): array {
		$key = strtoupper( trim( sanitize_text_field( $key ) ) );

		if ( strlen( $key ) < 12 ) {
			return [
				'success' => false,
				'message' => __( 'Invalid license key. Please check and try again.', 'dashboard-widget-manager' ),
			];
		}

		$api_result = $this->request_license_activation( $key );

		if ( ! $api_result['success'] ) {
			return $api_result;
		}

		$license_data = array_merge(
			[ 'key' => $key ],
			$api_result['license'] ?? []
		);

		$repo = DWM_Settings_Repository::get_instance();
		$repo->set( self::SETTING_KEY, $license_data, null, false );

		// Update the feature gate cache
		$repo->set(
			DWM_Pro_Feature_Gate::SETTING_KEY_LICENSE_CACHE,
			[
				'key'              => $key,
				'status'           => $license_data['status'] ?? 'active',
				'expires_at'       => $license_data['expires_at'] ?? null,
				'last_verified'    => current_time( 'timestamp' ),
				'expires_cache_at' => current_time( 'timestamp' ) + DAY_IN_SECONDS,
			],
			null,
			false
		);

		return [
			'success' => true,
			'message' => __( 'License activated successfully.', 'dashboard-widget-manager' ),
			'license' => $license_data,
		];
	}

	/**
	 * Deactivate the current license
	 *
	 * @return bool
	 */
	public function deactivate(): bool {
		$repo = DWM_Settings_Repository::get_instance();
		$repo->delete( self::SETTING_KEY );
		$repo->delete( DWM_Pro_Feature_Gate::SETTING_KEY_LICENSE_CACHE );

		return true;
	}

	/**
	 * Make API request to activate a license key
	 *
	 * @param string $license_key License key
	 * @return array
	 */
	private function request_license_activation( string $license_key ): array {
		$api_url   = rest_url( 'dwm/v1/license/activate' );
		$api_nonce = wp_create_nonce( 'wp_rest' );

		$settings   = DWM_Settings_Repository::get_instance()->get( 'portal', [] );
		$api_secret = is_array( $settings ) ? ( $settings['api_shared_secret'] ?? '' ) : '';

		$body      = wp_json_encode( [
			'license_key'    => $license_key,
			'domain'         => get_site_url(),
			'plugin_version' => DWM_VERSION,
		] );
		$timestamp = (string) time();
		$signature = hash_hmac( 'sha256', $timestamp . '.' . $body, $api_secret );

		// sslverify is always true in production. Define DWM_DEV_DISABLE_SSLVERIFY=true only in local dev environments.
		$sslverify = defined( 'DWM_DEV_DISABLE_SSLVERIFY' ) && DWM_DEV_DISABLE_SSLVERIFY ? false : true;

		$response = wp_remote_post(
			$api_url,
			[
				'timeout'   => 10,
				'blocking'  => true,
				'sslverify' => $sslverify,
				'headers'   => [
					'X-WP-Nonce'      => $api_nonce,
					'X-DWM-Timestamp' => $timestamp,
					'X-DWM-Signature' => $signature,
					'Content-Type'    => 'application/json',
				],
				'body'      => $body,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => __( 'Could not connect to the license server. Please try again.', 'dashboard-widget-manager' ),
			];
		}

		$parsed = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $parsed['success'] ) || ! $parsed['success'] ) {
			return [
				'success' => false,
				'message' => $parsed['message'] ?? __( 'License activation failed. Please check your key and try again.', 'dashboard-widget-manager' ),
			];
		}

		return [
			'success' => true,
			'license' => $parsed['license'] ?? [],
		];
	}

	/**
	 * AJAX handler: activate license
	 *
	 * @return void
	 */
	public function ajax_activate(): void {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';

		if ( empty( $key ) ) {
			$this->send_error( __( 'License key is required.', 'dashboard-widget-manager' ) );
			return;
		}

		$result = $this->activate( $key );

		if ( $result['success'] ) {
			$this->send_success( $result['message'], [ 'license' => $result['license'] ?? [] ] );
		} else {
			$this->send_error( $result['message'] );
		}
	}

	/**
	 * AJAX handler: deactivate license
	 *
	 * @return void
	 */
	public function ajax_deactivate(): void {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$this->deactivate();
		$this->send_success( __( 'License removed successfully.', 'dashboard-widget-manager' ) );
	}
}
