<?php
/**
 * Customizations to buddypress-group-email-subscription.
 *
 * @package Hc_Custom
 */

/**
 * Remove BPGES actions since we use crontab instead of WP cron.
 */
function hcommons_remove_bpges_actions() {
	remove_action( 'ass_digest_event', 'ass_daily_digest_fire' );
	remove_action( 'ass_digest_event_weekly', 'ass_weekly_digest_fire' );
}
add_action( 'bp_init', 'hcommons_remove_bpges_actions' );

/**
 * Add a line break after "Replying to this email will not..."
 * Assumes HTML email, plaintext not supported.
 *
 * @param string $notice Non-RBE notice.
 * @return string
 */
function hcommons_filter_bp_rbe_get_nonrbe_notice( string $notice ) {
	return $notice . '<br>';
}
add_action( 'bp_rbe_get_nonrbe_notice', 'hcommons_filter_bp_rbe_get_nonrbe_notice' );

/**
 * Add nested reply formatting to digests.
 *
 * @param string $group_message
 * @param int    $group_id
 * @param string $type
 * @param array  $activity_ids
 * @param int    $user_id
 *
 * @return string Filtered group message
 */
function hcommons_filter_ass_digest_format_item_group( $group_message, $group_id, $type, $activity_ids, $user_id ) {
	global $bp, $ass_email_css;

	$group = groups_get_group( $group_id );

	$group_permalink = bp_get_group_permalink( $group );
	$group_name_link = '<a class="item-group-group-link" href="' . esc_url( $group_permalink ) . '" name="' . esc_attr( $group->slug ) . '">' . esc_html( $group->name ) . '</a>';

	$userdomain = ass_digest_get_user_domain( $user_id );
	$unsubscribe_link = "$userdomain?bpass-action=unsubscribe&group=$group_id&access_key=" . md5( "{$group_id}{$user_id}unsubscribe" . wp_salt() );
	$gnotifications_link = ass_get_login_redirect_url( $group_permalink . 'notifications/' );

	// add the group title bar
	if ( 'dig' === $type ) {
		$group_message = "\n---\n\n<div class=\"item-group-title\" {$ass_email_css['group_title']}>" . sprintf( __( 'Group: %s', 'bp-ass' ), $group_name_link ) . "</div>\n\n";
	} elseif ( 'sum' === $type ) {
		$group_message = "\n---\n\n<div class=\"item-group-title\" {$ass_email_css['group_title']}>" . sprintf( __( 'Group: %s weekly summary', 'bp-ass' ), $group_name_link ) . "</div>\n";
	}

	// add change email settings link
	$group_message .= "\n<div class=\"item-group-settings-link\" {$ass_email_css['change_email']}>";
	$group_message .= __( 'To disable these notifications for this group click ', 'bp-ass' ) . " <a href=\"$unsubscribe_link\">" . __( 'unsubscribe', 'bp-ass' ) . '</a> - ';
	$group_message .= __( 'change ', 'bp-ass' ) . '<a href="' . $gnotifications_link . '">' . __( 'email options', 'bp-ass' ) . '</a>';
	$group_message .= "</div>\n\n";

	$group_message = apply_filters( 'ass_digest_group_message_title', $group_message, $group_id, $type );

	// Sort activity items and group by forum topic, where possible.
	$grouped_activity_ids = array(
		'topics' => array(),
		'other' => array(),
	);

	$topic_activity_map = array();

	foreach ( $activity_ids as $activity_id ) {
		$activity_item = ! empty( $bp->ass->items[ $activity_id ] ) ? $bp->ass->items[ $activity_id ] : false;

		switch ( $activity_item->type ) {
			case 'bbp_topic_create' :
				$topic_id = $activity_item->secondary_item_id;
				$grouped_activity_ids['topics'][] = $topic_id;

				if ( ! isset( $topic_activity_map[ $topic_id ] ) ) {
					$topic_activity_map[ $topic_id ] = array();
				}

				$topic_activity_map[ $topic_id ][] = $activity_id;
			break;

			case 'bbp_reply_create' :
				// Topic may or may not be in this digest queue.
				$topic_id = bbp_get_reply_topic_id( $activity_item->secondary_item_id );
				$grouped_activity_ids['topics'][] = $topic_id;

				if ( ! isset( $topic_activity_map[ $topic_id ] ) ) {
					$topic_activity_map[ $topic_id ] = array();
				}

				$topic_activity_map[ $topic_id ][] = $activity_id;
			break;

			default :
				$grouped_activity_ids['other'][] = $activity_id;
			break;
		}

		$grouped_activity_ids['topics'] = array_unique( $grouped_activity_ids['topics'] );
	}

	// Assemble forum topic markup first.
	foreach ( $grouped_activity_ids['topics'] as $topic_id ) {
		$topic = bbp_get_topic( $topic_id );
		if ( ! $topic ) {
			continue;
		}

		// 'Topic' header.
		$item_message  = '';
		$item_message .= "<div class=\"digest-item\" {$ass_email_css['item_div']}>";

		$item_message .= '<div class="digest-topic-header">';
		$item_message .= sprintf(
			__( 'Topic: %s', 'bp-ass' ),
			sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $topic_id ) ), esc_html( $topic->post_title ) )
		);
		$item_message .= '</div>'; // .digest-topic-header

		$item_message .= '<div class="digest-topic-items">';
		foreach ( $topic_activity_map[ $topic_id ] as $activity_id ) {
			$activity_item = new BP_Activity_Activity( $activity_id );

			$poster_name = bp_core_get_user_displayname( $activity_item->user_id );
			$poster_url = bp_core_get_user_domain( $activity_item->user_id );
			$topic_name = $topic->post_title;
			$topic_permalink = get_permalink( $topic_id );

			if ( 'bbp_topic_create' === $activity_item->type ) {
				$action_format = '<a href="%s">%s</a> posted on <a href="%s">%s</a>';
			} else {
				$action_format = '<a href="%s">%s</a> started <a href="%s">%s</a>';
			}

			$action = sprintf( $action_format, esc_url( $poster_url ), esc_html( $poster_name ), esc_url( $topic_permalink ), esc_html( $topic_name ) );

			/* Because BuddyPress core set gmt = true, timezone must be added */
			$timestamp = strtotime( $activity_item->date_recorded ) + date( 'Z' );

			$time_posted = date( get_option( 'time_format' ), $timestamp );
			$date_posted = date( get_option( 'date_format' ), $timestamp );

			$item_message .= '<div class="digest-topic-item" style="border-top:1px solid #eee; margin: 15px 0 15px 30px;">';
			$item_message .= "<span class=\"digest-item-action\" {$ass_email_css['item_action']}>" . $action . ": ";
			$item_message .= "<span class=\"digest-item-timestamp\" {$ass_email_css['item_date']}>" . sprintf( __('at %s, %s', 'bp-ass'), $time_posted, $date_posted ) ."</span>";
			$item_message .= "<br><span class=\"digest-item-content\" {$ass_email_css['item_content']}>" . apply_filters( 'ass_digest_content', $activity_item->content, $activity_item, $type ) . "</span>";
			$item_message .=  "</span>\n";
			$item_message .= '</div>'; // .digest-topic-item
		}
		$item_message .= '</div>'; // .digest-topic-items

		$item_message .= '</div>'; // .digest-item

		$group_message .= $item_message;
	}

	// Non-forum-related markup goes at the end.
	foreach ( $grouped_activity_ids['other'] as $activity_id ) {
		// Cache is set earlier in ass_digest_fire()
		$activity_item = ! empty( $bp->ass->items[ $activity_id ] ) ? $bp->ass->items[ $activity_id ] : false;

		if ( ! empty( $activity_item ) ) {

		    if( 'bpeo_create_event' === $activity_item->type ) {
				$event_id = $activity_item->secondary_item_id;

				$occurrences = eo_get_the_occurrences_of( $event_id );

        		if($occurrences) {
                	$occurence_ids = array_keys( $occurrences );
                    $occurence_id = $occurence_ids[0];
                } else {
                	continue;
                }

				$event_date = eo_get_the_start( 'g:i a jS M Y' , $event_id, $occurence_id );

				$group_message .=  "<div class=\"digest-item\" {$ass_email_css['item_div']}>";
				$group_message .=  "<span class=\"digest-item-action\" {$ass_email_css['item_action']}>" . $activity_item->action . ": ";
				$group_message .= "<span class=\"digest-item-timestamp\" {$ass_email_css['item_date']}>" . sprintf( __('at %s', 'bp-ass'), $event_date)  ."</span>";
				$group_message .=  "</span>\n";

				// activity content
				if ( ! empty( $activity_item->content ) ) {
					$item_message .= "<br><span class=\"digest-item-content\" {$ass_email_css['item_content']}>" . apply_filters( 'ass_digest_content', $activity_item->content, $activity_item, $type ) . "</span>";
				}

				$view_link = $activity_item->primary_link;

				$group_message .= ' - <a class="digest-item-view-link" href="' . ass_get_login_redirect_url( $view_link ) .'">' . __( 'View', 'bp-ass' ) . '</a>';

				$group_message .= "</div>\n\n";

			} else {
				$group_message .= ass_digest_format_item( $activity_item, $type );
			}
		}
	}

	return $group_message;
};
add_filter( 'ass_digest_format_item_group', 'hcommons_filter_ass_digest_format_item_group', 10, 5 );

