<?php
/**
 * Admin Page Template - Branding
 *
 * Handles markup rendering for dashboard customization controls.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data     = DWM_Data::get_instance();
$settings = $data->get_settings();

// Page wrapper configuration.
$current_page       = 'dwm-customize-dashboard';
$header_title       = __( 'Branding', 'dashboard-widget-manager' );
$header_description = __( 'Control dashboard visibility behavior by hiding core WordPress widgets and 3rd-party plugin widgets.', 'dashboard-widget-manager' );
$topbar_actions     = [];

include __DIR__ . '/partials/page-wrapper-start.php';
?>

<div class="dwm-page-content">
	<form id="dwm-settings-form">
		<?php wp_nonce_field( 'dwm_admin_nonce', 'dwm_settings_nonce' ); ?>

		<!-- Hide Dashboard Elements -->
		<div id="dwm-section-dropdown-panels" class="dwm-section dwm-customize-dashboard-section">
			<?php
			$title_raw         = esc_html__( 'Hide Dashboard Elements', 'dashboard-widget-manager' );
			$is_pro_only 	   = true;
			$help_modal_target = 'dwm-docs-modal';
			$help_icon_label   = __( 'Learn about hiding dropdown panels', 'dashboard-widget-manager' );
			$attrs             = 'data-docs-page="custom-dashboard-dropdown-panels"';
			include __DIR__ . '/partials/section-header-with-actions.php';
			unset( $title_raw, $help_modal_target, $help_icon_label, $attrs, $actions_html );
			?>

			<div class="dwm-form-row dwm-form-row--toggles">
				<div class="dwm-form-group dwm-form-group--toggle dwm-form-group--toggle-vertical">
					<div class="dwm-form-group-info">
						<label class="dwm-form-label" for="dwm-hide-help-dropdown"><?php esc_html_e( 'Hide Help Dropdown', 'dashboard-widget-manager' ); ?></label>
					</div>
					<label class="dwm-toggle" for="dwm-hide-help-dropdown">
						<input type="checkbox" id="dwm-hide-help-dropdown" name="settings[hide_help_dropdown]" value="1" data-autosave="true" <?php checked( ! empty( $settings['hide_help_dropdown'] ) ); ?>>
						<span class="dwm-toggle-slider"></span>
					</label>
				</div>

				<div class="dwm-form-group dwm-form-group--toggle dwm-form-group--toggle-vertical">
					<div class="dwm-form-group-info">
						<label class="dwm-form-label" for="dwm-hide-screen-options"><?php esc_html_e( 'Hide Screen Options', 'dashboard-widget-manager' ); ?></label>
					</div>
					<label class="dwm-toggle" for="dwm-hide-screen-options">
						<input type="checkbox" id="dwm-hide-screen-options" name="settings[hide_screen_options]" value="1" data-autosave="true" <?php checked( ! empty( $settings['hide_screen_options'] ) ); ?>>
						<span class="dwm-toggle-slider"></span>
					</label>
				</div>

				<div class="dwm-form-group dwm-form-group--toggle dwm-form-group--toggle-vertical">
					<div class="dwm-form-group-info">
						<label class="dwm-form-label" for="dwm-hide-inline-notices"><?php esc_html_e( 'Hide Notices', 'dashboard-widget-manager' ); ?></label>
					</div>
					<label class="dwm-toggle" for="dwm-hide-inline-notices">
						<input type="checkbox" id="dwm-hide-inline-notices" name="settings[hide_inline_notices]" value="1" data-autosave="true" <?php checked( ! empty( $settings['hide_inline_notices'] ) ); ?>>
						<span class="dwm-toggle-slider"></span>
					</label>
				</div>
			</div>

		</div>

		<!-- Hide Widgets -->
		<div id="dwm-section-hide-widgets" class="dwm-section dwm-customize-dashboard-section">
			<?php
			$title_raw         = esc_html__( 'Hide Widgets', 'dashboard-widget-manager' );
			$is_pro_only 	   = true;
			$help_modal_target = 'dwm-docs-modal';
			$help_icon_label   = __( 'Learn about hiding dashboard widgets', 'dashboard-widget-manager' );
			$attrs             = 'data-docs-page="custom-dashboard-hide-widgets"';
			include __DIR__ . '/partials/section-header-with-actions.php';
			unset( $title_raw, $help_modal_target, $help_icon_label, $attrs, $actions_html );
			?>

			<div class="dwm-form-group">
				<div class="dwm-form-label-row dwm-form-label-row--inline-controls">
					<p class="dwm-form-label"><?php esc_html_e( 'Hide Default Widgets', 'dashboard-widget-manager' ); ?></p>
					<div class="dwm-table-controls dwm-table-controls--inline">
						<a href="#" id="dwm-select-all-widgets"><?php esc_html_e( 'Select All', 'dashboard-widget-manager' ); ?></a>
						<span>/</span>
						<a href="#" id="dwm-deselect-all-widgets"><?php esc_html_e( 'Deselect All', 'dashboard-widget-manager' ); ?></a>
					</div>
				</div>
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

			<div class="dwm-form-group">
				<?php
				$third_party_widgets    = DWM_Admin::get_instance()->get_third_party_dashboard_widgets_for_settings();
				$hidden_third_party_raw = $settings['hidden_third_party_dashboard_widgets'] ?? '';
				$hidden_third_party_arr = empty( $hidden_third_party_raw )
					? array()
					: array_filter( array_map( 'trim', explode( "\n", $hidden_third_party_raw ) ) );
				?>
				<input type="hidden" name="settings[hidden_third_party_dashboard_widgets]" id="dwm-hidden-third-party-widgets-value" value="<?php echo esc_attr( $hidden_third_party_raw ); ?>">

				<?php if ( ! empty( $third_party_widgets ) ) : ?>
					<div class="dwm-form-label-row dwm-form-label-row--inline-controls">
						<p class="dwm-form-label"><?php esc_html_e( 'Hide 3rd-Party Widgets', 'dashboard-widget-manager' ); ?></p>
						<div class="dwm-table-controls dwm-table-controls--inline">
							<a href="#" id="dwm-select-all-third-party-widgets"><?php esc_html_e( 'Select All', 'dashboard-widget-manager' ); ?></a>
							<span>/</span>
							<a href="#" id="dwm-deselect-all-third-party-widgets"><?php esc_html_e( 'Deselect All', 'dashboard-widget-manager' ); ?></a>
						</div>
					</div>
					<div class="dwm-form-control">
						<div class="dwm-tables-grid">
							<?php foreach ( $third_party_widgets as $widget_id => $widget_label ) : ?>
								<label class="dwm-table-checkbox-label" title="<?php echo esc_attr( $widget_label ); ?>">
									<input type="checkbox" class="dwm-third-party-widget-hide-checkbox" value="<?php echo esc_attr( $widget_id ); ?>"
										<?php checked( in_array( $widget_id, $hidden_third_party_arr, true ) ); ?>>
									<?php echo esc_html( $widget_label ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				<?php else : ?>
					<div class="dwm-form-label-row">
						<p class="dwm-form-label"><?php esc_html_e( 'Hide 3rd-Party Widgets', 'dashboard-widget-manager' ); ?></p>
					</div>
					<div class="dwm-form-control">
						<p class="description"><?php esc_html_e( 'No 3rd-party dashboard widgets were detected.', 'dashboard-widget-manager' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

		</div>

		<!-- Dashboard Layout -->
		<div id="dwm-section-dashboard-layout" class="dwm-section dwm-customize-dashboard-section">
			<?php
			$title_raw         = esc_html__( 'Dashboard Layout', 'dashboard-widget-manager' );
			$is_pro_only 	   = true;
			$help_modal_target = 'dwm-docs-modal';
			$help_icon_label   = __( 'Learn about dashboard layout controls', 'dashboard-widget-manager' );
			$attrs             = 'data-docs-page="custom-dashboard-layout"';
			include __DIR__ . '/partials/section-header-with-actions.php';
			unset( $title_raw, $help_modal_target, $help_icon_label, $attrs, $actions_html );
			$bg_type          = (string) ( $settings['dashboard_background_type'] ?? 'solid' );
			$bg_type          = in_array( $bg_type, array( 'solid', 'gradient' ), true ) ? $bg_type : 'solid';
			$bg_gradient_type = (string) ( $settings['dashboard_bg_gradient_type'] ?? 'linear' );
			$bg_gradient_type = in_array( $bg_gradient_type, array( 'linear', 'radial' ), true ) ? $bg_gradient_type : 'linear';
			$bg_angle         = (int) ( $settings['dashboard_bg_gradient_angle'] ?? 90 );
			$bg_start_pos     = (int) ( $settings['dashboard_bg_gradient_start_position'] ?? 0 );
			$bg_end_pos       = (int) ( $settings['dashboard_bg_gradient_end_position'] ?? 100 );
			?>

			<div class="dwm-customize-block-row dwm-customize-block-row--background-padding">
				<div class="dwm-customize-block">
					<div class="dwm-customize-toggle-row">
						<div class="dwm-customize-toggle-copy">
							<span class="dwm-form-label"><?php esc_html_e( 'Custom Background', 'dashboard-widget-manager' ); ?></span>
						</div>
						<label class="dwm-toggle" for="dwm-dashboard-background-enabled">
							<input type="checkbox" id="dwm-dashboard-background-enabled" name="settings[dashboard_background_enabled]" value="1" data-toggle-controls="#dwm-dashboard-background-controls" <?php checked( ! empty( $settings['dashboard_background_enabled'] ) ); ?>>
							<span class="dwm-toggle-slider"></span>
						</label>
					</div>
					<div id="dwm-dashboard-background-controls" class="dwm-toggle-controlled<?php echo ! empty( $settings['dashboard_background_enabled'] ) ? '' : ' dwm-hidden-by-toggle'; ?>">
						<div class="dwm-form-row">
							<div class="dwm-form-group">
								<label for="dwm-background-type"><?php esc_html_e( 'Background Type', 'dashboard-widget-manager' ); ?></label>
								<select id="dwm-background-type" name="settings[dashboard_background_type]" class="dwm-select">
									<option value="solid" <?php selected( $bg_type, 'solid' ); ?>><?php esc_html_e( 'Solid Color', 'dashboard-widget-manager' ); ?></option>
									<option value="gradient" <?php selected( $bg_type, 'gradient' ); ?>><?php esc_html_e( 'Gradient', 'dashboard-widget-manager' ); ?></option>
								</select>
							</div>
							<div class="dwm-form-group dwm-bg-controls-column">
								<div class="dwm-bg-solid-controls" id="dwm-bg-solid-controls" style="<?php echo 'solid' === $bg_type ? '' : 'display:none;'; ?>">
									<label for="dwm-bg-solid-color" class="dwm-label-sm-margin"><?php esc_html_e( 'Background Color', 'dashboard-widget-manager' ); ?></label>
									<input type="color" id="dwm-bg-solid-color" name="settings[dashboard_bg_solid_color]" value="<?php echo esc_attr( $settings['dashboard_bg_solid_color'] ?? '#ffffff' ); ?>">
								</div>
								<div class="dwm-bg-gradient-controls dwm-form-group" id="dwm-bg-gradient-type-controls" style="<?php echo 'gradient' === $bg_type ? '' : 'display:none;'; ?>">
									<label for="dwm-bg-gradient-type"><?php esc_html_e( 'Gradient Type', 'dashboard-widget-manager' ); ?></label>
									<select id="dwm-bg-gradient-type" name="settings[dashboard_bg_gradient_type]" class="dwm-select">
										<option value="linear" <?php selected( $bg_gradient_type, 'linear' ); ?>><?php esc_html_e( 'Linear', 'dashboard-widget-manager' ); ?></option>
										<option value="radial" <?php selected( $bg_gradient_type, 'radial' ); ?>><?php esc_html_e( 'Radial', 'dashboard-widget-manager' ); ?></option>
									</select>
								</div>
							</div>
						</div>

						<div class="dwm-gradient-details-row" id="dwm-bg-gradient-details" style="<?php echo 'gradient' === $bg_type ? '' : 'display:none;'; ?>">
							<div class="dwm-gradient-controls-left">
								<div class="dwm-gradient-angle-group dwm-form-group" id="dwm-bg-gradient-angle-wrap" style="<?php echo 'linear' === $bg_gradient_type ? '' : 'display:none;'; ?>">
									<label for="dwm-bg-gradient-angle"><?php esc_html_e( 'Angle', 'dashboard-widget-manager' ); ?></label>
									<div class="dwm-angle-control">
										<input type="range" id="dwm-bg-gradient-angle" name="settings[dashboard_bg_gradient_angle]" min="0" max="360" value="<?php echo esc_attr( (string) $bg_angle ); ?>" class="dwm-format-slider">
										<span class="dwm-angle-value" id="dwm-bg-gradient-angle-value"><?php echo esc_html( (string) $bg_angle ); ?>°</span>
									</div>
								</div>
								<div class="dwm-gradient-stops dwm-form-group">
									<label><?php esc_html_e( 'Start Color', 'dashboard-widget-manager' ); ?></label>
									<div class="dwm-gradient-stop">
										<input type="color" id="dwm-bg-gradient-start" name="settings[dashboard_bg_gradient_start]" class="dwm-stop-color" value="<?php echo esc_attr( $settings['dashboard_bg_gradient_start'] ?? '#667eea' ); ?>">
										<input type="range" id="dwm-bg-gradient-start-position" name="settings[dashboard_bg_gradient_start_position]" class="dwm-stop-position" min="0" max="100" value="<?php echo esc_attr( (string) $bg_start_pos ); ?>">
										<span class="dwm-stop-label" id="dwm-bg-gradient-start-position-label"><?php echo esc_html( (string) $bg_start_pos ); ?>%</span>
									</div>
								<div class="dwm-gradient-stops dwm-form-group">
									<label><?php esc_html_e( 'End Color', 'dashboard-widget-manager' ); ?></label>
									<div class="dwm-gradient-stop">
										<input type="color" id="dwm-bg-gradient-end" name="settings[dashboard_bg_gradient_end]" class="dwm-stop-color" value="<?php echo esc_attr( $settings['dashboard_bg_gradient_end'] ?? '#764ba2' ); ?>">
										<input type="range" id="dwm-bg-gradient-end-position" name="settings[dashboard_bg_gradient_end_position]" class="dwm-stop-position" min="0" max="100" value="<?php echo esc_attr( (string) $bg_end_pos ); ?>">
										<span class="dwm-stop-label" id="dwm-bg-gradient-end-position-label"><?php echo esc_html( (string) $bg_end_pos ); ?>%</span>
									</div>
								</div>
								</div>
							</div>
							<div class="dwm-gradient-preview" id="dwm-bg-gradient-preview"></div>
						</div>
					</div>
				</div>

				<div class="dwm-customize-block">
					<div class="dwm-customize-toggle-row">
						<div class="dwm-customize-toggle-copy">
							<span class="dwm-form-label"><?php esc_html_e( 'Custom Padding', 'dashboard-widget-manager' ); ?></span>
						</div>
						<label class="dwm-toggle" for="dwm-dashboard-padding-enabled">
							<input type="checkbox" id="dwm-dashboard-padding-enabled" name="settings[dashboard_padding_enabled]" value="1" data-toggle-controls="#dwm-dashboard-padding-controls" <?php checked( ! empty( $settings['dashboard_padding_enabled'] ) ); ?>>
							<span class="dwm-toggle-slider"></span>
						</label>
					</div>
					<div id="dwm-dashboard-padding-controls" class="dwm-toggle-controlled<?php echo ! empty( $settings['dashboard_padding_enabled'] ) ? '' : ' dwm-hidden-by-toggle'; ?>">
						<div class="dwm-padding-controls">
							<div class="dwm-subsection-label-row dwm-no-align-items">
								<label class="dwm-subsection-label"><?php esc_html_e( 'Page Padding', 'dashboard-widget-manager' ); ?></label>
								<button type="button" class="dwm-link-btn<?php echo ! empty( $settings['dashboard_padding_linked'] ) ? ' is-linked' : ''; ?>" data-group="dashboard-padding" aria-label="<?php esc_attr_e( 'Link padding values', 'dashboard-widget-manager' ); ?>">
									<span class="dashicons dashicons-admin-links"></span>
								</button>
								<input type="hidden" name="settings[dashboard_padding_linked]" value="<?php echo ! empty( $settings['dashboard_padding_linked'] ) ? '1' : '0'; ?>" class="dwm-link-value" data-group="dashboard-padding">
							</div>
							<div class="dwm-form-row dwm-padding-grid-with-link">
								<?php foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) : ?>
									<?php
									$value_key = 'dashboard_padding_' . $side . '_value';
									$unit_key  = 'dashboard_padding_' . $side . '_unit';
									$side_val  = isset( $settings[ $value_key ] ) ? (string) $settings[ $value_key ] : '20';
									$side_unit = isset( $settings[ $unit_key ] ) ? (string) $settings[ $unit_key ] : 'px';
									?>
									<div class="dwm-form-group dwm-padding-input-group">
										<label for="dwm-padding-<?php echo esc_attr( $side ); ?>-value"><?php echo esc_html( ucfirst( $side ) ); ?></label>
										<div class="dwm-size-control">
											<input type="number" id="dwm-padding-<?php echo esc_attr( $side ); ?>-value" name="settings[<?php echo esc_attr( $value_key ); ?>]" class="dwm-padding-value dwm-padding-value" data-side="<?php echo esc_attr( $side ); ?>" min="0" max="300" value="<?php echo esc_attr( $side_val ); ?>">
											<select id="dwm-padding-<?php echo esc_attr( $side ); ?>-unit" name="settings[<?php echo esc_attr( $unit_key ); ?>]" class="dwm-padding-unit dwm-padding-unit dwm-linked-unit-select" data-group="dashboard-padding" data-side="<?php echo esc_attr( $side ); ?>">
												<option value="px" <?php selected( $side_unit, 'px' ); ?>>px</option>
												<option value="%" <?php selected( $side_unit, '%' ); ?>>%</option>
												<option value="rem" <?php selected( $side_unit, 'rem' ); ?>>rem</option>
												<option value="em" <?php selected( $side_unit, 'em' ); ?>>em</option>
												<option value="vh" <?php selected( $side_unit, 'vh' ); ?>>vh</option>
												<option value="vw" <?php selected( $side_unit, 'vw' ); ?>>vw</option>
											</select>
										</div>
										<input type="range" id="dwm-padding-<?php echo esc_attr( $side ); ?>-slider" class="dwm-padding-slider dwm-padding-slider" data-side="<?php echo esc_attr( $side ); ?>" min="0" max="300" value="<?php echo esc_attr( $side_val ); ?>">
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>

		<!-- Dashboard Branding -->
		<div id="dwm-section-dashboard-branding" class="dwm-section dwm-customize-dashboard-section">
			<?php
			$title_raw         = esc_html__( 'Dashboard Branding', 'dashboard-widget-manager' );
			$is_pro_only 	   = true;
			$help_modal_target = 'dwm-docs-modal';
			$help_icon_label   = __( 'Learn about dashboard branding controls', 'dashboard-widget-manager' );
			$attrs             = 'data-docs-page="custom-dashboard-branding"';
			include __DIR__ . '/partials/section-header-with-actions.php';
			unset( $title_raw, $help_modal_target, $help_icon_label, $attrs, $actions_html );
			$bg_type          = (string) ( $settings['dashboard_background_type'] ?? 'solid' );
			$bg_type          = in_array( $bg_type, array( 'solid', 'gradient' ), true ) ? $bg_type : 'solid';
			$bg_gradient_type = (string) ( $settings['dashboard_bg_gradient_type'] ?? 'linear' );
			$bg_gradient_type = in_array( $bg_gradient_type, array( 'linear', 'radial' ), true ) ? $bg_gradient_type : 'linear';
			$bg_angle         = (int) ( $settings['dashboard_bg_gradient_angle'] ?? 90 );
			$bg_start_pos     = (int) ( $settings['dashboard_bg_gradient_start_position'] ?? 0 );
			$bg_end_pos       = (int) ( $settings['dashboard_bg_gradient_end_position'] ?? 100 );
			$title_mode       = (string) ( $settings['dashboard_title_mode'] ?? 'default' );
			$title_mode       = in_array( $title_mode, array( 'default', 'hide', 'custom' ), true ) ? $title_mode : 'default';
			$logo_height      = (int) ( $settings['dashboard_logo_height'] ?? 56 );
			$logo_alignment   = (string) ( $settings['dashboard_logo_alignment'] ?? 'left' );
			$logo_alignment   = in_array( $logo_alignment, array( 'left', 'center', 'right' ), true ) ? $logo_alignment : 'left';
			?>

			<!-- Dashboard Title Row -->
			<div class="dwm-branding-title-row">
				<div class="dwm-branding-title-dropdowns">
					<div class="dwm-form-group">
						<?php
						$hero_logo_mode = sanitize_key( (string) ( $settings['dashboard_hero_logo_mode'] ?? 'disabled' ) );
						if ( ! in_array( $hero_logo_mode, array( 'disabled', 'hero_logo', 'logo_only', 'hero_only' ), true ) ) {
							$hero_logo_mode = 'disabled';
						}
						?>
						<label class="dwm-form-label" for="dwm-dashboard-hero-logo-mode"><?php esc_html_e( 'Hero & Logo', 'dashboard-widget-manager' ); ?></label>
						<select id="dwm-dashboard-hero-logo-mode" name="settings[dashboard_hero_logo_mode]" class="dwm-select">
							<option value="disabled" <?php selected( $hero_logo_mode, 'disabled' ); ?>><?php esc_html_e( 'Disabled', 'dashboard-widget-manager' ); ?></option>
							<option value="hero_logo" <?php selected( $hero_logo_mode, 'hero_logo' ); ?>><?php esc_html_e( 'Hero + Logo', 'dashboard-widget-manager' ); ?></option>
							<option value="logo_only" <?php selected( $hero_logo_mode, 'logo_only' ); ?>><?php esc_html_e( 'Logo Only', 'dashboard-widget-manager' ); ?></option>
							<option value="hero_only" <?php selected( $hero_logo_mode, 'hero_only' ); ?>><?php esc_html_e( 'Hero Only', 'dashboard-widget-manager' ); ?></option>
						</select>
					</div>
					<div class="dwm-branding-title-col">
						<div class="dwm-form-group">
							<label class="dwm-form-label" for="dwm-dashboard-title-mode"><?php esc_html_e( 'Dashboard Title', 'dashboard-widget-manager' ); ?></label>
							<select id="dwm-dashboard-title-mode" name="settings[dashboard_title_mode]" class="dwm-select dwm-branding-title-select">
								<option value="default" <?php selected( $title_mode, 'default' ); ?>><?php esc_html_e( 'Default', 'dashboard-widget-manager' ); ?></option>
								<option value="hide" <?php selected( $title_mode, 'hide' ); ?>><?php esc_html_e( 'Hide Title', 'dashboard-widget-manager' ); ?></option>
								<option value="custom" <?php selected( $title_mode, 'custom' ); ?>><?php esc_html_e( 'Custom Title', 'dashboard-widget-manager' ); ?></option>
							</select>
						</div>
						<div id="dwm-dashboard-title-custom-controls" class="dwm-toggle-controlled<?php echo 'custom' === $title_mode ? '' : ' dwm-hidden-by-toggle'; ?>">
							<div class="dwm-form-group">
								<div class="dwm-title-label-row">
									<label class="dwm-form-label" for="dwm-dashboard-title-text"><?php esc_html_e( 'Dashboard Title Text', 'dashboard-widget-manager' ); ?></label>
									<button type="button" class="dwm-format-icon-btn dwm-title-format-icon-btn" data-field="dashboard_title" data-open-modal="dwm-title-format-modal" title="<?php esc_attr_e( 'Format title text', 'dashboard-widget-manager' ); ?>">
										<span class="dashicons dashicons-admin-customizer"></span>
									</button>
								</div>
								<input type="text" id="dwm-dashboard-title-text" name="settings[dashboard_title_text]" value="<?php echo esc_attr( (string) ( $settings['dashboard_title_text'] ?? '' ) ); ?>">
							</div>
							<input type="hidden" id="dashboard_title_font_family" name="settings[dashboard_title_font_family]" value="<?php echo esc_attr( (string) ( $settings['dashboard_title_font_family'] ?? 'inherit' ) ); ?>">
							<input type="hidden" id="dashboard_title_font_size" name="settings[dashboard_title_font_size]" value="<?php echo esc_attr( (string) ( $settings['dashboard_title_font_size'] ?? '32px' ) ); ?>">
							<input type="hidden" id="dashboard_title_font_weight" name="settings[dashboard_title_font_weight]" value="<?php echo esc_attr( (string) ( $settings['dashboard_title_font_weight'] ?? '700' ) ); ?>">
							<input type="hidden" id="dashboard_title_alignment" name="settings[dashboard_title_alignment]" value="<?php echo esc_attr( (string) ( $settings['dashboard_title_alignment'] ?? 'left' ) ); ?>">
							<input type="hidden" id="dashboard_title_color" name="settings[dashboard_title_color]" value="<?php echo esc_attr( (string) ( $settings['dashboard_title_color'] ?? '#1d2327' ) ); ?>">
						</div>
					</div>
				</div>
			</div>
			<?php
			$hero_mode_has_hero   = in_array( $hero_logo_mode, array( 'hero_logo', 'hero_only' ), true );
			$hero_min_height      = max( 1, (int) ( $settings['dashboard_hero_min_height'] ?? 1 ) );
			$hero_min_height_unit = (string) ( $settings['dashboard_hero_min_height_unit'] ?? 'px' );
			?>
			<div id="dwm-hero-theme-row" class="dwm-hero-theme-dimensions-row<?php echo 'disabled' !== $hero_logo_mode ? '' : ' dwm-hidden-by-toggle'; ?>">
				<div class="dwm-form-group">
					<label class="dwm-form-label" id="dwm-alignment-row-label"><?php echo 'hero_only' === $hero_logo_mode ? esc_html__( 'Text Alignment', 'dashboard-widget-manager' ) : esc_html__( 'Logo Alignment', 'dashboard-widget-manager' ); ?></label>
					<input type="hidden" id="dwm-dashboard-logo-alignment" name="settings[dashboard_logo_alignment]" value="<?php echo esc_attr( $logo_alignment ); ?>">
					<div class="dwm-logo-align-buttons" role="group" aria-label="<?php esc_attr_e( 'Alignment', 'dashboard-widget-manager' ); ?>">
						<button type="button" class="dwm-logo-align-btn<?php echo 'left' === $logo_alignment ? ' is-active' : ''; ?>" data-align="left" aria-label="<?php esc_attr_e( 'Align Left', 'dashboard-widget-manager' ); ?>">
							<span class="dashicons dashicons-editor-alignleft"></span>
						</button>
						<button type="button" class="dwm-logo-align-btn<?php echo 'center' === $logo_alignment ? ' is-active' : ''; ?>" data-align="center" aria-label="<?php esc_attr_e( 'Align Center', 'dashboard-widget-manager' ); ?>">
							<span class="dashicons dashicons-editor-aligncenter"></span>
						</button>
						<button type="button" class="dwm-logo-align-btn<?php echo 'right' === $logo_alignment ? ' is-active' : ''; ?>" data-align="right" aria-label="<?php esc_attr_e( 'Align Right', 'dashboard-widget-manager' ); ?>">
							<span class="dashicons dashicons-editor-alignright"></span>
						</button>
					</div>
				</div>
				<div class="dwm-form-group<?php echo $hero_mode_has_hero ? '' : ' dwm-hidden-by-toggle'; ?>" id="dwm-hero-dimensions-group">
					<div class="dwm-form-row">
						<div class="dwm-form-group">
							<label for="dwm-hero-min-height"><?php esc_html_e( 'Min Height', 'dashboard-widget-manager' ); ?></label>
							<div class="dwm-size-control">
								<input type="number" id="dwm-hero-min-height" name="settings[dashboard_hero_min_height]" min="1" max="1000" value="<?php echo esc_attr( (string) $hero_min_height ); ?>">
								<select id="dwm-hero-min-height-unit" name="settings[dashboard_hero_min_height_unit]">
									<?php foreach ( array( 'px', '%', 'rem', 'em', 'vh' ) as $u ) : ?>
										<option value="<?php echo esc_attr( $u ); ?>" <?php selected( $hero_min_height_unit, $u ); ?>><?php echo esc_html( $u ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="dwm-hero-title-row" class="dwm-form-group<?php echo $hero_mode_has_hero ? '' : ' dwm-hidden-by-toggle'; ?>">
				<div class="dwm-title-label-row">
					<label for="dwm-dashboard-hero-title"><?php esc_html_e( 'Hero Title', 'dashboard-widget-manager' ); ?></label>
					<button type="button" class="dwm-format-icon-btn dwm-title-format-icon-btn" data-field="dashboard_hero_title" data-open-modal="dwm-title-format-modal" title="<?php esc_attr_e( 'Format hero title text', 'dashboard-widget-manager' ); ?>">
						<span class="dashicons dashicons-admin-customizer"></span>
					</button>
				</div>
				<input type="text" id="dwm-dashboard-hero-title" name="settings[dashboard_hero_title]" value="<?php echo esc_attr( (string) ( $settings['dashboard_hero_title'] ?? '' ) ); ?>">
				<input type="hidden" id="dashboard_hero_title_font_family" name="settings[dashboard_hero_title_font_family]" value="<?php echo esc_attr( (string) ( $settings['dashboard_hero_title_font_family'] ?? 'inherit' ) ); ?>">
				<input type="hidden" id="dashboard_hero_title_font_size" name="settings[dashboard_hero_title_font_size]" value="<?php echo esc_attr( (string) ( $settings['dashboard_hero_title_font_size'] ?? '28px' ) ); ?>">
				<input type="hidden" id="dashboard_hero_title_font_weight" name="settings[dashboard_hero_title_font_weight]" value="<?php echo esc_attr( (string) ( $settings['dashboard_hero_title_font_weight'] ?? '700' ) ); ?>">
				<input type="hidden" id="dashboard_hero_title_alignment" name="settings[dashboard_hero_title_alignment]" value="<?php echo esc_attr( (string) ( $settings['dashboard_hero_title_alignment'] ?? 'left' ) ); ?>">
				<input type="hidden" id="dashboard_hero_title_color" name="settings[dashboard_hero_title_color]" value="<?php echo esc_attr( (string) ( $settings['dashboard_hero_title_color'] ?? '#ffffff' ) ); ?>">
			</div>
			<div id="dwm-hero-message-row" class="dwm-form-group<?php echo $hero_mode_has_hero ? '' : ' dwm-hidden-by-toggle'; ?>">
				<div class="dwm-title-label-row">
					<label for="dwm-dashboard-hero-message"><?php esc_html_e( 'Hero Message', 'dashboard-widget-manager' ); ?></label>
					<button type="button" class="dwm-format-icon-btn dwm-title-format-icon-btn" data-field="dashboard_hero_message" data-open-modal="dwm-title-format-modal" title="<?php esc_attr_e( 'Format hero message text', 'dashboard-widget-manager' ); ?>">
						<span class="dashicons dashicons-admin-customizer"></span>
					</button>
				</div>
				<?php
				wp_editor(
					(string) ( $settings['dashboard_hero_message'] ?? '' ),
					'dwm-dashboard-hero-message',
					array(
						'textarea_name' => 'settings[dashboard_hero_message]',
						'textarea_rows' => 5,
						'media_buttons' => false,
						'teeny'         => true,
						'quicktags'     => true,
					)
				);
				?>
				<input type="hidden" id="dashboard_hero_message_font_family" name="settings[dashboard_hero_message_font_family]" value="<?php echo esc_attr( (string) ( $settings['dashboard_hero_message_font_family'] ?? 'inherit' ) ); ?>">
				<input type="hidden" id="dashboard_hero_message_font_size" name="settings[dashboard_hero_message_font_size]" value="<?php echo esc_attr( (string) ( $settings['dashboard_hero_message_font_size'] ?? '24px' ) ); ?>">
				<input type="hidden" id="dashboard_hero_message_font_weight" name="settings[dashboard_hero_message_font_weight]" value="<?php echo esc_attr( (string) ( $settings['dashboard_hero_message_font_weight'] ?? '700' ) ); ?>">
				<input type="hidden" id="dashboard_hero_message_alignment" name="settings[dashboard_hero_message_alignment]" value="<?php echo esc_attr( (string) ( $settings['dashboard_hero_message_alignment'] ?? 'left' ) ); ?>">
				<input type="hidden" id="dashboard_hero_message_color" name="settings[dashboard_hero_message_color]" value="<?php echo esc_attr( (string) ( $settings['dashboard_hero_message_color'] ?? '#ffffff' ) ); ?>">
			</div>
			<?php
				$logo_height_unit       = (string) ( $settings['dashboard_logo_height_unit'] ?? 'px' );
				$logo_bg_type           = sanitize_key( (string) ( $settings['dashboard_logo_bg_type'] ?? 'default' ) );
				$logo_bg_type           = in_array( $logo_bg_type, array( 'default', 'solid', 'gradient' ), true ) ? $logo_bg_type : 'default';
				$logo_bg_gradient_type  = sanitize_key( (string) ( $settings['dashboard_logo_bg_gradient_type'] ?? 'linear' ) );
				$logo_bg_gradient_type  = in_array( $logo_bg_gradient_type, array( 'linear', 'radial' ), true ) ? $logo_bg_gradient_type : 'linear';
				$logo_bg_angle          = max( 0, min( 360, (int) ( $settings['dashboard_logo_bg_gradient_angle'] ?? 90 ) ) );
				$logo_bg_start_pos      = max( 0, min( 100, (int) ( $settings['dashboard_logo_bg_gradient_start_position'] ?? 0 ) ) );
				$logo_bg_end_pos        = max( 0, min( 100, (int) ( $settings['dashboard_logo_bg_gradient_end_position'] ?? 100 ) ) );
				$logo_border_style      = (string) ( $settings['dashboard_logo_border_style'] ?? 'none' );
				$logo_border_color      = (string) ( $settings['dashboard_logo_border_color'] ?? '#dddddd' );
				$logo_border_radius_unit = (string) ( $settings['dashboard_logo_border_radius_unit'] ?? 'px' );
				$logo_border_unit       = (string) ( $settings['dashboard_logo_border_unit'] ?? 'px' );
				$logo_padding_unit      = (string) ( $settings['dashboard_logo_padding_unit'] ?? 'px' );
				$logo_margin_unit       = (string) ( $settings['dashboard_logo_margin_unit'] ?? 'px' );
				$logo_padding_linked    = ! empty( $settings['dashboard_logo_padding_linked'] );
				$logo_margin_linked     = ! empty( $settings['dashboard_logo_margin_linked'] );
				$logo_border_linked     = ! empty( $settings['dashboard_logo_border_linked'] );
				$logo_radius_tl         = (int) ( $settings['dashboard_logo_border_radius_tl'] ?? 0 );
				$logo_radius_tr         = (int) ( $settings['dashboard_logo_border_radius_tr'] ?? 0 );
				$logo_radius_br         = (int) ( $settings['dashboard_logo_border_radius_br'] ?? 0 );
				$logo_radius_bl         = (int) ( $settings['dashboard_logo_border_radius_bl'] ?? 0 );
				$logo_radius_linked     = ! empty( $settings['dashboard_logo_border_radius_linked'] );
			?>
			<div id="dwm-hero-logo-style-row" class="dwm-hero-logo-style-row<?php echo 'disabled' !== $hero_logo_mode ? '' : ' dwm-hidden-by-toggle'; ?>">
				<div class="dwm-subsection-label-row dwm-hero-logo-style-label-row">
					<label class="dwm-form-label dwm-subsection-label" id="dwm-style-target-label"><?php echo 'logo_only' === $hero_logo_mode ? esc_html__( 'Logo Style', 'dashboard-widget-manager' ) : esc_html__( 'Hero Style', 'dashboard-widget-manager' ); ?></label>
				</div>
				<div class="dwm-logo-style-block dwm-logo-background-block">
					<label class="dwm-form-label dwm-subsection-label"><?php esc_html_e( 'Background', 'dashboard-widget-manager' ); ?></label>
					<div class="dwm-form-row">
						<div class="dwm-form-group">
							<label for="dwm-logo-background-type" class="dwm-label-sm-margin"><?php esc_html_e( 'Type', 'dashboard-widget-manager' ); ?></label>
							<select id="dwm-logo-background-type" name="settings[dashboard_logo_bg_type]" class="dwm-select">
								<option value="default" <?php selected( $logo_bg_type, 'default' ); ?>><?php esc_html_e( 'Default', 'dashboard-widget-manager' ); ?></option>
								<option value="solid" <?php selected( $logo_bg_type, 'solid' ); ?>><?php esc_html_e( 'Solid Color', 'dashboard-widget-manager' ); ?></option>
								<option value="gradient" <?php selected( $logo_bg_type, 'gradient' ); ?>><?php esc_html_e( 'Gradient', 'dashboard-widget-manager' ); ?></option>
							</select>
						</div>
						<div class="dwm-form-group dwm-bg-controls-column">
							<div class="dwm-bg-solid-controls" id="dwm-logo-bg-solid-controls" style="<?php echo 'solid' === $logo_bg_type ? '' : 'display:none;'; ?>">
								<label for="dwm-logo-bg-solid-color" class="dwm-label-sm-margin"><?php esc_html_e( 'Color', 'dashboard-widget-manager' ); ?></label>
								<input type="color" id="dwm-logo-bg-solid-color" name="settings[dashboard_logo_bg_solid_color]" value="<?php echo esc_attr( $settings['dashboard_logo_bg_solid_color'] ?? '#ffffff' ); ?>">
							</div>
							<div class="dwm-bg-gradient-controls dwm-form-group" id="dwm-logo-bg-gradient-type-controls" style="<?php echo 'gradient' === $logo_bg_type ? '' : 'display:none;'; ?>">
								<label for="dwm-logo-bg-gradient-type"><?php esc_html_e( 'Gradient Type', 'dashboard-widget-manager' ); ?></label>
								<select id="dwm-logo-bg-gradient-type" name="settings[dashboard_logo_bg_gradient_type]" class="dwm-select">
									<option value="linear" <?php selected( $logo_bg_gradient_type, 'linear' ); ?>><?php esc_html_e( 'Linear', 'dashboard-widget-manager' ); ?></option>
									<option value="radial" <?php selected( $logo_bg_gradient_type, 'radial' ); ?>><?php esc_html_e( 'Radial', 'dashboard-widget-manager' ); ?></option>
								</select>
							</div>
						</div>
					</div>
					<div class="dwm-gradient-details-row" id="dwm-logo-bg-gradient-details" style="<?php echo 'gradient' === $logo_bg_type ? '' : 'display:none;'; ?>">
						<div class="dwm-gradient-controls-left">
							<div class="dwm-gradient-angle-group" id="dwm-logo-bg-gradient-angle-wrap" style="<?php echo 'linear' === $logo_bg_gradient_type ? '' : 'display:none;'; ?>">
								<label for="dwm-logo-bg-gradient-angle"><?php esc_html_e( 'Angle', 'dashboard-widget-manager' ); ?></label>
								<div class="dwm-angle-control">
									<input type="range" id="dwm-logo-bg-gradient-angle" name="settings[dashboard_logo_bg_gradient_angle]" min="0" max="360" value="<?php echo esc_attr( (string) $logo_bg_angle ); ?>" class="dwm-format-slider">
									<span class="dwm-angle-value" id="dwm-logo-bg-gradient-angle-value"><?php echo esc_html( (string) $logo_bg_angle ); ?>°</span>
								</div>
							</div>
							<label><?php esc_html_e( 'Color Stops', 'dashboard-widget-manager' ); ?></label>
							<div class="dwm-gradient-stops">
								<div class="dwm-gradient-stop">
									<input type="color" id="dwm-logo-bg-gradient-start" name="settings[dashboard_logo_bg_gradient_start]" class="dwm-stop-color" value="<?php echo esc_attr( $settings['dashboard_logo_bg_gradient_start'] ?? '#667eea' ); ?>">
									<input type="range" id="dwm-logo-bg-gradient-start-position" name="settings[dashboard_logo_bg_gradient_start_position]" class="dwm-stop-position" min="0" max="100" value="<?php echo esc_attr( (string) $logo_bg_start_pos ); ?>">
									<span class="dwm-stop-label" id="dwm-logo-bg-gradient-start-position-label"><?php echo esc_html( (string) $logo_bg_start_pos ); ?>%</span>
								</div>
								<div class="dwm-gradient-stop">
									<input type="color" id="dwm-logo-bg-gradient-end" name="settings[dashboard_logo_bg_gradient_end]" class="dwm-stop-color" value="<?php echo esc_attr( $settings['dashboard_logo_bg_gradient_end'] ?? '#764ba2' ); ?>">
									<input type="range" id="dwm-logo-bg-gradient-end-position" name="settings[dashboard_logo_bg_gradient_end_position]" class="dwm-stop-position" min="0" max="100" value="<?php echo esc_attr( (string) $logo_bg_end_pos ); ?>">
									<span class="dwm-stop-label" id="dwm-logo-bg-gradient-end-position-label"><?php echo esc_html( (string) $logo_bg_end_pos ); ?>%</span>
								</div>
							</div>
						</div>
						<div class="dwm-gradient-preview" id="dwm-logo-bg-gradient-preview"></div>
					</div>
				</div>
				<div class="dwm-logo-style-block">
					<div class="dwm-linked-inputs" data-group="logo-margin">
						<div class="dwm-subsection-label-row">
							<label class="dwm-form-label dwm-subsection-label"><?php esc_html_e( 'Margin', 'dashboard-widget-manager' ); ?></label>
							<button type="button" class="dwm-link-btn<?php echo $logo_margin_linked ? ' is-linked' : ''; ?>" data-group="logo-margin" aria-label="<?php esc_attr_e( 'Link margin values', 'dashboard-widget-manager' ); ?>">
								<span class="dashicons dashicons-admin-links"></span>
							</button>
							<input type="hidden" name="settings[dashboard_logo_margin_linked]" value="<?php echo esc_attr( $logo_margin_linked ? '1' : '0' ); ?>" class="dwm-link-value" data-group="logo-margin">
						</div>
						<div class="dwm-linked-inputs-row">
							<div class="dwm-linked-input-item">
								<label for="dwm-logo-margin-top"><?php esc_html_e( 'Top', 'dashboard-widget-manager' ); ?></label>
								<input type="number" id="dwm-logo-margin-top" name="settings[dashboard_logo_margin_top]" min="-200" max="200" value="<?php echo esc_attr( (string) ( $settings['dashboard_logo_margin_top'] ?? 0 ) ); ?>">
							</div>
							<div class="dwm-linked-input-item">
								<label for="dwm-logo-margin-right"><?php esc_html_e( 'Right', 'dashboard-widget-manager' ); ?></label>
								<input type="number" id="dwm-logo-margin-right" name="settings[dashboard_logo_margin_right]" min="-200" max="200" value="<?php echo esc_attr( (string) ( $settings['dashboard_logo_margin_right'] ?? 0 ) ); ?>">
							</div>
							<div class="dwm-linked-input-item">
								<label for="dwm-logo-margin-bottom"><?php esc_html_e( 'Bottom', 'dashboard-widget-manager' ); ?></label>
								<input type="number" id="dwm-logo-margin-bottom" name="settings[dashboard_logo_margin_bottom]" min="-200" max="200" value="<?php echo esc_attr( (string) ( $settings['dashboard_logo_margin_bottom'] ?? 0 ) ); ?>">
							</div>
							<div class="dwm-linked-input-item">
								<label for="dwm-logo-margin-left"><?php esc_html_e( 'Left', 'dashboard-widget-manager' ); ?></label>
								<input type="number" id="dwm-logo-margin-left" name="settings[dashboard_logo_margin_left]" min="-200" max="200" value="<?php echo esc_attr( (string) ( $settings['dashboard_logo_margin_left'] ?? 0 ) ); ?>">
							</div>
							<div class="dwm-linked-input-item dwm-linked-input-item--unit">
								<label for="dwm-logo-margin-unit"><?php esc_html_e( 'Unit', 'dashboard-widget-manager' ); ?></label>
								<select id="dwm-logo-margin-unit" name="settings[dashboard_logo_margin_unit]" class="dwm-linked-unit-select">
									<option value="px" <?php selected( $logo_margin_unit, 'px' ); ?>>px</option>
									<option value="%" <?php selected( $logo_margin_unit, '%' ); ?>>%</option>
									<option value="rem" <?php selected( $logo_margin_unit, 'rem' ); ?>>rem</option>
									<option value="em" <?php selected( $logo_margin_unit, 'em' ); ?>>em</option>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class="dwm-logo-style-block">
					<div class="dwm-subsection-label-row">
						<label class="dwm-form-label dwm-subsection-label"><?php esc_html_e( 'Border', 'dashboard-widget-manager' ); ?></label>
						<button type="button" id="dwm-logo-border-link-btn" class="dwm-link-btn<?php echo $logo_border_linked ? ' is-linked' : ''; ?><?php echo 'none' === $logo_border_style ? ' dwm-hidden-by-toggle' : ''; ?>" data-group="logo-border" aria-label="<?php esc_attr_e( 'Link border values', 'dashboard-widget-manager' ); ?>">
							<span class="dashicons dashicons-admin-links"></span>
						</button>
					</div>
					<div class="dwm-logo-border-style-row">
						<div class="dwm-logo-border-input-group">
							<label for="dwm-logo-border-style"><?php esc_html_e( 'Style', 'dashboard-widget-manager' ); ?></label>
							<select id="dwm-logo-border-style" name="settings[dashboard_logo_border_style]">
								<option value="none" <?php selected( $logo_border_style, 'none' ); ?>><?php esc_html_e( 'None', 'dashboard-widget-manager' ); ?></option>
								<option value="solid" <?php selected( $logo_border_style, 'solid' ); ?>><?php esc_html_e( 'Solid', 'dashboard-widget-manager' ); ?></option>
								<option value="dashed" <?php selected( $logo_border_style, 'dashed' ); ?>><?php esc_html_e( 'Dashed', 'dashboard-widget-manager' ); ?></option>
								<option value="dotted" <?php selected( $logo_border_style, 'dotted' ); ?>><?php esc_html_e( 'Dotted', 'dashboard-widget-manager' ); ?></option>
								<option value="double" <?php selected( $logo_border_style, 'double' ); ?>><?php esc_html_e( 'Double', 'dashboard-widget-manager' ); ?></option>
							</select>
						</div>
						<div id="dwm-logo-border-color-wrap" class="dwm-logo-border-input-group<?php echo 'none' === $logo_border_style ? ' dwm-hidden-by-toggle' : ''; ?>">
							<label for="dwm-logo-border-color"><?php esc_html_e( 'Color', 'dashboard-widget-manager' ); ?></label>
							<input type="color" id="dwm-logo-border-color" name="settings[dashboard_logo_border_color]" value="<?php echo esc_attr( $logo_border_color ); ?>">
						</div>
					</div>
					<div class="dwm-linked-inputs<?php echo 'none' === $logo_border_style ? ' dwm-hidden-by-toggle' : ''; ?>" data-group="logo-border">
						<input type="hidden" name="settings[dashboard_logo_border_linked]" value="<?php echo esc_attr( $logo_border_linked ? '1' : '0' ); ?>" class="dwm-link-value" data-group="logo-border">
						<div class="dwm-linked-inputs-row">
							<div class="dwm-linked-input-item">
								<label for="dwm-logo-border-top"><?php esc_html_e( 'Top', 'dashboard-widget-manager' ); ?></label>
								<input type="number" id="dwm-logo-border-top" name="settings[dashboard_logo_border_top]" min="0" max="20" value="<?php echo esc_attr( (string) ( $settings['dashboard_logo_border_top'] ?? 0 ) ); ?>">
							</div>
							<div class="dwm-linked-input-item">
								<label for="dwm-logo-border-right"><?php esc_html_e( 'Right', 'dashboard-widget-manager' ); ?></label>
								<input type="number" id="dwm-logo-border-right" name="settings[dashboard_logo_border_right]" min="0" max="20" value="<?php echo esc_attr( (string) ( $settings['dashboard_logo_border_right'] ?? 0 ) ); ?>">
							</div>
							<div class="dwm-linked-input-item">
								<label for="dwm-logo-border-bottom"><?php esc_html_e( 'Bottom', 'dashboard-widget-manager' ); ?></label>
								<input type="number" id="dwm-logo-border-bottom" name="settings[dashboard_logo_border_bottom]" min="0" max="20" value="<?php echo esc_attr( (string) ( $settings['dashboard_logo_border_bottom'] ?? 0 ) ); ?>">
							</div>
							<div class="dwm-linked-input-item">
								<label for="dwm-logo-border-left"><?php esc_html_e( 'Left', 'dashboard-widget-manager' ); ?></label>
								<input type="number" id="dwm-logo-border-left" name="settings[dashboard_logo_border_left]" min="0" max="20" value="<?php echo esc_attr( (string) ( $settings['dashboard_logo_border_left'] ?? 0 ) ); ?>">
							</div>
							<div class="dwm-linked-input-item dwm-linked-input-item--unit">
								<label for="dwm-logo-border-unit"><?php esc_html_e( 'Unit', 'dashboard-widget-manager' ); ?></label>
								<select id="dwm-logo-border-unit" name="settings[dashboard_logo_border_unit]" class="dwm-linked-unit-select">
									<option value="px" <?php selected( $logo_border_unit, 'px' ); ?>>px</option>
									<option value="rem" <?php selected( $logo_border_unit, 'rem' ); ?>>rem</option>
									<option value="em" <?php selected( $logo_border_unit, 'em' ); ?>>em</option>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div id="dwm-logo-radius-block" class="dwm-logo-style-block<?php echo 'none' === $logo_border_style ? ' dwm-hidden-by-toggle' : ''; ?>">
					<div class="dwm-linked-inputs" data-group="logo-radius">
						<div class="dwm-subsection-label-row">
							<label class="dwm-form-label dwm-subsection-label"><?php esc_html_e( 'Border Radius', 'dashboard-widget-manager' ); ?></label>
							<button type="button" class="dwm-link-btn<?php echo $logo_radius_linked ? ' is-linked' : ''; ?>" data-group="logo-radius" aria-label="<?php esc_attr_e( 'Link radius values', 'dashboard-widget-manager' ); ?>">
								<span class="dashicons dashicons-admin-links"></span>
							</button>
							<input type="hidden" name="settings[dashboard_logo_border_radius_linked]" value="<?php echo esc_attr( $logo_radius_linked ? '1' : '0' ); ?>" class="dwm-link-value" data-group="logo-radius">
						</div>
						<div class="dwm-linked-inputs-row">
							<div class="dwm-linked-input-item">
								<label for="dwm-logo-radius-tl"><?php esc_html_e( 'TL', 'dashboard-widget-manager' ); ?></label>
								<input type="number" id="dwm-logo-radius-tl" name="settings[dashboard_logo_border_radius_tl]" min="0" max="200" value="<?php echo esc_attr( (string) $logo_radius_tl ); ?>">
							</div>
							<div class="dwm-linked-input-item">
								<label for="dwm-logo-radius-tr"><?php esc_html_e( 'TR', 'dashboard-widget-manager' ); ?></label>
								<input type="number" id="dwm-logo-radius-tr" name="settings[dashboard_logo_border_radius_tr]" min="0" max="200" value="<?php echo esc_attr( (string) $logo_radius_tr ); ?>">
							</div>
							<div class="dwm-linked-input-item">
								<label for="dwm-logo-radius-br"><?php esc_html_e( 'BR', 'dashboard-widget-manager' ); ?></label>
								<input type="number" id="dwm-logo-radius-br" name="settings[dashboard_logo_border_radius_br]" min="0" max="200" value="<?php echo esc_attr( (string) $logo_radius_br ); ?>">
							</div>
							<div class="dwm-linked-input-item">
								<label for="dwm-logo-radius-bl"><?php esc_html_e( 'BL', 'dashboard-widget-manager' ); ?></label>
								<input type="number" id="dwm-logo-radius-bl" name="settings[dashboard_logo_border_radius_bl]" min="0" max="200" value="<?php echo esc_attr( (string) $logo_radius_bl ); ?>">
							</div>
							<div class="dwm-linked-input-item dwm-linked-input-item--unit">
								<label for="dwm-logo-radius-unit"><?php esc_html_e( 'Unit', 'dashboard-widget-manager' ); ?></label>
								<select id="dwm-logo-radius-unit" name="settings[dashboard_logo_border_radius_unit]" class="dwm-linked-unit-select">
									<option value="px" <?php selected( $logo_border_radius_unit, 'px' ); ?>>px</option>
									<option value="%" <?php selected( $logo_border_radius_unit, '%' ); ?>>%</option>
									<option value="rem" <?php selected( $logo_border_radius_unit, 'rem' ); ?>>rem</option>
									<option value="em" <?php selected( $logo_border_radius_unit, 'em' ); ?>>em</option>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>


			<!-- Custom Logo Section -->
			<div class="dwm-branding-logo-section">
				<?php
				$logo_mode_has_logo = in_array( $hero_logo_mode, array( 'hero_logo', 'logo_only' ), true );
				?>
				<input type="hidden" id="dwm-dashboard-logo-enabled" name="settings[dashboard_logo_enabled]" value="<?php echo $logo_mode_has_logo ? '1' : '0'; ?>">
				<div id="dwm-dashboard-logo-controls" class="dwm-toggle-controlled<?php echo $logo_mode_has_logo ? '' : ' dwm-hidden-by-toggle'; ?>">
					<div class="dwm-customize-block-row--logo-config">

						<!-- Col 1: Logo Controls -->
						<div class="dwm-logo-config-col dwm-logo-config-col--controls">
							<button type="button" class="dwm-button dwm-button-primary dwm-dashboard-media-pick dwm-logo-choose-button<?php echo ! empty( $settings['dashboard_logo_url'] ) ? ' dwm-hidden-by-toggle' : ''; ?>" data-target-input="#dwm-dashboard-logo-url"><?php esc_html_e( 'Choose Logo', 'dashboard-widget-manager' ); ?></button>
							<input type="hidden" id="dwm-dashboard-logo-url" name="settings[dashboard_logo_url]" value="<?php echo esc_attr( (string) ( $settings['dashboard_logo_url'] ?? '' ) ); ?>">
							<div id="dwm-dashboard-logo-size-controls" class="dwm-logo-size-controls<?php echo ! empty( $settings['dashboard_logo_url'] ) ? '' : ' dwm-hidden-by-toggle'; ?>">
								<label class="dwm-subsection-label"><?php esc_html_e( 'Size and Alignment', 'dashboard-widget-manager' ); ?></label>
								<label for="dwm-dashboard-logo-height" class="dwm-logo-field-label"><?php esc_html_e( 'Logo Height', 'dashboard-widget-manager' ); ?></label>
								<div class="dwm-logo-size-control-wrapper">
									<input type="range" id="dwm-dashboard-logo-height-slider" class="dwm-format-slider" min="1" max="320" value="<?php echo esc_attr( (string) $logo_height ); ?>">
									<div class="dwm-logo-size-inputs">
										<input type="number" id="dwm-dashboard-logo-height" name="settings[dashboard_logo_height]" min="1" max="500" value="<?php echo esc_attr( (string) $logo_height ); ?>">
										<select id="dwm-dashboard-logo-height-unit" name="settings[dashboard_logo_height_unit]">
											<option value="px" <?php selected( $logo_height_unit, 'px' ); ?>>px</option>
											<option value="%" <?php selected( $logo_height_unit, '%' ); ?>>%</option>
											<option value="rem" <?php selected( $logo_height_unit, 'rem' ); ?>>rem</option>
											<option value="em" <?php selected( $logo_height_unit, 'em' ); ?>>em</option>
											<option value="vh" <?php selected( $logo_height_unit, 'vh' ); ?>>vh</option>
										</select>
									</div>
								</div>
							</div>
						</div>

						<!-- Col 2: Style Controls -->
						<div id="dwm-dashboard-logo-style-col" class="dwm-logo-config-col dwm-logo-config-col--style<?php echo ! empty( $settings['dashboard_logo_url'] ) ? '' : ' dwm-hidden-by-toggle'; ?>">

							<div id="dwm-logo-border-block" class="dwm-logo-style-block<?php echo 'none' === $logo_border_style ? '' : ' has-following-group-divider'; ?>">
								<div class="dwm-linked-inputs" data-group="logo-padding">
									<div class="dwm-subsection-label-row">
										<label class="dwm-form-label dwm-subsection-label"><?php esc_html_e( 'Padding', 'dashboard-widget-manager' ); ?></label>
										<button type="button" class="dwm-link-btn<?php echo $logo_padding_linked ? ' is-linked' : ''; ?>" data-group="logo-padding" aria-label="<?php esc_attr_e( 'Link padding values', 'dashboard-widget-manager' ); ?>">
											<span class="dashicons dashicons-admin-links"></span>
										</button>
										<input type="hidden" name="settings[dashboard_logo_padding_linked]" value="<?php echo esc_attr( $logo_padding_linked ? '1' : '0' ); ?>" class="dwm-link-value" data-group="logo-padding">
									</div>
									<div class="dwm-linked-inputs-row">
										<div class="dwm-linked-input-item">
											<label for="dwm-logo-padding-top"><?php esc_html_e( 'Top', 'dashboard-widget-manager' ); ?></label>
											<input type="number" id="dwm-logo-padding-top" name="settings[dashboard_logo_padding_top]" min="0" max="200" value="<?php echo esc_attr( (string) ( $settings['dashboard_logo_padding_top'] ?? 10 ) ); ?>">
										</div>
										<div class="dwm-linked-input-item">
											<label for="dwm-logo-padding-right"><?php esc_html_e( 'Right', 'dashboard-widget-manager' ); ?></label>
											<input type="number" id="dwm-logo-padding-right" name="settings[dashboard_logo_padding_right]" min="0" max="200" value="<?php echo esc_attr( (string) ( $settings['dashboard_logo_padding_right'] ?? 10 ) ); ?>">
										</div>
										<div class="dwm-linked-input-item">
											<label for="dwm-logo-padding-bottom"><?php esc_html_e( 'Bottom', 'dashboard-widget-manager' ); ?></label>
											<input type="number" id="dwm-logo-padding-bottom" name="settings[dashboard_logo_padding_bottom]" min="0" max="200" value="<?php echo esc_attr( (string) ( $settings['dashboard_logo_padding_bottom'] ?? 10 ) ); ?>">
										</div>
										<div class="dwm-linked-input-item">
											<label for="dwm-logo-padding-left"><?php esc_html_e( 'Left', 'dashboard-widget-manager' ); ?></label>
											<input type="number" id="dwm-logo-padding-left" name="settings[dashboard_logo_padding_left]" min="0" max="200" value="<?php echo esc_attr( (string) ( $settings['dashboard_logo_padding_left'] ?? 10 ) ); ?>">
										</div>
										<div class="dwm-linked-input-item dwm-linked-input-item--unit">
											<label for="dwm-logo-padding-unit"><?php esc_html_e( 'Unit', 'dashboard-widget-manager' ); ?></label>
											<select id="dwm-logo-padding-unit" name="settings[dashboard_logo_padding_unit]" class="dwm-linked-unit-select">
												<option value="px" <?php selected( $logo_padding_unit, 'px' ); ?>>px</option>
												<option value="%" <?php selected( $logo_padding_unit, '%' ); ?>>%</option>
												<option value="rem" <?php selected( $logo_padding_unit, 'rem' ); ?>>rem</option>
												<option value="em" <?php selected( $logo_padding_unit, 'em' ); ?>>em</option>
											</select>
										</div>
									</div>
								</div>
							</div>




						</div>

						<!-- Col 3: Preview -->
							<div id="dwm-dashboard-logo-preview-col" class="dwm-logo-config-col dwm-logo-config-col--preview<?php echo ! empty( $settings['dashboard_logo_url'] ) ? '' : ' dwm-hidden-by-toggle'; ?>">
								<div id="dwm-dashboard-logo-link-options" class="dwm-logo-link-options<?php echo ! empty( $settings['dashboard_logo_url'] ) ? '' : ' dwm-hidden-by-toggle'; ?>">
									<div class="dwm-logo-link-field-label-row">
										<label for="dwm-dashboard-logo-link-url" class="dwm-subsection-label"><?php esc_html_e( 'Logo Link URL', 'dashboard-widget-manager' ); ?></label>
										<div class="dwm-logo-link-new-tab-inline">
											<span class="dwm-logo-link-new-tab-text"><?php esc_html_e( 'New Tab', 'dashboard-widget-manager' ); ?></span>
											<label class="dwm-toggle dwm-toggle--small dwm-logo-new-tab-toggle" for="dwm-dashboard-logo-link-new-tab">
												<input type="checkbox" id="dwm-dashboard-logo-link-new-tab" name="settings[dashboard_logo_link_new_tab]" value="1" <?php checked( ! empty( $settings['dashboard_logo_link_new_tab'] ) ); ?> />
											<span class="dwm-toggle-slider"></span>
										</label>
									</div>
								</div>
								<input type="url" id="dwm-dashboard-logo-link-url" name="settings[dashboard_logo_link_url]" value="<?php echo esc_attr( (string) ( $settings['dashboard_logo_link_url'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'https://example.com', 'dashboard-widget-manager' ); ?>">
							</div>
							<div class="dwm-dashboard-logo-preview-wrap<?php echo ! empty( $settings['dashboard_logo_url'] ) ? ' has-logo' : ''; ?>">
								<img id="dwm-dashboard-logo-preview" src="<?php echo esc_url( (string) ( $settings['dashboard_logo_url'] ?? '' ) ); ?>" alt="<?php esc_attr_e( 'Logo preview', 'dashboard-widget-manager' ); ?>" class="<?php echo empty( $settings['dashboard_logo_url'] ) ? 'is-empty' : ''; ?>">
								<button type="button" class="dwm-logo-replace-overlay" data-open-modal="#dwm-dashboard-logo-edit-modal"><?php esc_html_e( 'Edit Logo', 'dashboard-widget-manager' ); ?></button>
							</div>
						</div>

					</div>
			</div>

		</div>
	</div>

	<div id="dwm-dashboard-logo-edit-modal" class="dwm-modal dwm-dashboard-logo-edit-modal" role="dialog" aria-modal="true" aria-labelledby="dwm-dashboard-logo-edit-title">
			<div class="dwm-modal-overlay"></div>
			<div class="dwm-modal-content">
				<div class="dwm-modal-header">
					<h2 id="dwm-dashboard-logo-edit-title">
						<span class="dashicons dashicons-format-image"></span>
						<?php esc_html_e( 'Edit Dashboard Logo', 'dashboard-widget-manager' ); ?>
					</h2>
					<button type="button" class="dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div class="dwm-modal-body">
					<p><?php esc_html_e( 'Choose a different logo or remove the current one. Removing it will hide all logo-specific controls until a new logo is added.', 'dashboard-widget-manager' ); ?></p>
				</div>
				<div class="dwm-modal-footer">
					<button type="button" class="dwm-button dwm-button-secondary dwm-dashboard-logo-replace-action"><?php esc_html_e( 'Choose Different Logo', 'dashboard-widget-manager' ); ?></button>
					<button type="button" class="dwm-button dwm-button-danger dwm-dashboard-logo-remove-action"><?php esc_html_e( 'Remove Logo', 'dashboard-widget-manager' ); ?></button>
				</div>
			</div>
		</div>



		<!-- On-Load Announcement -->
		<div id="dwm-section-onload-announcement" class="dwm-section dwm-customize-dashboard-section">
			<?php
			$title_raw         = esc_html__( 'On-Load Announcement', 'dashboard-widget-manager' );
			$is_pro_only 	   = true;
			$help_modal_target = 'dwm-docs-modal';
			$help_icon_label   = __( 'Learn about dashboard on-load announcements', 'dashboard-widget-manager' );
			$attrs             = 'data-docs-page="custom-dashboard-on-load-announcement"';
			include __DIR__ . '/partials/section-header-with-actions.php';
			unset( $title_raw, $help_modal_target, $help_icon_label, $attrs, $actions_html );
			?>

			<div class="dwm-customize-block">
				<div class="dwm-customize-toggle-row">
					<div class="dwm-customize-toggle-copy">
						<span class="dwm-form-label"><?php esc_html_e( 'Enable On-Load Announcement', 'dashboard-widget-manager' ); ?></span>
					</div>
					<label class="dwm-toggle" for="dwm-dashboard-notice-enabled">
						<input type="checkbox" id="dwm-dashboard-notice-enabled" name="settings[dashboard_notice_enabled]" value="1" data-toggle-controls="#dwm-dashboard-notice-fields" <?php checked( ! empty( $settings['dashboard_notice_enabled'] ) ); ?>>
						<span class="dwm-toggle-slider"></span>
					</label>
				</div>

				<div id="dwm-dashboard-notice-fields" class="dwm-toggle-controlled<?php echo ! empty( $settings['dashboard_notice_enabled'] ) ? '' : ' dwm-hidden-by-toggle'; ?>">
					<?php
					$notice_type       = (string) ( $settings['dashboard_notice_type'] ?? 'toast' );
					$notice_level      = (string) ( $settings['dashboard_notice_level'] ?? 'info' );
					$notice_dismissible = ! empty( $settings['dashboard_notice_dismissible'] );
					$notice_auto_dismiss = (int) ( $settings['dashboard_notice_auto_dismiss'] ?? 6 );
					$notice_position   = (string) ( $settings['dashboard_notice_position'] ?? 'bottom-right' );
					$notice_frequency  = (string) ( $settings['dashboard_notice_frequency'] ?? 'always' );
					?>
					<div class="dwm-form-row">
						<div class="dwm-form-group">
							<label class="dwm-form-label" for="dwm-dashboard-notice-type"><?php esc_html_e( 'Display Type', 'dashboard-widget-manager' ); ?></label>
							<select id="dwm-dashboard-notice-type" name="settings[dashboard_notice_type]">
								<option value="toast" <?php selected( 'toast', $notice_type ); ?>><?php esc_html_e( 'Toast', 'dashboard-widget-manager' ); ?></option>
								<option value="popup" <?php selected( 'popup', $notice_type ); ?>><?php esc_html_e( 'Popup Modal', 'dashboard-widget-manager' ); ?></option>
								<option value="alert" <?php selected( 'alert', $notice_type ); ?>><?php esc_html_e( 'Inline Alert', 'dashboard-widget-manager' ); ?></option>
							</select>
						</div>
						<div class="dwm-form-group">
							<label class="dwm-form-label" for="dwm-dashboard-notice-level"><?php esc_html_e( 'Message Level', 'dashboard-widget-manager' ); ?></label>
							<select id="dwm-dashboard-notice-level" name="settings[dashboard_notice_level]">
								<option value="info" <?php selected( 'info', $notice_level ); ?>><?php esc_html_e( 'Info', 'dashboard-widget-manager' ); ?></option>
								<option value="success" <?php selected( 'success', $notice_level ); ?>><?php esc_html_e( 'Success', 'dashboard-widget-manager' ); ?></option>
								<option value="warning" <?php selected( 'warning', $notice_level ); ?>><?php esc_html_e( 'Warning', 'dashboard-widget-manager' ); ?></option>
								<option value="error" <?php selected( 'error', $notice_level ); ?>><?php esc_html_e( 'Error', 'dashboard-widget-manager' ); ?></option>
							</select>
						</div>
					</div>

					<div class="dwm-form-row">
						<div class="dwm-form-group">
							<label class="dwm-form-label" for="dwm-dashboard-notice-position"><?php esc_html_e( 'Toast Position', 'dashboard-widget-manager' ); ?></label>
							<select id="dwm-dashboard-notice-position" name="settings[dashboard_notice_position]">
								<option value="bottom-right" <?php selected( 'bottom-right', $notice_position ); ?>><?php esc_html_e( 'Bottom Right', 'dashboard-widget-manager' ); ?></option>
								<option value="bottom-left" <?php selected( 'bottom-left', $notice_position ); ?>><?php esc_html_e( 'Bottom Left', 'dashboard-widget-manager' ); ?></option>
								<option value="top-right" <?php selected( 'top-right', $notice_position ); ?>><?php esc_html_e( 'Top Right', 'dashboard-widget-manager' ); ?></option>
								<option value="top-left" <?php selected( 'top-left', $notice_position ); ?>><?php esc_html_e( 'Top Left', 'dashboard-widget-manager' ); ?></option>
							</select>
						</div>
						<div class="dwm-form-group">
							<label class="dwm-form-label" for="dwm-dashboard-notice-frequency"><?php esc_html_e( 'Frequency', 'dashboard-widget-manager' ); ?></label>
							<select id="dwm-dashboard-notice-frequency" name="settings[dashboard_notice_frequency]">
								<option value="always" <?php selected( 'always', $notice_frequency ); ?>><?php esc_html_e( 'Every Page Load', 'dashboard-widget-manager' ); ?></option>
								<option value="once-session" <?php selected( 'once-session', $notice_frequency ); ?>><?php esc_html_e( 'Once Per Session', 'dashboard-widget-manager' ); ?></option>
								<option value="once-day" <?php selected( 'once-day', $notice_frequency ); ?>><?php esc_html_e( 'Once Per Day', 'dashboard-widget-manager' ); ?></option>
							</select>
						</div>
					</div>

					<div class="dwm-form-row">
						<div class="dwm-form-group dwm-form-group--toggle dwm-form-group--toggle-vertical">
							<div class="dwm-form-group-info">
								<span class="dwm-form-label"><?php esc_html_e( 'Dismissible', 'dashboard-widget-manager' ); ?></span>
							</div>
							<label class="dwm-toggle" for="dwm-dashboard-notice-dismissible">
								<input type="checkbox" id="dwm-dashboard-notice-dismissible" name="settings[dashboard_notice_dismissible]" value="1" <?php checked( $notice_dismissible ); ?>>
								<span class="dwm-toggle-slider"></span>
							</label>
						</div>
						<div class="dwm-form-group">
							<label class="dwm-form-label" for="dwm-dashboard-notice-auto-dismiss"><?php esc_html_e( 'Auto-dismiss (seconds, 0 = never)', 'dashboard-widget-manager' ); ?></label>
							<input type="number" id="dwm-dashboard-notice-auto-dismiss" name="settings[dashboard_notice_auto_dismiss]" min="0" max="60" value="<?php echo esc_attr( (string) $notice_auto_dismiss ); ?>">
						</div>
					</div>

					<div class="dwm-form-group">
						<label class="dwm-form-label" for="dwm-dashboard-notice-title"><?php esc_html_e( 'Announcement Title', 'dashboard-widget-manager' ); ?></label>
						<input type="text" id="dwm-dashboard-notice-title" name="settings[dashboard_notice_title]" value="<?php echo esc_attr( (string) ( $settings['dashboard_notice_title'] ?? '' ) ); ?>">
					</div>

					<div class="dwm-form-group">
						<label class="dwm-form-label" for="dwm-dashboard-notice-message"><?php esc_html_e( 'Announcement Message', 'dashboard-widget-manager' ); ?></label>
						<textarea id="dwm-dashboard-notice-message" name="settings[dashboard_notice_message]" rows="3"><?php echo esc_textarea( (string) ( $settings['dashboard_notice_message'] ?? '' ) ); ?></textarea>
					</div>
				</div>
			</div>

			</div>

		<div id="dwm-title-format-modal" class="dwm-modal dwm-title-format-modal" role="dialog" aria-modal="true" aria-labelledby="dwm-title-format-modal-title">
			<div class="dwm-modal-overlay"></div>
			<div class="dwm-modal-content">
				<div class="dwm-modal-header">
					<h2 id="dwm-title-format-modal-title"><span class="dashicons dashicons-editor-textcolor"></span> <?php esc_html_e( 'Format Text', 'dashboard-widget-manager' ); ?></h2>
					<button type="button" class="dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div class="dwm-modal-body">
					<div class="dwm-format-quick-grid">
						<div class="dwm-format-card">
							<label for="dwm-title-format-font-family"><?php esc_html_e( 'Font Family', 'dashboard-widget-manager' ); ?></label>
							<select id="dwm-title-format-font-family">
								<option value="inherit"><?php esc_html_e( 'Default (Inherit)', 'dashboard-widget-manager' ); ?></option>
								<option value="Arial, sans-serif">Arial</option>
								<option value="'Helvetica Neue', Helvetica, sans-serif">Helvetica</option>
								<option value="Georgia, serif">Georgia</option>
								<option value="'Times New Roman', Times, serif">Times New Roman</option>
								<option value="'Courier New', Courier, monospace">Courier</option>
								<option value="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"><?php esc_html_e( 'System Font', 'dashboard-widget-manager' ); ?></option>
							</select>
						</div>
						<div class="dwm-format-card">
							<label><?php esc_html_e( 'Text Alignment', 'dashboard-widget-manager' ); ?></label>
							<div class="dwm-alignment-buttons">
								<button type="button" class="dwm-alignment-btn" data-align="left"><span class="dashicons dashicons-editor-alignleft"></span></button>
								<button type="button" class="dwm-alignment-btn" data-align="center"><span class="dashicons dashicons-editor-aligncenter"></span></button>
								<button type="button" class="dwm-alignment-btn" data-align="right"><span class="dashicons dashicons-editor-alignright"></span></button>
							</div>
						</div>
						<div class="dwm-format-card">
							<label for="dwm-title-format-font-size-value"><?php esc_html_e( 'Font Size', 'dashboard-widget-manager' ); ?></label>
							<div class="dwm-size-control">
								<input type="number" id="dwm-title-format-font-size-value" min="8" max="72" value="32">
								<select id="dwm-title-format-font-size-unit">
									<option value="px">px</option>
									<option value="rem">rem</option>
									<option value="em">em</option>
								</select>
							</div>
							<input type="range" id="dwm-title-format-font-size-slider" min="8" max="72" value="32" class="dwm-format-slider">
						</div>
						<div class="dwm-format-card">
							<label for="dwm-title-format-font-weight"><?php esc_html_e( 'Font Weight', 'dashboard-widget-manager' ); ?></label>
							<select id="dwm-title-format-font-weight">
								<option value="300"><?php esc_html_e( 'Light', 'dashboard-widget-manager' ); ?></option>
								<option value="400"><?php esc_html_e( 'Normal', 'dashboard-widget-manager' ); ?></option>
								<option value="500"><?php esc_html_e( 'Medium', 'dashboard-widget-manager' ); ?></option>
								<option value="600"><?php esc_html_e( 'Semi-Bold', 'dashboard-widget-manager' ); ?></option>
								<option value="700"><?php esc_html_e( 'Bold', 'dashboard-widget-manager' ); ?></option>
							</select>
						</div>
					</div>
					<div class="dwm-format-section dwm-format-color-section">
						<div class="dwm-section-heading"><label><?php esc_html_e( 'Text Color', 'dashboard-widget-manager' ); ?></label></div>
						<div class="dwm-color-controls">
							<div class="dwm-color-tabs">
								<button type="button" class="dwm-color-tab-btn active" data-tab="hex"><?php esc_html_e( 'Solid', 'dashboard-widget-manager' ); ?></button>
								<button type="button" class="dwm-color-tab-btn" data-tab="rgba"><?php esc_html_e( 'RGBA', 'dashboard-widget-manager' ); ?></button>
								<button type="button" class="dwm-color-tab-btn" data-tab="gradient"><?php esc_html_e( 'Gradient', 'dashboard-widget-manager' ); ?></button>
							</div>
							<div class="dwm-color-tab-wrapper">
								<div class="dwm-color-tab-content active" data-tab="hex">
									<label for="dwm-title-format-color"><?php esc_html_e( 'Hex Color', 'dashboard-widget-manager' ); ?></label>
									<div class="dwm-color-input-group">
										<input type="text" id="dwm-title-format-color" class="dwm-color-hex-input" value="#1d2327">
										<input type="color" id="dwm-title-color-wheel" value="#1d2327" class="dwm-native-color-picker">
									</div>
									<div class="dwm-color-presets">
										<label><?php esc_html_e( 'Presets:', 'dashboard-widget-manager' ); ?></label>
										<div class="dwm-preset-swatches">
											<button type="button" class="dwm-preset-swatch" style="background-color: #1e1e1e;" data-color="#1e1e1e" title="Black"></button>
											<button type="button" class="dwm-preset-swatch" style="background-color: #333333;" data-color="#333333" title="Dark Gray"></button>
											<button type="button" class="dwm-preset-swatch" style="background-color: #666666;" data-color="#666666" title="Gray"></button>
											<button type="button" class="dwm-preset-swatch" style="background-color: #999999;" data-color="#999999" title="Light Gray"></button>
											<button type="button" class="dwm-preset-swatch" style="background-color: #ffffff; border: 1px solid #ccc;" data-color="#ffffff" title="White"></button>
											<button type="button" class="dwm-preset-swatch" style="background-color: #0073aa;" data-color="#0073aa" title="WordPress Blue"></button>
										</div>
									</div>
								</div>
								<div class="dwm-color-tab-content" data-tab="rgba">
									<div class="dwm-rgba-inputs">
										<?php foreach ( array( 'r' => 29, 'g' => 35, 'b' => 39, 'a' => 100 ) as $key => $default_val ) : ?>
											<div class="dwm-rgba-input-group">
												<label for="dwm-title-rgba-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( strtoupper( $key ) ); ?></label>
												<input type="range" id="dwm-title-rgba-<?php echo esc_attr( $key ); ?>" min="0" max="<?php echo 'a' === $key ? '100' : '255'; ?>" value="<?php echo esc_attr( (string) $default_val ); ?>" class="dwm-rgba-slider">
												<input type="number" class="dwm-rgba-value" min="0" max="<?php echo 'a' === $key ? '100' : '255'; ?>" value="<?php echo esc_attr( (string) $default_val ); ?>">
											</div>
										<?php endforeach; ?>
									</div>
									<div class="dwm-rgba-preview" id="dwm-title-rgba-preview" style="background-color: rgba(29, 35, 39, 1);"></div>
								</div>
								<div class="dwm-color-tab-content" data-tab="gradient">
									<div class="dwm-gradient-controls">
										<div class="dwm-gradient-type-angle-row">
											<div class="dwm-gradient-type-group">
												<label for="dwm-title-gradient-type"><?php esc_html_e( 'Gradient Type', 'dashboard-widget-manager' ); ?></label>
												<select id="dwm-title-gradient-type">
													<option value="linear"><?php esc_html_e( 'Linear', 'dashboard-widget-manager' ); ?></option>
													<option value="radial"><?php esc_html_e( 'Radial', 'dashboard-widget-manager' ); ?></option>
												</select>
											</div>
											<div class="dwm-gradient-angle-group">
												<label for="dwm-title-gradient-angle"><?php esc_html_e( 'Angle', 'dashboard-widget-manager' ); ?></label>
												<div class="dwm-angle-control">
													<input type="range" id="dwm-title-gradient-angle" min="0" max="360" value="90" class="dwm-format-slider">
													<span class="dwm-angle-value" id="dwm-title-gradient-angle-value">90°</span>
												</div>
											</div>
										</div>
										<label><?php esc_html_e( 'Color Stops', 'dashboard-widget-manager' ); ?></label>
										<div class="dwm-gradient-stops">
											<div class="dwm-gradient-stop">
												<input type="color" class="dwm-stop-color" id="dwm-title-gradient-start" value="#667eea">
												<input type="range" min="0" max="100" value="0" class="dwm-stop-position" id="dwm-title-gradient-start-pos">
												<span class="dwm-stop-label" id="dwm-title-gradient-start-label">0%</span>
											</div>
											<div class="dwm-gradient-stop">
												<input type="color" class="dwm-stop-color" id="dwm-title-gradient-end" value="#764ba2">
												<input type="range" min="0" max="100" value="100" class="dwm-stop-position" id="dwm-title-gradient-end-pos">
												<span class="dwm-stop-label" id="dwm-title-gradient-end-label">100%</span>
											</div>
										</div>
									</div>
									<div class="dwm-gradient-preview" id="dwm-title-gradient-preview" style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="dwm-modal-footer">
					<button type="button" class="dwm-button dwm-button-primary" id="dwm-title-format-apply"><?php esc_html_e( 'Apply', 'dashboard-widget-manager' ); ?></button>
				</div>
			</div>
		</div>
	</form>
</div>

<?php include __DIR__ . '/partials/page-wrapper-end.php'; ?>
