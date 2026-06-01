/**
 * ndb-suburb-filter.js — make the "Filter by Suburb" dropdown work again.
 *
 * In the live Wix site this control was populated by Wix's runtime from a
 * dataset and navigated to the selected suburb's page. That runtime was stripped
 * during the WordPress conversion, leaving a static, non-functional control.
 *
 * This restores the behaviour as PURE PROGRESSIVE ENHANCEMENT, with a hard rule:
 * the approved (closed) appearance must not change by a single pixel. We do NOT
 * touch the Wix markup's visuals. Instead we lay a fully transparent native
 * <select> exactly over the existing control; the closed state shows the
 * untouched Wix design underneath, and clicking opens the browser-native list and
 * navigates to the chosen suburb. No styling of the open list is attempted, so
 * nothing about the approved design can drift.
 *
 * Data (window.ndbSuburbs = [{label, url}, …]) is injected server-side and only
 * on the Delivery Areas archive.
 */
( function () {
	'use strict';

	function init() {
		var data = window.ndbSuburbs;
		if ( ! data || ! data.length ) {
			return;
		}
		var host = document.getElementById( 'comp-mamvd9lp' );
		if ( ! host || host.getAttribute( 'data-ndb-wired' ) === '1' ) {
			return;
		}
		host.setAttribute( 'data-ndb-wired', '1' );

		// The overlay must be positioned relative to the control. Adding
		// position:relative with no offsets moves nothing visually.
		if ( getComputedStyle( host ).position === 'static' ) {
			host.style.position = 'relative';
		}

		var select = document.createElement( 'select' );
		select.setAttribute( 'aria-label', 'Filter by Suburb' );
		// Fully transparent, covers the control, intercepts the click. No visual.
		select.style.cssText = [
			'position:absolute',
			'top:0',
			'left:0',
			'width:100%',
			'height:100%',
			'margin:0',
			'padding:0',
			'border:0',
			'opacity:0',
			'cursor:pointer',
			'background:transparent',
			'-webkit-appearance:none',
			'appearance:none',
			'z-index:2'
		].join( ';' );

		var placeholder = document.createElement( 'option' );
		placeholder.value = '';
		placeholder.textContent = 'Select Suburb';
		placeholder.disabled = true;
		placeholder.selected = true;
		select.appendChild( placeholder );

		for ( var i = 0; i < data.length; i++ ) {
			var item = data[ i ];
			if ( ! item || ! item.url || ! item.label ) {
				continue;
			}
			var opt = document.createElement( 'option' );
			opt.value = item.url;
			opt.textContent = item.label;
			select.appendChild( opt );
		}

		select.addEventListener( 'change', function () {
			var url = select.value;
			if ( ! url ) {
				return;
			}
			// Reflect the choice in the visible Wix input before navigating, so the
			// control reads naturally during the brief moment before the new page
			// loads (purely cosmetic; navigation happens regardless).
			var input = host.querySelector( '.wixui-dropdown__input, input[role="combobox"]' );
			if ( input ) {
				input.value = select.options[ select.selectedIndex ].textContent;
			}
			window.location.href = url;
		} );

		// Keep a single accessible/tab target: the native <select>. The decorative
		// Wix input is removed from the tab order (non-visual change).
		var wixInput = host.querySelector( 'input[role="combobox"]' );
		if ( wixInput ) {
			wixInput.setAttribute( 'tabindex', '-1' );
			wixInput.setAttribute( 'aria-hidden', 'true' );
		}

		host.appendChild( select );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
