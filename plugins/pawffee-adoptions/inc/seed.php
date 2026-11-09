<?php
/**
 * Demo installer: turns the four residents that used to be hand-built on
 * the Adoption page into Pet entries with ACF profiles.
 *
 * Run once with WP-CLI: wp eval "pawffee_adoptions_seed();"
 * The Playground blueprint runs it automatically after the theme's own
 * demo installer has imported the photos.
 *
 * @package PawffeeAdoptions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Create the demo pets. Safe to call twice: it only runs once per site.
 *
 * @return string[] Log lines.
 */
function pawffee_adoptions_seed() {
	if ( get_option( 'pawffee_adoptions_seeded' ) ) {
		return array( 'Pets already installed.' );
	}

	pawffee_adoptions_register_post_type();

	$log  = array();
	$pets = array(
		array(
			'title'   => 'Milo & Mango',
			'photo'   => 'milo-mango-puppies',
			'alt'     => 'Milo and Mango, two golden retriever puppies, sitting in the grass',
			'story'   => array(
				'Milo and Mango arrived together as eight-week-old pups and have not been more than a tail-length apart since. Mango is the explorer; Milo follows, then takes the credit.',
				'They are learning sit, wait and the art of the gentle greeting, and they are already experts at falling asleep mid-play. They need a home that can take both of them, with a yard or a park close by.',
			),
			'fields'  => array(
				'species'      => 'dog',
				'sex'          => 'pair',
				'tagline'      => 'Bonded pair',
				'breed'        => 'Golden Retriever Mix Puppies',
				'age'          => array( 'value' => 4, 'unit' => 'months' ),
				'summary'      => 'Double the joy, double the snuggles! Milo and Mango are playful, affectionate siblings who love to chase each other, tumble in the grass, and shower everyone with puppy kisses. They’re happiest together and looking to find a home as a pair.',
				'good_to_know' => 'Ideal for families or anyone ready for twice the fun! Meet them during our Pet Meet & Greet hours to see their sweet bond in action.',
				'status'       => 'available',
				'good_with'    => array( 'kids', 'dogs' ),
				'health'       => array( 'vaccinated' => 1, 'fixed' => 0, 'microchipped' => 1 ),
				'adoption_fee' => 450,
			),
		),
		array(
			'title'   => 'Milo',
			'photo'   => 'milo-orange-tabby',
			'alt'     => 'Milo, an orange tabby cat, enjoying a head scratch',
			'story'   => array(
				'Milo came to us from a foster home where he ran the household with a soft paw. At the café he greets the first customer every morning and supervises the espresso machine from the window ledge.',
				'He is happy with gentle children and calm cats, and he will ask for head scratches until you give in.',
			),
			'fields'  => array(
				'species'      => 'cat',
				'sex'          => 'male',
				'tagline'      => 'Café favourite',
				'breed'        => 'Orange Tabby Cat',
				'age'          => array( 'value' => 3, 'unit' => 'years' ),
				'summary'      => 'Playful, affectionate, and absolutely loves head scratches! Milo is quick to greet you with a purr and enjoys cozying up on laps.',
				'good_to_know' => 'He’s a café favourite who loves to watch people enjoy their drinks — come pet him anytime!',
				'status'       => 'available',
				'good_with'    => array( 'kids', 'cats' ),
				'health'       => array( 'vaccinated' => 1, 'fixed' => 1, 'microchipped' => 1 ),
				'adoption_fee' => 150,
			),
		),
		array(
			'title'   => 'Benny',
			'photo'   => 'benny-labrador-mix',
			'alt'     => 'Benny, a yellow labrador mix, lounging on a rug in the café',
			'story'   => array(
				'Benny has never met a stranger. He spends his café shifts moving from table to table, then claiming the sunbeam by the front window for a long nap.',
				'A family has applied to adopt him and is visiting this week. If it isn’t the right match, he’ll be ready for his next meet and greet.',
			),
			'fields'  => array(
				'species'      => 'dog',
				'sex'          => 'male',
				'tagline'      => 'Friendly to all',
				'breed'        => 'Labrador Mix',
				'age'          => array( 'value' => 2, 'unit' => 'years' ),
				'summary'      => 'Outgoing, gentle, and always happy to meet new friends. Benny is great with families and gets along with other dogs (especially around food and snacks!).',
				'good_to_know' => 'You’ll often find Benny lounging in the café sunbeams or joining you at the table for a treat.',
				'status'       => 'pending',
				'good_with'    => array( 'kids', 'dogs' ),
				'health'       => array( 'vaccinated' => 1, 'fixed' => 1, 'microchipped' => 1 ),
				'adoption_fee' => 250,
			),
		),
		array(
			'title'   => 'Cleo',
			'photo'   => 'cleo-ragdoll-lap-cat',
			'alt'     => 'Cleo, a fluffy ragdoll mix cat, resting on a guest\'s lap',
			'story'   => array(
				'Cleo is the café’s quiet hour. She picks one lap per visit, settles in, and stays until the cup is empty.',
				'She would love a calm home, ideally with another easygoing cat for company.',
			),
			'fields'  => array(
				'species'      => 'cat',
				'sex'          => 'female',
				'tagline'      => 'Lap specialist',
				'breed'        => 'Ragdoll Mix',
				'age'          => array( 'value' => 4, 'unit' => 'years' ),
				'summary'      => 'Gentle and calm, Cleo is the perfect lap cat — she loves curling up while you sip your drink. Socialized and affectionate, she’s great with both quiet cafés and gentle visitors.',
				'good_to_know' => 'Cleo would do best in a calm, caring home — possibly with another chill cat friend!',
				'status'       => 'available',
				'good_with'    => array( 'cats' ),
				'health'       => array( 'vaccinated' => 1, 'fixed' => 1, 'microchipped' => 1 ),
				'adoption_fee' => 150,
			),
		),
	);

	foreach ( $pets as $order => $pet ) {
		$content = '';
		foreach ( $pet['story'] as $paragraph ) {
			$content .= "<!-- wp:paragraph -->\n<p>" . esc_html( $paragraph ) . "</p>\n<!-- /wp:paragraph -->\n\n";
		}

		$id = wp_insert_post(
			array(
				'post_type'    => 'pawffee_pet',
				'post_status'  => 'publish',
				'post_title'   => $pet['title'],
				'post_content' => $content,
				'menu_order'   => $order + 1,
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			$log[] = 'Pet failed: ' . $pet['title'] . ' — ' . $id->get_error_message();
			continue;
		}

		$photo = pawffee_adoptions_seed_photo( $pet['photo'], $pet['alt'] );
		if ( $photo ) {
			$pet['fields']['photo'] = $photo;
			set_post_thumbnail( $id, $photo );
		}

		foreach ( $pet['fields'] as $name => $value ) {
			pawffee_adoptions_seed_field( $name, $value, $id );
		}
	}

	$log[] = count( $pets ) . ' pets created';
	update_option( 'pawffee_adoptions_seeded', time() );
	flush_rewrite_rules();

	return $log;
}

/**
 * Write one field. With ACF active this is update_field(), so ACF records
 * its field references; otherwise the same meta keys are written directly.
 *
 * @param string $name  Field name.
 * @param mixed  $value Value; groups are arrays.
 * @param int    $id    Pet ID.
 */
function pawffee_adoptions_seed_field( $name, $value, $id ) {
	if ( function_exists( 'update_field' ) ) {
		update_field( $name, $value, $id );
		return;
	}

	if ( is_array( $value ) && in_array( $name, array( 'age', 'health' ), true ) ) {
		foreach ( $value as $sub => $sub_value ) {
			update_post_meta( $id, $name . '_' . $sub, $sub_value );
		}
		return;
	}

	update_post_meta( $id, $name, $value );
}

/**
 * Find a demo photo the theme already imported, or import it from the
 * theme's demo folder. Either way, make sure it has alternative text.
 *
 * The theme's installer titles attachments with their alt text, so the
 * lookup matches the stored file name rather than the post slug.
 *
 * @param string $slug File name without extension.
 * @param string $alt  Alternative text to use if the photo has none.
 * @return int Attachment ID, or 0.
 */
function pawffee_adoptions_seed_photo( $slug, $alt ) {
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery -- one-off installer.
				array(
					'key'     => '_wp_attached_file',
					'value'   => '/' . $slug . '.webp',
					'compare' => 'LIKE',
				),
			),
		)
	);

	$id = $existing ? (int) $existing[0] : 0;

	if ( ! $id ) {
		$source = get_stylesheet_directory() . '/demo/media/' . $slug . '.webp';
		if ( ! file_exists( $source ) ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = wp_tempnam( $slug . '.webp' );
		copy( $source, $tmp );

		$id = media_handle_sideload(
			array(
				'name'     => $slug . '.webp',
				'tmp_name' => $tmp,
			),
			0,
			$alt
		);

		if ( is_wp_error( $id ) ) {
			return 0;
		}
	}

	if ( '' === (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) {
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	}

	return (int) $id;
}
