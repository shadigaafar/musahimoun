<?php
/**
 * Musahimoun Contributor List Table.
 *
 * Handles the display, sorting, searching, and actions of the contributor list table in the WordPress admin.
 *
 * @since 1.0
 * @package Musahimoun
 */

namespace MSHMN;

require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

/**
 * Class Contributor_List_Table
 *
 * Extends the WP_List_Table class to create a custom table for listing contributors in the WordPress admin.
 */
class Contributor_List_Table extends \WP_List_Table {

	/**
	 * Delete nonce action.
	 *
	 * @var string
	 */
	private $deletenonceaction = '_mshmn_delete_contributor';

	/**
	 * Delete nonce name.
	 *
	 * @var string
	 */
	private $deletenoncename = 'mshmn_delete_nonce';
	/**
	 * Edit nonce action.
	 *
	 * @var string
	 */
	private $editnonceaction = '_mshmn_edit_contributor';
	/**
	 * Edit nonce name.
	 *
	 * @var string
	 */
	private $editnoncename = 'mshmn_edit_contributor_nonce';

	/**
	 * Main page name.
	 *
	 * @var string
	 */
	public $main_page_name = MSHMN_MAIN_MENU_SLUG_NAME;

	/**
	 * Constructor.
	 *
	 * Sets up basic properties for the table, such as singular and plural labels and AJAX support.
	 */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => 'contributor',  // Singular label for an item.
				'plural'   => 'contributors', // Plural label for items.
				'ajax'     => false,     // Disable AJAX loading (optional).
			)
		);
	}

	/**
	 * Retrieve the contributor data.
	 *
	 * Fetches and formats the contributor data to be displayed in the table.
	 *
	 * @param array $args Optional. Arguments for fetching data. Default is an empty array.
	 * @return array Array of associative arrays containing contributor data.
	 */
	public function get_data( $args = array() ) {
		$query = new Contributor_Service(
			array_merge(
				array(
					'order' => 'desc',
				),
				$args
			)
		);
		return $query->get_results( ARRAY_A );
	}

	/**
	 * Render the checkbox column.
	 *
	 * Displays a checkbox for each row in the table.
	 *
	 * @param array $item Associative array representing a single row of data.
	 * @return string HTML for the checkbox.
	 */
	public function column_cb( $item ) {
		// Ensure id key exists
		if ( ! isset( $item['id'] ) ) {
			return '';
		}
		
		return sprintf(
			'%s<input type="checkbox" name="bulk_delete[]" value="%s"/>',
			wp_nonce_field( $this->deletenonceaction, $this->deletenoncename, true, false ),
			esc_attr( $item['id'] )
		);
	}

	/**
	 * Define the table columns and their headers.
	 *
	 * @return array Associative array with column IDs as keys and column names as values.
	 */
	public function get_columns() {
		return array(
			'cb'    => '<input type="checkbox" />',
			'name'  => __( 'Name', 'musahimoun' ),
			'posts' => __( 'Posts', 'musahimoun' ),
		);
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array Associative array with sortable column IDs as keys and sort options as values.
	 */
	public function get_sortable_columns() {
		return array(
			'name' => array( 'name', true ), // Make the 'name' column sortable.
		);
	}

	/**
	 * Validate and sanitize contributor data to ensure all required keys exist.
	 *
	 * @param array $contributors Array of contributor data.
	 * @return array Sanitized array of contributor data.
	 */
	private function validate_contributor_data( $contributors ) {
		if ( ! is_array( $contributors ) ) {
			return array();
		}

		$validated_contributors = array();
		foreach ( $contributors as $contributor ) {
			if ( ! is_array( $contributor ) ) {
				continue;
			}

			// Ensure all required keys exist with fallback values
			$validated_contributor = array(
				'id'      => isset( $contributor['id'] ) ? intval( $contributor['id'] ) : 0,
				'name'    => isset( $contributor['name'] ) ? $contributor['name'] : '',
				'avatar'  => isset( $contributor['avatar'] ) ? $contributor['avatar'] : MSHMN_PLUGIN_URL . '/person.svg',
				'is_user' => isset( $contributor['is_user'] ) ? (bool) $contributor['is_user'] : false,
			);

			// Add any additional keys that might exist
			foreach ( $contributor as $key => $value ) {
				if ( ! isset( $validated_contributor[ $key ] ) ) {
					$validated_contributor[ $key ] = $value;
				}
			}

			$validated_contributors[] = $validated_contributor;
		}

		return $validated_contributors;
	}

	/**
	 * Prepare the table data.
	 *
	 * Handles pagination, sorting, and filtering of the table data.
	 */
	public function prepare_items() {
		$per_page     = 10; // Number of items per page.
		$current_page = $this->get_pagenum(); // Get the current page number.
		$total_items  = count( $this->get_data() ); // Get the total number of items.

		$this->process_bulk_action();

		// Set up pagination.
		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // Total number of items.
				'per_page'    => $per_page,    // Items per page.
			)
		);

		// Filter and sort the data.
		$contributors = $this->filter_data(
			$this->get_data(
				array(
					'per_page' => $per_page,
					'paged'    => $current_page,
				)
			)
		);

		// Validate and sanitize the contributor data
		$contributors = $this->validate_contributor_data( $contributors );

		usort( $contributors, array( &$this, 'usort_reorder' ) );

		// Paginate the data.
		$this->items = $contributors;

		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$columns  = $this->get_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->enqeueu_confrimation_dialog();

	}

	/**
	 * Retrieve hidden columns.
	 *
	 * @return array Array of hidden column IDs.
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Default method for rendering each table cell.
	 *
	 * @param array  $item        Associative array representing a single row of data.
	 * @param string $column_name The name of the current column.
	 *
	 * @return string The content to display in the cell.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'name':
				return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : ''; // Return the contributor's name.
			case 'posts':
				return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : ''; // Return the contributor's posts.
			default:
				//phpcs:ignore.
				return defined( 'WP_DEBUG' ) && WP_DEBUG ? print_r( $item, true ) : ''; // For debugging: print the entire row array.
		}
	}

	/**
	 * Render the actions column.
	 *
	 * Generates the edit and delete links for each row.
	 *
	 * @param array $item Associative array representing a single row of data.
	 *
	 * @return string The content to display in the actions column.
	 */
	public function column_name( $item ) {
		// Ensure is_user key exists with fallback
		$is_user = isset( $item['is_user'] ) ? $item['is_user'] : false;
		
		// Build URL for editing the contributor.
		$edit_nonce_action = $this->editnonceaction;
		$proto             = isset( $_SERVER['HTTPS'] ) ? 'https' : 'http';
		$edit_url          = add_query_arg(
			array(
				'action' => 'edit',
				'id'     => $item['id'],
			),
			admin_url( "admin.php?page=$this->main_page_name", $proto )
		);

		$edit_profile_url = get_edit_profile_url( $item['id'] );

		$nonced_edit_url = ! $is_user ? wp_nonce_url( $edit_url, $edit_nonce_action, $this->editnoncename ) : $edit_profile_url;

		// Build URL for deleting the contributor.
		$delete_url = add_query_arg(
			array(
				'action' => 'delete',
				'id'     => $item['id'],
			),
			admin_url( "admin.php?page=$this->main_page_name", $proto )
		);

		$nonce_delete_url = ! $is_user ? wp_nonce_url( $delete_url, $this->deletenonceaction, $this->deletenoncename ) : $edit_profile_url;
		// Define the available actions.
		$actions = array(
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $nonced_edit_url ), __( 'Edit', 'musahimoun' ) ),   // Edit link.
			'delete' => sprintf( '<a href="%s">%s</a>', esc_url( $nonce_delete_url ), __( 'Delete', 'musahimoun' ) ), // Delete link.
		);

		$contributor_type = $is_user ? sprintf( '<strong>— %s</strong>', __( 'User', 'musahimoun' ) ) : '';

		$avatar = sprintf( '<img src="%s" alt="%s" width="40"/>', $item['avatar'], $item['name'] );

		return sprintf( '<div style="display:flex;align-items:center;gap:5px">%s %s %s</div> %s', $avatar, esc_html( $item['name'] ), $contributor_type, $this->row_actions( $actions ) );
	}


	/**
	 * Column posts display.
	 *
	 * @param array $item Associative array representing a single row of data.
	 */
	public function column_posts( $item ) {
		// Ensure id key exists
		if ( ! isset( $item['id'] ) ) {
			return '0';
		}
		
		$id              = $item['id'];
		$number_of_posts = wp_cache_get( "mshmn_number_of_posts_cache-$id", 'musahimoun-cache' );

		if ( $number_of_posts ) {
			return (string) $number_of_posts ?? '0';
		}

		$post_query = new \WP_Query(
			array(
				'fields'     => 'ids',
				// phpcs:ignore.
				'meta_key'   => 'mshmn_all_post_contributor_ids',
				// phpcs:ignore.
				'meta_query' => array(
					'key'     => 'mshmn_all_post_contributor_ids',
					'value'   => '(:?^|,)(' . $item['id'] . ')(:?$|,)',
					'compare' => 'REGEXP',
				),
			)
		);

		$number_of_posts = is_array( $post_query->get_posts() ) ? count( $post_query->get_posts() ) : 0;

		wp_cache_set( "mshmn_number_of_posts_cache-$id", (string) $number_of_posts, 'musahimoun-cache', 10000 );
		return (string) $number_of_posts;
	}

	/**
	 * Custom sorting function.
	 *
	 * Sorts the data based on the selected column and order.
	 *
	 * @param array $a The first item for comparison.
	 * @param array $b The second item for comparison.
	 *
	 * @return int The result of the comparison (-1, 0, or 1).
	 */
	public function usort_reorder( $a, $b ) {
		//phpcs:ignore.
		$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : 'id'; // If no sort, default to id.
		//phpcs:ignore.
		$order   = ( ! empty( $_REQUEST['order'] ) ) ? sanitize_key( wp_unslash( $_REQUEST['order'] ) ) : 'desc'; // If no order, default to desc.
		
		// Safely access array keys with fallback values
		$value_a = isset( $a[ $orderby ] ) ? $a[ $orderby ] : '';
		$value_b = isset( $b[ $orderby ] ) ? $b[ $orderby ] : '';
		
		$result = strnatcmp( $value_a, $value_b ); // Determine sort order.
		return ( 'asc' === $order ) ? $result : -$result; // Send final sort direction to usort.
	}

	/**
	 * Retrieve bulk actions.
	 *
	 * Defines the bulk actions that are available.
	 *
	 * @return array Associative array of bulk actions.
	 */
	protected function get_bulk_actions() {
		return array(
			'bulk_delete' => __( 'Delete', 'musahimoun' ),
		);
	}


	/**
	 * Process bulk actions.
	 *
	 * Handles the bulk actions like editing or deleting contributors.
	 */
	public function process_bulk_action() {
		$service = new \MSHMN\Guest_Service();
		if ( 'delete' === $this->current_action() ) {
			// Fail early if nonce is missing or invalid
			if ( ! isset( $_GET[ $this->deletenoncename ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET[ $this->deletenoncename ] ) ), $this->deletenonceaction ) ) {
				wp_die( esc_html__( 'Security check failed.', 'musahimoun' ) );
			}

			$contributor_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
			if ( $contributor_id ) {
				$service->delete( array( 'id' => $contributor_id ) );
			}
		}

		// If the delete bulk action is triggered.
		if ( ( isset( $_POST['action'] ) && 'bulk_delete' === $_POST['action'] && isset( $_POST[ $this->deletenoncename ] ) && isset( $_POST['bulk_delete'] ) )
		|| ( isset( $_POST['action2'] ) && 'bulk_delete' === $_POST['action2'] && isset( $_POST[ $this->deletenoncename ] ) && isset( $_POST['bulk_delete'] ) )
		) {
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ $this->deletenoncename ] ) ), $this->deletenonceaction ) ) {
				wp_die( esc_html__( 'Security check failed.', 'musahimoun' ) );
			}

			//phpcs:ignore.
			$delete_ids = esc_sql(  array_map('sanitize_text_field', wp_unslash( $_POST['bulk_delete'] ))  );
			
			// loop over the array of record IDs and delete them.
			foreach ( $delete_ids as $id ) {
				$service->delete( array( 'id' => (int) $id ) );

			}
		}
	}


	/**
	 * Display delete confimaiton dialoge.
	 */
	public function enqeueu_confrimation_dialog() {
		wp_register_script('mshmn-list-table-script', MSHMN_PLUGIN_URL . '/admin/js/table-display-delete-confirmation.js', array('jquery'), '1.1.1', true);
		wp_enqueue_script('mshmn-list-table-script');
		wp_localize_script( 'mshmn-list-table-script', 'mshmnListTableTranslation',
			array( 
				'confirmationMessage'      => esc_html__( 'Are you sure you want to delete the selected contributor? This is permanent delete!', 'musahimoun' ),
			)
		);
	}

	/**
	 * Render the search box.
	 *
	 * Displays a search box above the table for filtering the list of contributors.
	 *
	 * @param string $text The button text.
	 * @param string $input_id The ID of the search input field.
	 */
	public function search_box( $text, $input_id ) {
		// Always display the search form, but validate nonce when processing search
		$search = ! empty( $_REQUEST['s'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) : ''; // Get the current search query.
		?>
		<form method="get" class="search-form">
		<?php
		wp_nonce_field( 'mshmn_search', 'mshmn_search_nonce' ); // Add nonce for security.
		?>
			<p class="search-box"> 
				<input type="hidden" name="page" value="<?php echo esc_attr( sanitize_key( wp_unslash( isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '' ) ) ); ?>" /> <!-- Include the current page value. -->
				<input type="text" name="s" id="<?php echo esc_attr( $input_id ); ?>" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php echo esc_attr( $text ); ?>" /> <!-- Search input. -->
				<input type="submit" class="button" value="<?php echo esc_attr( $text ); ?>" /> <!-- Submit button. -->
			</p>
			
		</form>
		<?php
	}

	/**
	 * Filter the data based on the search query.
	 *
	 * Filters the contributor data array based on the search query.
	 *
	 * @param array $data Array of contributor data.
	 *
	 * @return array Filtered array of contributor data.
	 */
	public function filter_data( $data ) {
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // Sanitize the search query.
		
		// Only process search if nonce is valid
		if ( ! empty( $search ) ) {
			// Fail early if nonce is missing or invalid
			if ( ! isset( $_REQUEST['mshmn_search_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['mshmn_search_nonce'] ) ), 'mshmn_search' ) ) {
				// Return unfiltered data if nonce validation fails
				return $data;
			}
			
			// Use Contributor_Service to get both users and guests with proper structure
			$contributor_service = new \MSHMN\Contributor_Service( array( 'search' => $search ) );
			$data = $contributor_service->get_results( ARRAY_A );
			return $data;
		}
		return $data; // Return the unfiltered data.
	}
}
