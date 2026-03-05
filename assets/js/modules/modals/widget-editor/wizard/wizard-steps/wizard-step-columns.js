/**
 * Dashboard Widget Manager - Wizard Step 9: Column Aliases, Links & Formatting
 *
 * Handles column name customization, reordering, link building with
 * template variable support, and per-column formatting options.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { wizardState } from '../wizard-state.js';

const $ = jQuery;

let columnsList = [];
let isInitialized = false;
const ALIAS_ALLOWED_REGEX = /^[a-zA-Z0-9_ ]+$/;

// Formatter type options keyed by display mode
const FORMATTER_OPTIONS = {
	text:    { label: 'Text (default)', options: {} },
	date:    { label: 'Date',           options: { format: 'Y-m-d' } },
	number:  { label: 'Number',         options: { decimals: 0, thousands_sep: ',', prefix: '', suffix: '' } },
	excerpt: { label: 'Excerpt',        options: { length: 100, suffix: '...' } },
	'case':  { label: 'Case Transform', options: { transform: 'uppercase' } }
};

/**
 * Initialize step 9 (column aliases + links + formatters)
 */
export function init() {
	if ( isInitialized ) {
		return;
	}
	isInitialized = true;

	// Toggle link controls
	$( document ).on( 'change', '.dwm-column-link-toggle', function() {
		const index = parseInt( $( this ).data( 'index' ), 10 );
		const enabled = $( this ).is( ':checked' );
		if ( columnsList[ index ] ) {
			if ( ! columnsList[ index ].link ) {
				columnsList[ index ].link = { enabled: false, url: '', open_in_new_tab: true };
			}
			columnsList[ index ].link.enabled = enabled;
		}
		$( this ).closest( '.dwm-column-config-row' ).find( '.dwm-column-link-fields' ).toggle( enabled );
	});

	// Link URL input
	$( document ).on( 'input', '.dwm-column-link-url', function() {
		const index = parseInt( $( this ).data( 'index' ), 10 );
		if ( columnsList[ index ] && columnsList[ index ].link ) {
			columnsList[ index ].link.url = $( this ).val();
		}
	});

	// Open in new tab toggle
	$( document ).on( 'change', '.dwm-column-link-newtab', function() {
		const index = parseInt( $( this ).data( 'index' ), 10 );
		if ( columnsList[ index ] && columnsList[ index ].link ) {
			columnsList[ index ].link.open_in_new_tab = $( this ).is( ':checked' );
		}
	});

	// Formatter type change
	$( document ).on( 'change', '.dwm-column-formatter-type', function() {
		const index = parseInt( $( this ).data( 'index' ), 10 );
		const type = $( this ).val();
		if ( columnsList[ index ] ) {
			columnsList[ index ].formatter = {
				type: type,
				options: Object.assign( {}, FORMATTER_OPTIONS[ type ] ? FORMATTER_OPTIONS[ type ].options : {} )
			};
		}
		const $row = $( this ).closest( '.dwm-column-config-row' );
		renderFormatterOptions( $row, index );
	});

	// Formatter option inputs (delegated)
	$( document ).on( 'input change', '.dwm-formatter-option', function() {
		const index = parseInt( $( this ).data( 'index' ), 10 );
		const optKey = $( this ).data( 'option' );
		if ( columnsList[ index ] && columnsList[ index ].formatter ) {
			columnsList[ index ].formatter.options[ optKey ] = $( this ).val();
		}
	});

	// Insert template variable into link URL
	$( document ).on( 'click', '.dwm-link-var-insert', function( e ) {
		e.preventDefault();
		const varName = $( this ).data( 'var' );
		const index = parseInt( $( this ).data( 'index' ), 10 );
		const $urlInput = $( '#dwm-column-link-url-' + index );
		const currentVal = $urlInput.val();
		const cursorPos = $urlInput[ 0 ].selectionStart || currentVal.length;
		const token = '{' + varName + '}';
		$urlInput.val( currentVal.slice( 0, cursorPos ) + token + currentVal.slice( cursorPos ) );
		$urlInput.trigger( 'input' );
		// Refocus and position cursor after inserted token
		$urlInput[ 0 ].focus();
		const newPos = cursorPos + token.length;
		$urlInput[ 0 ].setSelectionRange( newPos, newPos );
	});
}

/**
 * Populate columns from step config
 */
export function populateColumns( stepConfig ) {
	if ( ! stepConfig || ! stepConfig.columns ) {
		return;
	}

	columnsList = stepConfig.columns.map( function( col ) {
		const colName = col.name || col;
		return {
			original: colName,
			alias: colName.replace( /^.*\./, '' ),
			link: { enabled: false, url: '', open_in_new_tab: true },
			formatter: { type: 'text', options: {} }
		};
	});

	renderColumnsList();
}

