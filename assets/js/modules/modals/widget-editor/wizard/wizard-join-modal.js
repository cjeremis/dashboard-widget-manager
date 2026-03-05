/**
 * Dashboard Widget Manager - Wizard Join Modal Module
 *
 * Join configuration modal logic for the creation wizard and widget editor.
 * Handles opening/closing the join modal, compatibility checks, column
 * population, validation, query preview, and saving join configs.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

import {
	wizardState,
	getEditorJoinContext,
	setEditorJoinContext,
	getJoinContextPrimaryTable,
	getJoinContextPrimaryColumns,
	getJoinContextSelectedColumns,
	getJoinContextExistingJoins
} from './wizard-state.js';

import {
	escAttr,
	areTypesCompatible,
	buildCompatibilityMap,
	hasAnyCompatibleColumns,
	getCachedColumnsForTable,
	cacheColumnsForTable,
	getAvailableTablesForJoin
} from './wizard-utils.js';

import { ajax } from '../../../partials/ajax.js';
import * as columnValidator from '../../../utilities/column-validator.js';
import * as joinValidator from '../../../utilities/join-validator.js';

const $ = jQuery;

/**
 * Render wizard join rows from state (read-only summaries)
 */
export function renderWizardJoins() {
	const joins = wizardState.data.joins || [];
	const $list = $( '#dwm-wizard-joins-list' ).empty();

	joins.forEach( function( join, i ) {
		// Format join type for display
		let joinTypeDisplay = join.type;
		if ( join.type === 'LEFT' ) joinTypeDisplay = 'Left Join';
		else if ( join.type === 'RIGHT' ) joinTypeDisplay = 'Right Join';
		else if ( join.type === 'INNER' ) joinTypeDisplay = 'Inner Join';

		$list.append(
			'<div class="dwm-wizard-join-summary" data-index="' + i + '">' +
				'<div class="dwm-join-field half-width">' +
					'<span class="dwm-join-label">Type</span>' +
					'<span class="dwm-join-value">' + escAttr( joinTypeDisplay ) + '</span>' +
				'</div>' +
				'<div class="dwm-join-field">' +
					'<span class="dwm-join-label">Table</span>' +
					'<span class="dwm-join-value">' + escAttr( join.table ) + '</span>' +
				'</div>' +
				'<div class="dwm-join-on-label">ON</div>' +
				'<div class="dwm-join-field">' +
					'<span class="dwm-join-label">Primary Column</span>' +
					'<span class="dwm-join-value">' + escAttr( join.local_col ) + '</span>' +
				'</div>' +
				'<div class="dwm-join-equals-label">=</div>' +
				'<div class="dwm-join-field">' +
					'<span class="dwm-join-label">Join Column</span>' +
					'<span class="dwm-join-value">' + escAttr( join.foreign_col ) + '</span>' +
				'</div>' +
				'<div class="dwm-wizard-join-actions">' +
					'<button type="button" class="dwm-icon-button dwm-wizard-edit-join" data-index="' + i + '" title="Edit join">' +
						'<span class="dashicons dashicons-edit"></span>' +
					'</button>' +
					'<button type="button" class="dwm-icon-button dwm-icon-button-danger dwm-wizard-remove-join" data-index="' + i + '" title="Remove join">' +
						'<span class="dashicons dashicons-trash"></span>' +
					'</button>' +
				'</div>' +
			'</div>'
		);
	});

	syncJoinedColumnsIntoBuilder();
}

