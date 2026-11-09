<?php
/**
 * Single pet profile, for the pawffee_pet template.
 *
 * @package PawffeeAdoptions
 *
 * @var WP_Block $block Block instance.
 */

defined( 'ABSPATH' ) || exit;

$pawffee_id = (int) ( $block->context['postId'] ?? get_the_ID() );

if ( ! $pawffee_id || 'pawffee_pet' !== get_post_type( $pawffee_id ) ) {
	return;
}

$pawffee_markup  = '<!-- wp:group {"align":"full","className":"pw-pets pawffee-pet-profile","layout":{"type":"constrained"}} --><div class="wp-block-group alignfull pw-pets pawffee-pet-profile">';
$pawffee_markup .= pawffee_adoptions_pet_markup(
	pawffee_adoptions_get_pet( $pawffee_id ),
	array(
		'index'   => '',
		'flipped' => false,
		'heading' => 1,
		'detail'  => true,
	)
);
$pawffee_markup .= '</div><!-- /wp:group -->';

echo pawffee_adoptions_render( $pawffee_markup ); // phpcs:ignore WordPress.Security.EscapeOutput -- every value is escaped while the markup is built.
