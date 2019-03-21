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

class BPGroupDocuments {

        const CHECKBOX_NAME = 'group-files-minor-edit';


        function __construct() {
                add_action( 'bp_group_documents_add_success', [ $this, 'prevent_activity' ], 0 );
        }

        function prevent_activity() {
                //global $bp_docs;
                if ( isset( $_POST[ self::CHECKBOX_NAME ] ) ) {
                    remove_action( 'bp_group_documents_add_success', 'bp_group_documents_record_add' );
                }
        }
}

new BPGroupDocuments;