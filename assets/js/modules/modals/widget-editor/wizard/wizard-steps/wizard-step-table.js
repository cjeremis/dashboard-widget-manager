/**
 * Dashboard Widget Manager - Wizard Step: Table Display
 *
 * Handles table selection and column picking for the table display mode.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

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

	// Table selection with change warning
	$( document ).on( 'change', '#dwm-wizard-table-select', function() {
		const newTable = $( this ).val();

		// If columns are already selected, warn the user
		if ( stepState.table && stepState.columns.length > 0 && newTable !== stepState.table ) {
			stepState.pendingTableChange = newTable;
			$( '#dwm-confirm-table-change-modal' ).addClass( 'active' );
			// Revert select to current value while modal is open
			$( this ).val( stepState.table );
			return;
		}

		applyTableChange( newTable );
	});

	// Confirm table change (wizard context only)
	$( document ).on( 'click', '#dwm-confirm-table-change-yes', function() {
		if ( ! stepState.pendingTableChange ) return;
		$( '#dwm-confirm-table-change-modal' ).removeClass( 'active' );
		$( '#dwm-wizard-table-select' ).val( stepState.pendingTableChange );
		applyTableChange( stepState.pendingTableChange );
		stepState.pendingTableChange = '';
	});

	// Cancel table change
	$( document ).on( 'click', '.dwm-confirm-table-change-close', function() {
		$( '#dwm-confirm-table-change-modal' ).removeClass( 'active' );
		stepState.pendingTableChange = '';
	});

	// Column checkbox changes
	$( document ).on( 'change', '.dwm-wizard-col-checkbox', function() {
		stepState.columns = [];
		$( '.dwm-wizard-col-checkbox:checked' ).each( function() {
			stepState.columns.push( $( this ).val() );
		});
		updateStep3NextButton();
	});

	// Select all columns
	$( document ).on( 'click', '.dwm-wizard-select-all', function( e ) {
		e.preventDefault();
		$( '.dwm-wizard-col-checkbox' ).prop( 'checked', true ).trigger( 'change' );
	});

	// Deselect all columns
	$( document ).on( 'click', '.dwm-wizard-deselect-all', function( e ) {
		e.preventDefault();
		$( '.dwm-wizard-col-checkbox' ).prop( 'checked', false ).trigger( 'change' );
	});
}

/**
 * Update step 3 Next button visibility based on table and column selection
 */
function updateStep3NextButton() {
	const $nextButton = $( '.dwm-wizard-next' );

	// Show button only if table is selected AND at least 1 column is checked
	if ( stepState.table && stepState.columns.length > 0 ) {
		$nextButton.show();
	} else {
		$nextButton.hide();
	}
}

/**
 * Export for use in wizard.js when navigating to step
 */
export function initializeNextButton() {
	updateStep3NextButton();
}

