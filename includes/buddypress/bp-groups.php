<?php
/**
 * Customizations to BuddyPress Groups.
 *
 * @package Hc_Custom
 */

/**
 * Adds new button labels for society members. For non society see hcommons_add_non_society_member_join_group_button()
 *
 * @param $button
 * @param $group
 *
 * @return mixed
 */
function hcommons_filter_groups_button_labels ($button, $group) {
	$status = $group->status;
	if ( ! is_super_admin() && hcommons_check_non_member_active_session() ) {
		$button['link_class'] = "disabled-button";
	}
	if(empty(BP_Groups_Member::check_is_member(get_current_user_id(), $group->id))) {
		switch($status) {
			case 'public':
				$button['link_text'] = "Join Group";
				break;

			case 'private':
				$button['link_text'] = "Request Membership";
				break;
		}
	}

	return $button;
}
add_filter( 'bp_get_group_join_button', 'hcommons_filter_groups_button_labels', 10 , 2 );

/**
 * Filters the action for the new group activity update.
 *
 * @param string $activity_action The new group activity update.
 */
function hcommons_filter_groups_activity_new_update_action( $activity_action ) {
	$activity_action = preg_replace( '/(in the group <a href="[^"]*)(">)/', '\1activity\2', $activity_action );
	return $activity_action;
}
add_filter( 'groups_activity_new_update_action', 'hcommons_filter_groups_activity_new_update_action' );

/**
 * Adds join group button for non-society members
 */
function hcommons_add_non_society_member_join_group_button() {
	if ( ! is_super_admin() && hcommons_check_non_member_active_session() ) {
		global $groups_template;

		// Set group to current loop group if none passed.
		if ( empty( $group ) ) {
			$group =& $groups_template->group;
		}
		$is_not_committee = strtolower( \groups_get_groupmeta( $group->id, 'society_group_type', true ) ) !== "committee";

		if($is_not_committee) {
			$message = 'Join Group';
			if ($group->status === "private") {
				$message = 'Request Membership</div>';
			}
			echo '<div class="disabled-button">'.$message.'</div>';
		}
	}
}
add_action( 'bp_directory_groups_actions', 'hcommons_add_non_society_member_join_group_button' );

/**
 * Adds disclaimer notice for non society members
 */
function hcommons_add_non_society_member_disclaimer_group() {
	if ( ! is_super_admin() && hcommons_check_non_member_active_session() ) {
		printf(
			'<div class="non-member-disclaimer">Only %1$s members can join these groups.<br><a href="/register">Join %1$s now</a>!</div>',
			strtoupper( Humanities_Commons::$society_id )
		);
	}
}
add_action( 'bp_before_directory_groups_content', 'hcommons_add_non_society_member_disclaimer_group' );

/**
 * On the current group page, reconfigure the group nav when a forum is
 * enabled for the group.
 *
 * What we do here is:
 *  - move the 'Forum' tab to the beginning of the nav
 *  - rename the 'Home' tab to 'Activity'
 */
function hcommons_override_config_group_nav() {
		$group_slug = bp_current_item();

		// BP 2.6+.
	if ( function_exists( 'bp_rest_api_init' ) ) {
			buddypress()->groups->nav->edit_nav( array( 'position' => 1 ), 'forum', $group_slug );
			buddypress()->groups->nav->edit_nav( array( 'position' => 0 ), 'home', $group_slug );
			buddypress()->groups->nav->edit_nav( array( 'name' => __( 'Activity', 'buddypress' ) ), 'home', $group_slug );

		// Older versions of BP.
	} else {
			buddypress()->bp_options_nav[ $group_slug ]['home']['position']  = 0;
			buddypress()->bp_options_nav[ $group_slug ]['forum']['position'] = 1;
			buddypress()->bp_options_nav[ $group_slug ]['home']['name']      = __( 'Activity', 'buddypress' );
	}

}

/**
 * Set the group default tab to 'forum' if the current group has a forum
 * attached to it.
 *
 * @param string $retval Navigation slug.
 */
function hcommons_override_cbox_set_group_default_tab( $retval ) {
		// Check if bbPress or legacy forums are active and configured properly.
	if ( ( function_exists( 'bbp_is_group_forums_active' ) && bbp_is_group_forums_active() ) ||
				( function_exists( 'bp_forums_is_installed_correctly' ) && bp_forums_is_installed_correctly() ) ) {

			// If current group does not have a forum attached, stop now!
		if ( ! bp_group_is_forum_enabled( groups_get_current_group() ) ) {
					return $retval;
		}

			// Allow non-logged-in users to view a private group's homepage.
		if ( false === is_user_logged_in() && groups_get_current_group() && 'private' === bp_get_new_group_status() ) {
				return $retval;
		}

			// Reconfigure the group's nav.
			add_action( 'bp_actions', 'hcommons_override_config_group_nav', 99 );

			// Finally, use 'forum' as the default group tab.
			return 'home';
	}

		return $retval;
}
add_filter( 'bp_groups_default_extension', 'hcommons_override_cbox_set_group_default_tab', 100 );

/**
 * BuddyPress Groups Forbidden Group Slugs
 * Used for groups that have been redirected or slugs that we want to reserve.
 *
 * @param array $forbidden_names List of forbidden group slugs.
 */
function mla_bp_groups_forbidden_names( $forbidden_names ) {

	$mla_forbidden_group_slugs = array(
		'style',
	);

	return array_merge( $forbidden_names, $mla_forbidden_group_slugs );

}
add_filter( 'groups_forbidden_names', 'mla_bp_groups_forbidden_names', 10, 1 );

/**
 * Set forums' status to match the privacy status of the associated group.
 *
 * Fired whenever a group is saved.
 *
 * @param BP_Groups_Group $group Group object.
 */
function update_group_forum_visibility( BP_Groups_Group $group ) {
	global $wpdb;

	$bp = buddypress();

	// Get group forum IDs.
	$forum_ids = bbp_get_group_forum_ids( $group->id );

	// Bail if no forum IDs available.
	if ( empty( $forum_ids ) ) {
			return;
	}

	// Loop through forum IDs.
	foreach ( $forum_ids as $forum_id ) {

		// Get forum from ID.
		$forum = bbp_get_forum( $forum_id );

		// Check for change.
		if ( $group->status !== $forum->post_status ) {
			switch ( $group->status ) {

				// Changed to hidden.
				case 'hidden':
						bbp_hide_forum( $forum_id, $forum->post_status );
					break;

				// Changed to private.
				case 'private':
						bbp_privatize_forum( $forum_id, $forum->post_status );
					break;

				// Changed to public.
				case 'public':
				default:
						bbp_publicize_forum( $forum_id, $forum->post_status );
					break;
			}
		}
	}

	// Update activity table.
	switch ( $group->status ) {
		// Changed to hidden.
		case 'hidden':
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %s SET hide_sitewide = 1 WHERE item_id = %d AND component = 'groups'",
					$bp->activity->table_name,
					$group->id
				)
			);
			break;

		// Changed to private.
		case 'private':
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %s SET hide_sitewide = 1 WHERE item_id = %d AND component = 'groups'",
					$bp->activity->table_name,
					$group->id
				)
			);
			break;

		// Changed to public.
		case 'public':
		default:
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %s SET hide_sitewide = 0 WHERE item_id = %d AND component = 'groups'",
					$bp->activity->table_name,
					$group->id
				)
			);
			break;
	}
}

add_action( 'groups_group_after_save', 'update_group_forum_visibility' );
