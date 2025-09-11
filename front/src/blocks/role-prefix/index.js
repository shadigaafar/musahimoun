/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { MdOutlineShortText as icon } from 'react-icons/md';

/**
 * Internal dependencies
 */
import edit from './edit';
export default registerBlockType('mshmn/role-prefix', {
	icon,
	edit,
	save: () => null,
});
