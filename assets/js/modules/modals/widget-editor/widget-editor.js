/**
 * Dashboard Widget Manager - Widget Editor Module (Orchestrator)
 *
 * Core initialization, event binding, and UI setup for the widget editor.
 * Implementation details are split across sub-modules imported below.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { dwmConfirm } from '../../partials/dialog.js';

// Extracted sub-modules
import {
	state,
	getNewlySavedWidgetId,
	setNewlySavedWidgetId,
	setAutoBuildQuery,
	setAutoBuiltQueryValid,
	getPendingCodeEditType,
	setPendingCodeEditType,
	getPendingNavigationUrl,
	setPendingNavigationUrl
} from './widget-editor-state.js';

import {
	registerQueryCallbacks,
	updateQueryStats,
	setQueryEditorLoading,
	clearQueryValidationStatus,
	showQueryValidationStatus,
	updateValidateButtonState,
	setQueryEditingEnabled,
	setQueryText,
	handleQueryContentChanged,
	resetQueryEditorInteractionState,
	getCurrentQueryText,
	validateQuery,
	enforceSelectPrefix
} from './widget-editor-query.js';

import {
	updateQueryPreviewContent,
	renderQueryOutputPreview,
	resetQueryOutputPreview,
	setQueryPreviewTab,
	enableQueryOutputPreview,
	resetQueryPreviewTabs,
	openResultsModal,
	closeResultsModal,
	filterAndRenderResults,
	renderResultsTable
} from './widget-editor-results.js';

import {
	updateModeAwareTabLabels,
	getCurrentDisplayMode,
	getSelectedThemeForMode,
	ensureThemeSelectionForMode,
	updateEditorThemeSectionForMode,
	persistThemeSelection,
	applyAutoThemeAssets,
	getCodeTypeFromToggleId,
	getToggleIdByCodeType,
	setCodeEditingEnabled,
	resetCodeEditorInteractionState,
	seedAutoAssetSnapshotsFromCurrentValues,
	syncThemeSelectionFromWidget,
	getColumnAliasesFromDOM
} from './widget-editor-assets.js';

import {
	initOutputTab,
	getOutputConfig,
	setOutputConfig,
	renderOutputControls,
	resetOutputControls,
	updateOutputSectionsForMode,
	regenerateAssetsFromOutput
} from './widget-editor-output.js';

// Existing module imports
import { initVisualBuilder, populateBuilderFromConfig } from './visual-builder.js';
import { initWizard, showWizard, showWizardWithData, hideWizard, resetWizard, transitionToScratch } from './wizard/wizard.js';
import { isChartDisplayMode } from './theme-assets.js';

const $ = jQuery;

const DEFAULT_NO_RESULTS_TEMPLATE = '<div style="display:flex;align-items:center;justify-content:center;min-height:120px;padding:24px;text-align:center;">\n  <p style="margin:0;font-size:14px;color:#666;">No results found.</p>\n</div>';
const EDITOR_TITLE_HELP_BUTTON_HTML = '<button type="button" class="dwm-switch-to-wizard dwm-docs-trigger" id="dwm-editor-title-help" data-open-modal="dwm-docs-modal" data-docs-page="welcome" title="Open documentation"><span class="dashicons dashicons-book-alt"></span></button>';

function getDefaultNoResultsTemplate() {
	return DEFAULT_NO_RESULTS_TEMPLATE;
}

function normalizeAliasLabel( value ) {
	const raw = String( value || '' );
	const withSpaces = raw.replace( /_/g, ' ' );
	const words = withSpaces.trim().split( /\s+/ ).filter( function( word ) {
		return word.length > 0;
	} );
	if ( words.length === 0 ) {
		return '';
	}
	return words.map( function( word ) {
		return word.charAt( 0 ).toUpperCase() + word.slice( 1 ).toLowerCase();
	} ).join( ' ' );
}

// ── Column aliases ────────────────────────────────────────────────────────────

function renderColumnAliases( columns, existingAliases ) {
	const $list = $( '#dwm-output-column-aliases-list' );
	const aliases = existingAliases || {};

	if ( ! columns || columns.length === 0 ) {
		$list.html( '<p class="dwm-no-columns-message">Select columns on the Data tab first.</p>' );
		return;
	}

	$list.empty();
	columns.forEach( function( col ) {
		const alias = normalizeAliasLabel( aliases[ col ] || '' );
		const $row = $( '<div class="dwm-alias-row"></div>' ).attr( 'data-column', col );
		$row.append( $( '<label class="dwm-alias-label"></label>' ).text( col ) );
		$row.append(
			$( '<input type="text" class="dwm-alias-input dwm-input-text">' )
				.val( alias )
				.attr( 'placeholder', col )
		);
		$list.append( $row );
	} );
}

// Cached aliases and output config used to restore during populateForm flow.
let _pendingColumnAliases = {};
let _pendingOutputConfig = null;

function getColumnAliasesMap() {
	return getColumnAliasesFromDOM();
}

function getSelectedColumns() {
	const cols = [];
	$( '#dwm-builder-columns-list input[type=checkbox]:checked' ).each( function() {
		cols.push( $( this ).val() );
	} );
	return cols;
}

function updateOutputTabSections( mode ) {
	const isChart = isChartDisplayMode( mode );
	$( '#dwm-output-column-aliases-section' ).toggle( ! isChart );
	$( '#dwm-builder-chart-options' ).toggle( isChart );
	updateOutputSectionsForMode( mode || 'table' );
}

function rebuildTemplateWithAliases() {
	const mode = getCurrentDisplayMode();
	if ( isChartDisplayMode( mode ) ) return;
	const theme = getSelectedThemeForMode( mode );
	applyAutoThemeAssets( mode, theme, true );
}

/**
 * Check if widget editor modal is open
 */
function isWidgetEditorOpen() {
	return $('#dwm-widget-editor-modal').hasClass('active');
}

/**
 * Attempt to close widget editor (shows warning modal)
 */
function attemptCloseWidgetEditor() {
	openModal('dwm-confirm-close-editor-modal');
}

/**
 * Reset widget editor to initial state
 */
function resetWidgetEditor() {
	resetWizard();

	$('#dwm-builder-table').val('');
	$('#dwm-builder-columns-section').hide();
	$('#dwm-builder-joins-section').hide();
	$('#dwm-builder-conditions-section').hide();
	$('#dwm-builder-order-section').hide();
	$('#dwm-builder-apply-section').hide();
	$('#dwm-builder-joins-list').empty();
	$('#dwm-builder-conditions-list').empty();
	$('#dwm-builder-columns-list').empty();
	$('#dwm-builder-orders-list').empty();
	$('#dwm-builder-limit-toggle').prop('checked', false);
	$('#dwm-builder-limit').val('10');
	$('.dwm-builder-limit-input').hide();
	$('#dwm-builder-live-status').empty().removeClass('is-building is-valid is-error');
	updateModeAwareTabLabels( 'table' );
	resetCodeEditorInteractionState();
	ensureThemeSelectionForMode( 'table', 'default' );
	updateEditorThemeSectionForMode( 'table' );
	renderColumnAliases( [], {} );
	resetOutputControls();
	updateOutputTabSections( 'table' );
}

