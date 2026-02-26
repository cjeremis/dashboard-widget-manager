<?php
/**
 * Admin Partial Template - Notification Item
 *
 * Handles markup rendering for the notification item admin partial template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$notification_id = $notification->id ?? 0;
$icon            = $notification->icon ?? 'info';
$title           = $notification->title ?? '';
$message         = $notification->message ?? '';
$actions         = ! empty( $notification->actions ) ? json_decode( $notification->actions, true ) : [];
?>

<li class="dwm-notification-item" data-notification-id="<?php echo esc_attr( $notification_id ); ?>">
	<div class="dwm-notification-content">
		<div class="dwm-notification-icon dwm-notification-icon--<?php echo esc_attr( $icon ); ?>">
			<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
		</div>
		<div class="dwm-notification-text">
			<h4 class="dwm-notification-title"><?php echo esc_html( $title ); ?></h4>
			<p class="dwm-notification-message"><?php echo esc_html( $message ); ?></p>
			<?php if ( ! empty( $actions ) ) : ?>
				<div class="dwm-notification-actions">
					<?php foreach ( $actions as $action ) : ?>
						<?php
						$action_url   = $action['url'] ?? '#';
						$action_label = $action['label'] ?? 'Action';
						?>
						<a href="<?php echo esc_url( $action_url ); ?>" class="dwm-notification-action-button">
							<?php echo esc_html( $action_label ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<button class="dwm-notification-delete" type="button" data-notification-id="<?php echo esc_attr( $notification_id ); ?>" title="<?php esc_attr_e( 'Dismiss notification', 'dashboard-widget-manager' ); ?>">
		<span class="dashicons dashicons-no-alt"></span>
	</button>
</li>
