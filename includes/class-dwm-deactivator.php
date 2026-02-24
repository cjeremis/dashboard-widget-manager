<?php
/**
 * Deactivator Class
 *
 * Handles plugin deactivation.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivator class.
 *
 * Handles all plugin deactivation logic.
 */
class DWM_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Clears caches and unschedules cron jobs.
	 * Does not delete data - that's handled by uninstall.php.
	 */
	public static function deactivate() {
		// Check capability.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Clear query cache transients.
		self::clear_cache();

		// Unschedule cron jobs.
		self::unschedule_cron();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Clear all plugin caches.
	 */
	private static function clear_cache() {
		global $wpdb;

		// Clear query cache table.
		$cache_table = $wpdb->prefix . 'dwm_query_cache';
		$wpdb->query( "TRUNCATE TABLE {$cache_table}" );

		// Delete any transients (if we add any in the future).
		delete_transient( 'dwm_query_cache' );
	}

	/**
	 * Unschedule cron jobs.
	 */
	private static function unschedule_cron() {
		$timestamp = wp_next_scheduled( 'dwm_cleanup_cache' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'dwm_cleanup_cache' );
		}
	}
}
