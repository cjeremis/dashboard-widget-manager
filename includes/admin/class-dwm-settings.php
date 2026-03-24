<?php
/**
 * Settings Class
 *
 * Handles plugin settings.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 *
 * Manages plugin settings.
 */
class DWM_Settings {

	use DWM_Singleton;
	use DWM_AJAX_Handler;

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			'dwm_settings_group',
			'dwm_settings',
			array( $this, 'sanitize_settings' )
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $settings Settings array.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $settings ) {
		return DWM_Sanitizer::sanitize_settings( $settings );
	}

	/**
	 * Save settings via AJAX.
	 */
	public function ajax_save_settings() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}
		if ( ! DWM_Access_Control::current_user_can_access_plugin() ) {
			$this->send_error( __( 'You are restricted from accessing Dashboard Widget Manager.', 'dashboard-widget-manager' ), 403 );
			return;
		}

		$settings = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array();

		if ( empty( $settings ) ) {
			$this->send_error( __( 'Settings data is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		// Sanitize settings.
		$settings = DWM_Sanitizer::sanitize_settings( $settings );

		// Validate settings.
		$errors = DWM_Validator::validate_settings( $settings );
		if ( ! empty( $errors ) ) {
			$this->send_error( implode( ' ', $errors ), 400, array( 'errors' => $errors ) );
			return;
		}

		// Save settings.
		$data   = DWM_Data::get_instance();
		$result = $data->update_settings( $settings );

		if ( ! $result ) {
			$this->send_error( __( 'Failed to save settings.', 'dashboard-widget-manager' ), 500 );
			return;
		}

		// Check if any active widgets are now using excluded tables.
		$excluded_tables = $data->get_excluded_tables();
		$affected        = array();

		if ( ! empty( $excluded_tables ) ) {
			$active_widgets = $data->get_widgets( true );
			foreach ( $active_widgets as $widget ) {
				if ( ! empty( $widget['sql_query'] ) ) {
					$table_errors = DWM_Validator::validate_query_tables( $widget['sql_query'], $excluded_tables );
					if ( ! empty( $table_errors ) ) {
						$affected[] = $widget['name'];
					}
				}
			}
		}

		$response = array();
		if ( ! empty( $affected ) ) {
			$response['warning']          = sprintf(
				/* translators: 1: number of widgets, 2: comma-separated widget names */
				_n(
					'%1$d active widget is now using an excluded table and will fail to execute: %2$s',
					'%1$d active widgets are now using excluded tables and will fail to execute: %2$s',
					count( $affected ),
					'dashboard-widget-manager'
				),
				count( $affected ),
				implode( ', ', array_map( function( $n ) { return '"' . $n . '"'; }, $affected ) )
			);
			$response['affected_widgets'] = $affected;
		}

		$this->send_success( __( 'Settings saved successfully.', 'dashboard-widget-manager' ), $response );
	}

	/**
	 * Reset settings via AJAX.
	 */
	public function ajax_reset_settings() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}
		if ( ! DWM_Access_Control::current_user_can_access_plugin() ) {
			$this->send_error( __( 'You are restricted from accessing Dashboard Widget Manager.', 'dashboard-widget-manager' ), 403 );
			return;
		}

		$default_settings = $this->get_default_settings();

		$data   = DWM_Data::get_instance();
		$result = $data->update_settings( $default_settings );

		if ( ! $result ) {
			$this->send_error( __( 'Failed to reset settings.', 'dashboard-widget-manager' ), 500 );
			return;
		}

		$this->send_success(
			__( 'Settings reset successfully.', 'dashboard-widget-manager' ),
			array( 'settings' => $default_settings )
		);
	}

	/**
	 * Get default settings.
	 *
	 * @return array Default settings.
	 */
	public function get_default_settings() {
		$role_keys = DWM_Access_Control::get_all_role_keys();

		return array(
			'excluded_tables'             => '',
			'hide_help_dropdown'          => 0,
			'hide_screen_options'         => 0,
			'hide_inline_notices'         => 0,
			'hidden_dashboard_widgets'    => '',
			'hidden_third_party_dashboard_widgets' => '',
			'dashboard_branding_enabled'  => 0,
			'dashboard_logo_enabled'      => 0,
			'dashboard_background_enabled' => 0,
			'dashboard_background_type'   => 'default',
			'dashboard_bg_solid_color'    => '#ffffff',
			'dashboard_bg_gradient_type'  => 'linear',
			'dashboard_bg_gradient_angle' => 90,
			'dashboard_bg_gradient_start' => '#667eea',
			'dashboard_bg_gradient_start_position' => 0,
			'dashboard_bg_gradient_end'   => '#764ba2',
			'dashboard_bg_gradient_end_position' => 100,
			'dashboard_padding_enabled'   => 0,
			'dashboard_padding_linked'    => 1,
			'dashboard_padding_top_value' => 20,
			'dashboard_padding_top_unit'  => 'px',
			'dashboard_padding_right_value' => 20,
			'dashboard_padding_right_unit' => 'px',
			'dashboard_padding_bottom_value' => 20,
			'dashboard_padding_bottom_unit' => 'px',
			'dashboard_padding_left_value' => 20,
			'dashboard_padding_left_unit' => 'px',
			'dashboard_logo_url'          => '',
			'dashboard_logo_height'       => 56,
			'dashboard_logo_height_unit'  => 'px',
			'dashboard_logo_alignment'    => 'left',
			'dashboard_logo_link_enabled' => 0,
			'dashboard_logo_link_url'     => '',
			'dashboard_logo_link_new_tab' => 0,
			'dashboard_logo_bg_type'       => 'default',
			'dashboard_logo_bg_solid_color' => '#ffffff',
			'dashboard_logo_bg_gradient_type' => 'linear',
			'dashboard_logo_bg_gradient_angle' => 90,
			'dashboard_logo_bg_gradient_start' => '#667eea',
			'dashboard_logo_bg_gradient_start_position' => 0,
			'dashboard_logo_bg_gradient_end' => '#764ba2',
			'dashboard_logo_bg_gradient_end_position' => 100,
			'dashboard_logo_padding_top'   => 10,
			'dashboard_logo_padding_right' => 10,
			'dashboard_logo_padding_bottom' => 10,
			'dashboard_logo_padding_left'  => 10,
			'dashboard_logo_padding_unit'  => 'px',
			'dashboard_logo_padding_linked' => 1,
			'dashboard_logo_margin_top'    => 0,
			'dashboard_logo_margin_right'  => 0,
			'dashboard_logo_margin_bottom' => 0,
			'dashboard_logo_margin_left'   => 0,
			'dashboard_logo_margin_unit'   => 'px',
			'dashboard_logo_margin_linked' => 1,
			'dashboard_logo_border_top'    => 0,
			'dashboard_logo_border_right'  => 0,
			'dashboard_logo_border_bottom' => 0,
			'dashboard_logo_border_left'   => 0,
			'dashboard_logo_border_unit'   => 'px',
			'dashboard_logo_border_linked' => 1,
			'dashboard_logo_border_style'  => 'none',
			'dashboard_logo_border_color'  => '#dddddd',
			'dashboard_logo_border_radius_tl' => 0,
			'dashboard_logo_border_radius_tr' => 0,
			'dashboard_logo_border_radius_br' => 0,
			'dashboard_logo_border_radius_bl' => 0,
			'dashboard_logo_border_radius_unit' => 'px',
			'dashboard_logo_border_radius_linked' => 1,
			'dashboard_title_mode'        => 'default',
			'dashboard_title_text'        => '',
			'dashboard_title_font_family' => 'inherit',
			'dashboard_title_font_size'   => '32px',
			'dashboard_title_font_weight' => '700',
			'dashboard_title_alignment'   => 'left',
			'dashboard_title_color'       => '#1d2327',
			'dashboard_hero_enabled'      => 0,
			'dashboard_hero_theme'        => 'text-left',
			'dashboard_hero_title'        => __( 'Welcome to your custom dashboard', 'dashboard-widget-manager' ),
			'dashboard_hero_title_font_family' => 'inherit',
			'dashboard_hero_title_font_size'   => '28px',
			'dashboard_hero_title_font_weight' => '700',
			'dashboard_hero_title_alignment'   => 'left',
			'dashboard_hero_title_color'       => '#ffffff',
			'dashboard_hero_message'      => '',
			'dashboard_hero_background_type' => 'solid',
			'dashboard_hero_bg_solid_color'  => '#667eea',
			'dashboard_hero_bg_gradient_type' => 'linear',
			'dashboard_hero_bg_gradient_angle' => 90,
			'dashboard_hero_bg_gradient_start' => '#667eea',
			'dashboard_hero_bg_gradient_start_position' => 0,
			'dashboard_hero_bg_gradient_end' => '#764ba2',
			'dashboard_hero_bg_gradient_end_position' => 100,
			'dashboard_notice_enabled'      => 0,
			'dashboard_notice_type'         => 'toast',
			'dashboard_notice_level'        => 'info',
			'dashboard_notice_title'        => '',
			'dashboard_notice_message'      => '',
			'dashboard_notice_dismissible'  => 0,
			'dashboard_notice_auto_dismiss' => 6,
			'dashboard_notice_position'     => 'bottom-right',
			'dashboard_notice_frequency'    => 'always',
			'access_allowed_roles'          => implode( "\n", $role_keys ),
			'restricted_user_ids'         => '',
			'support_data_sharing_opt_in' => 0,
		);
	}

	/**
	 * Search users for Restricted Users picker.
	 */
	public function ajax_search_users() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}
		if ( ! DWM_Access_Control::current_user_can_access_plugin() ) {
			$this->send_error( __( 'You are restricted from accessing Dashboard Widget Manager.', 'dashboard-widget-manager' ), 403 );
			return;
		}

		$term_raw = isset( $_POST['term'] ) ? wp_unslash( $_POST['term'] ) : '';
		$term     = sanitize_text_field( (string) $term_raw );

		$exclude_raw = isset( $_POST['exclude_ids'] ) ? wp_unslash( $_POST['exclude_ids'] ) : array();
		if ( ! is_array( $exclude_raw ) ) {
			$exclude_raw = array();
		}
		$exclude_ids = array_map( 'absint', $exclude_raw );
		$exclude_ids = array_values( array_filter( $exclude_ids ) );

		$args = array(
			'number'         => 20,
			'orderby'        => 'display_name',
			'order'          => 'ASC',
			'exclude'        => $exclude_ids,
			'fields'         => array( 'ID', 'display_name', 'user_email', 'user_login' ),
			'search_columns' => array( 'user_login', 'user_nicename', 'display_name', 'user_email' ),
		);

		if ( '' !== $term ) {
			$args['search'] = '*' . $term . '*';
		}

		$users   = get_users( $args );
		$payload = array();
		foreach ( $users as $user ) {
			$payload[] = array(
				'id'           => (int) $user->ID,
				'display_name' => (string) $user->display_name,
				'user_login'   => (string) $user->user_login,
				'user_email'   => (string) $user->user_email,
			);
		}

		$this->send_success( __( 'Users loaded.', 'dashboard-widget-manager' ), array( 'users' => $payload ) );
	}
}
