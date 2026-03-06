<?php
/**
 * Admin Modal Template - Docs Modal
 *
 * Handles markup rendering for the docs modal admin modal template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$all_features      = DWM_Features::get_all_features();
$categories        = DWM_Features::get_categories();
$integrations      = DWM_Features::get_all_integrations();
$integrations_meta = DWM_Features::get_integrations_meta();
$is_pro_enabled    = class_exists( 'DWM_Pro_Feature_Gate' ) && DWM_Pro_Feature_Gate::is_pro_enabled();
$docs_hero_img     = esc_url( DWM_PLUGIN_URL . 'assets/images/logo.png' );
$docs_plugin_name  = 'Dashboard Widget Manager';
?>
<div id="dwm-docs-modal" class="dwm-modal dwm-docs-modal" role="dialog" aria-modal="true">
	<div class="dwm-modal-overlay"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2><span class="dashicons dashicons-book-alt"></span> <?php esc_html_e( 'Documentation for Dashboard Widget Manager', 'dashboard-widget-manager' ); ?></h2>
			<button type="button" class="dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<div class="dwm-docs-layout dwm-sidebar-modal-layout">

				<!-- Sidebar -->
				<div class="dwm-docs-sidebar dwm-sidebar-modal-sidebar">
					<!-- Filter Controls -->
					<div class="dwm-docs-filter-controls">
						<div class="dwm-docs-filter-row">
							<span class="dwm-docs-plan-filters" data-dwm-docs-plan-filters>
								<button type="button" class="dwm-docs-plan-filter is-active" data-dwm-docs-plan-filter="all"><?php esc_html_e( 'All', 'dashboard-widget-manager' ); ?></button>
								<span class="dwm-docs-plan-separator">|</span>
								<button type="button" class="dwm-docs-plan-filter" data-dwm-docs-plan-filter="free"><?php esc_html_e( 'Free', 'dashboard-widget-manager' ); ?></button>
								<span class="dwm-docs-plan-separator">|</span>
								<button type="button" class="dwm-docs-plan-filter" data-dwm-docs-plan-filter="pro"><?php esc_html_e( 'Pro', 'dashboard-widget-manager' ); ?></button>
							</span>
							<label class="dwm-docs-soon-toggle">
								<span class="dwm-docs-soon-label"><?php esc_html_e( 'Soon', 'dashboard-widget-manager' ); ?></span>
								<span class="dwm-toggle dwm-toggle--small">
									<input type="checkbox" checked data-dwm-docs-soon-toggle />
									<span class="dwm-toggle-track dwm-toggle-track-small" aria-hidden="true">
										<span class="dwm-toggle-thumb dwm-toggle-thumb-small"></span>
									</span>
								</span>
							</label>
						</div>
					</div>

					<div class="dwm-docs-search">
						<input type="search"
							class="dwm-docs-search-input"
							data-docs-search
							placeholder="<?php esc_attr_e( 'Search documentation...', 'dashboard-widget-manager' ); ?>"
						>
					</div>

					<nav class="dwm-docs-nav">

						<!-- Welcome link -->
						<button type="button"
							class="dwm-docs-welcome-link is-active"
							data-docs-page="welcome"
						>
							<span class="dashicons dashicons-welcome-learn-more"></span>
							<?php esc_html_e( 'Welcome', 'dashboard-widget-manager' ); ?>
						</button>

						<!-- Category accordions -->
						<ul class="dwm-docs-accordion" data-docs-accordion>
							<?php foreach ( $all_features as $category_name => $features ) :
								$category_icon = DWM_Features::get_category_icon( $category_name );
								$category_slug = sanitize_title( $category_name );
								$category_meta = $categories[ $category_name ] ?? [];
							?>
							<li class="dwm-docs-accordion-item" data-search-title="<?php echo esc_attr( strtolower( $category_name ) ); ?>">
								<button type="button"
									class="dwm-docs-accordion-trigger"
									aria-expanded="false"
								>
									<span class="dwm-docs-accordion-trigger-text">
										<span class="dashicons dashicons-<?php echo esc_attr( $category_icon ); ?>"></span>
										<?php echo esc_html( $category_name ); ?>
									</span>
									<span class="dwm-docs-accordion-icon">
										<span class="dashicons dashicons-arrow-down-alt2"></span>
									</span>
								</button>
								<div class="dwm-docs-accordion-panel" hidden>
									<ul class="dwm-docs-submenu">
										<?php if ( ! ( $category_meta['use_feature_overview'] ?? false ) ) : ?>
										<li class="dwm-docs-submenu-item" data-search-title="<?php echo esc_attr( strtolower( $category_name . ' overview' ) ); ?>">
											<button type="button"
												class="dwm-docs-submenu-link"
												data-docs-page="<?php echo esc_attr( 'category-overview-' . $category_slug ); ?>"
											>
												<?php echo esc_html( $category_name . ' Overview' ); ?>
											</button>
										</li>
										<?php endif; ?>
										<?php foreach ( $features as $feature ) :
											$is_pro     = 'pro' === ( $feature['plan'] ?? 'free' );
											$is_coming  = ! ( $feature['implemented'] ?? true );
										?>
										<li class="dwm-docs-submenu-item" data-search-title="<?php echo esc_attr( strtolower( $feature['title'] ) ); ?>" data-docs-plan="<?php echo esc_attr( $feature['plan'] ?? 'free' ); ?>" data-docs-implemented="<?php echo empty( $feature['implemented'] ) ? '0' : '1'; ?>">
											<button type="button"
												class="dwm-docs-submenu-link"
												data-docs-page="<?php echo esc_attr( $feature['docs_page'] ); ?>"
											>
												<?php echo esc_html( $feature['title'] ); ?>
												<?php if ( $is_coming ) : ?>
													<span class="dwm-docs-coming-badge"><?php esc_html_e( 'Soon', 'dashboard-widget-manager' ); ?></span>
												<?php elseif ( $is_pro && 'security' !== $category_slug ) : ?>
													<span class="dwm-docs-pro-badge dwm-docs-accordion-badge"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
												<?php endif; ?>
											</button>
										</li>
										<?php endforeach; ?>
									</ul>
								</div>
							</li>
							<?php endforeach; ?>
						</ul>

						<?php if ( ! empty( $integrations ) ) : ?>
						<!-- Integrations accordion -->
						<ul class="dwm-docs-accordion" data-docs-accordion>
							<li class="dwm-docs-accordion-item" data-search-title="integrations">
								<button type="button"
									class="dwm-docs-accordion-trigger"
									aria-expanded="false"
								>
									<span class="dwm-docs-accordion-trigger-text">
										<span class="dashicons dashicons-<?php echo esc_attr( $integrations_meta['icon'] ?? 'admin-plugins' ); ?>"></span>
										<?php echo esc_html( $integrations_meta['label'] ?? __( 'Integrations', 'dashboard-widget-manager' ) ); ?>
										<span class="dwm-docs-pro-badge dwm-docs-accordion-badge"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
									</span>
									<span class="dwm-docs-accordion-icon">
										<span class="dashicons dashicons-arrow-down-alt2"></span>
									</span>
								</button>
								<div class="dwm-docs-accordion-panel" hidden>
									<ul class="dwm-docs-submenu">
										<li class="dwm-docs-submenu-item" data-search-title="integrations overview">
											<button type="button"
												class="dwm-docs-submenu-link"
												data-docs-page="integrations-overview"
											>
												<span class="dashicons dashicons-info-outline" style="font-size:16px;width:16px;height:16px;margin-right:8px;color:#667eea;"></span>
												<?php esc_html_e( 'Integrations Overview', 'dashboard-widget-manager' ); ?>
											</button>
										</li>
										<?php foreach ( $integrations as $int_category_name => $int_items ) : ?>
										<li class="dwm-docs-submenu-item" data-search-title="<?php echo esc_attr( strtolower( $int_category_name ) ); ?>">
											<span class="dwm-docs-submenu-category-label"><?php echo esc_html( $int_category_name ); ?></span>
										</li>
										<?php foreach ( $int_items as $integration ) : ?>
										<li class="dwm-docs-submenu-item" data-search-title="<?php echo esc_attr( strtolower( $integration['title'] ) ); ?>" data-docs-plan="pro" data-docs-implemented="<?php echo empty( $integration['implemented'] ) ? '0' : '1'; ?>">
											<button type="button"
												class="dwm-docs-submenu-link"
												data-docs-page="<?php echo esc_attr( $integration['docs_page'] ); ?>"
											>
												<?php if ( ! empty( $integration['image'] ) ) : ?>
													<img src="<?php echo esc_url( $integration['image'] ); ?>" alt="" style="width:16px;height:16px;vertical-align:middle;margin-right:8px;">
												<?php endif; ?>
												<?php echo esc_html( $integration['title'] ); ?>
												<?php if ( empty( $integration['implemented'] ) ) : ?>
													<span class="dwm-docs-coming-badge"><?php esc_html_e( 'Soon', 'dashboard-widget-manager' ); ?></span>
												<?php endif; ?>
											</button>
										</li>
										<?php endforeach; ?>
										<?php endforeach; ?>
									</ul>
								</div>
							</li>
						</ul>
						<?php endif; ?>
					</nav>
				</div>

				<!-- Sidebar Toggle -->
				<button type="button" class="dwm-docs-sidebar-toggle dwm-features-sidebar-toggle" data-dwm-docs-sidebar-toggle aria-label="<?php esc_attr_e( 'Toggle sidebar', 'dashboard-widget-manager' ); ?>">
					<span class="dashicons dashicons-arrow-left-alt2"></span>
				</button>

				<!-- Content area -->
				<div class="dwm-docs-content dwm-sidebar-modal-content" data-docs-content>
					<div class="dwm-sidebar-modal-sticky-header" data-dwm-docs-sticky-header>
						<div class="dwm-sidebar-modal-sticky-header-left">
							<div class="dwm-sidebar-modal-sticky-title">
								<span class="dwm-sidebar-modal-sticky-icon" data-dwm-docs-sticky-icon></span>
								<div class="dwm-sidebar-modal-sticky-title-text">
									<h3 data-dwm-docs-sticky-title><?php esc_html_e( 'Welcome', 'dashboard-widget-manager' ); ?></h3>
								</div>
								<span class="dwm-sidebar-modal-sticky-badge" data-dwm-docs-sticky-badge></span>
							</div>
						</div>
						<div class="dwm-sidebar-modal-sticky-header-right">
							<div class="dwm-sidebar-modal-page-nav" data-dwm-docs-page-nav>
								<button type="button" class="dwm-sidebar-modal-nav-btn is-prev" data-dwm-docs-nav-direction="prev" aria-label="<?php esc_attr_e( 'Previous page', 'dashboard-widget-manager' ); ?>">
									<span class="dashicons dashicons-arrow-left-alt2"></span>
									<span class="dwm-nav-label" data-dwm-docs-prev-label><?php esc_html_e( 'Prev', 'dashboard-widget-manager' ); ?></span>
								</button>
								<button type="button" class="dwm-sidebar-modal-nav-btn is-next" data-dwm-docs-nav-direction="next" aria-label="<?php esc_attr_e( 'Next page', 'dashboard-widget-manager' ); ?>">
									<span class="dwm-nav-label" data-dwm-docs-next-label><?php esc_html_e( 'Next', 'dashboard-widget-manager' ); ?></span>
									<span class="dashicons dashicons-arrow-right-alt2"></span>
								</button>
								<button type="button" class="dwm-features-collapsed-search dwm-docs-collapsed-search" aria-label="<?php esc_attr_e( 'Search documentation', 'dashboard-widget-manager' ); ?>">
									<span class="dashicons dashicons-search"></span>
								</button>
							</div>
						</div>
					</div>

					<!-- Welcome page -->
					<div class="dwm-docs-page is-active" data-docs-page-content="welcome">
						<div class="dwm-overview-hero">
							<img src="<?php echo $docs_hero_img; ?>" alt="<?php echo esc_attr( $docs_plugin_name ); ?>" class="dwm-overview-hero-logo">
							<h2 class="dwm-overview-hero-title"><?php esc_html_e( 'Your Data Intelligence Hub', 'dashboard-widget-manager' ); ?></h2>
							<p class="dwm-overview-hero-subtitle"><?php esc_html_e( 'Query anything. Visualize everything. Own your dashboard.', 'dashboard-widget-manager' ); ?></p>
							<p class="dwm-overview-hero-desc"><?php esc_html_e( 'Dashboard Widget Manager turns your WordPress admin into a real-time data command center with visual builder workflows, flexible display modes, and secure query controls.', 'dashboard-widget-manager' ); ?></p>
						</div>

						<div class="dwm-docs-section-block">
							<h4><?php esc_html_e( 'Support Shortcuts', 'dashboard-widget-manager' ); ?></h4>
							<div class="dwm-docs-mode-grid">
								<button type="button" class="dwm-docs-mode-card" data-open-modal="dwm-features-modal">
									<span class="dashicons dashicons-awards"></span>
									<span class="dwm-docs-mode-card-body">
										<strong><?php esc_html_e( 'Features', 'dashboard-widget-manager' ); ?></strong>
										<span><?php esc_html_e( 'Browse feature categories and capabilities.', 'dashboard-widget-manager' ); ?></span>
									</span>
								</button>
								<button type="button" class="dwm-docs-mode-card" data-dwm-open-notifications>
									<span class="dashicons dashicons-megaphone"></span>
									<span class="dwm-docs-mode-card-body">
										<strong><?php esc_html_e( 'Notifications', 'dashboard-widget-manager' ); ?></strong>
										<span><?php esc_html_e( 'Open your notifications inbox.', 'dashboard-widget-manager' ); ?></span>
									</span>
								</button>
								<button type="button" class="dwm-docs-mode-card" data-open-modal="dwm-new-ticket-modal">
									<span class="dashicons dashicons-phone"></span>
									<span class="dwm-docs-mode-card-body">
										<strong><?php esc_html_e( 'Support', 'dashboard-widget-manager' ); ?></strong>
										<span><?php esc_html_e( 'Create a support ticket.', 'dashboard-widget-manager' ); ?></span>
									</span>
								</button>
							</div>
						</div>

						<?php if ( ! $is_pro_enabled ) : ?>
							<?php include DWM_PLUGIN_DIR . 'templates/admin/partials/pro-upsell-footer.php'; ?>
						<?php endif; ?>

						<!-- Plugin Ecosystem -->
						<div class="dwm-overview-ecosystem  dwm-docs-welcome-ecosystem">
							<div class="dwm-overview-ecosystem-header">
								<span class="dwm-overview-ecosystem-eyebrow"><?php esc_html_e( 'TopDevAmerica Suite', 'dashboard-widget-manager' ); ?></span>
								<h3><?php esc_html_e( 'Build an Unstoppable Stack', 'dashboard-widget-manager' ); ?></h3>
								<p><?php esc_html_e( 'Dashboard Widget Manager is powerful alone — but pair it with our other plugins and your WordPress site becomes a fully loaded command center.', 'dashboard-widget-manager' ); ?></p>
							</div>
							<div class="dwm-overview-ecosystem-grid">
								<?php
								$docs_plugins_img_url   = DWM_PLUGIN_URL . 'assets/images/plugins/';
								$docs_ecosystem_plugins = [
									[
										'logo'    => $docs_plugins_img_url . 'cta-manager-logo.png',
										'title'   => __( 'CTA Manager', 'dashboard-widget-manager' ),
										'tagline' => __( 'Convert More Visitors', 'dashboard-widget-manager' ),
										'desc'    => __( 'Create, manage, and track conversion-focused calls-to-action with targeting rules, A/B testing, and real-time analytics.', 'dashboard-widget-manager' ),
										'url'     => 'https://topdevamerica.com/plugins/cta-manager',
										'accent'  => 'orange',
										'combo'   => __( 'Track CTA performance right on your dashboard with custom widgets.', 'dashboard-widget-manager' ),
									],
									[
										'logo'    => $docs_plugins_img_url . 'dashboard-widget-manager-logo.png',
										'title'   => __( 'Dashboard Widget Manager', 'dashboard-widget-manager' ),
										'tagline' => __( 'You Are Here', 'dashboard-widget-manager' ),
										'desc'    => __( 'Build custom dashboard widgets with SQL queries, visual builder, chart support, and flexible caching.', 'dashboard-widget-manager' ),
										'url'     => '',
										'accent'  => 'purple',
										'combo'   => __( 'The engine that powers your entire data intelligence layer.', 'dashboard-widget-manager' ),
									],
									[
										'logo'    => $docs_plugins_img_url . 'ai-chat-manager-logo.png',
										'title'   => __( 'AI Chat Manager', 'dashboard-widget-manager' ),
										'tagline' => __( 'AI-Powered Chat', 'dashboard-widget-manager' ),
										'desc'    => __( 'Add a fully customizable AI chat assistant to your WordPress site, powered by Claude or OpenAI.', 'dashboard-widget-manager' ),
										'url'     => '',
										'accent'  => 'teal',
										'combo'   => __( 'Surface widget insights through natural language conversations.', 'dashboard-widget-manager' ),
									],
								];
								foreach ( $docs_ecosystem_plugins as $ep ) :
								?>
									<div class="dwm-overview-ecosystem-card dwm-overview-ecosystem-card--<?php echo esc_attr( $ep['accent'] ); ?>">
										<div class="dwm-overview-ecosystem-card-glow"></div>
										<div class="dwm-overview-ecosystem-card-top">
											<div class="dwm-overview-ecosystem-card-logo">
												<img src="<?php echo esc_url( $ep['logo'] ); ?>" alt="<?php echo esc_attr( $ep['title'] ); ?>" width="56" height="56" loading="lazy">
											</div>
											<div class="dwm-overview-ecosystem-card-title">
												<span class="dwm-overview-ecosystem-card-tagline"><?php echo esc_html( $ep['tagline'] ); ?></span>
												<h5><?php echo esc_html( $ep['title'] ); ?></h5>
											</div>
										</div>
										<p class="dwm-overview-ecosystem-card-desc"><?php echo esc_html( $ep['desc'] ); ?></p>
										<div class="dwm-overview-ecosystem-card-combo">
											<span>+</span>
											<span><?php echo esc_html( $ep['combo'] ); ?></span>
										</div>
										<?php if ( ! empty( $ep['url'] ) ) : ?>
											<div class="dwm-overview-ecosystem-card-footer">
												<a href="<?php echo esc_url( $ep['url'] ); ?>" class="dwm-overview-ecosystem-card-link" target="_blank" rel="noopener noreferrer">
													<?php esc_html_e( 'Learn More', 'dashboard-widget-manager' ); ?>
													<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
												</a>
											</div>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
							<div class="dwm-overview-ecosystem-tagline">
								<p><?php esc_html_e( 'All three plugins. One ecosystem. Zero limits.', 'dashboard-widget-manager' ); ?></p>
							</div>
						</div>

						<div class="dwm-docs-welcome-features">
							<div class="dwm-docs-welcome-feature">
								<span class="dwm-docs-welcome-feature-icon" aria-hidden="true">🗄️</span>
								<div>
									<strong><?php esc_html_e( 'SQL Query Engine', 'dashboard-widget-manager' ); ?></strong>
									<span><?php esc_html_e( 'Write SELECT queries with built-in variable support and validation.', 'dashboard-widget-manager' ); ?></span>
								</div>
							</div>
							<div class="dwm-docs-welcome-feature">
								<span class="dwm-docs-welcome-feature-icon" aria-hidden="true">🧩</span>
								<div>
									<strong><?php esc_html_e( 'HTML/PHP Templates', 'dashboard-widget-manager' ); ?></strong>
									<span><?php esc_html_e( 'Output query results using flexible HTML and PHP widget templates.', 'dashboard-widget-manager' ); ?></span>
								</div>
							</div>
							<div class="dwm-docs-welcome-feature">
								<span class="dwm-docs-welcome-feature-icon" aria-hidden="true">🎨</span>
								<div>
									<strong><?php esc_html_e( 'Custom CSS & JS', 'dashboard-widget-manager' ); ?></strong>
									<span><?php esc_html_e( 'Add scoped styles and scripts that load only with each widget.', 'dashboard-widget-manager' ); ?></span>
								</div>
							</div>
							<div class="dwm-docs-welcome-feature">
								<span class="dwm-docs-welcome-feature-icon" aria-hidden="true">🛡️</span>
								<div>
									<strong><?php esc_html_e( 'Built-In Security', 'dashboard-widget-manager' ); ?></strong>
									<span><?php esc_html_e( 'SELECT-only enforcement, table allowlists, and capability checks.', 'dashboard-widget-manager' ); ?></span>
								</div>
							</div>
						</div>
					</div>

					<!-- Category overview pages -->
				<?php foreach ( $all_features as $category_name => $features ) :
					$category_slug = sanitize_title( $category_name );
					$category_meta = $categories[ $category_name ] ?? [];
					if ( $category_meta['use_feature_overview'] ?? false ) {
						continue;
					}
				?>
				<div class="dwm-docs-page" data-docs-page-content="<?php echo esc_attr( 'category-overview-' . $category_slug ); ?>">
					<div class="dwm-docs-section">
						<div class="dwm-docs-section-title-wrapper">
							<span class="dashicons dashicons-<?php echo esc_attr( DWM_Features::get_category_icon( $category_name ) ); ?>"></span>
							<h2 class="dwm-docs-section-title">
								<?php echo esc_html( sprintf( __( '%s Overview', 'dashboard-widget-manager' ), $category_name ) ); ?>
							</h2>
						</div>
						<p class="dwm-docs-section-description">
							<?php echo esc_html( $category_meta['description'] ?? '' ); ?>
						</p>

						<div class="dwm-docs-section-block">
							<h4><?php echo esc_html( $category_name ); ?></h4>
							<div class="dwm-docs-mode-grid">
								<?php foreach ( $features as $feature ) :
									$is_pro    = 'pro' === ( $feature['plan'] ?? 'free' );
									$is_coming = ! ( $feature['implemented'] ?? true );
								?>
								<button type="button" class="dwm-docs-mode-card" data-docs-page="<?php echo esc_attr( $feature['docs_page'] ); ?>">
									<span class="dwm-docs-title-icon"><?php echo esc_html( $feature['icon'] ?? '' ); ?></span>
									<span class="dwm-docs-mode-card-body">
										<strong>
											<?php echo esc_html( $feature['title'] ); ?>
											<?php if ( $is_pro ) : ?>
												<span class="dwm-docs-pro-badge dwm-docs-card-badge"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
											<?php endif; ?>
											<?php if ( $is_coming ) : ?>
												<span class="dwm-docs-coming-badge dwm-docs-card-badge"><?php esc_html_e( 'Soon', 'dashboard-widget-manager' ); ?></span>
											<?php endif; ?>
										</strong>
										<span><?php echo esc_html( $feature['description'] ?? '' ); ?></span>
									</span>
								</button>
								<?php endforeach; ?>
							</div>
						</div>

						<?php if ( ! empty( $category_meta['features'] ) ) : ?>
						<div class="dwm-docs-feature-list">
							<h4><?php esc_html_e( 'Key Features', 'dashboard-widget-manager' ); ?></h4>
							<ul>
								<?php foreach ( $category_meta['features'] as $feat_item ) : ?>
									<li><?php echo esc_html( $feat_item ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
						<?php endif; ?>

						<?php if ( ! empty( $category_meta['details'] ) ) : ?>
						<div class="dwm-docs-section-block">
							<h4><?php esc_html_e( 'What this does', 'dashboard-widget-manager' ); ?></h4>
							<p><?php echo esc_html( $category_meta['details'] ); ?></p>
						</div>
						<?php endif; ?>

						<?php if ( ! empty( $category_meta['instructions'] ) && is_array( $category_meta['instructions'] ) ) : ?>
						<div class="dwm-docs-section-block">
							<h4><?php esc_html_e( 'How to configure & use it', 'dashboard-widget-manager' ); ?></h4>
							<ol class="dwm-docs-steps">
								<?php foreach ( $category_meta['instructions'] as $step ) : ?>
									<li><?php echo esc_html( $step ); ?></li>
								<?php endforeach; ?>
							</ol>
						</div>
						<?php endif; ?>

					</div>
				</div>
				<?php endforeach; ?>

					<!-- Feature pages (generated from features data) -->
					<?php foreach ( $all_features as $category_name => $features ) :
						foreach ( $features as $feature ) :
							$is_pro          = 'pro' === ( $feature['plan'] ?? 'free' );
							$is_coming       = ! ( $feature['implemented'] ?? true );
							$is_cat_overview = ! empty( $feature['category_overview'] );
					?>
					<div class="dwm-docs-page" data-docs-page-content="<?php echo esc_attr( $feature['docs_page'] ); ?>">
						<div class="dwm-docs-section">
							<div class="dwm-docs-section-title-wrapper">
								<?php if ( ! empty( $feature['icon'] ) ) : ?>
									<span class="dwm-docs-title-icon"><?php echo esc_html( $feature['icon'] ); ?></span>
								<?php endif; ?>
								<h2 class="dwm-docs-section-title">
									<?php echo esc_html( $feature['title'] ); ?>
								</h2>
								<?php if ( $is_pro ) : ?>
									<span class="dwm-docs-pro-badge dwm-docs-page-title-badge"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
								<?php endif; ?>
								<?php if ( $is_coming ) : ?>
									<span class="dwm-docs-coming-badge dwm-docs-page-title-badge"><?php esc_html_e( 'Coming Soon', 'dashboard-widget-manager' ); ?></span>
								<?php endif; ?>
							</div>
							<p class="dwm-docs-section-description"><?php echo esc_html( $feature['description'] ?? '' ); ?></p>

							<?php if ( $is_cat_overview && ! empty( $feature['overview_links'] ) && is_array( $feature['overview_links'] ) ) : ?>
							<div class="dwm-docs-section-block">
								<h4><?php echo esc_html( $category_name ); ?></h4>
								<div class="dwm-docs-mode-grid">
									<?php foreach ( $feature['overview_links'] as $mode ) : ?>
									<button type="button" class="dwm-docs-mode-card" data-docs-page="<?php echo esc_attr( $mode['page'] ); ?>">
										<span class="dashicons dashicons-<?php echo esc_attr( $mode['icon'] ); ?>"></span>
										<span class="dwm-docs-mode-card-body">
											<strong><?php echo esc_html( $mode['title'] ); ?></strong>
											<span><?php echo esc_html( $mode['description'] ); ?></span>
										</span>
									</button>
									<?php endforeach; ?>
								</div>
							</div>
							<?php endif; ?>

							<?php if ( ! empty( $feature['features'] ) ) : ?>
							<div class="dwm-docs-feature-list">
								<h4><?php esc_html_e( 'Key Features', 'dashboard-widget-manager' ); ?></h4>
								<ul>
									<?php foreach ( $feature['features'] as $item ) : ?>
										<li><?php echo esc_html( $item ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
							<?php endif; ?>

						<?php if ( ! empty( $feature['details'] ) ) : ?>
						<div class="dwm-docs-section-block">
							<h4><?php esc_html_e( 'What this does', 'dashboard-widget-manager' ); ?></h4>
							<p><?php echo esc_html( $feature['details'] ); ?></p>
						</div>
						<?php endif; ?>

						<?php if ( ! empty( $feature['instructions'] ) && is_array( $feature['instructions'] ) ) : ?>
						<div class="dwm-docs-section-block">
							<h4><?php esc_html_e( 'How to configure & use it', 'dashboard-widget-manager' ); ?></h4>
							<ol class="dwm-docs-steps">
								<?php foreach ( $feature['instructions'] as $step ) : ?>
									<li><?php echo esc_html( $step ); ?></li>
								<?php endforeach; ?>
							</ol>
						</div>
						<?php endif; ?>

						<?php if ( ! $is_cat_overview && ! empty( $feature['overview_links'] ) && is_array( $feature['overview_links'] ) ) : ?>
						<div class="dwm-docs-section-block">
							<h4><?php esc_html_e( 'Related Documentation', 'dashboard-widget-manager' ); ?></h4>
							<div class="dwm-docs-mode-grid">
								<?php foreach ( $feature['overview_links'] as $mode ) : ?>
								<button type="button" class="dwm-docs-mode-card" data-docs-page="<?php echo esc_attr( $mode['page'] ); ?>">
									<span class="dashicons dashicons-<?php echo esc_attr( $mode['icon'] ); ?>"></span>
									<span class="dwm-docs-mode-card-body">
										<strong><?php echo esc_html( $mode['title'] ); ?></strong>
										<span><?php echo esc_html( $mode['description'] ); ?></span>
									</span>
								</button>
								<?php endforeach; ?>
							</div>
						</div>
						<?php endif; ?>
						</div>
					</div>
					<?php
						endforeach;
					endforeach;
					?>


					<!-- Integrations Overview page -->
					<div class="dwm-docs-page" data-docs-page-content="integrations-overview">
						<div class="dwm-docs-section">
							<div class="dwm-docs-section-title-wrapper">
								<span class="dashicons dashicons-admin-plugins" style="font-size:28px;width:28px;height:28px;color:#667eea;"></span>
								<h2 class="dwm-docs-section-title">
									<?php esc_html_e( 'Integrations Overview', 'dashboard-widget-manager' ); ?>
								</h2>
								<span class="dwm-docs-pro-badge dwm-docs-page-title-badge"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
							</div>
							<p class="dwm-docs-section-description"><?php echo esc_html( $integrations_meta['description'] ?? '' ); ?></p>
							<div class="dwm-docs-detail-block">
								<h4><?php esc_html_e( 'About Integrations', 'dashboard-widget-manager' ); ?></h4>
								<p><?php esc_html_e( 'Dashboard Widget Manager Pro integrations allow you to connect your WordPress dashboard with external services and tools. Each integration adds specialized widgets and data sources that pull real-time information directly into your dashboard.', 'dashboard-widget-manager' ); ?></p>
							</div>
							<div class="dwm-docs-detail-block">
								<h4><?php esc_html_e( 'Available Categories', 'dashboard-widget-manager' ); ?></h4>
								<ul>
									<?php foreach ( $integrations as $cat_name => $cat_items ) : ?>
										<li><strong><?php echo esc_html( $cat_name ); ?></strong> &mdash; <?php echo esc_html( count( $cat_items ) ); ?> <?php echo esc_html( _n( 'integration', 'integrations', count( $cat_items ), 'dashboard-widget-manager' ) ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
							<div class="dwm-docs-detail-block">
								<h4><?php esc_html_e( 'Getting Started', 'dashboard-widget-manager' ); ?></h4>
								<p><?php esc_html_e( 'To use integrations, activate your Pro license and navigate to Settings to configure your integration credentials. Select any integration from the sidebar to learn more about its specific features and setup instructions.', 'dashboard-widget-manager' ); ?></p>
							</div>
						</div>
					</div>

					<!-- Integration pages -->
					<?php foreach ( $integrations as $int_category_name => $int_items ) : ?>
						<?php foreach ( $int_items as $integration ) : ?>
					<div class="dwm-docs-page" data-docs-page-content="<?php echo esc_attr( $integration['docs_page'] ); ?>">
						<div class="dwm-docs-section">
							<div class="dwm-docs-section-title-wrapper">
								<?php if ( ! empty( $integration['image'] ) ) : ?>
									<img src="<?php echo esc_url( $integration['image'] ); ?>" alt="" style="width:28px;height:28px;">
								<?php elseif ( ! empty( $integration['icon'] ) ) : ?>
									<span class="dwm-docs-title-icon"><?php echo esc_html( $integration['icon'] ); ?></span>
								<?php endif; ?>
								<h2 class="dwm-docs-section-title">
									<?php echo esc_html( $integration['title'] ); ?>
								</h2>
								<?php if ( 'pro' === ( $integration['plan'] ?? '' ) ) : ?>
									<span class="dwm-docs-pro-badge dwm-docs-page-title-badge"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
								<?php endif; ?>
								<?php if ( empty( $integration['implemented'] ) ) : ?>
									<span class="dwm-docs-coming-badge dwm-docs-page-title-badge"><?php esc_html_e( 'Coming Soon', 'dashboard-widget-manager' ); ?></span>
								<?php endif; ?>
							</div>
							<?php if ( ! empty( $integration['description'] ) ) : ?>
								<p class="dwm-docs-section-description"><?php echo esc_html( $integration['description'] ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $integration['features'] ) && is_array( $integration['features'] ) ) : ?>
							<div class="dwm-docs-feature-list">
								<h4><?php esc_html_e( 'Key Features', 'dashboard-widget-manager' ); ?></h4>
								<ul>
									<?php foreach ( $integration['features'] as $item ) : ?>
										<li><?php echo esc_html( $item ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
							<?php endif; ?>
							<?php if ( ! empty( $integration['details'] ) ) : ?>
							<div class="dwm-docs-detail-block">
								<h4><?php esc_html_e( 'Detailed Explanation', 'dashboard-widget-manager' ); ?></h4>
								<p><?php echo esc_html( $integration['details'] ); ?></p>
							</div>
							<?php endif; ?>
							<?php if ( ! empty( $integration['instructions'] ) && is_array( $integration['instructions'] ) ) : ?>
							<div class="dwm-docs-detail-block">
								<h4><?php esc_html_e( 'How to Use', 'dashboard-widget-manager' ); ?></h4>
								<ol>
									<?php foreach ( $integration['instructions'] as $step ) : ?>
										<li><?php echo esc_html( $step ); ?></li>
									<?php endforeach; ?>
								</ol>
							</div>
							<?php endif; ?>
						</div>
					</div>
						<?php endforeach; ?>
					<?php endforeach; ?>

				</div>
			</div>
		</div>
	</div>
</div>

<script>
(function() {
	function closeDocsModal() {
		if (typeof window.closeModal === 'function') {
			window.closeModal('dwm-docs-modal');
			return;
		}

		const docsModal = document.querySelector('#dwm-docs-modal');
		if (docsModal) {
			docsModal.classList.remove('active');
			document.body.classList.remove('dwm-modal-open');
		}
	}

	function openTargetModal(target, triggerEl) {
		if (!target || target === 'dwm-docs-modal') {
			return;
		}

		if (window.dwmModalAPI && typeof window.dwmModalAPI.open === 'function') {
			window.dwmModalAPI.open(target, { trigger: triggerEl });
			return;
		}

		if (typeof window.openModal === 'function') {
			window.openModal(target);
			return;
		}

		const normalized = target.charAt(0) === '#' ? target : '#' + target;
		const modalEl = document.querySelector(normalized);
		if (modalEl) {
			modalEl.classList.add('active');
			document.body.classList.add('dwm-modal-open');
		}
	}

	document.addEventListener('click', function(e) {
		const modalShortcut = e.target.closest('#dwm-docs-modal [data-open-modal]');
		if (modalShortcut) {
			const targetModal = modalShortcut.getAttribute('data-open-modal');
			if (targetModal && targetModal !== 'dwm-docs-modal') {
				e.preventDefault();
				openTargetModal(targetModal, modalShortcut);
				return;
			}
		}

		const notificationsShortcut = e.target.closest('[data-dwm-open-notifications]');
		if (!notificationsShortcut) {
			return;
		}

		e.preventDefault();
		closeDocsModal();
		setTimeout(function() {
			const notificationsButton = document.querySelector('.dwm-support-toolbar .dwm-notification-button') || document.querySelector('.dwm-notification-button');
			if (notificationsButton) {
				notificationsButton.click();
			}
		}, 180);
	});
})();
</script>
