<?php
/**
 * Notifications Storage Handler
 *
 * Handles notification storage and notification retrieval operations.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DWM_Notifications {

	use DWM_Singleton;

	const TABLE_NAME = 'dwm_notifications';

	/**
	 * Get the full table name with prefix
	 *
	 * @return string
	 */
	public static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Get notifications for current user
	 *
	 * @return array Array of notification objects
	 */
	public function get_user_notifications(): array {
		if ( ! $this->table_exists() ) {
			return [];
		}

		global $wpdb;
		$user_id = get_current_user_id();
		$table   = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d AND dismissed = 0 ORDER BY created_at DESC",
				$user_id
			),
			ARRAY_A
		);

		if ( empty( $results ) ) {
			return [];
		}

		$read_meta = get_user_meta( $user_id, 'dwm_notifications_read', true );
		$read_meta = is_array( $read_meta ) ? $read_meta : [];

		foreach ( $results as &$row ) {
			$row['is_read'] = in_array( (int) $row['id'], $read_meta, true );
		}
		unset( $row );

		return $results;
	}

	/**
	 * Add a notification
	 *
	 * @param string $type     Notification type/identifier
	 * @param string $title    Notification title
	 * @param string $message  Notification message
	 * @param string $icon     Dashicon name (without 'dashicons-' prefix)
	 * @param array  $actions  Array of actions with 'label' and 'url' keys
	 * @param int    $user_id  Optional user ID (defaults to current user)
	 *
	 * @return int|bool Notification ID on success, false on failure
	 */
	public function add_notification(
		string $type,
		string $title,
		string $message,
		string $icon = 'info',
		array $actions = [],
		int $user_id = 0
	) {
		if ( ! $this->table_exists() ) {
			return false;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		global $wpdb;
		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d AND type = %s AND dismissed = 0 LIMIT 1",
				$user_id,
				$type
			)
		);

		if ( $existing ) {
			return (int) $existing;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$table,
			[
				'user_id'    => $user_id,
				'type'       => $type,
				'title'      => $title,
				'message'    => $message,
				'icon'       => $icon,
				'actions'    => wp_json_encode( $actions ),
				'dismissed'  => 0,
				'created_at' => current_time( 'mysql' ),
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' ]
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Delete/dismiss a notification
	 *
	 * @param int $notification_id Notification ID
	 * @param int $user_id         Optional user ID for verification
	 *
	 * @return bool True on success, false on failure
	 */
	public function delete_notification( int $notification_id, int $user_id = 0 ): bool {
		if ( ! $this->table_exists() ) {
			return false;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		global $wpdb;
		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			$table,
			[ 'dismissed' => 1 ],
			[
				'id'      => $notification_id,
				'user_id' => $user_id,
			],
			[ '%d' ],
			[ '%d', '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Check if notification type exists for user
	 *
	 * @param string $type    Notification type
	 * @param int    $user_id Optional user ID
	 *
	 * @return bool True if notification exists and is not dismissed
	 */
	public function notification_exists( string $type, int $user_id = 0 ): bool {
		if ( ! $this->table_exists() ) {
			return false;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		global $wpdb;
		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d AND type = %s AND dismissed = 0 LIMIT 1",
				$user_id,
				$type
			)
		);

		return ! empty( $result );
	}

	/**
	 * Get notification count for current user
	 *
	 * @return int Number of active (non-dismissed) notifications
	 */
	public function get_notification_count(): int {
		if ( ! $this->table_exists() ) {
			return 0;
		}

		global $wpdb;
		$user_id = get_current_user_id();
		$table   = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND dismissed = 0",
				$user_id
			)
		);

		return (int) $count;
	}

	/**
	 * Clear all dismissed notifications for a user (cleanup)
	 *
	 * @param int $user_id Optional user ID
	 *
	 * @return int Number of rows deleted
	 */
	public function cleanup_dismissed_notifications( int $user_id = 0 ): int {
		if ( ! $this->table_exists() ) {
			return 0;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		global $wpdb;
		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$deleted = $wpdb->delete(
			$table,
			[
				'user_id'   => $user_id,
				'dismissed' => 1,
			],
			[ '%d', '%d' ]
		);

		return $deleted !== false ? (int) $deleted : 0;
	}

	/**
	 * Hard-delete all notifications for a user (used by reset and replace-import)
	 *
	 * @param int $user_id Optional user ID (defaults to current user)
	 *
	 * @return bool True on success
	 */
	public function delete_all_notifications( int $user_id = 0 ): bool {
		if ( ! $this->table_exists() ) {
			return false;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		global $wpdb;
		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->delete( $table, [ 'user_id' => $user_id ], [ '%d' ] );

		return $result !== false;
	}

	/**
	 * Delete all demo notifications for a user
	 *
	 * @param int $user_id Optional user ID (defaults to current user)
	 *
	 * @return int Number of notifications deleted
	 */
	public function delete_demo_notifications( int $user_id = 0 ): int {
		if ( ! $this->table_exists() ) {
			return 0;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return 0;
		}

		global $wpdb;
		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE user_id = %d AND type LIKE %s",
				$user_id,
				'demo_%'
			)
		);

		return $deleted !== false ? (int) $deleted : 0;
	}

	/**
	 * Check whether the notifications table exists.
	 *
	 * @return bool
	 */
	private function table_exists(): bool {
		return DWM_Activator::table_exists( self::TABLE_NAME );
	}
}
