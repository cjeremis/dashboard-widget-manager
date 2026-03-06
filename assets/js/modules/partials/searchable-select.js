/**
 * Dashboard Widget Manager - Searchable Select (Custom Combobox)
 *
 * Replaces a native <select> with a custom combobox: a trigger button that
 * opens a dropdown panel containing an integrated search field and a
 * scrollable, filterable list of options.
 *
 * The native <select> remains in the DOM (hidden) so existing change event
 * listeners and .val() reads continue to work without modification.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

let $openCombobox = null;

// Close on outside click
$( document ).on( 'click', function( e ) {
	if ( $openCombobox && ! $( e.target ).closest( '.dwm-combobox' ).length ) {
		closeCombobox( $openCombobox );
		$openCombobox = null;
	}
} );

// Close on Escape
$( document ).on( 'keydown', function( e ) {
	if ( e.key === 'Escape' && $openCombobox ) {
		closeCombobox( $openCombobox );
		$openCombobox = null;
	}
} );

// ── Build ─────────────────────────────────────────────────────────────────────

function buildCombobox( $select, triggerPlaceholder, searchPlaceholder ) {
	const $wrapper = $( '<div class="dwm-combobox"></div>' );

	const $trigger    = $( '<button type="button" class="dwm-combobox-trigger" aria-haspopup="listbox" aria-expanded="false"></button>' );
	const $triggerText = $( '<span class="dwm-combobox-trigger-text"></span>' );
	const $arrow      = $( '<span class="dwm-combobox-arrow" aria-hidden="true"></span>' );
	$trigger.append( $triggerText ).append( $arrow );

	const $panel  = $( '<div class="dwm-combobox-panel" hidden></div>' );
	const $search = $( '<input type="search" class="dwm-combobox-search" autocomplete="off">' );
	$search.attr( 'placeholder', searchPlaceholder || triggerPlaceholder || 'Search...' );
	const $list = $( '<ul class="dwm-combobox-list" role="listbox"></ul>' );
	$panel.append( $search ).append( $list );

	$wrapper.append( $trigger ).append( $panel );

	// Hide native select but keep it for .val() and change events
	$select.hide().after( $wrapper );

	const comboData = { $wrapper, $trigger, $triggerText, $panel, $search, $list, triggerPlaceholder: triggerPlaceholder || '' };
	$select.data( 'dwmCombobox', comboData );

	// ── Events ────────────────────────────────────────────────────────────────

	$trigger.on( 'click', function( e ) {
		e.stopPropagation();
		const isOpen = ! $panel.prop( 'hidden' );
		if ( isOpen ) {
			closeCombobox( $wrapper );
			$openCombobox = null;
		} else {
			if ( $openCombobox && $openCombobox[ 0 ] !== $wrapper[ 0 ] ) {
				closeCombobox( $openCombobox );
			}
			openCombobox( $wrapper, $select );
			$openCombobox = $wrapper;
		}
	} );

	$search.on( 'input', function() {
		filterList( $list, $( this ).val() );
	} );

	$search.on( 'keydown', function( e ) {
		if ( e.key === 'ArrowDown' ) {
			e.preventDefault();
			$list.find( '.dwm-combobox-option:visible' ).first().focus();
		}
	} );

	$list.on( 'keydown', '.dwm-combobox-option', function( e ) {
		const $visible = $list.find( '.dwm-combobox-option:visible' );
		const idx = $visible.index( this );
		if ( e.key === 'ArrowDown' ) {
			e.preventDefault();
			$visible.eq( idx + 1 ).focus();
		} else if ( e.key === 'ArrowUp' ) {
			e.preventDefault();
			if ( idx === 0 ) {
				$search.focus();
			} else {
				$visible.eq( idx - 1 ).focus();
			}
		} else if ( e.key === 'Enter' || e.key === ' ' ) {
			e.preventDefault();
			$( this ).trigger( 'click' );
		}
	} );

	$list.on( 'click', '.dwm-combobox-option', function() {
		const value = $( this ).data( 'value' );
		selectOption( $wrapper, $select, value );
		closeCombobox( $wrapper );
		$openCombobox = null;
		$trigger.focus();
	} );
}

// ── Open / Close ──────────────────────────────────────────────────────────────

function openCombobox( $wrapper, $select ) {
	const data = $select.data( 'dwmCombobox' );
	if ( ! data ) return;
	const { $trigger, $panel, $search, $list } = data;

	populateList( $list, $select );
	$panel.prop( 'hidden', false );
	$trigger.attr( 'aria-expanded', 'true' );
	$search.val( '' );
	filterList( $list, '' );

	// Scroll selected item into view
	const $sel = $list.find( '.dwm-combobox-option--selected' ).first();
	if ( $sel.length ) {
		$sel[ 0 ].scrollIntoView( { block: 'nearest' } );
	}

	setTimeout( function() { $search.focus(); }, 0 );
}

function closeCombobox( $wrapper ) {
	$wrapper.find( '.dwm-combobox-panel' ).prop( 'hidden', true );
	$wrapper.find( '.dwm-combobox-trigger' ).attr( 'aria-expanded', 'false' );
}

// ── List Population & Filtering ───────────────────────────────────────────────

function populateList( $list, $select ) {
	$list.empty();
	const currentVal = $select.val();

	$select.children().each( function() {
		const $child = $( this );
		if ( $child.is( 'optgroup' ) ) {
			const $group = $( '<li class="dwm-combobox-group" role="presentation"></li>' );
			$group.append( $( '<span class="dwm-combobox-group-label"></span>' ).text( $child.attr( 'label' ) ) );
			$child.children( 'option' ).each( function() {
				$group.append( buildOptionItem( $( this ), currentVal ) );
			} );
			$list.append( $group );
		} else if ( $child.is( 'option' ) ) {
			$list.append( buildOptionItem( $child, currentVal ) );
		}
	} );
}

function buildOptionItem( $option, currentVal ) {
	const value    = $option.val();
	const text     = $option.text();
	const selected = String( currentVal ) === String( value ) && value !== '';

	const $item = $( '<li class="dwm-combobox-option" role="option" tabindex="-1"></li>' );
	$item.text( text );
	$item.data( 'value', value );
	$item.data( 'searchText', ( text + ' ' + value ).toLowerCase() );
	$item.attr( 'aria-selected', selected ? 'true' : 'false' );
	if ( selected )    $item.addClass( 'dwm-combobox-option--selected' );
	if ( value === '' ) $item.addClass( 'dwm-combobox-option--placeholder' );
	return $item;
}

function filterList( $list, query ) {
	const q = query.trim().toLowerCase();

	$list.find( '.dwm-combobox-option' ).each( function() {
		const text = String( $( this ).data( 'searchText' ) || '' );
		$( this ).toggle( ! q || text.indexOf( q ) !== -1 );
	} );

	// Hide groups whose every option is hidden
	$list.find( '.dwm-combobox-group' ).each( function() {
		$( this ).toggle( $( this ).find( '.dwm-combobox-option:visible' ).length > 0 );
	} );
}

// ── Selection ─────────────────────────────────────────────────────────────────

function selectOption( $wrapper, $select, value ) {
	$select.val( value ).trigger( 'change' );
	syncTriggerText( $wrapper, $select );

	// Update aria-selected in open list (if still rendered)
	const data = $select.data( 'dwmCombobox' );
	if ( data ) {
		data.$list.find( '.dwm-combobox-option' )
			.removeClass( 'dwm-combobox-option--selected' )
			.attr( 'aria-selected', 'false' );
		data.$list.find( '.dwm-combobox-option' ).filter( function() {
			return String( $( this ).data( 'value' ) ) === String( value );
		} ).addClass( 'dwm-combobox-option--selected' ).attr( 'aria-selected', 'true' );
	}
}

function syncTriggerText( $wrapper, $select ) {
	const data = $select.data( 'dwmCombobox' );
	if ( ! data ) return;

	const val = $select.val();
	const safeVal = val ? String( val ).replace( /"/g, '\\"' ) : '';
	const $opt  = $select.find( 'option[value="' + safeVal + '"]' );
	const text  = $opt.length ? $opt.text() : '';
	const empty = ! val || val === '';

	data.$triggerText.text( empty ? ( data.triggerPlaceholder || data.$search.attr( 'placeholder' ) || 'Select' ) : text );
	data.$wrapper.toggleClass( 'dwm-combobox--empty', empty );
}

// ── Public API ────────────────────────────────────────────────────────────────

export function ensureSearchableSelect( selector, triggerPlaceholder, searchPlaceholder ) {
	const $select = $( selector );
	if ( ! $select.length ) return;

	if ( $select.data( 'dwmCombobox' ) ) {
		const data = $select.data( 'dwmCombobox' );
		if ( triggerPlaceholder ) {
			data.triggerPlaceholder = triggerPlaceholder;
		}
		if ( searchPlaceholder ) {
			data.$search.attr( 'placeholder', searchPlaceholder );
		} else if ( triggerPlaceholder && ! searchPlaceholder ) {
			data.$search.attr( 'placeholder', triggerPlaceholder );
		}
		return;
	}

	buildCombobox( $select, triggerPlaceholder, searchPlaceholder );
	syncTriggerText( $select.data( 'dwmCombobox' ).$wrapper, $select );
}

export function refreshSearchableSelect( selector ) {
	const $select = $( selector );
	if ( ! $select.length ) return;

	const data = $select.data( 'dwmCombobox' );
	if ( ! data ) return;

	syncTriggerText( data.$wrapper, $select );

	const disabled = $select.prop( 'disabled' );
	data.$wrapper.toggleClass( 'dwm-combobox--disabled', disabled );
	data.$trigger.prop( 'disabled', disabled );
}
