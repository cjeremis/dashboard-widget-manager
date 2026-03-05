/**
 * Dashboard Widget Manager - Wizard Step 10 (Pie): Theme Selection
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;
const DEFAULT_THEME = 'classic';

export function init() {
	$( document ).on( 'change', 'input[name="dwm_wizard_pie_theme"]', function() {} );
}

export function getStepConfig() {
	return {
		theme: $( 'input[name="dwm_wizard_pie_theme"]:checked' ).val() || DEFAULT_THEME
	};
}

export function clearStep() {
	$( 'input[name="dwm_wizard_pie_theme"][value="' + DEFAULT_THEME + '"]' ).prop( 'checked', true );
}

export function restoreStep( themeConfig ) {
	if ( ! themeConfig || ! themeConfig.theme ) {
		clearStep();
		return;
	}
	$( 'input[name="dwm_wizard_pie_theme"][value="' + themeConfig.theme + '"]' ).prop( 'checked', true );
}