/**
 * Initialize widget editor
 */
export function initWidgetEditor() {
	// Wire cross-module dependencies before anything else
	registerQueryCallbacks({
		enableQueryOutputPreview,
		renderQueryOutputPreview,
		resetQueryOutputPreview,
		updateQueryPreviewContent
	});

	bindEvents();
	bindCloseEditorEvents();
	bindNavigationWarning();
	initPostSaveModal();
	initCodeEditors();
	initQueryStats();
	initOutputTab();
	initVisualBuilder(state.codeEditors, switchTab);
	initWizard();

	// Expose cross-bundle callback so modals.js (admin bundle) can call it
	window.attemptCloseWidgetEditor = attemptCloseWidgetEditor;
}

/**
 * Bind close editor confirmation events
 */
function bindCloseEditorEvents() {
	$(document).on('click', '.dwm-confirm-close-editor-cancel', function() {
		$('#dwm-confirm-close-editor-modal').removeClass('active');
		setPendingNavigationUrl( null );
	});

	$(document).on('click', '#dwm-confirm-close-editor-yes', function() {
		resetWidgetEditor();
		$('#dwm-confirm-close-editor-modal').removeClass('active');
		$('#dwm-widget-editor-modal').removeClass('active');
		$('body').removeClass('dwm-modal-open');

		const url = getPendingNavigationUrl();
		if ( url ) {
			setPendingNavigationUrl( null );
			window.location.href = url;
		}
	});
}

/**
 * Intercept page refresh/navigation while the editor modal is open.
 */
function bindNavigationWarning() {
	window.addEventListener('beforeunload', function(e) {
		if (isWidgetEditorOpen()) {
			e.preventDefault();
			e.returnValue = '';
		}
	});

	$(document).on('click', 'a[href]', function(e) {
		if (!isWidgetEditorOpen()) return;

		const href = $(this).attr('href');
		if (!href || href === '#' || href.charAt(0) === '#' || href.startsWith('javascript:')) return;

		e.preventDefault();
		setPendingNavigationUrl( href );
		openModal('dwm-confirm-close-editor-modal');
	});
}

/**
 * Bind all widget editor events
 */
