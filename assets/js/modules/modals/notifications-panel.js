/**
 * Dashboard Widget Manager - Notifications Panel Module
 *
 * Manages the slide-out notifications panel, including toggling,
 * refreshing, deleting notifications, and updating the badge count.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

'use strict';

const DWMNotificationsManager = {
	panelElement: null,
	overlayElement: null,
	listElement: null,
	loadingElement: null,
	emptyElement: null,
	buttonElement: null,
	closeButton: null,
	isLoading: false,
	isOpen: false,
	hasOpened: false,

	/**
	 * Initialize the notifications system
	 */
	init() {
		this.cacheDom();
		if (!this.panelElement) return;

		this.bindEvents();
		this.updateNotificationCount();
	},

	/**
	 * Cache DOM elements
	 */
	cacheDom() {
		this.panelElement   = document.getElementById('dwm-notifications-panel');
		this.overlayElement = document.getElementById('dwm-notifications-overlay');
		this.listElement    = document.getElementById('dwm-notifications-list');
		this.loadingElement = document.getElementById('dwm-notifications-loading');
		this.emptyElement   = document.getElementById('dwm-notifications-empty');
		this.buttonElement  = document.querySelector('.dwm-notification-button');
		this.closeButton    = document.getElementById('dwm-notifications-close');
	},

	/**
	 * Bind event listeners
	 */
	bindEvents() {
		if (this.buttonElement) {
			this.buttonElement.addEventListener('click', (e) => {
				e.preventDefault();
				e.stopPropagation();
				this.togglePanel();
			});
		}

		if (this.closeButton) {
			this.closeButton.addEventListener('click', (e) => {
				e.preventDefault();
				this.closePanel();
			});
		}

		if (this.overlayElement) {
			this.overlayElement.addEventListener('click', () => {
				this.closePanel();
			});
		}

		this.bindDeleteButtons();

		document.addEventListener('keydown', (e) => {
			if (e.key === 'Escape' && this.isOpen) {
				this.closePanel();
			}
		});
	},

	/**
	 * Bind delete buttons
	 */
	bindDeleteButtons() {
		if (!this.panelElement) return;

		const deleteButtons = this.panelElement.querySelectorAll('.dwm-notification-delete');
		deleteButtons.forEach((button) => {
			button.replaceWith(button.cloneNode(true));
		});

		this.panelElement.querySelectorAll('.dwm-notification-delete').forEach((button) => {
			button.addEventListener('click', (e) => {
				e.preventDefault();
				e.stopPropagation();

				const notificationId = button.dataset.notificationId;
				if (notificationId) {
					this.deleteNotification(notificationId);
				}
			});
		});
	},

	/**
	 * Toggle panel open/closed
	 */
	togglePanel() {
		if (this.isOpen) {
			this.closePanel();
		} else {
			this.openPanel();
		}
	},

	/**
	 * Open the notifications panel
	 */
	openPanel() {
		if (this.isLoading || this.isOpen) return;

		this.isOpen = true;

		if (this.panelElement) {
			this.panelElement.classList.add('active');
		}
		if (this.overlayElement) {
			this.overlayElement.classList.add('active');
		}

		document.body.classList.add('dwm-notifications-open');

		if (this.hasOpened) {
			this.refreshNotifications();
		}

		this.hasOpened = true;
	},

	/**
	 * Close the notifications panel
	 */
	closePanel() {
		this.isOpen = false;

		if (this.panelElement) {
			this.panelElement.classList.remove('active');
		}
		if (this.overlayElement) {
			this.overlayElement.classList.remove('active');
		}

		document.body.classList.remove('dwm-notifications-open');
	},

	/**
	 * Refresh notifications via AJAX
	 */
	refreshNotifications() {
		if (this.isLoading) return;

		this.isLoading = true;
		this.showLoadingState();

		const data = new FormData();
		data.append('action', 'dwm_get_notifications');
		data.append('nonce', dwmAdminVars?.nonce || '');

		fetch(ajaxurl, {
			method: 'POST',
			body: data,
		})
			.then((response) => response.json())
			.then((data) => {
				if (data.success && data.data.notifications) {
					this.renderNotifications(data.data.notifications);
					this.updateButtonBadge(data.data.count);
					this.updateHeaderCount(data.data.count);
				}
			})
			.catch((error) => {
				console.error('Error loading notifications:', error);
			})
			.finally(() => {
				this.isLoading = false;
				this.hideLoadingState();
			});
	},

	/**
	 * Show loading state
	 */
	showLoadingState() {
		if (this.loadingElement) {
			this.loadingElement.style.display = 'flex';
		}
	},

	/**
	 * Hide loading state
	 */
	hideLoadingState() {
		if (this.loadingElement) {
			this.loadingElement.style.display = 'none';
		}
	},

	/**
	 * Render notifications in the panel
	 */
	renderNotifications(notifications) {
		if (!this.listElement) return;

		if (notifications.length === 0) {
			this.showEmptyState();
			return;
		}

		this.hideEmptyState();

		this.listElement.innerHTML = '';
		this.listElement.style.display = 'block';

		notifications.forEach((notification) => {
			const item = this.createNotificationElement(notification);
			this.listElement.appendChild(item);
		});

		this.bindDeleteButtons();
	},

	/**
	 * Create a notification element
	 */
	createNotificationElement(notification) {
		const li = document.createElement('li');
		li.dataset.notificationId   = notification.id;
		li.dataset.notificationType = notification.type || '';

		const isLicenseCta = notification.type === 'pro_api_key_missing';

		if (isLicenseCta) {
			li.className = 'dwm-notification-item dwm-notification-item--license-cta';
			li.innerHTML = this.createLicenseCtaHtml(notification);
		} else {
			li.className = 'dwm-notification-item';
			li.innerHTML = this.createRegularNotificationHtml(notification);
		}

		return li;
	},

	/**
	 * Create HTML for license CTA notification
	 */
	createLicenseCtaHtml(notification) {
		let actionsHtml = '';
		if (notification.actions && notification.actions.length > 0) {
			actionsHtml = '<div class="dwm-notification-license-actions">';
			notification.actions.forEach((action) => {
				let dataAttrs = '';
				if (action.scrollTo) {
					dataAttrs += ' data-scroll-to="' + this.escapeHtml(action.scrollTo) + '"';
				}
				if (action.focusField) {
					dataAttrs += ' data-focus-field="' + this.escapeHtml(action.focusField) + '"';
				}
				actionsHtml += '<a href="' + this.escapeHtml(action.url) + '" class="dwm-button dwm-pro-upgrade-button dwm-pro-upgrade-button--primary dwm-button-primary dwm-add-api-key-button"' + dataAttrs + '>' +
					'<span class="dashicons dashicons-unlock"></span>' +
					'<span class="dwm-pro-upgrade-button__label">' + this.escapeHtml(action.label) + '</span>' +
					'</a>';
			});
			actionsHtml += '</div>';
		}

		return '<div class="dwm-notification-license-glow"></div>' +
			'<div class="dwm-notification-license-header">' +
			'<div class="dwm-notification-license-icon">' +
			'<span class="dashicons dashicons-star-filled dwm-animate-slow"></span>' +
			'</div>' +
			'<div class="dwm-notification-license-text">' +
			'<h4 class="dwm-notification-license-title">' + this.escapeHtml(notification.title) + '</h4>' +
			'<span class="dwm-pro-badge dwm-pro-badge-inline">PRO</span>' +
			'</div>' +
			'</div>' +
			'<p class="dwm-notification-license-message">' + this.escapeHtml(notification.message) + '</p>' +
			actionsHtml;
	},

	/**
	 * Create HTML for a regular notification
	 */
	createRegularNotificationHtml(notification) {
		let actionsHtml = '';
		if (notification.actions && notification.actions.length > 0) {
			actionsHtml = '<div class="dwm-notification-actions">';
			notification.actions.forEach((action) => {
				let actionClass = 'dwm-button sm outline';
				if (action.class) {
					actionClass += ' ' + this.escapeHtml(action.class);
				}
				let dataAttrs = '';
				if (action.scrollTo) {
					dataAttrs += ' data-scroll-to="' + this.escapeHtml(action.scrollTo) + '"';
				}
				if (action.focusField) {
					dataAttrs += ' data-focus-field="' + this.escapeHtml(action.focusField) + '"';
				}
				actionsHtml += '<a href="' + this.escapeHtml(action.url) + '" class="' + actionClass + '"' + dataAttrs + '>' + this.escapeHtml(action.label) + '</a>';
			});
			actionsHtml += '</div>';
		}

		let deleteButtonHtml = '';
		if (notification.deletable !== false) {
			deleteButtonHtml = '<button class="dwm-notification-delete" type="button" data-notification-id="' + notification.id + '" title="Dismiss notification"><span class="dashicons dashicons-no-alt"></span></button>';
		}

		return '<div class="dwm-notification-content">' +
			'<div class="dwm-notification-icon dwm-notification-icon--' + this.escapeHtml(notification.icon) + '">' +
			'<span class="dashicons dashicons-' + this.escapeHtml(notification.icon) + '"></span>' +
			'</div>' +
			'<div class="dwm-notification-text">' +
			'<h4 class="dwm-notification-title">' + this.escapeHtml(notification.title) + '</h4>' +
			'<p class="dwm-notification-message">' + this.escapeHtml(notification.message) + '</p>' +
			actionsHtml +
			'</div>' +
			'</div>' +
			deleteButtonHtml;
	},

	/**
	 * Show empty state
	 */
	showEmptyState() {
		if (this.emptyElement) {
			this.emptyElement.style.display = 'flex';
		}
		if (this.listElement) {
			this.listElement.style.display = 'none';
		}
	},

	/**
	 * Hide empty state
	 */
	hideEmptyState() {
		if (this.emptyElement) {
			this.emptyElement.style.display = 'none';
		}
		if (this.listElement) {
			this.listElement.style.display = 'block';
		}
	},

	/**
	 * Delete a notification
	 */
	deleteNotification(notificationId) {
		const data = new FormData();
		data.append('action', 'dwm_delete_notification');
		data.append('nonce', dwmAdminVars?.nonce || '');
		data.append('notification_id', notificationId);

		fetch(ajaxurl, {
			method: 'POST',
			body: data,
		})
			.then((response) => response.json())
			.then((data) => {
				if (data.success) {
					const item = this.panelElement.querySelector('[data-notification-id="' + notificationId + '"]');
					if (item) {
						item.classList.add('removing');
						setTimeout(() => {
							item.remove();

							const listItems = this.listElement ? this.listElement.querySelectorAll('li') : [];
							if (listItems.length === 0) {
								this.showEmptyState();
							}
						}, 300);
					}

					this.updateButtonBadge(data.data.count);
					this.updateHeaderCount(data.data.count);
				}
			})
			.catch((error) => {
				console.error('Error deleting notification:', error);
			});
	},

	/**
	 * Update notification count badge on button
	 */
	updateButtonBadge(count) {
		if (!this.buttonElement) return;

		let badge = this.buttonElement.querySelector('.dwm-notification-badge');

		if (count > 0) {
			if (!badge) {
				badge = document.createElement('span');
				badge.className = 'dwm-notification-badge';
				this.buttonElement.appendChild(badge);
			}
		} else {
			if (badge) {
				badge.remove();
			}
		}
	},

	/**
	 * Update count in panel header
	 */
	updateHeaderCount(count) {
		if (!this.panelElement) return;

		let countElement = this.panelElement.querySelector('.dwm-notifications-count');

		if (count > 0) {
			if (!countElement) {
				const title = this.panelElement.querySelector('.dwm-notifications-title');
				if (title) {
					countElement = document.createElement('span');
					countElement.className = 'dwm-notifications-count';
					title.appendChild(countElement);
				}
			}
			if (countElement) {
				countElement.textContent = count;
			}
		} else {
			if (countElement) {
				countElement.remove();
			}
		}
	},

	/**
	 * Update notification count via AJAX
	 */
	updateNotificationCount() {
		const data = new FormData();
		data.append('action', 'dwm_get_notification_count');
		data.append('nonce', dwmAdminVars?.nonce || '');

		fetch(ajaxurl, {
			method: 'POST',
			body: data,
		})
			.then((response) => response.json())
			.then((data) => {
				if (data.success) {
					this.updateButtonBadge(data.data.count);
				}
			})
			.catch((error) => {
				console.error('Error updating notification count:', error);
			});
	},

	/**
	 * Escape HTML string
	 */
	escapeHtml(text) {
		if (!text) return '';
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;',
		};
		return String(text).replace(/[&<>"']/g, (char) => map[char]);
	},
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => {
		DWMNotificationsManager.init();
	});
} else {
	DWMNotificationsManager.init();
}

window.DWMNotificationsManager = DWMNotificationsManager;

export default DWMNotificationsManager;
