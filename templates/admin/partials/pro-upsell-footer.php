<?php
/**
 * Admin Partial Template - Pro Upsell Footer
 *
 * Handles markup rendering for the pro upsell footer admin partial template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Allow custom state and messages to be passed in.
$custom_state   = $custom_state ?? null;
$custom_title   = $custom_title ?? null;
$custom_message = $custom_message ?? null;
$custom_actions = $custom_actions ?? null;

if ( ! $custom_state ) {
	// DWM Pro does not exist yet — always show the upsell.
	$status_message = __( 'Unlock advanced dashboard widgets with SQL queries, PHP rendering, custom caching, and more.', 'dashboard-widget-manager' );
	$status_class   = 'dwm-pro-status-not-installed';
} else {
	$status_class   = 'dwm-pro-status-' . $custom_state;
	$status_message = $custom_message;
}

$title = $custom_title ?? __( 'Dashboard Widget Manager Pro', 'dashboard-widget-manager' );
?>
<div class="dwm-section dwm-pro-upsell dwm-pro-upsell-hero <?php echo esc_attr( $status_class ); ?>">
	<div class="dwm-pro-upsell-glow"></div>
	<div class="dwm-pro-upsell-header">
		<div class="dwm-pro-upsell-icon">
			<span class="dashicons dashicons-star-filled dwm-animate-slow"></span>
		</div>
		<div class="dwm-pro-upsell-text">
			<h2><?php echo esc_html( $title ); ?></h2>
			<span class="dwm-pro-badge dwm-pro-badge-inline" title="<?php esc_attr_e( 'Pro version available', 'dashboard-widget-manager' ); ?>">
				<?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?>
			</span>
		</div>
	</div>
	<p class="dwm-pro-upsell-status"><?php echo esc_html( $status_message ); ?></p>
	<div class="dwm-pro-upsell-actions">
		<?php if ( $custom_actions ) : ?>
			<?php echo $custom_actions; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php else : ?>
			<?php
			$label       = __( 'Upgrade to Pro', 'dashboard-widget-manager' );
			$url         = admin_url( 'admin.php?page=dwm-settings#dwm-pro-license-key' );
			$variant     = 'primary';
			$extra_class = 'dwm-upgrade-button';
			$icon        = 'star-filled';
			include __DIR__ . '/pro-upgrade-button.php';
			unset( $label, $url, $variant, $extra_class, $icon );
			?>
		<?php endif; ?>
	</div>
</div>
