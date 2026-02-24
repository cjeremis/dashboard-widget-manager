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

	/**
	 * Get plugin settings.
	 *
	 * @return array Plugin settings.
	 */
	public function get_settings() {
		$defaults = array(
			'enable_caching'         => true,
			'default_cache_duration' => self::DEFAULT_CACHE_DURATION,
			'max_execution_time'     => 30,
			'enable_query_logging'   => false,
			'allowed_tables'         => '',
		);

		$repository = DWM_Settings_Repository::get_instance();
		if ( ! $repository->table_exists() ) {
			return $defaults;
		}

		$settings = $repository->get( 'settings', $defaults );

		return wp_parse_args( $settings, $defaults );
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

		return $repository->set( 'settings', $settings, DWM_Settings_Repository::GROUP_GENERAL );
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
			$sql .= " WHERE enabled = 1";
		}

		$sql .= " ORDER BY widget_order ASC, name ASC";

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
	 * Create a new widget.
	 *
	 * @param array $widget_data Widget data array.
	 * @return int|false Widget ID on success, false on failure.
	 */
	public function create_widget( $widget_data ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_WIDGETS;

		// Prepare data for insertion.
		$data = array(
			'name'           => isset( $widget_data['name'] ) ? $widget_data['name'] : '',
			'description'    => isset( $widget_data['description'] ) ? $widget_data['description'] : '',
			'sql_query'      => isset( $widget_data['sql_query'] ) ? $widget_data['sql_query'] : '',
			'template'       => isset( $widget_data['template'] ) ? $widget_data['template'] : '',
			'styles'         => isset( $widget_data['styles'] ) ? $widget_data['styles'] : '',
			'scripts'        => isset( $widget_data['scripts'] ) ? $widget_data['scripts'] : '',
			'enabled'        => isset( $widget_data['enabled'] ) ? (int) $widget_data['enabled'] : 0,
			'widget_order'   => isset( $widget_data['widget_order'] ) ? (int) $widget_data['widget_order'] : 0,
			'cache_duration' => isset( $widget_data['cache_duration'] ) ? (int) $widget_data['cache_duration'] : 0,
			'created_by'     => get_current_user_id(),
		);

		$format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' );

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

		if ( isset( $widget_data['enabled'] ) ) {
			$data['enabled'] = (int) $widget_data['enabled'];
		}

		if ( isset( $widget_data['widget_order'] ) ) {
			$data['widget_order'] = (int) $widget_data['widget_order'];
		}

		if ( isset( $widget_data['cache_duration'] ) ) {
			$data['cache_duration'] = (int) $widget_data['cache_duration'];
		}

		if ( empty( $data ) ) {
			return false;
		}

		$result = $wpdb->update(
			$table,
			$data,
			array( 'id' => $id ),
			null,
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
	 * Toggle widget status (enable/disable).
	 *
	 * @param int  $id Widget ID.
	 * @param bool $enabled Enabled status.
	 * @return bool True on success, false on failure.
	 */
	public function toggle_widget_status( $id, $enabled ) {
		return $this->update_widget( $id, array( 'enabled' => $enabled ) );
	}

	/**
	 * Reorder widgets.
	 *
	 * @param array $order_map Array of widget ID => order pairs.
	 * @return bool True on success, false on failure.
	 */
	public function reorder_widgets( $order_map ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_WIDGETS;

		$success = true;

		foreach ( $order_map as $widget_id => $order ) {
			$result = $wpdb->update(
				$table,
				array( 'widget_order' => (int) $order ),
				array( 'id' => (int) $widget_id ),
				array( '%d' ),
				array( '%d' )
			);

			if ( $result === false ) {
				$success = false;
			}
		}

		return $success;
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
