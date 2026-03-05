<?php
/**
 * Admin Partial Template - Topbar
 *
 * Handles markup rendering for the topbar admin partial template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_page   = isset( $current_page ) ? $current_page : '';
$topbar_actions = isset( $topbar_actions ) ? (array) $topbar_actions : [];

/**
 * Top Menu Configuration
 *
 * - type:    'link' | 'upgrade'
 * - slug:    Page slug used to build admin URL and determine active state
 * - label:   Menu item text (translatable)
 * - classes: Array of CSS classes
 */
const DWM_TOPBAR_MENU_CONFIG = [
	[
		'type'    => 'link',
		'slug'    => 'dashboard-widget-manager',
		'label'   => 'Dashboard',
		'classes' => [ 'dwm-topbar-link' ],
	],
	[
		'type'    => 'link',
		'slug'    => 'dwm-settings',
		'label'   => 'Settings',
		'classes' => [ 'dwm-topbar-link' ],
	],
	[
		'type'    => 'link',
		'slug'    => 'dwm-tools',
		'label'   => 'Tools',
		'classes' => [ 'dwm-topbar-link' ],
	],
];

$nav_items = [];
foreach ( DWM_TOPBAR_MENU_CONFIG as $item ) {
	$item['label'] = __( $item['label'], 'dashboard-widget-manager' );
	$nav_items[]   = $item;
}
?>

<div class="dwm-admin-topbar">
	<div class="dwm-topbar-nav">
		<?php foreach ( $nav_items as $item ) : ?>
			<?php
			$slug         = $item['slug'] ?? '';
			$label        = $item['label'];
			$classes      = $item['classes'] ?? [];
			$url          = admin_url( 'admin.php?page=' . $slug );
			$is_active    = $current_page === $slug;

			if ( $is_active ) {
				$classes[] = 'is-active';
			}

			$class_string = implode( ' ', $classes );
			?>
			<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class_string ); ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</div>

	<?php if ( ! empty( $topbar_actions ) ) : ?>
		<div class="dwm-topbar-actions">
			<?php foreach ( $topbar_actions as $action_html ) : ?>
				<?php echo $action_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
