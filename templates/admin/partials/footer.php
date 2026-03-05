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

<!-- Pro Integrations Marquee -->
<?php include __DIR__ . '/integrations-marquee.php'; ?>

<!-- Plugin Promo Section -->
<?php include __DIR__ . '/plugin-promo.php'; ?>

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
