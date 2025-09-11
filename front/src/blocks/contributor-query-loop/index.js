/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { BsPeopleFill as icon } from 'react-icons/bs';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
export default registerBlockType('mshmn/author-query-loop', {
	icon,
	edit,
	save,
});
