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

		// Screen-reader status: announce only when the minute (or coarser unit)
		// changes — never per second — so assistive tech isn't flooded.
		announce( el, days, hours, minutes, format );

		// Stopwatch sweep: drain the ring around the seconds unit once per
		// minute, and snap the digit on each new second. Presentation only —
		// the actual end moment is fixed by the server timestamp above.
		sweep( el, seconds );

		return true;
	}

	/**
	 * Update the polite live region, but only on a meaningful (>= 1 minute)
	 * change. The visible digits are aria-hidden and tick every second; this
	 * coarse message is what screen readers actually hear.
	 *
	 * @param {HTMLElement} el      Countdown root.
	 * @param {number}      days    Whole days remaining.
	 * @param {number}      hours   Whole hours remaining within the day/run.
	 * @param {number}      minutes Whole minutes remaining within the hour.
	 * @param {string}      format  Display format (dhms|hms|compact).
	 */
	function announce( el, days, hours, minutes, format ) {
		var status = el.querySelector( '[data-ticker-status]' );
		if ( ! status ) {
			return;
		}

		// Re-announce only when the minute boundary (or coarser) changes.
		var stamp = days + ':' + hours + ':' + minutes;
		if ( status.getAttribute( 'data-ticker-stamp' ) === stamp ) {
			return;
		}
		status.setAttribute( 'data-ticker-stamp', stamp );

		// Reuse the already-translated unit labels from the visible markup, so
		// the spoken phrase needs no extra strings beyond the template.
		var parts = [];
		if ( 'dhms' === format && days > 0 ) {
			parts.push( days + ' ' + labelFor( el, 'days' ) );
		}
		if ( hours > 0 ) {
			parts.push( hours + ' ' + labelFor( el, 'hours' ) );
		}
		parts.push( minutes + ' ' + labelFor( el, 'minutes' ) );

		var template = status.getAttribute( 'data-ticker-announce-template' ) || '%s';
		status.textContent = template.replace( '%s', parts.join( ' ' ) );
	}

	/**
	 * Read a unit's already-translated label text from the visible markup.
	 *
	 * @param {HTMLElement} el   Countdown root.
	 * @param {string}      unit Unit name.
	 * @return {string} Trimmed label, or the unit name as a fallback.
	 */
	function labelFor( el, unit ) {
		var node = el.querySelector( '.ticker__unit--' + unit + ' .ticker__label' );
		return node ? node.textContent.trim() : unit;
	}

	/**
	 * Drive the seconds unit's stopwatch sweep and tick snap.
	 *
	 * @param {HTMLElement} el      Countdown root.
	 * @param {number}      seconds Current seconds remaining within the minute.
	 */
	function sweep( el, seconds ) {
		var unit = el.querySelector( '.ticker__unit--seconds' );
		if ( ! unit || unit.hidden ) {
			return;
		}

		// Ring drains as the minute empties: full at :59, gone at :00.
		var deg = ( seconds / 60 ) * 360;
		unit.style.setProperty( '--ticker-ring', deg.toFixed( 1 ) + 'deg' );

		// Re-trigger the snap by toggling the class off then on.
		unit.classList.remove( 'is-tick' );
		// Force reflow so the animation restarts on identical re-adds.
		void unit.offsetWidth;
		unit.classList.add( 'is-tick' );
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

		// Announce expiry once through the polite status (a sibling of the
		// clock, so it stays in the tree after the clock is hidden below).
		var status = el.querySelector( '[data-ticker-status]' );
		if ( status ) {
			status.textContent = el.getAttribute( 'data-ticker-expired-text' ) || '';
		}

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
