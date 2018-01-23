<?php
/**
 * Customizations to elasticpress-buddypress.
 *
 * @package Hc_Custom
 */

/**
 * Remove the 'register_widgets' action from siteorigin when on docs.
 * Otherwise, it calls url_to_postid() which creates a WP_Query on docs before the component initializes.
 */
function hc_remove_siteorigin_register_widgets_on_docs() {
	if ( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], '/docs/' ) ) {
		remove_action( 'widgets_init', array( SiteOrigin_Panels_Sidebars_Emulator::single(), 'register_widgets' ), 99 );
	}
}
add_action( 'widgets_init', 'hc_remove_siteorigin_register_widgets_on_docs' );
