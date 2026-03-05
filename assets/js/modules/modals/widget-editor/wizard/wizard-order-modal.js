/**
 * Dashboard Widget Manager - Wizard Order Modal Module
 *
 * Order configuration modal logic for the creation wizard and widget editor.
 * Handles opening/closing the order modal, query preview, validation,
 * output preview, and saving order configs.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { wizardState } from './wizard-state.js';
import { escAttr, buildOrderColumnOptions } from './wizard-utils.js';
import { ajax } from '../../../partials/ajax.js';
import * as stepOrder from './wizard-steps/wizard-step-order.js';

const $ = jQuery;

/**
 * Update step 6 Next button text: "Skip" if no orders, "Next" if orders exist
 */
export function updateStep6NextButton() {
	const orders = wizardState.data.orders || [];
	$( '.dwm-wizard-next' ).text( orders.length > 0 ? 'Next' : 'Skip' );
}

/**
 * Open the order configuration modal
 */
export function openOrderConfigModal( editIndex ) {
	wizardState.orderConfigState.editingIndex = editIndex !== undefined ? editIndex : null;

	// Populate column dropdown
	const $columnSelect = $( '#dwm-order-config-column' );
	$columnSelect.html( buildOrderColumnOptions( '' ) );

	// If editing, populate with existing values
	if ( editIndex !== null && wizardState.data.orders[ editIndex ] ) {
		const order = wizardState.data.orders[ editIndex ];
		$columnSelect.val( order.column );
		$( '#dwm-order-config-direction' ).val( order.direction );
		$( '#dwm-order-config-title' ).text( 'Edit Order' );
	} else {
		$( '#dwm-order-config-column' ).val( '' );
		$( '#dwm-order-config-direction' ).val( 'DESC' );
		$( '#dwm-order-config-title' ).text( 'Configure Order' );
	}

	// Reset validation state
	$( '#dwm-order-validation-status' ).hide();
	$( '#dwm-order-config-save' ).prop( 'disabled', true );
	$( '.dwm-order-preview-tab[data-tab="output"]' ).prop( 'disabled', true );

	// Switch to query tab
	$( '.dwm-order-preview-tab' ).removeClass( 'active' );
	$( '.dwm-order-preview-tab[data-tab="query"]' ).addClass( 'active' );
	$( '.dwm-order-preview-pane' ).removeClass( 'active' );
	$( '#dwm-order-preview-query-pane' ).addClass( 'active' );

	// Update preview
	updateOrderQueryPreview();

	// Show modal
	$( '#dwm-order-config-modal' ).addClass( 'active' );
}

/**
 * Close the order configuration modal
 */
export function closeOrderConfigModal() {
	$( '#dwm-order-config-modal' ).removeClass( 'active' );
	wizardState.orderConfigState.editingIndex = null;
	wizardState.orderConfigState.validationResults = [];
}

/**
 * Update the order query preview in modal
 */
export function updateOrderQueryPreview() {
	const column = $( '#dwm-order-config-column' ).val();
	const direction = $( '#dwm-order-config-direction' ).val();
	const $preview = $( '#dwm-order-query-preview-content' );

	// Reset save button and output tab
	$( '#dwm-order-config-save' ).prop( 'disabled', true );
	$( '.dwm-order-preview-tab[data-tab="output"]' ).prop( 'disabled', true );
	$( '#dwm-order-validation-status' ).hide();
	wizardState.orderConfigState.validationResults = [];

	// Switch back to query tab if output tab is active
	if ( $( '.dwm-order-preview-tab[data-tab="output"]' ).hasClass( 'active' ) ) {
		$( '.dwm-order-preview-tab' ).removeClass( 'active' );
		$( '.dwm-order-preview-tab[data-tab="query"]' ).addClass( 'active' );
		$( '.dwm-order-preview-pane' ).removeClass( 'active' );
		$( '#dwm-order-preview-query-pane' ).addClass( 'active' );
	}

	// Build preview query
	const primaryTable = ( wizardState.data.stepConfig && wizardState.data.stepConfig.table ) || 'table';
	const selectedColumns = ( wizardState.data.stepConfig && wizardState.data.stepConfig.columns ) || [];

	let selectClause = 'SELECT ';
	if ( selectedColumns.length > 0 ) {
		selectClause += selectedColumns.map( function( col ) {
			return primaryTable + '.' + col;
		} ).join( ', ' );
	} else {
		selectClause += primaryTable + '.*';
	}

	let query = selectClause + '\nFROM ' + primaryTable;

	// Add joins
	( wizardState.data.joins || [] ).forEach( function( join ) {
		query += '\n' + join.type + ' JOIN ' + join.table + ' ON ' + join.local_col + ' = ' + join.foreign_col;
	} );

	// Add conditions
	if ( wizardState.data.conditions && wizardState.data.conditions.length > 0 ) {
		query += '\nWHERE ';
		query += wizardState.data.conditions.map( function( cond ) {
			if ( cond.operator === 'IS NULL' || cond.operator === 'IS NOT NULL' ) {
				return cond.column + ' ' + cond.operator;
			}
			return cond.column + ' ' + cond.operator + ' ' + cond.value;
		} ).join( '\n  AND ' );
	}

	// Add ORDER BY (only when column is selected)
	if ( column ) {
		query += '\nORDER BY ' + column + ' ' + direction;
	}

	$preview.removeClass( 'empty' ).text( query );

	// Auto-validate
	if ( column && direction ) {
		validateOrderQuery( query );
	}
}

