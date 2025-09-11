<?php
/**
 *
 * Enqueue
 *
 * @since 1.1.0
 * @package musahimoun
 */

namespace MSHMN\meta;

/**
 * Enqueue script.
 */
function enqueue_scripts() {
	$asset = include __DIR__ . '/index.asset.php';

	wp_register_script( 'mshmn-meta-bock-script', plugin_dir_url( __FILE__ ) . 'index.js', $asset['dependencies'], $asset['version'], true );
	wp_enqueue_script( 'mshmn-meta-bock-script');
	wp_enqueue_style( 'musahimoun-meta-box-style', plugin_dir_url( __FILE__ ) . 'style.css', array(), $asset['version'], 'all' );
}
add_action( 'wp_enqueue_editor', __NAMESPACE__ . '\\enqueue_scripts', 1 );


/**
 * Register custom meta tag field.
 */
function author_block_register_post_meta() {

	$post_types = get_option( MSHMN_SUPPORTED_POST_TYPES, array() );

	if ( is_array( $post_types ) ) {
		foreach ( $post_types as $post_type ) {
			register_post_meta(
				$post_type,
				MSHMN_POST_CONTRIBUTORS_META,
				array(
					'show_in_rest' => true,
					'single'       => true,
					'type'         => 'string',
					'default'      => '',
				)
			);
		}
	}
}
add_action( 'init', __NAMESPACE__ . '\\author_block_register_post_meta' );

/**
 * Sanitize the 'MSHMN_ROLE_ASSINGMENTS_META' meta field.
 *
 * This function ensures that the meta value is an array of objects where each object has:
 * - a `role` key containing a sanitized string
 * - a `contributors` key containing an array of sanitized contributor IDs (can be empty).
 *
 * Example structure after sanitization:
 * [
 *    { 'role' => 'editor', 'contributors' => ['1', '2', '3'] },
 *    { 'role' => 'author', 'contributors' => [] }, // Empty array is now allowed
 * ]
 *
 * @param mixed $meta_value The meta value to be sanitized.
 * @return array The sanitized array of roles and contributors.
 */
function sanitize_contributors_by_roles( $meta_value ) {
	// Ensure the meta value is an array.
	if ( ! is_array( $meta_value ) ) {
		return array();
	}

	$sanitized_roles = array();

	foreach ( $meta_value as $entry ) {
		// Ensure each entry is an array with both 'role' and 'contributors' keys.
		if ( ! is_array( $entry ) || ! isset( $entry['role'], $entry['contributors'] ) ) {
			continue;
		}

		// Sanitize the role.
		$sanitized_role = sanitize_key( $entry['role'] );

		// Ensure contributors is an array and sanitize each ID (empty array is now allowed).
		$contributors           = is_array( $entry['contributors'] ) ? $entry['contributors'] : array();
		$sanitized_contributors = array_map( 'sanitize_key', $contributors );

		// Only add to the sanitized roles if there is a valid role.
		if ( '' !== $sanitized_role ) {
			$sanitized_roles[] = array(
				'role'         => $sanitized_role,
				'contributors' => $sanitized_contributors,
			);
		} else {
			$sanitized_roles[] = array(
				'role'         => null,
				'contributors' => array(),
			);
		}
	}

	return $sanitized_roles;
}

/**
 * Register the 'MSHMN_ROLE_ASSINGMENTS_META' meta field.
 *
 * Example structure:
 * [
 *    { 'role' => 1, 'contributors' => [1, 2, 3] }, // Role 1 associated with contributor/author IDs 1, 2, and 3.
 *    { 'role' => 3, 'contributors' => [] },        // Role 2 associated with no contributors (empty array).
 *    { 'role' => 5, 'contributors' => [6, 7, 8] }  // Role 3 associated with contributor/author IDs 6, 7, and 8.
 * ]
 */
function register_contributors_by_roles_meta() {
	$args = array(
		'show_in_rest'      => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'role'         => array(
							'type'        => array( 'number', 'null' ),
							'description' => 'Role ID.',
						),
						'contributors' => array(
							'type'        => 'array',
							'items'       => array(
								'type' => 'number',
							),
							'description' => 'List of contributor IDs associated with the role.',
						),
					),
				),
			),
		),
		'single'            => true,
		'default'           => array(
			array(
				'role'         => null, // Default role ID.
				'contributors' => array(), // Default contributor IDs.
			),
		),
		'type'              => 'array',
		'description'       => 'An array of objects mapping role names to lists of contributor/author IDs.',
		'auth_callback'     => function () {
			return current_user_can( 'edit_posts' );
		},
		'sanitize_callback' => __NAMESPACE__ . '\\sanitize_contributors_by_roles',
	);

	$post_types = get_option( MSHMN_SUPPORTED_POST_TYPES, array() );

	if ( is_array( $post_types ) ) {
		foreach ( $post_types as $post_type ) {
			register_post_meta( $post_type, MSHMN_ROLE_ASSINGMENTS_META, $args );
		}
	}
}

add_action( 'init', __NAMESPACE__ . '\\register_contributors_by_roles_meta' );
