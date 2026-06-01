<?php
/**
 * WooCommerce integration — render the shop gallery and single-product page with
 * the exact same markup/CSS classes the approved Wix design shipped, but driven
 * by live WooCommerce product data so the client edits everything from wp-admin.
 *
 * The gallery card markup lives in page-parts/_product-card.tpl.html (a verbatim
 * Wix gallery card with @@TOKENS@@). We fill the tokens per product, so the only
 * things that change between the static design and our output are the genuinely
 * dynamic fields (name, price, image, ribbon, link) — framing/classes are byte
 * identical, satisfying the "no UI change" constraint while staying editable.
 *
 * @package NangDelivery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The approved gallery order (Wix layout), by WooCommerce product slug.
 * Products not listed here are appended in WooCommerce's default order so a
 * client-added product still surfaces.
 */
function ndb_shop_order() {
	return array(
		'3-3l-miami-magic-1l-canister',
		'magic-miami-33l-2050g',
		'magic-miami-4-5l-3060g',
		'2-x-3-3l-miami-magic-1l-infusion-max-canister',
		'2-x-3-3l-miami-magic-infusions-2048g-n2o-cannister',
		'300-cream-chargers-30-x-10-pack',
		'6-x-infusion-max-580g-cylinder-n2o',
		'infusion-tank-pressure-release-nozzle',
		'4-5l-miami-magic-infusions-3060g-n2o-cannister',
		'discountchargers',
		'supremewhip-cream-chargers-50-pack',
		'nang-cream-whipper-500-ml',
		'balloons',
	);
}

/** Gallery money format, matching the Wix gallery ("A$145.00"). */
function ndb_gallery_price( $amount ) {
	return 'A$' . number_format( (float) $amount, 2, '.', ',' );
}

/**
 * Products to display in the gallery, in the approved order, followed by any
 * extra published products the client may have added later.
 *
 * @return WC_Product[]
 */
function ndb_shop_products() {
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		)
	);

	$by_slug = array();
	foreach ( $ids as $id ) {
		$by_slug[ get_post_field( 'post_name', $id ) ] = $id;
	}

	$ordered = array();
	foreach ( ndb_shop_order() as $slug ) {
		if ( isset( $by_slug[ $slug ] ) ) {
			$ordered[] = $by_slug[ $slug ];
			unset( $by_slug[ $slug ] );
		}
	}
	foreach ( $by_slug as $id ) {     // client-added extras, default order
		$ordered[] = $id;
	}

	$products = array();
	foreach ( $ordered as $id ) {
		$p = wc_get_product( $id );
		if ( $p && $p->is_visible() ) {
			$products[] = $p;
		}
	}
	return $products;
}

/** Build the gallery price block (3 Wix variants) for a product. */
function ndb_gallery_price_block( $product ) {
	if ( $product->is_type( 'variable' ) ) {
		$from = wc_get_price_to_display( $product, array( 'price' => $product->get_variation_price( 'min', true ) ) );
		return '<div class="UqnnNN briESr z3Ybtk" data-hook="prices-container">'
			. '<span class="iI5avH" data-hook="st-price-range">Price</span>'
			. '<span data-hook="price-range-from" class="WuSRvG">From ' . esc_html( ndb_gallery_price( $from ) ) . '</span>'
			. '</div>';
	}

	$regular = (float) $product->get_regular_price();
	$sale    = $product->get_sale_price();
	if ( '' !== $sale && null !== $sale && (float) $sale < $regular ) {
		return '<div class="UqnnNN briESr z3Ybtk" data-hook="prices-container">'
			. '<span class="iI5avH" data-hook="sr-product-item-price-before-discount">Regular Price</span>'
			. '<span data-hook="product-item-price-before-discount" class="DlHYV3" data-wix-original-price="' . esc_attr( ndb_gallery_price( $regular ) ) . '">' . esc_html( ndb_gallery_price( $regular ) ) . '</span>'
			. '<span class="iI5avH" data-hook="sr-product-item-price-to-pay">Sale Price</span>'
			. '<span data-hook="product-item-price-to-pay" class="e6onIk" data-wix-price="' . esc_attr( ndb_gallery_price( $sale ) ) . '">' . esc_html( ndb_gallery_price( $sale ) ) . '</span>'
			. '</div>';
	}

	$price = wc_get_price_to_display( $product );
	return '<div class="UqnnNN briESr z3Ybtk" data-hook="prices-container">'
		. '<span class="iI5avH" data-hook="sr-product-item-price-to-pay">Price</span>'
		. '<span data-hook="product-item-price-to-pay" class="cfpn1d" data-wix-price="' . esc_attr( ndb_gallery_price( $price ) ) . '">' . esc_html( ndb_gallery_price( $price ) ) . '</span>'
		. '</div>';
}

