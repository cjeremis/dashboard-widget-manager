<?php
/**
 * Admin Page Template - Integrations
 *
 * Displays the grouped integrations page for Dashboard Widget Manager.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$integrations      = DWM_Features::get_all_integrations();
$integrations_meta = DWM_Features::get_integrations_meta();
$labels            = DWM_Features::get_labels();

$current_page       = 'dwm-integrations';
$header_title       = __( 'Integrations', 'dashboard-widget-manager' );
$topbar_actions     = [];
$header_description = __( 'Connect Dashboard Widget Manager with external tools and services.' , 'dashboard-widget-manager' );

include __DIR__ . '/partials/page-wrapper-start.php';
?>

<div class="dwm-page-content">
	<div class="dwm-integrations-content">
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
								<div class="dwm-integration-footer">
									<span class="dwm-pro-badge"><?php echo esc_html( $labels['badge_pro'] ); ?></span>
									<?php if ( empty( $integration['implemented'] ) ) : ?>
										<span class="dwm-badge dwm-badge-primary dwm-pulse-primary"><?php echo esc_html( $labels['badge_coming_soon'] ); ?></span>
									<?php endif; ?>
									<button type="button" class="dwm-learn-more-button dwm-button-primary" data-open-modal="dwm-docs-modal" data-docs-page="<?php echo esc_attr( $integration['docs_page'] ?? '' ); ?>">
											<?php esc_html_e( 'Learn More', 'dashboard-widget-manager' ); ?>
										</button>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<?php include __DIR__ . '/partials/page-wrapper-end.php'; ?>
