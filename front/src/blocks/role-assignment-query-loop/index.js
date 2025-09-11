/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import Icon from './icon';

export default registerBlockType('mshmn/role-assignement-query-loop', {
	icon: Icon,
	edit,
	save,
});