function syncJoinedColumnsIntoBuilder() {
	const joins = wizardState.data.joins || [];
	const $builderList = $( '#dwm-builder-columns-list' );
	if ( ! $builderList.length ) {
		return;
	}

	const selected = {};
	$builderList.find( 'input[type="checkbox"]:checked' ).each( function() {
		selected[ $( this ).val() ] = true;
	} );

	$builderList.find( '.dwm-col-checkbox[data-join-column="1"]' ).remove();

	const stepConfig = wizardState.data.stepConfig || {};
	const primaryTable = stepConfig.table || '';
	const currentAvailable = Array.isArray( stepConfig.availableColumns ) ? stepConfig.availableColumns.slice() : [];
	const availableByName = {};
	currentAvailable.forEach( function( col ) {
		const name = col.name || col;
		if ( name.indexOf( '.' ) === -1 || ( primaryTable && name.indexOf( primaryTable + '.' ) === 0 ) ) {
			availableByName[ name ] = col;
		}
	} );

	joins.forEach( function( join ) {
		const joinColumns = getCachedColumnsForTable( join.table ) || [];
		joinColumns.forEach( function( col ) {
			const colName = col.name || col;
			const fullName = join.table + '.' + colName;

			if ( ! availableByName[ fullName ] ) {
				availableByName[ fullName ] = {
					name: fullName,
					type: col.type || '',
					key: col.key || ''
				};
			}

			if ( $builderList.find( 'input[value="' + fullName + '"]' ).length ) {
				return;
			}

			const safeId = 'dwm-col-' + fullName.replace( /[^a-zA-Z0-9_-]/g, '-' );
			const badge = col.key === 'PRI' ? ' <span class="dwm-col-badge dwm-col-badge-pk">PK</span>' : '';
			const checked = selected[ fullName ] ? ' checked' : '';

			$builderList.append(
				'<label class="dwm-col-checkbox" data-join-column="1" for="' + safeId + '">' +
					'<input type="checkbox" id="' + safeId + '" value="' + fullName + '"' + checked + '>' +
					'<span class="dwm-col-name">' + escAttr( fullName ) + '</span>' +
					'<span class="dwm-col-type">' + escAttr( col.type || '' ) + '</span>' +
					badge +
				'</label>'
			);
		} );
	} );

	if ( wizardState.data.stepConfig ) {
		wizardState.data.stepConfig.availableColumns = Object.values( availableByName );
	}
}

/**
 * Open join configuration modal
 */
export function openJoinConfigModal( editIndex, editorContext ) {
	// Store the edit index in state
	wizardState.joinConfigEditIndex = typeof editIndex === 'number' ? editIndex : -1;

	// Set editor context (null when called from wizard, object when called from widget editor)
	setEditorJoinContext( editorContext || null );

	// Get primary table name and columns via context-aware helpers
	const primaryTable   = getJoinContextPrimaryTable();
	const primaryColumns = getJoinContextPrimaryColumns();

	// Reset form
	$( '#dwm-join-config-type' ).val( '' );
	$( '#dwm-join-config-table' ).val( '' ).prop( 'disabled', true );
	$( '#dwm-join-config-local' ).val( '' ).prop( 'disabled', true );
	$( '#dwm-join-config-foreign' ).val( '' ).prop( 'disabled', true );

	// Reset preview panes, tabs, and save button
	$( '.dwm-join-preview-tab' ).removeClass( 'active' );
	$( '.dwm-join-preview-tab[data-tab="query"]' ).addClass( 'active' );
	$( '.dwm-join-preview-tab[data-tab="output"]' ).prop( 'disabled', true );
	$( '.dwm-join-preview-pane' ).removeClass( 'active' );
	$( '#dwm-join-preview-query-pane' ).addClass( 'active' );
	$( '#dwm-join-validation-status' ).hide();
	$( '#dwm-join-output-preview-content' ).empty();
	$( '#dwm-join-config-save' ).prop( 'disabled', true );
	wizardState.joinConfigState.validationResults = [];

	// Load available tables
	populateJoinConfigTables();

	// Store primary table columns in join config state for compatibility checking
	wizardState.joinConfigState.primaryTableColumns = primaryColumns;

	// Populate primary column dropdown initially (will be updated when join table is selected)
	let primaryColOptions = '<option value="">— select column —</option>';
	primaryColumns.forEach( function( col ) {
		const colName = col.name || col;
		const fullName = primaryTable + '.' + colName;
		primaryColOptions += '<option value="' + escAttr( fullName ) + '">' + escAttr( colName ) + '</option>';
	});
	$( '#dwm-join-config-local' ).html( primaryColOptions );

	// Update title with primary table name
	if ( wizardState.joinConfigEditIndex >= 0 ) {
		$( '#dwm-join-config-title' ).text( 'Edit Join with ' + primaryTable );
	} else {
		$( '#dwm-join-config-title' ).text( 'Configure Join with ' + primaryTable );
	}

	// If editing, populate fields
	if ( wizardState.joinConfigEditIndex >= 0 ) {
		const join = getJoinContextExistingJoins()[ wizardState.joinConfigEditIndex ];
		$( '#dwm-join-config-type' ).val( join.type ).trigger( 'change' );
		$( '#dwm-join-config-table' ).prop( 'disabled', false );

		// Check if join table columns are cached, fetch if needed
		let joinTableColumns = getCachedColumnsForTable( join.table );

		if ( joinTableColumns.length > 0 ) {
			// Columns are cached, populate immediately
			$( '#dwm-join-config-table' ).val( join.table ).trigger( 'change' );
			$( '#dwm-join-config-local' ).val( join.local_col ).prop( 'disabled', false ).trigger( 'change' );
			$( '#dwm-join-config-foreign' ).val( join.foreign_col ).prop( 'disabled', false );
			updateJoinQueryPreview();
		} else {
			// Fetch columns first, then populate
			ajax(
				'dwm_get_table_columns',
				{ table: join.table },
				function( data ) {
					const cols = data.columns || [];
					cacheColumnsForTable( join.table, cols );

					// Now populate fields
					$( '#dwm-join-config-table' ).val( join.table ).trigger( 'change' );
					$( '#dwm-join-config-local' ).val( join.local_col ).prop( 'disabled', false ).trigger( 'change' );
					$( '#dwm-join-config-foreign' ).val( join.foreign_col ).prop( 'disabled', false );
					updateJoinQueryPreview();
				},
				function() {
					// On error, still try to populate
					$( '#dwm-join-config-table' ).val( join.table ).trigger( 'change' );
					$( '#dwm-join-config-local' ).val( join.local_col ).prop( 'disabled', false ).trigger( 'change' );
					$( '#dwm-join-config-foreign' ).val( join.foreign_col ).prop( 'disabled', false );
					updateJoinQueryPreview();
				}
			);
		}
	}

	// Show current query state immediately (for new joins, before any selections)
	if ( wizardState.joinConfigEditIndex < 0 ) {
		updateJoinQueryPreview();
	}

	// Show modal
	$( '#dwm-join-config-modal' ).addClass( 'active' );
}