/**
 * Remove activity items that don't belong to the current network from digest emails
 *
 * @uses Humanities_Commons
 *
 * @param array $group_activity_ids List of activities keyed by group ID
 * @return array Only those activity IDs belonging to the current network
 */
function hcommons_filter_ass_digest_group_activity_ids( $group_activity_ids ) {
	$network_activity_ids = [];

	foreach ( $group_activity_ids as $group_id => $activity_ids ) {
		if ( Humanities_Commons::$society_id === bp_groups_get_group_type( $group_id ) ) {
			$network_activity_ids[ $group_id ] = $activity_ids;
		}

		// Sanity check for activity age - very old items should not be sent.
		// Allow for weekly digests to include one prior week of potentially delayed items.
		// Beyond that, consider the inclusion of this activity a bug and remove it.
		foreach ( $activity_ids as $i => $activity_id ) {
			$activity = new BP_Activity_Activity( $activity_id );
			$activity_age = time() - strtotime( $activity->date_recorded );

			if ( $activity_age > 2 * WEEK_IN_SECONDS ) {
				unset( $activity_ids[ $i ] );
			}
		}
	}

	return $network_activity_ids;
}
add_action( 'ass_digest_group_activity_ids', 'hcommons_filter_ass_digest_group_activity_ids' );

