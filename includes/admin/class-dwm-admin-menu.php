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
		if ( ! current_user_can( self::REQUIRED_CAP ) || ! DWM_Access_Control::current_user_can_access_plugin() ) {
			return;
		}

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

		// Widgets submenu (default).
		add_submenu_page(
			'dashboard-widget-manager',
			__( 'Widgets', 'dashboard-widget-manager' ),
			__( 'Widgets', 'dashboard-widget-manager' ),
			self::REQUIRED_CAP,
			'dashboard-widget-manager',
			array( $this, 'render_dashboard_page' )
		);

		// Branding submenu.
		add_submenu_page(
			'dashboard-widget-manager',
			__( 'Branding', 'dashboard-widget-manager' ),
			__( 'Branding', 'dashboard-widget-manager' ),
			self::REQUIRED_CAP,
			'dwm-customize-dashboard',
			array( $this, 'render_customize_dashboard_page' )
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

		// Tools submenu.
		add_submenu_page(
			'dashboard-widget-manager',
			__( 'Tools', 'dashboard-widget-manager' ),
			__( 'Tools', 'dashboard-widget-manager' ),
			self::REQUIRED_CAP,
			'dwm-tools',
			array( $this, 'render_tools_page' )
		);

		// Integrations submenu.
		add_submenu_page(
			'dashboard-widget-manager',
			__( 'Integrations', 'dashboard-widget-manager' ),
			__( 'Integrations', 'dashboard-widget-manager' ),
			self::REQUIRED_CAP,
			'dwm-integrations',
			array( $this, 'render_integrations_page' )
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
		if ( ! $page ) {
			return false;
		}

		return strpos( $page, 'dashboard-widget-manager' ) === 0 || strpos( $page, 'dwm-' ) === 0;
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
	 * Add action links to the plugin's row on the Plugins admin page.
	 *
	 * Reads submenu items dynamically so the links stay in sync with the
	 * registered admin menu. The Pro upgrade / license link is appended last
	 * (before the WP-provided Deactivate link) with the same gold colour used
	 * in the admin sidebar.
	 *
	 * @param array $links Existing action links (contains Deactivate).
	 * @return array
	 */
	public function add_action_links( array $links ): array {
		global $submenu;

		$new_links  = [];
		$parent     = 'dashboard-widget-manager';
		$pro_slug   = self::PRO_MENU_SLUG;
		$pro_config = self::get_pro_menu_config();

		// Build links from the live submenu — same order as the WP admin sidebar.
		if ( ! empty( $submenu[ $parent ] ) ) {
			foreach ( $submenu[ $parent ] as $item ) {
				$item_slug  = $item[2] ?? '';
				$item_label = strip_tags( $item[0] ?? '' );

				// Skip empty or the Pro placeholder page.
				if ( ! $item_slug || ! $item_label || $item_slug === $pro_slug ) {
					continue;
				}

				$new_links[] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'admin.php?page=' . $item_slug ) ),
					esc_html( $item_label )
				);
			}
		}

		// Pro upgrade / license link — bold, gold to match the admin sidebar colour.
		if ( $pro_config ) {
			$link_classes   = [ 'dwm-pro-action-link' ];
			$link_classes[] = 'add_license' === $pro_config['type'] ? 'dwm-add-api-key-button' : 'dwm-upgrade-button';

			$data_attrs = '';
			if ( ! empty( $pro_config['scroll_to'] ) ) {
				$data_attrs .= ' data-scroll-to="' . esc_attr( $pro_config['scroll_to'] ) . '"';
			}
			if ( ! empty( $pro_config['focus_field'] ) ) {
				$data_attrs .= ' data-focus-field="' . esc_attr( $pro_config['focus_field'] ) . '"';
			}

			$pro_label = 'add_license' === $pro_config['type']
				? __( 'Add License', 'dashboard-widget-manager' )
				: $pro_config['label'];

			$new_links[] = sprintf(
				'<a href="%s" class="%s" style="color:#f0b849;font-weight:bold;"%s>%s</a>',
				esc_url( $pro_config['target_url'] ),
				esc_attr( implode( ' ', $link_classes ) ),
				$data_attrs,
				esc_html( $pro_label )
			);
		}

		return array_merge( $new_links, $links );
	}

	/**
	 * Hide Screen Options tab on DWM plugin pages.
	 *
	 * @return void
	 */
	public static function maybe_hide_screen_options(): void {
		if ( self::is_plugin_page() ) {
			add_filter( 'screen_options_show_screen', '__return_false' );
		}
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
		if ( ! current_user_can( self::REQUIRED_CAP ) || ! DWM_Access_Control::current_user_can_access_plugin() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'dashboard-widget-manager' ) );
		}

		// Load template.
		require_once DWM_PLUGIN_DIR . 'templates/admin/widget-manager.php';
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( self::REQUIRED_CAP ) || ! DWM_Access_Control::current_user_can_access_plugin() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'dashboard-widget-manager' ) );
		}

		// Load template.
		require_once DWM_PLUGIN_DIR . 'templates/admin/settings.php';
	}

	/**
	 * Render tools page.
	 */
	public function render_tools_page() {
		if ( ! current_user_can( self::REQUIRED_CAP ) || ! DWM_Access_Control::current_user_can_access_plugin() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'dashboard-widget-manager' ) );
		}

		// Load template.
		require_once DWM_PLUGIN_DIR . 'templates/admin/tools.php';
	}

	/**
	 * Render integrations page.
	 */
	public function render_integrations_page() {
		if ( ! current_user_can( self::REQUIRED_CAP ) || ! DWM_Access_Control::current_user_can_access_plugin() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'dashboard-widget-manager' ) );
		}

		require_once DWM_PLUGIN_DIR . 'templates/admin/integrations.php';
	}

	/**
	 * Render Branding Page.
	 */
	public function render_customize_dashboard_page() {
		if ( ! current_user_can( self::REQUIRED_CAP ) || ! DWM_Access_Control::current_user_can_access_plugin() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'dashboard-widget-manager' ) );
		}

		// Load template.
		require_once DWM_PLUGIN_DIR . 'templates/admin/customize-dashboard.php';
	}
}