/**
 * Get list of all available column names for template variables
 */
function getAvailableColumns() {
	const cols = [];
	if ( wizardState.data.stepConfig && wizardState.data.stepConfig.columns ) {
		wizardState.data.stepConfig.columns.forEach( function( c ) {
			cols.push( c.name || c );
		});
	}
	return cols;
}

/**
 * Render the columns configuration list
 */
function renderColumnsList() {
	const $list = $( '#dwm-wizard-columns-config-list' );
	$list.empty();

	if ( columnsList.length === 0 ) {
		$list.html( '<p class="dwm-no-columns-message">No columns selected. Please return to previous steps to select columns.</p>' );
		return;
	}

	const displayMode = wizardState.data.displayMode || 'table';
	const availableCols = getAvailableColumns();

	columnsList.forEach( function( col, index ) {
		const linkEnabled = col.link && col.link.enabled;
		const linkUrl = ( col.link && col.link.url ) || '';
		const linkNewTab = col.link ? col.link.open_in_new_tab !== false : true;
		const formatterType = ( col.formatter && col.formatter.type ) || 'text';

		// Build template variables dropdown
		let varsHtml = '';
		availableCols.forEach( function( v ) {
			varsHtml += '<button type="button" class="dwm-link-var-insert" data-var="' + escapeHtml( v ) + '" data-index="' + index + '" title="Insert {' + escapeHtml( v ) + '}">{' + escapeHtml( v ) + '}</button>';
		});

		// Build formatter type select
		let formatterSelectHtml = '<select class="dwm-column-formatter-type dwm-select" data-index="' + index + '">';
		Object.keys( FORMATTER_OPTIONS ).forEach( function( key ) {
			const sel = key === formatterType ? ' selected' : '';
			formatterSelectHtml += '<option value="' + key + '"' + sel + '>' + FORMATTER_OPTIONS[ key ].label + '</option>';
		});
		formatterSelectHtml += '</select>';

		const $row = $(
			'<div class="dwm-column-config-row dwm-column-config-row-extended" data-index="' + index + '">' +
				'<span class="dwm-column-drag-handle dashicons dashicons-menu"></span>' +
				'<div class="dwm-column-config-info">' +
					// Row 1: Column name + Alias
					'<div class="dwm-column-config-field">' +
						'<label class="dwm-column-field-label">Column:</label>' +
						'<span class="dwm-column-original-name">' + escapeHtml( col.original ) + '</span>' +
					'</div>' +
					'<div class="dwm-column-config-field">' +
						'<label class="dwm-column-field-label" for="dwm-column-alias-' + index + '">Display name:</label>' +
						'<input type="text" id="dwm-column-alias-' + index + '" class="dwm-column-alias-input" data-index="' + index + '" value="' + escapeHtml( col.alias ) + '" placeholder="Display name">' +
					'</div>' +

					// Row 2: Add Link toggle + controls
					'<div class="dwm-column-config-field dwm-column-link-row">' +
						'<label class="dwm-column-field-label">Add Link:</label>' +
						'<label class="dwm-toggle-switch dwm-toggle-switch--sm">' +
							'<input type="checkbox" class="dwm-column-link-toggle" data-index="' + index + '"' + ( linkEnabled ? ' checked' : '' ) + '>' +
							'<span class="dwm-toggle-slider"></span>' +
						'</label>' +
						'<button type="button" class="dwm-help-icon-btn dwm-docs-trigger" data-open-modal="dwm-docs-modal" data-docs-page="feature-column-links" aria-label="View help for column links" title="View help for column links"><span class="dashicons dashicons-editor-help"></span></button>' +
					'</div>' +
					'<div class="dwm-column-link-fields" style="' + ( linkEnabled ? '' : 'display:none' ) + '">' +
						'<div class="dwm-column-config-field">' +
							'<label class="dwm-column-field-label" for="dwm-column-link-url-' + index + '">Link URL:</label>' +
							'<input type="text" id="dwm-column-link-url-' + index + '" class="dwm-column-link-url dwm-column-alias-input" data-index="' + index + '" value="' + escapeHtml( linkUrl ) + '" placeholder="https://">' +
						'</div>' +
						'<div class="dwm-column-config-field">' +
							'<label class="dwm-column-field-label">Open in new tab:</label>' +
							'<label class="dwm-toggle-switch dwm-toggle-switch--sm">' +
								'<input type="checkbox" class="dwm-column-link-newtab" data-index="' + index + '"' + ( linkNewTab ? ' checked' : '' ) + '>' +
								'<span class="dwm-toggle-slider"></span>' +
							'</label>' +
						'</div>' +
						( varsHtml ? '<div class="dwm-link-vars-row"><span class="dwm-column-field-label">Variables:</span><div class="dwm-link-vars-list">' + varsHtml + '</div></div>' : '' ) +
					'</div>' +

					// Row 3: Formatter
					'<div class="dwm-column-config-field dwm-column-formatter-row">' +
						'<label class="dwm-column-field-label">Format:</label>' +
						formatterSelectHtml +
					'</div>' +
					'<div class="dwm-column-formatter-options" id="dwm-formatter-options-' + index + '"></div>' +
				'</div>' +
			'</div>'
		);
		$list.append( $row );

		// Render formatter-specific options
		renderFormatterOptions( $row, index );
	});

	// Initialize sortable
	if ( typeof $.fn.sortable === 'function' ) {
		$list.sortable({
			handle: '.dwm-column-drag-handle',
			axis: 'y',
			update: function() {
				reorderColumns();
			}
		});
	}
}

