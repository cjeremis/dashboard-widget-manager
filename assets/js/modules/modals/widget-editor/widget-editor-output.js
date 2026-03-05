/**
 * Dashboard Widget Manager - Widget Editor Output Tab Module
 *
 * Manages the Output Tab: link builder, column formatters,
 * output_config persistence, and template/CSS regeneration with
 * override protection via the "Allow Editing" toggles.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { state } from './widget-editor-state.js';
import { escapeHtml } from './widget-editor-state.js';
import {
	getCurrentDisplayMode,
	getSelectedThemeForMode,
	applyAutoThemeAssets,
	getColumnAliasesFromDOM
} from './widget-editor-assets.js';
import { isChartDisplayMode } from './theme-assets.js';

const $ = jQuery;

// ── Formatter type definitions (mode-aware + data-type-aware) ────────

const FORMATTER_TYPES = {
	text:    { label: 'Text',    icon: 'dashicons-editor-textcolor' },
	date:    { label: 'Date',    icon: 'dashicons-calendar-alt' },
	number:  { label: 'Number',  icon: 'dashicons-editor-ol' },
	excerpt: { label: 'Excerpt', icon: 'dashicons-editor-justify' },
	'case':  { label: 'Case',    icon: 'dashicons-editor-spellcheck' }
};

const FORMATTER_OPTIONS = {
	text: [],
	date: [
		{ key: 'format', label: 'Date format', type: 'select', choices: [
			{ value: 'Y-m-d',       label: '2025-01-15' },
			{ value: 'm/d/Y',       label: '01/15/2025' },
			{ value: 'd/m/Y',       label: '15/01/2025' },
			{ value: 'M j, Y',      label: 'Jan 15, 2025' },
			{ value: 'F j, Y',      label: 'January 15, 2025' },
			{ value: 'relative',    label: 'Relative (e.g. 3 days ago)' }
		] }
	],
	number: [
		{ key: 'decimals',         label: 'Decimal places',    type: 'number', min: 0, max: 6, defaultVal: 0 },
		{ key: 'thousands_sep',    label: 'Thousands separator', type: 'select', choices: [
			{ value: ',', label: 'Comma (1,000)' },
			{ value: '.', label: 'Period (1.000)' },
			{ value: ' ', label: 'Space (1 000)' },
			{ value: '',  label: 'None (1000)' }
		] },
		{ key: 'prefix', label: 'Prefix (e.g. $)', type: 'text', placeholder: '$' },
		{ key: 'suffix', label: 'Suffix (e.g. %)', type: 'text', placeholder: '%' }
	],
	excerpt: [
		{ key: 'length', label: 'Max characters', type: 'number', min: 10, max: 500, defaultVal: 100 },
		{ key: 'suffix', label: 'Truncation suffix', type: 'text', placeholder: '...', defaultVal: '...' }
	],
	'case': [
		{ key: 'transform', label: 'Transform', type: 'select', choices: [
			{ value: 'uppercase',  label: 'UPPERCASE' },
			{ value: 'lowercase',  label: 'lowercase' },
			{ value: 'capitalize', label: 'Capitalize Each Word' },
			{ value: 'sentence',   label: 'Sentence case' }
		] }
	]
};

// ── Internal state ───────────────────────────────────────────────────

let _outputConfig = {
	display_mode: 'table',
	theme: 'default',
	columns: []
};

// ── Public API ───────────────────────────────────────────────────────

/**
 * Initialize the output tab: bind events.
 */
export function initOutputTab() {
	bindOutputEvents();
}

/**
 * Get the current output_config as a plain object.
 */
export function getOutputConfig() {
	syncOutputConfigFromDOM();
	return JSON.parse( JSON.stringify( _outputConfig ) );
}

/**
 * Set the output_config from a saved widget (or reset to defaults).
 */
export function setOutputConfig( config ) {
	if ( config && typeof config === 'object' ) {
		_outputConfig = JSON.parse( JSON.stringify( config ) );
	} else {
		_outputConfig = { display_mode: 'table', theme: 'default', columns: [] };
	}
	persistOutputConfigToHidden();
}

/**
 * Render link builder and formatter rows for the given columns.
 * Called when columns change or when loading a saved widget.
 */
export function renderOutputControls( columns, existingConfig ) {
	if ( existingConfig ) {
		_outputConfig = JSON.parse( JSON.stringify( existingConfig ) );
	} else {
		// Sync current DOM state back before re-rendering so in-progress edits are preserved.
		syncOutputConfigFromDOM();
	}

	renderLinkBuilder( columns );
	renderFormatterBuilder( columns );
	persistOutputConfigToHidden();
}

