<?php
/**
 * Customizations to bp-event-organiser plugin.
 *
 * @package Hc_Custom
 */

/**
 * Callback filter to use BPEO's content for the canonical event page.
 *
 * @param  string $content Current content.
 */
function hc_custom_filter_bp_event_content( $content ) {
	global $page;

	$post_type = get_post_type( get_the_ID() );

	if ( 'event' === $post_type ) {
		$page = 1;
	}

	return $content;
}

add_filter( 'the_content', 'hc_custom_filter_bp_event_content' );

/**
 * Remove buggy function from bp-events-organiser
 */
function hc_custom_remove_bpeo_filter_query_for_bp_group() {
		remove_action( 'pre_get_posts', 'bpeo_filter_query_for_bp_group' );
}
add_action( 'pre_get_posts', 'hc_custom_bpeo_filter_query_for_bp_group' );

/**
 * Modify `WP_Query` requests for the 'bp_group' param.
 *
 * @param object $query Query object, passed by reference.
 */
function hc_custom_bpeo_filter_query_for_bp_group( $query ) {
		// Only modify 'event' queries.
		$post_types = $query->get( 'post_type' );

	if ( ! in_array( 'event', (array) $post_types ) ) {
			return;
	}

	$bp_group = $query->get( 'bp_group', null );

	if ( null === $bp_group ) {
			return;
	}
	if ( ! is_array( $bp_group ) ) {
			$group_ids = array( $bp_group );
	} else {
			$group_ids = $bp_group;
	}
		// Empty array will always return no results.
	if ( empty( $group_ids ) ) {
			$query->set( 'post__in', array( 0 ) );
			return;
	}
		// Make sure private events are displayed.
		$query->set( 'post_status', array( 'publish', 'private' ) );
		// Convert group IDs to a tax query.
		$tq          = array();
		$tq[]        = $query->get( 'tax_query' );
		$group_terms = array();
	foreach ( $group_ids as $group_id ) {
			$group_terms[] = 'group_' . $group_id;
	}
		$tq[] = array(
			'taxonomy' => 'bpeo_event_group',
			'terms'    => $group_terms,
			'field'    => 'name',
			'operator' => 'IN',
		);

		$query->set( 'tax_query', $tq );
}

add_action( 'pre_get_posts', 'hc_custom_remove_bpeo_filter_query_for_bp_group' );

/**
 * Modify EO capabilities for group membership. Add capabilities for private events.
 *
 * @param array  $caps    Capability array.
 * @param string $cap     Capability to check.
 * @param int    $user_id ID of the user being checked.
 * @param array  $args    Miscellaneous args.
 * @return array Caps whitelist.
 */
function hc_custom_bpeo_group_event_meta_cap( $caps, $cap, $user_id, $args ) {
	// @todo Need real caching in BP for group memberships.
	if ( false === strpos( $cap, '_event' ) ) {
		return $caps;
	}

	// Some caps do not expect a specific event to be passed to the filter.
	$primitive_caps = array( 'read_events', 'read_private_events', 'edit_events', 'edit_others_events', 'publish_events', 'delete_events', 'delete_others_events', 'manage_event_categories', 'connect_event_to_group' );
	if ( ! in_array( $cap, $primitive_caps ) ) {
		$event = get_post( $args[0] );
		if ( 'event' !== $event->post_type ) {
			return $caps;
		}

		$event_groups = bpeo_get_event_groups( $event->ID );
		if ( empty( $event_groups ) ) {
			return $caps;
		}

		$user_groups = groups_get_user_groups( $user_id );
	}

	switch ( $cap ) {
		case 'read_private_events':
		case 'read_event':
			// we've already parsed this logic in bpeo_map_basic_meta_caps().
			if ( 'exist' === $caps[0] ) {
				return $caps;
			}

			if ( 'private' !== $event->post_status ) {
				// EO uses 'read', which doesn't include non-logged-in users.
				$caps = array( 'exist' );

			} elseif ( array_intersect( $user_groups['groups'], $event_groups ) ) {
				$caps = array( 'read' );
			}

			// @todo group admins / mods permissions
		case 'edit_event':
			break;

		case 'connect_event_to_group':
			$group_id = $args[0];
			$setting  = bpeo_get_group_minimum_member_role_for_connection( $group_id );

			if ( 'admin_mod' === $setting ) {
				$can_connect = groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id );
			} else {
				$can_connect = groups_is_user_member( $user_id, $group_id );
			}

			if ( $can_connect ) {
				$caps = array( 'read' );
			}

			break;
	}

	return $caps;
}
add_filter( 'map_meta_cap', 'hc_custom_bpeo_group_event_meta_cap', 20, 5 );
