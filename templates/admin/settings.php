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
$header_description = __( 'Configure security controls, support data sharing, and license settings.', 'dashboard-widget-manager' );
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

		<!-- Access Control -->
		<div id="dwm-section-access-control" class="dwm-section">
			<?php
			$title_raw         = esc_html__( 'Access Control', 'dashboard-widget-manager' ) . ' <span class="dwm-pro-badge">' . esc_html__( 'Pro', 'dashboard-widget-manager' ) . '</span>';
			$help_modal_target = 'dwm-docs-modal';
			$help_icon_label   = __( 'Learn about DWM access control', 'dashboard-widget-manager' );
			$attrs             = 'data-docs-page="settings-access-control"';
			include __DIR__ . '/partials/section-header-with-actions.php';
			unset( $title_raw, $help_modal_target, $help_icon_label, $attrs, $actions_html );

			$all_roles          = wp_roles()->roles;
			$all_role_keys      = array_keys( $all_roles );
			$allowed_roles_raw  = isset( $settings['access_allowed_roles'] ) ? (string) $settings['access_allowed_roles'] : implode( "\n", $all_role_keys );
			$allowed_roles_arr  = array_filter( array_map( 'trim', explode( "\n", $allowed_roles_raw ) ) );
			$allowed_roles_arr  = array_map( 'sanitize_key', $allowed_roles_arr );
			$allowed_roles_arr  = array_values( array_intersect( $allowed_roles_arr, $all_role_keys ) );
			$restricted_raw     = isset( $settings['restricted_user_ids'] ) ? (string) $settings['restricted_user_ids'] : '';
			$restricted_ids     = array_values( array_filter( array_map( 'absint', array_map( 'trim', explode( "\n", $restricted_raw ) ) ) ) );
			$restricted_users   = empty( $restricted_ids ) ? array() : get_users(
				array(
					'include' => $restricted_ids,
					'orderby' => 'display_name',
					'order'   => 'ASC',
				)
			);
			?>

			<div class="dwm-form-group">
				<div class="dwm-form-control">
					<input type="hidden" name="settings[access_allowed_roles]" id="dwm-access-allowed-roles-value" value="<?php echo esc_attr( $allowed_roles_raw ); ?>">
					<input type="hidden" name="settings[restricted_user_ids]" id="dwm-restricted-user-ids-value" value="<?php echo esc_attr( $restricted_raw ); ?>">

					<div class="dwm-table-controls">
						<a href="#" id="dwm-select-all-access-roles"><?php esc_html_e( 'Select All', 'dashboard-widget-manager' ); ?></a>
						<span>/</span>
						<a href="#" id="dwm-deselect-all-access-roles"><?php esc_html_e( 'Deselect All', 'dashboard-widget-manager' ); ?></a>
					</div>

					<div class="dwm-tables-grid">
						<?php foreach ( $all_roles as $role_key => $role_data ) : ?>
							<?php $role_label = translate_user_role( $role_data['name'] ?? $role_key ); ?>
							<label class="dwm-table-checkbox-label" title="<?php echo esc_attr( $role_label ); ?>">
								<input
									type="checkbox"
									class="dwm-access-role-checkbox"
									value="<?php echo esc_attr( $role_key ); ?>"
									<?php checked( in_array( $role_key, $allowed_roles_arr, true ) ); ?>
								>
								<?php echo esc_html( $role_label ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<div class="dwm-section-actions dwm-section-actions--between">
				<button type="button" class="dwm-button-secondary" data-open-modal="dwm-restricted-users-modal">
					<span class="dashicons dashicons-lock"></span>
					<?php esc_html_e( 'Restricted Users', 'dashboard-widget-manager' ); ?>
				</button>
				<button type="submit" class="dwm-button dwm-button-primary">
					<?php esc_html_e( 'Save Access Control', 'dashboard-widget-manager' ); ?>
				</button>
			</div>
		</div>

		<!-- Security -->
		<div id="dwm-section-table-allowlist" class="dwm-section">
			<?php
			$title_raw         = esc_html__( 'Table Allow List', 'dashboard-widget-manager' ) . ' <span class="dwm-pro-badge">' . esc_html__( 'Pro', 'dashboard-widget-manager' ) . '</span>';
			$help_modal_target = 'dwm-docs-modal';
			$help_icon_label   = __( 'Learn about the Table Allow List Setting', 'dashboard-widget-manager' );
			$attrs             = 'data-docs-page="feature-table-allowlist"';
			include __DIR__ . '/partials/section-header-with-actions.php';
			unset( $title_raw, $help_modal_target, $help_icon_label, $attrs, $actions_html );
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

		<!-- Support & Privacy -->
		<div id="dwm-section-support-privacy" class="dwm-section">
			<?php
			$title_raw         = esc_html__( 'Support & Privacy', 'dashboard-widget-manager' ) . ' <span class="dwm-pro-badge">' . esc_html__( 'Pro', 'dashboard-widget-manager' ) . '</span>';
			$help_modal_target = 'dwm-docs-modal';
			$help_icon_label   = __( 'Learn about support data sharing and legal disclosures', 'dashboard-widget-manager' );
			$attrs             = 'data-docs-page="category-overview-support"';
			include __DIR__ . '/partials/section-header-with-actions.php';
			unset( $title_raw, $help_modal_target, $help_icon_label, $attrs, $actions_html );

			$privacy_page_id = (int) get_option( 'tda_shared_privacy_page_id' );
			$terms_page_id   = (int) get_option( 'tda_shared_terms_page_id' );
			$privacy_url     = $privacy_page_id ? get_permalink( $privacy_page_id ) : 'https://topdevamerica.com/privacy-policy';
			$terms_url       = $terms_page_id ? get_permalink( $terms_page_id ) : 'https://topdevamerica.com/terms';
			?>

			<div class="dwm-form-group dwm-form-group--toggle dwm-form-group--toggle-stacked">
				<div class="dwm-toggle-label-row">
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
					<span class="dwm-form-label"><?php esc_html_e( 'Live Support Reply Sync', 'dashboard-widget-manager' ); ?></span>
				</div>

				<div class="dwm-form-group-info">
					<p class="description">
						<?php esc_html_e( 'When enabled, this plugin contacts TopDevAmerica servers to sync support reply notifications for your account. Data transmitted: your account email address and site URL. Disabled by default.', 'dashboard-widget-manager' ); ?>
					</p>
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

	<div id="dwm-restricted-users-modal" class="dwm-modal dwm-modal-lg" role="dialog" aria-modal="true" aria-labelledby="dwm-restricted-users-title">
		<div class="dwm-modal-overlay"></div>
		<div class="dwm-modal-content">
			<div class="dwm-modal-header">
				<h2 id="dwm-restricted-users-title"><span class="dashicons dashicons-lock"></span> <?php esc_html_e( 'Restricted Users', 'dashboard-widget-manager' ); ?></h2>
				<button type="button" class="dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="dwm-modal-body">
				<div class="dwm-restricted-users-search-wrap">
					<input
						type="search"
						id="dwm-restricted-users-search"
						class="dwm-search-input"
						placeholder="<?php esc_attr_e( 'Search restricted users by name, email, or username...', 'dashboard-widget-manager' ); ?>"
					>
				</div>
				<div id="dwm-restricted-users-list" class="dwm-restricted-users-list">
					<?php if ( empty( $restricted_users ) ) : ?>
						<p class="dwm-restricted-users-empty" id="dwm-restricted-users-empty"><?php esc_html_e( 'No users are currently restricted.', 'dashboard-widget-manager' ); ?></p>
					<?php else : ?>
						<?php foreach ( $restricted_users as $restricted_user ) : ?>
							<?php
							$user_search_blob = strtolower(
								trim(
									$restricted_user->display_name . ' ' .
									$restricted_user->user_email . ' ' .
									$restricted_user->user_login
								)
							);
							?>
							<div
								class="dwm-restricted-user-item"
								data-user-id="<?php echo esc_attr( (string) $restricted_user->ID ); ?>"
								data-search="<?php echo esc_attr( $user_search_blob ); ?>"
							>
								<div class="dwm-restricted-user-meta">
									<strong><?php echo esc_html( $restricted_user->display_name ); ?></strong>
									<span><?php echo esc_html( $restricted_user->user_email ); ?></span>
									<code><?php echo esc_html( $restricted_user->user_login ); ?></code>
								</div>
								<button type="button" class="dwm-button dwm-button-small dwm-button-danger dwm-restricted-user-remove">
									<?php esc_html_e( 'Remove', 'dashboard-widget-manager' ); ?>
								</button>
							</div>
						<?php endforeach; ?>
						<p class="dwm-restricted-users-empty" id="dwm-restricted-users-empty" style="display:none;"><?php esc_html_e( 'No users match your search.', 'dashboard-widget-manager' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<div class="dwm-modal-footer">
				<button type="button" class="dwm-button-primary" id="dwm-open-restricted-users-add" data-open-modal="dwm-restricted-users-add-modal">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Add New', 'dashboard-widget-manager' ); ?>
				</button>
				<button type="button" class="dwm-button dwm-button-secondary" data-close-modal>
					<?php esc_html_e( 'Done', 'dashboard-widget-manager' ); ?>
				</button>
			</div>
		</div>
	</div>

	<div id="dwm-restricted-users-add-modal" class="dwm-modal dwm-modal-md" role="dialog" aria-modal="true" aria-labelledby="dwm-restricted-users-add-title">
		<div class="dwm-modal-overlay"></div>
		<div class="dwm-modal-content">
			<div class="dwm-modal-header">
				<h2 id="dwm-restricted-users-add-title"><span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Add Restricted User', 'dashboard-widget-manager' ); ?></h2>
				<button type="button" class="dwm-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="dwm-modal-body">
				<input
					type="search"
					id="dwm-restricted-users-add-search"
					class="dwm-search-input"
					placeholder="<?php esc_attr_e( 'Search users by name, email, or username...', 'dashboard-widget-manager' ); ?>"
				>
				<div id="dwm-restricted-users-add-results" class="dwm-restricted-users-add-results">
					<p class="dwm-restricted-users-empty"><?php esc_html_e( 'Type to search for users.', 'dashboard-widget-manager' ); ?></p>
				</div>
			</div>
			<div class="dwm-modal-footer">
				<button type="button" class="dwm-button dwm-button-secondary" data-close-modal>
					<?php esc_html_e( 'Close', 'dashboard-widget-manager' ); ?>
				</button>
			</div>
		</div>
	</div>

</div>

<?php include __DIR__ . '/partials/page-wrapper-end.php'; ?>
