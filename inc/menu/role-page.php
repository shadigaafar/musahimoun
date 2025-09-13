<?php
/**
 * Add/Edit Role page.
 *
 * @package Musahimoun
 */

namespace MSHMN\Menu;

use MSHMN\Role_Service;

use function MSHMN\Functions\insert_role;
use function MSHMN\Functions\update_role;

/**
 * Render add/edit role page.
 */
function render_role_add_edit_page_cb() {
	if ( ! current_user_can( 'manage_options' ) ) {
		add_action(
			'admin_notices',
			function () {
				?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'You do not have sufficient permissions to access this page.', 'musahimoun' ); ?></p>
			</div>
				<?php
			}
		);
		return;
	}
	enqeue_script();
	// Initialize the Role_Service class and get all roles for selection.
	$role_service = new Role_Service();
	$roles        = $role_service->get_roles() ?? array();

	// Default values.
	$name              = '';
	$prefix            = '';
	$avatar_visibility = false;
	$icon              = '';

	// Handle form submission.
	if ( isset( $_POST['mshmn_role_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mshmn_role_nonce'] ) ), 'mshmn_role' ) ) {
		$id                = isset( $_POST['role_id'] ) ? intval( $_POST['role_id'] ) : 0;
		$name              = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$prefix            = isset( $_POST['prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['prefix'] ) ) : '';
		$avatar_visibility = isset( $_POST['avatar_visibility'] ) ? (bool) $_POST['avatar_visibility'] : false;
		$icon              = isset( $_POST['icon'] ) && ! empty( $_POST['icon'] ) ? intval( $_POST['icon'] ) : null;
		$set_as_default    = ! empty( $_POST['set_as_default_role'] );

		$data = array(
			'name'              => $name,
			'prefix'            => $prefix,
			'avatar_visibility' => $avatar_visibility,
			'icon'              => $icon,
		);

		if ( $id > 0 ) {
			// Update existing role.
			$data['id'] = $id;
			$result     = update_role( $data, $set_as_default );
		} else {
			// Insert new role.
			$result = insert_role( $data, $set_as_default );
			if ( isset( $result['data']->id ) ) {
				// Reset data after successful submission.
				$id           = $_POST['role_id'] = $result['data']->id;
				$role_service = new Role_Service();
				$roles        = $role_service->get_roles() ?? array();
			}
		}
		echo $result['message'];
	}

	// Enqueue the media uploader script.
	wp_enqueue_media();

	// Render the form.
	// Get current default role
	$current_default_role = get_option( MSHMN_DEFAULT_ROLE_OPTION_KEY, -1 );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form method="post">
			<?php wp_nonce_field( 'mshmn_role', 'mshmn_role_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="role_id"><?php esc_html_e( 'Select Role', 'musahimoun' ); ?></label></th>
						<td>
							<select name="role_id" id="role_id" class="regular-text">
								<option value="0"><?php esc_html_e( 'Add New Role', 'musahimoun' ); ?></option>
								<?php foreach ( $roles as $role ) : ?>
									<?php
									// Only show selected value if form was submitted with valid nonce
									$selected_role_id = 0;
									if ( isset( $_POST['mshmn_role_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mshmn_role_nonce'] ) ), 'mshmn_role' ) ) {
										$selected_role_id = isset( $_POST['role_id'] ) ? intval( $_POST['role_id'] ) : 0;
									}
									?>
									<option value="<?php echo esc_attr( $role->id ); ?>" <?php selected( $selected_role_id, $role->id ); ?>>
										<?php echo esc_html( $role->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<div id="loading" style="display:none; margin-top: 10px;">
								<img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" alt="<?php esc_attr_e( 'Loading...', 'musahimoun' ); ?>"> 
								<?php esc_html_e( 'Loading role data...', 'musahimoun' ); ?>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="name"><?php esc_html_e( 'Name', 'musahimoun' ); ?></label></th>
						<td>
							<input name="name" type="text" id="name" value="<?php echo esc_attr( $name ); ?>" class="regular-text" required>
							<br/>
							<small><?php esc_html_e( 'You can name this role as you like, for example, "Fact-checker"', 'musahimoun' ); ?></small>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="prefix"><?php esc_html_e( 'Prefix', 'musahimoun' ); ?></label></th>
						<td>
							<input name="prefix" type="text" id="prefix" value="<?php echo esc_attr( $prefix ); ?>" class="regular-text">
							<br/>
							<small><?php esc_html_e( "The prefix is the text displayed on the frontend before the person's name to indicate their role. For example, you might use 'Fact-checked by' as a prefix to highlight the fact-checker's contribution.", 'musahimoun' ); ?></small>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Show Avatar', 'musahimoun' ); ?></th>
						<td>
							<label for="avatar_visibility">
								<input name="avatar_visibility" type="checkbox" id="avatar_visibility" value="1" <?php checked( $avatar_visibility, true ); ?>>
								<?php esc_html_e( 'Check this box if you want to show the avatar of contributors under this role.', 'musahimoun' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Set as default role', 'musahimoun' ); ?></th>
						<td>
							<label for="set_as_default_role">
								<input name="set_as_default_role" type="checkbox" id="set_as_default_role" value="1" <?php checked( (int) $current_default_role === (int) ( $_POST['role_id'] ?? -1 ) ); ?>>
								<?php esc_html_e( 'Check this box to make this role the default for new contributors and migrations.', 'musahimoun' ); ?>
							</label>
							<br/>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Role Icon', 'musahimoun' ); ?></th>
						<td>
							<input type="hidden" name="icon" id="icon" value="<?php echo esc_attr( $icon ); ?>">
							<div id="icon-preview">
								<?php if ( $icon ) : ?>
									<img src="<?php echo esc_url( wp_get_attachment_url( $icon ) ); ?>" style="max-width: 100px; max-height: 100px;">
								<?php else : ?>
									<p><?php esc_html_e( 'No icon selected.', 'musahimoun' ); ?></p>
								<?php endif; ?>
							</div>
								<button type="button" id="select-icon-button" class="button"><?php esc_html_e( 'Select Icon', 'musahimoun' ); ?></button>
								<button type="button" id="remove-icon-button" class="button"><?php esc_html_e( 'Remove Icon', 'musahimoun' ); ?></button>
							<br/>
							<small><?php esc_html_e( 'Choose an icon for this role from the media library.', 'musahimoun' ); ?></small>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button( __( 'Save Role', 'musahimoun' ) ); ?>
			<div id="delete-button-container"></div> <!-- Placeholder for delete button -->
		</form>
		<small>
			<?php esc_html_e( 'Roles are useful for categorizing authors based on their specific functions within a post. For instance, if a post features an author and a separate fact checker, you can create distinct roles such as "Author" and "Fact Checker." When editing a post, you can then assign each person to their respective role, providing clarity on their contributions.', 'musahimoun' ); ?>
		</small>
	<?php
}

function enqeue_script() {
	wp_register_script( 'mshmn-role-page-script', MSHMN_PLUGIN_URL . '/admin/js/role-page.js', array( 'jquery' ), wp_rand(), true );
	wp_enqueue_script( 'mshmn-role-page-script' );
	wp_localize_script(
		'mshmn-role-page-script',
		'mshmnRolePageTranslation',
		array(
			'editNonce'                     => esc_html( wp_create_nonce( 'mshmn_role' ) ),
			'alertOnNoIconSelected'         => esc_html__( 'No icon selected.', 'musahimoun' ),
			'alertOnfailToLoadData'         => esc_html__( 'Failed to load role data.', 'musahimoun' ),
			'alertOnErrorLoadingData'       => esc_html__( 'An error occurred while loading role data.', 'musahimoun' ),
			'alertOnDelete'                 => esc_html__( 'You can not delete default role.', 'musahimoun' ),
			'deleteConfirmation'            => esc_html__( 'Are you sure you want to delete this role?', 'musahimoun' ),
			'deleteNonce'                   => esc_html( wp_create_nonce( 'delete_role' ) ),
			'alertOnDeleteSuccess'          => esc_html__( 'Role deleted successfully.', 'musahimoun' ),
			'alertOnDeleteFailed'           => esc_html__( 'Failed to delete role.', 'musahimoun' ),
			'alertOnDeleteError'            => esc_html__( 'An error occurred while deleting the role.', 'musahimoun' ),
			'alertOnNoRoleSelectedToDelete' => esc_html__( 'No role selected to delete.', 'musahimoun' ),
			'deleteButtonLabel'             => esc_html__( 'Delete Role', 'musahimoun' ),
		)
	);
}

/**
 * AJAX callback to get role data.
 */
function get_role_data_callback() {
	if ( ! check_ajax_referer( 'mshmn_role', 'nonce', false ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}

	if ( ! isset( $_POST['role_id'] ) ) {
		wp_send_json_error( 'role_id is not set' );
		return false;
	}
	$role_id = intval( $_POST['role_id'] );

	if ( $role_id > 0 ) {
		$role_service = new Role_Service();
		$role         = $role_service->get_roles( array( 'include' => array( $role_id ) ) )[0];
		$default_role = get_option( MSHMN_DEFAULT_ROLE_OPTION_KEY, -1 );

		if ( isset( $role ) ) {
			wp_send_json_success(
				array(
					'id'                  => $role->id,
					'name'                => $role->name,
					'prefix'              => $role->prefix,
					'avatar_visibility'   => $role->avatar_visibility,
					'icon'                => $role->icon,
					'icon_url'            => $role->icon ? wp_get_attachment_url( $role->icon ) : '',
					'set_as_default_role' => (int) $default_role === (int) $role->id,
				)
			);
		} else {
			wp_send_json_error( 'Role not found.' );
		}
	} else {
		wp_send_json_error( 'Invalid role ID.' );
	}
}
add_action( 'wp_ajax_mshmn_get_role_data', __NAMESPACE__ . '\\get_role_data_callback' );

/**
 * Handle AJAX request for deleting a role.
 */
function delete_role_callback() {
	if ( ! check_ajax_referer( 'delete_role', 'nonce', false ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}

	if ( ! isset( $_POST['role_id'] ) ) {
		wp_send_json_error( 'role_id is not set' );
		return false;
	}
	$role_id = intval( $_POST['role_id'] );

	if ( $role_id > 0 ) {
		$role_service = new Role_Service();
		$deleted      = $role_service->delete( array( 'id' => $role_id ) );

		if ( $deleted ) {
			wp_send_json_success( 'Role deleted successfully.' );
		} else {
			wp_send_json_error( 'Failed to delete role.' );
		}
	} else {
		wp_send_json_error( 'Invalid role ID.' );
	}
}
add_action( 'wp_ajax_mshmn_delete_role', __NAMESPACE__ . '\\delete_role_callback' );
