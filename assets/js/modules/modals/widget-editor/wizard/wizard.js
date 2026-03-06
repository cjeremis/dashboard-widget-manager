/**
 * Dashboard Widget Manager - Creation Wizard Module (Orchestrator)
 *
 * Multi-step wizard for guided widget creation.
 * Coordinates step navigation, data collection, and module initialisation.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

// ── Step Modules ─────────────────────────────────────────────────────────────
import * as stepTable from './wizard-steps/wizard-step-table.js';
import * as stepBar from './wizard-steps/wizard-step-bar.js';
import * as stepLine from './wizard-steps/wizard-step-line.js';
import * as stepPie from './wizard-steps/wizard-step-pie.js';
import * as stepDoughnut from './wizard-steps/wizard-step-doughnut.js';
import * as stepList from './wizard-steps/wizard-step-list.js';
import * as stepButton from './wizard-steps/wizard-step-button.js';
import * as stepCardList from './wizard-steps/wizard-step-card-list.js';
import * as stepOrder from './wizard-steps/wizard-step-order.js';
import * as stepLimit from './wizard-steps/wizard-step-limit.js';
import * as stepCache from './wizard-steps/wizard-step-cache.js';
import * as stepColumns from './wizard-steps/wizard-step-columns.js';
import * as stepTheme from './wizard-steps/wizard-step-theme.js';
import * as stepBarAxis from './wizard-steps/wizard-step-bar-axis.js';
import * as stepBarTheme from './wizard-steps/wizard-step-bar-theme.js';
import * as stepLineAxis from './wizard-steps/wizard-step-line-axis.js';
import * as stepLineTheme from './wizard-steps/wizard-step-line-theme.js';
import * as stepPieConfig from './wizard-steps/wizard-step-pie-config.js';
import * as stepPieTheme from './wizard-steps/wizard-step-pie-theme.js';
import * as stepDoughnutConfig from './wizard-steps/wizard-step-doughnut-config.js';
import * as stepDoughnutTheme from './wizard-steps/wizard-step-doughnut-theme.js';
import * as stepListTheme from './wizard-steps/wizard-step-list-theme.js';
import * as stepButtonTheme from './wizard-steps/wizard-step-button-theme.js';
import * as stepCardListTheme from './wizard-steps/wizard-step-card-list-theme.js';

// ── External Dependencies ────────────────────────────────────────────────────
import { showWizardPreview } from '../../preview-modal.js';
import { ensureSearchableSelect, refreshSearchableSelect } from '../../../partials/searchable-select.js';

// ── Extracted Wizard Modules ─────────────────────────────────────────────────
import {
	wizardState,
	TOTAL_STEPS,
	STEP_MODULES,
	setStepModules
} from './wizard-state.js';

import {
	getActiveStepModule,
	getEnrichedStepConfig,
	getStep3TableSelector,
	isChartDisplayMode
} from './wizard-utils.js';

import {
	renderWizardJoins,
	updateStep4NextButton,
	initJoinModalEvents
} from './wizard-join-modal.js';

import {
	renderWizardConditions,
	updateStep5NextButton,
	initFilterModalEvents
} from './wizard-filter-modal.js';

import {
	updateStep6NextButton,
	initOrderModalEvents
} from './wizard-order-modal.js';

import {
	prefillTableBuilder,
	prefillBuilderJoins,
	prefillBuilderConditions,
	prefillBuilderOrder,
	prefillThemeAssets,
	prefillChartConfiguration,
	buildBuilderConfigFromWizard
} from './wizard-prefill.js';
import { renderOutputControls } from '../widget-editor-output.js';

const $ = jQuery;
const EDITOR_TITLE_HELP_BUTTON_HTML = '<button type="button" class="dwm-switch-to-wizard dwm-docs-trigger" id="dwm-editor-title-help" data-open-modal="dwm-docs-modal" data-docs-page="welcome" title="Open documentation"><span class="dashicons dashicons-editor-help"></span></button>';

// ── Initialisation ───────────────────────────────────────────────────────────

/**
 * Initialize wizard event bindings
 */
