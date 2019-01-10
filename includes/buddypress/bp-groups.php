<?php
/**
 * Customizations to BuddyPress Groups.
 *
 * @package Hc_Custom
 */

/**
 * Adds new button labels for society members. For non society see hcommons_add_non_society_member_join_group_button()
 *
 * @param string $button HTML button for joining a group.
 * @param object $group BuddyPress group object.
 *
 * @return mixed
 */
function hcommons_filter_groups_button_labels( $button, $group ) {
	$status = $group->status;
	if ( ! is_super_admin() && hcommons_check_non_member_active_session() ) {
		$button['link_class'] = 'disabled-button';
	}
	if ( empty( BP_Groups_Member::check_is_member( get_current_user_id(), $group->id ) ) ) {
		switch ( $status ) {
			case 'public':
				$button['link_text'] = 'Join Group';
				break;

			case 'private':
				$button['link_text'] = 'Request Membership';
				break;
		}
	}

	return $button;
}
add_filter( 'bp_get_group_join_button', 'hcommons_filter_groups_button_labels', 10, 2 );

/**
 * Filters the action for the new group activity update.
 *
 * @param string $activity_action The new group activity update.
 */
function hcommons_filter_groups_activity_new_update_action( $activity_action ) {
	$activity_action = preg_replace( '/(in the group <a href="[^"]*)(">)/', '\1activity\2', $activity_action );
	return $activity_action;
}
add_filter( 'groups_activity_new_update_action', 'hcommons_filter_groups_activity_new_update_action' );

/**
 * Adds join group button for non-society members
 */
function hcommons_add_non_society_member_join_group_button() {
	if ( ! is_super_admin() && hcommons_check_non_member_active_session() ) {
		global $groups_template;

		// Set group to current loop group if none passed.
		if ( empty( $group ) ) {
			$group =& $groups_template->group;
		}
		$is_not_committee = strtolower( \groups_get_groupmeta( $group->id, 'society_group_type', true ) ) !== 'committee';

		if ( $is_not_committee ) {
			$message = 'Join Group';
			if ( 'private' === $group->status ) {
				$message = 'Request Membership</div>';
			}
			echo '<div class="disabled-button">' . $message . '</div>';
		}
	}
}
add_action( 'bp_directory_groups_actions', 'hcommons_add_non_society_member_join_group_button' );

/**
 * Adds disclaimer notice for non society members
 */
function hcommons_add_non_society_member_disclaimer_group() {
	if ( ! is_super_admin() && hcommons_check_non_member_active_session() ) {
		printf(
			'<div class="non-member-disclaimer">Only %1$s members can join these groups.<br><a href="/register">Join %1$s now</a>!</div>',
			strtoupper( Humanities_Commons::$society_id )
		);
	}
}
add_action( 'bp_before_directory_groups_content', 'hcommons_add_non_society_member_disclaimer_group' );

/**
 * On the current group page, reconfigure the group nav when a forum is
 * enabled for the group.
 *
 * What we do here is:
 *  - move the 'Forum' tab to the beginning of the nav
 *  - rename the 'Home' tab to 'Activity'
 */
function hcommons_override_config_group_nav() {
		$group_slug = bp_current_item();

		// BP 2.6+.
	if ( function_exists( 'bp_rest_api_init' ) ) {
			buddypress()->groups->nav->edit_nav( array( 'position' => 1 ), 'forum', $group_slug );
			buddypress()->groups->nav->edit_nav( array( 'position' => 0 ), 'home', $group_slug );
			buddypress()->groups->nav->edit_nav( array( 'name' => __( 'Activity', 'buddypress' ) ), 'home', $group_slug );

		// Older versions of BP.
	} else {
			buddypress()->bp_options_nav[ $group_slug ]['home']['position']  = 0;
			buddypress()->bp_options_nav[ $group_slug ]['forum']['position'] = 1;
			buddypress()->bp_options_nav[ $group_slug ]['home']['name']      = __( 'Activity', 'buddypress' );
	}

}

/**
 * Set the group default tab to 'forum' if the current group has a forum
 * attached to it.
 *
 * @param string $retval Navigation slug.
 */
