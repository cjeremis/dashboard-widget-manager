<?php
/**
 * Sanitizer Class
 *
 * Handles sanitization of all input data.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitizer class.
 *
 * Provides static methods for sanitizing various types of input.
 */
class DWM_Sanitizer {

	/**
	 * Sanitize widget name.
	 *
	 * @param string $name Widget name.
	 * @return string Sanitized widget name.
	 */
	public static function sanitize_widget_name( $name ) {
		return sanitize_text_field( $name );
	}

	/**
	 * Sanitize widget description.
	 *
	 * @param string $description Widget description.
	 * @return string Sanitized description.
	 */
	public static function sanitize_widget_description( $description ) {
		return sanitize_textarea_field( $description );
	}

	/**
	 * Sanitize SQL query.
	 *
	 * Removes comments and normalizes whitespace while preserving query structure.
	 *
	 * @param string $query SQL query.
	 * @return string Sanitized SQL query.
	 */
	public static function sanitize_sql_query( $query ) {
		if ( empty( $query ) ) {
			return '';
		}

		// Remove line comments (-- and #).
		$query = preg_replace( '/--[^\n]*/', '', $query );
		$query = preg_replace( '/#[^\n]*/', '', $query );

		// Remove block comments (/* */).
		$query = preg_replace( '/\/\*.*?\*\//s', '', $query );

		// Normalize whitespace.
		$query = preg_replace( '/\s+/', ' ', $query );

		// Trim and return.
		return trim( $query );
	}

	/**
	 * Sanitize template content.
	 *
	 * Allows HTML and PHP for administrators.
	 *
	 * @param string $template Template content.
	 * @return string Sanitized template.
	 */
	public static function sanitize_template( $template ) {
		// Only allow for users with manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		// Allow HTML and PHP for templates.
		// Use wp_kses_post for basic HTML sanitization while allowing most tags.
		return $template;
	}

	/**
	 * Sanitize CSS styles.
	 *
	 * Removes potentially dangerous CSS while preserving valid styles.
	 *
	 * @param string $css CSS content.
	 * @return string Sanitized CSS.
	 */
	public static function sanitize_styles( $css ) {
		if ( empty( $css ) ) {
			return '';
		}

		// Remove script tags and expressions.
		$css = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $css );
		$css = preg_replace( '/expression\s*\(/i', '', $css );
		$css = preg_replace( '/javascript\s*:/i', '', $css );
		$css = preg_replace( '/vbscript\s*:/i', '', $css );
		$css = preg_replace( '/-moz-binding\s*:/i', '', $css );

		// Remove import statements for security.
		$css = preg_replace( '/@import/i', '', $css );