export function initWizard() {

	// Populate STEP_MODULES map (avoids circular imports in wizard-state.js)
	setStepModules( {
		table: stepTable,
		bar: stepBar,
		line: stepLine,
		pie: stepPie,
		doughnut: stepDoughnut,
		list: stepList,
		button: stepButton,
		'card-list': stepCardList
	} );

	// Init display-type step modules
	Object.values( STEP_MODULES ).forEach( function( mod ) {
		mod.init();
	});

	// Init remaining step modules
	stepOrder.init();
	stepLimit.init();
	stepCache.init();
	stepColumns.init();
	stepTheme.init();
	stepBarAxis.init();
	stepBarTheme.init();
	stepLineAxis.init();
	stepLineTheme.init();
	stepPieConfig.init();
	stepPieTheme.init();
	stepDoughnutConfig.init();
	stepDoughnutTheme.init();
	stepListTheme.init();
	stepButtonTheme.init();
	stepCardListTheme.init();

	// Delegate modal event bindings to extracted modules
	initJoinModalEvents( goToStep );
	initFilterModalEvents();
	initOrderModalEvents();

	// ── Navigation Buttons ───────────────────────────────────────────────────

	// Next step
	$( document ).on( 'click', '.dwm-wizard-next', function( e ) {
		e.preventDefault();
		if ( validateCurrentStep() ) {
			collectStepData();
			if ( wizardState.currentStep >= TOTAL_STEPS ) {
				completeWizard();
			} else {
				const nextStep = wizardState.currentStep + 1;
				if ( nextStep === 3 && $( '#dwm-wizard-step3-content' ).is( ':empty' ) ) {
					renderDisplayTypeStep();
				}
				goToStep( nextStep );
			}
		}
	});

	// Back step
	$( document ).on( 'click', '.dwm-wizard-back', function( e ) {
		e.preventDefault();
		if ( wizardState.currentStep > 1 ) {
			clearStepData( wizardState.currentStep );
			goToStep( wizardState.currentStep - 1 );
		}
	});

	// Start Over - show confirmation modal
	$( document ).on( 'click', '.dwm-wizard-start-over', function( e ) {
		e.preventDefault();
		openModal( 'dwm-confirm-start-over-modal' );
	});

	// Confirm start over
	$( document ).on( 'click', '#dwm-confirm-start-over-yes', function() {
		closeModal( 'dwm-confirm-start-over-modal' );
		resetWizard();
	});

	// Cancel start over
	$( document ).on( 'click', '.dwm-confirm-start-over-cancel', function() {
		closeModal( 'dwm-confirm-start-over-modal' );
	});

	// Preview widget button
	$( document ).on( 'click', '.dwm-wizard-preview', function( e ) {
		e.preventDefault();
		// Collect current step data before previewing
		collectStepData();
		showWizardPreview( wizardState.data );
	});

	// ── Validation Error Modal ───────────────────────────────────────────────

	$( document ).on( 'click', '.dwm-validation-error-close', function() {
		closeModal( 'dwm-validation-error-modal' );
	});

	// ── Step 7: Limit ────────────────────────────────────────────────────────

	$( document ).on( 'change', '#dwm-wizard-limit-toggle', function() {
		stepLimit.handleLimitToggleChange();
	});

	$( document ).on( 'input change', '#dwm-wizard-limit', function() {
		stepLimit.handleLimitInputChange();
	});

	// ── Step 1: Widget Name ──────────────────────────────────────────────────

	$( document ).on( 'input', '#dwm-wizard-widget-name', function() {
		updateStep1NextButton();
	});

	// ── Step 2: Display Mode ─────────────────────────────────────────────────

	$( document ).on( 'change', 'input[name="dwm_wizard_display_mode"]', function() {
		const newDisplayType = $( this ).val();
		const oldDisplayType = wizardState.data.displayMode;

		// If there's an existing display type and configuration, warn before changing
		if ( oldDisplayType && oldDisplayType !== newDisplayType && hasDisplayTypeConfiguration() ) {
			wizardState.pendingDisplayTypeChange = newDisplayType;
			// Revert radio selection while modal is open
			$( `input[name="dwm_wizard_display_mode"][value="${ oldDisplayType }"]` ).prop( 'checked', true );
			openModal( 'dwm-confirm-display-type-change-modal' );
			return;
		}

		// No existing config, proceed with change
		applyDisplayTypeChange( newDisplayType );
	});

	// Confirm display type change (wizard context only)
	$( document ).on( 'click', '#dwm-confirm-display-type-change-yes', function() {
		if ( ! wizardState.pendingDisplayTypeChange ) return;
		closeModal( 'dwm-confirm-display-type-change-modal' );
		const newDisplayType = wizardState.pendingDisplayTypeChange;
		$( `input[name="dwm_wizard_display_mode"][value="${ newDisplayType }"]` ).prop( 'checked', true );
		applyDisplayTypeChange( newDisplayType );
		wizardState.pendingDisplayTypeChange = '';
	});

	// Cancel display type change
	$( document ).on( 'click', '.dwm-confirm-display-type-change-close', function() {
		closeModal( 'dwm-confirm-display-type-change-modal' );
		wizardState.pendingDisplayTypeChange = '';
	});
}

// ── Step Button Helpers ──────────────────────────────────────────────────────

function updateStep1NextButton() {
	const widgetName = $( '#dwm-wizard-widget-name' ).val().trim();
	const $nextButton = $( '.dwm-wizard-next' );

	if ( widgetName ) {
		$nextButton.show();
	} else {
		$nextButton.hide();
	}
}

function updateStep2NextButton() {
	const displayMode = $( 'input[name="dwm_wizard_display_mode"]:checked' ).val();
	const $nextButton = $( '.dwm-wizard-next' );

	if ( displayMode ) {
		$nextButton.show();
	} else {
		$nextButton.hide();
	}
}

// ── Display Type Helpers ─────────────────────────────────────────────────────

function hasDisplayTypeConfiguration() {
	return !! (
		wizardState.data.stepConfig ||
		( wizardState.data.joins && wizardState.data.joins.length > 0 ) ||
		( wizardState.data.conditions && wizardState.data.conditions.length > 0 ) ||
		( wizardState.data.orders && wizardState.data.orders.length > 0 ) ||
		wizardState.data.limit ||
		wizardState.data.chartAxisConfig ||
		wizardState.data.chartTheme
	);
}

function applyDisplayTypeChange( newDisplayType ) {
	const oldDisplayType = wizardState.data.displayMode;

	// Update display mode
	wizardState.data.displayMode = newDisplayType;

	// If changing from one type to another, clear all configuration
	if ( oldDisplayType && oldDisplayType !== newDisplayType ) {
		delete wizardState.data.stepConfig;
		wizardState.data.joins = [];
		wizardState.data.conditions = [];
		wizardState.data.orders = [];
		delete wizardState.data.limit;
		delete wizardState.data.noLimit;
		delete wizardState.data.columnAliases;
		delete wizardState.data.theme;
		delete wizardState.data.chartAxisConfig;
		delete wizardState.data.chartTheme;

		// Reset join config state
		wizardState.joinConfigState = {
			primaryTableColumns: [],
			joinTableColumns: [],
			compatibilityMap: {}
		};

		// Clear the old display type module's state
		const oldModule = STEP_MODULES[ oldDisplayType ];
		if ( oldModule && typeof oldModule.clearStep === 'function' ) {
			oldModule.clearStep();
		}

		// Clear UI for step 3 onwards
		$( '#dwm-wizard-step3-content' ).empty();
		$( '#dwm-wizard-joins-list' ).empty();
		$( '#dwm-wizard-conditions-list' ).empty();
	}

	updateStep2NextButton();
}

