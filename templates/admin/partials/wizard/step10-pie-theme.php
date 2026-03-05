<?php
/**
 * Wizard Step 10 (Pie) - Theme Selection
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$themes = array( 'classic' => 'Classic', 'sunset' => 'Sunset', 'forest' => 'Forest', 'oceanic' => 'Oceanic', 'monochrome' => 'Monochrome', 'candy' => 'Candy' );
?>
<div class="dwm-step10-pie-themes-grid">
<?php foreach ( $themes as $key => $label ) : ?>
	<label class="dwm-theme-option dwm-pie-theme-option">
		<input type="radio" name="dwm_wizard_pie_theme" value="<?php echo esc_attr( $key ); ?>"<?php checked( 'classic', $key ); ?>>
		<div class="dwm-theme-preview dwm-pie-theme-preview dwm-pie-theme-<?php echo esc_attr( $key ); ?>">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php echo esc_html( $label ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-pie-theme-circle"></div>
		</div>
	</label>
<?php endforeach; ?>
</div>
