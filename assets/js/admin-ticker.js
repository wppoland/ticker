/**
 * Ticker admin — progressive enhancement for the settings screen.
 *
 * Toggles dependent fields (campaign date, scarcity threshold) and provides a
 * title fallback for the help tooltips on browsers without the Popover API.
 */
( function () {
	'use strict';

	/**
	 * Show or hide a settings row based on a controlling input.
	 *
	 * @param {HTMLElement} control Controlling input.
	 * @param {boolean}     visible Whether the dependent row is visible.
	 */
	function setRowVisible( control, visible ) {
		var row = control.closest( 'tr' );
		if ( row ) {
			row.style.display = visible ? '' : 'none';
		}
	}

	function init() {
		var source = document.getElementById( 'ticker_source' );
		var campaign = document.getElementById( 'ticker_campaign_end' );
		var scarcityEnabled = document.getElementById( 'ticker_scarcity_enabled' );
		var threshold = document.getElementById( 'ticker_scarcity_threshold' );

		// Campaign date is most relevant when source = campaign, but is also a
		// fallback, so we keep it visible and only emphasise via a hint.
		if ( source && campaign ) {
			var sync = function () {
				var row = campaign.closest( 'tr' );
				if ( row ) {
					row.classList.toggle( 'ticker-row-emphasis', source.value === 'campaign' );
				}
			};
			source.addEventListener( 'change', sync );
			sync();
		}

		if ( scarcityEnabled && threshold ) {
			var syncScarcity = function () {
				setRowVisible( threshold, scarcityEnabled.checked );
			};
			scarcityEnabled.addEventListener( 'change', syncScarcity );
			syncScarcity();
		}

		// Popover fallback: if unsupported, expose help text via title attribute.
		var supportsPopover = 'popover' in HTMLElement.prototype;
		if ( ! supportsPopover ) {
			document.querySelectorAll( '.ticker-help' ).forEach( function ( btn ) {
				var id = btn.getAttribute( 'popovertarget' );
				var tip = id ? document.getElementById( id ) : null;
				if ( tip ) {
					btn.setAttribute( 'title', tip.textContent || '' );
				}
			} );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
