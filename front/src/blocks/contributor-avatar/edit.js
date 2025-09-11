/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import {
	useBlockProps,
	InspectorControls,
	__experimentalUnitControl as UnitControl,
} from '@wordpress/block-editor';
import {
	PanelBody,
	__experimentalBoxControl as BoxControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import localAvatar from './OIP.jpg';

function edit({ setAttributes, attributes: { width, radius }, contributor }) {
	const { avatar } = contributor || {};

	const blockProps = useBlockProps({
		className: classnames('mshmn-contributor-avatar-block'),
	});

	return (
		<>
			<InspectorControls>
				<PanelBody>
					<UnitControl
						label={__('Width', 'musahimoun')}
						min={0}
						max={150}
						value={width}
						onChange={(value) => setAttributes({ width: value })}
						units={false}
					/>
				</PanelBody>
				<PanelBody title={__('Border', 'musahimoun')}>
					<BoxControl
						label={__('Radius', 'musahimoun')}
						onChange={(value) => setAttributes({ radius: value })}
						values={radius}
					/>
				</PanelBody>
			</InspectorControls>
			<figure {...blockProps}>
				<img
					src={avatar ?? localAvatar}
					width={width}
					style={{
						borderRadius: `${Object.values(radius).join(' ')}`,
					}}
				/>
			</figure>
		</>
	);
}

export default edit;
