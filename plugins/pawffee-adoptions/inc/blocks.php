<?php
/**
 * Blocks: the Adoption list and the single-pet profile.
 *
 * Both are dynamic blocks rendered in PHP from the ACF fields. Each one
 * writes the same core block markup (Columns, Image, Heading, Paragraph,
 * Buttons) the Adoption page was originally built from, then renders it
 * with do_blocks(). That keeps the theme's layout, styles and motion layer
 * working exactly as before, with no duplicated CSS.
 *
 * @package PawffeeAdoptions
 */

defined( 'ABSPATH' ) || exit;

function pawffee_adoptions_register_blocks() {
	register_block_type( PAWFFEE_ADOPTIONS_DIR . 'blocks/pet-list' );
	register_block_type( PAWFFEE_ADOPTIONS_DIR . 'blocks/pet-profile' );

	wp_register_style(
		'pawffee-adoptions',
		PAWFFEE_ADOPTIONS_URL . 'assets/pets.css',
		array(),
		PAWFFEE_ADOPTIONS_VERSION
	);
}
add_action( 'init', 'pawffee_adoptions_register_blocks' );

/**
 * Serialized block markup for one pet, in the theme's pw-pet layout.
 *
 * @param array $pet     From pawffee_adoptions_get_pet().
 * @param array $options index: "01 / 04" or ''; flipped: bool; heading: 2|1; detail: bool (profile page).
 * @return string Block markup, ready for do_blocks().
 */
function pawffee_adoptions_pet_markup( $pet, $options ) {
	$name    = esc_html( $pet['name'] );
	// "Milo & Mango" keeps the italic ampersand the original page used.
	$name    = str_replace( ' &amp; ', ' <em>&amp;</em> ', $name );
	$level   = (int) $options['heading'];
	$classes = 'pw-pet pawffee-pet pawffee-pet--' . $pet['status'] . ( $options['flipped'] ? ' is-flipped' : '' );

	$meta = array_filter(
		array(
			$pet['species_label'] . ( 'pair' === $pet['sex'] ? 's' : '' ),
			$pet['tagline'],
			'available' !== $pet['status'] ? $pet['status_label'] : '',
		)
	);

	$alt = (string) get_post_meta( $pet['photo'], '_wp_attachment_image_alt', true );
	if ( '' === $alt ) {
		/* translators: %s: pet name. */
		$alt = sprintf( __( 'Photo of %s', 'pawffee-adoptions' ), $pet['name'] );
	}
	$img_url = $pet['photo'] ? wp_get_attachment_image_url( $pet['photo'], 'medium_large' ) : '';

	$facts = array(
		__( 'Breed', 'pawffee-adoptions' ) => $pet['breed'],
		__( 'Age', 'pawffee-adoptions' )   => $pet['age_label'],
	);

	if ( $options['detail'] ) {
		$facts[ __( 'Sex', 'pawffee-adoptions' ) ]       = $pet['sex_label'];
		$facts[ __( 'Good with', 'pawffee-adoptions' ) ] = pawffee_adoptions_good_with_label( $pet['good_with'] );
		$facts[ __( 'Health', 'pawffee-adoptions' ) ]    = pawffee_adoptions_health_label( $pet['health'] );
		if ( null !== $pet['fee'] ) {
			$facts[ __( 'Adoption fee', 'pawffee-adoptions' ) ] = '$' . number_format_i18n( $pet['fee'] ) . ' CAD';
		}
		$facts[ __( 'Status', 'pawffee-adoptions' ) ] = 'adopted' === $pet['status'] && $pet['adopted_on']
			/* translators: %s: adoption date. */
			? sprintf( __( 'Adopted %s', 'pawffee-adoptions' ), date_i18n( get_option( 'date_format' ), strtotime( $pet['adopted_on'] ) ) )
			: $pet['status_label'];
	}

	$out  = '<!-- wp:columns {"align":"wide","className":' . wp_json_encode( $classes ) . '} -->' . "\n";
	$out .= '<div class="wp-block-columns alignwide ' . esc_attr( $classes ) . '">';

	// Photo column.
	$out .= '<!-- wp:column {"className":"pw-pet__media"} --><div class="wp-block-column pw-pet__media">';
	if ( $img_url ) {
		$out .= '<!-- wp:image {"id":' . (int) $pet['photo'] . ',"sizeSlug":"medium_large","linkDestination":"none","className":"pw-pet__img"} -->';
		$out .= '<figure class="wp-block-image size-medium_large pw-pet__img"><img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $alt ) . '" class="wp-image-' . (int) $pet['photo'] . '"/></figure>';
		$out .= '<!-- /wp:image -->';
	}
	$out .= '</div><!-- /wp:column -->';

	// Text column.
	$out .= '<!-- wp:column {"className":"pw-pet__body"} --><div class="wp-block-column pw-pet__body">';
	if ( $options['index'] ) {
		$out .= pawffee_adoptions_paragraph( 'pw-pet__index', esc_html( $options['index'] ) );
	}
	$out .= pawffee_adoptions_paragraph( 'pw-card__meta', esc_html( implode( ' · ', $meta ) ) );

	$heading_inner = $options['detail'] ? $name : '<a href="' . esc_url( $pet['url'] ) . '">' . $name . '</a>';
	$out          .= '<!-- wp:heading {"level":' . $level . ',"className":"pw-pet__name"} -->';
	$out          .= '<h' . $level . ' class="wp-block-heading pw-pet__name">' . $heading_inner . '</h' . $level . '>';
	$out          .= '<!-- /wp:heading -->';

	foreach ( $facts as $label => $value ) {
		if ( '' !== (string) $value ) {
			$out .= pawffee_adoptions_paragraph( 'pw-fact', '<span>' . esc_html( $label ) . '</span>' . esc_html( $value ) );
		}
	}

	if ( $pet['summary'] ) {
		$out .= pawffee_adoptions_paragraph( 'pw-pet__summary', esc_html( $pet['summary'] ) );
	}
	if ( $pet['good_to_know'] ) {
		$out .= pawffee_adoptions_paragraph( 'pw-pet__note', '<strong>' . esc_html__( 'Good to know', 'pawffee-adoptions' ) . '</strong> ' . esc_html( $pet['good_to_know'] ) );
	}

	// Calls to action. Adopted pets get none; the link text names the pet so
	// screen-reader users hear more than a list of identical buttons.
	if ( 'adopted' !== $pet['status'] ) {
		$primary = 'pending' === $pet['status']
			? __( 'Join the waitlist', 'pawffee-adoptions' )
			: __( 'Book a meet & greet', 'pawffee-adoptions' );

		$out .= '<!-- wp:buttons --><div class="wp-block-buttons">';
		$out .= pawffee_adoptions_button( home_url( '/contact-us/' ), $primary, '', $primary . ' — ' . $pet['name'] );
		if ( $options['detail'] ) {
			$out .= pawffee_adoptions_button( home_url( '/adoption/' ), __( 'Meet the other residents', 'pawffee-adoptions' ), 'is-style-outline' );
		} else {
			/* translators: %s: pet name. */
			$out .= pawffee_adoptions_button( $pet['url'], sprintf( __( 'Meet %s', 'pawffee-adoptions' ), $pet['name'] ), 'is-style-outline' );
		}
		$out .= '</div><!-- /wp:buttons -->';
	}

	$out .= '</div><!-- /wp:column -->';
	$out .= '</div>' . "\n" . '<!-- /wp:columns -->';

	return $out;
}

