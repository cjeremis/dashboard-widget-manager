<?php
/**
 * Wizard Step 10 (Line) - Theme Selection
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$themes = array( 'classic' => 'Classic', 'sunset' => 'Sunset', 'forest' => 'Forest', 'oceanic' => 'Oceanic', 'monochrome' => 'Monochrome', 'candy' => 'Candy' );
?>
<div class="dwm-step10-line-themes-grid">
<?php foreach ( $themes as $key => $label ) : ?>
	<label class="dwm-theme-option dwm-line-theme-option">
		<input type="radio" name="dwm_wizard_line_theme" value="<?php echo esc_attr( $key ); ?>"<?php checked( 'classic', $key ); ?>>
		<div class="dwm-theme-preview dwm-line-theme-preview dwm-line-theme-<?php echo esc_attr( $key ); ?>">
			<div class="dwm-theme-preview-header"><span class="dwm-theme-name"><?php echo esc_html( $label ); ?></span><span class="dashicons dashicons-saved dwm-theme-check"></span></div>
			<div class="dwm-line-theme-lines"><span></span><span></span><span></span></div>
		</div>
	</label>
<?php endforeach; ?>
</div>
