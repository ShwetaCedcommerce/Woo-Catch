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
class Ced_Catch_Profile_Table extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Catch Profile', 'woocommerce-catch-integration' ), // singular name of the listed records
				'plural'   => __( 'Catch Profiles', 'woocommerce-catch-integration' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?
			)
		);
	}
	/**
	 *
	 * Function for preparing profile data to be displayed column
	 */
	public function prepare_items() {

		global $wpdb;

		$per_page = apply_filters( 'ced_catch_profile_list_per_page', 10 );
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

		$this->items = self::get_profiles( $per_page, $current_page );

		$count = self::get_count();

		// Set the pagination
		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);

		if ( ! $this->current_action() ) {
			$this->items = self::get_profiles( $per_page, $current_page );
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}
	}
	/**
	 *
	 * Function for status column
	 */
	public function get_profiles( $per_page = 10, $page_number = 1 ) {

		global $wpdb;
		$tableName = $wpdb->prefix . 'ced_catch_profiles';
		$shop_id   = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
		$result    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_catch_profiles WHERE `shop_id`=%d ORDER BY `id` DESC LIMIT %d  OFFSET %d", $shop_id, $per_page, ( $page_number - 1 ) * $per_page ), 'ARRAY_A' );

		return $result;
	}

	/*
	*
	* Function to count number of responses in result
	*
	*/
	public function get_count() {
		global $wpdb;
		$shop_id   = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
		$tableName = $wpdb->prefix . 'ced_catch_profiles';
		$result    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_catch_profiles WHERE `shop_id`= %d", $shop_id ), 'ARRAY_A' );
		return count( $result );
	}

	/*
	*
	* Text displayed when no customer data is available
	*
	*/
	public function no_items() {
		esc_attr_e( 'No Profiles Created.', 'woocommerce-catch-integration' );
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="catch_profile_ids[]" value="%s" />',
			$item['id']
		);
	}


	/**
	 * Function for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	public function column_profile_name( $item ) {
		$title   = '<strong>' . $item['profile_name'] . '</strong>';
		$shop_id = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
		$page    = isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '';
		$actions = array(
			'edit' => sprintf( '<a href="?page=%s&section=%s&shop_id=%s&profileID=%s&panel=edit">Edit</a>', esc_attr( $page ), 'profiles-view', $shop_id, $item['id'] ),
		);
		return $title . $this->row_actions( $actions, true );
		return $title;
	}

	/**
	 *
	 * Function for profile status column
	 */
	public function column_profile_status( $item ) {
		if ( 'inactive' == $item['profile_status'] ) {
			return 'InActive';
		} else {
			return 'Active';
		}
	}

	/**
	 *
	 * Function for category column
	 */
	public function column_woo_categories( $item ) {

		$woo_categories = json_decode( $item['woo_categories'], true );
		$woo_cat        = '';
		if ( ! empty( $woo_categories ) ) {
			foreach ( $woo_categories as $key => $value ) {
				$term = get_term_by( 'id', $value, 'product_cat' );
				if ( $term ) {
					$woo_cat .= $term->name . ', ';
				}
			}
			$woo_cat = rtrim( $woo_cat, ', ' );
			echo esc_attr( $woo_cat );
		}
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'             => '<input type="checkbox" />',
			'profile_name'   => __( 'Profile Name', 'woocommerce-catch-integration' ),
			'profile_status' => __( 'Profile Status', 'woocommerce-catch-integration' ),
			'woo_categories' => __( 'Mapped WooCommerce Categories', 'woocommerce-catch-integration' ),
		);
		$columns = apply_filters( 'ced_catch_alter_profiles_table_columns', $columns );
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
		$shop_id = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
		?>
		<div class="ced_catch_wrap ced_catch_wrap_extn">
				<div class="ced_catch_setting_header ced_catch_category_mapping_wrapper">
					<table>
					<input type="hidden" id="ced_catch_profile_nonce" name="ced_catch_profile_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ced_catch_profile_nonce' ) ); ?>"/>	
					<tr>
					<th><b class="manage_labels"><?php esc_attr_e( 'CATCH PROFILES', 'woocommerce-catch-integration' ); ?></b></th>
					<th></th>
					<th></th>
					<th><input type="button"  class="button-primary" id="ced_catch_update_product_attributes" data-id="<?php echo esc_attr( $shop_id ); ?>" value="UPDATE PRODUCT ATTRIBUTES"></th>
					</tr>
					</table>
				</div>			
			<div>
				<?php
				if ( ! session_id() ) {
					session_start();
				}

				?>
						
				<div class="ced_catch_heading">
					<div class="ced_catch_render_meta_keys_wrapper ced_catch_global_wrap">
						<div class="ced_catch_parent_element">
							<h2>
								<label class="basic_heading ced_catch_render_meta_keys_toggle"><?php esc_html_e( 'INSTRUCTION', 'catch-woocommerce-integration' ); ?></label>
								<span class="dashicons dashicons-arrow-down-alt2 ced_catch_instruction_icon"></span>
							</h2>
						</div>
						<div class="ced_catch_child_element default_modal">
							<ul type="disc">
								<li><?php echo esc_html_e( 'In this section you will see all the profiles created after category mapping.' ); ?></li>
								<li><?php echo esc_html_e( 'You can use the product specific attributes,category specific attributes and miscellaneous attributes in this section using the edit option under profile name.' ); ?>
								</li>
							</ul>
						</div>
					</div>
				</div>
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
	 *
	 * Function for getting current status
	 */
	public function current_action() {
		if ( isset( $_GET['panel'] ) ) {
			$action = isset( $_GET['panel'] ) ? sanitize_text_field( $_GET['panel'] ) : '';
			return $action;
		} elseif ( isset( $_POST['action'] ) ) {
			$nonce        = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
			$nonce_action = 'bulk-' . $this->_args['plural'];
			if ( wp_verify_nonce( $nonce, $nonce_action ) ) {
				$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';
				return $action;
			}
		}
	}

	/**
	 *
	 * Function for processing bulk actions
	 */
	public function process_bulk_action() {

		if ( ! session_id() ) {
			session_start();
		}

		if ( 'bulk-delete' === $this->current_action() || ( isset( $_GET['action'] ) && 'bulk-delete' === $_GET['action'] ) ) {
			$nonce        = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
			$nonce_action = 'bulk-' . $this->_args['plural'];
			if ( wp_verify_nonce( $nonce, $nonce_action ) ) {

				$profileIds = isset( $_POST['catch_profile_ids'] ) ? array_map( 'sanitize_text_field', $_POST['catch_profile_ids'] ) : array();

				if ( is_array( $profileIds ) && ! empty( $profileIds ) ) {
					global $wpdb;

					$tableName = $wpdb->prefix . 'ced_catch_profiles';

					$shop_id = isset( $_GET['shop_id'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_id'] ) ) : '';

					foreach ( $profileIds as $index => $pid ) {

						$product_ids_assigned = get_option( 'ced_catch_product_ids_in_profile_' . $pid, array() );
						foreach ( $product_ids_assigned as $index => $ppid ) {
							delete_post_meta( $ppid, 'ced_catch_profile_assigned' . $shop_id );
						}

						$term_id = $wpdb->get_results( $wpdb->prepare( "SELECT `woo_categories` FROM {$wpdb->prefix}ced_catch_profiles  WHERE `id` = %d ", $pid ), 'ARRAY_A' );
						$term_id = json_decode( $term_id[0]['woo_categories'], true );
						foreach ( $term_id as $key => $value ) {
							delete_term_meta( $value, 'ced_catch_profile_created_' . $shop_id );
							delete_term_meta( $value, 'ced_catch_profile_id_' . $shop_id );
							delete_term_meta( $value, 'ced_catch_mapped_category_' . $shop_id );
						}
					}
					foreach ( $profileIds as $id ) {
						$deleteStatus = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_catch_profiles WHERE `id` IN (%d)", $id ) );
					}
					$redirectURL = get_admin_url() . 'admin.php?page=ced_catch&section=profiles-view&shop_id=' . $shop_id;
					wp_redirect( $redirectURL );
				}
			}
		} elseif ( 'bulk-activate' === $this->current_action() || ( isset( $_POST['action'] ) && 'bulk-activate' === $_POST['action'] ) ) {

			$nonce        = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
			$nonce_action = 'bulk-' . $this->_args['plural'];
			if ( wp_verify_nonce( $nonce, $nonce_action ) ) {
				$profileIds = isset( $_POST['catch_profile_ids'] ) ? array_map( 'sanitize_text_field', $_POST['catch_profile_ids'] ) : array();
				if ( is_array( $profileIds ) && ! empty( $profileIds ) ) {

					global $wpdb;
					$tableName = $wpdb->prefix . 'ced_catch_profiles';
					foreach ( $profileIds as $key => $value ) {
						$wpdb->update( $tableName, array( 'profile_status' => 'active' ), array( 'id' => $value ) );

					}
				}
				$shop_id     = isset( $_GET['shop_id'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_id'] ) ) : '';
				$redirectURL = get_admin_url() . 'admin.php?page=ced_catch&section=profiles-view&shop_id=' . $shop_id;
				wp_redirect( $redirectURL );
			}
		} elseif ( 'bulk-deactivate' === $this->current_action() || ( isset( $_POST['action'] ) && 'bulk-deactivate' === $_POST['action'] ) ) {

			$nonce        = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( $_REQUEST['_wpnonce'] ) : '';
			$nonce_action = 'bulk-' . $this->_args['plural'];
			if ( wp_verify_nonce( $nonce, $nonce_action ) ) {
				$profileIds = isset( $_POST['catch_profile_ids'] ) ? array_map( 'sanitize_text_field', $_POST['catch_profile_ids'] ) : array();
				if ( is_array( $profileIds ) && ! empty( $profileIds ) ) {

					global $wpdb;
					$tableName = $wpdb->prefix . 'ced_catch_profiles';
					foreach ( $profileIds as $key => $value ) {
						$wpdb->update( $tableName, array( 'profile_status' => 'inactive' ), array( 'id' => $value ) );
					}
				}
				$shop_id     = isset( $_GET['shop_id'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_id'] ) ) : '';
				$redirectURL = get_admin_url() . 'admin.php?page=ced_catch&section=profiles-view&shop_id=' . $shop_id;
				wp_redirect( $redirectURL );
			}
		} elseif ( isset( $_GET['panel'] ) && 'edit' == $_GET['panel'] ) {
			$file = CED_CATCH_DIRPATH . 'admin/partials/profile-edit-view.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}
}

$ced_catch_profile_obj = new Ced_Catch_Profile_Table();
$ced_catch_profile_obj->prepare_items();
