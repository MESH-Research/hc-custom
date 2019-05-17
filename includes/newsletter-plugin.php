<?php
add_action( 'admin_menu', function () {
	if ( class_exists( "NewsletterModule" ) ) {
		remove_submenu_page( "newsletter_main_index", "newsletter_main_welcome" ); //Welcome page
		if ( true || ! is_super_admin() ) { //temp true for testing
			$newsletter = new NewsletterModule();
			remove_submenu_page( "newsletter_main_index", "newsletter_main_index" ); //dashboard
			remove_submenu_page( "newsletter_main_index", "newsletter_main_main" ); //settings page
			remove_submenu_page( "newsletter_main_index", "newsletter_subscription_antibot" ); //security page
			remove_submenu_page( "newsletter_main_index", "newsletter_subscription_options" ); //signup settings
			$newsletter->add_menu_page( "lists", "Lists" );
			$newsletter->add_menu_page( "main_info", "Settings" );
		}
	}
}, 999 );
