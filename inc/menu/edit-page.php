<?php
/**
 * Edit page.
 *
 * @package Musahimoun
 */

namespace MSHMN\Menu;

use MSHMN\Guest_Service;
use function MSHMN\Functions\get_guests;

/**
 * Enqueu Upload Media Script.
 */
function enqueu_media_script() {
	wp_register_script('mshmn-edit-page-script', MSHMN_PLUGIN_URL . '/admin/js/media-upload.js', array('jquery'), '1.1.1', true);
	wp_enqueue_script('mshmn-edit-page-script');
	wp_localize_script( 'mshmn-edit-page-script', 'mshmnEditPageTranslation',
		array( 
			'uploadTitle'      => esc_html__( 'Choose Avatar', 'musahimoun' ),
			'uploadButtonText' => esc_html__( 'Choose Image', 'musahimoun' ),
		)
	);
}

/**
 * Render edit page.
 */
function render_contributor_edit_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	enqueu_media_script();

	// Get the contributor ID from the URL and sanitize it.
	$contributor_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

	// Initialize the Guest_Service class.
	$contributor_query = new Guest_Service();

	// Fetch the contributor details if an ID is provided.
	if ( $contributor_id > 0 ) {
		$contributor = get_guests( array( 'include' => array( $contributor_id ) ) )[0] ?? null;
	} else {
		$contributor = null;
	}

	// Handle form submission.
			if ( isset( $_POST['mshmn_edit_contributor_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mshmn_edit_contributor_nonce'] ) ), '_mshmn_edit_contributor' ) ) {
		// Sanitize and unslash input data.
		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$avatar_id   = isset( $_POST['avatar_id'] ) && ! empty( $_POST['avatar_id'] ) ? intval( $_POST['avatar_id'] ) : null;

		// Prepare data for update.
		$data = array(
			'name'        => $name,
			'email'       => $email,
			'description' => $description,
			'avatar'      => $avatar_id,
		);

		if ( $contributor_id > 0 ) {
			// Update the existing contributor.
			$updated = $contributor_query->update( $data, array( 'id' => $contributor_id ) );

			if ( $updated ) {
				echo '<div class="updated"><p>' . esc_html__( 'Author updated successfully.', 'musahimoun' ) . '</p></div>';
			} else {
				// If update fails, show error message.
				echo '<div class="error"><p>' . esc_html__( 'Error updating author. Please try again.', 'musahimoun' ) . '</p></div>';
			}
		}else {
			$inserted = $contributor_query->insert( $data );

			if ( $inserted ) {
				// If insertion is successful, show success message.
				echo '<div class="updated"><p>' . esc_html__( 'Author added successfully.', 'musahimoun' ) . '</p></div>';
	
				// Clear form data after successful submission.
				$name        = '';
				$email       = '';
				$description = '';
				$avatar_id   = null;
			} else {
				// If insertion fails, show error message.
				echo '<div class="error"><p>' . esc_html__( 'Error adding author. Please try again.', 'musahimoun' ) . '</p></div>';
			}
		}


		// Refresh the page with the updated data.
		$contributor = get_guests( array( 'include' => array( $contributor_id ) ) )[0] ?? null;
	}

	// Enqueue the media uploader script.
	wp_enqueue_media();

	// Render the form.
	?>
	<div class="wrap">
		<h1><?php echo $contributor_id > 0 ? esc_html__( 'Edit Guest Contributor', 'musahimoun' ) : esc_html( get_admin_page_title() ); ?></h1>
		<form method="post">
			<?php wp_nonce_field( '_mshmn_edit_contributor', 'mshmn_edit_contributor_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="name"><?php esc_html_e( 'Name', 'musahimoun' ); ?></label></th>
						<td><input name="name" type="text" id="name" value="<?php echo esc_attr( $contributor->name ?? '' ); ?>" class="regular-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="email"><?php esc_html_e( 'Email', 'musahimoun' ); ?></label></th>
						<td><input name="email" type="email" id="email" value="<?php echo esc_attr( $contributor->email ?? '' ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row"><label for="description"><?php esc_html_e( 'Description', 'musahimoun' ); ?></label></th>
						<td><textarea name="description" id="description" rows="5" class="large-text"><?php echo esc_textarea( $contributor->description ?? '' ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="avatar"><?php esc_html_e( 'Avatar', 'musahimoun' ); ?></label></th>
						<td>
							<button type="button" class="button" id="upload_avatar_button"><?php esc_html_e( 'Choose Image', 'musahimoun' ); ?></button>
							<input type="hidden" name="avatar_id" id="avatar_id" value="<?php echo esc_attr( $contributor->avatar ?? '' ); ?>">
							<div id="avatar_preview">
								<?php if ( ! empty( $contributor->avatar ) ) : ?>
									<p><?php echo wp_get_attachment_image( $contributor->avatar, 'thumbnail' ); ?></p>
								<?php endif; ?>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button( __( 'Save Author', 'musahimoun' ) ); ?>
		</form>
	</div>
	<?php
}
