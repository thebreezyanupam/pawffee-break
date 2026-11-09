<?php
/**
 * Pawffee Break Café — child theme functions.
 *
 * The parent theme (Twenty Twenty-Five) only enqueues its own stylesheet via
 * get_parent_theme_file_uri(), so a child theme stylesheet is never loaded
 * automatically. Everything the child theme needs is registered here.
 *
 * @package Pawffee_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PAWFFEE_VERSION', '3.1.0' );

require_once __DIR__ . '/inc/demo-content.php';

/**
 * Returns a cache-busting version string for a file inside the child theme.
 *
 * Falls back to the theme version if the file cannot be stat'd. Using the
 * modification time means an edited stylesheet or script reaches the browser
 * immediately instead of sitting behind a stale cached copy.
 *
 * @param string $relative_path Path relative to the child theme root.
 * @return string
 */
function pawffee_asset_version( $relative_path ) {
	$absolute = get_stylesheet_directory() . '/' . ltrim( $relative_path, '/' );

	if ( file_exists( $absolute ) ) {
		return PAWFFEE_VERSION . '.' . filemtime( $absolute );
	}

	return PAWFFEE_VERSION;
}

/**
 * Enqueues the child theme stylesheet, typefaces and interaction script.
 */
function pawffee_enqueue_assets() {
	// Google Fonts. Preconnect first so the font request starts early.
	wp_enqueue_style(
		'pawffee-fonts',
		'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600;9..144,700&family=Manrope:wght@300;400;500;600;700&family=Fira+Code:wght@400;500&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'pawffee-child-style',
		get_stylesheet_uri(),
		array( 'twentytwentyfive-style', 'pawffee-fonts' ),
		pawffee_asset_version( 'style.css' )
	);

	wp_enqueue_script(
		'pawffee-motion',
		get_stylesheet_directory_uri() . '/assets/js/pawffee.js',
		array(),
		pawffee_asset_version( 'assets/js/pawffee.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'pawffee_enqueue_assets' );

/**
 * Adds the resource hints Google Fonts needs to avoid a render-blocking round trip.
 *
 * @param array  $hints         Existing URLs for the given relation type.
 * @param string $relation_type The relation type the URLs are printed for.
 * @return array
 */
function pawffee_resource_hints( $hints, $relation_type ) {
	if ( 'preconnect' === $relation_type && wp_style_is( 'pawffee-fonts', 'enqueued' ) ) {
		$hints[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}

	return $hints;
}
add_filter( 'wp_resource_hints', 'pawffee_resource_hints', 10, 2 );

/**
 * Mirrors the front end styles inside the block editor so the editing
 * experience matches the published page.
 */
function pawffee_editor_styles() {
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'pawffee_editor_styles' );

/**
 * Flags the document as script-capable before the first paint.
 *
 * Every reveal animation hides its target from the stylesheet, gated behind
 * .pw-js. Setting the class here — inline, in <head>, ahead of any content —
 * means the hidden state is in effect from the very first paint instead of
 * being applied after the browser has already drawn the element. With
 * JavaScript off the class is never set and the page renders fully visible.
 *
 * The timer is a safety net: if the motion script fails to boot for any
 * reason, .pw-fallback forces everything visible rather than leaving a blank
 * page behind.
 */
function pawffee_boot_flag() {
	?>
	<script>
		( function ( root ) {
			root.classList.add( 'pw-js' );
			var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
			var seen = null, nav = null;
			try {
				seen = window.sessionStorage.getItem( 'pw-intro-seen' );
				nav = window.sessionStorage.getItem( 'pw-nav' );
			} catch ( e ) {}
			if ( ! reduce && ! seen && <?php echo is_front_page() ? 'true' : 'false'; ?> ) {
				root.classList.add( 'pw-intro' );
			}
			if ( ! reduce && nav ) {
				root.classList.add( 'pw-arrive' );
			}
		}( document.documentElement ) );
		window.setTimeout( function () {
			if ( ! document.documentElement.classList.contains( 'pw-booted' ) ) {
				document.documentElement.classList.add( 'pw-fallback' );
				document.documentElement.classList.remove( 'pw-intro', 'pw-arrive' );
			}
		}, 1800 );
	</script>
	<?php
}
add_action( 'wp_head', 'pawffee_boot_flag', 1 );

/**
 * Registers the block styles used by the café layouts.
 */
function pawffee_block_styles() {
	register_block_style(
		'core/group',
		array(
			'name'  => 'pawffee-marquee',
			'label' => __( 'Marquee strip', 'pawffee-child' ),
		)
	);

	register_block_style(
		'core/image',
		array(
			'name'  => 'pawffee-reveal',
			'label' => __( 'Wipe reveal', 'pawffee-child' ),
		)
	);

	register_block_style(
		'core/heading',
		array(
			'name'  => 'pawffee-display',
			'label' => __( 'Editorial display', 'pawffee-child' ),
		)
	);
}
add_action( 'init', 'pawffee_block_styles' );

/**
 * Registers a pattern category for the café patterns.
 */
function pawffee_pattern_categories() {
	register_block_pattern_category(
		'pawffee',
		array(
			'label'       => __( 'Pawffee Break', 'pawffee-child' ),
			'description' => __( 'Layouts built for the Pawffee Break café.', 'pawffee-child' ),
		)
	);
}
add_action( 'init', 'pawffee_pattern_categories' );

/**
 * Prints the grain and cursor layers once per page, immediately after <body>.
 * These are presentational chrome, so they are injected rather than baked into
 * every template part.
 */
function pawffee_chrome() {
	?>
	<div class="pawffee-grain" aria-hidden="true"></div>
	<div class="pawffee-cursor" aria-hidden="true"><span class="pawffee-cursor__dot"></span><span class="pawffee-cursor__label"></span></div>
	<div class="pw-progress" aria-hidden="true"></div>
	<div class="pw-curtain" aria-hidden="true">
		<span class="pw-curtain__layer pw-curtain__layer--accent"></span>
		<span class="pw-curtain__layer pw-curtain__layer--ink">
			<span class="pw-curtain__content">
				<?php echo pawffee_paw_svg( 'pw-curtain__paw' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="pw-curtain__label"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			</span>
		</span>
	</div>
	<script>
		/* Arriving from a transition: show the destination name before first paint. */
		try {
			( function () {
				var label = window.sessionStorage.getItem( 'pw-nav' );
				if ( label && label !== '1' ) {
					document.querySelector( '.pw-curtain__label' ).textContent = label;
				}
			}() );
		} catch ( e ) {}
	</script>
	<?php if ( is_front_page() ) : ?>
	<div class="pw-loader" aria-hidden="true">
		<span class="pw-loader__layer pw-loader__layer--accent"></span>
		<span class="pw-loader__layer pw-loader__layer--ink">
			<span class="pw-loader__center">
				<?php echo pawffee_paw_svg( 'pw-loader__paw' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="pw-loader__brand">
					<?php
					$name = get_bloginfo( 'name' );
					foreach ( preg_split( '//u', $name, -1, PREG_SPLIT_NO_EMPTY ) as $i => $char ) {
						printf(
							'<span style="--i:%d">%s</span>',
							(int) $i,
							' ' === $char ? '&nbsp;' : esc_html( $char )
						);
					}
					?>
				</span>
				<span class="pw-loader__line">Sip · Relax · Adopt</span>
			</span>
			<span class="pw-loader__meta">
				<span>Hamilton, ON</span>
				<span class="pw-loader__count">0</span>
			</span>
			<span class="pw-loader__bar"></span>
		</span>
	</div>
	<?php endif; ?>
	<?php
}
add_action( 'wp_body_open', 'pawffee_chrome' );

/**
 * Small paw-print mark used by the loader and page transition.
 *
 * @param string $class CSS class for the SVG.
 * @return string
 */
function pawffee_paw_svg( $class ) {
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 48 48" aria-hidden="true" focusable="false">'
		. '<ellipse cx="11" cy="20" rx="4.6" ry="6" transform="rotate(-18 11 20)"/>'
		. '<ellipse cx="19.5" cy="11.5" rx="4.6" ry="6.4" transform="rotate(-6 19.5 11.5)"/>'
		. '<ellipse cx="28.5" cy="11.5" rx="4.6" ry="6.4" transform="rotate(6 28.5 11.5)"/>'
		. '<ellipse cx="37" cy="20" rx="4.6" ry="6" transform="rotate(18 37 20)"/>'
		. '<path d="M24 22.5c-6.6 0-12.5 7.4-12.5 12.6 0 4 3.1 5.9 6.4 5.9 2.6 0 4-1.3 6.1-1.3s3.5 1.3 6.1 1.3c3.3 0 6.4-1.9 6.4-5.9 0-5.2-5.9-12.6-12.5-12.6z"/>'
		. '</svg>';
}

/* --------------------------------------------------------------------------
 * Performance
 * ----------------------------------------------------------------------- */

/**
 * Serves the WebP copy of an uploaded image wherever one exists next to the
 * original. The PNG originals run 1.4–1.8 MB; their WebP twins are ~5% of
 * that. Applied to rendered block HTML, so editor content stays untouched and
 * any image without a WebP twin simply keeps its original URL.
 *
 * @param string $html Rendered block markup.
 * @return string
 */
function pawffee_prefer_webp( $html ) {
	if ( false === strpos( $html, '/wp-content/uploads/' ) ) {
		return $html;
	}

	static $cache = array();
	$base_dir     = wp_get_upload_dir()['basedir'];

	return preg_replace_callback(
		'#((?:https?://[^/"\'\s]+)?/wp-content/uploads/([^"\'\s,]+?))\.(png|jpe?g)\b#i',
		function ( $m ) use ( &$cache, $base_dir ) {
			$relative = $m[2];

			if ( ! isset( $cache[ $relative ] ) ) {
				$cache[ $relative ] = file_exists( $base_dir . '/' . $relative . '.webp' );
			}

			return $cache[ $relative ] ? $m[1] . '.webp' : $m[0];
		},
		$html
	);
}
add_filter( 'render_block', 'pawffee_prefer_webp', 20 );

/**
 * Makes root-relative links in theme parts and content ("/adoption/",
 * "/wp-content/uploads/…") work when WordPress lives in a sub-directory, such
 * as the scoped path WordPress Playground serves the demo from. A no-op on a
 * site installed at the domain root.
 *
 * @param string $html Rendered block markup.
 * @return string
 */
function pawffee_root_relative_urls( $html ) {
	static $prefix = null;

	if ( null === $prefix ) {
		$prefix = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
	}

	if ( '' === $prefix || false === strpos( $html, '="/' ) ) {
		return $html;
	}

	return preg_replace_callback(
		'#\b(href|src)="(/(?!/)[^"]*)"#',
		function ( $m ) use ( $prefix ) {
			// Already prefixed, or a protocol-relative URL.
			if ( 0 === strpos( $m[2], $prefix . '/' ) || $m[2] === $prefix ) {
				return $m[0];
			}

			return $m[1] . '="' . $prefix . $m[2] . '"';
		},
		$html
	);
}
add_filter( 'render_block', 'pawffee_root_relative_urls', 5 );

/**
 * Writes a WebP twin next to every newly uploaded PNG/JPEG and each of its
 * generated sizes, so new photos are picked up by pawffee_prefer_webp()
 * without a manual conversion step.
 *
 * @param array $metadata      Attachment metadata.
 * @param int   $attachment_id Attachment ID.
 * @return array
 */
function pawffee_make_webp_twins( $metadata, $attachment_id ) {
	$file = get_attached_file( $attachment_id );

	if ( ! $file || ! preg_match( '/\.(png|jpe?g)$/i', $file ) || ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		return $metadata;
	}

	$files = array( $file );
	$dir   = dirname( $file );

	if ( ! empty( $metadata['original_image'] ) ) {
		$files[] = $dir . '/' . $metadata['original_image'];
	}

	foreach ( (array) ( $metadata['sizes'] ?? array() ) as $size ) {
		if ( ! empty( $size['file'] ) ) {
			$files[] = $dir . '/' . $size['file'];
		}
	}

	foreach ( array_unique( $files ) as $source ) {
		$target = preg_replace( '/\.(png|jpe?g)$/i', '.webp', $source );

		if ( ! file_exists( $source ) || file_exists( $target ) ) {
			continue;
		}

		$editor = wp_get_image_editor( $source );

		if ( ! is_wp_error( $editor ) ) {
			$editor->set_quality( 80 );
			$editor->save( $target, 'image/webp' );
		}
	}

	return $metadata;
}
add_filter( 'wp_generate_attachment_metadata', 'pawffee_make_webp_twins', 20, 2 );
add_filter( 'post_thumbnail_html', 'pawffee_prefer_webp', 20 );
add_filter( 'wp_get_attachment_image', 'pawffee_prefer_webp', 20 );

/**
 * FooGallery builds its own markup outside the block renderer, so its output
 * is passed through the same WebP swap.
 *
 * @param string $html Gallery HTML.
 * @return string
 */
function pawffee_foogallery_webp( $html ) {
	return is_string( $html ) ? pawffee_prefer_webp( $html ) : $html;
}
add_filter( 'foogallery_attachment_html_image', 'pawffee_foogallery_webp', 20 );
add_filter( 'foogallery_attachment_resize_thumbnail', 'pawffee_foogallery_webp', 20 );

/**
 * Replaces YouTube embeds with a lightweight click-to-play preview.
 *
 * The real player pulls in roughly a megabyte of YouTube scripts on page load
 * whether anyone presses play or not. The preview is a thumbnail and a button;
 * the iframe is created only on click (see pawffee.js).
 *
 * @param string $content Rendered embed block.
 * @param array  $block   Parsed block.
 * @return string
 */
function pawffee_youtube_facade( $content, $block ) {
	$url = $block['attrs']['url'] ?? '';

	if ( ! preg_match( '#(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{11})#', $url, $m ) ) {
		return $content;
	}

	$id    = $m[1];
	$title = __( 'Play video', 'pawffee-child' );

	if ( preg_match( '#<iframe[^>]+title="([^"]*)"#', $content, $t ) ) {
		$title = html_entity_decode( $t[1], ENT_QUOTES );
	}

	return sprintf(
		'<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube pw-yt"><div class="wp-block-embed__wrapper">'
		. '<button type="button" class="pw-yt__btn" data-pw-yt="%1$s" aria-label="%2$s">'
		. '<img class="pw-yt__thumb" src="https://i.ytimg.com/vi/%1$s/hqdefault.jpg" alt="" loading="lazy" decoding="async" width="480" height="360">'
		. '<span class="pw-yt__play" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 5.5v13l11-6.5z"/></svg></span>'
		. '<span class="pw-yt__title">%3$s</span>'
		. '</button></div></figure>',
		esc_attr( $id ),
		esc_attr( sprintf( /* translators: %s: video title */ __( 'Play video: %s', 'pawffee-child' ), $title ) ),
		esc_html( $title )
	);
}
add_filter( 'render_block_core/embed', 'pawffee_youtube_facade', 10, 2 );

/**
 * Keeps oEmbed responses cached for a month. With the one-day default, the
 * first visit after expiry waited on a live round trip to YouTube before the
 * homepage could render — the multi-second spikes seen on the front page.
 *
 * @return int
 */
function pawffee_oembed_ttl() {
	return MONTH_IN_SECONDS;
}
add_filter( 'oembed_ttl', 'pawffee_oembed_ttl' );

/**
 * Starts fetching an internal page as soon as the pointer rests on its link,
 * so the next page is usually ready before the transition finishes.
 *
 * @param array|null $config Speculative loading configuration.
 * @return array|null
 */
function pawffee_speculation_rules( $config ) {
	if ( is_array( $config ) ) {
		// Prefetch rather than prerender: a prerendered page runs its scripts
		// before the click, which would skip the arrival transition.
		$config['mode']      = 'prefetch';
		$config['eagerness'] = 'moderate';
	}

	return $config;
}
add_filter( 'wp_speculation_rules_configuration', 'pawffee_speculation_rules' );

/**
 * Takes WP-Cron off the page-render path for front-end visits.
 *
 * By default WordPress spawns cron with a loopback HTTP request to itself
 * during page generation. On this server that loopback blocked the response
 * for 1s and sometimes several — the homepage's multi-second spikes. Front-end
 * views now skip the spawn; when jobs are due, the browser pings wp-cron.php
 * in the background after the page has loaded, so scheduled tasks still run
 * without anyone waiting on them. Admin and other requests are unchanged.
 */
function pawffee_defer_cron() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	remove_action( 'init', 'wp_cron' );
	add_action( 'wp_footer', 'pawffee_cron_ping', 100 );
}
add_action( 'after_setup_theme', 'pawffee_defer_cron' );

/**
 * Prints the background cron ping, only when jobs are actually due.
 */
function pawffee_cron_ping() {
	if ( ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) || ! function_exists( 'wp_get_ready_cron_jobs' ) || ! wp_get_ready_cron_jobs() ) {
		return;
	}
	?>
	<script>
		window.addEventListener( 'load', function () {
			window.setTimeout( function () {
				try {
					fetch( <?php echo wp_json_encode( site_url( 'wp-cron.php?doing_wp_cron' ) ); ?>, { keepalive: true, credentials: 'omit' } );
				} catch ( e ) {}
			}, 2500 );
		} );
	</script>
	<?php
}

/**
 * Drops the emoji detection script and styles; the site uses none.
 */
function pawffee_disable_emoji() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'pawffee_disable_emoji' );

/**
 * Hints the browser to fetch the hero showcase image early on the home page.
 */
function pawffee_preload_hero() {
	if ( ! is_front_page() ) {
		return;
	}

	$file = '/wp-content/uploads/2025/10/Gemini_Generated_Image_tazcxitazcxitazc.webp';

	if ( file_exists( ABSPATH . ltrim( $file, '/' ) ) ) {
		printf( '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n", esc_url( home_url( $file ) ) );
	}
}
add_action( 'wp_head', 'pawffee_preload_hero', 2 );
