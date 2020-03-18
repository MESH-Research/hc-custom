<?php
/**
 * Customizations to bp-groupblog
 *
 * @package Hc_Custom
 */

/**
 * Remove users from group blog upon leaving group.
 *
 * @param int $group_id Group.
 * @param int $user_id User.
 */
function hcommons_remove_user_from_group_site( $group_id, $user_id ) {
	$blog_id = get_groupblog_blog_id( $group_id );
	remove_user_from_blog( $user_id, $blog_id );
}
add_action( 'groups_leave_group', 'hcommons_remove_user_from_group_site', 10, 2 );



function hcommons_signup_hidden_fields( $signup_nonce_fields ) {
?>
<label class="checkbox" for="is-classsite">
  <input type="checkbox" id="is_classsite" name="is_classsite" value="1" <?php if( isset( $_POST['is_classsite'] ) || '1' == $_POST['is_classsite'] ) { ?>checked="checked"<?php } ?> />
      <strong><?php _e( 'Is this a course site?' , 'buddypress'); ?></strong><br><br>
</label>
<?php
} 
         
add_action( 'signup_hidden_fields', 'hcommons_signup_hidden_fields', 10, 1 ); 


/**
 * Hook into and modify site meta fields on creation.
 *
 * @param array $blog_meta_defaults blog meta fields.
 */

function hcommons_signup_create_blog_meta( $blog_meta_defaults ) { 

    if ( '1' == $_POST['is_classsite'] ) {
	
	$blog_meta_defaults['template'] = 'wp-bootstrap-starter';
	$blog_meta_defaults['stylesheet'] = 'wp-bootstrap-starter';
    }
  
    return $blog_meta_defaults; 
}
add_filter( 'signup_create_blog_meta', 'hcommons_signup_create_blog_meta', 10, 1 ); 


add_action( 'wp_insert_site', 'hcommons_wp_insert_site' ,0);

/**
 * Set wp_blog, siteurl, and homeurl of the new site domain if it is a class site.
 *
 * @param object $new_site WP_Site object.
 */

function hcommons_wp_insert_site( $new_site ){
    global $wpdb;
      
     if ( '1' == $_POST['is_classsite'] ) {
	$user = wp_get_current_user();

        $domain_parts = explode('.', $new_site->domain);

        $partial_domain = array_slice($domain_parts, 1);

        $append_domain = array($user->user_login.'-'.$domain_parts[0]);

        $corrected_domain = array_merge($append_domain, $partial_domain);

        $completed_domain = implode('.', $corrected_domain);
    
        $new_site->domain = $completed_domain;
	$rows_affected = $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->blogs}  SET domain = %s WHERE blog_id = %d", $completed_domain, $new_site->blog_id
        ) // $wpdb->prepare
        ); // $wpdb->query
     }
}