function bindEvents() {
	// Status filter
	$(document).on('click', '.dwm-status-filter', function() {
		$('.dwm-status-filter').removeClass('is-active');
		$(this).addClass('is-active');
		filterWidgetsByStatus($(this).attr('data-filter'));
	});

	// Empty trash
	$(document).on('click', '#dwm-empty-trash-btn', emptyTrash);

	// Tab switching
	$(document).on('click', '.dwm-tab-link', function(e) {
		e.preventDefault();
		if ($(this).is('[data-pro-locked]')) {
			openModal('dwm-pro-upgrade-modal');
			return;
		}
		if ($(this).hasClass('is-disabled')) {
			return;
		}
		const tab = $(this).data('tab');
		switchTab(tab);
	});

	// Tab lock triggers
	$(document).on('input', '#dwm-widget-name', updateTabLocks);
	$(document).on('change', '#dwm-builder-table', function() {
		setTimeout(updateTabLocks, 0);
	});
	$(document).on('change', '#dwm-builder-columns-list input[type=checkbox]', updateTabLocks);
	$(document).on('dwm:builderDataCleared dwm:builderBuilding dwm:builderRestored', updateTabLocks);

	// Create widget
	$(document).on('click', '.dwm-create-widget', function(e) {
		e.preventDefault();
		openCreateModal();
	});

	// Edit widget
	$(document).on('click', '.dwm-edit-widget', function(e) {
		e.preventDefault();
		const widgetId = $(this).data('widget-id');
		openEditModal(widgetId);
	});

	// Delete widget
	$(document).on('click', '.dwm-delete-widget', function(e) {
		e.preventDefault();
		const widgetId = $(this).data('widget-id');
		deleteWidget(widgetId);
	});

	// Toggle widget
	$(document).on('change', '.dwm-toggle-widget', function() {
		const widgetId = $(this).data('widget-id');
		const enabled = $(this).is(':checked');
		toggleWidget(widgetId, enabled);
	});

	// Validate query
	$(document).on('click', '#dwm-validate-query', function(e) {
		e.preventDefault();
		validateQuery();
	});

	$( document ).on( 'change', '#dwm-query-edit-toggle', function() {
		if ( $( this ).is( ':checked' ) ) {
			$( this ).prop( 'checked', false );
			openModal( 'dwm-confirm-sql-edit-modal' );
		} else {
			setQueryEditingEnabled( false );
		}
	} );

	$( document ).on( 'click', '#dwm-confirm-sql-edit-yes', function() {
		closeModal( 'dwm-confirm-sql-edit-modal' );
		$( '#dwm-query-edit-toggle' ).prop( 'checked', true );
		setQueryEditingEnabled( true );
	} );

	$( document ).on( 'click', '.dwm-confirm-sql-edit-close', function() {
		closeModal( 'dwm-confirm-sql-edit-modal' );
		$( '#dwm-query-edit-toggle' ).prop( 'checked', false );
	} );

	// Query Logging toggle — validate WP_DEBUG / WP_DEBUG_LOG before enabling
	$( document ).on( 'change', '#dwm-enable-query-logging', function() {
		if ( ! $( this ).is( ':checked' ) ) return;

		const debugOk    = parseInt( dwmAdminVars.wpDebugEnabled,    10 ) === 1;
		const debugLogOk = parseInt( dwmAdminVars.wpDebugLogEnabled, 10 ) === 1;

		if ( debugOk && debugLogOk ) return;

		// Prevent the toggle from enabling
		$( this ).prop( 'checked', false );

		// Populate the missing requirements list
		const $list = $( '#dwm-query-logging-missing-list' ).empty();
		if ( ! debugOk ) {
			$list.append( '<li><code>define( \'WP_DEBUG\', true );</code></li>' );
		}
		if ( ! debugLogOk ) {
			$list.append( '<li><code>define( \'WP_DEBUG_LOG\', true );</code></li>' );
		}

		openModal( 'dwm-query-logging-warning-modal' );
	} );

	$( document ).on( 'click', '#dwm-query-logging-warning-enable-anyway', function() {
		closeModal( 'dwm-query-logging-warning-modal' );
		$( '#dwm-enable-query-logging' ).prop( 'checked', true );
	} );

	$( document ).on( 'click', '#dwm-query-logging-warning-cancel', function() {
		closeModal( 'dwm-query-logging-warning-modal' );
	} );

	// Template/CSS/JS/No-Results editing toggles
	$( document ).on( 'change', '#dwm-template-edit-toggle, #dwm-styles-edit-toggle, #dwm-scripts-edit-toggle, #dwm-no-results-template-edit-toggle', function() {
		const type = getCodeTypeFromToggleId( $( this ).attr( 'id' ) );
		if ( ! type ) return;

		if ( $( this ).is( ':checked' ) ) {
			setPendingCodeEditType( type );
			$( this ).prop( 'checked', false );
			openModal( 'dwm-confirm-code-edit-modal' );
		} else {
			setCodeEditingEnabled( type, false );
		}
	} );

	$( document ).on( 'click', '#dwm-confirm-code-edit-yes', function() {
		const type = getPendingCodeEditType();
		if ( ! type ) return;
		setPendingCodeEditType( '' );
		closeModal( 'dwm-confirm-code-edit-modal' );
		$( '#' + getToggleIdByCodeType( type ) ).prop( 'checked', true );
		setCodeEditingEnabled( type, true );
	} );

	$( document ).on( 'click', '.dwm-confirm-code-edit-close', function() {
		const pending = getPendingCodeEditType();
		if ( pending ) {
			$( '#' + getToggleIdByCodeType( pending ) ).prop( 'checked', false );
		}
		setPendingCodeEditType( '' );
		closeModal( 'dwm-confirm-code-edit-modal' );
	} );

	// Theme selection in the template tab.
	$( document ).on( 'change', 'input[name^="dwm_editor_theme_"]', function() {
		const mode = getCurrentDisplayMode();
		const selectedTheme = getSelectedThemeForMode( mode );
		applyAutoThemeAssets( mode, selectedTheme, true );
		persistThemeSelection( mode, selectedTheme, true );
	} );

	// Query tab preview pane switching
	$(document).on('click', '.dwm-query-preview-tab', function(e) {
		e.preventDefault();
		setQueryPreviewTab( $(this).data('tab') );
	});

	// Template tab sub-tab switching
	$(document).on('click', '.dwm-template-preview-tab', function(e) {
		e.preventDefault();
		var tab = $(this).data('template-tab');
		$('.dwm-template-preview-tab').removeClass('active');
		$(this).addClass('active');
		$('.dwm-template-preview-pane').removeClass('active');
		$('.dwm-template-preview-pane[data-template-pane="' + tab + '"]').addClass('active');

		// Refresh CodeMirror when switching to a pane
		if ( tab === 'main' && state.codeEditors.template ) {
			state.codeEditors.template.codemirror.refresh();
		} else if ( tab === 'no-results' && state.codeEditors.no_results_template ) {
			state.codeEditors.no_results_template.codemirror.refresh();
		}
	});

	// Switch to wizard from manual mode
	$(document).on('click', '#dwm-switch-to-wizard', function(e) {
		e.preventDefault();
		openModal('dwm-confirm-wizard-modal');
	});

	$(document).on('click', '#dwm-confirm-wizard-yes', function() {
		closeModal('dwm-confirm-wizard-modal');
		switchToWizardMode();
	});

	$(document).on('click', '.dwm-confirm-wizard-close', function() {
		closeModal('dwm-confirm-wizard-modal');
	});

	// Switch to scratch from wizard mode
	$(document).on('click', '.dwm-switch-to-scratch', function(e) {
		e.preventDefault();
		openModal('dwm-confirm-scratch-modal');
	});

	$(document).on('click', '#dwm-confirm-scratch-yes', function() {
		closeModal('dwm-confirm-scratch-modal');
		transitionToScratch();
		switchTab('builder');
	});

	$(document).on('click', '.dwm-confirm-scratch-close', function() {
		closeModal('dwm-confirm-scratch-modal');
	});

	// Creation method selection
	$(document).on('change', 'input[name="dwm_creation_method"]', function() {
		const method = $(this).val();
		$('#dwm-creation-method').val(method);
		$('#dwm-creation-method-step').hide();

		if (method === 'wizard') {
			$('#dwm-widget-form').hide();
			$('#dwm-widget-editor-modal .dwm-modal-footer').hide();
			$('#dwm-wizard-footer').show();
			$('#dwm-switch-to-scratch').show();
			$('#dwm-switch-to-wizard').hide();
			showWizard();
		} else {
			hideWizard();
			$('#dwm-widget-form').show();
			$('#dwm-widget-editor-modal .dwm-modal-footer').show();
			$('#dwm-switch-to-wizard').show();
			switchTab('builder');
		}
	});

	// Submit form
	$(document).on('submit', '#dwm-widget-form', function(e) {
		e.preventDefault();
		saveWidget();
	});

	// Description "Display on Widget" toggle
	$(document).on('change', '.dwm-show-description-toggle', function() {
		const isOn = $(this).is(':checked');
		const $group = $(this).closest('.dwm-form-group');
		const $textarea = $group.find('textarea');
		if (!$textarea.data('optional-placeholder')) {
			$textarea.data('optional-placeholder', $textarea.attr('placeholder'));
		}
		$textarea
			.prop('required', isOn)
			.attr('placeholder', isOn ? 'Required' : $textarea.data('optional-placeholder'));
		$group.find('.dwm-desc-required-asterisk').toggle(isOn);
	});

	// Caching toggle.
	$(document).on('change', '#dwm-enable-caching', function() {
		updateCacheDurationVisibility();
	});

	// Results preview
	$(document).on('click', '.dwm-preview-btn', function(e) {
		e.preventDefault();
		openResultsModal();
	});

	// Close results modal
	$(document).on('click', '#dwm-results-modal-close', function(e) {
		e.preventDefault();
		closeResultsModal();
	});

	$(document).on('click', '#dwm-results-modal', function(e) {
		if (e.target === this) {
			closeResultsModal();
		}
	});

	$(document).on('keydown', function(e) {
		if (e.key === 'Escape' && $('#dwm-results-modal').hasClass('active')) {
			closeResultsModal();
		}
	});

	// Results search and pagination
	$(document).on('input', '#dwm-results-search', function() {
		state.searchTerm = $(this).val().toLowerCase();
		state.currentPage = 1;
		filterAndRenderResults();
	});

	$(document).on('click', '#dwm-results-thead th', function() {
		const column = $(this).data('column');
		if (state.sortColumn === column) {
			state.sortDirection = state.sortDirection === 'asc' ? 'desc' : 'asc';
		} else {
			state.sortColumn = column;
			state.sortDirection = 'asc';
		}
		filterAndRenderResults();
	});

	$(document).on('click', '#dwm-results-pagination button', function() {
		const page = $(this).data('page');
		if (page && page !== state.currentPage) {
			state.currentPage = page;
			renderResultsTable();
		}
	});

	// Track the latest auto-built SQL
	$( document ).on( 'dwm:autoBuildUpdated', function( _e, sql ) {
		setAutoBuildQuery( sql || '' );
		if ( ! state.queryEditEnabled ) {
			setQueryText( sql || '' );
		}
	} );

	$( document ).on( 'dwm:autoBuildConfigUpdated', function( _e, config ) {
		const displayMode = config && config.display_mode ? config.display_mode : getCurrentDisplayMode();
		const selectedTheme = getSelectedThemeForMode( displayMode );
		const effectiveTheme = isChartDisplayMode( displayMode )
			? ( config && config.chart_theme ? config.chart_theme : selectedTheme )
			: ( config && config.table_theme ? config.table_theme : selectedTheme );

		ensureThemeSelectionForMode( displayMode, effectiveTheme );
		applyAutoThemeAssets( displayMode, effectiveTheme, false );
		persistThemeSelection( displayMode, effectiveTheme, false );
	} );

	// Builder auto-validation events (fired by visual-builder.js).
	$(document).on('dwm:builderBuilding', function() {
		if ( state.queryEditEnabled ) return;
		setQueryEditorLoading( true );
	});

	$(document).on('dwm:builderValidated', function(e, data) {
		if ( state.queryEditEnabled ) {
			setQueryEditorLoading( false );
			return;
		}
		setAutoBuiltQueryValid( true );
		state.queryValidated        = true;
		state.lastValidatedQuery    = data.sql;
		state.lastValidationResults = data.validationResults;
		state.queryDirty            = false;
		updateValidateButtonState();
		updateQueryPreviewContent();
		enableQueryOutputPreview( true );
		renderQueryOutputPreview();
		setQueryEditorLoading( false );
		showQueryValidationStatus( 'dwm-validation-success', 'Query is valid (' + data.validationResults.row_count + ' rows)', 'dashicons-yes-alt' );
	});

	$(document).on('dwm:builderInvalidated', function() {
		if ( state.queryEditEnabled ) {
			setQueryEditorLoading( false );
			return;
		}
		setAutoBuiltQueryValid( false );
		state.queryValidated = false;
		clearQueryValidationStatus();
		enableQueryOutputPreview( false );
		resetQueryOutputPreview();
		setQueryEditorLoading( false );
		showQueryValidationStatus( 'dwm-validation-error', 'Query invalid', 'dashicons-warning' );
	});

	// Keep tab labels mode-aware for better clarity in chart modes.
	$( document ).on( 'dwm:builderDisplayModeChanged', function( _e, mode ) {
		updateModeAwareTabLabels( mode );
		updateEditorThemeSectionForMode( mode );
		updateOutputTabSections( mode );
		applyAutoThemeAssets( mode, getSelectedThemeForMode( mode ), false );
	} );

	// Re-render column aliases and output controls when columns change.
	$( document ).on( 'change', '#dwm-builder-columns-list input[type=checkbox]', function() {
		const cols = getSelectedColumns();
		const existing = getColumnAliasesMap() || {};
		renderColumnAliases( cols, existing );
		renderOutputControls( cols, null );
	} );

	// Clear aliases and output controls when the data table changes.
	$( document ).on( 'dwm:builderDataCleared', function() {
		renderColumnAliases( [], {} );
		resetOutputControls();
	} );

	// Restore aliases and output controls after builder finishes restoring config.
	$( document ).on( 'dwm:builderRestored', function() {
		const cols = getSelectedColumns();
		renderColumnAliases( cols, _pendingColumnAliases );
		_pendingColumnAliases = {};
		renderOutputControls( cols, _pendingOutputConfig );
		_pendingOutputConfig = null;
		updateOutputTabSections( getCurrentDisplayMode() );
	} );

	// Rebuild template when alias inputs change.
	$( document ).on( 'input', '#dwm-output-column-aliases-list .dwm-alias-input', function() {
		const normalized = normalizeAliasLabel( $( this ).val() );
		if ( $( this ).val() !== normalized ) {
			$( this ).val( normalized );
		}
		rebuildTemplateWithAliases();
	} );

	// Re-apply alias-aware template after server build overwrites it.
	$( document ).on( 'dwm:serverBuildApplied', function() {
		const aliases = getColumnAliasesMap();
		if ( aliases && Object.keys( aliases ).length > 0 ) {
			rebuildTemplateWithAliases();
		}
	} );
}

