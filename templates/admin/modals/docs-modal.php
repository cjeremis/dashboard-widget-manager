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

$all_features = DWM_Features::get_all_features();
$categories   = DWM_Features::get_categories();
?>
<div id="dwm-docs-modal" class="dwm-modal dwm-docs-modal">
	<div class="dwm-modal-overlay"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2><span class="dashicons dashicons-book-alt"></span> <?php esc_html_e( 'Documentation', 'dashboard-widget-manager' ); ?></h2>
			<button type="button" class="dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<div class="dwm-docs-layout">

				<!-- Sidebar -->
				<div class="dwm-docs-sidebar">
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
										<?php foreach ( $features as $feature ) :
											$is_pro     = 'pro' === ( $feature['plan'] ?? 'free' );
											$is_coming  = ! ( $feature['implemented'] ?? true );
										?>
										<li class="dwm-docs-submenu-item" data-search-title="<?php echo esc_attr( strtolower( $feature['title'] ) ); ?>">
											<button type="button"
												class="dwm-docs-submenu-link"
												data-docs-page="<?php echo esc_attr( $feature['docs_page'] ); ?>"
											>
												<?php echo esc_html( $feature['title'] ); ?>
												<?php if ( $is_pro && $is_coming ) : ?>
													<span class="dwm-docs-pro-badge"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
													<span class="dwm-docs-coming-badge"><?php esc_html_e( 'Soon', 'dashboard-widget-manager' ); ?></span>
												<?php elseif ( $is_pro ) : ?>
													<span class="dwm-docs-pro-badge"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
												<?php elseif ( $is_coming ) : ?>
													<span class="dwm-docs-coming-badge"><?php esc_html_e( 'Soon', 'dashboard-widget-manager' ); ?></span>
												<?php endif; ?>
											</button>
										</li>
										<?php endforeach; ?>
									</ul>
								</div>
							</li>
							<?php endforeach; ?>
						</ul>
					</nav>
				</div>

				<!-- Content area -->
				<div class="dwm-docs-content" data-docs-content>

					<!-- Welcome page -->
					<div class="dwm-docs-page is-active" data-docs-page-content="welcome">
						<div class="dwm-docs-welcome">
							<div class="dwm-docs-welcome-icon">
								<span class="dashicons dashicons-layout"></span>
							</div>
							<h2><?php esc_html_e( 'Dashboard Widget Manager', 'dashboard-widget-manager' ); ?></h2>
							<p><?php esc_html_e( 'Build custom WordPress dashboard widgets powered by SQL queries, HTML/PHP templates, and per-widget custom CSS and JavaScript. Browse the documentation to learn how to get the most out of each feature.', 'dashboard-widget-manager' ); ?></p>
						</div>

						<div class="dwm-docs-welcome-features">
							<div class="dwm-docs-welcome-feature">
								<span class="dashicons dashicons-database"></span>
								<div>
									<strong><?php esc_html_e( 'SQL Query Engine', 'dashboard-widget-manager' ); ?></strong>
									<span><?php esc_html_e( 'Write SELECT queries with built-in variable support and validation.', 'dashboard-widget-manager' ); ?></span>
								</div>
							</div>
							<div class="dwm-docs-welcome-feature">
								<span class="dashicons dashicons-editor-code"></span>
								<div>
									<strong><?php esc_html_e( 'HTML/PHP Templates', 'dashboard-widget-manager' ); ?></strong>
									<span><?php esc_html_e( 'Output query results using flexible HTML and PHP widget templates.', 'dashboard-widget-manager' ); ?></span>
								</div>
							</div>
							<div class="dwm-docs-welcome-feature">
								<span class="dashicons dashicons-art"></span>
								<div>
									<strong><?php esc_html_e( 'Per-Widget CSS & JS', 'dashboard-widget-manager' ); ?></strong>
									<span><?php esc_html_e( 'Add scoped styles and scripts that load only with each widget.', 'dashboard-widget-manager' ); ?></span>
								</div>
							</div>
							<div class="dwm-docs-welcome-feature">
								<span class="dashicons dashicons-shield"></span>
								<div>
									<strong><?php esc_html_e( 'Built-In Security', 'dashboard-widget-manager' ); ?></strong>
									<span><?php esc_html_e( 'SELECT-only enforcement, table allowlists, and capability checks.', 'dashboard-widget-manager' ); ?></span>
								</div>
							</div>
						</div>
					</div>

					<!-- Feature pages (generated from features data) -->
					<?php foreach ( $all_features as $category_name => $features ) :
						foreach ( $features as $feature ) :
							$is_pro    = 'pro' === ( $feature['plan'] ?? 'free' );
							$is_coming = ! ( $feature['implemented'] ?? true );
					?>
					<div class="dwm-docs-page" data-docs-page-content="<?php echo esc_attr( $feature['docs_page'] ); ?>">
						<div class="dwm-docs-section">
							<h2 class="dwm-docs-section-title">
								<span class="dwm-docs-title-icon"><?php echo esc_html( $feature['icon'] ?? '' ); ?></span>
								<?php echo esc_html( $feature['title'] ); ?>
								<?php if ( $is_pro ) : ?>
									<span class="dwm-docs-pro-badge"><?php esc_html_e( 'PRO', 'dashboard-widget-manager' ); ?></span>
								<?php endif; ?>
								<?php if ( $is_coming ) : ?>
									<span class="dwm-docs-coming-badge"><?php esc_html_e( 'Coming Soon', 'dashboard-widget-manager' ); ?></span>
								<?php endif; ?>
							</h2>
							<p class="dwm-docs-section-description"><?php echo esc_html( $feature['description'] ?? '' ); ?></p>

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
						</div>
					</div>
					<?php
						endforeach;
					endforeach;
					?>

				</div>
			</div>
		</div>
	</div>
</div>
