/**
 * Dashboard Widget Manager - Wizard Filter Modal Module
 *
 * Filter configuration modal logic for the creation wizard and widget editor.
 * Handles opening/closing the filter modal, column/operator selection,
 * value field rendering, validation, query preview, and saving filter configs.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { wizardState } from './wizard-state.js';

import {
	escAttr,
	getCachedColumnsForTable
} from './wizard-utils.js';

import { ajax } from '../../../partials/ajax.js';
import { ensureSearchableSelect, refreshSearchableSelect } from '../../../partials/searchable-select.js';
import * as columnValidator from '../../../utilities/column-validator.js';
import { buildNoResultsPreviewHtml } from '../output-preview-empty-state.js';

const $ = jQuery;

// ── Event Bindings ───────────────────────────────────────────────────────────

/**
 * Initialise all filter-modal-related event bindings.
 * Called once from initWizard() in wizard.js.
 */
export function initFilterModalEvents() {

	// Wizard filter: add row
	$( document ).on( 'click', '#dwm-wizard-add-condition', function() {
		openFilterConfigModal();
	});

	// Wizard filter: edit row
	$( document ).on( 'click', '.dwm-wizard-edit-filter', function() {
		const idx = parseInt( $( this ).data( 'index' ), 10 );
		openFilterConfigModal( idx );
	});

	// Wizard filter: remove row
	$( document ).on( 'click', '.dwm-wizard-remove-filter', function() {
		const idx = parseInt( $( this ).data( 'index' ), 10 );
		wizardState.data.conditions.splice( idx, 1 );
		renderWizardConditions();
		updateStep5NextButton();
	});

	// Builder bridge: open filter modal from the from-scratch widget editor
	$( document ).on( 'dwm:builder:open-filter', function( _e, ctx ) {
		wizardState.data.stepConfig = {
			table:            ctx.table            || '',
			columns:          ctx.selectedColumns  || [],
			availableColumns: ctx.availableColumns || [],
		};
		wizardState.data.joins      = ctx.joins      || [];
		wizardState.data.conditions = ctx.conditions || [];
		wizardState.data.orders     = ctx.orders     || [];
		wizardState.data.limit      = ctx.limit !== undefined ? ctx.limit : 10;
		wizardState.data.noLimit    = ctx.noLimit    !== undefined ? ctx.noLimit : true;
		wizardState.builderFilterMode = true;
		openFilterConfigModal( ctx.editIndex );
	} );

	// Filter config modal: cancel
	$( document ).on( 'click', '.dwm-filter-config-cancel', function() {
		wizardState.builderFilterMode = false;
		closeFilterConfigModal();
	} );

	// Filter config modal: save
	$( document ).on( 'click', '#dwm-filter-config-save', function() {
		saveFilterConfig();
	});

	// Filter config modal: preview tab switching
	$( document ).on( 'click', '.dwm-filter-preview-tab', function() {
		if ( $( this ).prop( 'disabled' ) ) {
			return;
		}
		const tab = $( this ).data( 'tab' );
		$( '.dwm-filter-preview-tab' ).removeClass( 'active' );
		$( this ).addClass( 'active' );
		$( '.dwm-filter-preview-pane' ).removeClass( 'active' );
		$( '#dwm-filter-preview-' + tab + '-pane' ).addClass( 'active' );
		if ( tab === 'output' ) {
			renderFilterOutputTable( wizardState.filterConfigState.validationResults );
		}
	});

	// Filter config modal: column change
	$( document ).on( 'change', '#dwm-filter-config-column', function() {
		onFilterColumnChange();
	});

	// Filter config modal: operator change
	$( document ).on( 'change', '#dwm-filter-config-operator', function() {
		onFilterOperatorChange();
	});
}

// ── Modal Open / Close ───────────────────────────────────────────────────────

/**
 * Open filter configuration modal
 */
