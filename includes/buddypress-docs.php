<?php
/**
 * Custom Changes to BuddyPress Docs plugin.
 *
 * @package Hc_Custom
 */

/**
 * Modifies the BP DOCS attachment url
 *
 */
function hc_bp_docs_attachment_url_base( $att_url, $attachment ) {

        global $wpdb;
        $url = $att_url;
        $society_id = Humanities_Commons::$society_id;
        if ( 'hc' !== $society_id ) {
                $url_components = parse_url( $att_url ); 
                $url = $url_components['scheme'] . '://' . $society_id . '.' . $url_components['host'] . $url_components['path'];
        }
        return $url;
}
add_filter( 'bp_docs_attachment_url_base', 'hc_bp_docs_attachment_url_base', 10, 2 );

/**
 * Modifies the default sort order. If it isn't set in the admin
 * settings it will default to title.
 *
 * @param str $order_by The order_by item: title, author, created, modified, etc.
 */
function hc_custom_bp_docs_default_sort_order( $order_by ) {

	$bp = buddypress();

	if ( isset( $bp->groups->current_group->id ) ) {
		// Default to the current group first.
		$group_id = $bp->groups->current_group->id;
	} elseif ( isset( $groups_template->group->id ) ) {
		// Then see if we're in the loop.
		$group_id = $groups_template->group->id;
	} else {
		return false;
	}

	$order_by = ! empty( groups_get_groupmeta( $group_id, 'bp_docs_orderby_default' ) ) ? groups_get_groupmeta( $group_id, 'bp_docs_orderby_default' ) : 'title';

	return $order_by;
}

add_filter( 'bp_docs_default_sort_order', 'hc_custom_bp_docs_default_sort_order' );

/**
 * Order attachments for a Doc alphabetically.
 *
 * @param array $atts_args Optional post args for the query.
 * @param int   $doc_id ID of the document.
 */
function hc_custom_bp_docs_get_doc_attachments_args( $atts_args, $doc_id ) {

	$order = array(
		'order'   => 'ASC',
		'orderby' => 'title',
	);

	$merged_array = array_merge( $atts_args, $order );

	return $merged_array;
}

add_filter( 'bp_docs_get_doc_attachments_args', 'hc_custom_bp_docs_get_doc_attachments_args', 10, 2 );

/**
 * Add meta field for numbered titles so that they
 * sort in order.
 *
 * @param int $doc_id ID of the document.
 */
function hc_custom_bp_docs_after_save( $doc_id ) {

	$post_title = get_the_title( $doc_id );

	preg_match_all( '!\d+!', $post_title, $matches );

	$number = implode( ' ', $matches[0] );

	if ( is_numeric( $number ) ) {
		update_post_meta( $doc_id, 'bp_docs_orderby', $number );
	} else {
		update_post_meta( $doc_id, 'bp_docs_orderby', 0 );
	}
}

add_action( 'bp_docs_after_save', 'hc_custom_bp_docs_after_save' );

/**
 * Change the query to include sort order for titles with numbers.
 *
 * @param array  $query_args Array of the args passed wo BP_Docs_Query.
 * @param object $bp_docs_query Object of the current query.
 */
function hc_custom_bp_docs_pre_query_args( $query_args, $bp_docs_query ) {

	$posted_orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : '';

	if ( empty( $posted_orderby ) ) {
		$query_args['orderby']  = 'meta_value_num title';
		$query_args['meta_key'] = 'bp_docs_orderby';
	}

	return $query_args;
}

add_filter( 'bp_docs_pre_query_args', 'hc_custom_bp_docs_pre_query_args', 10, 2 );

/**
 * Find out what the groups default orderby is or set the default.
 *
 * @param int $group_id The group id.
 */
function hc_custom_bp_group_get_orderby( $group_id = false ) {
	global $groups_template;

	if ( ! $group_id ) {
		$bp = buddypress();

		if ( isset( $bp->groups->current_group->id ) ) {
			// Default to the current group first.
			$group_id = $bp->groups->current_group->id;
		} elseif ( isset( $groups_template->group->id ) ) {
			// Then see if we're in the loop.
			$group_id = $groups_template->group->id;
		} else {
			return false;
		}
	}

	$orderby_default = groups_get_groupmeta( $group_id, 'bp_docs_orderby_default' );

	// When 'orderby_default' is not set, fall back to a default value.
	if ( ! $orderby_default ) {
		$orderby_default = 'title';
	}

	return $orderby_default;
}

/**
 * Output the 'checked' value, if needed, for a given sort order on the group admin screens.
 *
 * @param string      $setting The setting you want to check against ('members',
 *                             'mods', or 'admins').
 * @param object|bool $group   Optional. Group object. Default: current group in loop.
 */
function hc_custom_bp_group_show_orderby_default_setting( $setting, $group = false ) {
	$group_id = isset( $group->id ) ? $group->id : false;

	$orderby_status = hc_custom_bp_group_get_orderby( $group_id );

	if ( $setting == $orderby_status ) {
		echo ' checked="checked"';
	}
}

/**
 * When the Docs sort settings are updated save the custom meta field.
 *
 * @param int $group_id The group id.
 */
