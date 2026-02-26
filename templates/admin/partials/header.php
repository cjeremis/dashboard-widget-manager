<?php
/**
 * Admin Partial Template - Header
 *
 * Handles markup rendering for the header admin partial template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$header_title       = isset( $header_title ) ? $header_title : '';
$header_description = isset( $header_description ) ? $header_description : '';
$current_page       = isset( $current_page ) ? $current_page : '';
?>

<div class="dwm-header">
	<h1><?php echo wp_kses_post( $header_title ); ?></h1>
	<?php if ( $header_description ) : ?>
		<p><?php echo esc_html( $header_description ); ?></p>
	<?php endif; ?>
</div>
