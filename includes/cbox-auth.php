<?php
/**
 * Customizations to cbox-auth plugin
 *
 * @package Hc_Custom
 */

/**
 * Get object property or return default value.
 *
 * @param object $obj      Object.
 * @param string $property Property name.
 * @param mixed  $default  Default value.
 * @return mixed Property value or default value.
 */
function get_object_property( $obj, string $property, $default ) {
	return ( property_exists( $obj, $property ) ) ? $obj->$property : $default;
}

	/**
	 * Get group type.
	 */
function get_group_type() {
	global $groups_template;

	if ( $groups_template && get_object_property( $groups_template, 'group', false ) ) {
		$bp_id = $groups_template->group->id;
		return strtolower( \groups_get_groupmeta( $bp_id, 'society_group_type', true ) );
	}

	return 0;
}

	/**
	 * Hide the join/leave button for committees and forums for which the
	 * user is not an admin.
	 *
	 * @param BP_Groups_Group $group BuddyPress group.
	 */
function hide_join_button( $group ) {

		$user_id = \bp_loggedin_user_id();
		$group_type = get_group_type();

		// Remove the other actions that would create this button.
		$actions = array(
		'bp_group_header_actions'     => 'bp_group_join_button',
		'bp_directory_groups_actions' => 'bp_group_join_button',
		);
		foreach ( $actions as $name => $action ) {
			\remove_action( $name, $action, \has_action( $name, $action ) );
		}

		$is_committee   = ( 'committee' === $group_type );
		$is_forum_admin = ( 'forum' === $group_type && \groups_is_user_admin( $user_id, \bp_get_group_id() ) );

		if ( $is_committee || $is_forum_admin ) {
			return;
		}

		return \bp_group_join_button( $group );
}

	add_action( 'bp_group_header_actions', 'hide_join_button', 1 );
	add_action( 'bp_directory_groups_actions', 'hide_join_button', 1 );


	/**
	 * Hide the request membership tab for committees.
	 *
	 * @param string $string Unchanged filter string.
	 */
function hide_request_membership_tab( string $string ) {
	return ( 'committee' === get_group_type() ) ? null : $string;
}

	add_filter( 'bp_get_options_nav_request-membership', 'hide_request_membership_tab' );





