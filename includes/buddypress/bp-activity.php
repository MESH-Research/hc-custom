<?php
/**
 * Customizations to BuddyPress Activity.
 *
 * @package Hc_Custom
 */

/**
 * Removes the activity form if there is a discussion board.
 */
function hc_custom_bp_before_group_activity_post_form() {
	$bp = buddypress();

	// Get group forum IDs.
	$forum_ids = bbp_get_group_forum_ids( $group->id );

	// Bail if no forum IDs available.
	if ( empty( $forum_ids ) ) :
		return;
	else :
?>
		<style>
		#whats-new-form {
		display: none;
		}
		</style>
<?php

	endif;
}

add_action( 'bp_before_group_activity_post_form', 'hc_custom_bp_before_group_activity_post_form' );
