/**
 * Dashboard Widget Manager - Column Validator Utility
 *
 * Provides column data type detection, validation, and input field generation
 * for database column filtering across the plugin.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

/**
 * Parse MySQL column type to extract base type and constraints
 *
 * @param {string} columnType - MySQL column type (e.g., "int(5)", "varchar(255)", "tinyint(1)")
 * @return {Object} Parsed type info: { baseType, length, isUnsigned, precision, scale }
 */
export function parseColumnType( columnType ) {
	if ( ! columnType ) {
		return { baseType: 'text', length: null, isUnsigned: false, precision: null, scale: null };
	}

	const type = columnType.toLowerCase().trim();
	const result = {
		baseType: 'text',
		length: null,
		isUnsigned: type.includes( 'unsigned' ),
		precision: null,
		scale: null
	};

	// Extract length from types like int(5), varchar(255)
	const lengthMatch = type.match( /\((\d+)\)/ );
	if ( lengthMatch ) {
		result.length = parseInt( lengthMatch[1], 10 );
	}

	// Extract precision and scale from decimal types like decimal(10,2)
	const decimalMatch = type.match( /\((\d+),\s*(\d+)\)/ );
	if ( decimalMatch ) {
		result.precision = parseInt( decimalMatch[1], 10 );
		result.scale = parseInt( decimalMatch[2], 10 );
	}

	// Determine base type category
	if ( /^(tinyint\(1\)|bool|boolean)/.test( type ) ) {
		result.baseType = 'boolean';
	} else if ( /^(tinyint|smallint|mediumint|int|integer|bigint)/.test( type ) ) {
		result.baseType = 'integer';
	} else if ( /^(decimal|numeric|float|double|real)/.test( type ) ) {
		result.baseType = 'decimal';
	} else if ( /^(date|datetime|timestamp|time|year)/.test( type ) ) {
		result.baseType = 'date';
	} else if ( /^(char|varchar|text|tinytext|mediumtext|longtext)/.test( type ) ) {
		result.baseType = 'text';
	} else if ( /^(enum|set)/.test( type ) ) {
		result.baseType = 'enum';
	}

	return result;
}

/**
 * Get data category for a column type
 *
 * @param {string} columnType - MySQL column type
 * @return {string} Category: 'boolean', 'integer', 'decimal', 'date', 'text', 'enum'
 */
export function getColumnCategory( columnType ) {
	const parsed = parseColumnType( columnType );
	return parsed.baseType;
}

/**
 * Get appropriate HTML input type for a column
 *
 * @param {string} columnType - MySQL column type
 * @return {string} HTML input type: 'text', 'number', 'date', 'datetime-local', 'select'
 */
export function getInputType( columnType ) {
	const category = getColumnCategory( columnType );

	const typeMap = {
		'boolean': 'select',
		'integer': 'number',
		'decimal': 'number',
		'date': 'date',
		'text': 'text',
		'enum': 'select'
	};

	return typeMap[ category ] || 'text';
}

/**
 * Get validation constraints for a column
 *
 * @param {string} columnType - MySQL column type
 * @return {Object} Constraints: { min, max, maxLength, step, pattern }
 */
export function getValidationConstraints( columnType ) {
	const parsed = parseColumnType( columnType );
	const constraints = {
		min: null,
		max: null,
		maxLength: null,
		step: null,
		pattern: null
	};

	switch ( parsed.baseType ) {
		case 'integer':
			if ( parsed.length ) {
				constraints.maxLength = parsed.length;
				// Calculate max value based on digits (e.g., int(5) = max 99999)
				constraints.max = parseInt( '9'.repeat( parsed.length ), 10 );
			}
			if ( parsed.isUnsigned ) {
				constraints.min = 0;
			}
			constraints.step = 1;
			constraints.pattern = parsed.isUnsigned ? '[0-9]*' : '-?[0-9]*';
			break;

		case 'decimal':
			if ( parsed.scale ) {
				constraints.step = Math.pow( 10, -parsed.scale );
			} else {
				constraints.step = 0.01;
			}
			if ( parsed.isUnsigned ) {
				constraints.min = 0;
			}
			break;

		case 'text':
			if ( parsed.length ) {
				constraints.maxLength = parsed.length;
			}
			break;
	}

	return constraints;
}

