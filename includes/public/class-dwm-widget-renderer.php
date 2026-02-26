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

		// Append chart if configured.
		$chart_type   = isset( $widget['chart_type'] ) ? $widget['chart_type'] : '';
		$chart_config = isset( $widget['chart_config'] ) ? $widget['chart_config'] : '';
		if ( ! empty( $chart_type ) && ! empty( $chart_config ) ) {
			$output .= $this->render_chart( $widget, $results );
		}

		// Output widget container with inline styles and scripts.
		echo '<div class="dwm-widget-content" data-widget-id="' . esc_attr( $widget['id'] ) . '">';

		if ( ! empty( $widget['styles'] ) ) {
			echo '<style>' . $this->scope_widget_styles( $widget['styles'], $widget['id'] ) . '</style>';
		}

		echo $output;

		if ( ! empty( $widget['scripts'] ) ) {
			echo '<script>' . $widget['scripts'] . '</script>';
		}

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
	 * Render chart canvas and initialization script.
	 *
	 * @param array $widget  Widget data.
	 * @param array $results Query results.
	 * @return string HTML canvas + inline script.
	 */
	private function render_chart( $widget, $results ) {
		$config = json_decode( $widget['chart_config'], true );
		if ( ! is_array( $config ) ) {
			return '';
		}

		$chart_type   = sanitize_text_field( $widget['chart_type'] );
		$label_column = isset( $config['label_column'] ) ? $config['label_column'] : '';
		$data_columns = isset( $config['data_columns'] ) && is_array( $config['data_columns'] ) ? $config['data_columns'] : array();
		$chart_title  = isset( $config['title'] ) ? $config['title'] : '';
		$show_legend  = isset( $config['show_legend'] ) ? (bool) $config['show_legend'] : true;

		if ( empty( $label_column ) || empty( $data_columns ) ) {
			return '';
		}

		// Extract labels and dataset values.
		$labels = array();
		foreach ( $results as $row ) {
			$labels[] = isset( $row[ $label_column ] ) ? $row[ $label_column ] : '';
		}

		$palette = array(
			'rgba(102,126,234,0.8)',
			'rgba(118,75,162,0.8)',
			'rgba(40,167,69,0.8)',
			'rgba(255,193,7,0.8)',
			'rgba(220,53,69,0.8)',
			'rgba(23,162,184,0.8)',
			'rgba(253,126,20,0.8)',
			'rgba(111,66,193,0.8)',
		);

		$datasets = array();
		foreach ( $data_columns as $i => $col ) {
			$values = array();
			foreach ( $results as $row ) {
				$values[] = isset( $row[ $col ] ) ? (float) $row[ $col ] : 0;
			}
			$color      = $palette[ $i % count( $palette ) ];
			$datasets[] = array(
				'label'           => $col,
				'data'            => $values,
				'backgroundColor' => $color,
				'borderColor'     => str_replace( '0.8', '1', $color ),
				'borderWidth'     => 1,
			);
		}

		$canvas_id   = 'dwm-chart-' . esc_attr( $widget['id'] );
		$chart_data  = wp_json_encode(
			array(
				'type'    => $chart_type,
				'data'    => array(
					'labels'   => $labels,
					'datasets' => $datasets,
				),
				'options' => array(
					'responsive'          => true,
					'maintainAspectRatio' => true,
					'plugins'             => array(
						'legend' => array( 'display' => $show_legend ),
						'title'  => array(
							'display' => ! empty( $chart_title ),
							'text'    => $chart_title,
						),
					),
				),
			)
		);

		$output  = '<div class="dwm-chart-wrapper" style="margin-top:12px;">';
		$output .= '<canvas id="' . $canvas_id . '"></canvas>';
		$output .= '</div>';
		$output .= '<script>';
		$output .= '(function(){';
		$output .= 'function dwmInitChart(){';
		$output .= 'var ctx=document.getElementById(' . wp_json_encode( $canvas_id ) . ');';
		$output .= 'if(ctx&&typeof Chart!=="undefined"){new Chart(ctx,' . $chart_data . ');}';
		$output .= '}';
		$output .= 'if(typeof Chart!=="undefined"){dwmInitChart();}';
		$output .= 'else{window.addEventListener("load",dwmInitChart);}';
		$output .= '})();';
		$output .= '</script>';

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
	 * Scope widget styles by prefixing each rule's selectors.
	 *
	 * Converts flat CSS rules to scoped rules so widget styles
	 * don't bleed outside the widget container.
	 *
	 * @param string $styles    Raw CSS string.
	 * @param int    $widget_id Widget ID for scoping.
	 * @return string Scoped CSS.
	 */
	private function scope_widget_styles( $styles, $widget_id ) {
		$scope  = '.dwm-widget-content[data-widget-id="' . $widget_id . '"]';
		$parts  = explode( '}', $styles );
		$result = '';

		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( empty( $part ) ) {
				continue;
			}

			$brace = strpos( $part, '{' );
			if ( false === $brace ) {
				continue;
			}

			$selector_raw = trim( substr( $part, 0, $brace ) );
			$props        = trim( substr( $part, $brace + 1 ) );

			// Pass @-rules (keyframes, media queries) through unchanged.
			if ( str_starts_with( $selector_raw, '@' ) ) {
				$result .= $part . "}\n";
				continue;
			}

			$selectors = array_map( 'trim', explode( ',', $selector_raw ) );
			$prefixed  = array_map(
				fn( $s ) => $scope . ' ' . $s,
				array_filter( $selectors, 'strlen' )
			);

			$result .= implode( ', ', $prefixed ) . " {\n  " . $props . "\n}\n";
		}

		return $result;
	}
}
