/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { postAuthor as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import edit from './edit';
export default registerBlockType('mshmn/contributor-name', {
	icon: icon,
	edit,
	save: () => null,
});