/**
 * Close join configuration modal
 */
export function closeJoinConfigModal() {
	$( '#dwm-join-config-modal' ).removeClass( 'active' );
	wizardState.joinConfigEditIndex = -1;
	// Reset join config state
	wizardState.joinConfigState.joinTableColumns = [];
	wizardState.joinConfigState.compatibilityMap = {};
	setEditorJoinContext( null );
}

/**
 * Show validation error modal with error message and suggestions
 */
export function showValidationErrorModal( validation ) {
	const $modal = $( '#dwm-validation-error-modal' );
	const $message = $( '#dwm-validation-error-message' );
	const $suggestion = $( '#dwm-validation-error-suggestion' );
	const $suggestionText = $( '#dwm-validation-error-suggestion-text' );
	const $details = $( '#dwm-validation-error-details' );
	const $detailsText = $( '#dwm-validation-error-details-text' );

	// Set error message
	$message.text( validation.error || 'An unknown validation error occurred.' );

	// Show suggestion if available
	if ( validation.suggestion ) {
		$suggestionText.text( validation.suggestion );
		$suggestion.show();
	} else {
		$suggestion.hide();
	}

	// Show details if available
	if ( validation.details ) {
		let detailsContent = '';
		if ( validation.details.existingJoin ) {
			const existing = validation.details.existingJoin;
			detailsContent = `${existing.type} JOIN ${existing.table} ON ${existing.local_col} = ${existing.foreign_col}`;
		} else if ( validation.details.cycle ) {
			detailsContent = validation.details.cycle.join( ' → ' );
		} else {
			detailsContent = JSON.stringify( validation.details, null, 2 );
		}
		$detailsText.text( detailsContent );
		$details.show();
	} else {
		$details.hide();
	}

	// Show modal
	$modal.addClass( 'active' );
}

/**
 * Check compatibility and load join table columns
 */
export function checkJoinCompatibilityAndLoad( table ) {
	// Show loading state on primary column dropdown immediately
	$( '#dwm-join-config-local' )
		.prop( 'disabled', true )
		.html( '<option value="">Loading columns\u2026</option>' );

	// Get join table columns from cache or fetch
	let joinColumns = getCachedColumnsForTable( table );

	if ( joinColumns.length > 0 ) {
		processJoinTableCompatibility( table, joinColumns );
	} else {
		// Fetch from server
		ajax(
			'dwm_get_table_columns',
			{ table: table },
			function( data ) {
				const cols = data.columns || [];
				cacheColumnsForTable( table, cols );
				processJoinTableCompatibility( table, cols );
			},
			function() {
				$( '#dwm-join-config-local' ).html( '<option value="">Failed to load</option>' );
				$( '#dwm-join-config-foreign' ).html( '<option value="">Failed to load</option>' );
			}
		);
	}
}

/**
 * Process join table compatibility check
 */
