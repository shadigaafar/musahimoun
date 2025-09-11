<?php
/**
 * Guest Contributor REST API
 *
 * @package musahimoun
 */

use MSHMN\Guest_Service;

use function MSHMN\Functions\get_guests;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	function () {

		register_rest_route(
			'mshmn/v1',
			'/guest-authors',
			array(
				'methods'             => 'GET',
				'callback'            => 'mshmn_rest_get_guests',
				'permission_callback' => function() {
       				 return current_user_can( 'edit_posts' );
    				},
			)
		);

		register_rest_route(
			'mshmn/v1',
			'/guest-authors',
			array(
				'methods'             => 'PUT',
				'callback'            => 'mshmn_rest_add_guest_author',
				'permission_callback' => function() {
       				 return current_user_can( 'edit_posts' );
    				},
			)
		);

		register_rest_route(
			'mshmn/v1',
			'/guest-authors/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => 'mshmn_rest_delete_guest_author',
				'permission_callback' => function() {
       				 return current_user_can( 'manage_options' );
    				},
				'args'                => array(
					'id' => array(
						'validate_callback' => function ( $param, $request, $key ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		/**
		 * Get the guest authors
		 *
		 * @param WP_REST_Request $request REST request object.
		 */
		function mshmn_rest_get_guests( WP_REST_Request $request ): array {
			global $wpdb;

			$query_params = $request->get_query_params();

			$guest_authors = array();

			$ids = isset( $query_params['ids'] ) ? explode( ',', $query_params['ids'] ) : '';

			if ( isset( $query_params['ids'] ) && empty( $query_params['ids'] ) ) {
				return array();
			}

			$args           = is_array( $ids ) ? array( 'include' => $ids ) : array();
			$_guest_authors = get_guests( $args, ARRAY_A );

			foreach ( $_guest_authors as $guest_author ) {
				$guest_author_with_numeric_id = array_merge(
					$guest_author,
					array(
						'id' => absint( $guest_author['id'] ),
					)
				);
				array_push( $guest_authors, $guest_author_with_numeric_id );
			}

			return $guest_authors;
		}

		/**
		 * Create or update a single guest author
		 *
		 * @param WP_REST_Request $request REST request object.
		 */
		function mshmn_rest_add_guest_author( WP_REST_Request $request ): array {

			$parameters = $request->get_params();

			global $wpdb;
			$table = $wpdb->prefix . 'guest_authors';

			$response = array();
			foreach ( $parameters as  $parameter ) {

				if ( ! isset( $parameter['name'] ) ) {
					continue;
				}

				$data = array(
					'name'        => trim( $parameter['name'] ),
					'email'       => isset( $parameter['email'] ) ? trim( $parameter['email'] ) : '',
					'description' => isset( $parameter['description'] ) ? $parameter['description'] : '',
					'avatar'      => $parameter['avatar'],
				);

				$guest_author_service = new Guest_Service();

				if ( isset( $parameter['id'] ) && ! empty( $parameter['id'] ) ) {
					// update.

					// phpcs:ignore
					$guest_author = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM  $table WHERE id = %d", $parameter['id']  ), 'ARRAY_A' );
					if ( ! empty( $guest_author ) ) {

						$update = $guest_author_service->update( $data, array( 'id' => $parameter['id'] ) );
						if ( $update ) {
							$data['id'] = absint( $parameter['id'] );
							array_push( $response, $data );
							continue;
						}
					}
				}
				// create.
				$row = $guest_author_service->insert( $data );

				$wpdb->show_errors( true );
				if ( $row ) {
					array_push( $response, $data );
				}
			}
			return $response;
		}

		/**
		 * Delete a single guest author
		 *
		 * @param WP_REST_Request $request REST request object.
		 *
		 * @return int|false One on update, or false on error.
		 */
		function mshmn_rest_delete_guest_author( WP_REST_Request $request ) {
			$guest_author_service = new Guest_Service();
			$parameters           = $request->get_params();
			$row                  = $guest_author_service->delete( array( 'id' => $parameters['id'] ), array( '%d' ) );
			return $row;
		}
	}
);
