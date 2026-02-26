<?php
/**
 * Admin Menu Class
 *
 * Handles admin menu registration.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Menu class.
 *
 * Registers admin menus and submenus.
 */
class DWM_Admin_Menu {

	use DWM_Singleton;

	/**
	 * Required capability for accessing admin pages.
	 */
	const REQUIRED_CAP = 'manage_options';

	/**
	 * Pro upgrade menu slug.
	 */
	const PRO_MENU_SLUG = 'dwm-pro-upgrade';

	/**
	 * Register admin menus.
	 */
	public function register_menus() {
		// Main menu page.
		add_menu_page(
			__( 'Widget Manager', 'dashboard-widget-manager' ),
			__( 'Widget Manager', 'dashboard-widget-manager' ),
			self::REQUIRED_CAP,
			'dashboard-widget-manager',
			array( $this, 'render_dashboard_page' ),
			'dashicons-admin-customizer',
			31
		);

		// Dashboard submenu (default).
		add_submenu_page(
			'dashboard-widget-manager',
			__( 'Dashboard', 'dashboard-widget-manager' ),
			__( 'Dashboard', 'dashboard-widget-manager' ),
			self::REQUIRED_CAP,
			'dashboard-widget-manager',
			array( $this, 'render_dashboard_page' )
		);

		// Settings submenu.
		add_submenu_page(
			'dashboard-widget-manager',
			__( 'Settings', 'dashboard-widget-manager' ),
			__( 'Settings', 'dashboard-widget-manager' ),
			self::REQUIRED_CAP,
			'dwm-settings',
			array( $this, 'render_settings_page' )
		);

		// Pro upgrade / add license key submenu.
		$pro_config = self::get_pro_menu_config();
		if ( $pro_config ) {
			$menu_label_html = '<span style="display: inline-flex; align-items: center; font-weight: 600; color: #f0b849;"><span class="dashicons dashicons-' . esc_attr( $pro_config['icon'] ) . '" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span>' . esc_html( $pro_config['label'] ) . '</span>';

			add_submenu_page(
				'dashboard-widget-manager',
				$pro_config['label'],
				$menu_label_html,
				self::REQUIRED_CAP,
				self::PRO_MENU_SLUG,
				array( self::class, 'handle_pro_menu_redirect' )
			);
		}
	}

	/**
	 * Check if current page is a plugin page.
	 *
	 * @return bool
	 */
	public static function is_plugin_page(): bool {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : null;
		return $page && strpos( $page, 'dashboard-widget-manager' ) === 0;
	}

	/**
	 * Get configuration for the Pro upgrade / add-license menu action.
	 *
	 * @return array|null
	 */
	public static function get_pro_menu_config(): ?array {
		// If Pro is already active, no menu item needed.
		if ( class_exists( 'DWM_Pro_Feature_Gate' ) && DWM_Pro_Feature_Gate::is_pro_enabled() ) {
			return null;
		}

		$pro_plugin_file = 'dashboard-widget-manager-pro/dashboard-widget-manager-pro.php';

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$is_pro_active   = is_plugin_active( $pro_plugin_file );
		$has_license_key = false;

		if ( $is_pro_active && class_exists( 'DWM_Pro_Feature_Gate' ) ) {
			$has_license_key = ! empty( DWM_Pro_Feature_Gate::get_license_key() );
		}

		if ( $is_pro_active && ! $has_license_key ) {
			$label       = __( 'Add License Key', 'dashboard-widget-manager' );
			$icon        = 'unlock';
			$target_url  = admin_url( 'admin.php?page=dwm-settings#dwm-pro-license-key' );
			$type        = 'add_license';
			$scroll_to   = 'dwm-pro-license-key';
			$focus_field = 'dwm_pro_license_key';
		} else {
			$label       = __( 'Upgrade to Pro', 'dashboard-widget-manager' );
			$icon        = 'star-filled';
			$target_url  = admin_url( 'admin.php?page=dashboard-widget-manager&modal=pro-upgrade' );
			$type        = 'upgrade';
			$scroll_to   = '';
			$focus_field = '';
		}

		return [
			'type'        => $type,
			'label'       => $label,
			'icon'        => $icon,
			'target_url'  => $target_url,
			'scroll_to'   => $scroll_to,
			'focus_field' => $focus_field,
			'menu_href'   => admin_url( 'admin.php?page=' . self::PRO_MENU_SLUG ),
			'menu_slug'   => self::PRO_MENU_SLUG,
		];
	}

	/**
	 * Redirect the Pro menu entry to the configured target URL.
	 *
	 * @return void
	 */
	public static function handle_pro_menu_redirect(): void {
		$pro_config = self::get_pro_menu_config();
		$target_url = $pro_config['target_url'] ?? admin_url( 'admin.php?page=dwm-settings#dwm-pro-license-key' );
		wp_safe_redirect( $target_url );
		exit;
	}

	/**
	 * Render dashboard page.
	 */
	public function render_dashboard_page() {
		if ( ! current_user_can( self::REQUIRED_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'dashboard-widget-manager' ) );
		}

		// Load template.
		require_once DWM_PLUGIN_DIR . 'templates/admin/widget-manager.php';
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( self::REQUIRED_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'dashboard-widget-manager' ) );
		}

		// Load template.
		require_once DWM_PLUGIN_DIR . 'templates/admin/settings.php';
	}
}
