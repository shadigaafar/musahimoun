<?php
/**
 *
 * Enqueue store.
 *
 * @package musahimoun
 */

namespace MSHMN\store;

/**
 * Enqueue script.
 */
function enqueue_scripts() {
	$asset = include __DIR__ . '/index.asset.php';
	wp_register_script( 'musahimoun-store-js-file', plugin_dir_url( __FILE__ ) . 'index.js', $asset['dependencies'], $asset['version'], true );
	wp_enqueue_script( 'musahimoun-store-js-file' );
}
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_scripts' );

/**
 * Localize script.
 */	
function localize_script() {
	wp_localize_script(
		'musahimoun-store-js-file',
		'mshmnStore',
		array(
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'currentUserId' => get_current_user_id(),
		)
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\localize_script', 1 );
