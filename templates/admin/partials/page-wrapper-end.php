<?php
/**
 * Admin Partial Template - Page Wrapper End
 *
 * Handles markup rendering for the page wrapper end admin partial template.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Close page content and include footer
include __DIR__ . '/footer.php';
?>
</div>

<?php
// Render notifications panel (content populated by AJAX)
include __DIR__ . '/notifications-panel.php';

// Render docs modal (available on all DWM admin pages)
include DWM_PLUGIN_DIR . 'templates/admin/modals/docs-modal.php';

// Render support ticket form modal (available on all DWM admin pages)
include DWM_PLUGIN_DIR . 'templates/admin/partials/support-ticket-form.php';

// Render pro upgrade modal (available on all DWM admin pages)
include DWM_PLUGIN_DIR . 'templates/admin/modals/pro-upgrade.php';

// Render features modal LAST so it stacks on top of the pro upgrade modal
include DWM_PLUGIN_DIR . 'templates/admin/modals/features-modal.php';
?>

<style id="dwm-modal-maximize-shared-styles">
	.dwm-modal.is-maximized .dwm-modal-content {
		width: 100vw !important;
		max-width: 100vw !important;
		height: 100vh !important;
		max-height: 100vh !important;
		margin: 0 !important;
		border-radius: 0 !important;
	}

	.dwm-modal .dwm-modal-header {
		position: relative;
	}

	.dwm-modal.is-maximized .dwm-modal-header {
		padding-top: 10px !important;
		padding-bottom: 10px !important;
		min-height: 48px;
	}

	.dwm-modal.is-maximized .dwm-modal-header h2 {
		font-size: 21px;
		line-height: 1.2;
	}

	.dwm-modal .dwm-modal-maximize {
		position: absolute;
		top: 50%;
		right: 64px;
		transform: translateY(-50%);
		width: 30px;
		height: 30px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		border: 1px solid rgba(255, 255, 255, 0.28);
		background: rgba(255, 255, 255, 0.09);
		border-radius: 8px;
		color: #ffffff;
		cursor: pointer;
		transition: background 0.15s ease, transform 0.15s ease;
	}

	.dwm-modal .dwm-modal-maximize:hover {
		background: rgba(255, 255, 255, 0.22);
		transform: translateY(calc(-50% - 1px));
	}

	.dwm-modal .dwm-modal-maximize .dashicons {
		font-size: 17px;
		width: 17px;
		height: 17px;
	}

	.dwm-modal .dwm-modal-close {
		top: 50% !important;
		transform: translateY(-50%) !important;
	}

	.dwm-modal.is-maximized .dwm-modal-maximize {
		top: 50%;
		transform: translateY(-50%);
	}

	@media (max-width: 782px) {
		.dwm-modal .dwm-modal-maximize {
			right: 58px;
			width: 28px;
			height: 28px;
			border-radius: 7px;
		}
	}
</style>

<script>
(function() {
	'use strict';

	var modalSelectors = [
		'#dwm-widget-editor-modal',
		'#dwm-view-data-modal'
	];

	function getTargetModals() {
		return modalSelectors
			.map(function(selector) { return document.querySelector(selector); })
			.filter(function(modalEl) { return !!modalEl; });
	}

	function syncMaximizeIcon(modalEl) {
		var button = modalEl ? modalEl.querySelector('.dwm-modal-maximize') : null;
		if (!button) {
			return;
		}

		var icon = button.querySelector('.dashicons');
		var isMaximized = modalEl.classList.contains('is-maximized');
		if (icon) {
			icon.className = 'dashicons ' + (isMaximized ? 'dashicons-editor-contract minimize' : 'dashicons-editor-expand');
		}
		button.setAttribute('aria-label', isMaximized ? 'Restore modal size' : 'Maximize modal');
		button.setAttribute('title', isMaximized ? 'Restore' : 'Maximize');
	}

	function ensureMaximizeButton(modalEl) {
		if (!modalEl) {
			return;
		}

		var header = modalEl.querySelector('.dwm-modal-header');
		var closeBtn = modalEl.querySelector('.dwm-modal-close');
		if (!header || !closeBtn || header.querySelector('.dwm-modal-maximize')) {
			return;
		}

		var maximizeBtn = document.createElement('button');
		maximizeBtn.type = 'button';
		maximizeBtn.className = 'dwm-modal-maximize';
		maximizeBtn.innerHTML = '<span class="dashicons dashicons-editor-expand" aria-hidden="true"></span>';
		header.insertBefore(maximizeBtn, closeBtn);
		syncMaximizeIcon(modalEl);
	}

	function setMaximized(modalEl, enabled) {
		if (!modalEl) {
			return;
		}
		modalEl.classList.toggle('is-maximized', !!enabled);
		syncMaximizeIcon(modalEl);
	}

	function bindModalMaximize() {
		if (window.__dwmSharedModalMaximizeBound) {
			return;
		}
		window.__dwmSharedModalMaximizeBound = true;

		var modalEls = getTargetModals();
		if (!modalEls.length) {
			return;
		}

		modalEls.forEach(function(modalEl) {
			ensureMaximizeButton(modalEl);
		});

		document.addEventListener('click', function(e) {
			var maximizeBtn = e.target.closest('.dwm-modal-maximize');
			if (!maximizeBtn) {
				return;
			}

			var targetModal = maximizeBtn.closest('.dwm-modal');
			if (!targetModal || modalEls.indexOf(targetModal) === -1) {
				return;
			}

			e.preventDefault();
			setMaximized(targetModal, !targetModal.classList.contains('is-maximized'));
		});

		document.addEventListener('dwmModalOpened', function(e, $modal) {
			var modalEl = $modal && $modal[0] ? $modal[0] : null;
			if (!modalEl || modalEls.indexOf(modalEl) === -1) {
				return;
			}
			ensureMaximizeButton(modalEl);
			syncMaximizeIcon(modalEl);
		});

		document.addEventListener('keydown', function(e) {
			if (e.key !== 'Escape') {
				return;
			}

			modalEls.forEach(function(modalEl) {
				var isVisible = modalEl.classList.contains('active');
				if (isVisible && modalEl.classList.contains('is-maximized')) {
					setMaximized(modalEl, false);
				}
			});
		});

		modalEls.forEach(function(modalEl) {
			var observer = new MutationObserver(function() {
				ensureMaximizeButton(modalEl);
				if (!modalEl.classList.contains('active') && modalEl.classList.contains('is-maximized')) {
					setMaximized(modalEl, false);
				}
			});
			observer.observe(modalEl, { attributes: true, attributeFilter: [ 'class' ] });
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bindModalMaximize);
	} else {
		bindModalMaximize();
	}
})();
</script>
