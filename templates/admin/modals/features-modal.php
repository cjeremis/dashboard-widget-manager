<?php
/**
 * Admin Modal Template - Features Modal
 *
 * Handles markup rendering for the features modal admin modal template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$all_features      = DWM_Features::get_all_features();
$integrations      = DWM_Features::get_all_integrations();
$integrations_meta = DWM_Features::get_integrations_meta();
$labels            = DWM_Features::get_labels();

$first_category = array_key_first( $all_features );
?>

<div id="dwm-features-modal" class="dwm-modal dwm-features-modal">
	<div class="dwm-modal-overlay"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-awards"></span>
				<?php esc_html_e( 'Features', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<div class="dwm-sidebar-modal-layout">
				<!-- Sidebar -->
				<div class="dwm-sidebar-modal-sidebar">
					<nav class="dwm-sidebar-modal-nav">
						<ul class="dwm-sidebar-modal-menu" data-dwm-features-menu>
							<?php
							$first = true;
							foreach ( $all_features as $category_name => $features ) :
								$category_slug = sanitize_title( $category_name );
								$icon          = DWM_Features::get_category_icon( $category_name );
								$active_class  = $first ? ' is-active' : '';
								?>
								<li class="dwm-sidebar-modal-menu-item">
									<button
										type="button"
										class="dwm-sidebar-modal-menu-link<?php echo esc_attr( $active_class ); ?>"
										data-dwm-features-page="<?php echo esc_attr( $category_slug ); ?>"
									>
										<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
										<?php echo esc_html( $category_name ); ?>
										<span class="dwm-sidebar-modal-menu-count"><?php echo count( $features ); ?></span>
									</button>
								</li>
								<?php
								$first = false;
							endforeach;
							?>

							<!-- Integrations -->
							<li class="dwm-sidebar-modal-menu-item has-divider">
								<button
									type="button"
									class="dwm-sidebar-modal-menu-link"
									data-dwm-features-page="integrations"
								>
									<span class="dashicons dashicons-<?php echo esc_attr( $integrations_meta['icon'] ); ?>"></span>
									<?php echo esc_html( $integrations_meta['label'] ); ?>
									<span class="dwm-sidebar-modal-menu-count"><?php echo array_sum( array_map( 'count', $integrations ) ); ?></span>
								</button>
							</li>
						</ul>
					</nav>
				</div>

				<!-- Content Area -->
				<div class="dwm-sidebar-modal-content" data-dwm-features-content>
					<?php
					$first = true;
					foreach ( $all_features as $category_name => $category_features ) :
						$category_slug = sanitize_title( $category_name );
						$active_class  = $first ? ' is-active' : '';

						$has_pro_features = false;
						$has_coming_soon  = false;
						foreach ( $category_features as $feature ) {
							if ( 'pro' === ( $feature['plan'] ?? 'free' ) ) {
								$has_pro_features = true;
							}
							if ( empty( $feature['implemented'] ) ) {
								$has_coming_soon = true;
							}
						}
						?>
						<div class="dwm-sidebar-modal-page<?php echo esc_attr( $active_class ); ?>" data-dwm-features-page-content="<?php echo esc_attr( $category_slug ); ?>">
							<div class="dwm-sidebar-modal-page-header">
								<h3><?php echo esc_html( $category_name ); ?></h3>
								<p><?php echo esc_html( DWM_Features::get_category_description( $category_name ) ); ?></p>
							</div>

							<div class="dwm-features-grid">
								<?php
								foreach ( $category_features as $feature ) :
									$icon           = $feature['icon'] ?? '';
									$title          = $feature['title'] ?? '';
									$description    = $feature['description'] ?? '';
									$features       = $feature['features'] ?? [];
									$docs_page      = $feature['docs_page'] ?? '';
									$plan           = $feature['plan'] ?? 'free';
									$is_implemented = ! empty( $feature['implemented'] );
									$is_pro         = 'pro' === $plan;
									$badge          = ! $is_implemented ? 'primary' : '';
									$badge_text     = '';

									include DWM_PLUGIN_DIR . 'templates/admin/partials/feature-card.php';
									unset( $icon, $title, $description, $features, $docs_page, $badge, $badge_text, $is_pro, $is_implemented, $plan );
								endforeach;
								?>
							</div>
						</div>
						<?php
						$first = false;
					endforeach;
					?>

					<!-- Integrations Page -->
					<div class="dwm-sidebar-modal-page" data-dwm-features-page-content="integrations">
						<div class="dwm-sidebar-modal-page-header">
							<h3><?php echo esc_html( $integrations_meta['label'] ); ?></h3>
							<p><?php echo esc_html( $integrations_meta['description'] ); ?></p>
						</div>

						<div class="dwm-integrations-grid-wrapper">
							<?php foreach ( $integrations as $category_name => $items ) : ?>
								<div class="dwm-integration-group">
									<h4 class="dwm-feature-group-title"><?php echo esc_html( $category_name ); ?></h4>
									<div class="dwm-integrations-grid">
										<?php foreach ( $items as $integration ) : ?>
											<div class="dwm-integration-card<?php echo ! empty( $integration['implemented'] ) ? ' is-available' : ''; ?>">
												<div class="dwm-integration-header">
													<div class="dwm-integration-icon">
														<?php if ( ! empty( $integration['image'] ) ) : ?>
															<img src="<?php echo esc_url( $integration['image'] ); ?>" alt="<?php echo esc_attr( $integration['title'] ); ?>" loading="lazy">
														<?php elseif ( ! empty( $integration['icon'] ) ) : ?>
															<?php echo esc_html( $integration['icon'] ); ?>
														<?php endif; ?>
													</div>
												</div>
												<h4><?php echo esc_html( $integration['title'] ); ?></h4>
												<?php if ( ! empty( $integration['description'] ) ) : ?>
													<p class="dwm-integration-description"><?php echo esc_html( $integration['description'] ); ?></p>
												<?php endif; ?>
												<?php if ( ! empty( $integration['features'] ) ) : ?>
													<ul class="dwm-integration-features">
														<?php foreach ( $integration['features'] as $int_feature ) : ?>
															<li><?php echo esc_html( $int_feature ); ?></li>
														<?php endforeach; ?>
													</ul>
												<?php endif; ?>
												<?php if ( ! empty( $integration['docs_page'] ) ) : ?>
													<div class="dwm-integration-learn-more">
														<button type="button" class="dwm-learn-more-button" data-docs-page="<?php echo esc_attr( $integration['docs_page'] ); ?>">
															<?php esc_html_e( 'Learn More', 'dashboard-widget-manager' ); ?>
														</button>
													</div>
												<?php endif; ?>
												<div class="dwm-integration-footer">
													<?php if ( empty( $integration['implemented'] ) ) : ?>
														<span class="dwm-badge dwm-badge-primary dwm-pulse-primary"><?php echo esc_html( $labels['badge_coming_soon'] ); ?></span>
													<?php else : ?>
														<span class="dwm-badge dwm-badge-success"><?php echo esc_html( $labels['badge_available'] ); ?></span>
													<?php endif; ?>
												</div>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
(function() {
	document.addEventListener('click', function(e) {
		var menuLink = e.target.closest('[data-dwm-features-page]');
		if (!menuLink) return;

		var page  = menuLink.dataset.dwmFeaturesPage;
		var modal = menuLink.closest('.dwm-sidebar-modal-layout');
		if (!modal) return;

		modal.querySelectorAll('[data-dwm-features-page]').forEach(function(link) {
			link.classList.remove('is-active');
		});
		menuLink.classList.add('is-active');

		modal.querySelectorAll('[data-dwm-features-page-content]').forEach(function(content) {
			content.classList.remove('is-active');
		});
		var targetContent = modal.querySelector('[data-dwm-features-page-content="' + page + '"]');
		if (targetContent) {
			targetContent.classList.add('is-active');
		}
	});

	// Learn More button handler
	document.addEventListener('click', function(e) {
		var btn = e.target.closest('.dwm-learn-more-button');
		if (!btn) return;

		var docsPage = btn.dataset.docsPage;
		if (!docsPage || !window.DWMDocsModal) return;

		var $link = jQuery('[data-docs-page="' + docsPage + '"]').first();

		// Set page state BEFORE opening modal (prevents flash)
		window.DWMDocsModal.collapseAllAccordions();
		window.DWMDocsModal.showPage(docsPage);
		window.DWMDocsModal.setActiveLink($link.length ? $link : jQuery('.dwm-docs-welcome-link'));

		// Close features modal, open docs modal
		jQuery('.dwm-modal').removeClass('active');
		jQuery('#dwm-docs-modal').addClass('active');
		jQuery('body').addClass('dwm-modal-open');
	});
})();
</script>
