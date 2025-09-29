<?php
/**
 * Server-side rendering of the `mshmn/role-prefix` block.
 *
 * @package Musahimoun
 */

namespace MSHMN\blocks;

/**
 * Renders the `mshmn/role-prefix` block on the server.
 *
 * @param  array    $attributes Block attributes.
 * @param  string   $content    Block default content.
 * @param  WP_Block $block      Block instance.
 * @return string Returns the rendered post author name block.
 */
function render_block_role_prefix( $attributes, $content, $block ) {

	if ( ! isset( $block->context['postId'] ) || is_author() ) { // role prefix not needed on author archive pages.
		return '';
	}

	$contributor_id    = isset( $block->context['contributor']['id'] ) ? $block->context['contributor']['id'] : get_the_author_meta( 'ID' );
	$role_name         = isset( $block->context['contributor']['role']['name'] ) ? $block->context['contributor']['role']['name'] : '';
	$role_icon         = isset( $block->context['contributor']['role']['icon'] ) ? $block->context['contributor']['role']['icon'] : '';
	$role_prefix       = isset( $block->context['contributor']['role']['prefix'] ) ? $block->context['contributor']['role']['prefix'] : '';
	$contributor_index = $block->context['contributor']['contributorIndex'] ?? null;
	$is_repeat         = $attributes['isRepeat'];

	if ( empty( $contributor_id ) || ! is_int( $contributor_index ) || ( $contributor_index > 0 && ! $is_repeat ) ) {
		return '';
	}

	$role_icon = $role_icon ?? 'here icon';

	$role_prefix_html = '';
	if ( ! empty( $role_icon ) && ! empty( $role_prefix ) ) {
		$role_prefix_html .= sprintf( '<img src="%s" alt="%s" width="25" />', $role_icon, $role_name );
	}

	if ( ! empty( $role_prefix ) ) {
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'mshmn-role-prefix-block' ) );

		$role_prefix_html .= sprintf( '<span>%s</span>', $role_prefix );
	}

	if ( empty( $role_prefix_html ) ) {
		return '';
	}

	return sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $role_prefix_html );
}

/**
 * Registers the `mshmn/role-prefix` block on the server.
 */
function register_block_role_prefix() {

	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => __NAMESPACE__ . '\\render_block_role_prefix',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_block_role_prefix' );
