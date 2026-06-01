<?php
/**
 * Front-end asset enqueueing.
 *
 * Wix-origin stylesheets/scripts are localised into /assets during Phase 1–2
 * and registered here. Kept modular so each page bundle can be enqueued
 * conditionally.
 *
 * @package NangDelivery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove WordPress / plugin default front-end CSS that would otherwise alter
 * the pixel-perfect Wix layout (block library base styles, global styles,
 * classic-theme normalisation, emoji).
 *
 * Only on processed Wix-design pages: WordPress-native pages (cart, checkout,
 * my-account, privacy/refund, client-authored pages) render in the base shell
 * and legitimately need the WooCommerce block + global styles, so we leave them
 * intact there.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( function_exists( 'ndb_has_processed_part' ) && ! ndb_has_processed_part() ) {
		return; // native page — keep WC/block styles.
	}
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'wc-blocks-style' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}, 100 );

// Disable emoji detection script + styles.
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
} );

// Remove the WP block-editor inline SVG filter / global style noise on front-end.
remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );

add_action( 'wp_enqueue_scripts', function () {
	// Base theme stylesheet (metadata only) — kept registered for child-theme compatibility.
	wp_enqueue_style(
		'ndb-style',
		get_stylesheet_uri(),
		array(),
		NDB_THEME_VERSION
	);

	// Main localised stylesheet (populated in Phase 1–2).
	if ( file_exists( NDB_THEME_DIR . '/assets/css/main.css' ) ) {
		wp_enqueue_style(
			'ndb-main',
			NDB_THEME_URI . '/assets/css/main.css',
			array(),
			filemtime( NDB_THEME_DIR . '/assets/css/main.css' )
		);
	}

	// Main localised script.
	if ( file_exists( NDB_THEME_DIR . '/assets/js/main.js' ) ) {
		wp_enqueue_script(
			'ndb-main',
			NDB_THEME_URI . '/assets/js/main.js',
			array(),
			filemtime( NDB_THEME_DIR . '/assets/js/main.js' ),
			true
		);
	}

	// Delivery Areas archive: re-enable the "Filter by Suburb" dropdown as
	// progressive enhancement (transparent native <select> overlay — the approved
	// closed appearance is untouched; see assets/js/ndb-suburb-filter.js).
	if ( is_post_type_archive( 'delivery_area' ) && file_exists( NDB_THEME_DIR . '/assets/js/ndb-suburb-filter.js' ) ) {
		wp_enqueue_script(
			'ndb-suburb-filter',
			NDB_THEME_URI . '/assets/js/ndb-suburb-filter.js',
			array(),
			filemtime( NDB_THEME_DIR . '/assets/js/ndb-suburb-filter.js' ),
			true
		);

		$ndb_areas = get_posts(
			array(
				'post_type'        => 'delivery_area',
				'posts_per_page'   => -1,
				'post_status'      => 'publish',
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);
		$ndb_suburbs = array();
		foreach ( $ndb_areas as $ndb_area ) {
			$ndb_suburbs[] = array(
				'label' => get_the_title( $ndb_area ),
				// Root-relative for domain portability (consistent with the rest of
				// the theme); wp_make_link_relative drops the scheme+host.
				'url'   => wp_make_link_relative( get_permalink( $ndb_area ) ),
			);
		}
		wp_localize_script( 'ndb-suburb-filter', 'ndbSuburbs', $ndb_suburbs );
	}
} );
