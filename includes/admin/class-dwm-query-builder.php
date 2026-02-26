<?php
/**
 * Query Builder Class
 *
 * Provides AJAX endpoints for the Visual Builder UI:
 * listing available tables, fetching columns, and constructing SQL queries.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query Builder class.
 */
class DWM_Query_Builder {

	use DWM_Singleton;
	use DWM_AJAX_Handler;

	/**
	 * Return available tables for the builder dropdown.
	 *
	 * Respects the allowed_tables whitelist if configured.
	 * Otherwise returns all standard WordPress core tables.
	 */
	public function ajax_get_tables() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		global $wpdb;

		$data           = DWM_Data::get_instance();
		$allowed_tables = $data->get_allowed_tables();

		if ( ! empty( $allowed_tables ) ) {
			// Return only whitelisted tables that actually exist.
			$tables = array();
			foreach ( $allowed_tables as $table ) {
				$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
				if ( $exists ) {
					$tables[] = $table;
				}
			}
		} else {
			// Return all tables with WP prefix.
			$all    = $wpdb->get_col( 'SHOW TABLES' );
			$prefix = $wpdb->prefix;
			$tables = array_filter( $all, fn( $t ) => str_starts_with( $t, $prefix ) );
			$tables = array_values( $tables );
			sort( $tables );
		}

