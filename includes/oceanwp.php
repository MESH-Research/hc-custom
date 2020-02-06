<?php

/**
 * Enqueue jQuery AreYouSure, monitors html forms and alerts users to unsaved changes.
*/ 
function hc_custom_jquery_noconflict() {
        wp_enqueue_script( 'jquery-no-conflict', trailingslashit( plugins_url() ) . 'hc-custom/includes/js/jquery.noconflict.js', array( 'jquery' ) );
}

//add_action( 'wp_enqueue_scripts', 'hc_custom_jquery_noconflict' );


function modify_jquery() { 
  if ( !is_admin() ) {
    wp_enqueue_script( 'jquery-ui-tabs' );
  }
}
add_action( 'wp_footer', 'modify_jquery' );


/**
 * Disable the Lightbox scripts
 */
function my_enqueue_scripts() {

	// Unregister JS files
	wp_deregister_script( 'magnific-popup' );
	wp_deregister_script( 'oceanwp-lightbox' );
	//wp_deregister_script( 'oceanwp-main' );
	// Unregister CSS file
	wp_deregister_style( 'magnific-popup' );

}
add_action( 'wp_enqueue_scripts', 'my_enqueue_scripts', 99 );

/**
 * Add the no-lightbox class in the body tag
 */
function my_body_classes( $classes ) {

	$classes[] = 'no-lightbox';

	// Return classes
	return $classes;
}
add_filter( 'body_class', 'my_body_classes' );