/**
 * Validate the order query
 */
export function validateOrderQuery( query ) {
	const $status = $( '#dwm-order-validation-status' );

	$status.show().removeClass( 'dwm-validation-success dwm-validation-error' ).addClass( 'dwm-validation-pending' )
		.html( '<span class="spinner is-active"></span> Validating...' );

	ajax(
		'dwm_validate_query',
		{
			query: query,
			max_execution_time: parseInt( $( '#dwm-max-execution-time' ).val(), 10 ) || 30,
		},
		function( data ) {
			wizardState.orderConfigState.validationResults = data.results || [];
			const rowCount = data.row_count !== undefined ? data.row_count : wizardState.orderConfigState.validationResults.length;

			$status.removeClass( 'dwm-validation-pending' ).addClass( 'dwm-validation-success' )
				.html( '<span class="dashicons dashicons-yes-alt"></span> Query valid &mdash; ' + rowCount + ' row' + ( rowCount !== 1 ? 's' : '' ) + ' returned' );

			// Enable save button and output tab
			$( '#dwm-order-config-save' ).prop( 'disabled', false );
			$( '.dwm-order-preview-tab[data-tab="output"]' ).prop( 'disabled', false );

			// Render output preview
			renderOrderOutputPreview();
		},
		function( data ) {
			const msg = ( data && data.message ) ? data.message : 'Query validation failed';
			$status.removeClass( 'dwm-validation-pending' ).addClass( 'dwm-validation-error' )
				.html( '<span class="dashicons dashicons-warning"></span> ' + escAttr( msg ) );

			wizardState.orderConfigState.validationResults = [];
		}
	);
}

/**
 * Render the output preview in order modal
 */
export function renderOrderOutputPreview() {
	const $container = $( '#dwm-order-output-preview-content' );
	const results = wizardState.orderConfigState.validationResults || [];

	if ( results.length === 0 ) {
		$container.html( '<p class="dwm-output-empty">No results returned.</p>' );
		return;
	}

	// Build table using shared styled classes
	const cols = Object.keys( results[0] );
	let html = '<div class="dwm-output-table-wrapper"><table class="dwm-output-table"><thead><tr>';
	cols.forEach( function( col ) {
		html += '<th>' + escAttr( col ) + '</th>';
	} );
	html += '</tr></thead><tbody>';

	results.forEach( function( row ) {
		html += '<tr>';
		cols.forEach( function( col ) {
			const val = ( row[ col ] === null || row[ col ] === undefined ) ? '<em class="dwm-null">NULL</em>' : escAttr( String( row[ col ] ) );
			html += '<td>' + val + '</td>';
		} );
		html += '</tr>';
	} );

	html += '</tbody></table></div>';
	$container.html( html );
}

/**
 * Save the order configuration
 */
export function saveOrderConfig() {
	const column = $( '#dwm-order-config-column' ).val();
	const direction = $( '#dwm-order-config-direction' ).val();

	if ( ! column ) {
		return;
	}

	const order = {
		column: column,
		direction: direction,
		locked: true,
	};

	// Builder bridge: hand the saved order back to visual-builder.js.
	if ( wizardState.builderOrderMode ) {
		wizardState.builderOrderMode = false;
		$( document ).trigger( 'dwm:builder:order-saved', [ order, wizardState.orderConfigState.editingIndex ] );
		closeOrderConfigModal();
		return;
	}

	if ( wizardState.orderConfigState.editingIndex !== null ) {
		// Update existing order
		wizardState.data.orders[ wizardState.orderConfigState.editingIndex ] = order;
	} else {
		// Add new order
		wizardState.data.orders = wizardState.data.orders || [];
		wizardState.data.orders.push( order );
	}

	// Update step module state
	stepOrder.setStepState( { orders: wizardState.data.orders } );
	stepOrder.renderOrdersList();
	updateStep6NextButton();

	// Close modal
	closeOrderConfigModal();
}

/**
 * Validate the order configuration for step 6
 */
