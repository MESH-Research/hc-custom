<?php
/**
   * Disables "followers" menu item on the top right
   *
   * @uses $wp_admin_bar
   */
function hcommons_admin_bar_remove_followers() {
    global $wp_admin_bar;

    $wp_admin_bar->remove_node('my-account-follow-followers');
}

add_action('wp_before_admin_bar_render','hcommons_admin_bar_remove_followers');