/**
 * Initialize code editors
 */
function initCodeEditors() {
	if (typeof wp.codeEditor === 'undefined') {
		return;
	}

	const editorSettings = wp.codeEditor.defaultSettings || {};

	if ($('#dwm-widget-query').length) {
		state.codeEditors.query = wp.codeEditor.initialize('dwm-widget-query', {
			...editorSettings,
			codemirror: {
				...editorSettings.codemirror,
				mode: 'text/x-sql',
				lineNumbers: true,
				lineWrapping: true,
				readOnly: 'nocursor',
				styleActiveLine: false
			}
		});

		$( state.codeEditors.query.codemirror.getWrapperElement() ).addClass( 'dwm-cm-readonly' );

		state.codeEditors.query.codemirror.on('change', function() {
			updateQueryStats();
			handleQueryContentChanged();
		});

		state.codeEditors.query.codemirror.on('keydown', function(cm, e) {
			if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
				e.preventDefault();
				validateQuery();
			}
			if ( state.queryEditEnabled && ( e.key === ' ' || e.key === 'Enter' ) ) {
				setTimeout( enforceSelectPrefix, 0 );
			}
		});
	}

	$( document ).on( 'input', '#dwm-widget-query', function() {
		updateQueryStats();
		handleQueryContentChanged();
	} );

	$( document ).on( 'keyup', '#dwm-widget-query', function( e ) {
		if ( state.queryEditEnabled && ( e.key === ' ' || e.key === 'Enter' ) ) {
			enforceSelectPrefix();
		}
	} );

	if ($('#dwm-widget-template').length) {
		state.codeEditors.template = wp.codeEditor.initialize('dwm-widget-template', {
			...editorSettings,
			codemirror: {
				...editorSettings.codemirror,
				mode: 'application/x-httpd-php',
				lineNumbers: true,
				lineWrapping: true,
				readOnly: 'nocursor',
				styleActiveLine: false
			}
		});
		$( state.codeEditors.template.codemirror.getWrapperElement() ).addClass( 'dwm-cm-readonly' );
	}

	if ($('#dwm-widget-styles').length) {
		state.codeEditors.styles = wp.codeEditor.initialize('dwm-widget-styles', {
			...editorSettings,
			codemirror: {
				...editorSettings.codemirror,
				mode: 'css',
				lineNumbers: true,
				lineWrapping: true,
				readOnly: 'nocursor',
				styleActiveLine: false
			}
		});
		$( state.codeEditors.styles.codemirror.getWrapperElement() ).addClass( 'dwm-cm-readonly' );
	}

	if ($('#dwm-widget-scripts').length) {
		state.codeEditors.scripts = wp.codeEditor.initialize('dwm-widget-scripts', {
			...editorSettings,
			codemirror: {
				...editorSettings.codemirror,
				mode: 'javascript',
				lineNumbers: true,
				lineWrapping: true,
				readOnly: 'nocursor',
				styleActiveLine: false
			}
		});
		$( state.codeEditors.scripts.codemirror.getWrapperElement() ).addClass( 'dwm-cm-readonly' );
	}

	if ($('#dwm-widget-no-results-template').length) {
		state.codeEditors.no_results_template = wp.codeEditor.initialize('dwm-widget-no-results-template', {
			...editorSettings,
			codemirror: {
				...editorSettings.codemirror,
				mode: 'htmlmixed',
				lineNumbers: true,
				lineWrapping: true,
				readOnly: 'nocursor',
				styleActiveLine: false
			}
		});
		$( state.codeEditors.no_results_template.codemirror.getWrapperElement() ).addClass( 'dwm-cm-readonly' );
	}
}

