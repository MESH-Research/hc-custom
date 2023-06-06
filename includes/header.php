<?php
/**
 * Modifications to site header appearance
 */

 /**
 * Adds search modal and user menu to nagivation menu.
 *
 * @see wp-includes/blocks/navigation.php::render_block_core_navigation
 * 
 * @param WP_Block_List $inner_blocks Array of blocks in navigation menu
 * 
 * @return WP_Block_List Modified $inner_blocks with search and user menu added.
 */
function hc_add_navigation_inner_blocks( $inner_blocks ) {
	
	$search_modal_html = hc_navigation_search_modal();
	$user_menu_html = hc_navigation_logged_in_menu();

	$hc_navigation_html = "<div id='hc-navigation-block-wrapper'>$search_modal_html $user_menu_html</div>";

	$hc_navigation_block = new \WP_Block(
		[
			'blockName' => 'core/html',
			'attrs'     => [
				'content'        => '',
			],
			'innerBlocks' => [],
			'innerHTML'   => '',
			'innerContent' => [ $hc_navigation_html ],
		]
	);

	$inner_blocks->offsetSet( null, $hc_navigation_block );

	return $inner_blocks;
}
add_filter( 'block_core_navigation_render_inner_blocks', 'hc_add_navigation_inner_blocks', 10, 1 );

/**
 * Content for search modal in top navigation bar.
 */
function hc_navigation_search_modal() {
	ob_start();
	?>
	<div id="search-modal-wrapper">
		<button aria-label="Open Search" data-custom-open="modal-1" role="button"><span class="fa fa-search"></span></button>
		<div class="modal" id="modal-1" aria-hidden="true">
			<div tabindex="-1" data-micromodal-close>
				<div aria-label="Menu" aria-modal="true" role="dialog">
					<div id="modal-1-content">
						<button id="modal-1-close" aria-label="Close Search" data-micromodal-close>
							<span class="fa fa-close"></span> 
						</button>
						<form role="search" method="get" id="searchform" class="searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
							<div class="search-wrapper">
								<label class="screen-reader-text" for="s"><?php _e( 'Search for:', 'boss' ); ?></label>
								<input type="text" value="" name="s" id="s" placeholder="Search">
								<button type="submit" id="searchsubmit" title="<?php _e( 'Search', 'boss' ); ?>"><i class="fa fa-search"></i></button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
	$search_modal_html = ob_get_clean();
	return $search_modal_html;
}

/**
 * Content for user menu in top navigation bar.
 *
 * When logged-in, shows dropdown menu with user profile, etc. When not
 * logged-in, shows register and login buttons.
 */
function hc_navigation_logged_in_menu() {
	if ( ! is_user_logged_in() ) {
		return hc_register_login_items();
	}
	
	$user_menu_html = hc_navigation_notification_menu();
	$user_menu_html .= hc_navigation_user_menu();
	
	return $user_menu_html;
}

/**
 * Creates 'Register' and 'Login' items for top nav bar.
 * 
 * These are displayed instead of the notification and user menus when the user is logged out.
 */
function hc_register_login_items() {
	ob_start();
	?>
	<a href="<?= bp_get_signup_page() ?>" class="navigation-button">Register</a>
	<a href="<?= wp_login_url() ?>" class="navigation-button">Login</a>
	<?php
	$register_login_html = ob_get_clean();
	return $register_login_html;
}

/**
 * Creates the notification menu for the top nav bar.
 */
function hc_navigation_notification_menu() {
	ob_start();
	?>
	<div class="wp-block-navigation__container">
		<div id="navigation-bp-notifications-wrapper" class="wp-block-navigation-item has-child">
			<a class="notification-link fa fa-bell" href="<?= bp_get_notifications_permalink() ?>">
				<span id="notification-bp-notififications-pending-count" class="pending-count alert">
					<?= bp_notifications_get_unread_notification_count( get_current_user_id() ) ?>
				</span>
			</a>
			<button aria-label="Go To... submenu" class="wp-block-navigation__submenu-icon wp-block-navigation-submenu__toggle" aria-expanded="false">
				<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" focusable="false"><path d="M1.50002 4L6.00002 8L10.5 4" stroke-width="1.5"></path></svg>
			</button>
			<ul id="navigation-bp-notifications-list" class="wp-block-navigation__submenu-container">
				<?php 
				if ( bp_has_notifications() ) {
					while ( bp_the_notifications() ) {
						bp_the_notification();
						echo '<li>';
						bp_the_notification_description();
						echo '</li>';
					}
				}
				?>
			</ul>
		</div>
	</div>
	<?php
	$notifications_html = ob_get_clean();
	return $notifications_html;
}

/**
 * Creates the user menu for the top nav bar, generated from the BuddyPress nav menu.
 */
function hc_navigation_user_menu() {
	ob_start();
	?>
	<div class="wp-block-navigation__container">
		<div id="navigation-user-menu-wrapper" class="wp-block-navigation-item has-child">
			<a class="user-link" href="<?= bp_core_get_user_domain( get_current_user_id() ); ?>">
				<span class="name"><?= bp_core_get_user_displayname( get_current_user_id() ); ?></span>
			</a>
			<button aria-label="Go To... submenu" class="wp-block-navigation__submenu-icon wp-block-navigation-submenu__toggle" aria-expanded="false">
				<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" focusable="false"><path d="M1.50002 4L6.00002 8L10.5 4" stroke-width="1.5"></path></svg>
			</button>
			<?php bp_nav_menu( [ 'menu_class' => 'wp-block-navigation__submenu-container']); ?>
		</div>
	</div>
	<?php
	$user_menu_html = ob_get_clean();
	return $user_menu_html;
}


