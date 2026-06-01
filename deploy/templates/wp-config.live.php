<?php
/**
 * Production wp-config.php TEMPLATE for the live cPanel server.
 *
 * HOW TO USE
 *   1. Copy this file to the WordPress root on the server, renamed to
 *      wp-config.php  (it must sit next to wp-load.php).
 *   2. Fill in DB_NAME / DB_USER / DB_PASSWORD with the MySQL database you
 *      created in cPanel (DB_HOST is almost always 'localhost' on cPanel).
 *   3. Replace the 8 salt placeholders below with fresh values from:
 *         https://api.wordpress.org/secret-key/1.1/salt/
 *      (just paste the whole block over the placeholder block).
 *   4. Leave $table_prefix as 'ndb_' — the database dump uses that prefix.
 *
 * NOTE: this template is NOT loaded by WordPress and carries no secrets,
 * so it is safe to keep in git. The REAL wp-config.php is git-ignored.
 */

// ** Database settings — from cPanel → MySQL Databases ** //
define( 'DB_NAME',     'REPLACE_WITH_CPANEL_DB_NAME' );   // e.g. cpaneluser_nangdb
define( 'DB_USER',     'REPLACE_WITH_CPANEL_DB_USER' );   // e.g. cpaneluser_nang
define( 'DB_PASSWORD', 'REPLACE_WITH_DB_PASSWORD' );
define( 'DB_HOST',     'localhost' );                     // cPanel default
define( 'DB_CHARSET',  'utf8mb4' );
define( 'DB_COLLATE',  '' );

// ** Authentication unique keys and salts — REGENERATE, do not reuse ** //
// Paste a fresh block from https://api.wordpress.org/secret-key/1.1/salt/
define( 'AUTH_KEY',         'put-a-fresh-salt-here' );
define( 'SECURE_AUTH_KEY',  'put-a-fresh-salt-here' );
define( 'LOGGED_IN_KEY',    'put-a-fresh-salt-here' );
define( 'NONCE_KEY',        'put-a-fresh-salt-here' );
define( 'AUTH_SALT',        'put-a-fresh-salt-here' );
define( 'SECURE_AUTH_SALT', 'put-a-fresh-salt-here' );
define( 'LOGGED_IN_SALT',   'put-a-fresh-salt-here' );
define( 'NONCE_SALT',       'put-a-fresh-salt-here' );

// ** Database table prefix — MUST match the imported dump ** //
$table_prefix = 'ndb_';

// ** Production hardening ** //
define( 'WP_DEBUG', false );           // no errors shown to visitors
define( 'WP_DEBUG_DISPLAY', false );
define( 'DISALLOW_FILE_EDIT', true );  // disable the in-admin code editors
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
define( 'FS_METHOD', 'direct' );       // smooth plugin/theme updates on cPanel
@ini_set( 'display_errors', 0 );

/*
 * OPTIONAL — pin the URLs from wp-config instead of the database. Only
 * uncomment AFTER you have decided the final domain. If you run the
 * search-replace step (DEPLOY.md Step 7) you do NOT need these.
 *
 * define( 'WP_HOME',    'https://nangdeliverybrisbane.au' );
 * define( 'WP_SITEURL', 'https://nangdeliverybrisbane.au' );
 */

/* That's all, stop editing! Happy publishing. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
