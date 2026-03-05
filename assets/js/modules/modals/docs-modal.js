/**
 * Dashboard Widget Manager - Docs Modal Module
 *
 * Contains JavaScript behavior for the docs modal.
 * Handles accordion navigation, search, and page display.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

(function($) {
	'use strict';

	var DWMDocsModal = {
		selectors: {
			modal: '#dwm-docs-modal',
			closeBtn: '.dwm-modal-close',
			overlay: '.dwm-modal-overlay',
			searchInput: '[data-docs-search]',
			accordion: '[data-docs-accordion]',
			accordionItem: '.dwm-docs-accordion-item',
			accordionTrigger: '.dwm-docs-accordion-trigger',
			accordionPanel: '.dwm-docs-accordion-panel',
			submenuItem: '.dwm-docs-submenu-item',
			submenuLink: '.dwm-docs-submenu-link',
			submenuTrigger: '[data-submenu-trigger]',
			submenuPanel: '.dwm-docs-submenu-panel',
			submenuAccordion: '.dwm-docs-submenu-accordion',
			welcomeLink: '.dwm-docs-welcome-link',
			pageLink: '[data-docs-page]',
			pageContent: '[data-docs-page-content]',
			content: '[data-docs-content]',
			planFilterBtn: '[data-dwm-docs-plan-filter]',
			soonToggle: '[data-dwm-docs-soon-toggle]',
			sidebarToggle: '[data-dwm-docs-sidebar-toggle]',
			layout: '.dwm-docs-layout'
		},

		currentPage: 'welcome',
		activePlan: 'all',
		showSoon: true,
		$collapsedSearch: null,
		lastFocusedElement: null,

		init: function() {
			this.$collapsedSearch = $('<button type="button" class="dwm-docs-collapsed-search" aria-label="Search documentation"><span class="dashicons dashicons-search"></span></button>');
			this.ensureSearchHint();
			this.bindEvents();
		},

		ensureSearchHint: function() {
			var $searchWrap = $(this.selectors.modal).find('.dwm-docs-search').first();
			if (!$searchWrap.length || $searchWrap.find('.dwm-docs-search-hint').length) {
				return;
			}

			$searchWrap.append('<p class="dwm-docs-search-hint" style="display:none;margin:8px 0 0;color:#6b7280;font-size:12px;">Type at least 3 characters.</p>');
		},

		bindEvents: function() {
			var self = this;

			// Top-level accordion toggle
			$(document).on('click', self.selectors.accordionTrigger, function(e) {
				e.preventDefault();
				self.toggleAccordion($(this));
			});

			// Page navigation (scoped to inside the modal to avoid matching external help-icon triggers)
			$(document).on('click', self.selectors.modal + ' ' + self.selectors.pageLink, function(e) {
				e.preventDefault();
				var page = $(this).data('docs-page');
				if (page) {
					self.showPage(page);
					var $sidebarLink = $(self.selectors.modal + ' ' + self.selectors.submenuLink + '[data-docs-page="' + page + '"]').first();
					self.setActiveLink($sidebarLink.length ? $sidebarLink : $(this));
				}
			});

			// Search with debounce
			var searchTimeout;
			$(document).on('input', self.selectors.searchInput, function() {
				var $input = $(this);
				clearTimeout(searchTimeout);
				searchTimeout = setTimeout(function() {
					self.handleSearch($input.val());
				}, 150);
			});

			// Clear search on Escape
			$(document).on('keydown', self.selectors.searchInput, function(e) {
				if (e.key === 'Escape') {
					$(this).val('');
					self.handleSearch('');
				}
			});

			// Plan filter buttons
			$(document).on('click', self.selectors.modal + ' ' + self.selectors.planFilterBtn, function(e) {
				e.preventDefault();
				self.activePlan = $(this).data('dwm-docs-plan-filter');
				$(self.selectors.modal).find(self.selectors.planFilterBtn).removeClass('is-active');
				$(this).addClass('is-active');
				self.applyDocFilters();
			});

			// Soon toggle
			$(document).on('change', self.selectors.modal + ' ' + self.selectors.soonToggle, function() {
				self.showSoon = $(this).is(':checked');
				self.applyDocFilters();
			});

			// Sidebar collapse toggle
			$(document).on('click', self.selectors.modal + ' ' + self.selectors.sidebarToggle, function(e) {
				e.preventDefault();
				$(self.selectors.modal).find(self.selectors.layout).toggleClass('is-sidebar-collapsed');
			});

			// Collapsed search button — expand sidebar and focus search
			$(document).on('click', self.selectors.modal + ' .dwm-docs-collapsed-search', function(e) {
				e.preventDefault();
				$(self.selectors.modal).find(self.selectors.layout).removeClass('is-sidebar-collapsed');
				setTimeout(function() {
					$(self.selectors.modal).find(self.selectors.searchInput).focus();
				}, 350);
			});

			// Open docs modal from any trigger (help icons, toolbar buttons, etc.)
			$(document).on('click', '[data-open-modal="dwm-docs-modal"]', function(e) {
				e.preventDefault();
				e.stopImmediatePropagation();
				var docsPage = $(this).data('docs-page');
				var targetPage = docsPage || 'welcome';
				self.openModal(targetPage, this);
			});

			// Close via close button or overlay.
			$(document).on('click', self.selectors.modal + ' ' + self.selectors.closeBtn + ', ' + self.selectors.modal + ' ' + self.selectors.overlay, function(e) {
				e.preventDefault();
				self.closeModal();
			});

			// Escape to close and Tab focus-trap while modal is open.
			$(document).on('keydown', function(e) {
				var $modal = $(self.selectors.modal);
				if (!$modal.hasClass('active')) {
					return;
				}

				if (e.key === 'Escape') {
					e.preventDefault();
					self.closeModal();
					return;
				}

				if (e.key === 'Tab') {
					self.trapFocus(e);
				}
			});
		},

		openModal: function(targetPage, triggerEl) {
			this.lastFocusedElement = triggerEl || document.activeElement;

			// Set page state synchronously before showing modal.
			$(this.selectors.searchInput).val('');
			this.handleSearch('');
			this.collapseAllAccordions();
			this.showPage(targetPage);

			// Reset plan filters.
			this.activePlan = 'all';
			this.showSoon = true;
			var $modal = $(this.selectors.modal);
			$modal.find(this.selectors.planFilterBtn).removeClass('is-active');
			$modal.find('[data-dwm-docs-plan-filter="all"]').addClass('is-active');
			$modal.find(this.selectors.soonToggle).prop('checked', true);
			$modal.find('.is-plan-filtered').removeClass('is-plan-filtered');

			// Reset sidebar.
			$modal.find(this.selectors.layout).removeClass('is-sidebar-collapsed');

			var $sidebarLink = $modal.find(this.selectors.submenuLink + '[data-docs-page="' + targetPage + '"]').first();
			var $welcomeLink = $modal.find(this.selectors.welcomeLink).first();
			this.setActiveLink($sidebarLink.length ? $sidebarLink : $welcomeLink);

			$modal.addClass('active');
			$('body').addClass('dwm-modal-open');
			$(this.selectors.content).scrollTop(0);

			var self = this;
			setTimeout(function() {
				var $search = $modal.find(self.selectors.searchInput).first();
				if ($search.length) {
					$search.trigger('focus');
				}
			}, 0);
		},

		closeModal: function() {
			var $modal = $(this.selectors.modal);
			if (!$modal.hasClass('active')) {
				return;
			}

			$modal.removeClass('active');
			$('body').removeClass('dwm-modal-open');

			if (this.lastFocusedElement && typeof this.lastFocusedElement.focus === 'function') {
				this.lastFocusedElement.focus();
			}
		},

		getFocusableElements: function() {
			var $modal = $(this.selectors.modal);
			return $modal
				.find('a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])')
				.filter(':visible');
		},

		trapFocus: function(e) {
			var $focusables = this.getFocusableElements();
			if (!$focusables.length) {
				return;
			}

			var first = $focusables.get(0);
			var last = $focusables.get($focusables.length - 1);
			var active = document.activeElement;

			if (e.shiftKey && active === first) {
				e.preventDefault();
				last.focus();
			} else if (!e.shiftKey && active === last) {
				e.preventDefault();
				first.focus();
			}
		},

		resetModal: function() {
			$(this.selectors.searchInput).val('');
			this.handleSearch('');
			this.collapseAllAccordions();
			this.showPage('welcome');
			this.setActiveLink($(this.selectors.welcomeLink));

			// Reset plan filters
			this.activePlan = 'all';
			this.showSoon = true;
			var $modal = $(this.selectors.modal);
			$modal.find(this.selectors.planFilterBtn).removeClass('is-active');
			$modal.find('[data-dwm-docs-plan-filter="all"]').addClass('is-active');
			$modal.find(this.selectors.soonToggle).prop('checked', true);
			$modal.find('.is-plan-filtered').removeClass('is-plan-filtered');

			// Reset sidebar
			$modal.find(this.selectors.layout).removeClass('is-sidebar-collapsed');
		},

		/**
		 * Apply plan and soon filters to docs navigation
		 */
		applyDocFilters: function() {
			var self = this;
			var $modal = $(this.selectors.modal);

			// Reset all plan-filtered state
			$modal.find('.is-plan-filtered').removeClass('is-plan-filtered');

			if (this.activePlan === 'all' && this.showSoon) {
				return;
			}

			// Filter leaf items with data-docs-plan
			$modal.find('[data-docs-plan]').each(function() {
				var $el = $(this);
				var plan = $el.attr('data-docs-plan');
				var implemented = $el.attr('data-docs-implemented');

				if (self.activePlan !== 'all' && plan !== self.activePlan) {
					$el.addClass('is-plan-filtered');
					return;
				}
				if (!self.showSoon && implemented === '0') {
					$el.addClass('is-plan-filtered');
				}
			});

			// Hide top-level accordion items where all plan-children are filtered
			$modal.find(self.selectors.accordionItem).each(function() {
				var $item = $(this);
				if ($item.hasClass('is-plan-filtered')) return;
				var $children = $item.find('[data-docs-plan]');
				if ($children.length && $children.filter(':not(.is-plan-filtered)').length === 0) {
					$item.addClass('is-plan-filtered');
				}
			});
		},

		collapseAllAccordions: function() {
			$(this.selectors.accordionTrigger).attr('aria-expanded', 'false');
			$(this.selectors.accordionPanel).attr('hidden', true);
			$(this.selectors.submenuTrigger).attr('aria-expanded', 'false');
			$(this.selectors.submenuPanel).attr('hidden', true);
		},

		toggleAccordion: function($trigger) {
			var self = this;
			var $item = $trigger.closest(this.selectors.accordionItem);
			var $panel = $item.find(this.selectors.accordionPanel).first();
			var isExpanded = $trigger.attr('aria-expanded') === 'true';

			// Close all other accordions
			$(this.selectors.accordionItem).each(function() {
				var $other = $(this);
				if (!$other.is($item)) {
					$other.find(self.selectors.accordionTrigger).first().attr('aria-expanded', 'false');
					$other.find(self.selectors.accordionPanel).first().attr('hidden', true);
				}
			});

			$trigger.attr('aria-expanded', !isExpanded);
			if (isExpanded) {
				$panel.attr('hidden', true);
			} else {
				$panel.removeAttr('hidden');
				var $firstLink = $panel.find(self.selectors.submenuLink).first();
				if ($firstLink.length) {
					var page = $firstLink.data('docs-page');
					if (page) {
						self.showPage(page);
						self.setActiveLink($firstLink);
					}
				}
			}
		},

		expandAccordion: function($item) {
			var $trigger = $item.find(this.selectors.accordionTrigger).first();
			var $panel = $item.find(this.selectors.accordionPanel).first();
			if ($trigger.length && $trigger.attr('aria-expanded') !== 'true') {
				$trigger.attr('aria-expanded', 'true');
				$panel.removeAttr('hidden');
			}
		},

		showPage: function(pageId) {
			var $target = $('[data-docs-page-content="' + pageId + '"]');
			if (!$target.length) {
				if (pageId !== 'welcome') {
					this.showPage('welcome');
				}
				return;
			}
			$(this.selectors.pageContent).removeClass('is-active');
			$target.addClass('is-active');
			this.currentPage = pageId;

			// Move collapsed-search button into the active page's title wrapper
			var $wrapper = $target.find('.dwm-docs-section-title-wrapper').first();
			if ($wrapper.length && this.$collapsedSearch) {
				this.$collapsedSearch.appendTo($wrapper);
			} else if (this.$collapsedSearch) {
				this.$collapsedSearch.detach();
			}

			$(this.selectors.content).scrollTop(0);
		},

		setActiveLink: function($link) {
			if (!$link || !$link.length) {
				return;
			}

			$(this.selectors.submenuLink).removeClass('is-active');
			$(this.selectors.welcomeLink).removeClass('is-active');
			$link.addClass('is-active');

			// Expand parent accordion if needed
			var $parentItem = $link.closest(this.selectors.accordionItem);
			if ($parentItem.length) {
				this.expandAccordion($parentItem);
			}

			// Expand all parent submenu accordions (inner -> outer)
			var self = this;
			$link.parents(this.selectors.submenuAccordion).each(function() {
				self.expandSubmenuAccordion($(this));
			});
		},

		expandSubmenuAccordion: function($submenuAccordion) {
			var $trigger = $submenuAccordion.find(this.selectors.submenuTrigger).first();
			var $panel = $submenuAccordion.find(this.selectors.submenuPanel).first();

			if ($trigger.length && $trigger.attr('aria-expanded') !== 'true') {
				$trigger.attr('aria-expanded', 'true');
				$panel.removeAttr('hidden');
			}
		},

		handleSearch: function(query) {
			var self = this;
			var q = query.toLowerCase().trim();

			var $accordion = $(this.selectors.accordion);
			var $items = $accordion.find(this.selectors.accordionItem);
			var $searchHint = $(this.selectors.modal).find('.dwm-docs-search-hint').first();

			if (q.length === 0) {
				$searchHint.hide();
				$items.removeClass('is-hidden');
				$accordion.find(self.selectors.submenuItem).removeClass('is-hidden');
				return;
			}

			if (q.length < 3) {
				$searchHint.show();
				$items.removeClass('is-hidden');
				$accordion.find(self.selectors.submenuItem).removeClass('is-hidden');
				return;
			}

			$searchHint.hide();

			$items.each(function() {
				var $item = $(this);
				var parentTitle = ($item.data('search-title') || '').toLowerCase();
				var matchesParent = parentTitle.indexOf(q) !== -1;
				var hasVisibleChild = false;

				$item.find(self.selectors.submenuItem).each(function() {
					var $sub = $(this);
					var subTitle = ($sub.data('search-title') || '').toLowerCase();
					var matches = subTitle.indexOf(q) !== -1 || matchesParent;
					if (matches) {
						$sub.removeClass('is-hidden');
						hasVisibleChild = true;
					} else {
						$sub.addClass('is-hidden');
					}
				});

				if (matchesParent || hasVisibleChild) {
					$item.removeClass('is-hidden');
					if (hasVisibleChild) {
						self.expandAccordion($item);
					}
				} else {
					$item.addClass('is-hidden');
				}
			});
		}
	};

	$(function() {
		DWMDocsModal.init();
	});

	window.DWMDocsModal = DWMDocsModal;

})(jQuery);
