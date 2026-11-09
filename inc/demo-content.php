<?php
/**
 * Demo content installer.
 *
 * Builds the full Pawffee Break site — pages, stories, adoptable residents,
 * jobs, gallery, contact form and photos — from the bundle in /demo. The demo
 * shows an OpenStreetMap embed for the location; pawffee_seed_map() is kept for
 * installs that use WP Go Maps instead.
 * Nothing runs on its own: the WordPress Playground blueprint calls
 * pawffee_seed_demo() once. It is safe to call again; it stops if the demo
 * has already been installed.
 *
 * @package Pawffee_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installs the demo content. Returns a short log of what was created.
 *
 * @return string[]
 */
function pawffee_seed_demo() {
	$log = array();

	if ( get_option( 'pawffee_demo_seeded' ) ) {
		return array( 'Demo content already installed.' );
	}

	$dir    = get_stylesheet_directory() . '/demo';
	$bundle = json_decode( (string) file_get_contents( $dir . '/content.json' ), true );

	if ( ! is_array( $bundle ) ) {
		return array( 'Demo bundle missing or unreadable.' );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	// The installer runs without a logged-in user, so WordPress would filter
	// the bundled content as untrusted and strip the map <iframe>. The bundle
	// ships with the theme, so it is inserted as-is.
	kses_remove_filters();

	update_option( 'blogname', $bundle['site']['name'] );
	update_option( 'blogdescription', $bundle['site']['tagline'] );

	// Clear WordPress's sample content so it doesn't sit in the journal.
	foreach ( array( 'hello-world' => 'post', 'sample-page' => 'page' ) as $slug => $type ) {
		$sample = get_page_by_path( $slug, OBJECT, $type );
		if ( $sample ) {
			wp_delete_post( $sample->ID, true );
		}
	}

	/* Media ---------------------------------------------------------- */

	$media = array();

	foreach ( $bundle['media'] as $key => $item ) {
		$source = $dir . '/media/' . $item['file'];

		if ( ! file_exists( $source ) ) {
			continue;
		}

		// media_handle_sideload() moves the file, so hand it a copy.
		$tmp = wp_tempnam( $item['file'] );
		copy( $source, $tmp );

		$id = media_handle_sideload(
			array(
				'name'     => $item['file'],
				'tmp_name' => $tmp,
			),
			0,
			$item['alt']
		);

		if ( is_wp_error( $id ) ) {
			$log[] = 'Media failed: ' . $item['file'] . ' — ' . $id->get_error_message();
			continue;
		}

		update_post_meta( $id, '_wp_attachment_image_alt', $item['alt'] );
		$media[ (int) $key ] = $id;
	}

	$log[] = count( $media ) . ' photos imported';

	/* Plugin-backed pieces ------------------------------------------- */

	$gallery_id = pawffee_seed_gallery( $bundle['gallery'], $media );
	$form_id    = pawffee_seed_contact_form( $bundle['contact_form'] );
	if ( ! empty( $bundle['map'] ) ) {
		pawffee_seed_map( $bundle['map'] );
	}

	$fill = function ( $html ) use ( $media, $gallery_id, $form_id ) {
		$html = preg_replace_callback(
			'/\{\{url:(\d+):([a-z_]+)\}\}/',
			function ( $m ) use ( $media ) {
				$id = $media[ (int) $m[1] ] ?? 0;
				return $id ? (string) wp_get_attachment_image_url( $id, $m[2] ) : '';
			},
			$html
		);

		$html = preg_replace_callback(
			'/\{\{id:(\d+)\}\}/',
			function ( $m ) use ( $media ) {
				return (string) ( $media[ (int) $m[1] ] ?? 0 );
			},
			$html
		);

		return str_replace(
			array( '{{gallery}}', '{{cf7}}' ),
			array( (string) $gallery_id, (string) $form_id ),
			$html
		);
	};

	/* Pages ---------------------------------------------------------- */

	$page_ids = array();

	foreach ( $bundle['pages'] as $page ) {
		$existing = get_page_by_path( $page['slug'] );
		$args     = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $page['title'],
			'post_name'    => $page['slug'],
			'post_content' => wp_slash( $fill( $page['content'] ) ),
		);

		if ( $existing ) {
			$args['ID'] = $existing->ID;
		}

		$page_ids[ $page['slug'] ] = wp_insert_post( $args );
	}

	$blog = get_page_by_path( $bundle['blog_page']['slug'] );
	$page_ids[ $bundle['blog_page']['slug'] ] = $blog ? $blog->ID : wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => $bundle['blog_page']['title'],
			'post_name'   => $bundle['blog_page']['slug'],
		)
	);

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', (int) $page_ids[ $bundle['front_page'] ] );
	update_option( 'page_for_posts', (int) $page_ids[ $bundle['posts_page'] ] );
	$log[] = count( $page_ids ) . ' pages created';

	/* Stories -------------------------------------------------------- */

	foreach ( $bundle['posts'] as $post ) {
		$id = wp_insert_post(
			array(
				'post_type'     => 'post',
				'post_status'   => 'publish',
				'post_title'    => wp_slash( $post['title'] ),
				'post_name'     => $post['slug'],
				'post_date'     => $post['date'],
				'post_content'  => wp_slash( $fill( $post['content'] ) ),
				'post_excerpt'  => wp_slash( $post['excerpt'] ),
			)
		);

		if ( ! is_wp_error( $id ) && ! empty( $media[ $post['thumb'] ] ) ) {
			set_post_thumbnail( $id, $media[ $post['thumb'] ] );
		}
	}

	$log[] = count( $bundle['posts'] ) . ' stories created';

	/* Jobs ----------------------------------------------------------- */

	if ( post_type_exists( 'job_listing' ) ) {
		foreach ( $bundle['jobs'] as $job ) {
			$id = wp_insert_post(
				array(
					'post_type'    => 'job_listing',
					'post_status'  => 'publish',
					'post_title'   => wp_slash( $job['title'] ),
					'post_content' => wp_slash( $job['content'] ),
				)
			);

			if ( is_wp_error( $id ) ) {
				continue;
			}

			update_post_meta( $id, '_job_location', 'Hamilton, Ontario' );
			update_post_meta( $id, '_company_name', 'Pawffee Break' );
			update_post_meta( $id, '_application', 'jobs@pawffeebreak.example' );
			update_post_meta( $id, '_job_expires', gmdate( 'Y-m-d', strtotime( '+1 year' ) ) );
			update_post_meta( $id, '_filled', 0 );
			update_post_meta( $id, '_featured', 0 );
			update_post_meta( $id, '_remote_position', 0 );

			// WP Job Manager may not have registered its job types yet when
			// this runs straight after activation, so the type is recorded
			// here and applied by pawffee_assign_demo_job_types().
			update_post_meta( $id, '_pawffee_job_type', $job['type'] );
		}

		update_option( 'pawffee_demo_job_types_pending', 1 );
		pawffee_assign_demo_job_types();

		$log[] = count( $bundle['jobs'] ) . ' jobs created';
	}

	kses_init_filters();
	update_option( 'pawffee_demo_seeded', time() );

	return $log;
}

