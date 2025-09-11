jQuery(document).ready(function ($) {
	var mediaUploader;
	const tranlaitons = mshmnEditPageTranslation;
	$('#upload_avatar_button').click(function (e) {
		e.preventDefault();
		// If the uploader object has already been created, reopen the dialog
		if (mediaUploader) {
			mediaUploader.open();
			return;
		}
		// Extend the wp.media object
		mediaUploader = wp.media.frames.file_frame = wp.media({
			title: tranlaitons.uploadTitle,
			button: {
				text: tranlaitons.uploadButtonText,
			},
			multiple: false, // Set to true to allow multiple files to be selected
		});

		// When a file is selected, grab the ID and set it as the hidden field's value
		mediaUploader.on('select', function () {
			var attachment = mediaUploader
				.state()
				.get('selection')
				.first()
				.toJSON();
			$('#avatar_id').val(attachment.id);
			$('#avatar_preview').html(
				'<p><img src="' +
					attachment.sizes.thumbnail.url +
					'" style="max-width: 100px;"></p>'
			);
		});
		// Open the uploader dialog
		mediaUploader.open();
	});
});