function renderDisplayTypeStep() {
	const mod = getActiveStepModule();
	const $container = $( '#dwm-wizard-step3-content' );

	if ( mod ) {
		mod.renderStep( $container );
	} else {
		$container.html( '<p>No configuration available for this display type.</p>' );
	}
}

// ── Show / Hide / Reset ──────────────────────────────────────────────────────

export function showWizard() {
	wizardState.currentStep = 1;
	wizardState.data = {};

	clearAllWizardFields();
	$( '#dwm-wizard-container' ).show();
	goToStep( 1 );

	// Ensure step 1 Next button is hidden initially
	updateStep1NextButton();
}

export function showWizardWithData( editorData ) {
	wizardState.currentStep = 1;
	wizardState.data        = {};

	clearAllWizardFields();

	// Step 1: name / description
	if ( editorData.name ) {
		wizardState.data.name = editorData.name;
		$( '#dwm-wizard-widget-name' ).val( editorData.name );
	}
	if ( editorData.description ) {
		wizardState.data.description = editorData.description;
		$( '#dwm-wizard-widget-description' ).val( editorData.description );
	}
	if ( editorData.showDescription ) {
		wizardState.data.showDescription = true;
		$( '#dwm-wizard-show-description' ).prop( 'checked', true );
		$( '#dwm-wizard-widget-description' )
			.prop( 'required', true )
			.attr( 'placeholder', 'Required' );
		$( '#dwm-wizard-widget-description' ).closest( '.dwm-form-group' )
			.find( '.dwm-desc-required-asterisk' ).show();
	}

	// Step 2: display mode
	if ( editorData.displayMode ) {
		wizardState.data.displayMode = editorData.displayMode;
	}

	// Step 3: table / columns.
	if ( editorData.stepConfig ) {
		wizardState.data.stepConfig = editorData.stepConfig;
		const displayMode = editorData.displayMode || wizardState.data.displayMode || 'table';
		const step3Module = STEP_MODULES[ displayMode ];
		if ( step3Module && typeof step3Module.restoreStepData === 'function' ) {
			step3Module.restoreStepData(
				editorData.stepConfig.table,
				editorData.stepConfig.columns || [],
				editorData.stepConfig.availableColumns || []
			);
		}
	}

	// Steps 4-5: joins / conditions
	wizardState.data.joins      = editorData.joins      || [];
	wizardState.data.conditions = editorData.conditions || [];

	// Step 6: orders
	wizardState.data.orders = editorData.orders || [];
	stepOrder.setStepState( { orders: wizardState.data.orders } );

	// Step 7: limit
	if ( editorData.limit !== undefined ) {
		wizardState.data.limit  = editorData.limit;
		wizardState.data.noLimit = !! editorData.noLimit;
	}

	// Step 8: cache
	if ( editorData.cache ) {
		wizardState.data.cache = editorData.cache;
		if ( typeof stepCache.restoreStep === 'function' ) {
			stepCache.restoreStep( editorData.cache );
		}
	}

	if ( editorData.columnAliases ) {
		if ( Array.isArray( editorData.columnAliases ) ) {
			wizardState.data.columnAliases = editorData.columnAliases;
		} else if ( typeof editorData.columnAliases === 'object' ) {
			wizardState.data.columnAliases = Object.keys( editorData.columnAliases ).map( function( key ) {
				return {
					key: key,
					alias: editorData.columnAliases[ key ] || key
				};
			} );
		}
	}

	if ( editorData.outputConfig ) {
		wizardState.data.outputConfig = editorData.outputConfig;
	}

	if ( editorData.chartAxisConfig ) {
		wizardState.data.chartAxisConfig = { ...editorData.chartAxisConfig };
	}

	if ( editorData.chartTheme ) {
		wizardState.data.chartTheme = editorData.chartTheme;
	}

	$( '#dwm-wizard-container' ).show();
	goToStep( 1 );
	updateStep1NextButton();
}

export function hideWizard() {
	$( '#dwm-wizard-container' ).hide();
}

export function resetWizard() {
	wizardState.currentStep = 1;
	wizardState.data = {};
	wizardState.pendingDisplayTypeChange = '';

	clearAllWizardFields();

	$( '#dwm-creation-method' ).val( '' );
	$( '#dwm-widget-form' )[0].reset();
	$( '#dwm-widget-id' ).val( '' );
	$( '#dwm-creation-method' ).val( '' );
	$( '#dwm-builder-config' ).val( '' );
	$( '#dwm-chart-type' ).val( '' );
	$( '#dwm-chart-config' ).val( '' );
	$( 'input[name="dwm_creation_method"]' ).prop( 'checked', false );

	$( '#dwm-wizard-container' ).hide();
	$( '#dwm-wizard-footer' ).hide();
	$( '#dwm-switch-to-scratch' ).hide();
	$( '#dwm-widget-form' ).hide();
	$( '#dwm-widget-editor-modal .dwm-modal-footer' ).hide();
	$( '#dwm-creation-method-step' ).show();

	// Reset title
	$( '#dwm-editor-title' ).html(
		'<span class="dashicons dashicons-plus-alt2"></span> Create New Widget ' + EDITOR_TITLE_HELP_BUTTON_HTML
	);

	// Hide Next button (will be shown conditionally as steps are visited)
	$( '.dwm-wizard-next' ).hide();
}

// ── Step 9 / 10 Mode Config ─────────────────────────────────────────────────

