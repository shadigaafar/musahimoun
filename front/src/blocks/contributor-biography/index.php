<?php
/**
 * Server-side rendering of the `mshmn/contributor-biography` block.
 *
 * @package Musahimoun
 */

namespace MSHMN\blocks;

/**
 * Renders the `mshmn/contributor-biography` block on the server.
 *
 * @param  array    $attributes Block attributes.
 * @param  string   $content    Block default content.
 * @param  WP_Block $block      Block instance.
 * @return string Returns the rendered post author biography block.
 */
function render_block_contributor_biography( $attributes, $content, $block ) {
	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}

	$author_id = get_post_field( 'post_author', $block->context['postId'] );
	if ( empty( $author_id ) ) {
		return '';
	}

	$description = $block->context['contributor']['description'] ?? null;

	$author_biography = isset( $description ) ? $description : get_the_author_meta( 'description', $author_id );
	if ( empty( $author_biography ) ) {
		return '';
	}

	$align_class_name   = empty( $attributes['textAlign'] ) ? '' : "has-text-align-{$attributes['textAlign']}";
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $align_class_name ) );

	return sprintf( '<div %1$s>', $wrapper_attributes ) . $author_biography . '</div>';
}

/**
 * Registers the `mshmn/contributor-biography` block on the server.
 */
function register_block_contributor_biography() {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback'             => __NAMESPACE__ . '\\render_block_contributor_biography',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_block_contributor_biography' );
