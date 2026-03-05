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

		// Validate output_config.
		if ( ! empty( $widget['output_config'] ) ) {
			$output_errors = self::validate_output_config( $widget['output_config'] );
			$errors        = array_merge( $errors, $output_errors );
		}

		// Validate builder_config selected columns.
		if ( ! empty( $widget['builder_config'] ) ) {
			$builder_config = is_string( $widget['builder_config'] )
				? json_decode( $widget['builder_config'], true )
				: $widget['builder_config'];

			if ( is_array( $builder_config ) && ! empty( $builder_config['table'] ) ) {
				$columns = isset( $builder_config['columns'] ) && is_array( $builder_config['columns'] )
					? $builder_config['columns']
					: array();

				if ( empty( $columns ) ) {
					$errors[] = __( 'At least one column must be selected in the visual builder.', 'dashboard-widget-manager' );
				}
			}
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

		// Block UNION SELECT — prevents cross-table data exfiltration (e.g. wp_users) by admin-level users.
		if ( preg_match( '/\bUNION\b.*\bSELECT\b/i', $query ) ) {
			$errors[] = __( 'UNION queries are not allowed for security reasons.', 'dashboard-widget-manager' );
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
	 * Validate output_config JSON string.
	 *
	 * @param string $output_config JSON string.
	 * @return array Array of error messages. Empty if valid.
	 */
	public static function validate_output_config( $output_config ) {
		$errors = array();

		if ( empty( $output_config ) ) {
			return $errors;
		}

		$config = json_decode( $output_config, true );
		if ( ! is_array( $config ) || JSON_ERROR_NONE !== json_last_error() ) {
			$errors[] = __( 'Output config must be valid JSON.', 'dashboard-widget-manager' );
			return $errors;
		}

		// Validate display_mode.
		if ( isset( $config['display_mode'] ) ) {
			$valid_modes = DWM_Sanitizer::get_valid_display_modes();
			if ( ! in_array( $config['display_mode'], $valid_modes, true ) ) {
				$errors[] = sprintf(
					__( 'Invalid display mode "%s". Must be one of: %s', 'dashboard-widget-manager' ),
					esc_html( $config['display_mode'] ),
					implode( ', ', $valid_modes )
				);
			}
		}

		// Validate theme.
		if ( isset( $config['theme'] ) ) {
			$valid_themes = DWM_Sanitizer::get_valid_themes();
			if ( ! in_array( $config['theme'], $valid_themes, true ) ) {
				$errors[] = sprintf(
					__( 'Invalid theme "%s". Must be one of: %s', 'dashboard-widget-manager' ),
					esc_html( $config['theme'] ),
					implode( ', ', $valid_themes )
				);
			}
		}

		// Validate columns.
		if ( isset( $config['columns'] ) ) {
			if ( ! is_array( $config['columns'] ) ) {
				$errors[] = __( 'Output config columns must be an array.', 'dashboard-widget-manager' );
			} else {
				$seen_aliases = array();

				foreach ( $config['columns'] as $index => $col ) {
					if ( ! is_array( $col ) ) {
						$errors[] = sprintf(
							__( 'Column at index %d must be an object.', 'dashboard-widget-manager' ),
							$index
						);
						continue;
					}

					if ( empty( $col['key'] ) ) {
						$errors[] = sprintf(
							__( 'Column at index %d is missing required "key" field.', 'dashboard-widget-manager' ),
							$index
						);
					}

					if ( isset( $col['alias'] ) ) {
						$alias_normalized = strtolower( trim( (string) $col['alias'] ) );
						if ( '' !== $alias_normalized ) {
							if ( isset( $seen_aliases[ $alias_normalized ] ) ) {
								$errors[] = sprintf(
									__( 'Duplicate column alias "%s" found in output config.', 'dashboard-widget-manager' ),
									esc_html( $col['alias'] )
								);
							} else {
								$seen_aliases[ $alias_normalized ] = true;
							}
						}
					}

					// Validate formatter type if present.
					if ( isset( $col['formatter'] ) && is_array( $col['formatter'] ) && isset( $col['formatter']['type'] ) ) {
						$valid_formatters = DWM_Sanitizer::get_valid_formatter_types();
						if ( ! in_array( $col['formatter']['type'], $valid_formatters, true ) ) {
							$errors[] = sprintf(
								__( 'Column "%s" has invalid formatter type "%s". Must be one of: %s', 'dashboard-widget-manager' ),
								esc_html( $col['key'] ?? $index ),
								esc_html( $col['formatter']['type'] ),
								implode( ', ', $valid_formatters )
							);
						}
					}

					// Validate link config if present.
					if ( isset( $col['link'] ) && is_array( $col['link'] ) ) {
						if ( ! empty( $col['link']['enabled'] ) && empty( $col['link']['url'] ) ) {
							$errors[] = sprintf(
								__( 'Column "%s" has link enabled but no URL specified.', 'dashboard-widget-manager' ),
								esc_html( $col['key'] ?? $index )
							);
						}
					}
				}
			}
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
			$seen_uuids = array();
			foreach ( $data['widgets'] as $index => $widget ) {
				if ( ! is_array( $widget ) ) {
					$errors[] = sprintf(
						/* translators: %d: widget index */
						__( 'Invalid widget at index %d: must be an array.', 'dashboard-widget-manager' ),
						$index
					);
					continue;
				}

				if ( isset( $widget['uuid'] ) && '' !== trim( (string) $widget['uuid'] ) ) {
					$uuid = strtolower( trim( (string) $widget['uuid'] ) );
					if ( function_exists( 'wp_is_uuid' ) ) {
						$is_valid_uuid = wp_is_uuid( $uuid, 4 ) || wp_is_uuid( $uuid );
					} else {
						$is_valid_uuid = (bool) preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid );
					}

					if ( ! $is_valid_uuid ) {
						$errors[] = sprintf(
							/* translators: %d: widget index */
							__( 'Invalid UUID format for widget at index %d.', 'dashboard-widget-manager' ),
							$index
						);
					} elseif ( isset( $seen_uuids[ $uuid ] ) ) {
						$errors[] = sprintf(
							/* translators: %d: widget index */
							__( 'Duplicate widget UUID found at index %d.', 'dashboard-widget-manager' ),
							$index
						);
					} else {
						$seen_uuids[ $uuid ] = true;
					}
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
	 * Validate query against excluded tables.
	 *
	 * @param string $query SQL query.
	 * @param array  $excluded_tables Array of excluded table names.
	 * @return array Array of error messages. Empty if valid.
	 */
	public static function validate_query_tables( $query, $excluded_tables = array() ) {
		$errors = array();

		// If nothing excluded, allow all tables.
		if ( empty( $excluded_tables ) ) {
			return $errors;
		}

		// Extract table names from query.
		$query_tables = self::extract_table_names( $query );

		// Check each table against excluded list.
		foreach ( $query_tables as $table ) {
			if ( in_array( $table, $excluded_tables, true ) ) {
				$errors[] = sprintf(
					/* translators: %s: table name */
					__( 'Table "%s" is not allowed by security settings.', 'dashboard-widget-manager' ),
					$table
				);
			}
		}

		return $errors;
	}
}
