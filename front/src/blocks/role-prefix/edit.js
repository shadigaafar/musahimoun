import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { TbIcons } from 'react-icons/tb';
import classnames from 'classnames';

function edit({ attributes, setAttributes, context: { contributor } }) {
	const { role, contributorIndex } = contributor || {};

	const blockProps = useBlockProps({
		className: classnames('mshmn-role-prefix-block'),
	});

	if (!attributes?.isRepeat && contributorIndex > 0) {
		return null;
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Repetition', 'musahimoun')}>
					<ToggleControl
						label={__('Show for each contributor', 'musahimoun')}
						help={__(
							'Should prefix be repeated for each contributor?',
							'musahimoun'
						)}
						onChange={() =>
							setAttributes({
								isRepeat: !attributes?.isRepeat,
							})
						}
						checked={attributes?.isRepeat}
					/>
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				{role?.icon ? (
					<img
						src={role?.icon}
						width={30}
						style={{
							borderRadius: `${Object.values(radius).join(' ')}`,
						}}
					/>
				) : (
					<TbIcons fontSize={15} />
				)}
				<span>{role?.prefix ?? __('Role Prefix:', 'musahimoun')}</span>
			</div>
		</>
	);
}

export default edit;
