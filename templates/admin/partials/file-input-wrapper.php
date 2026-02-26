<?php
/**
 * Admin Partial Template - File Input Wrapper
 *
 * Renders a drag-and-drop file input with file details display.
 *
 * Variables:
 * $input_id         - ID for the file input element.
 * $input_name       - Name attribute for the file input.
 * $wrapper_id       - ID for the outer wrapper element.
 * $selected_id      - ID for the file-selected display element.
 * $file_name_id     - ID for the filename span.
 * $file_size_id     - ID for the filesize span.
 * $remove_button_id - ID for the remove button.
 * $label_text       - Label text shown in the drop zone.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="<?php echo esc_attr( $wrapper_id ); ?>" class="dwm-file-input-wrapper">
	<input
		type="file"
		id="<?php echo esc_attr( $input_id ); ?>"
		name="<?php echo esc_attr( $input_name ); ?>"
		accept=".json,application/json"
		class="dwm-file-input"
	>
	<span class="dwm-file-input-label">
		<?php echo esc_html( $label_text ); ?>
	</span>
</div>

<div id="<?php echo esc_attr( $selected_id ); ?>" class="dwm-file-selected" style="display: none;">
	<span class="dashicons dashicons-media-text dwm-file-icon"></span>
	<span id="<?php echo esc_attr( $file_name_id ); ?>" class="dwm-file-name"></span>
	<span id="<?php echo esc_attr( $file_size_id ); ?>" class="dwm-file-size"></span>
	<button type="button" id="<?php echo esc_attr( $remove_button_id ); ?>" class="dwm-file-remove" aria-label="<?php esc_attr_e( 'Remove file', 'dashboard-widget-manager' ); ?>">
		<span class="dashicons dashicons-no-alt"></span>
	</button>
</div>
