<?php
/**
 * Advanced Custom Fields setup.
 *
 * The "Pet profile" field group is stored as ACF local JSON in /acf-json,
 * so it is version-controlled with the code and loads without a database
 * import. Editing the group in Custom Fields > Field Groups writes the
 * change back to the same file.
 *
 * @package PawffeeAdoptions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load the plugin's field groups.
 *
 * @param string[] $paths Folders ACF reads JSON from.
 * @return string[]
 */
function pawffee_adoptions_acf_load_json( $paths ) {
	$paths[] = PAWFFEE_ADOPTIONS_DIR . 'acf-json';
	return $paths;
}
add_filter( 'acf/settings/load_json', 'pawffee_adoptions_acf_load_json' );

/**
 * Save edits to the Pet profile group back into the plugin, leaving every
 * other field group where ACF would normally put it.
 *
 * @return string
 */
function pawffee_adoptions_acf_save_json() {
	return PAWFFEE_ADOPTIONS_DIR . 'acf-json';
}
add_filter( 'acf/settings/save_json/key=group_pawffee_pet', 'pawffee_adoptions_acf_save_json' );

/**
 * Tell editors plainly when ACF is missing instead of failing quietly.
 */
function pawffee_adoptions_acf_notice() {
	if ( function_exists( 'acf_get_field_groups' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s</p></div>',
		esc_html__( 'Pawffee Adoptions needs Advanced Custom Fields.', 'pawffee-adoptions' ),
		esc_html__( 'Install and activate the free Advanced Custom Fields plugin to edit pet profiles. Existing pets still show on the site.', 'pawffee-adoptions' )
	);
}
add_action( 'admin_notices', 'pawffee_adoptions_acf_notice' );