/**
 * Initialize query stats
 */
function initQueryStats() {
	updateQueryStats();
	resetQueryPreviewTabs();
	resetQueryEditorInteractionState( getCurrentQueryText() );
	resetCodeEditorInteractionState();
	updateModeAwareTabLabels( 'table' );
	updateEditorThemeSectionForMode( 'table' );
}

/**
 * Switch tab
 */
function switchTab(tab) {
	// If leaving the query tab while editing is active with a trivial/empty query, auto-revert.
	if ( tab !== 'query' && state.queryEditEnabled ) {
		const q = getCurrentQueryText().trim();
		if ( ! q || /^SELECT\s*$/i.test( q ) ) {
			$( '#dwm-query-edit-toggle' ).prop( 'checked', false );
			setQueryEditingEnabled( false );
		}
	}

	$('.dwm-tab-link').removeClass('active');
	$('.dwm-tab-link[data-tab="' + tab + '"]').addClass('active');
	$('.dwm-tab-content').removeClass('active');
	$('.dwm-tab-content[data-tab="' + tab + '"]').addClass('active');

	if (state.codeEditors[tab]) {
		state.codeEditors[tab].codemirror.refresh();
	}

	// Refresh the no-results CodeMirror when switching to the template tab.
	if ( tab === 'template' && state.codeEditors.no_results_template ) {
		state.codeEditors.no_results_template.codemirror.refresh();
	}

	if ( tab === 'query' ) {
		updateQueryPreviewContent();
	}
}

/**
 * Lock or unlock a tab by toggling the is-disabled class.
 */
function toggleTabLock(tab, locked) {
	$('.dwm-tab-link[data-tab="' + tab + '"]').toggleClass('is-disabled', locked);
}

function updateCacheDurationVisibility() {
	const enabled = $('#dwm-enable-caching').is(':checked');
	$('#dwm-cache-duration-group').toggle(enabled);
}

/**
 * Update tab lock states based on current editor state
 */
function updateTabLocks() {
	const name        = $('#dwm-widget-name').val().trim();
	const table       = $('#dwm-builder-table').val();
	const hasColumns  = $('#dwm-builder-columns-list input[type=checkbox]:checked').length > 0;
	const dataUnlocked     = name.length > 0;
	const advancedUnlocked = dataUnlocked && !!table && hasColumns;

	toggleTabLock('data', !dataUnlocked);
	['query', 'caching', 'output', 'template', 'styles', 'scripts'].forEach(function(tab) {
		toggleTabLock(tab, !advancedUnlocked);
	});

	const activeTab = $('.dwm-tab-link.active').data('tab');
	if (activeTab === 'data' && !dataUnlocked) {
		switchTab('builder');
	} else if (!advancedUnlocked && ['query', 'caching', 'output', 'template', 'styles', 'scripts'].indexOf(activeTab) !== -1) {
		switchTab(dataUnlocked ? 'data' : 'builder');
	}
}

/**
 * Open create modal
 */
function openCreateModal() {
	state.currentWidgetId = null;
	$('#dwm-editor-title').html('<span class="dashicons dashicons-plus-alt2"></span> Create New Widget ' + EDITOR_TITLE_HELP_BUTTON_HTML);
	$('#dwm-widget-form')[0].reset();
	$('#dwm-widget-id').val('');
	$('#dwm-creation-method').val('');

	$('#dwm-enable-caching').prop('checked', true);
	$('#dwm-enable-query-logging').prop('checked', false);
	$('#dwm-cache-duration').val(300);
	updateCacheDurationVisibility();
	$('#dwm-auto-refresh').val('0');
	$('#dwm-max-execution-time').val(30);
	state.queryValidated = false;
	state.lastValidatedQuery = '';
	state.lastValidationResults = null;
	resetQueryEditorInteractionState( '' );
	resetQueryPreviewTabs();

	Object.keys(state.codeEditors).forEach(key => {
		if (state.codeEditors[key]) {
			state.codeEditors[key].codemirror.setValue('');
		}
	});

	// Seed default no-results template for new widgets.
	if (state.codeEditors.no_results_template) {
		state.codeEditors.no_results_template.codemirror.setValue(getDefaultNoResultsTemplate());
	} else {
		$('#dwm-widget-no-results-template').val(getDefaultNoResultsTemplate());
	}

	$('#dwm-builder-config').val('');
	$('#dwm-chart-type').val('');
	$('#dwm-chart-config').val('');
	$('#dwm-builder-chart-title').val('');
	$('#dwm-builder-chart-legend').prop('checked', true);
	$('#dwm-builder-chart-theme').val('classic');
	$('input[name="dwm_display_mode"][value="table"]').prop('checked', true);
	$('#dwm-builder-chart-options').hide();
	$('#dwm-show-description').prop('checked', false);
	$('#dwm-widget-description').prop('required', false);
	ensureThemeSelectionForMode( 'table', 'default' );
	updateEditorThemeSectionForMode( 'table' );
	resetCodeEditorInteractionState();
	applyAutoThemeAssets( 'table', 'default', true );
	persistThemeSelection( 'table', 'default', false );
	renderColumnAliases( [], {} );
	resetOutputControls();
	updateOutputTabSections( 'table' );
	populateBuilderFromConfig(null);

	$('input[name="dwm_creation_method"]').prop('checked', false);
	$('#dwm-creation-method-step').show();
	$('#dwm-widget-form').hide();
	$('#dwm-widget-editor-modal .dwm-modal-footer').hide();
	$('#dwm-wizard-footer').hide();
	$('#dwm-switch-to-scratch').hide();
	$('#dwm-switch-to-wizard').hide();
	hideWizard();

	updateQueryStats();
	updateTabLocks();
	switchTab('builder');
	openModal('dwm-widget-editor-modal');
}

/**
 * Open edit modal
 */
