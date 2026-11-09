<?php
/**
 * The Pet post type.
 *
 * Pets live in a plugin rather than the theme so the café keeps its
 * residents if the site is ever redesigned.
 *
 * @package PawffeeAdoptions
 */

defined( 'ABSPATH' ) || exit;

function pawffee_adoptions_register_post_type() {
	register_post_type(
		'pawffee_pet',
		array(
			'labels'          => array(
				'name'               => __( 'Pets', 'pawffee-adoptions' ),
				'singular_name'      => __( 'Pet', 'pawffee-adoptions' ),
				'menu_name'          => __( 'Pets', 'pawffee-adoptions' ),
				'add_new'            => __( 'Add pet', 'pawffee-adoptions' ),
				'add_new_item'       => __( 'Add a new pet', 'pawffee-adoptions' ),
				'edit_item'          => __( 'Edit pet', 'pawffee-adoptions' ),
				'new_item'           => __( 'New pet', 'pawffee-adoptions' ),
				'view_item'          => __( 'View pet', 'pawffee-adoptions' ),
				'search_items'       => __( 'Search pets', 'pawffee-adoptions' ),
				'not_found'          => __( 'No pets yet. Click "Add pet" to add the first resident.', 'pawffee-adoptions' ),
				'not_found_in_trash' => __( 'No pets in the trash.', 'pawffee-adoptions' ),
				'all_items'          => __( 'All pets', 'pawffee-adoptions' ),
				'item_published'     => __( 'Pet published.', 'pawffee-adoptions' ),
				'item_updated'       => __( 'Pet updated.', 'pawffee-adoptions' ),
			),
			'description'     => __( 'Residents of the café who are looking for a home.', 'pawffee-adoptions' ),
			'public'          => true,
			'show_in_rest'    => true,
			'menu_position'   => 20,
			'menu_icon'       => 'dashicons-pets',
			'has_archive'     => false,
			'rewrite'         => array(
				'slug'       => 'pets',
				'with_front' => false,
			),
			'supports'        => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'revisions' ),
			// Staff write the pet's story in the editor; the profile facts come from the ACF fields.
			'template'        => array(
				array(
					'core/paragraph',
					array( 'placeholder' => __( 'Tell their story: how they came to the café, their quirks, the home they need.', 'pawffee-adoptions' ) ),
				),
			),
		)
	);
}
add_action( 'init', 'pawffee_adoptions_register_post_type' );

/**
 * Keep the featured image in step with the ACF photo, so social cards and
 * any theme that shows featured images get the right picture.
 *
 * @param int $post_id Post being saved.
 */
function pawffee_adoptions_sync_thumbnail( $post_id ) {
	if ( 'pawffee_pet' !== get_post_type( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	$photo = (int) get_post_meta( $post_id, 'photo', true );

	if ( $photo && $photo !== (int) get_post_thumbnail_id( $post_id ) ) {
		set_post_thumbnail( $post_id, $photo );
	}
}
add_action( 'acf/save_post', 'pawffee_adoptions_sync_thumbnail', 20 );