/**
 * Gives the demo jobs their job types once WP Job Manager's taxonomy exists.
 * Runs from the seeder and, if the taxonomy wasn't ready then, on a later
 * page load. Clears its flag when done, after which it costs one option read.
 */
function pawffee_assign_demo_job_types() {
	if ( ! get_option( 'pawffee_demo_job_types_pending' ) || ! taxonomy_exists( 'job_listing_type' ) ) {
		return;
	}

	$jobs = get_posts(
		array(
			'post_type'      => 'job_listing',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'meta_key'       => '_pawffee_job_type', // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'         => 'ids',
		)
	);

	foreach ( $jobs as $id ) {
		$slug = get_post_meta( $id, '_pawffee_job_type', true );
		$term = get_term_by( 'slug', $slug, 'job_listing_type' );

		if ( ! $term ) {
			$made = wp_insert_term( ucwords( str_replace( '-', ' ', $slug ) ), 'job_listing_type', array( 'slug' => $slug ) );
			$term = is_wp_error( $made ) ? null : get_term( $made['term_id'], 'job_listing_type' );
		}

		if ( $term ) {
			wp_set_object_terms( $id, (int) $term->term_id, 'job_listing_type' );
		}
	}

	delete_option( 'pawffee_demo_job_types_pending' );
}
add_action( 'init', 'pawffee_assign_demo_job_types', 99 );

