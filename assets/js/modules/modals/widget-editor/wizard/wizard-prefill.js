/**
 * Dashboard Widget Manager - Wizard Prefill Module
 *
 * Functions that pre-fill the visual builder and theme/chart configuration
 * from wizard state when transitioning out of the creation wizard.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { wizardState } from './wizard-state.js';
import { isChartDisplayMode, escAttr } from './wizard-utils.js';
import { buildThemeAssets, isChartDisplayMode as isChartModeAsset, isDataDisplayMode as isDataModeAsset } from '../theme-assets.js';
import { setBuilderOrders } from '../visual-builder.js';

const $ = jQuery;

/**
 * Pre-fill the visual builder with table wizard data.
 */
export function prefillTableBuilder( config ) {
	const selectedCols = config.columns || [];
	if ( selectedCols.length > 0 ) {
		$( '#dwm-builder-columns-list input[type="checkbox"]' ).prop( 'checked', false );
		selectedCols.forEach( function( col ) {
			$( '#dwm-builder-columns-list input[value="' + col + '"]' ).prop( 'checked', true );
		});
	}
}

/**
 * Pre-fill the visual builder's join rows from wizard data
 */
export function prefillBuilderJoins( joins ) {
	const validJoins = joins.filter( function( j ) { return j.table.trim() !== ''; });
	if ( validJoins.length === 0 ) return;

	const $list = $( '#dwm-builder-joins-list' ).empty();

	validJoins.forEach( function( join, i ) {
		const joinTypes = ['LEFT', 'RIGHT', 'INNER'].map( function( t ) {
			return '<option value="' + t + '"' + ( join.type === t ? ' selected' : '' ) + '>' + t + '</option>';
		}).join('');

		$list.append(
			'<div class="dwm-builder-join-row" data-index="' + i + '">' +
				'<select class="dwm-join-type">' + joinTypes + '</select>' +
				' JOIN ' +
				'<input type="text" class="dwm-join-table dwm-input-text" placeholder="Table name" value="' + escAttr( join.table ) + '">' +
				' ON ' +
				'<input type="text" class="dwm-join-local-col dwm-input-text" placeholder="table.column" value="' + escAttr( join.local_col ) + '">' +
				' = ' +
				'<input type="text" class="dwm-join-foreign-col dwm-input-text" placeholder="join_table.column" value="' + escAttr( join.foreign_col ) + '">' +
				'<button type="button" class="dwm-icon-button dwm-icon-button-danger dwm-remove-join" data-index="' + i + '" title="Remove join">' +
					'<span class="dashicons dashicons-trash"></span>' +
				'</button>' +
			'</div>'
		);
	});

	$( '#dwm-builder-joins-section' ).show();
}

/**
 * Pre-fill the visual builder's condition rows from wizard data
 */
export function prefillBuilderConditions( conditions ) {
	const valid = conditions.filter( function( c ) { return c.column.trim() !== ''; });
	if ( valid.length === 0 ) return;

	const $list = $( '#dwm-builder-conditions-list' ).empty();
	const operators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE', 'IS NULL', 'IS NOT NULL'];

	valid.forEach( function( cond, i ) {
		const opOptions = operators.map( function( op ) {
			return '<option value="' + escAttr( op ) + '"' + ( cond.operator === op ? ' selected' : '' ) + '>' + op + '</option>';
		}).join('');

		const isNullOp = cond.operator === 'IS NULL' || cond.operator === 'IS NOT NULL';

		$list.append(
			'<div class="dwm-builder-condition-row" data-index="' + i + '">' +
				'<input type="text" class="dwm-condition-column dwm-input-text" placeholder="column_name" value="' + escAttr( cond.column ) + '">' +
				'<select class="dwm-condition-operator dwm-select">' + opOptions + '</select>' +
				'<input type="text" class="dwm-condition-value dwm-input-text" placeholder="value" value="' + escAttr( cond.value || '' ) + '"' + ( isNullOp ? ' style="display:none"' : '' ) + '>' +
				'<button type="button" class="dwm-icon-button dwm-icon-button-danger dwm-remove-condition" data-index="' + i + '" title="Remove filter">' +
					'<span class="dashicons dashicons-trash"></span>' +
				'</button>' +
			'</div>'
		);
	});

	$( '#dwm-builder-conditions-section' ).show();
}

/**
 * Pre-fill the visual builder's order by and limit from wizard data
 */
export function prefillBuilderOrder( sorts, limit, noLimit ) {
	setBuilderOrders( sorts || [] );
	if ( noLimit ) {
		$( '#dwm-builder-no-limit' ).prop( 'checked', true );
		$( '#dwm-builder-limit' ).val( '' );
	} else {
		$( '#dwm-builder-no-limit' ).prop( 'checked', false );
		$( '#dwm-builder-limit' ).val( limit );
	}
}

/**
 * Pre-fill Template, Styles, and Scripts tabs based on wizard theme selection
 */
