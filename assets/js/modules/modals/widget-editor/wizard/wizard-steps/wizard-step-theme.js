/**
 * Dashboard Widget Manager - Wizard Step 9: Theme Selection
 *
 * Handles table theme selection for widget styling.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

/**
 * Initialize step 9 (theme)
 */
export function init() {
	// Theme selection handled by radio inputs
	$( document ).on( 'change', 'input[name="dwm_wizard_theme"]', function() {
		// Visual feedback is handled by CSS
	});
}

/**
 * Get step configuration data
 */
export function getStepConfig() {
	return {
		theme: $( 'input[name="dwm_wizard_theme"]:checked' ).val() || 'default'
	};
}

/**
 * Clear step data
 */
export function clearStep() {
	$( 'input[name="dwm_wizard_theme"][value="default"]' ).prop( 'checked', true );
}

/**
 * Restore step data from saved config
 */
export function restoreStep( themeConfig ) {
	if ( ! themeConfig || ! themeConfig.theme ) {
		clearStep();
		return;
	}

	const theme = themeConfig.theme || 'default';
	$( 'input[name="dwm_wizard_theme"][value="' + theme + '"]' ).prop( 'checked', true );
}
