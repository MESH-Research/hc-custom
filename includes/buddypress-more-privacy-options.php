<?php
/**
 * Plugin Name: BuddyPress / More Privacy Options patch
 * Description: Hook into some functions provided by BuddyPress to accommodate features added by More Privacy Options
 *
 * This file contains code copied from BP that doesn't pass phpcs, ignore it.
 * @codingStandardsIgnoreFile
 *
 * @package Hc_Custom
 */

/**
 * ripped from BP_Blogs_Blog::get(), so we can add a filter to handle MPO options:
 * if it becomes possible to manipulate the sql that function uses with a parameter or global, we should do that instead
 *
 * @param array $return_value what BP_Blogs_Blog::get() returned. will be entirely replaced by this filter
 * @param array $args the args originally passed to BP_Blogs_Blog::get(), so we can reconstruct the query
 */
function more_privacy_options_blogs_get( $return_value, $args ) {
	global $wpdb;
	/**
	 * one of these things is not like the others...
	 * all variables passed to BP_Blogs_Blog::get() match their names given in $args except "limit" - that's "per_page"
	 */

	extract( $args );
	$limit = $per_page;

	$bp_q = buddypress();

	//	Visible(1)
	//  No Search(0)
	//  Network Users Only(-1)
	//  Site Members Only(-2)
	//  Site Admins Only(-3)
	//  Super Admins - everything...
	$visibility            = "public";
	$visibility_configured = false;

	$user_sql = ! empty( $user_id ) ? $wpdb->prepare( ' AND b.user_id = %d', $user_id ) : '';

	if ( is_user_logged_in() ) {
		$logged_in_user_id    = get_current_user_id();
		$visibility = "Visible";
		if ( is_super_admin() && ! $visibility_configured ) {
			$visibility = "Super Admins";
		}
		$member_types             = bp_get_member_type( $logged_in_user_id, false );
		$search_member_type_array = array_combine( array_map( 'strtolower', $member_types ), $member_types );
		$network_id               = Humanities_Commons::$society_id;
		if ( ! empty( $search_member_type_array[ $network_id ] ) && ! $visibility_configured ) {
			$visibility = "Network Users";
		}

		$blogs_user_belongs_to = get_blogs_of_user( $logged_in_user_id );
		$userblogs             = false;

		if ( ! empty( $blogs_user_belongs_to ) && ! $visibility_configured ) {
			$visibility = "Site Members";
			$userblogs  = array();
			$adminblogs = array();
			foreach ( $blogs_user_belongs_to as $a ) {
				$userblogs[] = $a->userblog_id;
				if ( current_user_can_for_blog( $a->userblog_id, 'activate_plugins' ) ) {
					$adminblogs[] = $a->userblog_id;
				}
			}
		}
	}


	switch ( $visibility ) {

		case "No Search":
			$hidden_sql = 'AND wb.public in ( 0, 1)';
			break;
		case "Network Users":
			$hidden_sql = 'AND wb.public in ( 1, -1 )';
			break;
		case "Site Members":
			$perms = "1, -2";
			if ( ! empty( $adminblogs ) ) {
				//Site Admins
				$visibility = "Site Admins";
				$userblogs  = array_merge( $userblogs, $adminblogs );
				$perms      .= ", -3";
			}
			$hidden_sql = 'AND (wb.public in ( ' . $perms . ' ) OR b.blog_id in (' . implode( ",", $userblogs ) . ')) ';
			break;
		case "Super Admins":
			$hidden_sql = 'AND wb.public in ( 0, 1, -1, -2, -3 )';
			break;
		case "Visible":
		default:
			$hidden_sql = 'AND wb.public in ( 1 )';
			break;
	}

	$pag_sql = ( $limit && $page ) ? $wpdb->prepare( ' LIMIT %d, %d', intval( ( $page - 1 ) * $limit ), intval( $limit ) ) : '';

	switch ( $type ) {
		case 'active':
		default:
			$order_sql = 'ORDER BY bm.meta_value DESC';
			break;
		case 'alphabetical':
			$order_sql = 'ORDER BY bm_name.meta_value ASC';
			break;
		case 'newest':
			$order_sql = 'ORDER BY wb.registered DESC';
			break;
		case 'random':
			$order_sql = 'ORDER BY RAND()';
			break;
	}

	$include_sql = '';
    $include_blog_ids = array_filter( wp_parse_id_list( $include_blog_ids ) );
    if ( ! empty( $include_blog_ids ) ) {
 		$blog_ids_sql = implode( ',', $include_blog_ids );
 		$include_sql  = " AND b.blog_id IN ({$blog_ids_sql})";
 	}
	$search_terms_sql = '';
	if ( ! empty( $search_terms ) ) {
		$search_terms_like = '%' . bp_esc_like( $search_terms ) . '%';
		$search_terms_sql  = $wpdb->prepare( 'AND (bm_name.meta_value LIKE %s OR bm_description.meta_value LIKE %s)', $search_terms_like, $search_terms_like );
	}
	$site_sql = "AND wb.site_id = " . get_current_network_id();
	$sql      = "FROM
		  " . $bp_q->blogs->table_name . " b
		  LEFT JOIN " . $bp_q->blogs->table_name_blogmeta . " bm ON (b.blog_id = bm.blog_id)
		  LEFT JOIN " . $bp_q->blogs->table_name_blogmeta . " bm_name ON (b.blog_id = bm_name.blog_id)
		  LEFT JOIN " . $bp_q->blogs->table_name_blogmeta . " bm_description ON (b.blog_id = bm_description.blog_id)
		  LEFT JOIN " . $wpdb->base_prefix . "blogs wb ON (b.blog_id = wb.blog_id $hidden_sql)
		  LEFT JOIN " . $wpdb->users . " u ON (b.user_id = u.ID)
		WHERE
		  wb.archived = '0' AND wb.spam = 0 AND wb.mature = 0 AND wb.deleted = 0  $site_sql
		  AND bm.meta_key = 'last_activity' AND bm_name.meta_key = 'name' AND bm_description.meta_key = 'description'
		  $search_terms_sql $user_sql $include_sql";

	$search_sql = "SELECT b.blog_id,
		        b.user_id as admin_user_id,
		        u.user_email as admin_user_email,
		        wb.domain,
		        wb.path,
		        bm.meta_value as last_activity,
		        bm_name.meta_value as name " . $sql . " GROUP BY b.blog_id $order_sql $pag_sql";

	$count_sql = "SELECT COUNT(DISTINCT b.blog_id)" . $sql;

	$paged_blogs = $wpdb->get_results( $search_sql );
	$total_blogs = $wpdb->get_var( $count_sql );

	$blog_ids = array();
	foreach ( (array) $paged_blogs as $blog ) {
		$blog_ids[] = (int) $blog->blog_id;
	}

	$paged_blogs = BP_Blogs_Blog::get_blog_extras( $paged_blogs, $blog_ids, $type );

	if ( $update_meta_cache ) {
		bp_blogs_update_meta_cache( $blog_ids );
	}

	return array(
		'blogs' => $paged_blogs,
		'total' => $total_blogs,
	);

}

