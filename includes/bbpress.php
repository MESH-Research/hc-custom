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

function filter_bp_xprofile_add_xprofile_query_to_user_query( BP_User_Query $q ) {

        $members_search =  !empty($_REQUEST['members_search']) ? sanitize_text_field($_REQUEST['members_search']) : sanitize_text_field($_REQUEST['search_terms']);

        if(isset($members_search) && !empty($members_search)) {

           $args = array('xprofile_query' => array('relation' => 'AND',
                    array(
                       'field' => 'Name',
                       'value' => $members_search,
                       'compare' => 'LIKE',
                     )));

            $xprofile_query = new BP_XProfile_Query( $args );
            $sql            = $xprofile_query->get_sql( 'u', $q->uid_name );

            if ( ! empty( $sql['join'] ) ) {
                $q->uid_clauses['select'] .= $sql['join'];
                $q->uid_clauses['where'] .= $sql['where'];
            }
        }
}

add_action( 'bp_pre_user_query', 'filter_bp_xprofile_add_xprofile_query_to_user_query' );

/**
   * Disables the forum subscription link
   *
   * A custom wrapper for bbp_get_user_subscribe_link()
   *
   * @uses bbp_parse_args()
   * @uses bbp_get_user_subscribe_link()
   */
function hcommons_get_forum_subscribe_link( $args = array() ) {

    //No link
    $retval = false;

    return $retval;
}
add_filter( 'bbp_get_forum_subscribe_link', 'hcommons_get_forum_subscribe_link' );

