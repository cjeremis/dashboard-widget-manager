/**
 * Dashboard Widget Manager - Widget Editor Results Module
 *
 * Query preview tabs, output rendering, and results modal logic
 * extracted from widget-editor.js.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { state, escapeHtml } from './widget-editor-state.js';
import { getCurrentQueryText } from './widget-editor-query.js';

const $ = jQuery;

export function updateQueryPreviewContent() {
	const query = getCurrentQueryText();
	const $preview = $( '#dwm-query-preview-content' );

	if ( ! query.trim() ) {
		$preview
			.addClass( 'empty' )
			.text( 'No query entered yet.' );
		return;
	}

	$preview
		.removeClass( 'empty' )
		.text( query );
}

export function renderQueryOutputPreview() {
	const $container = $( '#dwm-query-output-preview-content' );
	const rows = state.lastValidationResults && state.lastValidationResults.rows
		? state.lastValidationResults.rows
		: [];

	if ( rows.length === 0 ) {
		$container.html( '<p class="dwm-output-empty">No results returned.</p>' );
		return;
	}

	const columns = Object.keys( rows[ 0 ] );
	let html = '<div class="dwm-output-table-wrapper"><table class="dwm-output-table"><thead><tr>';

	columns.forEach( function( column ) {
		html += '<th>' + escapeHtml( column ) + '</th>';
	} );

	html += '</tr></thead><tbody>';

	rows.forEach( function( row ) {
		html += '<tr>';
		columns.forEach( function( column ) {
			const value = ( row[ column ] === null || row[ column ] === undefined )
				? '<em class="dwm-null">NULL</em>'
				: escapeHtml( String( row[ column ] ) );
			html += '<td>' + value + '</td>';
		} );
		html += '</tr>';
	} );

	html += '</tbody></table></div>';
	$container.html( html );
}

export function resetQueryOutputPreview() {
	$( '#dwm-query-output-preview-content' ).html( '<p class="dwm-output-empty">Validate your query to preview output.</p>' );
}

export function setQueryPreviewTab( tab ) {
	const requestedTab = tab || 'input';
	const $requested = $( '.dwm-query-preview-tab[data-tab="' + requestedTab + '"]' );

	if ( ! $requested.length || $requested.prop( 'disabled' ) ) {
		return;
	}

	$( '.dwm-query-preview-tab' ).removeClass( 'active' );
	$requested.addClass( 'active' );

	$( '.dwm-query-preview-pane' ).removeClass( 'active' );
	$( '.dwm-query-preview-pane[data-pane="' + requestedTab + '"]' ).addClass( 'active' );

	if ( requestedTab === 'input' && state.codeEditors.query ) {
		state.codeEditors.query.codemirror.refresh();
	}

	if ( requestedTab === 'output' ) {
		renderQueryOutputPreview();
	}
}

export function enableQueryOutputPreview( enabled ) {
	const isEnabled = !! enabled;
	const $outputTab = $( '.dwm-query-preview-tab[data-tab="output"]' );

	$outputTab.prop( 'disabled', ! isEnabled );

	if ( ! isEnabled && $outputTab.hasClass( 'active' ) ) {
		setQueryPreviewTab( 'input' );
	}
}

export function resetQueryPreviewTabs() {
	enableQueryOutputPreview( false );
	updateQueryPreviewContent();
	resetQueryOutputPreview();
	setQueryPreviewTab( 'input' );
}

/**
 * Open results modal
 */
export function openResultsModal() {
	if (!state.lastValidationResults) {
		showNotice('No query results to preview.', 'error');
		return;
	}

	state.resultsData = state.lastValidationResults.rows || [];
	state.filteredData = state.resultsData;
	state.currentPage = 1;
	state.searchTerm = '';
	state.sortColumn = null;
	state.sortDirection = 'asc';

	renderResultsTable();
	openModal('dwm-results-modal');
}

/**
 * Close results modal
 */
export function closeResultsModal() {
	closeModal( 'dwm-results-modal' );
}

/**
 * Filter and render results
 */
export function filterAndRenderResults() {
	state.filteredData = state.resultsData.filter(row => {
		if (!state.searchTerm) return true;
		return JSON.stringify(row).toLowerCase().includes(state.searchTerm);
	});

	if (state.sortColumn) {
		state.filteredData.sort((a, b) => {
			const aVal = a[state.sortColumn] || '';
			const bVal = b[state.sortColumn] || '';
			const cmp = String(aVal).localeCompare(String(bVal));
			return state.sortDirection === 'asc' ? cmp : -cmp;
		});
	}

	state.currentPage = 1;
	renderResultsTable();
}

/**
 * Render results table
 */
export function renderResultsTable() {
	const $thead = $( '#dwm-results-thead' );
	const $tbody = $( '#dwm-results-tbody' );
	const $empty = $( '#dwm-results-empty' );
	const $pagination = $( '#dwm-results-pagination' );
	const $showing = $( '#dwm-results-showing' );
	const totalRows = state.filteredData.length;

	if ( totalRows === 0 ) {
		$thead.empty();
		$tbody.empty();
		$empty.show();
		$pagination.empty();
		$showing.text( 'Showing 0 of 0 rows' );
		return;
	}

	$empty.hide();

	const columns = Object.keys( state.filteredData[ 0 ] || {} );
	const startIdx = ( state.currentPage - 1 ) * state.rowsPerPage;
	const endIdx = startIdx + state.rowsPerPage;
	const pageData = state.filteredData.slice( startIdx, endIdx );
	const startRow = startIdx + 1;
	const endRow = Math.min( endIdx, totalRows );

	let theadHtml = '<tr>';
	columns.forEach( function( column ) {
		const activeSort = state.sortColumn === column;
		const arrow = activeSort ? ( state.sortDirection === 'asc' ? ' ↑' : ' ↓' ) : '';
		theadHtml += '<th data-column="' + escapeHtml( column ) + '">' + escapeHtml( column ) + arrow + '</th>';
	} );
	theadHtml += '</tr>';
	$thead.html( theadHtml );

	let tbodyHtml = '';
	pageData.forEach( function( row ) {
		tbodyHtml += '<tr>';
		columns.forEach( function( column ) {
			const value = row[ column ];
			const display = value === null || value === undefined
				? '<em class="dwm-null">NULL</em>'
				: escapeHtml( String( value ) );
			tbodyHtml += '<td>' + display + '</td>';
		} );
		tbodyHtml += '</tr>';
	} );
	$tbody.html( tbodyHtml );
	$showing.text( 'Showing ' + startRow + '-' + endRow + ' of ' + totalRows + ' rows' );

	const totalPages = Math.max( 1, Math.ceil( totalRows / state.rowsPerPage ) );
	let pagerHtml = '';

	if ( state.currentPage > 1 ) {
		pagerHtml += '<button type="button" data-page="' + ( state.currentPage - 1 ) + '">Previous</button>';
	}

	for ( let page = 1; page <= totalPages; page++ ) {
		const active = page === state.currentPage ? ' class="active"' : '';
		pagerHtml += '<button type="button" data-page="' + page + '"' + active + '>' + page + '</button>';
	}

	if ( state.currentPage < totalPages ) {
		pagerHtml += '<button type="button" data-page="' + ( state.currentPage + 1 ) + '">Next</button>';
	}

	$pagination.html( pagerHtml );
}
