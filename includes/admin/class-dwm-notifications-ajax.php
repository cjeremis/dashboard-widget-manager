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

	/**
	 * Get user notifications
	 *
	 * @return void
	 */
	public function ajax_get_notifications(): void {
		if ( ! current_user_can( DWM_Admin_Menu::REQUIRED_CAP ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		if ( ! check_ajax_referer( 'dwm_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
		}

		// Sync remote support replies into local notifications before returning
		if ( class_exists( 'DWM_Support_AJAX' ) ) {
			DWM_Support_AJAX::get_instance()->sync_notifications_for_user( get_current_user_id() );
		}

		$notifications_db = DWM_Notifications::get_instance();
		$notifications    = $notifications_db->get_user_notifications();

		// Check if Pro is fully enabled (installed, active, AND licensed)
		$is_pro_enabled = class_exists( 'DWM_Pro_Feature_Gate' ) && DWM_Pro_Feature_Gate::is_pro_enabled();

		// Check if we should show the hardcoded license CTA
		$show_license_cta = false;
		if ( ! $is_pro_enabled ) {
			$pro_plugin_file  = 'dashboard-widget-manager-pro/dashboard-widget-manager-pro.php';
			$pro_plugin_path  = WP_PLUGIN_DIR . '/' . $pro_plugin_file;
			$is_pro_installed = file_exists( $pro_plugin_path );

			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$is_pro_active = $is_pro_installed && is_plugin_active( $pro_plugin_file );

			// Show license CTA if Pro is active but not fully enabled (missing license)
			$show_license_cta = $is_pro_active;
		}

		// Format notifications for JSON response
		$formatted_notifications = [];

		// Add hardcoded license CTA first if needed
		if ( $show_license_cta ) {
			$formatted_notifications[] = [
				'id'        => 'license-cta',
				'type'      => 'pro_api_key_missing',
				'title'     => __( 'Pro Plugin Installed', 'dashboard-widget-manager' ),
				'message'   => __( 'Enter your Dashboard Widget Manager Pro license key to unlock all premium features.', 'dashboard-widget-manager' ),
				'icon'      => 'star-filled',
				'actions'   => [
					[
						'label'      => __( 'Add License Key', 'dashboard-widget-manager' ),
						'url'        => admin_url( 'admin.php?page=dwm-settings#dwm-pro-license-key' ),
						'class'      => 'dwm-add-api-key-button',
						'scrollTo'   => 'dwm-pro-license-key',
						'focusField' => 'dwm_pro_license_key',
					],
				],
				'deletable' => false,
			];
		}

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

		// Count only database notifications (don't count the license CTA)
		$count = count( $notifications );
		foreach ( $notifications as $notification ) {
			if ( 'pro_api_key_missing' === $notification['type'] ) {
				$count--;
			}
		}

		wp_send_json_success(
			[
				'notifications' => $formatted_notifications,
				'count'         => $count,
			]
		);
	}

	/**
	 * Delete a notification
	 *
	 * @return void
	 */
	public function ajax_delete_notification(): void {
		if ( ! current_user_can( DWM_Admin_Menu::REQUIRED_CAP ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		if ( ! check_ajax_referer( 'dwm_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
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
		if ( ! current_user_can( DWM_Admin_Menu::REQUIRED_CAP ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		if ( ! check_ajax_referer( 'dwm_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
		}

		if ( class_exists( 'DWM_Support_AJAX' ) ) {
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
		if ( ! current_user_can( DWM_Admin_Menu::REQUIRED_CAP ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		if ( ! check_ajax_referer( 'dwm_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
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
		if ( ! current_user_can( DWM_Admin_Menu::REQUIRED_CAP ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		if ( ! check_ajax_referer( 'dwm_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
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
