/**
 * Ticker — live sale countdown.
 *
 * The end time is provided by the server as a UTC epoch (seconds) in
 * data-ticker-end; the browser only formats the remaining time, so client
 * clock skew never changes the actual end moment. Vanilla JS, no dependencies.
 */
( function () {
	'use strict';

	var TICK_MS = 1000;

	/**
	 * Zero-pad a number to two digits.
	 *
	 * @param {number} n Value.
	 * @return {string} Padded string.
	 */
	function pad( n ) {
		return n < 10 ? '0' + n : String( n );
	}

	/**
	 * Update a single countdown element.
	 *
	 * @param {HTMLElement} el Countdown root.
	 * @return {boolean} True while still counting, false once expired.
	 */
	function update( el ) {
		var end = parseInt( el.getAttribute( 'data-ticker-end' ), 10 );
		if ( isNaN( end ) ) {
			return false;
		}

		var nowSec = Math.floor( Date.now() / 1000 );
		var remaining = end - nowSec;

		if ( remaining <= 0 ) {
			expire( el );
			return false;
		}

		var days = Math.floor( remaining / 86400 );
		var hours = Math.floor( ( remaining % 86400 ) / 3600 );
		var minutes = Math.floor( ( remaining % 3600 ) / 60 );
		var seconds = remaining % 60;

		var format = el.getAttribute( 'data-ticker-format' ) || 'dhms';

		// In hms/compact mode, fold whole days into hours.
		if ( format !== 'dhms' ) {
			hours += days * 24;
			days = 0;
		}

		setValue( el, 'days', String( days ) );
		setValue( el, 'hours', pad( hours ) );
		setValue( el, 'minutes', pad( minutes ) );
		setValue( el, 'seconds', pad( seconds ) );

		return true;
	}

	/**
	 * Write a value into a unit, only touching the DOM when it changed.
	 *
	 * @param {HTMLElement} el   Countdown root.
	 * @param {string}      unit Unit name.
	 * @param {string}      val  New value.
	 */
	function setValue( el, unit, val ) {
		var node = el.querySelector( '[data-ticker-' + unit + ']' );
		if ( node && node.textContent !== val ) {
			node.textContent = val;
		}
	}

	/**
	 * Flip a countdown into its expired state.
	 *
	 * @param {HTMLElement} el Countdown root.
	 */
	function expire( el ) {
		if ( el.classList.contains( 'is-expired' ) ) {
			return;
		}
		el.classList.add( 'is-expired' );

		var clock = el.querySelector( '.ticker__clock' );
		if ( clock ) {
			clock.hidden = true;
		}

		var expired = el.querySelector( '.ticker__expired' );
		if ( expired ) {
			expired.hidden = false;
			expired.setAttribute( 'data-active', '1' );
		}
	}

	/**
	 * Initialise every countdown on the page with a shared interval.
	 */
	function init() {
		var nodes = document.querySelectorAll( '.ticker__countdown[data-ticker-end]' );
		if ( ! nodes.length ) {
			return;
		}

		var live = [];
		nodes.forEach( function ( el ) {
			if ( update( el ) ) {
				live.push( el );
			}
		} );

		if ( ! live.length ) {
			return;
		}

		var timer = setInterval( function () {
			live = live.filter( function ( el ) {
				return update( el );
			} );
			if ( ! live.length ) {
				clearInterval( timer );
			}
		}, TICK_MS );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