export function validateOrderConfig() {
	// Build the full query with orders
	const orders = wizardState.data.orders || [];

	if ( orders.length === 0 ) {
		window.DWMToast.warning( 'Please add at least one order column before validating.', { title: 'Order Config' } );
		return;
	}

	const primaryTable = ( wizardState.data.stepConfig && wizardState.data.stepConfig.table ) || 'table';
	const selectedColumns = ( wizardState.data.stepConfig && wizardState.data.stepConfig.columns ) || [];

	let selectClause = 'SELECT ';
	if ( selectedColumns.length > 0 ) {
		selectClause += selectedColumns.map( function( col ) {
			return primaryTable + '.' + col;
		} ).join( ', ' );
	} else {
		selectClause += primaryTable + '.*';
	}

	let query = selectClause + '\nFROM ' + primaryTable;

	// Add joins
	( wizardState.data.joins || [] ).forEach( function( join ) {
		query += '\n' + join.type + ' JOIN ' + join.table + ' ON ' + join.local_col + ' = ' + join.foreign_col;
	} );

	// Add conditions
	if ( wizardState.data.conditions && wizardState.data.conditions.length > 0 ) {
		query += '\nWHERE ';
		query += wizardState.data.conditions.map( function( cond ) {
			if ( cond.operator === 'IS NULL' || cond.operator === 'IS NOT NULL' ) {
				return cond.column + ' ' + cond.operator;
			}
			return cond.column + ' ' + cond.operator + ' ' + cond.value;
		} ).join( '\n  AND ' );
	}

	// Add ORDER BY
	query += '\nORDER BY ';
	query += orders.map( function( o ) {
		return o.column + ' ' + o.direction;
	} ).join( ', ' );

	// Show validation status
	const $status = $( '#dwm-order-validation-status' );
	$status.show().removeClass( 'dwm-validation-success dwm-validation-error' ).addClass( 'dwm-validation-pending' )
		.html( '<span class="spinner is-active"></span> Validating...' );

	ajax(
		'dwm_validate_query',
		{
			query: query,
			max_execution_time: parseInt( $( '#dwm-max-execution-time' ).val(), 10 ) || 30,
		},
		function( data ) {
			const rowCount = data.row_count !== undefined ? data.row_count : ( data.results ? data.results.length : 0 );
			$status.removeClass( 'dwm-validation-pending' ).addClass( 'dwm-validation-success' )
				.html( '<span class="dashicons dashicons-yes-alt"></span> Query valid &mdash; ' + rowCount + ' row' + ( rowCount !== 1 ? 's' : '' ) + ' returned' );
		},
		function( data ) {
			const msg = ( data && data.message ) ? data.message : 'Query validation failed';
			$status.removeClass( 'dwm-validation-pending' ).addClass( 'dwm-validation-error' )
				.html( '<span class="dashicons dashicons-warning"></span> ' + escAttr( msg ) );
		}
	);
}

/**
 * Initialize all order-related event bindings
 */
export function initOrderModalEvents() {

	// Builder bridge: open order modal from the from-scratch widget editor
	$( document ).on( 'dwm:builder:open-order', function( _e, ctx ) {
		wizardState.data.stepConfig = {
			table:            ctx.table            || '',
			availableColumns: ctx.availableColumns || [],
		};
		wizardState.data.joins  = ctx.joins  || [];
		wizardState.data.orders = ctx.orders || [];
		wizardState.builderOrderMode = true;
		openOrderConfigModal( ctx.editIndex );
	} );

	// Order config modal: cancel (builder bridge reset)
	$( document ).on( 'click', '.dwm-order-config-cancel', function() {
		wizardState.builderOrderMode = false;
		closeOrderConfigModal();
	} );

	// Step 6: Add order (open modal)
	$( document ).on( 'click', '#dwm-wizard-add-order', function() {
		openOrderConfigModal();
	} );

	// Step 6: Edit order
	$( document ).on( 'click', '.dwm-wizard-edit-order', function() {
		const idx = parseInt( $( this ).data( 'index' ), 10 );
		openOrderConfigModal( idx );
	} );

	// Step 6: Remove order
	$( document ).on( 'click', '.dwm-wizard-remove-order', function() {
		const idx = parseInt( $( this ).data( 'index' ), 10 );
		wizardState.data.orders.splice( idx, 1 );
		stepOrder.setStepState( { orders: wizardState.data.orders } );
		stepOrder.renderOrdersList();
		updateStep6NextButton();
	} );

	// Order config modal: save
	$( document ).on( 'click', '#dwm-order-config-save', function() {
		saveOrderConfig();
	} );

	// Order config modal: column change
	$( document ).on( 'change', '#dwm-order-config-column', function() {
		updateOrderQueryPreview();
	} );

	// Order config modal: direction change
	$( document ).on( 'change', '#dwm-order-config-direction', function() {
		updateOrderQueryPreview();
	} );

	// Order config modal: tab switching
	$( document ).on( 'click', '.dwm-order-preview-tab', function() {
		const tab = $( this ).data( 'tab' );
		if ( $( this ).prop( 'disabled' ) ) return;

		$( '.dwm-order-preview-tab' ).removeClass( 'active' );
		$( this ).addClass( 'active' );

		$( '.dwm-order-preview-pane' ).removeClass( 'active' );
		$( '#dwm-order-preview-' + tab + '-pane' ).addClass( 'active' );
	} );

	// Step 6: Validate order
	$( document ).on( 'click', '#dwm-wizard-validate-order', function() {
		validateOrderConfig();
	} );
}
