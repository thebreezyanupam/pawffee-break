<?php
/**
 * Adoptable pets list.
 *
 * @package PawffeeAdoptions
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$pawffee_statuses = array( 'available' );
if ( ! empty( $attributes['showPending'] ) ) {
	$pawffee_statuses[] = 'pending';
}
if ( ! empty( $attributes['showAdopted'] ) ) {
	$pawffee_statuses[] = 'adopted';
}

$pawffee_pets  = pawffee_adoptions_query_pets(
	array(
		'species'  => $attributes['species'] ?? 'all',
		'statuses' => $pawffee_statuses,
	)
);
$pawffee_total = count( $pawffee_pets );

$pawffee_markup = '<!-- wp:group {"align":"full","className":"pw-pets pawffee-pet-list","layout":{"type":"constrained"}} --><div class="wp-block-group alignfull pw-pets pawffee-pet-list">';

if ( ! $pawffee_total ) {
	$pawffee_markup .= pawffee_adoptions_paragraph(
		'pawffee-empty',
		esc_html__( 'Everyone has found a home for now. New residents arrive often, so check back soon or come say hello at the café.', 'pawffee-adoptions' )
	);
}

foreach ( $pawffee_pets as $pawffee_i => $pawffee_post ) {
	$pawffee_markup .= pawffee_adoptions_pet_markup(
		pawffee_adoptions_get_pet( $pawffee_post ),
		array(
			'index'   => sprintf( '%02d / %02d', $pawffee_i + 1, $pawffee_total ),
			'flipped' => (bool) ( $pawffee_i % 2 ),
			'heading' => 2,
			'detail'  => false,
		)
	);
}

$pawffee_markup .= '</div><!-- /wp:group -->';

echo pawffee_adoptions_render( $pawffee_markup ); // phpcs:ignore WordPress.Security.EscapeOutput -- every value is escaped while the markup is built.
