<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Ced_Catch_Account_Table extends WP_List_Table {

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

		$per_page = apply_filters( 'ced_catch_account_list_per_page', 10 );
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

		$this->items = self::get_accounts( $per_page, $current_page );

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
			$this->items = self::get_accounts( $per_page, $current_page );
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

	public function get_accounts( $per_page = 10, $page_number = 1 ) {

		global $wpdb;
		$tableName = $wpdb->prefix . 'ced_catch_accounts';

		$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_catch_accounts ORDER BY `id` DESC  LIMIT %d  OFFSET %d", $per_page, ( $page_number - 1 ) * $per_page ), 'ARRAY_A' );

		return $result;
	}

	/**
	 *
	 * Function to count number of responses in result
	 */
	public function get_count() {

		global $wpdb;
		$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_catch_accounts WHERE %d", 1 ), 'ARRAY_A' );
		return count( $result );
	}

	/*
	*
	*Text displayed when no customer data is available
	*
	*/
	public function no_items() {
		esc_html_e( 'No Accounts Linked.', 'woocommerce-catch-integration' );
	}

	/*
	 * Render the bulk edit checkbox
	 *
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="catch_account_ids[]" value="%s" />',
			$item['id']
		);
	}


	/**
	 *
	 * Function for name column
	 */
	public function column_name( $item ) {
		$title = '<strong>' . $item['name'] . '</strong>';
		return $title;
	}
	/**
	 *
	 * Function for Shop Id column
	 */
	public function column_shop_id( $item ) {
		return $item['shop_id'];
	}
	/**
	 *
	 * Function for Acoount Status column
	 */
	public function column_account_status( $item ) {

		if ( 'inactive' == $item['account_status'] ) {
			return 'InActive';
		} else {
			return 'Active';
		}
	}
	/**
	 *
	 * Function for Location column
	 */
	public function column_location( $item ) {
		return $item['location'];

	}
	/**
	 *
	 * Function for Configure column
	 */
	public function column_configure( $item ) {

		$buttonHtml = "<a class='button-primary' href='" . admin_url( 'admin.php?page=ced_catch&section=settings-view&shop_id=' . $item['shop_id'] ) . "'>" . __( 'Configure', 'woocommerce-catch-integration' ) . '</a>';
		return $buttonHtml;
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'             => '<input type="checkbox" />',
			'name'           => __( 'Account Name', 'woocommerce-catch-integration' ),
			'shop_id'        => __( 'Catch Store Id', 'woocommerce-catch-integration' ),
			'location'       => __( 'Catch Location', 'woocommerce-catch-integration' ),
			'account_status' => __( 'Account Status', 'woocommerce-catch-integration' ),
			'configure'      => __( 'Configure', 'woocommerce-catch-integration' ),
		);
		$columns = apply_filters( 'ced_catch_alter_feed_table_columns', $columns );
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
			'bulk-delete' => __( 'Delete', 'woocommerce-catch-integration' ),
		);
		return $actions;
	}

	/**
	 * Function to get changes in html
	 */
	public function renderHTML() {

		?>
		<div class="success-admin-notices"></div>
		<div class="ced_catch_wrap ced_catch_wrap_extn">
			<div class="ced_catch_setting_header cedcommerce-top-border">
				<?php esc_attr( ced_catch_cedcommerce_logo() ); ?>
				<label class="manage_labels"><b><?php esc_html_e( 'CATCH ACCOUNT', 'woocommerce-catch-integration' ); ?></b></label>
				<?php
					$acc_count = $this->get_count();
				if ( $acc_count < 1 ) {
					echo '<a href=javascript:void(0);" class="ced_catch_add_account_button button-primary">' . esc_attr( 'Add Account', 'woocommerce-catch-integration' ) . '</a>';
				}
				?>
			</div>
			<div>
				<div id="post-body" class="metabox-holder columns-2">
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
		<div class="ced_catch_add_account_popup_main_wrapper">
			<div class="ced_catch_add_account_popup_content">
				<div class="ced_catch_add_account_popup_header">
					<h5><?php esc_html_e( 'Authorise your Catch Account', 'woocommerce-catch-integration' ); ?></h5>
					<span class="ced_catch_add_account_popup_close">X</span>
				</div>
				<div class="ced_catch_add_account_popup_body">
					<table>
						<tr >
							<td><label><b><?php esc_html_e( 'Enter The API Key ' ); ?></b></label></br><span class="ced_catch_cedcommerce-tip">[ Get api key <a href="https://marketplace.catch.com.au/mmp/shop/user/api" target="_blank"><i>here</i> ]</a></span></td>
							<td><input type="text" class="ced_catch_inputs ced_catch_auth_input" name=""></td>
						</tr>
						<tr>
							<td><label><b><?php esc_html_e( 'Select the Operation Mode' ); ?></b></label></td>
							<td>
								<select id="ced_catch_operation_mode" class="ced_catch_auth_select">
									<option value="">--select--</option>
									<option value="production">Production</option>
									<option value="sandbox">Sandbox</option>									
								</select>
							</td>
						</tr>
					</table>
					<div class="ced_catch_add_account_button_wrapper">
						<a href="javascript:void(0);" id="ced_catch_authorise_account_button" class="button-primary"><?php esc_html_e( 'Authorize', 'woocommerce-catch-integration' ); ?></a>
					</div>
				</div>
			</div>
		</div>


		<?php
	}
	/**
	 *
	 * Function to get current action
	 */
	public function current_action() {
		$action = '';
		if ( isset( $_GET['section'] ) ) {
			$action = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';
			return $action;
		} elseif ( isset( $_POST['action'] ) ) {
			$nonce        = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
			$nonce_action = 'bulk-' . $this->_args['plural'];
			if ( wp_verify_nonce( $nonce, $nonce_action ) ) {
				$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';
			}
			return $action;
		}
	}
	/**
	 *
	 * Function to perform bulk actions for name column
	 */
	public function process_bulk_action() {

		if ( ! session_id() ) {
			session_start();
		}

		if ( 'bulk-delete' === $this->current_action() || ( isset( $_GET['action'] ) && 'bulk-delete' === $_GET['action'] ) ) {

			$nonce        = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
			$nonce_action = 'bulk-' . $this->_args['plural'];
			if ( wp_verify_nonce( $nonce, $nonce_action ) ) {
				$accountIds = isset( $_POST['catch_account_ids'] ) ? array_map( 'sanitize_text_field', $_POST['catch_account_ids'] ) : array();
				if ( is_array( $accountIds ) && ! empty( $accountIds ) ) {

					global $wpdb;

					$tableName = $wpdb->prefix . 'ced_catch_accounts';

					foreach ( $accountIds as $id ) {
						$deleteStatus = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_catch_accounts WHERE `id` IN (%d)", $id ) );
					}

					$redirectURL = get_admin_url() . 'admin.php?page=ced_catch';
					wp_redirect( $redirectURL );
				}
			}
		} elseif ( 'bulk-enable' === $this->current_action() || ( isset( $_GET['action'] ) && 'bulk-enable' === $_GET['action'] ) ) {
			$nonce        = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
			$nonce_action = 'bulk-' . $this->_args['plural'];
			if ( wp_verify_nonce( $nonce, $nonce_action ) ) {
				$accountIds = isset( $_POST['catch_account_ids'] ) ? array_map( 'sanitize_text_field', $_POST['catch_account_ids'] ) : array();
				if ( is_array( $accountIds ) && ! empty( $accountIds ) ) {
					global $wpdb;
					$tableName = $wpdb->prefix . 'ced_catch_accounts';
					foreach ( $accountIds as $key => $value ) {
						$wpdb->update( $tableName, array( 'account_status' => 'active' ), array( 'id' => $value ) );
					}
				}
				$redirectURL = get_admin_url() . 'admin.php?page=ced_catch';
				wp_redirect( $redirectURL );
			}
		} elseif ( 'bulk-disable' === $this->current_action() || ( isset( $_GET['action'] ) && 'bulk-disable' === $_GET['action'] ) ) {
			$nonce        = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
			$nonce_action = 'bulk-' . $this->_args['plural'];
			if ( wp_verify_nonce( $nonce, $nonce_action ) ) {
				$accountIds = isset( $_POST['catch_account_ids'] ) ? array_map( 'sanitize_text_field', $_POST['catch_account_ids'] ) : array();
				if ( is_array( $accountIds ) && ! empty( $accountIds ) ) {

					global $wpdb;
					$tableName = $wpdb->prefix . 'ced_catch_accounts';
					foreach ( $accountIds as $key => $value ) {
						$wpdb->update( $tableName, array( 'account_status' => 'inactive' ), array( 'id' => $value ) );
					}
				}
				$redirectURL = get_admin_url() . 'admin.php?page=ced_catch';
				wp_redirect( $redirectURL );
			}
		} elseif ( isset( $_GET['section'] ) ) {

			$file = CED_CATCH_DIRPATH . 'admin/partials/' . $this->current_action() . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}
}

$ced_catch_account_obj = new Ced_Catch_Account_Table();
$ced_catch_account_obj->prepare_items();