/**
 * Reset all output controls to empty state.
 */
export function resetOutputControls() {
	_outputConfig = { display_mode: 'table', theme: 'default', columns: [] };
	$( '#dwm-output-link-builder-list' ).html(
		'<p class="dwm-no-columns-message">Select columns on the Data tab first.</p>'
	);
	$( '#dwm-output-formatter-list' ).html(
		'<p class="dwm-no-columns-message">Select columns on the Data tab first.</p>'
	);
	persistOutputConfigToHidden();
}

/**
 * Show/hide the link builder and formatter sections based on display mode.
 */
export function updateOutputSectionsForMode( mode ) {
	const isChart = isChartDisplayMode( mode );
	$( '#dwm-output-link-builder-section' ).toggle( ! isChart );
	$( '#dwm-output-formatter-section' ).toggle( ! isChart );
}

/**
 * Trigger template/CSS regeneration from current output config.
 * Respects the "Allow Editing" toggles on the Template/Styles tabs.
 */
export function regenerateAssetsFromOutput() {
	const mode  = getCurrentDisplayMode();
	const theme = getSelectedThemeForMode( mode );
	applyAutoThemeAssets( mode, theme, false );
}

// ── Link Builder ─────────────────────────────────────────────────────

function renderLinkBuilder( columns ) {
	const $list = $( '#dwm-output-link-builder-list' );

	if ( ! columns || columns.length === 0 ) {
		$list.html( '<p class="dwm-no-columns-message">Select columns on the Data tab first.</p>' );
		return;
	}

	$list.empty();

	columns.forEach( function( col ) {
		const colConfig = getColumnConfig( col );
		const linkEnabled   = colConfig.link && colConfig.link.enabled;
		const linkUrl       = ( colConfig.link && colConfig.link.url ) || '';
		const openInNewTab  = colConfig.link ? colConfig.link.open_in_new_tab !== false : true;

		const $row = $( '<div class="dwm-link-builder-row"></div>' ).attr( 'data-column', col );

		// Column label + enable toggle
		$row.append(
			'<div class="dwm-link-builder-header">' +
				'<label class="dwm-link-builder-col-label">' + escapeHtml( col ) + '</label>' +
				'<div class="dwm-link-builder-toggle">' +
					'<label class="dwm-toggle">' +
						'<input type="checkbox" class="dwm-link-enable-toggle" data-column="' + escapeHtml( col ) + '"' + ( linkEnabled ? ' checked' : '' ) + '>' +
						'<span class="dwm-toggle-slider"></span>' +
					'</label>' +
					'<span class="dwm-link-toggle-label">Enable Link</span>' +
				'</div>' +
			'</div>'
		);

		// Link config (URL + open in new tab)
		const $config = $(
			'<div class="dwm-link-builder-config"' + ( linkEnabled ? '' : ' style="display:none"' ) + '>' +
				'<div class="dwm-link-builder-url-row">' +
					'<label>URL</label>' +
					'<input type="text" class="dwm-link-url-input dwm-input-text" data-column="' + escapeHtml( col ) + '" ' +
						'value="' + escapeHtml( linkUrl ) + '" ' +
						'placeholder="https://example.com/item/{' + escapeHtml( col ) + '}">' +
					'<p class="description">Use <code>{column_name}</code> to insert row values. E.g. <code>/post/{ID}</code></p>' +
					'<div class="dwm-link-vars-row">' +
						'<span class="dwm-column-field-label">Variables:</span>' +
						'<div class="dwm-link-vars-list">' + buildVariableButtonsMarkup( columns, col ) + '</div>' +
					'</div>' +
				'</div>' +
				'<div class="dwm-link-builder-newtab-row">' +
					'<label class="dwm-toggle">' +
						'<input type="checkbox" class="dwm-link-newtab-toggle" data-column="' + escapeHtml( col ) + '"' + ( openInNewTab ? ' checked' : '' ) + '>' +
						'<span class="dwm-toggle-slider"></span>' +
					'</label>' +
					'<span class="dwm-link-newtab-label">Open in new tab</span>' +
				'</div>' +
			'</div>'
		);

		$row.append( $config );
		$list.append( $row );
	} );
}

function buildVariableButtonsMarkup( columns, targetColumn ) {
	let html = '';
	( columns || [] ).forEach( function( colName ) {
		html += '<button type="button" class="dwm-link-var-insert" data-var="' + escapeHtml( colName ) + '" data-target-column="' + escapeHtml( targetColumn ) + '" title="Insert {' + escapeHtml( colName ) + '}">{' + escapeHtml( colName ) + '}</button>';
	} );
	return html;
}

// ── Formatter Builder ────────────────────────────────────────────────

