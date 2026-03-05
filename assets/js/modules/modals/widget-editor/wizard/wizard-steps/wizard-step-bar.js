/**
 * Dashboard Widget Manager - Wizard Step: Bar Chart Display
 *
 * Handles primary table and column selection for bar chart mode.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { ajax } from '../../../../partials/ajax.js';

const $ = jQuery;

// Cache configuration
const CACHE_KEY_TABLES = 'dwm_tables_cache';
const CACHE_KEY_COLUMNS_PREFIX = 'dwm_columns_cache_';
const CACHE_DURATION = 60 * 60 * 1000; // 1 hour

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

	// Table selection with change warning.
	$( document ).on( 'change', '#dwm-wizard-bar-table-select', function() {
		const newTable = $( this ).val();

		if ( stepState.table && stepState.columns.length > 0 && newTable !== stepState.table ) {
			stepState.pendingTableChange = newTable;
			$( '#dwm-confirm-table-change-modal' ).addClass( 'active' );
			$( this ).val( stepState.table );
			return;
		}

		applyTableChange( newTable );
	} );

	// Confirm table change (wizard context only).
	$( document ).on( 'click', '#dwm-confirm-table-change-yes', function() {
		if ( ! stepState.pendingTableChange ) {
			return;
		}
		$( '#dwm-confirm-table-change-modal' ).removeClass( 'active' );
		$( '#dwm-wizard-bar-table-select' ).val( stepState.pendingTableChange );
		applyTableChange( stepState.pendingTableChange );
		stepState.pendingTableChange = '';
	} );

	$( document ).on( 'click', '.dwm-confirm-table-change-close', function() {
		$( '#dwm-confirm-table-change-modal' ).removeClass( 'active' );
		stepState.pendingTableChange = '';
	} );

	$( document ).on( 'change', '.dwm-wizard-bar-col-checkbox', function() {
		stepState.columns = [];
		$( '.dwm-wizard-bar-col-checkbox:checked' ).each( function() {
			stepState.columns.push( $( this ).val() );
		} );
		updateStep3NextButton();
	} );

	$( document ).on( 'click', '.dwm-wizard-bar-select-all', function( e ) {
		e.preventDefault();
		$( '.dwm-wizard-bar-col-checkbox' ).prop( 'checked', true ).trigger( 'change' );
	} );

	$( document ).on( 'click', '.dwm-wizard-bar-deselect-all', function( e ) {
		e.preventDefault();
		$( '.dwm-wizard-bar-col-checkbox' ).prop( 'checked', false ).trigger( 'change' );
	} );
}

function updateStep3NextButton() {
	const $nextButton = $( '.dwm-wizard-next' );
	if ( stepState.table && stepState.columns.length > 0 ) {
		$nextButton.show();
	} else {
		$nextButton.hide();
	}
}

export function initializeNextButton() {
	updateStep3NextButton();
}

export function renderStep( $container ) {
	$( '#dwm-wizard-step3-title' ).text( 'Choose the Primary Table' );
	$( '#dwm-wizard-step3-desc' ).text( 'Choose the table and columns to build your bar chart dataset.' );

	$container.html(
		'<div class="dwm-form-group">' +
			'<select id="dwm-wizard-bar-table-select" class="dwm-select dwm-wizard-table-select middle-aligned">' +
				'<option value="">Loading tables…</option>' +
			'</select>' +
		'</div>' +
		'<div id="dwm-wizard-bar-columns-grid" class="dwm-wizard-columns-grid" style="display:none">' +
			'<div class="dwm-wizard-columns-header">' +
				'<label class="dwm-wizard-columns-label">Select Columns *</label>' +
				'<div class="dwm-wizard-columns-actions">' +
					'<a href="#" class="dwm-wizard-bar-select-all">Select All</a>' +
					'<span class="dwm-wizard-columns-separator">|</span>' +
					'<a href="#" class="dwm-wizard-bar-deselect-all">Deselect All</a>' +
				'</div>' +
			'</div>' +
			'<div id="dwm-wizard-bar-columns-list" class="dwm-wizard-columns-list"></div>' +
		'</div>'
	);

	loadTables();
}

export function validateStep() {
	if ( ! stepState.table ) {
		return false;
	}

	if ( stepState.columns.length === 0 ) {
		return false;
	}

	return true;
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
	stepState.table = table || '';
	stepState.columns = columns || [];
	stepState.availableColumns = availableColumns || [];
}

export function restoreColumnsDisplay() {
	if ( stepState.table && stepState.availableColumns.length > 0 ) {
		$( '#dwm-wizard-bar-columns-grid' ).show();
		populateColumnsCheckboxes( stepState.availableColumns );
	} else if ( stepState.table ) {
		loadColumns( stepState.table );
	}
}

function applyTableChange( table ) {
	stepState.table = table;
	stepState.columns = [];
	stepState.availableColumns = [];

	if ( table ) {
		$( '#dwm-wizard-bar-columns-grid' ).show();
		loadColumns( table );
	} else {
		$( '#dwm-wizard-bar-columns-list' ).html( '' );
		$( '#dwm-wizard-bar-columns-grid' ).hide();
	}

	updateStep3NextButton();
}

function getCachedTables() {
	try {
		const cached = sessionStorage.getItem( CACHE_KEY_TABLES );
		if ( ! cached ) {
			return null;
		}

		const data = JSON.parse( cached );
		const now = Date.now();
		if ( now - data.timestamp > CACHE_DURATION ) {
			sessionStorage.removeItem( CACHE_KEY_TABLES );
			return null;
		}

		return data.tables;
	} catch ( e ) {
		return null;
	}
}

function cacheTables( tables ) {
	try {
		sessionStorage.setItem( CACHE_KEY_TABLES, JSON.stringify( {
			tables,
			timestamp: Date.now()
		} ) );
	} catch ( e ) {
		// Ignore cache failures.
	}
}

function getCachedColumns( table ) {
	try {
		const cacheKey = CACHE_KEY_COLUMNS_PREFIX + table;
		const cached = sessionStorage.getItem( cacheKey );
		if ( ! cached ) {
			return null;
		}

		const data = JSON.parse( cached );
		const now = Date.now();
		if ( now - data.timestamp > CACHE_DURATION ) {
			sessionStorage.removeItem( cacheKey );
			return null;
		}

		return data.columns;
	} catch ( e ) {
		return null;
	}
}

function cacheColumns( table, columns ) {
	try {
		sessionStorage.setItem(
			CACHE_KEY_COLUMNS_PREFIX + table,
			JSON.stringify( {
				columns,
				timestamp: Date.now()
			} )
		);
	} catch ( e ) {
		// Ignore cache failures.
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
			cacheTables( tables );
			populateTablesDropdown( tables );
		},
		function() {
			$( '#dwm-wizard-bar-table-select' ).html( '<option value="">Failed to load tables</option>' );
		}
	);
}

function populateTablesDropdown( tables ) {
	let html = '<option value="">— select a table —</option>';
	tables.forEach( function( tableName ) {
		html += '<option value="' + tableName + '">' + tableName + '</option>';
	} );

	$( '#dwm-wizard-bar-table-select' ).html( html );

	// Keep builder dropdown synced with wizard table list.
	const $builderSelect = $( '#dwm-builder-table' );
	let builderHtml = '<option value="">— Select a table —</option>';
	tables.forEach( function( tableName ) {
		builderHtml += '<option value="' + tableName + '">' + tableName + '</option>';
	} );
	$builderSelect.html( builderHtml );

	if ( stepState.table ) {
		$( '#dwm-wizard-bar-table-select' ).val( stepState.table );
		restoreColumnsDisplay();
	}
}

function loadColumns( table ) {
	const $list = $( '#dwm-wizard-bar-columns-list' );
	$list.addClass( 'is-loading' ).html( '<span class="dwm-loading"></span> Loading columns…' );

	const cachedColumns = getCachedColumns( table );
	if ( cachedColumns ) {
		stepState.availableColumns = cachedColumns;
		populateColumnsCheckboxes( cachedColumns );
		return;
	}

	ajax(
		'dwm_get_table_columns',
		{ table: table },
		function( data ) {
			const columns = data.columns || [];
			stepState.availableColumns = columns;
			cacheColumns( table, columns );
			populateColumnsCheckboxes( columns );
		},
		function() {
			$list.removeClass( 'is-loading' ).html( '<p style="color:#d63638">Failed to load columns.</p>' );
		}
	);
}

function populateColumnsCheckboxes( columns ) {
	const $list = $( '#dwm-wizard-bar-columns-list' );
	$list.removeClass( 'is-loading' ).empty();

	if ( ! columns || columns.length === 0 ) {
		$list.html( '<p>No columns found for this table.</p>' );
		return;
	}

	columns.forEach( function( col ) {
		const colName = col.name || col;
		const colType = col.type || '';
		const isChecked = stepState.columns.indexOf( colName ) !== -1;
		const isPk = col.key === 'PRI';

		const checkboxId = 'dwm-wizard-bar-col-' + colName.replace( /[^a-zA-Z0-9_-]/g, '_' );
		const checkboxHtml =
			'<label class="dwm-wizard-col-item" for="' + checkboxId + '">' +
				'<input type="checkbox" id="' + checkboxId + '" class="dwm-wizard-bar-col-checkbox" value="' + colName + '"' + ( isChecked ? ' checked' : '' ) + '>' +
				'<span class="dwm-wizard-col-name">' + colName + ( isPk ? ' <span class="dwm-col-badge-pk">PK</span>' : '' ) + '</span>' +
				'<span class="dwm-wizard-col-type">' + colType + '</span>' +
			'</label>';

		$list.append( checkboxHtml );
	} );

	updateStep3NextButton();
}
