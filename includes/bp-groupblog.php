<?php
/**
 * Customizations to bp-groupblog
 *
 * @package Hc_Custom
 */

/**
 * Remove users from group blog upon leaving group.
 *
 * @param int $group_id Group.
 * @param int $user_id User.
 */
function hcommons_remove_user_from_group_site( $group_id, $user_id ) {
	$blog_id = get_groupblog_blog_id( $group_id );
	remove_user_from_blog( $user_id, $blog_id );
}
add_action( 'groups_leave_group', 'hcommons_remove_user_from_group_site', 10, 2 );
