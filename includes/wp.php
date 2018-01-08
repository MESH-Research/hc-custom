<?php

/**
 * If we're serving wp-login.php without shibboleth, redirect to the shibboleth login URL with JS.
 *
 * @return void
 */
function hc_add_login_redirect_script() {
	wp_parse_str( $_SERVER['QUERY_STRING'], $parsed_querystring );

	$redirect_url = add_query_arg(
		'redirect_to',
		$parsed_querystring['redirect_to'],
		shibboleth_get_option( 'shibboleth_login_url' )
	);

	echo "<script>window.location = '$redirect_url'</script>";
}
add_action( 'login_enqueue_scripts', 'hc_add_login_redirect_script' );
