/**
 * Nang Delivery Brisbane — WooCommerce cart wiring (Phase 5c).
 *
 * The product markup is the byte-faithful Wix design rendered server-side; Wix's
 * own runtime is gone, so this file rebuilds the small amount of behaviour the
 * approved design implies and connects it to live WooCommerce:
 *
 *   - Cart icon  : link to the cart page + a count badge (shown only when > 0,
 *                  so the approved empty-cart header is pixel-identical).
 *   - Quantity   : the +/- stepper on the single-product page.
 *   - Options    : the variation dropdown (populated from WooCommerce variations).
 *   - Add to Cart / Buy Now : add the product (or selected variation) × quantity
 *                  via a custom wc-ajax endpoint; Buy Now then goes to checkout.
 *   - Gallery    : each shop card's "Add to Cart" (simple → ajax add; variable →
 *                  open the product page so the shopper can choose an option).
 *
 * Config arrives in window.ndbCartCfg (localised); the single-product page also
 * emits window.ndbProduct with that product's id/type/price/variations.
 *
 * @package NangDelivery
 */
( function () {
	'use strict';

	var cfg = window.ndbCartCfg || {};
	var product = window.ndbProduct || null;
	var selected = null; // currently chosen variation (variable products)

	/* ------------------------------------------------------------------ *
	 * Helpers
	 * ------------------------------------------------------------------ */

	// Replace an element's leading text while preserving child nodes (e.g. the
	// screen-reader-only <span> that follows a price value).
	function setLeadingText( el, text ) {
		if ( ! el ) {
			return;
		}
		for ( var i = 0; i < el.childNodes.length; i++ ) {
			if ( 3 === el.childNodes[ i ].nodeType ) {
				el.childNodes[ i ].nodeValue = text;
				return;
			}
		}
		el.insertBefore( document.createTextNode( text ), el.firstChild );
	}

	function setBusy( btn, busy ) {
		if ( ! btn ) {
			return;
		}
		btn.setAttribute( 'aria-busy', busy ? 'true' : 'false' );
		btn.style.pointerEvents = busy ? 'none' : '';
		btn.style.opacity = busy ? '0.7' : '';
	}

	// Briefly confirm an add on the clicked button without leaving the page.
	function flashAdded( btn ) {
		var span = btn && btn.querySelector( 'span' );
		if ( ! span ) {
			return;
		}
		if ( null == btn.getAttribute( 'data-ndb-label' ) ) {
			btn.setAttribute( 'data-ndb-label', span.textContent );
		}
		span.textContent = 'Added ✓';
		clearTimeout( btn._ndbT );
		btn._ndbT = setTimeout( function () {
			span.textContent = btn.getAttribute( 'data-ndb-label' );
		}, 1800 );
	}

	/* ------------------------------------------------------------------ *
	 * Cart icon + count badge (every page)
	 * ------------------------------------------------------------------ */
	function cartLink() {
		return document.querySelector( 'a[data-hook="cart-icon-button"]' );
	}

	function ensureBadge() {
		var cart = cartLink();
		if ( ! cart ) {
			return null;
		}
		if ( cfg.cartUrl && ! cart.getAttribute( 'href' ) ) {
			cart.setAttribute( 'href', cfg.cartUrl );
		}
		var badge = cart.querySelector( '.ndb-cart-count' );
		if ( ! badge ) {
			badge = document.createElement( 'span' );
			badge.className = 'ndb-cart-count';
			cart.appendChild( badge );
		}
		return badge;
	}

	function updateBadge( count ) {
		var badge = ensureBadge();
		if ( ! badge ) {
			return;
		}
		count = count || 0;
		badge.textContent = count;
		badge.style.display = count > 0 ? '' : 'none';
	}

	/* ------------------------------------------------------------------ *
	 * Add to cart (custom wc-ajax endpoint — supports variations)
	 * ------------------------------------------------------------------ */
	function addToCart( productId, qty, variationId, variation ) {
		var body = new URLSearchParams();
		body.set( 'product_id', productId );
		body.set( 'quantity', qty );
		if ( variationId ) {
			body.set( 'variation_id', variationId );
		}
		if ( variation ) {
			Object.keys( variation ).forEach( function ( k ) {
				body.set( 'variation[' + k + ']', variation[ k ] );
			} );
		}
		return fetch( cfg.addUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} ).then( function ( r ) {
			return r.json();
		} ).then( function ( data ) {
			if ( data && data.success ) {
				updateBadge( data.count );
			}
			return data;
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Single-product page wiring
	 * ------------------------------------------------------------------ */
	function scope() {
		return document.querySelector( '[data-hook="product-page"]' );
	}

	function setupQuantity( root ) {
		var counter = root.querySelector( '[data-hook="product-quantity-counter"]' );
		if ( ! counter ) {
			return;
		}
		var input = counter.querySelector( 'input[type="number"]' );
		var minus = counter.querySelector( '[data-hook="counter-minus-button"]' );
		var plus = counter.querySelector( '[data-hook="counter-plus-button"]' );
		if ( ! input ) {
			return;
		}
		var min = parseInt( input.getAttribute( 'min' ), 10 ) || 1;
		var max = parseInt( input.getAttribute( 'max' ), 10 ) || 99999;

		function get() {
			return Math.max( min, parseInt( input.value, 10 ) || min );
		}
		function set( n ) {
			n = Math.min( max, Math.max( min, n ) );
			input.value = n;
			input.setAttribute( 'aria-valuenow', n );
			input.setAttribute( 'aria-valuetext', n );
			if ( minus ) {
				var atMin = n <= min;
				minus.disabled = atMin;
				minus.classList.toggle( 'oJ734Ml--disabled', atMin );
			}
		}
		if ( minus ) {
			minus.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				set( get() - 1 );
			} );
		}
		if ( plus ) {
			plus.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				set( get() + 1 );
			} );
		}
		input.addEventListener( 'change', function () {
			set( get() );
		} );
		set( get() );
	}

	function getQty( root ) {
		var input = root.querySelector( '[data-hook="product-quantity-counter"] input[type="number"]' );
		return input ? Math.max( 1, parseInt( input.value, 10 ) || 1 ) : 1;
	}

	function setMainPrice( root, text ) {
		setLeadingText(
			root.querySelector( '[data-hook="price-range-from"]' ) ||
				root.querySelector( '[data-hook="formatted-primary-price"]' ),
			text
		);
	}

	function clearDropdownError( base ) {
		if ( base ) {
			base.setAttribute( 'aria-invalid', 'false' );
			base.setAttribute( 'data-dropdown-base-error', 'false' );
		}
	}

	function showDropdownError( root ) {
		var base = root.querySelector( '[data-hook="dropdown-base"]' );
		if ( base ) {
			base.setAttribute( 'aria-invalid', 'true' );
			base.setAttribute( 'data-dropdown-base-error', 'true' );
			base.focus();
			base.scrollIntoView( { block: 'center', behavior: 'smooth' } );
		}
	}

	function setupDropdown( root ) {
		if ( ! product || 'variable' !== product.type || ! product.variations ) {
			return;
		}
		var base = root.querySelector( '[data-hook="dropdown-base"]' );
		if ( ! base ) {
			return;
		}
		var baseText = base.querySelector( '[data-hook="dropdown-base-text"]' );
		var host = base.closest( '[data-hook="popover-element"]' ) || base.parentNode;
		host.classList.add( 'ndb-dd-host' );

		var pop = document.createElement( 'ul' );
		pop.className = 'ndb-dd-popover';
		pop.setAttribute( 'role', 'listbox' );
		pop.hidden = true;

		product.variations.forEach( function ( v ) {
			var li = document.createElement( 'li' );
			li.className = 'ndb-dd-option';
			li.setAttribute( 'role', 'option' );
			li.tabIndex = -1;
			li.textContent = v.label;
			li.addEventListener( 'click', function () {
				selected = v;
				if ( baseText ) {
					baseText.textContent = v.label;
				}
				// Drop the placeholder appearance once a value is chosen.
				base.className = base.className.replace( /\b\S*--placeholder\b/g, '' ).replace( /\s+/g, ' ' ).trim();
				setMainPrice( root, v.price );
				clearDropdownError( base );
				close();
			} );
			pop.appendChild( li );
		} );
		host.appendChild( pop );

		function open() {
			pop.hidden = false;
			base.setAttribute( 'aria-expanded', 'true' );
		}
		function close() {
			pop.hidden = true;
			base.setAttribute( 'aria-expanded', 'false' );
		}
		base.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			if ( pop.hidden ) {
				open();
			} else {
				close();
			}
		} );
		document.addEventListener( 'click', function ( e ) {
			if ( ! host.contains( e.target ) ) {
				close();
			}
		} );
	}

	function setupSingleProduct() {
		if ( ! product ) {
			return;
		}
		var root = scope();
		if ( ! root ) {
			return;
		}
		setupQuantity( root );
		setupDropdown( root );

		function handle( redirect, btn ) {
			if ( 'variable' === product.type && ! selected ) {
				showDropdownError( root );
				return;
			}
			setBusy( btn, true );
			addToCart(
				product.id,
				getQty( root ),
				selected ? selected.id : 0,
				selected ? selected.attributes : null
			).then( function ( data ) {
				setBusy( btn, false );
				if ( data && data.success ) {
					if ( redirect && cfg.checkoutUrl ) {
						window.location.href = cfg.checkoutUrl;
					} else {
						flashAdded( btn );
					}
				}
			} ).catch( function () {
				setBusy( btn, false );
			} );
		}

		var atc = root.querySelector( '[data-hook="add-to-cart"]' );
		var buy = root.querySelector( '[data-hook="buy-now-button"]' );
		if ( atc ) {
			atc.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				handle( false, atc );
			} );
		}
		if ( buy ) {
			buy.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				handle( true, buy );
			} );
		}
	}

	/* ------------------------------------------------------------------ *
	 * Shop gallery cards
	 * ------------------------------------------------------------------ */
	function setupGallery() {
		Array.prototype.forEach.call(
			document.querySelectorAll( 'button[data-ndb-id]' ),
			function ( btn ) {
				btn.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					e.stopPropagation();
					var type = btn.getAttribute( 'data-ndb-type' );
					var url = btn.getAttribute( 'data-ndb-url' );
					if ( 'variable' === type ) {
						if ( url ) {
							window.location.href = url;
						}
						return;
					}
					var id = parseInt( btn.getAttribute( 'data-ndb-id' ), 10 );
					if ( ! id ) {
						return;
					}
					setBusy( btn, true );
					addToCart( id, 1, 0, null ).then( function ( data ) {
						setBusy( btn, false );
						if ( data && data.success ) {
							flashAdded( btn );
						}
					} ).catch( function () {
						setBusy( btn, false );
					} );
				} );
			}
		);
	}

	/* ------------------------------------------------------------------ */
	function init() {
		updateBadge( cfg.count );
		setupSingleProduct();
		setupGallery();
	}

	if ( 'loading' !== document.readyState ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', init );
	}
} )();
