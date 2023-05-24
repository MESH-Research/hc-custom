<?php
/**
 * Modifications to appearance of BuddyPress Nouveau templates
 */


/**
 * Enqueue CSS
 */
function hc_enqueue_bp_nouveau_css() {
	wp_enqueue_style(
		'hc-bp-nouveau-css',
		trailingslashit( plugins_url() ) . 'hc-custom/includes/css/buddypress.css',
		[],
		filemtime( plugin_dir_path( __FILE__ ) . 'css/buddypress.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'hc_enqueue_bp_nouveau_css', 10, 0 );