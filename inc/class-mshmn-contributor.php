<?php
/**
 * Guest Contributor, Multiple authors class definition
 *
 * @package Mushahimoun
 * @since 1.0
 **/

namespace MSHMN;

use function MSHMN\Functions\get_guests;

if ( ! class_exists( __NAMESPACE__ . '\\Mshmn_Contributor' ) ) :

	/**
	 * Class used to implement guest authors and multiple authors
	 */
	class Mshmn_Contributor {



		/**
		 * The author data
		 *
		 * @var object
		 */
		private $contributor;

		/**
		 * The post types supported
		 *
		 * @var array
		 */
		private $selected_post_types;


		/**
		 * Constructor
		 */
		public function __construct() {

			$this->selected_post_types = is_array( get_option( MSHMN_SUPPORTED_POST_TYPES, array() ) ) ? get_option( MSHMN_SUPPORTED_POST_TYPES, array() ) : array();

			$this->plugin_front_setup();

			foreach ( $this->selected_post_types as $post_type ) {
				add_filter( 'manage_' . $post_type . '_posts_columns', array( $this, 'add_contributors_column' ), 12, 1 );
			}
			add_action( 'manage_posts_custom_column', array( $this, 'display_contributors_column' ), 12, 2 );
			add_action( 'mshmn_after_role_updated', array( $this, 'add_default_role_assignement_meta' ), 12 );
			add_action( 'mshmn_plugin_activated', array( $this, 'add_default_role_assignement_meta' ), 12 );
			add_action( 'init', array( $this, 'remove_author_support' ), 12 );
			add_action( 'init', array( $this, 'register_patterns' ), 12 );
			add_action( 'init', array( $this, 'register_options' ), 12 );
			add_action( 'init', array( $this, 'set_post_authors_meta' ), 20 );
		}

		/**
		 * Sets up the plugin on the front site,
		 * by adding the necessary filters and actions.
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		public function plugin_front_setup() {
			if ( ! is_admin() ) {

				add_filter( 'the_author', array( $this, 'contributor_names' ), 12 );
				add_filter( 'get_the_author_display_name', array( $this, 'contributor_names' ), 12 );
				add_filter( 'get_the_author_user_nicename', array( $this, 'contributor_names' ), 12 );
				add_filter( 'get_the_author_nickname', array( $this, 'contributor_names' ), 12 );

				add_filter( 'the_author_description', array( $this, 'contributor_description' ), 12 );
				add_filter( 'get_the_author_description', array( $this, 'contributor_description' ), 12 );

				add_filter( 'the_author_user_email', array( $this, 'contributor_email' ), 12 );
				add_filter( 'get_the_author_user_email', array( $this, 'contributor_email' ), 12 );

				add_filter( 'the_author_ID', array( $this, 'contributor_id' ), 12 );
				add_filter( 'get_the_author_ID', array( $this, 'contributor_id' ), 12 );

				add_filter( 'posts_pre_query', array( $this, 'filter_posts_by_guests' ), 12, 2 );
				add_action( 'document_title_parts', array( $this, 'filter_document_title' ), 12, 1 );
			}
		}

		/**
		 * Get the author ids inside a query loop
		 */
		public function get_the_contributor_ids() {

			$all_contributor_ids = ! empty( get_post_meta( get_the_ID(), MSHMN_POST_CONTRIBUTORS_META, true ) ) ? explode( ',', get_post_meta( get_the_ID(), MSHMN_POST_CONTRIBUTORS_META, true ) ) : array();

			return $all_contributor_ids;
		}

		/**
		 * Gets all post authors, guest and users.
		 *
		 * @param array $args Arguments.
		 * @return array list of authors
		 */
		public function get_post_contributors( $args = array() ): array {
			$contributors = new Contributor_Service( $args );
			return $contributors->get_results() ?? array();
		}

		/**
		 * Filters author name
		 *
		 * @param string $name author name, passed from WordPress filters for author name.
		 * @return string author name.
		 */
		public function contributor_names( $name = '' ): string {
			if ( is_author() ) {
				return is_object( $this->contributor ) ? $this->contributor->name : $name;
			}

			return $name;
		}

		/**
		 * Filters author description
		 *
		 * @param string $description author's description, passed from WordPress filters for author's description.
		 * @return string author name.
		 */
		public function contributor_description( $description = '' ): string {

			if ( is_author() ) {
				return is_object( $this->contributor ) ? $this->contributor->description : $description;
			}

			return $description;
		}

		/**
		 * Filters author email.
		 *
		 * @param string $email author's email, passed from WordPress filters for author's email.
		 * @return string author name.
		 */
		public function contributor_email( $email = '' ): string {

			if ( is_author() ) {
				return is_object( $this->contributor ) ? $this->contributor->email : $email;
			}

			return $email;
		}

		/**
		 * Filters author ID
		 *
		 * @param string $id author's ID, passed from WordPress filters for author's ID.
		 * @return string author name.
		 */
		public function contributor_id( $id = '' ): string {

			if ( is_author() ) {
				return is_object( $this->contributor ) ? $this->contributor->id : $id;
			}

			return $id;
		}


		/**
		 * Filters post query on author page to display the posts associated the guest author.
		 *
		 * @param array  $posts array of posts, as passed from 'posts_pre_query' filter.
		 * @param object $query object as passed from 'posts_pre_query' filter.
		 */
		public function filter_posts_by_guests( $posts, $query ) {

			if ( ! is_admin() && $query->is_main_query() && is_author() ) {

				$author_nicename          = $query->query_vars['author_name'];
				$post_type_author_archive = get_option( MSHMN_SUPPORTED_AUTHOR_ARCHIVE, array() );

				$query->set( 'post_type', $post_type_author_archive );

				if ( $query->is_author() && $this->is_guest( 'nicename', $author_nicename ) ) {

					$this->contributor = get_guests( array( 'nicename' => $author_nicename ) )[0];

					$query->set(
						'meta_query',
						array(
							array(
								'key'     => MSHMN_POST_CONTRIBUTORS_META,
								'value'   => '[[:<:]]' . $this->contributor->id . '[[:>:]]',
								'compare' => 'REGEXP',
							),
						),
					);
				} elseif ( $query->is_author() ) {
					$user_id = get_user_by( 'slug', $author_nicename )->ID;

					// this step is important, cos some users can be assigned as an author by this plugin,
					// and the don't actually has posts, so they don't have author archive.
					$this->contributor = (object) $this->get_post_contributors( array( 'include' => $user_id ) )[0];
					$query->set( 'author', false );
					$query->set(
						'meta_query',
						array(
							array(
								'key'     => MSHMN_POST_CONTRIBUTORS_META,
								'value'   => '[[:<:]]' . $this->contributor->id . '[[:>:]]',
								'compare' => 'REGEXP',
							),
						),
					);
				}
				$qv                  = $query->query_vars;
				$query_vars_filtered = array_diff_key( $qv, array( 'author_name' => '' ) );

				return get_posts( $query_vars_filtered );
			}
			return $posts;
		}

		/**
		 * Determine if the author is a guest author.
		 *
		 * @param string $field required, the author field.
		 * @param mixed  $value required, the author value.
		 * @return bool  true if guest author, false otherwise.
		 */
		public function is_guest( $field, $value ) {
			$guest = get_guests( array( $field => $value ) );
			if ( ! empty( $guest ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Determine if the user author has any posts
		 *
		 * @param  string|integer $user_id the user id to check against.
		 * @return bool  true if  user has posts, false otherwise.
		 */
		public function is_user_has_posts( $user_id ) {
			$post_types = $this->selected_post_types;
			foreach ( $post_types as $post_type ) {
				$results = new \WP_Query(
					array(
						'author'         => $user_id,
						'post_type'      => $post_type,
						'post_status'    => 'publish',
						'posts_per_page' => 1,
					)
				);
				return count( $results->posts ) !== 0;
			}
		}
		/**
		 * Add post contributors column.
		 *
		 * @param  array $columns An associative array of column headings.
		 * @return array An associative array of column headings.
		 */
		public function add_contributors_column( $columns ) {
			$columns['post_authors'] = __( 'Authors/Contributors', 'musahimoun' );
			return $columns;
		}
		/**
		 * Display post contributors column.
		 *
		 * @param  array $column_name An associative array of column headings.
		 * @param  int   $post_id Post id.
		 * @return void
		 */
		public function display_contributors_column( $column_name, $post_id ) {
			$current_post_type = get_post_type( $post_id );
			if ( 'post_authors' === $column_name && in_array( $current_post_type, $this->selected_post_types, true ) ) {
				$ids = $this->get_the_contributor_ids();
				if ( ! empty( $ids ) ) {
					$authors      = $this->get_post_contributors(
						array(
							'include' => $this->get_the_contributor_ids() ?? array(),
							'fields'  => 'name',
						)
					);
					$author_names = $authors;

					if ( ! empty( $authors ) ) {

						$comma = /* translators: comma */ __( ', ', 'musahimoun' );
						echo esc_html( join( $comma, $author_names ) );
					}
				}
			}
		}

		/**
		 * Add the default role assignement to posts' meta.
		 */
		public function add_default_role_assignement_meta() {

			$default_role = get_option( MSHMN_DEFAULT_ROLE_OPTION_KEY, -1 );

			if ( $default_role === -1 || empty( $default_role ) ) {
				add_action(
					'admin_notices',
					function () {
						echo '<div class="notice notice-warning"><p>' . esc_html__( 'Please set a default role for authors/main-contributors in musahimoun->role page. Default role is required for proper functionality.', 'musahimoun' ) . '</p></div>';
					}
				);
				return;
			}

			$has_set_default = get_option( 'mshmn_has_set_default_role_assignment', false );

			if ( false === $has_set_default ) {

				$post_types = $this->selected_post_types;

				$post_query = new \WP_Query(
					array(
						'post_type'      => $post_types,
						'posts_per_page' => -1,
						'post_status'    => 'any',
						'fields'         => 'ids',
					)
				);

				$post_ids = $post_query->get_posts();

				if ( ! empty( $post_ids ) ) {

					foreach ( $post_ids as  $post_id ) {
						$post_contributors_stirng = get_post_meta( $post_id, MSHMN_POST_CONTRIBUTORS_META, true ) ?? '';
						$post_role_assignments    = is_array( get_post_meta( $post_id, MSHMN_ROLE_ASSINGMENTS_META, true ) ) ? get_post_meta( $post_id, MSHMN_ROLE_ASSINGMENTS_META, true ) : array();

						if ( empty( $post_role_assignments[0]['role'] ) && empty( $post_contributors_stirng ) ) {

							$role_query = new Role_Service();
							$author     = get_post_field( 'post_author', $post_id );

							if ( empty( $author ) || ! is_numeric( $author ) ) {
								continue;
							}

							$post_role_assignments[0]['role'] = (
								$role_query->get_roles(
									array(
										'include' => array( (int) $default_role ),
										'fields'  => array( 'id' ),
									)
								)[0]->id ?? null
							);

							if ( ! is_numeric( $post_role_assignments[0]['role'] ) ) {
								error_log( 'Default role is not valid, please set a valid default role from musahimoun->role page' );
							}

							$post_role_assignments[0]['contributors'] = array( (int) $author );
							update_post_meta( $post_id, MSHMN_POST_CONTRIBUTORS_META, (string) $author );
							update_post_meta( $post_id, MSHMN_ROLE_ASSINGMENTS_META, $post_role_assignments );

						}
					}
					update_option( 'mshmn_has_set_default_role_assignment', true );
				}
			}
		}

		/**
		 * Remove author support.
		 */
		public function remove_author_support() {
			foreach ( $this->selected_post_types as $supported_post_type ) {
				remove_post_type_support( $supported_post_type, 'author' );
			}
		}

		/**
		 * Register block patterns.
		 */
		public function register_patterns() {
			// var_dump('hid shadi');
			// exit;
			register_block_pattern_category(
				'mshmn_block_category',
				array(
					'label' => esc_html__( 'Contributors', 'musahimoun' ),
				)
			);
			register_block_pattern(
				'mshmn/pattern1',
				array(
					'title'       => __( 'Contributor row layout', 'musahimoun' ),
					'description' => _x( 'Basic Ready to use layout for authors/contributors', 'Block pattern description', 'musahimoun' ),
					'content'     => '<!-- wp:mshmn/role-assignement-query-loop {"layout":{"type":"flex"},"style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} --> <!-- wp:mshmn/author-query-loop {"layout":{"type":"default"},"style":{"spacing":{"blockGap":"var:preset|spacing|10"}}} --> <!-- wp:group --> <div class="wp-block-group"><!-- wp:group {"style":{"spacing":{"blockGap":"7px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} --> <div class="wp-block-group"><!-- wp:mshmn/contributor-avatar {"width":"40px"} /--> <!-- wp:mshmn/role-prefix {"style":{"typography":{"fontStyle":"italic","fontWeight":"100"}},"fontSize":"small"} /--> <!-- wp:mshmn/contributor-name {"textAlign":"right","isLink":true,"style":{"typography":{"fontWeight":"500","fontStyle":"normal"}},"fontSize":"small"} /--></div> <!-- /wp:group --></div> <!-- /wp:group --> <!-- /wp:mshmn/author-query-loop --> <!-- /wp:mshmn/role-assignement-query-loop -->',
					'categories'  => array( 'mshmn_block_category' ),
				)
			);
			register_block_pattern(
				'mshmn/pattern2',
				array(
					'title'       => __( 'Contributor Box layout', 'musahimoun' ),
					'description' => _x( 'Basic Ready to use layout for authors/contributors', 'Block pattern description', 'musahimoun' ),
					'content'     => '<!-- wp:mshmn/role-assignement-query-loop --> <!-- wp:mshmn/author-query-loop --> <!-- wp:group {"style":{"border":{"width":"1px","color":"#A4A4A4","radius":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left","orientation":"horizontal","verticalAlignment":"top"}} --> <div class="wp-block-group has-border-color" style="border-color:#A4A4A4;border-width:1px;border-radius:1px;padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20)"><!-- wp:mshmn/contributor-avatar /--> <!-- wp:group {"layout":{"type":"flex","orientation":"vertical"}} --> <div class="wp-block-group"><!-- wp:mshmn/role-prefix {"style":{"elements":{"link":{"color":{"text":"var:preset|color|contrast-2"}}}},"textColor":"contrast-2","fontSize":"small"} /--> <!-- wp:mshmn/contributor-name {"isLink":true,"style":{"typography":{"fontWeight":700,"fontStyle":"normal","letterSpacing":"1px"}}} /--> <!-- wp:mshmn/contributor-biography {"style":{"typography":{"fontStyle":"italic","fontWeight":"100"}},"fontSize":"small"} /--></div> <!-- /wp:group --></div> <!-- /wp:group --> <!-- /wp:mshmn/author-query-loop --> <!-- /wp:mshmn/role-assignement-query-loop -->',
					'categories'  => array( 'mshmn_block_category' ),
				)
			);
		}

		/**
		 * Filter the document title to show the contributor name on author archive pages.
		 *
		 * @param array $title The document title parts.
		 * @return array The modified document title parts.
		 */
		public function filter_document_title( $title ) {
			if ( is_author() ) {
				$title['title'] = ( is_object( $this->contributor ) ? $this->contributor->name : '' );
			}
			return $title;
		}

		/**
		 * Register post meta for authors.
		 */
		public function register_options() {

			register_setting(
				'options',
				MSHMN_DEFAULT_ROLE_OPTION_KEY,
				array(
					'default'           => '',
					'show_in_rest'      => array(
						'schema' => array(
							'type' => 'integer',
						),
					),
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => function ( $request ) {
						if ( $request->get_method() === 'GET' && current_user_can( 'edit_posts' ) ) {
							return true; // any user editor can read
						}
						return current_user_can( 'manage_options' ); // only admins can update
					},
				)
			);
		}

		/**
		 * Todo: delete this method.
		 * Mirgrating.
		 * Set post authors names meta when plugin is initialized.
		 */
		public function set_post_authors_meta() {

			$done = get_option( '_tmp_mshmn_set_post_authors_meta', false );

			if ( ! empty( $done ) || true === $done ) {
				return;
			}

			if ( empty( $this->selected_post_types ) ) {
				return;
			}

			$default_role = get_option( MSHMN_DEFAULT_ROLE_OPTION_KEY, false );

			if ( false === $default_role ) {
				add_action(
					'admin_notices',
					function () {
						echo '<div class="notice notice-warning"><p>' . esc_html__( 'Please set a default role for authors/main-contributors in musahimoun->role page. Default role is required for proper functionality.', 'musahimoun' ) . '</p></div>';
					}
				);
				return;
			}

			$post_ids = get_posts(
				array(
					'post_type'      => $this->selected_post_types,
					'posts_per_page' => -1,
					'post_status'    => 'any',
					'fields'         => 'ids',
				)
			);

			if ( empty( $post_ids ) ) {
				return;
			}

			foreach ( $post_ids as $post_id ) {

				$post_role_assignments = is_array( get_post_meta( $post_id, MSHMN_ROLE_ASSINGMENTS_META, false ) ) ? get_post_meta( $post_id, MSHMN_ROLE_ASSINGMENTS_META, true ) : array();

				if ( empty( $post_role_assignments ) ) {
					return;
				}
				$author_ids = array_reduce(
					$post_role_assignments,
					function ( $carry, $role_assignment ) use ( $default_role ) {
						if ( isset( $role_assignment['role'] ) && (int) $role_assignment['role'] === (int) $default_role ) {
							if ( isset( $role_assignment['contributors'] ) && is_array( $role_assignment['contributors'] ) ) {
								return array_merge( $carry, $role_assignment['contributors'] );
							}
						}
						return $carry;
					},
					array()
				);

				if ( empty( $author_ids ) ) {
					$author_id   = get_post_field( 'post_author', $post_id );
					$author_name = array( get_the_author_meta( 'display_name', $author_id ) );
					$post_type   = get_post_type( $post_id );

					if ( in_array( $post_type, $this->selected_post_types, true ) ) {
						$succuss = update_post_meta( $post_id, MSHMN_POST_AUTHORS_META, implode( ',', $author_name ) );
						if ( true === $succuss ) {
							add_action(
								'admin_notices',
								function () {
									echo '<div class="updated"><p>Updated post meta.</p></div>';
								}
							);
						}
					}
					continue;
				}

				$contributor_service = new Contributor_Service( array( 'include' => $author_ids ) );

				$contributors = $contributor_service->get_results();

				$author_names = isset( $contributors[0] ) && ! empty( $contributors[0]->name ) ? array_column( $contributors, 'name' ) : array();

				if ( empty( $author_names ) ) {
					return;
				}

				$post_type = get_post_type( $post_id );

				if ( in_array( $post_type, $this->selected_post_types, true ) ) {
					$succuss = update_post_meta( $post_id, MSHMN_POST_AUTHORS_META, implode( ',', $author_names ) );
					if ( true === $succuss ) {
						add_action(
							'admin_notices',
							function () {
								echo '<div class="updated"><p>Updated post meta.</p></div>';
							}
						);
					}
				}
			}

			update_option( '_tmp_mshmn_set_post_authors_meta', true );
		}
	}
endif;
