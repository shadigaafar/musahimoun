<?php
/**
 * Sub page that list all contributors in a table.
 *
 * @package musahimoun
 * @since   1.1.0
 */

namespace MSHMN\Menu;

require_once 'edit-page.php';

/**
 * Render the Author List Table on the WordPress admin page.
 */
function render_contributor_list_page_cb() {
	// Check user permissions.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

			if ( isset( $_REQUEST['mshmn_edit_contributor_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['mshmn_edit_contributor_nonce'] ) ), '_mshmn_edit_contributor' ) ) {
		render_contributor_edit_page();
	} else {
		$author_list_table = new \MSHMN\Contributor_List_Table();

		// Prepare the items for display.
		$author_list_table->prepare_items();

		// Render the list table.
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=mshmn-add-new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Guest Contributor', 'musahimoun' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mshmn-roles' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Role', 'musahimoun' ); ?></a>
				<?php
				// Render the search box.
				$author_list_table->search_box( __( 'Search Contributors', 'musahimoun' ), 'mshmn_contributor_search' );

				?>
				<form method="post">
					<?php
						$author_list_table->display();
					?>
				</form>
			<small><?php esc_html_e( '*Contributor is a general term here, it could be a writer, reviewer, proofreader, fact checker, etc.', 'musahimoun' ); ?></small>
		</div>
		<?php

	}
}
