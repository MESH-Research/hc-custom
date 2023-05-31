<?php
/**
 * Plugin Name:     HC Custom
 * Plugin URI:      https://github.com/mlaa/hc-custom
 * Description:     Miscellaneous actions & filters for Humanities Commons.
 * Author:          MLA
 * Author URI:      https://github.com/mlaa
 * Text Domain:     hc-custom
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Hc_Custom
 */
function load_hc_custom() {
/**
 * BuddyPress actions & filters.
 */
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-core.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-blogs.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-groups.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-members.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-activity.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-xprofile.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/buddypress-functions.php';

/**
 * Plugin actions & filters.
 */
require_once trailingslashit( __DIR__ ) . 'includes/avatar-privacy.php';
require_once trailingslashit( __DIR__ ) . 'includes/bbpress.php';
require_once trailingslashit( __DIR__ ) . 'includes/bbp-live-preview.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress-docs.php';
require_once trailingslashit( __DIR__ ) . 'includes/bp-groupblog.php';
require_once trailingslashit( __DIR__ ) . 'includes/bp-group-documents.php';
require_once trailingslashit( __DIR__ ) . 'includes/bp-event-organiser.php';
require_once trailingslashit( __DIR__ ) . 'includes/bp-attachment-xprofile.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress-followers.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress-group-email-subscription.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress-more-privacy-options.php';
require_once trailingslashit( __DIR__ ) . 'includes/cbox-auth.php';
require_once trailingslashit( __DIR__ ) . 'includes/elasticpress-buddypress.php';
require_once trailingslashit( __DIR__ ) . 'includes/humcore.php';
require_once trailingslashit( __DIR__ ) . 'includes/mashsharer.php';
require_once trailingslashit( __DIR__ ) . 'includes/siteorigin-panels.php';
require_once trailingslashit( __DIR__ ) . 'includes/wp-to-twitter.php';


/**
 * Miscellaneous actions & filters.
 */
require_once trailingslashit( __DIR__ ) . 'includes/mla-groups.php';
require_once trailingslashit( __DIR__ ) . 'includes/user-functions.php';

/**
 * Theme decoupling
 */
require_once trailingslashit( __DIR__ ) . 'includes/header.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress-nouveau.php';
}
add_action('bp_init','load_hc_custom',10,1);

/**
 *  BuddyPress Action & Filter Functions
 *  @author Dave Ventresca
 *  date last edited: 04/17/23
 */

 function hc_profile_header_meta() { //Grab xProfile Field Data and create a list of profile 'meta' to display on the user's profile
    $name = bp_get_profile_field_data(array('field' => 1));
    $user_meta = '';
    $user_job = bp_get_profile_field_data(array('field' => 15)); //title
    if ($user_job != '') {
        $user_meta .= '<br>';
        $user_meta .= 'Position: ' . $user_job;
    }
    $user_affiliation = bp_get_profile_field_data(array('field' => 14)); //affiliation
    if ($user_affiliation != '') {
        $user_meta .= '<br>';
        $user_meta .= 'Affiliation: <a href="' . get_site_url() . '?s=' . $user_affiliation . '&post_type[0]=user">' . $user_affiliation . '</a>';
    }
    $twitter = bp_get_profile_field_data(array('field' => 17)); //twitter
    $linkedin = bp_get_profile_field_data(array('field' => 1000026)); //linkedin
    $facebook = bp_get_profile_field_data(array('field' => 1000025)); //facebook
    $orcid = bp_get_profile_field_data(array('field' => 18)); //orcid
    $weburl = bp_get_profile_field_data(array('field' => 1000027)); //website
    if ($twitter != '' || $linkedin != '' || $facebook != '' || $orcid != '' || $weburl != '') {
      $user_meta .= '<div class="social-flex">';
      if ($twitter != '') {
        $user_meta .= '<a href="https://twitter.com/' . $twitter . '" target="_blank"><i class="fab fa-twitter"></i></a>';
      }
      if ($linkedin != '<a href="" rel="nofollow"></a>') {
        $fontawesome = '<i class="fab fa-linkedin-in"></i>';
        $newlinked = preg_replace('/(<a.*?>).*?(<\/a>)/', '$1'.$fontawesome.'$2', $linkedin);
        $newlinked = preg_replace("/<a(.*?)>/", "<a$1 target=\"_blank\">", $newlinked);
        $user_meta .= $newlinked;
      }
      if ($facebook != '<a href="" rel="nofollow"></a>') {
        $fontawesome = '<i class="fab fa-facebook-f"></i>';
        $newfb = preg_replace('/(<a.*?>).*?(<\/a>)/', '$1'.$fontawesome.'$2', $facebook);
        $newfb = preg_replace("/<a(.*?)>/", "<a$1 target=\"_blank\">", $newfb);
        $user_meta .= $newfb; 
      }
      if ($orcid != '') {
        $user_meta .= '<a href="https://orcid.org/' . $orcid . '" target="_blank"><i class="fad fa-id-badge"></i></a>';
      }
      if ($weburl != '<a href="" rel="nofollow"></a>') {
        $fontawesome = '<i class="fad fa-link"></i>';
        $newweb = preg_replace('/(<a.*?>).*?(<\/a>)/', '$1'.$fontawesome.'$2', $weburl);
        $newweb = preg_replace("/<a(.*?)>/", "<a$1 target=\"_blank\">", $newweb);
        $user_meta .= $newweb;
      }
      $user_meta .= '</div>';
    }
    

    echo $user_meta;
}
add_action( 'bp_profile_header_meta', 'hc_profile_header_meta' );

/*
 * Function to add Groups Directory Filter buttons for current society
 * Note: this works only with BP Nouveau would need to hook into 'bp_groups_directory_group_filter'
**/

function society_filter_groups_directory($nav_items) { //BP Nouveau filter function
  $society_id = Humanities_Commons::$society_id;
  if ($society_id == 'hc') {
    $class = '';
  }
  else { 
    array_shift($nav_items);
    $class = 'selected'; 
  }
  $societygroups = strtoupper($society_id) . ' Groups';
  $nav_items['society'] = array(
    'component' => 'groups',
    'slug' => 'society',
    'li_class' => array($class), //this isn't working for some reason... but the functionality of the button tab works just fine (odd) !???
    'link' => '#',
    'text' => __($societygroups,'buddypress'),
    'count' => false,
    'position' => 10,
  );
  return $nav_items;
}
add_filter('bp_nouveau_get_groups_directory_nav_items','society_filter_groups_directory');