export function openFilterConfigModal( editIndex ) {
	// Store the edit index in state
	wizardState.filterConfigEditIndex = typeof editIndex === 'number' ? editIndex : -1;

	// Reset filter config state
	wizardState.filterConfigState = {
		selectedColumn: null,
		selectedColumnType: null,
		selectedTable: null,
		validationResults: []
	};

	// Reset form
	$( '#dwm-filter-config-column' ).val( '' ).prop( 'disabled', false );
	$( '#dwm-filter-config-operator' ).val( '' ).prop( 'disabled', true );
	$( '#dwm-filter-value-error' ).hide().text( '' );
	$( '#dwm-filter-duplicate-error' ).hide().text( '' );
	$( '#dwm-filter-value-group' ).show();

	// Reset validation state
	$( '#dwm-filter-validation-status' ).hide();
	$( '#dwm-filter-config-save' ).prop( 'disabled', true );
	$( '.dwm-filter-preview-tab[data-tab="output"]' ).prop( 'disabled', true );

	// Switch to query tab
	$( '.dwm-filter-preview-tab' ).removeClass( 'active' );
	$( '.dwm-filter-preview-tab[data-tab="query"]' ).addClass( 'active' );
	$( '.dwm-filter-preview-pane' ).removeClass( 'active' );
	$( '#dwm-filter-preview-query-pane' ).addClass( 'active' );

	// Preview will be populated after column options are set below

	// Populate column dropdown with optgroups by table
	let columnOptions = '<option value="">— select column —</option>';

	// Get primary table and columns
	const primaryTable = ( wizardState.data.stepConfig && wizardState.data.stepConfig.table ) || '';
	const primaryColumns = ( wizardState.data.stepConfig && wizardState.data.stepConfig.availableColumns ) || [];

	if ( primaryTable && primaryColumns.length > 0 ) {
		columnOptions += '<optgroup label="' + escAttr( primaryTable ) + '">';
		primaryColumns.forEach( function( col ) {
			const colName = col.name || col;
			const colType = col.type || '';
			const fullName = primaryTable + '.' + colName;
			columnOptions += '<option value="' + escAttr( fullName ) + '" data-type="' + escAttr( colType ) + '" data-table="' + escAttr( primaryTable ) + '">' + escAttr( colName ) + '</option>';
		});
		columnOptions += '</optgroup>';
	}

	// Get joined tables and their columns
	const joins = wizardState.data.joins || [];
	joins.forEach( function( join ) {
		const joinColumns = getCachedColumnsForTable( join.table ) || [];
		if ( joinColumns.length > 0 ) {
			columnOptions += '<optgroup label="' + escAttr( join.table ) + '">';
			joinColumns.forEach( function( col ) {
				const colName = col.name || col;
				const colType = col.type || '';
				const fullName = join.table + '.' + colName;
				columnOptions += '<option value="' + escAttr( fullName ) + '" data-type="' + escAttr( colType ) + '" data-table="' + escAttr( join.table ) + '">' + escAttr( colName ) + '</option>';
			});
			columnOptions += '</optgroup>';
		}
	});

	$( '#dwm-filter-config-column' ).html( columnOptions );
	ensureSearchableSelect( '#dwm-filter-config-column', 'Search columns...' );
	refreshSearchableSelect( '#dwm-filter-config-column' );

	// Update title
	if ( wizardState.filterConfigEditIndex >= 0 ) {
		$( '#dwm-filter-config-title' ).text( 'Edit Filter' );
	} else {
		$( '#dwm-filter-config-title' ).text( 'Configure Filter' );
	}

	// If editing, populate fields and trigger cascading enables
	if ( wizardState.filterConfigEditIndex >= 0 ) {
		const filter = wizardState.data.conditions[ wizardState.filterConfigEditIndex ];
		$( '#dwm-filter-config-column' ).val( filter.column );
		onFilterColumnChange(); // This will enable operator and set up value field
		$( '#dwm-filter-config-operator' ).val( filter.operator );
		onFilterOperatorChange(); // This will enable value field

		// Set the value after the field is generated
		setTimeout( function() {
			const $valueField = $( '#dwm-filter-config-value' );
			if ( $valueField.is( 'select' ) ) {
				$valueField.val( filter.value );
			} else {
				$valueField.val( filter.value );
			}
			updateFilterQueryPreview();
		}, 50 );
	} else {
		// New filter - set up empty disabled value field and show base query immediately
		renderFilterValueField( 'text', null );
		updateFilterQueryPreview();
	}

	// Show modal
	$( '#dwm-filter-config-modal' ).addClass( 'active' );
}