function openEditModal(widgetId) {
	state.queryValidated = false;
	state.lastValidatedQuery = '';
	state.lastValidationResults = null;
	resetQueryEditorInteractionState( '' );
	resetQueryPreviewTabs();
	resetCodeEditorInteractionState();

	ajax(
		'dwm_get_widget',
		{ widget_id: widgetId },
		function(data) {
			resetWidgetEditor();
			state.currentWidgetId = widgetId;
			populateForm(data.widget);
			const widgetName = $('<span>').text(data.widget.name || '').html();
			$('#dwm-editor-title').html('<span class="dashicons dashicons-edit"></span> Edit Widget' + (widgetName ? ': ' + widgetName : '') + ' ' + EDITOR_TITLE_HELP_BUTTON_HTML);

			$('#dwm-creation-method-step').hide();
			hideWizard();
			$('#dwm-switch-to-wizard').hide();
			$('#dwm-widget-form').show();
			$('#dwm-widget-editor-modal .dwm-modal-footer').show();

			switchTab('builder');
			openModal('dwm-widget-editor-modal');

			setTimeout(function() {
				updateQueryStats();
				updateTabLocks();
			}, 100);

			if (data.widget.sql_query) {
				validateQuery();
			}
		},
		function(data) {
			showNotice(data.message || 'Failed to load widget.', 'error');
		}
	);
}

/**
 * Switch from manual mode to wizard mode, pre-populating wizard with current editor data
 */
function switchToWizardMode() {
	const wizardData = collectEditorDataForWizard();

	state.queryValidated = false;
	state.lastValidatedQuery = '';
	state.lastValidationResults = null;
	clearQueryValidationStatus();

	$('#dwm-creation-method').val('wizard');
	$('#dwm-widget-form').hide();
	$('#dwm-widget-editor-modal .dwm-modal-footer').hide();
	$('#dwm-wizard-footer').show();
	$('#dwm-switch-to-scratch').show();
	$('#dwm-switch-to-wizard').hide();
	showWizardWithData( wizardData );
}

/**
 * Read the current editor form state and map it to wizard data format
 */
function collectEditorDataForWizard() {
	const wizardData = {};

	wizardData.name            = $( '#dwm-widget-name' ).val() || '';
	wizardData.description     = $( '#dwm-widget-description' ).val() || '';
	wizardData.showDescription = $( '#dwm-show-description' ).is( ':checked' );

	const displayMode = $( 'input[name="dwm_display_mode"]:checked' ).val();
	if ( displayMode ) {
		wizardData.displayMode = displayMode;
	}

	const builderConfigJson = $( '#dwm-builder-config' ).val();
	if ( builderConfigJson ) {
		try {
			const config = JSON.parse( builderConfigJson );

			if ( config.table ) {
				let availableColumns = [];
				try {
					const cached = sessionStorage.getItem( 'dwm_columns_cache_' + config.table );
					if ( cached ) {
						const parsed = JSON.parse( cached );
						availableColumns = parsed.columns || [];
					}
				} catch ( e ) {}

				wizardData.stepConfig = {
					table:            config.table,
					columns:          config.columns          || [],
					availableColumns: availableColumns
				};
			}

			wizardData.joins          = config.joins          || [];
			wizardData.conditions     = config.conditions     || [];
			const outputConfig = getOutputConfig();
			const configuredOutput = outputConfig && Array.isArray( outputConfig.columns ) && outputConfig.columns.length > 0
				? outputConfig
				: ( config.output_config || null );
			wizardData.outputConfig = configuredOutput;
			wizardData.columnAliases = configuredOutput && Array.isArray( configuredOutput.columns )
				? configuredOutput.columns
				: [];

			if ( config.orders && config.orders.length ) {
				wizardData.orders = config.orders.map( function( o ) {
					return { column: o.column, direction: o.direction || 'DESC' };
				});
			} else if ( config.order_by ) {
				wizardData.orders = [ { column: config.order_by, direction: config.order_dir || 'DESC' } ];
			} else {
				wizardData.orders = [];
			}

			wizardData.limit   = config.limit;
			wizardData.noLimit = config.noLimit !== undefined ? config.noLimit : true;

			if ( config.display_mode && isChartDisplayMode( config.display_mode ) ) {
				wizardData.chartAxisConfig = {
					labelColumn: config.chart_label_column || '',
					dataColumns: config.chart_data_columns || [],
					title: config.chart_title || '',
					showLegend: config.chart_show_legend !== false
				};
				if ( config.chart_theme ) {
					wizardData.chartTheme = config.chart_theme;
				}
			} else if ( config.table_theme ) {
				wizardData.theme = config.table_theme;
			}
		} catch ( e ) {}
	}

	const chartConfigRaw = $( '#dwm-chart-config' ).val();
	if ( chartConfigRaw ) {
		try {
			const chartConfig = JSON.parse( chartConfigRaw );
			wizardData.chartAxisConfig = {
				labelColumn: chartConfig.label_column || '',
				dataColumns: chartConfig.data_columns || [],
				title: chartConfig.title || '',
				showLegend: chartConfig.show_legend !== false
			};
			if ( chartConfig.theme ) {
				wizardData.chartTheme = chartConfig.theme;
			}
		} catch ( e ) {}
	}

	const cacheEnabled  = $( '#dwm-enable-caching' ).is( ':checked' );
	const cacheDuration = parseInt( $( '#dwm-cache-duration' ).val(), 10 ) || 300;
	wizardData.cache = {
		enabled:     cacheEnabled,
		duration:    cacheDuration,
		autoRefresh: $( '#dwm-auto-refresh' ).val() === '1'
	};

	return wizardData;
}

/**
 * Populate form with widget data
 */
