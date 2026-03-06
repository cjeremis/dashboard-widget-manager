/**
 * Dashboard Widget Manager - Preview Module
 *
 * Handles widget preview functionality
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { buildNoResultsPreviewHtml } from './widget-editor/output-preview-empty-state.js';
const $ = jQuery;

let previewQuery = '';
let previewQueryEditor = null;

function escHtml( str ) {
	return String( str )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' );
}

/**
 * Initialize preview events
 */
export function initPreview() {
	// Preview from widget list cards
	$(document).on('click', '.dwm-preview-widget', function(e) {
		e.preventDefault();
		const widgetId = $(this).data('widget-id');
		showPreview(widgetId);
	});

	// Preview tabs switching
	$(document).on('click', '.dwm-preview-tab', function() {
		const tab = $(this).data('tab');
		$('.dwm-preview-tab').removeClass('active');
		$(this).addClass('active');
		$('.dwm-preview-tab-content').removeClass('active');
		$('#dwm-preview-' + tab + '-content').addClass('active');
		if ( tab === 'output' ) {
			loadPreviewOutput();
		}
	});
}

function resetPreviewQueryPane() {
	const $queryContent = $( '#dwm-preview-query-content' );
	$queryContent.removeClass( 'dwm-preview-query-editor-pane' );
	$queryContent.empty();
	previewQueryEditor = null;
}

function renderPreviewQueryAsText( query ) {
	resetPreviewQueryPane();
	$( '#dwm-preview-query-content' ).text( query || 'No query available' );
}

function renderPreviewQueryAsEditor( query ) {
	const $queryContent = $( '#dwm-preview-query-content' );
	const queryText = query || 'No query available';

	$queryContent
		.addClass( 'dwm-preview-query-editor-pane' )
		.html(
			'<div class="dwm-query-editor-wrapper dwm-preview-query-editor">' +
				'<textarea id="dwm-preview-query-editor" class="dwm-code-editor" rows="10" readonly></textarea>' +
			'</div>'
		);

	if ( typeof wp === 'undefined' || ! wp.codeEditor || ! wp.codeEditor.initialize ) {
		$( '#dwm-preview-query-editor' ).val( queryText );
		return;
	}

	const editorSettings = wp.codeEditor.defaultSettings || {};
	previewQueryEditor = wp.codeEditor.initialize( 'dwm-preview-query-editor', {
		...editorSettings,
		codemirror: {
			...editorSettings.codemirror,
			mode: 'text/x-sql',
			lineNumbers: true,
			lineWrapping: true,
			readOnly: 'nocursor'
		}
	} );

	if ( previewQueryEditor && previewQueryEditor.codemirror ) {
		const cm = previewQueryEditor.codemirror;
		cm.setValue( queryText );
		cm.scrollTo( 0, 0 );
		cm.refresh();

		// Keep the wizard preview non-interactive while still using editor styling.
		$( cm.getWrapperElement() ).on( 'mousedown.dwmPreviewReadonly click.dwmPreviewReadonly keydown.dwmPreviewReadonly', function( e ) {
			e.preventDefault();
		} );
	}
}

/**
 * Load output results for the preview Output tab
 */
function loadPreviewOutput() {
	const $content = $( '#dwm-preview-output-content' );

	if ( ! previewQuery ) {
		$content.html( '<p class="dwm-output-empty">No query available.</p>' );
		return;
	}

	$content.html( '<p>Loading output...</p>' );

	const limitedQuery = previewQuery.replace( /\bLIMIT\s+\d+(\s*,\s*\d+)?\s*$/i, '' ).trim() + '\nLIMIT 5';

	ajax(
		'dwm_validate_query',
		{ query: limitedQuery },
		function( data ) {
			const results = data.results || [];
			if ( results.length === 0 ) {
				$content.html( buildNoResultsPreviewHtml() );
				return;
			}
			const headers = Object.keys( results[ 0 ] );
			let html = '<div class="dwm-output-table-wrapper"><table class="dwm-output-table"><thead><tr>';
			headers.forEach( function( h ) {
				html += '<th>' + escHtml( h ) + '</th>';
			} );
			html += '</tr></thead><tbody>';
			results.forEach( function( row ) {
				html += '<tr>';
				headers.forEach( function( h ) {
					const val = ( row[ h ] === null || row[ h ] === undefined )
						? '<em class="dwm-null">NULL</em>'
						: escHtml( String( row[ h ] ) );
					html += '<td>' + val + '</td>';
				} );
				html += '</tr>';
			} );
			html += '</tbody></table></div>';
			$content.html( html );
		},
		function( data ) {
			$content.html( '<p class="dwm-output-empty dwm-output-error">' + escHtml( ( data && data.message ) ? data.message : 'Failed to load output.' ) + '</p>' );
		}
	);
}

/**
 * Show widget preview from saved widget
 *
 * @param {number} widgetId Widget ID to preview
 */
function showPreview(widgetId) {
	const $uiContent = $('#dwm-preview-ui-content');
	const $queryContent = $('#dwm-preview-query-content');
	const $outputContent = $('#dwm-preview-output-content');

	previewQuery = '';
	$uiContent.html('<p>Loading...</p>');
	$queryContent.html('Loading query...');
	$outputContent.empty();

	// Reset to UI tab
	$('.dwm-preview-tab').removeClass('active');
	$('.dwm-preview-tab[data-tab="ui"]').addClass('active');
	$('.dwm-preview-tab-content').removeClass('active');
	$('#dwm-preview-ui-content').addClass('active');

	openModal('dwm-preview-modal');

	ajax(
		'dwm_preview_widget',
		{ widget_id: widgetId },
		function(data) {
			previewQuery = data.query || '';
			$uiContent.html(data.html || '<p>No preview available</p>');
			renderPreviewQueryAsText( data.query );
		},
		function(data) {
			$uiContent.html(`<div class="dwm-widget-error"><p>${data.message || 'Failed to load preview.'}</p></div>`);
			renderPreviewQueryAsText( 'Error loading query' );
		}
	);
}

/**
 * Show wizard preview from current wizard state
 *
 * @param {Object} wizardData Current wizard configuration
 */
export function showWizardPreview(wizardData) {
	const $uiContent = $('#dwm-preview-ui-content');
	const $queryContent = $('#dwm-preview-query-content');
	const $outputContent = $('#dwm-preview-output-content');

	previewQuery = '';
	$uiContent.html('<p>Loading preview...</p>');
	$queryContent.html('Loading query...');
	$outputContent.empty();

	// Reset to UI tab
	$('.dwm-preview-tab').removeClass('active');
	$('.dwm-preview-tab[data-tab="ui"]').addClass('active');
	$('.dwm-preview-tab-content').removeClass('active');
	$('#dwm-preview-ui-content').addClass('active');

	openModal('dwm-preview-modal');

	ajax(
		'dwm_preview_wizard',
		{ wizard_data: wizardData },
		function(data) {
			previewQuery = data.query || '';
			$uiContent.html(data.html || '<p>No preview available</p>');
			renderPreviewQueryAsEditor( data.query );
		},
		function(data) {
			$uiContent.html(`<div class="dwm-widget-error"><p>${data.message || 'Failed to load preview.'}</p></div>`);
			renderPreviewQueryAsEditor( 'Error loading query' );
		}
	);
}
