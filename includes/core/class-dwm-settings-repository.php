<?php
/**
 * Settings Repository
 *
 * Provides CRUD operations for the dwm_settings table.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings Repository class.
 */
class DWM_Settings_Repository {

	use DWM_Singleton;

	const GROUP_GENERAL = 'general';
	const SCOPE_SITE    = 'site';

	private ?array $cache = null;

	/**
	 * Get the full table name with prefix.
	 *
	 * @return string
	 */
	public static function get_table_name(): string {
		return DWM_Activator::get_table_name( DWM_Activator::TABLE_SETTINGS );
	}

	/**
	 * Check if the settings table exists.
	 *
	 * @return bool
	 */
	public function table_exists(): bool {
		return DWM_Activator::table_exists( DWM_Activator::TABLE_SETTINGS );
	}

	/**
	 * Get a setting by key.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value if not found.
	 * @param string $scope   Scope.
	 * @return mixed
	 */
	public function get( string $key, mixed $default = null, string $scope = self::SCOPE_SITE ): mixed {
		if ( ! $this->table_exists() ) {
			return $default;
		}

		global $wpdb;
		$table = self::get_table_name();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT value_json FROM {$table} WHERE setting_key = %s AND scope = %s LIMIT 1",
				$key,
				$scope
			),
			ARRAY_A
		);

		if ( empty( $row ) ) {
			return $default;
		}

		$value = json_decode( $row['value_json'], true );
		return ( json_last_error() === JSON_ERROR_NONE ) ? $value : $default;
	}

	/**
	 * Set/update a setting.
	 *
	 * @param string      $key      Setting key.
	 * @param mixed       $value    Value to store.
	 * @param string|null $group    Optional setting group.
	 * @param bool        $autoload Whether to autoload this setting.
	 * @param string      $scope    Scope.
	 * @return bool
	 */
	public function set(
		string $key,
		mixed $value,
		?string $group = null,
		bool $autoload = true,
		string $scope = self::SCOPE_SITE
	): bool {
		if ( ! $this->table_exists() ) {
			return false;
		}

		global $wpdb;
		$table = self::get_table_name();

		$value_json = wp_json_encode( $value );
		if ( $value_json === false ) {
			return false;
		}

		$updated_by = get_current_user_id() ?: null;

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE setting_key = %s AND scope = %s LIMIT 1",
				$key,
				$scope
			)
		);

		if ( $existing ) {
			$data = array(
				'value_json' => $value_json,
				'autoload'   => $autoload ? 1 : 0,
				'updated_by' => $updated_by,
				'updated_at' => current_time( 'mysql' ),
			);

			if ( $group !== null ) {
				$data['setting_group'] = $group;
			}

			$result = $wpdb->update(
				$table,
				$data,
				array(
					'setting_key' => $key,
					'scope'       => $scope,
				)
			);
		} else {
			$result = $wpdb->insert(
				$table,
				array(
					'setting_key'   => $key,
					'setting_group' => $group,
					'value_json'    => $value_json,
					'scope'         => $scope,
					'autoload'      => $autoload ? 1 : 0,
					'updated_by'    => $updated_by,
					'created_at'    => current_time( 'mysql' ),
					'updated_at'    => current_time( 'mysql' ),
				)
			);
		}

		$this->cache = null;

		return $result !== false;
	}

	/**
	 * Delete a setting.
	 *
	 * @param string $key   Setting key.
	 * @param string $scope Scope.
	 * @return bool
	 */
	public function delete( string $key, string $scope = self::SCOPE_SITE ): bool {
		if ( ! $this->table_exists() ) {
			return false;
		}

		global $wpdb;
		$table = self::get_table_name();

		$result = $wpdb->delete(
			$table,
			array(
				'setting_key' => $key,
				'scope'       => $scope,
			)
		);

		$this->cache = null;

		return $result !== false;
	}

	/**
	 * Check if a setting key exists.
	 *
	 * @param string $key   Setting key.
	 * @param string $scope Scope.
	 * @return bool
	 */
	public function exists( string $key, string $scope = self::SCOPE_SITE ): bool {
		if ( ! $this->table_exists() ) {
			return false;
		}

		global $wpdb;
		$table = self::get_table_name();

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$table} WHERE setting_key = %s AND scope = %s LIMIT 1",
				$key,
				$scope
			)
		);

		return ! empty( $result );
	}

	/**
	 * Get all settings.
	 *
	 * @param string $scope Scope.
	 * @return array
	 */
	public function get_all( string $scope = self::SCOPE_SITE ): array {
		if ( ! $this->table_exists() ) {
			return array();
		}

		global $wpdb;
		$table = self::get_table_name();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT setting_key, value_json FROM {$table} WHERE scope = %s",
				$scope
			),
			ARRAY_A
		);

		$settings = array();
		foreach ( $rows as $row ) {
			$value = json_decode( $row['value_json'], true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				$settings[ $row['setting_key'] ] = $value;
			}
		}

		return $settings;
	}

	/**
	 * Get settings by group.
	 *
	 * @param string $group Setting group.
	 * @param string $scope Scope.
	 * @return array
	 */
	public function get_by_group( string $group, string $scope = self::SCOPE_SITE ): array {
		if ( ! $this->table_exists() ) {
			return array();
		}

		global $wpdb;
		$table = self::get_table_name();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT setting_key, value_json FROM {$table} WHERE setting_group = %s AND scope = %s",
				$group,
				$scope
			),
			ARRAY_A
		);

		$settings = array();
		foreach ( $rows as $row ) {
			$value = json_decode( $row['value_json'], true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				$settings[ $row['setting_key'] ] = $value;
			}
		}

		return $settings;
	}

	/**
	 * Clear the cache.
	 */
	public function clear_cache(): void {
		$this->cache = null;
	}
}
