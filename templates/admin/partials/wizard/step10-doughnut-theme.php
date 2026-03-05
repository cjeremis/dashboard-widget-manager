<?php
/**
 * Wizard Step 10 (Doughnut) - Theme Selection
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$themes = array( 'classic' => 'Classic', 'sunset' => 'Sunset', 'forest' => 'Forest', 'oceanic' => 'Oceanic', 'monochrome' => 'Monochrome', 'candy' => 'Candy' );
?>
<div class="dwm-step10-doughnut-themes-grid">
<?php foreach ( $themes as $key => $label ) : ?>
	<label class="dwm-theme-option dwm-doughnut-theme-option">
		<input type="radio" name="dwm_wizard_doughnut_theme" value="<?php echo esc_attr( $key ); ?>"<?php checked( 'classic', $key ); ?>>
		<div class="dwm-theme-preview dwm-doughnut-theme-preview dwm-doughnut-theme-<?php echo esc_attr( $key ); ?>">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php echo esc_html( $label ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-doughnut-theme-circle"></div>
		</div>
	</label>
<?php endforeach; ?>
</div>
