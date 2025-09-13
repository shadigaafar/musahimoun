<?php
/**
 * Migration from author plugins class definition
 *
 * @since 2.3.0
 * @package musahimoun
 */

namespace MSHMN\Migration;

use MSHMN\Functions;

use function MSHMN\Functions\get_contributor_by_nicename;
use function MSHMN\Functions\get_role_by_nicename;

if ( ! class_exists( __NAMESPACE__ . '\\Migration_Handler' ) ) :

	/**
	 * Class used to implement Migration_Handler object
	 */
	class Migration_Handler {

		/**
		 * Constructor
		 */
		public function run_migration() {

			// PublishPress Authors: taxonomy linking to CPT ppma_authors
			if ( $this->is_plugin_active( 'publishpress-authors/publishpress-authors.php' ) ) {
				$this->migrate_publishpress_authors();
			}
		}

		/**
		 * Check if a plugin is active.
		 *
		 * @param string $plugin The plugin path relative to the plugins directory.
		 * @return bool True if the plugin is active, false otherwise.
		 */
		public function is_plugin_active( $plugin ) {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			return function_exists( 'is_plugin_active' ) && \is_plugin_active( $plugin );
		}

		/**
		 * Get the default role id.
		 *
		 * @return int|null Role id or null.
		 */
		private function get_default_role_id() {
			$role_service = new \MSHMN\Role_Service();
			$roles        = $role_service->get_roles();
			return ! empty( $roles ) ? $roles[0]->id : null;
		}

		/**
		 * Insert a new role if not exists by nicename, or update existing one.
		 *
		 * @param array $role Role data.
		 * @return int|false Role id or false on failure.
		 */
		private function insert_new_role( $role ): int|false {

			if ( ! is_array( $role ) ) {
				return false;
			}

			$default = array(
				'name'     => '',
				'nicename' => '',
			);

			$data = wp_parse_args( $role, $default );
			if ( empty( $data['nicename'] ) && empty( $data['name'] ) ) {
				return $this->get_default_role_id();
			}

			$role_service = new \MSHMN\Role_Service();

			$is_nicename_exist = get_role_by_nicename( $data['nicename'] ) ?? false;

			if ( $is_nicename_exist ) {
				$updated = $role_service->update( $data, array( 'id' => $is_nicename_exist->id ) );

				if ( $updated ) {
					return (int) $is_nicename_exist->id;
				}

				return false;

			} else {
				$role_id = $role_service->insert( $data );

				return (int) $role_id;
			}
		}



		/**
		 * Insert or update a contributor (guest or user).
		 *
		 * @param array $data Contributor data.
		 * @return int|null Contributor ID.
		 */
		private function insert_contributor( array $data ) {
			$contributor_service = new \MSHMN\Contributor_Service();
			$guest_service       = new \MSHMN\Guest_Service();

			$contributor_id = null;
			$nicename       = $data['nicename'] ?? '';

			// Try to find existing contributor by nicename.
			$contributor = ! empty( $nicename ) ? get_contributor_by_nicename( $nicename ) : null;

			if ( $contributor ) {
				// Contributor exists → maybe update.
				$contributor_id = $contributor->id;

				// Gather fields that actually changed.
				$update_data = array();
				foreach ( array( 'name', 'description', 'email', 'id', 'avatar', 'nicename' ) as $key ) {
					if ( ! empty( $data[ $key ] ) && ( empty( $contributor->$key ) || $contributor->$key !== $data[ $key ] ) ) {
						$update_data[ $key ] = $data[ $key ];
					}
				}

				if ( ! empty( $update_data ) ) {
					$is_user = Functions\is_nicename_for_user( $nicename );

					if ( $is_user ) {
						// Map guest fields to WP user fields, then update user.
						foreach ( $update_data as $key => $value ) {
							$mapped_key                 = $contributor_service->map_to_user_field( $key );
							$update_data[ $mapped_key ] = $value;
						}
						wp_update_user( $update_data );
					} else {
						// Update guest contributor.
						$guest_service->update( $update_data, array( 'id' => $contributor_id ) );
					}
				}
			} else {
				// Contributor doesn’t exist → insert new one.

				// Build name: prefer full name, else combine first + last.
				$name = ! empty( $data['name'] )
					? $data['name']
					: trim( ( $data['first_name'] ?? '' ) . ' ' . ( $data['last_name'] ?? '' ) );

				$insert_data = array(
					'name'        => $name,
					'nicename'    => $nicename,
					'description' => $data['description'] ?? '',
					'email'       => $data['email'] ?? '',
					'avatar'      => $data['avatar'] ?? '',
				);

				$contributor_id = $guest_service->insert( $insert_data );
			}

			return $contributor_id;
		}



		/**
		 * Update contributor post meta to include the post id.
		 *
		 * @param int $contributor_id Contributor ID.
		 * @param int $post_id Post ID.
		 * @return void
		 */
		private function update_contributor_post_meta( $contributor_id, $post_id ) {
				// Store contributor ID in post meta as a comma-separated string.
			if ( $contributor_id && $post_id ) {
				$existing = get_post_meta( $post_id, MSHMN_POST_CONTRIBUTORS_META, true );
				$ids      = $existing ? explode( ',', $existing ) : array();
				if ( ! in_array( $contributor_id, $ids ) ) {
					$ids[] = $contributor_id;
				}
				update_post_meta( $post_id, MSHMN_POST_CONTRIBUTORS_META, implode( ',', $ids ) );
			}
		}
		private function assign_role_block_to_post( $post_id, $role_assignments ) {
			update_post_meta( $post_id, MSHMN_ROLE_ASSINGMENTS_META, $role_assignments );
		}


		/**
		 * Get all published post IDs.
		 *
		 * @return array Array of post IDs.
		 */
		private function get_all_published_post_ids() {
			$ids = get_posts(
				array(
					'post_status'   => 'publish',
					'post_type'     => 'any',
					'fields'        => 'ids',
					'numberposts'   => -1,
					'no_found_rows' => true,
				)
			);
			return (array) $ids;
		}


		/** ===== PublishPress Authors =====
		 *  Taxonomy (relationship): usually 'author' (kept for CAP compatibility).
		 *  Term meta often contains a pointer to the CPT post id.
		 */
		public function migrate_publishpress_authors(): void {
			$post_ids = $this->get_all_published_post_ids();

			$this->migrate_all_authors();
			$this->migrat_author_categories();

			foreach ( (array) $post_ids as $post_id ) {
				$this->migrate_author_relaitons( $post_id );
			}
		}

		/**
		 * Migrate all authors from the 'author' taxonomy to contributors.
		 *
		 * @return void
		 */
		private function migrate_all_authors(): void {

			$terms_ids = get_terms(
				array(
					'taxonomy'   => 'author',
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);

			if ( ! is_array( $terms_ids ) ) {
				return;
			}

			foreach ( (array) $terms_ids as $author_term_id ) {

				$id          = $author_term_id ?? 0;
				$author_term = get_term( $id, 'author' );
				$name        = $author_term->name ?? '';
				$nicename    = $author_term->slug ?? '';
				$first       = $last = $email = $desc = $avatar = '';
				$user_id     = '';

				$first   = get_term_meta( $id, 'first_name', true );
				$last    = get_term_meta( $id, 'last_name', true );
				$email   = get_term_meta( $id, 'user_email', true );
				$desc    = get_term_meta( $id, 'description', true );
				$user_id = get_term_meta( $id, 'user_id', true );
				$avatar  = get_term_meta( $id, 'avatar', true );

				$data = array(
					'name'        => $name ?: trim( ( $first ?: '' ) . ' ' . ( $last ?: '' ) ),
					'nicename'    => $nicename,
					'first_name'  => $first,
					'last_name'   => $last,
					'description' => $desc,
					'email'       => $email,
				);

				if ( ! empty( $user_id ) ) {
					$data['user_id'] = $user_id;
				}
				if ( ! empty( $avatar ) ) {
					$data['avatar'] = $avatar;
				}

				$this->insert_contributor( $data );

			}
		}

		/**
		 * Migrate author categories to roles.
		 *
		 * @return void
		 */
		private function migrat_author_categories(): void {

			/** @disregard */
			$author_categories = get_ppma_author_categories( array( 'limit' => 200 ) );

			if ( ! is_array( $author_categories ) ) {
				return;
			}

			foreach ( (array) $author_categories as $category ) {
				if ( ! is_array( $category ) || empty( $category ) ) {
					continue;
				}

				$role_name     = $category['category_name'] ?? '';
				$role_nicename = $category['slug'] ?? '';

				$this->insert_new_role(
					array(
						'name'     => $role_name,
						'nicename' => $role_nicename,
					)
				);
			}
		}

		/**
		 * Migrate author relations to role assignments.
		 *
		 * @param int $post_id Post ID.
		 * @return void
		 */
		private function migrate_author_relaitons( $post_id ) {

			if ( ! $post_id ) {
				return;
			}
			/** @disregard */
			$relations = get_ppma_author_relations( array( 'post_id' => $post_id ) );

			if ( ! is_array( $relations ) ) {
				return;
			}

			$role_assignments = array();

			$index = 0;
			foreach ( (array) $relations as $relation ) {
				if ( ! is_array( $relation ) || empty( $relation ) ) {
					continue;
				}

				$role_nicename                      = $relation['category_slug'] ?? '';
				$post_id                            = $relation['post_id'] ?? 0;
				$author_term_id                     = $relation['author_term_id'] ?? 0; // this author id in PlublishPress Authors.
				$author_term                        = get_term( $author_term_id, 'author' ); // this author in PlublishPress Authors.
				$contributor                        = get_contributor_by_nicename( $author_term->slug ?? '' );
				$role                               = get_role_by_nicename( $role_nicename );
				$role_assignments[ $index ]['role'] = is_object( $role ) ? $role->id : null;

				if ( ! empty( $contributor ) && ! empty( $contributor->id ) ) {
					$role_assignments[ $index ]['contributors'][] = $contributor->id;

					$this->update_contributor_post_meta( $contributor->id, $post_id );

				} else {
					$role_assignments[ $index ]['contributors'] = $role_assignments[ $index ]['contributors'] ?? array();
				}
				++$index;
			}

			$this->assign_role_block_to_post( $post_id, $role_assignments );
		}
	}
endif;
