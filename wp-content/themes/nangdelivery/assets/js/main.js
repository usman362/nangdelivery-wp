/**
 * Nang Delivery Brisbane — front-end behaviours.
 *
 * The visual layer (markup + inlined CSS) is rendered server-side and is
 * byte-faithful to the approved design. This file rebuilds the small amount of
 * runtime interactivity the design relies on, so the theme is fully
 * self-contained (no third-party menu runtime).
 *
 * Modules:
 *   - ndbActiveNav      : re-mark the current page's nav item as selected.
 *   - ndbResponsiveMenu : horizontal nav "More" overflow + dropdown.
 *
 * @package NangDelivery
 */
(function () {
	'use strict';

	/* ------------------------------------------------------------------ *
	 * Active navigation highlight
	 *
	 * The shared header/footer partials are page-agnostic: at build time the
	 * active item's data-state is normalised from "selected" back to "false"
	 * (and aria-current dropped) so one partial serves every page. We restore
	 * the highlight here by matching each nav link's path against the current
	 * URL — exactly the state Wix would have rendered server-side, just done
	 * once on the client.
	 * ------------------------------------------------------------------ */
	function normPath( p ) {
		try {
			// Resolve relative/absolute, strip query+hash, lower-case, ensure
			// a single trailing slash so "/about-us" and "/about-us/" unify.
			p = new URL( p, window.location.origin ).pathname;
		} catch ( e ) {
			p = ( p || '' ).split( '?' )[ 0 ].split( '#' )[ 0 ];
		}
		p = p.toLowerCase().replace( /\/+$/, '' );
		return '' === p ? '/' : p + '/';
	}

	function markActiveNav() {
		var here = normPath( window.location.pathname );
		Array.prototype.forEach.call(
			document.querySelectorAll( 'li[data-state] > a[href]' ),
			function ( a ) {
				var li = a.parentNode;
				if ( normPath( a.getAttribute( 'href' ) ) !== here ) {
					return;
				}
				var state = li.getAttribute( 'data-state' ) || '';
				if ( /\bselected\b/.test( state ) ) {
					return; // already active
				}
				li.setAttribute(
					'data-state',
					/\bfalse\b/.test( state )
						? state.replace( /\bfalse\b/, 'selected' )
						: ( state + ' selected' ).trim()
				);
				a.setAttribute( 'aria-current', 'page' );
			}
		);
	}

	/* ------------------------------------------------------------------ *
	 * Responsive overflow navigation
	 *
	 * The primary + footer navigations are fixed-width horizontal bars. When
	 * the menu items are wider than the bar, the trailing items collapse into
	 * a "More" dropdown. This mirrors the approved design exactly:
	 *   budget = bar width − "More" button width
	 *   keep items while the running width fits the budget; overflow the rest.
	 * The collapsed items are cloned into the existing dropdown panel and the
	 * panel is revealed on hover / keyboard focus.
	 * ------------------------------------------------------------------ */
	function setupMenu( menu ) {
		var nav    = menu.querySelector( 'nav' );
		var list   = menu.querySelector( 'ul.nXVsjj' ) || ( nav && nav.querySelector( 'ul' ) );
		if ( ! list ) {
			return null;
		}
		var moreLi = list.querySelector( 'li.NuGFGn' );          // the "More" toggle item
		var panel  = menu.querySelector( '.pY_SDa' );            // absolutely-positioned dropdown wrapper
		var subUl  = panel && panel.querySelector( 'ul.vdeTnd' ); // dropdown list (populated here)
		if ( ! moreLi || ! panel || ! subUl ) {
			return null; // not an overflow menu — leave untouched
		}

		var items = Array.prototype.filter.call( list.children, function ( li ) {
			return li !== moreLi;
		} );

		function reset() {
			items.forEach( function ( li ) {
				li.style.position   = '';
				li.style.visibility = '';
				li.style.left       = '';
			} );
			moreLi.style.display = 'none';
			subUl.innerHTML      = '';
		}

		function layout() {
			reset();

			var avail = list.clientWidth;
			if ( ! avail ) {
				return; // not laid out yet
			}

			// Natural widths with "More" removed from flow.
			var widths = items.map( function ( li ) {
				return li.getBoundingClientRect().width;
			} );
			var total = widths.reduce( function ( a, b ) {
				return a + b;
			}, 0 );

			// Everything fits — no overflow, "More" stays hidden.
			if ( total <= avail + 0.5 ) {
				return;
			}

			moreLi.style.display = '';
			var budget = avail - moreLi.getBoundingClientRect().width;

			var used = 0;
			items.forEach( function ( li, i ) {
				if ( used + widths[ i ] <= budget + 0.5 ) {
					used += widths[ i ];
					return;
				}
				// Collapse: pull out of the inline flow so "More" sits flush
				// after the last visible item, then mirror into the dropdown.
				li.style.position   = 'absolute';
				li.style.visibility = 'hidden';
				li.style.left       = '-99999px';

				var clone = li.cloneNode( true );
				clone.style.position   = '';
				clone.style.visibility = '';
				clone.style.left       = '';
				// "drop" state makes the inlined CSS stack the item vertically.
				var state = ( clone.getAttribute( 'data-state' ) || '' )
					.replace( /\b(header|link)\b/g, '' )
					.replace( /\s+/g, ' ' )
					.trim();
				clone.setAttribute( 'data-state', ( state + ' drop' ).trim() );
				subUl.appendChild( clone );
			} );
		}

		var open = false;
		function show() {
			if ( open ) {
				return;
			}
			open = true;
			// The bar clips its own box (overflow-x:hidden, which forces
			// overflow-y to compute as auto) — lift the clip so the panel can
			// drop below the bar, then align it under the "More" button.
			menu.style.overflow = 'visible';
			panel.style.left = moreLi.offsetLeft + 'px';
			panel.classList.add( 'Z8LNrH' );
			panel.style.visibility = 'visible';
			panel.setAttribute( 'data-dropdown-shown', 'true' );
			moreLi.setAttribute( 'data-dropdown', 'true' );
		}
		function hide() {
			open = false;
			menu.style.overflow = '';
			panel.classList.remove( 'Z8LNrH' );
			panel.style.visibility = '';
			panel.setAttribute( 'data-dropdown-shown', 'false' );
			moreLi.setAttribute( 'data-dropdown', 'false' );
		}

		moreLi.addEventListener( 'mouseenter', show );
		moreLi.addEventListener( 'focusin', show );
		menu.addEventListener( 'mouseleave', hide );
		menu.addEventListener( 'focusout', function ( e ) {
			if ( ! menu.contains( e.relatedTarget ) ) {
				hide();
			}
		} );

		return layout;
	}

	/* ------------------------------------------------------------------ *
	 * Blog search
	 *
	 * The blog header's search field was a Wix JS widget (no form, no name —
	 * it searched via client script). We rebind it to native WordPress search:
	 * pressing Enter in the field, or activating the magnifier button, sends
	 * the visitor to /?s=<query>, which the blog feed chrome renders
	 * (search.php → ndb_blog_feed). On a results page we mirror the active
	 * query back into the field. The markup is left byte-identical; only the
	 * behaviour the design relied on is rebuilt here — exactly as it ran live.
	 * ------------------------------------------------------------------ */
	function setupSearch() {
		var boxes = document.querySelectorAll( '[data-hook="search-input"]' );
		if ( ! boxes.length ) {
			return;
		}

		var current = '';
		try {
			current = new URLSearchParams( window.location.search ).get( 's' ) || '';
		} catch ( e ) {}

		Array.prototype.forEach.call( boxes, function ( box ) {
			var input = box.querySelector( 'input.search-input__input' )
				|| box.querySelector( 'input[type="text"]' );
			if ( ! input ) {
				return;
			}
			// Reflect the active query so the field shows what was searched.
			if ( current && ! input.value ) {
				input.value = current;
			}

			function go() {
				var q = ( input.value || '' ).trim();
				if ( ! q ) {
					input.focus();
					return;
				}
				window.location.href = '/?s=' + encodeURIComponent( q );
			}

			input.addEventListener( 'keydown', function ( e ) {
				if ( 'Enter' === e.key || 13 === e.keyCode ) {
					e.preventDefault();
					go();
				}
			} );

			var btn = box.querySelector( '[role="button"]' );
			if ( btn ) {
				btn.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					go();
				} );
				btn.addEventListener( 'keydown', function ( e ) {
					if ( 'Enter' === e.key || ' ' === e.key || 13 === e.keyCode || 32 === e.keyCode ) {
						e.preventDefault();
						go();
					}
				} );
			}
		} );
	}

	function init() {
		// Restore the active-page highlight before menus collapse, so any
		// cloned overflow items carry the selected state into the dropdown.
		markActiveNav();

		// Rebind the blog search field to native WP search.
		setupSearch();

		var layouts = [];
		Array.prototype.forEach.call(
			document.querySelectorAll( 'wix-dropdown-menu' ),
			function ( menu ) {
				var layout = setupMenu( menu );
				if ( layout ) {
					layouts.push( layout );
				}
			}
		);

		function run() {
			layouts.forEach( function ( fn ) {
				fn();
			} );
		}
		run();

		// Re-flow on resize (debounced) — bind once, recompute many.
		var t;
		window.addEventListener( 'resize', function () {
			clearTimeout( t );
			t = setTimeout( run, 150 );
		} );

		// Recompute once fonts settle (label widths can shift after webfont load).
		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( run );
		}
	}

	if ( 'loading' !== document.readyState ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', init );
	}
})();
