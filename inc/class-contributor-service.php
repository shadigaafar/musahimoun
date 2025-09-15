<?php
/**
 * Contributor_Service class definition
 *
 * @since 1.0
 * @package musahimoun
 */

namespace MSHMN;

if ( ! class_exists( __NAMESPACE__ . '\\Contributor_Service' ) ) :
	/**
	 * Class used to implement Contributor_Service object
	 */
	class Contributor_Service {

		/**
		 * Query vars, after parsing
		 *
		 * @since 1.0
		 * @var array
		 */
		public $query_vars = array();

		/**
		 * Constructor
		 *
		 * @param null|array $query Optional. The query variables.
		 */
		public function __construct( $query = null ) {
			$this->prepare_query( $query );
		}

		/**
		 * Fills in missing query variables with default values.
		 *
		 * @since 1.0
		 *
		 * @param array $args Query vars, as passed to `Contributor_Service`.
		 * @return array Complete query variables with undefined ones filled in with defaults.
		 */
		public static function fill_query_vars( $args ) {
			$defaults = array(
				'include'  => array(),  // Specific IDs to include (users or guests).
				'exclude'  => array(),  // Specific IDs to exclude (users or guests).
				'search'   => '',       // Search term.
				'paged'    => null,
				'per_page' => null,
				'orderby'  => 'id',
				'order'    => 'ASC',
				'fields'   => 'all', // TODO: FIX THIS, ALLOW MUTLIPLE FIELDS.
				'nicename' => '',  // Specific nicename to search for (users or guests).
			);
			return wp_parse_args( $args, $defaults );
		}

		/**
		 * Prepares the query variables.
		 *
		 * @param array $query Optional. Array of Query parameters.
		 */
		public function prepare_query( $query = array() ) {
			if ( empty( $this->query_vars ) || ! empty( $query ) ) {
				$this->query_vars = $this->fill_query_vars( $query );
			}

			// Ensure that query vars are filled after.
			$qv =& $this->query_vars;
			$qv = $this->fill_query_vars( $qv );
		}

		private function prepare_user_args( $query = array() ) {
			
			$qv = $this->query_vars;

			// Retrieve and limit user IDs. Necessary to get them arranged with $guests.
			$user_args  = array(
				'include'        => $qv['include'],
				'exclude'        => $qv['exclude'],
				'search'         => $qv['search'] ? '*' . $qv['search'] . '*' : '',
				'search_columns' => array( 'user_login', 'user_nicename', 'display_name' ),
				'paged'          => $qv['paged'],
				'number'         => $qv['per_page'], // Get up to per_page users.
				'orderby'        => 'id' === $qv['orderby'] ? 'ID' : $qv['orderby'],
				'order'          => $qv['order'],
				'fields'         => $this->map_to_user_field( $qv['fields'] ),
				'nicename'       => $qv['nicename'],
				'role__in'       => get_option( MSHMN_INCLUDED_USER_ROLES, array( 'author', 'editor', 'administrator' ) ),
			);

			if( empty( $user_args['include'] ) ) {
				unset( $user_args['include'] );
			} else {
				$user_args['include'] = $this->filter_user_ids( (array) $user_args['include'] );
			}

			return $user_args;
		}
		/**
		 * Get all contributors (users and guests) based on query vars.
		 *
		 * @param string $output Optional. Any of ARRAY_A | OBJECT constants. Default OBJECT.
		 * @return array|null Array of results, or null on failure.
		 */
		public function get_results( $output = OBJECT ) {
			
			$qv = $this->query_vars;

			$user_args = $this->prepare_user_args( $qv );

			$guest_args = $qv;

			if ( isset( $qv['fields'] ) ) {
				$guest_args['field'] = 'all' === $qv['fields'] ? '' : $qv['fields'];
			}
			
			$users = array();

			//check if include is set and not empty after filtering. Important for not getting all users if no match.
			if ( isset( $user_args['include'] ) && ! empty($this->filter_user_ids( $user_args['include'] )) ) {
				$user_query = new \WP_User_Query( $user_args );
				$users      = ! empty( $user_query->get_results() ) ? $this->format_users( $user_query->get_results(), $output ) : array();
			}

			$guest_service = new Guest_Service( $guest_args, ARRAY_A );
			
			$guests = $guest_service->get_results();

			if ( ! empty( $guests ) ) {
				foreach ( $guests as $key => $guest ) {
					if ( ! is_array( $guest ) ) {
						break;
					}
					$_guest = array_merge(
						$guest,
						array(
							'id'      => (int) $guest['id'],
							'url'     => get_author_posts_url( $guest['id'], rawurldecode( $guest['nicename'] ) ),
							'avatar'  => ! empty( $guest['avatar'] ) ? wp_get_attachment_image_url( $guest['avatar'] ) : MSHMN_PLUGIN_URL . '\\person.svg',
							'is_user' => false,
						)
					);

					if ( in_array( $output, array( ARRAY_A, ARRAY_N ), true ) ) {
						$guests[ $key ] = (array) $_guest;
					} else {
						$guests[ $key ] = (object) $_guest;
					}
				}
			}
			// TODO : FIX SORTING TO BE THE SAME AS IT WAS GET.
			return array_merge( $users ?? array(), $guests ?? array() );
		}

		/**
		 * Map contributor field to user field.
		 *
		 * @param string $field The field.
		 * @return string User field.
		 */
		public function map_to_user_field( $field ) {
			switch ( $field ) {
				case 'id':
					return 'ID';
				case 'name':
					return 'display_name';
				case 'nicename':
					return 'user_nicename';
				case 'email':
					return 'user_email';
				case 'description':
					return 'user_description';
				default:
					return 'all';
			}
		}

		/**
		 * Filter an array of IDs to return only user IDs.
		 *
		 * @param array $ids Array of IDs to filter.
		 * @return array Array of user IDs.
		 */
		private function filter_user_ids( $ids ) {
			$user_ids = array();

			foreach ( $ids as $id ) {
				if ( is_numeric( $id ) && get_userdata( $id ) ) {
					$user_ids[] = $id;
				}
			}

			return $user_ids;
		}

		/**
		 * Format the user results to match the structure expected by the `Guest_Service` class.
		 *
		 * @param array  $users Array of user objects.
		 * @param string $output Format of the output (OBJECT or ARRAY_A).
		 * @return array Array of formatted results.
		 */
		private function format_users( $users, $output = OBJECT ) {

			if ( ! isset( $users ) ) {
				return array();
			}

			$results = array();

			foreach ( $users as $user ) {
				if ( ! is_object( $user ) ) {
					$results = $users;
					break;
				}
				$formatted = array(
					'id'          => (int) $user->ID,
					'name'        => $user->display_name,
					'email'       => $user->user_email,
					'description' => $user->description,
					'nicename'    => $user->user_nicename,
					'url'         => get_author_posts_url( $user->ID, $user->user_nicename ),
					'avatar'      => get_avatar_url( $user->ID, array( 'size' => 150 ) ),
					'is_user'     => true,
				);

				if ( ARRAY_A === $output ) {
					$results[] = (array) $formatted;
				} else {
					$results[] = (object) $formatted;
				}
			}

			return $results;
		}
	}
endif;
