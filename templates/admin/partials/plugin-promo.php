<?php
/**
 * Admin Partial Template - Plugin Promo
 *
 * Handles markup rendering for the plugin promo section shown in the page footer.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugins_img_url = DWM_PLUGIN_URL . 'assets/images/plugins/';

$promo_plugins = array(
	array(
		'slug'        => 'dwm-manager',
		'logo'        => $plugins_img_url . 'cta-manager-logo.png',
		'title'       => __( 'CTA Manager', 'dashboard-widget-manager' ),
		'tagline'     => __( 'Convert More Visitors', 'dashboard-widget-manager' ),
		'description' => __( 'Create, manage, and track conversion-focused calls-to-action with targeting rules, A/B testing, and real-time analytics.', 'dashboard-widget-manager' ),
		'url'         => 'https://topdevamerica.com/plugins/cta-manager',
		'color_class' => 'dwm-promo-card--orange',
	),
	array(
		'slug'        => 'dashboard-widget-manager',
		'logo'        => $plugins_img_url . 'dashboard-widget-manager-logo.png',
		'title'       => __( 'Dashboard Widget Manager', 'dashboard-widget-manager' ),
		'tagline'     => __( 'Custom Dashboard Widgets', 'dashboard-widget-manager' ),
		'description' => __( 'Build custom WP admin dashboard widgets with SQL queries, PHP rendering, chart support, and flexible caching.', 'dashboard-widget-manager' ),
		'url'         => 'https://topdevamerica.com/plugins/dashboard-widget-manager',
		'color_class' => 'dwm-promo-card--purple',
	),
	array(
		'slug'        => 'ai-chat-manager',
		'logo'        => $plugins_img_url . 'ai-chat-manager-logo.png',
		'title'       => __( 'AI Chat Manager', 'dashboard-widget-manager' ),
		'tagline'     => __( 'AI-Powered Chat', 'dashboard-widget-manager' ),
		'description' => __( 'Add a fully customizable AI chat assistant to your WordPress site, powered by Claude or OpenAI.', 'dashboard-widget-manager' ),
		'url'         => 'https://topdevamerica.com/plugins/ai-chat-manager',
		'color_class' => 'dwm-promo-card--teal',
	),
);
?>

<div class="dwm-plugin-promo-section">
	<div class="dwm-plugin-promo-header">
		<span class="dwm-plugin-promo-eyebrow"><?php esc_html_e( 'TopDevAmerica', 'dashboard-widget-manager' ); ?></span>
		<h3 class="dwm-plugin-promo-title"><?php esc_html_e( 'More Tools for WordPress', 'dashboard-widget-manager' ); ?></h3>
		<p class="dwm-plugin-promo-subtitle"><?php esc_html_e( 'Extend your WordPress workflow with our growing suite of developer-focused plugins.', 'dashboard-widget-manager' ); ?></p>
	</div>

	<div class="dwm-plugin-promo-grid">
		<?php foreach ( $promo_plugins as $plugin ) : ?>
			<div class="dwm-promo-card-wrap <?php echo esc_attr( $plugin['color_class'] ); ?>">
				<div class="dwm-promo-card">
					<div class="dwm-promo-card-overlay <?php echo esc_attr( $plugin['color_class'] ); ?>"></div>
					<div class="dwm-promo-card-logo">
						<img src="<?php echo esc_url( $plugin['logo'] ); ?>" alt="<?php echo esc_attr( $plugin['title'] ); ?>" width="52" height="52" loading="lazy">
					</div>
					<div class="dwm-promo-card-body">
						<p class="dwm-promo-card-tagline"><?php echo esc_html( $plugin['tagline'] ); ?></p>
						<h4 class="dwm-promo-card-name"><?php echo esc_html( $plugin['title'] ); ?></h4>
						<p class="dwm-promo-card-desc"><?php echo esc_html( $plugin['description'] ); ?></p>
					</div>
					<div class="dwm-promo-card-footer">
						<a href="<?php echo esc_url( $plugin['url'] ); ?>" class="dwm-promo-card-btn" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Learn More', 'dashboard-widget-manager' ); ?>
							<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
						</a>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
