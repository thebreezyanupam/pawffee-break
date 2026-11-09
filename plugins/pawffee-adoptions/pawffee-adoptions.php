<?php
/**
 * Plugin Name:       Pawffee Adoptions
 * Description:       Adoptable pets for the Pawffee Break site: a Pet post type with Advanced Custom Fields profiles, an adoption list block, a profile block and a single-pet template. Works with ACF or ACF PRO.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      8.0
 * Author:            Anupam Pandey
 * Author URI:        https://www.anupampandey.com
 * License:           All rights reserved
 * Text Domain:       pawffee-adoptions
 *
 * @package PawffeeAdoptions
 */

defined( 'ABSPATH' ) || exit;

define( 'PAWFFEE_ADOPTIONS_VERSION', '1.0.0' );
define( 'PAWFFEE_ADOPTIONS_DIR', plugin_dir_path( __FILE__ ) );
define( 'PAWFFEE_ADOPTIONS_URL', plugin_dir_url( __FILE__ ) );

require_once PAWFFEE_ADOPTIONS_DIR . 'inc/post-type.php';
require_once PAWFFEE_ADOPTIONS_DIR . 'inc/fields.php';
require_once PAWFFEE_ADOPTIONS_DIR . 'inc/pet-data.php';
require_once PAWFFEE_ADOPTIONS_DIR . 'inc/blocks.php';
require_once PAWFFEE_ADOPTIONS_DIR . 'inc/templates.php';
require_once PAWFFEE_ADOPTIONS_DIR . 'inc/admin.php';
require_once PAWFFEE_ADOPTIONS_DIR . 'inc/seed.php';

/**
 * Register the post type before flushing, so /pets/ URLs work straight away.
 */
function pawffee_adoptions_activate() {
	pawffee_adoptions_register_post_type();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'pawffee_adoptions_activate' );

function pawffee_adoptions_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'pawffee_adoptions_deactivate' );
