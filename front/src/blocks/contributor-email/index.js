/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { MdEmail as icon } from 'react-icons/md';

/**
 * Internal dependencies
 */
import edit from './edit';
export default registerBlockType('mshmn/contributor-email', {
	icon: icon,
	edit,
	save: () => null,
});
