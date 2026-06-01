<?php
/**
 * Blog tag / category / author / date archives.
 *
 * The Wix site rendered every tag page (/blog/tags/<tag>) as the blog Pro-Gallery
 * feed filtered to that tag, in the same chrome as the main blog index. We keep
 * that exact design: the shared header/footer + blog <head>/prefix come from
 * get_header()/get_footer() (ndb_current_slug() => "blog" for these archives),
 * and ndb_blog_feed() stamps the approved card template once per matching post.
 *
 * The filter is derived from the queried object so one template serves tags,
 * categories, authors and date archives. WooCommerce product archives are handled
 * separately by archive-product.php and never reach here.
 *
 * @package NangDelivery
 */

get_header();

$ndb_filter = array();
$ndb_qo     = get_queried_object();

if ( is_tag() && $ndb_qo instanceof WP_Term ) {
	$ndb_filter['tag_id'] = $ndb_qo->term_id;
} elseif ( is_category() && $ndb_qo instanceof WP_Term ) {
	$ndb_filter['cat'] = $ndb_qo->term_id;
} elseif ( is_author() ) {
	$ndb_filter['author'] = get_queried_object_id();
} elseif ( is_date() ) {
	$ndb_date = array();
	if ( get_query_var( 'year' ) ) {
		$ndb_date['year'] = (int) get_query_var( 'year' );
	}
	if ( get_query_var( 'monthnum' ) ) {
		$ndb_date['month'] = (int) get_query_var( 'monthnum' );
	}
	if ( get_query_var( 'day' ) ) {
		$ndb_date['day'] = (int) get_query_var( 'day' );
	}
	if ( $ndb_date ) {
		$ndb_filter['date_query'] = array( $ndb_date );
	}
}

ndb_blog_feed( $ndb_filter );

get_footer();
