<?php
/**
 * Customizations to BuddyPress Functions.
 *
 * @package Hc_Custom
 */

/**
 * Filters bp_legacy_object_template_path to fix group member directory bug.
 *
 * @param string $template_path Template Directory.
 */
function hc_custom_bp_legacy_object_template_path( $template_path ) {

	if ( ! empty( $_POST['template'] ) && 'groups/single/members' === $_POST['template'] ) {
		$template_part = 'groups/single/members.php';
		$template_path = bp_locate_template( array( $template_part ), false );
	}

	return $template_path;
}

add_filter( 'bp_legacy_object_template_path', 'hc_custom_bp_legacy_object_template_path' );


/**
 * Output the wrapper markup for the blog signup form.
 *
 * @param string          $blogname   Optional. The default blog name (path or domain).
 * @param string          $blog_title Optional. The default blog title.
 * @param string|WP_Error $errors     Optional. The WP_Error object returned by a previous
 *                                    submission attempt.
 */
function hc_custom_bp_show_blog_signup_form($blogname = '', $blog_title = '', $errors = '') {
 global $current_user;

    if ( isset($_POST['submit']) ) {
        // Updated for BP 9.0.0 compatibility, following /srv/www/commons/current/web/app/plugins/buddypress/bp-blogs/bp-blogs-template.php
        $blog_id = bp_blogs_validate_blog_signup();
        if ( is_numeric( $blog_id ) ) {
            $site = get_site( $blog_id );

            if ( isset( $site->id ) && $site->id ) {
                $current_user = wp_get_current_user();

                bp_blogs_confirm_blog_signup(
                    $site->domain,
                    $site->path,
                    $site->blogname,
                    $current_user->user_login,
                    $current_user->user_email,
                    '',
                    $site->id
                );
            }
        }
    } 
    
    if ( ! isset( $_POST['submit'] ) || ! isset( $blog_id ) || false === $blog_id || is_wp_error( $blog_id ) ) {
        if ( isset( $blog_id ) && is_wp_error( $blog_id ) ) {
			$errors = $blog_id;
		} elseif ( ! is_wp_error($errors) ) {
            $errors = new WP_Error();
        }

        /**
         * Filters the default values for Blog name, title, and any current errors.
         *
         * @since BuddyPress 1.0.0
         *
         * @param array $value {
         *      string   $blogname   Default blog name provided.
         *      string   $blog_title Default blog title provided.
         *      WP_Error $errors     WP_Error object.
         * }
         */
        $filtered_results = apply_filters('signup_another_blog_init', array('blogname' => $blogname, 'blog_title' => $blog_title, 'errors' => $errors ));
        $blogname = $filtered_results['blogname'];
        $blog_title = $filtered_results['blog_title'];
        $errors = $filtered_results['errors'];
 
        if ( $errors->get_error_code() ) {
            echo "<p>" . __('There was a problem; please correct the form below and try again.', 'buddyboss') . "</p>";
        }
        ?>
        <p><?php printf(__("By filling out the form below, you can <strong>add a site to your account</strong>. There is no limit to the number of sites that you can have, so create to your heart's content, but blog responsibly!", 'buddyboss'), $current_user->display_name) ?></p>
 
        <p><?php _e("If you're not going to use a great domain, leave it for a new user.<br><br>Also bear in mind that many domains may create ambiguity; rather than 'hist101' you might include institution and semester information to avoid conflicts, such as 'msuhist101s20'.<br> Please note that if you check off 'Is this a course site,' below, the Learning Space theme will be activated and the site url will be prefixed with your username (e.g. hcadmin-learningspace.hcommons.org).", 'buddyboss') ?></p>
 
        <form class="standard-form" id="setupform" method="post" action="">
 
            <input type="hidden" name="stage" value="gimmeanotherblog" />
            <?php
 
            /**
             * Fires after the default hidden fields in blog signup form markup.
             *
             * @since BuddyPress 1.0.0
             */
            //do_action( 'signup_hidden_fields' ); ?>
 
            <?php hc_custom_bp_blogs_signup_blog($blogname, $blog_title, $errors); ?>

            <p>
                <input id="submit" type="submit" name="submit" class="submit" value="<?php esc_attr_e('Create Site', 'buddyboss') ?>" />
            </p>
 
            <?php wp_nonce_field( 'bp_blog_signup_form' ) ?>
        </form>
        <?php
    }
}

function hc_custom_bp_blogs_signup_blog( $blogname = '', $blog_title = '', $errors = '' ) {
    global $current_site;
 
    // Blog name.
    if( !is_subdomain_install() )
        echo '<label for="blogname">' . __('Site Name:', 'buddyboss') . '</label>';
    else
        echo '<label for="blogname">' . __('Site Domain:', 'buddyboss') . '</label>';
 
    if ( $errmsg = $errors->get_error_message('blogname') ) { ?>
 
        <p class="error"><?php echo $errmsg ?></p>
 
    <?php }
 
    if ( !is_subdomain_install() )
        echo '<span class="prefix_address">' . $current_site->domain . $current_site->path . '</span> <input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="63" /><br />';
    else
        echo '<input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="63" ' . bp_get_form_field_attributes( 'blogname' ) . '/> <span class="suffix_address">.' . bp_signup_get_subdomain_base() . '</span><br />';
 
    if ( !is_user_logged_in() ) {
        print '(<strong>' . __( 'Your address will be ' , 'buddyboss');
 
        if ( !is_subdomain_install() ) {
            print $current_site->domain . $current_site->path . __( 'blogname' , 'buddyboss');
        } else {
            print __( 'domain.' , 'buddyboss') . $current_site->domain . $current_site->path;
        }
 
        echo '.</strong> ' . __( 'Must be at least 4 characters, letters and numbers only. It cannot be changed so choose carefully!)' , 'buddyboss') . '</p>';
    }
 
    // Blog Title.
    ?>
 
    <label for="blog_title"><?php _e('Site Title:', 'buddyboss') ?></label>
 
    <?php if ( $errmsg = $errors->get_error_message('blog_title') ) { ?>
 
        <p class="error"><?php echo $errmsg ?></p>
 
    <?php }
    echo '<input name="blog_title" type="text" id="blog_title" value="'.esc_html($blog_title, 1).'" /></p>';
    ?>
    
    <?php do_action( 'signup_hidden_fields' ); ?>

    <fieldset class="create-site">
        <legend class="label"><?php _e('Privacy: I would like my site to appear in search engines, and in public listings around this network', 'buddyboss') ?></legend>
 
        <label class="checkbox" for="blog_public_on">
            <input type="radio" id="blog_public_on" name="blog_public" value="1" <?php if( !isset( $_POST['blog_public'] ) || '1' == $_POST['blog_public'] ) { ?>checked="checked"<?php } ?> />
            <strong><?php _e( 'Yes' , 'buddyboss'); ?></strong>
        </label>
        <label class="checkbox" for="blog_public_off">
            <input type="radio" id="blog_public_off" name="blog_public" value="0" <?php if( isset( $_POST['blog_public'] ) && '0' == $_POST['blog_public'] ) { ?>checked="checked"<?php } ?> />
            <strong><?php _e( 'No' , 'buddyboss'); ?></strong>
        </label>
    </fieldset>
 
    <?php
 
    /**
     * Fires at the end of all of the default input fields for blog creation form.
     *
     * @since BuddyPress 1.0.0
     *
     * @param WP_Error $errors WP_Error object if any present.
     */
    do_action('signup_blogform', $errors);
}
