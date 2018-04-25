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
 * TODO pass phpcs.
 * @codingStandardsIgnoreStart
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

	$userdomain       = ass_digest_get_user_domain( $user_id );
	$unsubscribe_link = "$userdomain?bpass-action=unsubscribe&group=$group_id&access_key=" . md5( "{$group_id}{$user_id}unsubscribe" . wp_salt() );
	// $gnotifications_link = ass_get_login_redirect_url( $group_permalink . 'notifications/' );
	$gnotifications_link = ass_get_login_redirect_url( $userdomain . 'settings/notifications/' );

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
		'other'  => array(),
	);

	$topic_activity_map = array();

	foreach ( $activity_ids as $activity_id ) {
		$activity_item = ! empty( $bp->ass->items[ $activity_id ] ) ? $bp->ass->items[ $activity_id ] : false;

		switch ( $activity_item->type ) {
			case 'bbp_topic_create':
				$topic_id                         = $activity_item->secondary_item_id;
				$grouped_activity_ids['topics'][] = $topic_id;

				if ( ! isset( $topic_activity_map[ $topic_id ] ) ) {
					$topic_activity_map[ $topic_id ] = array();
				}

				$topic_activity_map[ $topic_id ][] = $activity_id;
				break;

			case 'bbp_reply_create':
				// Topic may or may not be in this digest queue.
				$topic_id                         = bbp_get_reply_topic_id( $activity_item->secondary_item_id );
				$grouped_activity_ids['topics'][] = $topic_id;

				if ( ! isset( $topic_activity_map[ $topic_id ] ) ) {
					$topic_activity_map[ $topic_id ] = array();
				}

				$topic_activity_map[ $topic_id ][] = $activity_id;
				break;

			default:
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

			$poster_name     = bp_core_get_user_displayname( $activity_item->user_id );
			$poster_url      = bp_core_get_user_domain( $activity_item->user_id );
			$topic_name      = $topic->post_title;
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
			$item_message .= "<span class=\"digest-item-action\" {$ass_email_css['item_action']}>" . $action . ': ';
			$item_message .= "<span class=\"digest-item-timestamp\" {$ass_email_css['item_date']}>" . sprintf( __( 'at %1$s, %2$s', 'bp-ass' ), $time_posted, $date_posted ) . '</span>';
			$item_message .= "<br><span class=\"digest-item-content\" {$ass_email_css['item_content']}>" . apply_filters( 'ass_digest_content', $activity_item->content, $activity_item, $type ) . '</span>';
			$item_message .= "</span>\n";
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

			if ( 'bpeo_create_event' === $activity_item->type ) {
				$event_id = $activity_item->secondary_item_id;

				$occurrences = eo_get_the_occurrences_of( $event_id );

				if ( $occurrences ) {
					$occurence_ids = array_keys( $occurrences );
					$occurence_id  = $occurence_ids[0];
				} else {
					continue;
				}

				$event_date = eo_get_the_start( 'g:i a jS M Y', $event_id, $occurence_id );

				$group_message .= "<div class=\"digest-item\" {$ass_email_css['item_div']}>";
				$group_message .= "<span class=\"digest-item-action\" {$ass_email_css['item_action']}>" . $activity_item->action . ': ';
				$group_message .= "<span class=\"digest-item-timestamp\" {$ass_email_css['item_date']}>" . sprintf( __( 'at %s', 'bp-ass' ), $event_date ) . '</span>';
				$group_message .= "</span>\n";

				// activity content
				if ( ! empty( $activity_item->content ) ) {
					$item_message .= "<br><span class=\"digest-item-content\" {$ass_email_css['item_content']}>" . apply_filters( 'ass_digest_content', $activity_item->content, $activity_item, $type ) . '</span>';
				}

				$view_link = $activity_item->primary_link;

				$group_message .= ' - <a class="digest-item-view-link" href="' . ass_get_login_redirect_url( $view_link ) . '">' . __( 'View', 'bp-ass' ) . '</a>';

				$group_message .= "</div>\n\n";

			} else {
				$group_message .= ass_digest_format_item( $activity_item, $type );
			}
		}
	}


	return $group_message;
};
add_filter( 'ass_digest_format_item_group', 'hcommons_filter_ass_digest_format_item_group', 10, 5 );
// @codingStandardsIgnoreEnd

