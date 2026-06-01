<?php
/**
 * Shop archive — the WooCommerce product gallery, rendered with the approved
 * Wix gallery chrome (page-parts/shop.content.html) and live product cards.
 *
 * WooCommerce honours a theme archive-product.php over its bundled templates,
 * so this drives /shop/ (and product taxonomy archives) without a Wix runtime.
 *
 * @package NangDelivery
 */

get_header();
echo ndb_render_shop_gallery(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
get_footer();
