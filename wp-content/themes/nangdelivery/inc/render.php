<?php
/**
 * Rendering helpers for processed Wix page artifacts.
 *
 * Processed fragments live in /page-parts/<slug>.{head,body}.html and use the
 * token %%THEME%% wherever a theme asset URL is needed. We resolve that token
 * to the theme URI at output time.
 *
 * @package NangDelivery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ndb_parts_dir() {
	return NDB_THEME_DIR . '/page-parts';
}

/**
 * Resolve build-time tokens in a fragment.
 *
 *   %%THEME%%  → theme URI (asset URLs)
 *   %%PAGEID%% → the current page's Wix page-id suffix (shared header only)
 */
function ndb_resolve_tokens( $html, $slug = '' ) {
	$html = str_replace( '%%THEME%%', NDB_THEME_URI, $html );
	if ( false !== strpos( $html, '%%PAGEID%%' ) ) {
		$html = str_replace( '%%PAGEID%%', ndb_page_id( $slug ), $html );
	}
	return $html;
}

/**
 * Load a processed fragment for a slug/part and resolve tokens.
 *
 * @param string $slug e.g. "home"
 * @param string $part "head" | "content" | "body"
 * @return string Resolved HTML (empty string if missing).
 */
function ndb_get_part( $slug, $part ) {
	$file = ndb_parts_dir() . "/{$slug}.{$part}.html";
	if ( ! file_exists( $file ) ) {
		return '';
	}
	return ndb_resolve_tokens( file_get_contents( $file ), $slug );
}

/**
 * Echo a processed fragment (already-escaped Wix markup — output verbatim).
 */