/**
 * The gallery <wow-image> image block tokens for a product's featured image.
 * Dimensions come from the actual uploaded attachment (so the aspect-ratio box
 * is real WP data), and the <img src> is the sharp featured image.
 *
 * @return array{src:string,w:int,h:int,info:string}
 */
function ndb_gallery_image( $product ) {
	$tid = $product->get_image_id();
	$src = $tid ? wp_get_attachment_image_url( $tid, 'full' ) : wc_placeholder_img_src( 'full' );
	$w   = 500;
	$h   = 500;
	if ( $tid ) {
		$meta = wp_get_attachment_metadata( $tid );
		if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
			$w = (int) $meta['width'];
			$h = (int) $meta['height'];
		}
	}
	$base = $tid ? wp_basename( get_attached_file( $tid ) ) : 'product.png';
	$info = wp_json_encode(
		array(
			'displayMode'   => 'fit',
			'isLQIP'        => false,
			'isSEOBot'      => false,
			'lqipTransition' => null,
			'encoding'      => 'AVIF',
			'imageData'     => array(
				'width'       => $w,
				'height'      => $h,
				'uri'         => $base,
				'name'        => $base,
				'displayMode' => 'fit',
			),
		)
	);
	return array(
		'src'  => $src,
		'w'    => $w,
		'h'    => $h,
		'info' => htmlspecialchars( $info, ENT_QUOTES, 'UTF-8' ),
	);
}

/**
 * The variation dropdown's label (the attribute used for variations), e.g.
 * "How Many Tanks?". Empty for simple products.
 */
function ndb_variation_option_title( $product ) {
	if ( ! $product->is_type( 'variable' ) ) {
		return '';
	}
	foreach ( $product->get_attributes() as $attr ) {
		if ( $attr->get_variation() ) {
			return wc_attribute_label( $attr->get_name(), $product );
		}
	}
	return '';
}

/**
 * The textual-options block for a gallery card. For variable products this is
 * the Wix "choose an option" dropdown (closed state, showing the option title),
 * byte-faithful to the approved design. Empty for simple products.
 */
function ndb_gallery_options_block( $product ) {
	$title = ndb_variation_option_title( $product );
	if ( '' === $title ) {
		return '';
	}
	$pid    = (int) $product->get_id();
	$lbl_id = 'ndb-dd-label-' . $pid;
	$lst_id = 'ndb-dd-list-' . $pid;
	$t      = esc_html( $title );
	$ta     = esc_attr( $title );

	return '<div class="sX06Sd" data-hook="product-option-wrapper"><div data-hook="product-option" class="RG4yol"><div class="ss2MNkY oZVvxVf---alignment-6-bottom oZVvxVf---theme-3-box TPASection_j7al14w5 sFDXqR1 oLtge_3--separateStyles r65Mih" data-hook="product-options-dropdown"><div data-content-hook="popover-content-core-dropdown-undefined" class="sLcfw0L spE86SV ss2MNkY oZVvxVf---alignment-6-bottom oZVvxVf---theme-3-box sVMRt0M" data-hook="core-dropdown"><div class="sn9u5Tm" data-hook="popover-element"><button data-fullwidth="true" data-hook="dropdown-base" style="--wix-ui-tpa-button-font-size-default:16px;--wix-ui-tpa-button-line-height-default:1.5em" aria-expanded="false" id="' . esc_attr( $lbl_id ) . '" aria-label="' . $ta . '" role="combobox" aria-controls="' . esc_attr( $lst_id ) . '" type="button" class="sU1xPae sinOqXC sFUsTpJ oXzJJV7--fullWidth oXzJJV7---paddingMode-6-legacy oXzJJV7---hoverStyle-9-underline shuRjCB oTywvXs--placeholder s__2ypz8B slx1mTs" data-focusable-focus="false" data-focusable-focus-visible="false" tabindex="0"><span class="sAVMMAG sFMUfOY"><div class="sxXra36" data-hook="dropdown-base-text">' . $t . '</div></span><span class="siXgHWL sVo99F4 sEpngTD" data-hook="suffix-icon"><svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24" class="spfZZbp"><path fill-rule="evenodd" d="M18.2546728,8.18171329 L18.9617796,8.88882007 L12.5952867,15.2537133 L12.5978964,15.2558012 L11.8907896,15.962908 L11.8882867,15.9607133 L11.8874628,15.9617796 L11.180356,15.2546728 L11.1812867,15.2527133 L4.81828671,8.88882007 L5.52539349,8.18171329 L11.8882867,14.5457133 L18.2546728,8.18171329 Z"></path></svg></span></button></div></div></div></div></div>';
}