function populateForm(widget) {
	state.suppressQueryChange = true;

	$('#dwm-widget-id').val(widget.id);
	$('#dwm-widget-name').val(widget.name);
	$('#dwm-widget-description').val(widget.description);
	$('#dwm-enable-caching').prop('checked', widget.enable_caching == 1);
	$('#dwm-enable-query-logging').prop('checked', widget.enable_query_logging == 1);
	$('#dwm-cache-duration').val(widget.cache_duration);
	updateCacheDurationVisibility();
	$('#dwm-auto-refresh').val(widget.auto_refresh == 1 ? '1' : '0');
	$('#dwm-max-execution-time').val(widget.max_execution_time || 30);
	if (state.codeEditors.query) {
		state.codeEditors.query.codemirror.setValue(widget.sql_query || '');
	} else {
		$('#dwm-widget-query').val(widget.sql_query || '');
	}
	setAutoBuildQuery( widget.sql_query || '' );

	if (state.codeEditors.template) {
		state.codeEditors.template.codemirror.setValue(widget.template || '');
	} else {
		$('#dwm-widget-template').val(widget.template || '');
	}

	if (state.codeEditors.styles) {
		state.codeEditors.styles.codemirror.setValue(widget.styles || '');
	} else {
		$('#dwm-widget-styles').val(widget.styles || '');
	}

	if (state.codeEditors.scripts) {
		state.codeEditors.scripts.codemirror.setValue(widget.scripts || '');
	} else {
		$('#dwm-widget-scripts').val(widget.scripts || '');
	}

	const noResultsValue = widget.no_results_template ?? getDefaultNoResultsTemplate();
	if (state.codeEditors.no_results_template) {
		state.codeEditors.no_results_template.codemirror.setValue(noResultsValue);
	} else {
		$('#dwm-widget-no-results-template').val(noResultsValue);
	}

	$('#dwm-builder-config').val(widget.builder_config || '');
	$('#dwm-chart-type').val(widget.chart_type || '');
	$('#dwm-chart-config').val(widget.chart_config || '');

	let showDesc = false;
	let pendingOutputConfig = null;
	try {
		pendingOutputConfig = JSON.parse( widget.output_config || 'null' );
	} catch ( e ) {}
	try {
		const bc = JSON.parse( widget.builder_config || '{}' );
		showDesc = !! bc.show_description;
		if ( bc.output_config ) {
			pendingOutputConfig = bc.output_config;
			if ( Array.isArray( bc.output_config.columns ) ) {
				_pendingColumnAliases = {};
				bc.output_config.columns.forEach( function( col ) {
					const key = col.key || col.original;
					if ( key ) {
						_pendingColumnAliases[ key ] = col.alias || key;
					}
				} );
			}
		} else {
			_pendingColumnAliases = {};
		}
	} catch ( e ) {
		_pendingColumnAliases = {};
	}
	_pendingOutputConfig = pendingOutputConfig;
	$('#dwm-show-description').prop('checked', showDesc);
	const $desc = $('#dwm-widget-description');
	$desc.prop('required', showDesc).attr('placeholder', showDesc ? 'Required' : 'Optional description');
	$desc.closest('.dwm-form-group').find('.dwm-desc-required-asterisk').toggle(showDesc);

	populateBuilderFromConfig(widget.builder_config || null);
	syncThemeSelectionFromWidget( widget );
	seedAutoAssetSnapshotsFromCurrentValues();
	updateEditorThemeSectionForMode( getCurrentDisplayMode() );
	updateOutputTabSections( getCurrentDisplayMode() );

	state.suppressQueryChange = false;
	resetQueryEditorInteractionState( getCurrentQueryText() );
	updateQueryPreviewContent();
}

/**
 * Save widget
 */
function saveWidget() {
	if (!state.queryValidated) {
		showNotice('Please validate the query before saving.', 'error');
		return;
	}

	const widgetId = $('#dwm-widget-id').val();
	const isEdit = widgetId !== '';

	let builderConfigObj = {};
	try { builderConfigObj = JSON.parse( $('#dwm-builder-config').val() || '{}' ); } catch ( e ) {}
	builderConfigObj.show_description = $('#dwm-show-description').is(':checked');
	const activeMode = builderConfigObj.display_mode || getCurrentDisplayMode();
	const selectedTheme = getSelectedThemeForMode( activeMode );

	// Persist output_config (link builder + formatters).
	builderConfigObj.output_config = getOutputConfig();

	if ( isChartDisplayMode( activeMode ) ) {
		builderConfigObj.chart_theme = selectedTheme;
		builderConfigObj.table_theme = '';
	} else {
		builderConfigObj.table_theme = selectedTheme;
	}

	const widgetData = {
		name: $('#dwm-widget-name').val(),
		description: $('#dwm-widget-description').val(),
		enable_caching: $('#dwm-enable-caching').is(':checked') ? 1 : 0,
		enable_query_logging: $('#dwm-enable-query-logging').is(':checked') ? 1 : 0,
		cache_duration: $('#dwm-cache-duration').val(),
		auto_refresh: parseInt($('#dwm-auto-refresh').val(), 10) || 0,
		max_execution_time: $('#dwm-max-execution-time').val(),
			status: isEdit ? undefined : 'publish',
		sql_query: state.codeEditors.query ? state.codeEditors.query.codemirror.getValue() : $('#dwm-widget-query').val(),
		template: state.codeEditors.template ? state.codeEditors.template.codemirror.getValue() : $('#dwm-widget-template').val(),
		styles: state.codeEditors.styles ? state.codeEditors.styles.codemirror.getValue() : $('#dwm-widget-styles').val(),
		scripts: state.codeEditors.scripts ? state.codeEditors.scripts.codemirror.getValue() : $('#dwm-widget-scripts').val(),
		no_results_template: state.codeEditors.no_results_template ? state.codeEditors.no_results_template.codemirror.getValue() : $('#dwm-widget-no-results-template').val(),
		builder_config: JSON.stringify( builderConfigObj ),
		output_config: JSON.stringify( getOutputConfig() ),
		chart_type: $('#dwm-chart-type').val(),
		chart_config: $('#dwm-chart-config').val()
	};

	if ( isChartDisplayMode( activeMode ) ) {
		try {
			const parsedChartConfig = JSON.parse( widgetData.chart_config || '{}' );
			parsedChartConfig.theme = selectedTheme;
			widgetData.chart_config = JSON.stringify( parsedChartConfig );
		} catch ( e ) {}
	}

	const $submitBtn = $('#dwm-save-widget');
	showLoading($submitBtn);

	const action = isEdit ? 'dwm_update_widget' : 'dwm_create_widget';
	const data = isEdit ? { widget_id: widgetId, widget: widgetData } : { widget: widgetData };

	ajax(
		action,
		data,
		function( response ) {
			hideLoading($submitBtn);
			if ( isEdit ) {
				closeModal();
				showNotice( 'Widget updated successfully.' );
				setTimeout(function() { location.reload(); }, 1000);
			} else {
				setNewlySavedWidgetId( response && response.widget_id ? response.widget_id : null );
				openPostSaveModal();
			}
		},
		function(data) {
			hideLoading($submitBtn);
			showNotice(data.message || 'Failed to save widget.', 'error');
		}
	);
}

/**
 * Open the post-save modal with both toggles reset to ON
 */
function openPostSaveModal() {
	$('#dwm-saved-publish-toggle').prop('checked', true);
	$('#dwm-saved-dashboard-toggle').prop('checked', true);
	$('#dwm-saved-dashboard-row').show();
	openModal('dwm-widget-saved-modal');
}

/**
 * Close post-save modal and finish: close editor + reload
 */
function closePostSaveModal() {
	closeModal('dwm-widget-saved-modal');
	closeModal('dwm-widget-editor-modal');
	$('body').removeClass('dwm-modal-open');
	setTimeout(function() { location.reload(); }, 400);
}

/**
 * Init post-save modal event handlers
 */