function ndb_the_part( $slug, $part ) {
	echo ndb_get_part( $slug, $part ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * A page's processed fragment, or the shared base shell when the slug has no
 * processed part. This keeps WordPress-native pages (cart, checkout, my-account,
 * privacy/refund, and any client-authored page) inside the same Wix chrome —
 * the base shell carries the shared header/footer/master CSS and opens the
 * #SITE_CONTAINER…#masterPage wrappers the footer expects to close.
 *
 * @param string $slug current page slug
 * @param string $part "head" | "prefix"
 */
function ndb_get_part_or_base( $slug, $part ) {
	$html = ndb_get_part( $slug, $part );
	if ( '' !== $html ) {
		return $html;
	}
	return ndb_get_shared( '_base.' . $part, $slug );
}

function ndb_the_part_or_base( $slug, $part ) {
	echo ndb_get_part_or_base( $slug, $part ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Whether the current page is rendered from a processed Wix part (true) or is a
 * WordPress-native page using the base shell (false). Used by templates to wrap
 * native content in a layout container.
 */
function ndb_has_processed_part( $slug = '' ) {
	if ( '' === $slug ) {
		$slug = ndb_current_slug();
	}
	return '' !== ndb_get_part( $slug, 'content' );
}

/**
 * Load a shared partial (_header / _footer) resolved for the current slug.
 * The shared markup is identical across pages bar the %%PAGEID%% token.
 *
 * @param string $name "_header" | "_footer"
 * @param string $slug current page slug (for %%PAGEID%%)
 */
function ndb_get_shared( $name, $slug ) {
	$file = ndb_parts_dir() . "/{$name}.html";
	if ( ! file_exists( $file ) ) {
		return '';
	}
	$html = ndb_resolve_tokens( file_get_contents( $file ), $slug );
	/**
	 * Shared partials (header/footer/base shell) pass through the same dynamic
	 * token filter as page content, so footer-embedded plumbing (e.g. the
	 * newsletter form wired by inc/forms.php) resolves on every page — including
	 * WordPress-native pages that never call ndb_render_page_content().
	 */
	return apply_filters( 'ndb_shared_html', $html, $name, $slug );
}

function ndb_the_shared( $name, $slug ) {
	echo ndb_get_shared( $name, $slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * The Wix per-page id suffix for a slug (read from the part's meta.json).
 * Cached per request.
 */
function ndb_page_id( $slug ) {
	static $cache = array();
	if ( ! $slug ) {
		return '';
	}
	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}
	$pid  = '';
	$file = ndb_parts_dir() . "/{$slug}.meta.json";
	if ( file_exists( $file ) ) {
		$meta = json_decode( file_get_contents( $file ), true );
		if ( is_array( $meta ) && ! empty( $meta['pageid'] ) ) {
			$pid = $meta['pageid'];
		}
	}
	return $cache[ $slug ] = $pid;
}

/**
 * Render the current blog post inside the processed Wix post chrome.
 *
 * Loads the shared _post.content.html chrome (tokens already resolved for
 * %%THEME%%/%%PAGEID%%) and fills the per-post tokens from the WP loop:
 *   %%POST_TITLE%%  %%AUTHOR_NAME%%  %%AUTHOR_URL%%  %%POST_DATE%%
 *   %%READ_TIME%%   %%POST_BODY%%   (the_content)
 *
 * Each post is a real, editable WP post; the chrome keeps the approved design.
 */
function ndb_render_post_content() {
	$tpl = ndb_get_shared( '_post.content', 'post' );
	if ( '' === $tpl ) {
		the_content();
		return;
	}

	$id       = get_the_ID();
	$author   = get_post_meta( $id, '_ndb_author', true );
	if ( '' === $author ) {
		$author = get_the_author();
	}
	$readtime = get_post_meta( $id, '_ndb_readtime', true );
	$date     = get_the_date( 'M j, Y' );

	ob_start();
	the_content();
	$body = ob_get_clean();

	$repl = array(
		'%%POST_TITLE%%'   => esc_html( get_the_title() ),
		'%%AUTHOR_NAME%%'  => esc_html( $author ),
		'%%AUTHOR_URL%%'   => '#',
		'%%AVATAR_ALT%%'   => esc_attr( 'Writer: ' . $author ),
		'%%POST_DATE%%'    => esc_html( $date ),
		'%%READ_TIME%%'    => esc_html( $readtime ),
		'%%TAG_SECTION%%'  => ndb_post_tag_section( $id ),
		'%%RECENT_POSTS%%' => ndb_recent_post_cards( $id ),
		'%%POST_BODY%%'    => $body,
	);
	$out = strtr( $tpl, $repl );
	// These renderers echo directly and bypass the ndb_page_content_html filter,
	// so apply the site-wide phone/copyright swap explicitly (no-op by default).
	if ( function_exists( 'ndb_apply_site_settings' ) ) {
		$out = ndb_apply_site_settings( $out );
	}
	echo $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * The per-post "Tags:" section, in the approved Wix blog-footer markup.
 * Returns an empty string when the post has no tags — matching the source,
 * where tagless posts omit the whole section.
 */
function ndb_post_tag_section( $post_id ) {
	$tags = get_the_tags( $post_id );
	if ( empty( $tags ) || is_wp_error( $tags ) ) {
		return '';
	}
	$items = '';
	foreach ( $tags as $tag ) {
		$items .= '<li><a href="' . esc_url( get_tag_link( $tag ) ) . '" class="_u2fqx" rel="noopener noreferrer">' . esc_html( $tag->name ) . '</a></li>';
	}
	return '<section class="JJ6Vcq"><p class="OY6C7u">Tags:</p><nav dir="ltr" aria-label="Tags" data-hook="tag-cloud-root"><ul class="zmug2R">' . $items . '</ul></nav></section>';
}

/**
 * The "Recent Posts" cards — the 3 most-recent published posts excluding the
 * current one, rendered in the approved Wix card markup with each post's real
 * cover image (_ndb_cover). Mirrors the source widget's behaviour exactly.
 */
function ndb_recent_post_cards( $post_id ) {
	$recent = get_posts(
		array(
			'post_type'        => 'post',
			'posts_per_page'   => 3,
			'post_status'      => 'publish',
			'orderby'          => 'date',
			'order'            => 'DESC',
			'post__not_in'     => array( (int) $post_id ),
			'ignore_sticky_posts' => true,
			'no_found_rows'    => true,
			'suppress_filters' => false,
		)
	);
	if ( empty( $recent ) ) {
		return '';
	}
	$cards = '';
	foreach ( $recent as $p ) {
		$title = get_the_title( $p );
		$link  = get_permalink( $p );
		$thumb = get_post_meta( $p->ID, '_ndb_cover', true );
		// Wix sized recent-post thumbnails client-side (the SSR <img> was srcless);
		// now that we supply a real src, fill the fixed-ratio card the same way the
		// body images are filled (absolute, cover) — scoped inline, no global CSS.
		$img_style = 'position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;display:block;';
		$img   = '<img' . ( '' !== $thumb ? ' src="' . esc_url( $thumb ) . '"' : '' ) . ' alt="' . esc_attr( $title ) . '" data-pin-nopin="true" style="' . $img_style . '"/>';
		$cards .= '<article class="qbu2Gh" data-hook="recent-post-list-item"><div class="dkbPQd">'
			. '<a aria-label="' . esc_attr( $title ) . '" href="' . esc_url( $link ) . '" class="blog-link-hover-color" data-hook="link" tabindex="-1">'
			. '<div class="kkOpHH"><wow-image class="S7Q4bF HNM0p2" data-motion-part="BG_IMG undefined" data-bg-effect-name="" data-has-ssr-src="" style="--wix-img-max-width:max(1024px, 100%)">'
			. $img . '</wow-image></div></a>'
			. '<div class="ZnRZP5"><header data-hook="recent-post__title"><a class="hPl9QB" href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a></header>'
			. '<footer data-hook="post-footer" class="LO60EX"><div class="D6DTyh"><div class="LVcAxq post-stats" data-hook="post-stats">'
			. '<div data-hook="skeleton-loader" class="bEeZ1_ qcmXZU" style="--width:30px"> </div>'
			. '<div data-hook="skeleton-loader" class="bEeZ1_ ZN1aom" style="--width:30px"> </div></div></div>'
			. '<div data-hook="skeleton-loader" class="bEeZ1_ wChIrD" style="--width:30px"> </div></footer></div></div></article>';
	}
	return $cards;
}

/**
 * Render the native WP blog index inside the approved Wix Pro-Gallery chrome.
 *
 * The Wix feed is a JS infinite-scroll gallery: only 9 cards are SSR'd into a
 * fixed 3-column grid that reserves height for cards it lazy-loads on scroll.
 * We can't run that JS, so we stamp the tokenised card template
 * (blog.card.html) once per published post, position each card by index in the
 * same grid (292x569 cells, 32px gaps — columns at 0/324/648, rows pitched
 * 601px) and size the gallery container to fit. Every post shows, exactly like
 * scrolling the live feed, with byte-faithful per-card markup.
 *
 * @param array $query_args Optional get_posts() overrides. Tag/category/author/
 *                          date archives pass a filter (tag_id, cat, author,
 *                          date_query) so the same feed chrome renders the term's
 *                          posts — matching the Wix tag pages, which were the blog
 *                          feed filtered to that tag.
 */
function ndb_blog_feed( $query_args = array() ) {
	$tpl  = ndb_get_part( 'blog', 'content' );
	$card = ndb_get_part( 'blog', 'card' );
	if ( '' === $tpl || '' === $card ) {
		// Chrome missing — degrade to a plain list so the page still works.
		if ( have_posts() ) {
			echo '<main id="main" class="site-main">';
			while ( have_posts() ) {
				the_post();
				the_title( '<h2><a href="' . esc_url( get_permalink() ) . '">', '</a></h2>' );
			}
			echo '</main>';
		}
		return;
	}

	$posts = get_posts(
		array_merge(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => -1,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'suppress_filters'    => false,
			),
			$query_args
		)
	);

	$cards = '';
	$i     = 0;
	foreach ( $posts as $p ) {
		$id   = $p->ID;
		$col  = $i % 3;
		$row  = intdiv( $i, 3 );
		$left = $col * 324;
		$top  = $row * 601;

		$title  = get_the_title( $p );
		$cover  = get_post_meta( $id, '_ndb_cover', true );
		$author = get_post_meta( $id, '_ndb_author', true );
		if ( '' === $author ) {
			$author = get_the_author_meta( 'display_name', $p->post_author );
		}
		$read = get_post_meta( $id, '_ndb_readtime', true );
		/* Wix showed a relative time ("2 days ago"); keep that style but compute
		   it live so it never goes stale as the client adds posts. */
		$ago     = sprintf( __( '%s ago', 'nangdelivery' ), human_time_diff( get_post_time( 'U', true, $p ) ) );
		$excerpt = ndb_blog_card_excerpt( $p );
		$uuid    = ndb_card_uuid( $id );

		$cards .= strtr(
			$card,
			array(
				'%%CARD_TOP%%'        => $top . 'px',
				'%%CARD_LEFT%%'       => $left . 'px',
				'%%CARD_IDX%%'        => (string) $i,
				'%%CARD_UID%%'        => $uuid,
				'%%CARD_UID_NODASH%%' => str_replace( '-', '', $uuid ),
				'%%CARD_HREF%%'       => esc_url( get_permalink( $p ) ),
				'%%CARD_COVER%%'      => esc_url( $cover ),
				'%%CARD_TITLE%%'      => esc_html( $title ),
				'%%CARD_TITLE_ATTR%%' => esc_attr( $title ),
				'%%CARD_EXCERPT%%'    => esc_html( $excerpt ),
				'%%CARD_AUTHOR%%'     => esc_html( $author ),
				'%%CARD_AUTHOR_ATTR%%' => esc_attr( $author ),
				'%%CARD_AUTHOR_URL%%' => '#',
				'%%CARD_AGO%%'        => esc_html( $ago ),
				'%%CARD_AGO_ATTR%%'   => esc_attr( $ago ),
				'%%CARD_READ%%'       => esc_html( $read ),
				'%%CARD_READ_ATTR%%'  => esc_attr( $read ),
			)
		);
		++$i;
	}

	$rows   = (int) ceil( $i / 3 );
	$height = $rows > 0 ? ( ( $rows - 1 ) * 601 + 569 ) : 569;

	echo strtr( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$tpl,
		array(
			'%%POST_CARDS%%'  => $cards,
			'%%FEED_HEIGHT%%' => (string) $height,
		)
	);
}

/**
 * A deterministic UUID-shaped id for a blog-feed card. The Wix gallery gave each
 * card a unique id used only by its (now-removed) JS; we derive a stable one
 * from the post id so DOM ids stay unique without affecting layout.
 */
function ndb_card_uuid( $id ) {
	$h = md5( 'ndb-card-' . $id );
	return substr( $h, 0, 8 ) . '-' . substr( $h, 8, 4 ) . '-' . substr( $h, 12, 4 ) . '-' . substr( $h, 16, 4 ) . '-' . substr( $h, 20, 12 );
}

/**
 * Plain-text excerpt for a feed card. The card CSS line-clamps to 3 lines, so we
 * pass a generous slice and let the clamp decide what shows (matching the Wix
 * feed, which truncated server-side then clamped).
 */
function ndb_blog_card_excerpt( $p ) {
	$ex = wp_strip_all_tags( get_the_excerpt( $p ) );
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $ex ) > 300 ) {
		$ex = rtrim( mb_substr( $ex, 0, 300 ) ) . '…';
	}
	return $ex;
}

/**
 * Render the current delivery_area inside its processed Wix chrome.
 *
 * Each area keeps its own per-page content part (page-parts/<slug>.content.html)
 * because the Wix comp IDs + comp-scoped CSS differ per area. The shared template
 * SHAPE is tokenised, so the editable spots come from the WP post:
 *   %%AREA%%        → post title (hero "Fast Nangs Delivery {Area}" + "About {Area}")
 *   %%ABOUT_INTRO%% → _ndb_about_intro  (About section intro prose)
 *   %%ABOUT_MAIN%%  → _ndb_about_main   (About column-strip prose)
 *   %%FINAL_NOTE%%  → _ndb_final_note   (The Final Note prose)
 *
 * The three prose fields hold the approved Wix rich-text markup and are emitted
 * verbatim (raw HTML); the client edits them via the ACF fields on the CPT.
 */
function ndb_render_area_content() {
	$id   = get_the_ID();
	$slug = get_post_field( 'post_name', $id );
	$tpl  = ndb_get_part( $slug, 'content' );
	if ( '' === $tpl ) {
		the_content();
		return;
	}

	$repl = array(
		'%%AREA%%'        => esc_html( get_the_title() ),
		'%%ABOUT_INTRO%%' => ndb_area_field( $id, 'ndb_about_intro' ),
		'%%ABOUT_MAIN%%'  => ndb_area_field( $id, 'ndb_about_main' ),
		'%%FINAL_NOTE%%'  => ndb_area_field( $id, 'ndb_final_note' ),
	);
	$out = strtr( $tpl, $repl );
	// Direct echo bypasses the ndb_page_content_html filter; apply the site-wide
	// phone/copyright swap here so it reaches all 62 area pages (no-op by default).
	if ( function_exists( 'ndb_apply_site_settings' ) ) {
		$out = ndb_apply_site_settings( $out );
	}
	echo $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * A delivery-area prose field. Reads the ACF/meta value (raw Wix rich-text HTML)
 * and returns it verbatim. ACF-independent: works from post meta whether or not
 * the ACF plugin is active.
 */
function ndb_area_field( $id, $key ) {
	$val = get_post_meta( $id, $key, true );
	return is_string( $val ) ? $val : '';
}

/**
 * Editable-prose defaults for a marketing page, keyed by meta key
 * (ndb_rt_<compid>) → original Wix rich-text inner HTML. Written by
 * _build/build_pages.php; the source of truth for the out-of-the-box copy and
 * the renderer's fallback when a page-meta field is empty. Cached per request.
 */
function ndb_page_defaults( $slug ) {
	static $cache = array();
	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}
	$out  = array();
	$file = ndb_parts_dir() . "/{$slug}.defaults.json";
	if ( file_exists( $file ) ) {
		$json = json_decode( file_get_contents( $file ), true );
		if ( is_array( $json ) ) {
			$out = $json;
		}
	}
	return $cache[ $slug ] = $out;
}

/**
 * Render a processed marketing page, filling its %%RT_<compid>%% rich-text tokens
 * from the page's post meta (client-edited copy) and falling back to the baked
 * default for any field the client has left untouched — so the default render is
 * byte-identical to the approved Wix design. ACF-independent: reads raw meta, so
 * it works whether or not ACF is active (ACF only supplies the editing UI).
 *
 * Pages without a tokenised defaults.json (no editable prose) render their
 * processed part verbatim. Returns false when the slug has no processed part, so
 * callers can fall back to native WordPress content.
 *
 * @param string $slug Page slug; defaults to the current page.
 * @return bool Whether a processed part was rendered.
 */
function ndb_render_page_content( $slug = '' ) {
	if ( '' === $slug ) {
		$slug = ndb_current_slug();
	}
	$tpl = ndb_get_part( $slug, 'content' );
	if ( '' === $tpl ) {
		return false;
	}

	// Fill the rich-text tokens actually present (regex-driven, so a missing
	// defaults.json can never leave a raw %%RT_*%% token on the page). Each token
	// resolves to the client-edited meta, else the baked default, else empty.
	if ( false !== strpos( $tpl, '%%RT_' ) ) {
		$defaults = ndb_page_defaults( $slug );
		$pid      = get_queried_object_id();
		if ( ! $pid ) {
			$pid = get_the_ID();
		}
		$tpl = preg_replace_callback(
			'~%%RT_([a-z0-9_]+)%%~',
			function ( $m ) use ( $pid, $defaults ) {
				$meta_key = 'ndb_rt_' . $m[1];
				$val      = $pid ? get_post_meta( $pid, $meta_key, true ) : '';
				if ( is_string( $val ) && '' !== $val ) {
					return $val;
				}
				return isset( $defaults[ $meta_key ] ) ? $defaults[ $meta_key ] : '';
			},
			$tpl
		);
	}

	/**
	 * Final pass for non-prose dynamic tokens (e.g. form plumbing wired up by
	 * inc/forms.php). Kept as a filter so render.php stays generic and pages
	 * without forms are unaffected. Always applied so tokens never leak.
	 */
	$tpl = apply_filters( 'ndb_page_content_html', $tpl, $slug );

	echo $tpl; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return true;
}

/**
 * Resolve the current page's slug to a processed-part slug.
 * Front page => "home". Extend as templates are added.
 */
function ndb_current_slug() {
	if ( is_front_page() ) {
		return 'home';
	}
	// Single blog posts share one processed blog chrome (head/prefix); the per-post
	// title/body/date/author are injected from the WP loop by single.php.
	if ( is_singular( 'post' ) ) {
		return 'post';
	}
	// WooCommerce shop archive (and product taxonomy archives) reuse the shop
	// page's processed head/prefix so the chrome matches the approved design.
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		return 'shop';
	}
	// Blog tag/category/author/date archives reuse the blog feed's processed head
	// + prefix; archive.php fills them with the term's posts via ndb_blog_feed().
	if ( is_tag() || is_category() || is_author() || is_date() ) {
		return 'blog';
	}
	// Search results reuse the blog feed chrome too; search.php fills the feed
	// with the matching posts via ndb_blog_feed( array( 's' => … ) ).
	if ( is_search() ) {
		return 'blog';
	}
	// The delivery_area CPT archive (/deliveryarea/) is the approved "Delivery
	// Areas" page (heading + "Add your missing suburb" enquiry form); render its
	// processed chrome rather than a generic archive.
	if ( function_exists( 'is_post_type_archive' ) && is_post_type_archive( 'delivery_area' ) ) {
		return 'deliveryarea';
	}
	$post = get_queried_object();
	if ( $post && isset( $post->post_name ) && $post->post_name ) {
		return $post->post_name;
	}
	return 'home';
}