export function processJoinTableCompatibility( table, joinColumns ) {
	const primaryColumns = wizardState.joinConfigState.primaryTableColumns;

	// Store join table columns
	wizardState.joinConfigState.joinTableColumns = joinColumns;

	// Check if any columns are compatible
	if ( ! hasAnyCompatibleColumns( primaryColumns, joinColumns ) ) {
		// No compatible columns - show warning modal
		const primaryTable = getJoinContextPrimaryTable();
		$( '#dwm-no-compatible-columns-message' ).text(
			'The table "' + table + '" has no columns that are compatible with the selected columns from "' + primaryTable + '".'
		);
		$( '#dwm-no-compatible-columns-modal' ).addClass( 'active' );
		$( '#dwm-join-config-table' ).val( '' );
		return;
	}

	// Build compatibility map
	wizardState.joinConfigState.compatibilityMap = buildCompatibilityMap( primaryColumns, joinColumns );

	// Populate primary and foreign column dropdowns with disabled states
	populatePrimaryColumnsWithDisabled();
	populateForeignColumnsWithDisabled();

	// Enable local column dropdown
	$( '#dwm-join-config-local' ).prop( 'disabled', false );
}

/**
 * Populate primary column dropdown with disabled states for incompatible columns
 */
export function populatePrimaryColumnsWithDisabled() {
	const primaryColumns = wizardState.joinConfigState.primaryTableColumns;
	const compatibilityMap = wizardState.joinConfigState.compatibilityMap;
	const primaryTable = getJoinContextPrimaryTable();

	let html = '<option value="">— select column —</option>';
	primaryColumns.forEach( function( col ) {
		const colName = col.name || col;
		const fullName = primaryTable + '.' + colName;
		const hasMatches = compatibilityMap[ colName ] && compatibilityMap[ colName ].length > 0;
		const disabled = ! hasMatches ? ' disabled' : '';
		const title = ! hasMatches ? ' title="No Possible Join Options"' : '';
		html += '<option value="' + escAttr( fullName ) + '"' + disabled + title + '>' + escAttr( colName ) + '</option>';
	});

	$( '#dwm-join-config-local' ).html( html );
}

/**
 * Populate foreign column dropdown (all columns initially)
 */
export function populateForeignColumnsWithDisabled() {
	const joinColumns = wizardState.joinConfigState.joinTableColumns;
	const joinTable = $( '#dwm-join-config-table' ).val();

	let html = '<option value="">— select column —</option>';
	joinColumns.forEach( function( col ) {
		const colName = col.name || col;
		const fullName = joinTable + '.' + colName;
		html += '<option value="' + escAttr( fullName ) + '">' + escAttr( colName ) + '</option>';
	});

	$( '#dwm-join-config-foreign' ).html( html );
}

/**
 * Filter foreign columns based on selected primary column
 */
export function filterJoinColumnsByCompatibility( localCol ) {
	const compatibilityMap = wizardState.joinConfigState.compatibilityMap;
	const joinColumns = wizardState.joinConfigState.joinTableColumns;
	const joinTable = $( '#dwm-join-config-table' ).val();

	// Extract bare column name from fully qualified name (e.g., "wp_posts.ID" -> "ID")
	const bareColName = localCol.includes( '.' ) ? localCol.split( '.' ).pop() : localCol;
	const compatibleColumns = compatibilityMap[ bareColName ] || [];

	let html = '<option value="">— select column —</option>';
	joinColumns.forEach( function( col ) {
		const colName = col.name || col;
		const fullName = joinTable + '.' + colName;
		const isCompatible = compatibleColumns.indexOf( colName ) !== -1;
		const disabled = ! isCompatible ? ' disabled' : '';
		const title = ! isCompatible ? ' title="Incompatible column type"' : '';
		html += '<option value="' + escAttr( fullName ) + '"' + disabled + title + '>' + escAttr( colName ) + '</option>';
	});

	$( '#dwm-join-config-foreign' ).html( html );
}

/**
 * Repopulate all foreign columns (when no local column is selected)
 */
export function repopulateAllForeignColumns() {
	populateForeignColumnsWithDisabled();
}

/**
 * Populate join config available tables dropdown
 */
export function populateJoinConfigTables() {
	const editIndex = wizardState.joinConfigEditIndex;
	const availableTables = getAvailableTablesForJoin( editIndex );

	let tableOptions = '<option value="">— select table —</option>';
	availableTables.forEach( function( table ) {
		tableOptions += '<option value="' + escAttr( table ) + '">' + escAttr( table ) + '</option>';
	});

	$( '#dwm-join-config-table' ).html( tableOptions );
}