function getStep9ConfigByMode( mode ) {
	if ( mode === 'table' ) {
		return {
			title: 'Column Names & Ordering',
			description: 'Customize column display names and reorder columns in your widget output.',
			docsPage: 'feature-html-php-templates',
			helpLabel: 'View help for column names and ordering',
			containerId: 'table',
			render: function() {
				if ( hasSavedColumnConfig() ) {
					stepColumns.restoreStep( { columns: wizardState.data.columnAliases } );
				} else {
					stepColumns.populateColumns( getEnrichedStepConfig() );
				}
			}
		};
	}

	if ( mode === 'bar' ) {
		return {
			title: 'Bar Chart Axis Setup',
			description: 'Choose which column labels the X-axis and which numeric columns power the Y-axis bars.',
			docsPage: 'feature-visual-builder-chart-config',
			helpLabel: 'View help for bar chart axis setup',
			containerId: 'bar',
			render: function() {
				stepBarAxis.populateAxes( getEnrichedStepConfig(), wizardState.data.chartAxisConfig || {} );
			}
		};
	}

	if ( mode === 'line' ) {
		return {
			title: 'Line Chart Axis Setup',
			description: 'Choose which column labels the X-axis and which numeric columns are plotted as line series.',
			docsPage: 'feature-visual-builder-chart-config',
			helpLabel: 'View help for line chart axis setup',
			containerId: 'line',
			render: function() {
				stepLineAxis.populateAxes( getEnrichedStepConfig(), wizardState.data.chartAxisConfig || {} );
			}
		};
	}

	if ( mode === 'pie' ) {
		return {
			title: 'Pie Chart Data Setup',
			description: 'Choose the label column and a single numeric value column for pie slices.',
			docsPage: 'feature-visual-builder-chart-config',
			helpLabel: 'View help for pie chart setup',
			containerId: 'pie',
			render: function() {
				stepPieConfig.populateConfig( getEnrichedStepConfig(), wizardState.data.chartAxisConfig || {} );
			}
		};
	}

	if ( mode === 'doughnut' ) {
		return {
			title: 'Doughnut Chart Data Setup',
			description: 'Choose the label column and a single numeric value column for doughnut segments.',
			docsPage: 'feature-visual-builder-chart-config',
			helpLabel: 'View help for doughnut chart setup',
			containerId: 'doughnut',
			render: function() {
				stepDoughnutConfig.populateConfig( getEnrichedStepConfig(), wizardState.data.chartAxisConfig || {} );
			}
		};
	}

	if ( mode === 'list' ) {
		return {
			title: 'Column Names & Ordering',
			description: 'Customize column display names and configure links and formatting for your list widget.',
			docsPage: 'feature-html-php-templates',
			helpLabel: 'View help for list column configuration',
			containerId: 'list',
			render: function() {
				if ( hasSavedColumnConfig() ) {
					stepColumns.restoreStep( { columns: wizardState.data.columnAliases } );
				} else {
					stepColumns.populateColumns( getEnrichedStepConfig() );
				}
			}
		};
	}

	if ( mode === 'button' ) {
		return {
			title: 'Column Names & Ordering',
			description: 'Customize column display names and configure links for your button widget.',
			docsPage: 'feature-html-php-templates',
			helpLabel: 'View help for button column configuration',
			containerId: 'button',
			render: function() {
				if ( hasSavedColumnConfig() ) {
					stepColumns.restoreStep( { columns: wizardState.data.columnAliases } );
				} else {
					stepColumns.populateColumns( getEnrichedStepConfig() );
				}
			}
		};
	}

	if ( mode === 'card-list' ) {
		return {
			title: 'Column Names & Ordering',
			description: 'Customize column display names and configure links and formatting for your card list widget.',
			docsPage: 'feature-html-php-templates',
			helpLabel: 'View help for card list column configuration',
			containerId: 'card-list',
			render: function() {
				if ( hasSavedColumnConfig() ) {
					stepColumns.restoreStep( { columns: wizardState.data.columnAliases } );
				} else {
					stepColumns.populateColumns( getEnrichedStepConfig() );
				}
			}
		};
	}

	// Fallback — should not reach here
	return {
		title: 'Column Names & Ordering',
		description: 'Customize column display names and reorder columns in your widget output.',
		docsPage: 'feature-html-php-templates',
		helpLabel: 'View help for column names and ordering',
		containerId: 'table',
		render: function() {
			if ( hasSavedColumnConfig() ) {
				stepColumns.restoreStep( { columns: wizardState.data.columnAliases } );
			} else {
				stepColumns.populateColumns( getEnrichedStepConfig() );
			}
		}
	};
}

function hasSavedColumnConfig() {
	return Array.isArray( wizardState.data.columnAliases ) && wizardState.data.columnAliases.length > 0;
}

