<?php
/**
 * Meta box
 *
 * @since 1.0
 * @package Mushahimoun
 */

/**
 * Add custom meta box
 */
function mshmn_meta_box() {
	$screens = get_option( MSHMN_SUPPORTED_POST_TYPES, array() );
	foreach ( $screens as $screen ) {
		add_meta_box(
			'ku_author',
			__( 'Authors/Contributors', 'musahimoun' ),
			'mshmn_meta_box_callback',
			$screen,
			'normal',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'mshmn_meta_box' );

/**
 * Custom meta box callback.
 */
function mshmn_meta_box_callback() {
	wp_nonce_field( basename( __FILE__ ), 'mshmn_meta_box_nonce' ); // Security nonce.
	?>
	<div id="contributor_meta_box">
	
	</div>
	<?php
}
