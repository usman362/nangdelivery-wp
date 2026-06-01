<?php
/**
 * Single delivery area — renders the delivery_area CPT entry inside its
 * processed Wix per-area chrome. get_header()/get_footer() emit the shared
 * site header/footer; the per-area <head> + container prefix are emitted by
 * header.php via the area's slug (ndb_current_slug() => post_name).
 *
 * The hero, 13-product gallery, About + Final Note layout and comp-scoped CSS
 * are the approved Wix design; the area name + 3 prose blocks are real, editable
 * post fields filled from the WP loop by ndb_render_area_content().
 *
 * @package NangDelivery
 */

get_header();

while ( have_posts() ) {
	the_post();
	ndb_render_area_content();
}

get_footer();