function getStep10ThemeByMode( mode ) {
	if ( mode === 'table' ) {
		return {
			title: 'Choose Table Theme',
			description: 'Select a pre-designed theme to style your widget table.',
			docsPage: 'feature-custom-css',
			helpLabel: 'View help for widget table themes',
			containerId: 'table',
			restore: function() {
				stepTheme.restoreStep( { theme: wizardState.data.theme || 'default' } );
			}
		};
	}

	if ( mode === 'list' ) {
		return {
			title: 'Choose List Theme',
			description: 'Select a pre-designed theme to style your list widget.',
			docsPage: 'feature-display-list',
			helpLabel: 'View help for list themes',
			containerId: 'list',
			restore: function() { stepListTheme.restoreStep( { theme: wizardState.data.theme || 'clean' } ); }
		};
	}

	if ( mode === 'button' ) {
		return {
			title: 'Choose Button Theme',
			description: 'Select a pre-designed theme to style your button widget.',
			docsPage: 'feature-display-button',
			helpLabel: 'View help for button themes',
			containerId: 'button',
			restore: function() { stepButtonTheme.restoreStep( { theme: wizardState.data.theme || 'solid' } ); }
		};
	}

	if ( mode === 'card-list' ) {
		return {
			title: 'Choose Card List Theme',
			description: 'Select a pre-designed theme to style your card list widget.',
			docsPage: 'feature-display-card-list',
			helpLabel: 'View help for card list themes',
			containerId: 'card-list',
			restore: function() { stepCardListTheme.restoreStep( { theme: wizardState.data.theme || 'elevated' } ); }
		};
	}

	const docsByMode = {
		bar: 'feature-display-bar',
		line: 'feature-display-line',
		pie: 'feature-display-pie',
		doughnut: 'feature-display-doughnut'
	};
	const titleByMode = {
		bar: 'Choose Bar Chart Theme',
		line: 'Choose Line Chart Theme',
		pie: 'Choose Pie Chart Theme',
		doughnut: 'Choose Doughnut Chart Theme'
	};
	const helpByMode = {
		bar: 'View help for bar chart themes',
		line: 'View help for line chart themes',
		pie: 'View help for pie chart themes',
		doughnut: 'View help for doughnut chart themes'
	};
	const restoreByMode = {
		bar: function() { stepBarTheme.restoreStep( { theme: wizardState.data.chartTheme || 'classic' } ); },
		line: function() { stepLineTheme.restoreStep( { theme: wizardState.data.chartTheme || 'classic' } ); },
		pie: function() { stepPieTheme.restoreStep( { theme: wizardState.data.chartTheme || 'classic' } ); },
		doughnut: function() { stepDoughnutTheme.restoreStep( { theme: wizardState.data.chartTheme || 'classic' } ); }
	};

	return {
		title: titleByMode[ mode ] || 'Choose Chart Theme',
		description: 'Select one of six palette themes for your chart datasets.',
		docsPage: docsByMode[ mode ] || 'feature-display-bar',
		helpLabel: helpByMode[ mode ] || 'View help for chart themes',
		containerId: mode,
		restore: restoreByMode[ mode ] || restoreByMode.bar
	};
}

function normalizeWizardHelpButtons() {
	$( '.dwm-wizard-step' ).each( function() {
		const $step = $( this );
		const $help = $step.find( '> .dwm-wizard-step-help, .dwm-wizard-step-header > .dwm-wizard-step-help' ).first();
		if ( ! $help.length ) {
			return;
		}

		const $header = $step.find( '.dwm-wizard-step-header' ).first();
		if ( ! $header.length ) {
			return;
		}

		let $titleRow = $header.find( '.dwm-wizard-step-header-title-row' ).first();
		if ( ! $titleRow.length ) {
			const $h3 = $header.find( 'h3' ).first();
			if ( ! $h3.length ) {
				return;
			}
			$titleRow = $( '<div class="dwm-wizard-step-header-title-row"></div>' );
			$h3.before( $titleRow );
			$titleRow.append( $h3 );
		}

		if ( ! $titleRow.find( '.dwm-wizard-step-help' ).length ) {
			$titleRow.append( $help );
		}
	} );
}

// ── Step Navigation ──────────────────────────────────────────────────────────

