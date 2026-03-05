/**
 * Dashboard Widget Manager - Wizard Step 8: Caching Configuration
 *
 * Handles caching configuration for query results.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;
let stepState = {};
let isInitialized = false;

/**
 * Render step 8 into the given container
 */
export function renderStep( $container, wizardState ) {
	const cache = wizardState.data.cache || {};
	const enabled = cache.enabled !== false;
	const duration = cache.duration || 1800;
	const keyStrategy = cache.keyStrategy || 'global';
	const autoRefresh = cache.autoRefresh === true;

	const presetDurations = [ 300, 600, 900, 1800, 3600 ];
	const isCustom = enabled && presetDurations.indexOf( duration ) === -1;

	const durations = [
		{ value: '300',   label: '5 minutes' },
		{ value: '600',   label: '10 minutes' },
		{ value: '900',   label: '15 minutes' },
		{ value: '1800',  label: '30 minutes' },
		{ value: '3600',  label: '1 hour' },
		{ value: 'custom', label: 'Custom' }
	];

	let durationOptions = '';
	durations.forEach( function( d ) {
		const isSelected = isCustom
			? d.value === 'custom'
			: parseInt( d.value, 10 ) === duration;
		durationOptions += '<option value="' + d.value + '"' + ( isSelected ? ' selected' : '' ) + '>' + d.label + '</option>';
	});

	$container.html(
		'<div class="dwm-wizard-step-header">' +
			'<h3>Caching Configuration</h3>' +
			'<p>Configure query result caching to improve performance and reduce database load.</p>' +
		'</div>' +
		'<div class="dwm-wizard-step-body">' +
			'<div class="dwm-limit-options">' +
				'<div class="dwm-limit-option">' +
					'<label class="dwm-radio-label">' +
						'<input type="radio" name="dwm_wizard_cache_option" value="enabled"' + ( enabled ? ' checked' : '' ) + '>' +
						' Enable caching' +
					'</label>' +
					'<div class="dwm-limit-input-group" id="dwm-cache-settings-section"' + ( enabled ? '' : ' style="display:none;"' ) + '>' +
						'<div class="dwm-form-group">' +
							'<label for="dwm-wizard-cache-duration">Cache Duration</label>' +
							'<select id="dwm-wizard-cache-duration" class="dwm-select">' +
								durationOptions +
							'</select>' +
						'</div>' +
						'<div class="dwm-form-group" id="dwm-cache-custom-duration"' + ( isCustom ? '' : ' style="display:none;"' ) + '>' +
							'<label for="dwm-wizard-cache-custom-seconds">Custom Duration (seconds)</label>' +
							'<input type="number" id="dwm-wizard-cache-custom-seconds" class="dwm-input-number" min="60" max="3600" value="' + ( isCustom ? duration : 1800 ) + '">' +
						'</div>' +
						'<input type="hidden" id="dwm-wizard-cache-key-strategy" value="global">' +
						'<label class="dwm-radio-label">' +
							'<input type="checkbox" id="dwm-wizard-cache-auto-refresh"' + ( autoRefresh ? ' checked' : '' ) + '>' +
							' Auto-Refresh on Data Change' +
						'</label>' +
					'</div>' +
				'</div>' +
				'<div class="dwm-limit-option">' +
					'<label class="dwm-radio-label">' +
						'<input type="radio" name="dwm_wizard_cache_option" value="disabled"' + ( ! enabled ? ' checked' : '' ) + '>' +
						' No caching (run query on every load)' +
					'</label>' +
				'</div>' +
			'</div>' +
			'<div class="dwm-step-preview-section">' +
				'<div class="dwm-step-preview-header">' +
					'<span>Cache Preview</span>' +
				'</div>' +
				'<div id="dwm-cache-preview-content" class="dwm-preview-content empty">' +
					'Configure caching above to see the preview.' +
				'</div>' +
			'</div>' +
		'</div>'
	);

	updateCachePreview();
}

/**
 * Update cache preview text based on current settings
 */
