<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$file = CED_CATCH_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Ced_Catch_Import_Status_Table extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Catch Account', 'woocommerce-catch-integration' ), // singular name of the listed records
				'plural'   => __( 'Catch Accounts', 'woocommerce-catch-integration' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?
			)
		);
	}

	public function prepare_items() {

		global $wpdb;

		$per_page = apply_filters( 'ced_catch_import_status_per_page', 10 );
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		$this->items = self::get_import_Ids( $per_page );

		$count = self::get_count();

		// Set the pagination

		if ( ! $this->current_action() ) {

			$this->set_pagination_args(
				array(
					'total_items' => $count,
					'per_page'    => $per_page,
					'total_pages' => ceil( $count / $per_page ),
				)
			);
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}

	}

	/*
	*
	* Function to get all the accounts
	*
	*/


	public function get_import_Ids( $per_page = 10 ) {
		$shop_id                = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
		$ImportIds              = get_option( 'ced_catch_import_ids_' . $shop_id, array() );
		$ImportIds              = array_reverse( $ImportIds );
		$current_page           = $this->get_pagenum();
		$count                  = 0;
		$totalCount             = ( $current_page - 1 ) * $per_page;
		$ImportIdsToBeDisplayed = array();
		foreach ( $ImportIds as $key => $value ) {
			if ( ! is_array( $value ) ) {
				unset( $ImportIds[ $key ] );
				continue;
			}
			if ( 1 == $current_page && $count < $per_page ) {
				$count++;
				$ImportIdsToBeDisplayed[] = $value;
			} elseif ( $current_page > 1 ) {
				if ( $key < $totalCount ) {
					continue;
				} elseif ( $count < $per_page ) {
					$count++;
					$ImportIdsToBeDisplayed[] = $value;
				}
			}
		}

		return $ImportIdsToBeDisplayed;
	}

	/**
	 *
	 * Function to count number of responses in result
	 */
	public function get_count() {
		$shop_id   = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
		$ImportIds = get_option( 'ced_catch_import_ids_' . $shop_id, array() );
		return count( $ImportIds );
	}

	/*
	*
	*Text displayed when no customer data is available
	*
	*/
	public function no_items() {
		esc_attr_e( 'No Imports Yet.', 'woocommerce-catch-integration' );
	}

	public function column_cb( $item ) {
		echo "<input type='checkbox' value=" . esc_attr( $item['import_id'] ) . " name='import_ids[]'>";
	}
	public function column_import_id( $item ) {
		require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchSendHttpRequest.php';
		$sendRequestObj = new Class_Ced_Catch_Send_Http_Request();
		$shop_id        = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
		$item_id        = '<b>Catch Import ID : <a>' . esc_attr( $item['import_id'] ) . '</a></b>';

		$actions = array(
			'view' => '<a href=' . admin_url( 'admin.php?page=ced_catch&section=import-status-view&sub_section=import-details&upload_type=' . $item['upload_type'] . '&shop_id=' . $shop_id . '&import_id=' . $item['import_id'] ) . '>Feed details</a>',
		);

		return $item_id . $this->row_actions( $actions, true );
	}

	public function column_product_offer( $item ) {
		echo '<b>' . esc_attr( strtoupper( $item['upload_type'] ) ) . '</b>';
	}


	public function column_offerStatus( $item ) {
		$report = get_option( 'ced_catch_import_status_response' . $item['import_id'], true );
		if ( isset( $report['import_status'] ) ) {
			echo '<b>' . esc_attr( $report['import_status'] ) . '</b>';
		} elseif ( isset( $report['status'] ) ) {
			echo '<b>' . esc_attr( $report['status'] ) . '</b>';
		}
	}




	public function column_file( $item ) {
		return '<a href="' . wp_upload_dir()['baseurl'] . '/cedcommerce_catchuploads/' . basename( $item['file'] ) . '" target="_blank"> View</a>';
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'            => '<input type="checkbox">',
			'import_id'     => __( 'Import ID', 'woocommerce-catch-integration' ),
			'product_offer' => __( 'Import Type', 'woocommerce-catch-integration' ),
			'offerStatus'   => __( 'Status', 'woocommerce-catch-integration' ),
			'file'          => __( 'View Uploaded File', 'woocommerce-catch-integration' ),
		);
		$columns = apply_filters( 'ced_catch_alter_import_status_table_columns', $columns );
		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array();
		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'woocommerce-catch-integration' ),
		);
		return $actions;
	}

	/**
	 * Function to get changes in html
	 */
	public function renderHTML() {

		?>
		<div class="ced_catch_wrap ced_catch_wrap_extn">

			<div>
				<?php
				global $cedsearshelper;
				if ( ! session_id() ) {
					session_start();
				}
				?>
				<div id="post-body" class="metabox-holder columns-2 ced_catch_category_mapping_wrapper">
					<div id="">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								$this->display();
								?>
							</form>
						</div>
					</div>
					<div class="clear"></div>
				</div>
				<br class="clear">
			</div>
		</div>
		<?php
	}

	/**
	 * This function is used to get action
	 * current_action
	 *
	 * @return void
	 */
	public function current_action() {
		if ( isset( $_GET['sub_section'] ) ) {
			$action = isset( $_GET['sub_section'] ) ? sanitize_text_field( $_GET['sub_section'] ) : '';
			return $action;
		} elseif ( isset( $_POST['action'] ) ) {
			$nonce        = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
			$nonce_action = 'bulk-' . $this->_args['plural'];
			if ( wp_verify_nonce( $nonce, $nonce_action ) ) {
				$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';
				return $action;
			}
		} elseif ( isset( $_POST['action2'] ) ) {
			$nonce        = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
			$nonce_action = 'bulk-' . $this->_args['plural'];
			if ( wp_verify_nonce( $nonce, $nonce_action ) ) {
				$action = isset( $_POST['action2'] ) ? sanitize_text_field( $_POST['action2'] ) : '';
				return $action;
			}
		}
	}

	/**
	 * This function is used to process bulk action
	 * process_bulk_action
	 *
	 * @return void
	 */
	public function process_bulk_action() {
		if ( 'delete' == $this->current_action() || 'delete' == $this->current_action() ) {
			$shop_id      = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
			$nonce        = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
			$nonce_action = 'bulk-' . $this->_args['plural'];
			if ( wp_verify_nonce( $nonce, $nonce_action ) ) {
				$ImportIdstoBeDeleted = isset( $_POST['import_ids'] ) ? array_map( 'sanitize_text_field', $_POST['import_ids'] ) : array();
				if ( ! empty( $ImportIdstoBeDeleted ) && is_array( $ImportIdstoBeDeleted ) ) {
					$ImportIds = get_option( 'ced_catch_import_ids_' . $shop_id, array() );
					foreach ( $ImportIdstoBeDeleted as $key => $value ) {
						foreach ( $ImportIds as $k => $v ) {
							if ( $v['import_id'] == $value ) {
								unset( $ImportIds[ $k ] );
								update_option( 'ced_catch_import_ids_' . $shop_id, $ImportIds );
							}
						}
					}
					$redirectURL = get_admin_url() . 'admin.php?page=ced_catch&section=import-status-view&shop_id=' . $shop_id;
					wp_redirect( $redirectURL );
				}
			}
		} elseif ( isset( $_GET['sub_section'] ) ) {
			$sub_section = isset( $_GET['sub_section'] ) ? sanitize_text_field( $_GET['sub_section'] ) : '';
			$file        = CED_CATCH_DIRPATH . 'admin/partials/' . $this->current_action() . '.php';

			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}
}

$ced_catch_account_obj = new Ced_Catch_Import_Status_Table();
$ced_catch_account_obj->prepare_items();
