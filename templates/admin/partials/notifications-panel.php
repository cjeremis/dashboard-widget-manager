<?php
/**
 * Admin Partial Template - Notifications Panel
 *
 * Handles markup rendering for the notifications panel admin partial template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if Pro is fully enabled (installed, active, AND licensed)
$is_pro_enabled = class_exists( 'DWM_Pro_Feature_Gate' ) && DWM_Pro_Feature_Gate::is_pro_enabled();

// Build upsell CTA state — shown in all non-enabled states, hidden only when Pro is fully active
$show_upsell_cta  = false;
$upsell_state     = '';
$upsell_title     = '';
$upsell_message   = '';
$upsell_btn_label = '';
$upsell_btn_url   = '';
$upsell_btn_icon  = '';
$upsell_btn_class = '';
$upsell_btn_attrs = '';

if ( ! $is_pro_enabled ) {
	$show_upsell_cta  = true;
	$pro_plugin_file  = 'dashboard-widget-manager-pro/dashboard-widget-manager-pro.php';
	$is_pro_installed = file_exists( WP_PLUGIN_DIR . '/' . $pro_plugin_file );

	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$is_pro_active = is_plugin_active( $pro_plugin_file );

	if ( ! $is_pro_installed ) {
		$upsell_state     = 'not-installed';
		$upsell_title     = __( 'Upgrade to Pro', 'dashboard-widget-manager' );
		$upsell_message   = __( 'Purchase and install the Pro version to unlock advanced features.', 'dashboard-widget-manager' );
		$upsell_btn_label = __( 'Upgrade to Pro', 'dashboard-widget-manager' );
		$upsell_btn_url   = admin_url( 'admin.php?page=dwm-settings' );
		$upsell_btn_icon  = 'star-filled';
		$upsell_btn_class = 'dwm-upgrade-button';
	} elseif ( ! $is_pro_active ) {
		$upsell_state     = 'inactive';
		$upsell_title     = __( 'Pro is Installed', 'dashboard-widget-manager' );
		$upsell_message   = __( 'Activate the Pro plugin and enter your license key to unlock premium features.', 'dashboard-widget-manager' );
		$upsell_btn_label = __( 'Upgrade to Pro', 'dashboard-widget-manager' );
		$upsell_btn_url   = admin_url( 'admin.php?page=dwm-settings' );
		$upsell_btn_icon  = 'star-filled';
		$upsell_btn_class = 'dwm-upgrade-button';
	} else {
		$has_api_key = class_exists( 'DWM_Pro_Feature_Gate' ) && ! empty( DWM_Pro_Feature_Gate::get_license_key() );
		if ( ! $has_api_key ) {
			$upsell_state     = 'no-key';
			$upsell_title     = __( 'Pro Plugin Installed', 'dashboard-widget-manager' );
			$upsell_message   = __( 'Enter your Dashboard Widget Manager Pro license key to unlock all premium features.', 'dashboard-widget-manager' );
			$upsell_btn_label = __( 'Add License Key', 'dashboard-widget-manager' );
			$upsell_btn_url   = admin_url( 'admin.php?page=dwm-settings#dwm-pro-license-key' );
			$upsell_btn_icon  = 'unlock';
			$upsell_btn_class = 'dwm-add-api-key-button';
			$upsell_btn_attrs = 'data-scroll-to="dwm-pro-license-key" data-focus-field="dwm_pro_license_key"';
		} else {
			$upsell_state     = 'unlicensed';
			$upsell_title     = __( 'License Key Required', 'dashboard-widget-manager' );
			$upsell_message   = __( 'Your Dashboard Widget Manager Pro license key is invalid or expired. Renew your license to continue using Pro features.', 'dashboard-widget-manager' );
			$upsell_btn_label = __( 'Upgrade to Pro', 'dashboard-widget-manager' );
			$upsell_btn_url   = admin_url( 'admin.php?page=dwm-settings' );
			$upsell_btn_icon  = 'star-filled';
			$upsell_btn_class = 'dwm-upgrade-button';
		}
	}
}

// Get initial notifications server-side for instant load
$notifications_db = DWM_Notifications::get_instance();
$notifications    = $notifications_db->get_user_notifications();

// Filter out pro_api_key_missing from DB since it's now hardcoded
$notifications = array_filter( $notifications, function( $notification ) {
	return ( $notification['type'] ?? '' ) !== 'pro_api_key_missing';
} );
$notifications = array_values( $notifications );

// License CTA is NOT counted as a notification (it's hardcoded, not a real notification)
$count = count( $notifications );
?>

<div class="dwm-notifications-panel" id="dwm-notifications-panel" data-initial-count="<?php echo esc_attr( $count ); ?>">
	<!-- Panel Header -->
	<div class="dwm-notifications-header">
		<h3 class="dwm-notifications-title">
			<span class="dashicons dashicons-megaphone"></span>
			<?php esc_html_e( 'Notifications', 'dashboard-widget-manager' ); ?>
			<?php if ( $count > 0 ) : ?>
				<span class="dwm-notifications-count"><?php echo esc_html( $count ); ?></span>
			<?php endif; ?>
		</h3>
		<button class="dwm-notifications-close" id="dwm-notifications-close" type="button">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
	</div>

	<!-- Panel Body - Initial content rendered server-side -->
	<div class="dwm-notifications-body">
		<?php if ( $show_upsell_cta ) : ?>
			<!-- Pro Upsell CTA - visible in all non-enabled states -->
			<div class="dwm-notification-item dwm-notification-item--upsell-cta dwm-notification-item--<?php echo esc_attr( $upsell_state ); ?>" data-notification-id="upsell-cta" data-notification-type="pro_upsell">
				<div class="dwm-notification-license-glow"></div>
				<div class="dwm-notification-license-header">
					<div class="dwm-notification-license-icon">
						<span class="dashicons dashicons-star-filled dwm-animate-slow"></span>
					</div>
					<div class="dwm-notification-license-text">
						<h4 class="dwm-notification-license-title"><?php echo esc_html( $upsell_title ); ?></h4>
						<span class="dwm-pro-badge dwm-pro-badge-inline"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
					</div>
				</div>
				<p class="dwm-notification-license-message"><?php echo esc_html( $upsell_message ); ?></p>
				<div class="dwm-notification-license-actions">
					<a href="<?php echo esc_url( $upsell_btn_url ); ?>" class="dwm-button dwm-pro-upgrade-button dwm-pro-upgrade-button--primary dwm-button-primary <?php echo esc_attr( $upsell_btn_class ); ?>" <?php echo $upsell_btn_attrs; ?>>
						<span class="dashicons dashicons-<?php echo esc_attr( $upsell_btn_icon ); ?>"></span>
						<span class="dwm-pro-upgrade-button__label"><?php echo esc_html( $upsell_btn_label ); ?></span>
					</a>
				</div>
			</div>
		<?php endif; ?>

		<ul class="dwm-notifications-list" id="dwm-notifications-list"<?php echo ( empty( $notifications ) ) ? ' style="display:none;"' : ''; ?>>
			<?php if ( ! empty( $notifications ) ) : ?>
				<?php foreach ( $notifications as $notification ) :
					$notification_id = $notification['id'] ?? 0;
					$icon            = $notification['icon'] ?? 'info';
					$title           = $notification['title'] ?? '';
					$message         = $notification['message'] ?? '';
					$type            = $notification['type'] ?? '';
					$actions         = ! empty( $notification['actions'] ) ? json_decode( $notification['actions'], true ) : [];
					$is_deletable    = DWM_Notifications_Manager::is_notification_deletable( $type );
				?>
					<li class="dwm-notification-item" data-notification-id="<?php echo esc_attr( $notification_id ); ?>" data-notification-type="<?php echo esc_attr( $type ); ?>">
						<div class="dwm-notification-content">
							<div class="dwm-notification-icon dwm-notification-icon--<?php echo esc_attr( $icon ); ?>">
								<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
							</div>
							<div class="dwm-notification-text">
								<h4 class="dwm-notification-title"><?php echo esc_html( $title ); ?></h4>
								<p class="dwm-notification-message"><?php echo esc_html( $message ); ?></p>
								<?php if ( ! empty( $actions ) ) : ?>
									<div class="dwm-notification-actions">
										<?php foreach ( $actions as $action ) :
											$action_class = 'dwm-button sm outline';
											if ( ! empty( $action['class'] ) ) {
												$action_class .= ' ' . $action['class'];
											}
											$data_attrs = '';
											if ( ! empty( $action['scrollTo'] ) ) {
												$data_attrs .= ' data-scroll-to="' . esc_attr( $action['scrollTo'] ) . '"';
											}
											if ( ! empty( $action['focusField'] ) ) {
												$data_attrs .= ' data-focus-field="' . esc_attr( $action['focusField'] ) . '"';
											}
										?>
											<a href="<?php echo esc_url( $action['url'] ?? '#' ); ?>" class="<?php echo esc_attr( $action_class ); ?>"<?php echo $data_attrs; ?>>
												<?php echo esc_html( $action['label'] ?? 'Action' ); ?>
											</a>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
						<?php if ( $is_deletable ) : ?>
							<button class="dwm-notification-delete" type="button" data-notification-id="<?php echo esc_attr( $notification_id ); ?>" title="<?php esc_attr_e( 'Dismiss notification', 'dashboard-widget-manager' ); ?>">
								<span class="dashicons dashicons-no-alt"></span>
							</button>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			<?php endif; ?>
		</ul>

		<!-- Checking for new notifications message -->
		<div class="dwm-notifications-loading" id="dwm-notifications-loading" style="display: none;">
			<span class="dashicons dashicons-update"></span>
			<p><?php esc_html_e( 'Checking for new notifications...', 'dashboard-widget-manager' ); ?></p>
		</div>

		<!-- Empty state - shows when there are no notifications -->
		<div class="dwm-notifications-empty" id="dwm-notifications-empty"<?php echo ( ! empty( $notifications ) ) ? ' style="display:none;"' : ''; ?>>
			<span class="dashicons dashicons-megaphone"></span>
			<p><?php esc_html_e( 'No notifications', 'dashboard-widget-manager' ); ?></p>
		</div>
	</div>

	<!-- Panel Footer -->
	<div class="dwm-notifications-footer">
		<p class="dwm-notifications-info">
			<?php esc_html_e( 'Notifications from TopDevAmerica', 'dashboard-widget-manager' ); ?>
		</p>
	</div>
</div>

<!-- Overlay for panel -->
<div class="dwm-notifications-overlay" id="dwm-notifications-overlay"></div>
