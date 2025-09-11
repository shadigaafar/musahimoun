<?php
/**
 * Server-side rendering of the `mshmn/author-query-loop` block.
 *
 * @package Musahimoun
 */

namespace MSHMN\blocks;

/**
 * Renders the `mshmn/author-query-loop` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string Returns the output of the query, structured using the layout defined by the block's inner blocks.
 */
function render_block_author_query_loop( $attributes, $content, $block ) {

	$classnames = 'mshmn-contributor-query-loop-block';

	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classnames ) );

	$contributors = $block->context['roleAssignment']['contributors'];
	$role         = $block->context['roleAssignment']['role'];
	$content      = '';

	if ( ! isset( $contributors ) ) {
		return '';
	}
	foreach ( $contributors as $contributor_index => $contributor ) {

		if ( ! is_array( $contributor ) ) {
			break;
		}

		$block_content = (
			new \WP_Block(
				$block->parsed_block,
				array(
					'postType'    => get_post_type(),
					'postId'      => get_the_ID(),
					'contributor' => array_merge(
						$contributor,
						array(
							'role'             => $role,
							'contributorIndex' => $contributor_index,
						)
					),
				)
			)
		)->render( array( 'dynamic' => false ) );

		$content .= '<li>' . $block_content . '</li>';
	}

	return sprintf(
		'<ul %1$s>%2$s</ul>',
		$wrapper_attributes,
		$content
	);
}

/**
 * Registers the `mshmn/author-query-loop` block on the server.
 */
function register_block_author_query_loop() {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback'   => __NAMESPACE__ . '\\render_block_author_query_loop',
			'skip_inner_blocks' => true,
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_block_author_query_loop' );
