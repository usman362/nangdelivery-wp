<?php
/**
 * Blog index — the WordPress "Posts page" (the page whose slug is "blog",
 * reachable at /blog/). Renders the native WP post loop inside the approved Wix
 * Pro-Gallery feed chrome. The shared header/footer are emitted by
 * get_header()/get_footer(); the blog <head> + container prefix come from
 * header.php via the "blog" slug (ndb_current_slug() => the posts page name).
 *
 * @package NangDelivery
 */

get_header();
ndb_blog_feed();
get_footer();
