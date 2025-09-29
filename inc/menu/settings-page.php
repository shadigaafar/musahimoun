<?php
/**
 * Musahimoun Plugin Settings Page
 *
 * @package Musahimoun
 */

namespace MSHMN\Menu;

/**
 * Sanitize array input.
 *
 * @param mixed $input Value to sanitize.
 * @return array Sanitized array.
 */
function sanitize_array( $input ) {
	if ( ! is_array( $input ) || empty( $input ) ) {
		return array();
	}
	return array_map( 'sanitize_text_field', $input );
}

/**
 * Register settings, sections, and fields.
 */
function settings_init() {

	// Supported post types
	register_setting(
		'mshmn_general_settings',
		MSHMN_SUPPORTED_POST_TYPES,
		array(
			'default'           => array( 'post' ),
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_array',
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
		)
	);

	// Supported author archives
	register_setting(
		'mshmn_general_settings',
		MSHMN_SUPPORTED_AUTHOR_ARCHIVE,
		array(
			'default'           => array( 'post' ),
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_array',
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
		)
	);

	// General section
	add_settings_section(
		'mshmn_section_post_types',
		__( 'General', 'musahimoun' ),
		__NAMESPACE__ . '\\section_post_types_callback',
		'mshmn_general_settings'
	);

	// Post types field
	add_settings_field(
		'mshmn_field_post_types',
		__( 'Select Post Types', 'musahimoun' ),
		__NAMESPACE__ . '\\field_post_types_callback',
		'mshmn_general_settings',
		'mshmn_section_post_types'
	);

	// Author archive field
	add_settings_field(
		'mshmn_field_author_archive',
		__( 'Author Archives Display', 'musahimoun' ),
		__NAMESPACE__ . '\\field_author_archive_callback',
		'mshmn_general_settings',
		'mshmn_section_post_types'
	);

	// User roles to include in contributors table
	register_setting(
		'mshmn_general_settings',
		MSHMN_INCLUDED_USER_ROLES,
		array(
			'default'           => array( 'author', 'editor', 'administrator' ),
			'sanitize_callback' => __NAMESPACE__ . '\sanitize_array',
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
		)
	);

	// User roles section
	add_settings_section(
		'mshmn_section_user_roles',
		__( 'User Roles', 'musahimoun' ),
		__NAMESPACE__ . '\section_user_roles_callback',
		'mshmn_general_settings'
	);

	// User roles field
	add_settings_field(
		'mshmn_field_user_roles',
		__( 'Include User Roles as Contributors', 'musahimoun' ),
		__NAMESPACE__ . '\field_user_roles_callback',
		'mshmn_general_settings',
		'mshmn_section_user_roles'
	);

	// Migration section
	add_settings_section(
		'mshmn_section_migration',
		__( 'Migration', 'musahimoun' ),
		__NAMESPACE__ . '\\section_migration_callback',
		'mshmn_general_settings'
	);

	add_settings_field(
		'mshmn_field_migration',
		__( 'Migrate from other plugins', 'musahimoun' ),
		__NAMESPACE__ . '\\field_migration_callback',
		'mshmn_general_settings',
		'mshmn_section_migration'
	);
}
add_action( 'admin_init', __NAMESPACE__ . '\\settings_init' );


/**
 * Render settings page callback.
 */
function render_settings_page_cb() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_GET['settings-updated'] ) ) {
		add_settings_error( 'mshmn_messages', 'mshmn_message', __( 'Settings Saved', 'musahimoun' ), 'updated' );
	}

	settings_errors( 'mshmn_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'mshmn_general_settings' );
			do_settings_sections( 'mshmn_general_settings' );
			submit_button( __( 'Save Settings', 'musahimoun' ) );
			?>
		</form>
	</div>
	<?php
}

/**
 * Section callback for general settings.
 *
 * @param array $args Section args.
 */
function section_post_types_callback( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>">
		<?php esc_html_e( 'Select the post types and author archive settings for your site.', 'musahimoun' ); ?>
	</p>
	<?php
}

/**
 * Field callback for post types.
 */
