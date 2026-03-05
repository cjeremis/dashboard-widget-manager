/**
 * Dashboard Widget Manager - AJAX Utilities
 *
 * Provides AJAX request wrapper with nonce handling
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

/**
 * Make AJAX request with nonce
 *
 * @param {string} action AJAX action name
 * @param {object} data Request data
 * @param {function} successCallback Success callback
 * @param {function} errorCallback Error callback
 */
export function ajax(action, data, successCallback, errorCallback) {
	if (typeof dwmAdminVars === 'undefined') {
		console.error('[DWM Ajax] dwmAdminVars not defined');
		return;
	}

	const requestData = $.extend({
		action: action,
		nonce: dwmAdminVars.nonce
	}, data);

	$.ajax({
		url: dwmAdminVars.ajaxUrl,
		type: 'POST',
		data: requestData,
		success: function(response) {
			if (response.success) {
				if (successCallback) successCallback(response.data);
			} else {
				console.warn('[DWM Ajax] Server returned success=false for action:', action);
				console.warn('[DWM Ajax] Error data:', response.data);
				if (errorCallback) errorCallback(response.data);
			}
		},
		error: function(xhr, status, error) {
			console.group('[DWM Ajax] ERROR: ' + action);
			console.error('Status:', status);
			console.error('Error:', error);
			console.error('XHR Status:', xhr.status);
			console.error('XHR Status Text:', xhr.statusText);
			console.error('XHR Response Text:', xhr.responseText);

			try {
				const responseJson = JSON.parse(xhr.responseText);
				console.error('Parsed Response:', responseJson);
			} catch (e) {
				console.error('Could not parse response as JSON');
			}

			console.groupEnd();

			if (errorCallback) {
				errorCallback({
					message: dwmAdminVars.i18n.saveError,
					xhr: xhr,
					status: status,
					error: error,
					statusCode: xhr.status,
					responseText: xhr.responseText
				});
			}
		}
	});
}

// Expose to global scope for cross-bundle and non-import access
window.ajax = ajax;
