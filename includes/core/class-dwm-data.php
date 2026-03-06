<?php
/**
 * Data Class
 *
 * Handles all database operations for widgets and cache.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data class.
 *
 * Provides data access layer for all plugin data.
 */
class DWM_Data {

	use DWM_Singleton;

	/**
	 * Table name for widgets.
	 */
	const TABLE_WIDGETS = 'dwm_widgets';

	/**
	 * Table name for query cache.
	 */
	const TABLE_CACHE = 'dwm_query_cache';

	/**
	 * Default cache duration in seconds.
	 */
	const DEFAULT_CACHE_DURATION = 300;

	const VALID_STATUSES = array( 'publish', 'draft', 'archived', 'trash' );

	/**
	 * Get plugin settings.
	 *
	 * @return array Plugin settings.
	 */
	public function get_settings() {
		$role_keys = DWM_Access_Control::get_all_role_keys();
		$defaults = array(
			'excluded_tables'             => '',
			'hide_help_dropdown'          => 0,
			'hide_screen_options'         => 0,
			'hidden_dashboard_widgets'    => '',
			'hidden_third_party_dashboard_widgets' => '',
			'dashboard_branding_enabled'  => 0,
			'dashboard_logo_enabled'      => 0,
			'dashboard_background_enabled' => 0,
			'dashboard_background_type'   => 'default',
			'dashboard_bg_solid_color'    => '#ffffff',
			'dashboard_bg_gradient_type'  => 'linear',
			'dashboard_bg_gradient_angle' => 90,
			'dashboard_bg_gradient_start' => '#667eea',
			'dashboard_bg_gradient_start_position' => 0,
			'dashboard_bg_gradient_end'   => '#764ba2',
			'dashboard_bg_gradient_end_position' => 100,
			'dashboard_padding_enabled'   => 0,
			'dashboard_padding_linked'    => 1,
			'dashboard_padding_top_value' => 20,
			'dashboard_padding_top_unit'  => 'px',
			'dashboard_padding_right_value' => 20,
			'dashboard_padding_right_unit' => 'px',
			'dashboard_padding_bottom_value' => 20,
			'dashboard_padding_bottom_unit' => 'px',
			'dashboard_padding_left_value' => 20,
			'dashboard_padding_left_unit' => 'px',
			'dashboard_logo_url'          => '',
			'dashboard_logo_height'       => 56,
			'dashboard_logo_height_unit'  => 'px',
			'dashboard_logo_alignment'    => 'left',
			'dashboard_logo_link_enabled' => 0,
			'dashboard_logo_link_url'     => '',
			'dashboard_logo_link_new_tab' => 0,
			'dashboard_logo_bg_type'       => 'default',
			'dashboard_logo_bg_solid_color' => '#ffffff',
			'dashboard_logo_bg_gradient_type' => 'linear',
			'dashboard_logo_bg_gradient_angle' => 90,
			'dashboard_logo_bg_gradient_start' => '#667eea',
			'dashboard_logo_bg_gradient_start_position' => 0,
			'dashboard_logo_bg_gradient_end' => '#764ba2',
			'dashboard_logo_bg_gradient_end_position' => 100,
			'dashboard_logo_padding_top'   => 10,
			'dashboard_logo_padding_right' => 10,
			'dashboard_logo_padding_bottom' => 10,
			'dashboard_logo_padding_left'  => 10,
			'dashboard_logo_padding_unit'  => 'px',
			'dashboard_logo_padding_linked' => 1,
			'dashboard_logo_margin_top'    => 0,
			'dashboard_logo_margin_right'  => 0,
			'dashboard_logo_margin_bottom' => 0,
			'dashboard_logo_margin_left'   => 0,
			'dashboard_logo_margin_unit'   => 'px',
			'dashboard_logo_margin_linked' => 1,
			'dashboard_logo_border_top'    => 0,
			'dashboard_logo_border_right'  => 0,
			'dashboard_logo_border_bottom' => 0,
			'dashboard_logo_border_left'   => 0,
			'dashboard_logo_border_unit'   => 'px',
			'dashboard_logo_border_linked' => 1,
			'dashboard_logo_border_style'  => 'none',
			'dashboard_logo_border_color'  => '#dddddd',
			'dashboard_logo_border_radius_tl' => 0,
			'dashboard_logo_border_radius_tr' => 0,
			'dashboard_logo_border_radius_br' => 0,
			'dashboard_logo_border_radius_bl' => 0,
			'dashboard_logo_border_radius_unit' => 'px',
			'dashboard_logo_border_radius_linked' => 1,
			'dashboard_title_mode'        => 'default',
			'dashboard_title_text'        => '',
			'dashboard_title_font_family' => 'inherit',
			'dashboard_title_font_size'   => '32px',
			'dashboard_title_font_weight' => '700',
			'dashboard_title_alignment'   => 'left',
			'dashboard_title_color'       => '#1d2327',
			'dashboard_hero_enabled'      => 0,
			'dashboard_hero_theme'        => 'text-left',
			'dashboard_hero_title'        => __( 'Welcome to your custom dashboard', 'dashboard-widget-manager' ),
			'dashboard_hero_title_font_family' => 'inherit',
			'dashboard_hero_title_font_size'   => '28px',
			'dashboard_hero_title_font_weight' => '700',
			'dashboard_hero_title_alignment'   => 'left',
			'dashboard_hero_title_color'       => '#ffffff',
			'dashboard_hero_message'      => '',
			'dashboard_hero_background_type' => 'solid',
			'dashboard_hero_bg_solid_color'  => '#667eea',
			'dashboard_hero_bg_gradient_type' => 'linear',
			'dashboard_hero_bg_gradient_angle' => 90,
			'dashboard_hero_bg_gradient_start' => '#667eea',
			'dashboard_hero_bg_gradient_start_position' => 0,
			'dashboard_hero_bg_gradient_end' => '#764ba2',
			'dashboard_hero_bg_gradient_end_position' => 100,
			'dashboard_hero_padding_top'     => 16,
			'dashboard_hero_padding_right'   => 20,
			'dashboard_hero_padding_bottom'  => 16,
			'dashboard_hero_padding_left'    => 20,
			'dashboard_hero_padding_unit'    => 'px',
			'dashboard_hero_padding_linked'  => 1,
			'dashboard_hero_margin_top'      => 10,
			'dashboard_hero_margin_right'    => 0,
			'dashboard_hero_margin_bottom'   => 16,
			'dashboard_hero_margin_left'     => 0,
			'dashboard_hero_margin_unit'     => 'px',
			'dashboard_hero_margin_linked'   => 0,
			'dashboard_hero_border_top'      => 0,
			'dashboard_hero_border_right'    => 0,
			'dashboard_hero_border_bottom'   => 0,
			'dashboard_hero_border_left'     => 0,
			'dashboard_hero_border_unit'     => 'px',
			'dashboard_hero_border_linked'   => 1,
			'dashboard_hero_border_style'    => 'none',
			'dashboard_hero_border_color'    => '#dddddd',
			'dashboard_hero_border_radius_tl' => 10,
			'dashboard_hero_border_radius_tr' => 10,
			'dashboard_hero_border_radius_br' => 10,
			'dashboard_hero_border_radius_bl' => 10,
			'dashboard_hero_border_radius_unit' => 'px',
			'dashboard_hero_border_radius_linked' => 1,
			'dashboard_hero_height'          => 0,
			'dashboard_hero_height_unit'     => 'px',
			'dashboard_hero_min_height'      => 0,
			'dashboard_hero_min_height_unit' => 'px',
			'dashboard_notice_enabled'    => 0,
			'dashboard_notice_type'       => 'toast',
			'dashboard_notice_level'      => 'info',
			'dashboard_notice_title'      => '',
			'dashboard_notice_message'    => '',
			'dashboard_notice_dismissible' => 1,
			'dashboard_notice_auto_dismiss' => 6,
			'dashboard_notice_position'   => 'bottom-right',
			'dashboard_notice_frequency'  => 'always',
			'access_allowed_roles'        => implode( "\n", $role_keys ),
			'restricted_user_ids'         => '',
			'support_data_sharing_opt_in' => 0,
		);

		$repository = DWM_Settings_Repository::get_instance();
		if ( ! $repository->table_exists() ) {
			return $defaults;
		}

		$settings = $repository->get( 'settings', $defaults );
		unset( $settings['allowed_tables'] );

		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Get excluded tables as an array.
	 *
	 * Parses the excluded_tables setting from newline-separated string to array.
	 * Returns empty array when no tables are excluded (all tables accessible).
	 *
	 * @return array Array of excluded table names. Empty = none excluded.
	 */
	public function get_excluded_tables() {
		$settings = $this->get_settings();
		$raw      = isset( $settings['excluded_tables'] ) ? $settings['excluded_tables'] : '';

		if ( empty( $raw ) ) {
			return array();
		}

		$tables = explode( "\n", $raw );
		$tables = array_map( 'trim', $tables );
		return array_values( array_filter( $tables ) );
	}

	/**
	 * Update plugin settings.
	 *
	 * @param array $settings Settings array.
	 * @return bool True on success, false on failure.
	 */
	public function update_settings( $settings ) {
		$repository = DWM_Settings_Repository::get_instance();
		if ( ! $repository->table_exists() ) {
			return false;
		}

		// Merge incoming (possibly partial) settings with existing stored values
		// so that autosave of a single toggle doesn't wipe other settings.
		$existing = $repository->get( 'settings', array() );
		$merged   = array_merge( $existing, $settings );

		return $repository->set( 'settings', $merged, DWM_Settings_Repository::GROUP_GENERAL );
	}

	/**
	 * Get plugin version from settings table.
	 *
	 * @return string|null Plugin version or null if not set.
	 */
	public function get_version() {
		$repository = DWM_Settings_Repository::get_instance();
		if ( ! $repository->table_exists() ) {
			return null;
		}

		return $repository->get( 'version' );
	}

	/**
	 * Get plugin activation timestamp.
	 *
	 * @return string|null Activation timestamp or null if not set.
	 */
	public function get_activated_at() {
		$repository = DWM_Settings_Repository::get_instance();
		if ( ! $repository->table_exists() ) {
			return null;
		}

		return $repository->get( 'activated_at' );
	}

	/**
	 * Get all widgets.
	 *
	 * @param bool $enabled_only Get only enabled widgets.
	 * @return array Array of widget objects.
	 */
	public function get_widgets( $enabled_only = false ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_WIDGETS;

		$sql = "SELECT * FROM {$table}";

		if ( $enabled_only ) {
			$sql .= " WHERE enabled = 1 AND status = 'publish'";
		}

		$sql .= " ORDER BY created_at DESC, id DESC";

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return $results ? $results : array();
	}

	/**
	 * Get widget by ID.
	 *
	 * @param int $id Widget ID.
	 * @return array|null Widget data or null if not found.
	 */
	public function get_widget( $id ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_WIDGETS;

		$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id );

		$result = $wpdb->get_row( $sql, ARRAY_A );

		return $result;
	}

