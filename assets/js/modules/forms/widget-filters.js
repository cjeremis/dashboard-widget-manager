/**
 * Dashboard Widget Manager - Widget Filters Module
 *
 * Handles search and filter behavior for the widget manager list.
 * Mirrors the CTA Manager filter pattern with status and display mode filters.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import { dwmConfirm } from '../partials/dialog.js';

(function($) {
	'use strict';

	const DWMFilters = {

		filterConfig: {
			'dwm-status-filter':  { param: 'status',  attr: 'data-widget-status' },
			'dwm-display-filter': { param: 'display', attr: 'data-widget-display' },
		},

		searchParam: 'search',
		defaultStatusFilter: 'all',
		currentStatusFilter: null,

		init() {
			this.cacheElements();
			if ( ! this.$widgetList.length ) return;

			this.loadFiltersFromURL();
			this.setDefaultStatusFilter();
			this.loadHeaderStatusFromURL();
			this.bindEvents();
			this.applyFilters();
			this.updateSearchIcon();
		},

		cacheElements() {
			this.$widgetList       = $('.dwm-widget-cards');
			this.$widgetItems      = this.$widgetList.find('.dwm-widget-card');
			this.$applyBtn         = $('#dwm-apply-widget-filters');
			this.$resetBtn         = $('#dwm-reset-widget-filters');
			this.$searchInput      = $('#dwm-search-input');
			this.$searchIcon       = $('#dwm-search-icon');
			this.$filtersModal     = $('#dwm-filters-modal');
			this.$statusFilter     = $('#dwm-status-filter');
			this.$statusFilterBtns = $('.dwm-status-filter');
			this.$emptyTrashWrapper       = $('#dwm-empty-trash-wrapper');
			this.$emptyTrashWrapperModal  = $('#dwm-empty-trash-wrapper-modal');
			this.$emptyTrashBtn           = $('#dwm-empty-trash-btn');
			this.$emptyTrashBtnModal      = $('#dwm-empty-trash-btn-modal');
			this.$filterEmptyState = $('#dwm-filter-empty-state');
			this.$clearFiltersBtn  = $('#dwm-clear-filters-btn');
		},

		loadHeaderStatusFromURL() {
			const urlParams  = new URLSearchParams(window.location.search);
			const statusValue = urlParams.get('status');

			if ( statusValue ) {
				this.currentStatusFilter = statusValue;
				if ( this.$statusFilterBtns.length ) {
					this.$statusFilterBtns.removeClass('is-active');
					const $matchingBtn = this.$statusFilterBtns.filter('[data-filter="' + statusValue + '"]');
					if ( $matchingBtn.length ) {
						$matchingBtn.addClass('is-active');
					} else {
						this.currentStatusFilter = 'all';
						this.$statusFilterBtns.filter('[data-filter="all"]').addClass('is-active');
					}
				}
			} else {
				const $activeBtn = this.$statusFilterBtns.filter('.is-active');
				this.currentStatusFilter = $activeBtn.length
					? ( $activeBtn.attr('data-filter') || 'all' )
					: 'all';
			}
		},

		setDefaultStatusFilter() {
			const urlParams = new URLSearchParams(window.location.search);
			if ( ! urlParams.has('status') ) {
				this.$statusFilter.val(this.defaultStatusFilter);
			}
		},

		loadFiltersFromURL() {
			const urlParams = new URLSearchParams(window.location.search);

			for ( const [fieldId, config] of Object.entries(this.filterConfig) ) {
				const $field = $('#' + fieldId);
				if ( ! $field.length ) continue;
				const value = urlParams.get(config.param);
				if ( value !== null ) {
					$field.val(value);
				}
			}

			const searchValue = urlParams.get(this.searchParam);
			if ( searchValue ) {
				this.$searchInput.val(searchValue);
			}
		},

		getFilterValues() {
			const filters = {};

			for ( const [fieldId, config] of Object.entries(this.filterConfig) ) {
				const $field = $('#' + fieldId);
				filters[config.param] = $field.length ? ( $field.val() || '' ) : '';
			}

			filters[this.searchParam] = this.$searchInput.val() || '';
			return filters;
		},

		bindEvents() {
			const self = this;

			// Header status filter buttons
			this.$statusFilterBtns.on('click', function() {
				const $btn       = $(this);
				const filterValue = $btn.attr('data-filter');

				self.$statusFilterBtns.removeClass('is-active');
				$btn.addClass('is-active');
				self.currentStatusFilter = filterValue;
				self.applyFilters();
				self.updateURL();
			});

			// Apply filters button
			this.$applyBtn.on('click', function() {
				const modalStatusValue = self.$statusFilter.val();
				if ( modalStatusValue ) {
					self.currentStatusFilter = modalStatusValue;
					self.$statusFilterBtns.removeClass('is-active');
					const $matchingBtn = self.$statusFilterBtns.filter('[data-filter="' + modalStatusValue + '"]');
					if ( $matchingBtn.length ) {
						$matchingBtn.addClass('is-active');
					}
				}
				self.applyFilters();
				self.updateURL();
				self.closeModal();
			});

			// Reset filters button
			this.$resetBtn.on('click', function() {
				self.resetFilters();
				self.applyFilters();
				self.updateURL();
				self.toggleEmptyTrashButton();
				self.closeModal();
			});

			// Status dropdown change — toggle empty trash button
			this.$statusFilter.on('change', function() {
				self.toggleEmptyTrashButton();
			});

			// Empty trash (both list and modal buttons)
			this.$emptyTrashBtn.add(this.$emptyTrashBtnModal).on('click', function() {
				dwmConfirm({
					title:       'Empty Trash',
					message:     'Are you sure you want to permanently delete all trashed widgets? This action cannot be undone.',
					icon:        'trash',
					confirmText: 'Empty Trash',
					onConfirm()  { self.emptyTrash(); },
				});
			});

			// Search with debounce
			let searchTimeout;
			this.$searchInput.on('input', function() {
				clearTimeout(searchTimeout);
				self.updateSearchIcon();
				searchTimeout = setTimeout(function() {
					self.applyFilters();
					self.updateURL();
				}, 300);
			});

			// Search icon — clear when X is shown
			this.$searchIcon.on('click', function() {
				if ( self.$searchInput.val() ) {
					self.$searchInput.val('');
					self.updateSearchIcon();
					self.applyFilters();
					self.updateURL();
				}
			});

			// Clear filters button in empty state
			this.$clearFiltersBtn.on('click', function() {
				self.clearAllFilters();
			});

			// Initial empty trash visibility
			this.toggleEmptyTrashButton();
		},

		updateSearchIcon() {
			const hasValue = this.$searchInput.val().length > 0;
			const $icon    = this.$searchIcon.find('.dashicons');

			if ( hasValue ) {
				$icon.removeClass('dashicons-search').addClass('dashicons-no-alt');
				this.$searchIcon.addClass('has-value');
			} else {
				$icon.removeClass('dashicons-no-alt').addClass('dashicons-search');
				this.$searchIcon.removeClass('has-value');
			}
		},

		toggleEmptyTrashButton() {
			const isTrash = this.$statusFilter.val() === 'trash';
			this.$emptyTrashWrapper.toggle(isTrash);
			this.$emptyTrashWrapperModal.toggle(isTrash);
		},

		applyFilters() {
			this.$widgetItems = this.$widgetList.find('.dwm-widget-card');

			const filters      = this.getFilterValues();
			const searchTerm   = filters[this.searchParam].toLowerCase();
			const statusFilter = this.currentStatusFilter || 'all';

			this.$widgetItems.each(function() {
				const $item      = $(this);
				let visible      = true;
				const itemStatus = $item.attr('data-widget-status') || '';

				// Status filter logic
				if ( statusFilter === 'all' ) {
					if ( itemStatus === 'archived' || itemStatus === 'trash' ) {
						visible = false;
					}
				} else if ( statusFilter !== itemStatus ) {
					visible = false;
				}

				// Display mode filter
				if ( visible ) {
					const displayFilter = filters['display'];
					if ( displayFilter ) {
						const itemDisplay = $item.attr('data-widget-display') || '';
						if ( itemDisplay !== displayFilter ) {
							visible = false;
						}
					}
				}

				// Search
				if ( visible && searchTerm ) {
					const searchableText = $item.attr('data-widget-search') || '';
					if ( ! searchableText.includes(searchTerm) ) {
						visible = false;
					}
				}

				if ( visible ) {
					$item.removeClass('is-filter-hidden').show();
				} else {
					$item.addClass('is-filter-hidden').hide();
				}
			});

			this.updateVisibleCount();
		},

		updateVisibleCount() {
			const visibleCount = this.$widgetItems.not('.is-filter-hidden').length;
			const totalCount   = this.$widgetItems.length;

			if ( visibleCount === 0 && totalCount > 0 ) {
				this.$filterEmptyState.show();
				this.$widgetList.hide();
			} else {
				this.$filterEmptyState.hide();
				this.$widgetList.show();
			}
		},

		updateURL() {
			const filters = this.getFilterValues();
			const url     = new URL(window.location.href);

			for ( const config of Object.values(this.filterConfig) ) {
				url.searchParams.delete(config.param);
			}
			url.searchParams.delete(this.searchParam);

			for ( const [param, value] of Object.entries(filters) ) {
				if ( param === 'status' ) {
					if ( this.currentStatusFilter && this.currentStatusFilter !== 'all' ) {
						url.searchParams.set(param, this.currentStatusFilter);
					}
				} else if ( value ) {
					url.searchParams.set(param, value);
				}
			}

			window.history.replaceState({}, '', url.toString());
		},

		resetFilters() {
			for ( const [fieldId, config] of Object.entries(this.filterConfig) ) {
				const $field = $('#' + fieldId);
				if ( ! $field.length ) continue;
				if ( config.param === 'status' ) {
					$field.val(this.defaultStatusFilter);
				} else {
					$field.val('');
				}
			}

			this.currentStatusFilter = this.defaultStatusFilter;
			this.$statusFilterBtns.removeClass('is-active');
			this.$statusFilterBtns.filter('[data-filter="all"]').addClass('is-active');
			this.$searchInput.val('');
			this.updateSearchIcon();
		},

		clearAllFilters() {
			this.resetFilters();
			this.$statusFilterBtns.removeClass('is-active');
			this.$statusFilterBtns.filter('[data-filter="all"]').addClass('is-active');
			this.currentStatusFilter = 'all';

			const url       = new URL(window.location.href);
			const pageParam = url.searchParams.get('page');
			url.search      = '';
			if ( pageParam ) {
				url.searchParams.set('page', pageParam);
			}

			window.history.replaceState({}, '', url.toString());
			this.applyFilters();
		},

		emptyTrash() {
			const self = this;

			$.ajax({
				url:  dwmAdminVars.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dwm_empty_trash',
					nonce:  dwmAdminVars.nonce,
				},
				success( response ) {
					if ( response.success ) {
						self.$widgetItems.filter('[data-widget-status="trash"]').remove();
						self.$widgetItems = self.$widgetList.find('.dwm-widget-card');
						self.updateVisibleCount();
						if ( window.DWMToast ) {
							window.DWMToast.show(response.data.message || 'Trash emptied successfully.', 'success');
						}
					} else {
						if ( window.DWMToast ) {
							window.DWMToast.show(response.data.message || 'Failed to empty trash.', 'error');
						}
					}
				},
				error() {
					if ( window.DWMToast ) {
						window.DWMToast.show('An error occurred while emptying trash.', 'error');
					}
				},
			});
		},

		closeModal() {
			if ( typeof window.closeModal === 'function' ) {
				window.closeModal('dwm-filters-modal');
			}
		},
	};

	$(function() {
		DWMFilters.init();
	});

	window.DWMFilters = DWMFilters;

})(jQuery);

export default DWMFilters;
