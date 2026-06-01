<?php
/**
 * Site header — emits the processed Wix <head> for the current page, opens
 * <body>, then renders the shared site header (Wix container + masterPage +
 * #SITE_HEADER) up to the start of the page content region.
 *
 * The shared header/footer are single reusable partials (_header.html /
 * _footer.html); only the per-page %%PAGEID%% token differs between pages.
 *
 * @package NangDelivery
 */

$ndb_slug = ndb_current_slug();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<?php ndb_the_part_or_base( $ndb_slug, 'head' ); ?>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php ndb_the_part_or_base( $ndb_slug, 'prefix' ); ?>
<?php ndb_the_shared( '_header', $ndb_slug ); ?>
