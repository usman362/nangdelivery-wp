<?php
/**
 * Front page — outputs the processed homepage content region, with the editable
 * rich-text blocks filled from the Home page's ACF/meta fields (falling back to
 * the approved default copy). The shared header/footer are emitted by
 * get_header()/get_footer().
 *
 * @package NangDelivery
 */

get_header();
ndb_render_page_content( 'home' );
get_footer();
