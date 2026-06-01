<?php
/**
 * Generic fallback template. Real templates are added in Phase 3+.
 *
 * @package NangDelivery
 */

get_header();
?>
<main id="main" class="site-main">
	<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			the_title( '<h1>', '</h1>' );
			the_content();
		}
	}
	?>
</main>
<?php
get_footer();
