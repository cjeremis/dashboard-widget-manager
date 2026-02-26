<?php
/**
 * Admin Modal Template - Pro Upgrade
 *
 * Handles markup rendering for the pro upgrade admin modal template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="dwm-pro-upgrade-modal" class="dwm-modal dwm-pro-upgrade-modal">
	<div class="dwm-modal-overlay"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-star-filled"></span>
				<?php esc_html_e( 'Upgrade to Pro', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body dwm-pro-modal-card">
			<div class="dwm-step-title">
				<div class="dwm-step-icon dwm-step-icon--pro">
					<span class="dashicons dashicons-star-filled"></span>
				</div>
				<h2><?php esc_html_e( 'Upgrade to Pro', 'dashboard-widget-manager' ); ?></h2>
			</div>

			<p class="dwm-step-description"><?php esc_html_e( 'Unlock advanced features to supercharge your WordPress dashboard.', 'dashboard-widget-manager' ); ?></p>

			<ul class="dwm-pro-features-list">
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Custom HTML/PHP templates for widgets', 'dashboard-widget-manager' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Custom CSS scoped to each widget', 'dashboard-widget-manager' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Custom JavaScript per widget', 'dashboard-widget-manager' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Role-based widget visibility controls', 'dashboard-widget-manager' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Multi-user access management', 'dashboard-widget-manager' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Widget scheduling & automation', 'dashboard-widget-manager' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Advanced query caching controls', 'dashboard-widget-manager' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Export & import widget configurations', 'dashboard-widget-manager' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Email alerts & data notifications', 'dashboard-widget-manager' ); ?></li>
			</ul>

			<div class="dwm-pro-modal-actions">
				<a href="https://topdevamerica.com" target="_blank" rel="noopener noreferrer" class="dwm-button-primary dwm-upgrade-button">
					<span class="dashicons dashicons-star-filled"></span>
					<?php esc_html_e( 'Upgrade to Pro', 'dashboard-widget-manager' ); ?>
				</a>
				<button type="button" class="dwm-button-secondary dwm-modal-close">
					<?php esc_html_e( 'Maybe Later', 'dashboard-widget-manager' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>
