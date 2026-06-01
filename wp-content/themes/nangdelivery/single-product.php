<?php
/**
 * Single product — renders the processed Wix product-page content region for the
 * current product slug. Shared header/footer come from get_header()/get_footer().
 *
 * The processed content is byte-faithful to the approved design; live
 * WooCommerce data (price, options, add-to-cart) is wired client-side so the
 * client edits products from wp-admin without touching markup.
 *
 * @package NangDelivery
 */

get_header();

$ndb_slug = ndb_current_slug();
$ndb_part = ndb_get_part( $ndb_slug, 'content' );

if ( '' !== $ndb_part ) {
	echo $ndb_part; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	if ( function_exists( 'ndb_product_data_script' ) ) {
		ndb_product_data_script();
	}
} else {
	// Fallback to WooCommerce's default single-product content if a slug has no
	// processed part (e.g. a client-added product).
	while ( have_posts() ) {
		the_post();
		wc_get_template_part( 'content', 'single-product' );
	}
}

get_footer();
