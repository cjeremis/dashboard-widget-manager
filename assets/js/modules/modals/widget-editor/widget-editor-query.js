/**
 * Dashboard Widget Manager - Widget Editor Query Module
 *
 * Query-related functions extracted from widget-editor.js.
 * Handles query stats, validation, editing state, and SELECT prefix enforcement.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import {
	state,
	getAutoBuildQuery,
	setAutoBuildQuery,
	getAutoBuiltQueryValid,
	setAutoBuiltQueryValid,
	escapeHtml
} from './widget-editor-state.js';
import { ajax } from '../../partials/ajax.js';

const $ = jQuery;

// ── Callback registry ────────────────────────────────────────────────
// Functions that live in widget-editor.js but are called by query helpers.
let _callbacks = {};

/**
 * Register external callbacks that query functions depend on.
 *
 * Expected keys:
 *   enableQueryOutputPreview( enabled )
 *   renderQueryOutputPreview()
 *   resetQueryOutputPreview()
 *   updateQueryPreviewContent()
 */
export function registerQueryCallbacks( callbacks ) {
	_callbacks = callbacks;
}

// ── Query stats ──────────────────────────────────────────────────────

/**
 * Update query stats display
 */
export function updateQueryStats() {
	let query = '';
	if (state.codeEditors.query) {
		query = state.codeEditors.query.codemirror.getValue();
	} else {
		query = $('#dwm-widget-query').val() || '';
	}

	const charCount = query.length;
	const lineCount = query ? query.split('\n').length : 0;

	$('.dwm-char-count').text(charCount + ' character' + (charCount !== 1 ? 's' : ''));
	$('.dwm-line-count').text(lineCount + ' line' + (lineCount !== 1 ? 's' : ''));
}

// ── Loading / validation status ──────────────────────────────────────

/**
 * Set the query code editor into a loading state (disabled + placeholder text)
 * or restore it to normal (enabled).
 */
export function setQueryEditorLoading( loading ) {
	$( '.dwm-query-tab-spinner' ).toggle( loading );
	if ( loading ) {
		$( '.dwm-query-editor-loading' ).css( 'display', 'flex' );
	} else {
		$( '.dwm-query-editor-loading' ).hide();
	}
}

export function clearQueryValidationStatus() {
	const $status = $( '#dwm-query-editor-validation-status' );
	$status.hide().removeClass( 'dwm-validation-pending dwm-validation-success dwm-validation-error' ).empty();
}

export function showQueryValidationStatus( statusClass, message, iconClass ) {
	const $status = $( '#dwm-query-editor-validation-status' );
	$status
		.removeClass( 'dwm-validation-pending dwm-validation-success dwm-validation-error' )
		.addClass( statusClass )
		.html( '<span class="dashicons ' + iconClass + '"></span> ' + escapeHtml( message ) )
		.show();
}

// ── Query structure helpers ──────────────────────────────────────────

export function hasMinimumQueryStructure( query ) {
	if ( ! query ) return false;
	const q = query.trim();
	if ( ! /^SELECT\b/i.test( q ) ) return false;
	if ( ! /\bFROM\s+\S/i.test( q ) ) return false;
	const match = /^SELECT\s+([\s\S]+?)\s+FROM\b/i.exec( q );
	return !! match && !! match[ 1 ].trim();
}

export function updateValidateButtonState() {
	if ( ! state.queryEditEnabled ) {
		$( '.dwm-query-actions' ).hide();
		return;
	}
	$( '.dwm-query-actions' ).show();
	$( '#dwm-validate-query' ).prop( 'disabled', ! hasMinimumQueryStructure( getCurrentQueryText() ) );
}

// ── Editor read-only / text helpers ──────────────────────────────────

export function setQueryEditorReadonly( readonly ) {
	if ( state.codeEditors.query && state.codeEditors.query.codemirror ) {
		const cm = state.codeEditors.query.codemirror;
		cm.setOption( 'readOnly', readonly ? 'nocursor' : false );
		cm.setOption( 'styleActiveLine', ! readonly );
		$( cm.getWrapperElement() ).toggleClass( 'dwm-cm-readonly', !! readonly );
	}

	$( '#dwm-widget-query' ).prop( 'readonly', !! readonly );
}

