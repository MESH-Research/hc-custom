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

/**
 * BuddyPress actions & filters.
 */
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-core.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-blogs.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-groups.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-members.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-mentions.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-activity.php';
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
require_once trailingslashit( __DIR__ ) . 'includes/oceanwp.php';
require_once trailingslashit( __DIR__ ) . 'includes/siteorigin-panels.php';
require_once trailingslashit( __DIR__ ) . 'includes/wp-to-twitter.php';


/**
 * Miscellaneous actions & filters.
 */
require_once trailingslashit( __DIR__ ) . 'includes/mla-groups.php';

/**
 * HC Page Templates
 */
require_once trailingslashit( __DIR__ ) . 'class-hc-pagetemplater.php';

/**
 * Return the location of the plugin templates.
 */
function hc_get_template_location() {
        return dirname( __FILE__ ) . '/templates/';
}

/**
 * Register HC above BP in the stack.
 */
function hc_register_bp_template_stack() {
	bp_register_template_stack( 'hc_get_template_location', 8 );
}
add_filter( 'bp_init', 'hc_register_bp_template_stack' );

/**
 * Find templates in plugin when using bp_core_load_template.
 */
function hc_load_template_filter( $found_template, $templates ) {

	$filtered_template = '';
	foreach ( (array) $templates as $template ) {
		if ( file_exists( hc_get_template_location() . 'buddypress/' . $template ) ) {
			$filtered_template = hc_get_template_location() . 'buddypress/' . $template;
			break;
		} elseif ( file_exists( hc_get_template_location() . 'bbpress/' . $template ) ) {
			$filtered_template = hc_get_template_location() . 'bbpress/' . $template;
			break;
		} elseif ( file_exists( hc_get_template_location() . $template ) ) {
			$filtered_template = hc_get_template_location() . $template;
			break;
		} elseif ( file_exists( get_stylesheet_directory() . '/' . $template ) ) {
			$filtered_template = get_stylesheet_directory() . '/' . $template;
			break;
		} elseif ( file_exists( get_template_directory() . '/' . $template ) ) {
			$filtered_template = get_template_directory() . '/' . $template;
			break;
		}
	}

	return apply_filters( 'hc_load_template_filter', $filtered_template );
}
add_filter( 'bp_located_template', 'hc_load_template_filter', 10, 2 );