/**
 * Save join configuration from modal
 */
export function saveJoinConfig() {
	const joinType = $( '#dwm-join-config-type' ).val();
	const joinTable = $( '#dwm-join-config-table' ).val();
	const localCol = $( '#dwm-join-config-local' ).val();
	const foreignCol = $( '#dwm-join-config-foreign' ).val();

	// Validate all fields
	if ( ! joinType || ! joinTable || ! localCol || ! foreignCol ) {
		window.DWMToast.warning( 'Please fill in all fields before saving.', { title: 'Join Config' } );
		return;
	}

	const joinConfig = {
		type: joinType,
		table: joinTable,
		local_col: localCol,
		foreign_col: foreignCol
	};

	// Get existing joins for validation (exclude the one being edited)
	const allExistingJoins = getJoinContextExistingJoins();
	const existingJoins = wizardState.joinConfigEditIndex >= 0
		? allExistingJoins.filter( ( _j, index ) => index !== wizardState.joinConfigEditIndex )
		: allExistingJoins;

	// Validate join configuration
	const primaryTable = getJoinContextPrimaryTable();
	const validation = joinValidator.validateJoin( joinConfig, existingJoins, primaryTable );

	if ( ! validation.isValid ) {
		showValidationErrorModal( validation );
		return; // Don't save invalid join
	}

	// In editor context: dispatch event and close without touching wizardState
	if ( getEditorJoinContext() !== null ) {
		$( document ).trigger( 'dwm:editorJoinSaved', [ joinConfig, wizardState.joinConfigEditIndex ] );
		closeJoinConfigModal();
		return;
	}

	// Wizard context: save to wizardState and re-render
	wizardState.data.joins = wizardState.data.joins || [];

	if ( wizardState.joinConfigEditIndex >= 0 ) {
		wizardState.data.joins[ wizardState.joinConfigEditIndex ] = joinConfig;
	} else {
		wizardState.data.joins.push( joinConfig );
	}

	renderWizardJoins();
	updateStep4NextButton();
	closeJoinConfigModal();
}

/**
 * Update the join query preview based on current selections
 */
export function updateJoinSaveButton() {
	const allFilled = $( '#dwm-join-config-type' ).val() &&
		$( '#dwm-join-config-table' ).val() &&
		$( '#dwm-join-config-local' ).val() &&
		$( '#dwm-join-config-foreign' ).val();

	// Always disable Save Join on any field change — only re-enabled after passing validation
	$( '#dwm-join-config-save' ).prop( 'disabled', true );

	// If output tab is currently active, switch back to query tab
	if ( $( '.dwm-join-preview-tab[data-tab="output"]' ).hasClass( 'active' ) ) {
		switchToJoinQueryTab();
	}

	// Disable output tab and clear validation on any field change
	$( '.dwm-join-preview-tab[data-tab="output"]' ).prop( 'disabled', true );
	$( '#dwm-join-validation-status' ).hide();
	wizardState.joinConfigState.validationResults = [];

	if ( allFilled ) {
		validateJoinConfig();
	}
}

/**
 * Switch join config modal to the Query Preview tab
 */
export function switchToJoinQueryTab() {
	$( '.dwm-join-preview-tab' ).removeClass( 'active' );
	$( '.dwm-join-preview-tab[data-tab="query"]' ).addClass( 'active' );
	$( '.dwm-join-preview-pane' ).removeClass( 'active' );
	$( '#dwm-join-preview-query-pane' ).addClass( 'active' );
}

/**
 * Build a full executable JOIN query for validation (LIMIT 5)
 */