add_filter( 'bp_blogs_get_blogs', 'more_privacy_options_blogs_get', null, 3 );

/**
 * @param $options
 */
function mla_add_privacy_options($options) { ?>
<br />
<h3>Visibility Settings</h3>
<label class="checkbox" for="blog_public_on">
	<input type="radio" id="blog_public_on" name="blog_public" value="1" checked="checked" class="styled">
	<strong>Public and allow search engines to index this site. <i style="font-weight: normal">Note: it is up to search
			engines to honor your request. The site will appear in public listings around HUMANITIES COMMONS.</i></strong>
</label>
<label class="checkbox" for="blog_public_off"><br>
	<input type="radio" id="blog_public_off" name="blog_public" value="0" class="styled">
	<strong>Public but discourage search engines from index this site. <i style="font-weight: normal">Note: this option
			does not block access to your site — it is up to search engines to honor your request. The site will appear in
			public listings around HUMANITIES COMMONS</i></strong>
</label><br />

<?php 
}

/**
 * load the actions of mpo visibility
 */
add_action( 'wp_head', 'mla_add_privacy_options_action' );
function mla_add_privacy_options_action(){
	add_action('wpmueditblogaction','mla_add_privacy_options', 1);
	//add_action('blog_privacy_selector','mla_add_privacy_options', 1);
	add_action('signup_blogform','mla_add_privacy_options', 1);
}

/**
 *
 */
add_action( 'admin_head', 'mla_add_admin_js');
function mla_add_admin_js () {
	global $pagenow;
	$page = 'options-reading.php';
	if($pagenow === $page) {
	    wp_enqueue_script('hc_custom_mpo_admin_script', plugins_url('js/admin-mpo.js', __FILE__), array('jquery'));
    }
}