export function prefillThemeAssets( theme, mode ) {
	// Build column aliases map from wizard state for template generation
	let columnAliases = null;
	let outputConfig = null;

	if ( wizardState.data.columnAliases && Array.isArray( wizardState.data.columnAliases ) ) {
		columnAliases = {};
		const colConfigs = [];
		wizardState.data.columnAliases.forEach( function( col ) {
			const key = col.key || col.original;
			columnAliases[ key ] = col.alias || key;
			colConfigs.push( col );
		});
		outputConfig = { columns: colConfigs };
	}

	const assets = buildThemeAssets( mode, theme, columnAliases, outputConfig );
	$( '#dwm-widget-template' ).val( assets.template || '' );
	$( '#dwm-widget-styles' ).val( assets.styles || '' );
	$( '#dwm-widget-scripts' ).val( assets.scripts || '' );
}

/**
 * Pre-fill chart configuration fields in the builder and hidden form data.
 */
export function prefillChartConfiguration( displayMode, axisConfig, theme ) {
	const labelColumn = axisConfig.labelColumn || '';
	const dataColumns = Array.isArray( axisConfig.dataColumns ) ? axisConfig.dataColumns : [];
	const chartTitle = axisConfig.title || '';
	const showLegend = axisConfig.showLegend !== false;

	$( '#dwm-builder-chart-title' ).val( chartTitle );
	$( '#dwm-builder-chart-legend' ).prop( 'checked', showLegend );
	$( '#dwm-builder-chart-label' ).val( labelColumn );

	$( '#dwm-builder-chart-data-list input[type="checkbox"]' ).prop( 'checked', false );
	dataColumns.forEach( function( columnName ) {
		$( '#dwm-builder-chart-data-list input[value="' + escAttr( columnName ) + '"]' ).prop( 'checked', true );
	} );

	$( '#dwm-chart-type' ).val( displayMode );
	$( '#dwm-chart-config' ).val( JSON.stringify( {
		label_column: labelColumn,
		data_columns: dataColumns,
		title: chartTitle,
		show_legend: showLegend,
		theme: theme || 'classic'
	} ) );
}

/**
 * Build canonical builder config from wizard state for persistence.
 */
export function buildBuilderConfigFromWizard() {
	const stepConfig = wizardState.data.stepConfig || {};
	const chartAxisConfig = wizardState.data.chartAxisConfig || {};
	const displayMode = wizardState.data.displayMode || 'table';
	const chartTheme = wizardState.data.chartTheme || 'classic';
	const defaultThemes = { table: 'default', list: 'clean', button: 'solid', 'card-list': 'elevated' };
	const orderedColumns = Array.isArray( wizardState.data.columnAliases ) && wizardState.data.columnAliases.length > 0
		? wizardState.data.columnAliases.map( function( col ) {
			return col.key || col.original;
		} ).filter( function( key ) { return !! key; } )
		: ( stepConfig.columns || [] );

	const config = {
		table: stepConfig.table || '',
		columns: orderedColumns,
		joins: wizardState.data.joins || [],
		conditions: wizardState.data.conditions || [],
		orders: wizardState.data.orders || [],
		limit: wizardState.data.noLimit ? null : ( wizardState.data.limit || 10 ),
		noLimit: wizardState.data.noLimit !== undefined ? !! wizardState.data.noLimit : true,
		display_mode: displayMode,
		chart_label_column: isChartDisplayMode( displayMode ) ? ( chartAxisConfig.labelColumn || '' ) : '',
		chart_data_columns: isChartDisplayMode( displayMode ) ? ( chartAxisConfig.dataColumns || [] ) : [],
		chart_title: isChartDisplayMode( displayMode ) ? ( chartAxisConfig.title || '' ) : '',
		chart_show_legend: isChartDisplayMode( displayMode ) ? ( chartAxisConfig.showLegend !== false ) : true,
		chart_theme: isChartDisplayMode( displayMode ) ? chartTheme : '',
		table_theme: isChartDisplayMode( displayMode ) ? '' : ( wizardState.data.theme || defaultThemes[ displayMode ] || 'default' )
	};

	// Include output_config for data display modes.
	if ( ! isChartDisplayMode( displayMode ) && Array.isArray( wizardState.data.columnAliases ) ) {
		const savedOutputColumns = {};
		const savedColumns = wizardState.data.outputConfig && Array.isArray( wizardState.data.outputConfig.columns )
			? wizardState.data.outputConfig.columns
			: [];

		savedColumns.forEach( function( col ) {
			const key = col.key || col.original;
			if ( key ) {
				savedOutputColumns[ key ] = col;
			}
		} );

		const mergedColumns = wizardState.data.columnAliases.map( function( col ) {
			const key = col.key || col.original;
			const saved = savedOutputColumns[ key ] || {};
			const alias = col.alias || saved.alias || key;
			return {
				key: key,
				alias: alias,
				visible: col.visible !== false && saved.visible !== false,
				link: col.link || saved.link || { enabled: false, url: '', open_in_new_tab: true },
				formatter: col.formatter || saved.formatter || { type: 'text', options: {} }
			};
		} );

		config.output_config = {
			display_mode: displayMode,
			theme: wizardState.data.theme || defaultThemes[ displayMode ] || 'default',
			columns: mergedColumns
		};
	}

	return config;
}
