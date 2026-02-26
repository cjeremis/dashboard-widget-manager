<?php
/**
 * System Info Handler
 *
 * Handles collection of environment and system metadata for support flows.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DWM_System_Info {

	use DWM_Singleton;

	/**
	 * Get all system information
	 *
	 * @return array System information
	 */
	public function get_all_info(): array {
		return [
			'wp_version'             => $this->get_wp_version(),
			'php_version'            => $this->get_php_version(),
			'theme_name'             => $this->get_theme_name(),
			'theme_version'          => $this->get_theme_version(),
			'active_plugins'         => $this->get_active_plugins(),
			'dwm_version'            => $this->get_dwm_version(),
			'customer_site_url'      => $this->get_site_url(),
			'customer_ip'            => $this->get_client_ip(),
			'customer_user_agent'    => $this->get_user_agent(),
		];
	}

	/**
	 * Get WordPress version
	 *
	 * @return string
	 */
	public function get_wp_version(): string {
		global $wp_version;
		return $wp_version;
	}

	/**
	 * Get PHP version
	 *
	 * @return string
	 */
	public function get_php_version(): string {
		return phpversion();
	}

	/**
	 * Get current theme name
	 *
	 * @return string
	 */
	public function get_theme_name(): string {
		$theme = wp_get_theme();
		return $theme->get( 'Name' );
	}

	/**
	 * Get current theme version
	 *
	 * @return string
	 */
	public function get_theme_version(): string {
		$theme = wp_get_theme();
		return $theme->get( 'Version' );
	}

	/**
	 * Get list of active plugins
	 *
	 * @return array Active plugins with name and version
	 */
	public function get_active_plugins(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins        = get_plugins();
		$active_plugins     = get_option( 'active_plugins', [] );
		$active_plugin_data = [];

		foreach ( $active_plugins as $plugin_path ) {
			if ( isset( $all_plugins[ $plugin_path ] ) ) {
				$plugin_data          = $all_plugins[ $plugin_path ];
				$active_plugin_data[] = [
					'name'    => $plugin_data['Name'],
					'version' => $plugin_data['Version'],
					'path'    => $plugin_path,
				];
			}
		}

		return $active_plugin_data;
	}

	/**
	 * Get Dashboard Widget Manager version
	 *
	 * @return string|null
	 */
	public function get_dwm_version(): ?string {
		if ( defined( 'DWM_VERSION' ) ) {
			return DWM_VERSION;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_file = WP_PLUGIN_DIR . '/dashboard-widget-manager/dashboard-widget-manager.php';

		if ( file_exists( $plugin_file ) ) {
			$plugin_data = get_plugin_data( $plugin_file );
			return $plugin_data['Version'] ?? null;
		}

		return null;
	}

	/**
	 * Get site URL
	 *
	 * @return string
	 */
	public function get_site_url(): string {
		return get_site_url();
	}

	/**
	 * Get client IP address
	 *
	 * @return string|null
	 */
	public function get_client_ip(): ?string {
		$ip = null;

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		if ( $ip ) {
			$ip = filter_var( $ip, FILTER_VALIDATE_IP );
		}

		return $ip ?: null;
	}

	/**
	 * Get user agent
	 *
	 * @return string|null
	 */
	public function get_user_agent(): ?string {
		return isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] )
			: null;
	}

	/**
	 * Get current user email
	 *
	 * @return string|null
	 */
	public function get_current_user_email(): ?string {
		$current_user = wp_get_current_user();

		if ( $current_user && $current_user->ID > 0 ) {
			return $current_user->user_email;
		}

		return null;
	}

	/**
	 * Get current user name
	 *
	 * @return string|null
	 */
	public function get_current_user_name(): ?string {
		$current_user = wp_get_current_user();

		if ( $current_user && $current_user->ID > 0 ) {
			return $current_user->display_name;
		}

		return null;
	}
}
