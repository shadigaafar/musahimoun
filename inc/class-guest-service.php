<?php
/**
 * Guest_Service class definition
 *
 * @since 1.0
 * @package musahimoun
 */

namespace MSHMN;

use Exception;

use function MSHMN\Functions\get_type_specifiers;

if ( ! class_exists( __NAMESPACE__ . '\\Guest_Service' ) ) :
	/**
	 * Class used to implement Guest_Service object
	 *
	 * @property string $table_name
	 */
	class Guest_Service {
		/**
		 * Guest Contributor Table Name.
		 *
		 * @since 1.0
		 * @var string
		 */
		public $table_name;

		/**
		 * Query vars, after parsing
		 *
		 * @since 1.0
		 * @var array
		 */
		public $query_vars = array();

		/**
		 * The SQL query used to fetch matching users.
		 *
		 * @since 1.0
		 * @var string
		 */
		public $request;

		/**
		 * Sql clause
		 *
		 * @var string
		 */
		public $query_field;
		/**
		 * Sql clause
		 *
		 * @var string
		 */
		public $query_from;
		/**
		 * Sql clause
		 *
		 * @var string
		 */
		public $query_where;
		/**
		 * Sql clause
		 *
		 * @var string
		 */
		public $query_orderby;
		/**
		 * Sql clause
		 *
		 * @var string|null
		 */
		public $query_limit;

		/**
		 * Query Results
		 *
		 * @var array|null
		 */
		private $results;

		/**
		 * Format for data.
		 *
		 * @var array|null
		 */
		private $format;

		/**
		 * Deleted log.
		 *
		 * @var array
		 */
		public $deleted_contributors_ids;

		/**
		 * Constructor
		 *
		 * @param null|array $query Optional. The query variables.
		 * @param string     $output Optional. Any of ARRAY_A | OBJECT constants. Default OBJECT.
		 */
		public function __construct( $query = null, $output = OBJECT ) {
			$this->set_table_name();
			$this->set_deleted_log();
			$this->prepare_query( $query );
			$this->query( $output );
		}

		/**
		 * Fills in missing query variables with default values.
		 *
		 * @since 1.0
		 *
		 * @param array $args Query vars, as passed to `Guest_Service`.
		 * @return array Complete query variables with undefined ones filled in with defaults.
		 */
		public static function fill_query_vars( $args ) {
			$defaults = array(
				'field'    => '',
				'nicename' => '',
				'include'  => array(),
				'exclude'  => array(),
				'search'   => '',  // Added search parameter.
				'paged'    => null,      // Page number (optional).
				'per_page' => null,      // Number of items per page (optional).
				'orderby'  => 'id',
				'order'    => 'ASC',
			);
			return wp_parse_args( $args, $defaults );
		}

		/**
		 * Prepares the query variables.
		 *
		 * @param array $query Optional. Array of Query parameters.
		 */
		public function prepare_query( $query = array() ) {
			global $wpdb;
			if ( empty( $this->query_vars ) || ! empty( $query ) ) {
				$this->query_limit = null;
				$this->query_vars  = $this->fill_query_vars( $query );
			}

			// Ensure that query vars are filled after.
			$qv =& $this->query_vars;
			$qv = $this->fill_query_vars( $qv );

			$allowed_fields    = array(
				'id',
				'name',
				'nicename',
				'description',
				'email',
				'url',
			);
			$this->query_field = '*';
			if ( in_array( $qv['field'], $allowed_fields, true ) ) {
				$field             = 'id' === $qv['field'] ? 'id' : sanitize_key( $qv['field'] );
				$this->query_field = "$this->table_name.$field";
			}

			$this->query_from  = $wpdb->prepare( 'FROM %i', $this->table_name );
			$this->query_where = 'WHERE 1=1';

			// Handle the orderby and order parameters.
			$orderby = sanitize_key( $qv['orderby'] );
			$order   = strtoupper( $qv['order'] );
			$order   = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';

			$this->query_orderby = $wpdb->prepare( 'ORDER BY %s %s', $orderby, $order );

			if ( isset( $qv['include'] ) && is_array( $qv['include'] ) ) {
				$include = wp_parse_id_list( $qv['include'] );
			} else {
				$include = false;
			}

			if ( isset( $qv['nicename'] ) && ! empty( $qv['nicename'] ) ) {
				$nicename           = $qv['nicename'];
				$this->query_where .= $wpdb->prepare( ' AND nicename = %s', $nicename );
			}

			if ( isset( $qv['search'] ) && ! empty( $qv['search'] ) ) {
				$search_term        = '%' . $wpdb->esc_like( $qv['search'] ) . '%';
				$this->query_where .= $wpdb->prepare( ' AND name LIKE %s', $search_term );
			}

			if ( ! empty( $include ) ) {
				$id_placeholders = implode( ', ', array_fill( 0, count( $include ), '%d' ) );
				$prepare_values  = array_merge( array( 'id' ), $include );

				//phpcs:ignore.
				$this->query_where .= $wpdb->prepare( " AND %i IN ($id_placeholders)", $prepare_values );

			} elseif ( ! empty( $qv['exclude'] ) ) {
				$id_placeholders = implode( ', ', array_fill( 0, count( $qv['exclude'] ), '%d' ) );
				$prepare_values  = array_merge( array( 'id' ), $qv['exclude'] );
				//phpcs:ignore.
				$this->query_where .= $wpdb->prepare( " AND %i NOT IN ($id_placeholders)", $prepare_values );
			}

			// Handle Pagination if pagination parameters are set.
			$paged    = intval( $qv['paged'] );
			$per_page = intval( $qv['per_page'] );

			if ( ! empty( $paged ) && ! empty( $per_page ) ) {
				if ( $paged < 1 ) {
					$paged = 1;
				}
				$offset            = ( $paged - 1 ) * $per_page;
				$this->query_limit = $wpdb->prepare( 'LIMIT %d OFFSET %d', $per_page, $offset );
			}
		}

		/**
		 * Executes the query, with the current variables.
		 *
		 * @param string $output Optional. Any of ARRAY_A | OBJECT constants. Default OBJECT.
		 * @since 1.0
		 * @global wpdb $wpdb WordPress database abstraction object.
		 */
		public function query( $output = OBJECT ) {
			global $wpdb;

			$data    = wp_cache_get( 'musahimoun-cache-guests', 'musahimoun-cache' );
			$request = wp_cache_get( 'musahimoun-cache-request', 'musahimoun-cache' );

			$qv =& $this->query_vars;

			if ( null === $this->results ) {
				// Santitized earlier by $wpdb->prepare.
				$this->request = "SELECT $this->query_field  $this->query_from $this->query_where $this->query_orderby $this->query_limit";

				if ( $request !== $this->request || ! $data ) {
					if ( isset( $qv['field'] ) && ! empty( $qv['field'] ) && '*' !== $qv['field'] ) {
						// Sanitized earlier by $wpdb->prepare.
						// phpcs:ignore
						$this->results = $wpdb->get_col( $this->request );
					} else {
						// Sanitized earlier by $wpdb->prepare.
						// phpcs:ignore.
						$this->results = $wpdb->get_results( $this->request, $output );
					}
					if ( ! $this->results ) {
						$this->results = null;
					}
					wp_cache_set( 'musahimoun-cache-guests', $this->results, 'musahimoun-cache', 3600 );
					wp_cache_set( 'musahimoun-cache-request', $this->results, 'musahimoun-cache', 3600 );
				} else {
					$this->results = $data;
				}
			}
		}

		/**
		 * Returns the list of guests.
		 *
		 * @since 1.0
		 * @return array
		 */
		public function get_results() {
			return $this->results;
		}

		/**
		 * Prepare data for insert/update.
		 *
		 * @param array  $data Associative array of column names and values.
		 * @param string $for_type 'insert' | 'update'. Default 'insert'.
		 */
		private function prepare_data( $data, $for_type = 'insert' ) {
			$id       = $this->generate_unique_id();
			$nicename = isset( $data['nicename'] ) && ! $this->nicename_exists( $data['nicename'] ) ? $data['nicename'] :  $this->generate_nicename( $data['name'] );

			$prepared = array(
				'name'        => trim( $data ['name'] ),
				'email'       => isset( $data['email'] ) ? trim( $data['email'] ) : '',
				'description' => isset( $data['description'] ) ? $data['description'] : '',
				'nicename'    => $nicename,
			);

			if ( 'insert' === $for_type ) {
				$prepared = array_merge( array( 'id' => $id ), $prepared );
			}
			if ( isset( $data['avatar'] ) && ! empty( $data['avatar'] ) ) {
				$prepared['avatar'] = intval( $data['avatar'] );
			}

			$this->format = get_type_specifiers( $prepared );

			return $prepared;
		}

		/**
		 * Insert a new guest contributor.
		 *
		 * @param array           $data Associative array of column names and values.
		 * @param string[]|string $format Optional. An array of formats to be mapped to each of the values in $data. If string, that format will be used for all of the values in $data. A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types. Default null.
		 * @return int|false The ID of the guest author inserted, or false on failure.
		 */
		public function insert( $data, $format = null ) {
			global $wpdb;
			$prepared_data = $this->prepare_data( $data );
			$inserted = $wpdb->insert( $this->table_name, $prepared_data, $format ?? $this->format );
			if ( $inserted ) {
				return $prepared_data['id'];
			}
			return false;
		}

		/**
		 * Update an existing guest contributor.
		 *
		 * @param array           $data Associative array of column names and values.
		 * @param array           $where Associative array of WHERE conditions.
		 * @param string[]|string $format Optional. An array of formats to be mapped to each of the values in $data. If string, that format will be used for all of the values in $data. A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types. Default null.
		 * @return int|false The number of rows updated, or false on failure.
		 */
		public function update( $data, $where, $format = null ) {
			global $wpdb;
			//phpcs:ignore.
			return $wpdb->update( $this->table_name, $data, $where, $format ?? $this->format );
		}

		/**
		 * Delete a guest contributor.
		 *
		 * @param array           $where Associative array of WHERE conditions.
		 * @param string[]|string $where_format Optional. An array of formats to be mapped to each of the values in $data. If string, that format will be used for all of the values in $data. A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types. Default null.
		 * @return int|false The number of rows deleted, or false on failure.
		 */
		public function delete( $where, $where_format = null ) {
			global $wpdb;
			$id = $where['id'] ?? ''; // TODO : EXPAND THIS MORE, IF WHERE NOT AN ID.
			//phpcs:ignore.
			$is_deleted =  $wpdb->delete( $this->table_name, $where, $where_format );

			if ( $is_deleted ) {
				$this->log_deleted_id( $id );
			}

			return $is_deleted;
		}

		/**
		 * Delete a guest contributor.
		 *
		 * @param int $id The id of the item to be delelted.
		 * @throws \Exception Error if it generates finite loop.
		 */
		public function log_deleted_id( $id ) {
			$id         = is_int( $id ) ? array( $id ) : array();
			$prev       = $this->deleted_contributors_ids ?? array();
			$is_updated = update_option( 'musahimoun-deleted-contributors-ids', array( ...$prev, ...$id ) );

			if ( ! $is_updated && WP_DEBUG ) {
				throw new Exception( 'Could not update contributors deleted ids' );
			}
		}

		/**
		 * Set Deleted log.
		 */
		private function set_deleted_log() {
			$this->deleted_contributors_ids = get_option( 'musahimoun-deleted-contributors-ids', array() ) ?? array();
		}

		/**
		 * Set the table name.
		 */
		private function set_table_name() {
			global $wpdb;
			$this->table_name = $wpdb->prefix . 'mshmn_contributors';
		}

		/**
		 * Check if guest contributor nicename exists in wp_user table and wp_mshmn_contributors
		 *
		 * @param string $nicename the author nicename to check.
		 */
		public function nicename_exists( $nicename ) {
			global $wpdb;
			// phpcs:ignore
			$nicename = $wpdb->get_row( $wpdb->prepare( "SELECT nicename FROM %i WHERE nicename = %s", $this->table_name, $nicename ) );
			$user     = get_users( array( 'nicename' => $nicename ) );
			if ( ! empty( $nicename ) || ! empty( $user ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Generate unique nicename for guest contributor
		 *
		 * @param string $guest_name the author name to create nicename from.
		 * @throws \Exception Error if it generates finite loop.
		 * @return string The generated nicename
		 */
		public function generate_nicename( $guest_name ) {
			$name                = isset( $guest_name ) && ! empty( $guest_name ) ? $guest_name : 'k-new-contributor';
			$trimed_name         = sanitize_title( trim( $name ) );
			$name_lower_case     = mb_strtolower( $trimed_name );
			$name_spaces_to_dash = mb_ereg_replace( ' ', '-', $name_lower_case );
			$nicename            = rawurldecode( $name_spaces_to_dash );
			$i                   = 1;

			while ( $this->nicename_exists( $nicename ) ) {
				++$i;
				$nicename = $nicename . '-' . $i;
				if ( $i > 2000 ) {
					if ( WP_DEBUG ) {
						throw new \Exception( 'generate_nicename created infinite loop / ولدت الدالة generate_nicename حلقة لا متناهية' );
					}
					break;
				}
			}
			return $nicename;
		}

		/**
		 * Generate unique id for guest contributor
		 *
		 * @throws \Exception Error if it generates finite loop.
		 * @return int The generated ID
		 */
		public function generate_unique_id() {
			$id = absint( $this->guest_author_table_count() ) + 1;
			while ( $this->id_exists( $id ) || in_array( $id, $this->deleted_contributors_ids, true ) ) {
				++$id;

				if ( $id > 2000 ) {
					if ( WP_DEBUG ) {
						throw new \Exception( 'generate_unique_id created infinite loop / ولدت الدالة generate_unique_id حلقة لا متناهية' );
					}
					break;
				}
			}

			return $id;
		}

		/**
		 * Count number of records in guest contributor table in database
		 */
		public function guest_author_table_count() {
			global $wpdb;
			$table_name  = $this->table_name;
			$count_query = $wpdb->prepare( 'SELECT count(*) FROM %i', $table_name );
			// phpcs:ignore
			$num = $wpdb->get_var( $count_query );

			if ( empty( $num ) ) {
				return 0;
			}
			return absint( $num );
		}

		/**
		 * Check if guest contributor id exists in wp_user table and wp_mshmn_contributors
		 *
		 * @param int $id the author id to check against.
		 */
		public function id_exists( $id ) {
			global $wpdb;
			// phpcs:ignore
			$guest_id = $wpdb->get_row( $wpdb->prepare( 'SELECT id FROM %i WHERE id = %d', $this->table_name, absint( $id ) ), 'ARRAY_A' );
			$user_id  = get_users( array( 'include' => array( absint( $id ) ) ) );

			if ( ! empty( $guest_id ) || ! empty( $user_id ) ) {
				return true;
			} else {
				return false;
			}
		}
	}
endif;
