<?php
/**
 * Reads a pet's ACF profile into one tidy array for the blocks.
 *
 * Every read goes through pawffee_adoptions_field(), which uses ACF when it
 * is active and falls back to the raw post meta ACF writes, so the Adoption
 * page keeps working even if ACF is switched off for a moment.
 *
 * @package PawffeeAdoptions
 */

defined( 'ABSPATH' ) || exit;

/**
 * One field value, through ACF when available.
 *
 * @param string $name    Field name. Group sub-fields use "group_sub", as ACF stores them.
 * @param int    $post_id Pet ID.
 * @return mixed
 */
function pawffee_adoptions_field( $name, $post_id ) {
	if ( function_exists( 'get_field' ) ) {
		return get_field( $name, $post_id );
	}

	$value = get_post_meta( $post_id, $name, true );

	// Without ACF, rebuild the two groups from their stored sub-fields.
	if ( 'age' === $name ) {
		return array(
			'value' => get_post_meta( $post_id, 'age_value', true ),
			'unit'  => get_post_meta( $post_id, 'age_unit', true ),
		);
	}
	if ( 'health' === $name ) {
		return array(
			'vaccinated'   => get_post_meta( $post_id, 'health_vaccinated', true ),
			'fixed'        => get_post_meta( $post_id, 'health_fixed', true ),
			'microchipped' => get_post_meta( $post_id, 'health_microchipped', true ),
		);
	}

	return $value;
}

/**
 * Everything the templates need about one pet.
 *
 * @param int|WP_Post $post Pet.
 * @return array
 */
function pawffee_adoptions_get_pet( $post ) {
	$post    = get_post( $post );
	$id      = $post->ID;
	$species = (string) pawffee_adoptions_field( 'species', $id );
	$status  = (string) pawffee_adoptions_field( 'status', $id );
	$age     = (array) pawffee_adoptions_field( 'age', $id );
	$health  = (array) pawffee_adoptions_field( 'health', $id );
	$good    = pawffee_adoptions_field( 'good_with', $id );
	$fee     = pawffee_adoptions_field( 'adoption_fee', $id );
	$sex     = (string) pawffee_adoptions_field( 'sex', $id );

	$statuses = pawffee_adoptions_statuses();

	return array(
		'id'            => $id,
		// Raw title, decoded once: the blocks escape it themselves.
		'name'          => wp_specialchars_decode( $post->post_title, ENT_QUOTES ),
		'url'           => get_permalink( $id ),
		'species'       => $species,
		'species_label' => 'cat' === $species ? __( 'Cat', 'pawffee-adoptions' ) : __( 'Dog', 'pawffee-adoptions' ),
		'sex'           => $sex,
		'sex_label'     => array(
			'female' => __( 'Female', 'pawffee-adoptions' ),
			'male'   => __( 'Male', 'pawffee-adoptions' ),
			'pair'   => __( 'Bonded pair', 'pawffee-adoptions' ),
		)[ $sex ] ?? '',
		'tagline'       => (string) pawffee_adoptions_field( 'tagline', $id ),
		'breed'         => (string) pawffee_adoptions_field( 'breed', $id ),
		'age_label'     => pawffee_adoptions_age_label( $age['value'] ?? '', $age['unit'] ?? 'years' ),
		'photo'         => (int) pawffee_adoptions_field( 'photo', $id ),
		'summary'       => (string) pawffee_adoptions_field( 'summary', $id ),
		'good_to_know'  => (string) pawffee_adoptions_field( 'good_to_know', $id ),
		'status'        => isset( $statuses[ $status ] ) ? $status : 'available',
		'status_label'  => $statuses[ $status ] ?? $statuses['available'],
		'adopted_on'    => (string) pawffee_adoptions_field( 'adopted_on', $id ),
		'good_with'     => is_array( $good ) ? $good : array(),
		'health'        => array(
			'vaccinated'   => ! empty( $health['vaccinated'] ),
			'fixed'        => ! empty( $health['fixed'] ),
			'microchipped' => ! empty( $health['microchipped'] ),
		),
		'fee'           => ( '' === $fee || null === $fee ) ? null : (float) $fee,
	);
}

/**
 * Adoption statuses, in the order staff see them.
 *
 * @return array<string,string>
 */
function pawffee_adoptions_statuses() {
	return array(
		'available' => __( 'Available', 'pawffee-adoptions' ),
		'pending'   => __( 'Adoption pending', 'pawffee-adoptions' ),
		'adopted'   => __( 'Adopted', 'pawffee-adoptions' ),
	);
}

/**
 * "4 months", "1 year", "3 years".
 *
 * @param mixed  $value Number.
 * @param string $unit  months|years.
 * @return string
 */
function pawffee_adoptions_age_label( $value, $unit ) {
	if ( '' === $value || null === $value ) {
		return '';
	}

	$n = (int) $value;

	return 'months' === $unit
		/* translators: %d: age in months. */
		? sprintf( _n( '%d month', '%d months', $n, 'pawffee-adoptions' ), $n )
		/* translators: %d: age in years. */
		: sprintf( _n( '%d year', '%d years', $n, 'pawffee-adoptions' ), $n );
}

/**
 * "Kids, dogs and cats".
 *
 * @param string[] $good Values from the Good with field.
 * @return string
 */
function pawffee_adoptions_good_with_label( $good ) {
	$labels = array(
		'kids' => __( 'kids', 'pawffee-adoptions' ),
		'dogs' => __( 'dogs', 'pawffee-adoptions' ),
		'cats' => __( 'cats', 'pawffee-adoptions' ),
	);
	$items  = array_values( array_intersect_key( $labels, array_flip( $good ) ) );

	if ( ! $items ) {
		return '';
	}

	$last = array_pop( $items );
	$text = $items ? implode( ', ', $items ) . ' ' . __( 'and', 'pawffee-adoptions' ) . ' ' . $last : $last;

	return ucfirst( $text );
}

/**
 * Pets for the Adoption page, in the order staff set with "Order".
 *
 * @param array $args species: all|dog|cat; statuses: string[].
 * @return WP_Post[]
 */
function pawffee_adoptions_query_pets( $args ) {
	$meta = array(
		array(
			'key'     => 'status',
			'value'   => $args['statuses'],
			'compare' => 'IN',
		),
	);

	if ( in_array( $args['species'], array( 'dog', 'cat' ), true ) ) {
		$meta[] = array(
			'key'   => 'species',
			'value' => $args['species'],
		);
	}

	return get_posts(
		array(
			'post_type'        => 'pawffee_pet',
			'post_status'      => 'publish',
			'posts_per_page'   => 50,
			'orderby'          => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'meta_query'       => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery -- small, bounded list.
			'suppress_filters' => false,
		)
	);
}

/**
 * Each pet's summary becomes its page's meta description (the theme prints it).
 *
 * @param string $text Description so far.
 * @return string
 */
function pawffee_adoptions_meta_description( $text ) {
	if ( is_singular( 'pawffee_pet' ) ) {
		$summary = (string) pawffee_adoptions_field( 'summary', get_queried_object_id() );
		return '' !== $summary ? $summary : $text;
	}
	return $text;
}
add_filter( 'pawffee_meta_description', 'pawffee_adoptions_meta_description' );