export function buildJoinValidationQuery() {
	const joinType  = $( '#dwm-join-config-type' ).val();
	const joinTable = $( '#dwm-join-config-table' ).val();
	const localCol  = $( '#dwm-join-config-local' ).val();
	const foreignCol = $( '#dwm-join-config-foreign' ).val();

	if ( ! joinType || ! joinTable || ! localCol || ! foreignCol ) {
		console.warn('[DWM Join] Missing required fields, cannot build query');
		return '';
	}

	const primaryTable     = getJoinContextPrimaryTable() || 'table';
	const selectedColumns  = getJoinContextSelectedColumns();
	const tableAliases     = {};
	tableAliases[ primaryTable ] = 't1';
	let aliasCounter = 2;

	const existingJoins = getJoinContextExistingJoins().filter( function( _j, idx ) {
		return idx !== wizardState.joinConfigEditIndex;
	} );

	existingJoins.forEach( function( join ) {
		if ( join.table && ! tableAliases[ join.table ] ) {
			tableAliases[ join.table ] = 't' + aliasCounter++;
		}
	} );

	if ( ! tableAliases[ joinTable ] ) {
		tableAliases[ joinTable ] = 't' + aliasCounter++;
	}

	let selectClause = 'SELECT ';
	if ( selectedColumns.length > 0 ) {
		selectClause += selectedColumns.map( function( col ) {
			return tableAliases[ primaryTable ] + '.' + col;
		} ).join( ', ' );
	} else {
		selectClause += tableAliases[ primaryTable ] + '.*';
	}

	let query = selectClause + '\n';
	query += 'FROM ' + primaryTable + ' AS ' + tableAliases[ primaryTable ];

	existingJoins.forEach( function( join ) {
		const localBare   = join.local_col.includes( '.' )   ? join.local_col.split( '.' ).pop()   : join.local_col;
		const foreignBare = join.foreign_col.includes( '.' ) ? join.foreign_col.split( '.' ).pop() : join.foreign_col;
		query += '\n' + join.type + ' JOIN ' + join.table + ' AS ' + tableAliases[ join.table ];
		query += '\n  ON ' + tableAliases[ primaryTable ] + '.' + localBare + ' = ' + tableAliases[ join.table ] + '.' + foreignBare;
	} );

	const localColBare   = localCol.includes( '.' )   ? localCol.split( '.' ).pop()   : localCol;
	const foreignColBare = foreignCol.includes( '.' ) ? foreignCol.split( '.' ).pop() : foreignCol;
	query += '\n' + joinType + ' JOIN ' + joinTable + ' AS ' + tableAliases[ joinTable ];
	query += '\n  ON ' + tableAliases[ primaryTable ] + '.' + localColBare + ' = ' + tableAliases[ joinTable ] + '.' + foreignColBare;
	query += '\nLIMIT 5';

	return query;
}

/**
 * Validate the current join config by running the query via AJAX
 */
export function validateJoinConfig() {
	const query = buildJoinValidationQuery();
	if ( ! query ) {
		console.warn('[DWM Join] No query to validate');
		return;
	}

	const $status    = $( '#dwm-join-validation-status' );
	const $outputTab = $( '.dwm-join-preview-tab[data-tab="output"]' );

	$status.show().removeClass( 'dwm-validation-success dwm-validation-error' ).addClass( 'dwm-validation-pending' )
		.html( '<span class="spinner is-active"></span> Validating query...' );

	ajax(
		'dwm_validate_query',
		{
			query: query,
			max_execution_time: parseInt( $( '#dwm-max-execution-time' ).val(), 10 ) || 30,
		},
		function( data ) {

			const rowCount = data.row_count !== undefined ? data.row_count : ( data.results ? data.results.length : 0 );
			$status.removeClass( 'dwm-validation-pending' ).addClass( 'dwm-validation-success' )
				.html( '<span class="dashicons dashicons-yes-alt"></span> Query valid &mdash; ' + rowCount + ' row' + ( rowCount !== 1 ? 's' : '' ) + ' returned' );
			$outputTab.prop( 'disabled', false );
			$( '#dwm-join-config-save' ).prop( 'disabled', false );
			wizardState.joinConfigState.validationResults = data.results || [];
		},
		function( data ) {
			console.error('[DWM Join] Validation FAILED');
			console.error('[DWM Join] Error data:', data);

			const msg = ( data && data.message ) ? data.message : 'Query validation failed';
			$status.removeClass( 'dwm-validation-pending' ).addClass( 'dwm-validation-error' )
				.html( '<span class="dashicons dashicons-warning"></span> ' + escAttr( msg ) );
			$outputTab.prop( 'disabled', true );
			$( '#dwm-join-config-save' ).prop( 'disabled', true );
			wizardState.joinConfigState.validationResults = [];
		}
	);
}

/**
 * Render the output preview table from validation results
 */
export function renderJoinOutputTable( results ) {
	const $content = $( '#dwm-join-output-preview-content' );

	if ( ! results || results.length === 0 ) {
		$content.html( '<p class="dwm-output-empty">No results returned.</p>' );
		return;
	}

	const headers = Object.keys( results[ 0 ] );
	let html = '<div class="dwm-output-table-wrapper"><table class="dwm-output-table"><thead><tr>';
	headers.forEach( function( h ) {
		html += '<th>' + escAttr( h ) + '</th>';
	} );
	html += '</tr></thead><tbody>';

	results.forEach( function( row ) {
		html += '<tr>';
		headers.forEach( function( h ) {
			const val = ( row[ h ] === null || row[ h ] === undefined ) ? '<em class="dwm-null">NULL</em>' : escAttr( String( row[ h ] ) );
			html += '<td>' + val + '</td>';
		} );
		html += '</tr>';
	} );

	html += '</tbody></table></div>';
	$content.html( html );
}

