<?php
/**
 * Admin Partial Template - Section Header With Actions
 *
 * Handles markup rendering for the section header with actions admin partial template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$actions_html      = $actions_html ?? '';
$title_tag         = $title_tag ?? 'h2';
$extra_class       = $extra_class ?? '';
$title_raw         = $title_raw ?? '';
$help_modal_target = $help_modal_target ?? '';
$help_icon_label   = $help_icon_label ?? __( 'View help', 'dashboard-widget-manager' );

// If a help icon is requested but no actions_html provided, generate the help icon.
if ( $help_modal_target && empty( $actions_html ) ) {
	ob_start();
	$text         = '?';
	$modal_target = $help_modal_target;
	$variant      = 'button';
	$icon         = 'editor-help';
	$help_attrs   = isset( $attrs ) ? trim( $attrs ) : '';
	$attrs        = trim( $help_attrs . ' aria-label="' . esc_attr( $help_icon_label ) . '"' );
	$help_icon_classes = [ 'dwm-section-help-icon' ];
	if ( false !== strpos( $help_attrs, 'data-docs-page=' ) ) {
		$help_icon_classes[] = 'dwm-docs-trigger';
	}
	$extra_class  = implode( ' ', $help_icon_classes );
	include __DIR__ . '/help-trigger.php';
	unset( $text, $modal_target, $variant, $icon, $extra_class, $attrs, $help_icon_classes, $help_attrs );
	$actions_html = ob_get_clean();
}
?>
<div class="dwm-section-header <?php echo esc_attr( $extra_class ?? '' ); ?>">
	<<?php echo esc_attr( $title_tag ); ?> class="dwm-section-title">
		<?php
		if ( $title_raw ) {
			echo $title_raw; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo esc_html( $title );
		}
		?>
	</<?php echo esc_attr( $title_tag ); ?>>
	<?php if ( $actions_html ) : ?>
		<?php echo $actions_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>
</div>