		$this->send_success(
			__( 'Tables retrieved.', 'dashboard-widget-manager' ),
			array( 'tables' => $tables )
		);
	}

	/**
	 * Return columns for a given table.
	 *
	 * @param string $_POST['table'] Table name.
	 */
	public function ajax_get_table_columns() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		global $wpdb;

		$table = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';

		if ( empty( $table ) ) {
			$this->send_error( __( 'Table name is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		// Security: only allow alphanumeric + underscore table names.
		if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $table ) ) {
			$this->send_error( __( 'Invalid table name.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		// Verify the table actually exists.
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( ! $exists ) {
			$this->send_error( __( 'Table not found.', 'dashboard-widget-manager' ), 404 );
			return;
		}

		$columns_raw = $wpdb->get_results( "SHOW COLUMNS FROM `{$table}`", ARRAY_A );

		$columns = array();
		foreach ( $columns_raw as $col ) {
			$columns[] = array(
				'name' => $col['Field'],
				'type' => $col['Type'],
				'key'  => $col['Key'],
			);
		}

		$this->send_success(
			__( 'Columns retrieved.', 'dashboard-widget-manager' ),
			array( 'columns' => $columns )
		);
	}

	/**
	 * Build a SQL SELECT query from a visual builder configuration.
	 *
	 * @param string $_POST['config'] JSON-encoded builder configuration.
	 */
	public function ajax_build_query() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		$raw_config = isset( $_POST['config'] ) ? wp_unslash( $_POST['config'] ) : '';

		if ( empty( $raw_config ) ) {
			$this->send_error( __( 'Builder configuration is required.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$config = json_decode( $raw_config, true );

		if ( ! is_array( $config ) ) {
			$this->send_error( __( 'Invalid builder configuration.', 'dashboard-widget-manager' ), 400 );
			return;
		}

		$result = $this->build_query_from_config( $config );

		if ( is_wp_error( $result ) ) {
			$this->send_error( $result->get_error_message(), 400 );
			return;
		}

		$this->send_success(
			__( 'Query built successfully.', 'dashboard-widget-manager' ),
			array(
				'sql'      => $result['sql'],
				'template' => $result['template'],
			)
		);
	}

	/**
	 * Build SQL and default template from a builder config array.
	 *
	 * Config structure:
	 * {
	 *   table: string,
	 *   columns: string[],
	 *   joins: [{ type, table, local_col, foreign_col }],
	 *   conditions: [{ column, operator, value }],
	 *   order_by: string,
	 *   order_dir: 'ASC'|'DESC',
	 *   limit: int,
	 *   display_mode: 'table'|'bar'|'line'|'pie'|'doughnut',
	 *   chart_label_column: string,
	 *   chart_data_columns: string[]
	 * }
	 *
	 * @param array $config Builder configuration.
	 * @return array|WP_Error Array with 'sql' and 'template' keys, or WP_Error on failure.
	 */
	public function build_query_from_config( array $config ) {
		global $wpdb;

		$table = isset( $config['table'] ) ? sanitize_text_field( $config['table'] ) : '';

		if ( empty( $table ) || ! preg_match( '/^[a-zA-Z0-9_]+$/', $table ) ) {
			return new WP_Error( 'invalid_table', __( 'A valid primary table is required.', 'dashboard-widget-manager' ) );
		}

		$columns      = isset( $config['columns'] ) && is_array( $config['columns'] ) ? $config['columns'] : array();
		$joins        = isset( $config['joins'] ) && is_array( $config['joins'] ) ? $config['joins'] : array();
		$conditions   = isset( $config['conditions'] ) && is_array( $config['conditions'] ) ? $config['conditions'] : array();
		$orders = isset( $config['orders'] ) && is_array( $config['orders'] ) ? $config['orders'] : array();
		// Legacy support: "sorts" field and single order_by/order_dir
		if ( empty( $orders ) ) {
			// Check for legacy "sorts" field
			if ( isset( $config['sorts'] ) && is_array( $config['sorts'] ) ) {
				$orders = $config['sorts'];
			} elseif ( isset( $config['order_by'] ) ) {
				$order_by  = sanitize_text_field( $config['order_by'] );
				$order_dir = isset( $config['order_dir'] ) && strtoupper( $config['order_dir'] ) === 'ASC' ? 'ASC' : 'DESC';
				if ( ! empty( $order_by ) ) {
					$orders = array( array( 'column' => $order_by, 'direction' => $order_dir ) );
				}
			}
		}
		$no_limit     = isset( $config['noLimit'] ) && $config['noLimit'];
		$limit        = ! $no_limit && isset( $config['limit'] ) ? max( 1, min( 1000, (int) $config['limit'] ) ) : 10;
		$display_mode = isset( $config['display_mode'] ) ? sanitize_text_field( $config['display_mode'] ) : 'table';

		// --- SELECT clause ---
		$select_parts = array();
		if ( empty( $columns ) ) {
			$select_parts[] = '`' . $table . '`.*';
		} else {
			foreach ( $columns as $col ) {
				$col = sanitize_text_field( $col );
				if ( preg_match( '/^[a-zA-Z0-9_]+\.[a-zA-Z0-9_]+$/', $col ) ) {
					// Already qualified: alias.column
					$select_parts[] = $col;
				} elseif ( preg_match( '/^[a-zA-Z0-9_]+$/', $col ) ) {
					$select_parts[] = '`' . $table . '`.`' . $col . '`';
				}
			}
		}

		$sql = 'SELECT ' . implode( ', ', $select_parts );
		$sql .= "\nFROM `" . $table . '`';

		// --- JOIN clauses ---
		$valid_join_types = array( 'LEFT', 'RIGHT', 'INNER' );
		foreach ( $joins as $join ) {
			$join_type  = isset( $join['type'] ) && in_array( strtoupper( $join['type'] ), $valid_join_types, true )
				? strtoupper( $join['type'] )
				: 'LEFT';
			$join_table = isset( $join['table'] ) ? sanitize_text_field( $join['table'] ) : '';
			$local_col  = isset( $join['local_col'] ) ? sanitize_text_field( $join['local_col'] ) : '';
			$foreign_col = isset( $join['foreign_col'] ) ? sanitize_text_field( $join['foreign_col'] ) : '';

			if ( empty( $join_table ) || empty( $local_col ) || empty( $foreign_col ) ) {
				continue;
			}

			if (
				! preg_match( '/^[a-zA-Z0-9_]+$/', $join_table ) ||
				! preg_match( '/^[a-zA-Z0-9_.]+$/', $local_col ) ||
				! preg_match( '/^[a-zA-Z0-9_.]+$/', $foreign_col )
			) {
				continue;
			}

			$sql .= "\n{$join_type} JOIN `{$join_table}` ON {$local_col} = {$foreign_col}";
		}

		// --- WHERE clause ---
		$valid_operators = array( '=', '!=', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL' );
		$where_parts     = array();
		foreach ( $conditions as $cond ) {
			$col = isset( $cond['column'] ) ? sanitize_text_field( $cond['column'] ) : '';
			$op  = isset( $cond['operator'] ) ? strtoupper( sanitize_text_field( $cond['operator'] ) ) : '=';
			$val = isset( $cond['value'] ) ? $cond['value'] : '';

			if ( empty( $col ) || ! preg_match( '/^[a-zA-Z0-9_.`]+$/', $col ) ) {
				continue;
			}

			if ( ! in_array( $op, $valid_operators, true ) ) {
				continue;
			}

			if ( $op === 'IS NULL' || $op === 'IS NOT NULL' ) {
				$where_parts[] = $col . ' ' . $op;
			} elseif ( $op === 'IN' || $op === 'NOT IN' ) {
				$vals = array_map( fn( $v ) => $wpdb->prepare( '%s', trim( $v ) ), explode( ',', $val ) );
				$where_parts[] = $col . ' ' . $op . ' (' . implode( ', ', $vals ) . ')';
			} else {
				$where_parts[] = $col . ' ' . $op . ' ' . $wpdb->prepare( '%s', $val );
			}
		}

		if ( ! empty( $where_parts ) ) {
			$sql .= "\nWHERE " . implode( ' AND ', $where_parts );
		}

		// --- ORDER BY clause ---
		if ( ! empty( $orders ) ) {
			$order_parts = array();
			foreach ( $orders as $order ) {
				$col = isset( $order['column'] ) ? sanitize_text_field( $order['column'] ) : '';
				$dir = isset( $order['direction'] ) && strtoupper( $order['direction'] ) === 'ASC' ? 'ASC' : 'DESC';
				if ( ! empty( $col ) && preg_match( '/^[a-zA-Z0-9_.`]+$/', $col ) ) {
					$order_parts[] = "{$col} {$dir}";
				}
			}
			if ( ! empty( $order_parts ) ) {
				$sql .= "\nORDER BY " . implode( ', ', $order_parts );
			}
		}

		// --- LIMIT ---
		if ( ! $no_limit ) {
			$sql .= "\nLIMIT {$limit}";
		}

		// --- Default template ---
		$template = $this->generate_default_template( $table, $columns, $display_mode );

		return array(
			'sql'      => $sql,
			'template' => $template,
		);
	}

	/**
	 * Generate a default PHP/HTML template for the built query.
	 *
	 * @param string $table        Primary table name.
	 * @param array  $columns      Selected columns.
	 * @param string $display_mode 'table' or a chart type.
	 * @return string PHP template string.
	 */
	private function generate_default_template( string $table, array $columns, string $display_mode ): string {
		if ( $display_mode !== 'table' ) {
			// For chart mode the renderer handles the canvas; provide an empty template
			// so the widget doesn't also render a raw default table.
			return '';
		}

		// Derive display column names (strip table prefix from qualified names).
		$display_columns = array();
		foreach ( $columns as $col ) {
			if ( str_contains( $col, '.' ) ) {
				$parts             = explode( '.', $col );
				$display_columns[] = end( $parts );
			} else {
				$display_columns[] = $col;
			}
		}

		if ( empty( $display_columns ) ) {
			// No specific columns selected — render a generic table using all keys.
			$tpl  = "<?php if ( empty( \$query_results ) ) : ?>\n";
			$tpl .= "<p><?php esc_html_e( 'No results found.', 'dashboard-widget-manager' ); ?></p>\n";
			$tpl .= "<?php else : ?>\n";
			$tpl .= "<div class=\"dwm-table-wrapper\">\n";
			$tpl .= "<table class=\"dwm-builder-table\">\n";
			$tpl .= "<thead><tr><?php foreach ( array_keys( \$query_results[0] ) as \$col ) : ?>";
			$tpl .= "<th><?php echo esc_html( \$col ); ?></th><?php endforeach; ?></tr></thead>\n";
			$tpl .= "<tbody>\n";
			$tpl .= "<?php foreach ( \$query_results as \$row ) : ?>\n";
			$tpl .= "<tr><?php foreach ( \$row as \$val ) : ?><td><?php echo esc_html( \$val ); ?></td><?php endforeach; ?></tr>\n";
			$tpl .= "<?php endforeach; ?>\n";
			$tpl .= "</tbody>\n</table>\n</div>\n";
			$tpl .= "<?php endif; ?>";
			return $tpl;
		}

		// Generate a typed table with the selected columns as headers.
		$tpl  = "<?php if ( empty( \$query_results ) ) : ?>\n";
		$tpl .= "<p><?php esc_html_e( 'No results found.', 'dashboard-widget-manager' ); ?></p>\n";
		$tpl .= "<?php else : ?>\n";
		$tpl .= "<div class=\"dwm-table-wrapper\">\n";
		$tpl .= "<table class=\"dwm-builder-table\">\n";
		$tpl .= "<thead>\n<tr>\n";

		foreach ( $display_columns as $col ) {
			$label = ucwords( str_replace( '_', ' ', $col ) );
			$tpl  .= "  <th>" . esc_html( $label ) . "</th>\n";
		}

		$tpl .= "</tr>\n</thead>\n<tbody>\n";
		$tpl .= "<?php foreach ( \$query_results as \$row ) : ?>\n";
		$tpl .= "<tr>\n";

		foreach ( $display_columns as $col ) {
			$tpl .= "  <td><?php echo esc_html( \$row['" . esc_js( $col ) . "'] ?? '' ); ?></td>\n";
		}

		$tpl .= "</tr>\n";
		$tpl .= "<?php endforeach; ?>\n";
		$tpl .= "</tbody>\n</table>\n</div>\n";
		$tpl .= "<?php endif; ?>";

		return $tpl;
	}
}
