import {
	BlockContextProvider,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useMemo, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

const TEMPLATE = [
	[
		'core/group',
		{},
		[
			[
				'mshmn/contributor-name',
				{ style: { typography: { fontWeight: 700 } } },
			],
			['mshmn/contributor-biography'],
			['core/separator'],
		],
	],
];

function AuthorQueryLoopInnerBlocks() {
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'mshmn-contributor' },
		{ template: TEMPLATE }
	);
	return <li {...innerBlocksProps} />;
}

function edit({ clientId, context: { postType, postId, roleAssignment } }) {
	const { contributors, role, index } = roleAssignment || {};
	const [activeContributorId, setActiveContributorId] = useState(null);
	const blockProps = useBlockProps({
		className: 'mshmn-contributor-query-loop-block',
	});

	const blockContexts = useMemo(
		() =>
			contributors?.map((contributor, contributorIndex) => ({
				postType,
				postId,
				contributor: {
					roleAssignmentIndex: index,
					contributorIndex,
					role,
					contributor,
				},
				_internalId:
					contributor?.id ?? `contributor-${contributorIndex}`,
			})) ?? [],
		[contributors, postType, postId]
	);

	return (
		<ul {...blockProps}>
			{blockContexts.length > 0 ? (
				blockContexts.map((blockContext) => {
					const isActive =
						blockContext.contributor?.id === activeContributorId;
					return (
						<BlockContextProvider
							key={blockContext._internalId}
							value={blockContext}
						>
							<div
								className={`mshmn-contributor-wrapper ${
									isActive ? 'is-active' : ''
								}`}
								onClick={() =>
									setActiveContributorId(
										blockContext.contributor?.id
									)
								}
							>
								<AuthorQueryLoopInnerBlocks />
							</div>
						</BlockContextProvider>
					);
				})
			) : (
				<AuthorQueryLoopInnerBlocks />
			)}
		</ul>
	);
}

export default edit;
