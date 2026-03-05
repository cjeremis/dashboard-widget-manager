/**
 * Dashboard Widget Manager - Join Validator Utility
 *
 * Validates SQL join configurations to prevent common errors and invalid queries.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

/**
 * Validate a new join configuration against existing joins
 *
 * @param {Object} newJoin - New join configuration { type, table, local_col, foreign_col }
 * @param {Array} existingJoins - Array of existing join configurations
 * @param {string} primaryTable - Primary table name
 * @return {Object} { isValid: boolean, error: string|null, errorType: string|null }
 */
export function validateJoin( newJoin, existingJoins, primaryTable ) {
	const errors = [];

	// 1. Check for required fields
	if ( ! newJoin.type || ! newJoin.table || ! newJoin.local_col || ! newJoin.foreign_col ) {
		return {
			isValid: false,
			error: 'All join fields are required.',
			errorType: 'REQUIRED_FIELDS'
		};
	}

	// 2. Check if joining table to itself (self-join not supported)
	if ( newJoin.table === primaryTable ) {
		return {
			isValid: false,
			error: 'Cannot join a table to itself. Self-joins are not supported in the wizard.',
			errorType: 'SELF_JOIN'
		};
	}

	// 3. Check for duplicate join (same table already joined)
	const duplicateTable = existingJoins.find( join => join.table === newJoin.table );
	if ( duplicateTable ) {
		return {
			isValid: false,
			error: `The table "${newJoin.table}" is already joined. You cannot join the same table multiple times.`,
			errorType: 'DUPLICATE_TABLE',
			details: {
				existingJoin: duplicateTable
			}
		};
	}

	// 4. Check for exact duplicate join configuration
	const exactDuplicate = existingJoins.find( join =>
		join.type === newJoin.type &&
		join.table === newJoin.table &&
		join.local_col === newJoin.local_col &&
		join.foreign_col === newJoin.foreign_col
	);

	if ( exactDuplicate ) {
		return {
			isValid: false,
			error: 'This exact join configuration already exists.',
			errorType: 'EXACT_DUPLICATE'
		};
	}

	// 5. Check if local column belongs to a valid source (primary table or previously joined table)
	const localTableColumn = parseColumnReference( newJoin.local_col );
	if ( ! isColumnSourceValid( localTableColumn.table, primaryTable, existingJoins ) ) {
		return {
			isValid: false,
			error: `The primary column "${newJoin.local_col}" references a table that hasn't been joined yet. Join columns must reference the primary table or previously joined tables.`,
			errorType: 'INVALID_COLUMN_SOURCE',
			suggestion: 'Make sure the primary column references the main table or a table you\'ve already joined.'
		};
	}

	// 6. Check if foreign column belongs to the table being joined
	const foreignTableColumn = parseColumnReference( newJoin.foreign_col );
	if ( foreignTableColumn.table !== newJoin.table ) {
		return {
			isValid: false,
			error: `The join column "${newJoin.foreign_col}" must belong to the table being joined (${newJoin.table}).`,
			errorType: 'FOREIGN_COLUMN_MISMATCH',
			suggestion: `Select a column from ${newJoin.table} for the join column.`
		};
	}

	// 7. Check for circular dependencies
	const circularDependency = detectCircularDependency( newJoin, existingJoins, primaryTable );
	if ( circularDependency ) {
		return {
			isValid: false,
			error: 'This join would create a circular dependency, which is not allowed.',
			errorType: 'CIRCULAR_DEPENDENCY',
			details: circularDependency
		};
	}

	// All validations passed
	return {
		isValid: true,
		error: null,
		errorType: null
	};
}

/**
 * Parse a column reference into table and column name
 *
 * @param {string} columnRef - Column reference (e.g., "wp_posts.ID" or "ID")
 * @return {Object} { table: string|null, column: string }
 */
function parseColumnReference( columnRef ) {
	if ( ! columnRef ) {
		return { table: null, column: null };
	}

	const parts = columnRef.split( '.' );
	if ( parts.length === 2 ) {
		return { table: parts[0], column: parts[1] };
	}

	return { table: null, column: columnRef };
}

