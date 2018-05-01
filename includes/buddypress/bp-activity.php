<?php
/**
 * Customizations to BuddyPress Activity.
 *
 * @package Hc_Custom
 */

/**
 * Removes the activity form if there is a discussion board.
 *
 * @param array  $templates Array of templates located.
 * @param string $slug      Template part slug requested.
 * @param string $name      Template part name requested.
 */
function hc_custom_template_part_filter( $templates, $slug, $name ) {

	if ( 'activity/post-form' !== $slug ) {
		return $templates;
	}

	if ( bp_is_group() ) {
		$bp = buddypress();

		// Get group forum IDs.
		$forum_ids = bbp_get_group_forum_ids( $group->id );

		// Bail if no forum IDs available.
		if ( empty( $forum_ids ) ) {
			return $templates;
		} else {
			return false;
		}
	}

}

add_filter( 'bp_get_template_part', 'hc_custom_template_part_filter', 10, 3 );
