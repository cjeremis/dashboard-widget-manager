<?php
/**
 * Widget Renderer Class
 *
 * Handles dashboard widget rendering.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget Renderer class.
 *
 * Renders custom widgets on WordPress dashboard.
 */
class DWM_Widget_Renderer {

	use DWM_Singleton;

	/**
	 * Register dashboard widgets.
	 */
	public function register_dashboard_widgets() {
		$data    = DWM_Data::get_instance();
		$widgets = $data->get_widgets( true );

		foreach ( $widgets as $widget ) {
			$widget_id   = 'dwm_widget_' . $widget['id'];
			$widget_name = esc_html( $widget['name'] );

			wp_add_dashboard_widget(
				$widget_id,
				$widget_name,
				function() use ( $widget ) {
					$this->render_widget_callback( $widget );
				}
			);
		}
	}

	/**
	 * Render widget callback.
	 *
	 * @param array $widget Widget data array.
	 */
	private function render_widget_callback( $widget ) {
		// Execute query.
		$executor = DWM_Query_Executor::get_instance();
		$results  = $executor->execute_query( $widget['id'] );

		// Handle errors.
		if ( is_wp_error( $results ) ) {
			echo '<div class="dwm-widget-error">';
			echo '<p><strong>' . esc_html__( 'Error:', 'dashboard-widget-manager' ) . '</strong> ' . esc_html( $results->get_error_message() ) . '</p>';
			echo '</div>';
			return;
		}

		// Apply template.
		$output = $this->apply_template( $widget['template'], $results, $widget );

		// Enqueue widget assets.
		$this->enqueue_widget_assets( $widget );

		// Output widget.
		echo '<div class="dwm-widget-content" data-widget-id="' . esc_attr( $widget['id'] ) . '">';
		echo $output;
		echo '</div>';
	}

	/**
	 * Render widget by ID (for preview/admin).
	 *
	 * @param int $widget_id Widget ID.
	 */
	public function render_widget( $widget_id ) {
		$data   = DWM_Data::get_instance();
		$widget = $data->get_widget( $widget_id );

		if ( ! $widget ) {
			echo '<p>' . esc_html__( 'Widget not found.', 'dashboard-widget-manager' ) . '</p>';
			return;
		}

		$this->render_widget_callback( $widget );
	}

	/**
	 * Apply template to query results.
	 *
	 * @param string $template Template string.
	 * @param array  $results Query results.
	 * @param array  $widget Widget data.
	 * @return string Rendered output.
	 */
	private function apply_template( $template, $results, $widget ) {
		// If no template, show default table.
		if ( empty( $template ) ) {
			return $this->render_default_table( $results );
		}

		// Make variables available for template.
		$query_results = $results;
		$widget_data   = $widget;

		// Start output buffering.
		ob_start();

		// Evaluate PHP in template if present.
		try {
			eval( '?>' . $template );
		} catch ( Exception $e ) {
			ob_end_clean();
			return '<p><strong>' . esc_html__( 'Template Error:', 'dashboard-widget-manager' ) . '</strong> ' . esc_html( $e->getMessage() ) . '</p>';
		}

		$output = ob_get_clean();

		// Replace template variables.
		$output = $this->replace_template_variables( $output, $results );

		return $output;
	}

	/**
	 * Replace template variables with values.
	 *
	 * @param string $template Template string.
	 * @param array  $results Query results.
	 * @return string Template with replaced variables.
	 */
	private function replace_template_variables( $template, $results ) {
		// Handle single result row.
		if ( ! empty( $results ) && isset( $results[0] ) ) {
			foreach ( $results[0] as $key => $value ) {
				// Handle escaped variables.
				$template = str_replace( '{{esc_html:' . $key . '}}', esc_html( $value ), $template );
				$template = str_replace( '{{esc_url:' . $key . '}}', esc_url( $value ), $template );
				$template = str_replace( '{{esc_attr:' . $key . '}}', esc_attr( $value ), $template );

				// Default to esc_html.
				$template = str_replace( '{{' . $key . '}}', esc_html( $value ), $template );
			}
		}

		return $template;
	}

	/**
	 * Render default table view.
	 *
	 * @param array $results Query results.
	 * @return string HTML table.
	 */
	private function render_default_table( $results ) {
		if ( empty( $results ) ) {
			return '<p>' . esc_html__( 'No results found.', 'dashboard-widget-manager' ) . '</p>';
		}

		$output = '<table class="dwm-results-table widefat">';

		// Header row.
		$output .= '<thead><tr>';
		foreach ( array_keys( $results[0] ) as $column ) {
			$output .= '<th>' . esc_html( $column ) . '</th>';
		}
		$output .= '</tr></thead>';

		// Body rows.
		$output .= '<tbody>';
		foreach ( $results as $row ) {
			$output .= '<tr>';
			foreach ( $row as $value ) {
				$output .= '<td>' . esc_html( $value ) . '</td>';
			}
			$output .= '</tr>';
		}
		$output .= '</tbody>';

		$output .= '</table>';

		return $output;
	}

	/**
	 * Enqueue widget-specific assets.
	 *
	 * @param array $widget Widget data.
	 */
	private function enqueue_widget_assets( $widget ) {
		// Enqueue styles.
		if ( ! empty( $widget['styles'] ) ) {
			$styles = $this->scope_widget_styles( $widget['styles'], $widget['id'] );
			wp_add_inline_style( 'dashicons', $styles );
		}

		// Enqueue scripts.
		if ( ! empty( $widget['scripts'] ) ) {
			$scripts = $widget['scripts'];
			wp_add_inline_script( 'jquery', $scripts );
		}
	}

	/**
	 * Scope widget styles to widget container.
	 *
	 * @param string $styles CSS styles.
	 * @param int    $widget_id Widget ID.
	 * @return string Scoped styles.
	 */
	private function scope_widget_styles( $styles, $widget_id ) {
		// Wrap styles with widget-specific scope.
		return '.dwm-widget-content[data-widget-id="' . $widget_id . '"] { ' . $styles . ' }';
	}
}
