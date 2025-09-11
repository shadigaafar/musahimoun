import {
	BlockContextProvider,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { memo, useMemo, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store } from './../../store/index';

const TEMPLATE = [['mshmn/author-query-loop', {}]];

function RoleQueryLoopInnerBlocks() {
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'mshmn-inner-blocks' },
		{
			template: TEMPLATE,
		}
	);
	return <li {...innerBlocksProps}></li>;
}

function RoleQueryLoopPreview({
	blocks,
	isHidden,
	setActiveBlockContextId,
	blockContextId,
}) {
	const style = {
		display: isHidden ? 'none' : undefined,
	};

	const handleClick = () => {
		setActiveBlockContextId(blockContextId);
	};

	return (
		<li
			tabIndex={0}
			role="button"
			onClick={handleClick}
			onKeyDown={handleClick}
			style={style}
		>
			Preview
		</li>
	);
}

const MemoizedRoleQueryLoopPreview = memo(RoleQueryLoopPreview);

function edit({ clientId, context: { postType, postId } }) {
	const [activeBlockContextId, setActiveBlockContextId] = useState();

	const roleAssignments = useSelect((select) => {
		return select(store).getRoleAssignments();
	});

	const blocks = useSelect(
		(select) => select(blockEditorStore).getBlocks(clientId),
		[clientId]
	);

	const blockProps = useBlockProps({
		className: 'mshmn-role-assignment-query-loop-block',
	});

	const blockContexts = useMemo(
		() =>
			roleAssignments?.map((roleAssignment, index) => ({
				postType,
				postId,
				roleAssignment: {
					...roleAssignment,
					index,
				},
				_internalId: roleAssignment?.role?.id ?? `musahimoun-${index}`,
			})) ?? [{}],
		[roleAssignments, postType, postId]
	);

	return (
		<ul {...blockProps}>
			{blockContexts && postType ? (
				blockContexts.map((blockContext) => {
					const isActive =
						blockContext.roleAssignment?.role?.id ===
						(activeBlockContextId ||
							blockContexts[0]?.roleAssignment?.role?.id);
					return (
						<React.Fragment key={blockContext._internalId}>
							<BlockContextProvider value={blockContext}>
								{isActive && <RoleQueryLoopInnerBlocks />}
								<MemoizedRoleQueryLoopPreview
									blocks={blocks}
									blockContextId={blockContext._internalId}
									setActiveBlockContextId={
										setActiveBlockContextId
									}
									isHidden={isActive}
								/>
							</BlockContextProvider>
						</React.Fragment>
					);
				})
			) : (
				<RoleQueryLoopInnerBlocks />
			)}
		</ul>
	);
}

export default edit;
