<?php
/**
 * Customizations to bp-group-documents plugin.
 *
 * @package Hc_Custom
 * @version 1.0.11272018
 */

/**
 * Remove ability for group admins to change member default notification settings.
 **/
function hc_custom_bp_group_documents_email_notification() {
	remove_action( 'bp_group_documents_add_success', 'bp_group_documents_email_notification' );
}

add_action( 'bp_group_documents_add_success' , 'hc_custom_bp_group_documents_email_notification' , 0 ) ;

