/**
 * Dashboard Widget Manager - Output Preview Empty State Helpers
 *
 * Shared no-results rendering logic for query/output preview panes.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { state } from './widget-editor-state.js';

const $ = jQuery;

const DEFAULT_NO_RESULTS_TEMPLATE = '<div style="display:flex;align-items:center;justify-content:center;min-height:120px;padding:24px;text-align:center;">\n  <p style="margin:0;font-size:14px;color:#666;">No results found.</p>\n</div>';

function normalizeTemplate( value ) {
	return String( value || '' ).replace( /\r/g, '' ).trim();
}

function getTemplateFromEditor() {
	const cm = state && state.codeEditors && state.codeEditors.no_results_template && state.codeEditors.no_results_template.codemirror
		? state.codeEditors.no_results_template.codemirror
		: null;
	if ( cm ) {
		return cm.getValue() || '';
	}
	return $( '#dwm-widget-no-results-template' ).val() || '';
}

export function hasCustomNoResultsTemplate() {
	const template = normalizeTemplate( getTemplateFromEditor() );
	if ( ! template ) {
		return false;
	}
	return template !== normalizeTemplate( DEFAULT_NO_RESULTS_TEMPLATE );
}

export function buildNoResultsPreviewHtml() {
	if ( hasCustomNoResultsTemplate() ) {
		return '<div class="dwm-output-empty-template">' + getTemplateFromEditor() + '</div>';
	}

	return (
		'<div class="dwm-output-empty-wrap">' +
			'<p class="dwm-output-empty">No results returned.</p>' +
			'<p class="dwm-output-empty-hint">You can customize the no results template on the Output tab.</p>' +
		'</div>'
	);
}
