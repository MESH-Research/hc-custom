<?php
/**
 * Customizations to BuddyPress Groups.
 *
 * @package Hc_Custom
 */


function hcommons_filter_groups_activity_new_update_action( $activity_action ) {
	$activity_action = preg_replace( '/(in the group <a href="[^"]*)(">)/', '\1activity\2', $activity_action );
	return $activity_action;
}
add_filter( 'groups_activity_new_update_action', 'hcommons_filter_groups_activity_new_update_action' );

function hcommons_add_non_society_member_join_group_button() {
	if ( ! is_super_admin() && hcommons_check_non_member_active_session() ) {
		echo '<div class="disabled-button">Join Group</div>';
	}
}
add_action( 'bp_directory_groups_actions', 'hcommons_add_non_society_member_join_group_button' );

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
                buddypress()->bp_options_nav[$group_slug]['home']['position'] = 0;
                buddypress()->bp_options_nav[$group_slug]['forum']['position'] = 1;
                buddypress()->bp_options_nav[$group_slug]['home']['name']      = __( 'Activity', 'buddypress' );
        }

}

/**
 * Set the group default tab to 'forum' if the current group has a forum
 * attached to it.
 */
function hcommons_override_cbox_set_group_default_tab( $retval ) {
        // check if bbPress or legacy forums are active and configured properly
        if ( ( function_exists( 'bbp_is_group_forums_active' ) && bbp_is_group_forums_active() ) ||
                ( function_exists( 'bp_forums_is_installed_correctly' ) && bp_forums_is_installed_correctly() ) ) {

                // if current group does not have a forum attached, stop now!
                if ( ! bp_group_is_forum_enabled( groups_get_current_group() ) ) {
                        return $retval;
                }

                // Allow non-logged-in users to view a private group's homepage.
                if ( false === is_user_logged_in() && groups_get_current_group() && 'private' === bp_get_new_group_status() ) {
                        return $retval;
                }

                // reconfigure the group's nav
                add_action( 'bp_actions', 'hcommons_override_config_group_nav', 99 );

                // finally, use 'forum' as the default group tab
                return 'home';
        }

        return $retval;
}
add_filter( 'bp_groups_default_extension', 'hcommons_override_cbox_set_group_default_tab', 100 );

