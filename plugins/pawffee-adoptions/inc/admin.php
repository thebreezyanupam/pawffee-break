<?php
/**
 * Dashboard tools for café staff: list columns, a status filter, and
 * built-in help tabs that work as the training guide.
 *
 * @package PawffeeAdoptions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Columns on Pets > All pets.
 *
 * @param array $columns Default columns.
 * @return array
 */
function pawffee_adoptions_columns( $columns ) {
	return array(
		'cb'             => $columns['cb'],
		'pawffee_photo'  => '<span class="screen-reader-text">' . esc_html__( 'Photo', 'pawffee-adoptions' ) . '</span>',
		'title'          => __( 'Name', 'pawffee-adoptions' ),
		'pawffee_status' => __( 'Status', 'pawffee-adoptions' ),
		'pawffee_facts'  => __( 'Species, breed and age', 'pawffee-adoptions' ),
		'menu_order'     => __( 'Order', 'pawffee-adoptions' ),
		'date'           => $columns['date'],
	);
}
add_filter( 'manage_pawffee_pet_posts_columns', 'pawffee_adoptions_columns' );

/**
 * Column content.
 *
 * @param string $column  Column key.
 * @param int    $post_id Pet ID.
 */
function pawffee_adoptions_column_content( $column, $post_id ) {
	$pet = pawffee_adoptions_get_pet( $post_id );

	switch ( $column ) {
		case 'pawffee_photo':
			if ( $pet['photo'] ) {
				echo wp_get_attachment_image( $pet['photo'], array( 56, 56 ), false, array( 'class' => 'pawffee-admin-thumb', 'alt' => '' ) );
			}
			break;

		case 'pawffee_status':
			printf(
				'<span class="pawffee-status pawffee-status--%1$s">%2$s</span>',
				esc_attr( $pet['status'] ),
				esc_html( $pet['status_label'] )
			);
			break;

		case 'pawffee_facts':
			echo esc_html( implode( ' · ', array_filter( array( $pet['species_label'], $pet['breed'], $pet['age_label'] ) ) ) );
			break;

		case 'menu_order':
			echo (int) get_post_field( 'menu_order', $post_id );
			break;
	}
}
add_action( 'manage_pawffee_pet_posts_custom_column', 'pawffee_adoptions_column_content', 10, 2 );

/**
 * Sort by the Order field by default, so the list matches the Adoption page.
 *
 * @param WP_Query $query Admin list query.
 */
