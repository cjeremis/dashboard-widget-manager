<?php
/**
 * Admin Partial Template - Feature Card
 *
 * Handles markup rendering for the feature card admin partial template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$icon           = $icon ?? '';
$title          = $title ?? '';
$description    = $description ?? '';
$features       = $features ?? [];
$badge          = $badge ?? '';
$badge_text     = $badge_text ?? '';
$is_pro         = $is_pro ?? false;
$is_implemented = $is_implemented ?? false;
$docs_page      = $docs_page ?? '';

$feature_labels = DWM_Features::get_labels();

if ( $badge && ! $badge_text ) {
	$badge_text = 'primary' === $badge ? $feature_labels['badge_coming_soon'] : ucwords( str_replace( '-', ' ', $badge ) );
}

$card_classes = [ 'dwm-feature-card' ];
if ( $badge ) {
	$card_classes[] = 'is-coming-soon';
} elseif ( $is_pro ) {
	$card_classes[] = 'is-pro';
} elseif ( $is_implemented ) {
	$card_classes[] = 'is-free';
}
$details      = $details ?? '';
$search_parts = array_filter( [ $title, $description, implode( ' ', $features ), $details ] );
$search_text  = mb_strtolower( implode( ' ', $search_parts ) );
?>
<div class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>"
	data-feature-plan="<?php echo esc_attr( $is_pro ? 'pro' : 'free' ); ?>"
	data-feature-implemented="<?php echo $is_implemented ? '1' : '0'; ?>"
	data-feature-title="<?php echo esc_attr( mb_strtolower( $title ) ); ?>"
	data-feature-search="<?php echo esc_attr( $search_text ); ?>">
	<div class="dwm-feature-header">
		<div class="dwm-feature-icon"><?php echo esc_html( $icon ); ?></div>
		<h4><?php echo esc_html( $title ); ?></h4>
	</div>
	<p class="dwm-feature-description"><?php echo esc_html( $description ); ?></p>
	<?php if ( ! empty( $features ) ) : ?>
		<ul class="dwm-feature-list">
			<?php foreach ( $features as $feature_item ) : ?>
				<li><?php echo esc_html( $feature_item ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<div class="dwm-feature-footer">
		<?php if ( $is_pro && $badge ) : ?>
			<span class="dwm-pro-badge"><?php echo esc_html( $feature_labels['badge_pro'] ); ?></span>
			<span class="dwm-badge dwm-badge-primary dwm-pulse-primary"><?php echo esc_html( $badge_text ); ?></span>
		<?php elseif ( $badge ) : ?>
			<span class="dwm-badge dwm-badge-primary dwm-pulse-primary"><?php echo esc_html( $badge_text ); ?></span>
		<?php elseif ( $is_pro ) : ?>
			<span class="dwm-pro-badge"><?php echo esc_html( $feature_labels['badge_pro'] ); ?></span>
		<?php elseif ( $is_implemented ) : ?>
			<span class="dwm-free-badge"><?php echo esc_html( $feature_labels['badge_free'] ); ?></span>
		<?php endif; ?>
		<?php if ( ! empty( $docs_page ) ) : ?>
			<button type="button" class="dwm-learn-more-button dwm-button-primary" data-open-modal="dwm-docs-modal" data-docs-page="<?php echo esc_attr( $docs_page ); ?>">
				<?php esc_html_e( 'Learn More', 'dashboard-widget-manager' ); ?>
			</button>
		<?php endif; ?>
	</div>
</div>
