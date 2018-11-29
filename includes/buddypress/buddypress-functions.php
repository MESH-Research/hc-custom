<?php
/**
 * Customizations to BuddyPress Functions.
 *
 * @package Hc_Custom
 */

/**
 * Filters bp_legacy_object_template_path to fix group member directory bug.
 *
 * @param string $template_path Template Directory.
 */
function hc_custom_bp_legacy_object_template_path( $template_path ) {

	if ( ! empty( $_POST['template'] ) && 'groups/single/members' === $_POST['template'] ) {
		$template_part = 'groups/single/members.php';
		$template_path = bp_locate_template( array( $template_part ), false );
	}

	return $template_path;
}

add_filter( 'bp_legacy_object_template_path', 'hc_custom_bp_legacy_object_template_path' );
