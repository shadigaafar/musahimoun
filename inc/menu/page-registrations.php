<?php
/**
 * Page Registerations.
 *
 * @package Mshmn
 */

namespace MSHMN\Menu;

add_action( 'admin_menu', __NAMESPACE__ . '\\page_registrations' );

define( 'MSHMN_PLUGIN_CAPABILITY', 'manage_options' );

/**
 * Create submenu.
 */
function page_registrations() {
	add_menu_page(
		__( 'Musahimoun: Contributors', 'musahimoun' ), // Title displayed on the page.
		__( 'Mushahimoun', 'musahimoun' ), // Title displayed in the menu.
		MSHMN_PLUGIN_CAPABILITY, // Capability required to access the page.
		MSHMN_MAIN_MENU_SLUG_NAME, // Unique identifier for the menu slug.
		__NAMESPACE__ . '\\render_contributor_list_page_cb', // Function to call to display the page content.
		'dashicons-edit', // Optional menu icon (leave empty for default).
		20 // Optional menu position (lower numbers appear higher).
	);

	add_submenu_page(
		'mshmn', // Use the parent slug as usual.
		__( 'Add new contributor author', 'musahimoun' ),
		__( 'Add new', 'musahimoun' ),
		MSHMN_PLUGIN_CAPABILITY,
		MSHMN_MAIN_MENU_SLUG_NAME . '-add-new',
		__NAMESPACE__ . '\\render_contributor_edit_page',
		2
	);

	add_submenu_page(
		'mshmn', // Use the parent slug as usual.
		__( 'Contributor\'s Roles In Posts', 'musahimoun' ),
		__( 'Roles', 'musahimoun' ),
		MSHMN_PLUGIN_CAPABILITY,
		MSHMN_MAIN_MENU_SLUG_NAME . '-roles',
		__NAMESPACE__ . '\\render_role_add_edit_page_cb',
		4
	);

	add_submenu_page(
		MSHMN_MAIN_MENU_SLUG_NAME, // Main menu slug (replace with your main menu slug).
		__( 'Settings', 'musahimoun' ), // Submenu page title.
		__( 'Settings', 'musahimoun' ), // Submenu capability (user role that can access).
		MSHMN_PLUGIN_CAPABILITY, // Required capability level.
		MSHMN_MAIN_MENU_SLUG_NAME . '-settings', // Submenu slug (unique identifier).
		__NAMESPACE__ . '\\render_settings_page_cb', // Callback function to display submenu content.
		20
	);
}

/**
 * Filter summenu to make sure that 'musahimoun-add-new' does not appear in admin menu.
 *
 * @param string $submenu_file Submenu file.
 */
function admin_submenu_filter( $submenu_file ) {

	global $plugin_page;

	$hidden_submenus = array(
		'' => true,
	);

			// Select another submenu item to highlight (optional).
		if ( $plugin_page && isset( $hidden_submenus[ $plugin_page ] ) ) {
			$submenu_file = 'mshmn';
		}

		// Hide the submenu.
		foreach ( $hidden_submenus as $submenu => $unused ) {
			remove_submenu_page( 'mshmn', $submenu );
		}

	return $submenu_file;
}
// add_filter( 'submenu_file', __NAMESPACE__ . '\\admin_submenu_filter' );