export function setQueryText( text ) {
	state.suppressQueryChange = true;
	if ( state.codeEditors.query && state.codeEditors.query.codemirror ) {
		state.codeEditors.query.codemirror.setValue( text );
	} else {
		$( '#dwm-widget-query' ).val( text );
	}
	state.suppressQueryChange = false;
}

// ── SELECT prefix enforcement ────────────────────────────────────────

export function enforceSelectPrefix() {
	if ( ! state.queryEditEnabled ) return;
	const current = getCurrentQueryText();
	if ( /^SELECT[\s\n]/i.test( current ) ) return;

	// Find if SELECT appears anywhere and use everything from there, otherwise just prepend.
	const idx = current.search( /SELECT[\s\n]/i );
	const corrected = 'SELECT ' + ( idx >= 0 ? current.slice( idx + 7 ) : current.replace( /^\s*/, '' ) );
	setQueryText( corrected );
}

// Named handlers for SELECT prefix protection so they can be removed on toggle-off.
export function _selectPrefixBeforeChange( _cm, changeObj ) {
	const PREFIX_LEN = 7; // "SELECT "
	const from = changeObj.from;
	const to   = changeObj.to;

	// Only intervene when the change starts inside the protected zone.
	if ( from.line !== 0 || from.ch >= PREFIX_LEN ) return;

	// Change is entirely within the prefix — block it completely.
	if ( to.line === 0 && to.ch <= PREFIX_LEN ) {
		changeObj.cancel();
		return;
	}

	// Change spans prefix into user-editable content — trim start to end of prefix.
	if ( changeObj.update ) {
		changeObj.update( { line: 0, ch: PREFIX_LEN }, to, changeObj.text );
	} else {
		changeObj.cancel();
	}
}

export function _selectPrefixCursorGuard( cm ) {
	const from = cm.getCursor( 'from' );
	if ( from.line === 0 && from.ch < 7 ) {
		cm.setCursor( { line: 0, ch: 7 } );
	}
}

// ── Editing enabled / dirty state ────────────────────────────────────

export function setQueryEditingEnabled( enabled ) {
	state.queryEditEnabled = !! enabled;
	const cm = state.codeEditors.query && state.codeEditors.query.codemirror;

	if ( enabled ) {
		// Capture the current auto-built SQL so disabling editing will revert to it.
		// This handles cases where _autoBuildQuery hasn't been updated yet (e.g. first
		// time opening the editor before any data-tab change triggers autoBuildAndValidate).
		if ( ! getAutoBuildQuery() ) {
			setAutoBuildQuery( getCurrentQueryText() );
		}

		// Seed with SELECT if the field is empty.
		const current = getCurrentQueryText().trim();
		if ( ! current ) {
			setQueryText( 'SELECT ' );
		}
		setQueryEditorReadonly( false );
		state.queryValidated = false;
		if ( _callbacks.enableQueryOutputPreview ) _callbacks.enableQueryOutputPreview( false );

		if ( cm ) {
			cm.on( 'beforeChange', _selectPrefixBeforeChange );
			cm.on( 'cursorActivity', _selectPrefixCursorGuard );
			setTimeout( function() {
				cm.focus();
				cm.setCursor( { line: 0, ch: 7 } );
			}, 0 );
		}
	} else {
		if ( cm ) {
			cm.off( 'beforeChange', _selectPrefixBeforeChange );
			cm.off( 'cursorActivity', _selectPrefixCursorGuard );
		}

		// Revert to the latest auto-built query.
		setQueryText( getAutoBuildQuery() );
		setQueryEditorReadonly( true );
		state.queryValidated     = getAutoBuiltQueryValid();
		state.lastValidatedQuery = getAutoBuildQuery();
		state.queryDirty         = false;
		clearQueryValidationStatus();
		updateValidateButtonState();
		if ( getAutoBuiltQueryValid() ) {
			if ( _callbacks.resetQueryOutputPreview ) _callbacks.resetQueryOutputPreview();
			if ( _callbacks.enableQueryOutputPreview ) _callbacks.enableQueryOutputPreview( true );
		} else {
			if ( _callbacks.enableQueryOutputPreview ) _callbacks.enableQueryOutputPreview( false );
			if ( _callbacks.resetQueryOutputPreview ) _callbacks.resetQueryOutputPreview();
		}
	}
}

