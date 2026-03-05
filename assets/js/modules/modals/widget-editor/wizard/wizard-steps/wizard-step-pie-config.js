/**
 * Dashboard Widget Manager - Wizard Step 9 (Pie): Data Mapping
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

let stepState = {
	labelColumn: '',
	dataColumn: '',
	title: '',
	showLegend: true,
	availableColumns: [],
	numericColumns: []
};

export function init() {
	$( document ).on( 'change', '#dwm-wizard-pie-label-column', function() {
		stepState.labelColumn = $( this ).val() || '';
		$( '#dwm-wizard-pie-config-error' ).hide().text( '' );
	} );
	$( document ).on( 'change', 'input[name="dwm_wizard_pie_data_column"]', function() {
		stepState.dataColumn = $( this ).val() || '';
		$( '#dwm-wizard-pie-config-error' ).hide().text( '' );
	} );
	$( document ).on( 'input', '#dwm-wizard-pie-chart-title', function() {
		stepState.title = $( this ).val() || '';
	} );
	$( document ).on( 'change', '#dwm-wizard-pie-show-legend', function() {
		stepState.showLegend = $( this ).is( ':checked' );
	} );
}

export function populateConfig( stepConfig, existingConfig ) {
	const availableColumns = ( stepConfig && stepConfig.availableColumns ) || [];
	const selectedColumns = ( stepConfig && stepConfig.columns ) || [];
	const columnsByName = {};
	availableColumns.forEach( function( col ) {
		const name = normalizeColumnName( col.name || col );
		if ( name ) columnsByName[ name ] = col;
	} );

	stepState.availableColumns = selectedColumns
		.map( function( columnName ) {
			const normalized = normalizeColumnName( columnName );
			return columnsByName[ normalized ] || { name: normalized, type: '' };
		} )
		.filter( function( col ) { return !! normalizeColumnName( col.name || col ); } );

	stepState.numericColumns = stepState.availableColumns.filter( function( col ) {
		return isNumericColumnType( col.type || '' );
	} );

	stepState.labelColumn = existingConfig && existingConfig.labelColumn ? existingConfig.labelColumn : '';
	stepState.dataColumn = existingConfig && existingConfig.dataColumns && existingConfig.dataColumns[ 0 ] ? existingConfig.dataColumns[ 0 ] : '';
	stepState.title = existingConfig && existingConfig.title ? existingConfig.title : '';
	stepState.showLegend = existingConfig && existingConfig.showLegend !== undefined ? !! existingConfig.showLegend : true;

	if ( ! stepState.labelColumn && stepState.availableColumns.length > 0 ) {
		stepState.labelColumn = normalizeColumnName( stepState.availableColumns[ 0 ].name || '' );
	}

	const validNumericNames = stepState.numericColumns.map( function( col ) { return normalizeColumnName( col.name || col ); } );
	if ( validNumericNames.indexOf( normalizeColumnName( stepState.dataColumn ) ) === -1 ) {
		stepState.dataColumn = validNumericNames[ 0 ] || '';
	}

	renderConfigUi();
}

export function validateStep() {
	const $error = $( '#dwm-wizard-pie-config-error' );
	$error.hide().text( '' );
	if ( ! stepState.labelColumn ) {
		$error.text( 'Select a label column before continuing.' ).show();
		return false;
	}
	if ( ! stepState.dataColumn ) {
		$error.text( 'Select one numeric value column before continuing.' ).show();
		return false;
	}
	return true;
}

export function getStepConfig() {
	return {
		labelColumn: stepState.labelColumn,
		dataColumns: stepState.dataColumn ? [ stepState.dataColumn ] : [],
		title: stepState.title,
		showLegend: !! stepState.showLegend
	};
}

export function clearStep() {
	stepState = { labelColumn: '', dataColumn: '', title: '', showLegend: true, availableColumns: [], numericColumns: [] };
	$( '#dwm-wizard-pie-label-column' ).empty();
	$( '#dwm-wizard-pie-data-columns-list' ).empty();
	$( '#dwm-wizard-pie-chart-title' ).val( '' );
	$( '#dwm-wizard-pie-show-legend' ).prop( 'checked', true );
	$( '#dwm-wizard-pie-config-error' ).hide().text( '' );
}

function renderConfigUi() {
	const $labelSelect = $( '#dwm-wizard-pie-label-column' );
	const $dataList = $( '#dwm-wizard-pie-data-columns-list' );
	$labelSelect.empty();
	$dataList.empty();

	$labelSelect.append( '<option value="">— select label column —</option>' );
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
		const checked = stepState.dataColumn === normalized ? ' checked' : '';
		const id = 'dwm-wizard-pie-data-column-' + index;
		$dataList.append(
			'<label class="dwm-wizard-col-item" for="' + id + '">' +
				'<input type="radio" id="' + id + '" name="dwm_wizard_pie_data_column" value="' + escapeHtml( normalized ) + '"' + checked + '>' +
				'<span class="dwm-wizard-col-name">' + escapeHtml( normalized ) + '</span>' +
			'</label>'
		);
	} );

	$( '#dwm-wizard-pie-chart-title' ).val( stepState.title || '' );
	$( '#dwm-wizard-pie-show-legend' ).prop( 'checked', !! stepState.showLegend );
}

function isNumericColumnType( columnType ) {
	if ( ! columnType ) return false;
	const type = String( columnType ).toLowerCase();
	const numericTypes = [ 'int', 'bigint', 'tinyint', 'smallint', 'mediumint', 'integer', 'decimal', 'float', 'double', 'real', 'numeric' ];
	return numericTypes.some( function( token ) { return type.indexOf( token ) !== -1; } );
}

function normalizeColumnName( columnName ) {
	const value = String( columnName || '' );
	if ( value.indexOf( '.' ) === -1 ) return value;
	const parts = value.split( '.' );
	return parts[ parts.length - 1 ];
}

function escapeHtml( text ) {
	const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
	return String( text ).replace( /[&<>"']/g, function( char ) { return map[ char ]; } );
}
