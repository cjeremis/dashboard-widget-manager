/**
 * Dashboard Widget Manager - Visual Builder Module (Orchestrator)
 *
 * UI-driven widget builder that generates SQL queries and templates
 * from dropdown/selector inputs without requiring code knowledge.
 * Implementation details are split across sub-modules.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { ajax } from '../../partials/ajax.js';
import { ensureSearchableSelect, refreshSearchableSelect } from '../../partials/searchable-select.js';
import {
	state,
	setCodeEditors, setSwitchTab,
	getPendingTableChange, setPendingTableChange,
	getPendingDisplayModeChange, setPendingDisplayModeChange,
	DISPLAY_MODE_SELECTOR, getDisplayMode, setDisplayMode,
	getCachedTables, cacheTables,
	getCachedColumns, cacheColumns,
	escHtml, escAttr, isNumericColumnType,
} from './visual-builder-state.js';
import {
	scheduleBuild,
	autoBuildAndValidate,
	setLiveStatus,
	syncStateFromDOM,
} from './visual-builder-query.js';
import { isChartDisplayMode } from './theme-assets.js';

// Re-export public API from sub-modules
export { getCachedQuery } from './visual-builder-state.js';
export { isBuilderBuilding } from './visual-builder-query.js';

const $ = jQuery;

// ── Init ─────────────────────────────────────────────────────────────────────

export function initVisualBuilder( codeEditors, switchTabFn ) {
	setCodeEditors( codeEditors );
	setSwitchTab( switchTabFn );

	loadTables();
	ensureSearchableSelect( '#dwm-builder-table', 'Select Primary Table', 'Search Tables' );
	refreshSearchableSelect( '#dwm-builder-table' );
	bindEvents();
}

// ── Populate from config ─────────────────────────────────────────────────────

export function populateBuilderFromConfig( configJson ) {
	if ( ! configJson ) return;

	let config;
	try {
		config = JSON.parse( configJson );
	} catch ( e ) {
		return;
	}

	state.displayMode      = config.display_mode       || 'table';
	state.table            = config.table              || '';
	state.selectedColumns  = config.columns            || [];
	state.joins            = ( config.joins      || [] ).map( j => ( { type: j.type, table: j.table, local_col: j.local_col, foreign_col: j.foreign_col } ) );
	state.conditions       = ( config.conditions || [] ).map( c => ( { column: c.column, operator: c.operator, value: c.value, columnType: c.columnType || '' } ) );

	if ( config.orders && config.orders.length ) {
		state.orders = config.orders.map( o => ( { column: o.column, direction: o.direction || 'DESC' } ) );
	} else if ( config.order_by ) {
		state.orders = [ { column: config.order_by, direction: config.order_dir || 'DESC' } ];
	} else {
		state.orders = [];
	}

	state.limit            = config.limit              || 10;
	state.noLimit          = config.noLimit !== undefined ? config.noLimit : true;
	state.chartLabelColumn = config.chart_label_column || '';
	state.chartDataColumns = config.chart_data_columns || [];
	state.chartTitle       = config.chart_title        || '';
	state.chartShowLegend  = config.chart_show_legend !== false;
	state.chartTheme       = config.chart_theme        || '';
	state.tableTheme       = config.table_theme        || 'default';
	if ( state.displayMode !== 'table' && ! state.chartTheme ) {
		state.chartTheme = 'classic';
	}

	// Restore UI.
	setDisplayMode( state.displayMode );
	$( '#dwm-builder-limit-toggle' ).prop( 'checked', ! state.noLimit );
	$( '#dwm-builder-limit' ).val( state.limit );
	$( '.dwm-builder-limit-input' ).toggle( ! state.noLimit );
	$( '#dwm-builder-chart-title' ).val( state.chartTitle );
	$( '#dwm-builder-chart-legend' ).prop( 'checked', state.chartShowLegend );
	$( '#dwm-builder-chart-theme' ).val( state.chartTheme || 'classic' );

	if ( state.table ) {
		$( '#dwm-builder-table' ).val( state.table );
		loadColumnsForTable( state.table, function() {
			restoreColumnSelections();
			renderConditions();
			renderJoins();
			renderOrders();
			updateChartLabelOptions();
			showBuilderSections();

			$( document ).trigger( 'dwm:builderRestored' );

			state.joins.forEach( function( join ) {
				if ( join.table && ! state.joinColumns[ join.table ] ) {
					const cached = getCachedColumns( join.table );
					if ( cached ) {
						state.joinColumns[ join.table ] = cached;
					} else {
						ajax( 'dwm_get_table_columns', { table: join.table }, function( data ) {
							state.joinColumns[ join.table ] = data.columns || [];
							cacheColumns( join.table, state.joinColumns[ join.table ] );
							updateChartLabelOptions();
							renderConditions();
						}, null );
					}
				}
			} );

			scheduleBuild();
		} );
	}

	toggleChartOptions( state.displayMode );
	$( document ).trigger( 'dwm:builderDisplayModeChanged', [ state.displayMode ] );
}

// ── Table loading ────────────────────────────────────────────────────────────

function loadTables() {
	const $select = $( '#dwm-builder-table' );

	const cached = getCachedTables();
	if ( cached ) {
		cached.forEach( t => $select.append( `<option value="${ escHtml(t) }">${ escHtml(t) }</option>` ) );
		if ( state.table ) $select.val( state.table );
		ensureSearchableSelect( '#dwm-builder-table', 'Select Primary Table', 'Search Tables' );
		refreshSearchableSelect( '#dwm-builder-table' );
		return;
	}

	$select.html( '<option value="">' + dwmAdminVars.i18n.loading + '</option>' );
	ajax(
		'dwm_get_tables',
		{},
		function( data ) {
			const tables = data.tables || [];
			cacheTables( tables );
			tables.forEach( t => $select.append( `<option value="${ escHtml(t) }">${ escHtml(t) }</option>` ) );
			if ( state.table ) $select.val( state.table );
			ensureSearchableSelect( '#dwm-builder-table', 'Select Primary Table', 'Search Tables' );
			refreshSearchableSelect( '#dwm-builder-table' );
		},
		function() {
			$select.html( '<option value="">Failed to load tables</option>' );
			ensureSearchableSelect( '#dwm-builder-table', 'Select Primary Table', 'Search Tables' );
			refreshSearchableSelect( '#dwm-builder-table' );
		}
	);
}

// ── Column loading ───────────────────────────────────────────────────────────

function loadColumnsForTable( table, callback ) {
	if ( ! table ) return;

	const cached = getCachedColumns( table );
	if ( cached ) {
		state.availableColumns = cached;
		renderColumnCheckboxes( state.availableColumns );
		updateChartLabelOptions();
		showBuilderSections();
		if ( callback ) callback();
		return;
	}

	$('#dwm-builder-columns-section').show();
	$('#dwm-builder-columns-list')
		.addClass( 'is-loading' )
		.html( '<span class="dwm-loading"></span> Loading columns\u2026' );
	$('#dwm-builder-table').prop( 'disabled', true );

	ajax(
		'dwm_get_table_columns',
		{ table },
		function( data ) {
			state.availableColumns = data.columns || [];
			cacheColumns( table, state.availableColumns );
			$('#dwm-builder-columns-list').removeClass( 'is-loading' );
			$('#dwm-builder-table').prop( 'disabled', false );
			renderColumnCheckboxes( state.availableColumns );
			updateChartLabelOptions();
			showBuilderSections();
			if ( callback ) callback();
		},
		function() {
			$('#dwm-builder-columns-list')
				.removeClass( 'is-loading' )
				.html( '<p style="color:#d63638">Failed to load columns.</p>' );
			$('#dwm-builder-table').prop( 'disabled', false );
			showNotice( 'Failed to load columns for table.', 'error' );
		}
	);
}

// ── Column checkbox rendering ────────────────────────────────────────────────

function renderColumnCheckboxes( columns ) {
	const $list = $( '#dwm-builder-columns-list' ).empty();
	$( '#dwm-builder-columns-controls' ).toggle( columns.length > 0 );

	columns.forEach( col => {
		const id      = `dwm-col-${ escAttr( col.name ) }`;
		const checked = state.selectedColumns.includes( col.name ) ? 'checked' : '';
		const badge   = col.key === 'PRI' ? ' <span class="dwm-col-badge dwm-col-badge-pk">PK</span>' : '';

		$list.append(`
			<label class="dwm-col-checkbox" for="${ id }">
				<input type="checkbox" id="${ id }" value="${ escAttr( col.name ) }" ${ checked }>
				<span class="dwm-col-name">${ escHtml( col.name ) }</span>
				<span class="dwm-col-type">${ escHtml( col.type ) }</span>
				${ badge }
			</label>
		`);
	} );
}

function restoreColumnSelections() {
	state.selectedColumns = [];
	$( '#dwm-builder-columns-list input[type=checkbox]:checked' ).each( function() {
		state.selectedColumns.push( $( this ).val() );
	} );
}

// ── ORDER BY rendering ───────────────────────────────────────────────────────

function renderOrders() {
	const $list = $( '#dwm-builder-orders-list' ).empty();

	if ( state.orders.length === 0 ) {
		$list.append( '<p class="dwm-builder-empty-state">No order rules. Click <strong>Add Order</strong> to sort your results.</p>' );
		return;
	}

	state.orders.forEach( ( order, i ) => {
		const directionDisplay = order.direction === 'ASC' ? 'Oldest First (ASC)' : 'Newest First (DESC)';

		$list.append(`
			<div class="dwm-wizard-filter-summary" data-index="${ i }">
				<div class="dwm-filter-field">
					<span class="dwm-filter-label">Column</span>
					<span class="dwm-filter-value">${ escHtml( order.column || '' ) }</span>
				</div>
				<div class="dwm-filter-field">
					<span class="dwm-filter-label">Direction</span>
					<span class="dwm-filter-value">${ escHtml( directionDisplay ) }</span>
				</div>
				<div class="dwm-wizard-filter-actions">
					<button type="button" class="dwm-icon-button dwm-builder-edit-order" data-index="${ i }" title="Edit order">
						<span class="dashicons dashicons-edit"></span>
					</button>
					<button type="button" class="dwm-icon-button dwm-icon-button-danger dwm-builder-remove-order" data-index="${ i }" title="Remove order">
						<span class="dashicons dashicons-trash"></span>
					</button>
				</div>
			</div>
		`);
	} );
}

// ── Chart label / data options ───────────────────────────────────────────────

function updateChartLabelOptions() {
	const $label  = $( '#dwm-builder-chart-label' ).empty();
	const $data   = $( '#dwm-builder-chart-data-list' ).empty();
	$data.removeClass( 'dwm-builder-checkboxes--single' );
	const mode    = state.displayMode || 'table';
	const singleValueMode = mode === 'pie' || mode === 'doughnut';

	const allCols = getAllAvailableColumns();
	const numericCols = allCols.filter( function( col ) {
		return isNumericColumnType( col.type );
	} );
	const allColumnNames = allCols.map( function( col ) { return col.name; } );
	if ( allColumnNames.indexOf( state.chartLabelColumn ) === -1 ) {
		state.chartLabelColumn = allColumnNames[ 0 ] || '';
	}

	$label.append( '<option value="">— Select label column —</option>' );
	allCols.forEach( col => {
		const sel = col.name === state.chartLabelColumn ? 'selected' : '';
		$label.append( `<option value="${ escAttr(col.name) }" ${ sel }>${ escHtml(col.name) }</option>` );
	} );

	if ( singleValueMode && state.chartDataColumns.length > 1 ) {
		state.chartDataColumns = [ state.chartDataColumns[ 0 ] ];
	}
	if ( singleValueMode && state.chartDataColumns.length === 0 && numericCols.length > 0 ) {
		state.chartDataColumns = [ numericCols[ 0 ].name ];
	}

	if ( numericCols.length === 0 ) {
		$data.append( '<p class="dwm-no-columns-message">No numeric columns available for chart values.</p>' );
		return;
	}

	if ( singleValueMode ) {
		const selectedValue = state.chartDataColumns[ 0 ] || numericCols[ 0 ].name;
		let optionsHtml = '<option value="">— Select numeric column —</option>';

		numericCols.forEach( function( col ) {
			const selected = col.name === selectedValue ? ' selected' : '';
			optionsHtml += '<option value="' + escAttr( col.name ) + '"' + selected + '>' + escHtml( col.name ) + ' (' + escHtml( col.type || '' ) + ')</option>';
		} );

		$data.append(
			'<select id="dwm-builder-chart-data-select" class="dwm-select">' +
				optionsHtml +
			'</select>'
		);
		return;
	}

	numericCols.forEach( col => {
		const id      = `dwm-chart-data-${ escAttr(col.name) }`;
		const checked = state.chartDataColumns.includes( col.name ) ? 'checked' : '';
		const inputType = 'checkbox';
		const inputName = '';
		$data.append(`
			<label class="dwm-col-checkbox" for="${ id }">
				<input type="${ inputType }" ${ inputName ? `name="${ inputName }"` : '' } id="${ id }" value="${ escAttr(col.name) }" ${ checked }>
				<span class="dwm-col-name">${ escHtml(col.name) }</span>
				<span class="dwm-col-type">${ escHtml(col.type || '') }</span>
			</label>
		`);
	} );

	if ( numericCols.length === 1 ) {
		$data.addClass( 'dwm-builder-checkboxes--single' );
	}
}

function getAllAvailableColumns() {
	const cols = state.availableColumns.map( c => ( { name: c.name, type: c.type || '' } ) );
	Object.values( state.joinColumns ).forEach( jCols => {
		jCols.forEach( c => cols.push( { name: c.name, type: c.type || '' } ) );
	} );
	return cols;
}

// ── Show/hide sections ───────────────────────────────────────────────────────

function showBuilderSections() {
	$( '#dwm-builder-columns-section' ).show();
	updatePostColumnsSections();
}

function updatePostColumnsSections() {
	const hasColumns = $( '#dwm-builder-columns-list input[type=checkbox]:checked' ).length > 0;
	$( '#dwm-builder-joins-section, #dwm-builder-conditions-section, #dwm-builder-order-section, #dwm-builder-limit-section, #dwm-builder-apply-section' ).toggle( hasColumns );
}

function toggleChartOptions( mode ) {
	const isChart = isChartDisplayMode( mode );
	$( '#dwm-builder-chart-options' ).toggle( isChart );
	updateChartConfigUiForMode( mode );
}

// ── JOIN rows ────────────────────────────────────────────────────────────────

function renderJoins() {
	const $list = $( '#dwm-builder-joins-list' ).empty();

	state.joins.forEach( ( join, i ) => {
		let joinTypeDisplay = join.type;
		if ( join.type === 'LEFT' )  joinTypeDisplay = 'Left Join';
		else if ( join.type === 'RIGHT' ) joinTypeDisplay = 'Right Join';
		else if ( join.type === 'INNER' ) joinTypeDisplay = 'Inner Join';

		$list.append(`
			<div class="dwm-wizard-join-summary" data-index="${ i }">
				<div class="dwm-join-field half-width">
					<span class="dwm-join-label">Type</span>
					<span class="dwm-join-value">${ escHtml( joinTypeDisplay ) }</span>
				</div>
				<div class="dwm-join-field">
					<span class="dwm-join-label">Table</span>
					<span class="dwm-join-value">${ escHtml( join.table || '' ) }</span>
				</div>
				<div class="dwm-join-on-label">ON</div>
				<div class="dwm-join-field">
					<span class="dwm-join-label">Primary Column</span>
					<span class="dwm-join-value">${ escHtml( join.local_col || '' ) }</span>
				</div>
				<div class="dwm-join-equals-label">=</div>
				<div class="dwm-join-field">
					<span class="dwm-join-label">Join Column</span>
					<span class="dwm-join-value">${ escHtml( join.foreign_col || '' ) }</span>
				</div>
				<div class="dwm-wizard-join-actions">
					<button type="button" class="dwm-icon-button dwm-builder-edit-join" data-index="${ i }" title="Edit join">
						<span class="dashicons dashicons-edit"></span>
					</button>
					<button type="button" class="dwm-icon-button dwm-icon-button-danger dwm-builder-remove-join" data-index="${ i }" title="Remove join">
						<span class="dashicons dashicons-trash"></span>
					</button>
				</div>
			</div>
		`);
	} );
}

// ── Condition rows ───────────────────────────────────────────────────────────

function renderConditions() {
	const $list = $( '#dwm-builder-conditions-list' ).empty();

	state.conditions.forEach( ( cond, i ) => {
		const isNull      = cond.operator === 'IS NULL' || cond.operator === 'IS NOT NULL';
		const valueHtml   = ( ! isNull && cond.value )
			? `<div class="dwm-filter-field">
					<span class="dwm-filter-label">Value</span>
					<span class="dwm-filter-value">${ escHtml( cond.value ) }</span>
				</div>`
			: '';

		$list.append(`
			<div class="dwm-wizard-filter-summary" data-index="${ i }">
				<div class="dwm-filter-field">
					<span class="dwm-filter-label">Column</span>
					<span class="dwm-filter-value">${ escHtml( cond.column || '' ) }</span>
				</div>
				<div class="dwm-filter-field">
					<span class="dwm-filter-label">Operator</span>
					<span class="dwm-filter-value">${ escHtml( cond.operator || '' ) }</span>
				</div>
				${ valueHtml }
				<div class="dwm-wizard-filter-actions">
					<button type="button" class="dwm-icon-button dwm-builder-edit-filter" data-index="${ i }" title="Edit filter">
						<span class="dashicons dashicons-edit"></span>
					</button>
					<button type="button" class="dwm-icon-button dwm-icon-button-danger dwm-builder-remove-filter" data-index="${ i }" title="Remove filter">
						<span class="dashicons dashicons-trash"></span>
					</button>
				</div>
			</div>
		`);
	} );
}

// ── Builder confirmation helpers ─────────────────────────────────────────────

function hasBuilderConfiguration() {
	return !! ( state.table && state.selectedColumns.length > 0 );
}

function applyTableChange( table ) {
	state.table            = table;
	state.availableColumns = [];
	state.selectedColumns  = [];
	state.joins            = [];
	state.joinColumns      = {};
	state.conditions       = [];
	state.orders           = [];

	$( '#dwm-builder-columns-list' ).empty();
	$( '#dwm-builder-joins-list' ).empty();
	$( '#dwm-builder-conditions-list' ).empty();
	$( '#dwm-builder-orders-list' ).empty();
	setLiveStatus( '' );

	$( document ).trigger( 'dwm:builderDataCleared' );

	if ( table ) {
		loadColumnsForTable( table, function() {
			scheduleBuild();
		} );
	} else {
		$( '#dwm-builder-columns-section, #dwm-builder-joins-section, #dwm-builder-conditions-section, #dwm-builder-order-section, #dwm-builder-limit-section, #dwm-builder-apply-section' ).hide();
	}
}

function applyDisplayModeChange( mode ) {
	state.displayMode = mode;
	if ( isChartDisplayMode( mode ) && ! state.chartTheme ) {
		state.chartTheme = 'classic';
	}
	if ( ! isChartDisplayMode( mode ) ) {
		state.chartTheme = '';
	}
	toggleChartOptions( mode );
	updateChartLabelOptions();
	$( document ).trigger( 'dwm:builderDisplayModeChanged', [ mode ] );
	scheduleBuild();
}

// ── Chart UI helpers ─────────────────────────────────────────────────────────

function updateChartConfigUiForMode( mode ) {
	const isPieLike = mode === 'pie' || mode === 'doughnut';
	const modeName = mode === 'line' ? 'Line' : mode === 'pie' ? 'Pie' : mode === 'doughnut' ? 'Doughnut' : 'Bar';

	$( '#dwm-builder-chart-config-title' ).text( modeName + ' Chart Configuration' );
	$( '#dwm-builder-chart-config-desc' ).text(
		isPieLike
			? 'Configure a label column and one numeric value column for this chart.'
			: 'Configure label and numeric dataset columns for this chart.'
	);
	$( '#dwm-builder-chart-label-title' ).text( isPieLike ? 'Label Column *' : 'X-Axis Label Column *' );
	$( '#dwm-builder-chart-data-title' ).text( isPieLike ? 'Value Column *' : 'Y-Axis Data Column(s) *' );

	const helpLabel = modeName + ' Chart Configuration help';
	$( '#dwm-builder-chart-config-help' )
		.attr( 'data-docs-page', 'feature-visual-builder-chart-config' )
		.attr( 'aria-label', helpLabel )
		.attr( 'title', helpLabel );
}

// ── Events ───────────────────────────────────────────────────────────────────

function bindEvents() {
	// Column select all / deselect all.
	$( document ).on( 'click', '#dwm-builder-select-all-cols', function( e ) {
		e.preventDefault();
		$( '#dwm-builder-columns-list input[type=checkbox]' ).prop( 'checked', true ).trigger( 'change' );
		scheduleBuild();
	} );

	$( document ).on( 'click', '#dwm-builder-deselect-all-cols', function( e ) {
		e.preventDefault();
		$( '#dwm-builder-columns-list input[type=checkbox]' ).prop( 'checked', false ).trigger( 'change' );
		scheduleBuild();
	} );

	// Table selection.
	$( document ).on( 'change', '#dwm-builder-table', function() {
		const newTable  = $( this ).val();
		const hasConfig = state.table && (
			state.selectedColumns.length > 0 ||
			state.joins.length > 0 ||
			state.conditions.length > 0 ||
			state.orders.length > 0
		);

		if ( hasConfig && newTable !== state.table ) {
			setPendingTableChange( newTable );
			$( '#dwm-builder-table' ).val( state.table );
			$( '#dwm-confirm-table-change-modal' ).addClass( 'active' );
			return;
		}

		applyTableChange( newTable );
	} );

	// Confirm table change.
	$( document ).on( 'click', '#dwm-confirm-table-change-yes', function() {
		const pending = getPendingTableChange();
		if ( ! pending ) return;
		$( '#dwm-confirm-table-change-modal' ).removeClass( 'active' );
		setPendingTableChange( '' );
		$( '#dwm-builder-table' ).val( pending );
		applyTableChange( pending );
	} );

	$( document ).on( 'click', '.dwm-confirm-table-change-close', function() {
		if ( ! getPendingTableChange() ) return;
		$( '#dwm-confirm-table-change-modal' ).removeClass( 'active' );
		setPendingTableChange( '' );
	} );

	// Column checkboxes.
	$( document ).on( 'change', '#dwm-builder-columns-list input[type=checkbox]', function() {
		updatePostColumnsSections();
		scheduleBuild();
	} );

	// Display mode toggle.
	$( document ).on( 'change', DISPLAY_MODE_SELECTOR, function() {
		const newMode = $( this ).val();
		const oldMode = state.displayMode;

		if ( oldMode && oldMode !== newMode && hasBuilderConfiguration() ) {
			setPendingDisplayModeChange( newMode );
			setDisplayMode( oldMode );
			$( '#dwm-confirm-display-type-change-modal' ).addClass( 'active' );
			return;
		}

		applyDisplayModeChange( newMode );
	} );

	// Confirm display mode change.
	$( document ).on( 'click', '#dwm-confirm-display-type-change-yes', function() {
		const pending = getPendingDisplayModeChange();
		if ( ! pending ) return;
		$( '#dwm-confirm-display-type-change-modal' ).removeClass( 'active' );
		setPendingDisplayModeChange( '' );
		setDisplayMode( pending );
		applyDisplayModeChange( pending );
	} );

	$( document ).on( 'click', '.dwm-confirm-display-type-change-close', function() {
		if ( ! getPendingDisplayModeChange() ) return;
		$( '#dwm-confirm-display-type-change-modal' ).removeClass( 'active' );
		setPendingDisplayModeChange( '' );
	} );

	// Limit toggle.
	$( document ).on( 'change', '#dwm-builder-limit-toggle', function() {
		const enabled = $( this ).is( ':checked' );
		if ( enabled ) {
			const normalizedLimit = Math.max( 1, Math.min( 1000, parseInt( $( '#dwm-builder-limit' ).val(), 10 ) || 10 ) );
			state.limit = normalizedLimit;
			$( '#dwm-builder-limit' ).val( normalizedLimit );
		}
		state.noLimit = ! enabled;
		$( '.dwm-builder-limit-input' ).toggle( enabled );
		autoBuildAndValidate();
	} );

	// Limit change.
	$( document ).on( 'change', '#dwm-builder-limit', function() {
		scheduleBuild();
	} );

	// Chart option changes.
	$( document ).on( 'change', '#dwm-builder-chart-label, #dwm-builder-chart-data-list input[type=checkbox], #dwm-builder-chart-legend', function() {
		scheduleBuild();
	} );
	$( document ).on( 'change', '#dwm-builder-chart-data-select, #dwm-builder-chart-theme', function() {
		scheduleBuild();
	} );
	$( document ).on( 'input', '#dwm-builder-chart-title', function() {
		scheduleBuild();
	} );

	// ── Join events ──────────────────────────────────────────────────────────

	$( document ).on( 'click', '#dwm-builder-add-join', function() {
		$( document ).trigger( 'dwm:openEditorJoinModal', [ {
			editIndex:       -1,
			primaryTable:    state.table,
			primaryColumns:  state.availableColumns,
			selectedColumns: state.selectedColumns,
			existingJoins:   state.joins,
		} ] );
	} );

	$( document ).on( 'click', '.dwm-builder-edit-join', function() {
		const idx = parseInt( $( this ).data( 'index' ), 10 );
		$( document ).trigger( 'dwm:openEditorJoinModal', [ {
			editIndex:       idx,
			primaryTable:    state.table,
			primaryColumns:  state.availableColumns,
			selectedColumns: state.selectedColumns,
			existingJoins:   state.joins,
		} ] );
	} );

	$( document ).on( 'click', '.dwm-builder-remove-join', function() {
		const idx = parseInt( $( this ).data( 'index' ), 10 );
		if ( state.joins[ idx ] ) {
			delete state.joinColumns[ state.joins[ idx ].table ];
		}
		state.joins.splice( idx, 1 );
		renderJoins();
		updateChartLabelOptions();
		renderConditions();
		scheduleBuild();
	} );

	$( document ).on( 'dwm:editorJoinSaved', function( _e, joinConfig, editIndex ) {
		const normalized = {
			type:        joinConfig.type,
			table:       joinConfig.table,
			local_col:   joinConfig.local_col,
			foreign_col: joinConfig.foreign_col,
		};

		if ( editIndex >= 0 ) {
			if ( state.joins[ editIndex ] && state.joins[ editIndex ].table !== normalized.table ) {
				delete state.joinColumns[ state.joins[ editIndex ].table ];
			}
			state.joins[ editIndex ] = normalized;
		} else {
			state.joins.push( normalized );
		}

		renderJoins();

		const table = normalized.table;
		if ( ! state.joinColumns[ table ] ) {
			const cached = getCachedColumns( table );
			if ( cached ) {
				state.joinColumns[ table ] = cached;
				updateChartLabelOptions();
				renderConditions();
			} else {
				ajax( 'dwm_get_table_columns', { table }, function( data ) {
					state.joinColumns[ table ] = data.columns || [];
					cacheColumns( table, state.joinColumns[ table ] );
					updateChartLabelOptions();
					renderConditions();
				}, null );
			}
		} else {
			updateChartLabelOptions();
			renderConditions();
		}

		scheduleBuild();
	} );

	// ── Condition events ─────────────────────────────────────────────────────

	$( document ).on( 'click', '#dwm-builder-add-condition', function() {
		$( document ).trigger( 'dwm:builder:open-filter', [ {
			table:            state.table,
			selectedColumns:  state.selectedColumns,
			availableColumns: state.availableColumns,
			joins:            state.joins,
			conditions:       state.conditions,
			orders:           state.orders.filter( o => o.locked ),
			limit:            state.limit,
			noLimit:          state.noLimit,
			editIndex:        undefined,
		} ] );
	} );

	$( document ).on( 'click', '.dwm-builder-edit-filter', function() {
		const idx = parseInt( $( this ).data( 'index' ), 10 );
		$( document ).trigger( 'dwm:builder:open-filter', [ {
			table:            state.table,
			selectedColumns:  state.selectedColumns,
			availableColumns: state.availableColumns,
			joins:            state.joins,
			conditions:       state.conditions,
			orders:           state.orders.filter( o => o.locked ),
			limit:            state.limit,
			noLimit:          state.noLimit,
			editIndex:        idx,
		} ] );
	} );

	$( document ).on( 'click', '.dwm-builder-remove-filter', function() {
		const idx = parseInt( $( this ).data( 'index' ), 10 );
		state.conditions.splice( idx, 1 );
		renderConditions();
		scheduleBuild();
	} );

	$( document ).on( 'dwm:builder:filter-saved', function( e, filterConfig, editIndex ) {
		if ( editIndex >= 0 ) {
			state.conditions[ editIndex ] = filterConfig;
		} else {
			state.conditions.push( filterConfig );
		}
		renderConditions();
		scheduleBuild();
	} );

	// ── Order events ─────────────────────────────────────────────────────────

	$( document ).on( 'click', '#dwm-builder-add-order', function() {
		$( document ).trigger( 'dwm:builder:open-order', [ {
			table:            state.table,
			availableColumns: state.availableColumns,
			joins:            state.joins,
			orders:           state.orders,
			editIndex:        undefined,
		} ] );
	} );

	$( document ).on( 'click', '.dwm-builder-edit-order', function() {
		const idx = parseInt( $( this ).data( 'index' ), 10 );
		$( document ).trigger( 'dwm:builder:open-order', [ {
			table:            state.table,
			availableColumns: state.availableColumns,
			joins:            state.joins,
			orders:           state.orders,
			editIndex:        idx,
		} ] );
	} );

	$( document ).on( 'click', '.dwm-builder-remove-order', function() {
		const idx = parseInt( $( this ).data( 'index' ), 10 );
		state.orders.splice( idx, 1 );
		renderOrders();
		scheduleBuild();
	} );

	$( document ).on( 'dwm:builder:order-saved', function( _e, orderConfig, editIndex ) {
		if ( editIndex !== null && editIndex >= 0 ) {
			state.orders[ editIndex ] = orderConfig;
		} else {
			state.orders.push( orderConfig );
		}
		renderOrders();
		scheduleBuild();
	} );
}

// ── External API ─────────────────────────────────────────────────────────────

export function setBuilderOrders( orders ) {
	state.orders = ( orders || [] ).map( o => ( {
		column:    o.column    || '',
		direction: o.direction || 'DESC',
	} ) );
	renderOrders();
}
