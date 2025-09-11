<?php
/**
 * Database functions
 *
 * @package Mushahimoun
 */

namespace MSHMN\db;

/**
 * Create guest contributor database table
 */
function create_guest_contributor_table() {

	global $wpdb;
	$charset_collate  = $wpdb->get_charset_collate();
	$table_name       = $wpdb->prefix . 'mshmn_contributors';
	$posts_table_name = $wpdb->prefix . 'posts';

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id bigint(20) NOT NULL,
		name varchar(255) NOT NULL,
		description longtext NOT NULL,
		nicename varchar(255) NOT NULL,
		email varchar(255) NOT NULL,
		avatar bigint(20) UNSIGNED,
        CONSTRAINT mshmn_id_nicename_fk UNIQUE (id, nicename),
		CONSTRAINT mshmn_avatar_fk FOREIGN KEY (avatar) REFERENCES $posts_table_name(ID)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
add_action( 'mshmn_plugin_activated', __NAMESPACE__ . '\\create_guest_contributor_table', 2 );

/**
 * Create custom database table for roles.
 *
 * @package Kuttab
 */
function create_mshmn_role_table() {
	global $wpdb;
	$charset_collate  = $wpdb->get_charset_collate();
	$table_name       = $wpdb->prefix . 'mshmn_roles';
	$posts_table_name = $wpdb->prefix . 'posts';
	$sql              = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
		name     varchar(255) NOT NULL,
        nicename varchar(255) NOT NULL,
        prefix varchar(255) NOT NULL,
		icon bigint(20) UNSIGNED,
        avatar_visibility tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY unique_nicename (nicename),
		CONSTRAINT mshmn_role_icon_fk FOREIGN KEY (icon) REFERENCES $posts_table_name(ID)

    ) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
// Hook the function to run when your theme or plugin is activated.
add_action( 'mshmn_plugin_activated', __NAMESPACE__ . '\\create_mshmn_role_table', 2 );
