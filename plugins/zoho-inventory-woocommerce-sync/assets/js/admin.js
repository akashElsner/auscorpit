/**
 * Zoho Inventory WooCommerce Sync — Admin JavaScript
 *
 * Handles:
 *  - Manual sync buttons
 *  - Queue stats loading / refreshing
 *  - Log table AJAX refresh
 *  - Organisation dropdown loading
 *  - Disconnect button
 *  - Clipboard copy
 *
 * Depends on: jQuery, ZohoInventorySync (localised data from wp_localize_script).
 */
/* global ZohoInventorySync, jQuery */

( function ( $ ) {
	'use strict';

	const { ajaxUrl, nonce, i18n } = ZohoInventorySync;

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Make an AJAX call and return a jQuery Deferred.
	 *
	 * @param {string} action  wp_ajax action name.
	 * @param {Object} data    Extra POST data.
	 * @returns {jqXHR}
	 */
	function ajax( action, data ) {
		return $.post( ajaxUrl, Object.assign( { action, nonce }, data ) );
	}

	/** Display a temporary inline status message on a cell. */
	function setStatus( $cell, text, type ) {
		$cell
			.removeClass( 'is-loading is-success is-error' )
			.addClass( type ? 'is-' + type : '' )
			.text( text );
	}

	// -------------------------------------------------------------------------
	// Queue stats
	// -------------------------------------------------------------------------

	function loadQueueStats() {
		var $wrapper = $( '#zoho-queue-stats' );
		$wrapper.html( '<p><em>' + i18n.syncing + '</em></p>' );

		ajax( 'zoho_inv_sync_queue_stats' )
			.done( function ( res ) {
				if ( ! res.success ) return;
				var s = res.data;
				$wrapper.html(
					buildPill( 'pending',    s.pending    || 0, 'Pending'    ) +
					buildPill( 'processing', s.processing || 0, 'Processing' ) +
					buildPill( 'completed',  s.completed  || 0, 'Completed'  ) +
					buildPill( 'failed',     s.failed     || 0, 'Failed'     )
				);
			} )
			.fail( function () {
				$wrapper.html( '<p><em>' + i18n.error + '</em></p>' );
			} );
	}

	function buildPill( statusClass, count, label ) {
		return '<div class="zoho-stat-pill ' + statusClass + '">' +
			'<span class="stat-count">' + count + '</span>' +
			'<span class="stat-label">' + label + '</span>' +
			'</div>';
	}

	// -------------------------------------------------------------------------
	// Manual sync buttons
	// -------------------------------------------------------------------------

	$( document ).on( 'click', '.zoho-sync-btn', function () {
		var entity   = $( this ).data( 'entity' );
		var $btn     = $( this );
		var $status  = $( '#zoho-sync-status-' + entity );

		if ( ! confirm( i18n.confirmSync ) ) return;

		$btn.prop( 'disabled', true );
		setStatus( $status, '<span class="zoho-spinner"></span>' + i18n.syncing, 'loading' );

		ajax( 'zoho_inv_sync_manual', { entity: entity } )
			.done( function ( res ) {
				if ( res.success ) {
					setStatus( $status, i18n.done + ' ' + ( res.data.message || '' ), 'success' );
					loadQueueStats();
				} else {
					setStatus( $status, ( res.data && res.data.message ) || i18n.error, 'error' );
				}
			} )
			.fail( function () {
				setStatus( $status, i18n.error, 'error' );
			} )
			.always( function () {
				$btn.prop( 'disabled', false );
			} );
	} );

	// -------------------------------------------------------------------------
	// Refresh queue stats button
	// -------------------------------------------------------------------------

	$( '#zoho-refresh-stats-btn' ).on( 'click', loadQueueStats );

	// -------------------------------------------------------------------------
	// Log table refresh
	// -------------------------------------------------------------------------

	function refreshLogs() {
		var $tbody = $( '#zoho-log-tbody' );
		$tbody.html( '<tr><td colspan="5"><em>' + i18n.syncing + '</em></td></tr>' );

		ajax( 'zoho_inv_sync_get_logs', {
			entity_type : $( '#zoho-log-entity-filter' ).val(),
			status      : $( '#zoho-log-status-filter' ).val(),
		} )
			.done( function ( res ) {
				if ( res.success && res.data.html ) {
					$tbody.html( res.data.html || '<tr><td colspan="5">No entries.</td></tr>' );
				}
			} )
			.fail( function () {
				$tbody.html( '<tr><td colspan="5">' + i18n.error + '</td></tr>' );
			} );
	}

	$( '#zoho-log-refresh-btn' ).on( 'click', refreshLogs );
	$( '#zoho-log-entity-filter, #zoho-log-status-filter' ).on( 'change', refreshLogs );

	// -------------------------------------------------------------------------
	// Load Zoho organizations
	// -------------------------------------------------------------------------

	$( '#zoho-load-orgs-btn' ).on( 'click', function () {
		var $btn    = $( this );
		var $wrapper = $( '#zoho-orgs-wrapper' );
		var $select  = $( '#zoho-org-select' );

		$btn.prop( 'disabled', true ).text( i18n.loadingOrgs );

		ajax( 'zoho_inv_sync_get_orgs' )
			.done( function ( res ) {
				if ( res.success && res.data.organizations ) {
					$select.find( 'option:not(:first)' ).remove();
					$.each( res.data.organizations, function ( i, org ) {
						$select.append(
							$( '<option>' )
								.val( org.organization_id )
								.text( org.name + ' (' + org.organization_id + ')' )
						);
					} );
					$wrapper.slideDown();
				} else {
					alert( ( res.data && res.data.message ) || i18n.error );
				}
			} )
			.fail( function () {
				alert( i18n.error );
			} )
			.always( function () {
				$btn.prop( 'disabled', false ).text( 'Load Organizations' );
			} );
	} );

	// -------------------------------------------------------------------------
	// Disconnect
	// -------------------------------------------------------------------------

	$( '#zoho-disconnect-btn' ).on( 'click', function () {
		if ( ! confirm( i18n.confirmDisc ) ) return;

		var $btn = $( this );
		$btn.prop( 'disabled', true );

		ajax( 'zoho_inv_sync_disconnect' )
			.done( function ( res ) {
				if ( res.success ) {
					window.location.reload();
				} else {
					alert( ( res.data && res.data.message ) || i18n.error );
					$btn.prop( 'disabled', false );
				}
			} )
			.fail( function () {
				alert( i18n.error );
				$btn.prop( 'disabled', false );
			} );
	} );

	// -------------------------------------------------------------------------
	// Copy to clipboard
	// -------------------------------------------------------------------------

	$( document ).on( 'click', '.zoho-copy-btn', function () {
		var targetId = $( this ).data( 'target' );
		var text     = $( '#' + targetId ).text().trim();
		var $btn     = $( this );

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( function () {
				$btn.addClass( 'copied' ).text( 'Copied!' );
				setTimeout( function () {
					$btn.removeClass( 'copied' ).text( 'Copy' );
				}, 2000 );
			} );
		}
	} );

	// -------------------------------------------------------------------------
	// Init
	// -------------------------------------------------------------------------

	// Auto-load queue stats if the stats element is present (manual-sync tab).
	if ( $( '#zoho-queue-stats' ).length ) {
		loadQueueStats();
	}

} )( jQuery );
