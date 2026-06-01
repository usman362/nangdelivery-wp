<?php
/**
 * Search results.
 *
 * The Wix blog's search field returned a filtered view of the same Pro-Gallery
 * feed. We keep that: the blog <head>/prefix + shared header/footer come from
 * get_header()/get_footer() (ndb_current_slug() => "blog" for is_search()), and
 * ndb_blog_feed() stamps the approved card template once per matching post.
 *
 * Search is scoped to blog posts (post_type=post) to match the live behaviour —
 * the field lived in the blog header and searched posts, not pages/products.
 *
 * @package NangDelivery
 */

get_header();

ndb_blog_feed( array( 's' => get_search_query() ) );

get_footer();