/**
 * Close filter configuration modal
 */
export function closeFilterConfigModal() {
	$( '#dwm-filter-config-modal' ).removeClass( 'active' );
	wizardState.filterConfigEditIndex = -1;
	wizardState.filterConfigState = {
		selectedColumn: null,
		selectedColumnType: null,
		selectedTable: null
	};
}

// ── Column / Operator / Value Handlers ───────────────────────────────────────

/**
 * Handle column selection change - enables operator and stores column info
 */
export function onFilterColumnChange() {
	const $columnSelect = $( '#dwm-filter-config-column' );
	const $operatorSelect = $( '#dwm-filter-config-operator' );
	const selectedColumn = $columnSelect.val();

	// Clear errors
	$( '#dwm-filter-value-error' ).hide().text( '' );
	$( '#dwm-filter-duplicate-error' ).hide().text( '' );

	if ( ! selectedColumn ) {
		// No column selected - disable operator and value
		$operatorSelect.val( '' ).prop( 'disabled', true );
		renderFilterValueField( 'text', null );
		wizardState.filterConfigState = {
			selectedColumn: null,
			selectedColumnType: null,
			selectedTable: null
		};
		updateFilterQueryPreview();
		return;
	}

	// Get column type from selected option's data attribute
	const $selectedOption = $columnSelect.find( 'option:selected' );
	const columnType = $selectedOption.data( 'type' ) || '';
	const columnTable = $selectedOption.data( 'table' ) || '';

	// Store in state
	wizardState.filterConfigState.selectedColumn = selectedColumn;
	wizardState.filterConfigState.selectedColumnType = columnType;
	wizardState.filterConfigState.selectedTable = columnTable;

	// Enable operator dropdown
	$operatorSelect.prop( 'disabled', false );

	// Reset operator and value
	$operatorSelect.val( '' );
	renderFilterValueField( 'text', null );

	updateFilterQueryPreview();
}

/**
 * Handle operator selection change - enables and customizes value field
 */
export function onFilterOperatorChange() {
	const operator = $( '#dwm-filter-config-operator' ).val();
	const columnType = wizardState.filterConfigState.selectedColumnType || '';

	// Clear errors
	$( '#dwm-filter-value-error' ).hide().text( '' );
	$( '#dwm-filter-duplicate-error' ).hide().text( '' );

	if ( ! operator ) {
		// No operator selected - disable value field
		renderFilterValueField( 'text', null );
		updateFilterQueryPreview();
		return;
	}

	// IS NULL and IS NOT NULL don't need a value field
	const isNullOp = operator === 'IS NULL' || operator === 'IS NOT NULL';
	if ( isNullOp ) {
		$( '#dwm-filter-value-group' ).hide();
		updateFilterQueryPreview();
		return;
	}

	// Show value group
	$( '#dwm-filter-value-group' ).show();

	// Determine field type based on column type
	const category = columnValidator.getColumnCategory( columnType );
	const inputType = columnValidator.getInputType( columnType );

	// Render appropriate value field
	renderFilterValueField( inputType, columnType );

	updateFilterQueryPreview();
}

/**
 * Render the appropriate value field based on column type
 *
 * @param {string} inputType - 'text', 'number', 'date', 'select'
 * @param {string} columnType - MySQL column type
 */
