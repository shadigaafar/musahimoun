<?php
/**
 * Custom Options and Settings for Musahimoun Plugin
 *
 * @since 1.1.0
 *
 * @package musahimoun
 */

namespace MSHMN\Menu;

/**
 * Initialize the settings for the Musahimoun plugin.
 */
function settings_init() {
	// Register a setting for the supported post types.
	register_setting(
		'mshmn_general_settings',
		MSHMN_SUPPORTED_POST_TYPES,
		array(
			'default'           => array( 'post' ),
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_array',
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
			),
		)
	);

	// Register a setting for the supported author archives.
	register_setting(
		'mshmn_general_settings',
		MSHMN_SUPPORTED_AUTHOR_ARCHIVE,
		array(
			'default'           => array( 'post' ),
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_array',
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
			),
		)
	);
	// Add a section to the settings page for selecting post types.
	add_settings_section(
		'mshmn_section_post_types',
		__( 'General', 'musahimoun' ),
		__NAMESPACE__ . '\section_post_types_callback',
		'mshmn_general_settings'
	);

	// Add a field to select supported post types.
	add_settings_field(
		'mshmn_field_post_types',
		__( 'Select Post Types', 'musahimoun' ),
		__NAMESPACE__ . '\field_post_types_callback',
		'mshmn_general_settings',
		'mshmn_section_post_types',
		array(
			'class'     => 'mshmn_row',
		)
	);

	// Add a field to select supported author archives.
	add_settings_field(
		'mshmn_field_author_archive',
		__( 'Author Archives Display', 'musahimoun' ),
		__NAMESPACE__ . '\field_author_archive_callback',
		'mshmn_general_settings',
		'mshmn_section_post_types',
		array(
			'class'     => 'mshmn_row',
		)
	);

	// Add migration section and field
	add_settings_section(
		'mshmn_section_migration',
		__( 'Migration', 'musahimoun' ),
		__NAMESPACE__ . '\section_migration_callback',
		'mshmn_general_settings'
	);
	add_settings_field(
		'mshmn_field_migration',
		__( 'Migrate from other plugins', 'musahimoun' ),
		__NAMESPACE__ . '\field_migration_callback',
		'mshmn_general_settings',
		'mshmn_section_migration',
		array(
			'class' => 'mshmn_row',
		)
	);
}
add_action( 'admin_init', __NAMESPACE__ . '\\settings_init', 1 );

/**
 * Section callback function for Post Type Settings.
 *
 * @param array $args The settings array, defining title, id, callback.
 */
function section_post_types_callback( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Select the post types and author archive settings for your site.', 'musahimoun' ); ?></p>
	<?php
}

/**
 * Callback function for the Post Types field.
 */
function field_post_types_callback() {
	$post_types          = get_post_types( array( 'public' => true ), 'objects' );
	$selected_post_types = is_array( get_option( MSHMN_SUPPORTED_POST_TYPES, array() ) ) ? get_option( MSHMN_SUPPORTED_POST_TYPES, array() ) : array();

	foreach ( $post_types as $post_type ) {
		$checked = in_array( $post_type->name, $selected_post_types, true ) ? 'checked' : '';
		?>
		<label for="<?php echo esc_attr( $post_type->name ); ?>">
			<input type="checkbox" name="<?php echo esc_attr( MSHMN_SUPPORTED_POST_TYPES ); ?>[]" id="<?php echo esc_attr( 'mshmn_post_type_' . $post_type->name ); ?>" value="<?php echo esc_attr( $post_type->name ); ?>" <?php echo esc_attr( $checked ); ?>>
			<?php echo esc_html( $post_type->label ); ?>
		</label>
		<br>
		<?php
	}
}

/**
 * Callback function for the Author Archives field.
 */
function field_author_archive_callback() {
	$post_types               = get_post_types( array( 'public' => true ), 'objects' );
	$post_type_author_archive = is_array( get_option( MSHMN_SUPPORTED_AUTHOR_ARCHIVE, array() ) ) ? get_option( MSHMN_SUPPORTED_AUTHOR_ARCHIVE, array() ) : array();

	foreach ( $post_types as $post_type ) {
		$checked = in_array( $post_type->name, $post_type_author_archive, true ) ? 'checked' : '';
		?>
		<label for="<?php echo esc_attr( $post_type->name ); ?>">
			<input type="checkbox" name="<?php echo esc_attr( MSHMN_SUPPORTED_AUTHOR_ARCHIVE ); ?>[]" id="<?php echo esc_attr( 'mshmn_author_archive_' . $post_type->name ); ?>" value="<?php echo esc_attr( $post_type->name ); ?>" <?php echo esc_attr( $checked ); ?>>
			<?php echo esc_html( $post_type->label ); ?>
		</label>
		<br>
		<?php
	}
}

/**
 * Sanitize an array of values.
 *
 * @param array $_array Array to be sanitized.
 * @return array Sanitized array.
 */
function sanitize_array( $_array ) {
	if ( ! is_array( $_array ) || empty( $_array ) ) {
		return array();
	}
	return array_map( 'sanitize_text_field', $_array );
}


/**
 * Render the settings page.
 */
function render_settings_page_cb() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// phpcs:ignore.
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
 * Add custom fields support to selected post types.
 */
function add_custom_fields_support() {
	$selected_post_types = is_array( get_option( MSHMN_SUPPORTED_POST_TYPES, array() ) ) ? get_option( MSHMN_SUPPORTED_POST_TYPES, array() ) : array();
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
		<?php esc_html_e( 'Migrate authors and avatars from other plugins (PublishPress Authors) to Musahimoun. Make sure PublishPress Authors plugin is activated before migration', 'musahimoun' ); ?>
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
        && isset( $_POST['mshmn_migrate_authors'] )
        && isset( $_POST['mshmn_migrate_authors_nonce'] )
        && current_user_can( 'manage_options' )
    ) {
        $nonce = sanitize_text_field( wp_unslash( $_POST['mshmn_migrate_authors_nonce'] ) );

        if ( wp_verify_nonce( $nonce, 'mshmn_migrate_authors_action' ) ) {
            include_once dirname( __DIR__ ) . '/class-migration-handler.php';
            $handler = new \MSHMN\Migration\Migration_Handler();
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

add_action( 'admin_init', __NAMESPACE__ . '\handle_migration_request', 20 );
