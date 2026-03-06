/**
 * Dashboard Widget Manager - Modal Utilities
 *
 * Provides modal management functions (open, close, etc.)
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;
let modalOpenOrder = 0;
const modalZBase = 999999;

function normalizeModalTarget(modalTarget) {
	if (!modalTarget) {
		return $();
	}

	if (modalTarget.jquery) {
		return modalTarget;
	}

	if (modalTarget.nodeType === 1) {
		return $(modalTarget);
	}

	if (typeof modalTarget === 'string') {
		if (modalTarget.charAt(0) === '#') {
			return $(modalTarget);
		}
		return $(`#${modalTarget}`);
	}

	return $(modalTarget);
}

function getTopActiveModal(excludeId) {
	const ordered = getActiveModalsInOrder();
	for (let idx = ordered.length - 1; idx >= 0; idx -= 1) {
		const modalEl = ordered[idx];
		if (!excludeId || modalEl.id !== excludeId) {
			return $(modalEl);
		}
	}
	return $();
}

function inferSourceModal(options, targetId) {
	if (options && options.sourceModal) {
		const $source = normalizeModalTarget(options.sourceModal);
		if ($source.length) {
			return $source;
		}
	}

	if (options && options.trigger) {
		const triggerEl = options.trigger.jquery ? options.trigger[0] : options.trigger;
		if (triggerEl && triggerEl.closest) {
			const sourceFromTrigger = triggerEl.closest('.dwm-modal.active');
			if (sourceFromTrigger && sourceFromTrigger.id !== targetId) {
				return $(sourceFromTrigger);
			}
		}
	}

	return getTopActiveModal(targetId);
}

/**
 * Open modal
 *
 * @param {string|Element|jQuery} modalTarget Modal element ID, selector, element, or jQuery object
 * @param {Object} [options] Open options
 */
export function openModal(modalTarget, options = {}) {
	const $modal = normalizeModalTarget(modalTarget);
	if (!$modal.length) {
		return;
	}

	const modalId = $modal.attr('id') || '';
	const $sourceModal = options.inheritState === false ? $() : inferSourceModal(options, modalId);
	if ($sourceModal.length) {
		$modal.toggleClass('is-maximized', $sourceModal.hasClass('is-maximized'));
	}

	$modal.attr('data-modal-open-order', ++modalOpenOrder);
	$modal.addClass('active');
	$('body').addClass('dwm-modal-open');
	syncModalLayering();
	// Reset scroll after display:none is removed
	$modal.find( '.dwm-modal-body, [data-docs-content]' ).scrollTop( 0 );
	$(document).trigger('dwmModalOpened', [$modal, modalId]);
}

/**
 * Close modal
 *
 * @param {string} [modalId] If provided, closes only that modal.
 *                           If omitted, closes all modals (e.g. after save/delete).
 */
export function closeModal( modalId ) {
	if ( modalId ) {
		const $modal = normalizeModalTarget(modalId);
		const closedModalId = $modal.attr('id') || '';
		$modal.removeClass( 'active' );
		$modal.removeAttr('data-modal-open-order');
		$modal.css({ 'z-index': '', 'pointer-events': '' });
		$modal.find('.dwm-modal-overlay').css('visibility', '');
		$(document).trigger('dwmModalClosed', [$modal, closedModalId]);
	} else {
		$( '.dwm-modal.active' ).each(function() {
			const $modal = $(this);
			const closedModalId = $modal.attr('id') || '';
			$modal
				.removeClass('active')
				.removeAttr('data-modal-open-order')
				.css({ 'z-index': '', 'pointer-events': '' });
			$modal.find('.dwm-modal-overlay').css('visibility', '');
			$(document).trigger('dwmModalClosed', [$modal, closedModalId]);
		});
	}
	syncModalLayering();
	// Only remove the body scroll-lock when no modals remain open
	if ( $( '.dwm-modal.active' ).length === 0 ) {
		$( 'body' ).removeClass( 'dwm-modal-open' );
	}
}

function getActiveModalsInOrder() {
	return $('.dwm-modal.active').get().sort((a, b) => {
		const aOrder = parseInt($(a).attr('data-modal-open-order'), 10) || 0;
		const bOrder = parseInt($(b).attr('data-modal-open-order'), 10) || 0;
		return aOrder - bOrder;
	});
}

function syncModalLayering() {
	const ordered = getActiveModalsInOrder();

	if (!ordered.length) {
		$('body').removeClass('dwm-modal-open');
		return;
	}

	ordered.forEach((modalEl, idx) => {
		const isTop = idx === ordered.length - 1;
		const $modal = $(modalEl);

		$modal.css({
			'z-index': modalZBase + (idx * 2),
			'pointer-events': isTop ? 'auto' : 'none'
		});
		$modal.find('.dwm-modal-overlay').css('visibility', isTop ? 'visible' : 'hidden');
	});

	$('body').addClass('dwm-modal-open');
}

// Expose to global scope for cross-bundle and non-import access
window.openModal  = openModal;
window.closeModal = closeModal;
window.dwmModalAPI = {
	open: openModal,
	close: closeModal
};

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
			openModal(modalId, { trigger: this });
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