export function renderFilterValueField( inputType, columnType ) {
	const $container = $( '#dwm-filter-value-container' );
	const category = columnType ? columnValidator.getColumnCategory( columnType ) : 'text';
	const constraints = columnType ? columnValidator.getValidationConstraints( columnType ) : {};

	let fieldHTML = '';

	if ( inputType === 'select' && category === 'boolean' ) {
		// Boolean dropdown
		fieldHTML = '<select id="dwm-filter-config-value" class="dwm-select">' +
			'<option value="">— select value —</option>' +
			'<option value="TRUE">TRUE</option>' +
			'<option value="FALSE">FALSE</option>' +
			'<option value="EMPTY">EMPTY (NULL or blank)</option>' +
			'</select>';
	} else if ( inputType === 'number' ) {
		// Number input with constraints
		let attrs = 'type="number" class="dwm-input-text"';
		if ( constraints.min !== null ) attrs += ' min="' + constraints.min + '"';
		if ( constraints.max !== null ) attrs += ' max="' + constraints.max + '"';
		if ( constraints.maxLength ) attrs += ' data-maxlength="' + constraints.maxLength + '"';
		if ( constraints.step ) attrs += ' step="' + constraints.step + '"';
		if ( constraints.pattern ) attrs += ' pattern="' + constraints.pattern + '"';

		fieldHTML = '<input id="dwm-filter-config-value" ' + attrs + ' placeholder="Enter number">';
	} else if ( inputType === 'date' ) {
		// Date input
		fieldHTML = '<input id="dwm-filter-config-value" type="date" class="dwm-input-text">';
	} else {
		// Text input (default)
		let attrs = 'type="text" class="dwm-input-text"';
		if ( constraints.maxLength ) attrs += ' maxlength="' + constraints.maxLength + '"';

		fieldHTML = '<input id="dwm-filter-config-value" ' + attrs + ' placeholder="Enter value" disabled>';
	}

	$container.html( fieldHTML );

	// If we have a column type, enable the field
	if ( columnType ) {
		$( '#dwm-filter-config-value' ).prop( 'disabled', false );
	}

	// Add change listener for value field
	$( '#dwm-filter-config-value' ).off( 'change input' ).on( 'change input', function() {
		validateFilterValue();
		updateFilterQueryPreview();
	});
}

// ── Validation ───────────────────────────────────────────────────────────────

/**
 * Validate the current filter value
 */
export function validateFilterValue() {
	const value = $( '#dwm-filter-config-value' ).val();
	const operator = $( '#dwm-filter-config-operator' ).val();
	const columnType = wizardState.filterConfigState.selectedColumnType;
	const $error = $( '#dwm-filter-value-error' );

	if ( ! value || ! columnType ) {
		$error.hide().text( '' );
		return true;
	}

	const category = columnValidator.getColumnCategory( columnType );

	// Prevent accidental no-op "match everything" LIKE filters.
	if ( operator === 'LIKE' && /^\s*%+\s*$/.test( String( value ) ) ) {
		$error.text( 'A LIKE value of only % matches every row. Enter more specific text.' ).show();
		return false;
	}

	// Boolean doesn't need validation
	if ( category === 'boolean' ) {
		$error.hide().text( '' );
		return true;
	}

	// Validate using column validator
	const validation = columnValidator.validateValue( value, columnType );

	if ( ! validation.isValid ) {
		$error.text( validation.error ).show();
		return false;
	}

	$error.hide().text( '' );
	return true;
}

// ── Save ─────────────────────────────────────────────────────────────────────

/**
 * Save filter configuration
 */
