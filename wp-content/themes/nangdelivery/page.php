<?php
/**
 * Generic page template — renders the processed content region matching the
 * current page's slug. Shared header/footer come from get_header()/get_footer().
 *
 * Falls back to standard WordPress content when no processed part exists, so
 * client-authored pages still work.
 *
 * @package NangDelivery
 */

get_header();

$ndb_slug = ndb_current_slug();

if ( ! ndb_render_page_content( $ndb_slug ) ) {
	// WordPress-native page (cart, checkout, account, privacy, client pages):
	// render inside the base-shell chrome, wrapped in a centred layout main.
	echo '<main id="PAGES_CONTAINER" class="ndb-native-main">';
	echo '<div class="ndb-native-page">';
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	echo '</div></main>';
}

get_footer();
