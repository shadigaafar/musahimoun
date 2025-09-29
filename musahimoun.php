<?php
/**
 * Plugin Name: Musahimoun
 * Plugin URI:
 * Description: This plugin allows you to choose an author, create a guest author or choose multiple authors and contributors..
 * Version: 1.2.3
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: Shadi gaafar
 * Author URI:
 * Text Domain: musahimoun
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @package Musahimoun
 **/

namespace MSHMN;

use MSHMN\Mshmn_Contributor;
use function MSHMN\Functions\get_guests;

if ( defined( 'ABSPATH' ) ) :

	// Set a constant for plugin.
	define( 'MSHMN_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
	define( 'MSHMN_PLUGIN_URL', plugins_url( '', __FILE__ ) );
	define( 'MSHMN_SUPPORTED_POST_TYPES', 'mshmn_post_types' );
	define( 'MSHMN_SUPPORTED_AUTHOR_ARCHIVE', 'mshmn_author_archive_post_types' );
	define( 'MSHMN_ROLE_ASSINGMENTS_META', 'mshmn_role_assignments' );
	define( 'MSHMN_POST_CONTRIBUTORS_META', 'mshmn_all_post_contributor_ids' );
	define( 'MSHMN_POST_AUTHORS_META', 'mshmn_all_post_author_names' );
	define( 'MSHMN_MAIN_MENU_SLUG_NAME', 'mshmn' );
	define( 'MSHMN_DEFAULT_ROLE_OPTION_KEY', 'mshmn_default_role' );
	define( 'MSHMN_INCLUDED_USER_ROLES', 'mshmn_included_user_roles' );



	/**
	 * Highlight var for testing in development;
	 *
	 * @param array  $arr .
	 * @param string $name .
	 */
	function highlight_array( $arr, $name = 'var' ) {

		// phpcs:ignore.
		highlight_string( "<?php\n\$$name =\n" . var_export( $arr, true ) . ";\n?>" );
	}

	register_activation_hook(
		__FILE__,
		function () {
			set_transient( 'musahimoun_activation_notice', true, 30 );
			do_action( 'mshmn_plugin_activated' );
		}
	);
	require MSHMN_PLUGIN_PATH . '/inc/db.php';
	require MSHMN_PLUGIN_PATH . '/inc/class-legacy-compatibility.php';
	require MSHMN_PLUGIN_PATH . '/inc/class-guest-service.php';
	require MSHMN_PLUGIN_PATH . '/inc/class-role-service.php';
	require MSHMN_PLUGIN_PATH . '/inc/class-contributor-service.php';
	require MSHMN_PLUGIN_PATH . '/inc/functions.php';
	require MSHMN_PLUGIN_PATH . '/inc/class-mshmn-contributor.php';
	require MSHMN_PLUGIN_PATH . '/inc/menu/page-registrations.php';
	require MSHMN_PLUGIN_PATH . '/inc/menu/contributor-list-page.php';
	require MSHMN_PLUGIN_PATH . '/inc/menu/settings-page.php';
	require MSHMN_PLUGIN_PATH . '/inc/menu/role-page.php';
	require MSHMN_PLUGIN_PATH . '/inc/register-blocks.php';
	require MSHMN_PLUGIN_PATH . '/inc/rest/contributors-rest.php';
	require MSHMN_PLUGIN_PATH . '/inc/rest/role-rest.php';
	require MSHMN_PLUGIN_PATH . '/inc/rest/guest-author-rest.php';
	require MSHMN_PLUGIN_PATH . '/inc/meta-box.php';
	require MSHMN_PLUGIN_PATH . '/inc/class-contributor-list-table.php';




	/**
	 * Initialize legacy compatibility
	 */
	new Legacy_Compatibility();

	/**
	 * Runs the guest author implementation on init
	 */
	new Mshmn_Contributor();



	/**
	 * Make sure user ID is not booked by guest contributor
	 *
	 * @param string $user_id required, the user id.
	 * @return void .
	 * @throws \Exception .
	 */
	function update_registered_user( $user_id ) {
		global $wpdb;

		$user_table    = $wpdb->prefix . 'users';
		$guest_service = new Guest_Service();

		// check ID availability.
		if ( $guest_service->id_exists( $user_id ) ) {
			$data = array( 'ID' => $guest_service->generate_unique_id() );
			// phpcs:ignore.
			$wpdb->update( $user_table, $data, array( 'ID' => $user_id ), array( '%d' ) );
		}

		// check nicename availability.
		$user_nicename       = get_user_meta( $user_id, 'user_nicename', true );
		$is_nicename_is_used = ! empty( get_guests( array( 'nicename' => $user_nicename ) ) );
		if ( $is_nicename_is_used ) {
			$new_nicename = $guest_service->generate_nicename( $user_nicename );
			$data         = array( 'user_nicename' => $new_nicename );
			// phpcs:ignore.
			$wpdb->update( $user_table, $data, array( 'ID' => $user_id ), array( '%d' ) );
		}
	}
	add_action( 'user_register', __NAMESPACE__ . '\\update_registered_user', 12, 1 );

	/**
	 * Log deleted user id.
	 *
	 * @param int $user_id The deleted user id.
	 */
	function log_deleted_user( $user_id ) {
		$guest_service = new Guest_Service();
		$guest_service->log_deleted_id( $user_id );
	}
	add_action( 'deleted_user', __NAMESPACE__ . '\\log_deleted_user', 12, 1 );


	/**
	 * Checks if the current theme is a block theme and displays a notice if not.
	 *
	 * This function checks if the current WordPress theme is a block theme using
	 * `wp_is_block_theme()`. If the theme is not a block theme, it prepares a notice
	 * message and hooks the `my_plugin_display_notice` function to display it for
	 * admin users.
	 */
	function check_block_theme() {
		if ( ! wp_is_block_theme() ) {

			// Add an admin notice for admin users.
			if ( is_admin() ) {
				add_action( 'admin_notices', __NAMESPACE__ . '\\display_notice' );
			}
		}
	}
	add_action( 'init', __NAMESPACE__ . '\\check_block_theme' );


	/**
	 * Displays an admin notice for users when the current theme is not a block theme.
	 *
	 * This function outputs the HTML for an admin notice.
	 *
	 * This function is hooked to the `admin_notices` action.
	 */
	function display_notice() {
		$notice_text = __( 'This plugin will not work with non-block themes. Please switch to a block theme for full functionality.', 'musahimoun' );

		?>
		<div class="notice notice-error">
			<p><?php echo esc_html( $notice_text ); ?></p>
		</div>
		<?php
	}


	/**
	 * Add default role.
	 */
	function add_default_role() {

		$role_service   = new Role_Service();
		$is_role_exists = $role_service->get_roles() ?? false;

		$is_ar = 'ar' === determine_locale();

		if ( ! $is_role_exists ) {
					$data = array(
						'name'              => $is_ar ? 'كاتب' : __( 'Author', 'musahimoun' ),
						'prefix'            => $is_ar ? 'كَتبه' : __( 'Written by', 'musahimoun' ),
						'conjunction'       => '',
						'avatar_visibility' => true,
						'icon'              => null,
					);
					$role_service->insert( $data );
		}
	}
	add_action( 'mshmn_plugin_activated', __NAMESPACE__ . '\\add_default_role' );


	/**
	 * Notify user to configure the plugin on activation.
	 */
	function on_plugin_activation_notify() {
		if ( get_transient( 'musahimoun_activation_notice' ) ) {
			?>
		<div class="notice notice-info is-dismissible">
			<p><?php esc_html_e( 'Go to Musahimoun → Settings to configure the plugin.', 'musahimoun' ); ?></p>
		</div>
			<?php
			// Remove the transient so it only shows once
			delete_transient( 'musahimoun_activation_notice' );
		}
	}
	add_action( 'admin_notices', __NAMESPACE__ . '\\on_plugin_activation_notify' );
endif;
