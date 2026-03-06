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
					if ( ! current_user_can( 'manage_options' ) ) {
						return;
					}
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
	private function render_widget_callback( $widget, $force_refresh = false ) {
		// Execute query.
		$executor = DWM_Query_Executor::get_instance();
		$results  = $executor->execute_query( $widget['id'], (bool) $force_refresh );

		// Handle errors.
		if ( is_wp_error( $results ) ) {
			echo '<div class="dwm-widget-error">';
			echo '<p><strong>' . esc_html__( 'Error:', 'dashboard-widget-manager' ) . '</strong> ' . esc_html( $results->get_error_message() ) . '</p>';
			echo '</div>';
			return;
		}

		// Show no-results template when query returns zero rows.
		$no_results_template = isset( $widget['no_results_template'] ) ? trim( $widget['no_results_template'] ) : '';
		if ( empty( $results ) && '' !== $no_results_template ) {
			$output = wp_kses_post( $no_results_template );

			// Skip chart rendering — no data to chart.
			$description      = isset( $widget['description'] ) ? trim( $widget['description'] ) : '';
			$show_description = false;
			if ( ! empty( $description ) ) {
				$builder_config   = isset( $widget['builder_config'] ) ? json_decode( $widget['builder_config'], true ) : array();
				$show_description = ! empty( $builder_config['show_description'] );
			}

			$auto_refresh   = ! empty( $widget['auto_refresh'] ) && ! empty( $widget['enable_caching'] );
			$cache_duration = isset( $widget['cache_duration'] ) ? (int) $widget['cache_duration'] : 0;
			$result_hash    = md5( wp_json_encode( $results ) );

			echo '<div class="dwm-widget-content" data-widget-id="' . esc_attr( $widget['id'] ) . '"'
				. ' data-auto-refresh="' . ( $auto_refresh ? '1' : '0' ) . '"'
				. ' data-cache-duration="' . esc_attr( $cache_duration ) . '"'
				. ' data-result-hash="' . esc_attr( $result_hash ) . '"'
				. '>';

			if ( ! empty( $widget['styles'] ) ) {
				echo '<style>' . $this->scope_widget_styles( $widget['styles'], $widget['id'] ) . '</style>';
			}

			echo $output;

			$can_edit_widgets = current_user_can( 'manage_options' ) && DWM_Access_Control::current_user_can_access_plugin();
			$edit_url         = admin_url( 'admin.php?page=dashboard-widget-manager&edit=' . $widget['id'] );
			echo '<div class="dwm-widget-footer">';
			echo '<div class="dwm-widget-footer-left"></div>';
			echo '<div class="dwm-widget-footer-right">';
			if ( $can_edit_widgets ) {
				echo '<a href="' . esc_url( $edit_url ) . '" class="dwm-widget-edit-btn" title="' . esc_attr__( 'Edit widget', 'dashboard-widget-manager' ) . '">';
				echo '<span class="dashicons dashicons-edit"></span>';
				echo '</a>';
			}
			echo '</div>';
			echo '</div>';

			echo '</div>';
			return;
		}

		// Apply template.
		$output = $this->apply_template( $widget['template'], $results, $widget );

		// Append chart if configured.
		$chart_type   = $this->get_effective_chart_type( $widget );
		$chart_config = $this->get_effective_chart_config( $widget, $chart_type );
		if ( ! empty( $chart_type ) && ! empty( $chart_config ) ) {
			$chart_widget                 = $widget;
			$chart_widget['chart_type']   = $chart_type;
			$chart_widget['chart_config'] = $chart_config;
			$output                      .= $this->render_chart( $chart_widget, $results );
		}

		// Check if description should be shown.
		$description      = isset( $widget['description'] ) ? trim( $widget['description'] ) : '';
		$show_description = false;
		if ( ! empty( $description ) ) {
			$builder_config   = isset( $widget['builder_config'] ) ? json_decode( $widget['builder_config'], true ) : array();
			$show_description = ! empty( $builder_config['show_description'] );
		}

		// Determine auto-refresh and caching state.
		$auto_refresh     = ! empty( $widget['auto_refresh'] ) && ! empty( $widget['enable_caching'] );
		$cache_duration   = isset( $widget['cache_duration'] ) ? (int) $widget['cache_duration'] : 0;
		$result_hash      = md5( wp_json_encode( $results ) );
		$chart_type       = isset( $widget['chart_type'] ) ? trim( (string) $widget['chart_type'] ) : '';
		$chart_config_raw = isset( $widget['chart_config'] ) ? (string) $widget['chart_config'] : '';
		$chart_config     = json_decode( $chart_config_raw, true );
		if ( ! is_array( $chart_config ) ) {
			$chart_config = array();
		}

		// Output widget container with inline styles and scripts.
		echo '<div class="dwm-widget-content" data-widget-id="' . esc_attr( $widget['id'] ) . '"'
			. ' data-auto-refresh="' . ( $auto_refresh ? '1' : '0' ) . '"'
			. ' data-cache-duration="' . esc_attr( $cache_duration ) . '"'
			. ' data-result-hash="' . esc_attr( $result_hash ) . '"'
			. ( ! empty( $chart_type ) ? ' data-chart-type="' . esc_attr( $chart_type ) . '"' : '' )
			. ( ! empty( $chart_config ) ? ' data-chart-config="' . esc_attr( wp_json_encode( $chart_config ) ) . '"' : '' )
			. '>';

		if ( ! empty( $widget['styles'] ) ) {
			echo '<style>' . $this->scope_widget_styles( $widget['styles'], $widget['id'] ) . '</style>';
		}

		echo $output;

		if ( ! empty( $widget['scripts'] ) && current_user_can( 'unfiltered_html' ) ) {
			echo '<script>' . $widget['scripts'] . '</script>';
		}

		// Inject description modal if enabled.
		if ( $show_description ) {
			$modal_id = 'dwm-widget-desc-modal-' . $widget['id'];
			echo '<div id="' . esc_attr( $modal_id ) . '" class="dwm-modal dwm-modal-sm">';
			echo '<div class="dwm-modal-overlay"></div>';
			echo '<div class="dwm-modal-content">';
			echo '<div class="dwm-modal-header">';
			echo '<h2><span class="dashicons dashicons-editor-help"></span> ' . esc_html( $widget['name'] ) . '</h2>';
			echo '<button type="button" class="dwm-modal-close" aria-label="' . esc_attr__( 'Close modal', 'dashboard-widget-manager' ) . '"><span class="dashicons dashicons-no-alt"></span></button>';
			echo '</div>';
			echo '<div class="dwm-modal-body">';
			echo '<p>' . nl2br( esc_html( $description ) ) . '</p>';
			echo '</div>';
			echo '</div>';
			echo '</div>';
		}

		// Footer: help icon (left) | edit + refresh (right).
		$can_edit_widgets = current_user_can( 'manage_options' ) && DWM_Access_Control::current_user_can_access_plugin();
		$edit_url         = admin_url( 'admin.php?page=dashboard-widget-manager&edit=' . $widget['id'] );
		echo '<div class="dwm-widget-footer">';

		echo '<div class="dwm-widget-footer-left">';
		if ( $show_description ) {
			$modal_id = 'dwm-widget-desc-modal-' . $widget['id'];
			echo '<button type="button" class="dwm-help-icon-btn" data-open-modal="' . esc_attr( $modal_id ) . '" title="' . esc_attr__( 'About this widget', 'dashboard-widget-manager' ) . '">';
			echo '<span class="dashicons dashicons-editor-help"></span>';
			echo '</button>';
		}
		echo '</div>';

		echo '<div class="dwm-widget-footer-right">';
		if ( $can_edit_widgets ) {
			echo '<a href="' . esc_url( $edit_url ) . '" class="dwm-widget-edit-btn" title="' . esc_attr__( 'Edit widget', 'dashboard-widget-manager' ) . '">';
			echo '<span class="dashicons dashicons-edit"></span>';
			echo '</a>';
		}
		if ( $auto_refresh ) {
			echo '<span class="dwm-auto-refresh-badge" title="' . esc_attr__( 'This widget automatically refreshes when data changes', 'dashboard-widget-manager' ) . '">';
			echo '<span class="dashicons dashicons-update"></span> ' . esc_html__( 'Auto-refresh on data change', 'dashboard-widget-manager' );
			echo '</span>';
		} else {
			echo '<button type="button" class="dwm-manual-refresh-btn" data-widget-id="' . esc_attr( $widget['id'] ) . '" title="' . esc_attr__( 'Refresh widget data', 'dashboard-widget-manager' ) . '">';
			echo '<span class="dashicons dashicons-update"></span>';
			echo '</button>';
		}
		echo '</div>';

		echo '</div>';

		echo '</div>';
	}

	/**
	 * Handle AJAX request to refresh a widget.
	 */
	public function ajax_refresh_widget() {
		check_ajax_referer( 'dwm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$widget_id   = isset( $_POST['widget_id'] ) ? (int) $_POST['widget_id'] : 0;
		$client_hash = isset( $_POST['hash'] ) ? sanitize_text_field( wp_unslash( $_POST['hash'] ) ) : '';

		if ( ! $widget_id ) {
			wp_send_json_error( array( 'message' => 'Invalid widget ID' ) );
		}

		$data   = DWM_Data::get_instance();
		$widget = $data->get_widget( $widget_id );

		if ( ! $widget ) {
			wp_send_json_error( array( 'message' => 'Widget not found' ) );
		}

		$executor = DWM_Query_Executor::get_instance();
		$results  = $executor->execute_query( $widget_id, true ); // force refresh

		if ( is_wp_error( $results ) ) {
			wp_send_json_error( array( 'message' => $results->get_error_message() ) );
		}

		$new_hash = md5( wp_json_encode( $results ) );

		if ( $new_hash === $client_hash ) {
			wp_send_json_success( array( 'changed' => false ) );
		}

		// Re-render widget HTML.
		ob_start();
		$this->render_widget_callback( $widget );
		$html = ob_get_clean();

		wp_send_json_success( array(
			'changed' => true,
			'html'    => $html,
			'hash'    => $new_hash,
		) );
	}

	/**
	 * Render widget by ID (for preview/admin).
	 *
	 * @param int $widget_id Widget ID.
	 */
	public function render_widget( $widget_id, $force_refresh = false ) {
		$data   = DWM_Data::get_instance();
		$widget = $data->get_widget( $widget_id );

		if ( ! $widget ) {
			echo '<p>' . esc_html__( 'Widget not found.', 'dashboard-widget-manager' ) . '</p>';
			return;
		}

		$this->render_widget_callback( $widget, (bool) $force_refresh );
	}

	/**
	 * Apply template to query results.
	 *
	 * Checks for output_config first (new pipeline), then falls back to
	 * legacy template rendering for backward compatibility.
	 *
	 * Note: chart display modes do not run through the output_config pipeline.
	 * Charts are rendered separately via render_chart() after apply_template() returns.
	 * output_config column aliases, link builders, and formatters are not applied to chart data.
	 *
	 * @param string $template Template string.
	 * @param array  $results Query results.
	 * @param array  $widget Widget data.
	 * @return string Rendered output.
	 */
	private function apply_template( $template, $results, $widget ) {
		// Check for output_config-based rendering first.
		if ( ! empty( $widget['output_config'] ) ) {
			$output_config = json_decode( $widget['output_config'], true );
			if ( is_array( $output_config ) ) {
				$display_mode = isset( $output_config['display_mode'] ) ? $output_config['display_mode'] : 'table';
				$non_legacy_modes = array( 'list', 'button', 'card-list' );

				// For new display modes, use the output_config pipeline.
				if ( in_array( $display_mode, $non_legacy_modes, true ) ) {
					return $this->render_with_output_config( $results, $output_config, (int) $widget['id'] );
				}

				// For table mode with output_config, use output_config-aware table.
				if ( 'table' === $display_mode && ! empty( $output_config['columns'] ) ) {
					// Only use output_config table if there is no custom template override.
					if ( empty( $template ) ) {
						return $this->render_with_output_config( $results, $output_config, (int) $widget['id'] );
					}
				}
			}
		}

		// Legacy rendering path (backward compatible).
		// In chart mode, an empty template means "no custom markup above chart".
		$chart_type = $this->get_effective_chart_type( $widget );
		if ( empty( $template ) && ! empty( $chart_type ) ) {
			return '';
		}

		// If no template (table mode), show default table.
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
		} catch ( \Throwable $e ) {
			ob_end_clean();
			return '<p><strong>' . esc_html__( 'Template Error:', 'dashboard-widget-manager' ) . '</strong> ' . esc_html( $e->getMessage() ) . '</p>';
		}

		$output = ob_get_clean();

		// Replace template variables.
		$output = $this->replace_template_variables( $output, $results );

		return $output;
	}

	/**
	 * Render query results using output_config.
	 *
	 * Dispatches to the appropriate display mode renderer based on
	 * output_config['display_mode']. This is the main entry point
	 * for the new render pipeline.
	 *
	 * @param array $results       Query results (array of associative arrays).
	 * @param array $output_config Decoded output_config array.
	 * @param int   $widget_id     Widget ID (for scoping CSS classes).
	 * @return string Rendered HTML.
	 */
	public function render_with_output_config( $results, $output_config, $widget_id ) {
		if ( empty( $results ) ) {
			return '<p>' . esc_html__( 'No results found.', 'dashboard-widget-manager' ) . '</p>';
		}

		$display_mode = isset( $output_config['display_mode'] ) ? $output_config['display_mode'] : 'table';
		$theme        = isset( $output_config['theme'] ) ? $output_config['theme'] : 'theme-1';
		$columns      = isset( $output_config['columns'] ) && is_array( $output_config['columns'] ) ? $output_config['columns'] : array();

		// If no columns defined, auto-generate from result keys.
		if ( empty( $columns ) && ! empty( $results[0] ) ) {
			foreach ( array_keys( $results[0] ) as $key ) {
				$columns[] = array(
					'key'       => $key,
					'alias'     => ucwords( str_replace( '_', ' ', $key ) ),
					'visible'   => true,
					'link'      => array( 'enabled' => false, 'url' => '', 'open_in_new_tab' => true ),
					'formatter' => array( 'type' => 'text', 'options' => array() ),
				);
			}
		}

		// Filter to only visible columns.
		$visible_columns = array_filter( $columns, function( $col ) {
			return ! isset( $col['visible'] ) || true === $col['visible'];
		} );
		$allowed_link_keys = array();
		foreach ( $columns as $col ) {
			if ( ! empty( $col['key'] ) ) {
				$allowed_link_keys[] = (string) $col['key'];
			}
		}
		$allowed_link_keys = array_values( array_unique( $allowed_link_keys ) );

		$wrapper_class = 'dwm-oc-output dwm-oc-mode-' . esc_attr( $display_mode ) . ' dwm-oc-' . esc_attr( $theme );

		switch ( $display_mode ) {
			case 'list':
				$inner = $this->render_mode_list( $results, $visible_columns, $widget_id, $allowed_link_keys );
				break;
			case 'button':
				$inner = $this->render_mode_button( $results, $visible_columns, $widget_id, $allowed_link_keys );
				break;
			case 'card-list':
				$inner = $this->render_mode_card_list( $results, $visible_columns, $widget_id, $allowed_link_keys );
				break;
			case 'table':
			default:
				$inner = $this->render_mode_table( $results, $visible_columns, $widget_id, $allowed_link_keys );
				break;
		}

		$output  = '<div class="' . $wrapper_class . '">';
		$output .= $this->get_output_config_styles( $display_mode, $theme, $widget_id );
		$output .= $inner;
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render table display mode with output_config columns.
	 *
	 * @param array $results         Query results.
	 * @param array $visible_columns Visible column configs.
	 * @param int   $widget_id       Widget ID.
	 * @return string HTML table.
	 */
	private function render_mode_table( $results, $visible_columns, $widget_id, $allowed_link_keys = array() ) {
		$output = '<div class="dwm-table-wrapper">';
		$output .= '<table class="dwm-oc-table widefat">';

		// Header.
		$output .= '<thead><tr>';
		foreach ( $visible_columns as $col ) {
			$label   = ! empty( $col['alias'] ) ? $col['alias'] : $col['key'];
			$output .= '<th>' . esc_html( $label ) . '</th>';
		}
		$output .= '</tr></thead>';

		// Body.
		$output .= '<tbody>';
		foreach ( $results as $row ) {
			$output .= '<tr>';
			foreach ( $visible_columns as $col ) {
				$key   = $col['key'];
				$value = isset( $row[ $key ] ) ? $row[ $key ] : '';
				$formatted = $this->apply_formatter( $value, $col );
				$linked    = $this->apply_link( $formatted, $col, $row, $allowed_link_keys );
				$output   .= '<td>' . $linked . '</td>';
			}
			$output .= '</tr>';
		}
		$output .= '</tbody>';

		$output .= '</table>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render list display mode.
	 *
	 * @param array $results         Query results.
	 * @param array $visible_columns Visible column configs.
	 * @param int   $widget_id       Widget ID.
	 * @return string HTML list.
	 */
	private function render_mode_list( $results, $visible_columns, $widget_id, $allowed_link_keys = array() ) {
		$output = '<ul class="dwm-oc-list">';

		foreach ( $results as $row ) {
			$output .= '<li class="dwm-oc-list-item">';

			$parts = array();
			foreach ( $visible_columns as $col ) {
				$key       = $col['key'];
				$value     = isset( $row[ $key ] ) ? $row[ $key ] : '';
				$formatted = $this->apply_formatter( $value, $col );
				$linked    = $this->apply_link( $formatted, $col, $row, $allowed_link_keys );
				$label     = ! empty( $col['alias'] ) ? $col['alias'] : $col['key'];

				if ( count( $visible_columns ) === 1 ) {
					// Single column: just show the value.
					$parts[] = $linked;
				} else {
					// Multiple columns: show label + value pairs.
					$parts[] = '<span class="dwm-oc-list-label">' . esc_html( $label ) . ':</span> '
						. '<span class="dwm-oc-list-value">' . $linked . '</span>';
				}
			}

			$output .= implode( ' <span class="dwm-oc-list-sep">&middot;</span> ', $parts );
			$output .= '</li>';
		}

		$output .= '</ul>';

		return $output;
	}

	/**
	 * Render button display mode.
	 *
	 * Each row becomes a styled button/link. The first visible column's value
	 * is used as the button text. If a link is configured, the button links there.
	 *
	 * @param array $results         Query results.
	 * @param array $visible_columns Visible column configs.
	 * @param int   $widget_id       Widget ID.
	 * @return string HTML buttons.
	 */
	private function render_mode_button( $results, $visible_columns, $widget_id, $allowed_link_keys = array() ) {
		$output = '<div class="dwm-oc-button-grid">';

		// Find the primary column (first visible) for button text.
		$primary_col  = ! empty( $visible_columns ) ? reset( $visible_columns ) : null;
		$secondary_cols = array_slice( $visible_columns, 1 );

		foreach ( $results as $row ) {
			$button_text = '';
			$button_url  = '';
			$new_tab     = true;

			if ( $primary_col ) {
				$key         = $primary_col['key'];
				$raw_value   = isset( $row[ $key ] ) ? $row[ $key ] : '';
				$button_text = $this->apply_formatter( $raw_value, $primary_col );

				// Check if this column has a link.
				if ( ! empty( $primary_col['link']['enabled'] ) && ! empty( $primary_col['link']['url'] ) ) {
					$button_url = $this->resolve_link_url( $primary_col['link']['url'], $row, $allowed_link_keys );
					$new_tab    = ! empty( $primary_col['link']['open_in_new_tab'] );
				}
			}

			// Subtitle from secondary columns.
			$subtitle_parts = array();
			foreach ( $secondary_cols as $col ) {
				$key       = $col['key'];
				$value     = isset( $row[ $key ] ) ? $row[ $key ] : '';
				$formatted = $this->apply_formatter( $value, $col );
				if ( '' !== $formatted ) {
					$subtitle_parts[] = $formatted;
				}
			}

			if ( ! empty( $button_url ) ) {
				$output .= '<a href="' . esc_url( $button_url ) . '" class="dwm-oc-button"'
					. ( $new_tab ? ' target="_blank" rel="noopener noreferrer"' : '' ) . '>';
			} else {
				$output .= '<span class="dwm-oc-button">';
			}

			$output .= '<span class="dwm-oc-button-text">' . $button_text . '</span>';

			if ( ! empty( $subtitle_parts ) ) {
				$output .= '<span class="dwm-oc-button-sub">' . esc_html( implode( ' | ', $subtitle_parts ) ) . '</span>';
			}

			if ( ! empty( $button_url ) ) {
				$output .= '</a>';
			} else {
				$output .= '</span>';
			}
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render card-list display mode.
	 *
	 * Each row is rendered as a card with fields listed vertically.
	 *
	 * @param array $results         Query results.
	 * @param array $visible_columns Visible column configs.
	 * @param int   $widget_id       Widget ID.
	 * @return string HTML cards.
	 */
	private function render_mode_card_list( $results, $visible_columns, $widget_id, $allowed_link_keys = array() ) {
		$output = '<div class="dwm-oc-card-list">';

		foreach ( $results as $row ) {
			$output .= '<div class="dwm-oc-card">';

			foreach ( $visible_columns as $index => $col ) {
				$key       = $col['key'];
				$value     = isset( $row[ $key ] ) ? $row[ $key ] : '';
				$formatted = $this->apply_formatter( $value, $col );
				$linked    = $this->apply_link( $formatted, $col, $row, $allowed_link_keys );
				$label     = ! empty( $col['alias'] ) ? $col['alias'] : $col['key'];

				if ( 0 === $index ) {
					// First field is the card title.
					$output .= '<div class="dwm-oc-card-title">' . $linked . '</div>';
				} else {
					$output .= '<div class="dwm-oc-card-field">';
					$output .= '<span class="dwm-oc-card-label">' . esc_html( $label ) . '</span>';
					$output .= '<span class="dwm-oc-card-value">' . $linked . '</span>';
					$output .= '</div>';
				}
			}

			$output .= '</div>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Apply formatter rules to a column value.
	 *
	 * @param mixed $value Raw value from query.
	 * @param array $col   Column config with formatter settings.
	 * @return string Formatted (and escaped) value.
	 */
	private function apply_formatter( $value, $col ) {
		$formatter = isset( $col['formatter'] ) && is_array( $col['formatter'] ) ? $col['formatter'] : array();
		$type      = isset( $formatter['type'] ) ? $formatter['type'] : 'text';
		$options   = isset( $formatter['options'] ) && is_array( $formatter['options'] ) ? $formatter['options'] : array();

		switch ( $type ) {
			case 'date':
				$format    = ! empty( $options['format'] ) ? $options['format'] : 'M j, Y';
				$timestamp = strtotime( (string) $value );
				if ( false !== $timestamp && $timestamp > 0 ) {
					if ( 'relative' === $format ) {
						return esc_html( human_time_diff( $timestamp, time() ) . ' ago' );
					}
					return esc_html( date_i18n( $format, $timestamp ) );
				}
				return esc_html( $value );

			case 'number':
				$decimals  = isset( $options['decimals'] ) ? (int) $options['decimals'] : 0;
				$prefix    = isset( $options['prefix'] ) ? $options['prefix'] : '';
				$suffix    = isset( $options['suffix'] ) ? $options['suffix'] : '';
				$numeric   = is_numeric( $value ) ? (float) $value : 0;
				$formatted = number_format_i18n( $numeric, $decimals );
				return esc_html( $prefix . $formatted . $suffix );

			case 'excerpt':
				$length = isset( $options['length'] ) ? (int) $options['length'] : 80;
				$length = max( 10, min( 500, $length ) );
				$text   = wp_strip_all_tags( (string) $value );
				if ( mb_strlen( $text ) > $length ) {
					return esc_html( mb_substr( $text, 0, $length ) ) . '&hellip;';
				}
				return esc_html( $text );

			case 'case':
				$transform = isset( $options['transform'] ) ? $options['transform'] : 'sentence';
				$text      = (string) $value;
				switch ( $transform ) {
					case 'uppercase':
						return esc_html( mb_strtoupper( $text ) );
					case 'lowercase':
						return esc_html( mb_strtolower( $text ) );
					case 'capitalize':
						return esc_html( ucwords( mb_strtolower( $text ) ) );
					case 'sentence':
					default:
						return esc_html( ucfirst( mb_strtolower( $text ) ) );
				}

			case 'text':
			default:
				return esc_html( $value );
		}
	}

	/**
	 * Apply link wrapping to a formatted value based on column config.
	 *
	 * @param string $formatted Already-formatted value (may contain HTML entities).
	 * @param array  $col       Column config with link settings.
	 * @param array  $row       Full result row for template variable resolution.
	 * @return string Value optionally wrapped in an anchor tag.
	 */
	private function apply_link( $formatted, $col, $row, $allowed_link_keys = array() ) {
		$link = isset( $col['link'] ) && is_array( $col['link'] ) ? $col['link'] : array();

		if ( empty( $link['enabled'] ) || empty( $link['url'] ) || '' === trim( wp_strip_all_tags( (string) $formatted ) ) ) {
			return $formatted;
		}

		$url     = $this->resolve_link_url( $link['url'], $row, $allowed_link_keys );
		$new_tab = ! empty( $link['open_in_new_tab'] );

		$attrs = ' href="' . esc_url( $url ) . '"';
		if ( $new_tab ) {
			$attrs .= ' target="_blank" rel="noopener noreferrer"';
		}

		return '<a' . $attrs . '>' . $formatted . '</a>';
	}

	/**
	 * Resolve template variables in a link URL.
	 *
	 * Replaces {column_name} placeholders with corresponding row values.
	 *
	 * @param string $url_template URL with optional {column_name} placeholders.
	 * @param array  $row          Result row.
	 * @return string Resolved URL.
	 */
	private function resolve_link_url( $url_template, $row, $allowed_keys = array() ) {
		$allowed_lookup = array_fill_keys( array_map( 'strval', $allowed_keys ), true );

		return preg_replace_callback( '/\{([a-zA-Z0-9_]+)\}/', function( $matches ) use ( $row, $allowed_lookup ) {
			$key = $matches[1];
			if ( ! empty( $allowed_lookup ) && ! isset( $allowed_lookup[ $key ] ) ) {
				return '';
			}
			return isset( $row[ $key ] ) ? urlencode( $row[ $key ] ) : '';
		}, $url_template );
	}

	/**
	 * Generate default scoped CSS for output_config display modes and themes.
	 *
	 * @param string $display_mode Display mode.
	 * @param string $theme        Theme identifier.
	 * @param int    $widget_id    Widget ID for scoping.
	 * @return string Style tag with CSS, or empty string.
	 */
	private function get_output_config_styles( $display_mode, $theme, $widget_id ) {
		$theme_colors = $this->get_theme_colors( $theme );
		$primary      = $theme_colors['primary'];
		$bg           = $theme_colors['bg'];
		$border       = $theme_colors['border'];
		$hover_bg     = $theme_colors['hover_bg'];
		$text         = $theme_colors['text'];
		$muted        = $theme_colors['muted'];

		$css = '';

		switch ( $display_mode ) {
			case 'table':
				$css .= ".dwm-oc-table { width: 100%; border-collapse: collapse; font-size: 13px; }";
				$css .= ".dwm-oc-table th { text-align: left; padding: 8px 10px; background: {$bg}; border-bottom: 2px solid {$border}; font-weight: 600; color: {$text}; font-size: 12px; text-transform: uppercase; letter-spacing: 0.3px; }";
				$css .= ".dwm-oc-table td { padding: 8px 10px; border-bottom: 1px solid {$border}; color: {$text}; }";
				$css .= ".dwm-oc-table tbody tr:hover { background: {$hover_bg}; }";
				$css .= ".dwm-oc-table tbody tr:last-child td { border-bottom: none; }";
				$css .= ".dwm-oc-table a { color: {$primary}; text-decoration: none; }";
				$css .= ".dwm-oc-table a:hover { text-decoration: underline; }";
				break;

			case 'list':
				$css .= ".dwm-oc-list { margin: 0; padding: 0; list-style: none; }";
				$css .= ".dwm-oc-list-item { padding: 10px 12px; border-bottom: 1px solid {$border}; transition: background-color 0.2s ease; font-size: 13px; color: {$text}; }";
				$css .= ".dwm-oc-list-item:last-child { border-bottom: none; }";
				$css .= ".dwm-oc-list-item:hover { background: {$hover_bg}; }";
				$css .= ".dwm-oc-list-label { font-weight: 600; color: {$muted}; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; }";
				$css .= ".dwm-oc-list-value { color: {$text}; }";
				$css .= ".dwm-oc-list-sep { margin: 0 6px; color: {$muted}; opacity: 0.5; }";
				$css .= ".dwm-oc-list a { color: {$primary}; text-decoration: none; }";
				$css .= ".dwm-oc-list a:hover { text-decoration: underline; }";
				break;

			case 'button':
				$css .= ".dwm-oc-button-grid { display: flex; flex-wrap: wrap; gap: 8px; }";
				$css .= ".dwm-oc-button { display: inline-flex; flex-direction: column; align-items: center; justify-content: center; padding: 10px 16px; background: {$primary}; color: #fff; border-radius: 4px; font-size: 13px; font-weight: 500; text-decoration: none; transition: all 0.2s ease; cursor: pointer; border: none; min-width: 80px; text-align: center; }";
				$css .= ".dwm-oc-button:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.15); color: #fff; }";
				$css .= ".dwm-oc-button-text { line-height: 1.3; }";
				$css .= ".dwm-oc-button-sub { font-size: 11px; opacity: 0.85; margin-top: 2px; }";
				$css .= "span.dwm-oc-button { background: {$bg}; color: {$text}; border: 1px solid {$border}; }";
				$css .= "span.dwm-oc-button:hover { background: {$hover_bg}; }";
				break;

			case 'card-list':
				$css .= ".dwm-oc-card-list { display: flex; flex-direction: column; gap: 10px; }";
				$css .= ".dwm-oc-card { padding: 12px; background: {$bg}; border: 1px solid {$border}; border-radius: 6px; transition: all 0.2s ease; }";
				$css .= ".dwm-oc-card:hover { border-color: {$primary}; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }";
				$css .= ".dwm-oc-card-title { font-size: 14px; font-weight: 600; color: {$text}; margin-bottom: 8px; }";
				$css .= ".dwm-oc-card-title a { color: {$primary}; text-decoration: none; }";
				$css .= ".dwm-oc-card-title a:hover { text-decoration: underline; }";
				$css .= ".dwm-oc-card-field { display: flex; justify-content: space-between; align-items: baseline; padding: 3px 0; font-size: 12px; }";
				$css .= ".dwm-oc-card-label { color: {$muted}; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px; font-size: 11px; }";
				$css .= ".dwm-oc-card-value { color: {$text}; }";
				$css .= ".dwm-oc-card-value a { color: {$primary}; text-decoration: none; }";
				$css .= ".dwm-oc-card-value a:hover { text-decoration: underline; }";
				break;
		}

		if ( empty( $css ) ) {
			return '';
		}

		return '<style>' . $css . '</style>';
	}

	/**
	 * Get theme color palette.
	 *
	 * @param string $theme Theme identifier (theme-1 through theme-6).
	 * @return array Associative array with color keys.
	 */
	private function get_theme_colors( $theme ) {
		$themes = array(
			'theme-1' => array(
				'primary'  => '#2271b1',
				'bg'       => '#f6f7f7',
				'border'   => '#e0e0e0',
				'hover_bg' => '#f0f6ff',
				'text'     => '#1d2327',
				'muted'    => '#646970',
			),
			'theme-2' => array(
				'primary'  => '#1e7e34',
				'bg'       => '#f6faf6',
				'border'   => '#c3e6cb',
				'hover_bg' => '#e8f5e9',
				'text'     => '#1d2327',
				'muted'    => '#5a6c5e',
			),
			'theme-3' => array(
				'primary'  => '#d63384',
				'bg'       => '#fdf4f9',
				'border'   => '#f1c1db',
				'hover_bg' => '#fce4ef',
				'text'     => '#1d2327',
				'muted'    => '#7c5a6a',
			),
			'theme-4' => array(
				'primary'  => '#e65100',
				'bg'       => '#fff8f0',
				'border'   => '#ffe0b2',
				'hover_bg' => '#fff3e0',
				'text'     => '#1d2327',
				'muted'    => '#8d6e63',
			),
			'theme-5' => array(
				'primary'  => '#6f42c1',
				'bg'       => '#f8f4ff',
				'border'   => '#d6c4ef',
				'hover_bg' => '#f0e6ff',
				'text'     => '#1d2327',
				'muted'    => '#6c5b7b',
			),
			'theme-6' => array(
				'primary'  => '#495057',
				'bg'       => '#f8f9fa',
				'border'   => '#dee2e6',
				'hover_bg' => '#e9ecef',
				'text'     => '#212529',
				'muted'    => '#6c757d',
			),
		);

		if ( isset( $themes[ $theme ] ) ) {
			return $themes[ $theme ];
		}

		$named_themes = array(
			'dark' => array(
				'primary'  => '#8ab4f8',
				'bg'       => '#1f1f1f',
				'border'   => '#3a3a3a',
				'hover_bg' => '#2a2a2a',
				'text'     => '#f1f3f4',
				'muted'    => '#bdc1c6',
			),
			'striped' => array(
				'primary'  => '#005c99',
				'bg'       => '#f3f8ff',
				'border'   => '#d3e3fd',
				'hover_bg' => '#e8f0fe',
				'text'     => '#1f2933',
				'muted'    => '#52606d',
			),
			'minimal' => array(
				'primary'  => '#4b5563',
				'bg'       => '#ffffff',
				'border'   => '#e5e7eb',
				'hover_bg' => '#f9fafb',
				'text'     => '#111827',
				'muted'    => '#6b7280',
			),
			'bordered' => array(
				'primary'  => '#0f766e',
				'bg'       => '#f8fafc',
				'border'   => '#94a3b8',
				'hover_bg' => '#f1f5f9',
				'text'     => '#0f172a',
				'muted'    => '#475569',
			),
		);
		if ( isset( $named_themes[ $theme ] ) ) {
			return $named_themes[ $theme ];
		}

		// Map named themes (from JS template generator) to palette equivalents.
		$named_map = array(
			'default'    => 'theme-1',
			'ocean'      => 'theme-1',
			'clean'      => 'theme-1',
			'compact'    => 'theme-6',
			'accent'     => 'theme-5',
			'solid'      => 'theme-1',
			'outline'    => 'theme-1',
			'pill'       => 'theme-5',
			'flat'       => 'theme-6',
			'gradient'   => 'theme-5',
			'elevated'   => 'theme-1',
			'colorful'   => 'theme-5',
			'classic'    => 'theme-1',
			'sunset'     => 'theme-4',
			'forest'     => 'theme-2',
			'oceanic'    => 'theme-1',
			'monochrome' => 'theme-6',
			'candy'      => 'theme-3',
		);
		if ( isset( $named_map[ $theme ], $themes[ $named_map[ $theme ] ] ) ) {
			return $themes[ $named_map[ $theme ] ];
		}

		return $themes['theme-1'];
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
		$chart_theme  = isset( $config['theme'] ) ? sanitize_text_field( $config['theme'] ) : 'classic';

		if ( empty( $label_column ) || empty( $data_columns ) ) {
			return '';
		}

		// Extract labels and dataset values.
		$labels = array();
		foreach ( $results as $row ) {
			$labels[] = isset( $row[ $label_column ] ) ? $row[ $label_column ] : '';
		}

		$palette = $this->get_chart_palette( $chart_theme );

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
		$output .= '<canvas id="' . $canvas_id . '" class="dwm-chart-canvas"></canvas>';
		$output .= '</div>';
		$output .= '<script>';
		$output .= '(function(){';
		$output .= 'function dwmInitChart(){';
		$output .= 'var ctx=document.getElementById(' . wp_json_encode( $canvas_id ) . ');';
		$output .= 'var ChartCtor=(window.Chart||window.WPMailSMTPChart||null);';
		$output .= 'if(ctx&&ChartCtor){new ChartCtor(ctx,' . $chart_data . ');}';
		$output .= '}';
		$output .= 'if(window.Chart||window.WPMailSMTPChart){dwmInitChart();}';
		$output .= 'else{window.addEventListener("load",dwmInitChart);}';
		$output .= '})();';
		$output .= '</script>';

		return $output;
	}

	/**
	 * Get chart palette colors by theme key.
	 *
	 * @param string $theme Theme identifier.
	 * @return array<string>
	 */
	private function get_chart_palette( $theme ) {
		$palettes = array(
			'classic'    => array(
				'rgba(102,126,234,0.8)',
				'rgba(118,75,162,0.8)',
				'rgba(56,161,105,0.8)',
				'rgba(246,173,85,0.8)',
				'rgba(220,53,69,0.8)',
				'rgba(23,162,184,0.8)',
			),
			'sunset'     => array(
				'rgba(251,113,133,0.8)',
				'rgba(249,115,22,0.8)',
				'rgba(250,204,21,0.8)',
				'rgba(245,158,11,0.8)',
				'rgba(244,63,94,0.8)',
				'rgba(217,119,6,0.8)',
			),
			'forest'     => array(
				'rgba(20,83,45,0.8)',
				'rgba(22,101,52,0.8)',
				'rgba(21,128,61,0.8)',
				'rgba(77,124,15,0.8)',
				'rgba(54,83,20,0.8)',
				'rgba(101,163,13,0.8)',
			),
			'oceanic'    => array(
				'rgba(14,165,233,0.8)',
				'rgba(2,132,199,0.8)',
				'rgba(3,105,161,0.8)',
				'rgba(8,145,178,0.8)',
				'rgba(6,182,212,0.8)',
				'rgba(37,99,235,0.8)',
			),
			'monochrome' => array(
				'rgba(156,163,175,0.8)',
				'rgba(107,114,128,0.8)',
				'rgba(75,85,99,0.8)',
				'rgba(55,65,81,0.8)',
				'rgba(31,41,55,0.8)',
				'rgba(17,24,39,0.8)',
			),
			'candy'      => array(
				'rgba(236,72,153,0.8)',
				'rgba(139,92,246,0.8)',
				'rgba(34,211,238,0.8)',
				'rgba(20,184,166,0.8)',
				'rgba(251,113,133,0.8)',
				'rgba(59,130,246,0.8)',
			),
		);

		if ( isset( $palettes[ $theme ] ) ) {
			return $palettes[ $theme ];
		}

		return $palettes['classic'];
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
		// Note: only the first row's values are used. For multi-row output use a PHP template with $query_results.
		if ( ! empty( $results ) && isset( $results[0] ) ) {
			$pairs = array();
			foreach ( $results[0] as $key => $value ) {
				$pairs[ '{{esc_html:' . $key . '}}' ] = esc_html( $value );
				$pairs[ '{{esc_url:' . $key . '}}' ]  = esc_url( $value );
				$pairs[ '{{esc_attr:' . $key . '}}' ] = esc_attr( $value );
				$pairs[ '{{' . $key . '}}' ]           = esc_html( $value );
			}
			// Single-pass replacement prevents a column value containing {{other_col}}
			// from being substituted a second time.
			$template = strtr( $template, $pairs );
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
		return $this->scope_css_block( $styles, $scope );
	}

	/**
	 * Recursively scope CSS selectors inside a block.
	 *
	 * @param string $css   Raw CSS block content.
	 * @param string $scope Scope selector.
	 * @return string Scoped CSS.
	 */
	private function scope_css_block( $css, $scope ) {
		$length = strlen( $css );
		$index  = 0;
		$result = '';

		while ( $index < $length ) {
			while ( $index < $length && ctype_space( $css[ $index ] ) ) {
				$index++;
			}

			if ( $index >= $length ) {
				break;
			}

			$header_start = $index;
			while ( $index < $length && '{' !== $css[ $index ] && '}' !== $css[ $index ] ) {
				$index++;
			}

			if ( $index >= $length || '}' === $css[ $index ] ) {
				$index++;
				continue;
			}

			$header = trim( substr( $css, $header_start, $index - $header_start ) );
			$index++; // Skip opening brace.

			$depth       = 1;
			$body_start  = $index;
			while ( $index < $length && $depth > 0 ) {
				if ( '{' === $css[ $index ] ) {
					$depth++;
				} elseif ( '}' === $css[ $index ] ) {
					$depth--;
				}
				$index++;
			}

			$body = substr( $css, $body_start, max( 0, $index - $body_start - 1 ) );

			if ( '' === $header ) {
				continue;
			}

			if ( str_starts_with( $header, '@media' ) || str_starts_with( $header, '@supports' ) || str_starts_with( $header, '@container' ) ) {
				$result .= $header . " {\n" . $this->scope_css_block( $body, $scope ) . "}\n";
				continue;
			}

			if ( str_starts_with( $header, '@' ) ) {
				// Keep non-container at-rules (e.g. keyframes, font-face) untouched.
				$result .= $header . " {\n" . $body . "\n}\n";
				continue;
			}

			$selectors = array_filter( array_map( 'trim', explode( ',', $header ) ) );
			$prefixed  = array();
			foreach ( $selectors as $selector ) {
				$prefixed[] = str_starts_with( $selector, $scope ) ? $selector : $scope . ' ' . $selector;
			}

			if ( ! empty( $prefixed ) ) {
				$result .= implode( ', ', $prefixed ) . " {\n" . trim( $body ) . "\n}\n";
			}
		}

		return $result;
	}

	/**
	 * Resolve chart type from widget fields, with builder_config fallback.
	 *
	 * @param array $widget Widget data.
	 * @return string
	 */
	private function get_effective_chart_type( $widget ) {
		$chart_type = isset( $widget['chart_type'] ) ? sanitize_text_field( (string) $widget['chart_type'] ) : '';
		if ( ! empty( $chart_type ) ) {
			return $chart_type;
		}

		$builder_config = isset( $widget['builder_config'] ) ? json_decode( (string) $widget['builder_config'], true ) : array();
		if ( ! is_array( $builder_config ) ) {
			return '';
		}

		$display_mode = isset( $builder_config['display_mode'] ) ? sanitize_text_field( (string) $builder_config['display_mode'] ) : '';
		if ( in_array( $display_mode, array( 'bar', 'line', 'pie', 'doughnut' ), true ) ) {
			return $display_mode;
		}

		return '';
	}

	/**
	 * Resolve chart config from widget fields, with builder_config fallback.
	 *
	 * @param array  $widget     Widget data.
	 * @param string $chart_type Effective chart type.
	 * @return string
	 */
	private function get_effective_chart_config( $widget, $chart_type ) {
		if ( empty( $chart_type ) ) {
			return '';
		}

		$chart_config_raw = isset( $widget['chart_config'] ) ? trim( (string) $widget['chart_config'] ) : '';
		if ( '' !== $chart_config_raw ) {
			$decoded = json_decode( $chart_config_raw, true );
			if ( is_array( $decoded ) ) {
				return wp_json_encode( $decoded );
			}
		}

		$builder_config = isset( $widget['builder_config'] ) ? json_decode( (string) $widget['builder_config'], true ) : array();
		if ( ! is_array( $builder_config ) ) {
			return '';
		}

		$label_column = isset( $builder_config['chart_label_column'] ) ? (string) $builder_config['chart_label_column'] : '';
		$data_columns = isset( $builder_config['chart_data_columns'] ) && is_array( $builder_config['chart_data_columns'] ) ? $builder_config['chart_data_columns'] : array();
		if ( empty( $label_column ) || empty( $data_columns ) ) {
			return '';
		}

		$config = array(
			'label_column' => $label_column,
			'data_columns' => array_values( array_map( 'strval', $data_columns ) ),
			'title'        => isset( $builder_config['chart_title'] ) ? (string) $builder_config['chart_title'] : '',
			'show_legend'  => isset( $builder_config['chart_show_legend'] ) ? (bool) $builder_config['chart_show_legend'] : true,
			'theme'        => isset( $builder_config['chart_theme'] ) ? (string) $builder_config['chart_theme'] : 'classic',
		);

		return wp_json_encode( $config );
	}
}
