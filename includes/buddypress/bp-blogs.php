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
		$blog_id  = get_main_site_id( $network->id );
		$blog_ids = array_diff( $blog_ids, array( $blog_id ) );
	}

	if ( ! empty( $blog_ids ) ) {
		$include_blogs            = implode( ',', $blog_ids );
		$args['include_blog_ids'] = $include_blogs;
	}

	return $args;
}

add_filter( 'bp_before_has_blogs_parse_args', 'hcommons_exclude_root_blogs', 999 );

/**
 * BuddyPress does not consider whether post comments are enabled when users reply to a post activity.
 * Remove the action responsible for posting the comment unless comments are enabled.
 *
 * @param int    $comment_id The activity ID for the posted activity comment.
 * @param array  $r          Parameters for the activity comment.
 * @param object $activity   Parameters of the parent activity item (in this case, the blog post).
 */
function hcommons_constrain_activity_comments( $comment_id, $r, $activity ) {
	switch_to_blog( $activity->item_id );

	// BP filters comments_open to prevent comments on its own post types.
	// Disable it for new_blog_post activities.
	if ( 'new_blog_post' === $activity->type ) {
		remove_filter( 'comments_open', 'bp_comments_open', 10, 2 );
	}

	if ( ! comments_open( $activity->secondary_item_id ) ) {
		remove_action( 'bp_activity_comment_posted', 'bp_blogs_sync_add_from_activity_comment', 10, 3 );
	}

	restore_current_blog();
}
// Priority 5 to run before bp_blogs_sync_add_from_activity_comment().
add_action( 'bp_activity_comment_posted', 'hcommons_constrain_activity_comments', 5, 3 );
