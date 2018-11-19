<?php
/**
 * Custom Changes to BuddyPress Live Preview plugin.
 *
 * @package Hc_Custom
 */


function hc_custom_live_preview_timeout( $timeout ) {

   $timeout = 300;

   return $timeout;
}

add_filter( 'bbp_live_preview_timeout', 'hc_custom_live_preview_timeout' );
