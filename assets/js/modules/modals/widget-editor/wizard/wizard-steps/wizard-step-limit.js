/**
 * Dashboard Widget Manager - Wizard Step: Limit Results
 *
 * Handles LIMIT configuration for controlling how many rows are returned.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

let stepState = {
	limit: 10,
	noLimit: true
};

export function init() {
	// Event handlers will be set up in wizard.js
}

export function renderStep( $container, wizardState ) {
	stepState.limit = wizardState.data.limit || 10;
	stepState.noLimit = wizardState.data.noLimit !== undefined ? wizardState.data.noLimit : false;

	const isEnabled = ! stepState.noLimit;

	$container.html(
		'<div class="dwm-wizard-step-header">' +
			'<div class="dwm-wizard-step-header-title-row">' +
				'<h3>Limit Results</h3>' +
			'</div>' +
			'<p>Optionally restrict how many rows your widget returns.</p>' +
		'</div>' +
		'<div class="dwm-wizard-step-body">' +
			'<div class="dwm-step-section">' +
				'<div class="dwm-step-section-header">' +
					'<div>' +
						'<span class="dwm-step-section-title">Enable Row Limit</span>' +
					'</div>' +
					'<label class="dwm-toggle">' +
						'<input type="checkbox" id="dwm-wizard-limit-toggle"' + ( isEnabled ? ' checked' : '' ) + '>' +
						'<span class="dwm-toggle-slider"></span>' +
					'</label>' +
				'</div>' +
				'<div id="dwm-limit-input-group"' + ( isEnabled ? '' : ' style="display:none;"' ) + '>' +
					'<label for="dwm-wizard-limit">Number of rows</label>' +
					'<input type="number" id="dwm-wizard-limit" class="dwm-input-number" value="' + stepState.limit + '" min="1" max="1000">' +
				'</div>' +
			'</div>' +
		'</div>'
	);
}

export function validateStep() {
	// Always valid - we have defaults
	return true;
}

export function collectData() {
	return {
		limit: stepState.noLimit ? null : stepState.limit,
		noLimit: stepState.noLimit
	};
}

export function clearStep() {
	stepState.limit = 10;
	stepState.noLimit = false;
}

export function getStepState() {
	return stepState;
}

export function setStepState( newState ) {
	stepState = newState;
}

export function handleLimitToggleChange() {
	const isEnabled = $( '#dwm-wizard-limit-toggle' ).is( ':checked' );
	stepState.noLimit = ! isEnabled;

	if ( isEnabled ) {
		$( '#dwm-limit-input-group' ).show();
		stepState.limit = Math.max( 1, Math.min( 1000, parseInt( $( '#dwm-wizard-limit' ).val(), 10 ) || 10 ) );
	} else {
		$( '#dwm-limit-input-group' ).hide();
	}
}

export function handleLimitInputChange() {
	stepState.limit = Math.max( 1, Math.min( 1000, parseInt( $( '#dwm-wizard-limit' ).val(), 10 ) || 10 ) );
}
