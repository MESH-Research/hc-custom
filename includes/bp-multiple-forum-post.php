<?php
/**
 * Customizations to bp-multiple-forum-post plugin
 *
 * @package Hc_Custom
 */

/**
 * Append "This topic also posted in" message to the initial post in a topic that's been cross-posted.
 *
 * @uses bpmfp_get_duplicate_topics_ids()
 * @uses bpmfp_get_this_topic_also_posted_in_message()
 **/
function hc_custom_add_duplicate_topics_to_forum_topic() {
	remove_action( 'bbp_theme_after_reply_content', 'bpmfp_add_duplicate_topics_to_forum_topic' );

	$reply_id   = bbp_get_reply_id();
	$reply_post = get_post( $reply_id );
	// Only show on the first item in a thread - the topic.
	if ( 0 !== $reply_post->menu_order ) {
		return;
	}
	$topic_id      = $reply_id;
	$all_topic_ids = bpmfp_get_duplicate_topics_ids( $topic_id );

	for ( $index = 0; $index < count( $all_topic_ids ); $index++ ) {
		// Get the forum ID for the topic, and the group ID for the forum, and the group object.
		$topic_forum_id  = get_post( $all_topic_ids[ $index ] )->post_parent;
		$forum_group_ids = bbp_get_forum_group_ids( $topic_forum_id );
		$forum_group_id  = $forum_group_ids[0];
		$forum_group     = groups_get_group( array( 'group_id' => $forum_group_id ) );

		// If the group that the duplicate topic is in is public, or the current user is a member of it,
		// or the current user is a moderator, then include a link to the topic.
		if ( 'public' !== $forum_group->status || ! groups_is_user_member( bp_loggedin_user_id(), $forum_group_id ) || ! current_user_can( 'bp_moderate' ) ) {
			unset( $all_topic_ids[ $index ] );
		}
	}

	if ( ! empty( $all_topic_ids ) ) {
		echo bpmfp_get_this_topic_also_posted_in_message( $all_topic_ids, 'forum_topic' );
	}
}
add_action( 'bbp_theme_after_reply_content', 'hc_custom_add_duplicate_topics_to_forum_topic', 0 );
