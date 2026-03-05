<?php
/**
 * Demo Data Handler
 *
 * Handles demo data import and cleanup operations.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Demo Data class.
 *
 * Manages demo data import and deletion for the plugin.
 */
class DWM_Demo_Data {

	use DWM_Singleton;
	use DWM_AJAX_Handler;

	/**
	 * Load demo data from file.
	 *
	 * @return array|null Decoded demo data or null if not found/invalid.
	 */
	private function load_demo_data() {
		$demo_file = DWM_PLUGIN_DIR . 'includes/admin/data/demo-data.json';

		if ( ! file_exists( $demo_file ) ) {
			return null;
		}

		$contents  = file_get_contents( $demo_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$demo_data = json_decode( $contents, true );

		return null === $demo_data ? null : $demo_data;
	}

	/**
	 * Check whether the demo data file exists.
	 *
	 * @return bool
	 */
	public function demo_data_exists(): bool {
		return file_exists( DWM_PLUGIN_DIR . 'includes/admin/data/demo-data.json' );
	}

	/**
	 * AJAX handler to import demo data (widgets + notifications).
	 *
	 * Accepts optional POST toggle: import_widgets (bool, default true).
	 *
	 * @return void
	 */
	public function ajax_import_demo_data(): void {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$import_widgets       = ! isset( $_POST['import_widgets'] ) || filter_var( wp_unslash( $_POST['import_widgets'] ), FILTER_VALIDATE_BOOLEAN );
		$import_notifications = ! isset( $_POST['import_notifications'] ) || filter_var( wp_unslash( $_POST['import_notifications'] ), FILTER_VALIDATE_BOOLEAN );

		if ( ! $import_widgets && ! $import_notifications ) {
			$this->send_error( __( 'Please select at least one data type to import.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$demo_data = $this->load_demo_data();
		if ( null === $demo_data ) {
			$this->send_error( __( 'Demo data file not found.', 'dashboard-widget-manager' ), 404 );
			return;
		}

		$widget_count       = 0;
		$notification_count = 0;

		if ( $import_widgets && isset( $demo_data['widgets'] ) && is_array( $demo_data['widgets'] ) ) {
			$widget_count = $this->import_demo_widgets( $demo_data['widgets'] );
		}

		if ( $import_notifications && isset( $demo_data['notifications'] ) && is_array( $demo_data['notifications'] ) ) {
			$notification_count = $this->import_demo_notifications( $demo_data['notifications'] );
		}

		$count   = $widget_count + $notification_count;
		/* translators: %1$d: widgets imported, %2$d: notifications imported */
		$message = sprintf( __( '%1$d widget(s) and %2$d notification(s) imported.', 'dashboard-widget-manager' ), $widget_count, $notification_count );

		$this->send_success( $message, array( 'count' => $count, 'widgets' => $widget_count, 'notifications' => $notification_count ) );
	}

	/**
	 * AJAX handler to delete all demo data (widgets + notifications).
	 * Never touches real (non-demo) widgets, settings, or notifications.
	 *
	 * @return void
	 */
	public function ajax_delete_demo_data(): void {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$data                = DWM_Data::get_instance();
		$deleted_widgets     = $data->delete_demo_widgets();
		$deleted_notifs      = DWM_Notifications::get_instance()->delete_demo_notifications();

		/* translators: %1$d: widgets deleted, %2$d: notifications deleted */
		$message = sprintf( __( '%1$d demo widget(s) and %2$d demo notification(s) deleted.', 'dashboard-widget-manager' ), $deleted_widgets, $deleted_notifs );

		$this->send_success( $message, array( 'widgets' => $deleted_widgets, 'notifications' => $deleted_notifs ) );
	}

	/**
	 * Import widgets from demo data, marking each as is_demo = 1.
	 *
	 * Skips widgets that already exist with the same name to avoid duplicates.
	 *
	 * @param array $widgets_data Array of widget objects from demo JSON.
	 * @return int Count of widgets created.
	 */
	private function import_demo_widgets( array $widgets_data ): int {
		$data  = DWM_Data::get_instance();
		$count = 0;

		foreach ( $widgets_data as $widget ) {
			if ( ! is_array( $widget ) || empty( $widget['name'] ) ) {
				continue;
			}

			// Skip if a widget with this name already exists.
			$existing = $data->get_widget_by_name( sanitize_text_field( $widget['name'] ) );
			if ( $existing ) {
				continue;
			}

			// Strip any existing ID fields.
			unset( $widget['id'], $widget['created_at'], $widget['updated_at'], $widget['created_by'] );

			// Mark as demo.
			$widget['is_demo'] = 1;

			// Sanitize key fields.
			$widget['name']         = sanitize_text_field( $widget['name'] );
			$widget['description']  = isset( $widget['description'] ) ? sanitize_text_field( $widget['description'] ) : '';
			$widget['enabled']      = isset( $widget['enabled'] ) ? (int) $widget['enabled'] : 1;
			$widget['widget_order'] = isset( $widget['widget_order'] ) ? (int) $widget['widget_order'] : 0;

			$new_id = $data->create_widget( $widget );

			if ( $new_id ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Import notifications from demo data.
	 *
	 * All demo notification types must start with 'demo_' so they can be
	 * targeted by delete_demo_notifications() without touching real notifications.
	 * Uses add_notification() which skips duplicates by type+user.
	 *
	 * @param array $notifications_data Array of notification objects from demo JSON.
	 * @return int Count of notifications created.
	 */
	private function import_demo_notifications( array $notifications_data ): int {
		$notifications = DWM_Notifications::get_instance();
		$count         = 0;

		foreach ( $notifications_data as $notif ) {
			if ( ! is_array( $notif ) || empty( $notif['type'] ) ) {
				continue;
			}

			$type    = sanitize_text_field( $notif['type'] );
			$title   = sanitize_text_field( $notif['title'] ?? '' );
			$message = sanitize_text_field( $notif['message'] ?? '' );
			$icon    = sanitize_text_field( $notif['icon'] ?? 'info' );
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

			$result = $notifications->add_notification( $type, $title, $message, $icon, $actions );
			if ( $result ) {
				$count++;
			}
		}

		return $count;
	}
}
