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
	 * Excludes any tables in the excluded_tables blacklist.
	 * Returns all WordPress-prefixed tables when nothing is excluded.
	 */
	public function ajax_get_tables() {
		if ( ! $this->verify_ajax_request() ) {
			return;
		}

		global $wpdb;

		$data            = DWM_Data::get_instance();
		$excluded_tables = $data->get_excluded_tables();
		$all             = $wpdb->get_col( 'SHOW TABLES' );
		$prefix          = $wpdb->prefix;
		$tables          = array_filter( $all, fn( $t ) => str_starts_with( $t, $prefix ) );
		$tables          = array_values( $tables );

		if ( ! empty( $excluded_tables ) ) {
			$tables = array_values( array_filter( $tables, fn( $t ) => ! in_array( $t, $excluded_tables, true ) ) );
		}

		sort( $tables );

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

		// Check table is not excluded by security settings.
		$data            = DWM_Data::get_instance();
		$excluded_tables = $data->get_excluded_tables();
		if ( ! empty( $excluded_tables ) && in_array( $table, $excluded_tables, true ) ) {
			$this->send_error( __( 'Table not found.', 'dashboard-widget-manager' ), 404 );
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
				'css'      => $result['css'],
				'scripts'  => $result['scripts'],
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
		if ( ! $this->table_exists( $table ) ) {
			return new WP_Error( 'missing_table', __( 'The selected primary table does not exist.', 'dashboard-widget-manager' ) );
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

		$schema_by_table = array(
			$table => $this->get_table_columns( $table ),
		);
		$allowed_tables = array( $table );

		// --- JOIN clauses ---
		$valid_join_types = array( 'LEFT', 'RIGHT', 'INNER' );
		$join_parts       = array();
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
				return new WP_Error( 'invalid_join_clause', __( 'Invalid join table or column format.', 'dashboard-widget-manager' ) );
			}

			if ( ! $this->table_exists( $join_table ) ) {
				return new WP_Error(
					'join_table_not_found',
					sprintf(
						/* translators: %s: table name */
						__( 'JOIN table "%s" does not exist.', 'dashboard-widget-manager' ),
						$join_table
					)
				);
			}

			if ( ! in_array( $join_table, $allowed_tables, true ) ) {
				$allowed_tables[] = $join_table;
			}
			if ( ! isset( $schema_by_table[ $join_table ] ) ) {
				$schema_by_table[ $join_table ] = $this->get_table_columns( $join_table );
			}

			$local_ref = $this->parse_column_reference( $local_col, $table, $allowed_tables, $schema_by_table, true );
			if ( is_wp_error( $local_ref ) ) {
				return $local_ref;
			}
			$foreign_ref = $this->parse_column_reference( $foreign_col, $join_table, $allowed_tables, $schema_by_table, true );
			if ( is_wp_error( $foreign_ref ) ) {
				return $foreign_ref;
			}

			$join_parts[] = "{$join_type} JOIN `{$join_table}` ON {$local_ref['qualified']} = {$foreign_ref['qualified']}";
		}

		// --- SELECT clause ---
		$select_parts      = array();
		$template_columns  = array();
		if ( empty( $columns ) ) {
			return new WP_Error( 'missing_columns', __( 'At least one column must be selected.', 'dashboard-widget-manager' ) );
		}

		foreach ( $columns as $col ) {
			$column_ref = $this->parse_column_reference( $col, $table, $allowed_tables, $schema_by_table, false );
			if ( is_wp_error( $column_ref ) ) {
				return $column_ref;
			}
			$select_parts[] = $column_ref['qualified'];
			$template_columns[] = $column_ref['table'] === $table
				? $column_ref['column']
				: $column_ref['table'] . '.' . $column_ref['column'];
		}
		if ( empty( $select_parts ) ) {
			return new WP_Error( 'invalid_columns', __( 'No valid columns were selected.', 'dashboard-widget-manager' ) );
		}

		$sql = 'SELECT ' . implode( ', ', $select_parts );
		$sql .= "\nFROM `" . $table . '`';
		if ( ! empty( $join_parts ) ) {
			$sql .= "\n" . implode( "\n", $join_parts );
		}

		// --- WHERE clause ---
		$valid_operators = array( '=', '!=', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL' );
		$where_parts     = array();
		foreach ( $conditions as $cond ) {
			$col = isset( $cond['column'] ) ? sanitize_text_field( $cond['column'] ) : '';
			$op  = isset( $cond['operator'] ) ? strtoupper( sanitize_text_field( $cond['operator'] ) ) : '=';
			$val = isset( $cond['value'] ) ? $cond['value'] : '';

			if ( empty( $col ) || ! preg_match( '/^[a-zA-Z0-9_.]+$/', $col ) ) {
				return new WP_Error( 'invalid_where_column', __( 'Invalid WHERE column name.', 'dashboard-widget-manager' ) );
			}

			if ( ! in_array( $op, $valid_operators, true ) ) {
				continue;
			}

			$column_ref = $this->parse_column_reference( $col, $table, $allowed_tables, $schema_by_table, true );
			if ( is_wp_error( $column_ref ) ) {
				return $column_ref;
			}
			$qualified_col = $column_ref['qualified'];

			if ( $op === 'IS NULL' || $op === 'IS NOT NULL' ) {
				$where_parts[] = $qualified_col . ' ' . $op;
			} elseif ( $op === 'IN' || $op === 'NOT IN' ) {
				$raw_vals = array_filter(
					array_map( 'trim', explode( ',', (string) $val ) ),
					static fn( $v ) => '' !== $v
				);
				if ( empty( $raw_vals ) ) {
					continue;
				}
				$vals          = array_map( static fn( $v ) => $wpdb->prepare( '%s', $v ), $raw_vals );
				$where_parts[] = $qualified_col . ' ' . $op . ' (' . implode( ', ', $vals ) . ')';
			} else {
				$where_parts[] = $qualified_col . ' ' . $op . ' ' . $wpdb->prepare( '%s', $val );
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
				if ( empty( $col ) || ! preg_match( '/^[a-zA-Z0-9_.`]+$/', $col ) ) {
					return new WP_Error( 'invalid_order_column', __( 'Invalid ORDER BY column name.', 'dashboard-widget-manager' ) );
				}
				$column_ref = $this->parse_column_reference( $col, $table, $allowed_tables, $schema_by_table, true );
				if ( is_wp_error( $column_ref ) ) {
					return $column_ref;
				}
				$order_parts[] = "{$column_ref['qualified']} {$dir}";
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
		$template = $this->generate_default_template( $table, $template_columns, $display_mode );

		return array(
			'sql'      => $sql,
			'template' => $template,
			'css'      => $this->generate_default_css( $table, $template_columns, $display_mode ),
			'scripts'  => $this->generate_default_scripts( $table, $template_columns, $display_mode ),
		);
	}

	/**
	 * Check if a table exists.
	 *
	 * @param string $table Table name.
	 * @return bool
	 */
	private function table_exists( string $table ): bool {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	}

	/**
	 * Get database columns for a table.
	 *
	 * @param string $table Table name.
	 * @return array
	 */
	private function get_table_columns( string $table ): array {
		global $wpdb;

		$columns = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $columns ) ? $columns : array();
	}

	/**
	 * Validate and normalize a column reference.
	 *
	 * @param string $column           Column token (table.column or column).
	 * @param string $default_table    Table used when column is unqualified.
	 * @param array  $allowed_tables   Tables allowed by current builder config.
	 * @param array  $schema_by_table  Cached table=>columns map.
	 * @param bool   $allow_backticks  Whether to strip backticks before parsing.
	 * @return array|WP_Error
	 */
	private function parse_column_reference( string $column, string $default_table, array $allowed_tables, array &$schema_by_table, bool $allow_backticks = false ) {
		$column = sanitize_text_field( $column );
		if ( $allow_backticks ) {
			$column = str_replace( '`', '', $column );
		}

		if ( ! preg_match( '/^[a-zA-Z0-9_.]+$/', $column ) ) {
			return new WP_Error( 'invalid_column', __( 'Column format is invalid.', 'dashboard-widget-manager' ) );
		}

		if ( str_contains( $column, '.' ) ) {
			$parts = explode( '.', $column, 2 );
			$table = $parts[0];
			$field = $parts[1];
		} else {
			$table = $default_table;
			$field = $column;
		}

		if ( ! in_array( $table, $allowed_tables, true ) ) {
			return new WP_Error(
				'invalid_column_table',
				sprintf(
					/* translators: %s: table name */
					__( 'Column references disallowed table "%s".', 'dashboard-widget-manager' ),
					$table
				)
			);
		}

		if ( ! isset( $schema_by_table[ $table ] ) ) {
			$schema_by_table[ $table ] = $this->get_table_columns( $table );
		}
		if ( empty( $schema_by_table[ $table ] ) || ! in_array( $field, $schema_by_table[ $table ], true ) ) {
			return new WP_Error(
				'invalid_column_name',
				sprintf(
					/* translators: 1: column name, 2: table name */
					__( 'Column "%1$s" does not exist on table "%2$s".', 'dashboard-widget-manager' ),
					$field,
					$table
				)
			);
		}

		return array(
			'table'     => $table,
			'column'    => $field,
			'qualified' => '`' . $table . '`.`' . $field . '`',
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

	/**
	 * Generate default scoped CSS for the built widget.
	 *
	 * @param string $table        Primary table name (unused, reserved for future use).
	 * @param array  $columns      Selected columns (unused, reserved for future use).
	 * @param string $display_mode 'table' or a chart type.
	 * @return string CSS string.
	 */
	private function generate_default_css( string $table, array $columns, string $display_mode ): string {
		if ( $display_mode !== 'table' ) {
			return ".dwm-chart-wrapper {\n\tposition: relative;\n\tmax-width: 100%;\n}";
		}

		return ".dwm-table-wrapper {\n\toverflow-x: auto;\n\twidth: 100%;\n}\n\n.dwm-builder-table {\n\twidth: 100%;\n\tborder-collapse: collapse;\n\tfont-size: 14px;\n}\n\n.dwm-builder-table thead th {\n\tbackground: #f0f0f0;\n\tfont-weight: 600;\n\ttext-align: left;\n\tpadding: 10px 12px;\n\tborder-bottom: 2px solid #ddd;\n}\n\n.dwm-builder-table tbody td {\n\tpadding: 8px 12px;\n\tborder-bottom: 1px solid #eee;\n\tvertical-align: top;\n}\n\n.dwm-builder-table tbody tr:hover {\n\tbackground: #f9f9f9;\n}";
	}

	/**
	 * Generate default JavaScript for the built widget.
	 *
	 * Chart rendering is handled by the plugin renderer; table mode needs no JS.
	 *
	 * @param string $table        Primary table name.
	 * @param array  $columns      Selected columns.
	 * @param string $display_mode 'table' or a chart type.
	 * @return string JS string (empty for all current modes).
	 */
	private function generate_default_scripts( string $table, array $columns, string $display_mode ): string {
		return '';
	}
}
