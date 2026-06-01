<?php
/**
 * Custom post types.
 *
 * @package NangDelivery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Delivery Areas.
 *
 * One entry per Brisbane suburb the business delivers to. The suburb name is
 * the post title and the "About {Area}" / "The Final Note" prose is the editable
 * post body; the surrounding hero, product grid and FAQ come from the shared
 * processed Wix chrome (single-delivery_area.php).
 *
 * Rewrite base "deliveryarea" reproduces the original Wix URLs:
 *   /deliveryarea/        → archive (the suburbs listing)
 *   /deliveryarea/albion/ → single suburb page
 */
add_action(
	'init',
	function () {
		register_post_type(
			'delivery_area',
			array(
				'labels'        => array(
					'name'               => __( 'Delivery Areas', 'nangdelivery' ),
					'singular_name'      => __( 'Delivery Area', 'nangdelivery' ),
					'add_new_item'       => __( 'Add New Delivery Area', 'nangdelivery' ),
					'edit_item'          => __( 'Edit Delivery Area', 'nangdelivery' ),
					'new_item'           => __( 'New Delivery Area', 'nangdelivery' ),
					'view_item'          => __( 'View Delivery Area', 'nangdelivery' ),
					'search_items'       => __( 'Search Delivery Areas', 'nangdelivery' ),
					'not_found'          => __( 'No delivery areas found', 'nangdelivery' ),
					'all_items'          => __( 'All Delivery Areas', 'nangdelivery' ),
					'menu_name'          => __( 'Delivery Areas', 'nangdelivery' ),
				),
				'public'        => true,
				'has_archive'   => 'deliveryarea',
				'rewrite'       => array(
					'slug'       => 'deliveryarea',
					'with_front' => false,
				),
				'menu_icon'     => 'dashicons-location',
				'menu_position' => 22,
				// No 'editor': the area's editable copy lives in the three ACF
				// fields below (which map to the tokenised spots in the chrome).
				'supports'      => array( 'title', 'thumbnail', 'excerpt', 'page-attributes' ),
				'show_in_rest'  => true,
			)
		);
	}
);

/**
 * ACF field group: the client-editable copy of a Delivery Area.
 *
 * The area name is the post title (drives the hero "Fast Nangs Delivery {Area}"
 * and the "About {Area}" heading). These three WYSIWYG fields are the prose
 * blocks of the page; they map to %%ABOUT_INTRO%% / %%ABOUT_MAIN%% /
 * %%FINAL_NOTE%% tokens that ndb_render_area_content() fills. Field NAMES match
 * the meta keys the renderer reads, so output is ACF-independent.
 */
add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		acf_add_local_field_group(
			array(
				'key'             => 'group_ndb_delivery_area',
				'title'           => 'Delivery Area Content',
				'fields'          => array(
					array(
						'key'          => 'field_ndb_about_intro',
						'label'        => 'About — Intro',
						'name'         => 'ndb_about_intro',
						'type'         => 'wysiwyg',
						'instructions' => 'Short intro paragraph shown under the "About {Area}" heading.',
						'tabs'         => 'all',
						'toolbar'      => 'full',
						'media_upload' => 0,
						'delay'        => 0,
					),
					array(
						'key'          => 'field_ndb_about_main',
						'label'        => 'About — Main',
						'name'         => 'ndb_about_main',
						'type'         => 'wysiwyg',
						'instructions' => 'Main body copy in the About section (multiple paragraphs).',
						'tabs'         => 'all',
						'toolbar'      => 'full',
						'media_upload' => 0,
						'delay'        => 0,
					),
					array(
						'key'          => 'field_ndb_final_note',
						'label'        => 'The Final Note',
						'name'         => 'ndb_final_note',
						'type'         => 'wysiwyg',
						'instructions' => 'Closing paragraph in the "The Final Note" section.',
						'tabs'         => 'all',
						'toolbar'      => 'full',
						'media_upload' => 0,
						'delay'        => 0,
					),
				),
				'location'        => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'delivery_area',
						),
					),
				),
				'menu_order'      => 0,
				'position'        => 'normal',
				'style'           => 'default',
				'label_placement' => 'top',
				'active'          => true,
				'description'     => 'Editable copy for this delivery-area page. Layout/design is fixed by the theme.',
			)
		);
	}
);
