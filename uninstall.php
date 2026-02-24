<?php
/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled.
 * Removes all plugin data from the database.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly or not during uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check user capability.
if ( ! current_user_can( 'delete_plugins' ) ) {
	exit;
}

global $wpdb;

// Delete transients.
delete_transient( 'dwm_query_cache' );

// Clear scheduled hooks.
wp_clear_scheduled_hook( 'dwm_cleanup_cache' );

// Drop custom tables.
$widgets_table  = $wpdb->prefix . 'dwm_widgets';
$cache_table    = $wpdb->prefix . 'dwm_query_cache';
$settings_table = $wpdb->prefix . 'dwm_settings';

$wpdb->query( "DROP TABLE IF EXISTS {$settings_table}" );
$wpdb->query( "DROP TABLE IF EXISTS {$cache_table}" );
$wpdb->query( "DROP TABLE IF EXISTS {$widgets_table}" );

// Clear any plugin-specific transients (if any exist).
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dwm_%' OR option_name LIKE '_transient_timeout_dwm_%'" );