		return trim( $css );
	}

	/**
	 * Sanitize JavaScript code.
	 *
	 * Basic sanitization for JavaScript content.
	 *
	 * @param string $js JavaScript content.
	 * @return string Sanitized JavaScript.
	 */
	public static function sanitize_scripts( $js ) {
		// Only allow for users with manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		// Allow JavaScript for administrators.
		// Remove any PHP tags if present.
		$js = str_replace( array( '<?php', '<?', '?>' ), '', $js );

		return trim( $js );
	}

	/**
	 * Sanitize boolean value.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return bool Boolean value.
	 */
	public static function sanitize_boolean( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Sanitize integer value.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return int Integer value.
	 */
	public static function sanitize_integer( $value ) {
		return absint( $value );
	}

	/**
	 * Sanitize cache duration.
	 *
	 * @param mixed $duration Duration in seconds.
	 * @return int Sanitized duration.
	 */
	public static function sanitize_cache_duration( $duration ) {
		$duration = absint( $duration );

		// Maximum 1 hour (3600 seconds).
		if ( $duration > 3600 ) {
			$duration = 3600;
		}

		return $duration;
	}

	/**
	 * Sanitize widget order.
	 *
	 * @param mixed $order Widget order.
	 * @return int Sanitized order.
	 */
	public static function sanitize_widget_order( $order ) {
		return absint( $order );
	}

	public static function sanitize_status( $status ) {
		$valid = array( 'publish', 'draft', 'archived', 'trash' );
		return in_array( $status, $valid, true ) ? $status : 'draft';
	}

	/**
	 * Valid display modes for output_config.
	 *
	 * @var array
	 */
	private static $valid_display_modes = array( 'table', 'list', 'button', 'card-list' );

	/**
	 * Valid themes for output_config.
	 *
	 * Includes both the legacy theme-1..theme-6 inline CSS palette slugs and
	 * the named theme slugs used by the JS template/CSS auto-generator.
	 *
	 * @var array
	 */
	private static $valid_themes = array(
		// Inline CSS palette slugs (output_config renderer).
		'theme-1', 'theme-2', 'theme-3', 'theme-4', 'theme-5', 'theme-6',
		// Named table themes (JS template generator).
		'default', 'minimal', 'dark', 'striped', 'bordered', 'ocean',
		// Named list themes.
		'clean', 'compact', 'accent',
		// Named button themes.
		'solid', 'outline', 'pill', 'flat', 'gradient',
		// Named card-list themes.
		'elevated', 'colorful',
		// Named chart themes.
		'classic', 'sunset', 'forest', 'oceanic', 'monochrome', 'candy',
	);

	/**
	 * Valid formatter types for output_config columns.
	 *
	 * @var array
	 */
	private static $valid_formatter_types = array( 'text', 'date', 'number', 'excerpt', 'case' );

	/**
	 * Sanitize output_config JSON string.
	 *
	 * Parses, sanitizes, and re-encodes output_config.
	 *
	 * @param string $raw Raw output_config JSON string.
	 * @return string Sanitized JSON string, or empty string on invalid input.
	 */
	public static function sanitize_output_config( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return '';
		}

		$config = json_decode( $raw, true );
		if ( ! is_array( $config ) || JSON_ERROR_NONE !== json_last_error() ) {
			return '';
		}

		$sanitized = self::sanitize_output_config_array( $config );

		return wp_json_encode( $sanitized );
	}

	/**
	 * Sanitize output_config as an associative array.
	 *
	 * @param array $config Decoded output_config array.
	 * @return array Sanitized output_config array.
	 */
	public static function sanitize_output_config_array( $config ) {
		$sanitized = array();

		// display_mode.
		$display_mode = isset( $config['display_mode'] ) ? sanitize_text_field( $config['display_mode'] ) : 'table';
		if ( ! in_array( $display_mode, self::$valid_display_modes, true ) ) {
			$display_mode = 'table';
		}
		$sanitized['display_mode'] = $display_mode;

		// theme.
		$theme = isset( $config['theme'] ) ? sanitize_text_field( $config['theme'] ) : 'default';
		if ( ! in_array( $theme, self::$valid_themes, true ) ) {
			$theme = 'default';
		}
		$sanitized['theme'] = $theme;

		// columns.
		$sanitized['columns'] = array();
		if ( isset( $config['columns'] ) && is_array( $config['columns'] ) ) {
			foreach ( $config['columns'] as $col ) {
				if ( ! is_array( $col ) ) {
					continue;
				}
				$sanitized['columns'][] = self::sanitize_output_column( $col );
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize a single column config within output_config.
	 *
	 * @param array $col Column config array.
	 * @return array Sanitized column config.
	 */
	private static function sanitize_output_column( $col ) {
		$sanitized = array();

		$sanitized['key']     = isset( $col['key'] ) ? sanitize_text_field( $col['key'] ) : '';
		$alias = isset( $col['alias'] ) ? sanitize_text_field( $col['alias'] ) : '';
		$sanitized['alias']   = preg_replace( '/[^a-zA-Z0-9_ ]/', '', $alias );
		$sanitized['visible'] = isset( $col['visible'] ) ? (bool) $col['visible'] : true;

		// Link config.
		$link = isset( $col['link'] ) && is_array( $col['link'] ) ? $col['link'] : array();
		$link_url = isset( $link['url'] ) ? (string) $link['url'] : '';
		if ( '' !== $link_url ) {
			$url_for_validation = preg_replace( '/\{[a-zA-Z0-9_]+\}/', 'dwm-token', $link_url );
			$link_url           = '' !== esc_url_raw( $url_for_validation ) ? sanitize_text_field( $link_url ) : '';
		}
		$sanitized['link'] = array(
			'enabled'         => isset( $link['enabled'] ) ? (bool) $link['enabled'] : false,
			'url'             => $link_url,
			'open_in_new_tab' => isset( $link['open_in_new_tab'] ) ? (bool) $link['open_in_new_tab'] : true,
		);

		// Formatter config.
		$formatter = isset( $col['formatter'] ) && is_array( $col['formatter'] ) ? $col['formatter'] : array();
		$formatter_type = isset( $formatter['type'] ) ? sanitize_text_field( $formatter['type'] ) : 'text';
		if ( ! in_array( $formatter_type, self::$valid_formatter_types, true ) ) {
			$formatter_type = 'text';
		}
		$formatter_options = isset( $formatter['options'] ) && is_array( $formatter['options'] ) ? $formatter['options'] : array();
		$sanitized_options = array();
		foreach ( $formatter_options as $opt_key => $opt_val ) {
			$sanitized_options[ sanitize_text_field( $opt_key ) ] = sanitize_text_field( (string) $opt_val );
		}
		$sanitized['formatter'] = array(
			'type'    => $formatter_type,
			'options' => $sanitized_options,
		);

		return $sanitized;
	}

	/**
	 * Get valid display modes.
	 *
	 * @return array
	 */
	public static function get_valid_display_modes() {
		return self::$valid_display_modes;
	}

	/**
	 * Get valid themes.
	 *
	 * @return array
	 */
	public static function get_valid_themes() {
		return self::$valid_themes;
	}

	/**
	 * Get valid formatter types.
	 *
	 * @return array
	 */
	public static function get_valid_formatter_types() {
		return self::$valid_formatter_types;
	}

	/**
	 * Sanitize widget data array.
	 *
	 * @param array $widget_data Widget data array.
	 * @return array Sanitized widget data.
	 */
	public static function sanitize_widget_data( $widget_data ) {
		$sanitized = array();

		if ( isset( $widget_data['name'] ) ) {
			$sanitized['name'] = self::sanitize_widget_name( $widget_data['name'] );
		}

		if ( isset( $widget_data['uuid'] ) ) {
			$sanitized['uuid'] = sanitize_text_field( $widget_data['uuid'] );
		}

		if ( isset( $widget_data['description'] ) ) {
			$sanitized['description'] = self::sanitize_widget_description( $widget_data['description'] );
		}

		if ( isset( $widget_data['sql_query'] ) ) {
			$sanitized['sql_query'] = self::sanitize_sql_query( $widget_data['sql_query'] );
		}

		if ( isset( $widget_data['template'] ) ) {
			$sanitized['template'] = self::sanitize_template( $widget_data['template'] );
		}

		if ( isset( $widget_data['styles'] ) ) {
			$sanitized['styles'] = self::sanitize_styles( $widget_data['styles'] );
		}

		if ( isset( $widget_data['scripts'] ) ) {
			$sanitized['scripts'] = self::sanitize_scripts( $widget_data['scripts'] );
		}

		if ( isset( $widget_data['no_results_template'] ) ) {
			$sanitized['no_results_template'] = self::sanitize_template( $widget_data['no_results_template'] );
		}

		if ( isset( $widget_data['enabled'] ) ) {
			$sanitized['enabled'] = self::sanitize_boolean( $widget_data['enabled'] );
		}

		if ( isset( $widget_data['widget_order'] ) ) {
			$sanitized['widget_order'] = self::sanitize_widget_order( $widget_data['widget_order'] );
		}

		if ( isset( $widget_data['cache_duration'] ) ) {
			$sanitized['cache_duration'] = self::sanitize_cache_duration( $widget_data['cache_duration'] );
		}

		if ( isset( $widget_data['enable_caching'] ) ) {
			$sanitized['enable_caching'] = self::sanitize_boolean( $widget_data['enable_caching'] );
		}

		if ( isset( $widget_data['auto_refresh'] ) ) {
			$sanitized['auto_refresh'] = absint( $widget_data['auto_refresh'] );
		}

		if ( isset( $widget_data['max_execution_time'] ) ) {
			$max = absint( $widget_data['max_execution_time'] );
			$sanitized['max_execution_time'] = min( max( 1, $max ), 60 );
		}

		if ( isset( $widget_data['enable_query_logging'] ) ) {
			$sanitized['enable_query_logging'] = self::sanitize_boolean( $widget_data['enable_query_logging'] );
		}

		if ( array_key_exists( 'chart_type', $widget_data ) ) {
			$sanitized['chart_type'] = sanitize_text_field( (string) $widget_data['chart_type'] );
		}

		if ( array_key_exists( 'chart_config', $widget_data ) ) {
			$chart_config_raw = trim( (string) $widget_data['chart_config'] );
			if ( '' === $chart_config_raw ) {
				$sanitized['chart_config'] = '';
			} else {
				$decoded = json_decode( $chart_config_raw, true );
				if ( JSON_ERROR_NONE === json_last_error() ) {
					$sanitized['chart_config'] = wp_json_encode( $decoded );
				}
			}
		}

		if ( array_key_exists( 'builder_config', $widget_data ) ) {
			$builder_config_raw = trim( (string) $widget_data['builder_config'] );
			if ( '' === $builder_config_raw ) {
				$sanitized['builder_config'] = '';
			} else {
				$decoded = json_decode( $builder_config_raw, true );
				if ( JSON_ERROR_NONE === json_last_error() ) {
					$sanitized['builder_config'] = wp_json_encode( $decoded );
				}
			}
		}

		if ( array_key_exists( 'output_config', $widget_data ) ) {
			$sanitized['output_config'] = self::sanitize_output_config( (string) $widget_data['output_config'] );
		}

		if ( isset( $widget_data['status'] ) ) {
			$sanitized['status'] = self::sanitize_status( $widget_data['status'] );
		}

		return $sanitized;
	}

	/**
	 * Sanitize settings array.
	 *
	 * @param array $settings Settings array.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_settings( $settings ) {
		$sanitized = array();

		if ( isset( $settings['excluded_tables'] ) ) {
			// Sanitize each table name.
			$tables = explode( "\n", $settings['excluded_tables'] );
			$tables = array_map( 'trim', $tables );
			$tables = array_filter( $tables );
			$sanitized['excluded_tables'] = implode( "\n", $tables );
		}

		foreach ( array( 'hide_help_dropdown', 'hide_screen_options' ) as $key ) {
			if ( array_key_exists( $key, $settings ) ) {
				$sanitized[ $key ] = absint( $settings[ $key ] ) ? 1 : 0;
			}
		}

		if ( isset( $settings['hidden_dashboard_widgets'] ) ) {
			$widget_ids   = explode( "\n", $settings['hidden_dashboard_widgets'] );
			$widget_ids   = array_map( 'trim', $widget_ids );
			$widget_ids   = array_filter( $widget_ids );
			$valid_ids    = array( 'welcome-panel', 'dashboard_activity', 'dashboard_right_now', 'dashboard_quick_press', 'dashboard_site_health', 'dashboard_primary' );
			$widget_ids   = array_values( array_intersect( $widget_ids, $valid_ids ) );
			$sanitized['hidden_dashboard_widgets'] = implode( "\n", $widget_ids );
		}

		return $sanitized;
	}
}
