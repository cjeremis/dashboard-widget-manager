<?php
/**
 * Admin Page Template - Settings
 *
 * Handles markup rendering for the settings admin page template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data     = DWM_Data::get_instance();
$settings = $data->get_settings();

// Page wrapper configuration.
$current_page       = 'dwm-settings';
$header_title       = __( 'Settings', 'dashboard-widget-manager' );
$header_description = __( 'Configure security and behavior controls including table restrictions, cache defaults, execution limits, notifications, and license settings.', 'dashboard-widget-manager' );
$topbar_actions     = [];

include __DIR__ . '/partials/page-wrapper-start.php';
?>

<div class="dwm-page-content">

	<?php
	// Pro License section - shown as the first section on the settings page.
	$is_pro_enabled_via_filter = class_exists( 'DWM_Pro_Feature_Gate' ) && DWM_Pro_Feature_Gate::is_pro_enabled();
	$license_key_min_length    = 19;

	if ( $is_pro_enabled_via_filter && ! class_exists( 'DWM_License_Manager' ) ) :
		// Dev bypass mode (filter/cookie override) but license manager not yet available.
		$license_status    = 'active';
		$license_key       = 'DEV-MODE-LICENSE';
		$is_license_active = true;
		$has_saved_license = true;
		$section_title     = __( 'Pro License (Development Mode)', 'dashboard-widget-manager' );
		include DWM_PLUGIN_DIR . 'templates/admin/partials/license-manager.php';
		unset( $license_status, $license_key, $is_license_active, $has_saved_license, $section_title );

	elseif ( class_exists( 'DWM_License_Manager' ) ) :
		// License manager available - show the full license form.
		$license_data      = DWM_License_Manager::get_instance()->get_license_data();
		$license_status    = $license_data['status'] ?? 'inactive';
		$license_key       = $license_data['key'] ?? '';
		$is_license_active = 'active' === $license_status;
		$has_saved_license = ! empty( $license_key ) && $is_license_active;

		include DWM_PLUGIN_DIR . 'templates/admin/partials/license-manager.php';
		unset( $license_data, $license_status, $license_key, $is_license_active, $has_saved_license );

	endif;
	unset( $is_pro_enabled_via_filter, $license_key_min_length );
	?>

	<form id="dwm-settings-form">
		<?php wp_nonce_field( 'dwm_admin_nonce', 'dwm_settings_nonce' ); ?>

		<!-- Security -->
		<div class="dwm-section">
			<?php
			$title             = __( 'Table Allow List', 'dashboard-widget-manager' );
			$help_modal_target = 'dwm-docs-modal';
			$help_icon_label   = __( 'Learn about the Table Allow List Setting', 'dashboard-widget-manager' );
			$attrs             = 'data-docs-page="feature-table-allowlist"';
			include __DIR__ . '/partials/section-header-with-actions.php';
			unset( $title, $help_modal_target, $help_icon_label, $attrs, $actions_html );
			?>

			<div class="dwm-form-group">
				<div class="dwm-form-control">
					<?php
					global $wpdb;
					$all_tables          = $wpdb->get_col( 'SHOW TABLES' );
					sort( $all_tables );
					$excluded_tables_raw = $settings['excluded_tables'] ?? '';
					$excluded_tables_arr = empty( $excluded_tables_raw )
						? array()
						: array_filter( array_map( 'trim', explode( "\n", $excluded_tables_raw ) ) );
					?>
					<input type="hidden" name="settings[excluded_tables]" id="dwm-excluded-tables-value" value="<?php echo esc_attr( $excluded_tables_raw ); ?>">
					<div class="dwm-table-controls">
						<a href="#" id="dwm-select-all-tables"><?php esc_html_e( 'Select All', 'dashboard-widget-manager' ); ?></a>
						<span>/</span>
						<a href="#" id="dwm-deselect-all-tables"><?php esc_html_e( 'Deselect All', 'dashboard-widget-manager' ); ?></a>
					</div>
					<div class="dwm-tables-grid">
						<?php foreach ( $all_tables as $table ) : ?>
							<label class="dwm-table-checkbox-label" title="<?php echo esc_attr( $table ); ?>">
								<input type="checkbox" class="dwm-table-checkbox" value="<?php echo esc_attr( $table ); ?>"
									<?php checked( ! in_array( $table, $excluded_tables_arr, true ) ); ?>>
								<?php echo esc_html( $table ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<div class="dwm-section-actions">
				<button type="submit" class="dwm-button dwm-button-primary">
					<?php esc_html_e( 'Save Security', 'dashboard-widget-manager' ); ?>
				</button>
			</div>
		</div>

		<!-- Dashboard Overrides -->
		<div class="dwm-section">
			<?php
			$title             = __( 'Dashboard Overrides', 'dashboard-widget-manager' );
			$help_modal_target = 'dwm-docs-modal';
			$help_icon_label   = __( 'Learn about Dashboard Overrides', 'dashboard-widget-manager' );
			$attrs             = 'data-docs-page="category-overview-settings"';
			include __DIR__ . '/partials/section-header-with-actions.php';
			unset( $title, $help_modal_target, $help_icon_label, $attrs, $actions_html );
			?>

			<div class="dwm-form-row dwm-form-row--toggles">
				<div class="dwm-form-group dwm-form-group--toggle">
					<label class="dwm-toggle" for="dwm-hide-help-dropdown">
						<input type="checkbox" id="dwm-hide-help-dropdown" name="settings[hide_help_dropdown]" value="1" data-autosave="true" <?php checked( ! empty( $settings['hide_help_dropdown'] ) ); ?>>
						<span class="dwm-toggle-slider"></span>
					</label>
					<div class="dwm-form-group-info">
						<span class="dwm-form-label"><?php esc_html_e( 'Hide Help Dropdown', 'dashboard-widget-manager' ); ?></span>
					</div>
				</div>

				<div class="dwm-form-group dwm-form-group--toggle">
					<label class="dwm-toggle" for="dwm-hide-screen-options">
						<input type="checkbox" id="dwm-hide-screen-options" name="settings[hide_screen_options]" value="1" data-autosave="true" <?php checked( ! empty( $settings['hide_screen_options'] ) ); ?>>
						<span class="dwm-toggle-slider"></span>
					</label>
					<div class="dwm-form-group-info">
						<span class="dwm-form-label"><?php esc_html_e( 'Hide Screen Options', 'dashboard-widget-manager' ); ?></span>
					</div>
				</div>
			</div>

			<div class="dwm-form-group">
				<p class="dwm-form-label"><?php esc_html_e( 'Hide Default Widgets', 'dashboard-widget-manager' ); ?></p>
				<div class="dwm-form-control">
					<?php
					$default_wp_widgets = array(
						'welcome-panel'         => __( 'Welcome Panel', 'dashboard-widget-manager' ),
						'dashboard_activity'    => __( 'Activity', 'dashboard-widget-manager' ),
						'dashboard_right_now'   => __( 'At a Glance', 'dashboard-widget-manager' ),
						'dashboard_quick_press' => __( 'Quick Draft', 'dashboard-widget-manager' ),
						'dashboard_site_health' => __( 'Site Health Status', 'dashboard-widget-manager' ),
						'dashboard_primary'     => __( 'Events and News', 'dashboard-widget-manager' ),
					);
					$hidden_widgets_raw = $settings['hidden_dashboard_widgets'] ?? '';
					$hidden_widgets_arr = empty( $hidden_widgets_raw )
						? array()
						: array_filter( array_map( 'trim', explode( "\n", $hidden_widgets_raw ) ) );
					?>
					<input type="hidden" name="settings[hidden_dashboard_widgets]" id="dwm-hidden-widgets-value" value="<?php echo esc_attr( $hidden_widgets_raw ); ?>">
					<div class="dwm-table-controls">
						<a href="#" id="dwm-select-all-widgets"><?php esc_html_e( 'Select All', 'dashboard-widget-manager' ); ?></a>
						<span>/</span>
						<a href="#" id="dwm-deselect-all-widgets"><?php esc_html_e( 'Deselect All', 'dashboard-widget-manager' ); ?></a>
					</div>
					<div class="dwm-tables-grid">
						<?php foreach ( $default_wp_widgets as $widget_id => $widget_label ) : ?>
							<label class="dwm-table-checkbox-label" title="<?php echo esc_attr( $widget_label ); ?>">
								<input type="checkbox" class="dwm-widget-hide-checkbox" value="<?php echo esc_attr( $widget_id ); ?>"
									<?php checked( in_array( $widget_id, $hidden_widgets_arr, true ) ); ?>>
								<?php echo esc_html( $widget_label ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<div class="dwm-section-actions">
				<button type="submit" class="dwm-button dwm-button-primary">
					<?php esc_html_e( 'Save Overrides', 'dashboard-widget-manager' ); ?>
				</button>
			</div>
		</div>

		<!-- Support & Privacy -->
		<div class="dwm-section">
			<?php
			$title             = __( 'Support & Privacy', 'dashboard-widget-manager' );
			$help_modal_target = 'dwm-docs-modal';
			$help_icon_label   = __( 'Learn about support data sharing and legal disclosures', 'dashboard-widget-manager' );
			$attrs             = 'data-docs-page="category-overview-support"';
			include __DIR__ . '/partials/section-header-with-actions.php';
			unset( $title, $help_modal_target, $help_icon_label, $attrs, $actions_html );

			$privacy_page_id = (int) get_option( 'tda_shared_privacy_page_id' );
			$terms_page_id   = (int) get_option( 'tda_shared_terms_page_id' );
			$privacy_url     = $privacy_page_id ? get_permalink( $privacy_page_id ) : 'https://topdevamerica.com/privacy-policy';
			$terms_url       = $terms_page_id ? get_permalink( $terms_page_id ) : 'https://topdevamerica.com/terms';
			?>

			<div class="dwm-form-row dwm-form-row--toggles">
				<div class="dwm-form-group dwm-form-group--toggle">
					<label class="dwm-toggle" for="dwm-support-data-sharing-opt-in">
						<input
							type="checkbox"
							id="dwm-support-data-sharing-opt-in"
							name="settings[support_data_sharing_opt_in]"
							value="1"
							data-autosave="true"
							<?php checked( ! empty( $settings['support_data_sharing_opt_in'] ) ); ?>
						>
						<span class="dwm-toggle-slider"></span>
					</label>
					<div class="dwm-form-group-info">
						<span class="dwm-form-label"><?php esc_html_e( 'Live Support Reply Sync', 'dashboard-widget-manager' ); ?></span>
						<p class="description">
							<?php esc_html_e( 'When enabled, this plugin contacts TopDevAmerica servers to sync support reply notifications for your account. Data transmitted: your account email address and site URL. Disabled by default.', 'dashboard-widget-manager' ); ?>
						</p>
					</div>
				</div>
			</div>

			<div class="dwm-info-box dwm-info-box--info">
				<span class="dashicons dashicons-privacy"></span>
				<div>
					<strong><?php esc_html_e( 'Legal Disclosures', 'dashboard-widget-manager' ); ?></strong>
					<p>
						<?php esc_html_e( 'By using support and license services, you agree to the terms and privacy disclosures below.', 'dashboard-widget-manager' ); ?>
						<a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Privacy Policy', 'dashboard-widget-manager' ); ?></a>
						|
						<a href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Terms of Service', 'dashboard-widget-manager' ); ?></a>
					</p>
				</div>
			</div>
		</div>

	</form>

</div>

<?php include __DIR__ . '/partials/page-wrapper-end.php'; ?>
