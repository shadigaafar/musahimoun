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
import { MdEmail } from 'react-icons/md';
import { __ } from '@wordpress/i18n';
function edit({
	attributes: { textAlign },
	context: { contributor },
	setAttributes,
}) {
	const { email } = contributor || {};

	const blockProps = useBlockProps({
		className: classnames('mshmn-contributor-email-block', {
			[`has-text-align-${textAlign}`]: textAlign,
		}),
	});

	const displayEmail = email ? (
		<a
			href="#author-pseudo-email"
			onClick={(event) => event.preventDefault()}
			className="mshmn-contributor-email-block__email"
		>
			<MdEmail />
		</a>
	) : (
		<MdEmail />
	);

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

			{displayEmail ? <div {...blockProps}> {displayEmail} </div> : null}
		</>
	);
}

export default edit;