function pawffee_adoptions_admin_query( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'pawffee_pet' !== $query->get( 'post_type' ) ) {
		return;
	}

	if ( ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
	}

	$status = isset( $_GET['pawffee_status'] ) ? sanitize_key( wp_unslash( $_GET['pawffee_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- read-only list filter.
	if ( $status && array_key_exists( $status, pawffee_adoptions_statuses() ) ) {
		$query->set(
			'meta_query',
			array(
				array(
					'key'   => 'status',
					'value' => $status,
				),
			)
		);
	}
}
add_action( 'pre_get_posts', 'pawffee_adoptions_admin_query' );

/**
 * "All statuses" dropdown above the list.
 *
 * @param string $post_type Current list.
 */
function pawffee_adoptions_status_filter( $post_type ) {
	if ( 'pawffee_pet' !== $post_type ) {
		return;
	}

	$current = isset( $_GET['pawffee_status'] ) ? sanitize_key( wp_unslash( $_GET['pawffee_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- read-only list filter.

	echo '<label class="screen-reader-text" for="pawffee-status-filter">' . esc_html__( 'Filter by adoption status', 'pawffee-adoptions' ) . '</label>';
	echo '<select name="pawffee_status" id="pawffee-status-filter">';
	echo '<option value="">' . esc_html__( 'All statuses', 'pawffee-adoptions' ) . '</option>';
	foreach ( pawffee_adoptions_statuses() as $value => $label ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $value ), selected( $current, $value, false ), esc_html( $label ) );
	}
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'pawffee_adoptions_status_filter' );

/**
 * Small admin styles: round thumbnails and status pills with text, never colour alone.
 */
function pawffee_adoptions_admin_styles() {
	$screen = get_current_screen();
	if ( ! $screen || 'pawffee_pet' !== $screen->post_type ) {
		return;
	}
	echo '<style>
		.column-pawffee_photo { width: 64px; }
		.column-menu_order { width: 64px; }
		.pawffee-admin-thumb { width: 56px; height: 56px; object-fit: cover; border-radius: 50%; }
		.pawffee-status { display: inline-block; padding: 2px 10px; border-radius: 999px; font-weight: 600; font-size: 12px; }
		.pawffee-status--available { background: #e3f4e8; color: #1c6b3a; }
		.pawffee-status--pending { background: #fdf0d5; color: #7a5200; }
		.pawffee-status--adopted { background: #e9e6f5; color: #4a3d8f; }
	</style>';
}
add_action( 'admin_head', 'pawffee_adoptions_admin_styles' );

/**
 * Help tabs on the pet screens. The same steps are in docs/EDITING-GUIDE.md.
 */
function pawffee_adoptions_help_tabs() {
	$screen = get_current_screen();
	if ( ! $screen || 'pawffee_pet' !== $screen->post_type ) {
		return;
	}

	$screen->add_help_tab(
		array(
			'id'      => 'pawffee-add-pet',
			'title'   => __( 'Adding a pet', 'pawffee-adoptions' ),
			'content' => '<ol>
				<li>' . esc_html__( 'Go to Pets > Add pet and type their name as the title.', 'pawffee-adoptions' ) . '</li>
				<li>' . esc_html__( 'Write their story in the editor: how they came to the café and the home they need.', 'pawffee-adoptions' ) . '</li>
				<li>' . esc_html__( 'Fill in the Profile tab below the editor. Fields marked * are required.', 'pawffee-adoptions' ) . '</li>
				<li>' . esc_html__( 'Choose a portrait photo and make sure it has Alternative Text describing the pet.', 'pawffee-adoptions' ) . '</li>
				<li>' . esc_html__( 'Set Order in the Page Attributes panel: lower numbers show first on the Adoption page.', 'pawffee-adoptions' ) . '</li>
				<li>' . esc_html__( 'Publish. They appear on the Adoption page straight away.', 'pawffee-adoptions' ) . '</li>
			</ol>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'pawffee-status',
			'title'   => __( 'Pending and adopted', 'pawffee-adoptions' ),
			'content' => '<p>' . esc_html__( 'Open the Adoption & care tab and change Adoption status:', 'pawffee-adoptions' ) . '</p><ul>
				<li><strong>' . esc_html__( 'Adoption pending', 'pawffee-adoptions' ) . '</strong>: ' . esc_html__( 'the pet stays on the Adoption page with a note and a "Join the waitlist" button.', 'pawffee-adoptions' ) . '</li>
				<li><strong>' . esc_html__( 'Adopted', 'pawffee-adoptions' ) . '</strong>: ' . esc_html__( 'enter the adoption date. The pet leaves the Adoption page but keeps their profile, so links people shared still work.', 'pawffee-adoptions' ) . '</li>
			</ul><p>' . esc_html__( 'Use the status filter above the Pets list to see who is pending or adopted.', 'pawffee-adoptions' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'pawffee-photos',
			'title'   => __( 'Photos', 'pawffee-adoptions' ),
			'content' => '<ul>
				<li>' . esc_html__( 'Use a portrait (taller than wide) photo at least 600 pixels wide. It is cropped to an arch shape.', 'pawffee-adoptions' ) . '</li>
				<li>' . esc_html__( 'Upload JPG, PNG or WebP. The site makes a smaller WebP copy automatically.', 'pawffee-adoptions' ) . '</li>
				<li>' . esc_html__( 'Alternative Text: describe the pet, for example "Benny, a yellow labrador mix, lounging on a rug". Don\'t start with "image of".', 'pawffee-adoptions' ) . '</li>
			</ul>',
		)
	);

	$screen->set_help_sidebar(
		'<p><strong>' . esc_html__( 'Need more help?', 'pawffee-adoptions' ) . '</strong></p><p>' . esc_html__( 'The full staff guide is in the plugin\'s docs folder: EDITING-GUIDE.md.', 'pawffee-adoptions' ) . '</p>'
	);
}
add_action( 'current_screen', 'pawffee_adoptions_help_tabs' );