/**
 * Render formatter-specific option inputs
 */
function renderFormatterOptions( $row, index ) {
	const col = columnsList[ index ];
	if ( ! col || ! col.formatter ) return;

	const $container = $row.find( '#dwm-formatter-options-' + index );
	$container.empty();

	const type = col.formatter.type;
	const opts = col.formatter.options || {};

	if ( type === 'date' ) {
		$container.html(
			'<div class="dwm-formatter-option-row">' +
				'<label class="dwm-column-field-label">Format:</label>' +
				'<select class="dwm-formatter-option dwm-select" data-index="' + index + '" data-option="format">' +
					'<option value="Y-m-d"' + ( opts.format === 'Y-m-d' ? ' selected' : '' ) + '>YYYY-MM-DD</option>' +
					'<option value="m/d/Y"' + ( opts.format === 'm/d/Y' ? ' selected' : '' ) + '>MM/DD/YYYY</option>' +
					'<option value="d/m/Y"' + ( opts.format === 'd/m/Y' ? ' selected' : '' ) + '>DD/MM/YYYY</option>' +
					'<option value="M j, Y"' + ( opts.format === 'M j, Y' ? ' selected' : '' ) + '>Mon DD, YYYY</option>' +
					'<option value="F j, Y"' + ( opts.format === 'F j, Y' ? ' selected' : '' ) + '>Month DD, YYYY</option>' +
					'<option value="relative"' + ( opts.format === 'relative' ? ' selected' : '' ) + '>Relative (e.g. 3 days ago)</option>' +
				'</select>' +
			'</div>'
		);
	}

	if ( type === 'number' ) {
		$container.html(
			'<div class="dwm-formatter-option-row">' +
				'<label class="dwm-column-field-label">Decimals:</label>' +
				'<input type="number" class="dwm-formatter-option dwm-input-number" data-index="' + index + '" data-option="decimals" value="' + ( opts.decimals || 0 ) + '" min="0" max="10" style="width:80px">' +
			'</div>' +
			'<div class="dwm-formatter-option-row">' +
				'<label class="dwm-column-field-label">Thousands sep:</label>' +
				'<select class="dwm-formatter-option dwm-select" data-index="' + index + '" data-option="thousands_sep">' +
					'<option value=","' + ( opts.thousands_sep === ',' || ! opts.thousands_sep ? ' selected' : '' ) + '>, (comma)</option>' +
					'<option value="."' + ( opts.thousands_sep === '.' ? ' selected' : '' ) + '>. (period)</option>' +
					'<option value=" "' + ( opts.thousands_sep === ' ' ? ' selected' : '' ) + '>(space)</option>' +
					'<option value=""' + ( opts.thousands_sep === '' ? ' selected' : '' ) + '>(none)</option>' +
				'</select>' +
			'</div>' +
			'<div class="dwm-formatter-option-row">' +
				'<label class="dwm-column-field-label">Prefix:</label>' +
				'<input type="text" class="dwm-formatter-option dwm-column-alias-input" data-index="' + index + '" data-option="prefix" value="' + escapeHtml( opts.prefix || '' ) + '" placeholder="e.g. $" style="width:80px">' +
			'</div>' +
			'<div class="dwm-formatter-option-row">' +
				'<label class="dwm-column-field-label">Suffix:</label>' +
				'<input type="text" class="dwm-formatter-option dwm-column-alias-input" data-index="' + index + '" data-option="suffix" value="' + escapeHtml( opts.suffix || '' ) + '" placeholder="e.g. %" style="width:80px">' +
			'</div>'
		);
	}

	if ( type === 'excerpt' ) {
		$container.html(
			'<div class="dwm-formatter-option-row">' +
				'<label class="dwm-column-field-label">Max length:</label>' +
				'<input type="number" class="dwm-formatter-option dwm-input-number" data-index="' + index + '" data-option="length" value="' + ( opts.length || 100 ) + '" min="10" max="1000" style="width:100px">' +
			'</div>' +
			'<div class="dwm-formatter-option-row">' +
				'<label class="dwm-column-field-label">Suffix:</label>' +
				'<input type="text" class="dwm-formatter-option dwm-column-alias-input" data-index="' + index + '" data-option="suffix" value="' + escapeHtml( opts.suffix !== undefined ? opts.suffix : '...' ) + '" placeholder="..." style="width:80px">' +
			'</div>'
		);
	}

	if ( type === 'case' ) {
		$container.html(
			'<div class="dwm-formatter-option-row">' +
				'<label class="dwm-column-field-label">Transform:</label>' +
				'<select class="dwm-formatter-option dwm-select" data-index="' + index + '" data-option="transform">' +
					'<option value="uppercase"' + ( opts.transform === 'uppercase' || ! opts.transform ? ' selected' : '' ) + '>UPPERCASE</option>' +
					'<option value="lowercase"' + ( opts.transform === 'lowercase' ? ' selected' : '' ) + '>lowercase</option>' +
					'<option value="capitalize"' + ( opts.transform === 'capitalize' ? ' selected' : '' ) + '>Capitalize Each Word</option>' +
					'<option value="sentence"' + ( opts.transform === 'sentence' ? ' selected' : '' ) + '>Sentence case</option>' +
				'</select>' +
			'</div>'
		);
	}
}