export function renderStep( $container ) {
	$( '#dwm-wizard-step3-title' ).text( 'Choose the Primary Table' );
	$( '#dwm-wizard-step3-desc' ).text( 'Choose which database table to use as the primary table for this widget.' );

	$container.html(
		'<div class="dwm-form-group">' +
			'<select id="dwm-wizard-table-select" class="dwm-select dwm-wizard-table-select middle-aligned">' +
				'<option value="">Loading tables\u2026</option>' +
			'</select>' +
		'</div>' +
		'<div id="dwm-wizard-columns-grid" class="dwm-wizard-columns-grid" style="display:none">' +
			'<div class="dwm-wizard-columns-header">' +
				'<label class="dwm-wizard-columns-label">Select Columns *</label>' +
				'<div class="dwm-wizard-columns-actions">' +
					'<a href="#" class="dwm-wizard-select-all">Select All</a>' +
					'<span class="dwm-wizard-columns-separator">|</span>' +
					'<a href="#" class="dwm-wizard-deselect-all">Deselect All</a>' +
				'</div>' +
			'</div>' +
			'<div id="dwm-wizard-columns-list" class="dwm-wizard-columns-list"></div>' +
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
	stepState.table            = table              || '';
	stepState.columns          = columns            || [];
	stepState.availableColumns = availableColumns   || [];
}

export function restoreColumnsDisplay() {
	if ( stepState.table && stepState.availableColumns.length > 0 ) {
		// Show the columns grid and populate with existing data
		$( '#dwm-wizard-columns-grid' ).show();
		populateColumnsCheckboxes( stepState.table, stepState.availableColumns );
	} else if ( stepState.table ) {
		// Table selected but no columns loaded - load them
		loadColumns( stepState.table );
	}
}

function applyTableChange( table ) {
	stepState.table = table;
	stepState.columns = [];
	stepState.availableColumns = [];

	if ( table ) {
		// Ensure grid is visible before loading
		$( '#dwm-wizard-columns-grid' ).show();
		loadColumns( table );
	} else {
		// Clear and hide columns grid when no table is selected
		$( '#dwm-wizard-columns-list' ).html( '' );
		$( '#dwm-wizard-columns-grid' ).hide();
	}

	// Update Next button visibility
	updateStep3NextButton();
}

/**
 * Get cached tables if available and not expired
 */
function getCachedTables() {
	try {
		const cached = sessionStorage.getItem( CACHE_KEY_TABLES );
		if ( ! cached ) {
			return null;
		}

		const data = JSON.parse( cached );
		const now = Date.now();

		// Check if cache is expired
		if ( now - data.timestamp > CACHE_DURATION ) {
			sessionStorage.removeItem( CACHE_KEY_TABLES );
			return null;
		}

		return data.tables;
	} catch ( e ) {
		// If there's any error reading cache, just return null
		return null;
	}
}

/**
 * Cache the tables list with timestamp
 */
function cacheTables( tables ) {
	try {
		const data = {
			tables: tables,
			timestamp: Date.now()
		};
		sessionStorage.setItem( CACHE_KEY_TABLES, JSON.stringify( data ) );
	} catch ( e ) {
		// Silently fail if storage is not available
	}
}

/**
 * Get cached columns for a specific table if available and not expired
 */
function getCachedColumns( table ) {
	try {
		const cacheKey = CACHE_KEY_COLUMNS_PREFIX + table;
		const cached = sessionStorage.getItem( cacheKey );
		if ( ! cached ) {
			return null;
		}

		const data = JSON.parse( cached );
		const now = Date.now();

		// Check if cache is expired
		if ( now - data.timestamp > CACHE_DURATION ) {
			sessionStorage.removeItem( cacheKey );
			return null;
		}

		return data.columns;
	} catch ( e ) {
		// If there's any error reading cache, just return null
		return null;
	}
}

/**
 * Cache the columns list for a specific table with timestamp
 */
function cacheColumns( table, columns ) {
	try {
		const cacheKey = CACHE_KEY_COLUMNS_PREFIX + table;
		const data = {
			columns: columns,
			timestamp: Date.now()
		};
		sessionStorage.setItem( cacheKey, JSON.stringify( data ) );
	} catch ( e ) {
		// Silently fail if storage is not available
	}
}

function loadTables() {
	// Check cache first
	const cachedTables = getCachedTables();
	if ( cachedTables ) {
		populateTablesDropdown( cachedTables );
		return;
	}

	// No cache or expired - fetch from server
	ajax(
		'dwm_get_tables',
		{},
		function( data ) {
			const tables = data.tables || [];

			// Cache the results
			cacheTables( tables );

			// Populate dropdowns
			populateTablesDropdown( tables );
		},
		function() {
			$( '#dwm-wizard-table-select' ).html( '<option value="">Failed to load tables</option>' );
		}
	);
}

/**
 * Populate both wizard and builder table dropdowns
 */
function populateTablesDropdown( tables ) {
	// Populate wizard dropdown
	let html = '<option value="">\u2014 select a table \u2014</option>';
	tables.forEach( function( t ) {
		html += '<option value="' + t + '">' + t + '</option>';
	});
	$( '#dwm-wizard-table-select' ).html( html );

	// Also sync tables into the main builder dropdown
	const $builderSelect = $( '#dwm-builder-table' );
	let builderHtml = '<option value="">\u2014 Select a table \u2014</option>';
	tables.forEach( function( t ) {
		builderHtml += '<option value="' + t + '">' + t + '</option>';
	});
	$builderSelect.html( builderHtml );

	// Restore previous selection if any
	if ( stepState.table ) {
		$( '#dwm-wizard-table-select' ).val( stepState.table );
		restoreColumnsDisplay();
	}
}

function loadColumns( table ) {
	const $list = $( '#dwm-wizard-columns-list' );
	$list.addClass( 'is-loading' ).html( '<span class="dwm-loading"></span> Loading columns\u2026' );
	$( '#dwm-wizard-columns-grid' ).show();

	// Check cache first
	const cachedColumns = getCachedColumns( table );
	if ( cachedColumns ) {
		populateColumnsCheckboxes( table, cachedColumns );
		return;
	}

	// No cache or expired - fetch from server
	// Disable dropdown during AJAX call
	$( '#dwm-wizard-table-select' ).prop( 'disabled', true );

	ajax(
		'dwm_get_table_columns',
		{ table: table },
		function( data ) {
			const cols = data.columns || [];

			// Cache the results
			cacheColumns( table, cols );

			// Populate the checkboxes
			populateColumnsCheckboxes( table, cols );
		},
		function() {
			$list.removeClass( 'is-loading' ).html( '<p>Failed to load columns.</p>' );
			$( '#dwm-wizard-columns-grid' ).show();
			// Re-enable dropdown on error
			$( '#dwm-wizard-table-select' ).prop( 'disabled', false );
		}
	);
}

/**
 * Populate the wizard columns checkboxes and sync to builder
 */
function populateColumnsCheckboxes( table, cols ) {
	const $list = $( '#dwm-wizard-columns-list' );
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
				'<input type="checkbox" class="dwm-wizard-col-checkbox" value="' + col.name + '"' + checked + '>' +
				'<span class="dwm-wizard-col-name">' + col.name + isPK + '</span>' +
				'<span class="dwm-wizard-col-type">' + col.type + '</span>' +
			'</label>';
	});

	$list.removeClass( 'is-loading' ).html( html );

	// Sync columns into the main builder in the background
	syncBuilderColumns( table, cols );

	// Re-enable dropdown after rendering
	$( '#dwm-wizard-table-select' ).prop( 'disabled', false );
}

function syncBuilderColumns( table, cols ) {
	// Set the builder's table dropdown silently (no trigger to avoid re-fetching)
	$( '#dwm-builder-table' ).val( table );

	// Render column checkboxes in the builder
	const $list = $( '#dwm-builder-columns-list' ).empty();
	cols.forEach( function( col ) {
		const id = 'dwm-col-' + col.name;
		const badge = col.key === 'PRI' ? ' <span class="dwm-col-badge dwm-col-badge-pk">PK</span>' : '';
		$list.append(
			'<label class="dwm-col-checkbox" for="' + id + '">' +
				'<input type="checkbox" id="' + id + '" value="' + col.name + '">' +
				'<span class="dwm-col-name">' + col.name + '</span>' +
				'<span class="dwm-col-type">' + col.type + '</span>' +
				badge +
			'</label>'
		);
	});

	// Show builder sections so they're ready
	$( '#dwm-builder-columns-section' ).show();
	$( '#dwm-builder-joins-section' ).show();
	$( '#dwm-builder-conditions-section' ).show();
	$( '#dwm-builder-order-section' ).show();
	$( '#dwm-builder-apply-section' ).show();
}