function initPostSaveModal() {
	$(document).on('change', '#dwm-saved-publish-toggle', function() {
		const isPublished = $(this).is(':checked');
		const newStatus = isPublished ? 'publish' : 'draft';
		const savedId = getNewlySavedWidgetId();

		if ( ! savedId ) return;

		ajax( 'dwm_toggle_widget', { widget_id: savedId, status: newStatus }, function() {
			if ( isPublished ) {
				$('#dwm-saved-dashboard-row').show();
				$('#dwm-saved-dashboard-toggle').prop('checked', true);
			} else {
				$('#dwm-saved-dashboard-row').hide();
			}
		});
	});

	$(document).on('change', '#dwm-saved-dashboard-toggle', function() {
		const enabled = $(this).is(':checked') ? 1 : 0;
		const savedId = getNewlySavedWidgetId();
		if ( ! savedId ) return;
		ajax( 'dwm_set_widget_enabled', { widget_id: savedId, enabled: enabled } );
	});

	$(document).on('click', '#dwm-widget-saved-done', closePostSaveModal);
	$(document).on('click', '#dwm-widget-saved-close', closePostSaveModal);
	$(document).on('click', '#dwm-widget-saved-modal .dwm-modal-overlay', closePostSaveModal);
}

/**
 * Delete widget (soft-delete to trash, or permanent delete if already trashed)
 */
function deleteWidget(widgetId) {
	const $card = $(`.dwm-widget-card[data-widget-id="${widgetId}"]`);
	const cardStatus = $card.attr('data-widget-status') || 'draft';

	if (cardStatus === 'trash') {
		dwmConfirm({
			title:       'Delete Widget',
			message:     'Permanently delete this widget? This cannot be undone.',
			icon:        'trash',
			confirmText: 'Delete',
			onConfirm() {
				ajax(
					'dwm_permanent_delete',
					{ widget_id: widgetId },
					function() {
						$card.remove();
						updateStatusCounts();
						showNotice('Widget permanently deleted.');
					},
					function(data) {
						showNotice(data.message || 'Failed to delete widget.', 'error');
					}
				);
			},
		});
	} else {
		dwmConfirm({
			title:        'Move to Trash',
			message:      'Move this widget to trash?',
			icon:         'trash',
			confirmText:  'Move to Trash',
			confirmClass: 'dwm-button-secondary',
			onConfirm() {
				ajax(
					'dwm_delete_widget',
					{ widget_id: widgetId },
					function() {
						$card.attr('data-widget-status', 'trash');
						$card.find('.dwm-badge').first()
							.removeClass('dwm-badge-success dwm-badge-disabled dwm-badge-archived')
							.addClass('dwm-badge-trashed')
							.text('Trashed');
						$card.find('.dwm-toggle-widget').prop('checked', false);
						filterWidgetsByStatus(state.currentStatusFilter);
						updateStatusCounts();
						showNotice('Widget moved to trash.');
					},
					function(data) {
						showNotice(data.message || 'Failed to trash widget.', 'error');
					}
				);
			},
		});
	}
}

/**
 * Toggle widget (publish/draft)
 */
function toggleWidget(widgetId, enabled) {
	const newStatus = enabled ? 'publish' : 'draft';
	ajax(
		'dwm_toggle_widget',
		{ widget_id: widgetId, status: newStatus },
		function(data) {
			const $card = $(`.dwm-widget-card[data-widget-id="${widgetId}"]`);
			const $badge = $card.find('.dwm-badge').first();

			$card.attr('data-widget-status', newStatus);

			if (enabled) {
				$badge.removeClass('dwm-badge-disabled dwm-badge-archived dwm-badge-trashed').addClass('dwm-badge-success').text('Active');
			} else {
				$badge.removeClass('dwm-badge-success dwm-badge-archived dwm-badge-trashed').addClass('dwm-badge-disabled').text('Draft');
			}

			if (data.first_published_at && !$card.find('.dwm-first-active').length) {
				const $meta = $card.find('.dwm-widget-card-meta');
				const $createdBy = $meta.find('span:last');
				$('<span class="dwm-first-active">Activated: <strong>' + data.first_published_at + '</strong></span>').insertBefore($createdBy);
			}

			updateStatusCounts();
			showNotice(enabled ? 'Widget published.' : 'Widget set to draft.');
		},
		function(data) {
			showNotice(data.message || 'Failed to toggle widget.', 'error');
			$(`.dwm-toggle-widget[data-widget-id="${widgetId}"]`).prop('checked', !enabled);
		}
	);
}

/**
 * Filter widgets by status
 */
function filterWidgetsByStatus(status) {
	state.currentStatusFilter = status;

	$('.dwm-widget-card').each(function() {
		const cardStatus = $(this).attr('data-widget-status') || 'draft';
		const isDemo = $(this).attr('data-is-demo') === '1';
		let visible = true;

		if (status === 'demo') {
			visible = isDemo;
		} else if (status === 'all') {
			if (cardStatus === 'archived' || cardStatus === 'trash') {
				visible = false;
			}
		} else if (status !== cardStatus) {
			visible = false;
		}

		$(this).toggle(visible);
	});

	$('#dwm-empty-trash-wrapper').toggle(status === 'trash');
}

/**
 * Update status filter counts
 */
function updateStatusCounts() {
	const counts = { publish: 0, draft: 0, archived: 0, trash: 0, demo: 0 };

	$('.dwm-widget-card').each(function() {
		const s = $(this).attr('data-widget-status') || 'draft';
		if (counts.hasOwnProperty(s)) {
			counts[s]++;
		}
		if ($(this).attr('data-is-demo') === '1') {
			counts.demo++;
		}
	});

	const filterMap = { publish: 'Active', draft: 'Draft', archived: 'Archive', trash: 'Trash', demo: 'Demo' };

	Object.keys(filterMap).forEach(function(key) {
		const $btn = $(`.dwm-status-filter[data-filter="${key}"]`);
		const $sep = $btn.prev('.dwm-status-separator');

		if (counts[key] > 0) {
			if ($btn.length) {
				$btn.find('.dwm-status-count').text(counts[key]);
				$btn.show();
				$sep.show();
			} else {
				const $filters = $('.dwm-status-filters');
				$filters.append(
					'<span class="dwm-status-separator">|</span>' +
					'<button type="button" class="dwm-status-filter" data-filter="' + key + '">' +
					'<span class="dwm-status-count">' + counts[key] + '</span> ' + filterMap[key] +
					'</button>'
				);
			}
		} else {
			$btn.hide();
			$sep.hide();
		}
	});
}

/**
 * Empty trash
 */
function emptyTrash() {
	dwmConfirm({
		title:       'Empty Trash',
		message:     'Permanently delete all trashed widgets? This cannot be undone.',
		icon:        'trash',
		confirmText: 'Empty Trash',
		onConfirm() {
			ajax(
				'dwm_empty_trash',
				{},
				function(data) {
					$('.dwm-widget-card[data-widget-status="trash"]').remove();
					updateStatusCounts();
					filterWidgetsByStatus('all');
					$('.dwm-status-filter[data-filter="all"]').addClass('is-active');
					showNotice('Trash emptied.');
				},
				function(data) {
					showNotice(data.message || 'Failed to empty trash.', 'error');
				}
			);
		},
	});
}
