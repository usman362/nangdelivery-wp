<?php
/**
 * Theme setup: supports, menus, image sizes.
 *
 * @package NangDelivery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );

	// WooCommerce support.
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'nangdelivery' ),
		'footer'  => __( 'Footer Menu', 'nangdelivery' ),
	) );
} );

/**
 * Legacy Wix tag-URL redirects.
 *
 * Wix served tag archives at /blog/tags/<slug>; under the /post/ permalink front
 * the WordPress tag base is /post/tag/<slug>/. Old inbound links and indexed
 * search results still point at the Wix paths, so we 301 them to the matching WP
 * tag archive (which renders the same Pro-Gallery feed via archive.php). Internal
 * "Tags:" links already use get_tag_link() and need no help.
 *
 * The raw (still-encoded) request basename is matched first: 213 of the 214 Wix
 * tags share their exact slug (incl. the percent-encoded N₂O subscript). One tag
 * Wix slugged differently (apostrophe) is covered by the alias map; anything else
 * falls back to sanitize_title() and finally a normal 404.
 */
add_action( 'template_redirect', function () {
	if ( is_admin() ) {
		return;
	}
	$path = isset( $_SERVER['REQUEST_URI'] )
		? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH )
		: '';
	if ( '' === $path || 0 !== strpos( $path, '/blog/tags/' ) ) {
		return;
	}

	$slug = preg_replace( '~\.html$~', '', basename( rtrim( $path, '/' ) ) );
	if ( '' === $slug || 'tags' === $slug ) {
		return;
	}

	$alias = array(
		'beginner-s-guide-to-cream-chargers' => 'beginners-guide-to-cream-chargers',
		// HTTrack flattened the N₂O subscript to a dash; the live Wix URL used the
		// %e2%82%82 form (handled by the primary lookup), but accept the flattened
		// form too in case it was ever linked.
		'safe-handling-of-n-o-chargers'      => 'safe-handling-of-n%e2%82%82o-chargers',
	);
	if ( isset( $alias[ $slug ] ) ) {
		$slug = $alias[ $slug ];
	}

	$term = get_term_by( 'slug', $slug, 'post_tag' );
	if ( ! $term ) {
		$term = get_term_by( 'slug', sanitize_title( rawurldecode( $slug ) ), 'post_tag' );
	}
	if ( $term && ! is_wp_error( $term ) ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			wp_safe_redirect( $link, 301 );
			exit;
		}
	}
} );

/**
 * Register widget areas.
 */
add_action( 'widgets_init', function () {
	register_sidebar( array(
		'name'          => __( 'Footer', 'nangdelivery' ),
		'id'            => 'footer-1',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );
} );