/**
 * Reorder columns based on DOM order
 */
function reorderColumns() {
	const newOrder = [];
	$( '.dwm-column-config-row' ).each( function() {
		const index = parseInt( $( this ).data( 'index' ), 10 );
		newOrder.push( columnsList[ index ] );
	});
	columnsList = newOrder;
	renderColumnsList();
}

/**
 * Update column aliases from inputs
 */
function updateColumnData() {
	$( '.dwm-column-alias-input' ).each( function() {
		const index = parseInt( $( this ).data( 'index' ), 10 );
		if ( columnsList[ index ] && ! $( this ).hasClass( 'dwm-column-link-url' ) ) {
			columnsList[ index ].alias = $( this ).val() || columnsList[ index ].original;
		}
	});
}

/**
 * Get step configuration data (output_config aligned)
 */
export function getStepConfig() {
	updateColumnData();

	return {
		columns: columnsList.map( function( col ) {
			return {
				key: col.original,
				alias: col.alias,
				visible: true,
				link: {
					enabled: !! ( col.link && col.link.enabled ),
					url: ( col.link && col.link.url ) || '',
					open_in_new_tab: col.link ? col.link.open_in_new_tab !== false : true
				},
				formatter: {
					type: ( col.formatter && col.formatter.type ) || 'text',
					options: ( col.formatter && col.formatter.options ) || {}
				}
			};
		})
	};
}

export function validateStep() {
	updateColumnData();

	const seenAliases = {};
	let isValid = true;

	$( '.dwm-column-alias-input' ).removeClass( 'dwm-input-error' );

	columnsList.forEach( function( col, index ) {
		const alias = String( col.alias || '' ).trim();
		const normalized = alias.toLowerCase();
		const $input = $( '#dwm-column-alias-' + index );

		if ( alias && ! ALIAS_ALLOWED_REGEX.test( alias ) ) {
			$input.addClass( 'dwm-input-error' );
			isValid = false;
			return;
		}

		if ( normalized ) {
			if ( seenAliases[ normalized ] !== undefined ) {
				$input.addClass( 'dwm-input-error' );
				$( '#dwm-column-alias-' + seenAliases[ normalized ] ).addClass( 'dwm-input-error' );
				isValid = false;
				return;
			}
			seenAliases[ normalized ] = index;
		}
	} );

	if ( ! isValid ) {
		window.DWMToast.warning( 'Display names must be unique and can only contain letters, numbers, spaces, and underscores.', { title: 'Column Config' } );
	}

	return isValid;
}

/**
 * Clear step data
 */
export function clearStep() {
	columnsList = [];
	$( '#dwm-wizard-columns-config-list' ).empty();
	$( '.dwm-column-alias-input' ).removeClass( 'dwm-input-error' );
}

/**
 * Restore step data from saved config
 */
export function restoreStep( columnsConfig ) {
	if ( ! columnsConfig || ! columnsConfig.columns ) {
		clearStep();
		return;
	}

	columnsList = columnsConfig.columns.map( function( c ) {
		return {
			original: c.key || c.original,
			alias: c.alias || c.key || c.original,
			link: c.link || { enabled: false, url: '', open_in_new_tab: true },
			formatter: c.formatter || { type: 'text', options: {} }
		};
	});

	renderColumnsList();
}

/**
 * Escape HTML
 */
function escapeHtml( text ) {
	const map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return String( text ).replace( /[&<>"']/g, function( m ) {
		return map[ m ];
	});
}
