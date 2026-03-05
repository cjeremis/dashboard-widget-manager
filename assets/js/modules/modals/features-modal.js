/**
 * Dashboard Widget Manager - Features Modal Module
 *
 * Contains JavaScript behavior for the features modal.
 * Used by admin entry bundles to handle modal interactions.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

(function($) {
	'use strict';

	var DWMFeaturesModal = {
		/**
		 * Selectors
		 */
		selectors: {
			modal: '#dwm-features-modal',
			openTrigger: '[data-open-modal="#dwm-features-modal"]',
			layout: '.dwm-sidebar-modal-layout',
			sidebarToggle: '[data-dwm-features-sidebar-toggle]',
			stickyHeader: '[data-dwm-features-sticky-header]',
			stickyTitle: '[data-dwm-sticky-title]',
			stickyIcon: '[data-dwm-sticky-icon]',
			stickyBadge: '[data-dwm-sticky-badge]',
			pageContainer: '[data-dwm-features-content]',
			pages: '[data-dwm-features-page-content]',
			menuLinks: '[data-dwm-features-page]',
			pageNav: '[data-dwm-page-nav]',
			navButtons: '[data-dwm-nav-direction]',
			sidebarSearch: '[data-dwm-features-search]',
		},

		/**
		 * Collapsed search button (dynamically created, docs-modal pattern)
		 */
		$collapsedSearch: null,

		/**
		 * Initialize the module
		 */
		init: function() {
			this.$collapsedSearch = $('<button type="button" class="dwm-features-collapsed-search" aria-label="Search features"><span class="dashicons dashicons-search"></span></button>');
			this.bindEvents();
		},

		/**
		 * Bind all event handlers
		 */
		bindEvents: function() {
			var self = this;

			// Modal opened event
			$(document).on('dwmModalOpened', function(e, $modal, modalId) {
				if (modalId === 'dwm-features-modal') {
					self.onModalOpened();
				}
			});

			// Sidebar toggle (circular button at sidebar edge)
			$(document).on('click', this.selectors.sidebarToggle, function(e) {
				e.preventDefault();
				self.toggleSidebar();
			});

			// Collapsed search — expand sidebar and focus search
			$(document).on('click', self.selectors.modal + ' .dwm-features-collapsed-search', function(e) {
				e.preventDefault();
				$(self.selectors.modal).find(self.selectors.layout).removeClass('is-sidebar-collapsed');
				sessionStorage.setItem('dwm_features_sidebar_collapsed', '0');
				setTimeout(function() {
					$(self.selectors.modal).find(self.selectors.sidebarSearch).focus();
				}, 350);
			});

			// Page navigation (menu links)
			$(document).on('click', this.selectors.menuLinks, function(e) {
				e.preventDefault();
				self.onPageChange($(this));
			});

			// Next/Previous buttons
			$(document).on('click', this.selectors.navButtons, function() {
				var direction = $(this).data('dwm-nav-direction');
				self.navigatePages(direction);
			});
		},

		/**
		 * Handle modal opening
		 */
		onModalOpened: function() {
			// Ensure first tab is active when modal opens
			var $modal = $(this.selectors.modal);
			var $firstTab = $modal.find('.dwm-sidebar-modal-menu-link').first();

			if ($firstTab.length && window.DWMFeaturesNav && window.DWMFeaturesNav.selectPage) {
				var firstPageId = $firstTab.data('dwm-features-page');
				window.DWMFeaturesNav.selectPage(firstPageId);
			}

			// Update sticky header for active page
			this.updateStickyHeader();
			this.updatePageNavigation();
			this.restoreSidebarState();
			this.placeCollapsedSearch();
		},

		/**
		 * Toggle sidebar collapsed state (docs-modal pattern)
		 */
		toggleSidebar: function() {
			var $layout = $(this.selectors.layout);
			$layout.toggleClass('is-sidebar-collapsed');

			var isCollapsed = $layout.hasClass('is-sidebar-collapsed');
			sessionStorage.setItem('dwm_features_sidebar_collapsed', isCollapsed ? '1' : '0');
		},

		/**
		 * Handle page change
		 */
		onPageChange: function($button) {
			var pageId = $button.data('dwm-features-page');
			var $page = $('[data-dwm-features-page-content="' + pageId + '"]');

			if (!$page.length) {
				return;
			}

			// Update active page
			$(this.selectors.pages).removeClass('is-active');
			$page.addClass('is-active');

			// Update menu active state
			$(this.selectors.menuLinks).removeClass('is-active');
			$button.addClass('is-active');

			// Scroll content to top
			$(this.selectors.pageContainer).scrollTop(0);

			// Update sticky header
			this.updateStickyHeader();

			// Update navigation buttons
			this.updatePageNavigation();

			// Reposition collapsed search
			this.placeCollapsedSearch();
		},

		/**
		 * Navigate to next or previous page
		 */
		navigatePages: function(direction) {
			var $activeMenu = $(this.selectors.menuLinks + '.is-active');
			var $allMenus = $(this.selectors.menuLinks);
			var currentIndex = $allMenus.index($activeMenu);
			var nextIndex = direction === 'next' ? currentIndex + 1 : currentIndex - 1;

			if (nextIndex >= 0 && nextIndex < $allMenus.length) {
				$allMenus.eq(nextIndex).click();
			}
		},

		/**
		 * Update sticky header with current page info
		 */
		updateStickyHeader: function() {
			var $activePage = $(this.selectors.pages + '.is-active');
			var title = $activePage.data('page-title') || 'Features';
			var icon = $activePage.data('page-icon') || '';

			// Update sticky header text
			$(this.selectors.stickyTitle).text(title);

			// Update icon if available
			if (icon) {
				$(this.selectors.stickyIcon).text(this.getCategoryIcon(icon));
			}

			// Show/hide badge for non-overview pages
			var $badge = $(this.selectors.stickyBadge);
			if ($activePage.data('dwm-features-page-content') !== 'overview') {
				$badge.addClass('is-visible');
			} else {
				$badge.removeClass('is-visible');
			}
		},

		/**
		 * Update next/previous button visibility
		 */
		updatePageNavigation: function() {
			var $activeMenu = $(this.selectors.menuLinks + '.is-active');
			var $allMenus = $(this.selectors.menuLinks);
			var currentIndex = $allMenus.index($activeMenu);
			var hasPrev = currentIndex > 0;
			var hasNext = currentIndex < $allMenus.length - 1;

			var $prevBtn = $(this.selectors.navButtons + '.is-prev');
			var $nextBtn = $(this.selectors.navButtons + '.is-next');

			$prevBtn.prop('disabled', !hasPrev).toggle(hasPrev);
			$nextBtn.prop('disabled', !hasNext).toggle(hasNext);
		},

		/**
		 * Place collapsed search button in sticky header right
		 */
		placeCollapsedSearch: function() {
			if (!this.$collapsedSearch) return;
			var $headerRight = $(this.selectors.modal).find('.dwm-sidebar-modal-sticky-header-right');
			if ($headerRight.length) {
				this.$collapsedSearch.prependTo($headerRight);
			}
		},

		/**
		 * Get icon emoji for category
		 */
		getCategoryIcon: function(iconName) {
			var iconMap = {
				'chart-bar': '📊',
				'search': '🔍',
				'database': '💾',
				'art': '🎨',
				'layout': '📐',
				'admin-plugins': '🔌',
				'performance': '⚡',
				'code-standards': '</>',
				'integrations': '🔗',
				'filter': '🔍'
			};
			return iconMap[iconName] || '•';
		},

		/**
		 * Restore sidebar state from sessionStorage
		 */
		restoreSidebarState: function() {
			var isCollapsed = sessionStorage.getItem('dwm_features_sidebar_collapsed') === '1';
			var $layout = $(this.selectors.layout);

			if (isCollapsed) {
				$layout.addClass('is-sidebar-collapsed');
			}

			// Auto-collapse on mobile
			if (window.innerWidth <= 782) {
				$layout.addClass('is-sidebar-collapsed');
			}
		}
	};

	// Initialize when DOM is ready
	$(function() {
		DWMFeaturesModal.init();
	});

	// Expose globally for debugging
	window.DWMFeaturesModal = DWMFeaturesModal;

})(jQuery);

// ES6 Export
export default DWMFeaturesModal;