function hcommons_override_cbox_set_group_default_tab( $retval ) {
	$group_id = bp_get_current_group_id();

	// If there is a landing page set, use it instead.
	$retval = ! empty( groups_get_groupmeta( $group_id, 'group_landing_page' ) ) ? groups_get_groupmeta( $group_id, 'group_landing_page' ) : $retval;

	// Check if bbPress or legacy forums are active and configured properly.
	if ( ( function_exists( 'bbp_is_group_forums_active' ) && bbp_is_group_forums_active() ) ||
				( function_exists( 'bp_forums_is_installed_correctly' ) && bp_forums_is_installed_correctly() ) ) {

		// If current group does not have a forum attached, stop now!
		if ( ! bp_group_is_forum_enabled( groups_get_current_group() ) ) {
					return $retval;
		}

			// Allow non-logged-in users to view a private group's homepage.
		if ( false === is_user_logged_in() && groups_get_current_group() && 'private' === bp_get_new_group_status() ) {
				return $retval;
		}

			// Reconfigure the group's nav.
			add_action( 'bp_actions', 'hcommons_override_config_group_nav', 99 );

			// Finally, use 'forum' as the default group tab.
			return ! empty( groups_get_groupmeta( $group_id, 'group_landing_page' ) ) ? groups_get_groupmeta( $group_id, 'group_landing_page' ) : 'home';
	}

		return $retval;
}
add_filter( 'bp_groups_default_extension', 'hcommons_override_cbox_set_group_default_tab', 100 );

/**
 * BuddyPress Groups Forbidden Group Slugs
 * Used for groups that have been redirected or slugs that we want to reserve.
 *
 * @param array $forbidden_names List of forbidden group slugs.
 */
function mla_bp_groups_forbidden_names( $forbidden_names ) {

	$mla_forbidden_group_slugs = array(
		'style',
	);

	return array_merge( $forbidden_names, $mla_forbidden_group_slugs );

}
add_filter( 'groups_forbidden_names', 'mla_bp_groups_forbidden_names', 10, 1 );

/**
 * Set forums' status to match the privacy status of the associated group.
 *
 * Fired whenever a group is saved.
 *
 * @param BP_Groups_Group $group Group object.
 */
function update_group_forum_visibility( BP_Groups_Group $group ) {
	global $wpdb;

	$bp = buddypress();

	// Get group forum IDs.
	$forum_ids = bbp_get_group_forum_ids( $group->id );

	// Bail if no forum IDs available.
	if ( empty( $forum_ids ) ) {
			return;
	}

	// Loop through forum IDs.
	foreach ( $forum_ids as $forum_id ) {

		// Get forum from ID.
		$forum = bbp_get_forum( $forum_id );

		// Check for change.
		if ( $group->status !== $forum->post_status ) {
			switch ( $group->status ) {

				// Changed to hidden.
				case 'hidden':
						bbp_hide_forum( $forum_id, $forum->post_status );
					break;

				// Changed to private.
				case 'private':
						bbp_privatize_forum( $forum_id, $forum->post_status );
					break;

				// Changed to public.
				case 'public':
				default:
						bbp_publicize_forum( $forum_id, $forum->post_status );
					break;
			}
		}
	}

	// Update activity table.
	switch ( $group->status ) {
		// Changed to hidden.
		case 'hidden':
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %s SET hide_sitewide = 1 WHERE item_id = %d AND component = 'groups'",
					$bp->activity->table_name,
					$group->id
				)
			);
			break;

		// Changed to private.
		case 'private':
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %s SET hide_sitewide = 1 WHERE item_id = %d AND component = 'groups'",
					$bp->activity->table_name,
					$group->id
				)
			);
			break;

		// Changed to public.
		case 'public':
		default:
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %s SET hide_sitewide = 0 WHERE item_id = %d AND component = 'groups'",
					$bp->activity->table_name,
					$group->id
				)
			);
			break;
	}
}

add_action( 'groups_group_after_save', 'update_group_forum_visibility' );

/**
 * Set forums' status to match the privacy status of the associated group.
 *
 * @param string $parent_slug The group slug.
 */
