<?php
/**
 * Dashboard Template
 *
 * Displays the plugin dashboard.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap dwm-dashboard dwm-page-wrapper">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="dwm-welcome">
		<h2><?php esc_html_e( 'Widget Manager', 'dashboard-widget-manager' ); ?></h2>
		<p><?php esc_html_e( 'Create WP Dashboard widgets with SQL, PHP, HTML, CSS, and JS.', 'dashboard-widget-manager' ); ?></p>
	</div>

	<div class="dwm-statistics">
		<h2><?php esc_html_e( 'Statistics', 'dashboard-widget-manager' ); ?></h2>
		<div class="dwm-stats-grid">
			<div class="dwm-stat-card">
				<div class="dwm-stat-value"><?php echo absint( $statistics['total_widgets'] ); ?></div>
				<div class="dwm-stat-label"><?php esc_html_e( 'Total Widgets', 'dashboard-widget-manager' ); ?></div>
			</div>
			<div class="dwm-stat-card">
				<div class="dwm-stat-value"><?php echo absint( $statistics['enabled_widgets'] ); ?></div>
				<div class="dwm-stat-label"><?php esc_html_e( 'Enabled Widgets', 'dashboard-widget-manager' ); ?></div>
			</div>
			<div class="dwm-stat-card">
				<div class="dwm-stat-value"><?php echo absint( $statistics['cache_entries'] ); ?></div>
				<div class="dwm-stat-label"><?php esc_html_e( 'Cache Entries', 'dashboard-widget-manager' ); ?></div>
			</div>
		</div>
	</div>

	<div class="dwm-quick-actions">
		<h2><?php esc_html_e( 'Quick Actions', 'dashboard-widget-manager' ); ?></h2>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dwm-widgets' ) ); ?>" class="button button-primary button-large">
				<?php esc_html_e( 'Create New Widget', 'dashboard-widget-manager' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dwm-settings' ) ); ?>" class="button button-large">
				<?php esc_html_e( 'Settings', 'dashboard-widget-manager' ); ?>
			</a>
		</p>
	</div>

	<div class="dwm-recent-widgets">
		<h2><?php esc_html_e( 'Recent Widgets', 'dashboard-widget-manager' ); ?></h2>
		<?php if ( ! empty( $recent_widgets ) ) : ?>
			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'dashboard-widget-manager' ); ?></th>
						<th><?php esc_html_e( 'Description', 'dashboard-widget-manager' ); ?></th>
						<th><?php esc_html_e( 'Status', 'dashboard-widget-manager' ); ?></th>
						<th><?php esc_html_e( 'Created', 'dashboard-widget-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent_widgets as $widget ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $widget['name'] ); ?></strong></td>
							<td><?php echo esc_html( $widget['description'] ); ?></td>
							<td>
								<?php if ( $widget['enabled'] ) : ?>
									<span class="dwm-badge dwm-badge-success"><?php esc_html_e( 'Enabled', 'dashboard-widget-manager' ); ?></span>
								<?php else : ?>
									<span class="dwm-badge dwm-badge-disabled"><?php esc_html_e( 'Disabled', 'dashboard-widget-manager' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( gmdate( 'Y-m-d H:i', strtotime( $widget['created_at'] ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No widgets created yet.', 'dashboard-widget-manager' ); ?></p>
		<?php endif; ?>
	</div>
</div>
