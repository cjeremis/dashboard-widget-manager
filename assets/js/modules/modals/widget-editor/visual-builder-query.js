/**
 * Dashboard Widget Manager - Visual Builder Query Module
 *
 * SQL building, auto-build-and-validate, state sync from DOM,
 * config construction, and live status display.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { ajax } from '../../partials/ajax.js';
import {
	state,
	getCodeEditors,
	getBuildTimer, setBuildTimer,
	getIsBuilding, setIsBuilding,
	getDisplayMode,
	cacheQuery,
	escHtml
} from './visual-builder-state.js';

const $ = jQuery;

// ── Auto-build scheduler ─────────────────────────────────────────────────────

export function isBuilderBuilding() {
	return getIsBuilding();
}

export function scheduleBuild() {
	clearTimeout( getBuildTimer() );
	setBuildTimer( setTimeout( autoBuildAndValidate, 400 ) );
}

// ── Sync state from DOM ──────────────────────────────────────────────────────

export function syncStateFromDOM() {
	state.displayMode = getDisplayMode();
	state.table       = $( '#dwm-builder-table' ).val() || '';

	state.selectedColumns = [];
	$( '#dwm-builder-columns-list input[type=checkbox]:checked' ).each( function() {
		state.selectedColumns.push( $( this ).val() );
	} );

	state.noLimit = ! $( '#dwm-builder-limit-toggle' ).is( ':checked' );
	state.limit   = parseInt( $( '#dwm-builder-limit' ).val(), 10 ) || 10;

	state.chartLabelColumn = $( '#dwm-builder-chart-label' ).val() || '';
	state.chartDataColumns = [];
	const singleDataColumn = $( '#dwm-builder-chart-data-select' ).val();
	if ( singleDataColumn ) {
		state.chartDataColumns.push( singleDataColumn );
	}
	$( '#dwm-builder-chart-data-list input[type=checkbox]:checked' ).each( function() {
		state.chartDataColumns.push( $( this ).val() );
	} );
	state.chartTitle      = $( '#dwm-builder-chart-title' ).val() || '';
	state.chartShowLegend = $( '#dwm-builder-chart-legend' ).is( ':checked' );
	state.chartTheme      = $( '#dwm-builder-chart-theme' ).val() || '';
	try {
		const rawConfig = JSON.parse( $( '#dwm-builder-config' ).val() || '{}' );
		state.tableTheme = rawConfig.table_theme || state.tableTheme || 'default';
	} catch ( e ) {}
}

// ── Build config ─────────────────────────────────────────────────────────────

export function buildConfig() {
	return {
		table:              state.table,
		columns:            state.selectedColumns,
		joins:              state.joins.map( j => ( {
			type: j.type, table: j.table, local_col: j.local_col, foreign_col: j.foreign_col,
		} ) ),
		conditions:         state.conditions.map( c => ( {
			column: c.column, operator: c.operator, value: c.value,
		} ) ),
		orders:             state.orders.map( o => ( {
			column: o.column, direction: o.direction,
		} ) ),
		limit:              state.noLimit ? null : state.limit,
		noLimit:            state.noLimit,
		display_mode:       state.displayMode,
		chart_label_column: state.chartLabelColumn,
		chart_data_columns: state.chartDataColumns,
		chart_title:        state.chartTitle,
		chart_show_legend:  state.chartShowLegend,
		chart_theme:        state.chartTheme,
		table_theme:        state.tableTheme || 'default',
	};
}

// ── Live status ──────────────────────────────────────────────────────────────

export function setLiveStatus( status, message ) {
	const $el = $( '#dwm-builder-live-status' );
	$el.removeClass( 'is-building is-valid is-error' );
	if ( status === 'building' ) {
		$el.addClass( 'is-building' ).html( '<span class="dashicons dashicons-update dwm-spin"></span> Building query\u2026' );
	} else if ( status === 'valid' ) {
		$el.empty();
	} else if ( status === 'error' ) {
		$el.addClass( 'is-error' ).html( '<span class="dashicons dashicons-warning"></span> ' + escHtml( message || 'Error' ) );
	} else {
		$el.empty();
	}
}

// ── Client-side query builder ────────────────────────────────────────────────

export function buildQueryClientSide( config ) {
	const table      = config.table      || '';
	const columns    = config.columns    || [];
	const joins      = config.joins      || [];
	const conditions = config.conditions || [];
	const orders     = config.orders     || [];
	const noLimit    = !! config.noLimit;
	const limit      = ! noLimit ? Math.max( 1, Math.min( 1000, parseInt( config.limit, 10 ) || 10 ) ) : 10;

	// SELECT
	const selectParts = [];
	if ( columns.length === 0 ) {
		selectParts.push( '`' + table + '`.*' );
	} else {
		columns.forEach( function( col ) {
			if ( /^[a-zA-Z0-9_]+\.[a-zA-Z0-9_]+$/.test( col ) ) {
				selectParts.push( col );
			} else if ( /^[a-zA-Z0-9_]+$/.test( col ) ) {
				selectParts.push( '`' + table + '`.`' + col + '`' );
			}
		} );
	}

	let sql = 'SELECT ' + selectParts.join( ', ' );
	sql += '\nFROM `' + table + '`';

	// JOINs
	const validJoinTypes = [ 'LEFT', 'RIGHT', 'INNER' ];
	joins.forEach( function( join ) {
		const joinType   = validJoinTypes.indexOf( ( join.type || '' ).toUpperCase() ) !== -1
			? join.type.toUpperCase() : 'LEFT';
		const joinTable  = join.table       || '';
		const localCol   = join.local_col   || '';
		const foreignCol = join.foreign_col || '';
		if ( ! joinTable || ! localCol || ! foreignCol ) return;
		sql += '\n' + joinType + ' JOIN `' + joinTable + '` ON ' + localCol + ' = ' + foreignCol;
	} );

	// WHERE
	const validOps  = [ '=', '!=', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL' ];
	const whereParts = [];
	conditions.forEach( function( cond ) {
		const col = cond.column   || '';
		const op  = ( cond.operator || '=' ).toUpperCase();
		const val = cond.value !== undefined ? String( cond.value ) : '';
		if ( ! col || validOps.indexOf( op ) === -1 ) return;
		if ( op === 'IS NULL' || op === 'IS NOT NULL' ) {
			whereParts.push( col + ' ' + op );
		} else if ( op === 'IN' || op === 'NOT IN' ) {
			const vals = val.split( ',' ).map( function( v ) {
				return "'" + v.trim().replace( /'/g, "\\'" ) + "'";
			} );
			whereParts.push( col + ' ' + op + ' (' + vals.join( ', ' ) + ')' );
		} else {
			whereParts.push( col + ' ' + op + " '" + val.replace( /'/g, "\\'" ) + "'" );
		}
	} );
	if ( whereParts.length ) {
		sql += '\nWHERE ' + whereParts.join( ' AND ' );
	}

	// ORDER BY
	const orderParts = [];
	orders.forEach( function( order ) {
		const col = order.column || '';
		const dir = ( order.direction || 'DESC' ).toUpperCase() === 'ASC' ? 'ASC' : 'DESC';
		if ( col && /^[a-zA-Z0-9_.`]+$/.test( col ) ) {
			orderParts.push( col + ' ' + dir );
		}
	} );
	if ( orderParts.length ) {
		sql += '\nORDER BY ' + orderParts.join( ', ' );
	}

	// LIMIT
	if ( ! noLimit ) {
		sql += '\nLIMIT ' + limit;
	}

	return sql;
}

// ── Auto build & validate ────────────────────────────────────────────────────

export function autoBuildAndValidate() {
	syncStateFromDOM();

	if ( ! state.table ) return;

	const config = buildConfig();
	const sql = buildQueryClientSide( config );

	$( document ).trigger( 'dwm:autoBuildUpdated', [ sql ] );
	$( document ).trigger( 'dwm:autoBuildConfigUpdated', [ config ] );

	cacheQuery( sql );

	// Store chart config.
	if ( state.displayMode !== 'table' ) {
		$( '#dwm-chart-type' ).val( state.displayMode );
		$( '#dwm-chart-config' ).val( JSON.stringify( {
			label_column: state.chartLabelColumn,
			data_columns: state.chartDataColumns,
			title:        state.chartTitle,
			show_legend:  state.chartShowLegend,
			theme:        state.chartTheme || '',
		} ) );
	} else {
		$( '#dwm-chart-type' ).val( '' );
		$( '#dwm-chart-config' ).val( '' );
	}

	// Store builder config, preserving non-query keys (e.g. output_config, show_description).
	let existingConfig = {};
	try { existingConfig = JSON.parse( $( '#dwm-builder-config' ).val() || '{}' ); } catch ( e ) {}
	const mergedConfig = Object.assign( {}, existingConfig, config );
	$( '#dwm-builder-config' ).val( JSON.stringify( mergedConfig ) );

	// Trigger building event, then validate to get row count.
	setIsBuilding( true );
	$( document ).trigger( 'dwm:builderBuilding' );
	setLiveStatus( 'building', '' );

	ajax(
		'dwm_validate_query',
		{
			query: sql,
			max_execution_time: parseInt( $( '#dwm-max-execution-time' ).val(), 10 ) || 30,
		},
		function( validData ) {
			setIsBuilding( false );
			setLiveStatus( 'valid', validData.row_count + ' rows' );
			$( document ).trigger( 'dwm:builderValidated', [ { sql, validationResults: validData } ] );
		},
		function( errData ) {
			setIsBuilding( false );
			setLiveStatus( 'error', errData.message || 'Invalid query' );
			$( document ).trigger( 'dwm:builderInvalidated' );
		}
	);

	// Always generate template, CSS, and JS from the server.
	const _codeEditors = getCodeEditors();
	ajax(
		'dwm_build_query',
		{ config: JSON.stringify( config ) },
		function( data ) {
			const templateEditingEnabled = $( '#dwm-template-edit-toggle' ).is( ':checked' );
			const stylesEditingEnabled   = $( '#dwm-styles-edit-toggle' ).is( ':checked' );
			const scriptsEditingEnabled  = $( '#dwm-scripts-edit-toggle' ).is( ':checked' );

			if ( _codeEditors ) {
				if ( _codeEditors.template && ! templateEditingEnabled ) {
					_codeEditors.template.codemirror.setValue( data.template || '' );
				}
				if ( _codeEditors.styles && ! stylesEditingEnabled ) {
					_codeEditors.styles.codemirror.setValue( data.css || '' );
				}
				if ( _codeEditors.scripts && ! scriptsEditingEnabled ) {
					_codeEditors.scripts.codemirror.setValue( data.scripts || '' );
				}
			} else {
				if ( ! templateEditingEnabled ) {
					$( '#dwm-widget-template' ).val( data.template || '' );
				}
				if ( ! stylesEditingEnabled ) {
					$( '#dwm-widget-styles' ).val( data.css || '' );
				}
				if ( ! scriptsEditingEnabled ) {
					$( '#dwm-widget-scripts' ).val( data.scripts || '' );
				}
			}

			$( document ).trigger( 'dwm:serverBuildApplied' );
		},
		function() {}
	);
}
