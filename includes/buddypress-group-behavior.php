<?php
/**
 * Customizations to buddypress followers
 *
 * @package Hc_Custom
 */

/**
 * Filter the register url to be society specific
 *
 * @since HCommons
 *
 * @param string $register_url
 * @return string $register_url Modified url.
 */

function hc_custom_bp_get_signup_page( $register_url ) {
	$register_url  = '/begin/';

	return $register_url;
};

add_filter( 'bp_get_signup_page', 'hc_custom_bp_get_signup_page', 10, 1 );

/**
 * Add shortcode to get the enrollment url
 *
 * @since HCommons
 *
 * @return string env url.
 */

function hc_custom_get_env_url( $atts ) {
	$a = shortcode_atts( array(
		'id'    => '',
		'class' => '',
		'text'  => '',
	), $atts );

	if ( empty( $a['text'] ) ) {
		return;
	}

	$id = ( ! empty( $a['id'] ) ? 'id="'. $a['id'] .'"' : '');

	$class = ( ! empty( $a['class'] ) ? 'class="'. $a['class'] .'"' : '');

	if ( class_exists( 'Humanities_Commons' ) && ! empty( Humanities_Commons::$society_id ) && defined( strtoupper( Humanities_Commons::$society_id ) . '_ENROLLMENT_URL' ) ) {
		$env_url = '<a href="' . constant( strtoupper( Humanities_Commons::$society_id ) . '_ENROLLMENT_URL' ) . '/done:core"'. $id .' '. $class .'>' . $a['text'] . '</a>';

		return $env_url;
	}
}

add_shortcode( 'hc_custom_env_url', 'hc_custom_get_env_url' );