/**
 * Sanity checks for email digests:
 * * Number of items should be reasonably small
 * * Age of items should be reasonably recent
 * * Origin network should be consistent per-digest (no cross-network activities)
 *
 * @uses Humanities_Commons
 *
 * @param string $summary
 * @return string summary
 */
function hcommons_filter_ass_digest_summary_full( string $summary ) {
	// start with a clean slate, handle below if we need to kill this particular email
	remove_filter( 'ass_send_email_args', '__return_false' );

	/**
	 * Prevent this digest from being sent to the current user.
	 */
	$skip_current_user_digest = function() use ( $summary ) {
		error_log( 'DIGEST: killed digest with summary: ' . $summary );
		add_filter( 'ass_send_email_args', '__return_false' );
	};

	/**
	 * This is intended to prevent very large digest emails from being sent,
	 * whether due to lots of legitimate activity or erroneous filtering.
	 */
	preg_match_all( '/\((\d+) items\)/', $summary, $matches, PREG_PATTERN_ORDER );
	foreach ( $matches[1] as $num_items ) {
		if ( $num_items > 50 ) {
			$skip_current_user_digest();
		}
	}

	// This should contain the name of at least one group.
	if ( 'Group Summary:' === trim( strip_tags( $summary ) ) ) {
		$skip_current_user_digest();
	}

	return $summary;
}
add_filter( 'ass_digest_summary_full', 'hcommons_filter_ass_digest_summary_full' );
