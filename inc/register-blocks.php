<?php
/**
 * Register Blocks and require php associated files.
 *
 * @since 1.0
 *
 * @package musahimoun
 */

namespace MSHMN\registering_requiring;

if ( defined( 'ABSPATH' ) ) :

	/**
	 * Require files.
	 */
	function require_files() {
		// Get an array of PHP file paths.
		$block_php_file_paths = glob( MSHMN_PLUGIN_PATH . '/front/dist/blocks/*/*.php' ) ?? array();
		$other_php_file_paths = glob( MSHMN_PLUGIN_PATH . '/front/dist/index.php' ) ?? array();

		$paths = array_merge( $other_php_file_paths, $block_php_file_paths );

		if ( is_array( $paths ) ) {
			// Loop through each path and require the file.
			foreach ( $paths as $path ) {
				include_once $path;
			}
		}
	}
	require_files();

	/**
	 * Creating a new (custom) block category.
	 *
	 * @param   array $categories     List of block categories.
	 * @return  array
	 */
	function new_block_category( $categories ) {
		// Plugin’s egory title and slug.
		$block_category = array(
			'title' => __( 'Musahimoun', 'musahimoun' ),
			'slug'  => 'musahimoun',
		);
		$category_slugs = wp_list_pluck( $categories, 'slug' );

		if ( ! in_array( $block_category['slug'], $category_slugs, true ) ) {
			$categories = array_merge(
				$categories,
				array(
					array(
						'title' => $block_category['title'], // Required.
						'slug'  => $block_category['slug'], // Required.
						'icon'  => 'author', // Slug of a WordPress Dashicon or custom SVG.
					),
				)
			);
		}

		return $categories;
	}
	add_filter( 'block_categories_all', __NAMESPACE__ . '\\new_block_category' );

	/**
	 * Get block name from block.json file.
	 *
	 * @param string $path_to_json The path to block.json file.
	 */
	function read_block_name_from_block_json_file( $path_to_json ) {
		$file = $path_to_json . '/block.json';

		if ( ! file_exists( $file ) ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		$filesystem = new \WP_Filesystem_Direct( false );
		//phpcs:ignore.
		$content =$filesystem->get_contents( $file );

		$data_obj = json_decode( $content );

		return $data_obj->name;
	}

	/**
	 * Register blocks.
	 */
	function register_blocks() {
		$file_list = glob( MSHMN_PLUGIN_PATH . '/front/dist/blocks/*' );

		// Loop through the array that glob returned and register blocks.
		foreach ( $file_list as $path_to_json ) {

			$block_name = read_block_name_from_block_json_file( $path_to_json );

			if ( empty( $block_name ) ) {
				continue;
			}
			$is_registered = \WP_Block_Type_Registry::get_instance()->is_registered( $block_name );
			if ( ! $is_registered ) {
				register_block_type_from_metadata( $path_to_json );
			}
		}
	}
	add_action( 'init', __NAMESPACE__ . '\\register_blocks', 11 );
endif;