/**
 * Update the join query preview based on current form selections
 */
export function updateJoinQueryPreview() {
	updateJoinSaveButton();

	const joinType = $( '#dwm-join-config-type' ).val();
	const joinTable = $( '#dwm-join-config-table' ).val();
	const localCol = $( '#dwm-join-config-local' ).val();
	const foreignCol = $( '#dwm-join-config-foreign' ).val();
	const $preview = $( '#dwm-join-query-preview-content' );

	// Get primary table info
	const primaryTable = getJoinContextPrimaryTable() || 'table';
	const selectedColumns = getJoinContextSelectedColumns();

	// Create table alias map
	const tableAliases = {};
	tableAliases[ primaryTable ] = 't1';
	let aliasCounter = 2;

	// Get existing joins (excluding the one being edited)
	const existingJoins = getJoinContextExistingJoins().filter( function( _j, idx ) {
		return idx !== wizardState.joinConfigEditIndex;
	});

	// Assign aliases to existing join tables
	existingJoins.forEach( function( join ) {
		if ( join.table && ! tableAliases[ join.table ] ) {
			tableAliases[ join.table ] = 't' + aliasCounter++;
		}
	});

	// Assign alias to current join table (if selected)
	if ( joinTable && ! tableAliases[ joinTable ] ) {
		tableAliases[ joinTable ] = 't' + aliasCounter++;
	}

	// Build SELECT clause with qualified column names
	let selectClause = 'SELECT ';
	if ( selectedColumns.length > 0 ) {
		selectClause += selectedColumns.map( function( col ) {
			return tableAliases[ primaryTable ] + '.' + col;
		}).join( ', ' );
	} else {
		selectClause += tableAliases[ primaryTable ] + '.*';
	}

	// Build FROM clause
	let query = selectClause + '\n';
	query += 'FROM ' + primaryTable + ' AS ' + tableAliases[ primaryTable ];

	// Add existing joins
	existingJoins.forEach( function( join ) {
		query += '\n' + join.type + ' JOIN ' + join.table + ' AS ' + tableAliases[ join.table ];
		query += '\n  ON ' + tableAliases[ primaryTable ] + '.' + join.local_col + ' = ' + tableAliases[ join.table ] + '.' + join.foreign_col;
	});

	// Add current join being configured (only if join type has been selected)
	if ( joinType ) {
		query += '\n' + joinType + ' JOIN ';

		if ( joinTable ) {
			query += joinTable + ' AS ' + tableAliases[ joinTable ];

			if ( localCol && foreignCol ) {
				query += '\n  ON ' + tableAliases[ primaryTable ] + '.' + localCol + ' = ' + tableAliases[ joinTable ] + '.' + foreignCol;
			} else if ( localCol ) {
				query += '\n  ON ' + tableAliases[ primaryTable ] + '.' + localCol + ' = ' + tableAliases[ joinTable ] + '.<column>';
			} else {
				query += '\n  ON ' + tableAliases[ primaryTable ] + '.<column> = ' + tableAliases[ joinTable ] + '.<column>';
			}
		} else {
			query += '<table> AS t' + aliasCounter;
			query += '\n  ON ' + tableAliases[ primaryTable ] + '.<column> = t' + aliasCounter + '.<column>';
		}
	}

	// Update preview
	$preview.removeClass( 'empty' ).text( query );
}

/**
 * Update step 4 Next button text: "Skip" if no joins, "Next" if joins exist
 */
export function updateStep4NextButton() {
	const joins = wizardState.data.joins || [];
	const hasJoins = joins.some( function( join ) { return join.table.trim() !== ''; });
	$( '.dwm-wizard-next' ).text( hasJoins ? 'Next' : 'Skip' );
}

/**
 * Initialize join modal event bindings
 *
 * @param {Function} goToStep - Reference to goToStep() from the main wizard module
 */
