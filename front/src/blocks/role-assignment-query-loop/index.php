<?php
/**
 * Server-side rendering of the `mshmn/role-assignment-query-loop` block.
 *
 * @package Musahimoun
 */

namespace MSHMN\blocks;

use function MSHMN\Functions\get_role_assingments;

/**
 * Renders the `mshmn/role-assignment-query-loop` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string Returns the output of the query, structured using the layout defined by the block's inner blocks.
 */
function render_block_role_assignment_loop( $attributes, $content, $block ) {

	$role_assignments = get_role_assingments();

	$classnames = 'mshmn-role-assignment-query-loop-block';

	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classnames ) );

	$content = '';

	if ( is_array( $role_assignments ) && 0 === count( $role_assignments ) ) {
		return '';
	}

	foreach ( $role_assignments as $role_assignment ) {

		$block_content = (
			new \WP_Block(
				$block->parsed_block,
				array(
					'postType'       => get_post_type(),
					'postId'         => get_the_ID(),
					'roleAssignment' => $role_assignment,
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
 * Registers the `mshmn/role-assignment-query-loop` block on the server.
 */
function register_block_role_assignment_loop() {

	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback'   => __NAMESPACE__ . '\\render_block_role_assignment_loop',
			'skip_inner_blocks' => true,
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_block_role_assignment_loop' );