export function saveFilterConfig() {
	const column = $( '#dwm-filter-config-column' ).val();
	const operator = $( '#dwm-filter-config-operator' ).val();
	const value = $( '#dwm-filter-config-value' ).val();
	const columnType = wizardState.filterConfigState.selectedColumnType;

	// Validate required fields
	if ( ! column || ! operator ) {
		console.warn('[DWM Filter] Missing column or operator');
		window.DWMToast.warning( 'Please select a column and operator before saving.', { title: 'Filter Config' } );
		return;
	}

	// Validate value for operators that require it
	const isNullOp = operator === 'IS NULL' || operator === 'IS NOT NULL';
	if ( ! isNullOp ) {
		if ( ! value && value !== 0 && value !== '0' ) {
			window.DWMToast.warning( 'Please enter a value for this operator.', { title: 'Filter Config' } );
			return;
		}

		// Run validation
		if ( ! validateFilterValue() ) {
			window.DWMToast.warning( 'Please fix the validation error before saving.', { title: 'Filter Config' } );
			return;
		}
	}

	const filterConfig = {
		column: column,
		operator: operator,
		value: isNullOp ? '' : value,
		columnType: columnType || ''
	};

	const editIdx = wizardState.filterConfigEditIndex;

	// Prevent duplicate filters (in builder mode, wizardState.data.conditions is the same
	// reference as state.conditions in visual-builder.js, so this check covers both modes)
	const existingConditions = wizardState.data.conditions || [];
	const isDuplicate = existingConditions.some( function( cond, i ) {
		if ( editIdx >= 0 && i === editIdx ) return false;
		return cond.column === filterConfig.column &&
			cond.operator === filterConfig.operator &&
			cond.value === filterConfig.value;
	} );

	if ( isDuplicate ) {
		$( '#dwm-filter-duplicate-error' ).text( 'A filter with these exact settings already exists.' ).show();
		return;
	}

	// Builder bridge: hand the saved filter back to visual-builder.js.
	// MUST be before any wizard state modification — wizardState.data.conditions shares
	// the same array reference as state.conditions in visual-builder.js, so pushing here
	// and then letting the event handler also push causes duplication.
	if ( wizardState.builderFilterMode ) {
		wizardState.builderFilterMode = false;
		$( document ).trigger( 'dwm:builder:filter-saved', [ filterConfig, editIdx ] );
		closeFilterConfigModal();
		return;
	}

	// Wizard mode: update state
	wizardState.data.conditions = wizardState.data.conditions || [];
	if ( editIdx >= 0 ) {
		wizardState.data.conditions[ editIdx ] = filterConfig;
	} else {
		wizardState.data.conditions.push( filterConfig );
	}

	// Re-render conditions list
	renderWizardConditions();
	updateStep5NextButton();

	// Close modal
	closeFilterConfigModal();
}

// ── Query Preview ────────────────────────────────────────────────────────────

/**
 * Update the filter query preview based on current selections
 */
