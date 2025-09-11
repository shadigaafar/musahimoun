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
