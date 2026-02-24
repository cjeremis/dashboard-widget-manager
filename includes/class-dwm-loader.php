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

		// Settings.
		$this->add_action( 'admin_init', 'DWM_Settings', 'register_settings' );

		// AJAX - Widget Management.
		$this->add_action( 'wp_ajax_dwm_get_widgets', 'DWM_Widget_Manager', 'ajax_get_widgets' );
		$this->add_action( 'wp_ajax_dwm_get_widget', 'DWM_Widget_Manager', 'ajax_get_widget' );
		$this->add_action( 'wp_ajax_dwm_create_widget', 'DWM_Widget_Manager', 'ajax_create_widget' );
		$this->add_action( 'wp_ajax_dwm_update_widget', 'DWM_Widget_Manager', 'ajax_update_widget' );
		$this->add_action( 'wp_ajax_dwm_delete_widget', 'DWM_Widget_Manager', 'ajax_delete_widget' );
		$this->add_action( 'wp_ajax_dwm_toggle_widget', 'DWM_Widget_Manager', 'ajax_toggle_widget' );
		$this->add_action( 'wp_ajax_dwm_reorder_widgets', 'DWM_Widget_Manager', 'ajax_reorder_widgets' );
		$this->add_action( 'wp_ajax_dwm_preview_widget', 'DWM_Widget_Manager', 'ajax_preview_widget' );
		$this->add_action( 'wp_ajax_dwm_validate_query', 'DWM_Widget_Manager', 'ajax_validate_query' );

		// AJAX - Settings.
		$this->add_action( 'wp_ajax_dwm_save_settings', 'DWM_Settings', 'ajax_save_settings' );
		$this->add_action( 'wp_ajax_dwm_reset_settings', 'DWM_Settings', 'ajax_reset_settings' );

		// Cron.
		$this->add_action( 'dwm_cleanup_cache', 'DWM_Data', 'cleanup_expired_cache' );
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
