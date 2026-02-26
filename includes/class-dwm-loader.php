<?php
/**
 * Loader Class
 *
 * Orchestrates all plugin hooks and filters.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loader class.
 *
 * Manages and registers all hooks for the plugin.
 */
class DWM_Loader {

	use DWM_Singleton;

	/**
	 * Array of actions registered with WordPress.
	 *
	 * @var array
	 */
	protected $actions = array();

	/**
	 * Array of filters registered with WordPress.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Initialize the loader.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load required dependencies.
	 */
	private function load_dependencies() {
		// Core classes are auto-loaded via the autoloader.
		// This method is kept for consistency with the pattern.
	}

	/**
	 * Register all admin-related hooks.
	 */
	private function define_admin_hooks() {
		// Admin menu.
		$this->add_action( 'admin_menu', 'DWM_Admin_Menu', 'register_menus' );

		// Admin assets.
		$this->add_action( 'admin_enqueue_scripts', 'DWM_Admin', 'enqueue_styles' );
		$this->add_action( 'admin_enqueue_scripts', 'DWM_Admin', 'enqueue_scripts' );
		$this->add_action( 'admin_enqueue_scripts', 'DWM_Admin', 'enqueue_chartjs' );
		$this->add_action( 'admin_enqueue_scripts', 'DWM_Admin', 'inject_dashboard_button' );

		// Settings.
		$this->add_action( 'admin_init', 'DWM_Settings', 'register_settings' );

		// AJAX - Widget Management.
		$this->add_action( 'wp_ajax_dwm_get_widgets', 'DWM_Widget_Manager', 'ajax_get_widgets' );
		$this->add_action( 'wp_ajax_dwm_get_widget', 'DWM_Widget_Manager', 'ajax_get_widget' );
		$this->add_action( 'wp_ajax_dwm_create_widget', 'DWM_Widget_Manager', 'ajax_create_widget' );
		$this->add_action( 'wp_ajax_dwm_update_widget', 'DWM_Widget_Manager', 'ajax_update_widget' );
		$this->add_action( 'wp_ajax_dwm_delete_widget', 'DWM_Widget_Manager', 'ajax_delete_widget' );
		$this->add_action( 'wp_ajax_dwm_permanent_delete', 'DWM_Widget_Manager', 'ajax_permanent_delete' );
		$this->add_action( 'wp_ajax_dwm_empty_trash', 'DWM_Widget_Manager', 'ajax_empty_trash' );
		$this->add_action( 'wp_ajax_dwm_toggle_widget', 'DWM_Widget_Manager', 'ajax_toggle_widget' );
		$this->add_action( 'wp_ajax_dwm_reorder_widgets', 'DWM_Widget_Manager', 'ajax_reorder_widgets' );
		$this->add_action( 'wp_ajax_dwm_preview_widget', 'DWM_Widget_Manager', 'ajax_preview_widget' );
		$this->add_action( 'wp_ajax_dwm_preview_wizard', 'DWM_Widget_Manager', 'ajax_preview_wizard' );
		$this->add_action( 'wp_ajax_dwm_validate_query', 'DWM_Widget_Manager', 'ajax_validate_query' );

		// AJAX - Notifications.
		$this->add_action( 'wp_ajax_dwm_get_notifications', 'DWM_Notifications_AJAX', 'ajax_get_notifications' );
		$this->add_action( 'wp_ajax_dwm_delete_notification', 'DWM_Notifications_AJAX', 'ajax_delete_notification' );
		$this->add_action( 'wp_ajax_dwm_get_notification_count', 'DWM_Notifications_AJAX', 'ajax_get_notification_count' );
		$this->add_action( 'wp_ajax_dwm_mark_read', 'DWM_Notifications_AJAX', 'ajax_mark_read' );
		$this->add_action( 'wp_ajax_dwm_mark_unread', 'DWM_Notifications_AJAX', 'ajax_mark_unread' );

		// AJAX - Support.
		$this->add_action( 'wp_ajax_dwm_submit_ticket', 'DWM_Support_AJAX', 'submit_ticket' );
		$this->add_action( 'wp_ajax_dwm_get_tickets', 'DWM_Support_AJAX', 'get_tickets' );
		$this->add_action( 'wp_ajax_dwm_get_ticket_detail', 'DWM_Support_AJAX', 'get_ticket_detail' );
		$this->add_action( 'wp_ajax_dwm_submit_reply', 'DWM_Support_AJAX', 'submit_reply' );

		// AJAX - Settings.
		$this->add_action( 'wp_ajax_dwm_save_settings', 'DWM_Settings', 'ajax_save_settings' );
		$this->add_action( 'wp_ajax_dwm_reset_settings', 'DWM_Settings', 'ajax_reset_settings' );

		// AJAX - License.
		$this->add_action( 'wp_ajax_dwm_pro_activate_license', 'DWM_License_Manager', 'ajax_activate' );
		$this->add_action( 'wp_ajax_dwm_pro_deactivate_license', 'DWM_License_Manager', 'ajax_deactivate' );

		// AJAX - Export / Import.
		$this->add_action( 'wp_ajax_dwm_export', 'DWM_Export_Import', 'ajax_export' );
		$this->add_action( 'wp_ajax_dwm_import', 'DWM_Export_Import', 'ajax_import' );
		$this->add_action( 'wp_ajax_dwm_validate_import', 'DWM_Export_Import', 'ajax_validate_import' );
		$this->add_action( 'wp_ajax_dwm_reset_data', 'DWM_Export_Import', 'ajax_reset_data' );

		// AJAX - Demo Data.
		$this->add_action( 'wp_ajax_dwm_import_demo_data', 'DWM_Demo_Data', 'ajax_import_demo_data' );
		$this->add_action( 'wp_ajax_dwm_delete_demo_data', 'DWM_Demo_Data', 'ajax_delete_demo_data' );

		// AJAX - Query Builder.
		$this->add_action( 'wp_ajax_dwm_get_tables', 'DWM_Query_Builder', 'ajax_get_tables' );
		$this->add_action( 'wp_ajax_dwm_get_table_columns', 'DWM_Query_Builder', 'ajax_get_table_columns' );
		$this->add_action( 'wp_ajax_dwm_build_query', 'DWM_Query_Builder', 'ajax_build_query' );

		// Cron.
		$this->add_action( 'dwm_cleanup_cache', 'DWM_Data', 'cleanup_expired_cache' );
		$this->add_action( 'dwm_cleanup_trash', 'DWM_Data', 'cleanup_trash' );
	}

