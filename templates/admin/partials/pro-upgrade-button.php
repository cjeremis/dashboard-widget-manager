<?php
/**
 * Admin Partial Template - Pro Upgrade Button
 *
 * Handles markup rendering for the pro upgrade button admin partial template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$label       = isset( $label ) ? $label : __( 'Upgrade to Pro', 'dashboard-widget-manager' );
$url         = isset( $url ) ? $url : admin_url( 'admin.php?page=dwm-settings#dwm-pro-license-key' );
$variant     = isset( $variant ) ? $variant : 'primary'; // primary|ghost|block|small
$extra_class = isset( $extra_class ) ? $extra_class : '';
$icon        = isset( $icon ) ? $icon : 'star-filled';
$icon_class  = isset( $icon_class ) ? $icon_class : ( 'star-filled' === $icon ? 'dwm-animate-default' : '' );
$target      = isset( $target ) && in_array( $target, [ '_self', '_blank' ], true ) ? $target : '_self';
$rel         = isset( $rel ) ? $rel : ( '_blank' === $target ? 'noopener noreferrer' : 'nofollow' );
$extra_attrs = isset( $extra_attrs ) ? $extra_attrs : '';

$classes = [
	'dwm-button',
	'dwm-pro-upgrade-button',
	'dwm-pro-upgrade-button--' . $variant,
	trim( $extra_class ),
];

if ( 'primary' === $variant ) {
	$classes[] = 'dwm-button-primary';
} elseif ( 'ghost' === $variant ) {
	$classes[] = 'dwm-button-secondary';
} elseif ( 'block' === $variant ) {
	$classes[] = 'dwm-button-primary';
	$classes[] = 'dwm-button-block';
} elseif ( 'small' === $variant ) {
	$classes[] = 'dwm-button-small';
}
?>

<a
	href="<?php echo esc_url( $url ); ?>"
	class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>"
	target="<?php echo esc_attr( $target ); ?>"
	rel="<?php echo esc_attr( $rel ); ?>"
	<?php echo $extra_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
	<?php if ( $icon ) : ?>
		<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?><?php echo $icon_class ? ' ' . esc_attr( $icon_class ) : ''; ?>"></span>
	<?php endif; ?>
	<span class="dwm-pro-upgrade-button__label"><?php echo esc_html( $label ); ?></span>
</a>
