/**
 * Dashboard Widget Manager - Wizard State Module
 *
 * Shared state, constants, and join context helpers for the creation wizard.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

// ── Constants ────────────────────────────────────────────────────────────────
export const TOTAL_STEPS = 10;
export const CACHE_KEY_COLUMNS_PREFIX = 'dwm_columns_cache_';
export const CACHE_DURATION = 60 * 60 * 1000; // 1 hour

// ── Step Modules Map ─────────────────────────────────────────────────────────
// Populated by wizard.js via setStepModules() during init to avoid circular imports.
export let STEP_MODULES = {};

export function setStepModules( modules ) {
	STEP_MODULES = modules;
}

// ── Wizard State ─────────────────────────────────────────────────────────────
export let wizardState = {
	currentStep: 1,
	data: {},
	joinConfigState: {
		primaryTableColumns: [],
		joinTableColumns: [],
		compatibilityMap: {}
	},
	filterConfigState: {
		selectedColumn: null,
		selectedColumnType: null,
		selectedTable: null,
		validationResults: []
	},
	orderConfigState: {
		editingIndex: null,
		validationResults: []
	},
	pendingDisplayTypeChange: '',
	builderOrderMode: false
};

// ── Editor Join Context ──────────────────────────────────────────────────────
// Set when the join config modal is opened from the widget editor (not the wizard).
let _editorJoinContext = null;

export function getEditorJoinContext() {
	return _editorJoinContext;
}

export function setEditorJoinContext( context ) {
	_editorJoinContext = context;
}

export function getJoinContextPrimaryTable() {
	return _editorJoinContext
		? _editorJoinContext.primaryTable
		: ( ( wizardState.data.stepConfig && wizardState.data.stepConfig.table ) || '' );
}

export function getJoinContextPrimaryColumns() {
	return _editorJoinContext
		? ( _editorJoinContext.primaryColumns || [] )
		: ( ( wizardState.data.stepConfig && wizardState.data.stepConfig.availableColumns ) || [] );
}

export function getJoinContextSelectedColumns() {
	return _editorJoinContext
		? ( _editorJoinContext.selectedColumns || [] )
		: ( ( wizardState.data.stepConfig && wizardState.data.stepConfig.columns ) || [] );
}

export function getJoinContextExistingJoins() {
	return _editorJoinContext
		? ( _editorJoinContext.existingJoins || [] )
		: ( wizardState.data.joins || [] );
}
