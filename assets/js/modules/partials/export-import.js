/**
 * Dashboard Widget Manager - Export / Import
 *
 * Handles widget and settings data export (download, view, copy) and JSON file import.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { dwmConfirm } from './dialog.js';

const $ = jQuery;

let exportedData = null;
let selectedFile  = null;

export function initExportImport() {
	// Open export options modal
	$(document).on('click', '#dwm-export-button, #dwm-demo-export-button', function() {
		openModal('dwm-export-options-modal');
	});

	// Download from export options modal
	$(document).on('click', '#dwm-export-download-btn', function() {
		const options = getExportOptions();
		exportWithOptions(options, function(data) {
			const didDownload = download(
				JSON.stringify(filterData(data, options), null, 2),
				'Export Data'
			);
			if (didDownload) {
				closeModal('dwm-export-options-modal');
			}
		});
	});

	// Open view data modal
	$(document).on('click', '#dwm-view-data, #dwm-demo-view-data', function() {
		viewData();
	});

	// View data toggles
	$(document).on('change', '.dwm-view-toggle', function() {
		if (exportedData) {
			updateViewDataDisplay(exportedData);
		}
	});

	// Copy to clipboard
	$(document).on('click', '#dwm-modal-copy-json', function() {
		const text = $('#dwm-export-json-preview').val();
		copyToClipboard(text);
	});

	// Download from view modal
	$(document).on('click', '#dwm-modal-download-json', function() {
		const text = $('#dwm-export-json-preview').val();
		download(text, 'View Data');
	});

	// File selection
	$(document).on('change', '#dwm-import-file', function(e) {
		handleFileSelect(e);
	});

	// Drag and drop
	$(document).on('dragover', '#dwm-file-input-wrapper', function(e) {
		e.preventDefault();
		$(this).addClass('dwm-drag-over');
	});

	$(document).on('dragleave drop', '#dwm-file-input-wrapper', function(e) {
		$(this).removeClass('dwm-drag-over');
		if (e.type === 'drop') {
			e.preventDefault();
			const file = e.originalEvent.dataTransfer.files[0];
			if (file) {
				handleFileSelect({ target: { files: [file] } });
			}
		}
	});

	// Remove file
	$(document).on('click', '#dwm-file-remove', function() {
		removeFile();
	});

	// Import button
	$(document).on('click', '#dwm-import-button', function() {
		importData();
	});
}

function getExportOptions() {
	return {
		widgets:       $('#dwm-export-widgets').is(':checked'),
		settings:      $('#dwm-export-settings').is(':checked'),
		notifications: $('#dwm-export-notifications').is(':checked'),
	};
}

function exportWithOptions(options, callback) {
	ajax(
		'dwm_export',
		{},
		function(data) {
			callback(data);
		},
		function(data) {
			window.DWMToast.error(data.message || 'Failed to export data.', { title: 'Export Data' });
		}
	);
}

function filterData(data, options) {
	const filtered = {
		plugin:      data.plugin,
		version:     data.version,
		exported_at: data.exported_at,
		site_url:    data.site_url,
	};

	if (options.widgets && data.widgets) {
		filtered.widgets = data.widgets;
	}
	if (options.settings && data.settings) {
		filtered.settings = data.settings;
	}
	if (options.notifications && data.notifications) {
		filtered.notifications = data.notifications;
	}

	return filtered;
}

function download(jsonString, title = 'Export Data') {
	const normalized = (jsonString || '').trim();

	if (!normalized) {
		window.DWMToast.warning('No data available to download.', { title });
		return false;
	}

	try {
		JSON.parse(normalized);
	} catch (err) {
		window.DWMToast.warning('Export data is still loading or invalid.', { title });
		return false;
	}

	try {
		const blob = new Blob([normalized], { type: 'application/json' });
		const url  = URL.createObjectURL(blob);
		const a    = document.createElement('a');
		const now  = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
		a.href     = url;
		a.download = 'dwm-export-' + now + '.json';
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		URL.revokeObjectURL(url);
		window.DWMToast.success('JSON download started.', { title });
		return true;
	} catch (err) {
		window.DWMToast.error('Failed to process JSON download.', { title });
		return false;
	}
}

function setViewDataReady(ready) {
	$('#dwm-view-toggle-widgets, #dwm-view-toggle-settings, #dwm-view-toggle-notifications').prop('disabled', !ready);
	$('#dwm-modal-copy-json, #dwm-modal-download-json').prop('disabled', !ready);
}

function viewData() {
	openModal('dwm-view-data-modal');
	$('#dwm-export-json-preview').val('Loading export data...');
	setViewDataReady(false);

	ajax(
		'dwm_export',
		{},
		function(data) {
			exportedData = data;
			updateViewDataDisplay(data);
			setViewDataReady(true);
		},
		function(data) {
			const message = (data && data.message) ? data.message : 'Failed to load data.';
			$('#dwm-export-json-preview').val(message);
			window.DWMToast.error(message, { title: 'View Data' });
		}
	);
}

function updateViewDataDisplay(data) {
	const options  = getViewToggles();
	const filtered = filterData(data, options);
	$('#dwm-export-json-preview').val(JSON.stringify(filtered, null, 2));
}

function getViewToggles() {
	return {
		widgets:       $('#dwm-view-toggle-widgets').is(':checked'),
		settings:      $('#dwm-view-toggle-settings').is(':checked'),
		notifications: $('#dwm-view-toggle-notifications').is(':checked'),
	};
}

function copyToClipboard(text) {
	const $btn = $('#dwm-modal-copy-json');
	$btn.prop('disabled', true);

	const onClose = () => $btn.prop('disabled', false);

	if (navigator.clipboard && window.isSecureContext) {
		navigator.clipboard.writeText(text).then(function() {
			window.DWMToast.success('Copied to clipboard.', { onClose });
		});
	} else {
		const $temp = $('<textarea>').val(text).appendTo('body').select();
		document.execCommand('copy');
		$temp.remove();
		window.DWMToast.success('Copied to clipboard.', { onClose });
	}
}

function handleFileSelect(e) {
	const file = e.target.files && e.target.files[0];
	if (!file) return;
	selectedFile = file;

	const maxSize = 5 * 1024 * 1024;
	if (file.size > maxSize) {
		window.DWMToast.error('File size exceeds 5MB limit.', { title: 'Import Data' });
		removeFile();
		return;
	}

	if (!file.name.endsWith('.json') && file.type !== 'application/json') {
		window.DWMToast.error('Only JSON files are allowed.', { title: 'Import Data' });
		removeFile();
		return;
	}

	$('#dwm-file-input-wrapper').hide();
	$('#dwm-file-selected').show();
	$('#dwm-file-name').text(file.name);
	$('#dwm-file-size').text('(' + formatFileSize(file.size) + ')');

	$('#dwm-import-actions').show();
	$('#dwm-import-button').prop('disabled', false);
}

function removeFile() {
	selectedFile = null;
	$('#dwm-import-file').val('');
	$('#dwm-file-input-wrapper').show();
	$('#dwm-file-selected').hide();
	$('#dwm-file-name').text('');
	$('#dwm-file-size').text('');
	$('#dwm-import-actions').hide();
	$('#dwm-import-button').prop('disabled', true);
}

function importData() {
	const $btn = $('#dwm-import-button');
	const file = selectedFile;

	if (!file) {
		window.DWMToast.error('Please select a file to import.', { title: 'Import Data' });
		return;
	}

	dwmConfirm({
		title:       'Import Data',
		message:     'Are you sure you want to import this data? This will replace all existing data.',
		icon:        'upload',
		confirmText: 'Import',
		onConfirm() {
			showLoading($btn);

			const formData = new FormData();
			formData.append('action', 'dwm_import');
			formData.append('nonce', dwmAdminVars.nonce);
			formData.append('file', file);

			$.ajax({
				url:         dwmAdminVars.ajaxUrl,
				type:        'POST',
				data:        formData,
				processData: false,
				contentType: false,
				success: function(response) {
					hideLoading($btn);
					if (response.success) {
						window.DWMToast.success(response.data.message || 'Data imported successfully.', { title: 'Import Data' });
						removeFile();
					} else {
						window.DWMToast.error((response.data && response.data.message) || 'Import failed.', { title: 'Import Data' });
					}
				},
				error: function() {
					hideLoading($btn);
					window.DWMToast.error('Import request failed. Please try again.', { title: 'Import Data' });
				},
			});
		},
	});
}

function formatFileSize(bytes) {
	if (bytes < 1024) return bytes + ' B';
	if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
	return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}
