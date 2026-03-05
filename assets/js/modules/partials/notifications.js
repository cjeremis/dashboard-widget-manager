/**
 * Dashboard Widget Manager - Notifications
 *
 * Provides notification and loading state utilities
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

/**
 * Show notification message
 *
 * @param {string} message Message to display
 * @param {string} type 'success' or 'error'
 */
export function showNotice(message, type = 'success') {
	const noticeClass = type === 'success' ? 'dwm-notice-success' : 'dwm-notice-error';
	const $notice = $(`<div class="dwm-notice ${noticeClass}">${message}</div>`);

	$('.wrap').first().prepend($notice);

	setTimeout(function() {
		$notice.fadeOut(300, function() {
			$(this).remove();
		});
	}, 3000);
}

/**
 * Show loading state on element
 *
 * @param {jQuery} $element Element to show loading on
 */
export function showLoading($element) {
	$element.prop('disabled', true);
	$element.append(' <span class="dwm-loading"></span>');
}

/**
 * Hide loading state on element
 *
 * @param {jQuery} $element Element to hide loading on
 */
export function hideLoading($element) {
	$element.prop('disabled', false);
	$element.find('.dwm-loading').remove();
}

// Expose to global scope for cross-bundle and non-import access
window.showNotice  = showNotice;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
