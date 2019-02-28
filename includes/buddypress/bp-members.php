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

/**
 * Modify the member search to see if there is a direct hit on display_name field.
 *

 */

function mla_member_name_search( $is_members, $members, $r ) {
	error_log("\n\n");
	error_log( print_r( $_REQUEST, true ) );
	if ( empty( $_REQUEST[ 'member_search_type' ] ) || 'name' !== $_REQUEST[ 'member_search_type' ] ) {
		return $is_members;
	}
	$member = filter_var( $_REQUEST[ 's' ], FILTER_SANITIZE_STRING );
	global $wpdb;
	$custom_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT user_id FROM ' . $wpdb->prefix . 'users WHERE display_name LIKE %d', '%' . $wpdb->esc_like( $member ) . '%' ) );

	if ( ! empty( $custom_ids ) ) {
		// convert the array to a csv string.
		$r['include'] = implode( ',', $custom_ids );
		$members = new BP_Core_Members_Template(
			$r['type'],
			$r['page'],
			$r['per_page'],
			$r['max'],
			$r['user_id'],
			$r['search_terms'],
			$r['include'],
			$r['populate_extras'],
			$r['exclude'],
			$r['meta_key'],
			$r['meta_value'],
			$r['page_arg'],
			$r['member_type'],
			$r['member_type__in'],
			$r['member_type__not_in']
		);
		$is_members = $members->has_members();
	}

	return $is_members;
}
add_filter( 'bp_has_members', 'mla_member_name_search', 10, 3 );

function mla_member_search_form( $html ) {
	$query_arg = bp_core_get_component_search_query_arg( 'members' );
	if ( ! empty( $_REQUEST[ $query_arg ] ) ) {
		$search_value = stripslashes( $_REQUEST[ $query_arg ] );
	} else {
		$search_value = bp_get_search_default_text( 'members' );
	}
	?>
    <form action="" method="get" id="search-members-form">
        <label for="members_search"><input type="text" name="<?php echo esc_attr( $query_arg ); ?>" id="members_search"
                                           placeholder="<?php echo esc_attr( $search_value ); ?>"/></label>
        <input type="submit" id="members_search_submit" name="members_search_submit"
               value="<?php echo __( 'Search', 'buddypress' ); ?>"/>
        <input name="member_search_type" type="checkbox" value="name" /><?php echo __('Limit to member name', 'hc_custom') ?>
    </form>
	<?php
}

add_filter( 'bp_directory_members_search_form', 'mla_member_search_form' );
