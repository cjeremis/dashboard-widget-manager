/**
 * Dashboard Widget Manager - Wizard Step 10 (Bar): Theme Selection
 *
 * Handles bar chart palette theme selection.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

const DEFAULT_THEME = 'classic';

export function init() {
	$( document ).on( 'change', 'input[name="dwm_wizard_bar_theme"]', function() {
		// Selection styling handled in CSS.
	} );
}

export function getStepConfig() {
	return {
		theme: $( 'input[name="dwm_wizard_bar_theme"]:checked' ).val() || DEFAULT_THEME
	};
}

export function clearStep() {
	$( 'input[name="dwm_wizard_bar_theme"][value="' + DEFAULT_THEME + '"]' ).prop( 'checked', true );
}

export function restoreStep( themeConfig ) {
	if ( ! themeConfig || ! themeConfig.theme ) {
		clearStep();
		return;
	}

	const selectedTheme = themeConfig.theme || DEFAULT_THEME;
	$( 'input[name="dwm_wizard_bar_theme"][value="' + selectedTheme + '"]' ).prop( 'checked', true );
}
