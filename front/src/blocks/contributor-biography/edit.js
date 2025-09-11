/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import {
	AlignmentControl,
	BlockControls,
	useBlockProps,
} from '@wordpress/block-editor';

import { __ } from '@wordpress/i18n';

function ContributorBiographyEdit({
	context: { contributor },
	attributes: { textAlign },
	setAttributes,
}) {
	const { description } = contributor || {};

	const blockProps = useBlockProps({
		className: classnames({
			[`has-text-align-${textAlign}`]: textAlign,
		}),
	});

	const displayAuthorBiography =
		description || __('Contributor Bio', 'musahimoun');

	return (
		<>
			<BlockControls group="block">
				<AlignmentControl
					value={textAlign}
					onChange={(nextAlign) => {
						setAttributes({ textAlign: nextAlign });
					}}
				/>
			</BlockControls>
			<div {...blockProps}> {displayAuthorBiography} </div>
		</>
	);
}

export default ContributorBiographyEdit;
