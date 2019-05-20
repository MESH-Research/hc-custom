<?php
add_action( 'admin_menu', function () {
	if ( class_exists( "NewsletterModule" ) ) {
		remove_submenu_page( "newsletter_main_index", "newsletter_main_welcome" ); //Welcome page
		if (! is_super_admin() || true) { //temp true for testing
			$newsletter_main = new NewsletterModule('main', '5.9.5');
			$newsletter_subs = new NewsletterModule('subscription', '5.9.5');

			remove_submenu_page( "newsletter_main_index", "newsletter_main_index" ); //dashboard
			remove_submenu_page( "newsletter_main_index", "newsletter_main_main" ); //settings page
			remove_submenu_page( "newsletter_main_index", "newsletter_subscription_antibot" ); //security page
			remove_submenu_page( "newsletter_main_index", "newsletter_subscription_options" ); //signup settings
			remove_submenu_page( "newsletter_main_index", "newsletter_main_extensions" ); //signup settings
			$newsletter_subs->add_menu_page( "lists", "Lists" );
			$newsletter_main->add_menu_page( "info", "Settings" );
		}
	}
}, 999 );
add_action( 'wp_head', function () {
	if(is_admin() && (! is_super_admin() || true)) {
		echo "<style>#tnp-header{display:none;}</style>";
	}
}, 1);
add_action( 'admin_enqueue_scripts', function () {
	if(is_admin() && (! is_super_admin() || true)) {
		if ( ! wp_script_is( 'jquery', 'done' ) ) {
			wp_enqueue_script( 'jquery' );
		}
		wp_add_inline_script( 'jquery-migrate', 'jQuery(document).ready(function(){$("#tnp-header").remove();});' );
	}
}, 1);