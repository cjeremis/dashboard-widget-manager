/**
 * Dashboard Widget Manager - Status Change Modal Module
 *
 * Handles badge overlay hover/click behavior and the status change modal
 * for widget cards on the Widget Manager page.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

(function($) {
	'use strict';

	var STATUS_OPTIONS = [
		{ db: 'publish',  label: 'Active'   },
		{ db: 'draft',    label: 'Draft'    },
		{ db: 'archived', label: 'Archived' },
		{ db: 'trash',    label: 'Trashed'  }
	];

	var DESCRIPTIONS = {
		trash:    'Trashed widgets are automatically permanently deleted after 30 days. You can restore them from the Widget Manager at any time before deletion.',
		archived: 'Archived widgets are never automatically deleted and can be restored at any time from the Widget Manager.'
	};

	var currentWidgetId     = null;
	var currentWidgetStatus = null;

	function buildModalHtml() {
		return (
			'<div id="dwm-status-change-modal" class="dwm-modal">' +
				'<div class="dwm-modal-overlay"></div>' +
				'<div class="dwm-modal-content dwm-modal-sm" role="dialog" aria-modal="true" aria-labelledby="dwm-status-change-modal-title">' +
					'<div class="dwm-modal-header">' +
						'<h2 id="dwm-status-change-modal-title"><span class="dashicons dashicons-flag"></span> Change Status</h2>' +
						'<button type="button" class="dwm-modal-close" aria-label="Close modal"><span class="dashicons dashicons-no-alt"></span></button>' +
					'</div>' +
					'<div class="dwm-modal-body">' +
						'<p id="dwm-status-change-tagline" class="dwm-status-change-tagline"></p>' +
						'<select id="dwm-status-change-select" class="dwm-status-change-select">' +
							'<option value="" disabled>Select a status...</option>' +
						'</select>' +
						'<p id="dwm-status-change-description" class="dwm-status-change-description"></p>' +
					'</div>' +
					'<div class="dwm-modal-footer">' +
						'<button type="button" id="dwm-status-change-apply" class="dwm-button dwm-button-primary" disabled>' +
							'<span class="dashicons dashicons-yes"></span> Apply' +
						'</button>' +
					'</div>' +
				'</div>' +
			'</div>'
		);
	}

	function isDirty() {
		var selected = $('#dwm-status-change-select').val();
		return !!selected && selected !== currentWidgetStatus;
	}

	function updateApplyState() {
		$('#dwm-status-change-apply').prop('disabled', !isDirty());
	}

	function populateSelect(widgetStatus) {
		var $select = $('#dwm-status-change-select');
		$select.empty().append('<option value="" disabled>Select a status...</option>');

		var currentLabel = widgetStatus.charAt(0).toUpperCase() + widgetStatus.slice(1);
		STATUS_OPTIONS.forEach(function(opt) {
			if (opt.db === widgetStatus) {
				currentLabel = opt.label;
			}
		});

		$('#dwm-status-change-tagline').html('Currently <strong>' + currentLabel + '</strong>');

		STATUS_OPTIONS.forEach(function(opt) {
			$select.append('<option value="' + opt.db + '">' + opt.label + '</option>');
		});

		$select.val(widgetStatus);

		var initialDescription = DESCRIPTIONS[widgetStatus] || '';
		if (initialDescription) {
			$('#dwm-status-change-description').text(initialDescription).show();
		} else {
			$('#dwm-status-change-description').hide().empty();
		}
		updateApplyState();
	}

	function executeStatusChange(dbStatus) {
		var $btn         = $('#dwm-status-change-apply');
		var originalHtml = $btn.html();

		$btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Updating...');

		$.ajax({
			url:      dwmAdminVars.ajaxUrl,
			type:     'POST',
			dataType: 'json',
			data: {
				action:     'dwm_change_status',
				nonce:      dwmAdminVars.nonce,
				widget_id:  currentWidgetId,
				new_status: dbStatus
			},
			success: function(response) {
				if (response.success) {
					if (window.closeModal) {
						closeModal('dwm-status-change-modal');
					}
					if (window.DWMToast) {
						DWMToast.success(response.data.message || 'Status updated');
					}
					setTimeout(function() {
						location.reload();
					}, 800);
				} else {
					var msg = (response.data && response.data.message) || 'Failed to update status';
					if (window.DWMToast) {
						DWMToast.error(msg);
					}
					$btn.prop('disabled', false).html(originalHtml);
				}
			},
			error: function() {
				if (window.DWMToast) {
					DWMToast.error('Failed to update status. Please try again.');
				}
				$btn.prop('disabled', false).html(originalHtml);
			}
		});
	}

	function bindEvents() {
		// Badge overlay show/hide on hover
		$(document).on('mouseenter', '.dwm-widget-status-badge', function() {
			$(this).find('.dwm-badge-overlay').stop(true).fadeIn(150);
		});

		$(document).on('mouseleave', '.dwm-widget-status-badge', function() {
			$(this).find('.dwm-badge-overlay').stop(true).fadeOut(150);
		});

		// Badge overlay click -> open status change modal
		$(document).on('click', '.dwm-badge-overlay', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var $card        = $(this).closest('.dwm-widget-card');
			var widgetId     = $card.attr('data-widget-id');
			var widgetStatus = $card.attr('data-widget-status');

			if (!widgetId) return;

			currentWidgetId     = widgetId;
			currentWidgetStatus = widgetStatus;

			populateSelect(widgetStatus);

			if (window.openModal) {
				openModal('dwm-status-change-modal');
			}
		});

		// Dropdown change - show/hide description, check dirty state
		$(document).on('change', '#dwm-status-change-select', function() {
			var val   = $(this).val();
			var $desc = $('#dwm-status-change-description');

			if (val && DESCRIPTIONS[val]) {
				$desc.text(DESCRIPTIONS[val]).slideDown(150);
			} else {
				$desc.slideUp(150);
			}

			updateApplyState();
		});

		// Apply button
		$(document).on('click', '#dwm-status-change-apply', function() {
			var dbStatus = $('#dwm-status-change-select').val();
			if (!dbStatus) return;
			executeStatusChange(dbStatus);
		});
	}

	$(function() {
		$('body').append(buildModalHtml());
		bindEvents();
	});

})(jQuery);

export default {};
