<?php
/**
 * Functions related to guest authors
 *
 * @since 1.0
 * @package musahimoun
 */

namespace MSHMN\Functions;

use MSHMN\Guest_Service;

/**
 * Retrieve list of guest authors matching criteria.
 *
 * @since 1.0
 *
 * @see Guest_Service
 *
 * @param array  $args Optional. Arguments to retrieve guest authors. See Guest_Service::prepare_query()
 *                    for more information on accepted arguments.
 * @param string $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants. With one of the first three, return an array of rows indexed from 0 by SQL result row number. Each row is an associative array (column => value, ...), a numerically indexed array (0 => value, ...), or an object ( ->column = value ), respectively. With OBJECT_K, return an associative array of row objects keyed by the value of each row's first column's value. Duplicate keys are discarded. Default OBJECT.
 * @return array|null List of guest authors.
 */
function get_guests( $args = array(), $output = OBJECT ): array {

	$user_search = new Guest_Service( $args, $output );

	return (array) $user_search->get_results();
}

/**
 * Retrieve a contributor by nicename.
 *
 * @since 1.0
 *
 * @param string $nicename The nicename of the guest author.
 * @return object|null Contributor object if found, null otherwise.
 */
function get_contributor_by_nicename( $nicename ) {
	$contributor_service = new \MSHMN\Contributor_Service( array( 'nicename' => $nicename ) );
	$contributors        = $contributor_service->get_results();

	if ( ! empty( $contributors ) ) {
		return $contributors[0];
	}
	return null;
}

/**
 * Get role assignments from post meta.
 *
 * @return array An array of associative values 'role'  and 'contributors'.
 */
function get_role_assingments(): array {
	global $post;

	$role_assignments_with_entity_ids = get_post_meta( $post->ID, MSHMN_ROLE_ASSINGMENTS_META, true );

	$role_assignments_with_entites = array();

	if ( ! is_array( $role_assignments_with_entity_ids ) ) {
		return array();
	}

	foreach ( $role_assignments_with_entity_ids as $key => $role_assignment ) {

		if ( ! is_array( $role_assignment ) ) {
			continue;
		}

		$roles        = new \MSHMN\Role_Service( array( 'include' => $role_assignment['role'] ), ARRAY_A );
		$contributors = array();

		if ( ! empty( $role_assignment['contributors'] ) ) {
			$contributors_ids   = $role_assignment['contributors'];
			$contributors_query = new \MSHMN\Contributor_Service( array( 'include' => $contributors_ids ) );
			$contributors       = $contributors_query->get_results( ARRAY_A );
		}

		$role = $roles->get_results()[0] ?? array();
		$role_assignments_with_entites[ $key ]['role'] = array_merge(
			$role,
			array(
				'icon' => ! empty( $role['icon'] ) ? wp_get_attachment_image_url( $role['icon'], 'thumbnail', true ) : null,
			)
		);

		$role_assignments_with_entites[ $key ]['contributors'] = $contributors;
	}

	return $role_assignments_with_entites;
}
/**
 * Gets the relative path from the plugin directory to the current file.
 *
 * This function combines the strengths of the provided responses and ensures
 * compatibility across different plugin structures.
 *
 * @param string $file The path to the current file within WordPress.
 * @param int    $skip_level The starting level, 1 means skipping first level.
 * @return string The relative path from the plugin directory to the current file or empty string.
 */
function get_plugin_dire_rel_path( $file, $skip_level = 0 ) {
	// Extract the plugin directory path from MSHMN_PLUGIN_PATH (if defined).
	$plugin_dir = MSHMN_PLUGIN_PATH;

	$plugin_dir   = str_replace( '\\', '/', $plugin_dir );
	$current_file = str_replace( '\\', '/', $file );

	// Ensure both paths are absolute before relative path calculation.
	$plugin_dir   = realpath( $plugin_dir );
	$current_file = realpath( $current_file );

	if ( $plugin_dir && $current_file ) {
		// Calculate the relative path using a common base directory.
		$common_path = dirname( $plugin_dir );
		if ( strpos( $current_file, $common_path ) === 0 ) {
			$relative_path = substr( $current_file, strlen( $common_path ) + 1 );
			// Remove the first level directory (if present).
			$path_without_main_dire = explode( '\\', $relative_path, 1 + $skip_level )[ $skip_level ];
			return str_replace( '\\', '/', $path_without_main_dire );
		}
	}
	// Fallback: If paths cannot be determined or are not within the same base directory,
	// return an empty string.
	return '';
}


/**
 * Retrieve original directory from a compiled js file by get the source from .js.map file.
 *
 * @param string $map_file_path Path to file.
 */
function get_original_dire_from_js_map_file( $map_file_path ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
	$filesystem        = new \WP_Filesystem_Direct( true );
	$map_file_contents = $filesystem->get_contents( $map_file_path );

	// Parse the JSON-formatted source map data.
	$map_data = json_decode( $map_file_contents, true );

	// Extract the original source file path if available.
	if ( isset( $map_data['sources'] ) && is_array( $map_data['sources'] ) && count( $map_data['sources'] ) > 0 ) {
		$original_file_path = $map_data['sources'][0];

		// Extract the directory part of the path.
		$original_directory = explode( './', dirname( $original_file_path ) )[1];
		return $original_directory;
	} else {
		return null; // Original directory information not found in the map file.
	}
}

/**
 * Constructs an array of type specifiers based on the types of values in the input array.
 *
 * This function examines each value in the input array and constructs a new array where:
 * - `%s` is used for strings,
 * - `%d` is used for integers,
 * - `%f` is used for floats,
 * - Other types (e.g., booleans, null) default to `%s`.
 *
 * @param array $input_array The input array with values whose types need to be determined.
 *
 * @return array An array where the keys match those of the input array, and the values are
 *               type specifiers (`%s`, `%d`, `%f`) based on the type of each corresponding value.
 */
function get_type_specifiers( array $input_array ): array {
	$type_array = array();

	foreach ( $input_array as $key => $value ) {
		switch ( true ) {
			case is_string( $value ):
				array_push( $type_array, '%s' );
				break;
			case is_int( $value ):
				array_push( $type_array, '%d' );
				break;
			case is_float( $value ):
				array_push( $type_array, '%f' );
				break;
			default:
				// Default case, handles other types (e.g., boolean, null).
				array_push( $type_array, '%s' );
				break;
		}
	}

	return $type_array;
}

/**
 * Check if nicename is for a user.
 * @param string $nicename The nicename to check.
 * @return bool True if nicename is for a user, false otherwise.
 */
function is_nicename_for_user( $nicename ) {
	$user = get_user_by( 'slug', $nicename );
	return ( $user !== false );
}

/**
 * Get role by nicename.
 *
 * @param string $nicename The nicename of the role.
 * @return object|null The role object if found, null otherwise.
 */
function get_role_by_nicename( $nicename ) {
	$role_service = new \MSHMN\Role_Service( array( 'nicename' => $nicename ) );
	$roles        = $role_service->get_results();
	if ( ! empty( $roles ) ) {
		return $roles[0];
	}
	return null;
}