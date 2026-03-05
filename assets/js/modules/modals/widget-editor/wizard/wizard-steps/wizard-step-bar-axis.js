/**
 * Dashboard Widget Manager - Wizard Step 9 (Bar): Axis Mapping
 *
 * Handles bar chart axis and dataset mapping.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

let stepState = {
	labelColumn: '',
	dataColumns: [],
	title: '',
	showLegend: true,
	availableColumns: [],
	numericColumns: []
};

export function init() {
	$( document ).on( 'change', '#dwm-wizard-bar-label-column', function() {
		stepState.labelColumn = $( this ).val() || '';
		$( '#dwm-wizard-bar-axis-error' ).hide().text( '' );
	} );

	$( document ).on( 'change', '.dwm-wizard-bar-data-column', function() {
		const selected = [];
		$( '.dwm-wizard-bar-data-column:checked' ).each( function() {
			selected.push( $( this ).val() );
		} );
		stepState.dataColumns = selected;
		$( '#dwm-wizard-bar-axis-error' ).hide().text( '' );
	} );

	$( document ).on( 'input', '#dwm-wizard-bar-chart-title', function() {
		stepState.title = $( this ).val() || '';
	} );

	$( document ).on( 'change', '#dwm-wizard-bar-show-legend', function() {
		stepState.showLegend = $( this ).is( ':checked' );
	} );
}

export function populateAxes( stepConfig, existingConfig ) {
	const availableColumns = ( stepConfig && stepConfig.availableColumns ) || [];
	const selectedColumns = ( stepConfig && stepConfig.columns ) || [];

	const columnsByName = {};
	availableColumns.forEach( function( col ) {
		const name = normalizeColumnName( col.name || col );
		if ( name ) {
			columnsByName[ name ] = col;
		}
	} );

	stepState.availableColumns = selectedColumns
		.map( function( columnName ) {
			const normalized = normalizeColumnName( columnName );
			return columnsByName[ normalized ] || { name: normalized, type: '' };
		} )
		.filter( function( col ) {
			return !! normalizeColumnName( col.name || col );
		} );

	stepState.numericColumns = stepState.availableColumns.filter( function( col ) {
		return isNumericColumnType( col.type || '' );
	} );

	stepState.labelColumn = existingConfig && existingConfig.labelColumn ? existingConfig.labelColumn : '';
	stepState.dataColumns = existingConfig && Array.isArray( existingConfig.dataColumns ) ? [ ...existingConfig.dataColumns ] : [];
	stepState.title = existingConfig && existingConfig.title ? existingConfig.title : '';
	stepState.showLegend = existingConfig && existingConfig.showLegend !== undefined ? !! existingConfig.showLegend : true;

	if ( ! stepState.labelColumn && stepState.availableColumns.length > 0 ) {
		stepState.labelColumn = normalizeColumnName( stepState.availableColumns[ 0 ].name || '' );
	}

	const validNumericNames = stepState.numericColumns.map( function( col ) {
		return normalizeColumnName( col.name || col );
	} );
	stepState.dataColumns = stepState.dataColumns.filter( function( name ) {
		return validNumericNames.indexOf( normalizeColumnName( name ) ) !== -1;
	} );

	if ( stepState.dataColumns.length === 0 && validNumericNames.length > 0 ) {
		stepState.dataColumns = [ validNumericNames[ 0 ] ];
	}

	renderAxisUi();
}

export function validateStep() {
	const $error = $( '#dwm-wizard-bar-axis-error' );
	$error.hide().text( '' );

	if ( ! stepState.labelColumn ) {
		$error.text( 'Select an X-axis label column before continuing.' ).show();
		return false;
	}

	if ( stepState.numericColumns.length === 0 ) {
		$error.text( 'No numeric columns are available. Go back and include at least one numeric column for the bar values.' ).show();
		return false;
	}

	if ( ! stepState.dataColumns || stepState.dataColumns.length === 0 ) {
		$error.text( 'Select at least one Y-axis data column before continuing.' ).show();
		return false;
	}

	return true;
}

export function getStepConfig() {
	return {
		labelColumn: stepState.labelColumn,
		dataColumns: [ ...stepState.dataColumns ],
		title: stepState.title,
		showLegend: !! stepState.showLegend
	};
}

export function clearStep() {
	stepState = {
		labelColumn: '',
		dataColumns: [],
		title: '',
		showLegend: true,
		availableColumns: [],
		numericColumns: []
	};

	$( '#dwm-wizard-bar-label-column' ).empty();
	$( '#dwm-wizard-bar-data-columns-list' ).empty();
	$( '#dwm-wizard-bar-chart-title' ).val( '' );
	$( '#dwm-wizard-bar-show-legend' ).prop( 'checked', true );
	$( '#dwm-wizard-bar-axis-error' ).hide().text( '' );
}

function renderAxisUi() {
	const $labelSelect = $( '#dwm-wizard-bar-label-column' );
	const $dataList = $( '#dwm-wizard-bar-data-columns-list' );
	const $error = $( '#dwm-wizard-bar-axis-error' );

	$labelSelect.empty();
	$dataList.empty();
	$error.hide().text( '' );

	if ( stepState.availableColumns.length === 0 ) {
		$labelSelect.append( '<option value="">No columns available</option>' );
		$dataList.html( '<p class="dwm-no-columns-message">No columns were selected. Go back and choose columns for your chart.</p>' );
		return;
	}

	$labelSelect.append( '<option value="">— select X-axis column —</option>' );
	stepState.availableColumns.forEach( function( col ) {
		const normalized = normalizeColumnName( col.name || col );
		const selected = stepState.labelColumn === normalized ? ' selected' : '';
		$labelSelect.append( '<option value="' + escapeHtml( normalized ) + '"' + selected + '>' + escapeHtml( normalized ) + '</option>' );
	} );

	if ( stepState.numericColumns.length === 0 ) {
		$dataList.html( '<p class="dwm-no-columns-message">No numeric columns found in your current selection.</p>' );
		return;
	}

	stepState.numericColumns.forEach( function( col, index ) {
		const normalized = normalizeColumnName( col.name || col );
		const checked = stepState.dataColumns.indexOf( normalized ) !== -1 ? ' checked' : '';
		const id = 'dwm-wizard-bar-data-column-' + index;

		$dataList.append(
			'<label class="dwm-wizard-col-item" for="' + id + '">' +
				'<input type="checkbox" id="' + id + '" class="dwm-wizard-bar-data-column" value="' + escapeHtml( normalized ) + '"' + checked + '>' +
				'<span class="dwm-wizard-col-name">' + escapeHtml( normalized ) + '</span>' +
			'</label>'
		);
	} );

	$( '#dwm-wizard-bar-chart-title' ).val( stepState.title || '' );
	$( '#dwm-wizard-bar-show-legend' ).prop( 'checked', !! stepState.showLegend );
}

function isNumericColumnType( columnType ) {
	if ( ! columnType ) {
		return false;
	}

	const type = String( columnType ).toLowerCase();
	const numericTypes = [ 'int', 'bigint', 'tinyint', 'smallint', 'mediumint', 'integer', 'decimal', 'float', 'double', 'real', 'numeric' ];
	return numericTypes.some( function( token ) {
		return type.indexOf( token ) !== -1;
	} );
}

function normalizeColumnName( columnName ) {
	const value = String( columnName || '' );
	if ( value.indexOf( '.' ) === -1 ) {
		return value;
	}
	const parts = value.split( '.' );
	return parts[ parts.length - 1 ];
}

function escapeHtml( text ) {
	const map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return String( text ).replace( /[&<>"']/g, function( char ) {
		return map[ char ];
	} );
}
