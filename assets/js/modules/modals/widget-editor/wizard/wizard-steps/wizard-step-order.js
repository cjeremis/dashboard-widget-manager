/**
 * Dashboard Widget Manager - Wizard Step: Order By
 *
 * Handles ORDER BY configuration with modal-based interface matching the
 * Add Filter step pattern.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

let stepState = {
	orders: []
};

export function init() {
	// This will be called from wizard.js to set up event handlers
}

export function renderStep( $container, wizardState ) {
	stepState.orders = wizardState.data.orders || [];
	renderOrdersList();
}

export function validateStep() {
	// Order by is optional, always valid
	return true;
}

export function collectData() {
	return {
		orders: [ ...stepState.orders ]
	};
}

export function clearStep() {
	stepState.orders = [];
}

export function getStepState() {
	return stepState;
}

export function setStepState( newState ) {
	stepState = newState;
}

export function renderOrdersList() {
	const $list = $( '#dwm-wizard-orders-list' ).empty();

	stepState.orders.forEach( function( order, i ) {
		const directionDisplay = order.direction === 'ASC' ? 'Oldest First (ASC)' : 'Newest First (DESC)';

		$list.append(
			'<div class="dwm-wizard-filter-summary" data-index="' + i + '">' +
				'<div class="dwm-filter-field">' +
					'<span class="dwm-filter-label">Column</span>' +
					'<span class="dwm-filter-value">' + escapeHtml( order.column ) + '</span>' +
				'</div>' +
				'<div class="dwm-filter-field">' +
					'<span class="dwm-filter-label">Direction</span>' +
					'<span class="dwm-filter-value">' + escapeHtml( directionDisplay ) + '</span>' +
				'</div>' +
				'<div class="dwm-wizard-filter-actions">' +
					'<button type="button" class="dwm-icon-button dwm-wizard-edit-order" data-index="' + i + '" title="Edit order">' +
						'<span class="dashicons dashicons-edit"></span>' +
					'</button>' +
					'<button type="button" class="dwm-icon-button dwm-icon-button-danger dwm-wizard-remove-order" data-index="' + i + '" title="Remove order">' +
						'<span class="dashicons dashicons-trash"></span>' +
					'</button>' +
				'</div>' +
			'</div>'
		);
	});
}


function escapeHtml( text ) {
	const map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return String( text ).replace( /[&<>"']/g, function( m ) { return map[ m ]; } );
}
