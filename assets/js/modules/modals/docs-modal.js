/**
 * Dashboard Widget Manager - Docs Modal Module
 *
 * Contains JavaScript behavior for the docs modal.
 * Handles accordion navigation, search, page display,
 * maximize/restore, category badge, and prev/next navigation.
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
			layout: '.dwm-docs-layout',
			stickyTitle: '[data-dwm-docs-sticky-title]',
			stickyIcon: '[data-dwm-docs-sticky-icon]',
			stickyBadge: '[data-dwm-docs-sticky-badge]',
			pageNav: '[data-dwm-docs-page-nav]',
			navButtons: '[data-dwm-docs-nav-direction]'
		},

		currentPage: 'welcome',
		activePlan: 'all',
		showSoon: true,
		lastFocusedElement: null,

		init: function() {
			this.ensureSearchHint();
			this.ensureMaximizeButton();
			this.bindMaximizeEvents();
			this.bindEvents();
		},

		// -------------------------------------------------------------------------
		// Maximize button
		// -------------------------------------------------------------------------

		ensureMaximizeButton: function() {
			var $modal = $(this.selectors.modal);
			var $header = $modal.find('.dwm-modal-header');
			var $closeBtn = $modal.find('.dwm-modal-close').first();
			if (!$header.length || !$closeBtn.length || $header.find('.dwm-modal-maximize').length) {
				return;
			}
			var $btn = $('<button type="button" class="dwm-modal-maximize" aria-label="Maximize docs modal" title="Maximize"><span class="dashicons dashicons-editor-expand" aria-hidden="true"></span></button>');
			$btn.insertBefore($closeBtn);
		},

		syncMaximizeButtonState: function() {
			var $modal = $(this.selectors.modal);
			var $btn = $modal.find('.dwm-modal-maximize');
			if (!$btn.length) { return; }
			var isMaximized = $modal.hasClass('is-maximized');
			$btn.find('.dashicons').attr('class', 'dashicons ' + (isMaximized ? 'dashicons-editor-contract' : 'dashicons-editor-expand'));
			$btn.attr('aria-label', isMaximized ? 'Restore docs modal size' : 'Maximize docs modal');
			$btn.attr('title', isMaximized ? 'Restore' : 'Maximize');
		},

		setMaximizedState: function(enabled) {
			$(this.selectors.modal).toggleClass('is-maximized', !!enabled);
			this.syncMaximizeButtonState();
		},

		bindMaximizeEvents: function() {
			var self = this;
			var $modal = $(this.selectors.modal);
			if ($modal.data('dwm-maximize-bound')) { return; }
			$modal.data('dwm-maximize-bound', true);
			$modal.on('click', '.dwm-modal-maximize', function(e) {
				e.preventDefault();
				self.setMaximizedState(!$(self.selectors.modal).hasClass('is-maximized'));
			});
		},

		// -------------------------------------------------------------------------
		// Sticky header + prev/next nav
		// -------------------------------------------------------------------------

		getOrderedNavEntries: function() {
			var entries = [];
			var $modal = $(this.selectors.modal);

			var $welcome = $modal.find(this.selectors.welcomeLink).first();
			if ($welcome.length) {
				entries.push({ pageId: 'welcome', $link: $welcome });
			}

			$modal.find(this.selectors.submenuLink).each(function() {
				var pageId = $(this).data('docs-page');
				if (pageId) {
					entries.push({ pageId: pageId, $link: $(this) });
				}
			});

			return entries;
		},

		getPageMeta: function($link, pageId) {
			if (pageId === 'welcome') {
				return { title: 'Welcome', iconClass: 'dashicons-book-alt', badgeText: '' };
			}

			var $page = $(this.selectors.modal).find('[data-docs-page-content="' + pageId + '"]').first();
			var $titleWrapper = $page.find('.dwm-docs-section-title-wrapper').first();
			var $title = $titleWrapper.find('.dwm-docs-section-title').first();
			var $badge = $titleWrapper.find('.dwm-docs-page-title-badge, .dwm-docs-coming-badge, .dwm-docs-pro-badge').first();
			var $pageIcon = $titleWrapper.find('.dwm-docs-title-icon, .dashicons').first();
			var pageTitle = $title.text().trim();
			var pageIconClass = '';
			if ($pageIcon.length) {
				pageIconClass = (($pageIcon.attr('class') || '').match(/dashicons-[\w-]+/) || [ '' ])[0];
			}

			var $accordionItem = $link.closest(this.selectors.accordionItem);
			if ($accordionItem.length) {
				var $trigger = $accordionItem.find(this.selectors.accordionTrigger).first();
				var title = pageTitle || $trigger.find('.dwm-docs-accordion-trigger-text').clone().children().remove().end().text().trim();
				var iconClass = 'dashicons-book-alt';
				var $icon = $trigger.find('.dashicons').first();
				if (pageIconClass) {
					iconClass = pageIconClass;
				} else if ($icon.length) {
					var cls = $icon.attr('class') || '';
					var match = cls.match(/dashicons-[\w-]+/);
					if (match) { iconClass = match[0]; }
				}
				return {
					title: title || 'Documentation',
					iconClass: iconClass,
					badgeText: $badge.text().trim()
				};
			}

			return {
				title: pageTitle || 'Documentation',
				iconClass: pageIconClass || 'dashicons-book-alt',
				badgeText: $badge.text().trim()
			};
		},

		getMenuLabel: function($menuLink) {
			if (!$menuLink || !$menuLink.length) {
				return '';
			}

			var $clone = $menuLink.clone();
			$clone.find('.dashicons, .dwm-docs-pro-badge, .dwm-docs-coming-badge, img').remove();
			return $clone.text().replace(/\s+/g, ' ').trim();
		},

		updateStickyHeader: function(pageId) {
			var $modal = $(this.selectors.modal);
			var $activeLink = pageId === 'welcome'
				? $modal.find(this.selectors.welcomeLink).first()
				: $modal.find(this.selectors.submenuLink + '[data-docs-page="' + pageId + '"]').first();
			var meta = this.getPageMeta($activeLink, pageId);
			var $stickyIcon = $modal.find(this.selectors.stickyIcon);
			var $stickyBadge = $modal.find(this.selectors.stickyBadge);

			$modal.find(this.selectors.stickyTitle).text(meta.title || 'Documentation');
			$stickyIcon.attr('class', meta.iconClass ? 'dwm-sidebar-modal-sticky-icon dashicons ' + meta.iconClass : 'dwm-sidebar-modal-sticky-icon dashicons dashicons-book-alt');

			if (meta.badgeText) {
				$stickyBadge.text(meta.badgeText).addClass('is-visible');
			} else {
				$stickyBadge.text('').removeClass('is-visible');
			}
		},

		updatePageNavigation: function(pageId) {
			var entries = this.getOrderedNavEntries();
			var currentIndex = -1;
			var i;

			for (i = 0; i < entries.length; i++) {
				if (entries[i].pageId === pageId) {
					currentIndex = i;
					break;
				}
			}

			if (currentIndex === -1) {
				currentIndex = 0;
			}

			var prevEntry = currentIndex > 0 ? entries[currentIndex - 1] : null;
			var nextEntry = currentIndex < entries.length - 1 ? entries[currentIndex + 1] : null;
			var $modal = $(this.selectors.modal);
			var $prevBtn = $modal.find(this.selectors.navButtons + '.is-prev');
			var $nextBtn = $modal.find(this.selectors.navButtons + '.is-next');
			var prevLabel = prevEntry ? this.getMenuLabel(prevEntry.$link) : '';
			var nextLabel = nextEntry ? this.getMenuLabel(nextEntry.$link) : '';

			$prevBtn.find('[data-dwm-docs-prev-label]').text(prevLabel ? 'Prev: ' + prevLabel : 'Prev');
			$nextBtn.find('[data-dwm-docs-next-label]').text(nextLabel ? 'Next: ' + nextLabel : 'Next');
			$prevBtn.attr('aria-label', prevLabel ? 'Previous: ' + prevLabel : 'Previous');
			$nextBtn.attr('aria-label', nextLabel ? 'Next: ' + nextLabel : 'Next');
			$prevBtn.attr('data-docs-nav-target', prevEntry ? prevEntry.pageId : '');
			$nextBtn.attr('data-docs-nav-target', nextEntry ? nextEntry.pageId : '');
			$prevBtn.prop('disabled', !prevEntry).toggle(!!prevEntry);
			$nextBtn.prop('disabled', !nextEntry).toggle(!!nextEntry);
		},

		toggleSidebar: function() {
			var $layout = $(this.selectors.modal).find(this.selectors.layout);
			$layout.toggleClass('is-sidebar-collapsed');
			sessionStorage.setItem('dwm_docs_sidebar_collapsed', $layout.hasClass('is-sidebar-collapsed') ? '1' : '0');
		},

		restoreSidebarState: function() {
			var $layout = $(this.selectors.modal).find(this.selectors.layout);
			var isCollapsed = sessionStorage.getItem('dwm_docs_sidebar_collapsed') === '1';

			$layout.toggleClass('is-sidebar-collapsed', isCollapsed);

			if (window.innerWidth <= 782) {
				$layout.addClass('is-sidebar-collapsed');
			}
		},

		// -------------------------------------------------------------------------
		// Events
		// -------------------------------------------------------------------------

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

			// Page navigation (scoped to inside the modal)
			$(document).on('click', self.selectors.modal + ' ' + self.selectors.pageLink, function(e) {
				e.preventDefault();
				var page = $(this).data('docs-page');
				if (page) {
					self.showPage(page);
					var $sidebarLink = $(self.selectors.modal + ' ' + self.selectors.submenuLink + '[data-docs-page="' + page + '"]').first();
					self.setActiveLink($sidebarLink.length ? $sidebarLink : $(this));
				}
			});

			// Header tools prev/next nav
			$(document).on('click', self.selectors.modal + ' ' + self.selectors.navButtons + '[data-docs-nav-target]', function(e) {
				e.preventDefault();
				var targetPageId = $(this).attr('data-docs-nav-target');
				if (!targetPageId) { return; }
				var $sidebarLink = $(self.selectors.modal + ' ' + self.selectors.submenuLink + '[data-docs-page="' + targetPageId + '"]').first();
				var $welcomeLink = $(self.selectors.modal + ' ' + self.selectors.welcomeLink).first();
				self.showPage(targetPageId);
				self.setActiveLink($sidebarLink.length ? $sidebarLink : (targetPageId === 'welcome' ? $welcomeLink : $sidebarLink));
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
				self.toggleSidebar();
			});

			// Collapsed search button — expand sidebar and focus search
			$(document).on('click', self.selectors.modal + ' .dwm-docs-collapsed-search', function(e) {
				e.preventDefault();
				$(self.selectors.modal).find(self.selectors.layout).removeClass('is-sidebar-collapsed');
				sessionStorage.setItem('dwm_docs_sidebar_collapsed', '0');
				setTimeout(function() {
					$(self.selectors.modal).find(self.selectors.searchInput).focus();
				}, 350);
			});

			// Open docs modal from any trigger
			$(document).on('click', '[data-open-modal="dwm-docs-modal"]', function(e) {
				e.preventDefault();
				e.stopImmediatePropagation();
				var docsPage = $(this).data('docs-page');
				var targetPage = docsPage || 'welcome';
				self.openModal(targetPage, this);
			});

			// Close via close button or overlay
			$(document).on('click', self.selectors.modal + ' ' + self.selectors.closeBtn + ', ' + self.selectors.modal + ' ' + self.selectors.overlay, function(e) {
				e.preventDefault();
				self.closeModal();
			});

			// Escape to close and Tab focus-trap
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

			$(this.selectors.searchInput).val('');
			this.handleSearch('');
			this.collapseAllAccordions();

			this.activePlan = 'all';
			this.showSoon = true;
			var $modal = $(this.selectors.modal);
			$modal.find(this.selectors.planFilterBtn).removeClass('is-active');
			$modal.find('[data-dwm-docs-plan-filter="all"]').addClass('is-active');
			$modal.find(this.selectors.soonToggle).prop('checked', true);
			$modal.find('.is-plan-filtered').removeClass('is-plan-filtered');

			this.restoreSidebarState();
			this.showPage(targetPage);

			var $sidebarLink = $modal.find(this.selectors.submenuLink + '[data-docs-page="' + targetPage + '"]').first();
			var $welcomeLink = $modal.find(this.selectors.welcomeLink).first();
			this.setActiveLink($sidebarLink.length ? $sidebarLink : $welcomeLink);

			if (window.dwmModalAPI && typeof window.dwmModalAPI.open === 'function') {
				window.dwmModalAPI.open($modal, { trigger: triggerEl });
			} else {
				$modal.addClass('active');
				$('body').addClass('dwm-modal-open');
			}
			$(this.selectors.content).scrollTop(0);
			this.syncMaximizeButtonState();

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

			if (window.dwmModalAPI && typeof window.dwmModalAPI.close === 'function') {
				window.dwmModalAPI.close($modal);
			} else {
				$modal.removeClass('active');
				$('body').removeClass('dwm-modal-open');
			}
			this.setMaximizedState(false);

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

			this.activePlan = 'all';
			this.showSoon = true;
			var $modal = $(this.selectors.modal);
			$modal.find(this.selectors.planFilterBtn).removeClass('is-active');
			$modal.find('[data-dwm-docs-plan-filter="all"]').addClass('is-active');
			$modal.find(this.selectors.soonToggle).prop('checked', true);
			$modal.find('.is-plan-filtered').removeClass('is-plan-filtered');

			$modal.find(this.selectors.layout).removeClass('is-sidebar-collapsed');
			sessionStorage.setItem('dwm_docs_sidebar_collapsed', '0');
			this.setMaximizedState(false);
		},

		applyDocFilters: function() {
			var self = this;
			var $modal = $(this.selectors.modal);

			$modal.find('.is-plan-filtered').removeClass('is-plan-filtered');

			if (this.activePlan === 'all' && this.showSoon) {
				return;
			}

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
			var $target = $(this.selectors.modal).find('[data-docs-page-content="' + pageId + '"]');
			if (!$target.length) {
				if (pageId !== 'welcome') {
					this.showPage('welcome');
				}
				return;
			}
			$(this.selectors.pageContent).removeClass('is-active');
			$target.addClass('is-active');
			this.currentPage = pageId;
			$(this.selectors.pageContent).removeClass('has-sticky-summary');
			$target.addClass('has-sticky-summary');
			this.updateStickyHeader(pageId);
			this.updatePageNavigation(pageId);

			$(this.selectors.content).scrollTop(0);
		},

		setActiveLink: function($link) {
			if (!$link || !$link.length) {
				return;
			}

			$(this.selectors.submenuLink).removeClass('is-active');
			$(this.selectors.welcomeLink).removeClass('is-active');
			$link.addClass('is-active');

			var $parentItem = $link.closest(this.selectors.accordionItem);
			if ($parentItem.length) {
				this.expandAccordion($parentItem);
			}

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
		$( document ).trigger( 'dwm-docs-modal-ready' );
	});

	window.DWMDocsModal = DWMDocsModal;

})(jQuery);
