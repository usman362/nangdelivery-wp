<?php
/**
 * Single blog post — renders the WP post inside the processed Wix blog-post
 * chrome. The shared header/footer come from get_header()/get_footer(); the
 * blog <head> + container prefix are emitted by header.php via the "post" slug.
 *
 * The article body is real, editable post_content; the surrounding hero/title/
 * author/date layout is the approved Wix design, filled from the WP loop.
 *
 * @package NangDelivery
 */

get_header();

while ( have_posts() ) {
	the_post();
	ndb_render_post_content();
}

get_footer();
