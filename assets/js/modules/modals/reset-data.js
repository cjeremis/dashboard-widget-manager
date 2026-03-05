/**
 * Dashboard Widget Manager - Reset Data Modal
 *
 * Handles the reset data modal, including export-before-reset and confirmed data deletion.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { dwmConfirm } from '../partials/dialog.js';

const $ = jQuery;

export function initResetModal() {
	$(document).on('click', '#dwm-open-reset-modal', function() {
		openModal('dwm-reset-data-modal');
	});

	// Export first button in reset modal — open the export options modal on top
	$(document).on('click', '#dwm-reset-export-first', function() {
		openModal('dwm-export-options-modal');
	});

	// Reset confirm button
	$(document).on('click', '#dwm-reset-confirm-button', function() {
		const resetWidgets        = $('#dwm-reset-widgets').is(':checked');
		const resetSettings       = $('#dwm-reset-settings-check').is(':checked');
		const resetNotifications  = $('#dwm-reset-notifications').is(':checked');

		if (!resetWidgets && !resetSettings && !resetNotifications) {
			window.DWMToast.warning('Please select at least one data type to reset.', { title: 'Reset Data' });
			return;
		}

		const $btn = $(this);

		dwmConfirm({
			title:       'Reset Data',
			message:     'Are you sure you want to permanently delete the selected data? This cannot be undone.',
			icon:        'trash',
			confirmText: 'Reset Data',
			onConfirm() {
				showLoading($btn);

				ajax(
					'dwm_reset_data',
					{
						reset: {
							widgets:       resetWidgets       ? 1 : 0,
							settings:      resetSettings      ? 1 : 0,
							notifications: resetNotifications ? 1 : 0,
						},
					},
					function(data) {
						hideLoading($btn);
						closeModal();
						window.DWMToast.success(data.message || 'Data reset successfully.', { title: 'Reset Data' });
						setTimeout(function() { location.reload(); }, 1500);
					},
					function(data) {
						hideLoading($btn);
						window.DWMToast.error(data.message || 'Failed to reset data.', { title: 'Reset Data' });
					}
				);
			},
		});
	});
}
