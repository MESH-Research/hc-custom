<?php

/**
 * If we're serving wp-login.php without shibboleth, redirect to the shibboleth login URL with JS.
 *
 * @return void
 */
function hc_add_login_redirect_script() {
	wp_parse_str( $_SERVER['QUERY_STRING'], $parsed_querystring );

	$redirect_url = shibboleth_get_option( 'shibboleth_login_url' );

	if ( isset( $parsed_querystring['redirect_to'] ) ) {
		$redirect_url = add_query_arg(
			'redirect_to',
			$parsed_querystring['redirect_to'],
			$redirect_url
		);
	}

	// Only add redirect script if password-protected is not active, otherwise this causes a loop.
	if ( ! class_exists( 'Password_Protected' ) ) {
		echo "<script>window.location = '$redirect_url'</script>";
	}
}
add_action( 'login_enqueue_scripts', 'hc_add_login_redirect_script' );
