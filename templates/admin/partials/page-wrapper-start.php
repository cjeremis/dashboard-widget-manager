<?php
/**
 * Admin Partial Template - Page Wrapper Start
 *
 * Handles markup rendering for the page wrapper start admin partial template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set defaults for optional variables
$topbar_actions = $topbar_actions ?? [];

?>
<div class="dwm-admin-container">
	<div class="dwm-support-toolbar">
		<button type="button" class="dwm-support-link" data-open-modal="dwm-features-modal" title="<?php esc_attr_e( 'Dashboard Widget Manager Features', 'dashboard-widget-manager' ); ?>">
			<span class="dashicons dashicons-awards"></span>
		</button>
		<button type="button" class="dwm-support-link dwm-notification-button" title="<?php esc_attr_e( 'View notifications', 'dashboard-widget-manager' ); ?>">
			<span class="dashicons dashicons-megaphone"></span>
		</button>
		<button type="button" class="dwm-support-link" data-open-modal="dwm-new-ticket-modal" title="<?php esc_attr_e( 'Support', 'dashboard-widget-manager' ); ?>">
			<span class="dashicons dashicons-phone"></span>
		</button>
		<button type="button" class="dwm-support-link" data-open-modal="dwm-docs-modal" title="<?php esc_attr_e( 'Documentation', 'dashboard-widget-manager' ); ?>">
			<span class="dashicons dashicons-book-alt"></span>
		</button>
		<button type="button" class="dwm-support-link dwm-support-upgrade dwm-topbar-upgrade" data-open-modal="dwm-pro-upgrade-modal" title="<?php esc_attr_e( 'Upgrade to Pro', 'dashboard-widget-manager' ); ?>">
			<span class="dashicons dashicons-star-filled"></span>
			<?php esc_html_e( 'Upgrade to Pro', 'dashboard-widget-manager' ); ?>
		</button>
	</div>
	<?php
	// Include navigation topbar
	include __DIR__ . '/topbar.php';
	?>

	<?php
	// Include page header
	include __DIR__ . '/header.php';
	// Content starts here (provided by including template)
	?>
