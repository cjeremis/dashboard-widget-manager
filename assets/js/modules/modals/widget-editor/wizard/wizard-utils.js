/**
 * Dashboard Widget Manager - Wizard Utilities Module
 *
 * Pure utility / helper functions used across the creation wizard.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import {
	wizardState,
	STEP_MODULES,
	CACHE_KEY_COLUMNS_PREFIX,
	CACHE_DURATION,
	getJoinContextPrimaryTable,
	getJoinContextExistingJoins
} from './wizard-state.js';

const $ = jQuery;

// ── String Helpers ───────────────────────────────────────────────────────────

export function escAttr( str ) {
	return String( str ).replace( /&/g, '&amp;' ).replace( /"/g, '&quot;' ).replace( /'/g, '&#39;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
}

// ── Type Compatibility ───────────────────────────────────────────────────────

/**
 * Check if two column types are compatible for joining
 */
export function areTypesCompatible( type1, type2 ) {
	if ( ! type1 || ! type2 ) {
		return false;
	}

	// Normalize types to lowercase
	const t1 = type1.toLowerCase();
	const t2 = type2.toLowerCase();

	// Exact match
	if ( t1 === t2 ) {
		return true;
	}

	// Integer types can join with each other
	const intTypes = [ 'int', 'bigint', 'tinyint', 'smallint', 'mediumint', 'integer' ];
	const isInt1 = intTypes.some( function( it ) { return t1.includes( it ); });
	const isInt2 = intTypes.some( function( it ) { return t2.includes( it ); });
	if ( isInt1 && isInt2 ) {
		return true;
	}

	// String types can join with each other
	const stringTypes = [ 'varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext' ];
	const isString1 = stringTypes.some( function( st ) { return t1.includes( st ); });
	const isString2 = stringTypes.some( function( st ) { return t2.includes( st ); });
	if ( isString1 && isString2 ) {
		return true;
	}

	// Date/time types can join with each other
	const dateTypes = [ 'datetime', 'timestamp', 'date', 'time' ];
	const isDate1 = dateTypes.some( function( dt ) { return t1.includes( dt ); });
	const isDate2 = dateTypes.some( function( dt ) { return t2.includes( dt ); });
	if ( isDate1 && isDate2 ) {
		return true;
	}

	// Decimal/float types can join with each other
	const decimalTypes = [ 'decimal', 'float', 'double', 'real' ];
	const isDecimal1 = decimalTypes.some( function( dec ) { return t1.includes( dec ); });
	const isDecimal2 = decimalTypes.some( function( dec ) { return t2.includes( dec ); });
	if ( isDecimal1 && isDecimal2 ) {
		return true;
	}

	return false;
}

/**
 * Build compatibility map between primary and join table columns
 */
export function buildCompatibilityMap( primaryColumns, joinColumns ) {
	const map = {};

	primaryColumns.forEach( function( primaryCol ) {
		map[ primaryCol.name ] = [];

		joinColumns.forEach( function( joinCol ) {
			if ( areTypesCompatible( primaryCol.type, joinCol.type ) ) {
				map[ primaryCol.name ].push( joinCol.name );
			}
		});
	});

	return map;
}

/**
 * Check if any columns are compatible between two tables
 */
export function hasAnyCompatibleColumns( primaryColumns, joinColumns ) {
	for ( let i = 0; i < primaryColumns.length; i++ ) {
		for ( let j = 0; j < joinColumns.length; j++ ) {
			if ( areTypesCompatible( primaryColumns[i].type, joinColumns[j].type ) ) {
				return true;
			}
		}
	}
	return false;
}

// ── Step Module Helpers ──────────────────────────────────────────────────────

/**
 * Get active display type step module
 */
export function getActiveStepModule() {
	const mode = wizardState.data.displayMode;
	return mode && STEP_MODULES[ mode ] ? STEP_MODULES[ mode ] : null;
}

// ── Column Cache ─────────────────────────────────────────────────────────────

/**
 * Get cached columns for a specific table
 */
export function getCachedColumnsForTable( table ) {
	if ( ! table ) {
		return [];
	}

	try {
		const cacheKey = CACHE_KEY_COLUMNS_PREFIX + table;
		const cached = sessionStorage.getItem( cacheKey );
		if ( ! cached ) {
			return [];
		}

		const data = JSON.parse( cached );
		const now = Date.now();

		// Check if cache is expired
		if ( now - data.timestamp > CACHE_DURATION ) {
			return [];
		}

		return data.columns || [];
	} catch ( e ) {
		return [];
	}
}

/**
 * Cache columns for a specific table
 */
