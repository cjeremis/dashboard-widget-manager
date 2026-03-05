/**
 * Dashboard Widget Manager - Wizard Step: Button Display
 *
 * Handles table selection and column picking for the button display mode.
 * Reuses the same data-selection UX as the table display mode.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { ajax } from '../../../../partials/ajax.js';

const $ = jQuery;

const CACHE_KEY_TABLES = 'dwm_tables_cache';
const CACHE_KEY_COLUMNS_PREFIX = 'dwm_columns_cache_';
const CACHE_DURATION = 60 * 60 * 1000;

let stepState = {
	table: '',
	columns: [],
	availableColumns: [],
	pendingTableChange: ''
};
let isInitialized = false;

export function init() {
	if ( isInitialized ) {
		return;
	}
	isInitialized = true;

	$( document ).on( 'change', '#dwm-wizard-button-table-select', function() {
		const newTable = $( this ).val();
		if ( stepState.table && stepState.columns.length > 0 && newTable !== stepState.table ) {
			stepState.pendingTableChange = newTable;
			$( '#dwm-confirm-table-change-modal' ).addClass( 'active' );
			$( this ).val( stepState.table );
			return;
		}
		applyTableChange( newTable );
	});

	$( document ).on( 'click', '#dwm-confirm-table-change-yes', function() {
		if ( ! stepState.pendingTableChange ) return;
		const $sel = $( '#dwm-wizard-button-table-select' );
		if ( ! $sel.length ) return;
		$( '#dwm-confirm-table-change-modal' ).removeClass( 'active' );
		$sel.val( stepState.pendingTableChange );
		applyTableChange( stepState.pendingTableChange );
		stepState.pendingTableChange = '';
	});

	$( document ).on( 'change', '.dwm-wizard-button-col-checkbox', function() {
		stepState.columns = [];
		$( '.dwm-wizard-button-col-checkbox:checked' ).each( function() {
			stepState.columns.push( $( this ).val() );
		});
		updateNextButton();
	});

	$( document ).on( 'click', '.dwm-wizard-button-select-all', function( e ) {
		e.preventDefault();
		$( '.dwm-wizard-button-col-checkbox' ).prop( 'checked', true ).trigger( 'change' );
	});

	$( document ).on( 'click', '.dwm-wizard-button-deselect-all', function( e ) {
		e.preventDefault();
		$( '.dwm-wizard-button-col-checkbox' ).prop( 'checked', false ).trigger( 'change' );
	});
}

function updateNextButton() {
	const $nextButton = $( '.dwm-wizard-next' );
	if ( stepState.table && stepState.columns.length > 0 ) {
		$nextButton.show();
	} else {
		$nextButton.hide();
	}
}

export function initializeNextButton() {
	updateNextButton();
}

export function renderStep( $container ) {
	$( '#dwm-wizard-step3-title' ).text( 'Choose the Primary Table' );
	$( '#dwm-wizard-step3-desc' ).text( 'Choose which database table to use as the data source for your button widget.' );

	$container.html(
		'<div class="dwm-form-group">' +
			'<select id="dwm-wizard-button-table-select" class="dwm-select dwm-wizard-table-select middle-aligned">' +
				'<option value="">Loading tables\u2026</option>' +
			'</select>' +
		'</div>' +
		'<div id="dwm-wizard-button-columns-grid" class="dwm-wizard-columns-grid" style="display:none">' +
			'<div class="dwm-wizard-columns-header">' +
				'<label class="dwm-wizard-columns-label">Select Columns *</label>' +
				'<div class="dwm-wizard-columns-actions">' +
					'<a href="#" class="dwm-wizard-button-select-all">Select All</a>' +
					'<span class="dwm-wizard-columns-separator">|</span>' +
					'<a href="#" class="dwm-wizard-button-deselect-all">Deselect All</a>' +
				'</div>' +
			'</div>' +
			'<div id="dwm-wizard-button-columns-list" class="dwm-wizard-columns-list"></div>' +
		'</div>'
	);

	loadTables();
}

export function validateStep() {
	return !! ( stepState.table && stepState.columns.length > 0 );
}

export function collectData() {
	return {
		table: stepState.table,
		columns: [ ...stepState.columns ],
		availableColumns: [ ...stepState.availableColumns ]
	};
}

export function clearStep() {
	stepState.table = '';
	stepState.columns = [];
	stepState.availableColumns = [];
	stepState.pendingTableChange = '';
}

export function restoreStepData( table, columns, availableColumns ) {
	stepState.table            = table            || '';
	stepState.columns          = columns          || [];
	stepState.availableColumns = availableColumns || [];
}

export function restoreColumnsDisplay() {
	if ( stepState.table && stepState.availableColumns.length > 0 ) {
		$( '#dwm-wizard-button-columns-grid' ).show();
		populateColumnsCheckboxes( stepState.table, stepState.availableColumns );
	} else if ( stepState.table ) {
		loadColumns( stepState.table );
	}
}

function applyTableChange( table ) {
	stepState.table = table;
	stepState.columns = [];
	stepState.availableColumns = [];

	if ( table ) {
		$( '#dwm-wizard-button-columns-grid' ).show();
		loadColumns( table );
	} else {
		$( '#dwm-wizard-button-columns-list' ).html( '' );
		$( '#dwm-wizard-button-columns-grid' ).hide();
	}

	updateNextButton();
}

function getCachedTables() {
	try {
		const cached = sessionStorage.getItem( CACHE_KEY_TABLES );
		if ( ! cached ) return null;
		const data = JSON.parse( cached );
		if ( Date.now() - data.timestamp > CACHE_DURATION ) {
			sessionStorage.removeItem( CACHE_KEY_TABLES );
			return null;
		}
		return data.tables;
	} catch ( e ) {
		return null;
	}
}

function loadTables() {
	const cachedTables = getCachedTables();
	if ( cachedTables ) {
		populateTablesDropdown( cachedTables );
		return;
	}

	ajax(
		'dwm_get_tables',
		{},
		function( data ) {
			const tables = data.tables || [];
			try {
				sessionStorage.setItem( CACHE_KEY_TABLES, JSON.stringify( { tables: tables, timestamp: Date.now() } ) );
			} catch ( e ) { /* ignore */ }
			populateTablesDropdown( tables );
		},
		function() {
			$( '#dwm-wizard-button-table-select' ).html( '<option value="">Failed to load tables</option>' );
		}
	);
}

