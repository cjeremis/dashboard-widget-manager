<?php
/**
 * Admin Partial Template - Footer
 *
 * Handles markup rendering for the footer admin partial template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$year    = gmdate( 'Y' );
$version = defined( 'DWM_VERSION' ) ? DWM_VERSION : '';
?>

<!-- Pro Upsell Footer -->
<?php include __DIR__ . '/pro-upsell-footer.php'; ?>

<div class="dwm-footer">
	<span>
		<?php
		echo esc_html(
			sprintf(
				/* translators: %s: plugin version number */
				__( 'Dashboard Widget Manager v%s', 'dashboard-widget-manager' ),
				$version
			)
		);
		?>
	</span>
</div>

<!-- Global Modals -->
<?php include DWM_PLUGIN_DIR . 'templates/admin/modals/features-modal.php'; ?>
<?php include DWM_PLUGIN_DIR . 'templates/admin/modals/docs-modal.php'; ?>
<?php include __DIR__ . '/support-ticket-form.php'; ?>
