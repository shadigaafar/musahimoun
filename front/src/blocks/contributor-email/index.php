<?php
/**
 * Server-side rendering of the `mshmn/contributor-email` block.
 *
 * @package Musahimoun
 */

namespace MSHMN\blocks;

/**
 * Renders the `mshmn/contributor-email` block on the server.
 *
 * @param  array    $attributes Block attributes.
 * @param  string   $content    Block default content.
 * @param  WP_Block $block      Block instance.
 * @return string Returns the rendered post contributor name block.
 */
function render_block_contributor_email( $attributes, $content, $block ) {
	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}

	$author_id = get_post_field( 'post_author', $block->context['postId'] );

	if ( empty( $author_id ) ) {
		return '';
	}

	$email = $block->context['contributor']['email'] ?? null;

	$contributor_email = isset( $email ) ? $email : get_the_author_meta( 'user_email' );
	$align_class_name  = empty( $attributes['textAlign'] ) ? '' : "has-text-align-{$attributes['textAlign']}";

	if ( ! isset( $contributor_email ) || empty( $contributor_email ) ) {
		return '';
	}

		$contributor_email = sprintf( '<a href="mailto:%1$s" target="%2$s" class="mshmn-contributor-email-block__email"><svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path fill="none" d="M0 0h24v24H0z"></path><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"></path></svg></a>', $contributor_email, '_blank' );

	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $align_class_name ) );

	return sprintf( '<div %1$s>', $wrapper_attributes ) . $contributor_email . '</div>';
}

/**
 * Registers the `mshmn/contributor-email` block on the server.
 */
function register_block_contributor_email() {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback'             => __NAMESPACE__ . '\\render_block_contributor_email',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_block_contributor_email' );