function populateTablesDropdown( tables ) {
	let html = '<option value="">\u2014 select a table \u2014</option>';
	tables.forEach( function( t ) {
		html += '<option value="' + t + '">' + t + '</option>';
	});
	$( '#dwm-wizard-button-table-select' ).html( html );

	if ( stepState.table ) {
		$( '#dwm-wizard-button-table-select' ).val( stepState.table );
		restoreColumnsDisplay();
	}
}

function loadColumns( table ) {
	const $list = $( '#dwm-wizard-button-columns-list' );
	$list.addClass( 'is-loading' ).html( '<span class="dwm-loading"></span> Loading columns\u2026' );
	$( '#dwm-wizard-button-columns-grid' ).show();

	try {
		const cacheKey = CACHE_KEY_COLUMNS_PREFIX + table;
		const cached = sessionStorage.getItem( cacheKey );
		if ( cached ) {
			const data = JSON.parse( cached );
			if ( Date.now() - data.timestamp <= CACHE_DURATION ) {
				populateColumnsCheckboxes( table, data.columns );
				return;
			}
		}
	} catch ( e ) { /* ignore */ }

	$( '#dwm-wizard-button-table-select' ).prop( 'disabled', true );

	ajax(
		'dwm_get_table_columns',
		{ table: table },
		function( data ) {
			const cols = data.columns || [];
			try {
				sessionStorage.setItem( CACHE_KEY_COLUMNS_PREFIX + table, JSON.stringify( { columns: cols, timestamp: Date.now() } ) );
			} catch ( e ) { /* ignore */ }
			populateColumnsCheckboxes( table, cols );
			$( '#dwm-wizard-button-table-select' ).prop( 'disabled', false );
		},
		function() {
			$list.removeClass( 'is-loading' ).html( '<p>Failed to load columns.</p>' );
			$( '#dwm-wizard-button-table-select' ).prop( 'disabled', false );
		}
	);
}

function populateColumnsCheckboxes( table, cols ) {
	const $list = $( '#dwm-wizard-button-columns-list' );
	stepState.availableColumns = cols;

	if ( cols.length === 0 ) {
		$list.html( '<p>No columns found.</p>' );
		return;
	}

	let html = '';
	cols.forEach( function( col ) {
		const checked = stepState.columns.indexOf( col.name ) !== -1 ? ' checked' : '';
		const isPK = col.key === 'PRI' ? ' <span class="dwm-col-badge dwm-col-badge-pk">PK</span>' : '';
		html +=
			'<label class="dwm-wizard-col-item">' +
				'<input type="checkbox" class="dwm-wizard-button-col-checkbox" value="' + col.name + '"' + checked + '>' +
				'<span class="dwm-wizard-col-name">' + col.name + isPK + '</span>' +
				'<span class="dwm-wizard-col-type">' + col.type + '</span>' +
			'</label>';
	});

	$list.removeClass( 'is-loading' ).html( html );

	const $builderSelect = $( '#dwm-builder-table' );
	$builderSelect.val( table );
	const $builderList = $( '#dwm-builder-columns-list' ).empty();
	cols.forEach( function( col ) {
		const id = 'dwm-col-' + col.name;
		const badge = col.key === 'PRI' ? ' <span class="dwm-col-badge dwm-col-badge-pk">PK</span>' : '';
		$builderList.append(
			'<label class="dwm-col-checkbox" for="' + id + '">' +
				'<input type="checkbox" id="' + id + '" value="' + col.name + '">' +
				'<span class="dwm-col-name">' + col.name + '</span>' +
				'<span class="dwm-col-type">' + col.type + '</span>' +
				badge +
			'</label>'
		);
	});

	$( '#dwm-builder-columns-section' ).show();
	$( '#dwm-builder-joins-section' ).show();
	$( '#dwm-builder-conditions-section' ).show();
	$( '#dwm-builder-order-section' ).show();
	$( '#dwm-builder-apply-section' ).show();

	$( '#dwm-wizard-button-table-select' ).prop( 'disabled', false );
}