function updateCachePreview() {
	const $preview = $( '#dwm-cache-preview-content' );
	if ( ! $preview.length ) {
		return;
	}

	const enabled = $( 'input[name="dwm_wizard_cache_option"]:checked' ).val() === 'enabled';
	if ( ! enabled ) {
		$preview.addClass( 'empty' ).text( 'No caching - query runs on every widget load.' );
		return;
	}

	const duration = $( '#dwm-wizard-cache-duration' ).val();

	let durationLabel;
	if ( duration === 'custom' ) {
		const seconds = parseInt( $( '#dwm-wizard-cache-custom-seconds' ).val(), 10 ) || 1800;
		durationLabel = seconds + 's';
	} else {
		const labels = {
			'300': '5 min',
			'600': '10 min',
			'900': '15 min',
			'1800': '30 min',
			'3600': '1 hr',
		};
		durationLabel = labels[ duration ] || duration + 's';
	}

	$preview.removeClass( 'empty' ).text( 'Cache: ' + durationLabel );
}

/**
 * Initialize step 8 event handlers
 */
export function init() {
	if ( isInitialized ) {
		return;
	}
	isInitialized = true;

	$( document ).on( 'change', 'input[name="dwm_wizard_cache_option"]', function() {
		const enabled = $( this ).val() === 'enabled';
		$( '#dwm-cache-settings-section' ).toggle( enabled );
		updateCachePreview();
	} );

	$( document ).on( 'change', '#dwm-wizard-cache-duration', function() {
		$( '#dwm-cache-custom-duration' ).toggle( $( this ).val() === 'custom' );
		updateCachePreview();
	} );

	$( document ).on( 'input', '#dwm-wizard-cache-custom-seconds', function() {
		updateCachePreview();
	} );


}

/**
 * Collect step data
 */
export function collectData() {
	if ( ! $( '#dwm-wizard-cache-duration' ).length ) {
		return Object.assign( {}, stepState );
	}

	const enabled = $( 'input[name="dwm_wizard_cache_option"]:checked' ).val() === 'enabled';

	if ( ! enabled ) {
		stepState = { enabled: false };
		return Object.assign( {}, stepState );
	}

	const duration = $( '#dwm-wizard-cache-duration' ).val();
	const customSeconds = Math.max( 60, Math.min( 3600, parseInt( $( '#dwm-wizard-cache-custom-seconds' ).val(), 10 ) || 1800 ) );

	stepState = {
		enabled: true,
		duration: duration === 'custom' ? customSeconds : parseInt( duration, 10 ),
		keyStrategy: $( '#dwm-wizard-cache-key-strategy' ).val(),
		autoRefresh: $( '#dwm-wizard-cache-auto-refresh' ).prop( 'checked' )
	};

	return Object.assign( {}, stepState );
}

/**
 * Get step configuration data (legacy - delegates to collectData)
 */
export function getStepConfig() {
	return collectData();
}

/**
 * Clear step data
 */
export function clearStep() {
	stepState = {};
	$( 'input[name="dwm_wizard_cache_option"][value="enabled"]' ).prop( 'checked', true );
	$( '#dwm-wizard-cache-duration' ).val( '1800' );
	$( '#dwm-wizard-cache-custom-seconds' ).val( '1800' );
	$( '#dwm-wizard-cache-auto-refresh' ).prop( 'checked', false );
	$( '#dwm-cache-settings-section' ).show();
	$( '#dwm-cache-custom-duration' ).hide();
}

/**
 * Restore step data from saved config
 */
export function restoreStep( cacheConfig ) {
	if ( ! cacheConfig ) {
		clearStep();
		return;
	}
	stepState = Object.assign( {}, cacheConfig );

	const enabled = cacheConfig.enabled !== false;
	$( 'input[name="dwm_wizard_cache_option"][value="' + ( enabled ? 'enabled' : 'disabled' ) + '"]' ).prop( 'checked', true );
	$( '#dwm-cache-settings-section' ).toggle( enabled );

	if ( enabled ) {
		const presetDurations = [ 300, 600, 900, 1800, 3600 ];
		if ( presetDurations.indexOf( cacheConfig.duration ) !== -1 ) {
			$( '#dwm-wizard-cache-duration' ).val( cacheConfig.duration );
			$( '#dwm-cache-custom-duration' ).hide();
		} else {
			$( '#dwm-wizard-cache-duration' ).val( 'custom' );
			$( '#dwm-wizard-cache-custom-seconds' ).val( cacheConfig.duration || 1800 );
			$( '#dwm-cache-custom-duration' ).show();
		}
		$( '#dwm-wizard-cache-auto-refresh' ).prop( 'checked', cacheConfig.autoRefresh === true );
	}
}
