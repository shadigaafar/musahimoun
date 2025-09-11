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
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { store as coreStore } from '@wordpress/core-data';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { isEmpty } from 'lodash';

function ContributorNameEdit({
	context: { index, postType, postId, contributor, contributorsCount },
	attributes: { textAlign, isLink, linkTarget, separator },
	setAttributes,
}) {
	const { name } = contributor || {};

	const { userContributor } = useSelect(
		(select) => {
			const { getEditedEntityRecord, getUser } = select(coreStore);
			const _authorId = getEditedEntityRecord(
				'postType',
				postType,
				postId
			)?.author;

			return {
				userContributor: _authorId ? getUser(_authorId) : null,
			};
		},
		[postType, postId]
	);

	const blockProps = useBlockProps({
		className: classnames('mshmn-block-post-contributor-name', {
			[`has-text-align-${textAlign}`]: textAlign,
		}),
	});

	const displayName =
		name || userContributor?.name || __('Contributor Name', 'musahimoun');

	const authorSuffix = !isEmpty(name) && index !== contributorsCount - 1 && (
		<span>{separator ? separator : __(',', 'musahimoun')}</span>
	);
	const displayContributor = isLink ? (
		<a
			href="#author-pseudo-link"
			onClick={(event) => event.preventDefault()}
			className="mshmn-block-contributor-name__link"
		>
			{displayName}
			{authorSuffix}
		</a>
	) : (
		displayName
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
			<InspectorControls>
				<PanelBody title={__('Link Settings', 'musahimoun')}>
					<ToggleControl
						label={__('Link to Contributor Archive', 'musahimoun')}
						onChange={() => setAttributes({ isLink: !isLink })}
						checked={isLink}
					/>
					{isLink && (
						<ToggleControl
							label={__('Open in New Tab', 'musahimoun')}
							onChange={(value) =>
								setAttributes({
									linkTarget: value ? '_blank' : '_self',
								})
							}
							checked={linkTarget === '_blank'}
						/>
					)}
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>{displayContributor}</div>
		</>
	);
}

export default ContributorNameEdit;
