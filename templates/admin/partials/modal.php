<?php
/**
 * Admin Partial Template - Modal
 *
 * Generic modal wrapper. Accepts variables:
 * $modal['id']           - The modal element ID.
 * $modal['title_html']   - HTML for the modal title (unescaped, must be pre-escaped).
 * $modal['body_html']    - HTML for the modal body.
 * $modal['footer_html']  - HTML for the modal footer.
 * $modal['size_class']   - Optional size class (e.g. 'dwm-modal-md', 'dwm-modal-xl').
 * $modal['display']      - Optional inline display style override (e.g. 'none').
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$modal_id      = $modal['id'] ?? '';
$title_html    = $modal['title_html'] ?? '';
$body_html     = $modal['body_html'] ?? '';
$footer_html   = $modal['footer_html'] ?? '';
$size_class    = $modal['size_class'] ?? 'dwm-modal-md';
$display_style = isset( $modal['display'] ) ? ' style="display: ' . esc_attr( $modal['display'] ) . ';"' : '';
?>
<div id="<?php echo esc_attr( $modal_id ); ?>" class="dwm-modal <?php echo esc_attr( $size_class ); ?>"<?php echo $display_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="dwm-modal-overlay"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2><?php echo $title_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
			<button type="button" class="dwm-modal-close" <?php echo esc_attr( $close_attrs ); ?> aria-label="<?php esc_attr_e( 'Close modal', 'dashboard-widget-manager' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="dwm-modal-body">
			<?php echo $body_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php if ( $footer_html ) : ?>
		<div class="dwm-modal-footer">
			<?php echo $footer_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php endif; ?>
	</div>
</div>