/**
 * Convert user-friendly boolean value to SQL condition
 *
 * @param {string} tableName - Table name
 * @param {string} columnName - Column name
 * @param {string} boolValue - User selection: 'TRUE', 'FALSE', 'EMPTY'
 * @param {string} operator - Comparison operator (usually '=')
 * @return {string} SQL condition
 */
export function getBooleanSqlCondition( tableName, columnName, boolValue, operator = '=' ) {
	const fullColumn = tableName + '.' + columnName;

	if ( boolValue === 'EMPTY' ) {
		return fullColumn + ' IS NULL OR ' + fullColumn + ' = \'\'';
	}

	if ( boolValue === 'TRUE' ) {
		return '(' + fullColumn + ' IN (\'TRUE\', \'true\', \'t\', \'yes\', \'1\', 1) OR ' + fullColumn + ' = TRUE)';
	}

	if ( boolValue === 'FALSE' ) {
		return '(' + fullColumn + ' IN (\'FALSE\', \'false\', \'f\', \'no\', \'0\', 0) OR ' + fullColumn + ' = FALSE)';
	}

	return fullColumn + ' ' + operator + ' ' + boolValue;
}

/**
 * Get boolean display value for preview
 *
 * @param {string} boolValue - User selection: 'TRUE', 'FALSE', 'EMPTY'
 * @return {string} Display text
 */
export function getBooleanDisplayValue( boolValue ) {
	const displayMap = {
		'TRUE': 'TRUE (true, t, yes, 1)',
		'FALSE': 'FALSE (false, f, no, 0)',
		'EMPTY': 'NULL or empty'
	};

	return displayMap[ boolValue ] || boolValue;
}

/**
 * Validate value against column constraints
 *
 * @param {string} value - User input value
 * @param {string} columnType - MySQL column type
 * @return {Object} { isValid: boolean, error: string|null }
 */
export function validateValue( value, columnType ) {
	if ( ! value && value !== 0 && value !== '0' ) {
		return { isValid: false, error: 'Value is required' };
	}

	const parsed = parseColumnType( columnType );
	const constraints = getValidationConstraints( columnType );

	// Integer validation
	if ( parsed.baseType === 'integer' ) {
		const numValue = parseInt( value, 10 );

		if ( isNaN( numValue ) ) {
			return { isValid: false, error: 'Value must be a valid integer' };
		}

		if ( constraints.min !== null && numValue < constraints.min ) {
			return { isValid: false, error: 'Value must be at least ' + constraints.min };
		}

		if ( constraints.max !== null && numValue > constraints.max ) {
			return { isValid: false, error: 'Value cannot exceed ' + constraints.max };
		}

		if ( constraints.maxLength && value.toString().replace( '-', '' ).length > constraints.maxLength ) {
			return { isValid: false, error: 'Value cannot exceed ' + constraints.maxLength + ' digits' };
		}
	}

	// Decimal validation
	if ( parsed.baseType === 'decimal' ) {
		const numValue = parseFloat( value );

		if ( isNaN( numValue ) ) {
			return { isValid: false, error: 'Value must be a valid number' };
		}

		if ( constraints.min !== null && numValue < constraints.min ) {
			return { isValid: false, error: 'Value must be at least ' + constraints.min };
		}

		if ( parsed.scale ) {
			const decimalPlaces = ( value.toString().split( '.' )[1] || '' ).length;
			if ( decimalPlaces > parsed.scale ) {
				return { isValid: false, error: 'Value cannot have more than ' + parsed.scale + ' decimal places' };
			}
		}
	}

	// Text validation
	if ( parsed.baseType === 'text' && constraints.maxLength ) {
		if ( value.length > constraints.maxLength ) {
			return { isValid: false, error: 'Value cannot exceed ' + constraints.maxLength + ' characters' };
		}
	}

	return { isValid: true, error: null };
}
