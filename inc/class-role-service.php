<?php
/**
 * Role and Role_Service Refactored.
 *
 * @package Musahimoun
 */

namespace MSHMN;

use Exception;
use wpdb;

use function MSHMN\Functions\get_type_specifiers;

if ( ! class_exists( __NAMESPACE__ . '\\Role' ) || ! class_exists( __NAMESPACE__ . '\\Role_Service' ) || ! class_exists( __NAMESPACE__ . '\\Concrete_Role' ) ) :


	/**
	 * Abstract Role class (data only).
	 */
	abstract class Role {
		public int $id;
		public string $name;
		public string $prefix;
		public bool $avatar_visibility;
		public int $icon;
		public string $nicename;

		public function __construct(
			int $id,
			string $name,
			string $prefix,
			bool $avatar_visibility,
			int $icon,
			string $nicename
		) {
			$this->id                = $id;
			$this->name              = $name;
			$this->prefix            = $prefix;
			$this->avatar_visibility = $avatar_visibility;
			$this->icon              = $icon;
			$this->nicename          = $nicename;
		}

		abstract public function get_permissions(): array;
	}

	/**
	 * Concrete Role Example.
	 */
	class Concrete_Role extends Role {
		public function get_permissions(): array {
			return array( 'read' );
		}
	}

	/**
	 * Service / Repository class for Roles.
	 */
	class Role_Service {
		private wpdb $db;
		private string $table_name;
		private array $results        = array();
		private string $output_format = OBJECT;
		private string $query         = '';

		public function __construct() {
			global $wpdb;
			$this->db         = $wpdb;
			$this->table_name = $this->db->prefix . 'mshmn_roles';
		}

		/**
		 * Fetch roles from DB.
		 *
		 * @param array  $args Query parameters.
		 * @param string $output Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K.
		 * @return Role[]
		 */
		public function get_roles( array $args = array(), string $output = OBJECT ): array {
			$this->output_format = $output;

			$args = $this->fill_query_vars( $args );

			$query  = $this->build_select_clause( $args );
			$query .= $this->db->prepare( ' FROM %i WHERE 1=1', $this->table_name );
			$query .= $this->build_where_clause( $args );
			$query .= $this->build_orderby_clause( $args );
			$query .= $this->build_limit_clause( $args );

			$this->query = $query;

			$rows = $this->db->get_results( $query, $output );
			if ( empty( $rows ) ) {
				return array();
			}

			if ( isset( $args['fields'] ) && count( $args['fields'] ) === 1 && in_array( $args['fields'][0], array( 'id', 'name', 'prefix', 'nicename', 'avatar_visibility', 'icon' ), true ) ) {
				return $rows;
			}
			return array_map( fn( $row ) => $this->map_row_to_role( (array) $row ), $rows );
		}

		/**
		 * Fill default query args.
		 */
		private function fill_query_vars( array $args ): array {
			$defaults = array(
				'fields'   => array(),
				'prefix'   => '',
				'nicename' => '',
				'include'  => array(),
				'exclude'  => array(),
				'search'   => '',
				'paged'    => 1,
				'per_page' => 0,
			);

			return wp_parse_args( $args, $defaults );
		}

		private function build_select_clause( array $args ): string {
			if ( empty( $args['fields'] ) ) {
				return 'SELECT *';
			}

			$valid_fields = array( 'id', 'name', 'prefix', 'nicename', 'avatar_visibility', 'icon' );
			$fields       = array_filter( $args['fields'], fn( $field ) => in_array( $field, $valid_fields, true ) );
			if ( empty( $fields ) ) {
				return 'SELECT *';
			}

			$placeholders = implode( ',', array_fill( 0, count( $fields ), '%i' ) );

			return $this->db->prepare( "SELECT $placeholders", ...$args['fields'] );
		}

		private function build_where_clause( array $args ): string {
			$clauses = array();

			if ( ! empty( $args['prefix'] ) ) {
				$clauses[] = $this->db->prepare( 'prefix = %s', $args['prefix'] );
			}

			if ( ! empty( $args['nicename'] ) ) {
				$clauses[] = $this->db->prepare( 'nicename = %s', $args['nicename'] );
			}

			if ( ! empty( $args['search'] ) ) {
				$search    = '%' . $this->db->esc_like( $args['search'] ) . '%';
				$clauses[] = $this->db->prepare( 'prefix LIKE %s', $search );
			}

			if ( ! empty( $args['include'] ) ) {
				$ids          = wp_parse_id_list( $args['include'] );
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				$clauses[]    = $this->db->prepare( "id IN ($placeholders)", ...$ids );
			} elseif ( ! empty( $args['exclude'] ) ) {
				$ids          = wp_parse_id_list( $args['exclude'] );
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				$clauses[]    = $this->db->prepare( "id NOT IN ($placeholders)", ...$ids );
			}

			return ! empty( $clauses ) ? ' AND ' . implode( ' AND ', $clauses ) : '';
		}

		private function build_orderby_clause( array $args ): string {
			return ' ORDER BY id ASC';
		}

		private function build_limit_clause( array $args ): string {
			$paged    = max( 1, (int) $args['paged'] );
			$per_page = max( 0, (int) $args['per_page'] );

			if ( $per_page > 0 ) {
				$offset = ( $paged - 1 ) * $per_page;
				return $this->db->prepare( ' LIMIT %d OFFSET %d', $per_page, $offset );
			}

			return '';
		}

		/**
		 * Map DB row to Role object.
		 *
		 * @param array $row Database row.
		 * @return Role Mapped Role object.
		 */
		private function map_row_to_role( array $row ): Role {
			return new Concrete_Role(
				(int) $row['id'],
				$row['name'] ?? '',
				$row['prefix'] ?? '',
				(bool) ( $row['avatar_visibility'] ?? false ),
				(int) ( $row['icon'] ?? null ),
				$row['nicename'] ?? ''
			);
		}

		/**
		 * Insert a new role.
		 *
		 * @param array $data Data to insert.
		 * @return int Inserted role ID.
		 */
		public function insert( array $data ): int {
			$prepared = $this->prepare_data( $data );
			$inserted = $this->db->insert( $this->table_name, $prepared, get_type_specifiers( $prepared ) );
			if ( false === $inserted ) {
				throw new Exception( 'Failed to insert role.' );
			}
			return (int) $this->db->insert_id;
		}

		/**
		 * Update an existing role.
		 *
		 * @param array                     $data Data to update.
		 * @param array<key-of-Role, mixed> $where Conditions to identify the role to update.
		 * @return string Updated role nicename.
		 */
		public function update( array $data, array $where ): string {
			$updated = $this->db->update( $this->table_name, $data, $where, get_type_specifiers( $data ) );
			if ( false === $updated ) {
				throw new Exception( 'Failed to update role.' );
			}
			return $updated;
		}

		public function delete( array $where ): int {
			if ( ( $where['id'] ?? 0 ) === 1 ) {
				return 0; // prevent deleting default role
			}
			$deleted = $this->db->delete( $this->table_name, $where );
			if ( false === $deleted ) {
				throw new Exception( 'Failed to delete role.' );
			}
			return $deleted;
		}

		private function prepare_data( array $data ): array {
			$name     = trim( $data['name'] ?? '' );
			$nicename = $this->generate_nicename( $name );

			return array(
				'name'              => $name,
				'nicename'          => $nicename,
				'prefix'            => trim( $data['prefix'] ?? '' ),
				'avatar_visibility' => isset( $data['avatar_visibility'] ) ? (bool) $data['avatar_visibility'] : false,
				'icon'              => isset( $data['icon'] ) ? (int) $data['icon'] : null,
			);
		}

		private function generate_nicename( string $name ): string {
			$name     = $name ?: 'role-name';
			$nicename = sanitize_title( $name );
			$original = $nicename;
			$i        = 1;

			while ( $this->nicename_exists( $nicename ) ) {
				$nicename = $original . '-' . $i++;
				if ( $i > 2000 ) {
					throw new Exception( 'generate_nicename created infinite loop' );
				}
			}

			return $nicename;
		}

		private function nicename_exists( string $nicename ): bool {
			$row = $this->db->get_var(
				$this->db->prepare( "SELECT nicename FROM {$this->table_name} WHERE nicename = %s", $nicename )
			);
			return ! empty( $row );
		}
	}

endif;