export function updateFilterQueryPreview() {
	const column = $( '#dwm-filter-config-column' ).val();
	const operator = $( '#dwm-filter-config-operator' ).val();
	const value = $( '#dwm-filter-config-value' ).val();
	const $preview = $( '#dwm-filter-query-preview-content' );
	const columnType = wizardState.filterConfigState.selectedColumnType || '';
	const columnTable = wizardState.filterConfigState.selectedTable || '';

	// Get primary table info
	const primaryTable = ( wizardState.data.stepConfig && wizardState.data.stepConfig.table ) || 'table';
	const selectedColumns = ( wizardState.data.stepConfig && wizardState.data.stepConfig.columns ) || [];

	// Build SELECT clause
	let selectClause = 'SELECT ';
	if ( selectedColumns.length > 0 ) {
		selectClause += selectedColumns.join( ', ' );
	} else {
		selectClause += '*';
	}

	// Build base query
	let query = selectClause + '\n';
	query += 'FROM ' + primaryTable;

	// Add any existing joins
	const joins = wizardState.data.joins || [];
	joins.forEach( function( join ) {
		query += '\n' + join.type + ' JOIN ' + join.table;
		query += '\n  ON ' + join.local_col + ' = ' + join.foreign_col;
	});

	// Build ORDER BY and LIMIT suffixes from current builder state so the preview
	// always reflects the full query that will actually be executed.
	let orderSuffix = '';
	const previewOrders = wizardState.data.orders || [];
	if ( previewOrders.length > 0 ) {
		const orderParts = previewOrders
			.filter( function( o ) { return !! o.column; } )
			.map( function( o ) { return o.column + ' ' + ( o.direction || 'DESC' ); } );
		if ( orderParts.length > 0 ) {
			orderSuffix = '\nORDER BY ' + orderParts.join( ', ' );
		}
	}

	let limitSuffix = '';
	const previewNoLimit = wizardState.data.noLimit;
	const previewLimit   = wizardState.data.limit || 10;
	if ( ! previewNoLimit ) {
		limitSuffix = '\nLIMIT ' + previewLimit;
	}

	// If no column or operator selected yet, show the base query with ORDER BY / LIMIT.
	if ( ! column || ! operator ) {
		$preview.removeClass( 'empty' ).text( query + orderSuffix + limitSuffix );
		return;
	}

	// Add WHERE clause
	const isNullOp = operator === 'IS NULL' || operator === 'IS NOT NULL';

	if ( isNullOp ) {
		query += '\nWHERE ' + column + ' ' + operator;
	} else {
		// Check if this is a boolean column
		const category = columnType ? columnValidator.getColumnCategory( columnType ) : 'text';

		if ( category === 'boolean' && value ) {
			// Use intelligent boolean filtering
			const columnName = column.split( '.' ).pop();
			const boolCondition = columnValidator.getBooleanSqlCondition( columnTable, columnName, value, operator );
			query += '\nWHERE ' + boolCondition;
		} else if ( value ) {
			// Standard filtering
			query += '\nWHERE ' + column + ' ' + operator;

			// Add quotes for string values
			const quotedValue = isNaN( value ) ? "'" + value + "'" : value;
			query += ' ' + quotedValue;
		} else {
			// No value yet
			query += '\nWHERE ' + column + ' ' + operator + ' [value pending]';
		}
	}

	// Append ORDER BY and LIMIT from builder state (computed above).
	query += orderSuffix + limitSuffix;

	// Update preview
	$preview.removeClass( 'empty' ).text( query );

	// Auto-validate if we have all required fields
	const allFilled = column && operator && ( isNullOp || value );
	if ( allFilled ) {
		validateFilterQuery();
	}
}

/**
 * Validate the filter by sending the structured config to the server.
 * The server builds SQL from the config, so no raw SQL is sent over the wire.
 */
export function validateFilterQuery() {
	const $status = $( '#dwm-filter-validation-status' );
	const $outputTab = $( '.dwm-filter-preview-tab[data-tab="output"]' );

	$status.show().removeClass( 'dwm-validation-success dwm-validation-error' ).addClass( 'dwm-validation-pending' )
		.html( '<span class="spinner is-active"></span> Validating query...' );

	const column   = $( '#dwm-filter-config-column' ).val();
	const operator = $( '#dwm-filter-config-operator' ).val();
	const value    = $( '#dwm-filter-config-value' ).val() || '';

	const config = {
		table:      ( wizardState.data.stepConfig && wizardState.data.stepConfig.table ) || '',
		columns:    ( wizardState.data.stepConfig && wizardState.data.stepConfig.columns ) || [],
		joins:      wizardState.data.joins || [],
		conditions: [ { column, operator, value } ],
		orders:     wizardState.data.orders || [],
		limit:      wizardState.data.limit || 10,
		noLimit:    wizardState.data.noLimit || false,
	};

	ajax(
		'dwm_validate_filter_config',
		{
			config: JSON.stringify( config ),
			max_execution_time: parseInt( $( '#dwm-max-execution-time' ).val(), 10 ) || 30,
		},
		function( data ) {
			const rowCount = data.row_count !== undefined ? data.row_count : ( data.results ? data.results.length : 0 );
			$status.removeClass( 'dwm-validation-pending' ).addClass( 'dwm-validation-success' )
				.html( '<span class="dashicons dashicons-yes-alt"></span> Query valid &mdash; ' + rowCount + ' row' + ( rowCount !== 1 ? 's' : '' ) + ' returned' );
			$outputTab.prop( 'disabled', false );
			$( '#dwm-filter-config-save' ).prop( 'disabled', false );
			wizardState.filterConfigState.validationResults = data.results || [];
		},
		function( data ) {
			console.error('[DWM Filter] Validation FAILED');
			console.error('[DWM Filter] Error data:', data);

			const msg = ( data && data.message ) ? data.message : 'Query validation failed';
			$status.removeClass( 'dwm-validation-pending' ).addClass( 'dwm-validation-error' )
				.html( '<span class="dashicons dashicons-warning"></span> ' + escAttr( msg ) );
			$outputTab.prop( 'disabled', true );
			$( '#dwm-filter-config-save' ).prop( 'disabled', true );
			wizardState.filterConfigState.validationResults = [];
		}
	);
}

