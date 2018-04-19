<?php
/**
 * Customizations to bbpress
 *
 * @package Hc_Custom
 */

/**
 * Disable akismet for forum posts.
 */
add_filter( 'bbp_is_akismet_active', '__return_false' );

/**
 * Replace default bbp notification formatter with our own multinetwork-compatible version.
 * Copied from bbp_format_buddypress_notifications().
 * Added switch_to_blog logic for multinetwork compatibility
 */
function hcommons_bbp_format_buddypress_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string', $component_action_name, $component_name, $notification_id ) {
	$return = $action;

	if ( function_exists( 'bbp_format_buddypress_notifications' ) ) {

		// New reply notifications
		if ( 'bbp_new_reply' === $action ) {
			$society_id           = bp_notifications_get_meta( $notification_id, 'society_id', true );
			$notification_blog_id = (int) constant( strtoupper( $society_id ) . '_ROOT_BLOG_ID' );
			$switched             = false;
			if ( ! empty( $notification_blog_id ) && $notification_blog_id !== get_current_blog_id() ) {
				switch_to_blog( $notification_blog_id );
				$switched = true;
			}

			$topic_id    = bbp_get_reply_topic_id( $item_id );
			$topic_title = bbp_get_topic_title( $topic_id );
			$topic_link  = wp_nonce_url(
				add_query_arg(
					array(
						'action'   => 'bbp_mark_read',
						'topic_id' => $topic_id,
					), bbp_get_reply_url( $item_id )
				), 'bbp_mark_topic_' . $topic_id
			);
			$title_attr  = __( 'Topic Replies', 'bbpress' );

			if ( (int) $total_items > 1 ) {
				$text   = sprintf( __( 'You have %d new replies', 'bbpress' ), (int) $total_items );
				$filter = 'bbp_multiple_new_subscription_notification';
			} else {
				if ( ! empty( $secondary_item_id ) ) {
					$text = sprintf( __( 'You have %d new reply to %2$s from %3$s', 'bbpress' ), (int) $total_items, $topic_title, bp_core_get_user_displayname( $secondary_item_id ) );
				} else {
					$text = sprintf( __( 'You have %1$d new reply to %2$s', 'bbpress' ), (int) $total_items, $topic_title );
				}
				$filter = 'bbp_single_new_subscription_notification';
			}

			// WordPress Toolbar
			if ( 'string' === $format ) {
				$return = apply_filters( $filter, '<a href="' . esc_url( $topic_link ) . '" title="' . esc_attr( $title_attr ) . '">' . esc_html( $text ) . '</a>', (int) $total_items, $text, $topic_link );

				// Deprecated BuddyBar
			} else {
				$return = apply_filters(
					$filter, array(
						'text' => $text,
						'link' => $topic_link,
					), $topic_link, (int) $total_items, $text, $topic_title
				);
			}

			do_action( 'bbp_format_buddypress_notifications', $action, $item_id, $secondary_item_id, $total_items );

			if ( $switched ) {
				restore_current_blog();
			}
		}
	}

	return $return;
}
remove_filter( 'bp_notifications_get_notifications_for_user', 'bbp_format_buddypress_notifications' );
add_filter( 'bp_notifications_get_notifications_for_user', 'hcommons_bbp_format_buddypress_notifications', 10, 8 );

/**
 * Make sure the_permalink() ends in /forum when posting a new topic so that
 * authors see their post and any errors after submission.
 *
 * @param string $url the permalink.
 * @return string filtered permalink ending in '/forum' (if applicable).
 */
function hcommons_fix_group_forum_permalinks( $url ) {
	if (
		bp_is_group() &&
		bp_is_current_action( 'forum' ) &&
		0 === preg_match( '#/forum#i', $url )
	) {
		$url = trailingslashit( $url ) . 'forum';
	}

	return $url;
}
// Priority 20 to run after CBox_BBP_Autoload->override_the_permalink_with_group_permalink().
add_filter( 'the_permalink', 'hcommons_fix_group_forum_permalinks', 20 );

/**
 * Add xprofile query to user query.
 *
 * @param BP_User_Query $q User query.
 */
function filter_bp_xprofile_add_xprofile_query_to_user_query( BP_User_Query $q ) {

	if ( bp_is_group_members() ) {
		$members_search = ! empty( $_REQUEST['members_search'] ) ? sanitize_text_field( $_REQUEST['members_search'] ) : sanitize_text_field( $_REQUEST['search_terms'] );

		if ( isset( $members_search ) && ! empty( $members_search ) ) {

			$args = array(
				'xprofile_query' => array(
					'relation' => 'AND',
					array(
						'field'   => 'Name',
						'value'   => $members_search,
						'compare' => 'LIKE',
					),
				),
			);

			$xprofile_query = new BP_XProfile_Query( $args );
			$sql            = $xprofile_query->get_sql( 'u', $q->uid_name );

			if ( ! empty( $sql['join'] ) ) {
				$q->uid_clauses['select'] .= $sql['join'];
				$q->uid_clauses['where']  .= $sql['where'];
			}
		}
	}
}

add_action( 'bp_pre_user_query', 'filter_bp_xprofile_add_xprofile_query_to_user_query' );

/**
 * Disables the forum subscription link.
 */
add_filter( 'bbp_get_forum_subscribe_link', '__return_false' );

/**
 * Disables bbPress image button.
 *
 * @param array $buttons the permalink.
 */
function hcommons_tinymce_buttons( $buttons ) {

	if ( bp_is_group() ) {
		// Remove image button.
		$remove  = array( 'image' );
		$buttons = array_diff( $buttons, $remove );
	}

	return $buttons;
}

add_filter( 'mce_buttons', 'hcommons_tinymce_buttons', 21 );

/**
 * Filter who can edit forum topics.
 *
 * @uses mla_is_group_committee()
 * @param  array $array Array of the links to modify.
 * @return array Modified array of items.
 */
function hcommons_topic_admin_links( $array ) {
	// Super admins can edit any post.
	if ( is_super_admin() ) {
		return $array;
	}

	// Committee admins can edit any post in their group.
	if ( mla_is_group_committee( bp_get_current_group_id() ) && groups_is_user_admin( get_current_user_id(), bp_get_current_group_id() ) ) {
		return $array;
	}

	// All other users can only edit their own posts.
	if (
		isset( $array['edit'] ) &&
		get_current_user_id() !== bbp_get_topic_author_id( bbp_get_topic_id() )
	) {
		unset( $array['edit'] );
	}

	return $array;
}
add_filter( 'bbp_topic_admin_links', 'hcommons_topic_admin_links' );

/**
 * Filter who can edit forum replies.
 *
 * @uses mla_is_group_committee()
 * @param  array $array Array of the links to modify.
 * @return array Modified array of items.
 */
function hcommons_reply_admin_links( $array ) {
	// Super admins can edit any post.
	if ( is_super_admin() ) {
		return $array;
	}

	// Committee admins can edit any post in their group.
	if ( mla_is_group_committee( bp_get_current_group_id() ) && groups_is_user_admin( get_current_user_id(), bp_get_current_group_id() ) ) {
		return $array;
	}

	// All other users can only edit their own posts.
	if (
		isset( $array['edit'] ) &&
		get_current_user_id() !== bbp_get_reply_author_id( bbp_get_reply_id() )
	) {
		unset( $array['edit'] );
	}

	return $array;
}
add_filter( 'bbp_reply_admin_links', 'hcommons_reply_admin_links' );
