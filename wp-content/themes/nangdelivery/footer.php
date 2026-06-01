<?php
/**
 * Site footer — renders the shared Wix footer (#SITE_FOOTER) and the closing
 * container markup, then closes the document.
 *
 * @package NangDelivery
 */

$ndb_slug = ndb_current_slug();
ndb_the_shared( '_footer', $ndb_slug );
?>
<?php wp_footer(); ?>
</body>
</html>
