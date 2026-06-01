<?php
/**
 * Nang Delivery Brisbane theme bootstrap.
 *
 * @package NangDelivery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NDB_THEME_VERSION', '1.0.0' );
define( 'NDB_THEME_DIR', get_template_directory() );
define( 'NDB_THEME_URI', get_template_directory_uri() );

require_once NDB_THEME_DIR . '/inc/setup.php';
require_once NDB_THEME_DIR . '/inc/enqueue.php';
require_once NDB_THEME_DIR . '/inc/render.php';
require_once NDB_THEME_DIR . '/inc/post-types.php';
require_once NDB_THEME_DIR . '/inc/page-fields.php';
require_once NDB_THEME_DIR . '/inc/forms.php';
require_once NDB_THEME_DIR . '/inc/site-settings.php';

if ( class_exists( 'WooCommerce' ) ) {
	require_once NDB_THEME_DIR . '/inc/woo.php';
}
