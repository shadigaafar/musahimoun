/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { BsPersonCircle as icon } from 'react-icons/bs';

/**
 * Internal dependencies
 */
import edit from './edit';
export default registerBlockType('mshmn/contributor-avatar', {
	icon,
	edit,
	save: () => null,
});
