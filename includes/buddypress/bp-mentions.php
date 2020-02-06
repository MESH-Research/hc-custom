<?php
/**
 * @package HC Custom
 * Add your own functions in this file.
 */

/**
 * Adds support for user at-mentions to the Suggestions API.
 */
class MLA_Name_Suggestions extends BP_Suggestions {

        /**
        * Default arguments for this suggestions service.
        *
        * @since BuddyPress (2.1.0)
        * @var array $args {
        *     @type int $limit Maximum number of results to display. Default: 200.
        *     @type string $term The suggestion service will try to find results that contain this string.
        *           Mandatory.
        * }
        */
        protected $default_args = array(
                'limit'        => 200,
                'term'         => '',
                'type'         => '',
        );

        /**
        * Validate and sanitise the parameters for the suggestion service query.
        *
        * @return true|WP_Error If validation fails, return a WP_Error object. On success, return true (bool).
        * @since BuddyPress (2.1.0)
        */
        public function validate() {
                $this->args = apply_filters( 'mla_name_suggestions_args', $this->args, $this );

                // Check for invalid or missing mandatory parameters.
                if ( empty( $this->args['term'] ) || ! is_user_logged_in()  ) {
                        return new WP_Error( 'missing_requirement' );
                }

                return apply_filters( 'mla_name_suggestions_validate_args', parent::validate(), $this );
        }

        /**
        * Find and return a list of user name suggestions that match the query.
        *
        * @return array|WP_Error Array of results. If there were problems, returns a WP_Error object.
        * @since BuddyPress (2.1.0)
        */
        public function get_suggestions() {

                $user_query = array(
                        'count_total'     => '',  // Prevents total count
                        'populate_extras' => false,
                        'type'            => 'alphabetical',

                        'page'            => 1,
                        'per_page'        => $this->args['limit'],
                        'search_terms'    => $this->args['term'],
                        'search_wildcard' => 'right',
                );

                $user_query = apply_filters( 'mla_suggestions_query_args', $user_query, $this );

                if ( is_wp_error( $user_query ) ) {
                        return $user_query;
                }

                add_action( 'bp_pre_user_query', array( $this, 'mla_query_users_by_name' ) );

                $user_query = new BP_User_Query( $user_query );

                $results = array();
                foreach ( $user_query->results as $user ) {
                        $result        = new stdClass();
                        $result->ID    = $user->user_nicename;
                        $result->image = bp_core_fetch_avatar( array( 'html' => false, 'item_id' => $user->ID ) );
                        $result->name  = bp_core_get_user_displayname( $user->ID );

                        $results[] = $result;
                }

                return apply_filters( 'mla_name_suggestions_get_suggestions', $results, $this );
        }

        /**
        * Query users by name
        *
        * @param BP_User_Query $bp_user_query
        */
        public function mla_query_users_by_name( $bp_user_query ) {

                global $wpdb;
                $society_id = get_network_option( '', 'society_id' );

        if ( ! empty( $bp_user_query->query_vars['search_terms'] ) ) {
                        $bp_user_query->uid_clauses['where'] = " WHERE u.ID IN ( SELECT tr.object_id FROM {$wpdb->users} us, wp_1000360_terms t, wp_1000360_term_relationships tr, wp_1000360_term_taxonomy tt where t.term_id = tt.term_id and tt.term_taxonomy_id = tr.term_taxonomy_id and tt.taxonomy='bp_member_type' and t.slug='{$society_id}' and tr.object_id = us.ID and us.spam = 0 AND us.deleted = 0 AND us.user_status = 0 AND ( us.display_name LIKE '%" . ucfirst( strtolower(  $bp_user_query->query_vars['search_terms'] ) ) ."%' OR us.user_login LIKE '%" . strtolower( $bp_user_query->query_vars['search_terms'] ) . "%' ) )";
                        $bp_user_query->uid_clauses['orderby'] = "ORDER BY u.display_name";
                }

        }

}
add_filter( 'bp_suggestions_services', function() { return 'MLA_Name_Suggestions'; } );

/**
 * Override BP AJAX endpoint for Suggestions API lookups.
 *
 * @since BuddyPress (2.1.0)
 */
function mla_ajax_get_suggestions() {
        if ( ! bp_is_user_active() || empty( $_GET['term'] ) || empty( $_GET['type'] ) ) {
                wp_send_json_error( 'missing_parameter' );
                exit;
        }

        $results = bp_core_get_suggestions( array(
                'term' => sanitize_text_field( $_GET['term'] ),
                'type' => 'mla_members',
                'limit' => '200',
        ) );

        if ( is_wp_error( $results ) ) {
                wp_send_json_error( $results->get_error_message() );
                exit;
        }

        wp_send_json_success( $results );
}
remove_action( 'wp_ajax_bp_get_suggestions', 'bp_ajax_get_suggestions' );
add_action( 'wp_ajax_bp_get_suggestions', 'mla_ajax_get_suggestions' );

/**
 * Enqueue @mentions JS.
 *
*/
function mla_member_mentions_script() {
        if ( ! bp_activity_maybe_load_mentions_scripts() ) {
                return;
        }

        // Special handling for New/Edit screens in wp-admin
        if ( is_admin() ) {
                if (
                        ! get_current_screen() ||
                        ! in_array( get_current_screen()->base, array( 'page', 'post' ) ) ||
                        ! post_type_supports( get_current_screen()->post_type, 'editor' ) ) {
                        return;
                }
        }

	$min = '';
        //$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_script( 'mla-mentions', get_stylesheet_directory_uri() . "/js/mentions{$min}.js", array( 'jquery', 'jquery-atwho' ), bp_get_version(), true );
	wp_enqueue_style( 'mla-mentions-css', get_stylesheet_directory_uri() . "/css/mentions{$min}.css", array(), bp_get_version() );

}
remove_action( 'bp_enqueue_scripts', 'bp_activity_mentions_script' );
remove_action( 'bp_admin_enqueue_scripts', 'bp_activity_mentions_script' );
add_action( 'bp_enqueue_scripts', 'mla_member_mentions_script' );
add_action( 'bp_admin_enqueue_scripts', 'mla_member_mentions_script' );

function mla_mentions_script_enable( $current_status ) {
        return $current_status || bp_is_groups_component();
}
add_filter( 'bp_activity_maybe_load_mentions_scripts', 'mla_mentions_script_enable' );

/**
 * @param boolean $load
 * @param $mentions_enabled
 * @return boolean enabled ot not?
 */
function buddydev_enable_mention_autosuggestions_on_compose( $load, $mentions_enabled ) {

        if ( ! $mentions_enabled ) {
                return $load; //activity mention is  not enabled, so no need to bother
        }
        //modify this condition to suit yours
        if( is_user_logged_in() && bp_is_messages_compose_screen() ) {
                $load = true;
        }

        return $load;
}
add_filter( 'bp_activity_maybe_load_mentions_scripts', 'buddydev_enable_mention_autosuggestions_on_compose', 10, 2 );

/**
 * Removes autocomplete js and css so mentions.js can be used in compose screen for autocomplete
 *
 * @return void
 */
function remove_messages_add_autocomplete_js_css() {
        remove_action( 'bp_enqueue_scripts', 'messages_add_autocomplete_js' );
        remove_action( 'wp_head', 'messages_add_autocomplete_css' );
}

add_action( 'init', 'remove_messages_add_autocomplete_js_css' );

