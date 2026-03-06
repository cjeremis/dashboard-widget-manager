/**
 * Dashboard Widget Manager - Dashboard Entry Point
 *
 * Entry point for the Dashboard admin page.
 * Initializes widget editor and related dashboard functionality.
 *
 * Output: /assets/js/minimized/admin/dashboard.min.js
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import '../modules/utilities/column-validator.js';
import '../modules/utilities/join-validator.js';
import { initWidgetEditor } from '../modules/modals/widget-editor/widget-editor.js';
import { initPreview } from '../modules/modals/preview-modal.js';
import '../modules/forms/widget-filters.js';
import '../modules/modals/status-change-modal.js';

/**
 * Auto-open widget editor for a specific widget via ?edit={widget-id} URL param.
 * Runs after initWidgetEditor() so event handlers are fully bound.
 */
function handleEditParam() {
	var urlParams = new URLSearchParams( window.location.search );
	var editId    = urlParams.get( 'edit' );
	if ( ! editId ) return;

	setTimeout( function() {
		var $btn = jQuery( '.dwm-edit-widget[data-widget-id="' + editId + '"]' );
		if ( $btn.length ) {
			$btn.trigger( 'click' );
		}
	}, 200 );
}

/**
 * Auto-open create new widget modal via ?action=create URL param.
 * Runs after initWidgetEditor() so event handlers are fully bound.
 */
function handleCreateParam() {
	var urlParams = new URLSearchParams( window.location.search );
	if ( urlParams.get( 'action' ) !== 'create' ) return;

	setTimeout( function() {
		var $btn = jQuery( '.dwm-create-widget' );
		if ( $btn.length ) {
			$btn.trigger( 'click' );
		}
	}, 200 );
}

jQuery(document).ready(function() {
	initWidgetEditor();
	initPreview();
	handleEditParam();
	handleCreateParam();
});
