<?php
/**
 * Dashboard Class
 *
 * Handles plugin dashboard display.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard class.
 *
 * Manages plugin dashboard.
 */
class DWM_Dashboard {

	use DWM_Singleton;

	/**
	 * Render dashboard.
	 */
	public function render() {
		$data       = DWM_Data::get_instance();
		$statistics = $data->get_widget_statistics();
		$widgets    = $data->get_widgets();

		// Recent widgets (last 5).
		$recent_widgets = array_slice( $widgets, -5, 5, true );
		$recent_widgets = array_reverse( $recent_widgets );

		// Load template.
		require_once DWM_PLUGIN_DIR . 'templates/admin/dashboard.php';
	}

	/**
	 * Get widget statistics.
	 *
	 * @return array Statistics array.
	 */
	public function get_widget_statistics() {
		$data = DWM_Data::get_instance();
		return $data->get_widget_statistics();
	}
}