export function cacheColumnsForTable( table, columns ) {
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

// ── Table / Column Helpers ───────────────────────────────────────────────────

/**
 * Get available tables for join dropdown (excludes primary table and already-used tables)
 */
export function getAvailableTablesForJoin( currentIndex ) {
	// Get cached tables from the builder dropdown (populated by wizard-step-table.js)
	const allTables = [];
	$( '#dwm-builder-table option' ).each( function() {
		const val = $( this ).val();
		if ( val ) {
			allTables.push( val );
		}
	});

	const primaryTable = getJoinContextPrimaryTable();
	const joins = getJoinContextExistingJoins();

	// Filter out primary table and tables used in other joins
	return allTables.filter( function( table ) {
		if ( table === primaryTable ) {
			return false;
		}

		// Check if table is used in another join (not the current one)
		for ( let i = 0; i < joins.length; i++ ) {
			if ( i !== currentIndex && joins[ i ].table === table ) {
				return false;
			}
		}

		return true;
	});
}

/**
 * Get all available columns from primary table and joined tables
 */
export function getAllAvailableColumns() {
	const columns = [];

	// Get primary table columns
	const primaryTable = ( wizardState.data.stepConfig && wizardState.data.stepConfig.table ) || '';
	const primaryColumns = ( wizardState.data.stepConfig && wizardState.data.stepConfig.availableColumns ) || [];

	primaryColumns.forEach( function( col ) {
		const colName = col.name || col;
		columns.push( primaryTable + '.' + colName );
	});

	// Get joined table columns
	const joins = wizardState.data.joins || [];
	joins.forEach( function( join ) {
		const joinColumns = getCachedColumnsForTable( join.table ) || [];
		joinColumns.forEach( function( col ) {
			const colName = col.name || col;
			columns.push( join.table + '.' + colName );
		});
	});

	return columns;
}

/**
 * Build enriched stepConfig that includes join table columns in availableColumns
 * so numeric type detection works for columns from joined tables.
 */
export function getEnrichedStepConfig() {
	const stepConfig = wizardState.data.stepConfig || {};
	const selectedColumns = Array.isArray( stepConfig.columns ) ? stepConfig.columns.slice() : [];
	const columnSet = {};
	selectedColumns.forEach( function( col ) {
		columnSet[ col ] = true;
	} );
	const byName = {};

	( stepConfig.availableColumns || [] ).forEach( function( col ) {
		byName[ col.name || col ] = col;
	} );

	( wizardState.data.joins || [] ).forEach( function( join ) {
		const joinColumns = getCachedColumnsForTable( join.table ) || [];
		joinColumns.forEach( function( col ) {
			const bareName = col.name || col;
			const fullName = join.table + '.' + bareName;
			if ( ! columnSet[ fullName ] ) {
				selectedColumns.push( fullName );
				columnSet[ fullName ] = true;
			}
			if ( ! byName[ fullName ] ) {
				byName[ fullName ] = Object.assign( {}, col, { name: fullName } );
			}
		} );
	} );

	return Object.assign( {}, stepConfig, {
		columns: selectedColumns,
		availableColumns: Object.values( byName )
	} );
}

/**
 * Build column options for order modal dropdown
 */
export function buildOrderColumnOptions( selectedVal ) {
	let html = '<option value="">\u2014 select column \u2014</option>';

	const primaryTable   = ( wizardState.data.stepConfig && wizardState.data.stepConfig.table ) || '';
	const primaryColumns = ( wizardState.data.stepConfig && wizardState.data.stepConfig.availableColumns ) || [];

	if ( primaryTable && primaryColumns.length > 0 ) {
		html += '<optgroup label="' + escAttr( primaryTable ) + '">';
		primaryColumns.forEach( function( col ) {
			const colName  = col.name || col;
			const fullName = primaryTable + '.' + colName;
			const sel      = fullName === selectedVal ? ' selected' : '';
			html += '<option value="' + escAttr( fullName ) + '"' + sel + '>' + escAttr( colName ) + '</option>';
		} );
		html += '</optgroup>';
	}

	( wizardState.data.joins || [] ).forEach( function( join ) {
		const joinColumns = getCachedColumnsForTable( join.table ) || [];
		if ( joinColumns.length > 0 ) {
			html += '<optgroup label="' + escAttr( join.table ) + '">';
			joinColumns.forEach( function( col ) {
				const colName  = col.name || col;
				const fullName = join.table + '.' + colName;
				const sel      = fullName === selectedVal ? ' selected' : '';
				html += '<option value="' + escAttr( fullName ) + '"' + sel + '>' + escAttr( colName ) + '</option>';
			} );
			html += '</optgroup>';
		}
	} );

	return html;
}

// ── Display Mode Helpers ─────────────────────────────────────────────────────

export function isChartDisplayMode( mode ) {
	return mode === 'bar' || mode === 'line' || mode === 'pie' || mode === 'doughnut';
}

export function isDataDisplayMode( mode ) {
	return mode === 'table' || mode === 'list' || mode === 'button' || mode === 'card-list';
}

export function getStep3TableSelector( mode ) {
	const map = {
		table: '#dwm-wizard-table-select',
		bar: '#dwm-wizard-bar-table-select',
		line: '#dwm-wizard-line-table-select',
		pie: '#dwm-wizard-pie-table-select',
		doughnut: '#dwm-wizard-doughnut-table-select',
		list: '#dwm-wizard-list-table-select',
		button: '#dwm-wizard-button-table-select',
		'card-list': '#dwm-wizard-card-list-table-select'
	};
	return map[ mode ] || '#dwm-wizard-table-select';
}