export function setQueryDirtyFromCurrentValue() {
	state.queryDirty = getCurrentQueryText() !== ( state.lastValidatedQuery || '' );
	updateValidateButtonState();
}

export function handleQueryContentChanged() {
	if ( state.suppressQueryChange ) {
		return;
	}

	state.queryValidated = false;
	clearQueryValidationStatus();
	if ( _callbacks.enableQueryOutputPreview ) _callbacks.enableQueryOutputPreview( false );
	if ( _callbacks.resetQueryOutputPreview ) _callbacks.resetQueryOutputPreview();
	if ( _callbacks.updateQueryPreviewContent ) _callbacks.updateQueryPreviewContent();
	setQueryDirtyFromCurrentValue();
}

export function resetQueryEditorInteractionState( queryText ) {
	// Optimistically treat a non-empty saved query as valid (the builder validation
	// chain guarantees only valid queries can be saved).
	setAutoBuiltQueryValid( !! ( queryText && queryText.trim() ) );
	state.queryValidated = false;
	state.lastValidatedQuery = queryText || '';
	state.queryDirty = false;
	state.suppressQueryChange = false;
	$( '#dwm-query-edit-toggle' ).prop( 'checked', false );
	clearQueryValidationStatus();
	setQueryEditorLoading( false );
	if ( _callbacks.resetQueryOutputPreview ) _callbacks.resetQueryOutputPreview();
	setQueryEditingEnabled( false ); // uses _autoBuiltQueryValid to set output preview state
	updateValidateButtonState();
}

// ── Query text accessor ──────────────────────────────────────────────

export function getCurrentQueryText() {
	if ( state.codeEditors.query ) {
		return state.codeEditors.query.codemirror.getValue() || '';
	}

	return $( '#dwm-widget-query' ).val() || '';
}

// ── Validate query (AJAX) ────────────────────────────────────────────

export function validateQuery() {
	// Enforce SELECT prefix on manual queries before validating.
	if ( state.queryEditEnabled ) {
		enforceSelectPrefix();
	}

	const currentQuery = getCurrentQueryText();

	if ( ! /^SELECT[\s\n]/i.test( currentQuery.trim() ) ) {
		setQueryEditorLoading( false );
		showQueryValidationStatus( 'dwm-validation-error', 'Only SELECT queries are allowed.', 'dashicons-warning' );
		state.queryValidated = false;
		updateValidateButtonState();
		return;
	}

	if ( _callbacks.updateQueryPreviewContent ) _callbacks.updateQueryPreviewContent();
	setQueryEditorLoading( true );
	showQueryValidationStatus( 'dwm-validation-pending', 'Validating query...', 'dashicons-update dwm-spin' );

	ajax(
		'dwm_validate_query',
		{
			query: currentQuery,
			max_execution_time: parseInt( $( '#dwm-max-execution-time' ).val(), 10 ) || 30,
		},
		function(data) {
			setQueryText( currentQuery );
			setQueryEditorLoading( false );
			state.queryValidated = true;
			state.lastValidatedQuery = currentQuery;
			state.lastValidationResults = data;
			state.queryDirty = false;
			if ( ! state.queryEditEnabled ) {
				setAutoBuiltQueryValid( true );
			}
			updateValidateButtonState();
			if ( _callbacks.enableQueryOutputPreview ) _callbacks.enableQueryOutputPreview( true );
			if ( _callbacks.renderQueryOutputPreview ) _callbacks.renderQueryOutputPreview();
			showQueryValidationStatus( 'dwm-validation-success', 'Query is valid (' + data.row_count + ' rows)', 'dashicons-yes-alt' );
		},
		function(data) {
			setQueryEditorLoading( false );
			state.queryValidated = false;
			state.queryDirty = true;
			if ( ! state.queryEditEnabled ) {
				setAutoBuiltQueryValid( false );
			}
			updateValidateButtonState();
			if ( _callbacks.enableQueryOutputPreview ) _callbacks.enableQueryOutputPreview( false );
			if ( _callbacks.resetQueryOutputPreview ) _callbacks.resetQueryOutputPreview();
			showQueryValidationStatus( 'dwm-validation-error', data.message || 'Invalid query', 'dashicons-warning' );
		}
	);
}
