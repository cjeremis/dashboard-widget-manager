/**
 * Dashboard Widget Manager - Widget Editor State Module
 *
 * Shared state, constants, and utility helpers for the widget editor.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// Editor state (object reference — mutations visible to all importers)
export const state = {
	currentWidgetId: null,
	codeEditors: {},
	queryValidated: false,
	lastValidatedQuery: '',
	lastValidationResults: null,
	resultsData: [],
	filteredData: [],
	currentPage: 1,
	rowsPerPage: 10,
	sortColumn: null,
	sortDirection: 'asc',
	searchTerm: '',
	currentStatusFilter: 'all',
	queryEditEnabled: false,
	queryDirty: false,
	suppressQueryChange: false,
	assetEditEnabled: {
		template: false,
		styles: false,
		scripts: false,
		no_results_template: false
	}
};

// ── newlySavedWidgetId ──────────────────────────────────────────────
// Tracks the ID of a newly created widget for the post-save modal
let newlySavedWidgetId = null;
export function getNewlySavedWidgetId() { return newlySavedWidgetId; }
export function setNewlySavedWidgetId( value ) { newlySavedWidgetId = value; }

// ── _autoBuildQuery ─────────────────────────────────────────────────
// Tracks the latest auto-built query so we can revert to it when editing is disabled
let _autoBuildQuery = '';
export function getAutoBuildQuery() { return _autoBuildQuery; }
export function setAutoBuildQuery( value ) { _autoBuildQuery = value; }

// ── _autoBuildAssets ────────────────────────────────────────────────
let _autoBuildAssets = {
	template: '',
	styles: '',
	scripts: ''
};
export function getAutoBuildAssets() { return _autoBuildAssets; }
export function setAutoBuildAssets( value ) { _autoBuildAssets = value; }

// ── _pendingCodeEditType ────────────────────────────────────────────
let _pendingCodeEditType = '';
export function getPendingCodeEditType() { return _pendingCodeEditType; }
export function setPendingCodeEditType( value ) { _pendingCodeEditType = value; }

// ── pendingNavigationUrl ────────────────────────────────────────────
// Pending navigation URL set when a link click is intercepted while the editor is open
let pendingNavigationUrl = null;
export function getPendingNavigationUrl() { return pendingNavigationUrl; }
export function setPendingNavigationUrl( value ) { pendingNavigationUrl = value; }

// ── _autoBuiltQueryValid ────────────────────────────────────────────
// Tracks whether the auto-built (non-custom) query is currently valid.
// Set true by dwm:builderValidated, false by dwm:builderInvalidated, and seeded
// optimistically by resetQueryEditorInteractionState when a saved query is present.
let _autoBuiltQueryValid = false;
export function getAutoBuiltQueryValid() { return _autoBuiltQueryValid; }
export function setAutoBuiltQueryValid( value ) { _autoBuiltQueryValid = value; }

// ── Utilities ───────────────────────────────────────────────────────

export function escapeHtml( value ) {
	return String( value )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' )
		.replace( /'/g, '&#039;' );
}
