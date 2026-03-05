/**
 * Dashboard Widget Manager - Demo Data
 *
 * Handles demo data import and deletion.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

export function initDemoData() {
	// Open import demo modal
	$(document).on('click', '#dwm-open-import-demo-modal', function() {
		openModal('dwm-import-demo-modal');
	});

	// Confirm import demo
	$(document).on('click', '#dwm-import-demo-confirm', function() {
		const $btn                = $(this);
		const importWidgets       = $('#dwm-import-demo-widgets').is(':checked');
		const importNotifications = $('#dwm-import-demo-notifications').is(':checked');

		if ( !importWidgets && !importNotifications ) {
			window.DWMToast.warning('Please select at least one data type to import.', { title: 'Import Demo Data' });
			return;
		}

		showLoading($btn);

		ajax(
			'dwm_import_demo_data',
			{ import_widgets: importWidgets ? 1 : 0, import_notifications: importNotifications ? 1 : 0 },
			function(data) {
				hideLoading($btn);
				closeModal();
				window.DWMToast.success(data.message || 'Demo data imported successfully.', { title: 'Import Demo Data' });
				setTimeout(function() { location.reload(); }, 1500);
			},
			function(data) {
				hideLoading($btn);
				window.DWMToast.error(data.message || 'Failed to import demo data.', { title: 'Import Demo Data' });
			}
		);
	});

	// Delete demo data
	$(document).on('click', '#dwm-delete-demo-data', function() {
		openModal('dwm-delete-demo-modal');
	});

	$(document).on('click', '#dwm-delete-demo-confirm', function() {
		const $btn = $(this);
		showLoading($btn);

		ajax(
			'dwm_delete_demo_data',
			{},
			function(data) {
				hideLoading($btn);
				closeModal('dwm-delete-demo-modal');
				window.DWMToast.success(data.message || 'Demo widgets deleted.', { title: 'Delete Demo Data' });
				setTimeout(function() { location.reload(); }, 1500);
			},
			function(data) {
				hideLoading($btn);
				window.DWMToast.error(data.message || 'Failed to delete demo widgets.', { title: 'Delete Demo Data' });
			}
		);
	});
}
