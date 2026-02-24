<?php
/**
 * Admin Class
 *
 * Handles admin asset enqueuing.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 *
 * Manages admin styles and scripts.
 */
class DWM_Admin {

	use DWM_Singleton;

	/**
	 * Enqueue admin styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_styles( $hook ) {
		// Only load on plugin pages.
		if ( ! $this->is_plugin_page( $hook ) ) {
			return;
		}

		// Base admin styles.
		wp_enqueue_style(
			'dwm-admin',
			DWM_PLUGIN_URL . 'assets/css/minimized/admin/global.min.css',
			array(),
			DWM_VERSION,
			'all'
		);

		// Page-specific styles.
		if ( strpos( $hook, 'dwm-widgets' ) !== false ) {
			wp_enqueue_style(
				'dwm-widget-editor',
				DWM_PLUGIN_URL . 'assets/css/minimized/admin/widget-manager.min.css',
				array( 'dwm-admin' ),
				DWM_VERSION,
				'all'
			);

			// CodeMirror for code editing.
			wp_enqueue_code_editor( array( 'type' => 'application/x-httpd-php' ) );
			wp_enqueue_style( 'wp-codemirror' );
		}

		if ( strpos( $hook, 'dashboard-widget-manager' ) !== false && strpos( $hook, 'dwm-' ) === false ) {
			wp_enqueue_style(
				'dwm-dashboard',
				DWM_PLUGIN_URL . 'assets/css/minimized/admin/dashboard.min.css',
				array( 'dwm-admin' ),
				DWM_VERSION,
				'all'
			);
		}
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		// Only load on plugin pages.
		if ( ! $this->is_plugin_page( $hook ) ) {
			return;
		}

		// Base admin script.
		wp_enqueue_script(
			'dwm-admin',
			DWM_PLUGIN_URL . 'assets/js/minimized/admin/admin.min.js',
			array( 'jquery' ),
			DWM_VERSION,
			true
		);

		// Localize script with AJAX data.
		wp_localize_script(
			'dwm-admin',
			'dwmAdminVars',
			array(
				'nonce'   => wp_create_nonce( 'dwm_admin_nonce' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => array(
					'confirmDelete'  => __( 'Are you sure you want to delete this widget?', 'dashboard-widget-manager' ),
					'saveSuccess'    => __( 'Saved successfully.', 'dashboard-widget-manager' ),
					'saveError'      => __( 'Error saving data.', 'dashboard-widget-manager' ),
					'validating'     => __( 'Validating query...', 'dashboard-widget-manager' ),
					'queryValid'     => __( 'Query is valid!', 'dashboard-widget-manager' ),
					'queryInvalid'   => __( 'Query is invalid.', 'dashboard-widget-manager' ),
					'loading'        => __( 'Loading...', 'dashboard-widget-manager' ),
				),
			)
		);

		// Page-specific scripts.
		if ( strpos( $hook, 'dwm-widgets' ) !== false ) {
			wp_enqueue_script(
				'dwm-widget-editor',
				DWM_PLUGIN_URL . 'assets/js/minimized/admin/widget-editor.min.js',
				array( 'jquery', 'dwm-admin', 'wp-codemirror' ),
				DWM_VERSION,
				true
			);

			// jQuery UI for sortable.
			wp_enqueue_script( 'jquery-ui-sortable' );
		}
	}

	/**
	 * Check if current page is a plugin page.
	 *
	 * @param string $hook Current admin page hook.
	 * @return bool True if plugin page, false otherwise.
	 */
	private function is_plugin_page( $hook ) {
		$plugin_pages = array(
			'toplevel_page_dashboard-widget-manager',
			'widget-manager_page_dwm-widgets',
			'widget-manager_page_dwm-settings',
		);

		return in_array( $hook, $plugin_pages, true );
	}
}
