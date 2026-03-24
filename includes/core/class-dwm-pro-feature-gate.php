<?php
/**
 * Pro Feature Gate Handler
 *
 * Handles Pro feature access checks and feature gate logic.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// If the Pro plugin already provides this class, do not redeclare.
if ( class_exists( 'DWM_Pro_Feature_Gate', false ) ) {
	return;
}

class DWM_Pro_Feature_Gate {

	/**
	 * Setting key for settings table storage
	 */
	const SETTING_KEY_LICENSE_CACHE = 'pro_license_cache';

	/**
	 * Singleton instance.
	 *
	 * @var DWM_Pro_Feature_Gate|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return DWM_Pro_Feature_Gate
	 */
	public static function get_instance(): DWM_Pro_Feature_Gate {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the settings repository instance
	 *
	 * @return DWM_Settings_Repository
	 */
	private static function get_settings_repo(): DWM_Settings_Repository {
		return DWM_Settings_Repository::get_instance();
	}

	/**
	 * Get portal settings from settings table
	 *
	 * @return array
	 */
	private static function get_portal_settings(): array {
		$settings = self::get_settings_repo()->get( 'portal', [] );

		return is_array( $settings ) ? $settings : [];
	}

	/**
	 * Get license cache from settings table
	 *
	 * @return array|null
	 */
	private static function get_license_cache(): ?array {
		$cache = self::get_settings_repo()->get( self::SETTING_KEY_LICENSE_CACHE, null );

		return ( $cache !== null && is_array( $cache ) ) ? $cache : null;
	}

	/**
	 * Update license cache in settings table
	 *
	 * @param array $cache License cache data
	 * @return void
	 */
	private static function set_license_cache( array $cache ): void {
		self::get_settings_repo()->set(
			self::SETTING_KEY_LICENSE_CACHE,
			$cache,
			null,
			false
		);
	}

	/**
	 * Get the license key from cache
	 *
	 * @return string License key or empty string if not found
	 */
	public static function get_license_key(): string {
		$cache = self::get_license_cache();

		return ( $cache && ! empty( $cache['key'] ) ) ? $cache['key'] : '';
	}

	/**
	 * Determine if Pro features are enabled.
	 *
	 * Checks in order:
	 * 1. Plugin filter override (dashboard_widget_manager_pro_enabled) - checked first for external control
	 * 2. Dev bypass cookie
	 * 3. Local license cache (24-hour TTL)
	 * 4. TopDevAmerica API validation if cache expired
	 * 5. Grace period (7 days) if API fails
	 *
	 * @return bool
	 */
	public static function is_pro_enabled(): bool {
		/**
		 * Filter to override Pro gating (checked first for external control).
		 * Used by TopDevAmerica plugin for development bypass.
		 *
		 * @param bool|null $enabled Whether Pro is enabled. Return true/false to override, null to continue checks.
		 */
		$filter_result = apply_filters( 'dashboard_widget_manager_pro_enabled', null );
		if ( null !== $filter_result ) {
			return (bool) $filter_result;
		}

		// Global dev bypass cookie for TopDevAmerica plugins
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_COOKIE['topdevamerica-pro'] ) && 'true' === $_COOKIE['topdevamerica-pro'] ) {
			return true;
		}

		// Check local license cache from settings table
		$license_cache = self::get_license_cache();

		if ( $license_cache ) {
			// Check if cache is still valid (24-hour TTL)
			$cache_expires = $license_cache['expires_cache_at'] ?? 0;
			if ( time() < $cache_expires ) {
				$status = $license_cache['status'] ?? 'inactive';
				return 'active' === $status;
			}
		}

		// Cache expired or doesn't exist - try API validation
		$api_result = self::validate_license_via_api();

		if ( $api_result['success'] ) {
			// API validation succeeded - update cache
			self::update_license_cache_data( $api_result['license'] );
			return true;
		}

		// API validation failed - check grace period
		if ( $api_result['grace_period_active'] ) {
			return true;
		}

		// Out of grace period and API failed - license is invalid
		self::update_license_cache_data( [ 'status' => 'invalid' ] );

		return false;
	}

	/**
	 * Validate license via TopDevAmerica API
	 *
	 * @return array Result with success, license data, and grace period info
	 */
	private static function validate_license_via_api(): array {
		$license_cache = self::get_license_cache();

		if ( ! $license_cache || empty( $license_cache['key'] ) ) {
			return [
				'success'             => false,
				'grace_period_active' => false,
			];
		}

		$license_key = $license_cache['key'];
		$api_url     = rest_url( 'dwm/v1/license/status' );
		$api_nonce   = wp_create_nonce( 'wp_rest' );

		$timestamp  = (string) time();
		$settings   = self::get_portal_settings();
		$api_secret = $settings['api_shared_secret'] ?? '';

		// For GET requests, body is empty string
		$signature = hash_hmac( 'sha256', $timestamp . '.', $api_secret );

		// sslverify is always true in production. Define DWM_DEV_DISABLE_SSLVERIFY=true only in local dev environments.
		$sslverify = defined( 'DWM_DEV_DISABLE_SSLVERIFY' ) && DWM_DEV_DISABLE_SSLVERIFY ? false : true;

		$response = wp_remote_get(
			add_query_arg( 'license_key', rawurlencode( $license_key ), $api_url ),
			[
				'timeout'   => 5,
				'blocking'  => true,
				'sslverify' => $sslverify,
				'headers'   => [
					'X-WP-Nonce'      => $api_nonce,
					'X-DWM-Timestamp' => $timestamp,
					'X-DWM-Signature' => $signature,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			$last_verified    = $license_cache['last_verified'] ?? 0;
			$grace_period_end = $last_verified + ( 7 * DAY_IN_SECONDS );

			return [
				'success'             => false,
				'grace_period_active' => time() < $grace_period_end,
			];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['success'] ) || ! $body['success'] ) {
			$last_verified    = $license_cache['last_verified'] ?? 0;
			$grace_period_end = $last_verified + ( 7 * DAY_IN_SECONDS );

			return [
				'success'             => false,
				'grace_period_active' => time() < $grace_period_end,
			];
		}

		return [
			'success' => true,
			'license' => $body['license'] ?? [],
		];
	}

	/**
	 * Update local license cache with license data
	 *
	 * @param array $license_data License data from API
	 * @return void
	 */
	private static function update_license_cache_data( array $license_data ): void {
		$cache = [
			'key'              => $license_data['key'] ?? '',
			'status'           => $license_data['status'] ?? 'inactive',
			'expires_at'       => $license_data['expires_at'] ?? null,
			'last_verified'    => current_time( 'timestamp' ),
			'expires_cache_at' => current_time( 'timestamp' ) + DAY_IN_SECONDS,
		];

		self::set_license_cache( $cache );
	}

	/**
	 * Check a specific feature flag.
	 *
	 * @param string $feature Feature key.
	 * @return bool
	 */
	public function is_feature_enabled( string $feature ): bool {
		return self::is_pro_enabled();
	}
}
