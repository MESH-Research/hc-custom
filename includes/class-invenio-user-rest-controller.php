<?php
/**
 * A REST Contoller for providing Commons user data to the Commons Works
 * (Invenio) application.
 *
 * This is a temporary solution pending a more robust Commons API. It uses
 * token-based authentication and is not publicly accessible.
 */

namespace KCommons\HCCustom\Rest;

class InvenioUserRestController extends \WP_REST_Controller {
	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace = 'commons/v1';

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'users';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<username>[a-zA-Z0-9-]+)',
			[
				[
					'methods' => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args' => [
						'username' => [
							'description' => __( 'Username of the user to retrieve.' ),
							'type' => 'string',
							'required' => true,
						],
					],
				],
			]
		);
	}

	/**
	 * Check if a given request has access to get items.
	 */
	public function get_items_permissions_check( $request ) {
		$api_key = getenv( 'INVENIO_API_KEY' );
		if ( ! $api_key ) {
			return new \WP_Error( 'rest_forbidden', __( 'API key not set.' ), [  'status' => 403  ] );
		}

		if ( ! isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'Authorization header not set.' ), [  'status' => 403  ] );
		}

		if ( $_SERVER['HTTP_AUTHORIZATION'] !== 'Bearer ' . $api_key ) {
			return new \WP_Error( 'rest_forbidden', __( 'Invalid authorization header.' ), [  'status' => 403  ] );
		}
	
		return true;
	}

	/**
	 * Generate response for single user.
	 */
	public function get_item( $request ) {
		$username = $request['username'];
		$user_data = $this->get_user_data( $username );
		if ( is_wp_error( $user_data ) ) {
			return $user_data;
		}

		return rest_ensure_response( $user_data );
	}
	
	/**
	 * Get user data
	 */
	protected function get_user_data( $username ) {
		$user = get_user_by( 'login', $username );
		if ( ! $user ) {
			return new \WP_Error( 'rest_user_invalid_id', __( 'Invalid user ID.' ), [  'status' => 404  ] );
		}

		$user_data =  [
			'username' => $user->user_login,
			'email' => $user->user_email,
			'name' => $user->display_name,
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
		];

		$user_data['institutional_affiliation'] = xprofile_get_field_data( 'Institutional or Other Affiliation', $user->ID );

		$user_data['groups'] = [];
		$groups = bp_get_user_groups( 
			$user->ID,
			[
				'is_confirmed' => null,
				'is_admin' => null,
				'is_mod' => null,
				'invite_sent' => null,
			]
		);
		foreach ( $groups as $group ) {
			$group_obj = groups_get_group( $group->group_id );
			if ( $group->is_admin ) {
				$role = 'admin';
			} elseif ( $group->is_mod ) {
				$role = 'moderator';
			} else {
				$role = 'member';
			}
			$user_data['groups'][] = [
				'id'   => $group->group_id,
				'name' => $group_obj->name,
				'role' => $role,
			];
		}

		return $user_data;
	}
}

/**
 * Register the REST controller.
 */
function register_invenio_user_rest_controller() {
	$controller = new InvenioUserRestController();
	$controller->register_routes();
}
add_action( 'rest_api_init', __NAMESPACE__ . '\register_invenio_user_rest_controller' );