function renderFormatterBuilder( columns ) {
	const $list = $( '#dwm-output-formatter-list' );

	if ( ! columns || columns.length === 0 ) {
		$list.html( '<p class="dwm-no-columns-message">Select columns on the Data tab first.</p>' );
		return;
	}

	$list.empty();

	columns.forEach( function( col ) {
		const colConfig   = getColumnConfig( col );
		const fmtType     = ( colConfig.formatter && colConfig.formatter.type ) || 'text';
		const fmtOptions  = ( colConfig.formatter && colConfig.formatter.options ) || {};

		const $row = $( '<div class="dwm-formatter-row"></div>' ).attr( 'data-column', col );

		// Header: column name + type selector
		const $header = $(
			'<div class="dwm-formatter-header">' +
				'<label class="dwm-formatter-col-label">' + escapeHtml( col ) + '</label>' +
				'<select class="dwm-formatter-type-select dwm-select" data-column="' + escapeHtml( col ) + '">' +
					buildFormatterTypeOptions( fmtType ) +
				'</select>' +
			'</div>'
		);
		$row.append( $header );

		// Options panel (dynamic per type)
		const $optionsPanel = $( '<div class="dwm-formatter-options" data-column="' + escapeHtml( col ) + '"></div>' );
		renderFormatterOptions( $optionsPanel, fmtType, fmtOptions );
		$row.append( $optionsPanel );

		$list.append( $row );
	} );
}

function buildFormatterTypeOptions( selectedType ) {
	let html = '';
	Object.keys( FORMATTER_TYPES ).forEach( function( key ) {
		const selected = key === selectedType ? ' selected' : '';
		html += '<option value="' + key + '"' + selected + '>' + FORMATTER_TYPES[ key ].label + '</option>';
	} );
	return html;
}

function renderFormatterOptions( $container, type, existingOptions ) {
	$container.empty();
	const optDefs = FORMATTER_OPTIONS[ type ] || [];

	if ( optDefs.length === 0 ) {
		$container.html( '<p class="dwm-formatter-no-options">No additional options for this format.</p>' );
		return;
	}

	optDefs.forEach( function( opt ) {
		const currentVal = existingOptions[ opt.key ] !== undefined
			? existingOptions[ opt.key ]
			: ( opt.defaultVal !== undefined ? opt.defaultVal : '' );

		const col = $container.attr( 'data-column' );
		let inputHtml = '';

		if ( opt.type === 'select' ) {
			inputHtml = '<select class="dwm-formatter-option-input dwm-select" data-column="' + escapeHtml( col ) + '" data-option-key="' + opt.key + '">';
			opt.choices.forEach( function( c ) {
				const sel = String( currentVal ) === String( c.value ) ? ' selected' : '';
				inputHtml += '<option value="' + escapeHtml( c.value ) + '"' + sel + '>' + escapeHtml( c.label ) + '</option>';
			} );
			inputHtml += '</select>';
		} else if ( opt.type === 'number' ) {
			inputHtml = '<input type="number" class="dwm-formatter-option-input dwm-input-number" ' +
				'data-column="' + escapeHtml( col ) + '" data-option-key="' + opt.key + '" ' +
				'value="' + escapeHtml( String( currentVal ) ) + '" ' +
				( opt.min !== undefined ? 'min="' + opt.min + '" ' : '' ) +
				( opt.max !== undefined ? 'max="' + opt.max + '" ' : '' ) +
				'>';
		} else {
			inputHtml = '<input type="text" class="dwm-formatter-option-input dwm-input-text" ' +
				'data-column="' + escapeHtml( col ) + '" data-option-key="' + opt.key + '" ' +
				'value="' + escapeHtml( String( currentVal ) ) + '" ' +
				( opt.placeholder ? 'placeholder="' + escapeHtml( opt.placeholder ) + '"' : '' ) +
				'>';
		}

		$container.append(
			'<div class="dwm-formatter-option-group">' +
				'<label>' + escapeHtml( opt.label ) + '</label>' +
				inputHtml +
			'</div>'
		);
	} );
}

// ── Column config helpers ────────────────────────────────────────────

function getColumnConfig( columnKey ) {
	if ( ! _outputConfig.columns ) {
		_outputConfig.columns = [];
	}
	let found = null;
	_outputConfig.columns.forEach( function( c ) {
		if ( c.key === columnKey ) {
			found = c;
		}
	} );
	if ( ! found ) {
		found = {
			key: columnKey,
			alias: '',
			visible: true,
			link: { enabled: false, url: '', open_in_new_tab: true },
			formatter: { type: 'text', options: {} }
		};
		_outputConfig.columns.push( found );
	}
	return found;
}