// ── Output Preview ───────────────────────────────────────────────────────────

/**
 * Render the output preview table from validation results
 */
export function renderFilterOutputTable( results ) {
	const $content = $( '#dwm-filter-output-preview-content' );

	if ( ! results || results.length === 0 ) {
		$content.html( buildNoResultsPreviewHtml() );
		return;
	}

	const headers = Object.keys( results[ 0 ] );
	let html = '<div class="dwm-output-table-wrapper"><table class="dwm-output-table"><thead><tr>';
	headers.forEach( function( h ) {
		html += '<th>' + escAttr( h ) + '</th>';
	} );
	html += '</tr></thead><tbody>';

	results.forEach( function( row ) {
		html += '<tr>';
		headers.forEach( function( h ) {
			const val = ( row[ h ] === null || row[ h ] === undefined ) ? '<em class="dwm-null">NULL</em>' : escAttr( String( row[ h ] ) );
			html += '<td>' + val + '</td>';
		} );
		html += '</tr>';
	} );

	html += '</tbody></table></div>';
	$content.html( html );
}

// ── Wizard Step Helpers ──────────────────────────────────────────────────────

/**
 * Render wizard condition rows from state
 */
export function renderWizardConditions() {
	const conditions = wizardState.data.conditions || [];
	const $list = $( '#dwm-wizard-conditions-list' ).empty();

	conditions.forEach( function( cond, i ) {
		const valueDisplay = cond.operator === 'IS NULL' || cond.operator === 'IS NOT NULL'
			? ''
			: cond.value;

		$list.append(
			'<div class="dwm-wizard-filter-summary" data-index="' + i + '">' +
				'<div class="dwm-filter-field">' +
					'<span class="dwm-filter-label">Column</span>' +
					'<span class="dwm-filter-value">' + escAttr( cond.column ) + '</span>' +
				'</div>' +
				'<div class="dwm-filter-field">' +
					'<span class="dwm-filter-label">Operator</span>' +
					'<span class="dwm-filter-value">' + escAttr( cond.operator ) + '</span>' +
				'</div>' +
				( valueDisplay ? '<div class="dwm-filter-field">' +
					'<span class="dwm-filter-label">Value</span>' +
					'<span class="dwm-filter-value">' + escAttr( valueDisplay ) + '</span>' +
				'</div>' : '' ) +
				'<div class="dwm-wizard-filter-actions">' +
					'<button type="button" class="dwm-icon-button dwm-wizard-edit-filter" data-index="' + i + '" title="Edit filter">' +
						'<span class="dashicons dashicons-edit"></span>' +
					'</button>' +
					'<button type="button" class="dwm-icon-button dwm-icon-button-danger dwm-wizard-remove-filter" data-index="' + i + '" title="Remove filter">' +
						'<span class="dashicons dashicons-trash"></span>' +
					'</button>' +
				'</div>' +
			'</div>'
		);
	});
}

/**
 * Update step 5 Next button text: "Skip" if no conditions, "Next" if conditions exist
 */
export function updateStep5NextButton() {
	const conditions = wizardState.data.conditions || [];
	const hasConditions = conditions.some( function( c ) { return c.column.trim() !== ''; });
	$( '.dwm-wizard-next' ).text( hasConditions ? 'Next' : 'Skip' );
}