/**
 * Check if a column source is valid (exists in primary table or previously joined tables)
 *
 * @param {string} sourceTable - Table name from column reference
 * @param {string} primaryTable - Primary table name
 * @param {Array} existingJoins - Array of existing joins
 * @return {boolean} True if valid source
 */
function isColumnSourceValid( sourceTable, primaryTable, existingJoins ) {
	// If no table specified, assume it's from primary table
	if ( ! sourceTable ) {
		return true;
	}

	// Check if it's the primary table
	if ( sourceTable === primaryTable ) {
		return true;
	}

	// Check if it's a previously joined table
	return existingJoins.some( join => join.table === sourceTable );
}

/**
 * Detect circular dependencies in join chain
 *
 * @param {Object} newJoin - New join to add
 * @param {Array} existingJoins - Existing joins
 * @param {string} primaryTable - Primary table name
 * @return {Object|null} Circular dependency info or null if none detected
 */
function detectCircularDependency( newJoin, existingJoins, primaryTable ) {
	// Build a dependency graph
	const dependencies = {};

	// Add primary table as root
	dependencies[ primaryTable ] = [];

	// Add existing joins to graph
	existingJoins.forEach( join => {
		const localCol = parseColumnReference( join.local_col );
		const sourceTable = localCol.table || primaryTable;

		if ( ! dependencies[ sourceTable ] ) {
			dependencies[ sourceTable ] = [];
		}
		dependencies[ sourceTable ].push( join.table );

		if ( ! dependencies[ join.table ] ) {
			dependencies[ join.table ] = [];
		}
	});

	// Add new join to graph
	const newLocalCol = parseColumnReference( newJoin.local_col );
	const newSourceTable = newLocalCol.table || primaryTable;

	if ( ! dependencies[ newSourceTable ] ) {
		dependencies[ newSourceTable ] = [];
	}
	dependencies[ newSourceTable ].push( newJoin.table );

	// Check for cycles using DFS
	const visited = new Set();
	const recursionStack = new Set();

	function hasCycle( table ) {
		if ( recursionStack.has( table ) ) {
			return { found: true, cycle: Array.from( recursionStack ) };
		}

		if ( visited.has( table ) ) {
			return { found: false };
		}

		visited.add( table );
		recursionStack.add( table );

		const neighbors = dependencies[ table ] || [];
		for ( const neighbor of neighbors ) {
			const result = hasCycle( neighbor );
			if ( result.found ) {
				return result;
			}
		}

		recursionStack.delete( table );
		return { found: false };
	}

	const cycleResult = hasCycle( primaryTable );
	if ( cycleResult.found ) {
		return {
			cycle: cycleResult.cycle,
			message: 'Circular dependency detected: ' + cycleResult.cycle.join( ' → ' )
		};
	}

	return null;
}

/**
 * Validate all joins together for consistency
 *
 * @param {Array} joins - All join configurations
 * @param {string} primaryTable - Primary table name
 * @return {Object} { isValid: boolean, errors: Array }
 */
export function validateAllJoins( joins, primaryTable ) {
	const errors = [];

	if ( ! joins || joins.length === 0 ) {
		return { isValid: true, errors: [] };
	}

	// Check each join incrementally
	for ( let i = 0; i < joins.length; i++ ) {
		const join = joins[i];
		const previousJoins = joins.slice( 0, i );
		const validation = validateJoin( join, previousJoins, primaryTable );

		if ( ! validation.isValid ) {
			errors.push({
				index: i,
				join: join,
				error: validation.error,
				errorType: validation.errorType
			});
		}
	}

	return {
		isValid: errors.length === 0,
		errors: errors
	};
}

/**
 * Get a user-friendly error message with suggestions
 *
 * @param {Object} validation - Validation result
 * @return {string} Formatted error message
 */
export function getValidationErrorMessage( validation ) {
	if ( validation.isValid ) {
		return '';
	}

	let message = validation.error;

	if ( validation.suggestion ) {
		message += '\n\nSuggestion: ' + validation.suggestion;
	}

	if ( validation.details ) {
		if ( validation.details.existingJoin ) {
			const existing = validation.details.existingJoin;
			message += '\n\nExisting join: ' + existing.type + ' JOIN ' + existing.table +
				' ON ' + existing.local_col + ' = ' + existing.foreign_col;
		}
	}

	return message;
}