function goToStep( step ) {
	wizardState.currentStep = step;
	$( '.dwm-wizard-step' ).removeClass( 'active' );
	$( `.dwm-wizard-step[data-wizard-step="${ step }"]` ).addClass( 'active' );

	// Manage shared footer button states
	$( '.dwm-wizard-back' ).toggle( step > 1 );
	$( '.dwm-wizard-preview' ).toggle( step > 3 );
	if ( step === 10 ) {
		$( '.dwm-wizard-next' ).text( 'Finish' ).show();
	} else if ( step >= 4 ) {
		$( '.dwm-wizard-next' ).text( 'Next' ).show();
	} else {
		$( '.dwm-wizard-next' ).text( 'Next' );
	}

	// Step 8: render cache step
	if ( step === 8 ) {
		stepCache.renderStep( $( '#dwm-wizard-step8-content' ), wizardState );
	}

	// Update step 2 header with widget name
	if ( wizardState.data.name ) {
		$( '#dwm-wizard-step2-widget-name' ).text( wizardState.data.name );
	}

	// Update modal title with widget name once past step 1
	if ( step > 1 && wizardState.data.name ) {
		$( '#dwm-editor-title' ).html(
			'<span class="dashicons dashicons-plus-alt2"></span> Create New Widget: ' +
			$( '<span>' ).text( wizardState.data.name ).html() +
			' ' + EDITOR_TITLE_HELP_BUTTON_HTML
		);
	} else {
		$( '#dwm-editor-title' ).html(
			'<span class="dashicons dashicons-plus-alt2"></span> Create New Widget ' +
			EDITOR_TITLE_HELP_BUTTON_HTML
		);
	}

	// Step 1: initialize Next button visibility
	if ( step === 1 ) {
		updateStep1NextButton();
	}

	// Step 2: restore display mode selection and initialize Next button visibility
	if ( step === 2 ) {
		if ( wizardState.data.displayMode ) {
			$( `input[name="dwm_wizard_display_mode"][value="${ wizardState.data.displayMode }"]` ).prop( 'checked', true );
		}
		updateStep2NextButton();
	}

	// Step 3: initialize Next button visibility and restore columns if table is selected
	if ( step === 3 ) {
		const step3Module = getActiveStepModule();
		if ( step3Module && typeof step3Module.initializeNextButton === 'function' ) {
			step3Module.initializeNextButton();
		}
		const tableSelector = getStep3TableSelector( wizardState.data.displayMode );
		ensureSearchableSelect( tableSelector, 'Select Primary Table', 'Search Tables' );
		refreshSearchableSelect( tableSelector );
		// If returning to step 3 with a table already selected, ensure columns are displayed
		const selectedTable = $( tableSelector ).val();
		if ( selectedTable && step3Module && typeof step3Module.restoreColumnsDisplay === 'function' ) {
			step3Module.restoreColumnsDisplay();
		}
	}

	// Step 4: update title with primary table name and initialize button text
	if ( step === 4 ) {
		const primaryTable = ( wizardState.data.stepConfig && wizardState.data.stepConfig.table ) || '';
		if ( primaryTable ) {
			// Safely escape table name and wrap in styled span
			const escapedTable = $( '<div>' ).text( primaryTable ).html();
			$( '#dwm-wizard-step4-title' ).html( 'Join Tables with <span class="dwm-primary-dark-text">' + escapedTable + '</span>' );
		} else {
			$( '#dwm-wizard-step4-title' ).text( 'Join Tables' );
		}
		// Re-render joins from state (in case user navigated back and forth)
		renderWizardJoins();
		updateStep4NextButton();
	}

	// Step 5: initialize filter button text
	if ( step === 5 ) {
		// Re-render conditions from state (in case user navigated back and forth)
		renderWizardConditions();
		updateStep5NextButton();
	}

	// Step 6: render order list
	if ( step === 6 ) {
		wizardState.data.orders = wizardState.data.orders || [];
		stepOrder.setStepState( { orders: wizardState.data.orders } );
		stepOrder.renderOrdersList();
		updateStep6NextButton();
	}

	// Step 7: render limit step
	if ( step === 7 ) {
		stepLimit.renderStep( $( '#dwm-wizard-step7-content' ), wizardState );
	}

	// Step 9: mode-specific configuration
	if ( step === 9 ) {
		const step9Config = getStep9ConfigByMode( wizardState.data.displayMode || 'table' );
		[ 'table', 'bar', 'line', 'pie', 'doughnut', 'list', 'button', 'card-list' ].forEach( function( mode ) {
			$( '#dwm-wizard-step9-' + mode ).toggle( step9Config.containerId === mode );
		} );
		$( '#dwm-wizard-step9-title' ).text( step9Config.title );
		$( '#dwm-wizard-step9-desc' ).text( step9Config.description );
		$( '#dwm-wizard-step9-help' )
			.attr( 'data-docs-page', step9Config.docsPage )
			.attr( 'aria-label', step9Config.helpLabel )
			.attr( 'title', step9Config.helpLabel );
		step9Config.render();
	}

	// Step 10: mode-specific theme
	if ( step === 10 ) {
		const step10Config = getStep10ThemeByMode( wizardState.data.displayMode || 'table' );
		[ 'table', 'bar', 'line', 'pie', 'doughnut', 'list', 'button', 'card-list' ].forEach( function( mode ) {
			$( '#dwm-wizard-step10-' + mode ).toggle( step10Config.containerId === mode );
		} );
		$( '#dwm-wizard-step10-title' ).text( step10Config.title );
		$( '#dwm-wizard-step10-desc' ).text( step10Config.description );
		$( '#dwm-wizard-step10-help' )
			.attr( 'data-docs-page', step10Config.docsPage )
			.attr( 'aria-label', step10Config.helpLabel )
			.attr( 'title', step10Config.helpLabel );
		step10Config.restore();
	}

	normalizeWizardHelpButtons();
}

// ── Validation ───────────────────────────────────────────────────────────────

function validateCurrentStep() {
	const step = wizardState.currentStep;

	if ( step === 1 ) {
		const name = $( '#dwm-wizard-widget-name' ).val().trim();
		if ( ! name ) {
			$( '#dwm-wizard-widget-name' ).addClass( 'dwm-input-error' ).focus();
			return false;
		}
		$( '#dwm-wizard-widget-name' ).removeClass( 'dwm-input-error' );

		const showDesc = $( '#dwm-wizard-show-description' ).is( ':checked' );
		const desc = $( '#dwm-wizard-widget-description' ).val().trim();
		if ( showDesc && ! desc ) {
			$( '#dwm-wizard-widget-description' ).addClass( 'dwm-input-error' ).focus();
			return false;
		}
		$( '#dwm-wizard-widget-description' ).removeClass( 'dwm-input-error' );
		return true;
	}

	if ( step === 2 ) {
		return !! $( 'input[name="dwm_wizard_display_mode"]:checked' ).val();
	}

	if ( step === 3 ) {
		const mod = getActiveStepModule();
		return mod ? mod.validateStep() : true;
	}

	if ( step === 9 ) {
		const mode = wizardState.data.displayMode || 'table';
		if ( mode === 'bar' ) {
			return stepBarAxis.validateStep();
		}
		if ( mode === 'line' ) {
			return stepLineAxis.validateStep();
		}
		if ( mode === 'pie' ) {
			return stepPieConfig.validateStep();
		}
		if ( mode === 'doughnut' ) {
			return stepDoughnutConfig.validateStep();
		}
		return stepColumns.validateStep();
	}

	// Step 4 (joins) is always valid — it's optional
	return true;
}

// ── Data Collection ──────────────────────────────────────────────────────────

