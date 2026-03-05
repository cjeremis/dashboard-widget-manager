<?php
/**
 * Notifications AJAX Handler
 *
 * Handles AJAX requests for notifications (fetch, delete, etc.)
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DWM_Notifications_AJAX {

	use DWM_Singleton;
	use DWM_AJAX_Handler;

	/**
	 * Check whether live support notification sync is enabled.
	 *
	 * @return bool
	 */
	private function is_live_sync_enabled(): bool {
		$settings = DWM_Data::get_instance()->get_settings();

		return ! empty( $settings['support_data_sharing_opt_in'] );
	}

	/**
	 * Add one local release/update notification per version.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	private function maybe_add_version_notification( int $user_id ): void {
		$meta_key     = 'dwm_last_seen_plugin_version';
		$seen_version = (string) get_user_meta( $user_id, $meta_key, true );

		if ( $seen_version === DWM_VERSION ) {
			return;
		}

		$notifications = DWM_Notifications::get_instance();
		$type          = 'plugin_update_' . sanitize_key( str_replace( '.', '_', DWM_VERSION ) );
		$privacy_url   = get_permalink( (int) get_option( 'tda_shared_privacy_page_id' ) );
		$terms_url     = get_permalink( (int) get_option( 'tda_shared_terms_page_id' ) );

		$actions = [];
		if ( $privacy_url ) {
			$actions[] = [ 'label' => __( 'Privacy Policy', 'dashboard-widget-manager' ), 'url' => $privacy_url ];
		}
		if ( $terms_url ) {
			$actions[] = [ 'label' => __( 'Terms', 'dashboard-widget-manager' ), 'url' => $terms_url ];
		}

		$notifications->add_notification(
			$type,
			sprintf( __( 'Dashboard Widget Manager Updated to %s', 'dashboard-widget-manager' ), DWM_VERSION ),
			__( 'New release notes and policy disclosures are available in your plugin settings and legal pages.', 'dashboard-widget-manager' ),
			'update',
			$actions,
			$user_id
		);

		update_user_meta( $user_id, $meta_key, DWM_VERSION );
	}

	/**
	 * Get user notifications
	 *
	 * @return void
	 */
	public function ajax_get_notifications(): void {
		if ( ! $this->verify_ajax_request( 'nonce', 'dwm_admin_nonce', DWM_Admin_Menu::REQUIRED_CAP ) ) {
			return;
		}

		$user_id = get_current_user_id();
		$this->maybe_add_version_notification( $user_id );

		// Sync remote support replies into local notifications before returning.
		if ( $this->is_live_sync_enabled() && class_exists( 'DWM_Support_AJAX' ) ) {
			DWM_Support_AJAX::get_instance()->sync_notifications_for_user( get_current_user_id() );
		}

		$notifications_db = DWM_Notifications::get_instance();
		$notifications    = $notifications_db->get_user_notifications();

		// Format notifications for JSON response
		$formatted_notifications = [];

		foreach ( $notifications as $notification ) {
			// Skip pro_api_key_missing from DB since it's now hardcoded
			if ( 'pro_api_key_missing' === $notification['type'] ) {
				continue;
			}

			$formatted_notifications[] = [
				'id'        => $notification['id'],
				'type'      => $notification['type'],
				'title'     => $notification['title'],
				'message'   => $notification['message'],
				'icon'      => $notification['icon'],
				'actions'   => $notification['actions'] ? json_decode( $notification['actions'], true ) : [],
				'deletable' => DWM_Notifications_Manager::is_notification_deletable( $notification['type'] ),
			];
		}

		wp_send_json_success(
			[
				'notifications' => $formatted_notifications,
				'count'         => count( $formatted_notifications ),
			]
		);
	}

	/**
	 * Delete a notification
	 *
	 * @return void
	 */
	public function ajax_delete_notification(): void {
		if ( ! $this->verify_ajax_request( 'nonce', 'dwm_admin_nonce', DWM_Admin_Menu::REQUIRED_CAP ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedInput.InputNotSanitized
		$notification_id = isset( $_POST['notification_id'] ) ? (int) wp_unslash( $_POST['notification_id'] ) : 0;

		if ( ! $notification_id ) {
			wp_send_json_error( [ 'message' => 'Invalid notification ID' ], 400 );
		}

		// Verify the notification belongs to the current user
		global $wpdb;
		$table        = DWM_Notifications::get_table_name();
		$notification = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d AND user_id = %d LIMIT 1",
				$notification_id,
				get_current_user_id()
			),
			ARRAY_A
		);

		if ( ! $notification ) {
			wp_send_json_error( [ 'message' => 'Notification not found' ], 404 );
		}

		if ( ! DWM_Notifications_Manager::is_notification_deletable( $notification['type'] ) ) {
			wp_send_json_error( [ 'message' => 'This notification cannot be deleted' ], 403 );
		}

		$notifications_db = DWM_Notifications::get_instance();
		$result           = $notifications_db->delete_notification( $notification_id );

		if ( $result ) {
			$count = $notifications_db->get_notification_count();
			wp_send_json_success( [ 'count' => $count ] );
		} else {
			wp_send_json_error( [ 'message' => 'Failed to delete notification' ], 500 );
		}
	}

	/**
	 * Get notification count
	 *
	 * @return void
	 */
	public function ajax_get_notification_count(): void {
		if ( ! $this->verify_ajax_request( 'nonce', 'dwm_admin_nonce', DWM_Admin_Menu::REQUIRED_CAP ) ) {
			return;
		}

		$user_id = get_current_user_id();
		$this->maybe_add_version_notification( $user_id );

		if ( $this->is_live_sync_enabled() && class_exists( 'DWM_Support_AJAX' ) ) {
			DWM_Support_AJAX::get_instance()->sync_notifications_for_user( get_current_user_id() );
		}

		$notifications_db = DWM_Notifications::get_instance();
		$notifications    = $notifications_db->get_user_notifications();

		// Count notifications excluding pro_api_key_missing (which is now hardcoded)
		// License CTA is NOT counted as a notification
		$count = 0;
		foreach ( $notifications as $notification ) {
			if ( 'pro_api_key_missing' !== $notification['type'] ) {
				$count++;
			}
		}

		wp_send_json_success( [ 'count' => $count ] );
	}

	/**
	 * Mark notification as read
	 *
	 * @return void
	 */
	public function ajax_mark_read(): void {
		if ( ! $this->verify_ajax_request( 'nonce', 'dwm_admin_nonce', DWM_Admin_Menu::REQUIRED_CAP ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedInput.InputNotSanitized
		$notification_id = isset( $_POST['notification_id'] ) ? (int) wp_unslash( $_POST['notification_id'] ) : 0;

		if ( ! $notification_id ) {
			wp_send_json_error( [ 'message' => 'Invalid notification ID' ], 400 );
		}

		$user_id = get_current_user_id();

		// Verify notification belongs to user
		global $wpdb;
		$table        = DWM_Notifications::get_table_name();
		$notification = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE id = %d AND user_id = %d LIMIT 1",
				$notification_id,
				$user_id
			)
		);

		if ( ! $notification ) {
			wp_send_json_error( [ 'message' => 'Notification not found' ], 404 );
		}

		$read_meta = get_user_meta( $user_id, 'dwm_notifications_read', true );
		$read_meta = is_array( $read_meta ) ? $read_meta : [];

		if ( ! in_array( $notification_id, $read_meta, true ) ) {
			$read_meta[] = $notification_id;
			update_user_meta( $user_id, 'dwm_notifications_read', $read_meta );
		}

		wp_send_json_success( [ 'message' => 'Notification marked as read' ] );
	}

	/**
	 * Mark notification as unread
	 *
	 * @return void
	 */
	public function ajax_mark_unread(): void {
		if ( ! $this->verify_ajax_request( 'nonce', 'dwm_admin_nonce', DWM_Admin_Menu::REQUIRED_CAP ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedInput.InputNotSanitized
		$notification_id = isset( $_POST['notification_id'] ) ? (int) wp_unslash( $_POST['notification_id'] ) : 0;

		if ( ! $notification_id ) {
			wp_send_json_error( [ 'message' => 'Invalid notification ID' ], 400 );
		}

		$user_id = get_current_user_id();

		// Verify notification belongs to user
		global $wpdb;
		$table        = DWM_Notifications::get_table_name();
		$notification = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE id = %d AND user_id = %d LIMIT 1",
				$notification_id,
				$user_id
			)
		);

		if ( ! $notification ) {
			wp_send_json_error( [ 'message' => 'Notification not found' ], 404 );
		}

		$read_meta = get_user_meta( $user_id, 'dwm_notifications_read', true );
		$read_meta = is_array( $read_meta ) ? $read_meta : [];

		$read_meta = array_diff( $read_meta, [ $notification_id ] );
		update_user_meta( $user_id, 'dwm_notifications_read', array_values( $read_meta ) );

		wp_send_json_success( [ 'message' => 'Notification marked as unread' ] );
	}
}