/** Render a single gallery card for a product. */
function ndb_render_product_card( $product ) {
	static $tpl = null;
	if ( null === $tpl ) {
		$tpl = (string) @file_get_contents( ndb_parts_dir() . '/_product-card.tpl.html' );
	}
	if ( '' === $tpl ) {
		return '';
	}

	$name   = $product->get_name();
	$slug   = $product->get_slug();
	$href   = get_permalink( $product->get_id() );
	$ribbon = (string) get_post_meta( $product->get_id(), '_ndb_ribbon', true );
	$img    = ndb_gallery_image( $product );

	$aria = '' !== $ribbon
		? sprintf( '%s. %s gallery', $name, $ribbon )
		: sprintf( '%s gallery', $name );

	$ribbon_html = '' !== $ribbon
		? '<div class="MbIdEx" style="display:var(--shouldShowRibbonOnImage-display, inherit)"><div class="INg0tB FbHYze" data-hook="RibbonDataHook.RibbonOnImage">' . esc_html( $ribbon ) . '</div></div>'
		: '';

	$repl = array(
		'@@SLUG@@'   => esc_attr( $slug ),
		'@@ARIA@@'   => esc_attr( $aria ),
		'@@HREF@@'   => esc_url( $href ),
		'@@SW@@'     => (int) $img['w'],
		'@@SH@@'     => (int) $img['h'],
		'@@INFO@@'   => $img['info'],
		'@@SRC@@'    => esc_url( $img['src'] ),
		'@@RIBBON@@' => $ribbon_html,
		'@@NAME@@'   => esc_html( $name ),
		'@@PRICE@@'  => ndb_gallery_price_block( $product ),
		'@@OPTIONS@@' => ndb_gallery_options_block( $product ),
		'@@PID@@'    => (int) $product->get_id(),
		'@@PTYPE@@'  => esc_attr( $product->get_type() ),
	);

	return strtr( $tpl, $repl );
}

/**
 * Render the full shop content region: the verbatim Wix gallery chrome from
 * page-parts/shop.content.html with the product-list <ul> inner cards replaced
 * by live WooCommerce-rendered cards (approved order).
 */
function ndb_render_shop_gallery() {
	$content = ndb_get_part( 'shop', 'content' );
	if ( '' === $content ) {
		return '';
	}

	$anchor = 'data-hook="product-list-wrapper"';
	$pos    = strpos( $content, $anchor );
	if ( false === $pos ) {
		return $content; // structure changed; output as-is rather than break.
	}
	$ul_open_end = strpos( $content, '>', $pos );
	$ul_close    = strpos( $content, '</ul>', $ul_open_end );
	if ( false === $ul_open_end || false === $ul_close ) {
		return $content;
	}

	$cards = '';
	foreach ( ndb_shop_products() as $product ) {
		$cards .= ndb_render_product_card( $product );
	}

	return substr( $content, 0, $ul_open_end + 1 ) . $cards . substr( $content, $ul_close );
}

/* ==========================================================================
 * Cart wiring (Phase 5c) — connect the byte-faithful Wix controls to live
 * WooCommerce: add-to-cart, variation options, quantity, and the cart-icon
 * count badge. The visual layer is unchanged; only behaviour is added.
 * ======================================================================== */