function hc_custom_groups_settings_updated( $group_id ) {
	$group_docs_orderby = isset( $_POST['group-docs-orderby'] ) ? $_POST['group-docs-orderby'] : '';
	$group_docs_toggle  = isset( $_POST['group-docs-toggle'] ) ? $_POST['group-docs-toggle'] : '';

	if ( ! empty( $group_docs_orderby ) ) {
		groups_update_groupmeta( $group_id, 'bp_docs_orderby_default', $group_docs_orderby );
	}

	if ( ! empty ( $group_docs_toggle) ) {
		groups_update_groupmeta( $group_id, 'bp_docs_toggle_default', $group_docs_toggle );
	}

}

add_action( 'groups_settings_updated', 'hc_custom_groups_settings_updated' );

add_filter( 'bp_docs_allow_comment_section', '__return_true', 999 );

/**
 * Update post meta for folders.
 *
 * @param int    $post_id The post id.
 * @param object $post The post object.
 */
function hc_custom_buddypress_docs_save_post( $post_id, $post ) {

	if ( 'bp_docs_folder' === $post->post_type ) {

		$post_title = $post->post_title;
		$folder_id  = $post->ID;

		preg_match_all( '!\d+!', $post_title, $matches );

		$number = implode( ' ', $matches[0] );

		if ( is_numeric( $number ) ) {
			update_post_meta( $folder_id, 'bp_docs_orderby', $number );
		} else {
			update_post_meta( $folder_id, 'bp_docs_orderby', 0 );
		}
	}

}

add_action( 'save_post', 'hc_custom_buddypress_docs_save_post', 10, 2 );

/**
 * Sort numbered folder titles correctly.
 *
 * @param object $query The queried object.
 */
function hc_custom_pre_get_posts( $query ) {
	if ( 'bp_docs_folder' === $query->get( 'post_type' ) ) {
		if ( bp_docs_is_bp_docs_page() ) {
			$query->set( 'orderby', 'meta_value_num title' );
			$query->set( 'meta_key', 'bp_docs_orderby' );
		}
	}

	if ( 'attachment' === $query->get( 'post_type' ) ) {
		if ( bp_docs_is_bp_docs_page() ) {
			$query->set( 'posts_per_page', -1 );
		}
	}

	return $query;
}

add_action( 'pre_get_posts', 'hc_custom_pre_get_posts' );

/**
 * Echo the correct class according to the group settings.
 *
 */
function hc_custom_bp_docs_toggleable_open_or_closed_class() {
	global $groups_template;

	$bp = buddypress();

    if ( isset( $bp->groups->current_group->id ) ) {
            // Default to the current group first.
            $group_id = $bp->groups->current_group->id;
    } elseif ( isset( $groups_template->group->id ) ) {
            // Then see if we're in the loop.
            $group_id = $groups_template->group->id;
    } else {
            return false;
    }

    $toggle = ! empty( groups_get_groupmeta( $group_id, 'bp_docs_toggle_default' ) ) ? groups_get_groupmeta( $group_id, 'bp_docs_toggle_default' ) : 'toggle-closed';

	echo $toggle;
}


/**
 * Output the 'checked' value, if needed, for a given html class.
 *
 * @param string      $setting The setting you want to check against ('members',
 *                             'mods', or 'admins').
 * @param object|bool $group   Optional. Group object. Default: current group in loop.
 */
function hc_custom_bp_group_docs_toggle_default_setting( $setting, $group = false ) {
        $group_id = isset( $group->id ) ? $group->id : false;

        $toggle_status = hc_custom_bp_group_get_toggle( $group_id );

        if ( $setting == $toggle_status ) {
                echo ' checked="checked"';
        }
}

/**
 * Find out what the groups default toggle is or set the default.
 *
 * @param int $group_id The group id.
 */
function hc_custom_bp_group_get_toggle( $group_id = false ) {
        global $groups_template;

        if ( ! $group_id ) {
                $bp = buddypress();

                if ( isset( $bp->groups->current_group->id ) ) {
                        // Default to the current group first.
                        $group_id = $bp->groups->current_group->id;
                } elseif ( isset( $groups_template->group->id ) ) {
                        // Then see if we're in the loop.
                        $group_id = $groups_template->group->id;
                } else {
                        return false;
                }
        }

        $toggle_default = groups_get_groupmeta( $group_id, 'bp_docs_toggle_default' );

        // When 'orderby_default' is not set, fall back to a default value.
        if ( ! $toggle_default ) {
                $toggle_default = 'off';
        }

        return $toggle_default;
}


 add_action( 'bp_docs_before_tags_meta_box', 'hc_custom_remove_bp_docs_folders_meta_box' , 0 );


/**
 * Remove buddypress-docs version of the folder metabox.
 *
 */
function hc_custom_remove_bp_docs_folders_meta_box() {
		remove_action( 'bp_docs_before_tags_meta_box', 'bp_docs_folders_meta_box' );
}

	add_action( 'bp_docs_before_tags_meta_box', 'hc_custom_bp_docs_folders_meta_box' );

/**
 * Add the meta box to the edit page.
 *
 */