	/**
	 * Get widget by name.
	 *
	 * @param string $name Widget name.
	 * @return array|null Widget data or null if not found.
	 */
	public function get_widget_by_name( $name ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_WIDGETS;

		$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE name = %s", $name );

		$result = $wpdb->get_row( $sql, ARRAY_A );

		return $result;
	}

	/**
	 * Get widget by UUID.
	 *
	 * @param string $uuid Widget UUID.
	 * @return array|null Widget data or null if not found.
	 */
	public function get_widget_by_uuid( string $uuid ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_WIDGETS;
		$sql   = $wpdb->prepare( "SELECT * FROM {$table} WHERE uuid = %s", $uuid );

		return $wpdb->get_row( $sql, ARRAY_A );
	}

	/**
	 * Get widgets by name.
	 *
	 * @param string $name Widget name.
	 * @return array<int, array<string, mixed>> Widgets with matching name.
	 */
	public function get_widgets_by_name( string $name ): array {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_WIDGETS;
		$sql   = $wpdb->prepare( "SELECT * FROM {$table} WHERE name = %s ORDER BY id ASC", $name );
		$rows  = $wpdb->get_results( $sql, ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Normalize widget UUID to a valid RFC-4122 v4 string or empty string.
	 *
	 * @param mixed $uuid Raw UUID value.
	 * @return string
	 */
	private function normalize_widget_uuid( $uuid ): string {
		if ( ! is_string( $uuid ) ) {
			return '';
		}

		$uuid = trim( strtolower( $uuid ) );
		if ( '' === $uuid ) {
			return '';
		}

		if ( function_exists( 'wp_is_uuid' ) ) {
			if ( wp_is_uuid( $uuid, 4 ) || wp_is_uuid( $uuid ) ) {
				return $uuid;
			}
		}

		if ( preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid ) ) {
			return $uuid;
		}

		return '';
	}

	/**
	 * Create a new widget.
	 *
	 * @param array $widget_data Widget data array.
	 * @return int|false Widget ID on success, false on failure.
	 */
	public function create_widget( $widget_data ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_WIDGETS;

		// Derive status and enabled.
		if ( isset( $widget_data['status'] ) ) {
			$status  = $widget_data['status'];
			$enabled = ( 'publish' === $status ) ? 1 : 0;
		} elseif ( ! empty( $widget_data['enabled'] ) ) {
			$status  = 'publish';
			$enabled = 1;
		} else {
			$status  = 'draft';
			$enabled = 0;
		}

		$uuid = $this->normalize_widget_uuid( $widget_data['uuid'] ?? '' );
		if ( '' === $uuid ) {
			$uuid = wp_generate_uuid4();
		}

		$data = array(
			'uuid'                 => $uuid,
			'name'                 => isset( $widget_data['name'] ) ? $widget_data['name'] : '',
			'description'          => isset( $widget_data['description'] ) ? $widget_data['description'] : '',
			'sql_query'            => isset( $widget_data['sql_query'] ) ? $widget_data['sql_query'] : '',
			'template'             => isset( $widget_data['template'] ) ? $widget_data['template'] : '',
			'styles'               => isset( $widget_data['styles'] ) ? $widget_data['styles'] : '',
			'scripts'              => isset( $widget_data['scripts'] ) ? $widget_data['scripts'] : '',
			'no_results_template'  => isset( $widget_data['no_results_template'] ) ? $widget_data['no_results_template'] : '',
			'chart_type'           => isset( $widget_data['chart_type'] ) ? sanitize_text_field( $widget_data['chart_type'] ) : '',
			'chart_config'         => isset( $widget_data['chart_config'] ) ? $widget_data['chart_config'] : null,
			'builder_config'       => isset( $widget_data['builder_config'] ) ? $widget_data['builder_config'] : null,
			'output_config'        => isset( $widget_data['output_config'] ) ? $widget_data['output_config'] : null,
			'status'               => $status,
			'enabled'              => $enabled,
			'widget_order'         => isset( $widget_data['widget_order'] ) ? (int) $widget_data['widget_order'] : 0,
			'cache_duration'       => isset( $widget_data['cache_duration'] ) ? (int) $widget_data['cache_duration'] : 300,
			'enable_caching'       => isset( $widget_data['enable_caching'] ) ? (int) $widget_data['enable_caching'] : 1,
			'auto_refresh'         => isset( $widget_data['auto_refresh'] ) ? (int) $widget_data['auto_refresh'] : 0,
			'max_execution_time'   => isset( $widget_data['max_execution_time'] ) ? (int) $widget_data['max_execution_time'] : 30,
			'enable_query_logging' => isset( $widget_data['enable_query_logging'] ) ? (int) $widget_data['enable_query_logging'] : 0,
			'is_demo'              => isset( $widget_data['is_demo'] ) ? (int) $widget_data['is_demo'] : 0,
			'created_by'           => get_current_user_id(),
		);

		if ( ! empty( $widget_data['first_published_at'] ) ) {
			$data['first_published_at'] = sanitize_text_field( $widget_data['first_published_at'] );
		} elseif ( 'publish' === $status ) {
			$data['first_published_at'] = current_time( 'mysql' );
		}

		$format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d' );
		if ( array_key_exists( 'first_published_at', $data ) ) {
			$format[] = '%s';
		}

		$result = $wpdb->insert( $table, $data, $format );

		if ( $result ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Update an existing widget.
	 *
	 * @param int   $id Widget ID.
	 * @param array $widget_data Widget data array.
	 * @return bool True on success, false on failure.
	 */
	public function update_widget( $id, $widget_data ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_WIDGETS;

		// Prepare data for update.
		$data = array();

		if ( isset( $widget_data['name'] ) ) {
			$data['name'] = $widget_data['name'];
		}

		if ( isset( $widget_data['uuid'] ) ) {
			$uuid = $this->normalize_widget_uuid( $widget_data['uuid'] );
			if ( '' !== $uuid ) {
				$data['uuid'] = $uuid;
			}
		}

		if ( isset( $widget_data['description'] ) ) {
			$data['description'] = $widget_data['description'];
		}

		if ( isset( $widget_data['sql_query'] ) ) {
			$data['sql_query'] = $widget_data['sql_query'];
		}

		if ( isset( $widget_data['template'] ) ) {
			$data['template'] = $widget_data['template'];
		}

		if ( isset( $widget_data['styles'] ) ) {
			$data['styles'] = $widget_data['styles'];
		}

		if ( isset( $widget_data['scripts'] ) ) {
			$data['scripts'] = $widget_data['scripts'];
		}

		if ( isset( $widget_data['no_results_template'] ) ) {
			$data['no_results_template'] = $widget_data['no_results_template'];
		}

		if ( isset( $widget_data['status'] ) ) {
			$data['status']  = $widget_data['status'];
			$data['enabled'] = ( 'publish' === $widget_data['status'] ) ? 1 : 0;
		}

		if ( isset( $widget_data['enabled'] ) && ! isset( $widget_data['status'] ) ) {
			$data['enabled'] = (int) $widget_data['enabled'];
		}

		if ( isset( $widget_data['widget_order'] ) ) {
			$data['widget_order'] = (int) $widget_data['widget_order'];
		}

		if ( isset( $widget_data['cache_duration'] ) ) {
			$data['cache_duration'] = (int) $widget_data['cache_duration'];
		}

		if ( isset( $widget_data['enable_caching'] ) ) {
			$data['enable_caching'] = (int) $widget_data['enable_caching'];
		}

		if ( isset( $widget_data['auto_refresh'] ) ) {
			$data['auto_refresh'] = (int) $widget_data['auto_refresh'];
		}

		if ( isset( $widget_data['max_execution_time'] ) ) {
			$data['max_execution_time'] = (int) $widget_data['max_execution_time'];
		}

		if ( isset( $widget_data['enable_query_logging'] ) ) {
			$data['enable_query_logging'] = (int) $widget_data['enable_query_logging'];
		}

		if ( isset( $widget_data['first_published_at'] ) ) {
			$data['first_published_at'] = $widget_data['first_published_at'];
		}

		if ( array_key_exists( 'chart_type', $widget_data ) ) {
			$data['chart_type'] = sanitize_text_field( $widget_data['chart_type'] );
		}

		if ( array_key_exists( 'chart_config', $widget_data ) ) {
			$data['chart_config'] = $widget_data['chart_config'];
		}

		if ( array_key_exists( 'builder_config', $widget_data ) ) {
			$data['builder_config'] = $widget_data['builder_config'];
		}

		if ( array_key_exists( 'output_config', $widget_data ) ) {
			$data['output_config'] = $widget_data['output_config'];
		}

		if ( empty( $data ) ) {
			return false;
		}

		$integer_fields = array(
			'enabled',
			'widget_order',
			'cache_duration',
			'auto_refresh',
			'max_execution_time',
			'enable_query_logging',
		);
		$formats = array();
		foreach ( $data as $field_key => $field_value ) {
			$formats[] = in_array( $field_key, $integer_fields, true ) ? '%d' : '%s';
		}

		$result = $wpdb->update(
			$table,
			$data,
			array( 'id' => $id ),
			$formats,
			array( '%d' )
		);

		// Clear widget cache after update.
		if ( $result ) {
			$this->clear_widget_cache( $id );
		}

		return $result !== false;
	}

	/**
	 * Delete a widget.
	 *
	 * @param int $id Widget ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_widget( $id ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_WIDGETS;

		// Clear widget cache before deletion.
		$this->clear_widget_cache( $id );

		$result = $wpdb->delete(
			$table,
			array( 'id' => $id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Update widget status.
	 *
	 * @param int    $id              Widget ID.
	 * @param string $status          New status (publish, draft, archived, trash).
	 * @param bool   $clear_demo_flag Whether to clear is_demo during status update.
	 * @return bool True on success, false on failure.
	 */
	public function update_widget_status( $id, $status, $clear_demo_flag = false ) {
		if ( ! in_array( $status, self::VALID_STATUSES, true ) ) {
			return false;
		}

		$update_data = array(
			'status'  => $status,
			'enabled' => ( 'publish' === $status ) ? 1 : 0,
		);

		if ( 'publish' === $status ) {
			$widget = $this->get_widget( $id );
			if ( $widget && empty( $widget['first_published_at'] ) ) {
				$update_data['first_published_at'] = current_time( 'mysql' );
			}
		}

		if ( $clear_demo_flag ) {
			$update_data['is_demo'] = 0;
		}

		return $this->update_widget( $id, $update_data );
	}

	public function set_widget_enabled( $id, $enabled ) {
		return $this->update_widget( $id, array( 'enabled' => $enabled ? 1 : 0 ) );
	}

	public function trash_widget( $id ) {
		return $this->update_widget_status( $id, 'trash' );
	}

	public function cleanup_trash() {
		global $wpdb;

		$table  = $wpdb->prefix . self::TABLE_WIDGETS;
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) );

		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE status = 'trash' AND updated_at < %s",
			$cutoff
		) );