/**
 * Makes OpenStreetMap the default WP Go Maps engine, so a fresh install shows
 * the café map without needing a Google Maps API key. Only affects defaults;
 * an engine chosen in the plugin's settings is left alone.
 *
 * @param array $settings Default plugin settings.
 * @return array
 */
function pawffee_wpgmza_default_engine( $settings ) {
	$settings['engine'] = 'open-layers';
	return $settings;
}
add_filter( 'wpgmza_plugin_get_default_settings', 'pawffee_wpgmza_default_engine' );

/**
 * Creates the FooGallery gallery used on the Gallery page.
 *
 * @param array $gallery Gallery definition from the bundle.
 * @param array $media   Map of bundle media keys to attachment IDs.
 * @return int Gallery post ID, or 0 when FooGallery is not active.
 */
function pawffee_seed_gallery( $gallery, $media ) {
	if ( ! post_type_exists( 'foogallery' ) ) {
		return 0;
	}

	$id = wp_insert_post(
		array(
			'post_type'   => 'foogallery',
			'post_status' => 'publish',
			'post_title'  => $gallery['title'],
		)
	);

	if ( is_wp_error( $id ) ) {
		return 0;
	}

	$attachments = array();

	foreach ( $gallery['media'] as $key ) {
		if ( ! empty( $media[ $key ] ) ) {
			$attachments[] = (string) $media[ $key ];
		}
	}

	update_post_meta( $id, 'foogallery_attachments', $attachments );
	update_post_meta( $id, 'foogallery_template', 'default' );
	update_post_meta( $id, 'foogallery_datasource', 'media_library' );

	if ( ! empty( $gallery['settings'] ) && is_array( $gallery['settings'] ) ) {
		update_post_meta( $id, '_foogallery_settings', $gallery['settings'] );
	}

	return $id;
}

/**
 * Creates the Contact Form 7 form used on the Contact page.
 *
 * @param array $form Form definition from the bundle.
 * @return int Form post ID, or 0 when Contact Form 7 is not active.
 */
function pawffee_seed_contact_form( $form ) {
	if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
		return 0;
	}

	$contact = WPCF7_ContactForm::get_template( array( 'title' => $form['title'] ) );
	$contact->set_properties( array( 'form' => $form['form'] ) );

	return (int) $contact->save();
}

/**
 * Points the WP Go Maps default map at the café and drops a marker on it.
 *
 * @param array $map Map definition from the bundle.
 */
function pawffee_seed_map( $map ) {
	global $wpdb;

	$maps    = $wpdb->prefix . 'wpgmza_maps';
	$markers = $wpdb->prefix . 'wpgmza';

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $maps ) ) !== $maps ) {
		return;
	}

	// Fresh installs default to the Google engine, which needs an API key.
	// The site uses the free OpenStreetMap (OpenLayers) engine instead.
	// Saved settings are switched here; if the plugin hasn't saved any yet,
	// pawffee_wpgmza_default_engine() sets the default it will save.
	$settings = json_decode( (string) get_option( 'wpgmza_global_settings', '' ), true );

	if ( is_array( $settings ) && ! empty( $settings ) ) {
		$settings['engine'] = 'open-layers';
		update_option( 'wpgmza_global_settings', wp_json_encode( $settings ) );
	}

	$map_id = (int) $wpdb->get_var( "SELECT id FROM {$maps} ORDER BY id ASC LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL

	if ( ! $map_id ) {
		return;
	}

	$wpdb->update(
		$maps,
		array(
			'map_title'      => 'Pawffee Break',
			'map_start_lat'  => $map['lat'],
			'map_start_lng'  => $map['lng'],
			'map_start_zoom' => $map['zoom'],
			'map_width'      => 100,
			'map_width_type' => '%',
			'map_height'     => 420,
		),
		array( 'id' => $map_id )
	);

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $markers ) ) === $markers ) {
		$wpdb->delete( $markers, array( 'map_id' => $map_id ) );
		$wpdb->insert(
			$markers,
			array(
				'map_id'  => $map_id,
				'address' => $map['address'],
				'lat'     => $map['lat'],
				'lng'     => $map['lng'],
				'title'   => 'Pawffee Break',
			)
		);
	}
}
