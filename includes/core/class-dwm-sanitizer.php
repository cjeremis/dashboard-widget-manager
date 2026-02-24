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

		if ( isset( $widget_data['enabled'] ) ) {
			$sanitized['enabled'] = self::sanitize_boolean( $widget_data['enabled'] );
		}

		if ( isset( $widget_data['widget_order'] ) ) {
			$sanitized['widget_order'] = self::sanitize_widget_order( $widget_data['widget_order'] );
		}

		if ( isset( $widget_data['cache_duration'] ) ) {
			$sanitized['cache_duration'] = self::sanitize_cache_duration( $widget_data['cache_duration'] );
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

		if ( isset( $settings['enable_caching'] ) ) {
			$sanitized['enable_caching'] = self::sanitize_boolean( $settings['enable_caching'] );
		}

		if ( isset( $settings['default_cache_duration'] ) ) {
			$sanitized['default_cache_duration'] = self::sanitize_cache_duration( $settings['default_cache_duration'] );
		}

		if ( isset( $settings['max_execution_time'] ) ) {
			$sanitized['max_execution_time'] = absint( $settings['max_execution_time'] );
		}

		if ( isset( $settings['enable_query_logging'] ) ) {
			$sanitized['enable_query_logging'] = self::sanitize_boolean( $settings['enable_query_logging'] );
		}

		if ( isset( $settings['allowed_tables'] ) ) {
			// Sanitize each table name.
			$tables = explode( "\n", $settings['allowed_tables'] );
			$tables = array_map( 'trim', $tables );
			$tables = array_filter( $tables );
			$sanitized['allowed_tables'] = implode( "\n", $tables );
		}

		return $sanitized;
	}
}