function collectStepData() {
	const step = wizardState.currentStep;

	if ( step === 1 ) {
		wizardState.data.name = $( '#dwm-wizard-widget-name' ).val().trim();
		wizardState.data.description = $( '#dwm-wizard-widget-description' ).val().trim();
		wizardState.data.showDescription = $( '#dwm-wizard-show-description' ).is( ':checked' );
	}

	if ( step === 2 ) {
		wizardState.data.displayMode = $( 'input[name="dwm_wizard_display_mode"]:checked' ).val();
	}

	if ( step === 3 ) {
		const mod = getActiveStepModule();
		if ( mod ) {
			const oldTable = wizardState.data.stepConfig ? wizardState.data.stepConfig.table : '';
			const newConfig = mod.collectData();
			const newTable = newConfig.table;

			// If the primary table changed, clear joins and conditions
			if ( oldTable && newTable && oldTable !== newTable ) {
				wizardState.data.joins = [];
				wizardState.data.conditions = [];
			}

			wizardState.data.stepConfig = newConfig;
		}
	}

	if ( step === 6 ) {
		// Orders are managed in stepOrder module
		const orderData = stepOrder.collectData();
		wizardState.data.orders = orderData.orders;
	}

	if ( step === 7 ) {
		// Limit is managed in stepLimit module
		const limitData = stepLimit.collectData();
		wizardState.data.limit = limitData.limit;
		wizardState.data.noLimit = limitData.noLimit;
	}

	if ( step === 8 ) {
		// Cache is managed in stepCache module
		wizardState.data.cache = stepCache.collectData();
	}

	if ( step === 9 ) {
		const mode = wizardState.data.displayMode || 'table';
		if ( mode === 'bar' ) {
			wizardState.data.chartAxisConfig = stepBarAxis.getStepConfig();
			delete wizardState.data.columnAliases;
		} else if ( mode === 'line' ) {
			wizardState.data.chartAxisConfig = stepLineAxis.getStepConfig();
			delete wizardState.data.columnAliases;
		} else if ( mode === 'pie' ) {
			wizardState.data.chartAxisConfig = stepPieConfig.getStepConfig();
			delete wizardState.data.columnAliases;
		} else if ( mode === 'doughnut' ) {
			wizardState.data.chartAxisConfig = stepDoughnutConfig.getStepConfig();
			delete wizardState.data.columnAliases;
		} else {
			wizardState.data.columnAliases = stepColumns.getStepConfig().columns;
			wizardState.data.outputConfig = {
				display_mode: mode,
				columns: wizardState.data.columnAliases
			};
			delete wizardState.data.chartAxisConfig;
		}
	}

	if ( step === 10 ) {
		const mode = wizardState.data.displayMode || 'table';
		if ( mode === 'bar' ) {
			wizardState.data.chartTheme = stepBarTheme.getStepConfig().theme;
			delete wizardState.data.theme;
		} else if ( mode === 'line' ) {
			wizardState.data.chartTheme = stepLineTheme.getStepConfig().theme;
			delete wizardState.data.theme;
		} else if ( mode === 'pie' ) {
			wizardState.data.chartTheme = stepPieTheme.getStepConfig().theme;
			delete wizardState.data.theme;
		} else if ( mode === 'doughnut' ) {
			wizardState.data.chartTheme = stepDoughnutTheme.getStepConfig().theme;
			delete wizardState.data.theme;
		} else if ( mode === 'list' ) {
			wizardState.data.theme = stepListTheme.getStepConfig().theme;
			delete wizardState.data.chartTheme;
		} else if ( mode === 'button' ) {
			wizardState.data.theme = stepButtonTheme.getStepConfig().theme;
			delete wizardState.data.chartTheme;
		} else if ( mode === 'card-list' ) {
			wizardState.data.theme = stepCardListTheme.getStepConfig().theme;
			delete wizardState.data.chartTheme;
		} else {
			wizardState.data.theme = stepTheme.getStepConfig().theme;
			delete wizardState.data.chartTheme;
		}
	}
}

function clearStepData( step ) {
	if ( step === 2 ) {
		// Don't delete data - just clear UI
		$( 'input[name="dwm_wizard_display_mode"]' ).prop( 'checked', false );
	}

	if ( step === 4 ) {
		// Don't clear joins - they should only be cleared if primary table changes
		$( '#dwm-wizard-joins-list' ).empty();
	}

	if ( step === 5 ) {
		// Don't clear conditions - they should only be cleared if primary table changes
		$( '#dwm-wizard-conditions-list' ).empty();
	}

	if ( step === 6 ) {
		// Re-render order list when navigating back
		stepOrder.renderOrdersList();
	}

	if ( step === 7 ) {
		// Re-render limit step when navigating back
		stepLimit.renderStep( $( '#dwm-wizard-step7-content' ), wizardState );
	}

	if ( step === 8 ) {
		// Re-render cache step when navigating back
		stepCache.renderStep( $( '#dwm-wizard-step8-content' ), wizardState );
	}
}

