<?php
/**
 * Notifications Manager
 *
 * Handles internal notification triggers and notification lifecycle events.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DWM_Notifications_Manager {

	use DWM_Singleton;

	/**
	 * Notification types that cannot be dismissed by the user
	 */
	const UNDELETABLE_NOTIFICATIONS = [];

	/**
	 * Check if a notification type is deletable
	 *
	 * @param string $type Notification type
	 *
	 * @return bool True if deletable, false if undeletable
	 */
	public static function is_notification_deletable( string $type ): bool {
		return ! in_array( $type, self::UNDELETABLE_NOTIFICATIONS, true );
	}

	/**
	 * Add activation notification
	 *
	 * Called when the plugin is activated to notify the user.
	 *
	 * @return void
	 */
	public function add_activation_notification(): void {
		$notifications_db = DWM_Notifications::get_instance();
		$user_id          = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		global $wpdb;
		$table = DWM_Notifications::get_table_name();

		// Remove any existing activation notification first
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete(
			$table,
			[
				'user_id' => $user_id,
				'type'    => 'plugin_activated',
			],
			[ '%d', '%s' ]
		);

		$notifications_db->add_notification(
			'plugin_activated',
			__( 'Welcome to Dashboard Widget Manager', 'dashboard-widget-manager' ),
			__( 'Create custom dashboard widgets powered by SQL queries. Head to the Widget Manager to get started.', 'dashboard-widget-manager' ),
			'star-filled',
			[
				[
					'label' => __( 'Create Widget', 'dashboard-widget-manager' ),
					'url'   => admin_url( 'admin.php?page=dashboard-widget-manager' ),
				],
			],
			$user_id
		);
	}
}