function ensureColumnConfig( columnKey ) {
	return getColumnConfig( columnKey );
}

// ── DOM ↔ Config sync ────────────────────────────────────────────────

function syncOutputConfigFromDOM() {
	// Sync display_mode and theme from the editor's canonical sources
	_outputConfig.display_mode = getCurrentDisplayMode();
	_outputConfig.theme        = getSelectedThemeForMode( _outputConfig.display_mode );

	// Sync aliases from alias list
	const aliases = getColumnAliasesFromDOM();

	// Sync link builder state
	$( '#dwm-output-link-builder-list .dwm-link-builder-row' ).each( function() {
		const col    = $( this ).attr( 'data-column' );
		const config = ensureColumnConfig( col );

		config.link = {
			enabled:         $( this ).find( '.dwm-link-enable-toggle' ).is( ':checked' ),
			url:             $( this ).find( '.dwm-link-url-input' ).val() || '',
			open_in_new_tab: $( this ).find( '.dwm-link-newtab-toggle' ).is( ':checked' )
		};

		// Sync alias
		if ( aliases && aliases[ col ] ) {
			config.alias = aliases[ col ];
		}
	} );

	// Sync formatter state
	$( '#dwm-output-formatter-list .dwm-formatter-row' ).each( function() {
		const col    = $( this ).attr( 'data-column' );
		const config = ensureColumnConfig( col );
		const type   = $( this ).find( '.dwm-formatter-type-select' ).val() || 'text';
		const options = {};

		$( this ).find( '.dwm-formatter-option-input' ).each( function() {
			const key = $( this ).attr( 'data-option-key' );
			let val   = $( this ).val();
			// Convert numeric strings to numbers for number inputs
			if ( $( this ).attr( 'type' ) === 'number' && val !== '' ) {
				val = parseFloat( val );
			}
			if ( key ) {
				options[ key ] = val;
			}
		} );

		config.formatter = { type: type, options: options };
	} );

	persistOutputConfigToHidden();
}

function persistOutputConfigToHidden() {
	$( '#dwm-output-config' ).val( JSON.stringify( _outputConfig ) );
}

// ── Event binding ────────────────────────────────────────────────────

function bindOutputEvents() {
	// Link enable toggle
	$( document ).on( 'change', '.dwm-link-enable-toggle', function() {
		const $row    = $( this ).closest( '.dwm-link-builder-row' );
		const enabled = $( this ).is( ':checked' );
		$row.find( '.dwm-link-builder-config' ).toggle( enabled );
		syncOutputConfigFromDOM();
		regenerateAssetsFromOutput();
	} );

	// Link URL input
	$( document ).on( 'input', '.dwm-link-url-input', function() {
		syncOutputConfigFromDOM();
		regenerateAssetsFromOutput();
	} );

	// Insert template variable token into the link URL.
	$( document ).on( 'click', '.dwm-link-var-insert', function( e ) {
		e.preventDefault();
		const varName = $( this ).attr( 'data-var' );
		const targetColumn = $( this ).attr( 'data-target-column' );
		const $input = $( '.dwm-link-url-input[data-column="' + targetColumn + '"]' );

		if ( ! $input.length ) {
			return;
		}

		const input = $input[ 0 ];
		const currentVal = $input.val() || '';
		const token = '{' + varName + '}';
		const start = input.selectionStart !== null && input.selectionStart !== undefined ? input.selectionStart : currentVal.length;
		const end = input.selectionEnd !== null && input.selectionEnd !== undefined ? input.selectionEnd : currentVal.length;

		$input.val( currentVal.slice( 0, start ) + token + currentVal.slice( end ) );
		$input.trigger( 'input' );
		input.focus();
		const caretPos = start + token.length;
		input.setSelectionRange( caretPos, caretPos );
	} );

	// Link new tab toggle
	$( document ).on( 'change', '.dwm-link-newtab-toggle', function() {
		syncOutputConfigFromDOM();
		regenerateAssetsFromOutput();
	} );

	// Formatter type change — re-render options panel
	$( document ).on( 'change', '.dwm-formatter-type-select', function() {
		const col  = $( this ).attr( 'data-column' );
		const type = $( this ).val();
		const $panel = $( '.dwm-formatter-options[data-column="' + col + '"]' );
		renderFormatterOptions( $panel, type, {} );
		syncOutputConfigFromDOM();
		regenerateAssetsFromOutput();
	} );

	// Formatter option change
	$( document ).on( 'change input', '.dwm-formatter-option-input', function() {
		syncOutputConfigFromDOM();
		regenerateAssetsFromOutput();
	} );
}
