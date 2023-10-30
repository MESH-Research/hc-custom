<?php
/**
 * A REST Controller for providing Commons groups metadata to the Commons Works
 * (Invenio) applicaiton.
 *
 * This is a temporary solution pending a more robust Commons API. It uses
 * token-based authentication and is not publicly accessible.
 */

namespace KCommons\HCCustom\Rest;

class InvenioGroupsRestController extends \WP_REST_Controller {

	/**
	 * The namespace for this controller's route.
	 *
	 * @var string
	 */
	protected $namespace = 'commons/v1';

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'groups';

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( 
			$this->namespace, 
			'/' . $this->rest_base, 
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $this->get_items_arguments(),
				],
			]
		);
		register_rest_route( 
			$this->namespace, 
			'/' . $this->rest_base . '/(?P<id>[\d]+)', 
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [],
				],
			]
		);
	}

	/**
	 * Check permissions for the read items.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return bool|\WP_Error
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
	 * Pagination parameters for bulk groups request.
	 */
	public function get_items_arguments() {
		$args = [];

		$args['page'] = [
			'default' => 1,
			'sanitize_callback' => 'absint',
		];

		$args['per_page'] = [
			'default' => 20,
			'sanitize_callback' => 'absint',
		];

		return $args;
	}

	/**
	 * Generate response for single group.
	 */
	public function get_item( $request ) {
		$group = groups_get_group( [ 'group_id' => $request['id'] ] );
		$group_data = $this->group_data( $group );

		return rest_ensure_response( $group_data );
	}

	/**
	 * Bulk groups request.
	 */
	public function get_items( $request ) {
		$groups = groups_get_groups( 
			[ 
				'page'     => $request['page'],
				'per_page' => $request['per_page'],
			] 
		);
		$groups_data = [];

		foreach ( $groups['groups'] as $group ) {
			$groups_data[] = $this->group_data( $group );
		}

		return rest_ensure_response( $groups_data );
	}

	/**
	 * Generate group data from a group object.
	 */
	protected function group_data( $group ) {
		if ( ! $group ) {
			return new \WP_Error( 'rest_invalid_group', __( 'Invalid group ID.' ), [  'status' => 404  ] );
		}

		$group_avatar = bp_core_fetch_avatar( [
			'item_id' => $group->id,
			'object'  => 'group',
			'type'    => 'full',
			'html'    => false,
		] );

		$groupblog = get_groupblog_blog_id( $group->id );
		$groupblog_url = $groupblog ? get_site_url( $groupblog ) : '';

		$upload_roles_meta = groups_get_groupmeta( $group->id, 'upload_roles' );
		$upload_roles = $upload_roles_meta ? $upload_roles_meta : [ 'member', 'moderator', 'administrator' ];

		$moderate_roles_meta = groups_get_groupmeta( $group->id, 'moderate_roles' );
		$moderate_roles = $moderate_roles_meta ? $moderate_roles_meta : [ 'moderator', 'administrator' ];

		$group_data = [
			'id'             => $group->id,
			'name'           => $group->name,
			'url'		     => bp_get_group_permalink( $group ),
			'visibility'     => $group->status,
			'description'    => $group->description,
			'avatar'         => $group_avatar,
			'groupblog'      => $groupblog_url,
			'upload_roles'   => $upload_roles,
			'moderate_roles' => $moderate_roles,
		];

		return $group_data;
	}

}

/**
 * Register the REST controller.s
 */
function register_invenio_groups_rest_controller() {
	$controller = new InvenioGroupsRestController();
	$controller->register_routes();
}
add_action( 'rest_api_init', __NAMESPACE__ . '\register_invenio_groups_rest_controller' );