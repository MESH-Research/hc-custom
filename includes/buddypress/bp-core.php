<?php
/**
 * Customizations to BuddyPress Groups.
 *
 * @package Hc_Custom
 */


/**
 * inject BP_Email into wp_mail
 */
function hcommons_filter_wp_mail( $args ) {
	extract( $args );

	// replace default footer to remove "unsubscribe" since that isn't handled for non-bp-email types
	add_action( 'bp_before_email_footer', 'ob_start', 999, 0 );
	add_action( 'bp_after_email_footer', 'ob_get_clean', -999, 0 );
	add_action( 'bp_after_email_footer', 'hcommons_email_footer' );

	// load template markup
	ob_start();
	add_filter( 'bp_locate_template_and_load', '__return_true' );
	bp_locate_template( 'assets/emails/single-bp-email.php', true, false );
	remove_filter( 'bp_locate_template_and_load', '__return_true' );
	$template = ob_get_contents();
	ob_end_clean();

	$args['message'] = bp_core_replace_tokens_in_text( $template, [
		'content' => make_clickable( nl2br( $message ) ),
		'recipient.name' => 'there', // since we don't know the user's actual name
	] );

	// wp core sets headers to a string value joined by newlines for e.g. comment notifications.
	// most plugins use/keep the array set by apply_filter( 'wp_mail' ).
	// cast to array
	if ( is_string( $args['headers'] ) ) {
		$args['headers'] = explode( "\n", $args['headers'] );
	}
	// remove existing content-type header if present
	$args['headers'] = array_filter( $args['headers'], function( $v ) {
		return strpos( strtolower( $v ), 'content-type' ) === false;
	} );
	// set html content-type
	$args['headers'][] = 'Content-Type: text/html';

	// clean up
	remove_action( 'bp_before_email_footer', 'ob_start', 999, 0 );
	remove_action( 'bp_after_email_footer', 'ob_get_clean', -999, 0 );
	remove_action( 'bp_after_email_footer', 'hcommons_email_footer' );

	return $args;
}
add_filter( 'wp_mail', 'hcommons_filter_wp_mail' );

/**
 * used in hcommons_filter_wp_mail()
 */
function hcommons_email_footer() {
	$settings = bp_email_get_appearance_settings();
	echo $settings['footer_text'];
}

/**
 * sometimes we don't want to use our html filter (e.g. bbpress has its own),
 * but there's no way to tell inside wp_mail when that's the case - this is a workaround
 */
function hcommons_unfilter_wp_mail() {
	remove_filter( 'wp_mail', 'hcommons_filter_wp_mail' );
}
add_action( 'bbp_pre_notify_subscribers', 'hcommons_unfilter_wp_mail' );
add_action( 'bbp_pre_notify_forum_subscribers', 'hcommons_unfilter_wp_mail' );
// no action available for this one, so abuse a filter instead
add_filter( 'newsletters_execute_mail_message', function( $message ) {
	hcommons_unfilter_wp_mail();
	return $message;
} );
