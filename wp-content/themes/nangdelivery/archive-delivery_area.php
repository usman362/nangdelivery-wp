<?php
/**
 * Delivery Areas archive (/deliveryarea/).
 *
 * The Wix "Delivery Areas" page is a single approved design: a heading and the
 * "Add your missing suburb" enquiry form — it does not list the individual
 * suburbs. We render that processed page here (ndb_render_page_content
 * "deliveryarea"), so the CPT archive shows the approved page instead of a
 * generic post list. Individual suburbs are real delivery_area entries served by
 * single-delivery_area.php.
 *
 * The shared header/footer + the deliveryarea <head>/prefix come from
 * get_header()/get_footer() (ndb_current_slug() => "deliveryarea" for this
 * archive).
 *
 * @package NangDelivery
 */

get_header();

if ( ! ndb_render_page_content( 'deliveryarea' ) ) {
	// Defensive fallback: if the processed part is ever missing, render the
	// matching WP page's content inside the base-shell layout so the route
	// still works.
	echo '<main id="PAGES_CONTAINER" class="ndb-native-main"><div class="ndb-native-page">';
	$ndb_page = get_page_by_path( 'deliveryarea', OBJECT, 'page' );
	if ( $ndb_page ) {
		echo apply_filters( 'the_content', $ndb_page->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</div></main>';
}

get_footer();
