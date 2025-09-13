jQuery(document).ready(function ($) {
	const translation = mshmnRolePageTranslation;
	$('#role_id').change(function () {
		var roleId = $(this).val();
		if (roleId > 0) {
			$('#loading').show(); // Show loading spinner.

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'mshmn_get_role_data',
					role_id: roleId,
					nonce: translation.editNonce,
				},
				success: function (response) {
					$('#loading').hide(); // Hide loading spinner.
					if (response.success) {
						console.log(response.data);
						$('#set_as_default_role').prop(
							'checked',
							response.data.set_as_default_role
						);
						// Populate form fields with the fetched data.
						$('#name').val(response.data.name);
						$('#prefix').val(response.data.prefix);
						$('#avatar_visibility').prop(
							'checked',
							response.data.avatar_visibility
						);
						if (response.data.icon) {
							$('#icon').val(response.data.icon);
							$('#icon-preview').html(
								'<img src="' +
									response.data.icon_url +
									'" style="max-width: 100px; max-height: 100px;">'
							);
						} else {
							$('#icon').val('');
							$('#icon-preview').html(
								`<p>${translation.alertOnNoIconSelected}</p>`
							);
						}
					} else {
						alert(`${translation.alertOnfailToLoadData}`);
					}
				},
				error: function () {
					$('#loading').hide(); // Hide loading spinner.
					alert(`${translation.alertOnErrorLoadingData}`);
				},
			});
		} else {
			// Clear form if adding new role.
			$('#set_as_default_role').prop('checked', false);
			$('#name').val('');
			$('#prefix').val('');
			$('#avatar_visibility').prop('checked', false);
			$('#icon').val('');
			$('#icon-preview').html(
				`<p>${translation.alertOnNoIconSelected}</p>`
			);
		}
	});

	// Media uploader for selecting icon
	var mediaUploader;

	$('#select-icon-button').click(function (e) {
		e.preventDefault();

		// If the media frame already exists, reopen it.
		if (mediaUploader) {
			mediaUploader.open();
			return;
		}

		// Create the media frame.
		mediaUploader = wp.media({
			title: 'Select Icon',
			button: {
				text: 'Use this icon',
			},
			multiple: false, // Set to true to allow multiple files to be selected.
		});

		// When an image is selected, run a callback.
		mediaUploader.on('select', function () {
			var attachment = mediaUploader
				.state()
				.get('selection')
				.first()
				.toJSON();
			$('#icon').val(attachment.id);
			$('#icon-preview').html(
				'<img src="' +
					attachment.url +
					'" style="max-width: 100px; max-height: 100px;">'
			);
		});

		// Open the media frame.
		mediaUploader.open();
	});

	// Remove icon
	$('#remove-icon-button').click(function (e) {
		e.preventDefault();
		$('#icon').val('');
		$('#icon-preview').html(`<p>${translation.alertOnNoIconSelected}</p>`);
	});

	//delete
	function updateDeleteButton() {
		var roleId = $('#role_id').val();
		var buttonContainer = $('#delete-button-container');

		if (roleId > 0) {
			// Inject the delete button if a valid role is selected
			buttonContainer.html(
				`<button type="button" id="delete-role-button" class="button button-secondary">${translation.deleteButtonLabel}</button>`
			);
		} else {
			// Clear the button container if no role is selected
			buttonContainer.html('');
		}
	}

	// Initialize button visibility on page load
	updateDeleteButton();

	// Update button visibility when role changes
	$('#role_id').change(function () {
		updateDeleteButton();
	});

	// Handle delete button click
	$(document).on('click', '#delete-role-button', function (e) {
		e.preventDefault();

		var roleId = $('#role_id').val();
		if (1 === roleId) {
			alert(`${translation.alertOnDelete}`);
		} else if (roleId > 0) {
			if (confirm(`${translation.deleteConfirmation}`)) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'mshmn_delete_role',
						role_id: roleId,
						nonce: translation.deleteNonce,
					},
					success: function (response) {
						if (response.success) {
							alert(`${translation.alertOnDeleteSuccess}`);
							location.reload(); // Reload the page to reflect changes
						} else {
							alert(`${translation.alertOnDeleteFailed}`);
						}
					},
					error: function () {
						alert(`${translation.alertOnDeleteError}`);
					},
				});
			}
		} else {
			alert(`${translation.alertOnNoRoleSelectedToDelete}`);
		}
	});
});