/** Current cart item count (0 when the cart/session isn't available yet). */
function ndb_cart_count() {
	return ( function_exists( 'WC' ) && WC() && WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0;
}

/**
 * The per-product JS payload consumed by assets/js/cart.js on the single
 * product page. Variable products ship their purchasable variations (id, label,
 * display price, and the attribute map needed to add the exact variation).
 *
 * @return array
 */
function ndb_product_js_data( $product ) {
	$data = array(
		'id'   => (int) $product->get_id(),
		'type' => $product->get_type(),
	);

	if ( $product->is_type( 'variable' ) ) {
		$variations = array();
		foreach ( $product->get_available_variations() as $v ) {
			if ( isset( $v['is_purchasable'] ) && ! $v['is_purchasable'] ) {
				continue;
			}
			$values = array_filter( array_map( 'wc_clean', (array) $v['attributes'] ) );
			$variations[] = array(
				'id'         => (int) $v['variation_id'],
				'label'      => trim( implode( ' / ', $values ) ),
				'price'      => ndb_gallery_price( $v['display_price'] ),
				'attributes' => $v['attributes'],
			);
		}
		$data['variations'] = $variations;
		$data['optionName'] = ndb_variation_option_title( $product );
	} else {
		$data['price'] = ndb_gallery_price( wc_get_price_to_display( $product ) );
	}

	return $data;
}

/**
 * Emit window.ndbProduct for the current single product (called by
 * single-product.php for the processed-part branch).
 */
function ndb_product_data_script() {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product ) {
		return;
	}
	echo '<script id="ndb-product-data">window.ndbProduct=' . wp_json_encode( ndb_product_js_data( $product ) ) . ';</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Bare-slug product URL fallback.
 *
 * The approved Wix markup links related products (and a few other in-content
 * product references) by BARE slug — e.g. /supremewhip-cream-chargers-50-pack/ —
 * because Wix's (now-stripped) client runtime resolved those to the store route
 * at click time. WooCommerce serves products under /product-page/<slug>/, so a
 * bare-slug request 404s (the header renders, the body is empty).
 *
 * Rather than touch the approved markup, we restore Wix's old behaviour on the
 * server: when a request 404s on a single-segment /<slug>/ that matches a
 * PUBLISHED product, 301 to that product's canonical permalink. This branch only
 * runs on a URL that is ALREADY a dead 404, so nothing that currently resolves
 * (real pages, posts, suburbs, the shop) is affected, and no design changes.
 */
add_action( 'template_redirect', 'ndb_redirect_bare_product_slug', 1 );
function ndb_redirect_bare_product_slug() {
	if ( ! is_404() ) {
		return;
	}
	$path = isset( $_SERVER['REQUEST_URI'] )
		? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH )
		: '';
	$path = trim( $path, '/' );
	// Only the single-segment bare-slug shape Wix produced (e.g. "/Balloons/");
	// never interfere with deeper paths.
	if ( '' === $path || false !== strpos( $path, '/' ) ) {
		return;
	}
	$slug = sanitize_title( rawurldecode( $path ) ); // normalises case, e.g. "Balloons" → "balloons".
	if ( '' === $slug ) {
		return;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'name'           => $slug,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	if ( ! empty( $ids ) ) {
		wp_safe_redirect( get_permalink( $ids[0] ), 301 );
		exit;
	}
}

/**
 * Custom add-to-cart endpoint (?wc-ajax=ndb_add_to_cart). Unlike WooCommerce's
 * bundled add_to_cart ajax, this accepts a variation id + attributes, so a
 * single endpoint serves both simple and variable products.
 */
add_action( 'wc_ajax_ndb_add_to_cart', 'ndb_ajax_add_to_cart' );
function ndb_ajax_add_to_cart() {
	$product_id   = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$quantity     = isset( $_POST['quantity'] ) ? max( 1, (int) wp_unslash( $_POST['quantity'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
	$variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$variation    = ( isset( $_POST['variation'] ) && is_array( $_POST['variation'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
		? wc_clean( wp_unslash( $_POST['variation'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
		: array();

	if ( ! $product_id ) {
		wp_send_json( array( 'success' => false, 'message' => __( 'Missing product.', 'nangdelivery' ) ) );
	}

	$passed = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation );
	$added  = $passed ? WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation ) : false;

	if ( $added ) {
		wp_send_json(
			array(
				'success'   => true,
				'count'     => ndb_cart_count(),
				'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array() ),
			)
		);
	}

	$notices = function_exists( 'wc_get_notices' ) ? wc_get_notices( 'error' ) : array();
	if ( function_exists( 'wc_clear_notices' ) ) {
		wc_clear_notices();
	}
	wp_send_json(
		array(
			'success' => false,
			'message' => ! empty( $notices ) ? wp_strip_all_tags( $notices[0]['notice'] ) : __( 'Could not add to cart.', 'nangdelivery' ),
		)
	);
}

/**
 * Expose the cart count to WooCommerce's fragment-refresh mechanism so any
 * add-to-cart path keeps our badge in sync.
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'ndb_cart_count_fragment' );
function ndb_cart_count_fragment( $fragments ) {
	$count = ndb_cart_count();
	$fragments['span.ndb-cart-count'] = '<span class="ndb-cart-count"' . ( $count > 0 ? '' : ' style="display:none"' ) . '>' . esc_html( $count ) . '</span>';
	return $fragments;
}

/**
 * Enqueue the cart behaviour script + its localized config, plus the tiny CSS
 * for the count badge and the variation dropdown popover. Runs late (priority
 * 20) so the Wix bundle ('ndb-main') is already registered for the inline CSS.
 */
add_action( 'wp_enqueue_scripts', 'ndb_enqueue_cart_assets', 20 );
function ndb_enqueue_cart_assets() {
	$path = NDB_THEME_DIR . '/assets/js/cart.js';
	if ( ! file_exists( $path ) ) {
		return;
	}

	wp_enqueue_script( 'ndb-cart', NDB_THEME_URI . '/assets/js/cart.js', array(), (string) filemtime( $path ), true );
	wp_localize_script(
		'ndb-cart',
		'ndbCartCfg',
		array(
			'addUrl'      => class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( 'ndb_add_to_cart' ) : '',
			'cartUrl'     => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
			'checkoutUrl' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
			'count'       => ndb_cart_count(),
		)
	);

	$css_handle = wp_style_is( 'ndb-main', 'enqueued' ) ? 'ndb-main' : 'ndb-style';
	wp_add_inline_style( $css_handle, ndb_cart_inline_css() );
}

/** Minimal styles: cart-count badge (matches the coral cart icon) + dropdown popover. */
function ndb_cart_inline_css() {
	return implode(
		'',
		array(
			'a[data-hook="cart-icon-button"]{position:relative!important;overflow:visible!important}',
			'.ndb-cart-count{position:absolute;top:-7px;right:-9px;min-width:17px;height:17px;padding:0 4px;border-radius:9px;background:rgb(245,78,88);color:#fff;font-size:11px;line-height:17px;font-weight:700;text-align:center;box-sizing:border-box;pointer-events:none;z-index:2}',
			'.ndb-dd-host{position:relative}',
			'.ndb-dd-popover{position:absolute;left:0;right:0;top:100%;z-index:1000;margin:4px 0 0;padding:6px 0;list-style:none;background:#fff;border:1px solid #e0e0e0;box-shadow:0 6px 20px rgba(0,0,0,.14);max-height:280px;overflow-y:auto;font:inherit}',
			'.ndb-dd-option{padding:11px 16px;cursor:pointer;font-size:16px;line-height:1.4;color:#162d3d}',
			'.ndb-dd-option:hover,.ndb-dd-option:focus{background:#f4f4f4;outline:none}',
			// WordPress-native pages (cart/checkout/account/etc.) rendered in the base shell.
			'.ndb-native-main{display:block;width:100%;box-sizing:border-box;background:#fff}',
			'.ndb-native-page{max-width:1180px;margin:0 auto;padding:56px 24px 80px;box-sizing:border-box;color:#162d3d;font-size:16px;line-height:1.6}',
			'.ndb-native-page a{color:rgb(245,78,88)}',
			'.ndb-native-page h1,.ndb-native-page h2,.ndb-native-page h3{color:#162d3d}',
		)
	);
}
