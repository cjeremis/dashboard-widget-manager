/**
 * Dashboard Widget Manager - Cache Tools
 *
 * Handles clear cache tools actions and confirmation modal.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const $ = jQuery;

export function initCacheTools() {
	$( document ).on( 'click', '#dwm-open-clear-caches-modal', function() {
		openModal( 'dwm-clear-caches-modal' );
	} );

	$( document ).on( 'click', '#dwm-clear-caches-confirm', function() {
		const $btn = $( this );
		showLoading( $btn );

		ajax(
			'dwm_clear_caches',
			{},
			function( data ) {
				hideLoading( $btn );
				clearClientDwmCaches();
				closeModal( 'dwm-clear-caches-modal' );
				window.DWMToast.success( data.message || 'All caches have been cleared.', { title: 'Clear Caches' } );
			},
			function( data ) {
				hideLoading( $btn );
				window.DWMToast.error( data.message || 'Failed to clear caches.', { title: 'Clear Caches' } );
			}
		);
	} );
}

/**
 * Remove browser-side cache artifacts used by DWM builders/wizard.
 */
function clearClientDwmCaches() {
	clearStorageByPrefix( sessionStorage, 'dwm_' );
	clearStorageByPrefix( localStorage, 'dwm_' );
}

function clearStorageByPrefix( storage, prefix ) {
	if ( ! storage ) {
		return;
	}

	const keysToDelete = [];
	for ( let i = 0; i < storage.length; i++ ) {
		const key = storage.key( i );
		if ( key && key.indexOf( prefix ) === 0 ) {
			keysToDelete.push( key );
		}
	}

	keysToDelete.forEach( function( key ) {
		storage.removeItem( key );
	} );
}