/**
 * A core Paragraph block with a class.
 *
 * @param string $class CSS class.
 * @param string $html  Already-escaped inner HTML.
 * @return string
 */
function pawffee_adoptions_paragraph( $class, $html ) {
	return '<!-- wp:paragraph {"className":"' . esc_attr( $class ) . '"} --><p class="' . esc_attr( $class ) . '">' . $html . '</p><!-- /wp:paragraph -->';
}

/**
 * A core Button block.
 *
 * @param string $url   Link.
 * @param string $text  Visible text.
 * @param string $class Style class, e.g. is-style-outline.
 * @param string $label Accessible name when it should say more than the text.
 * @return string
 */
function pawffee_adoptions_button( $url, $text, $class = '', $label = '' ) {
	$attrs = $class ? '{"className":"' . esc_attr( $class ) . '"}' : '';
	$aria  = $label ? ' aria-label="' . esc_attr( $label ) . '"' : '';

	return '<!-- wp:button ' . $attrs . ' --><div class="wp-block-button' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $url ) . '"' . $aria . '>' . esc_html( $text ) . '</a></div><!-- /wp:button -->';
}

/**
 * "Vaccinated, microchipped" or "Vaccinated · spay/neuter booked".
 *
 * @param array $health From the Health group.
 * @return string
 */
function pawffee_adoptions_health_label( $health ) {
	$done = array();
	$todo = array();

	foreach (
		array(
			'vaccinated'   => __( 'vaccinated', 'pawffee-adoptions' ),
			'fixed'        => __( 'spayed or neutered', 'pawffee-adoptions' ),
			'microchipped' => __( 'microchipped', 'pawffee-adoptions' ),
		) as $key => $label
	) {
		if ( ! empty( $health[ $key ] ) ) {
			$done[] = $label;
		} else {
			$todo[] = $label;
		}
	}

	if ( ! $done ) {
		return __( 'Health checks under way', 'pawffee-adoptions' );
	}

	$text = ucfirst( implode( ', ', $done ) );
	if ( $todo ) {
		/* translators: %s: health steps still to do. */
		$text .= ' · ' . sprintf( __( 'not yet %s', 'pawffee-adoptions' ), implode( ' or ', $todo ) );
	}

	return $text;
}

/**
 * Render block markup the same way post content is rendered, including
 * responsive images (srcset, sizes, lazy loading).
 *
 * @param string $markup Serialized blocks.
 * @return string HTML.
 */
function pawffee_adoptions_render( $markup ) {
	wp_enqueue_style( 'pawffee-adoptions' );
	return wp_filter_content_tags( do_blocks( $markup ) );
}
