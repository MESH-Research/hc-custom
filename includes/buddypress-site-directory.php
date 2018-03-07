<?php
/**
 * Customizations to buddypress site directory.
 *
 * @package Hc_Custom
 */

/**
 * Filter the sites query by latest post, exclude blogs with none
 *
 * @param array $args Includes the string of blog ids.
 * @return array $args
 */
function hcommons_exclude_no_latest_post( $args ) {
	global $wpdb;

	$blog_ids = explode( ',', $args['include_blog_ids'] );

	foreach ( $blog_ids  as $blog_id ) {
		$blog_prefix = $wpdb->get_blog_prefix( $blog_id );
		$latest_post = $wpdb->get_row( "SELECT ID FROM {$blog_prefix}posts WHERE post_status = 'publish' AND post_type = 'post' AND id != 1 ORDER BY id DESC LIMIT 1" );

		if ( null === $latest_post ) {
			$blog_ids = array_diff( $blog_ids, array( $blog_id ) );
		}
	}

	if ( ! empty( $blog_ids ) ) {
		$include_blogs = implode( ',', $blog_ids );
		$args['include_blog_ids'] = $include_blogs;
	}

	return $args;
}

add_filter( 'bp_before_has_blogs_parse_args', 'hcommons_exclude_root_blogs', 999 );

