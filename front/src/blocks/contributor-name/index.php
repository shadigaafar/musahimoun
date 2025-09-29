<?php
/**
 * Server-side rendering of the `mshmn/contributor-name` block.
 *
 * @package Musahimoun
 */

namespace MSHMN\blocks;

/**
 * Renders the `mshmn/contributor-name` block on the server.
 *
 * @param  array    $attributes Block attributes.
 * @param  string   $content    Block default content.
 * @param  WP_Block $block      Block instance.
 * @return string Returns the rendered post author name block.
 */
function render_block_contributor_name( $attributes, $content, $block ) {

	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}

	$author_id = get_post_field( 'post_author', $block->context['postId'] );
	if ( empty( $author_id ) ) {
		return '';
	}

	$name = $block->context['contributor']['name'] ?? null;
	$url  = $block->context['contributor']['url'] ?? null;

	$contributors_names = array();
	$align_class_name   = empty( $attributes['textAlign'] ) ? '' : "has-text-align-{$attributes['textAlign']}";

	if ( ! $name ) {

		$contributors_names = get_the_author_meta( 'display_name', $author_id );
		if ( isset( $attributes['isLink'] ) && $attributes['isLink'] ) {
			$contributors_names = sprintf( '<address><a href="%1$s" rel="author" target="%2$s" class="mshmn-block-contributor-name__link">%3$s</a></address>', get_author_posts_url( $author_id ), esc_attr( $attributes['linkTarget'] ), $contributors_names );
		}
	} elseif ( $name ) {
		if ( isset( $attributes['isLink'] ) && $attributes['isLink'] ) {
			$contributors_names[] = sprintf( '<address><a href="%1$s" rel="author" target="%2$s" class="mshmn-block-contributor-name__link">%3$s</a></address>', esc_url( $url ), esc_attr( $attributes['linkTarget'] ), $name );
		} else {
			$contributors_names[] = $name;
		}
		$contributors_names = implode( '', $contributors_names );

	}

	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $align_class_name . ' mshmn-block-post-contributor-name' ) );

	return sprintf( '<div %1$s>', $wrapper_attributes ) . $contributors_names . '</div>';
}

/**
 * Registers the `mshmn/contributor-name` block on the server.
 */
function register_block_contributor_name() {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'MSHMN\blocks\render_block_contributor_name',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_block_contributor_name' );
