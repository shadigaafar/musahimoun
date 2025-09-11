<?php
/**
 * Role_Service class definition
 *
 * @since 1.0
 * @package Musahimoun
 */

namespace MSHMN;

use function MSHMN\Functions\get_type_specifiers;

if ( ! class_exists( __NAMESPACE__ . '\\Role_Service' ) ) :
	/**
	 * Class used to implement Role_Service object
	 */
	class Role_Service {
		/**
		 * Role Table Name.
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
		 * The SQL query used to fetch matching roles.
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
		 * Constructor
		 *
		 * @param null|array $query Optional. The query variables.
		 * @param string     $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants.
		 */
		public function __construct( $query = null, $output = OBJECT ) {
			$this->set_table_name();
			$this->prepare_query( $query );
			$this->query( $output );
		}

		/**
		 * Fills in missing query variables with default values.
		 *
		 * @since 1.0
		 * @param array $args Query vars, as passed to `Role_Service`.
		 * @return array Complete query variables with defaults.
		 */
		public static function fill_query_vars( $args ) {
			$defaults = array(
				'field'    => '',
				'prefix'   => '',
				'nicename' => '',
				'include'  => array(),
				'exclude'  => array(),
				'search'   => '',  // Added search parameter.
				'paged'    => null, // Page number (optional).
				'per_page' => null, // Number of items per page (optional).
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

			$qv =& $this->query_vars;
			$qv = $this->fill_query_vars( $qv );

			$allowed_fields    = array( 'id', 'nicename', 'prefix', 'avatar_visibility' );
			$this->query_field = '*';

			if ( in_array( $qv['field'], $allowed_fields, true ) ) {
				$field             = 'id' === $qv['field'] ? 'id' : sanitize_key( $qv['field'] );
				$this->query_field = "$this->table_name.$field";
			}

			$this->query_from    = $wpdb->prepare( 'FROM %i', $this->table_name );
			$this->query_where   = 'WHERE 1=1';
			$this->query_orderby = 'ORDER BY id ASC';

			if ( isset( $qv['prefix'] ) && ! empty( $qv['prefix'] ) ) {
				$prefix             = $qv['prefix'];
				$this->query_where .= $wpdb->prepare( ' AND prefix = %s', $prefix );
			}

			if ( isset( $qv['nicename'] ) && ! empty( $qv['nicename'] ) ) {
				$nicename           = $qv['nicename'];
				$this->query_where .= $wpdb->prepare( ' AND nicename = %s', $nicename );
			}
			if ( isset( $qv['search'] ) && ! empty( $qv['search'] ) ) {
				$search_term        = '%' . $wpdb->esc_like( $qv['search'] ) . '%';
				$this->query_where .= $wpdb->prepare( ' AND prefix LIKE %s', $search_term );
			}

			if ( ! empty( $qv['include'] ) ) {
				$include         = wp_parse_id_list( $qv['include'] );
				$id_placeholders = implode( ', ', array_fill( 0, count( $include ), '%d' ) );
				$prepare_values  = array_merge( array( 'id' ), $include );

                //phpcs:ignore.
				$this->query_where .= $wpdb->prepare( " AND %i IN ($id_placeholders)", $prepare_values );

			} elseif ( ! empty( $qv['exclude'] ) ) {
				$exclude         = wp_parse_id_list( $qv['exclude'] );
				$id_placeholders = implode( ', ', array_fill( 0, count( $exclude ), '%d' ) );
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
		 * @since 1.0
		 * @param string $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants.
		 * @global wpdb $wpdb WordPress database abstraction object.
		 */
		public function query( $output ) {
			global $wpdb;

			$qv =& $this->query_vars;

			if ( null === $this->results ) {
					// Santitized earlier by $wpdb->prepare.
					$this->request = "SELECT $this->query_field  $this->query_from $this->query_where $this->query_orderby $this->query_limit";

				if ( isset( $qv['field'] ) && ! empty( $qv['field'] ) && '*' !== $qv['field'] ) {
					// Santitized earlier by $wpdb->prepare.
                    //phpcs:ignore.
					$this->results = $wpdb->get_col( $this->request );
				} else {
					// Santitized earlier by $wpdb->prepare.
                    //phpcs:ignore.
					$this->results = $wpdb->get_results( $this->request, $output );
				}
			}

			if ( ! $this->results ) {
				$this->results = null;
			}
		}

		/**
		 * Returns the list of roles.
		 *
		 * @since 1.0
		 * @return array
		 */
		public function get_results() {
			return $this->results;
		}

		/**
		 * Set the table name.
		 */
		private function set_table_name() {
			global $wpdb;
			$this->table_name = $wpdb->prefix . 'mshmn_roles';
		}

		/**
		 * Prepare data for insert/update.
		 *
		 * @param array $data Associative array of column names and values.
		 */
		private function prepare_data( $data ) {
			$name     = isset( $data['name'] ) ? $data['name'] : '';
			$nicename = $this->generate_nicename( $name );
			$icon     = $data['icon'] ?? null;
			$prepared = array(
				'name'     => $name,
				'nicename' => $nicename,
				'prefix'   => isset( $data['prefix'] ) ? trim( $data['prefix'] ) : '',
			);

			if ( isset( $icon ) && ! empty( $icon ) && is_integer( $icon ) ) {
				$prepared['icon'] = intval( $icon );
			}

			if ( isset( $data['avatar_visibility'] ) ) {
				$prepared['avatar_visibility'] = intval( $data['avatar_visibility'] );
			}
			$this->format = get_type_specifiers( $prepared );

			return $prepared;
		}

		/**
		 * Insert a new role.
		 *
		 * @param array           $data Associative array of column names and values.
		 * @param string[]|string $format Optional. An array of formats to be mapped to each of the values in $data. If string, that format will be used for all of the values in $data. A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types. Default null.
		 * @return int|false The id of inserted Role, or false on failure.
		 */
		public function insert( $data, $format = null ) {
			global $wpdb;
			//phpcs:ignore.
			$prepared_data = $this->prepare_data( $data );
			$inserted      = $wpdb->insert( $this->table_name, $prepared_data, $format ?? $this->format );
			if ( $inserted ) {
				return $wpdb->insert_id;
			}
			return false;
		}

		/**
		 * Update an existing role.
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
		 * Delete a role.
		 *
		 * @param array           $where Associative array of WHERE conditions.
		 * @param string[]|string $where_format Optional. An array of formats to be mapped to each of the values in $data. If string, that format will be used for all of the values in $data. A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types. Default null.
		 * @return int|false The number of rows deleted, or false on failure.
		 */
		public function delete( $where, $where_format = null ) {
			global $wpdb;
			$id = isset( $where['id'] ) ? (int) $where['id'] : 0;

			if ( 1 === $id ) { // prvent delete default role.
				return false;
			} else {
				//phpcs:ignore.
				return $wpdb->delete( $this->table_name, $where, $where_format );
			}
		}

		/**
		 * Check if role nicename exists in wp_users table and mshmn_roles.
		 *
		 * @param string $nicename the role nicename to check.
		 * @return bool True if the nicename exists, false otherwise.
		 */
		public function nicename_exists( $nicename ) {
			global $wpdb;
			// Check in mshmn_roles table.
            //phpcs:ignore.
			$nicename_exists_in_role = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT nicename FROM %i WHERE nicename = %s',
					$this->table_name,
					$nicename
				)
			);

			if ( ! empty( $nicename_exists_in_role ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Generate unique nicename for role.
		 *
		 * @param string $name The name.
		 * @throws \Exception Error if it generates finite loop.
		 * @return string The generated nicename
		 */
		private function generate_nicename( $name ) {
			$name                = isset( $name ) && ! empty( $name ) ? $name : 'k-role-name';
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
						throw new \Exception( 'generate_nicename created infinite loop' );
					}
					break;
				}
			}
			return $nicename;
		}
	}
endif;