/**
 * Remove activity items that don't belong to the current network from digest emails
 *
 * @uses Humanities_Commons
 *
 * @param array $group_activity_ids List of activities keyed by group ID.
 * @return array Only those activity IDs belonging to the current network
 */
function hcommons_filter_ass_digest_group_activity_ids( $group_activity_ids ) {
	$network_activity_ids = [];

	foreach ( $group_activity_ids as $group_id => $activity_ids ) {
		if ( bp_groups_get_group_type( $group_id ) === Humanities_Commons::$society_id ) {
			$network_activity_ids[ $group_id ] = $activity_ids;
		}

		// Sanity check for activity age - very old items should not be sent.
		// Allow for weekly digests to include one prior week of potentially delayed items.
		// Beyond that, consider the inclusion of this activity a bug and remove it.
		foreach ( $activity_ids as $i => $activity_id ) {
			$activity     = new BP_Activity_Activity( $activity_id );
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
 * @param string $summary Summary.
 * @return string Summary.
 */
function hcommons_filter_ass_digest_summary_full( string $summary ) {
	// Start with a clean slate, handle below if we need to kill this particular email.
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

/**
 * Modify the default bbp_reply_create/bbp_topic_create subject
 *
 * @param string $activity_text The subject line of the e-mail.
 *
 * @param object $activity The BP_Activity_Activity object for this notification.
 *
 * @return string $activity_text Return modified string.
 */
function hcommons_bp_ass_activity_notification_action( $activity_text, $activity ) {
	$topic_id    = bbp_get_reply_topic_id( $activity->secondary_item_id );
	$topic_title = get_post_field( 'post_title', $topic_id, 'raw' );

	$topic_title = wp_trim_words( $topic_title, 7, '...' );

	$forum_id    = bbp_get_topic_forum_id( $topic_id );
	$forum_title = get_post_field( 'post_title', $forum_id, 'raw' );

	$forum_title = wp_trim_words( $forum_title, 4, '...' );

	switch ( $activity->type ) {
		case 'bbp_topic_create':
			// @codingStandardsIgnoreLine
			$activity_text = sprintf( esc_html__( '%1$s (%2$s)', 'bbpress' ), $topic_title, $forum_title );
			break;

		case 'bbp_reply_create':
			// @codingStandardsIgnoreLine
			$activity_text = sprintf( esc_html__( 're: %1$s (%2$s)', 'bbpress' ), $topic_title, $forum_title );
			break;
	}

	return $activity_text;

}

add_filter( 'bp_ass_activity_notification_action', 'hcommons_bp_ass_activity_notification_action', 10, 2 );

/**
 * Reproduces email notification settings (as in legacy email system) in Group Activity Subscription
 */
function hc_custom_group_forum_subscription_settings() {

	global $current_user;
	global $groups_template, $bp;
	global $group_obj;

	?>
	<table class="notification-settings" id="groups-notification-settings">
	<thead>
		<tr>
			<th class="icon"></th>
			<th class="title"><?php _e( 'Group notifications', 'group_forum_subscription' ); ?></th>
			<th class="no-email gas-choice"><?php _e( 'No Email', 'buddypress' ); ?></th>
			<th class="weekly gas-choice"><?php _e( 'Weekly Summary', 'buddypress' ); ?></th>
			<th class="daily gas-choice"><?php _e( 'Daily Digest', 'buddypress' ); ?></th>
			<th class="new-topics gas-choice"><?php _e( 'New Topics', 'buddypress' ); ?></th>
			<th class="all-email gas-choice"><?php _e( 'All Email', 'buddypress' ); ?></th>

		</tr>
	</thead>

	<?php
	$group_types = bp_groups_get_group_types();

	foreach ( $group_types as $group_type ) {

		$args = array(
			'per_page'       => 100,
			'group_type__in' => $group_type,
			'action'         => '',
			'type'           => '',
			'orderby'        => 'name',
			'order'          => 'ASC',
		);
		if ( bp_has_groups( $args ) ) {

		?>
		<thead>
			<tr id="network">
				<th class="network-header"><?php echo strtoupper( $group_type ); ?></th>
			</tr>
		</thead>

		<tbody>

		<?php
		while ( bp_groups() ) :
			bp_the_group();

			$group_id    = bp_get_group_id();
			$subscribers = groups_get_groupmeta( $group_id, 'ass_subscribed_users' );
			$user_id     = $bp->displayed_user->id;
			$my_status   = $subscribers[ $user_id ];

		?>
				<tr>
					<td></td>

					<td>
						<a href="<?php bp_group_permalink(); ?>"><?php bp_group_name(); ?></a>
					</td>

					<td class="no-email gas-choice">
						<input type="radio" name="group-notifications[<?php echo $group_id; ?>]" value="no"
																					<?php
																					if ( 'no' == $my_status || ! $my_status ) {
															?>
															checked="checked" <?php } ?>/>
					</td>

					<td class="weekly gas-choice">
						<input type="radio" name="group-notifications[<?php echo $group_id; ?>]" value="sum"
																					<?php
																					if ( 'sum' == $my_status ) {
															?>
															checked="checked" <?php } ?>/>
					</td>

					<td class="daily gas-choice">
						<input type="radio" name="group-notifications[<?php echo $group_id; ?>]" value="dig"
																					<?php
																					if ( 'dig' == $my_status ) {
															?>
															checked="checked" <?php } ?>/>
					</td>

					<td class="new-topics gas-choice">
						<input type="radio" name="group-notifications[<?php echo $group_id; ?>]" value="sub"
																					<?php
																					if ( 'sub' == $my_status ) {
															?>
															checked="checked" <?php } ?>/>
					</td>

					<td class="weekly gas-choice">
						<input type="radio" name="group-notifications[<?php echo $group_id; ?>]" value="supersub"
																					<?php
																					if ( 'supersub' == $my_status ) {
															?>
															checked="checked" <?php } ?>/>
					</td>
				</tr>
			<?php
			endwhile;
		}
	}
	?>
	</tbody>
</table>
<?php
}

add_action( 'bp_notification_settings', 'hc_custom_group_forum_subscription_settings' );


/**
 * Adds a section for users to set their default group notifications when joining a new group.
 */
function hc_custom_default_group_forum_subscription_settings() {
	global $bp;

	$user_id   = $bp->displayed_user->id;
	$my_status = get_user_meta( $user_id, 'default_group_notifications', true );
?>

	<table class="notification-settings" id="groups-notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php _e( 'Default Notifications For New Groups', 'group_forum_subscription' ); ?></th>
				<th class="no-email gas-choice"><?php _e( 'No Email', 'buddypress' ); ?></th>
				<th class="weekly gas-choice"><?php _e( 'Weekly Summary', 'buddypress' ); ?></th>
				<th class="daily gas-choice"><?php _e( 'Daily Digest', 'buddypress' ); ?></th>
				<th class="new-topics gas-choice"><?php _e( 'New Topics', 'buddypress' ); ?></th>
				<th class="all-email gas-choice"><?php _e( 'All Email', 'buddypress' ); ?></th>

			</tr>
		</thead>

		<tbody>

		<tr>
			<td></td>

			<td>
				<!--placeholder for name to show -->
			</td>

			<td class="no-email gas-choice">
				<input type="radio" name="default-group-notifications" value="no"
				<?php if ( 'no' == $my_status || ! $my_status ) { ?>
					checked="checked"
				<?php } ?>/>
			</td>

			<td class="weekly gas-choice">
				<input type="radio" name="default-group-notifications" value="sum"
				<?php
				if ( 'sum' == $my_status ) {
?>
checked="checked" <?php } ?>/>
			</td>

			<td class="daily gas-choice">
				<input type="radio" name="default-group-notifications" value="dig"
				<?php if ( 'dig' == $my_status ) { ?>
					checked="checked"
				<?php } ?>/>
			</td>

			<td class="new-topics gas-choice">
				<input type="radio" name="default-group-notifications" value="sub"
				<?php if ( 'sub' == $my_status ) { ?>
					checked="checked"
				<?php } ?>/>
			</td>

			<td class="weekly gas-choice">
				<input type="radio" name="default-group-notifications" value="supersub"
				<?php if ( 'supersub' == $my_status ) { ?>
					checked="checked"
				<?php } ?>/>
			</td>
		</tr>
<?php
}

// @codingStandardsIgnoreLine
//add_action( 'bp_notification_settings', 'hc_custom_default_group_forum_subscription_settings' );

/**
 * Save group notification email settings.
 **/
function hc_custom_update_group_subscribe_settings() {
	global $bp;

	if ( ! bp_is_settings_component() && ! bp_is_current_action( 'notifications' ) ) {
		return false;
	}

	// If the edit form has been submitted, save the edited details.
	if ( isset( $_POST['group-notifications'] ) ) {
		$user_id = bp_loggedin_user_id();

		foreach ( $_POST['group-notifications'] as $group_id => $value ) {
			// Save the setting.
			ass_group_subscription( $value, $user_id, $group_id );
		}
	}

	if ( isset( $_POST['default-group-notifications'] ) ) {
		$user_id = bp_loggedin_user_id();
		$value   = $_POST['default-group-notifications'];

		update_user_meta( $user_id, 'default_group_notifications', $value );
	}

}

add_action( 'bp_actions', 'hc_custom_update_group_subscribe_settings' );

/**
 * Give the user a notice if they are default subscribed to this group (does not work for invites or requests).
 *
 * @param int $group_id ID of the group the member has joined.
 * @param int $user_id ID of the user who joined the group.
 **/
function hc_custom_join_group_message( $group_id, $user_id ) {

	remove_action( 'groups_join_group', 'ass_join_group_message' );

	if ( bp_loggedin_user_id() != $user_id ) {
		return; }

	$status = get_user_meta( $user_id, 'default_group_notifications', true );

	if ( empty( $status ) ) {
		$status = 'no';
		update_user_meta( $user_id, 'default_group_notifications', 'no' );
	}

	ass_group_subscription( $status, $user_id, $group_id );

	bp_core_add_message( __( 'You successfully joined the group. Your group email status is: ', 'bp-ass' ) . ass_subscribe_translate( $status ) );

}

// @codingStandardsIgnoreLine
//add_action( 'groups_join_group', 'hc_custom_join_group_message', 2, 2 );

/**
 * Overwrite unsubscribe link in e-mails.
 *
 * @param string   $formatted_tokens Associative pairing of token names (key) and replacement values (value).
 *
 * @param string   $tokens Associative pairing of unformatted token names (key) and replacement values (value).
 *
 * @param BP_Email $instance Current instance of the email type class.
 */
function hc_custom_bp_email_set_tokens( $formatted_tokens, $tokens, $instance ) {
	$formatted_tokens['unsubscribe'] = bp_displayed_user_domain() . bp_get_settings_slug() . '/notifications';

	return $formatted_tokens;
}

add_filter( 'bp_email_set_tokens', 'hc_custom_bp_email_set_tokens', 1, 3 );

/**
 * Change group digest unsubscribe link in e-mails.
 *
 *  @param string $unsubscribe_message The unsubscribe message.
 *
 *  @param string $userdomain_bp_groups_slug The url containing the userdomain and the groups slug.
 **/
function hc_custom_ass_digest_disable_notifications( $unsubscribe_message, $userdomain_bp_groups_slug ) {
	$userdomain = explode( '/', $userdomain_bp_groups_slug );

	if ( ! isset( $userdomain[4] ) ) {
		return $unsubscribe_message;
	}

	$settings_page = bp_get_settings_slug() . '/notifications';

	// @codingStandardsIgnoreLine
	$unsubscribe_message = '\n\n' . sprintf( __( 'To disable these notifications per group please login and go to: %s where you can change your email settings for each group.', 'bp-ass' ), '<a href="https://{$userdomain[2]}/{$userdomain[3]}/{$userdomain[4]}/{$settings_page}/">' . __( 'My Groups', 'bp-ass' ) . '</a>' );

	return $unsubscribe_message;
}

add_filter( 'ass_digest_disable_notifications', 'hc_custom_ass_digest_disable_notifications', 10, 2 );

/**
 * Add custom BP email footer for HTML emails.
 *
 * We want to override the default {{unsubscribe}} token with something else.
 **/
function hc_custom_ass_bp_email_footer_html_unsubscribe_links() {
	$tokens = buddypress()->ges_tokens;

	if ( ! isset( $tokens['subscription_type'] ) ) {
		return;
	}

	remove_action( 'bp_after_email_footer', 'ass_bp_email_footer_html_unsubscribe_links' );

	$userdomain    = strtok( $tokens['ges.unsubscribe'], '?' );
	$settings_page = $userdomain . '/settings/notifications/';

	$link_format  = '<a href="%1$s" title="%2$s" style="text-decoration: underline;">%3$s</a>';
	$footer_links = array();

	switch ( $tokens['subscription_type'] ) {
		// Self-notifications.
		case 'self_notify':
			$footer_links[] = sprintf(
				$link_format,
				$tokens['ges.settings-link'],
				esc_attr__( 'Once you are logged in, uncheck "Receive notifications of your own posts?".', 'bp-ass' ),
				esc_html__( 'Change email settings', 'bp-ass' )
			);

			break;

		// Everything else.
		case 'sub':
		case 'supersub':
		case 'dig':
		case 'sum':
			$footer_links[] = sprintf(
				$link_format,
				$settings_page,
				esc_attr__( 'Once you are logged in, change your email settings for each group.', 'bp-ass' ),
				esc_html__( 'Change email settings', 'bp-ass' )
			);

			break;
	}

	if ( ! empty( $footer_links ) ) {
		echo implode( ' &middot; ', $footer_links );
	}

		unset( buddypress()->ges_tokens );
}

add_action( 'bp_after_email_footer', 'hc_custom_ass_bp_email_footer_html_unsubscribe_links' );

/**
 * Disable the default subscription settings during group creation.
 */
function hc_custom_disable_subscription_settings_form() {
	remove_action( 'bp_after_group_settings_creation_step', 'ass_default_subscription_settings_form' );
}

// @codingStandardsIgnoreLine
//add_action( 'bp_after_group_settings_creation_step', 'hc_custom_disable_subscription_settings_form', 0 );

/**
 * Set default notification for user on accept or invite.
 *
 * @param int $user_id  ID of the user who joined the group.
 * @param int $group_id ID of the group the member has joined.
 */
function hc_custom_set_notifications_on_accept_invite_or_request( $user_id, $group_id ) {

	$status = get_user_meta( $user_id, 'default_group_notifications', true );

	if ( empty( $status ) ) {
		$status = 'no';
		update_user_meta( $user_id, 'default_group_notifications', 'no' );
	}

	ass_group_subscription( $status, $user_id, $group_id );
}

add_action( 'groups_accept_invite', 'hc_custom_set_notifications_on_accept_invite_or_request', 20, 2 );
add_action( 'groups_membership_accepted', 'hc_custom_set_notifications_on_accept_invite_or_request', 20, 2 );

/**
 * Adds a section for users to set their newsletter settings
 */
function hc_custom_newsletter_settings() {
	global $bp;

	$user_id          = $bp->displayed_user->id;
	$newsletter_optin = get_user_meta( $user_id, 'newsletter_optin', true );
?>

	<table class="notification-settings" id="groups-notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php _e( 'Newsletter', 'group_forum_subscription' ); ?></th>
				<th class="no-email gas-choice"><?php _e( 'Yes', 'buddypress' ); ?></th>
				<th class="weekly gas-choice"><?php _e( 'No', 'buddypress' ); ?></th>
			</tr>
		</thead>

		<tbody>

		<tr>
			<td></td>

			<td>
				Subscribe to Newsletter
			</td>

			<td class="no-newsletter gas-choice">
				<input type="radio" name="newsletter-optin" value="yes"
				<?php if ( 'yes' === $newsletter_optin ) : ?>
					checked="checked"
				<?php endif; ?>/>
			</td>

			<td class="yes-newsletter gas-choice">
				<input type="radio" name="newsletter-optin" value="no"
				<?php if ( 'no' === $newsletter_optin || ! $newsletter_optin ) : ?>
					checked="checked"
				<?php endif; ?>/>
			</td>

		</tr>
<?php
}

add_action( 'bp_notification_settings', 'hc_custom_newsletter_settings' );

/**
 * Save group notification email settings.
 **/
function hc_custom_update_newsletter_settings() {
	global $bp;

	if ( ! bp_is_settings_component() && ! bp_is_current_action( 'notifications' ) ) {
		return false;
	}

	if ( isset( $_POST['newsletter-optin'] ) ) {
		$user_id = bp_loggedin_user_id();
		$value   = $_POST['newsletter-optin'];

		update_user_meta( $user_id, 'newsletter_optin', $value );
	}

}

add_action( 'bp_actions', 'hc_custom_update_newsletter_settings' );
