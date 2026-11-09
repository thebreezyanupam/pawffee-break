<?php
/**
 * Single pet template, registered by the plugin (WordPress 6.7+).
 *
 * It uses the active theme's header and footer, so it looks native in any
 * block theme. A theme can still override it with its own
 * templates/single-pawffee_pet.html, and staff can edit it in
 * Appearance > Editor > Templates.
 *
 * @package PawffeeAdoptions
 */

defined( 'ABSPATH' ) || exit;

function pawffee_adoptions_register_templates() {
	if ( ! function_exists( 'register_block_template' ) ) {
		return;
	}

	$story = __( 'Their story', 'pawffee-adoptions' );

	register_block_template(
		'pawffee-adoptions//single-pawffee_pet',
		array(
			'title'       => __( 'Single Pet', 'pawffee-adoptions' ),
			'description' => __( 'A pet\'s profile: photo, facts from their ACF profile, and their story.', 'pawffee-adoptions' ),
			'post_types'  => array( 'pawffee_pet' ),
			'content'     => '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"default"}} -->
<main class="wp-block-group"><!-- wp:pawffee/pet-profile /-->

<!-- wp:group {"align":"full","className":"pawffee-story","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull pawffee-story"><!-- wp:heading {"className":"pawffee-story__title"} -->
<h2 class="wp-block-heading pawffee-story__title">' . esc_html( $story ) . '</h2>
<!-- /wp:heading -->

<!-- wp:post-content {"layout":{"type":"constrained"}} /--></div>
<!-- /wp:group --></main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->',
		)
	);
}
add_action( 'init', 'pawffee_adoptions_register_templates' );
