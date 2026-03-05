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
				<span class="dashicons dashicons-star-filled dwm-animate-slow"></span>
				<?php esc_html_e( 'Dashboard Widget Manager Pro', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body dwm-pro-modal-card">
			<div class="dwm-step-title">
				<div class="dwm-step-icon dwm-step-icon--pro">
					<span class="dashicons dashicons-star-filled dwm-animate-slow"></span>
				</div>
				<h2><?php esc_html_e( 'Upgrade to Pro', 'dashboard-widget-manager' ); ?></h2>
			</div>

			<p class="dwm-step-description"><?php esc_html_e( 'Unlock advanced features to supercharge your WordPress dashboard.', 'dashboard-widget-manager' ); ?></p>

			<ul class="dwm-pro-features-list">
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Role-based widget visibility controls', 'dashboard-widget-manager' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Widget scheduling &amp; automation', 'dashboard-widget-manager' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Advanced chart types &amp; styling', 'dashboard-widget-manager' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Custom CSS and HTML widgets', 'dashboard-widget-manager' ); ?></li>
				<li><span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Data export &amp; email reports', 'dashboard-widget-manager' ); ?></li>
			</ul>

			<a href="#" class="dwm-pro-see-all-features" data-open-modal="dwm-features-modal">
				<?php esc_html_e( 'See all features', 'dashboard-widget-manager' ); ?> &rarr;
			</a>
		</div>
		<div class="dwm-modal-footer">
			<div class="dwm-pro-modal-actions">
				<a href="https://topdevamerica.com" target="_blank" rel="noopener noreferrer" class="dwm-button-primary dwm-upgrade-button">
					<span class="dashicons dashicons-star-filled"></span>
					<?php esc_html_e( 'Upgrade to Pro', 'dashboard-widget-manager' ); ?>
				</a>
				<button type="button" class="dwm-button-secondary" data-close-modal>
					<?php esc_html_e( 'Maybe Later', 'dashboard-widget-manager' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>