export function initJoinModalEvents( goToStep ) {

	// Wizard join: add row (open modal)
	$( document ).on( 'click', '#dwm-wizard-add-join', function() {
		openJoinConfigModal();
	});

	// Widget editor: open join config modal in editor context
	$( document ).on( 'dwm:openEditorJoinModal', function( _e, options ) {
		openJoinConfigModal(
			typeof options.editIndex === 'number' ? options.editIndex : -1,
			{
				primaryTable:    options.primaryTable    || '',
				primaryColumns:  options.primaryColumns  || [],
				selectedColumns: options.selectedColumns || [],
				existingJoins:   options.existingJoins   || []
			}
		);
	} );

	// Join config modal: cancel
	$( document ).on( 'click', '.dwm-join-config-cancel', function() {
		closeJoinConfigModal();
	});

	// Join config modal: save
	$( document ).on( 'click', '#dwm-join-config-save', function() {
		saveJoinConfig();
	});

	// Join config modal: when join type changes, load join table dropdown
	$( document ).on( 'change', '#dwm-join-config-type', function() {
		if ( $( this ).val() ) {
			$( '#dwm-join-config-table' ).prop( 'disabled', false );
		} else {
			$( '#dwm-join-config-table' ).prop( 'disabled', true ).val( '' );
			$( '#dwm-join-config-local' ).prop( 'disabled', true ).val( '' );
			$( '#dwm-join-config-foreign' ).prop( 'disabled', true ).val( '' );
		}
		updateJoinQueryPreview();
	});

	// Join config modal: when join table changes, check compatibility and load foreign columns
	$( document ).on( 'change', '#dwm-join-config-table', function() {
		const table = $( this ).val();
		if ( table ) {
			checkJoinCompatibilityAndLoad( table );
		} else {
			$( '#dwm-join-config-local' ).prop( 'disabled', true ).val( '' );
			$( '#dwm-join-config-foreign' ).prop( 'disabled', true ).val( '' );
			wizardState.joinConfigState.joinTableColumns = [];
			wizardState.joinConfigState.compatibilityMap = {};
		}
		updateJoinQueryPreview();
	});

	// No compatible columns modal: choose different table
	$( document ).on( 'click', '#dwm-no-compatible-choose-different', function() {
		$( '#dwm-no-compatible-columns-modal' ).removeClass( 'active' );
		$( '#dwm-join-config-table' ).val( '' ).focus();
	});

	// No compatible columns modal: go back to column selection
	$( document ).on( 'click', '#dwm-no-compatible-go-back', function() {
		$( '#dwm-no-compatible-columns-modal' ).removeClass( 'active' );
		closeJoinConfigModal();
		goToStep( 3 );
	});

	// No compatible columns modal: close button
	$( document ).on( 'click', '.dwm-no-compatible-columns-close', function() {
		$( '#dwm-no-compatible-columns-modal' ).removeClass( 'active' );
	});

	// Join config modal: when local column selected, filter foreign column options
	$( document ).on( 'change', '#dwm-join-config-local', function() {
		const localCol = $( this ).val();
		if ( localCol ) {
			filterJoinColumnsByCompatibility( localCol );
			$( '#dwm-join-config-foreign' ).prop( 'disabled', false );
		} else {
			// Show all foreign columns if no local column selected
			repopulateAllForeignColumns();
			$( '#dwm-join-config-foreign' ).prop( 'disabled', true ).val( '' );
		}
		updateJoinQueryPreview();
	});

	// Join config modal: when foreign column changes, update preview
	$( document ).on( 'change', '#dwm-join-config-foreign', function() {
		updateJoinQueryPreview();
	});

	// Join config modal: preview tab switching
	$( document ).on( 'click', '.dwm-join-preview-tab', function() {
		if ( $( this ).prop( 'disabled' ) ) {
			return;
		}
		const tab = $( this ).data( 'tab' );
		$( '.dwm-join-preview-tab' ).removeClass( 'active' );
		$( this ).addClass( 'active' );
		$( '.dwm-join-preview-pane' ).removeClass( 'active' );
		$( '#dwm-join-preview-' + tab + '-pane' ).addClass( 'active' );
		if ( tab === 'output' ) {
			renderJoinOutputTable( wizardState.joinConfigState.validationResults || [] );
		}
	});

	// Wizard join: edit existing join
	$( document ).on( 'click', '.dwm-wizard-edit-join', function() {
		const idx = parseInt( $( this ).data( 'index' ), 10 );
		openJoinConfigModal( idx );
	});

	// Wizard join: remove join
	$( document ).on( 'click', '.dwm-wizard-remove-join', function() {
		const idx = parseInt( $( this ).data( 'index' ), 10 );
		wizardState.data.joins.splice( idx, 1 );
		renderWizardJoins();
		updateStep4NextButton();
	});
}