function field_post_types_callback( $args ) {
	$post_types          = get_post_types( array( 'public' => true ), 'objects' );
	$selected_post_types = (array) get_option( MSHMN_SUPPORTED_POST_TYPES, array() );

	foreach ( $post_types as $post_type ) {
		$checked = in_array( $post_type->name, $selected_post_types, true ) ? 'checked' : '';
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( MSHMN_SUPPORTED_POST_TYPES ); ?>[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php echo esc_attr( $checked ); ?>>
			<?php echo esc_html( $post_type->label ); ?>
		</label><br>
		<?php
	}
}

/**
 * Field callback for author archives.
 */
function field_author_archive_callback() {
	$post_types               = get_post_types( array( 'public' => true ), 'objects' );
	$selected_author_archives = (array) get_option( MSHMN_SUPPORTED_AUTHOR_ARCHIVE, array() );

	foreach ( $post_types as $post_type ) {
		$checked = in_array( $post_type->name, $selected_author_archives, true ) ? 'checked' : '';
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( MSHMN_SUPPORTED_AUTHOR_ARCHIVE ); ?>[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php echo esc_attr( $checked ); ?>>
			<?php echo esc_html( $post_type->label ); ?>
		</label><br>
		<?php
	}
}


/**
 * Section callback for user roles settings.
 *
 * @param array $args Section args.
 */
function section_user_roles_callback( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>">
		<?php esc_html_e( 'Select which real user roles should be included and displayed in the contributors table.', 'musahimoun' ); ?>
	</p>
	<?php
}

/**
 * Field callback for user roles selection.
 */
function field_user_roles_callback() {
	global $wp_roles;
	if ( ! isset( $wp_roles ) ) {
		$wp_roles = wp_roles();
	}
	$all_roles         = $wp_roles->roles;
	$all_roles['none'] = array( 'name' => __( 'User with No Role', 'musahimoun' ) );
	$selected_roles    = (array) get_option( MSHMN_INCLUDED_USER_ROLES, array( 'author', 'editor', 'administrator' ) );

	foreach ( $all_roles as $role_key => $role ) {
		$checked = in_array( $role_key, $selected_roles, true ) ? 'checked' : '';
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( MSHMN_INCLUDED_USER_ROLES ); ?>[]" value="<?php echo esc_attr( $role_key ); ?>" <?php echo esc_attr( $checked ); ?>>
			<?php echo esc_html( $role['name'] ); ?>
		</label><br>
		<?php
	}
}
/**
 * Add support for custom fields to selected post types.
 */
function add_custom_fields_support() {
	$selected_post_types = (array) get_option( MSHMN_SUPPORTED_POST_TYPES, array() );
	foreach ( $selected_post_types as $post_type ) {
		add_post_type_support( $post_type, 'custom-fields' );
	}
}
add_action( 'init', __NAMESPACE__ . '\\add_custom_fields_support' );

/**
 * Migration section callback.
 */
function section_migration_callback( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>">
		<?php esc_html_e( 'Migrate authors and avatars from other plugins (PublishPress Authors) to Musahimoun. Make sure PublishPress Authors plugin is active.', 'musahimoun' ); ?>
	</p>
	<?php
}

/**
 * Migration field callback.
 */
function field_migration_callback() {
	?>
	<form method="post">
		<?php wp_nonce_field( 'mshmn_migrate_authors_action', 'mshmn_migrate_authors_nonce' ); ?>
		<input type="hidden" name="mshmn_migrate_authors" value="1" />
		<?php submit_button( __( 'Migrate Authors & Avatars', 'musahimoun' ), 'secondary', 'submit', false ); ?>
	</form>
	<?php
}

/**
 * Handle migration request.
 */
function handle_migration_request() {
	if (
		is_admin()
		&& isset( $_POST['mshmn_migrate_authors'], $_POST['mshmn_migrate_authors_nonce'] )
		&& current_user_can( 'manage_options' )
	) {
		$nonce = sanitize_text_field( wp_unslash( $_POST['mshmn_migrate_authors_nonce'] ) );

		if ( wp_verify_nonce( $nonce, 'mshmn_migrate_authors_action' ) ) {
			include_once dirname( __DIR__ ) . '/class-migration-handler.php';
			$handler = new \MSHMN\Migration\Migration_Handler();
			if ( ! $handler->is_ready ) {
				return;
			}
			$handler->run_migration();

			add_settings_error(
				'mshmn_messages',
				'mshmn_migration',
				__( 'Migration completed.', 'musahimoun' ),
				'updated'
			);
		}
	}
}
add_action( 'admin_init', __NAMESPACE__ . '\\handle_migration_request', 20 );


/**
 * Plugin activation hook to set default options if they don't exist.
 * For some reason, settings is not saved if no initial value is set.
 */
function add_options_on_plugin_activate() {
	// Only add if it doesn’t exist
	if ( false === get_option( MSHMN_SUPPORTED_POST_TYPES ) ) {
		add_option( MSHMN_SUPPORTED_POST_TYPES, array() );
	}
	if ( false === get_option( MSHMN_SUPPORTED_AUTHOR_ARCHIVE ) ) {
		add_option( MSHMN_SUPPORTED_AUTHOR_ARCHIVE, array() );
	}
	if ( false === get_option( MSHMN_INCLUDED_USER_ROLES ) ) {
		add_option( MSHMN_INCLUDED_USER_ROLES, array( 'author', 'editor', 'administrator' ) );
	}
}
add_action( 'mshmn_plugin_activated', __NAMESPACE__ . '\\add_options_on_plugin_activate' );
