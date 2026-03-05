/**
 * Dashboard Widget Manager - Widget Editor Assets Module
 *
 * Asset/theme-related helpers extracted from widget-editor.js.
 * Handles theme selection, code-editor read/write, and auto-build asset management.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import {
	state,
	getAutoBuildAssets,
	setAutoBuildAssets,
	getPendingCodeEditType,
	setPendingCodeEditType
} from './widget-editor-state.js';

import {
	buildThemeAssets,
	getDefaultThemeForMode,
	isChartDisplayMode,
	isDataDisplayMode,
	normalizeThemeForMode
} from './theme-assets.js';

const $ = jQuery;

// ── Display-mode helpers ────────────────────────────────────────────

export function updateModeAwareTabLabels( mode ) {
	const isChartMode = isChartDisplayMode( mode );
	$( '#dwm-tab-label-template' ).text( 'Template' );
	$( '#dwm-tab-label-styles' ).text( 'Styles' );
	$( '#dwm-tab-label-scripts' ).text( 'Scripts' );
}

export function getCurrentDisplayMode() {
	return $( 'input[name="dwm_display_mode"]:checked' ).val() || 'table';
}

export function getThemeInputNameByMode( mode ) {
	// Chart modes and data display modes each have their own radio group.
	// All data display modes (table, list, button, card-list) have a dedicated input.
	// Unknown modes fall back to 'table'.
	if ( isChartDisplayMode( mode ) ) {
		return 'dwm_editor_theme_' + mode;
	}
	if ( isDataDisplayMode( mode ) ) {
		return 'dwm_editor_theme_' + mode;
	}
	return 'dwm_editor_theme_table';
}

// ── Theme selection ─────────────────────────────────────────────────

export function getSelectedThemeForMode( mode ) {
	const normalized = isChartDisplayMode( mode ) ? mode : ( isDataDisplayMode( mode ) ? mode : 'table' );
	const theme = $( 'input[name="' + getThemeInputNameByMode( normalized ) + '"]:checked' ).val();
	return normalizeThemeForMode( normalized, theme || getDefaultThemeForMode( normalized ) );
}

export function ensureThemeSelectionForMode( mode, theme ) {
	const normalized = isChartDisplayMode( mode ) ? mode : ( isDataDisplayMode( mode ) ? mode : 'table' );
	const normalizedTheme = normalizeThemeForMode( normalized, theme || getDefaultThemeForMode( normalized ) );
	$( 'input[name="' + getThemeInputNameByMode( normalized ) + '"][value="' + normalizedTheme + '"]' ).prop( 'checked', true );
	return normalizedTheme;
}

export function updateEditorThemeSectionForMode( mode ) {
	const normalized = isChartDisplayMode( mode ) ? mode : ( isDataDisplayMode( mode ) ? mode : 'table' );
	const modeTitleMap = {
		table:     'Table Theme',
		list:      'List Theme',
		button:    'Button Theme',
		'card-list': 'Card List Theme',
		bar:       'Bar Chart Theme',
		line:      'Line Chart Theme',
		pie:       'Pie Chart Theme',
		doughnut:  'Doughnut Chart Theme'
	};
	const modeDescMap = {
		table:     'Choose a table theme preset to auto-populate Template, Styles, and Scripts.',
		list:      'Choose a list theme preset to auto-populate Template, Styles, and Scripts.',
		button:    'Choose a button theme preset to auto-populate Template, Styles, and Scripts.',
		'card-list': 'Choose a card list theme preset to auto-populate Template, Styles, and Scripts.',
		bar:       'Choose a bar chart theme preset to auto-populate Template, Styles, and Scripts.',
		line:      'Choose a line chart theme preset to auto-populate Template, Styles, and Scripts.',
		pie:       'Choose a pie chart theme preset to auto-populate Template, Styles, and Scripts.',
		doughnut:  'Choose a doughnut chart theme preset to auto-populate Template, Styles, and Scripts.'
	};
	const modeDocsMap = {
		table:     'feature-editor-theme-presets',
		list:      'feature-display-list',
		button:    'feature-display-button',
		'card-list': 'feature-display-card-list',
		bar:       'feature-display-bar',
		line:      'feature-display-line',
		pie:       'feature-display-pie',
		doughnut:  'feature-display-doughnut'
	};

	$( '#dwm-editor-theme-title' ).text( modeTitleMap[ normalized ] || modeTitleMap.table );
	$( '#dwm-editor-theme-desc' ).text( modeDescMap[ normalized ] || modeDescMap.table );
	$( '#dwm-editor-theme-help' ).attr( 'data-docs-page', modeDocsMap[ normalized ] || modeDocsMap.table );
	[ 'table', 'list', 'button', 'card-list', 'bar', 'line', 'pie', 'doughnut' ].forEach( function( sectionMode ) {
		$( '#dwm-editor-theme-options-' + sectionMode ).toggle( sectionMode === normalized );
	} );
}

export function persistThemeSelection( mode, theme, triggerBuilderSync ) {
	const normalizedMode  = isChartDisplayMode( mode ) ? mode : ( isDataDisplayMode( mode ) ? mode : 'table' );
	const normalizedTheme = normalizeThemeForMode( normalizedMode, theme || getDefaultThemeForMode( normalizedMode ) );

	let builderConfig = {};
	try {
		builderConfig = JSON.parse( $( '#dwm-builder-config' ).val() || '{}' );
	} catch ( e ) {}
	builderConfig.display_mode = normalizedMode;
	if ( isChartDisplayMode( normalizedMode ) ) {
		builderConfig.chart_theme = normalizedTheme;
		builderConfig.table_theme = '';
		const currentBuilderTheme = $( '#dwm-builder-chart-theme' ).val();
		$( '#dwm-builder-chart-theme' ).val( normalizedTheme );
		if ( triggerBuilderSync && currentBuilderTheme !== normalizedTheme ) {
			$( '#dwm-builder-chart-theme' ).trigger( 'change' );
		}
	} else {
		builderConfig.table_theme = normalizedTheme;
	}
	$( '#dwm-builder-config' ).val( JSON.stringify( builderConfig ) );

	if ( isChartDisplayMode( normalizedMode ) ) {
		try {
			const chartConfig = JSON.parse( $( '#dwm-chart-config' ).val() || '{}' );
			chartConfig.theme = normalizedTheme;
			$( '#dwm-chart-config' ).val( JSON.stringify( chartConfig ) );
		} catch ( e ) {}
	}
}

export function applyAutoThemeAssets( mode, theme, force ) {
	const normalizedMode  = mode || 'table';
	const normalizedTheme = normalizeThemeForMode( normalizedMode, theme || getDefaultThemeForMode( normalizedMode ) );
	const aliases         = ! isChartDisplayMode( normalizedMode ) ? getColumnAliasesFromDOM() : null;

	// Read output config from hidden field to avoid circular dependency with output module.
	let outputConfig = null;
	try {
		const raw = $( '#dwm-output-config' ).val();
		if ( raw ) { outputConfig = JSON.parse( raw ); }
	} catch ( e ) {}

	const assets = buildThemeAssets( normalizedMode, normalizedTheme, aliases, outputConfig );
	const _autoBuildAssets = getAutoBuildAssets();

	[ 'template', 'styles', 'scripts' ].forEach( function( type ) {
		const previousAuto = _autoBuildAssets[ type ] || '';
		const currentValue = getCodeEditorValue( type );
		_autoBuildAssets[ type ] = assets[ type ] || '';

		if ( state.assetEditEnabled[ type ] ) {
			return;
		}

		const canOverwrite = !! force || ! currentValue.trim() || currentValue === previousAuto;
		if ( canOverwrite ) {
			setCodeEditorValue( type, _autoBuildAssets[ type ] );
		}
	} );

	setAutoBuildAssets( _autoBuildAssets );
}

// ── Column aliases ──────────────────────────────────────────────────

export function getColumnAliasesFromDOM() {
	const aliases = {};
	$( '#dwm-output-column-aliases-list .dwm-alias-row' ).each( function() {
		const col   = $( this ).attr( 'data-column' );
		const alias = $( this ).find( '.dwm-alias-input' ).val() || '';
		if ( col ) {
			aliases[ col ] = alias || col;
		}
	} );
	return Object.keys( aliases ).length > 0 ? aliases : null;
}

// ── Code-type mapping helpers ───────────────────────────────────────

export function getCodeTypeFromToggleId( toggleId ) {
	if ( toggleId === 'dwm-template-edit-toggle' ) return 'template';
	if ( toggleId === 'dwm-styles-edit-toggle' ) return 'styles';
	if ( toggleId === 'dwm-scripts-edit-toggle' ) return 'scripts';
	if ( toggleId === 'dwm-no-results-template-edit-toggle' ) return 'no_results_template';
	return '';
}

export function getToggleIdByCodeType( type ) {
	if ( type === 'template' ) return 'dwm-template-edit-toggle';
	if ( type === 'styles' ) return 'dwm-styles-edit-toggle';
	if ( type === 'scripts' ) return 'dwm-scripts-edit-toggle';
	if ( type === 'no_results_template' ) return 'dwm-no-results-template-edit-toggle';
	return '';
}

export function getTextareaIdByCodeType( type ) {
	if ( type === 'template' ) return '#dwm-widget-template';
	if ( type === 'styles' ) return '#dwm-widget-styles';
	if ( type === 'scripts' ) return '#dwm-widget-scripts';
	if ( type === 'no_results_template' ) return '#dwm-widget-no-results-template';
	return '';
}

// ── Code-editor value accessors ─────────────────────────────────────

export function getCodeEditorValue( type ) {
	if ( state.codeEditors[ type ] && state.codeEditors[ type ].codemirror ) {
		return state.codeEditors[ type ].codemirror.getValue() || '';
	}
	const selector = getTextareaIdByCodeType( type );
	return selector ? ( $( selector ).val() || '' ) : '';
}

export function setCodeEditorValue( type, value ) {
	if ( state.codeEditors[ type ] && state.codeEditors[ type ].codemirror ) {
		state.codeEditors[ type ].codemirror.setValue( value || '' );
		return;
	}
	const selector = getTextareaIdByCodeType( type );
	if ( selector ) {
		$( selector ).val( value || '' );
	}
}

export function setCodeEditorReadonly( type, readonly ) {
	if ( state.codeEditors[ type ] && state.codeEditors[ type ].codemirror ) {
		const cm = state.codeEditors[ type ].codemirror;
		cm.setOption( 'readOnly', readonly ? 'nocursor' : false );
		cm.setOption( 'styleActiveLine', ! readonly );
		$( cm.getWrapperElement() ).toggleClass( 'dwm-cm-readonly', !! readonly );
	}

	const selector = getTextareaIdByCodeType( type );
	if ( selector ) {
		$( selector ).prop( 'readonly', !! readonly );
	}
}

// ── Code-editing state management ───────────────────────────────────

export function setCodeEditingEnabled( type, enabled ) {
	if ( ! type ) return;
	state.assetEditEnabled[ type ] = !! enabled;
	setCodeEditorReadonly( type, ! enabled );

	if ( ! enabled ) {
		const _autoBuildAssets = getAutoBuildAssets();
		setCodeEditorValue( type, _autoBuildAssets[ type ] || '' );
	}
}

export function resetCodeEditorInteractionState() {
	[ 'template', 'styles', 'scripts', 'no_results_template' ].forEach( function( type ) {
		state.assetEditEnabled[ type ] = false;
		const toggleId = getToggleIdByCodeType( type );
		if ( toggleId ) {
			$( '#' + toggleId ).prop( 'checked', false );
		}
		setCodeEditorReadonly( type, true );
	} );
}

export function seedAutoAssetSnapshotsFromCurrentValues() {
	const _autoBuildAssets = getAutoBuildAssets();
	_autoBuildAssets.template = getCodeEditorValue( 'template' );
	_autoBuildAssets.styles   = getCodeEditorValue( 'styles' );
	_autoBuildAssets.scripts  = getCodeEditorValue( 'scripts' );
	_autoBuildAssets.no_results_template = getCodeEditorValue( 'no_results_template' );
	setAutoBuildAssets( _autoBuildAssets );
}

export function syncThemeSelectionFromWidget( widget ) {
	let builderConfig = {};
	let chartConfig = {};
	try { builderConfig = JSON.parse( widget.builder_config || '{}' ); } catch ( e ) {}
	try { chartConfig = JSON.parse( widget.chart_config || '{}' ); } catch ( e ) {}
	const configDisplayMode = builderConfig.display_mode || '';
	const hasValidConfigDisplayMode = isDataDisplayMode( configDisplayMode );
	const displayMode = hasValidConfigDisplayMode
		? configDisplayMode
		: ( widget.chart_type && isChartDisplayMode( widget.chart_type ) ? widget.chart_type : 'table' );

	const theme = isChartDisplayMode( displayMode )
		? ( chartConfig.theme || builderConfig.chart_theme || 'classic' )
		: ( builderConfig.table_theme || getDefaultThemeForMode( displayMode ) );

	ensureThemeSelectionForMode( displayMode, theme );
	persistThemeSelection( displayMode, theme, false );
}
