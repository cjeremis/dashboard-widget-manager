/**
 * Dashboard Widget Manager - Settings Page
 *
 * Handles settings form save, reset, export, import, and view data functionality
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { ajax } from '../partials/ajax.js';
import { showLoading, hideLoading } from '../partials/notifications.js';

const $ = jQuery;

/**
 * Initialize settings page
 */
export function initSettings() {
	initSettingsForm();
}

// ============================================================
// Settings Form
// ============================================================

/**
 * Derive a human-readable label for a setting input.
 */
function getSettingLabel($input) {
	// Try the associated <label> via for= or wrapping parent.
	const id = $input.attr('id');
	if (id) {
		const $label = $('label[for="' + id + '"]').not('.dwm-toggle');
		if ($label.length) return $label.first().text().trim();
	}

	// Try the closest form-group's label.
	const $group = $input.closest('.dwm-form-group');
	if ($group.length) {
		const $lbl = $group.find('.dwm-form-label').first();
		if ($lbl.length) return $lbl.text().trim();
	}

	// Fallback: humanize the setting key (e.g. hide_help_dropdown → Hide Help Dropdown).
	const name = $input.attr('name') || '';
	const match = name.match(/settings\[([^\]]+)\]/);
	if (match) {
		return match[1].replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
	}

	return 'Setting';
}

function initSettingsForm() {
	const DWMToast = window.DWMToast;
	let $activeSubmitBtn = null;

	// Track which submit button was clicked so we can scope the save.
	$(document).on('click', '#dwm-settings-form [type="submit"]', function() {
		$activeSubmitBtn = $(this);
	});

	// Section-scoped form submit — only saves inputs in the clicked button's section,
	// excluding [data-autosave] inputs (those save immediately on change).
	$(document).on('submit', '#dwm-settings-form', function(e) {
		e.preventDefault();

		const $form     = $(this);
		const $btn      = $activeSubmitBtn || $form.find('[type="submit"]').first();
		const $section  = $btn.closest('.dwm-section');
		const settings  = {};

		$section.find('input, select, textarea').not('[data-autosave]').each(function() {
			const name  = $(this).attr('name');
			if (!name) return;
			const match = name.match(/settings\[([^\]]+)\]/);
			if (!match) return;

			if ($(this).is('[type="checkbox"]')) {
				settings[match[1]] = $(this).is(':checked') ? 1 : 0;
			} else {
				settings[match[1]] = $(this).val();
			}
		});

		showLoading($btn);

		const sectionTitle = $section.find('.dwm-section-header h3, .dwm-section-title').first().text().trim() || 'Settings';

		ajax(
			'dwm_save_settings',
			{ settings },
			function(data) {
				hideLoading($btn);
				DWMToast.success(sectionTitle + ' saved.', { title: sectionTitle });
				if (data.warning) {
					DWMToast.warning(data.warning);
				}
			},
			function(data) {
				hideLoading($btn);
				DWMToast.error(data.message || 'Failed to save ' + sectionTitle + '.', { title: sectionTitle });
			}
		);
	});

	// Auto-save toggle inputs immediately on change.
	$(document).on('change', '[data-autosave]', function() {
		const $input = $(this);
		const name   = $input.attr('name');
		if (!name) return;
		const match  = name.match(/settings\[([^\]]+)\]/);
		if (!match) return;

		const label    = getSettingLabel($input);
		const isOn     = $input.is(':checked');
		const settings = {};
		settings[match[1]] = isOn ? 1 : 0;

		ajax(
			'dwm_save_settings',
			{ settings },
			function() {
				DWMToast.success(label + ' ' + (isOn ? 'enabled' : 'disabled') + '.', { title: label });
			},
			function(data) {
				DWMToast.error(data.message || 'Failed to save ' + label + '.', { title: label });
			}
		);
	});

	// Excluded tables checkbox grid — unchecked = excluded
	function updateExcludedTablesValue() {
		const $checkboxes = $('.dwm-table-checkbox');
		const $unchecked  = $checkboxes.not(':checked');
		if ($unchecked.length === 0) {
			$('#dwm-excluded-tables-value').val('');
		} else {
			const tables = [];
			$unchecked.each(function() {
				tables.push($(this).val());
			});
			$('#dwm-excluded-tables-value').val(tables.join('\n'));
		}
	}

	$(document).on('change', '.dwm-table-checkbox', updateExcludedTablesValue);

	$(document).on('click', '#dwm-select-all-tables', function(e) {
		e.preventDefault();
		$('.dwm-table-checkbox').prop('checked', true);
		updateExcludedTablesValue();
	});

	$(document).on('click', '#dwm-deselect-all-tables', function(e) {
		e.preventDefault();
		$('.dwm-table-checkbox').prop('checked', false);
		updateExcludedTablesValue();
	});

	// Hidden dashboard widgets — checked = hidden
	function updateHiddenWidgetsValue() {
		const $checked = $('.dwm-widget-hide-checkbox:checked');
		if ($checked.length === 0) {
			$('#dwm-hidden-widgets-value').val('');
		} else {
			const ids = [];
			$checked.each(function() {
				ids.push($(this).val());
			});
			$('#dwm-hidden-widgets-value').val(ids.join('\n'));
		}
	}

	$(document).on('change', '.dwm-widget-hide-checkbox', updateHiddenWidgetsValue);

	$(document).on('click', '#dwm-select-all-widgets', function(e) {
		e.preventDefault();
		$('.dwm-widget-hide-checkbox').prop('checked', true);
		updateHiddenWidgetsValue();
	});

	$(document).on('click', '#dwm-deselect-all-widgets', function(e) {
		e.preventDefault();
		$('.dwm-widget-hide-checkbox').prop('checked', false);
		updateHiddenWidgetsValue();
	});
}
