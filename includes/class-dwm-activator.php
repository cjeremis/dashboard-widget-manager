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

	const TABLE_WIDGETS       = 'dwm_widgets';
	const TABLE_CACHE         = 'dwm_query_cache';
	const TABLE_SETTINGS      = 'dwm_settings';
	const TABLE_NOTIFICATIONS = 'dwm_notifications';

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

		$widgets_table        = $wpdb->prefix . self::TABLE_WIDGETS;
		$cache_table          = $wpdb->prefix . self::TABLE_CACHE;
		$settings_table       = $wpdb->prefix . self::TABLE_SETTINGS;
		$notifications_table  = $wpdb->prefix . self::TABLE_NOTIFICATIONS;

		$sql_widgets = "CREATE TABLE IF NOT EXISTS {$widgets_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) NOT NULL,
			name varchar(255) NOT NULL,
			description text DEFAULT '',
			sql_query text NOT NULL,
			template text DEFAULT '',
			styles text DEFAULT '',
			scripts text DEFAULT '',
			no_results_template text DEFAULT '',
			output_config longtext DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'draft',
			enabled tinyint(1) NOT NULL DEFAULT 0,
			widget_order int(11) NOT NULL DEFAULT 0,
			cache_duration int(11) NOT NULL DEFAULT 300,
			enable_caching tinyint(1) NOT NULL DEFAULT 1,
			auto_refresh tinyint(1) NOT NULL DEFAULT 0,
			max_execution_time int(11) NOT NULL DEFAULT 30,
			enable_query_logging tinyint(1) NOT NULL DEFAULT 0,
			is_demo tinyint(1) NOT NULL DEFAULT 0,
			first_published_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			created_by bigint(20) unsigned NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uuid (uuid),
			KEY status (status),
			KEY enabled (enabled),
			KEY widget_order (widget_order),
			KEY created_by (created_by),
			KEY is_demo (is_demo)
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

		$sql_notifications = "CREATE TABLE IF NOT EXISTS {$notifications_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			type varchar(100) NOT NULL,
			title varchar(255) NOT NULL,
			message text NOT NULL,
			icon varchar(100) NOT NULL DEFAULT 'info',
			actions longtext DEFAULT NULL,
			dismissed tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY type (type),
			KEY dismissed (dismissed)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql_widgets );
		dbDelta( $sql_cache );
		dbDelta( $sql_settings );
		dbDelta( $sql_notifications );
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
			'excluded_tables' => '',
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
	 * Run schema upgrades for existing installs.
	 *
	 * Called on admin_init to add any new columns introduced after initial activation.
	 */
	public static function maybe_upgrade() {
		global $wpdb;

		// Create the notifications table if it doesn't exist yet (added after initial activation).
		$notifications_table = $wpdb->prefix . self::TABLE_NOTIFICATIONS;
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $notifications_table ) ) !== $notifications_table ) {
			$charset_collate     = $wpdb->get_charset_collate();
			$sql_notifications   = "CREATE TABLE IF NOT EXISTS {$notifications_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				type varchar(100) NOT NULL,
				title varchar(255) NOT NULL,
				message text NOT NULL,
				icon varchar(100) NOT NULL DEFAULT 'info',
				actions longtext DEFAULT NULL,
				dismissed tinyint(1) NOT NULL DEFAULT 0,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY user_id (user_id),
				KEY type (type),
				KEY dismissed (dismissed)
			) {$charset_collate};";
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql_notifications );
		}

		$table   = $wpdb->prefix . self::TABLE_WIDGETS;
		$columns = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`", 0 );

		if ( ! in_array( 'enable_caching', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `enable_caching` tinyint(1) NOT NULL DEFAULT 1 AFTER `cache_duration`" );
		}

		if ( ! in_array( 'auto_refresh', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `auto_refresh` tinyint(1) NOT NULL DEFAULT 0 AFTER `enable_caching`" );
		}

		if ( ! in_array( 'max_execution_time', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `max_execution_time` int(11) NOT NULL DEFAULT 30 AFTER `enable_caching`" );
		}

		if ( ! in_array( 'enable_query_logging', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `enable_query_logging` tinyint(1) NOT NULL DEFAULT 0 AFTER `max_execution_time`" );
		}

		if ( ! in_array( 'is_demo', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `is_demo` tinyint(1) NOT NULL DEFAULT 0 AFTER `enable_query_logging`" );
		}

		if ( ! in_array( 'first_published_at', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `first_published_at` datetime DEFAULT NULL AFTER `is_demo`" );
			// Backfill: set first_published_at to created_at for already-enabled widgets.
			$wpdb->query( "UPDATE `{$table}` SET `first_published_at` = `created_at` WHERE `enabled` = 1 AND `first_published_at` IS NULL" );
		}

		if ( ! in_array( 'chart_type', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `chart_type` varchar(50) NOT NULL DEFAULT '' AFTER `scripts`" );
		}

		if ( ! in_array( 'chart_config', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `chart_config` longtext DEFAULT NULL AFTER `chart_type`" );
		}

		if ( ! in_array( 'builder_config', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `builder_config` longtext DEFAULT NULL AFTER `chart_config`" );
		}

		if ( ! in_array( 'no_results_template', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `no_results_template` text DEFAULT '' AFTER `scripts`" );
		}

		if ( ! in_array( 'output_config', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `output_config` longtext DEFAULT NULL AFTER `builder_config`" );
		}

		if ( ! in_array( 'status', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `status` varchar(20) NOT NULL DEFAULT 'draft' AFTER `builder_config`" );
			$wpdb->query( "UPDATE `{$table}` SET `status` = 'publish' WHERE `enabled` = 1" );
			$wpdb->query( "UPDATE `{$table}` SET `status` = 'draft' WHERE `enabled` = 0" );
			$wpdb->query( "ALTER TABLE `{$table}` ADD KEY `status` (`status`)" );
		}

		if ( ! in_array( 'uuid', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `uuid` char(36) DEFAULT NULL AFTER `id`" );
		}

		$missing_uuid_rows = $wpdb->get_results( "SELECT id FROM `{$table}` WHERE uuid IS NULL OR uuid = ''", ARRAY_A );
		if ( ! empty( $missing_uuid_rows ) ) {
			foreach ( $missing_uuid_rows as $row ) {
				$wpdb->update(
					$table,
					array( 'uuid' => wp_generate_uuid4() ),
					array( 'id' => (int) $row['id'] ),
					array( '%s' ),
					array( '%d' )
				);
			}
		}

		$wpdb->query( "ALTER TABLE `{$table}` MODIFY COLUMN `uuid` char(36) NOT NULL" );

		$uuid_index_exists = false;
		$indexes           = $wpdb->get_results( "SHOW INDEX FROM `{$table}`", ARRAY_A );
		foreach ( $indexes as $index ) {
			if ( isset( $index['Key_name'] ) && 'uuid' === $index['Key_name'] ) {
				$uuid_index_exists = true;
				break;
			}
		}
		if ( ! $uuid_index_exists ) {
			$wpdb->query( "ALTER TABLE `{$table}` ADD UNIQUE KEY `uuid` (`uuid`)" );
		}
	}

	/**
	 * Schedule cron job for cache cleanup.
	 */
	private static function schedule_cron() {
		if ( ! wp_next_scheduled( 'dwm_cleanup_cache' ) ) {
			wp_schedule_event( time(), 'daily', 'dwm_cleanup_cache' );
		}

		if ( ! wp_next_scheduled( 'dwm_cleanup_trash' ) ) {
			wp_schedule_event( time(), 'daily', 'dwm_cleanup_trash' );
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
			self::TABLE_NOTIFICATIONS,
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
