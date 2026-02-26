<?php
/**
 * Admin Partial Template - License Manager
 *
 * Handles markup rendering for the license manager admin partial template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set defaults
$license_key            = $license_key ?? '';
$license_status         = $license_status ?? 'inactive';
$is_license_active      = $is_license_active ?? false;
$has_saved_license      = $has_saved_license ?? ( ! empty( $license_key ) && $is_license_active );
$license_key_min_length = $license_key_min_length ?? 12;
$license_pattern        = $license_pattern ?? '[A-Za-z0-9\-]{12,64}';
$section_id             = $section_id ?? 'dwm-inline-license';
$section_title          = $section_title ?? __( 'Pro License', 'dashboard-widget-manager' );
$input_id               = $input_id ?? 'dwm-pro-license-key';
$input_name             = $input_name ?? 'dwm_pro_license_key';
$activate_button_id     = $activate_button_id ?? 'dwm-license-activate';
$deactivate_button_id   = $deactivate_button_id ?? 'dwm-license-deactivate';

$badge_variant = $is_license_active ? 'success' : 'inactive';
$badge_text    = $is_license_active ? __( 'Active', 'dashboard-widget-manager' ) : __( 'Inactive', 'dashboard-widget-manager' );
?>
<div class="dwm-section" id="<?php echo esc_attr( $section_id ); ?>" data-license-status="<?php echo esc_attr( $license_status ); ?>" data-min-length="<?php echo esc_attr( $license_key_min_length ); ?>">
	<?php
	$title_raw       = esc_html( $section_title ) . ' <span class="dwm-license-status-badge dwm-license-badge-' . esc_attr( $badge_variant ) . '" id="dwm-license-status-badge">' . esc_html( $badge_text ) . '</span>';
	$help_icon_label = __( 'View license help', 'dashboard-widget-manager' );
	include __DIR__ . '/section-header-with-actions.php';
	unset( $title_raw, $help_icon_label );
	?>

	<div class="dwm-form-group">
		<label for="<?php echo esc_attr( $input_id ); ?>"><?php esc_html_e( 'License Key', 'dashboard-widget-manager' ); ?></label>
		<div class="dwm-license-input-row">
			<input
				type="text"
				id="<?php echo esc_attr( $input_id ); ?>"
				name="<?php echo esc_attr( $input_name ); ?>"
				value="<?php echo esc_attr( $has_saved_license ? str_repeat( '*', strlen( $license_key ) ) : $license_key ); ?>"
				placeholder="<?php esc_attr_e( 'Enter or paste your license key', 'dashboard-widget-manager' ); ?>"
				minlength="12"
				maxlength="64"
				data-min-length="<?php echo esc_attr( $license_key_min_length ); ?>"
				pattern="<?php echo esc_attr( $license_pattern ); ?>"
				<?php echo $has_saved_license ? 'disabled="disabled"' : ''; ?>
				data-actual-key="<?php echo esc_attr( $license_key ); ?>"
				class="dwm-license-key-input"
			/>
			<button type="button" class="dwm-button dwm-button-primary" id="<?php echo esc_attr( $activate_button_id ); ?>" <?php echo $has_saved_license ? 'style="display:none;"' : ''; ?>>
				<?php echo esc_html( $is_license_active ? __( 'Refresh License', 'dashboard-widget-manager' ) : __( 'Activate License', 'dashboard-widget-manager' ) ); ?>
			</button>
			<?php if ( $has_saved_license ) : ?>
				<button type="button" class="dwm-button dwm-button-danger" id="<?php echo esc_attr( $deactivate_button_id ); ?>">
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e( 'Remove License', 'dashboard-widget-manager' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>
</div>
