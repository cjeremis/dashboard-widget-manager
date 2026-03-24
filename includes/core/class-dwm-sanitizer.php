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

		foreach ( array( 'hide_help_dropdown', 'hide_screen_options', 'hide_inline_notices', 'dashboard_branding_enabled', 'dashboard_logo_enabled', 'dashboard_logo_link_enabled', 'dashboard_logo_link_new_tab', 'dashboard_background_enabled', 'dashboard_padding_enabled', 'dashboard_padding_linked', 'dashboard_hero_enabled', 'dashboard_notice_enabled', 'dashboard_notice_dismissible' ) as $key ) {
			if ( array_key_exists( $key, $settings ) ) {
				$sanitized[ $key ] = absint( $settings[ $key ] ) ? 1 : 0;
			}
		}

		if ( isset( $settings['dashboard_background_type'] ) ) {
			$type = sanitize_key( (string) $settings['dashboard_background_type'] );
			$sanitized['dashboard_background_type'] = in_array( $type, array( 'solid', 'gradient' ), true ) ? $type : 'solid';
		}

		if ( isset( $settings['dashboard_bg_solid_color'] ) ) {
			$sanitized['dashboard_bg_solid_color'] = sanitize_hex_color( $settings['dashboard_bg_solid_color'] ) ?: '#ffffff';
		}

		if ( isset( $settings['dashboard_bg_gradient_type'] ) ) {
			$type = sanitize_key( (string) $settings['dashboard_bg_gradient_type'] );
			$sanitized['dashboard_bg_gradient_type'] = in_array( $type, array( 'linear', 'radial' ), true ) ? $type : 'linear';
		}

		if ( isset( $settings['dashboard_bg_gradient_angle'] ) ) {
			$angle = (int) $settings['dashboard_bg_gradient_angle'];
			$angle = max( 0, min( 360, $angle ) );
			$sanitized['dashboard_bg_gradient_angle'] = $angle;
		}

		if ( isset( $settings['dashboard_bg_gradient_start'] ) ) {
			$sanitized['dashboard_bg_gradient_start'] = sanitize_hex_color( $settings['dashboard_bg_gradient_start'] ) ?: '#667eea';
		}

		if ( isset( $settings['dashboard_bg_gradient_end'] ) ) {
			$sanitized['dashboard_bg_gradient_end'] = sanitize_hex_color( $settings['dashboard_bg_gradient_end'] ) ?: '#764ba2';
		}

		if ( isset( $settings['dashboard_bg_gradient_start_position'] ) ) {
			$pos = absint( $settings['dashboard_bg_gradient_start_position'] );
			$sanitized['dashboard_bg_gradient_start_position'] = max( 0, min( 100, $pos ) );
		}

		if ( isset( $settings['dashboard_bg_gradient_end_position'] ) ) {
			$pos = absint( $settings['dashboard_bg_gradient_end_position'] );
			$sanitized['dashboard_bg_gradient_end_position'] = max( 0, min( 100, $pos ) );
		}

		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			$value_key = 'dashboard_padding_' . $side . '_value';
			$unit_key  = 'dashboard_padding_' . $side . '_unit';

			if ( isset( $settings[ $value_key ] ) ) {
				$value = (float) $settings[ $value_key ];
				$value = max( 0, min( 300, $value ) );
				$sanitized[ $value_key ] = $value;
			}

			if ( isset( $settings[ $unit_key ] ) ) {
				$unit = sanitize_key( (string) $settings[ $unit_key ] );
				$sanitized[ $unit_key ] = in_array( $unit, array( 'px', '%', 'rem', 'em', 'vh', 'vw' ), true ) ? $unit : 'px';
			}
		}

		if ( isset( $settings['dashboard_logo_url'] ) ) {
			$sanitized['dashboard_logo_url'] = esc_url_raw( (string) $settings['dashboard_logo_url'] );
		}

		if ( isset( $settings['dashboard_logo_height'] ) ) {
			$height = absint( $settings['dashboard_logo_height'] );
			$sanitized['dashboard_logo_height'] = max( 1, min( 500, $height ) );
		}

		if ( isset( $settings['dashboard_logo_height_unit'] ) ) {
			$unit = sanitize_key( (string) $settings['dashboard_logo_height_unit'] );
			$sanitized['dashboard_logo_height_unit'] = in_array( $unit, array( 'px', '%', 'rem', 'em', 'vh' ), true ) ? $unit : 'px';
		}

		if ( isset( $settings['dashboard_logo_alignment'] ) ) {
			$align = sanitize_key( (string) $settings['dashboard_logo_alignment'] );
			$sanitized['dashboard_logo_alignment'] = in_array( $align, array( 'left', 'center', 'right' ), true ) ? $align : 'left';
		}

		if ( isset( $settings['dashboard_logo_link_url'] ) ) {
			$sanitized['dashboard_logo_link_url'] = esc_url_raw( (string) $settings['dashboard_logo_link_url'] );
		}

		if ( isset( $settings['dashboard_logo_bg_type'] ) ) {
			$type = sanitize_key( (string) $settings['dashboard_logo_bg_type'] );
			$sanitized['dashboard_logo_bg_type'] = in_array( $type, array( 'default', 'solid', 'gradient' ), true ) ? $type : 'default';
		}

		if ( isset( $settings['dashboard_logo_bg_solid_color'] ) ) {
			$sanitized['dashboard_logo_bg_solid_color'] = sanitize_hex_color( $settings['dashboard_logo_bg_solid_color'] ) ?: '#ffffff';
		}

		if ( isset( $settings['dashboard_logo_bg_gradient_type'] ) ) {
			$type = sanitize_key( (string) $settings['dashboard_logo_bg_gradient_type'] );
			$sanitized['dashboard_logo_bg_gradient_type'] = in_array( $type, array( 'linear', 'radial' ), true ) ? $type : 'linear';
		}

		if ( isset( $settings['dashboard_logo_bg_gradient_angle'] ) ) {
			$sanitized['dashboard_logo_bg_gradient_angle'] = max( 0, min( 360, (int) $settings['dashboard_logo_bg_gradient_angle'] ) );
		}

		if ( isset( $settings['dashboard_logo_bg_gradient_start'] ) ) {
			$sanitized['dashboard_logo_bg_gradient_start'] = sanitize_hex_color( $settings['dashboard_logo_bg_gradient_start'] ) ?: '#667eea';
		}

		if ( isset( $settings['dashboard_logo_bg_gradient_start_position'] ) ) {
			$sanitized['dashboard_logo_bg_gradient_start_position'] = max( 0, min( 100, absint( $settings['dashboard_logo_bg_gradient_start_position'] ) ) );
		}

		if ( isset( $settings['dashboard_logo_bg_gradient_end'] ) ) {
			$sanitized['dashboard_logo_bg_gradient_end'] = sanitize_hex_color( $settings['dashboard_logo_bg_gradient_end'] ) ?: '#764ba2';
		}

		if ( isset( $settings['dashboard_logo_bg_gradient_end_position'] ) ) {
			$sanitized['dashboard_logo_bg_gradient_end_position'] = max( 0, min( 100, absint( $settings['dashboard_logo_bg_gradient_end_position'] ) ) );
		}

		$logo_spacing_units = array( 'px', '%', 'rem', 'em' );
		foreach ( array( 'padding', 'margin' ) as $prop ) {
			foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
				$key = 'dashboard_logo_' . $prop . '_' . $side;
				if ( isset( $settings[ $key ] ) ) {
					$val = (int) $settings[ $key ];
					if ( 'margin' === $prop ) {
						$val = max( -200, min( 200, $val ) );
					} else {
						$val = max( 0, min( 200, $val ) );
					}
					$sanitized[ $key ] = $val;
				}
			}
			$unit_key = 'dashboard_logo_' . $prop . '_unit';
			if ( isset( $settings[ $unit_key ] ) ) {
				$unit = sanitize_key( (string) $settings[ $unit_key ] );
				$sanitized[ $unit_key ] = in_array( $unit, $logo_spacing_units, true ) ? $unit : 'px';
			}
			$linked_key = 'dashboard_logo_' . $prop . '_linked';
			if ( array_key_exists( $linked_key, $settings ) ) {
				$sanitized[ $linked_key ] = absint( $settings[ $linked_key ] ) ? 1 : 0;
			}
		}

		$border_units = array( 'px', 'rem', 'em' );
		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			$key = 'dashboard_logo_border_' . $side;
			if ( isset( $settings[ $key ] ) ) {
				$sanitized[ $key ] = max( 0, min( 20, absint( $settings[ $key ] ) ) );
			}
		}
		if ( isset( $settings['dashboard_logo_border_unit'] ) ) {
			$unit = sanitize_key( (string) $settings['dashboard_logo_border_unit'] );
			$sanitized['dashboard_logo_border_unit'] = in_array( $unit, $border_units, true ) ? $unit : 'px';
		}
		if ( array_key_exists( 'dashboard_logo_border_linked', $settings ) ) {
			$sanitized['dashboard_logo_border_linked'] = absint( $settings['dashboard_logo_border_linked'] ) ? 1 : 0;
		}

		if ( isset( $settings['dashboard_logo_border_style'] ) ) {
			$style = sanitize_key( (string) $settings['dashboard_logo_border_style'] );
			$sanitized['dashboard_logo_border_style'] = in_array( $style, array( 'none', 'solid', 'dashed', 'dotted', 'double' ), true ) ? $style : 'none';
		}

		if ( isset( $settings['dashboard_logo_border_color'] ) ) {
			$sanitized['dashboard_logo_border_color'] = sanitize_hex_color( $settings['dashboard_logo_border_color'] ) ?: '#dddddd';
		}

		$radius_units = array( 'px', '%', 'rem', 'em' );
		foreach ( array( 'tl', 'tr', 'br', 'bl' ) as $corner ) {
			$key = 'dashboard_logo_border_radius_' . $corner;
			if ( isset( $settings[ $key ] ) ) {
				$sanitized[ $key ] = max( 0, min( 200, absint( $settings[ $key ] ) ) );
			}
		}
		if ( isset( $settings['dashboard_logo_border_radius_unit'] ) ) {
			$unit = sanitize_key( (string) $settings['dashboard_logo_border_radius_unit'] );
			$sanitized['dashboard_logo_border_radius_unit'] = in_array( $unit, $radius_units, true ) ? $unit : 'px';
		}
		if ( array_key_exists( 'dashboard_logo_border_radius_linked', $settings ) ) {
			$sanitized['dashboard_logo_border_radius_linked'] = absint( $settings['dashboard_logo_border_radius_linked'] ) ? 1 : 0;
		}

		if ( isset( $settings['dashboard_title_mode'] ) ) {
			$mode = sanitize_key( (string) $settings['dashboard_title_mode'] );
			$sanitized['dashboard_title_mode'] = in_array( $mode, array( 'default', 'hide', 'custom' ), true ) ? $mode : 'default';
		}

		if ( isset( $settings['dashboard_title_text'] ) ) {
			$sanitized['dashboard_title_text'] = sanitize_text_field( (string) $settings['dashboard_title_text'] );
		}

		if ( isset( $settings['dashboard_title_font_family'] ) ) {
			$sanitized['dashboard_title_font_family'] = sanitize_text_field( (string) $settings['dashboard_title_font_family'] );
		}

		if ( isset( $settings['dashboard_title_font_size'] ) ) {
			$size = sanitize_text_field( (string) $settings['dashboard_title_font_size'] );
			if ( preg_match( '/^\d+(?:\.\d+)?(px|rem|em)$/', $size ) ) {
				$sanitized['dashboard_title_font_size'] = $size;
			} else {
				$sanitized['dashboard_title_font_size'] = '32px';
			}
		}

		if ( isset( $settings['dashboard_title_font_weight'] ) ) {
			$weight = sanitize_text_field( (string) $settings['dashboard_title_font_weight'] );
			$sanitized['dashboard_title_font_weight'] = in_array( $weight, array( '300', '400', '500', '600', '700' ), true ) ? $weight : '700';
		}

		if ( isset( $settings['dashboard_title_alignment'] ) ) {
			$align = sanitize_key( (string) $settings['dashboard_title_alignment'] );
			$sanitized['dashboard_title_alignment'] = in_array( $align, array( 'left', 'center', 'right' ), true ) ? $align : 'left';
		}

		if ( isset( $settings['dashboard_title_color'] ) ) {
			$sanitized['dashboard_title_color'] = sanitize_text_field( (string) $settings['dashboard_title_color'] );
		}

		if ( isset( $settings['dashboard_hero_title'] ) ) {
			$sanitized['dashboard_hero_title'] = sanitize_text_field( (string) $settings['dashboard_hero_title'] );
		}

		if ( isset( $settings['dashboard_hero_theme'] ) ) {
			$theme = sanitize_key( (string) $settings['dashboard_hero_theme'] );
			if ( 'classic' === $theme ) {
				$theme = 'text-left';
			}
			$sanitized['dashboard_hero_theme'] = in_array( $theme, array( 'text-left', 'text-center', 'text-right', 'text-split', 'logo-left', 'logo-top', 'logo-right', 'split' ), true ) ? $theme : 'text-left';
		}

		if ( isset( $settings['dashboard_hero_title_font_family'] ) ) {
			$sanitized['dashboard_hero_title_font_family'] = sanitize_text_field( (string) $settings['dashboard_hero_title_font_family'] );
		}

		if ( isset( $settings['dashboard_hero_title_font_size'] ) ) {
			$size = sanitize_text_field( (string) $settings['dashboard_hero_title_font_size'] );
			if ( preg_match( '/^\d+(?:\.\d+)?(px|rem|em)$/', $size ) ) {
				$sanitized['dashboard_hero_title_font_size'] = $size;
			} else {
				$sanitized['dashboard_hero_title_font_size'] = '28px';
			}
		}

		if ( isset( $settings['dashboard_hero_title_font_weight'] ) ) {
			$weight = sanitize_text_field( (string) $settings['dashboard_hero_title_font_weight'] );
			$sanitized['dashboard_hero_title_font_weight'] = in_array( $weight, array( '300', '400', '500', '600', '700' ), true ) ? $weight : '700';
		}

		if ( isset( $settings['dashboard_hero_title_alignment'] ) ) {
			$align = sanitize_key( (string) $settings['dashboard_hero_title_alignment'] );
			$sanitized['dashboard_hero_title_alignment'] = in_array( $align, array( 'left', 'center', 'right' ), true ) ? $align : 'left';
		}

		if ( isset( $settings['dashboard_hero_title_color'] ) ) {
			$sanitized['dashboard_hero_title_color'] = sanitize_text_field( (string) $settings['dashboard_hero_title_color'] );
		}

		if ( isset( $settings['dashboard_hero_message'] ) ) {
			$sanitized['dashboard_hero_message'] = wp_kses_post( (string) $settings['dashboard_hero_message'] );
		}

		if ( isset( $settings['dashboard_hero_message_font_family'] ) ) {
			$sanitized['dashboard_hero_message_font_family'] = sanitize_text_field( (string) $settings['dashboard_hero_message_font_family'] );
		}

		if ( isset( $settings['dashboard_hero_message_font_size'] ) ) {
			$size = sanitize_text_field( (string) $settings['dashboard_hero_message_font_size'] );
			if ( preg_match( '/^\d+(?:\.\d+)?(px|rem|em)$/', $size ) ) {
				$sanitized['dashboard_hero_message_font_size'] = $size;
			} else {
				$sanitized['dashboard_hero_message_font_size'] = '24px';
			}
		}

		if ( isset( $settings['dashboard_hero_message_font_weight'] ) ) {
			$weight = sanitize_text_field( (string) $settings['dashboard_hero_message_font_weight'] );
			$sanitized['dashboard_hero_message_font_weight'] = in_array( $weight, array( '300', '400', '500', '600', '700' ), true ) ? $weight : '700';
		}

		if ( isset( $settings['dashboard_hero_message_alignment'] ) ) {
			$align = sanitize_key( (string) $settings['dashboard_hero_message_alignment'] );
			$sanitized['dashboard_hero_message_alignment'] = in_array( $align, array( 'left', 'center', 'right' ), true ) ? $align : 'left';
		}

		if ( isset( $settings['dashboard_hero_message_color'] ) ) {
			$sanitized['dashboard_hero_message_color'] = sanitize_text_field( (string) $settings['dashboard_hero_message_color'] );
		}

		if ( isset( $settings['dashboard_hero_background_type'] ) ) {
			$type = sanitize_key( (string) $settings['dashboard_hero_background_type'] );
			$sanitized['dashboard_hero_background_type'] = in_array( $type, array( 'solid', 'gradient' ), true ) ? $type : 'solid';
		}

		if ( isset( $settings['dashboard_hero_bg_solid_color'] ) ) {
			$sanitized['dashboard_hero_bg_solid_color'] = sanitize_hex_color( $settings['dashboard_hero_bg_solid_color'] ) ?: '#667eea';
		}

		if ( isset( $settings['dashboard_hero_bg_gradient_type'] ) ) {
			$type = sanitize_key( (string) $settings['dashboard_hero_bg_gradient_type'] );
			$sanitized['dashboard_hero_bg_gradient_type'] = in_array( $type, array( 'linear', 'radial' ), true ) ? $type : 'linear';
		}

		if ( isset( $settings['dashboard_hero_bg_gradient_angle'] ) ) {
			$angle = (int) $settings['dashboard_hero_bg_gradient_angle'];
			$angle = max( 0, min( 360, $angle ) );
			$sanitized['dashboard_hero_bg_gradient_angle'] = $angle;
		}

		if ( isset( $settings['dashboard_hero_bg_gradient_start'] ) ) {
			$sanitized['dashboard_hero_bg_gradient_start'] = sanitize_hex_color( $settings['dashboard_hero_bg_gradient_start'] ) ?: '#667eea';
		}

		if ( isset( $settings['dashboard_hero_bg_gradient_end'] ) ) {
			$sanitized['dashboard_hero_bg_gradient_end'] = sanitize_hex_color( $settings['dashboard_hero_bg_gradient_end'] ) ?: '#764ba2';
		}

		if ( isset( $settings['dashboard_hero_bg_gradient_start_position'] ) ) {
			$pos = absint( $settings['dashboard_hero_bg_gradient_start_position'] );
			$sanitized['dashboard_hero_bg_gradient_start_position'] = max( 0, min( 100, $pos ) );
		}

		if ( isset( $settings['dashboard_hero_bg_gradient_end_position'] ) ) {
			$pos = absint( $settings['dashboard_hero_bg_gradient_end_position'] );
			$sanitized['dashboard_hero_bg_gradient_end_position'] = max( 0, min( 100, $pos ) );
		}

		// Hero spacing (padding / margin).
		$hero_spacing_units = array( 'px', '%', 'rem', 'em' );
		foreach ( array( 'padding', 'margin' ) as $prop ) {
			foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
				$key = 'dashboard_hero_' . $prop . '_' . $side;
				if ( isset( $settings[ $key ] ) ) {
					$val = (int) $settings[ $key ];
					if ( 'margin' === $prop ) {
						$val = max( -200, min( 200, $val ) );
					} else {
						$val = max( 0, min( 200, $val ) );
					}
					$sanitized[ $key ] = $val;
				}
			}
			$unit_key = 'dashboard_hero_' . $prop . '_unit';
			if ( isset( $settings[ $unit_key ] ) ) {
				$unit = sanitize_key( (string) $settings[ $unit_key ] );
				$sanitized[ $unit_key ] = in_array( $unit, $hero_spacing_units, true ) ? $unit : 'px';
			}
			$linked_key = 'dashboard_hero_' . $prop . '_linked';
			if ( array_key_exists( $linked_key, $settings ) ) {
				$sanitized[ $linked_key ] = absint( $settings[ $linked_key ] ) ? 1 : 0;
			}
		}

		// Hero border.
		$hero_border_units = array( 'px', 'rem', 'em' );
		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			$key = 'dashboard_hero_border_' . $side;
			if ( isset( $settings[ $key ] ) ) {
				$sanitized[ $key ] = max( 0, min( 20, absint( $settings[ $key ] ) ) );
			}
		}
		if ( isset( $settings['dashboard_hero_border_unit'] ) ) {
			$unit = sanitize_key( (string) $settings['dashboard_hero_border_unit'] );
			$sanitized['dashboard_hero_border_unit'] = in_array( $unit, $hero_border_units, true ) ? $unit : 'px';
		}
		if ( array_key_exists( 'dashboard_hero_border_linked', $settings ) ) {
			$sanitized['dashboard_hero_border_linked'] = absint( $settings['dashboard_hero_border_linked'] ) ? 1 : 0;
		}
		if ( isset( $settings['dashboard_hero_border_style'] ) ) {
			$style = sanitize_key( (string) $settings['dashboard_hero_border_style'] );
			$sanitized['dashboard_hero_border_style'] = in_array( $style, array( 'none', 'solid', 'dashed', 'dotted', 'double' ), true ) ? $style : 'none';
		}
		if ( isset( $settings['dashboard_hero_border_color'] ) ) {
			$sanitized['dashboard_hero_border_color'] = sanitize_hex_color( $settings['dashboard_hero_border_color'] ) ?: '#dddddd';
		}

		// Hero border radius.
		$hero_radius_units = array( 'px', '%', 'rem', 'em' );
		foreach ( array( 'tl', 'tr', 'br', 'bl' ) as $corner ) {
			$key = 'dashboard_hero_border_radius_' . $corner;
			if ( isset( $settings[ $key ] ) ) {
				$sanitized[ $key ] = max( 0, min( 200, absint( $settings[ $key ] ) ) );
			}
		}
		if ( isset( $settings['dashboard_hero_border_radius_unit'] ) ) {
			$unit = sanitize_key( (string) $settings['dashboard_hero_border_radius_unit'] );
			$sanitized['dashboard_hero_border_radius_unit'] = in_array( $unit, $hero_radius_units, true ) ? $unit : 'px';
		}
		if ( array_key_exists( 'dashboard_hero_border_radius_linked', $settings ) ) {
			$sanitized['dashboard_hero_border_radius_linked'] = absint( $settings['dashboard_hero_border_radius_linked'] ) ? 1 : 0;
		}

		// Hero height / min-height.
		foreach ( array( 'dashboard_hero_height', 'dashboard_hero_min_height' ) as $dim_key ) {
			if ( isset( $settings[ $dim_key ] ) ) {
				$sanitized[ $dim_key ] = max( 0, min( 1000, absint( $settings[ $dim_key ] ) ) );
			}
			$dim_unit_key = $dim_key . '_unit';
			if ( isset( $settings[ $dim_unit_key ] ) ) {
				$unit = sanitize_key( (string) $settings[ $dim_unit_key ] );
				$sanitized[ $dim_unit_key ] = in_array( $unit, array( 'px', '%', 'rem', 'em', 'vh' ), true ) ? $unit : 'px';
			}
		}

		if ( array_key_exists( 'dashboard_hero_logo_mode', $settings ) ) {
			$allowed = array( 'disabled', 'logo_only', 'hero_only', 'hero_logo' );
			$sanitized['dashboard_hero_logo_mode'] = in_array( $settings['dashboard_hero_logo_mode'], $allowed, true )
				? $settings['dashboard_hero_logo_mode']
				: 'disabled';
		}

		if ( isset( $settings['dashboard_notice_type'] ) ) {
			$type = sanitize_key( (string) $settings['dashboard_notice_type'] );
			$sanitized['dashboard_notice_type'] = in_array( $type, array( 'toast', 'popup', 'alert' ), true ) ? $type : 'toast';
		}

		if ( isset( $settings['dashboard_notice_level'] ) ) {
			$level = sanitize_key( (string) $settings['dashboard_notice_level'] );
			$sanitized['dashboard_notice_level'] = in_array( $level, array( 'info', 'success', 'warning', 'error' ), true ) ? $level : 'info';
		}

		if ( isset( $settings['dashboard_notice_title'] ) ) {
			$sanitized['dashboard_notice_title'] = sanitize_text_field( (string) $settings['dashboard_notice_title'] );
		}

		if ( isset( $settings['dashboard_notice_message'] ) ) {
			$sanitized['dashboard_notice_message'] = sanitize_textarea_field( (string) $settings['dashboard_notice_message'] );
		}

		if ( array_key_exists( 'dashboard_notice_dismissible', $settings ) ) {
			$sanitized['dashboard_notice_dismissible'] = absint( $settings['dashboard_notice_dismissible'] ) ? 1 : 0;
		}

		if ( isset( $settings['dashboard_notice_auto_dismiss'] ) ) {
			$sanitized['dashboard_notice_auto_dismiss'] = max( 0, min( 60, (int) $settings['dashboard_notice_auto_dismiss'] ) );
		}

		if ( isset( $settings['dashboard_notice_position'] ) ) {
			$pos = sanitize_key( (string) $settings['dashboard_notice_position'] );
			$sanitized['dashboard_notice_position'] = in_array( $pos, array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' ), true ) ? $pos : 'bottom-right';
		}

		if ( isset( $settings['dashboard_notice_frequency'] ) ) {
			$freq = sanitize_key( (string) $settings['dashboard_notice_frequency'] );
			$sanitized['dashboard_notice_frequency'] = in_array( $freq, array( 'always', 'once-session', 'once-day' ), true ) ? $freq : 'always';
		}

		if ( isset( $settings['hidden_dashboard_widgets'] ) ) {
			$widget_ids   = explode( "\n", $settings['hidden_dashboard_widgets'] );
			$widget_ids   = array_map( 'trim', $widget_ids );
			$widget_ids   = array_filter( $widget_ids );
			$valid_ids    = array( 'welcome-panel', 'dashboard_activity', 'dashboard_right_now', 'dashboard_quick_press', 'dashboard_site_health', 'dashboard_primary' );
			$widget_ids   = array_values( array_intersect( $widget_ids, $valid_ids ) );
			$sanitized['hidden_dashboard_widgets'] = implode( "\n", $widget_ids );
		}

		if ( isset( $settings['hidden_third_party_dashboard_widgets'] ) ) {
			$widget_ids = explode( "\n", $settings['hidden_third_party_dashboard_widgets'] );
			$widget_ids = array_map( 'trim', $widget_ids );
			$widget_ids = array_filter( $widget_ids );
			$widget_ids = array_map( 'sanitize_key', $widget_ids );
			$widget_ids = array_values( array_unique( array_filter( $widget_ids ) ) );
			$sanitized['hidden_third_party_dashboard_widgets'] = implode( "\n", $widget_ids );
		}

		if ( isset( $settings['access_allowed_roles'] ) ) {
			$roles      = explode( "\n", (string) $settings['access_allowed_roles'] );
			$roles      = array_map( 'trim', $roles );
			$roles      = array_map( 'sanitize_key', $roles );
			$roles      = array_values( array_unique( array_filter( $roles ) ) );
			$valid_keys = DWM_Access_Control::get_all_role_keys();
			$roles      = array_values( array_intersect( $roles, $valid_keys ) );
			$sanitized['access_allowed_roles'] = implode( "\n", $roles );
		}

		if ( isset( $settings['restricted_user_ids'] ) ) {
			$user_ids = explode( "\n", (string) $settings['restricted_user_ids'] );
			$user_ids = array_map( 'trim', $user_ids );
			$user_ids = array_map( 'absint', $user_ids );
			$user_ids = array_values( array_unique( array_filter( $user_ids ) ) );
			$sanitized['restricted_user_ids'] = implode( "\n", $user_ids );
		}

		return $sanitized;
	}
}
