<?php
/**
 * Admin Partial Template - Help Trigger
 *
 * Handles markup rendering for the help trigger admin partial template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text        = $text ?? '?';
$variant     = $variant ?? 'link';
$icon        = $icon ?? 'editor-help';
$extra_class = $extra_class ?? '';
$attrs       = $attrs ?? '';

$base_class = $variant === 'button' ? 'dwm-help-icon-btn' : 'dwm-help-trigger';
$classes    = trim( $base_class . ' ' . $extra_class );
?>

<?php if ( $variant === 'button' ) : ?>
	<button type="button"
		class="<?php echo esc_attr( $classes ); ?>"
		data-open-modal="<?php echo esc_attr( $modal_target ); ?>"
		<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
		<?php if ( $text !== '?' ) : ?>
			<?php echo esc_html( $text ); ?>
		<?php endif; ?>
	</button>
<?php else : ?>
	<a href="#"
		class="<?php echo esc_attr( $classes ); ?>"
		data-open-modal="<?php echo esc_attr( $modal_target ); ?>"
		<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( $icon ) : ?>
			<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
		<?php endif; ?>
		<?php echo esc_html( $text ); ?>
	</a>
<?php endif; ?>