function clearAllWizardFields() {
	$( '#dwm-wizard-widget-name' ).val( '' ).removeClass( 'dwm-input-error' );
	$( '#dwm-wizard-widget-description' ).val( '' ).prop( 'required', false ).attr( 'placeholder', 'Briefly describe what this widget displays' ).removeClass( 'dwm-input-error' );
	$( '#dwm-wizard-show-description' ).prop( 'checked', false );
	$( '#dwm-wizard-widget-description' ).closest( '.dwm-form-group' ).find( '.dwm-desc-required-asterisk' ).hide();
	$( 'input[name="dwm_wizard_display_mode"]' ).prop( 'checked', false );
	$( '#dwm-wizard-step3-content' ).empty();
	$( '#dwm-wizard-joins-list' ).empty();
	$( '#dwm-wizard-conditions-list' ).empty();
	$( '#dwm-wizard-orders-list' ).empty();
	$( '#dwm-wizard-step7-content' ).empty();
	$( '#dwm-wizard-step8-content' ).empty();
	[ 'table', 'bar', 'line', 'pie', 'doughnut', 'list', 'button', 'card-list' ].forEach( function( mode ) {
		$( '#dwm-wizard-step9-' + mode ).toggle( mode === 'table' );
		$( '#dwm-wizard-step10-' + mode ).toggle( mode === 'table' );
	} );
	$( '#dwm-wizard-step9-title' ).text( 'Column Names & Ordering' );
	$( '#dwm-wizard-step9-desc' ).text( 'Customize column display names and reorder columns in your widget output.' );
	$( '#dwm-wizard-step9-help' )
		.attr( 'data-docs-page', 'feature-html-php-templates' )
		.attr( 'aria-label', 'View help for column names and ordering' )
		.attr( 'title', 'View help for column names and ordering' );
	$( '#dwm-wizard-step10-title' ).text( 'Choose Table Theme' );
	$( '#dwm-wizard-step10-desc' ).text( 'Select a pre-designed theme to style your widget table.' );
	$( '#dwm-wizard-step10-help' )
		.attr( 'data-docs-page', 'feature-custom-css' )
		.attr( 'aria-label', 'View help for widget table themes' )
		.attr( 'title', 'View help for widget table themes' );

	Object.values( STEP_MODULES ).forEach( function( mod ) {
		mod.clearStep();
	});

	stepOrder.clearStep();
	stepOrder.renderOrdersList();
	stepLimit.clearStep();
	stepCache.clearStep();
	stepColumns.clearStep();
	stepTheme.clearStep();
	stepBarAxis.clearStep();
	stepBarTheme.clearStep();
	stepLineAxis.clearStep();
	stepLineTheme.clearStep();
	stepPieConfig.clearStep();
	stepPieTheme.clearStep();
	stepDoughnutConfig.clearStep();
	stepDoughnutTheme.clearStep();
	stepListTheme.clearStep();
	stepButtonTheme.clearStep();
	stepCardListTheme.clearStep();
}

// ── Complete Wizard ──────────────────────────────────────────────────────────

function completeWizard() {
	const displayMode = wizardState.data.displayMode || 'table';

	$( '#dwm-widget-name' ).val( wizardState.data.name );
	$( '#dwm-widget-description' ).val( wizardState.data.description || '' );

	// Carry show_description toggle state from wizard to editor form.
	const showDesc = !! wizardState.data.showDescription;
	$( '#dwm-show-description' ).prop( 'checked', showDesc );
	$( '#dwm-widget-description' ).prop( 'required', showDesc );

	if ( displayMode ) {
		$( `input[name="dwm_display_mode"][value="${ displayMode }"]` ).prop( 'checked', true ).trigger( 'change' );
	}

	if ( wizardState.data.stepConfig ) {
		prefillTableBuilder( wizardState.data.stepConfig );
	}

	// Pre-fill joins in the builder
	prefillBuilderJoins( wizardState.data.joins || [] );

	// Pre-fill conditions in the builder
	prefillBuilderConditions( wizardState.data.conditions || [] );

	// Pre-fill order by / limit in the builder
	prefillBuilderOrder( wizardState.data.orders || [], wizardState.data.limit, wizardState.data.noLimit );

	// Apply final display-specific configuration.
	if ( isChartDisplayMode( displayMode ) ) {
		prefillChartConfiguration(
			displayMode,
			wizardState.data.chartAxisConfig || {},
			wizardState.data.chartTheme || 'classic'
		);
		prefillThemeAssets( wizardState.data.chartTheme || 'classic', displayMode );
	} else {
		// Data display modes: table, list, button, card-list
		const defaultThemes = { table: 'default', list: 'clean', button: 'solid', 'card-list': 'elevated' };
		const theme = wizardState.data.theme || defaultThemes[ displayMode ] || 'default';
		prefillThemeAssets( theme, displayMode );
		$( '#dwm-chart-type' ).val( '' );
		$( '#dwm-chart-config' ).val( '' );

		// Render output controls synchronously to avoid save-race before async builder restore.
		const orderedColumns = Array.isArray( wizardState.data.columnAliases )
			? wizardState.data.columnAliases.map( function( col ) {
				return col.key || col.original;
			} ).filter( function( key ) { return !! key; } )
			: ( ( wizardState.data.stepConfig && wizardState.data.stepConfig.columns ) || [] );

		const outputConfig = wizardState.data.outputConfig || ( buildBuilderConfigFromWizard().output_config || null );
		renderOutputControls( orderedColumns, outputConfig );
	}

	// Apply cache settings from wizard to editor form fields.
	const cacheData = wizardState.data.cache || {};
	$( '#dwm-enable-caching' ).prop( 'checked', cacheData.enabled !== false ).trigger( 'change' );
	if ( cacheData.duration ) {
		$( '#dwm-cache-duration' ).val( cacheData.duration );
	}
	$( '#dwm-auto-refresh' ).val( cacheData.autoRefresh ? '1' : '0' );

	$( '#dwm-builder-config' ).val( JSON.stringify( buildBuilderConfigFromWizard() ) );

	$( '#dwm-wizard-container' ).hide();
	$( '#dwm-wizard-footer' ).hide();
	$( '#dwm-switch-to-scratch' ).hide();
	$( '#dwm-widget-form' ).show();
	$( '#dwm-widget-editor-modal .dwm-modal-footer' ).show();

	// Update title with widget name and show lightbulb
	var escapedName = $( '<span>' ).text( wizardState.data.name || '' ).html();
	$( '#dwm-editor-title' ).html(
		'<span class="dashicons dashicons-plus-alt2"></span> Create New Widget' +
		( escapedName ? ': ' + escapedName : '' ) + ' ' + EDITOR_TITLE_HELP_BUTTON_HTML
	);
}

// ── Public API ───────────────────────────────────────────────────────────────

export function getWizardData() {
	return { ...wizardState.data };
}

export function transitionToScratch() {
	collectStepData();
	completeWizard();
}
