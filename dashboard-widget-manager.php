<?php
/**
 * Plugin Name: Dashboard Widget Manager
 * Plugin URI: https://topdevamerica.com/plugins/dashboard-widget-manager
 * Description: Create custom dashboard widgets with SQL queries, templates, styles, and scripts. A professional solution for WordPress administrators.
 * Version: 1.0.0
 * Author: TopDevAmerica
 * Author URI: https://topdevamerica.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: dashboard-widget-manager
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package Dashboard_Widget_Manager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 */
define( 'DWM_VERSION', '1.0.0' );

/**
 * Plugin file path.
 */
define( 'DWM_PLUGIN_FILE', __FILE__ );

/**
 * Plugin directory path.
 */
define( 'DWM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'DWM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'DWM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader for plugin classes.
 *
 * @param string $class_name The class name to load.
 */
function dwm_autoloader( $class_name ) {
	// Check if the class uses the DWM_ prefix.
	if ( strpos( $class_name, 'DWM_' ) !== 0 ) {
		return;
	}

	// Convert class name to file name.
	$class_file = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

	// Check if it's a trait.
	if ( strpos( $class_name, 'DWM_Singleton' ) !== false || strpos( $class_name, 'DWM_AJAX_Handler' ) !== false ) {
		$class_file = 'trait-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
	}

	// Define the directories to search.
	$directories = array(
		DWM_PLUGIN_DIR . 'includes/',
		DWM_PLUGIN_DIR . 'includes/admin/',
		DWM_PLUGIN_DIR . 'includes/core/',
		DWM_PLUGIN_DIR . 'includes/public/',
		DWM_PLUGIN_DIR . 'includes/traits/',
	);

	// Search for the class file in each directory.
	foreach ( $directories as $directory ) {
		$file_path = $directory . $class_file;
		if ( file_exists( $file_path ) ) {
			require_once $file_path;
			return;
		}
	}
}

// Register the autoloader.
spl_autoload_register( 'dwm_autoloader' );

// Load CLI commands if WP-CLI is available.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once DWM_PLUGIN_DIR . 'includes/core/class-dwm-cli.php';
}

/**
 * Check PHP version.
 */
function dwm_check_php_version() {
	if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
		add_action( 'admin_notices', 'dwm_php_version_notice' );
		return false;
	}
	return true;
}

/**
 * PHP version notice.
 */
function dwm_php_version_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required PHP version, 2: Current PHP version */
				esc_html__( 'Dashboard Widget Manager requires PHP version %1$s or higher. You are running version %2$s.', 'dashboard-widget-manager' ),
				'8.0',
				PHP_VERSION
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Check WordPress version.
 */
function dwm_check_wp_version() {
	global $wp_version;
	if ( version_compare( $wp_version, '6.0', '<' ) ) {
		add_action( 'admin_notices', 'dwm_wp_version_notice' );
		return false;
	}
	return true;
}

/**
 * WordPress version notice.
 */
function dwm_wp_version_notice() {
	global $wp_version;
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required WordPress version, 2: Current WordPress version */
				esc_html__( 'Dashboard Widget Manager requires WordPress version %1$s or higher. You are running version %2$s.', 'dashboard-widget-manager' ),
				'6.0',
				$wp_version
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Activation hook.
 */
function dwm_activate() {
	// Check versions before activation.
	if ( ! dwm_check_php_version() || ! dwm_check_wp_version() ) {
		deactivate_plugins( DWM_PLUGIN_BASENAME );
		wp_die(
			esc_html__( 'Dashboard Widget Manager could not be activated due to version requirements.', 'dashboard-widget-manager' ),
			esc_html__( 'Plugin Activation Error', 'dashboard-widget-manager' ),
			array( 'back_link' => true )
		);
	}

	require_once DWM_PLUGIN_DIR . 'includes/class-dwm-activator.php';
	DWM_Activator::activate();
}

register_activation_hook( __FILE__, 'dwm_activate' );

/**
 * Deactivation hook.
 */
function dwm_deactivate() {
	require_once DWM_PLUGIN_DIR . 'includes/class-dwm-deactivator.php';
	DWM_Deactivator::deactivate();
}

register_deactivation_hook( __FILE__, 'dwm_deactivate' );

add_action( 'admin_init', function() {
	require_once DWM_PLUGIN_DIR . 'includes/class-dwm-activator.php';
	DWM_Activator::maybe_upgrade();
} );

/**
 * Initialize the plugin.
 */
function dwm_init() {
	// Check versions.
	if ( ! dwm_check_php_version() || ! dwm_check_wp_version() ) {
		return;
	}

	// Load text domain.
	load_plugin_textdomain(
		'dashboard-widget-manager',
		false,
		dirname( DWM_PLUGIN_BASENAME ) . '/languages'
	);

	// Initialize the loader.
	$loader = DWM_Loader::get_instance();
	$loader->run();
}

add_action( 'plugins_loaded', 'dwm_init' );
