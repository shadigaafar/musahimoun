/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { BsFillPersonLinesFill as icon } from 'react-icons/bs';

/**
 * Internal dependencies
 */
import edit from './edit';
export default registerBlockType('mshmn/contributor-biography', {
	icon,
	edit,
	save: () => null,
});