		foreach ( $ids as $id ) {
			$this->clear_widget_cache( (int) $id );
		}

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$table} WHERE status = 'trash' AND updated_at < %s",
			$cutoff
		) );
	}

	/**
	 * Get cached query result.
	 *
	 * @param string $cache_key Cache key.
	 * @return mixed|null Cached result or null if not found or expired.
	 */
	public function get_cached_query_result( $cache_key ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_CACHE;

		$sql = $wpdb->prepare(
			"SELECT query_result FROM {$table} WHERE cache_key = %s AND expires_at > NOW()",
			$cache_key
		);

		$result = $wpdb->get_var( $sql );

		if ( $result ) {
			return maybe_unserialize( $result );
		}

		return null;
	}

	/**
	 * Set cached query result.
	 *
	 * @param string $cache_key Cache key.
	 * @param int    $widget_id Widget ID.
	 * @param mixed  $result Query result to cache.
	 * @param int    $duration Cache duration in seconds.
	 * @return bool True on success, false on failure.
	 */
	public function set_cached_query_result( $cache_key, $widget_id, $result, $duration ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_CACHE;

		// Delete existing cache entry.
		$wpdb->delete(
			$table,
			array( 'cache_key' => $cache_key ),
			array( '%s' )
		);

		// Calculate expiration time.
		$expires_at = gmdate( 'Y-m-d H:i:s', time() + $duration );

		// Insert new cache entry.
		$insert_result = $wpdb->insert(
			$table,
			array(
				'cache_key'    => $cache_key,
				'widget_id'    => $widget_id,
				'query_result' => maybe_serialize( $result ),
				'expires_at'   => $expires_at,
			),
			array( '%s', '%d', '%s', '%s' )
		);

		return $insert_result !== false;
	}

	/**
	 * Clear cache for specific widget.
	 *
	 * @param int $widget_id Widget ID.
	 * @return bool True on success, false on failure.
	 */
	public function clear_widget_cache( $widget_id ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_CACHE;

		$result = $wpdb->delete(
			$table,
			array( 'widget_id' => $widget_id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Cleanup expired cache entries.
	 *
	 * Called by cron job daily.
	 */
	public function cleanup_expired_cache() {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_CACHE;

		$wpdb->query( "DELETE FROM {$table} WHERE expires_at < NOW()" );
	}

	/**
	 * Clear all Dashboard Widget Manager caches.
	 *
	 * This purges query cache rows and plugin transients, and resets
	 * in-request static caches used by plugin repositories.
	 *
	 * @return bool True on success, false if any DB cache purge fails.
	 */
	public function clear_all_caches() {
		global $wpdb;

		$success = true;
		$table   = $wpdb->prefix . self::TABLE_CACHE;

		$truncate_result = $wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( false === $truncate_result ) {
			$delete_result = $wpdb->query( "DELETE FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( false === $delete_result ) {
				$success = false;
			}
		}

		// Legacy/fallback transient key.
		delete_transient( 'dwm_query_cache' );

		$this->delete_dwm_transients();
		$this->delete_dwm_site_transients();

		if ( class_exists( 'DWM_Features' ) ) {
			DWM_Features::clear_cache();
		}

		if ( class_exists( 'DWM_Settings_Repository' ) ) {
			DWM_Settings_Repository::get_instance()->clear_cache();
		}

		/**
		 * Allow extensions/add-ons to clear their own DWM-related caches.
		 */
		do_action( 'dwm_clear_caches' );

		return $success;
	}

	/**
	 * Delete all transient rows whose key starts with `dwm_`.
	 *
	 * @return void
	 */
	private function delete_dwm_transients() {
		global $wpdb;

		$transient_like = $wpdb->esc_like( '_transient_dwm_' ) . '%';
		$timeout_like   = $wpdb->esc_like( '_transient_timeout_dwm_' ) . '%';

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$transient_like,
				$timeout_like
			)
		);
	}

	/**
	 * Delete all multisite transient rows whose key starts with `dwm_`.
	 *
	 * @return void
	 */
	private function delete_dwm_site_transients() {
		global $wpdb;

		if ( ! is_multisite() || empty( $wpdb->sitemeta ) ) {
			return;
		}

		$site_transient_like = $wpdb->esc_like( '_site_transient_dwm_' ) . '%';
		$site_timeout_like   = $wpdb->esc_like( '_site_transient_timeout_dwm_' ) . '%';

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
				$site_transient_like,
				$site_timeout_like
			)
		);
	}

	/**
	 * Export all plugin data.
	 *
	 * @return array Export data array.
	 */
	public function export_all() {
		$widgets       = $this->get_widgets();
		$settings      = $this->get_settings();
		$notifications = DWM_Notifications::get_instance()->get_user_notifications();

		return array(
			'plugin'        => 'dashboard-widget-manager',
			'version'       => DWM_VERSION,
			'exported_at'   => current_time( 'mysql' ),
			'site_url'      => get_site_url(),
			'widgets'       => $widgets,
			'settings'      => $settings,
			'notifications' => $notifications,
		);
	}

	/**
	 * Import all plugin data.
	 *
	 * @param array $data  Import data array.
	 * @param bool  $merge Whether to merge with existing data (false = replace).
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function import_all( array $data, bool $merge = false ) {
		global $wpdb;

		$in_transaction = false !== $wpdb->query( 'START TRANSACTION' );

		// Import settings.
		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			if ( $merge ) {
				$current  = $this->get_settings();
				$settings = wp_parse_args( $data['settings'], $current );
			} else {
				$settings = $data['settings'];
			}
			$settings = DWM_Sanitizer::sanitize_settings( $settings );
			if ( ! $this->update_settings( $settings ) ) {
				if ( $in_transaction ) {
					$wpdb->query( 'ROLLBACK' );
				}
				return new WP_Error( 'import_settings_failed', __( 'Failed to import settings.', 'dashboard-widget-manager' ) );
			}
		}

		// Import widgets.
		if ( isset( $data['widgets'] ) && is_array( $data['widgets'] ) ) {
			if ( ! $merge ) {
				// Replace: clear existing widgets first.
				$existing = $this->get_widgets();
				foreach ( $existing as $widget ) {
					if ( ! $this->delete_widget( (int) $widget['id'] ) ) {
						if ( $in_transaction ) {
							$wpdb->query( 'ROLLBACK' );
						}
						return new WP_Error( 'import_delete_failed', __( 'Failed to clear existing widgets before import.', 'dashboard-widget-manager' ) );
					}
				}
			}

			foreach ( $data['widgets'] as $widget ) {
				if ( ! is_array( $widget ) ) {
					if ( $in_transaction ) {
						$wpdb->query( 'ROLLBACK' );
					}
					return new WP_Error( 'import_widget_invalid', __( 'Invalid widget record found in import file.', 'dashboard-widget-manager' ) );
				}

				$sanitized_widget = array();
				if ( isset( $widget['name'] ) ) {
					$sanitized_widget['name'] = DWM_Sanitizer::sanitize_widget_name( $widget['name'] );
				}
				if ( isset( $widget['description'] ) ) {
					$sanitized_widget['description'] = DWM_Sanitizer::sanitize_widget_description( $widget['description'] );
				}
				if ( isset( $widget['sql_query'] ) ) {
					$sanitized_widget['sql_query'] = DWM_Sanitizer::sanitize_sql_query( $widget['sql_query'] );
				}
				if ( isset( $widget['template'] ) ) {
					$sanitized_widget['template'] = (string) $widget['template'];
				}
				if ( isset( $widget['styles'] ) ) {
					$sanitized_widget['styles'] = DWM_Sanitizer::sanitize_styles( $widget['styles'] );
				}
				if ( isset( $widget['scripts'] ) ) {
					$scripts                    = str_replace( array( '<?php', '<?', '?>' ), '', (string) $widget['scripts'] );
					$sanitized_widget['scripts'] = trim( $scripts );
				}
				if ( isset( $widget['no_results_template'] ) ) {
					$sanitized_widget['no_results_template'] = (string) $widget['no_results_template'];
				}
				if ( isset( $widget['status'] ) ) {
					$sanitized_widget['status'] = DWM_Sanitizer::sanitize_status( $widget['status'] );
				}
				foreach ( array( 'enabled', 'widget_order', 'cache_duration', 'enable_caching', 'auto_refresh', 'max_execution_time', 'enable_query_logging', 'is_demo' ) as $int_key ) {
					if ( isset( $widget[ $int_key ] ) ) {
						$sanitized_widget[ $int_key ] = (int) $widget[ $int_key ];
					}
				}
				if ( array_key_exists( 'chart_type', $widget ) ) {
					$sanitized_widget['chart_type'] = sanitize_text_field( (string) $widget['chart_type'] );
				}
				if ( array_key_exists( 'chart_config', $widget ) ) {
					$sanitized_widget['chart_config'] = $widget['chart_config'];
				}
				if ( array_key_exists( 'builder_config', $widget ) ) {
					$sanitized_widget['builder_config'] = $widget['builder_config'];
				}
				if ( array_key_exists( 'output_config', $widget ) ) {
					$sanitized_widget['output_config'] = DWM_Sanitizer::sanitize_output_config( (string) $widget['output_config'] );
				}
				if ( isset( $widget['first_published_at'] ) ) {
					$sanitized_widget['first_published_at'] = sanitize_text_field( (string) $widget['first_published_at'] );
				}
				$import_uuid = $this->normalize_widget_uuid( $widget['uuid'] ?? '' );
				if ( '' !== $import_uuid ) {
					$sanitized_widget['uuid'] = $import_uuid;
				}

				$validation_errors = DWM_Validator::validate_widget_data( $sanitized_widget );
				if ( ! empty( $validation_errors ) ) {
					if ( $in_transaction ) {
						$wpdb->query( 'ROLLBACK' );
					}
					return new WP_Error( 'import_widget_validation_failed', implode( ' ', $validation_errors ) );
				}

				// Strip ID for re-insert.
				unset( $sanitized_widget['id'], $sanitized_widget['created_at'], $sanitized_widget['updated_at'] );

				if ( $merge ) {
					if ( '' !== $import_uuid ) {
						$existing_widget = $this->get_widget_by_uuid( $import_uuid );
						if ( $existing_widget ) {
							if ( ! $this->update_widget( (int) $existing_widget['id'], $sanitized_widget ) ) {
								if ( $in_transaction ) {
									$wpdb->query( 'ROLLBACK' );
								}
								return new WP_Error( 'import_widget_update_failed', __( 'Failed to update an existing widget during import.', 'dashboard-widget-manager' ) );
							}
							continue;
						}
					} else {
						$name_matches = $this->get_widgets_by_name( $sanitized_widget['name'] ?? '' );
						if ( count( $name_matches ) > 1 ) {
							if ( $in_transaction ) {
								$wpdb->query( 'ROLLBACK' );
							}
							return new WP_Error( 'import_widget_match_ambiguous', __( 'Import conflict: multiple existing widgets share the same name and no UUID was provided.', 'dashboard-widget-manager' ) );
						}
						if ( 1 === count( $name_matches ) ) {
							if ( ! $this->update_widget( (int) $name_matches[0]['id'], $sanitized_widget ) ) {
								if ( $in_transaction ) {
									$wpdb->query( 'ROLLBACK' );
								}
								return new WP_Error( 'import_widget_update_failed', __( 'Failed to update an existing widget during import.', 'dashboard-widget-manager' ) );
							}
							continue;
						}
					}
				}

				$result = $this->create_widget( $sanitized_widget );
				if ( false === $result ) {
					if ( $in_transaction ) {
						$wpdb->query( 'ROLLBACK' );
					}
					return new WP_Error( 'import_widget_create_failed', __( 'Failed to create a widget during import.', 'dashboard-widget-manager' ) );
				}
			}
		}

		// Import notifications.
		if ( isset( $data['notifications'] ) && is_array( $data['notifications'] ) ) {
			$notifications_obj = DWM_Notifications::get_instance();

			if ( ! $merge ) {
				// Replace mode: wipe the current user's notifications first.
				$notifications_obj->delete_all_notifications();
			}

			foreach ( $data['notifications'] as $notif ) {
				if ( ! is_array( $notif ) || empty( $notif['type'] ) ) {
					continue;
				}

				$actions = [];
				if ( ! empty( $notif['actions'] ) && is_array( $notif['actions'] ) ) {
					foreach ( $notif['actions'] as $action ) {
						if ( ! empty( $action['label'] ) && ! empty( $action['url'] ) ) {
							$actions[] = [
								'label' => sanitize_text_field( $action['label'] ),
								'url'   => sanitize_text_field( $action['url'] ),
							];
						}
					}
				}

				$notifications_obj->add_notification(
					sanitize_text_field( $notif['type'] ),
					sanitize_text_field( $notif['title'] ?? '' ),
					sanitize_text_field( $notif['message'] ?? '' ),
					sanitize_text_field( $notif['icon'] ?? 'info' ),
					$actions
				);
			}
		}

		if ( $in_transaction ) {
			$wpdb->query( 'COMMIT' );
		}

		return true;
	}

	/**
	 * Get all demo widgets.
	 *
	 * @return array Array of demo widget rows.
	 */
	public function get_demo_widgets() {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_WIDGETS;
		$sql   = "SELECT * FROM {$table} WHERE is_demo = 1 ORDER BY widget_order ASC, name ASC";

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return $results ? $results : array();
	}

	/**
	 * Check if any demo widgets exist.
	 *
	 * @return bool True if demo widgets exist.
	 */
	public function has_demo_widgets() {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_WIDGETS;
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_demo = 1" );

		return ( (int) $count ) > 0;
	}

	/**
	 * Delete all demo widgets.
	 *
	 * @return int Number of widgets deleted.
	 */
	public function delete_demo_widgets() {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_WIDGETS;

		// Get IDs first to clear caches.
		$ids = $wpdb->get_col( "SELECT id FROM {$table} WHERE is_demo = 1" );
		foreach ( $ids as $id ) {
			$this->clear_widget_cache( (int) $id );
		}

		$result = $wpdb->delete( $table, array( 'is_demo' => 1 ), array( '%d' ) );

		return $result !== false ? (int) $result : 0;
	}

	/**
	 * Truncate all widgets and query cache, resetting AUTO_INCREMENT to 1.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function truncate_widgets() {
		global $wpdb;

		$widgets_table = $wpdb->prefix . self::TABLE_WIDGETS;
		$cache_table   = $wpdb->prefix . self::TABLE_CACHE;

		$result_widgets = $wpdb->query( "TRUNCATE TABLE {$widgets_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result_cache   = $wpdb->query( "TRUNCATE TABLE {$cache_table}" );   // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $result_widgets !== false && $result_cache !== false;
	}

	/**
	 * Get widget statistics.
	 *
	 * @return array Array of statistics.
	 */
	public function get_widget_statistics() {
		global $wpdb;

		$widgets_table = $wpdb->prefix . self::TABLE_WIDGETS;
		$cache_table   = $wpdb->prefix . self::TABLE_CACHE;

		$total_widgets   = $wpdb->get_var( "SELECT COUNT(*) FROM {$widgets_table}" );
		$enabled_widgets = $wpdb->get_var( "SELECT COUNT(*) FROM {$widgets_table} WHERE enabled = 1" );
		$cache_entries   = $wpdb->get_var( "SELECT COUNT(*) FROM {$cache_table}" );

		return array(
			'total_widgets'   => (int) $total_widgets,
			'enabled_widgets' => (int) $enabled_widgets,
			'cache_entries'   => (int) $cache_entries,
		);
	}
}
