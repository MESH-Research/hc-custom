<?php
/**
 * Customizations to BuddyPress Members.
 *
 * @package Hc_Custom
 */

/**
 * Disable follow button for non-society-members.
 */
function hcommons_add_non_society_member_follow_button() {
	if ( ! is_super_admin() && hcommons_check_non_member_active_session() ) {
		echo '<div class="disabled-button">Follow</div>';
	}
}
add_action( 'bp_directory_members_actions', 'hcommons_add_non_society_member_follow_button' );

/**
 * Add follow disclaimer for non-society-members.
 */
function hcommons_add_non_society_member_disclaimer_member() {
	if ( ! is_super_admin() && hcommons_check_non_member_active_session() ) {
		printf(
			'<div class="non-member-disclaimer">Only %s members can follow others from here.<br>To follow these members, go to <a href="%s">Humanities Commons</a>.</div>',
			strtoupper( Humanities_Commons::$society_id ),
			get_site_url( getenv( 'HC_ROOT_BLOG_ID' ) )
		);
	}
}
add_action( 'bp_before_directory_members_content', 'hcommons_add_non_society_member_disclaimer_member' );
