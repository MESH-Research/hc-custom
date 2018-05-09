<?php
/**
 * Customizations to wp-to-twitter.
 *
 * @package Hc_Custom
 */

/**
 * Remove the twitter.com platform js.
 *
 * We don't need it and we don't want unnecessary external clientside requests.
 */
function hc_custom_dequeue_twitter_platform() {
	wp_dequeue_script( 'twitter-platform', 'https://platform.twitter.com/widgets.js' );
}
add_action( 'dynamic_sidebar_after', 'hc_custom_dequeue_twitter_platform' );
