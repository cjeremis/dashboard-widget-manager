/**
 * Dashboard Widget Manager - Toast Utility Module
 *
 * Provides toast notification support across all admin pages.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

(function(window, $) {
  'use strict';

  class DWMToast {
    constructor() {
      this.container = null;
      this.toasts = [];
      this.currentPosition = 'top-right';
      this.init();
    }

    init() {
      if (!$('.dwm-toast-container').length) {
        this.container = $('<div class="dwm-toast-container dwm-toast-position-top-right"></div>');
        $('body').append(this.container);
      } else {
        this.container = $('.dwm-toast-container');
      }
    }

    updateContainerPosition(position) {
      if (this.currentPosition !== position) {
        this.container.removeClass(`dwm-toast-position-${this.currentPosition}`);
        this.container.addClass(`dwm-toast-position-${position}`);
        this.currentPosition = position;
      }
    }

    show(message, type = 'info', options = {}) {
      const defaults = {
        title: '',
        duration: 5000,
        progress: true,
        manualDismiss: false,
        position: 'top-right',
        width: 'default',
        onClose: null,
      };

      const settings = $.extend({}, defaults, options);

      if (settings.manualDismiss) {
        settings.duration = 0;
        settings.progress = false;
      }

      this.updateContainerPosition(settings.position);

      const icons = {
        success: 'dashicons-yes-alt',
        error:   'dashicons-dismiss',
        warning: 'dashicons-warning',
        info:    'dashicons-info',
      };

      const icon = icons[type] || icons.info;

      let widthClass = '';
      if (settings.width === 'auto') widthClass = ' dwm-toast-width-auto';
      else if (settings.width === 'full') widthClass = ' dwm-toast-width-full';

      const $toast = $(`
        <div class="dwm-toast dwm-toast-${type}${widthClass}" role="alert" aria-live="polite">
          <div class="dwm-toast-icon">
            <span class="dashicons ${icon}"></span>
          </div>
          <div class="dwm-toast-content">
            ${settings.title ? `<div class="dwm-toast-title">${this.escapeHtml(settings.title)}</div>` : ''}
            <div class="dwm-toast-message">${this.escapeHtml(message)}</div>
          </div>
          ${settings.manualDismiss ? '<button type="button" class="dwm-toast-close" aria-label="Close notification">&times;</button>' : ''}
          ${settings.progress && settings.duration > 0 ? '<div class="dwm-toast-progress"><div class="dwm-toast-progress-bar"></div></div>' : ''}
        </div>
      `);

      this.container.append($toast);
      this.toasts.push($toast);

      setTimeout(() => $toast.addClass('dwm-toast-show'), 10);

      if (settings.manualDismiss) {
        $toast.find('.dwm-toast-close').on('click', () => this.close($toast, settings.onClose));
      }

      if (settings.duration > 0) {
        if (settings.progress) {
          const $bar = $toast.find('.dwm-toast-progress-bar');
          $bar.css({ width: '100%', transition: `width ${settings.duration}ms linear` });
          setTimeout(() => $bar.css('width', '0%'), 10);
        }
        setTimeout(() => this.close($toast, settings.onClose), settings.duration);
      }

      return $toast;
    }

    success(message, options = {}) { return this.show(message, 'success', options); }
    error(message, options = {})   { return this.show(message, 'error', $.extend({ duration: 7000 }, options)); }
    warning(message, options = {}) { return this.show(message, 'warning', $.extend({ duration: 6000 }, options)); }
    info(message, options = {})    { return this.show(message, 'info', options); }

    close($toast, onCloseCallback = null) {
      $toast.removeClass('dwm-toast-show').addClass('dwm-toast-hide');
      setTimeout(() => {
        $toast.remove();
        const i = this.toasts.indexOf($toast);
        if (i > -1) this.toasts.splice(i, 1);
        if (typeof onCloseCallback === 'function') onCloseCallback();
      }, 300);
    }

    closeAll() {
      this.toasts.forEach($toast => this.close($toast));
    }

    escapeHtml(text) {
      const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
      return String(text).replace(/[&<>"']/g, m => map[m]);
    }
  }

  window.DWMToast = new DWMToast();

})(window, jQuery);

export default {};