/**
 * Enqueue JS for header functionality.
 */
function hc_enqueue_header_js() {
	$a = plugin_dir_url( __FILE__ ) . 'js/micromodal.min.js';
	$b = plugin_dir_url( __FILE__ ) . 'js/header.js';
	
	wp_enqueue_script( 
		'hc-header-micromodal-js', 
		plugin_dir_url( __FILE__ ) . 'js/micromodal.min.js'
	);
	
	wp_enqueue_script( 
		'hc-header-js',
		plugin_dir_url( __FILE__ ) . 'js/header.js',
		filemtime( plugin_dir_path( __FILE__ ) . 'js/header.js' )	
	);
}
add_action( 'wp_enqueue_scripts', 'hc_enqueue_header_js', 10, 0 );

/**
 * Make sure FontAwesome is enqueued
 * 
 * Copied from BuddyBoss
 */
function hc_enqueue_fontawesome() {
	// FontAwesome icon fonts. If browsing on a secure connection, use HTTPS.
	// We will only load if our is latest.
	$recent_fwver = (isset(wp_styles()->registered["fontawesome"]))?wp_styles()->registered["fontawesome"]->ver:"0";
	$current_fwver = "4.4.0";
	if(version_compare($current_fwver, $recent_fwver , '>')) {
		wp_deregister_style( 'fontawesome' );
		wp_register_style( 'fontawesome', "//maxcdn.bootstrapcdn.com/font-awesome/{$current_fwver}/css/font-awesome.min.css", false, $current_fwver);
		wp_enqueue_style( 'fontawesome' );
	}
}
add_action( 'wp_enqueue_scripts', 'hc_enqueue_fontawesome', 10, 0 );

/**
 * Enqueue header CSS
 */
function hc_enqueue_header_css() {
	wp_enqueue_style(
		'hc-header-css',
		trailingslashit( plugins_url() ) . 'hc-custom/includes/css/header.css',
		[],
		filemtime( plugin_dir_path( __FILE__ ) . 'css/header.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'hc_enqueue_header_css', 10, 0 );

/**
 * Addresses an apparent bug in BP where the profile nav item children don't get
 * properly added as a submenu.
 *
 * When consructing the navigation menu, BP uses 'css_id' rather than 'slug' to
 * determine parent-child relationships, but the profile component uses the
 * 'slug' field instead when creating submenu items. The slug of the profile
 * menu is 'profile' while the css_id is 'xprofile', so the submenu items end up
 * orphaned.
 *
 * This function addresses this bug by changing the 'parent' field from
 * 'profile' to 'xprofile'. To avoid breaking if the underlying bug is fixed, it
 * bails if there is no top-level menu with a css_id of 'xprofile'.
 *
 * @see buddypress/bp-core/bp-core-template.php::bp_nav_menu() - creating the
 * navigation menu
 * @see
 * buddypress/bp-xprofile/classes/class-bp-xprofile-component.php::setup_nav() -
 * creating the menu items
 *
 * @param array $menu_items Array of stdClass objects with css_id and parent
 * properties.
 * @param array $args Unused.
 *
 * @return array corrected array of $menu_items
 */
function hc_fix_bp_nav_menu_items( $menu_items, $args ) {
	$found_xprofile = false;
	$new_menu_items = [];

	foreach ( $menu_items as $item ) {
		if ( $item->parent === 'profile' ) {
			$item->parent = 'xprofile';
		}
		elseif ( $item->css_id === 'xprofile' ) {
			$found_xprofile = true;
		}
		$new_menu_items[] = $item;
	}

	if ( $found_xprofile ) {
		return $new_menu_items;
	}

	return $menu_items;
}
add_filter( 'bp_nav_menu_objects', 'hc_fix_bp_nav_menu_items', 10, 2 );

/**
 * Generates working links for top-level menu items in the nav menu.
 *
 * By default, top-level items like Profiles just link to a slug (eg.
 * "xprofile") rather than an actual URL, for use in the BuddyPress pseudo
 * tabbed menu. This function replaces those slugs with actual URLs for use in
 * the dropdown user menu.
 */
function hc_add_links_to_parent_nav_items( $menu_items, $args) {
	$section_links = [];

	foreach ( $menu_items as $item ) {
		if ( $item->parent === 0 || array_key_exists( $item->parent, $section_links ) ) {
			continue;
		}
		$section_links[ $item->parent ] = $item->link;
	}

	foreach( $menu_items as &$item ) {
		if ( $item->parent !== 0 || ! array_key_exists( $item->css_id, $section_links ) ) {
			continue;
		}
		$item->link = $section_links[ $item->css_id ];
	}

	return $menu_items;
}
add_filter( 'bp_nav_menu_objects', 'hc_add_links_to_parent_nav_items', 15, 2 );

/**
 * Adds a logout option to the bottom / end of the user menu.
 */
function hc_add_logout_to_main_nav() {
	bp_core_create_nav_link(
		[
			'name'        => 'Logout',
			'slug'        => wp_logout_url( '/logged-out/' ),
			'position'    => 200,
			'item_css_id' => 'hc-nav-logout',
		]
	);
}
add_action( 'bp_setup_nav', 'hc_add_logout_to_main_nav', 20, 0 );

