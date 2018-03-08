<?php
/**
 * Customizations to BuddyPress Blogs.
 *
 * @package Hc_Custom
 */

/**
 * Filter the sites query, exclude root blogs from query.
 *
 * @param array $args Includes the string of blog ids.
 * @return array $args
 */
function hcommons_exclude_root_blogs( $args ) {

	$blog_ids = explode( ',', $args['include_blog_ids'] );

	foreach ( get_networks() as $network ) {
		$blog_id = get_main_site_id( $network->id );
		$blog_ids = array_diff( $blog_ids, array( $blog_id ) );
	}

	if ( ! empty( $blog_ids ) ) {
		$include_blogs = implode( ',', $blog_ids );
		$args['include_blog_ids'] = $include_blogs;
	}

	return $args;
}

add_filter( 'bp_before_has_blogs_parse_args', 'hcommons_exclude_root_blogs', 999 );

