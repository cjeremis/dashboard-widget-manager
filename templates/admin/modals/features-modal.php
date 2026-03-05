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
$user_has_pro      = class_exists( 'DWM_Pro_Feature_Gate' ) && DWM_Pro_Feature_Gate::is_pro_enabled();
$hero_img          = esc_url( DWM_PLUGIN_URL . 'assets/images/logo.png' );
$hero_plugin_name  = 'Dashboard Widget Manager';

$first_category = array_key_first( $all_features );
?>

<div id="dwm-features-modal" class="dwm-modal dwm-features-modal">
	<div class="dwm-modal-overlay"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons dashicons-awards"></span>
				<?php esc_html_e( 'Dashboard Widget Manager Features', 'dashboard-widget-manager' ); ?>
			</h2>
			<button type="button" class="dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<div class="dwm-sidebar-modal-layout">
				<!-- Sidebar Toggle (docs-modal pattern) -->
				<button type="button" class="dwm-features-sidebar-toggle" data-dwm-features-sidebar-toggle aria-label="<?php esc_attr_e( 'Toggle sidebar', 'dashboard-widget-manager' ); ?>">
					<span class="dashicons dashicons-arrow-left-alt2"></span>
				</button>

				<!-- Sidebar -->
				<div class="dwm-sidebar-modal-sidebar">
					<!-- Filter Controls -->
					<div class="dwm-features-filter-controls">
						<div class="dwm-features-filter-row">
							<span class="dwm-status-filters" data-dwm-features-plan-filters>
								<button type="button" class="dwm-status-filter is-active" data-dwm-features-plan="all"><?php esc_html_e( 'All', 'dashboard-widget-manager' ); ?></button>
								<span class="dwm-status-separator">|</span>
								<button type="button" class="dwm-status-filter" data-dwm-features-plan="free"><?php esc_html_e( 'Free', 'dashboard-widget-manager' ); ?></button>
								<span class="dwm-status-separator">|</span>
								<button type="button" class="dwm-status-filter" data-dwm-features-plan="pro"><?php esc_html_e( 'Pro', 'dashboard-widget-manager' ); ?></button>
							</span>
							<label class="dwm-features-soon-toggle">
								<span class="dwm-features-soon-label"><?php esc_html_e( 'Soon', 'dashboard-widget-manager' ); ?></span>
								<span class="dwm-toggle dwm-toggle--small">
									<input type="checkbox" checked data-dwm-features-soon-toggle />
									<span class="dwm-toggle-track dwm-toggle-track-small" aria-hidden="true">
										<span class="dwm-toggle-thumb dwm-toggle-thumb-small"></span>
									</span>
								</span>
							</label>
						</div>
					</div>
					<!-- Search -->
					<div class="dwm-sidebar-modal-search">
						<div class="dwm-search-wrapper">
							<input type="text" class="dwm-search-input" data-dwm-features-search placeholder="<?php esc_attr_e( 'Search features...', 'dashboard-widget-manager' ); ?>" />
							<button type="button" class="dwm-search-icon" data-dwm-features-search-clear aria-label="<?php esc_attr_e( 'Clear search', 'dashboard-widget-manager' ); ?>">
								<span class="dashicons dashicons-search"></span>
							</button>
						</div>
					</div>
					<nav class="dwm-sidebar-modal-nav">
						<ul class="dwm-sidebar-modal-menu" data-dwm-features-menu>
							<!-- Overview -->
							<li class="dwm-sidebar-modal-menu-item">
								<button
									type="button"
									class="dwm-sidebar-modal-menu-link is-active"
									data-dwm-features-page="overview"
								>
									<span class="dashicons dashicons-dashboard"></span>
									<?php esc_html_e( 'Overview', 'dashboard-widget-manager' ); ?>
								</button>
							</li>
							<?php
							$first = false;
							$is_first_category = true;
							foreach ( $all_features as $category_name => $features ) :
								$category_slug   = sanitize_title( $category_name );
								$icon            = DWM_Features::get_category_icon( $category_name );
								$active_class    = $first ? ' is-active' : '';
								$divider_class   = ( $is_first_category || DWM_Features::has_divider_before( $category_name ) ) ? ' has-divider' : '';
								$is_first_category = false;
								?>
								<li class="dwm-sidebar-modal-menu-item<?php echo esc_attr( $divider_class ); ?>">
									<button
										type="button"
										class="dwm-sidebar-modal-menu-link<?php echo esc_attr( $active_class ); ?>"
										data-dwm-features-page="<?php echo esc_attr( $category_slug ); ?>"
									>
										<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
										<?php echo esc_html( $category_name ); ?>
										<?php if ( in_array( $category_slug, [ 'performance', 'styles-scripts' ], true ) ) : ?>
											<span class="dwm-sidebar-modal-menu-pro-badge"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
										<?php endif; ?>
										<span class="dwm-sidebar-modal-menu-count"><?php echo count( array_filter( $features, function( $f ) { return empty( $f['category_overview'] ); } ) ); ?></span>
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
									<span class="dwm-sidebar-modal-menu-pro-badge"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
									<span class="dwm-sidebar-modal-menu-count"><?php echo array_sum( array_map( 'count', $integrations ) ); ?></span>
								</button>
							</li>
						</ul>
					</nav>
				</div>

				<!-- Content Area -->
				<div class="dwm-sidebar-modal-content" data-dwm-features-content>
					<!-- Sticky Header with Navigation -->
					<div class="dwm-sidebar-modal-sticky-header" data-dwm-features-sticky-header>
						<div class="dwm-sidebar-modal-sticky-header-left">
							<div class="dwm-sidebar-modal-sticky-title">
								<span class="dwm-sidebar-modal-sticky-icon" data-dwm-sticky-icon></span>
								<h3 data-dwm-sticky-title><?php esc_html_e( 'Features', 'dashboard-widget-manager' ); ?></h3>
								<span class="dwm-sidebar-modal-sticky-badge" data-dwm-sticky-badge></span>
							</div>
						</div>
						<div class="dwm-sidebar-modal-sticky-header-right">
							<div class="dwm-sidebar-modal-page-nav" data-dwm-page-nav>
								<button type="button" class="dwm-sidebar-modal-nav-btn is-prev" data-dwm-nav-direction="prev" aria-label="<?php esc_attr_e( 'Previous page', 'dashboard-widget-manager' ); ?>">
									<span class="dashicons dashicons-arrow-left-alt2"></span>
									<span class="dwm-nav-label"><?php esc_html_e( 'Prev', 'dashboard-widget-manager' ); ?></span>
								</button>
								<button type="button" class="dwm-sidebar-modal-nav-btn is-next" data-dwm-nav-direction="next" aria-label="<?php esc_attr_e( 'Next page', 'dashboard-widget-manager' ); ?>">
									<span class="dwm-nav-label"><?php esc_html_e( 'Next', 'dashboard-widget-manager' ); ?></span>
									<span class="dashicons dashicons-arrow-right-alt2"></span>
								</button>
							</div>
						</div>
					</div>

					<!-- Overview Landing Page -->
					<div class="dwm-sidebar-modal-page is-active" data-dwm-features-page-content="overview">
						<!-- Hero -->
						<div class="dwm-overview-hero">
							<img src="<?php echo $hero_img; ?>" alt="<?php echo esc_attr( $hero_plugin_name ); ?>" class="dwm-overview-hero-logo">
							<h2 class="dwm-overview-hero-title"><?php esc_html_e( 'Your Data Intelligence Hub', 'dashboard-widget-manager' ); ?></h2>
							<p class="dwm-overview-hero-subtitle"><?php esc_html_e( 'Query anything. Visualize everything. Own your dashboard.', 'dashboard-widget-manager' ); ?></p>
							<p class="dwm-overview-hero-desc"><?php esc_html_e( 'Dashboard Widget Manager turns your WordPress admin into a real-time data command center — no external tools, no complex queries, no code required.', 'dashboard-widget-manager' ); ?></p>
						</div>

						<!-- Value Pillars -->
						<div class="dwm-overview-pillars">
							<?php
							$overview_pillars = [
								[
									'icon'      => '📊',
									'title'     => __( 'Data Visualization', 'dashboard-widget-manager' ),
									'desc'      => __( '8 display modes — tables, lists, buttons, cards, and 4 chart types. See your data the way you actually need it — at a glance.', 'dashboard-widget-manager' ),
									'docs_page' => 'category-overview-display-modes',
								],
								[
									'icon'      => '🔍',
									'title'     => __( 'Query Power', 'dashboard-widget-manager' ),
									'desc'      => __( 'Visual query builder, table joins, filters, and raw SQL. Access any data in your database without leaving WordPress.', 'dashboard-widget-manager' ),
									'docs_page' => 'category-overview-query-engine',
								],
								[
									'icon'      => '🔗',
									'title'     => __( 'Integrations', 'dashboard-widget-manager' ),
									'desc'      => __( '20+ connections to Salesforce, GitHub, Slack, Google services, and more. Pull external data right into your dashboard.', 'dashboard-widget-manager' ),
									'docs_page' => 'welcome',
								],
								[
									'icon'      => '🎨',
									'title'     => __( 'Customization', 'dashboard-widget-manager' ),
									'desc'      => __( 'HTML/PHP templates, scoped CSS & JS, theme presets. Build widgets that look and behave exactly how you want.', 'dashboard-widget-manager' ),
									'docs_page' => 'category-overview-template-system',
								],
							];
							foreach ( $overview_pillars as $pillar ) :
								?>
								<div class="dwm-overview-pillar">
									<div class="dwm-overview-pillar-icon"><?php echo esc_html( $pillar['icon'] ); ?></div>
									<h4><?php echo esc_html( $pillar['title'] ); ?></h4>
									<p><?php echo esc_html( $pillar['desc'] ); ?></p>
									<button type="button" class="dwm-learn-more-button dwm-overview-pillar-link" data-docs-page="<?php echo esc_attr( $pillar['docs_page'] ); ?>">
										<?php esc_html_e( 'Learn More', 'dashboard-widget-manager' ); ?>
									</button>
								</div>
							<?php endforeach; ?>
						</div>

						<!-- BLACKHAWK-10 -->
						<div class="dwm-overview-blackhawk">
							<div class="dwm-overview-blackhawk-header">
								<span class="dwm-overview-blackhawk-badge"><?php esc_html_e( 'BLACKHAWK-10', 'dashboard-widget-manager' ); ?></span>
								<h3><?php esc_html_e( 'The Precision 10', 'dashboard-widget-manager' ); ?></h3>
								<p><?php esc_html_e( 'Ten mission-critical features that turn raw database tables into actionable intelligence on your WordPress dashboard.', 'dashboard-widget-manager' ); ?></p>
							</div>
							<div class="dwm-overview-blackhawk-list">
								<?php
								$blackhawk_features = [
									[ 'rank' => '01', 'icon' => '🧙', 'cat' => __( 'Builder', 'dashboard-widget-manager' ), 'title' => __( 'Visual Query Builder', 'dashboard-widget-manager' ), 'tagline' => __( 'Build complex SQL with point-and-click. No syntax required.', 'dashboard-widget-manager' ), 'docs_page' => 'feature-visual-builder' ],
									[ 'rank' => '02', 'icon' => '✨', 'cat' => __( 'Wizard', 'dashboard-widget-manager' ), 'title' => __( 'Widget Creation Wizard', 'dashboard-widget-manager' ), 'tagline' => __( 'Guided step-by-step widget building from table to dashboard.', 'dashboard-widget-manager' ), 'docs_page' => 'guide-wizard' ],
									[ 'rank' => '03', 'icon' => '📊', 'cat' => __( 'Display', 'dashboard-widget-manager' ), 'title' => __( '8 Display Modes', 'dashboard-widget-manager' ), 'tagline' => __( 'Tables, lists, buttons, cards, and 4 chart types out of the box.', 'dashboard-widget-manager' ), 'docs_page' => 'display-modes-overview' ],
									[ 'rank' => '04', 'icon' => '🔐', 'cat' => __( 'Security', 'dashboard-widget-manager' ), 'title' => __( 'Role-Based Visibility', 'dashboard-widget-manager' ), 'tagline' => __( 'Control exactly who sees each widget by WordPress role.', 'dashboard-widget-manager' ), 'docs_page' => 'feature-widget-roles' ],
									[ 'rank' => '05', 'icon' => '🔀', 'cat' => __( 'Query', 'dashboard-widget-manager' ), 'title' => __( 'Table Joins', 'dashboard-widget-manager' ), 'tagline' => __( 'Combine data from multiple database tables in one widget.', 'dashboard-widget-manager' ), 'docs_page' => 'feature-visual-builder-joins' ],
									[ 'rank' => '06', 'icon' => '⚙️', 'cat' => __( 'Automation', 'dashboard-widget-manager' ), 'title' => __( 'Auto-generated Templates', 'dashboard-widget-manager' ), 'tagline' => __( 'Instant HTML, CSS, and JS scaffolded from your query.', 'dashboard-widget-manager' ), 'docs_page' => 'feature-auto-generated-templates' ],
									[ 'rank' => '07', 'icon' => '⚡', 'cat' => __( 'Performance', 'dashboard-widget-manager' ), 'title' => __( 'Query Caching', 'dashboard-widget-manager' ), 'tagline' => __( 'Smart caching with configurable TTL for fast dashboard loads.', 'dashboard-widget-manager' ), 'docs_page' => 'feature-caching' ],
									[ 'rank' => '08', 'icon' => '🎨', 'cat' => __( 'Styling', 'dashboard-widget-manager' ), 'title' => __( 'Custom CSS & JavaScript', 'dashboard-widget-manager' ), 'tagline' => __( 'Fully scoped per-widget styling and scripting.', 'dashboard-widget-manager' ), 'docs_page' => 'feature-custom-css' ],
									[ 'rank' => '09', 'icon' => '🔗', 'cat' => __( 'Integrations', 'dashboard-widget-manager' ), 'title' => __( '20+ Integrations', 'dashboard-widget-manager' ), 'tagline' => __( 'Salesforce, GitHub, Slack, Google services, and more.', 'dashboard-widget-manager' ), 'docs_page' => 'welcome' ],
									[ 'rank' => '10', 'icon' => '🛢️', 'cat' => __( 'Data', 'dashboard-widget-manager' ), 'title' => __( 'SQL Query Editor', 'dashboard-widget-manager' ), 'tagline' => __( 'Raw SQL with variables, validation, and live preview.', 'dashboard-widget-manager' ), 'docs_page' => 'feature-sql-queries' ],
								];
								foreach ( $blackhawk_features as $bf ) :
								?>
									<div class="dwm-overview-blackhawk-item">
										<span class="dwm-overview-blackhawk-rank"><?php echo esc_html( $bf['rank'] ); ?></span>
										<span class="dwm-overview-blackhawk-icon"><?php echo esc_html( $bf['icon'] ); ?></span>
										<div class="dwm-overview-blackhawk-info">
											<div class="dwm-overview-blackhawk-meta">
												<h5><?php echo esc_html( $bf['title'] ); ?></h5>
												<span class="dwm-overview-blackhawk-cat"><?php echo esc_html( $bf['cat'] ); ?></span>
											</div>
											<p><?php echo esc_html( $bf['tagline'] ); ?></p>
										</div>
										<button type="button" class="dwm-learn-more-button dwm-overview-blackhawk-link" data-docs-page="<?php echo esc_attr( $bf['docs_page'] ); ?>">
											<?php esc_html_e( 'Learn More', 'dashboard-widget-manager' ); ?>
										</button>
									</div>
								<?php endforeach; ?>
							</div>
							<p class="dwm-overview-blackhawk-note"><?php esc_html_e( 'And this is only a fraction of what\'s coming with Pro.', 'dashboard-widget-manager' ); ?></p>
						</div>

						<!-- Plugin Ecosystem -->
						<div class="dwm-overview-ecosystem">
							<div class="dwm-overview-ecosystem-header">
								<span class="dwm-overview-ecosystem-eyebrow"><?php esc_html_e( 'TopDevAmerica Suite', 'dashboard-widget-manager' ); ?></span>
								<h3><?php esc_html_e( 'Build an Unstoppable Stack', 'dashboard-widget-manager' ); ?></h3>
								<p><?php esc_html_e( 'Dashboard Widget Manager is powerful alone — but pair it with our other plugins and your WordPress site becomes a fully loaded command center.', 'dashboard-widget-manager' ); ?></p>
							</div>
							<div class="dwm-overview-ecosystem-grid">
								<?php
								$plugins_img_url   = DWM_PLUGIN_URL . 'assets/images/plugins/';
								$ecosystem_plugins = [
									[
										'logo'    => $plugins_img_url . 'cta-manager-logo.png',
										'title'   => __( 'CTA Manager', 'dashboard-widget-manager' ),
										'tagline' => __( 'Convert More Visitors', 'dashboard-widget-manager' ),
										'desc'    => __( 'Create, manage, and track conversion-focused calls-to-action with targeting rules, A/B testing, and real-time analytics.', 'dashboard-widget-manager' ),
										'url'     => 'https://topdevamerica.com/plugins/cta-manager',
										'accent'  => 'orange',
										'combo'   => __( 'Track CTA performance right on your dashboard with custom widgets.', 'dashboard-widget-manager' ),
									],
									[
										'logo'    => $plugins_img_url . 'dashboard-widget-manager-logo.png',
										'title'   => __( 'Dashboard Widget Manager', 'dashboard-widget-manager' ),
										'tagline' => __( 'You Are Here', 'dashboard-widget-manager' ),
										'desc'    => __( 'Build custom dashboard widgets with SQL queries, visual builder, chart support, and flexible caching.', 'dashboard-widget-manager' ),
										'url'     => '',
										'accent'  => 'purple',
										'combo'   => __( 'The engine that powers your entire data intelligence layer.', 'dashboard-widget-manager' ),
									],
									[
										'logo'    => $plugins_img_url . 'ai-chat-manager-logo.png',
										'title'   => __( 'AI Chat Manager', 'dashboard-widget-manager' ),
										'tagline' => __( 'AI-Powered Chat', 'dashboard-widget-manager' ),
										'desc'    => __( 'Add a fully customizable AI chat assistant to your WordPress site, powered by Claude or OpenAI.', 'dashboard-widget-manager' ),
										'url'     => 'https://topdevamerica.com/plugins/ai-chat-manager',
										'accent'  => 'teal',
										'combo'   => __( 'Surface widget insights through natural language conversations.', 'dashboard-widget-manager' ),
									],
								];
								foreach ( $ecosystem_plugins as $ep ) :
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

						<?php if ( ! $user_has_pro ) : ?>
						<!-- Upgrade CTA -->
						<div class="dwm-overview-upgrade">
							<span class="dwm-features-pro-cta-glow"></span>
							<div class="dwm-overview-upgrade-content">
								<span class="dwm-overview-upgrade-icon-wrap">
									<span class="dashicons dashicons-star-filled dwm-overview-upgrade-icon dwm-animate-slow"></span>
								</span>
								<div class="dwm-overview-upgrade-text">
									<div class="dwm-overview-upgrade-topline">
										<strong><?php esc_html_e( 'Ready to command your data?', 'dashboard-widget-manager' ); ?></strong>
										<span class="dwm-pro-badge dwm-pro-badge-inline" title="<?php esc_attr_e( 'Pro version available', 'dashboard-widget-manager' ); ?>">
											<?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?>
										</span>
									</div>
									<span class="dwm-overview-upgrade-message"><?php esc_html_e( 'Unlock the full arsenal with Dashboard Widget Manager Pro.', 'dashboard-widget-manager' ); ?></span>
								</div>
							</div>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=dashboard-widget-manager&modal=pro-upgrade' ) ); ?>" class="dwm-overview-upgrade-button">
								<span class="dashicons dashicons-star-filled"></span>
								<?php esc_html_e( 'Upgrade to Pro', 'dashboard-widget-manager' ); ?>
							</a>
						</div>
						<?php endif; ?>
					</div>

					<?php
					$first = false;
					foreach ( $all_features as $category_name => $category_features ) :
						$category_slug = sanitize_title( $category_name );
						$active_class  = $first ? ' is-active' : '';

						$is_pro_category = in_array( $category_slug, [ 'performance', 'styles-scripts' ], true );
						?>
						<div class="dwm-sidebar-modal-page<?php echo esc_attr( $active_class ); ?>" data-dwm-features-page-content="<?php echo esc_attr( $category_slug ); ?>">
							<div class="dwm-sidebar-modal-page-header">
								<h3><?php echo esc_html( $category_name ); ?></h3>
								<p><?php echo esc_html( DWM_Features::get_category_description( $category_name ) ); ?></p>
							</div>

							<?php if ( $is_pro_category && ! $user_has_pro ) : ?>
								<div class="dwm-features-pro-cta">
									<span class="dwm-features-pro-cta-glow"></span>
									<span class="dashicons dashicons-star-filled dwm-features-pro-cta-icon"></span>
									<strong class="dwm-features-pro-cta-title"><?php esc_html_e( 'Unlock Pro', 'dashboard-widget-manager' ); ?></strong>
									<span class="dwm-pro-badge"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
									<span class="dwm-features-pro-cta-message"><?php esc_html_e( 'Activate your Pro license for these features.', 'dashboard-widget-manager' ); ?></span>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=dwm-settings#dwm-pro-license-key' ) ); ?>" class="dwm-features-pro-cta-button">
										<span class="dashicons dashicons-unlock"></span>
										<?php esc_html_e( 'Add License Key', 'dashboard-widget-manager' ); ?>
									</a>
								</div>
							<?php endif; ?>

							<div class="dwm-features-grid">
								<?php
								foreach ( $category_features as $feature ) :
									if ( ! empty( $feature['category_overview'] ) ) :
										continue;
									endif;
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

							<?php if ( ! $is_pro_category && ! $user_has_pro ) : ?>
								<div class="dwm-features-pro-cta dwm-features-pro-cta--bottom">
									<span class="dwm-features-pro-cta-glow"></span>
									<span class="dashicons dashicons-star-filled dwm-features-pro-cta-icon"></span>
									<strong class="dwm-features-pro-cta-title"><?php esc_html_e( 'Get Pro', 'dashboard-widget-manager' ); ?></strong>
									<span class="dwm-pro-badge"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
									<span class="dwm-features-pro-cta-message"><?php esc_html_e( 'Unlock advanced features, integrations, and more with Pro.', 'dashboard-widget-manager' ); ?></span>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=dashboard-widget-manager&modal=pro-upgrade' ) ); ?>" class="dwm-features-pro-cta-button">
										<span class="dashicons dashicons-star-filled"></span>
										<?php esc_html_e( 'Upgrade to Pro', 'dashboard-widget-manager' ); ?>
									</a>
								</div>
							<?php endif; ?>
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

						<?php if ( ! $user_has_pro ) : ?>
							<div class="dwm-features-pro-cta">
								<span class="dwm-features-pro-cta-glow"></span>
								<span class="dashicons dashicons-star-filled dwm-features-pro-cta-icon"></span>
								<strong class="dwm-features-pro-cta-title"><?php esc_html_e( 'Pro Integrations', 'dashboard-widget-manager' ); ?></strong>
								<span class="dwm-pro-badge"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
								<span class="dwm-features-pro-cta-message"><?php esc_html_e( 'Activate your Pro license for integrations.', 'dashboard-widget-manager' ); ?></span>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=dwm-settings#dwm-pro-license-key' ) ); ?>" class="dwm-features-pro-cta-button">
									<span class="dashicons dashicons-unlock"></span>
									<?php esc_html_e( 'Add License Key', 'dashboard-widget-manager' ); ?>
								</a>
							</div>
						<?php endif; ?>

						<div class="dwm-integrations-grid-wrapper">
							<?php foreach ( $integrations as $category_name => $items ) : ?>
								<div class="dwm-integration-group">
									<h4 class="dwm-feature-group-title"><?php echo esc_html( $category_name ); ?></h4>
									<div class="dwm-integrations-grid">
										<?php foreach ( $items as $integration ) : ?>
											<div class="dwm-integration-card<?php echo ! empty( $integration['implemented'] ) ? ' is-available' : ''; ?>">
												<div class="dwm-integration-header">
													<?php if ( ! empty( $integration['logos'] ) ) : ?>
														<div class="dwm-integration-logos">
															<?php foreach ( $integration['logos'] as $logo_url ) : ?>
																<img src="<?php echo esc_url( $logo_url ); ?>" alt="" loading="lazy">
															<?php endforeach; ?>
														</div>
													<?php else : ?>
														<div class="dwm-integration-icon">
															<?php if ( ! empty( $integration['image'] ) ) : ?>
																<img src="<?php echo esc_url( $integration['image'] ); ?>" alt="<?php echo esc_attr( $integration['title'] ); ?>" loading="lazy">
															<?php elseif ( ! empty( $integration['icon'] ) ) : ?>
																<?php echo esc_html( $integration['icon'] ); ?>
															<?php endif; ?>
														</div>
													<?php endif; ?>
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
												<div class="dwm-integration-footer">
													<?php if ( empty( $integration['implemented'] ) ) : ?>
														<span class="dwm-pro-badge"><?php echo esc_html( $labels['badge_pro'] ); ?></span>
														<span class="dwm-badge dwm-badge-primary dwm-pulse-primary"><?php echo esc_html( $labels['badge_coming_soon'] ); ?></span>
													<?php else : ?>
														<span class="dwm-badge dwm-badge-success"><?php echo esc_html( $labels['badge_available'] ); ?></span>
													<?php endif; ?>
													<?php if ( ! empty( $integration['docs_page'] ) ) : ?>
														<button type="button" class="dwm-learn-more-button" data-docs-page="<?php echo esc_attr( $integration['docs_page'] ); ?>">
															<?php esc_html_e( 'Learn More', 'dashboard-widget-manager' ); ?>
														</button>
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
	var layout = document.querySelector('.dwm-sidebar-modal-layout');
	if (!layout) return;

	var searchInput   = layout.querySelector('[data-dwm-features-search]');
	var searchClear   = layout.querySelector('[data-dwm-features-search-clear]');
	var planBtns      = layout.querySelectorAll('[data-dwm-features-plan]');
	var soonToggle    = layout.querySelector('[data-dwm-features-soon-toggle]');
	var menuItems     = layout.querySelectorAll('[data-dwm-features-page]');
	var activePlan    = 'all';
	var showSoon      = true;
	var searchTerm    = '';

	function applyFilters() {
		var pages = layout.querySelectorAll('[data-dwm-features-page-content]');
		var term = searchTerm.toLowerCase();

		pages.forEach(function(page) {
			var slug = page.dataset.dwmFeaturesPageContent;
			if (slug === 'integrations' || slug === 'overview') return;

			var cards = page.querySelectorAll('.dwm-feature-card');
			var visible = 0;

			cards.forEach(function(card) {
				var plan = card.dataset.featurePlan;
				var implemented = card.dataset.featureImplemented === '1';
				var show = true;

				if (activePlan !== 'all' && plan !== activePlan) show = false;
				if (!showSoon && !implemented) show = false;

				if (show && term.length >= 3) {
					var titleText = card.dataset.featureTitle || '';
					var searchText = card.dataset.featureSearch || '';
					if (titleText.indexOf(term) === -1 && searchText.indexOf(term) === -1) {
						show = false;
					}
				}

				card.style.display = show ? '' : 'none';
				if (show) visible++;
			});

			var menuBtn = layout.querySelector('[data-dwm-features-page="' + slug + '"]');
			if (menuBtn) {
				var countEl = menuBtn.querySelector('.dwm-sidebar-modal-menu-count');
				if (countEl) countEl.textContent = visible;
			}
		});
	}

	function resetFilters() {
		searchTerm = '';
		if (searchInput) searchInput.value = '';
		updateSearchIcon();
		activePlan = 'all';
		planBtns.forEach(function(btn) {
			btn.classList.toggle('is-active', btn.dataset.dwmFeaturesPlan === 'all');
		});
		if (soonToggle) {
			soonToggle.checked = true;
			showSoon = true;
		}
		applyFilters();
	}

	function updateSearchIcon() {
		if (!searchClear) return;
		var icon = searchClear.querySelector('.dashicons');
		if (!icon) return;
		if (searchInput && searchInput.value.length >= 3) {
			icon.className = 'dashicons dashicons-no-alt';
			searchClear.classList.add('has-value');
		} else {
			icon.className = 'dashicons dashicons-search';
			searchClear.classList.remove('has-value');
		}
	}

	if (searchInput) {
		var debounce;
		searchInput.addEventListener('input', function() {
			clearTimeout(debounce);
			debounce = setTimeout(function() {
				searchTerm = searchInput.value;
				updateSearchIcon();
				applyFilters();
			}, 200);
		});
	}

	if (searchClear) {
		searchClear.addEventListener('click', function() {
			if (searchInput && searchInput.value.length > 0) {
				searchInput.value = '';
				searchTerm = '';
				updateSearchIcon();
				applyFilters();
				searchInput.focus();
			}
		});
	}

	planBtns.forEach(function(btn) {
		btn.addEventListener('click', function() {
			activePlan = btn.dataset.dwmFeaturesPlan;
			planBtns.forEach(function(b) { b.classList.remove('is-active'); });
			btn.classList.add('is-active');
			applyFilters();
		});
	});

	if (soonToggle) {
		soonToggle.addEventListener('change', function() {
			showSoon = soonToggle.checked;
			applyFilters();
		});
	}

	// Navigation
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

	// Learn More
	document.addEventListener('click', function(e) {
		var btn = e.target.closest('.dwm-learn-more-button');
		if (!btn) return;

		var docsPage = btn.dataset.docsPage;
		if (!docsPage || !window.DWMDocsModal) return;

		var $link = jQuery('#dwm-docs-modal [data-docs-page="' + docsPage + '"]').first();
		window.DWMDocsModal.collapseAllAccordions();
		window.DWMDocsModal.showPage(docsPage);
		window.DWMDocsModal.setActiveLink($link.length ? $link : jQuery('#dwm-docs-modal .dwm-docs-welcome-link'));

		jQuery('#dwm-docs-modal').addClass('active');
		jQuery('body').addClass('dwm-modal-open');
	});

	// Reset on modal close
	var observer = new MutationObserver(function(mutations) {
		mutations.forEach(function(m) {
			if (m.type === 'attributes') {
				var modal = document.getElementById('dwm-features-modal');
				if (modal && !modal.classList.contains('active')) {
					resetFilters();
				}
			}
		});
	});
	var featuresModalEl = document.getElementById('dwm-features-modal');
	if (featuresModalEl) {
		observer.observe(featuresModalEl, { attributes: true, attributeFilter: ['class'] });
	}
})();
</script>