function hc_custom_get_options_nav( $parent_slug = '' ) {
	global $bp;

	?>
	<h4><?php _e( 'Show or Hide Menu Items for Members', 'group_forum_menu' ); ?></h4>
	<table class="group-nav-settings" >
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"></th>
				<th class="show gas-choice"><?php _e( 'Show', 'buddypress' ); ?></th>
				<th class="hide gas-choice"><?php _e( 'Hide', 'buddypress' ); ?></th>
			</tr>
	</thead>
	<tbody>

	<?php

	$group_id              = bp_get_group_id();
	$current_item          = bp_current_item();
	$single_item_component = bp_current_component();

	// Adjust the selected nav item for the current single item if needed.
	if ( ! empty( $parent_slug ) ) {
		$current_item  = $parent_slug;
		$selected_item = bp_action_variable( 0 );
	}

	// If the nav is not defined by the parent component, look in the Members nav.
	if ( ! isset( $bp->{$single_item_component}->nav ) ) {
		$secondary_nav_items = $bp->members->nav->get_secondary( array( 'parent_slug' => $current_item ) );
	} else {
		$secondary_nav_items = $bp->{$single_item_component}->nav->get_secondary( array( 'parent_slug' => $current_item ) );
	}

	if ( ! $secondary_nav_items ) {
		return false;
	}

	foreach ( $secondary_nav_items as $subnav_item ) :
		// List type depends on our current component.
		$list_type = bp_is_group() ? 'groups' : 'personal';

		if ( 'groups_screen_group_admin' === $subnav_item->screen_function || 'members' === $subnav_item->slug || 'invite-anyone' == $subnav_item->slug || 'notifications' === $subnav_item->slug ) {
			continue;
		}

		$current_status = ! empty( groups_get_groupmeta( $group_id, $subnav_item->slug ) ) ? groups_get_groupmeta( $group_id, $subnav_item->slug ) : '';
		?>

		<tr>
			<td></td>
			<td>
				<a href="<?php echo esc_url( $subnav_item->link ); ?>"> <?php echo $subnav_item->name; ?> </a>
			</td>

			<td class="show gas-choice">
				<input type="radio" name="group-nav-settings[<?php echo $subnav_item->slug; ?>]" value="show"
					<?php
					if ( 'show' == $current_status || ! $current_status ) {
						?>
						checked="checked" <?php } ?>/>
			</td>

			<td class="hide gas-choice">
				<input type="radio" name="group-nav-settings[<?php echo $subnav_item->slug; ?>]" value="hide"
					<?php
					if ( 'hide' == $current_status ) {
						?>
						checked="checked" <?php } ?>/>
			</td>
		</tr>
		<?php endforeach; ?>

		</tbody>
	</table>

	<?php
}

/**
 * Save group nav settings.
 *
 * @param int $group_id The group id.
 */
function hc_custom_groups_nav_settings( $group_id ) {

	$group_nav_settings = isset( $_POST['group-nav-settings'] ) ? $_POST['group-nav-settings'] : '';
	$group_landing_page = isset( $_POST['group-landing-page-select'] ) ? $_POST['group-landing-page-select'] : '';
	$group              = groups_get_group( array( 'group_id' => $group_id ) );
	$group_slug         = $group->slug;

	if ( ! empty( $group_nav_settings ) ) {

		foreach ( $_POST['group-nav-settings'] as $menu_item => $value ) {
			groups_update_groupmeta( $group_id, $menu_item, $value );
		}
	}

	if ( ! empty( $group_landing_page ) ) {
		groups_update_groupmeta( $group_id, 'group_landing_page', $group_landing_page );
	}

}
add_action( 'groups_settings_updated', 'hc_custom_groups_nav_settings' );

/**
 * Remove tabs based on group settings.
 */
function hc_custom_remove_group_manager_subnav_tabs() {
	global $bp;

	// Site admins will see all tabs.
	if ( ! bp_is_group() || is_super_admin() ) {
		return;
	}

	$group_id = bp_get_current_group_id();

	// Group admins will see all tabs.
	if ( ! $group_id || groups_is_user_admin( get_current_user_id(), $group_id ) ) {
		return;
	}

	$parent_nav_slug     = bp_get_current_group_slug();
	$secondary_nav_items = $bp->groups->nav->get_secondary( array( 'parent_slug' => $parent_nav_slug ) );

	$selected_item = null;

	// Remove the nav items. Not stored, just unsets it.
	foreach ( $secondary_nav_items as $subnav_item ) {
		if ( 'hide' === groups_get_groupmeta( $group_id, $subnav_item->slug ) ) {

			bp_core_remove_subnav_item( $parent_nav_slug, $subnav_item->slug, 'groups' );
		}
	}
}
add_action( 'bp_actions', 'hc_custom_remove_group_manager_subnav_tabs' );

