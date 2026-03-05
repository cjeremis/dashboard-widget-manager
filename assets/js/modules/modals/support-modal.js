/**
 * Dashboard Widget Manager - Support Modal Module
 *
 * Handles the new support ticket form submission.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

'use strict';

const DWMSupportModal = {

	/**
	 * Initialize
	 */
	init() {
		const form = document.getElementById('dwm-support-ticket-form');
		if (!form) return;

		this.bindFormSubmit(form);
	},

	/**
	 * Bind form submission
	 */
	bindFormSubmit(form) {
		form.addEventListener('submit', (e) => {
			e.preventDefault();
			this.submitTicket(form);
		});
	},

	/**
	 * Submit support ticket via AJAX
	 */
	submitTicket(form) {
		const submitBtn = document.getElementById('dwm-submit-ticket-btn');
		const messageEl = document.getElementById('dwm-ticket-form-message');

		if (submitBtn && submitBtn.classList.contains('is-loading')) return;

		const nonce    = form.querySelector('[name="dwm_support_nonce"]');
		const subject  = form.querySelector('[name="subject"]');
		const priority = form.querySelector('[name="priority"]');
		const description = form.querySelector('[name="description"]');
		const dataConsent = form.querySelector('[name="support_data_consent"]');

		if (!nonce || !subject || !description || !dataConsent) return;

		this.setLoading(submitBtn, true);
		this.hideMessage(messageEl);

		const data = new FormData();
		data.append('action', 'dwm_submit_ticket');
		data.append('nonce', nonce.value);
		data.append('subject', subject.value);
		data.append('priority', priority ? priority.value : 'normal');
		data.append('description', description.value);
		data.append('support_data_consent', dataConsent.checked ? '1' : '0');

		fetch(ajaxurl, {
			method: 'POST',
			body: data,
		})
			.then((response) => response.json())
			.then((response) => {
				if (response.success) {
					const ticketNum = response.data?.ticket_number || '';
					const msg = ticketNum
						? 'Ticket ' + ticketNum + ' submitted successfully. We\'ll respond via email.'
						: 'Support ticket submitted successfully.';
					this.showMessage(messageEl, msg, 'success');
					form.reset();
				} else {
					const msg = response.data?.message || 'Failed to submit ticket. Please try again.';
					this.showMessage(messageEl, msg, 'error');
				}
			})
			.catch(() => {
				this.showMessage(messageEl, 'Failed to submit ticket. Please check your connection and try again.', 'error');
			})
			.finally(() => {
				this.setLoading(submitBtn, false);
			});
	},

	/**
	 * Toggle loading state on submit button
	 */
	setLoading(btn, loading) {
		if (!btn) return;
		if (loading) {
			btn.classList.add('is-loading');
			btn.disabled = true;
			btn.dataset.originalText = btn.textContent;
			btn.textContent = 'Submitting...';
		} else {
			btn.classList.remove('is-loading');
			btn.disabled = false;
			btn.textContent = btn.dataset.originalText || 'Submit Ticket';
		}
	},

	/**
	 * Show form message
	 */
	showMessage(el, message, type) {
		if (!el) return;
		el.textContent = message;
		el.className   = 'dwm-message dwm-message--' + type;
		el.style.display = 'block';
	},

	/**
	 * Hide form message
	 */
	hideMessage(el) {
		if (!el) return;
		el.style.display = 'none';
	},
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => {
		DWMSupportModal.init();
	});
} else {
	DWMSupportModal.init();
}

window.DWMSupportModal = DWMSupportModal;

export default DWMSupportModal;
