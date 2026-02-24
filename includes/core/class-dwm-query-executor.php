<?php
/**
 * Query Executor Class
 *
 * Handles secure SQL query execution with caching.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query Executor class.
 *
 * Executes SQL queries securely with validation and caching.
 */
class DWM_Query_Executor {

	use DWM_Singleton;

	/**
	 * Execute widget query.
	 *
	 * @param int  $widget_id Widget ID.
	 * @param bool $force_refresh Force refresh from database.
	 * @return array|WP_Error Query results or error.
	 */
	public function execute_query( $widget_id, $force_refresh = false ) {
		$data   = DWM_Data::get_instance();
		$widget = $data->get_widget( $widget_id );

		if ( ! $widget ) {
			return new WP_Error( 'widget_not_found', __( 'Widget not found.', 'dashboard-widget-manager' ) );
		}

		$query = $widget['sql_query'];

		// Validate query.
		$validation_errors = DWM_Validator::validate_sql_query( $query );
		if ( ! empty( $validation_errors ) ) {
			return new WP_Error( 'invalid_query', implode( ' ', $validation_errors ) );
		}

		// Parse query variables.
		$query = $this->parse_query_variables( $query );

		// Check cache.
		$settings       = $data->get_settings();
		$cache_enabled  = $settings['enable_caching'];
		$cache_duration = $widget['cache_duration'] > 0 ? $widget['cache_duration'] : $settings['default_cache_duration'];

		if ( $cache_enabled && ! $force_refresh && $cache_duration > 0 ) {
			$cache_key = $this->generate_cache_key( $widget_id, $query );
			$cached    = $data->get_cached_query_result( $cache_key );

			if ( $cached !== null ) {
				return $cached;
			}
		}

		// Execute query.
		$results = $this->execute_sql( $query );

		if ( is_wp_error( $results ) ) {
			return $results;
		}

		// Cache results.
		if ( $cache_enabled && $cache_duration > 0 ) {
			$cache_key = $this->generate_cache_key( $widget_id, $query );
			$data->set_cached_query_result( $cache_key, $widget_id, $results, $cache_duration );
		}

		return $results;
	}

	/**
	 * Validate query structure.
	 *
	 * @param string $query SQL query.
	 * @return array Array of validation errors. Empty if valid.
	 */
	public function validate_query( $query ) {
		return DWM_Validator::validate_sql_query( $query );
	}

	/**
	 * Parse query variables.
	 *
	 * Replaces template variables with actual values.
	 *
	 * @param string $query SQL query with variables.
	 * @return string Parsed SQL query.
	 */
	public function parse_query_variables( $query ) {
		$variables = array(
			'{{current_user_id}}' => get_current_user_id(),
			'{{site_url}}'        => get_site_url(),
			'{{admin_email}}'     => get_option( 'admin_email' ),
			'{{current_time}}'    => current_time( 'mysql' ),
			'{{current_date}}'    => current_time( 'Y-m-d' ),
		);

		// Add WordPress table prefix variables.
		global $wpdb;
		$variables['{{table_prefix}}'] = $wpdb->prefix;

		// Common WordPress table shortcuts.
		$variables['{{posts}}']    = $wpdb->posts;
		$variables['{{users}}']    = $wpdb->users;
		$variables['{{comments}}'] = $wpdb->comments;
		$variables['{{options}}']  = $wpdb->options;
		$variables['{{postmeta}}'] = $wpdb->postmeta;
		$variables['{{usermeta}}'] = $wpdb->usermeta;

		// Replace variables.
		$query = str_replace( array_keys( $variables ), array_values( $variables ), $query );

		return $query;
	}

	/**
	 * Execute SQL query.
	 *
	 * @param string $query SQL query to execute.
	 * @return array|WP_Error Query results or error.
	 */
	private function execute_sql( $query ) {
		global $wpdb;

		// Get max execution time from settings.
		$data     = DWM_Data::get_instance();
		$settings = $data->get_settings();
		$max_time = isset( $settings['max_execution_time'] ) ? (int) $settings['max_execution_time'] : 30;

		// Start timing.
		$start_time = microtime( true );

		// Suppress errors to handle them gracefully.
		$wpdb->suppress_errors( true );

		// Execute query.
		$results = $wpdb->get_results( $query, ARRAY_A );

		// End timing.
		$execution_time = microtime( true ) - $start_time;

		// Check for errors.
		if ( $wpdb->last_error ) {
			return new WP_Error(
				'query_error',
				sprintf(
					/* translators: %s: Database error message */
					__( 'Database error: %s', 'dashboard-widget-manager' ),
					$wpdb->last_error
				)
			);
		}

		// Check execution time.
		if ( $execution_time > $max_time ) {
			return new WP_Error(
				'query_timeout',
				sprintf(
					/* translators: 1: Execution time, 2: Max allowed time */
					__( 'Query execution time (%.2fs) exceeded maximum allowed time (%ds).', 'dashboard-widget-manager' ),
					$execution_time,
					$max_time
				)
			);
		}

		// Log query if logging is enabled.
		if ( $settings['enable_query_logging'] ) {
			$this->log_query( $query, $execution_time, count( $results ) );
		}

		return $results;
	}

	/**
	 * Format query results.
	 *
	 * @param array  $results Query results.
	 * @param string $format Output format (array, json, csv).
	 * @return mixed Formatted results.
	 */
	public function format_results( $results, $format = 'array' ) {
		switch ( $format ) {
			case 'json':
				return wp_json_encode( $results );

			case 'csv':
				if ( empty( $results ) ) {
					return '';
				}

				$output = '';
				// Header row.
				$output .= implode( ',', array_keys( $results[0] ) ) . "\n";
				// Data rows.
				foreach ( $results as $row ) {
					$output .= implode( ',', array_map( 'esc_csv', $row ) ) . "\n";
				}
				return $output;

			default:
				return $results;
		}
	}

	/**
	 * Generate cache key.
	 *
	 * @param int    $widget_id Widget ID.
	 * @param string $query SQL query.
	 * @return string Cache key.
	 */
	private function generate_cache_key( $widget_id, $query ) {
		return 'dwm_' . md5( $widget_id . $query );
	}

	/**
	 * Log query execution.
	 *
	 * @param string $query SQL query.
	 * @param float  $execution_time Execution time in seconds.
	 * @param int    $row_count Number of rows returned.
	 */
	private function log_query( $query, $execution_time, $row_count ) {
		// Log to WordPress debug log if WP_DEBUG_LOG is enabled.
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log(
				sprintf(
					'[DWM Query] Time: %.4fs | Rows: %d | Query: %s',
					$execution_time,
					$row_count,
					$query
				)
			);
		}
	}

	/**
	 * Test query without caching.
	 *
	 * Used for query validation in admin interface.
	 *
	 * @param string $query SQL query to test.
	 * @return array|WP_Error Query results or error.
	 */
	public function test_query( $query ) {
		// Validate query first.
		$validation_errors = $this->validate_query( $query );
		if ( ! empty( $validation_errors ) ) {
			return new WP_Error( 'invalid_query', implode( ' ', $validation_errors ) );
		}

		// Parse variables.
		$query = $this->parse_query_variables( $query );

		// Execute query.
		$results = $this->execute_sql( $query );

		return $results;
	}
}