	/**
	 * Register all public-facing hooks.
	 */
	private function define_public_hooks() {
		// Dashboard widgets.
		$this->add_action( 'wp_dashboard_setup', 'DWM_Widget_Renderer', 'register_dashboard_widgets' );
	}

	/**
	 * Add an action to the collection.
	 *
	 * @param string $hook The name of the WordPress action.
	 * @param string $class The class name.
	 * @param string $callback The callback method name.
	 * @param int    $priority The priority at which the function should be fired.
	 * @param int    $accepted_args The number of arguments that should be passed to the callback.
	 */
	public function add_action( $hook, $class, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $class, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a filter to the collection.
	 *
	 * @param string $hook The name of the WordPress filter.
	 * @param string $class The class name.
	 * @param string $callback The callback method name.
	 * @param int    $priority The priority at which the function should be fired.
	 * @param int    $accepted_args The number of arguments that should be passed to the callback.
	 */
	public function add_filter( $hook, $class, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $class, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a hook to the collection.
	 *
	 * @param array  $hooks The collection of hooks.
	 * @param string $hook The name of the WordPress action or filter.
	 * @param string $class The class name.
	 * @param string $callback The callback method name.
	 * @param int    $priority The priority at which the function should be fired.
	 * @param int    $accepted_args The number of arguments that should be passed to the callback.
	 * @return array The collection of hooks.
	 */
	private function add( $hooks, $hook, $class, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'class'         => $class,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Register all hooks with WordPress.
	 */
	public function run() {
		// Register actions.
		foreach ( $this->actions as $hook ) {
			$class_instance = $hook['class']::get_instance();
			add_action(
				$hook['hook'],
				array( $class_instance, $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		// Register filters.
		foreach ( $this->filters as $hook ) {
			$class_instance = $hook['class']::get_instance();
			add_filter(
				$hook['hook'],
				array( $class_instance, $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}
