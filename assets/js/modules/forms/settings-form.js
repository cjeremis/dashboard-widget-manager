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

		if (window.tinymce && typeof window.tinymce.triggerSave === 'function') {
			window.tinymce.triggerSave();
		}

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

		// Lock the toggle immediately — re-enabled once the toast fully disappears.
		$input.prop('disabled', true);
		const unlock = function() { $input.prop('disabled', false); };

		ajax(
			'dwm_save_settings',
			{ settings },
			function() {
				DWMToast.success(label + ' ' + (isOn ? 'enabled' : 'disabled') + '.', { title: label, onClose: unlock });
			},
			function(data) {
				DWMToast.error(data.message || 'Failed to save ' + label + '.', { title: label, onClose: unlock });
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

	// Allowed roles checkbox grid — checked = allowed
	function updateAllowedRolesValue() {
		const $checked = $('.dwm-access-role-checkbox:checked');
		if ($checked.length === 0) {
			$('#dwm-access-allowed-roles-value').val('');
			return;
		}

		const roles = [];
		$checked.each(function() {
			roles.push($(this).val());
		});
		$('#dwm-access-allowed-roles-value').val(roles.join('\n'));
	}

	$(document).on('change', '.dwm-access-role-checkbox', updateAllowedRolesValue);

	$(document).on('click', '#dwm-select-all-access-roles', function(e) {
		e.preventDefault();
		$('.dwm-access-role-checkbox').prop('checked', true);
		updateAllowedRolesValue();
	});

	$(document).on('click', '#dwm-deselect-all-access-roles', function(e) {
		e.preventDefault();
		$('.dwm-access-role-checkbox').prop('checked', false);
		updateAllowedRolesValue();
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

	// Hidden 3rd-party dashboard widgets — checked = hidden
	function updateHiddenThirdPartyWidgetsValue() {
		const $checked = $('.dwm-third-party-widget-hide-checkbox:checked');
		if ($checked.length === 0) {
			$('#dwm-hidden-third-party-widgets-value').val('');
		} else {
			const ids = [];
			$checked.each(function() {
				ids.push($(this).val());
			});
			$('#dwm-hidden-third-party-widgets-value').val(ids.join('\n'));
		}
	}

	$(document).on('change', '.dwm-third-party-widget-hide-checkbox', updateHiddenThirdPartyWidgetsValue);

	$(document).on('click', '#dwm-select-all-third-party-widgets', function(e) {
		e.preventDefault();
		$('.dwm-third-party-widget-hide-checkbox').prop('checked', true);
		updateHiddenThirdPartyWidgetsValue();
	});

	$(document).on('click', '#dwm-deselect-all-third-party-widgets', function(e) {
		e.preventDefault();
		$('.dwm-third-party-widget-hide-checkbox').prop('checked', false);
		updateHiddenThirdPartyWidgetsValue();
	});

	// Restricted Users modal behavior.
	function escapeHtml(value) {
		return String(value || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function getRestrictedUserIds() {
		const ids = [];
		$('#dwm-restricted-users-list .dwm-restricted-user-item').each(function() {
			const id = parseInt($(this).attr('data-user-id'), 10);
			if (id) ids.push(id);
		});
		return ids;
	}

	function syncRestrictedUserIdsValue() {
		const ids = getRestrictedUserIds();
		$('#dwm-restricted-user-ids-value').val(ids.join('\n'));
	}

	function updateRestrictedUsersEmptyState() {
		const $list = $('#dwm-restricted-users-list');
		const $items = $list.find('.dwm-restricted-user-item');
		const $empty = $('#dwm-restricted-users-empty');
		if (!$empty.length) return;

		if ($items.length === 0) {
			$empty.text('No users are currently restricted.').show();
			return;
		}

		const visibleCount = $items.filter(':visible').length;
		if (visibleCount === 0) {
			$empty.text('No users match your search.').show();
		} else {
			$empty.hide();
		}
	}

	function appendRestrictedUser(user) {
		const id = parseInt(user.id, 10);
		if (!id) return false;

		if ($('#dwm-restricted-users-list .dwm-restricted-user-item[data-user-id="' + id + '"]').length) {
			return false;
		}

		const searchBlob = (user.display_name + ' ' + user.user_email + ' ' + user.user_login).toLowerCase();
		const html = '' +
			'<div class="dwm-restricted-user-item" data-user-id="' + id + '" data-search="' + escapeHtml(searchBlob) + '">' +
				'<div class="dwm-restricted-user-meta">' +
					'<strong>' + escapeHtml(user.display_name) + '</strong>' +
					'<span>' + escapeHtml(user.user_email) + '</span>' +
					'<code>' + escapeHtml(user.user_login) + '</code>' +
				'</div>' +
				'<button type="button" class="dwm-button dwm-button-small dwm-button-danger dwm-restricted-user-remove">Remove</button>' +
			'</div>';

		$('#dwm-restricted-users-list').append(html);
		syncRestrictedUserIdsValue();
		updateRestrictedUsersEmptyState();
		return true;
	}

	$(document).on('input', '#dwm-restricted-users-search', function() {
		const needle = ($(this).val() || '').toLowerCase().trim();
		$('#dwm-restricted-users-list .dwm-restricted-user-item').each(function() {
			const haystack = ($(this).attr('data-search') || '').toLowerCase();
			$(this).toggle(!needle || haystack.indexOf(needle) !== -1);
		});
		updateRestrictedUsersEmptyState();
	});

	$(document).on('click', '.dwm-restricted-user-remove', function() {
		$(this).closest('.dwm-restricted-user-item').remove();
		syncRestrictedUserIdsValue();
		updateRestrictedUsersEmptyState();
	});

	let addUserSearchTimer = null;
	let addUserSearchRequestId = 0;

	function renderAddUserResults(users) {
		const $results = $('#dwm-restricted-users-add-results');
		if (!$results.length) return;

		if (!Array.isArray(users) || users.length === 0) {
			$results.html('<p class="dwm-restricted-users-empty">No users found.</p>');
			return;
		}

		const rows = users.map((user) => (
			'<div class="dwm-restricted-user-add-item">' +
				'<div class="dwm-restricted-user-meta">' +
					'<strong>' + escapeHtml(user.display_name) + '</strong>' +
					'<span>' + escapeHtml(user.user_email) + '</span>' +
					'<code>' + escapeHtml(user.user_login) + '</code>' +
				'</div>' +
				'<button type="button" class="dwm-button dwm-button-small dwm-button-primary dwm-restricted-user-add-btn" ' +
					'data-user-id="' + escapeHtml(user.id) + '" ' +
					'data-display-name="' + escapeHtml(user.display_name) + '" ' +
					'data-user-email="' + escapeHtml(user.user_email) + '" ' +
					'data-user-login="' + escapeHtml(user.user_login) + '">' +
					'Add' +
				'</button>' +
			'</div>'
		)).join('');

		$results.html(rows);
	}

	function searchAddableUsers(term) {
		const requestId = ++addUserSearchRequestId;
		const excluded = getRestrictedUserIds();

		ajax(
			'dwm_search_users',
			{
				term,
				exclude_ids: excluded
			},
			function(data) {
				if (requestId !== addUserSearchRequestId) return;
				renderAddUserResults(data.users || []);
			},
			function(data) {
				if (requestId !== addUserSearchRequestId) return;
				$('#dwm-restricted-users-add-results').html(
					'<p class="dwm-restricted-users-empty">' + escapeHtml((data && data.message) || 'Failed to load users.') + '</p>'
				);
			}
		);
	}

	$(document).on('click', '#dwm-open-restricted-users-add', function() {
		const term = ($('#dwm-restricted-users-add-search').val() || '').trim();
		searchAddableUsers(term);
	});

	$(document).on('input', '#dwm-restricted-users-add-search', function() {
		const term = ($(this).val() || '').trim();
		clearTimeout(addUserSearchTimer);
		addUserSearchTimer = setTimeout(function() {
			searchAddableUsers(term);
		}, 220);
	});

	$(document).on('click', '.dwm-restricted-user-add-btn', function() {
		const user = {
			id: $(this).attr('data-user-id'),
			display_name: $(this).attr('data-display-name'),
			user_email: $(this).attr('data-user-email'),
			user_login: $(this).attr('data-user-login')
		};

		const added = appendRestrictedUser(user);
		if (added) {
			window.DWMToast.success('User added to restricted list.', { title: 'Restricted Users' });
			$(this).closest('.dwm-restricted-user-add-item').remove();
			updateRestrictedUsersEmptyState();
		} else {
			window.DWMToast.warning('User is already restricted.', { title: 'Restricted Users' });
		}
	});

	function syncToggleControlledFields($toggle) {
		const selectors = ($toggle.attr('data-toggle-controls') || '').trim();
		if (!selectors) return;

		const isEnabled = $toggle.is(':checked');
		$(selectors).toggleClass('dwm-hidden-by-toggle', !isEnabled);
	}

	$(document).on('change', 'input[type="checkbox"][data-toggle-controls]', function() {
		syncToggleControlledFields($(this));
	});

	$('input[type="checkbox"][data-toggle-controls]').each(function() {
		syncToggleControlledFields($(this));
	});

	// Media picker for dashboard logo URL input.
	function hasDashboardLogoConfigured() {
		const hasUrl = String($('#dwm-dashboard-logo-url').val() || '').trim().length > 0;
		const isEnabled = $('#dwm-dashboard-logo-enabled').is(':checked');
		return hasUrl && isEnabled;
	}

	function openModal(selector) {
		if (window.dwmModalAPI && typeof window.dwmModalAPI.open === 'function') {
			window.dwmModalAPI.open(selector);
			return;
		}

		$(selector).addClass('active');
		$('body').addClass('dwm-modal-open');
	}

	function closeModal(selector) {
		if (window.dwmModalAPI && typeof window.dwmModalAPI.close === 'function') {
			window.dwmModalAPI.close(selector);
			return;
		}

		$(selector).removeClass('active');
		$('body').removeClass('dwm-modal-open');
	}

	function syncHeroThemeOptionsByLogo() {
		const hasLogo = hasDashboardLogoConfigured();
		const $select = $('#dwm-dashboard-hero-theme');
		if (!$select.length) return;

		$select.find('option').each(function() {
			const type = ($(this).attr('data-theme-type') || 'text').toLowerCase();
			const visible = hasLogo ? true : type !== 'logo';
			$(this).prop('disabled', !visible).toggle(visible);
		});

		const $selected = $select.find('option:selected');
		if (!$selected.length || $selected.prop('disabled')) {
			const fallback = hasLogo ? 'logo-left' : 'text-left';
			const $fallback = $select.find('option[value="' + fallback + '"]');
			$select.val($fallback.length ? fallback : $select.find('option:not(:disabled)').first().val());
		}
	}

	function syncLogoLinkOptionsVisibility() {
		const hasLogo = String($('#dwm-dashboard-logo-url').val() || '').trim().length > 0;
		$('#dwm-dashboard-logo-link-options').toggleClass('dwm-hidden-by-toggle', !hasLogo);
		$('#dwm-dashboard-logo-size-controls').toggleClass('dwm-hidden-by-toggle', !hasLogo);
		$('#dwm-dashboard-logo-style-col').toggleClass('dwm-hidden-by-toggle', !hasLogo);
		$('#dwm-dashboard-logo-preview-col').toggleClass('dwm-hidden-by-toggle', !hasLogo);
		syncHeroThemeOptionsByLogo();
	}

	function syncLogoChooseButtonState() {
		const hasLogo = String($('#dwm-dashboard-logo-url').val() || '').trim().length > 0;
		$('.dwm-logo-choose-button').toggle(!hasLogo);
		$('.dwm-dashboard-logo-preview-wrap').toggleClass('has-logo', hasLogo).toggle(hasLogo);
	}

	function clearDashboardLogoConfiguration() {
		$('#dwm-dashboard-logo-url').val('');
		$('#dwm-dashboard-logo-preview').attr('src', '').addClass('is-empty');
		$('#dwm-dashboard-logo-link-url').val('');
		$('#dwm-dashboard-logo-link-new-tab').prop('checked', false);
		syncLogoLinkOptionsVisibility();
		syncLogoChooseButtonState();
		syncHeroThemeOptionsByLogo();
	}

	function syncLogoAlignmentButtons() {
		const current = $('#dwm-dashboard-logo-alignment').val() || 'left';
		$('.dwm-logo-align-btn').removeClass('is-active');
		$('.dwm-logo-align-btn[data-align="' + current + '"]').addClass('is-active');
	}

	function openDashboardLogoMediaFrame() {
		if (!window.wp || !window.wp.media) {
			window.DWMToast.error('Media library is unavailable on this screen.', { title: 'Dashboard Logo' });
			return;
		}

		const $target = $('#dwm-dashboard-logo-url');
		if (!$target.length) {
			return;
		}

		const frame = window.wp.media({
			title: 'Select Dashboard Logo',
			button: { text: 'Use this image' },
			multiple: false,
			library: { type: 'image' }
		});

		frame.on('select', function() {
			const attachment = frame.state().get('selection').first().toJSON();
			if (attachment && attachment.url) {
				$target.val(attachment.url).trigger('change');
				$('#dwm-dashboard-logo-preview').attr('src', attachment.url).removeClass('is-empty');
				syncLogoChooseButtonState();
			}
		});

		frame.open();
	}

	$(document).on('click', '.dwm-logo-align-btn', function(e) {
		e.preventDefault();
		const align = $(this).attr('data-align') || 'left';
		$('#dwm-dashboard-logo-alignment').val(align);
		syncLogoAlignmentButtons();
	});

	function getLogoHeightRange(unit) {
		if (unit === '%' || unit === 'vh') return { min: 1, max: 100 };
		if (unit === 'rem' || unit === 'em') return { min: 1, max: 30 };
		return { min: 1, max: 320 }; // px default
	}

	function syncLogoHeightSliderRange() {
		const unit = $('#dwm-dashboard-logo-height-unit').val() || 'px';
		const range = getLogoHeightRange(unit);
		const $input = $('#dwm-dashboard-logo-height');
		const $slider = $('#dwm-dashboard-logo-height-slider');
		$input.attr('min', range.min).attr('max', range.max);
		$slider.attr('min', range.min).attr('max', range.max);
	}

	function syncLogoBorderControlsVisibility() {
		const borderStyle = ($('#dwm-logo-border-style').val() || 'none').toLowerCase();
		const showBorderControls = borderStyle !== 'none';
		$('[data-group="logo-border"]').toggleClass('dwm-hidden-by-toggle', !showBorderControls);
		$('#dwm-logo-border-link-btn').toggleClass('dwm-hidden-by-toggle', !showBorderControls);
		$('#dwm-logo-border-color-wrap').toggleClass('dwm-hidden-by-toggle', !showBorderControls);
		$('#dwm-logo-radius-block').toggleClass('dwm-hidden-by-toggle', !showBorderControls);
		$('#dwm-logo-border-block').toggleClass('has-following-group-divider', showBorderControls);
	}

	$(document).on('input change', '#dwm-dashboard-logo-height', function() {
		$('#dwm-dashboard-logo-height-slider').val($(this).val());
	});

	$(document).on('input', '#dwm-dashboard-logo-height-slider', function() {
		$('#dwm-dashboard-logo-height').val($(this).val());
	});

	$(document).on('change', '#dwm-dashboard-logo-height-unit', function() {
		syncLogoHeightSliderRange();
	});

	// Linked padding/margin controls
	function initLinkedInputGroup(group) {
		const $root = $('[data-group="' + group + '"]');
		const $inputs = $root.find('.dwm-linked-input-item input[type="number"]');
		const $linkBtn = $root.find('.dwm-link-btn[data-group="' + group + '"]');
		const $linkVal = $root.find('.dwm-link-value[data-group="' + group + '"]');

		function isLinked() {
			return $linkVal.val() === '1';
		}

		function syncFromFirst() {
			if (!isLinked()) return;
			const val = $inputs.first().val();
			$inputs.not($inputs.first()).val(val);
		}

		$linkBtn.on('click', function() {
			const linked = !isLinked();
			$linkVal.val(linked ? '1' : '0');
			$linkBtn.toggleClass('is-linked', linked);
			if (linked) syncFromFirst();
		});

		$inputs.on('input change', function() {
			if (isLinked()) {
				const val = $(this).val();
				$inputs.val(val);
			}
		});

		$linkBtn.toggleClass('is-linked', isLinked());
	}

	initLinkedInputGroup('logo-padding');
	initLinkedInputGroup('logo-margin');
	initLinkedInputGroup('logo-border');
	initLinkedInputGroup('logo-radius');
	syncLogoBorderControlsVisibility();

	$(document).on('change', '#dwm-logo-border-style', function() {
		syncLogoBorderControlsVisibility();
	});

	$(document).on('click', '.dwm-dashboard-media-pick', function() {
		const targetSelector = $(this).attr('data-target-input');
		const $target = targetSelector ? $(targetSelector) : $();
		if (!$target.length) {
			return;
		}

		if (targetSelector === '#dwm-dashboard-logo-url') {
			openDashboardLogoMediaFrame();
		}
	});

	$(document).on('click', '[data-open-modal="#dwm-dashboard-logo-edit-modal"]', function(e) {
		e.preventDefault();
		if (!hasDashboardLogoConfigured()) {
			return;
		}
		openModal('#dwm-dashboard-logo-edit-modal');
	});

	$(document).on('click', '#dwm-dashboard-logo-edit-modal .dwm-modal-close, #dwm-dashboard-logo-edit-modal .dwm-modal-overlay', function(e) {
		e.preventDefault();
		closeModal('#dwm-dashboard-logo-edit-modal');
	});

	$(document).on('click', '.dwm-dashboard-logo-replace-action', function(e) {
		e.preventDefault();
		closeModal('#dwm-dashboard-logo-edit-modal');
		openDashboardLogoMediaFrame();
	});

	$(document).on('click', '.dwm-dashboard-logo-remove-action', function(e) {
		e.preventDefault();
		clearDashboardLogoConfiguration();
		closeModal('#dwm-dashboard-logo-edit-modal');
	});

	$(document).on('input change', '#dwm-dashboard-logo-url', function() {
		const url = String($(this).val() || '').trim();
		if (url) {
			$('#dwm-dashboard-logo-preview').attr('src', url).removeClass('is-empty');
		} else {
			$('#dwm-dashboard-logo-preview').attr('src', '').addClass('is-empty');
		}
		syncLogoLinkOptionsVisibility();
		syncLogoChooseButtonState();
	});
	$(document).on('change', '#dwm-dashboard-logo-enabled', function() {
		syncHeroThemeOptionsByLogo();
	});

	function updateGradientControlVisibility(prefix) {
		const type = $('#' + prefix + '-background-type').val() || 'default';
		const gradientType = $('#' + prefix + '-bg-gradient-type').val() || 'linear';
		$('#' + prefix + '-bg-solid-controls').css('display', type === 'solid' ? '' : 'none');
		$('#' + prefix + '-bg-gradient-type-controls').css('display', type === 'gradient' ? '' : 'none');
		$('#' + prefix + '-bg-gradient-details').css('display', type === 'gradient' ? '' : 'none');
		$('#' + prefix + '-bg-gradient-angle-wrap').css('display', gradientType === 'linear' ? '' : 'none');
	}

	function buildGradientCss(prefix) {
		const type = $('#' + prefix + '-bg-gradient-type').val() || 'linear';
		const angle = parseInt($('#' + prefix + '-bg-gradient-angle').val(), 10) || 90;
		const startColor = $('#' + prefix + '-bg-gradient-start').val() || '#667eea';
		const startPos = parseInt($('#' + prefix + '-bg-gradient-start-position').val(), 10) || 0;
		const endColor = $('#' + prefix + '-bg-gradient-end').val() || '#764ba2';
		const endPos = parseInt($('#' + prefix + '-bg-gradient-end-position').val(), 10) || 100;

		if (type === 'radial') {
			return 'radial-gradient(' + startColor + ' ' + startPos + '%, ' + endColor + ' ' + endPos + '%)';
		}
		return 'linear-gradient(' + angle + 'deg, ' + startColor + ' ' + startPos + '%, ' + endColor + ' ' + endPos + '%)';
	}

	function updateGradientPreview(prefix) {
		$('#' + prefix + '-bg-gradient-preview').css('background', buildGradientCss(prefix));
		$('#' + prefix + '-bg-gradient-angle-value').text(($('#' + prefix + '-bg-gradient-angle').val() || '90') + '°');
		$('#' + prefix + '-bg-gradient-start-position-label').text(($('#' + prefix + '-bg-gradient-start-position').val() || '0') + '%');
		$('#' + prefix + '-bg-gradient-end-position-label').text(($('#' + prefix + '-bg-gradient-end-position').val() || '100') + '%');
	}

	$(document).on('change', '#dwm-background-type, #dwm-bg-gradient-type', function() {
		updateGradientControlVisibility('dwm');
		updateGradientPreview('dwm');
	});
	$(document).on('input change', '#dwm-bg-gradient-angle, #dwm-bg-gradient-start, #dwm-bg-gradient-start-position, #dwm-bg-gradient-end, #dwm-bg-gradient-end-position', function() {
		updateGradientPreview('dwm');
	});
	$(document).on('change', '#dwm-hero-background-type, #dwm-hero-bg-gradient-type', function() {
		updateGradientControlVisibility('dwm-hero');
		updateGradientPreview('dwm-hero');
	});
	$(document).on('input change', '#dwm-hero-bg-gradient-angle, #dwm-hero-bg-gradient-start, #dwm-hero-bg-gradient-start-position, #dwm-hero-bg-gradient-end, #dwm-hero-bg-gradient-end-position', function() {
		updateGradientPreview('dwm-hero');
	});
	$(document).on('change', '#dwm-logo-background-type, #dwm-logo-bg-gradient-type', function() {
		updateGradientControlVisibility('dwm-logo');
		updateGradientPreview('dwm-logo');
	});
	$(document).on('input change', '#dwm-logo-bg-gradient-angle, #dwm-logo-bg-gradient-start, #dwm-logo-bg-gradient-start-position, #dwm-logo-bg-gradient-end, #dwm-logo-bg-gradient-end-position', function() {
		updateGradientPreview('dwm-logo');
	});

	function isPaddingLinked() {
		return $('#dwm-padding-linked').val() === '1';
	}

	function setPaddingLinked(linked) {
		$('#dwm-padding-linked').val(linked ? '1' : '0');
		$('#dwm-padding-link').toggleClass('is-linked', linked);
	}

	function syncPaddingSide(side, value) {
		$('#dwm-padding-' + side + '-value').val(value);
		$('#dwm-padding-' + side + '-slider').val(value);
	}

	$(document).on('click', '#dwm-padding-link', function(e) {
		e.preventDefault();
		setPaddingLinked(!isPaddingLinked());
	});

	$(document).on('input change', '.dwm-padding-value, .dwm-padding-slider', function() {
		const side = $(this).attr('data-side');
		const value = $(this).val();
		syncPaddingSide(side, value);

		if (isPaddingLinked()) {
			['top', 'right', 'bottom', 'left'].forEach(function(otherSide) {
				syncPaddingSide(otherSide, value);
			});
		}
	});

	function syncTitleModeVisibility() {
		const mode = $('#dwm-dashboard-title-mode').val() || 'default';
		$('#dwm-dashboard-title-custom-controls').toggleClass('dwm-hidden-by-toggle', mode !== 'custom');
	}
	$(document).on('change', '#dwm-dashboard-title-mode', syncTitleModeVisibility);

	let activeTitleFormatField = 'dashboard_title';

	function getFormatFieldDefaults(fieldKey) {
		if (fieldKey === 'dashboard_hero_title') {
			return {
				fontSize: '28px',
				fontFamily: 'inherit',
				fontWeight: '700',
				alignment: 'left',
				color: '#ffffff'
			};
		}
		return {
			fontSize: '32px',
			fontFamily: 'inherit',
			fontWeight: '700',
			alignment: 'left',
			color: '#1d2327'
		};
	}

	function openTitleFormatModal(fieldKey) {
		const defaults = getFormatFieldDefaults(fieldKey);
		const fontSize = $('#' + fieldKey + '_font_size').val() || defaults.fontSize;
		const match = String(fontSize).match(/^(\d+(?:\.\d+)?)(px|rem|em)$/);
		$('#dwm-title-format-font-size-value').val(match ? match[1] : 32);
		$('#dwm-title-format-font-size-unit').val(match ? match[2] : 'px');
		$('#dwm-title-format-font-size-slider').val(match ? match[1] : 32);
		$('#dwm-title-format-font-family').val($('#' + fieldKey + '_font_family').val() || defaults.fontFamily);
		$('#dwm-title-format-font-weight').val($('#' + fieldKey + '_font_weight').val() || defaults.fontWeight);
		$('.dwm-alignment-btn').removeClass('active');
		$('.dwm-alignment-btn[data-align="' + ($('#' + fieldKey + '_alignment').val() || defaults.alignment) + '"]').addClass('active');

		const color = $('#' + fieldKey + '_color').val() || defaults.color;
		if (String(color).indexOf('gradient') !== -1) {
			$('.dwm-color-tab-btn').removeClass('active');
			$('.dwm-color-tab-btn[data-tab="gradient"]').addClass('active');
			$('.dwm-color-tab-content').removeClass('active');
			$('.dwm-color-tab-content[data-tab="gradient"]').addClass('active');
		} else if (String(color).indexOf('rgba') !== -1) {
			$('.dwm-color-tab-btn').removeClass('active');
			$('.dwm-color-tab-btn[data-tab="rgba"]').addClass('active');
			$('.dwm-color-tab-content').removeClass('active');
			$('.dwm-color-tab-content[data-tab="rgba"]').addClass('active');
		} else {
			$('.dwm-color-tab-btn').removeClass('active');
			$('.dwm-color-tab-btn[data-tab="hex"]').addClass('active');
			$('.dwm-color-tab-content').removeClass('active');
			$('.dwm-color-tab-content[data-tab="hex"]').addClass('active');
			$('#dwm-title-format-color').val(color);
			$('#dwm-title-color-wheel').val(color);
		}

		updateTitleGradientPreview();
	}

	function updateTitleGradientPreview() {
		const type = $('#dwm-title-gradient-type').val() || 'linear';
		const angle = parseInt($('#dwm-title-gradient-angle').val(), 10) || 90;
		const c1 = $('#dwm-title-gradient-start').val() || '#667eea';
		const p1 = parseInt($('#dwm-title-gradient-start-pos').val(), 10) || 0;
		const c2 = $('#dwm-title-gradient-end').val() || '#764ba2';
		const p2 = parseInt($('#dwm-title-gradient-end-pos').val(), 10) || 100;
		const css = type === 'radial'
			? 'radial-gradient(' + c1 + ' ' + p1 + '%, ' + c2 + ' ' + p2 + '%)'
			: 'linear-gradient(' + angle + 'deg, ' + c1 + ' ' + p1 + '%, ' + c2 + ' ' + p2 + '%)';
		$('#dwm-title-gradient-preview').css('background', css);
		$('#dwm-title-gradient-angle-value').text(angle + '°');
		$('#dwm-title-gradient-start-label').text(p1 + '%');
		$('#dwm-title-gradient-end-label').text(p2 + '%');
		return css;
	}

	$(document).on('click', '.dwm-title-format-icon-btn', function() {
		activeTitleFormatField = $(this).data('field') || 'dashboard_title';
		openTitleFormatModal(activeTitleFormatField);
	});
	$(document).on('click', '.dwm-color-tab-btn', function(e) {
		e.preventDefault();
		const tab = $(this).data('tab');
		$('.dwm-color-tab-btn').removeClass('active');
		$(this).addClass('active');
		$('.dwm-color-tab-content').removeClass('active');
		$('.dwm-color-tab-content[data-tab="' + tab + '"]').addClass('active');
	});
	$(document).on('input change', '#dwm-title-format-font-size-value', function() {
		$('#dwm-title-format-font-size-slider').val($(this).val());
	});
	$(document).on('input', '#dwm-title-format-font-size-slider', function() {
		$('#dwm-title-format-font-size-value').val($(this).val());
	});
	$(document).on('input', '#dwm-title-color-wheel', function() {
		$('#dwm-title-format-color').val($(this).val());
	});
	$(document).on('input', '#dwm-title-format-color', function() {
		$('#dwm-title-color-wheel').val($(this).val());
	});
	$(document).on('input change', '#dwm-title-gradient-type, #dwm-title-gradient-angle, #dwm-title-gradient-start, #dwm-title-gradient-start-pos, #dwm-title-gradient-end, #dwm-title-gradient-end-pos', function() {
		updateTitleGradientPreview();
	});
	$(document).on('input', '.dwm-rgba-slider', function() {
		$(this).closest('.dwm-rgba-input-group').find('.dwm-rgba-value').val($(this).val());
	});
	$(document).on('input', '.dwm-rgba-value', function() {
		const v = $(this).val();
		$(this).closest('.dwm-rgba-input-group').find('.dwm-rgba-slider').val(v);
	});
	$(document).on('click', '.dwm-alignment-btn', function(e) {
		e.preventDefault();
		$('.dwm-alignment-btn').removeClass('active');
		$(this).addClass('active');
	});

	$(document).on('click', '#dwm-title-format-apply', function(e) {
		e.preventDefault();
		const size = ($('#dwm-title-format-font-size-value').val() || '32') + ($('#dwm-title-format-font-size-unit').val() || 'px');
		const family = $('#dwm-title-format-font-family').val() || 'inherit';
		const weight = $('#dwm-title-format-font-weight').val() || '700';
		const align = $('.dwm-alignment-btn.active').data('align') || 'left';

		let color;
		const tab = $('.dwm-color-tab-btn.active').data('tab');
		if (tab === 'gradient') {
			color = updateTitleGradientPreview();
		} else if (tab === 'rgba') {
			const r = $('#dwm-title-rgba-r').val() || 29;
			const g = $('#dwm-title-rgba-g').val() || 35;
			const b = $('#dwm-title-rgba-b').val() || 39;
			const a = ((parseInt($('#dwm-title-rgba-a').val(), 10) || 100) / 100).toFixed(2);
			color = 'rgba(' + r + ', ' + g + ', ' + b + ', ' + a + ')';
		} else {
			color = $('#dwm-title-format-color').val() || '#1d2327';
		}

		$('#' + activeTitleFormatField + '_font_size').val(size);
		$('#' + activeTitleFormatField + '_font_family').val(family);
		$('#' + activeTitleFormatField + '_font_weight').val(weight);
		$('#' + activeTitleFormatField + '_alignment').val(align);
		$('#' + activeTitleFormatField + '_color').val(color);

		if (window.dwmModalAPI && typeof window.dwmModalAPI.close === 'function') {
			window.dwmModalAPI.close('#dwm-title-format-modal');
		} else {
			$('#dwm-title-format-modal').hide();
		}
	});

	updateGradientControlVisibility('dwm');
	updateGradientPreview('dwm');
	updateGradientControlVisibility('dwm-hero');
	updateGradientPreview('dwm-hero');
	updateGradientControlVisibility('dwm-logo');
	updateGradientPreview('dwm-logo');
	syncTitleModeVisibility();
	syncLogoLinkOptionsVisibility();
	syncLogoAlignmentButtons();
	syncLogoChooseButtonState();
	syncLogoHeightSliderRange();
	syncHeroThemeOptionsByLogo();
	setPaddingLinked(isPaddingLinked());

	updateAllowedRolesValue();
	syncRestrictedUserIdsValue();
	updateRestrictedUsersEmptyState();
}
