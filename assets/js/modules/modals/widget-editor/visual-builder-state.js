/**
 * Dashboard Widget Manager - Visual Builder State Module
 *
 * Shared state, session cache helpers, selectors, and utility functions
 * for the visual builder.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

// ── Builder state ────────────────────────────────────────────────────────────

export const state = {
	displayMode:       'table',
	table:             '',
	availableColumns:  [],
	selectedColumns:   [],
	joins:             [],
	joinColumns:       {},
	conditions:        [],
	orders:            [],
	limit:             10,
	noLimit:           false,
	chartLabelColumn:  '',
	chartDataColumns:  [],
	chartTitle:        '',
	chartShowLegend:   true,
	chartTheme:        '',
	tableTheme:        'default',
};

// ── Private let variables with getter/setter access ──────────────────────────

let _codeEditors = null;
let _switchTab   = null;
let _pendingTableChange       = '';
let _pendingDisplayModeChange = '';
let _buildTimer  = null;
let _isBuilding  = false;

export function getCodeEditors()                  { return _codeEditors; }
export function setCodeEditors( v )               { _codeEditors = v; }
export function getSwitchTab()                    { return _switchTab; }
export function setSwitchTab( v )                 { _switchTab = v; }
export function getPendingTableChange()           { return _pendingTableChange; }
export function setPendingTableChange( v )        { _pendingTableChange = v; }
export function getPendingDisplayModeChange()     { return _pendingDisplayModeChange; }
export function setPendingDisplayModeChange( v )  { _pendingDisplayModeChange = v; }
export function getBuildTimer()                   { return _buildTimer; }
export function setBuildTimer( v )                { _buildTimer = v; }
export function getIsBuilding()                   { return _isBuilding; }
export function setIsBuilding( v )                { _isBuilding = v; }

// ── Selectors ────────────────────────────────────────────────────────────────

export const DISPLAY_MODE_SELECTOR = 'input[name="dwm_display_mode"]';

export function getDisplayMode() {
	return $( DISPLAY_MODE_SELECTOR + ':checked' ).val() || 'table';
}

export function setDisplayMode( mode ) {
	$( DISPLAY_MODE_SELECTOR + `[value="${ mode }"]` ).prop( 'checked', true );
}

// ── Session cache ────────────────────────────────────────────────────────────

const CACHE_KEY_TABLES          = 'dwm_tables_cache';
const CACHE_KEY_COLUMNS_PREFIX  = 'dwm_columns_cache_';
const CACHE_KEY_QUERY           = 'dwm_query_cache';
const CACHE_DURATION            = 60 * 60 * 1000;

export function getCachedTables() {
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

export function cacheTables( tables ) {
	try {
		sessionStorage.setItem( CACHE_KEY_TABLES, JSON.stringify( { tables, timestamp: Date.now() } ) );
	} catch ( e ) {}
}

export function getCachedColumns( table ) {
	try {
		const cacheKey = CACHE_KEY_COLUMNS_PREFIX + table;
		const cached   = sessionStorage.getItem( cacheKey );
		if ( ! cached ) return null;
		const data = JSON.parse( cached );
		if ( Date.now() - data.timestamp > CACHE_DURATION ) {
			sessionStorage.removeItem( cacheKey );
			return null;
		}
		return data.columns;
	} catch ( e ) {
		return null;
	}
}

export function cacheColumns( table, columns ) {
	try {
		sessionStorage.setItem(
			CACHE_KEY_COLUMNS_PREFIX + table,
			JSON.stringify( { columns, timestamp: Date.now() } )
		);
	} catch ( e ) {}
}

export function cacheQuery( sql ) {
	try {
		sessionStorage.setItem( CACHE_KEY_QUERY, JSON.stringify( { sql, timestamp: Date.now() } ) );
	} catch ( e ) {}
}

export function getCachedQuery() {
	try {
		const cached = sessionStorage.getItem( CACHE_KEY_QUERY );
		if ( ! cached ) return null;
		return JSON.parse( cached ).sql || null;
	} catch ( e ) {
		return null;
	}
}

// ── Helpers ──────────────────────────────────────────────────────────────────

export function escHtml( str ) {
	return String( str )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' );
}

export function escAttr( str ) {
	return String( str )
		.replace( /"/g, '&quot;' )
		.replace( /'/g, '&#39;' );
}

export function isNumericColumnType( columnType ) {
	if ( ! columnType ) return false;
	const type = String( columnType ).toLowerCase();
	const numericTypes = [ 'int', 'bigint', 'tinyint', 'smallint', 'mediumint', 'integer', 'decimal', 'float', 'double', 'real', 'numeric' ];
	return numericTypes.some( function( token ) {
		return type.indexOf( token ) !== -1;
	} );
}
