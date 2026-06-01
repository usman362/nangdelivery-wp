<?php
/**
 * Auto-registered ACF fields for editable marketing-page copy.
 *
 * _build/build_pages.php tokenises each page's Wix rich-text blocks and writes a
 * page-parts/<slug>.defaults.json mapping meta-key (ndb_rt_<compid>) => original
 * inner HTML. Here we turn every such file into an ACF field group bound to the
 * matching WordPress page, with one WYSIWYG field per block. The field NAME is the
 * meta key the renderer (ndb_render_page_content) reads, so output stays
 * ACF-independent — ACF only supplies the editing UI.
 *
 * Adding a page to the system is therefore just: tokenise it with build_pages.php
 * and seed its meta; its editor fields appear automatically.
 *
 * @package NangDelivery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A short, human label for an editable block, derived from its default text so
 * the client can tell the WYSIWYG boxes apart in wp-admin.
 */
function ndb_rt_label( $html ) {
	$text = wp_strip_all_tags( (string) $html );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = str_replace( "\xc2\xa0", ' ', $text ); // non-breaking space → space
	$text = trim( preg_replace( '~\s+~u', ' ', $text ) );
	if ( '' === $text ) {
		return __( 'Text block', 'nangdelivery' );
	}
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $text ) > 60 ) {
		$text = rtrim( mb_substr( $text, 0, 60 ) ) . '…';
	}
	return $text;
}

add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) || ! function_exists( 'ndb_parts_dir' ) ) {
			return;
		}
		foreach ( glob( ndb_parts_dir() . '/*.defaults.json' ) as $file ) {
			$slug     = basename( $file, '.defaults.json' );
			$defaults = json_decode( (string) file_get_contents( $file ), true );
			if ( ! is_array( $defaults ) || empty( $defaults ) ) {
				continue;
			}

			$page = get_page_by_path( $slug, OBJECT, 'page' );
			if ( ! $page ) {
				continue;
			}

			$fields = array();
			$n      = 0;
			foreach ( $defaults as $meta_key => $default ) {
				++$n;
				$fields[] = array(
					'key'          => 'field_' . $meta_key,
					'label'        => sprintf( '%d. %s', $n, ndb_rt_label( $default ) ),
					'name'         => $meta_key,
					'type'         => 'wysiwyg',
					'instructions' => __( 'Editable text block. The surrounding layout and design are fixed by the theme — edit the wording only.', 'nangdelivery' ),
					'tabs'         => 'all',
					'toolbar'      => 'full',
					'media_upload' => 0,
					'delay'        => 0,
				);
			}

			acf_add_local_field_group(
				array(
					'key'             => 'group_ndb_page_' . $slug,
					'title'           => sprintf( /* translators: %s: page title */ __( 'Page Content — %s', 'nangdelivery' ), get_the_title( $page->ID ) ),
					'fields'          => $fields,
					'location'        => array(
						array(
							array(
								'param'    => 'page',
								'operator' => '==',
								'value'    => (string) $page->ID,
							),
						),
					),
					'menu_order'      => 0,
					'position'        => 'normal',
					'style'           => 'default',
					'label_placement' => 'top',
					'active'          => true,
					'description'     => __( 'Editable copy for this page. Layout/design is fixed by the theme.', 'nangdelivery' ),
				)
			);
		}
	}
);
