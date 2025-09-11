<?php
/**
 * Server-side rendering of the `mshmn/contributor-avatar` block.
 *
 * @package Musahimoun
 */

namespace MSHMN\blocks;

/**
 * Renders the `mshmn/contributor-avatar` block on the server.
 *
 * @param  array    $attributes Block attributes.
 * @param  string   $content    Block default content.
 * @param  WP_Block $block      Block instance.
 * @return string Returns the rendered post author name block.
 */
function render_block_contributor_avatar( $attributes, $content, $block ) {
	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}

	$contributor_id                = isset( $block->context['contributor']['id'] ) ? $block->context['contributor']['id'] : get_the_author_meta( 'ID' );
	$contributor_name              = $block->context['contributor']['name'] ?? '';
	$contributor_avatar_visibility = isset( $block->context['contributor']['role']['avatar_visibility'] ) ? $block->context['contributor']['role']['avatar_visibility'] : false;

	if ( empty( $contributor_id ) || ! $contributor_avatar_visibility ) {
		return '';
	}

	$avatar_url = $block->context['contributor']['avatar'] ?? MSHMN_PLUGIN_URL . '\\person.svg';

	$contributor_avatar = '';
	if ( isset( $avatar_url ) && $avatar_url ) {
		$wrapper_attributes = get_block_wrapper_attributes();
		$contributor_avatar = sprintf( '<img src="%1$s" alt="%2$s" width="%3$s" class="wp-block-post-author-avatar__img" style="border-radius: %4$s"/>', $avatar_url, $contributor_name, $attributes['width'], implode( ' ', $attributes['radius'] ) );
	}

	if ( empty( $contributor_avatar ) ) {
		return '';
	}

	return sprintf( '<figure %1$s>%2$s</figure>', $wrapper_attributes, $contributor_avatar );
}

/**
 * Registers the `mshmn/contributor-avatar` block on the server.
 */
function register_block_contributor_avatar() {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback'             => __NAMESPACE__ . '\\render_block_contributor_avatar',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_block_contributor_avatar' );
