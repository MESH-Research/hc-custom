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
