/**
 * Dashboard Widget Manager - Dialog Utility Module
 *
 * Provides a reusable, accessible confirm dialog that replaces native browser
 * confirm() and alert() calls with styled in-page modals.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const MODAL_ID = 'dwm-dialog-modal';

function ensureModal() {
	if ( document.getElementById( MODAL_ID ) ) {
		return;
	}

	const html = `
<div id="${MODAL_ID}" class="dwm-modal dwm-modal-sm">
	<div class="dwm-modal-overlay" id="dwm-dialog-overlay"></div>
	<div class="dwm-modal-content">
		<div class="dwm-modal-header">
			<h2>
				<span class="dashicons" id="dwm-dialog-icon"></span>
				<span id="dwm-dialog-title"></span>
			</h2>
			<button type="button" class="dwm-modal-close" id="dwm-dialog-close" aria-label="Close modal">&times;</button>
		</div>
		<div class="dwm-modal-body">
			<p id="dwm-dialog-message"></p>
		</div>
		<div class="dwm-modal-footer">
			<button type="button" id="dwm-dialog-cancel" class="dwm-button dwm-button-secondary">Cancel</button>
			<button type="button" id="dwm-dialog-confirm" class="dwm-button dwm-button-danger">Confirm</button>
		</div>
	</div>
</div>`;

	document.body.insertAdjacentHTML( 'beforeend', html );
}

function close() {
	const $modal = jQuery( '#' + MODAL_ID );
	$modal.removeClass( 'active' );

	if ( jQuery( '.dwm-modal.active' ).length === 0 ) {
		jQuery( 'body' ).removeClass( 'dwm-modal-open' );
	}

	// Unbind one-time listeners
	jQuery( '#dwm-dialog-confirm, #dwm-dialog-cancel, #dwm-dialog-close, #dwm-dialog-overlay' ).off( '.dwmdialog' );
}

/**
 * Show a confirm dialog.
 *
 * @param {Object} options
 * @param {string} options.title        Modal title.
 * @param {string} options.message      Body message.
 * @param {string} [options.icon]       Dashicons class suffix (default: 'warning').
 * @param {string} [options.confirmText] Confirm button label (default: 'Confirm').
 * @param {string} [options.confirmClass] Button class (default: 'dwm-button-danger').
 * @param {Function} options.onConfirm  Called when user confirms.
 * @param {Function} [options.onCancel] Called when user cancels (optional).
 */
export function dwmConfirm( {
	title,
	message,
	icon         = 'warning',
	confirmText  = 'Confirm',
	confirmClass = 'dwm-button-danger',
	onConfirm,
	onCancel     = null,
} ) {
	ensureModal();

	const $ = jQuery;

	$( '#dwm-dialog-title' ).text( title );
	$( '#dwm-dialog-message' ).text( message );
	$( '#dwm-dialog-icon' ).attr( 'class', 'dashicons dashicons-' + icon );
	$( '#dwm-dialog-confirm' )
		.text( confirmText )
		.attr( 'class', 'dwm-button ' + confirmClass );

	// Show modal
	$( '#' + MODAL_ID ).addClass( 'active' );
	$( 'body' ).addClass( 'dwm-modal-open' );

	// One-time event bindings
	$( '#dwm-dialog-confirm' ).one( 'click.dwmdialog', function() {
		close();
		if ( typeof onConfirm === 'function' ) {
			onConfirm();
		}
	} );

	function cancel() {
		close();
		if ( typeof onCancel === 'function' ) {
			onCancel();
		}
	}

	$( '#dwm-dialog-cancel' ).one( 'click.dwmdialog', cancel );
	$( '#dwm-dialog-close' ).one( 'click.dwmdialog', cancel );
	$( '#dwm-dialog-overlay' ).one( 'click.dwmdialog', cancel );
}
