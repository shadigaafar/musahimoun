<?php
/**
 * Rest API for users and guest authors alike.
 *
 * @package Musahimoun
 */

namespace MSHMN\Rest;

/**
 * Registers a custom REST API endpoint for retrieving both users and guest authors.
 *
 * This function registers a REST API route under 'mshmn/v1' with the endpoint 'contributors'.
 * The endpoint supports a GET request and can optionally filter results based on a search term.
 *
 * @since 1.0
 *
 * @return void
 */
function register_custom_user_guest_contributor_endpoint() {
	register_rest_route(
		'mshmn/v1',
		'/contributors',
		array(
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\\get_users_and_guest_contributors',
			'args'                => array(
				'search' => array(
					'description'       => __( 'Optional search term to filter users and guest authors by name.', 'musahimoun' ),
					'required'          => false,
					'validate_callback' => function ( $param ) {
						return is_string( $param );
					},
					'sanitize_callback' => function ( $param ) {
						return sanitize_text_field( $param );
					},
				),
				'ids'    => array(
					'description'       => __( 'Optional to filter users and guests by IDs', 'musahimoun' ),
					'required'          => false,
					'validate_callback' => function ( $param ) {
						return is_string( $param );
					},
					'sanitize_callback' => function ( $param ) {
						return sanitize_text_field( $param );
					},
				),
			),
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' ); // Restrict access to users with edit_posts capability.
			},
		)
	);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\register_custom_user_guest_contributor_endpoint' );

/**
 * Retrieves both WordPress users and guest authors, optionally filtered by a search term.
 *
 * This function handles the callback for the REST API endpoint registered above. It queries both
 * the WordPress user table and the custom guest authors table, combining the results into a
 * unified response. If a search term is provided, it filters the results based on the term.
 *
 * @since 1.0
 *
 * @param \WP_REST_Request $request The REST API request object, automatically passed by WordPress.
 *
 * @return \WP_REST_Response The response object containing the combined list of users and guest authors.
 */
function get_users_and_guest_contributors( \WP_REST_Request $request ) {
	$search = $request->get_param( 'search' );
	$ids    = null !== $request->get_param( 'ids' ) ? explode( ',', $request->get_param( 'ids' ) ) : array();

	// Fetch WordPress users.
	$query_args = array(
		'search'  => $search ? esc_attr( $search ) : '',
		'include' => $ids,
	);

	$contributor_query = new \MSHMN\Contributor_Service( $query_args );

	$response_data = $contributor_query->get_results( ARRAY_A );

	// Return the combined data as a JSON response.
	return new \WP_REST_Response( $response_data, 200 );
}
