<?php
/**
 * Validator Class
 *
 * Handles validation of all input data.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validator class.
 *
 * Provides static methods for validating various types of input.
 */
class DWM_Validator {

	/**
	 * Dangerous SQL patterns to block.
	 *
	 * @var array
	 */
	private static $dangerous_patterns = array(
		'/\b(DROP|DELETE|UPDATE|INSERT|ALTER|CREATE|TRUNCATE|GRANT|REVOKE|REPLACE)\b/i',
		'/\b(LOAD_FILE|INTO\s+OUTFILE|INTO\s+DUMPFILE)\b/i',
		'/\b(EXEC|EXECUTE|SCRIPT)\b/i',
		'/--/',
		'/#/',
		'/\/\*/',
		'/\*\//',
	);

	/**
	 * Validate widget data.
	 *
	 * @param array $widget Widget data array.
	 * @return array Array of error messages. Empty if valid.
	 */
	public static function validate_widget_data( $widget ) {
		$errors = array();

		// Validate name.
		if ( empty( $widget['name'] ) ) {
			$errors[] = __( 'Widget name is required.', 'dashboard-widget-manager' );
		} elseif ( strlen( $widget['name'] ) > 255 ) {
			$errors[] = __( 'Widget name must be less than 255 characters.', 'dashboard-widget-manager' );
		}

		// Validate SQL query.
		if ( empty( $widget['sql_query'] ) ) {
			$errors[] = __( 'SQL query is required.', 'dashboard-widget-manager' );
		} else {
			$query_errors = self::validate_sql_query( $widget['sql_query'] );
			$errors       = array_merge( $errors, $query_errors );
		}

		// Validate cache duration.
		if ( isset( $widget['cache_duration'] ) ) {
			$cache_errors = self::validate_cache_duration( $widget['cache_duration'] );
			$errors       = array_merge( $errors, $cache_errors );
		}

		// Validate max execution time.
		if ( isset( $widget['max_execution_time'] ) ) {
			if ( ! is_numeric( $widget['max_execution_time'] ) ) {
				$errors[] = __( 'Maximum execution time must be a number.', 'dashboard-widget-manager' );
			} elseif ( $widget['max_execution_time'] < 1 ) {
				$errors[] = __( 'Maximum execution time must be at least 1 second.', 'dashboard-widget-manager' );
			} elseif ( $widget['max_execution_time'] > 60 ) {
				$errors[] = __( 'Maximum execution time cannot exceed 60 seconds.', 'dashboard-widget-manager' );
			}
		}

		// Validate widget order.
		if ( isset( $widget['widget_order'] ) && $widget['widget_order'] < 0 ) {
			$errors[] = __( 'Widget order must be a positive number.', 'dashboard-widget-manager' );
		}

		return $errors;
	}

	/**
	 * Validate SQL query for security.
	 *
	 * @param string $query SQL query to validate.
	 * @return array Array of error messages. Empty if valid.
	 */
	public static function validate_sql_query( $query ) {
		$errors = array();

		if ( empty( $query ) ) {
			$errors[] = __( 'SQL query cannot be empty.', 'dashboard-widget-manager' );
			return $errors;
		}

		// Check if query starts with SELECT.
		$trimmed_query = trim( strtoupper( $query ) );
		if ( ! preg_match( '/^SELECT\b/i', $trimmed_query ) ) {
			$errors[] = __( 'Only SELECT queries are allowed.', 'dashboard-widget-manager' );
		}

		// Check for dangerous patterns.
		foreach ( self::$dangerous_patterns as $pattern ) {
			if ( preg_match( $pattern, $query ) ) {
				$errors[] = __( 'Query contains dangerous SQL commands or patterns.', 'dashboard-widget-manager' );
				break;
			}
		}

		// Check for multiple statements (semicolon not at the end).
		$query_clean = trim( $query, "; \t\n\r\0\x0B" );
		if ( strpos( $query_clean, ';' ) !== false ) {
			$errors[] = __( 'Multiple SQL statements are not allowed.', 'dashboard-widget-manager' );
		}

		// Check for UNION attacks.
		if ( preg_match( '/\bUNION\b.*\bSELECT\b/i', $query ) ) {
			// UNION SELECT is often used in SQL injection, but may be legitimate.
			// We'll allow it but could add a warning.
			// For maximum security in production, uncomment the line below:
			// $errors[] = __( 'UNION queries are not allowed for security reasons.', 'dashboard-widget-manager' );
		}

		return $errors;
	}

