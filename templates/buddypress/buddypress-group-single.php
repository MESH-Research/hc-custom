<?php
global $class, $rtmedia_query;
?>
    <!-- if widgets are loaded for any BuddyPress component, display the BuddyPress sidebar -->

    <div class="page-right-sidebar <?php echo $class; ?>">
    
    <!-- This is more dummy so plugins like buddypress-learndash can work, except rt media -->
    <?php if ( $rtmedia_query && $rtmedia_query->query["context"] != 'group' ): ?>
    <?php the_content(); ?>
    <?php endif; ?>

	<!-- BuddyPress template content -->

            <div id="buddypress">                    

                <?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>

                <?php do_action( 'bp_before_group_home_content' ); ?>

                <div id="item-header" role="complementary">

                    <?php
                    /**
                     * If the cover image feature is enabled, use a specific header
                     */
//                    if ( bp_group_use_cover_image_header() ) :
//                        bp_get_template_part( 'groups/single/cover-image-header' );
//                    else :
                        bp_get_template_part( 'groups/single/group-header' );
//                    endif;
                    ?>

                    <?php
			    ob_start();
			    do_action( 'bp_group_header_meta' );
			    $action_output = ob_get_contents();
			    ob_end_clean();
			    
			    if(!empty($action_output)) {
				echo '<div class="group-header-meta">';
				    echo $action_output;
				echo '</div>';
			    }
			    
			    ?>
			    

                </div><!-- #item-header -->


                <div id="primary" class="site-content"> <!-- moved from top -->

                   <div id="content" role="main"> <!-- moved from top -->
	                   
                        <div class="below-cover-photo">
                        
                            <div id="group-description">
                                <?php bp_group_description(); ?>
                            </div>

                        </div>
                        
                       <div id="item-nav"> <!-- movwed inside #primary-->
                            <div class="item-list-tabs no-ajax" id="object-nav" role="navigation">
                                    <ul>

                                        <?php bp_get_options_nav(); ?>

                                        <?php do_action( 'bp_group_options_nav' ); ?>

                                    </ul>
                            </div>
                        </div><!-- #item-nav -->
                        

                        <div id="item-body">

                                <?php do_action( 'bp_before_group_body' );

                                /**
                                 * Does this next bit look familiar? If not, go check out WordPress's
                                 * /wp-includes/template-loader.php file.
                                 *
                                 * @todo A real template hierarchy? Gasp!
                                 */

                                // Looking at home location
                                if ( bp_is_group_home() ) :

                                    if ( bp_group_is_visible() ) {

                                        // Load appropriate front template
                                        bp_groups_front_template_part();

                                    } else {

                                        /**
                                         * Fires before the display of the group status message.
                                         *
                                         * @since 1.1.0
                                         */
                                        do_action( 'bp_before_group_status_message' ); ?>

                                        <div id="message" class="info">
                                            <p><?php bp_group_status_message(); ?></p>
                                        </div>

                                        <?php

                                        /**
                                         * Fires after the display of the group status message.
                                         *
                                         * @since 1.1.0
                                         */
                                        do_action( 'bp_after_group_status_message' );

                                    }

                                // Not looking at home
                                else :

                                    // Group Admin
                                    if     ( bp_is_group_admin_page() ) : bp_get_template_part( 'groups/single/admin'        );

                                    // Group Activity
                                    elseif ( bp_is_group_activity()   ) : bp_get_template_part( 'groups/single/activity'     );

                                    // Group Members
                                    elseif ( bp_is_group_members()    ) : bp_groups_members_template_part();

                                    // Group Invitations
                                    elseif ( bp_is_group_invites()    ) : bp_get_template_part( 'groups/single/send-invites' );

                                    // Old group forums
                                    elseif ( bp_is_group_forum()      ) : bp_get_template_part( 'groups/single/forum'        );

                                    // Membership request
                                    elseif ( bp_is_group_membership_request() ) : bp_get_template_part( 'groups/single/request-membership' );

                                    elseif ( $rtmedia_query && $rtmedia_query->query["context"] == 'group' ) : bp_get_template_part( 'groups/single/media');
                                    // Anything else (plugins mostly)
                                    else                                : bp_get_template_part( 'groups/single/plugins'      );

                                    endif;

                                endif;

                                do_action( 'bp_after_group_body' ); ?>

                        </div><!-- #item-body -->

                    </div><!-- #content -->
<?php
$search = ( isset( $_GET['bbp_search'] ) ) ? $_GET['bbp_search'] : false;

if(!empty($search)) :

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1; 

?>
 <div class="bbp-on-search-form">
  
            <?php bbp_get_template_part( 'form', 'search' ); ?>
  
  </div>

  <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
  <header class="forum-header page-header">
      <h1 class="entry-title main-title search-title-results">Search Results for '<?php echo $search ?>' </h1>
  </header><!-- .page-header -->

     <div class="entry-content">
         <div id="bbpress-forums">
<?php
if(bbp_has_search_results(array('s' => $search, 'paged' => $paged))) :
        bbp_set_query_name( bbp_get_search_rewrite_id() ); 

         do_action( 'bbp_template_before_search' ); 


                  bbp_get_template_part( 'pagination', 'search' ); 

                  bbp_get_template_part( 'loop',       'search' ); 

                 bbp_get_template_part( 'pagination', 'search' ); 

        do_action( 'bbp_template_after_search_results' );
else :
?>
	    <div class="bbp-template-notice">
	        <p>No search results were found here.</p>
            </div>

<?php endif; ?>
         </div>
     </div>
</article> <!-- #article -->

<?php endif; ?>
 
</div><!-- #primary -->

            <?php
		    
	  	    global $groups_template;
		    //backup the group current loop to ignore loop conflict from widgets
		    $groups_template_safe = $groups_template;
		    get_sidebar('buddypress');
		    //restore the oringal $groups_template before sidebar.
		    $groups_template = $groups_template_safe;
		    
		    ?>
                    
            
            <?php do_action( 'bp_after_group_home_content' ); ?>

            <?php endwhile; endif; ?>
                    
             
            </div><!-- #buddypress -->
        			

    </div><!-- closing div -->
