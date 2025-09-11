import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const ALLOWED_MEDIA_TYPES = ['image'];
function MediaUploader({ onSelect, value, media, btnText }) {
	return (
		<>
			<img
				src={media?.media_details?.sizes?.thumbnail?.source_url}
				width="200"
				alt={media?.alt_text}
			/>
			<br />
			<MediaUploadCheck>
				<MediaUpload
					onSelect={onSelect}
					allowedTypes={ALLOWED_MEDIA_TYPES}
					value={value}
					render={({ open }) => (
						<Button onClick={open}>{btnText}</Button>
					)}
				/>
			</MediaUploadCheck>
		</>
	);
}

export default MediaUploader;