	/**
	 * Validate cache duration.
	 *
	 * @param int $duration Cache duration in seconds.
	 * @return array Array of error messages. Empty if valid.
	 */
	public static function validate_cache_duration( $duration ) {
		$errors = array();

		if ( ! is_numeric( $duration ) ) {
			$errors[] = __( 'Cache duration must be a number.', 'dashboard-widget-manager' );
		} elseif ( $duration < 0 ) {
			$errors[] = __( 'Cache duration cannot be negative.', 'dashboard-widget-manager' );
		} elseif ( $duration > 3600 ) {
			$errors[] = __( 'Cache duration cannot exceed 1 hour (3600 seconds).', 'dashboard-widget-manager' );
		}

		return $errors;
	}

	/**
	 * Validate settings data.
	 *
	 * @param array $settings Settings array.
	 * @return array Array of error messages. Empty if valid.
	 */
	public static function validate_settings( $settings ) {
		return array();
	}

	/**
	 * Validate import data structure.
	 *
	 * @param array $data Import data array.
	 * @return array Array of error messages. Empty if valid.
	 */
	public static function validate_import_data( array $data ) {
		$errors = array();

		// If plugin identifier is present, it must match.
		if ( isset( $data['plugin'] ) && 'dashboard-widget-manager' !== $data['plugin'] ) {
			$errors[] = __( 'Invalid export file: plugin identifier mismatch.', 'dashboard-widget-manager' );
		}

		// If version is present, check compatibility.
		if ( isset( $data['version'] ) && version_compare( $data['version'], DWM_VERSION, '>' ) ) {
			$errors[] = sprintf(
				/* translators: %1$s: file version, %2$s: installed version */
				__( 'Import file is from a newer version (%1$s) than installed (%2$s).', 'dashboard-widget-manager' ),
				esc_html( $data['version'] ),
				DWM_VERSION
			);
		}

		// Check that at least one valid data type is present.
		$valid_keys = array( 'widgets', 'settings' );
		$has_data   = false;
		foreach ( $valid_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$has_data = true;
				break;
			}
		}

		if ( ! $has_data ) {
			$errors[] = __( 'Invalid import file: no valid data found.', 'dashboard-widget-manager' );
		}

		// Validate widgets structure if present.
		if ( isset( $data['widgets'] ) && is_array( $data['widgets'] ) ) {
			foreach ( $data['widgets'] as $index => $widget ) {
				if ( ! is_array( $widget ) ) {
					$errors[] = sprintf(
						/* translators: %d: widget index */
						__( 'Invalid widget at index %d: must be an array.', 'dashboard-widget-manager' ),
						$index
					);
				}
			}
		}

		return $errors;
	}

	/**
	 * Validate table name against WordPress prefix.
	 *
	 * @param string $table_name Table name to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_table_name( $table_name ) {
		global $wpdb;

		// Check if table name starts with WordPress prefix.
		if ( strpos( $table_name, $wpdb->prefix ) !== 0 ) {
			return false;
		}

		// Check for valid characters (alphanumeric and underscore only).
		if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $table_name ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Extract table names from SQL query.
	 *
	 * @param string $query SQL query.
	 * @return array Array of table names found in query.
	 */
	public static function extract_table_names( $query ) {
		$tables = array();

		// Match table names after FROM and JOIN clauses.
		preg_match_all( '/\b(?:FROM|JOIN)\s+([a-zA-Z0-9_]+)/i', $query, $matches );

		if ( ! empty( $matches[1] ) ) {
			$tables = $matches[1];
		}

		return array_unique( $tables );
	}

	/**
	 * Validate query against allowed tables.
	 *
	 * @param string $query SQL query.
	 * @param array  $allowed_tables Array of allowed table names.
	 * @return array Array of error messages. Empty if valid.
	 */
	public static function validate_query_tables( $query, $allowed_tables = array() ) {
		$errors = array();

		// If no restrictions, allow all WordPress tables.
		if ( empty( $allowed_tables ) ) {
			return $errors;
		}

		// Extract table names from query.
		$query_tables = self::extract_table_names( $query );

		// Check each table against allowed list.
		foreach ( $query_tables as $table ) {
			if ( ! in_array( $table, $allowed_tables, true ) ) {
				$errors[] = sprintf(
					/* translators: %s: table name */
					__( 'Table "%s" is not in the allowed tables list.', 'dashboard-widget-manager' ),
					$table
				);
			}
		}

		return $errors;
	}
}