/**
 * Allow group admin to change the default landing page.
 */
function hc_custom_choose_landing_page() {
	global $bp;

	$group_id        = bp_get_group_id();
	$parent_nav_slug = bp_get_current_group_slug();
	$selected        = groups_get_groupmeta( $group_id, 'group_landing_page' );

	$parent_nav_slug     = bp_current_item();
	$secondary_nav_items = $bp->groups->nav->get_secondary( array( 'parent_slug' => $parent_nav_slug ) );

	?>
		<h4><?php _e( 'Select Default Landing Page for Group', 'group_forum_menu' ); ?></h4>

		<select name="group-landing-page-select" id="group-landing-page-select">

			<?php foreach ( $secondary_nav_items as $subnav_item ) :

				$name = preg_replace('/\d/', '', $subnav_item->name );

				if ( 'groups_screen_group_admin' === $subnav_item->screen_function ) {
					continue;
				}
				?>

				<option value="<?php echo esc_attr( $subnav_item->slug ); ?>"<?php selected( $subnav_item->slug, $selected ); ?>><?php echo $name ?></option>

			<?php endforeach; ?>

		</select>

	<?php

}

/**
 * Hide the request membership tab for MLA committees.
 *
 * @param string $string Unchanged filter string.
 */
function hc_custom_modify_nav( $string, $subnav_item, $selected_item ) {
	global $bp;

	// Site admins will see all tabs.
	if ( ! bp_is_group() ) {
		return $string;
	}

	$group_id = bp_get_current_group_id();

	// Group admins will see all tabs.
	if ( ! $group_id && ( !groups_is_user_admin( get_current_user_id(), $group_id ) || ! is_super_admin() ) ) {

		return $string;
	}

	if ( 'hide' === groups_get_groupmeta( $group_id, $subnav_item->slug ) ) {
		$string = '<li id="' . esc_attr( $subnav_item->css_id . '-groups-li' ) . '" ' . $selected_item . '><span class="disabled-nav"> '. $subnav_item->name . '</span></li>';
	}

	return $string;
}

function hc_custom_modify_home_nav( $string, $subnav_item, $selected_item ) {
	return hc_custom_modify_nav($string, $subnav_item, $selected_item);
}

add_filter( 'bp_get_options_nav_home', 'hc_custom_modify_home_nav', 10, 3 );

function hc_custom_modify_forum_nav( $string, $subnav_item, $selected_item ) {
	return hc_custom_modify_nav($string, $subnav_item, $selected_item);
}

add_filter( 'bp_get_options_nav_nav-forum', 'hc_custom_modify_forum_nav', 10, 3 );

function hc_custom_modify_events_nav( $string, $subnav_item, $selected_item ) {
	return hc_custom_modify_nav($string, $subnav_item, $selected_item);
}

add_filter( 'bp_get_options_nav_nav-events', 'hc_custom_modify_events_nav', 10, 3 );


function hc_custom_modify_deposits_nav( $string, $subnav_item, $selected_item ) {
	return hc_custom_modify_nav($string, $subnav_item, $selected_item);
}

add_filter( 'bp_get_options_nav_deposits', 'hc_custom_modify_deposits_nav', 10, 3 );


function hc_custom_modify_docs_nav( $string, $subnav_item, $selected_item ) {
	return hc_custom_modify_nav($string, $subnav_item, $selected_item);
}

add_filter( 'bp_get_options_nav_nav-docs', 'hc_custom_modify_docs_nav', 10, 3 );

function hc_custom_modify_documents_nav( $string, $subnav_item, $selected_item ) {
	return hc_custom_modify_nav($string, $subnav_item, $selected_item);
}

add_filter( 'bp_get_options_nav_nav-documents', 'hc_custom_modify_documents_nav', 10, 3 );

function hc_custom_modify_blog_nav( $string, $subnav_item, $selected_item ) {
	return hc_custom_modify_nav($string, $subnav_item, $selected_item);
}

add_filter( 'bp_get_options_nav_nav-group-blog', 'hc_custom_modify_blog_nav', 10, 3 );


