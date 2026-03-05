/**
 * Dashboard Widget Manager - Modal Utilities
 *
 * Provides modal management functions (open, close, etc.)
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

/**
 * Open modal
 *
 * @param {string} modalId Modal element ID
 */
export function openModal(modalId) {
	const $modal = $(`#${modalId}`);
	$modal.addClass('active');
	$('body').addClass('dwm-modal-open');
	// Reset scroll after display:none is removed
	$modal.find( '.dwm-modal-body, [data-docs-content]' ).scrollTop( 0 );
}

/**
 * Close modal
 *
 * @param {string} [modalId] If provided, closes only that modal.
 *                           If omitted, closes all modals (e.g. after save/delete).
 */
export function closeModal( modalId ) {
	if ( modalId ) {
		$( '#' + modalId ).removeClass( 'active' );
	} else {
		$( '.dwm-modal' ).removeClass( 'active' );
	}
	// Only remove the body scroll-lock when no modals remain open
	if ( $( '.dwm-modal.active' ).length === 0 ) {
		$( 'body' ).removeClass( 'dwm-modal-open' );
	}
}

// Expose to global scope for cross-bundle and non-import access
window.openModal  = openModal;
window.closeModal = closeModal;

/**
 * Initialize modal event handlers
 */
export function initModals() {
	// Modal close button - prevent closing widget editor
	$(document).on('click', '.dwm-modal-close', function(e) {
		const $modal = $(this).closest('.dwm-modal');
		const modalId = $modal.attr('id');

		if (modalId === 'dwm-widget-editor-modal') {
			e.preventDefault();
			e.stopPropagation();
			window.attemptCloseWidgetEditor?.();
		} else if (modalId === 'dwm-join-config-modal') {
			// Join config modal close button is handled by wizard.js
			return;
		} else {
			closeModal( modalId );
		}
	});

	// Modal overlay - prevent closing widget editor
	$(document).on('click', '.dwm-modal-overlay', function(e) {
		const $modal = $(this).closest('.dwm-modal');
		const modalId = $modal.attr('id');

		if (modalId === 'dwm-widget-editor-modal') {
			e.preventDefault();
			e.stopPropagation();
			window.attemptCloseWidgetEditor?.();
		} else if (modalId === 'dwm-join-config-modal') {
			// Join config modal can be closed normally
			$modal.removeClass('active');
		} else {
			closeModal( modalId );
		}
	});

	// data-open-modal attribute handler
	$(document).on('click', '[data-open-modal]', function() {
		const modalId = $(this).data('open-modal');
		if (modalId) {
			openModal(modalId);
		}
	});

	// data-close-modal attribute handler
	$(document).on('click', '[data-close-modal]', function() {
		const $modal = $(this).closest('.dwm-modal');
		const modalId = $modal.attr('id');
		if (modalId) {
			closeModal(modalId);
		}
	});

	// ESC key — same behaviour as clicking the X button on the topmost active modal
	$(document).on('keydown', function(e) {
		if (e.key !== 'Escape') return;

		const $activeModals = $('.dwm-modal.active');
		if ($activeModals.length === 0) return;

		const $topModal = $activeModals.last();
		const modalId   = $topModal.attr('id');

		if (modalId === 'dwm-widget-editor-modal') {
			window.attemptCloseWidgetEditor?.();
		} else if (modalId === 'dwm-join-config-modal') {
			// Handled by wizard.js
		} else {
			closeModal(modalId);
		}
	});

}
