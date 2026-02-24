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

		// Manage Widgets submenu.
		add_submenu_page(
			'dashboard-widget-manager',
			__( 'Manage Widgets', 'dashboard-widget-manager' ),
			__( 'Manage Widgets', 'dashboard-widget-manager' ),
			self::REQUIRED_CAP,
			'dwm-widgets',
			array( $this, 'render_widget_manager_page' )
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
	}

	/**
	 * Render dashboard page.
	 */
	public function render_dashboard_page() {
		if ( ! current_user_can( self::REQUIRED_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'dashboard-widget-manager' ) );
		}

		$dashboard = DWM_Dashboard::get_instance();
		$dashboard->render();
	}

	/**
	 * Render widget manager page.
	 */
	public function render_widget_manager_page() {
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
