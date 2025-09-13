<?php
/**
 * Role REST API
 *
 * @package musahimoun
 */

use MSHMN\Role_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	function () {

		register_rest_route(
			'mshmn/v1',
			'/roles',
			array(
				'methods'             => 'GET',
				'callback'            => 'mshmn_rest_get_roles',
				'permission_callback' => function() {
       				 return current_user_can( 'edit_posts' );
    				},
			)
		);

		register_rest_route(
			'mshmn/v1',
			'/roles',
			array(
				'methods'             => 'PUT',
				'callback'            => 'mshmn_rest_add_or_update_role',
				'permission_callback' => function() {
       				 return current_user_can( 'edit_posts' );
    				},
			)
		);

		register_rest_route(
			'mshmn/v1',
			'/roles/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => 'mshmn_rest_delete_role',
				'permission_callback' => function() {
       				 return current_user_can( 'manage_options' );
    				},
				'args'                => array(
					'id' => array(
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		/**
		 * Get roles
		 *
		 * @param WP_REST_Request $request REST request object.
		 */
		function mshmn_rest_get_roles( WP_REST_Request $request ): array {
			$role_service = new Role_Service();

			$query_params = $request->get_query_params();

			$roles = array();

			$ids = isset( $query_params['ids'] ) ? explode( ',', $query_params['ids'] ) : '';

			if ( isset( $query_params['ids'] ) && empty( $query_params['ids'] ) ) {
				return array();
			}

			$args   = is_array( $ids ) ? array( 'include' => $ids ) : array();
			$_roles = $role_service->get_roles( $args );

			foreach ( $_roles as $role ) {
				$role_with_numeric_id = array_merge(
					(array) $role,
					array(
						'id' => absint( $role->id ),
					)
				);
				array_push( $roles, $role_with_numeric_id );
			}

			return $roles;
		}

		/**
		 * Create or update a role
		 *
		 * @param WP_REST_Request $request REST request object.
		 */
		function mshmn_rest_add_or_update_role( WP_REST_Request $request ): array {

			$parameters = $request->get_params();

			$response = array();
			foreach ( $parameters as $parameter ) {

				if ( ! isset( $parameter['nicename'] ) ) {
					continue;
				}

				$data = array(
					'nicename'    => trim( $parameter['nicename'] ),
					'prefix'      => isset( $parameter['prefix'] ) ? trim( $parameter['prefix'] ) : '',
					'conjunction' => isset( $parameter['conjunction'] ) ? trim( $parameter['conjunction'] ) : '',
					'picture'     => isset( $parameter['picture'] ) ? (bool) $parameter['picture'] : false,
				);

				$role_service = new Role_Service();

				if ( isset( $parameter['id'] ) && ! empty( $parameter['id'] ) ) {
					// Update existing role.
					$existing_role = $role_service->get_roles( array( 'include' => array( $parameter['id'] ) ) );
					if ( ! empty( $existing_role ) ) {
						$update = $role_service->update( $data, array( 'id' => $parameter['id'] ) );
						if ( false !== $update ) {
							$data['id'] = absint( $parameter['id'] );
							array_push( $response, $data );
							continue;
						}
					}
				} else {
					// Insert new role.
					$row = $role_service->insert( $data );

					if ( $row ) {
						$data['id'] = $row; // Assuming the insert method returns the new ID.
						array_push( $response, $data );
					}
				}
			}
			return $response;
		}

		/**
		 * Delete a role
		 *
		 * @param WP_REST_Request $request REST request object.
		 *
		 * @return int|false One on update, or false on error.
		 */
		function mshmn_rest_delete_role( WP_REST_Request $request ) {
			$role_service = new Role_Service();
			$parameters   = $request->get_params();
			$row          = $role_service->delete( array( 'id' => $parameters['id'] ), array( '%d' ) );
			return $row;
		}
	}
);