function hc_custom_bp_docs_folders_meta_box() {

        $doc_id = get_the_ID();
        $associated_group_id = bp_is_active( 'groups' ) ? bp_docs_get_associated_group_id( $doc_id ) : 0;

        if ( ! $associated_group_id && isset( $_GET['group'] ) ) {
                $group_id = BP_Groups_Group::get_id_from_slug( urldecode( $_GET['group'] ) );
                if ( current_user_can( 'bp_docs_associate_with_group', $group_id ) ) {
                        $associated_group_id = $group_id;
                }
        }

        // On the Create screen, respect the 'folder' $_GET param
		if ( bp_docs_is_doc_create() ) {
			$folder_id = bp_docs_get_current_folder_id();
		} else {
		$folder_id = bp_docs_get_doc_folder( $doc_id );
		}

	?>

	<div id="doc-folders" class="doc-meta-box">
		<div class="toggleable <?php hc_custom_bp_docs_toggleable_open_or_closed_class() ?>">
			<p id="folders-toggle-edit" class="toggle-switch">
				<span class="hide-if-js toggle-link-no-js"><?php _e( 'Folders', 'bp-docs' ) ?></span>
				<a class="hide-if-no-js toggle-link" id="folders-toggle-link" href="#"><span class="show-pane plus-or-minus"></span><span class="toggle-title"><?php _e( 'Folders', 'bp-docs' ) ?></span></a>
			</p>

			<div class="toggle-content">
				<table class="toggle-table" id="toggle-table-folders">
					<tr>
						<td class="desc-column">
							<label for="bp_docs_tag"><?php _e( 'Select a folder for this Doc.', 'bp-docs' ) ?></label>
						</td>

						<td>
							<div class="existing-or-new-selector">
								<input type="radio" name="existing-or-new-folder" id="use-existing-folder" value="existing" checked="checked" />
								<label for="use-existing-folder" class="radio-label"><?php _e( 'Use an existing folder', 'bp-docs' ) ?></label><br />
								<div class="selector-content">
									<?php bp_docs_folder_selector( array(
										'name'     => 'bp-docs-folder',
										'id'       => 'bp-docs-folder',
										'group_id' => $associated_group_id,
										'selected' => $folder_id,
									) ) ?>
								</div>
							</div>

							<div class="existing-or-new-selector" id="new-folder-block">
								<input type="radio" name="existing-or-new-folder" id="create-new-folder" value="new" />
								<label for="create-new-folder" class="radio-label"><?php _e( 'Create a new folder', 'bp-docs' ) ?></label>
								<div class="selector-content">

									<?php bp_docs_create_new_folder_markup( array(
										'group_id' => $associated_group_id,
										'selected' => $associated_group_id,
									) ) ?>
								</div><!-- .selector-content -->
							</div>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>

	<?php
}

/**
 * Prevent duplicate and delayed buddypress notifications from showing.
 *
 * This addresses @link https://github.com/MESH-Research/commons/issues/77
 *
 * This function is not called directly. It is called through the
 * 'bp_core_render_message' action, which occurs immediately after a buddypress
 * notification has been displayed.
 * 
 * @see buddypress/bp-core/bp-core-functions.php bp_core_render_message()
 *
 * @author Mike Thicke
 *
 * @global $bp The BuddyPress object.
 */
function hcommons_prevent_bp_message_duplicates() {
	global $bp;
	$bp->template_message = null; //Prevent message from being shown twice.

	// Prevent message from being shown on next page load.
	@setcookie( 'bp-message', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
	@setcookie( 'bp-message-type', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
}
add_action( 'bp_core_render_message', 'hcommons_prevent_bp_message_duplicates', 10 );

function hcommons_restricted_comment_terms_doc_fallback( $terms, $term_query ) {
	if (
		isset( $term_query->query_vars['taxonomy'] ) && 
		is_array( $term_query->query_vars['taxonomy'] ) &&
		count( $term_query->query_vars['taxonomy'] ) > 0 &&
		$term_query->query_vars['taxonomy'][0] === 'bp_docs_comment_access' 
	) {
		if ( array_key_exists( 'slug', $term_query->query_vars ) ) {
			if ( in_array( 'default-term-query-in-progress', $term_query->query_vars['slug'] ) ) {
				return;
			} else {
				$term_query_copy = clone( $term_query );
				$term_query->query_vars['slug'][] = 'default-term-query-in-progress';
				$term_query->get_terms();
				if ( true ) {
					$slugs = $term_query_copy->query_vars['slug'];
					foreach ( $slugs as $slug ) {
						$doc_slug = str_replace( 'comment_', '', $slug );
						if ( $doc_slug != $slug ) {
							$term_query_copy->query_vars['slug'][] = $doc_slug;
						}
					}
					$term_query_copy->query_vars['taxonomy'][0] = 'bp_docs_access';
					$term_query_copy->get_terms();
					unset( $term_query_copy->query_vars['slug']['default-term-query-in-progress'] );
					return $term_query_copy->terms;
				}
			}
		}
	}
	return null;
}
add_action( 'terms_pre_query', 'hcommons_restricted_comment_terms_doc_fallback', 10, 2 );
