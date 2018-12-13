<?php
/**
 * Customizations to bp-multiple-forum-post plugin
 *
 * @package Hc_Custom
 */

/**
 * Remove bpmf filter if no logged in user.
 *
 * @uses bpmfp_get_duplicate_topics_ids()
 * @uses bpmfp_get_this_topic_also_posted_in_message()
 **/
function hc_custom_add_duplicate_topics_to_forum_topic() {
	remove_action( 'bbp_theme_after_reply_content', 'bpmfp_add_duplicate_topics_to_forum_topic' );

	$reply_id   = bbp_get_reply_id();
	$reply_post = get_post( $reply_id );
	$added_topic_links = array();
	// Only show on the first item in a thread - the topic.
	if ( 0 !== $reply_post->menu_order ) {
		return;
	}
	$topic_id      = $reply_id;
	$all_topic_ids = bpmfp_get_duplicate_topics_ids( $topic_id );
	for ( $index = 0; $index < count( $all_topic_ids ); $index++ ) {
		// Get the forum ID for the topic, and the group ID for the forum, and the group object.
		$topic_forum_id = get_post( $all_topic_ids[$index] )->post_parent;
		$forum_group_ids = bbp_get_forum_group_ids( $topic_forum_id );
		$forum_group_id = $forum_group_ids[0];
		$forum_group = groups_get_group( array( 'group_id' => $forum_group_id ) );

		// If the group that the duplicate topic is in is public, or the current user is a member of it,
		// or the current user is a moderator, then include a link to the topic.
		// If conditional should encompass the entire routine so that hidden group names aren't shown.
		if ( 'public' == $forum_group->status || groups_is_user_member( bp_loggedin_user_id(), $forum_group_id ) || current_user_can( 'bp_moderate' ) ) {
			$topic_link = bbp_get_topic_permalink( $topic_ids[$index] );
			$forum_name = get_post_field( 'post_title', $topic_forum_id, 'raw' );
			if ( $forum_name ) {
				$added_topic_link = esc_html( $forum_name );
				if ( $topic_link && ( $context === 'forum_topic' || $context === 'email' ) ) {
					$added_topic_link = ' <a href="' . esc_url( $topic_link ) . '">' . $added_topic_link . '</a>';
				}
			}
			if ( ! empty( $added_topic_link ) ) {
				$added_topic_links[] = $added_topic_link;
			}
		}
	}

	if ( ! empty( $added_topic_links ) ) {
		echo bpmfp_get_this_topic_also_posted_in_message( $all_topic_ids, 'forum_topic' );
	}
}

add_action( 'bbp_theme_after_reply_content', 'hc_custom_add_duplicate_topics_to_forum_topic', 0 );
