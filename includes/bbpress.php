<?php
/**
 * Customizations to bbpress
 *
 * @package Hc_Custom
 */

/**
 * Ensure forum posts by group admins who are society members are never marked as spam.
 */
function hcommons_allow_society_group_admins_ham() {
	$user_id = get_current_user_id();
	$group_ids = bbp_get_forum_group_ids( $_REQUEST['bbp_forum_id'] );
	$current_user_is_society_member = ( 1 < count( Humanities_Commons::hcommons_get_user_memberships() ) );

	foreach ( $group_ids as $group_id ) {
		if ( $current_user_is_society_member && groups_is_user_admin( $user_id, $group_id ) ) {
			remove_action( 'bbp_new_topic_pre_insert', [ bbpress()->extend->akismet, 'check_post' ], 1 );
			remove_action( 'bbp_new_reply_pre_insert', [ bbpress()->extend->akismet, 'check_post' ], 1 );
			break; // Trust user to crosspost if they're an admin of any group being posted to.
		}
	}
}
// Priority 1 to run before the actions that might be removed by this function.
add_action( 'bbp_new_topic_pre_insert', 'hcommons_allow_society_group_admins_ham', 0 );
