<?php
/**
 * Activator Class
 *
 * Handles plugin activation.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activator class.
 */
class DWM_Activator {

	const TABLE_WIDGETS  = 'dwm_widgets';
	const TABLE_CACHE    = 'dwm_query_cache';
	const TABLE_SETTINGS = 'dwm_settings';

	/**
	 * Activate the plugin.
	 */
	public static function activate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		self::create_tables();
		self::set_default_options();
		self::schedule_cron();

		flush_rewrite_rules();
	}

	/**
	 * Create database tables.
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$widgets_table  = $wpdb->prefix . self::TABLE_WIDGETS;
		$cache_table    = $wpdb->prefix . self::TABLE_CACHE;
		$settings_table = $wpdb->prefix . self::TABLE_SETTINGS;

		$sql_widgets = "CREATE TABLE IF NOT EXISTS {$widgets_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			description text DEFAULT '',
			sql_query text NOT NULL,
			template text DEFAULT '',
			styles text DEFAULT '',
			scripts text DEFAULT '',
			enabled tinyint(1) NOT NULL DEFAULT 0,
			widget_order int(11) NOT NULL DEFAULT 0,
			cache_duration int(11) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			created_by bigint(20) unsigned NOT NULL,
			PRIMARY KEY (id),
			KEY enabled (enabled),
			KEY widget_order (widget_order),
			KEY created_by (created_by)
		) {$charset_collate};";

		$sql_cache = "CREATE TABLE IF NOT EXISTS {$cache_table} (
			cache_key varchar(64) NOT NULL,
			widget_id bigint(20) unsigned NOT NULL,
			query_result longtext NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			expires_at datetime NOT NULL,
			PRIMARY KEY (cache_key),
			KEY widget_id (widget_id),
			KEY expires_at (expires_at)
		) {$charset_collate};";

		$sql_settings = "CREATE TABLE IF NOT EXISTS {$settings_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			setting_key varchar(191) NOT NULL,
			setting_group varchar(100) DEFAULT NULL,
			value_json longtext NOT NULL,
			scope varchar(50) NOT NULL DEFAULT 'site',
			autoload tinyint(1) NOT NULL DEFAULT 1,
			updated_by bigint(20) unsigned DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY setting_key_scope (setting_key, scope),
			KEY setting_group (setting_group),
			KEY autoload (autoload)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql_widgets );
		dbDelta( $sql_cache );
		dbDelta( $sql_settings );
	}

	/**
	 * Set default options.
	 */
	private static function set_default_options() {
		$repository = DWM_Settings_Repository::get_instance();

		if ( ! $repository->table_exists() ) {
			return;
		}

		$default_settings = array(
			'enable_caching'         => true,
			'default_cache_duration' => 300,
			'max_execution_time'     => 30,
			'enable_query_logging'   => false,
			'allowed_tables'         => '',
		);

		if ( ! $repository->exists( 'settings' ) ) {
			$repository->set( 'settings', $default_settings, 'general' );
		}

		if ( ! $repository->exists( 'version' ) ) {
			$repository->set( 'version', DWM_VERSION, 'general' );
		}

		if ( ! $repository->exists( 'activated_at' ) ) {
			$repository->set( 'activated_at', current_time( 'mysql' ), 'general' );
		}
	}

	/**
	 * Schedule cron job for cache cleanup.
	 */
	private static function schedule_cron() {
		if ( ! wp_next_scheduled( 'dwm_cleanup_cache' ) ) {
			wp_schedule_event( time(), 'daily', 'dwm_cleanup_cache' );
		}
	}

	/**
	 * Get table name with prefix.
	 *
	 * @param string $table Table constant.
	 * @return string
	 */
	public static function get_table_name( string $table ): string {
		global $wpdb;
		return $wpdb->prefix . $table;
	}

	/**
	 * Check if a table exists.
	 *
	 * @param string $table Table constant.
	 * @return bool
	 */
	public static function table_exists( string $table ): bool {
		global $wpdb;
		$table_name = self::get_table_name( $table );
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
	}

	/**
	 * Drop all tables.
	 */
	public static function drop_all_tables() {
		global $wpdb;

		$tables = array(
			self::TABLE_SETTINGS,
			self::TABLE_CACHE,
			self::TABLE_WIDGETS,
		);

		foreach ( $tables as $table ) {
			$table_name = self::get_table_name( $table );
			$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
		}
	}
}
