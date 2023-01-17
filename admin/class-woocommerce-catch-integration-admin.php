<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://cedcommerce.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Catch_Integration
 * @subpackage Woocommerce_Catch_Integration/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woocommerce_Catch_Integration
 * @subpackage Woocommerce_Catch_Integration/admin
 * author     CedCommerce <plugins@cedcommerce.com>
 */
class Woocommerce_Catch_Integration_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->loadDependency();
		add_action( 'manage_edit-shop_order_columns', array( $this, 'ced_catch_add_table_columns' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'ced_catch_manage_table_columns' ), 10, 2 );
		add_action( 'wp_ajax_ced_catch_order_sync', array( $this, 'ced_catch_order_sync' ) );
		add_action( 'wp_ajax_nopriv_ced_catch_order_sync', array( $this, 'ced_catch_order_sync' ) );
		add_action( 'wp_ajax_nopriv_ced_catch_inventory_webhook', array( $this, 'ced_catch_inventory_webhook' ) );
		add_action( 'wp_ajax_ced_catch_inventory_webhook', array( $this, 'ced_catch_inventory_webhook' ) );
		add_action( 'wp_ajax_nopriv_ced_catch_update_status', array( $this, 'ced_catch_update_status' ) );
		add_action( 'wp_ajax_ced_catch_update_status', array( $this, 'ced_catch_update_status' ) );
	}

	public function loadDependency() {
		require_once CED_CATCH_DIRPATH . 'admin/catch/class-catch.php';
		$this->ced_catch_manager = Class_Ced_Catch_Manager::get_instance();
		require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchSendHttpRequest.php';
		$this->sendRequestObj = new Class_Ced_Catch_Send_Http_Request();
		require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchOrders.php';
		$this->catchOrdersInstance = Class_Ced_Catch_Orders::get_instance();
	}

	public function ced_catch_order_sync() {
		$shopID = get_option( 'ced_catch_active_shop', '' );
		do_action( 'ced_catch_Fetch_orders_' . $shopID );
	}

	public function ced_catch_inventory_webhook() {
		$shopID = get_option( 'ced_catch_active_shop', '' );
		do_action( 'ced_catch_inventory_scheduler_job_' . $shopID );
	}

	public function ced_catch_update_status() {
		$shopID = get_option( 'ced_catch_active_shop', '' );
		do_action( 'ced_catch_auto_change_product_status_' . $shopID );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Catch_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Catch_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( isset( $_GET['page'] ) && ( 'ced_catch' == sanitize_text_field( $_GET['page'] ) || 'cedcommerce-integrations' == sanitize_text_field( $_GET['page'] ) ) ) {
			wp_enqueue_style( 'ced-boot-css', 'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', array(), '2.0.0', 'all' );
		}
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woocommerce-catch-integration-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Catch_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Catch_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woocommerce-catch-integration-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'ced_catch_select2_js', plugin_dir_url( __FILE__ ) . 'js/select2.min.js', array( 'jquery' ), '1.0', true );

		if ( isset( $_GET['page'] ) && ( 'ced_catch' == sanitize_text_field( $_GET['page'] ) || 'cedcommerce-integrations' == sanitize_text_field( $_GET['page'] ) ) ) {
			wp_enqueue_script( $this->plugin_name . '_hubspot', '//js-na1.hs-scripts.com/6086579.js', array( 'jquery' ), $this->version, false );

		}

		$ajax_nonce     = wp_create_nonce( 'ced-catch-ajax-seurity-string' );
		$localize_array = array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => $ajax_nonce,
			'shop_id'    => isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '',
		);
		wp_localize_script( $this->plugin_name, 'ced_catch_admin_obj', $localize_array );
	}

	public function ced_catch_add_menus() {
		global $submenu;
		if ( empty( $GLOBALS['admin_page_hooks']['cedcommerce-integrations'] ) ) {
			add_menu_page( __( 'CedCommerce', 'woocommerce-catch-integration' ), __( 'CedCommerce', 'woocommerce-catch-integration' ), 'manage_woocommerce', 'cedcommerce-integrations', array( $this, 'ced_marketplace_listing_page' ), plugins_url( 'catch-integration-for-woocommerce/admin/images/cedcommerce-logo.png' ), 12 );
			$menus = apply_filters( 'ced_add_marketplace_menus_array', array() );
			if ( is_array( $menus ) && ! empty( $menus ) ) {
				foreach ( $menus as $key => $value ) {
					add_submenu_page( 'cedcommerce-integrations', $value['name'], $value['name'], 'manage_woocommerce', $value['menu_link'], array( $value['instance'], $value['function'] ) );
				}
			}
		}
	}

	public function ced_catch_add_marketplace_menus_to_array( $menus = array() ) {
		$menus[] = array(
			'name'            => 'Catch',
			'slug'            => 'woocommerce-catch-integration',
			'menu_link'       => 'ced_catch',
			'instance'        => $this,
			'function'        => 'ced_catch_accounts_page',
			'card_image_link' => CED_CATCH_URL . 'admin/images/catch-card.png',
		);
		return $menus;
	}

	public function ced_catch_marketplace_to_be_logged( $marketplaces = array() ) {

		$marketplaces[] = array(
			'name'             => 'Catch',
			'marketplace_slug' => 'catch',
		);
		return $marketplaces;
	}

	public function ced_marketplace_listing_page() {
		$activeMarketplaces = apply_filters( 'ced_add_marketplace_menus_array', array() );
		if ( is_array( $activeMarketplaces ) && ! empty( $activeMarketplaces ) ) {
			require CED_CATCH_DIRPATH . 'admin/partials/marketplaces.php';
		}
	}

	public function my_catch_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['ced_catch_2min'] ) ) {
			$schedules['ced_catch_2min'] = array(
				'interval' => 2 * 60,
				'display'  => __( 'Once every 2 minutes' ),
			);
		}

		if ( ! isset( $schedules['ced_catch_6min'] ) ) {
			$schedules['ced_catch_6min'] = array(
				'interval' => 6 * 60,
				'display'  => __( 'Once every 6 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_catch_2min'] ) ) {
			$schedules['ced_catch_2min'] = array(
				'interval' => 2 * 60,
				'display'  => __( 'Once every 2 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_catch_10min'] ) ) {
			$schedules['ced_catch_10min'] = array(
				'interval' => 10 * 60,
				'display'  => __( 'Once every 10 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_catch_15min'] ) ) {
			$schedules['ced_catch_15min'] = array(
				'interval' => 15 * 60,
				'display'  => __( 'Once every 15 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_catch_30min'] ) ) {
			$schedules['ced_catch_30min'] = array(
				'interval' => 30 * 60,
				'display'  => __( 'Once every 30 minutes' ),
			);
		}
		if ( ! isset( $schedules['ced_catch_60min'] ) ) {
			$schedules['ced_catch_60min'] = array(
				'interval' => 60 * 60,
				'display'  => __( 'Once every 60 minutes' ),
			);
		}

		// ced_catch_hourly
		if ( ! isset( $schedules['ced_catch_hourly'] ) ) {
			$schedules['ced_catch_hourly'] = array(
				'interval' => 3600,
				'display'  => __( 'Hourly' ),
			);
		}

		if ( ! isset( $schedules['ced_catch_daily'] ) ) {
			$schedules['ced_catch_daily'] = array(
				'interval' => 86400,
				'display'  => __( 'Daily' ),
			);
		}

		if ( ! isset( $schedules['ced_catch_weekly'] ) ) {
			$schedules['ced_catch_weekly'] = array(
				'interval' => 604800,
				'display'  => __( 'Weekly' ),
			);
		}

		// ced_catch_twicedaily
		if ( ! isset( $schedules['ced_catch_twicedaily'] ) ) {
			$schedules['ced_catch_twicedaily'] = array(
				'interval' => 43200,
				'display'  => __( 'Twice Daily' ),
			);
		}

		return $schedules;
	}

	/*
	*
	*Function for displaying default page
	*
	*
	*/
	public function ced_catch_accounts_page() {

		$fileAccounts = CED_CATCH_DIRPATH . 'admin/partials/ced-catch-accounts.php';
		if ( file_exists( $fileAccounts ) ) {
			require_once $fileAccounts;
		}
	}




	/**
	 * Catch_Integration_For_Woocommerce ced_catch_add_table_columns.
	 *
	 * @since    1.0.0
	 */
	public function ced_catch_add_table_columns( $columns ) {
		$modified_columns = array();
		foreach ( $columns as $key => $value ) {
			$modified_columns[ $key ] = $value;
			if ( 'order_number' == $key ) {
				$modified_columns['order_from'] = '<span title="Order source">Order source</span>';
			}
		}
		return $modified_columns;
	}

	/**
	 * Catch_Integration_For_Woocommerce ced_catch_manage_table_columns.
	 *
	 * @since    1.0.0
	 */
	public function ced_catch_manage_table_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'order_from':
				$_ced_catch_order_id = get_post_meta( $post_id, '_ced_catch_order_id', true );
				if ( ! empty( $_ced_catch_order_id ) ) {
					$catch_icon = CED_CATCH_URL . 'admin/images/catch-card.png';
					echo '<p><img src="' . esc_url( $catch_icon ) . '" height="20" width="50"></p>';
				}
		}
	}

	public function ced_catch_save_operation_mode() {
		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );

		if ( $check_ajax ) {
			$operationMode = isset( $_POST['operationMode'] ) ? sanitize_text_field( $_POST['operationMode'] ) : 'sandbox';
			update_option( 'ced_catch_operation_mode', $operationMode );
		}
		die();
	}

	public function ced_catch_authorise_account() {
		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );

		if ( $check_ajax ) {
			$apiKey = isset( $_POST['ApiKey'] ) ? sanitize_text_field( $_POST['ApiKey'] ) : '';
			$apiKey = trim( $apiKey );

			$action      = 'account';
			$shopDetails = $this->sendRequestObj->sendHttpRequestGet( $action, '', '', $apiKey );

			$shopDetails = json_decode( $shopDetails, true );

			if ( isset( $shopDetails['status'] ) ) {
				echo json_encode(
					array(
						'status' => 400,
						'msg'    => $shopDetails['message'],
					)
				);
				wp_die();
			} elseif ( ! empty( $shopDetails ) ) {
				update_option( 'ced_catch_api_key_' . $shopDetails['shop_id'], $apiKey );
				global $wpdb;
				$tableName = $wpdb->prefix . 'ced_catch_accounts';
				$result    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_catch_accounts WHERE `shop_id`= %d", $shopDetails['shop_id'] ), 'ARRAY_A' );

				if ( empty( $result ) ) {
					$wpdb->insert(
						$tableName,
						array(
							'name'           => $shopDetails['shop_name'],
							'account_status' => 'active',
							'shop_id'        => $shopDetails['shop_id'],
							'location'       => $shopDetails['contact_informations']['city'] . ',' . $shopDetails['contact_informations']['country'],
							'shop_data'      => json_encode( $shopDetails ),
						)
					);
				}

				echo json_encode(
					array(
						'status' => 200,
						'msg'    => 'Authorized Successfully',
					)
				);
				wp_die();
			}
		}
	}



	public function ced_catch_render_order_settings() {
		$file = CED_CATCH_DIRPATH . 'admin/pages/ced-catch-order-settings.php';
		if ( file_exists( $file ) ) {
			include_once $file;
			return true;
		}
		return false;
	}

	public function ced_catch_render_product_settings() {
		$file = CED_CATCH_DIRPATH . 'admin/pages/ced-catch-product-settings.php';
		if ( file_exists( $file ) ) {
			include_once $file;
			return true;
		}
		return false;
	}

	public function ced_catch_render_shedulers_settings() {
		$file = CED_CATCH_DIRPATH . 'admin/pages/ced-catch-shedulers-settings.php';
		if ( file_exists( $file ) ) {
			include_once $file;
			return true;
		}
		return false;
	}




	public function ced_catch_category_refresh_button() {
		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$shopid         = isset( $_POST['store_id'] ) ? sanitize_text_field( $_POST['store_id'] ) : '';
			$isShopInActive = ced_catch_inactive_shops( $shopid );
			if ( $isShopInActive ) {
				echo json_encode(
					array(
						'status'  => 400,
						'message' => __(
							'Shop is Not Active',
							'woocommerce-catch-integration'
						),
					)
				);
				die;
			}

			$fileCategory = CED_CATCH_DIRPATH . 'admin/catch/lib/catchCategory.php';
			if ( file_exists( $fileCategory ) ) {
				require_once $fileCategory;
			}

			$catchCategory = Class_Ced_Catch_Category::get_instance();
			$catchCategory = $catchCategory->getRefreshedCatchCategory( $shopid );
			$catchCategory = json_decode( $catchCategory, true );

			if ( isset( $catchCategory['hierarchies'] ) && ! empty( $catchCategory['hierarchies'] ) ) {
				$folderName          = CED_CATCH_DIRPATH . 'admin/catch/lib/json/';
				$completeCatListFile = $folderName . 'categoryList.json';
				@file_put_contents( $completeCatListFile, json_encode( $catchCategory['hierarchies'] ) );
				echo json_encode(
					array(
						'status'  => 200,
						'message' => 'Categories Refreshed successfully.',
					)
				);
				die;
			} else {
				echo json_encode(
					array(
						'status'  => 400,
						'message' => 'Categories Not Refreshed.',
					)
				);
				die;
			}
		}
	}

	public function ced_catch_fetch_next_level_category() {
		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$store_category_id      = isset( $_POST['store_id'] ) ? sanitize_text_field( $_POST['store_id'] ) : '';
			$catch_store_id         = isset( $_POST['catch_store_id'] ) ? sanitize_text_field( $_POST['catch_store_id'] ) : '';
			$catch_category_name    = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
			$catch_category_id      = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '';
			$level                  = isset( $_POST['level'] ) ? sanitize_text_field( $_POST['level'] ) : '';
			$next_level             = intval( $level ) + 1;
			$folderName             = CED_CATCH_DIRPATH . 'admin/catch/lib/json/';
			$categoryFirstLevelFile = $folderName . 'categoryList.json';
			$catchCategoryList      = file_get_contents( $categoryFirstLevelFile );
			$catchCategoryList      = json_decode( $catchCategoryList, true );
			$select_html            = '';
			$nextLevelCategoryArray = array();

			if ( ! empty( $catchCategoryList ) ) {
				foreach ( $catchCategoryList as $key => $value ) {
					if ( isset( $value['parent_code'] ) && str_replace( "'", '~', $value['parent_code'] ) == str_replace( "'", '~', $catch_category_id ) ) {
						$nextLevelCategoryArray[] = $value;
					}
				}
			}

			if ( is_array( $nextLevelCategoryArray ) && ! empty( $nextLevelCategoryArray ) ) {

				$select_html .= '<td data-catlevel="' . $next_level . '"><select class="ced_catch_level' . $next_level . '_category ced_catch_select_category  select_boxes_cat_map" name="ced_catch_level' . $next_level . '_category[]" data-level=' . $next_level . ' data-storeCategoryID="' . $store_category_id . '" data-catchStoreId="' . $catch_store_id . '">';
				$select_html .= '<option value=""> --' . __( 'Select', 'woocommerce-catch-integration' ) . '-- </option>';
				foreach ( $nextLevelCategoryArray as $key => $value ) {
					if ( '' != $value['label'] ) {
						$select_html .= '<option value="' . str_replace( "'", '~', $value['code'] ) . '">' . $value['label'] . '</option>';
					}
				}
				$select_html .= '</select></td>';
				print_r( $select_html );
				die;
			}
		}
	}

	public function ced_catch_filter_woocommerce_order_number( $id, $instance ) {
		$renderDataOnGlobalSettings = get_option( 'ced_catch_global_settings', array() );
		foreach ( $renderDataOnGlobalSettings as $key => $value ) {
			if ( ! empty( $value['ced_catch_set_catchOrderNumber'] ) ) {
				if ( 'on' == $value['ced_catch_set_catchOrderNumber'] ) {
					$catch_order_id = get_post_meta( $instance->get_id(), '_ced_catch_order_id', true );
					if ( ! empty( $catch_order_id ) ) {
						return $catch_order_id;
					}
				}
			}
		}
		return $id;
	}


	/*
	*
	*Function for Storing mapped categories
	*
	*
	*/

	public function ced_catch_map_categories_to_store() {
		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$catch_category_array = isset( $_POST['catch_category_array'] ) ? array_map( 'sanitize_text_field', $_POST['catch_category_array'] ) : '';
			$store_category_array = isset( $_POST['store_category_array'] ) ? array_map( 'sanitize_text_field', $_POST['store_category_array'] ) : '';
			$catch_category_name  = isset( $_POST['catch_category_name'] ) ? array_map( 'sanitize_text_field', $_POST['catch_category_name'] ) : '';

			$catch_store_id = isset( $_POST['catch_store_id'] ) ? sanitize_text_field( $_POST['catch_store_id'] ) : '';

			$catch_saved_category        = get_option( 'ced_catch_saved_category', array() );
			$alreadyMappedCategories     = array();
			$alreadyMappedCategoriesName = array();
			$catchMappedCategories       = array_combine( $store_category_array, $catch_category_array );
			$catchMappedCategories       = array_filter( $catchMappedCategories );
			$alreadyMappedCategories     = get_option( 'ced_woo_catch_mapped_categories', array() );

			if ( is_array( $catchMappedCategories ) && ! empty( $catchMappedCategories ) ) {
				foreach ( $catchMappedCategories as $key => $value ) {
					$alreadyMappedCategories[ $catch_store_id ][ $key ] = $value;
				}
			}
			update_option( 'ced_woo_catch_mapped_categories', $alreadyMappedCategories );
			$catchMappedCategoriesName   = array_combine( $catch_category_array, $catch_category_name );
			$catchMappedCategoriesName   = array_filter( $catchMappedCategoriesName );
			$alreadyMappedCategoriesName = get_option( 'ced_woo_catch_mapped_categories_name', array() );
			if ( is_array( $catchMappedCategoriesName ) && ! empty( $catchMappedCategoriesName ) ) {
				foreach ( $catchMappedCategoriesName as $key => $value ) {
					$alreadyMappedCategoriesName[ $catch_store_id ][ $key ] = $value;
				}
			}

			update_option( 'ced_woo_catch_mapped_categories_name', $alreadyMappedCategoriesName );
			$this->ced_catch_manager->ced_catch_createAutoProfiles( $catchMappedCategories, $catchMappedCategoriesName, $catch_store_id );
			wp_die();
		}
	}




	public function ced_catch_update_offers( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( '_stock' == $meta_key ) {
			$shopID   = get_option( 'ced_catch_active_shop', '' );
			$on_catch = get_post_meta( $post_id, 'ced_catch_product_on_catch_' . $shopID, true );
			if ( $on_catch ) {
				$response = $this->ced_catch_manager->prepareProductHtmlForOffer( array( $post_id ), '', 'UPDATE', true );
			}
		}
	}

	public function ced_catch_Fetch_orders() {

		$current_action     = current_action();
		$shop_id            = str_replace( 'ced_catch_Fetch_orders_', '', $current_action );
		$shop_id            = trim( $shop_id );
		$last_created_order = get_option( 'ced_catch_last_order_created_time', '' );
		$action             = 'orders';
		update_option( 'ced_catch_last_order_cron_trigger', date_i18n( 'Y-m-d H:i:s' ) );

		$ced_fetch_order_by_catch_status = get_option( 'ced_fetch_order_by_catch_status', '' );
		if ( ! empty( $ced_fetch_order_by_catch_status ) ) {
			$ced_fetch_order_by_catch_status = implode( ',', $ced_fetch_order_by_catch_status );
			$status                          = $ced_fetch_order_by_catch_status;
		} else {
			$status = 'WAITING_ACCEPTANCE,SHIPPING';
		}

		if ( '' != $last_created_order ) {
			$action = $action . '?order_state_codes=' . $status . '&start_date=' . $last_created_order;
		} else {
			$action = $action . '?order_state_codes=' . $status;
		}

		$orders = $this->sendRequestObj->sendHttpRequestGet( $action, '', $shop_id );
		$orders = json_decode( $orders, true );
		if ( is_array( $orders['orders'] ) && count( $orders['orders'] ) > 0 ) {
			$this->catchOrdersInstance->create_local_order( $orders['orders'], $shop_id );
		}
	}

	public function ced_catch_manual_fetch_orders() {
		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$shop_id            = isset( $_POST['shop_id'] ) ? sanitize_text_field( $_POST['shop_id'] ) : '';
			$shop_id            = trim( $shop_id );
			$last_created_order = get_option( 'ced_catch_last_order_created_time', '' );
			$action             = 'orders';

			$ced_fetch_order_by_catch_status = get_option( 'ced_fetch_order_by_catch_status', '' );
			if ( ! empty( $ced_fetch_order_by_catch_status ) ) {
				$ced_fetch_order_by_catch_status = implode( ',', $ced_fetch_order_by_catch_status );
				$status                          = $ced_fetch_order_by_catch_status;
			} else {
				$status = 'WAITING_ACCEPTANCE,SHIPPING';
			}

			if ( '' != $last_created_order ) {
				$action = $action . '?order_state_codes=' . $status . '&start_date=' . $last_created_order;
			} else {
				$action = $action . '?order_state_codes=' . $status;
			}
			$orders = $this->sendRequestObj->sendHttpRequestGet( $action, '', $shop_id );
			$orders = json_decode( $orders, true );
			if ( is_array( $orders['orders'] ) && count( $orders['orders'] ) > 0 ) {
				$this->catchOrdersInstance->create_local_order( $orders['orders'], $shop_id );
			}
		}
	}

	public function ced_catch_process_catch_orders() {
		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$shop_id        = isset( $_POST['shop_id'] ) ? sanitize_text_field( $_POST['shop_id'] ) : '';
			$shop_id        = trim( $shop_id );
			$order_id       = isset( $_POST['order_id'] ) ? trim( sanitize_text_field( $_POST['order_id'] ) ) : '';
			$operation      = isset( $_POST['operation'] ) ? trim( sanitize_text_field( $_POST['operation'] ) ) : '';
			$catch_order_id = get_post_meta( $order_id, '_ced_catch_order_id', true );
			$order          = wc_get_order( $order_id );
			$order_items    = get_post_meta( $order_id, 'order_items', true );
			if ( ! empty( $catch_order_id ) ) {
				$action = 'orders/' . $catch_order_id . '/accept';
				if ( 'Accept' == $operation ) {
					$accept = true;
				} elseif ( 'Reject' == $operation ) {
					$accept = false;
				}
				$order_lines = array();
				foreach ( $order_items as $index => $details ) {
					$orderAccept['accepted']      = $accept;
					$orderAccept['id']            = $details['order_line_id'];
					$order_lines['order_lines'][] = $orderAccept;
				}
				$parameters = $order_lines;
				$response   = $this->sendRequestObj->sendHttpRequestPut( $action, $parameters, $shop_id );
				if ( 'Accept' == $operation ) {
					update_post_meta( $order_id, '_catch_umb_order_status', 'Accepted' );
					$order->update_status( 'processing' );
				} elseif ( 'Reject' == $operation ) {
					update_post_meta( $order_id, '_catch_umb_order_status', 'Rejected' );
					$order->update_status( 'cancelled' );
				}
			}
		}
	}

	public function ced_catch_Fetch_orders_by_id() {
		require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchOrders.php';
		$catchOrdersInstance             = Class_Ced_Catch_Orders::get_instance();
		$current_action                  = current_action();
		$shop_id                         = str_replace( 'ced_catch_Fetch_orders_by_id_', '', $current_action );
		$shop_id                         = trim( $shop_id );
		$action                          = 'orders';
		$order_ids_to_get_updated_status = get_option( 'ced_catch_get_order_ids_to_be_updated', array() );

		if ( empty( $order_ids_to_get_updated_status ) ) {
			$order_ids = $this->catchOrdersInstance->get_catch_order_ids( $shop_id );
			if ( ! empty( $order_ids ) ) {
				$order_ids_to_get_updated_status = array_chunk( $order_ids, 10 );
			}
		}

		if ( isset( $order_ids_to_get_updated_status[0] ) && ! empty( $order_ids_to_get_updated_status[0] ) ) {
			$order_ids = implode( ',', $order_ids_to_get_updated_status[0] );
			$action    = $action . '?order_ids=' . $order_ids;
			$orders    = $this->sendRequestObj->sendHttpRequestGet( $action, '', $shop_id );
			$orders    = json_decode( $orders, true );
			if ( is_array( $orders['orders'] ) && count( $orders['orders'] ) > 0 ) {
				$orders_count = count( $orders['orders'] );
				update_option( 'ced_catch_orders_offset_' . $shop_id, $orders_count );
				$catchOrdersInstance->create_local_order( $orders['orders'], $shop_id, true );
				unset( $order_ids_to_get_updated_status[0] );
				$order_ids_to_get_updated_status = array_values( $order_ids_to_get_updated_status );
				update_option( 'ced_catch_get_order_ids_to_be_updated', $order_ids_to_get_updated_status );
			}
		}
	}

	public function ced_catch_fetch_orders_with_specific_order_id() {
		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : '';
			$shop_id  = isset( $_POST['shop_id'] ) ? sanitize_text_field( $_POST['shop_id'] ) : '';
			$action   = 'orders';
			$action   = $action . '?order_ids=' . $order_id;
			$orders   = $this->sendRequestObj->sendHttpRequestGet( $action, '', $shop_id );
			$orders   = json_decode( $orders, true );

			if ( is_array( $orders['orders'] ) && count( $orders['orders'] ) > 0 ) {
				$this->catchOrdersInstance->create_local_order( $orders['orders'], $shop_id );
			}
		}
	}

	public function ced_catch_inventory_schedule_manager() {
		$current_action = current_action();
		$shop_id        = str_replace( 'ced_catch_inventory_scheduler_job_', '', $current_action );
		$shop_id        = trim( $shop_id );
		$offset         = get_option( 'ced_catch_offers_offset_' . $shop_id, false );
		if ( ! empty( $offset ) ) {
			$offset = $offset;
		} else {
			$offset = 0;
		}
		$max    = 100;
		$action = 'offers?max=' . $max . '&offset=' . $offset;

		$getOffers = $this->sendRequestObj->sendHttpRequestGet( $action, '', $shop_id );
		$getOffers = json_decode( $getOffers, true );

		if ( isset( $getOffers['offers'] ) && ! empty( $getOffers['offers'] ) ) {
			$totalOffersOnShop    = isset( $getOffers['total_count'] ) ? $getOffers['total_count'] : '';
			$totalOffersRetrieved = get_option( 'ced_catch_offers_retrieved_' . $shop_id, false );
			$totalOffersRetrieved = (int) $totalOffersRetrieved;
			$offersRetrieved      = count( $getOffers['offers'] ) + $totalOffersRetrieved;
			update_option( 'ced_catch_offers_retrieved_' . $shop_id, $offersRetrieved );
			$offset = $offset + count( $getOffers['offers'] );
			if ( $offset < $totalOffersOnShop ) {
				update_option( 'ced_catch_offers_offset_' . $shop_id, $offset );
			} else {
				update_option( 'ced_catch_offers_offset_' . $shop_id, '' );
			}
			foreach ( $getOffers['offers'] as $key => $value ) {
				$productSku = isset( $value['shop_sku'] ) ? $value['shop_sku'] : '';
				$catch_sku  = isset( $value['product_sku'] ) ? $value['product_sku'] : '';
				$offerIDs   = $this->ced_catch_if_product_exists_in_store( $productSku );
				if ( $offerIDs ) {
					$product = wc_get_product( $offerIDs );
					if ( ! is_object( $product ) ) {
						continue;
					}
					$type = $product->get_type();
					if ( 'variation' == $type ) {

						$parent_id           = $product->get_parent_id();
						$offersToBeUpdated[] = $parent_id;
						update_post_meta( $parent_id, 'ced_catch_product_on_catch_' . $shop_id, true );
					} else {
						$offersToBeUpdated[] = $offerIDs;
					}

					update_post_meta( $offerIDs, 'ced_catch_product_sku', $catch_sku );
					update_post_meta( $offerIDs, 'ced_catch_product_on_catch_' . $shop_id, true );
					delete_post_meta( $offerIDs, 'ced_catch_uploaded_product_status' . $shop_id, true );
				}
			}
			if ( ! empty( $offersToBeUpdated ) ) {
				$this->ced_catch_manager->prepareProductHtmlForOffer( $offersToBeUpdated, $shop_id, 'UPDATE', true );
			}
		}
	}


	public function ced_catch_sync_products() {

		$current_action = current_action();
		$shop_id        = str_replace( 'ced_catch_sync_products_', '', $current_action );
		$shop_id        = trim( $shop_id );
		$offset         = get_option( 'ced_catch_sync_products_offset_' . $shop_id, false );
		if ( ! empty( $offset ) ) {
			$offset = $offset;
		} else {
			$offset = 0;
		}
		$max    = 100;
		$action = 'offers?max=' . $max . '&offset=' . $offset;

		$getOffers = $this->sendRequestObj->sendHttpRequestGet( $action, '', $shop_id );

		$getOffers = json_decode( $getOffers, true );

		if ( isset( $getOffers['offers'] ) && ! empty( $getOffers['offers'] ) ) {
			$totalOffersOnShop    = isset( $getOffers['total_count'] ) ? $getOffers['total_count'] : '';
			$totalOffersRetrieved = get_option( 'ced_catch_synced_products_' . $shop_id, true );
			$totalOffersRetrieved = (int) $totalOffersRetrieved;
			$offersRetrieved      = count( $getOffers['offers'] ) + $totalOffersRetrieved;
			update_option( 'ced_catch_synced_products_' . $shop_id, $offersRetrieved );
			$offset = $offset + count( $getOffers['offers'] );
			if ( $offset < $totalOffersOnShop ) {
				update_option( 'ced_catch_sync_products_offset_' . $shop_id, $offset );
			} else {
				update_option( 'ced_catch_sync_products_offset_' . $shop_id, '' );
			}
			foreach ( $getOffers['offers'] as $key => $value ) {
				$productSku = isset( $value['shop_sku'] ) ? $value['shop_sku'] : '';
				$offerID    = $this->ced_catch_if_product_exists_in_store( $productSku );
				if ( $offerID ) {
					$catch_sku = $value['product_sku'];
					update_post_meta( $offerID, 'ced_catch_product_sku', $catch_sku );
					update_post_meta( $offerID, 'ced_catch_product_on_catch_' . $shop_id, true );
					$product = wc_get_product( $offerID );
					if ( ! is_object( $product ) ) {
						continue;
					}
					$type = $product->get_type();
					if ( 'variation' == $type ) {
						$parent_id = $product->get_parent_id();
						update_post_meta( $parent_id, 'ced_catch_product_on_catch_' . $shop_id, true );
					}
				}
			}
		}
	}


	public function ced_catch_if_product_exists_in_store( $Sku = '' ) {

		$Id = get_posts(
			array(
				'numberposts' => -1,
				'post_type'   => array( 'product', 'product_variation' ),
				'meta_key'    => '_sku',
				'meta_value'  => $Sku,
				'compare'     => '=',
				'fields'      => 'ids',
			)
		);

		if ( $Id ) {
			return $Id[0];
		} else {
			return false;
		}
	}

	public function ced_catch_add_order_metabox() {
		global $post;
		$product = wc_get_product( $post->ID );
		if ( get_post_meta( $post->ID, '_is_ced_catch_order', true ) ) {
			add_meta_box(
				'ced_catch_manage_orders_metabox',
				__( 'Manage Catch Orders', 'woocommerce-catch-integration' ) . wc_help_tip( __( 'Please save tracking information of order.', 'woocommerce-catch-integration' ) ),
				array( $this, 'catch_render_orders_metabox' ),
				'shop_order',
				'advanced',
				'high'
			);
		}

		if ( ! is_object( $product ) ) {
			return;
		}

		add_meta_box(
			'ced_catch_description_metabox',
			__( 'Catch Custom Description', 'catch-woocommerce-integration' ),
			array( $this, 'ced_catch_render_metabox' ),
			'product',
			'advanced',
			'high'
		);
	}

	public function ced_catch_render_metabox() {
		global $post;
		$product_id       = $post->ID;
		$long_description = get_post_meta( $product_id, '_ced_catch_custom_description', true );
		?>
		<table>
			<tbody>
				<tr>
					<td>
						<?php
						$key          = 'ced_catch_data[' . $product_id . '][ced_catch_custom_title]';
						$custom_title = get_post_meta( $product_id, 'ced_catch_custom_title', true );
						woocommerce_wp_text_input(
							array(
								'id'                => $key,
								'label'             => __( 'Catch Custom Title', 'woocommerce' ),
								'description'       => '',
								'type'              => 'text',
								'value'             => $custom_title,
								'custom_attributes' => array(
									'min' => '1',
								),
							)
						);

						woocommerce_wp_text_input(
							array(
								'id'          => 'ced_catch_metabox_nonce',
								'label'       => '',
								'description' => '',
								'type'        => 'hidden',
								'value'       => esc_attr( wp_create_nonce( 'ced_catch_metabox_nonce' ) ),
							)
						);

						$content   = $long_description;
						$editor_id = '_ced_catch_custom_description';
						$settings  = array( 'textarea_rows' => 10 );
						echo '<label>Catch Custom Description</label>';
						wp_editor( $content, $editor_id, $settings );
						?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}


	public function catch_render_orders_metabox() {
		global $post;
		$order_id = isset( $post->ID ) ? intval( $post->ID ) : '';

		$carrier_code = get_post_meta( $order_id, 'ced_catch_carrier_code', true );
		$carrier_name = get_post_meta( $order_id, 'ced_catch_carrier_name', true );
		$carrier_url  = get_post_meta( $order_id, 'ced_catch_carrier_url', true );
		$tracking_no  = get_post_meta( $order_id, 'ced_catch_tracking_number', true );

		$carrier_code = ! empty( $carrier_code ) ? $carrier_code : '';
		$carrier_name = ! empty( $carrier_name ) ? $carrier_name : '';
		$carrier_url  = ! empty( $carrier_url ) ? $carrier_url : '';
		$tracking_no  = ! empty( $tracking_no ) ? $tracking_no : '';

		$carrier_codes = get_option( 'ced_catch_carrier_codes_list', array() );
		if ( empty( $carrier_codes ) ) {
			$action   = 'shipping/carriers';
			$shopId   = get_option( 'ced_catch_shop_id', '' );
			$response = $this->sendRequestObj->sendHttpRequestGet( $action, '', $shopId );
			$response = json_decode( $response, true );
			if ( isset( $response['carriers'] ) ) {
				$carrier_codes = $response['carriers'];
				update_option( 'ced_catch_carrier_codes_list', $response['carriers'] );
			}
		}

		?>
		<div class="ced_catch_loader">
		</div>
		<div class="admin_notice"></div>
		<table>
			<tr><td>Order State :</td>
				<td><?php echo esc_attr( strtoupper( get_post_meta( $order_id, '_catch_umb_order_status', true ) ) ); ?></td>
			</tr>
			<tr>
				<td>Shipping Carrier Code</td>
				<td><select id="ced_catch_carrier_code" name="ced_catch_carrier_code">
					<?php
					foreach ( $carrier_codes as $key => $value ) {
						$selected = '';
						if ( $carrier_code == $value['code'] ) {
							$selected = 'selected';
						}
						?>
						<option value="<?php echo esc_attr( $value['code'] ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_attr( $value['label'] ); ?></option>
						<?php
					}
					?>
				</select></td>
			</tr>
			<tr>
				<td>Shipping Carrier Name</td>
				<td><input type='text' id='ced_catch_carrier_name' name='ced_catch_carrier_name' value='<?php echo esc_attr( $carrier_name ); ?>'></td>
			</tr>
			<tr>
				<td>Shipping Carrier URL</td>
				<td><input type='text' id='ced_catch_carrier_url' name='ced_catch_carrier_url' value='<?php echo esc_attr( $carrier_url ); ?>'></td>
			</tr>
			<tr>
				<td>Tracking Number</td>
				<td><input type='text' id="ced_catch_tracking_number" name='ced_catch_tracking_number' value='<?php echo esc_attr( $tracking_no ); ?>'></td>
			</tr>
			<tr>
				<td><input type='button' data-order_id="<?php echo esc_attr( $order_id ); ?>" class="btn button-primary ced_catch_shipment_tracking" value='Submit'></td>
			</tr>
		</table>
		<?php
	}


	public function ced_catch_shipment_tracking() {
		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {

			$ced_catch_carrier_code    = isset( $_POST['ced_catch_carrier_code'] ) ? sanitize_text_field( $_POST['ced_catch_carrier_code'] ) : '';
			$ced_catch_carrier_name    = isset( $_POST['ced_catch_carrier_name'] ) ? sanitize_text_field( $_POST['ced_catch_carrier_name'] ) : '';
			$ced_catch_carrier_url     = isset( $_POST['ced_catch_carrier_url'] ) ? sanitize_text_field( $_POST['ced_catch_carrier_url'] ) : '';
			$ced_catch_tracking_number = isset( $_POST['ced_catch_tracking_number'] ) ? sanitize_text_field( $_POST['ced_catch_tracking_number'] ) : '';
			$order_id                  = isset( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : '';
			$shop_id                   = get_post_meta( $order_id, 'ced_catch_order_shop_id', true );
			$catch_order_id            = get_post_meta( $order_id, '_ced_catch_order_id', true );

			$ced_catch_shipment_log = 'Date :' . date_i18n( 'Y-m-d H:i:s' ) . "\r\nMessage : processing of Order tracking : Order Id - " . $order_id . "\r\n";

			$action     = 'orders/' . $catch_order_id . '/tracking';
			$parameters = array(
				'carrier_code'    => $ced_catch_carrier_code,
				'carrier_name'    => $ced_catch_carrier_name,
				'carrier_url'     => $ced_catch_carrier_url,
				'tracking_number' => $ced_catch_tracking_number,
			);

			$actionShip = 'orders/' . $catch_order_id . '/ship';
			$this->sendRequestObj->sendHttpRequestPut( $action, $parameters, $shop_id );
			$this->sendRequestObj->sendHttpRequestPut( $actionShip, array(), $shop_id );
			update_post_meta( $order_id, '_catch_umb_order_status', 'Shipped' );
			update_post_meta( $order_id, 'ced_catch_carrier_code', $ced_catch_carrier_code );
			update_post_meta( $order_id, 'ced_catch_tracking_number', $ced_catch_tracking_number );

			$ced_catch_default_order_statuses = array(
				'SHIPPED' => 'wc-completed',
			);

			$ced_catch_mapped_order_statuses = get_option( 'ced_catch_mapped_order_statuses', array() );
			$order_obj                       = wc_get_order( $order_id );
			if ( is_object( $order_obj ) ) {
				$woo_order_status = isset( $ced_catch_mapped_order_statuses['SHIPPED'] ) ? $ced_catch_mapped_order_statuses['SHIPPED'] : $ced_catch_default_order_statuses['SHIPPED'];
				$order_obj->update_status( $woo_order_status );
			}
			$ced_catch_shipment_log .= 'Tracking No : ' . $ced_catch_tracking_number . ' Carrier Code :' . $ced_catch_carrier_code . "\r\nResponse - Tracking Submitted Successfully\r\n\n";
			$this->ced_catch_manager->ced_catch_custom_log( 'cedcommerce/catch/logs/shipment', '', $ced_catch_shipment_log );

			echo json_encode( 'Order Status Shipped Successfully' );
			die();
		}
	}

	public function ced_catch_custom_product_tabs( $tab ) {
		$tab['custom_inventory'] = array(
			'label'  => __( 'Catch Data', 'woocommerce' ),
			'target' => 'inventory_options',
			'class'  => array( 'show_if_simple' ),
		);
		return $tab;
	}


	public function inventory_options_product_tab_content() {
		global $post;

		// Note the 'id' attribute needs to match the 'target' parameter set above
		?>
		<div id='inventory_options' class='panel woocommerce_options_panel'>
			<div class='options_group'>
				<?php
				$this->render_fields( $post->ID );
				?>
			</div>		
		</div>
		<?php
	}

	public function render_fields( $post_id ) {

		$ced_catch_custom_identifier_type  = get_post_meta( $post_id, 'ced_catch_custom_identifier_type', true );
		$ced_catch_custom_identifier_value = get_post_meta( $post_id, 'ced_catch_custom_identifier_value', true );
		$ced_catch_custom_size_chart       = get_post_meta( $post_id, 'ced_catch_custom_size_chart', true );
		$catch_custom_price                = get_post_meta( $post_id, 'ced_catch_custom_price', true );
		$catch_custom_stock                = get_post_meta( $post_id, 'ced_catch_custom_stock', true );
		$catch_custom_logistic_class       = get_post_meta( $post_id, 'ced_catch_custom_logistic_class', true );
		$ced_catch_product_keywords        = get_post_meta( $post_id, 'ced_catch_product_keywords', true );

		$offer_attribute_data = CED_CATCH_DIRPATH . 'admin/catch/lib/json/offer-attributes.json';
		$offer_attribute_data = file_get_contents( $offer_attribute_data );
		$offer_attribute_data = json_decode( $offer_attribute_data, true );

		echo '<h2>Catch Product Data<span class="dashicons ced_catch_instruction_icon dashicons-arrow-down-alt2"></span></h2>';
		$catch_product_attribute          = @file_get_contents( CED_CATCH_DIRPATH . 'admin/catch/lib/json/catch-product-specific.json' );
		$catch_product_attribute          = json_decode( $catch_product_attribute, true );
		$ced_catch_custom_variation_image = get_post_meta( $post_id, 'ced_catch_custom_variation_image', true );

		echo '<div class="ced_catch_display_product_specific">';
		woocommerce_wp_text_input(
			array(
				'id'          => 'ced_catch_variation_nonce',
				'label'       => '',
				'description' => '',
				'type'        => 'hidden',
				'value'       => esc_attr( wp_create_nonce( 'ced_catch_variation_nonce' ) ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => 'ced_catch_data[' . $post_id . '][ced_catch_custom_variation_image]',
				'label'       => 'Custom Galary Image',
				'description' => '',
				'type'        => 'text',
				'value'       => $ced_catch_custom_variation_image,
				'placeholder' => 'Enter Url Separated by , Symbol',
			)
		);
		foreach ( $catch_product_attribute as $key => $attributes_field ) {
			foreach ( $attributes_field as $value ) {
				if ( ! empty( $value['code'] ) && 'image-size-chart' == $value['code'] ) {
					$value['type'] = 'TEXT';
				}
				$key            = 'ced_catch_custom_' . $value['code'];
				$selected_value = get_post_meta( $post_id, $key, true );
				$id             = 'ced_catch_data[' . $post_id . '][' . $key . ']';
				if ( 'LIST' == $value['type'] ) {
					$attribute_options   = array();
					$attribute_options[] = '--select--';
					foreach ( $value['values'] as $key => $list_value ) {
						$attribute_options[ $list_value['code'] ] = $list_value['label'];
					}
					woocommerce_wp_select(
						array(
							'id'          => $id,
							'label'       => $value['label'],
							'options'     => $attribute_options,
							'value'       => $selected_value,
							'desc_tip'    => 'true',
							'description' => $value['description'],
						)
					);
				} elseif ( 'TEXT' == $value['type'] || 'INTEGER' == $value['type'] || 'DECIMAL' == $value['type'] || 'LONG_TEXT' == $value['type'] || 'MULTIPLE' == $value['type'] ) {
					woocommerce_wp_text_input(
						array(
							'id'          => $id,
							'label'       => $value['label'],
							'desc_tip'    => 'true',
							'description' => $value['description'],
							'type'        => 'text',
							'value'       => $selected_value,
						)
					);
				}
			}
		}
		echo '</div>';

		echo '<h2>Catch Offer Data<span class="dashicons ced_catch_offer_instruction_icon dashicons-arrow-down-alt2"></span></h2>';
		echo '<div class="ced_catch_display_offer_specific">';
		foreach ( $offer_attribute_data as $key => $value ) {
			if ( $value['required'] ) {
				$key = 'ced_catch_custom_' . $value['code'];

				$selected_value = get_post_meta( $post_id, $key, true );
				$id             = 'ced_catch_data[' . $post_id . '][' . $key . ']';
				if ( 'LIST' == $value['type'] ) {
					$options   = array();
					$options[] = '--select--';
					foreach ( $value['values_list'] as $label => $label_data ) {
						$option_value             = $label_data['code'];
						$option_label             = $label_data['label'];
						$options[ $option_value ] = $option_label;
					}
					woocommerce_wp_select(
						array(
							'id'          => $id,
							'label'       => $value['label'],
							'options'     => $options,
							'value'       => $selected_value,
							'desc_tip'    => 'true',
							'description' => $value['description'],
						)
					);
				} elseif ( 'TEXT' == $value['type'] ) {
					woocommerce_wp_text_input(
						array(
							'id'          => $id,
							'label'       => $value['label'],
							'desc_tip'    => 'true',
							'description' => $value['description'],
							'type'        => 'text',
							'value'       => $selected_value,
						)
					);
				}
			} else {
				$type = '';
				if ( 'discount-price' == $value['code'] || 'discount-start-date' == $value['code'] || 'discount-end-date' == $value['code'] ) {
					$key            = 'ced_catch_custom_' . $value['code'];
					$selected_value = get_post_meta( $post_id, $key, true );
					$id             = 'ced_catch_data[' . $post_id . '][' . $key . ']';
					if ( 'TEXT' == $value['type'] ) {
						if ( 'discount-price' == $value['code'] ) {
							$type = 'text';
						} else {
							$type = 'date';
						}
						woocommerce_wp_text_input(
							array(
								'id'          => $id,
								'label'       => $value['label'],
								'desc_tip'    => 'true',
								'description' => $value['description'],
								'type'        => $type,
								'value'       => $selected_value,
							)
						);
					}
				}
			}
		}
		echo '</div>';
	}

	public function ced_catch_render_product_fields( $loop, $variation_data, $variation ) {
		if ( ! empty( $variation_data ) ) {
			?>
			<div id='catch_inventory_options_variable' class='panel woocommerce_options_panel'><div class='options_group'>
				<?php
				echo "<div class='ced_catch_variation_product_level_wrap'>";
				echo "<div class=''>";
				echo '</div>';
				echo "<div class='ced_catch_variation_product_content'>";
				$this->render_fields( $variation->ID );
				echo '</div>';
				echo '</div>';
				?>
			</div></div>
			<?php
		}
	}


	public function ced_catch_update_product_attributes() {
		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$shop_id       = isset( $_POST['shop_id'] ) ? sanitize_text_field( $_POST['shop_id'] ) : '';
			$shop_id       = trim( $shop_id );
			$action        = 'products/attributes';
			$parameters    = '';
			$attr_response = $this->sendRequestObj->sendHttpRequestGet( $action, $parameters, $shop_id, '' );
			if ( isset( json_decode( $attr_response, true )['message'] ) && 'Unauthorized' == json_decode( $attr_response, true )['message'] ) {
				echo 'Unauthorized';
				die;
			}
			file_put_contents( CED_CATCH_DIRPATH . 'admin/catch/lib/json/catch-product-attribute.json', $attr_response );

			$action   = 'values_lists';
			$response = $this->sendRequestObj->sendHttpRequestGet( $action, $parameters, $shop_id, '' );
			file_put_contents( CED_CATCH_DIRPATH . 'admin/catch/lib/json/catch-product-listattribute.json', $response );

			if ( ! empty( $attr_response ) ) {
				echo 'Attributes Updated Successfully';
			} else {
				echo 'File Not Found';
			}

			wp_die();
		}
	}


	public function ced_catch_map_order_status() {
		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$woo_order_status                = isset( $_POST['woo_order_status'] ) ? sanitize_text_field( $_POST['woo_order_status'] ) : '';
			$ced_catch_order_status          = isset( $_POST['ced_catch_order_status'] ) ? sanitize_text_field( $_POST['ced_catch_order_status'] ) : '';
			$ced_catch_mapped_order_statuses = get_option( 'ced_catch_mapped_order_statuses', array() );
			if ( ! empty( $woo_order_status ) ) {
				echo 'Order status mapped successfully';
				$ced_catch_mapped_order_statuses[ $ced_catch_order_status ] = $woo_order_status;
			} elseif ( isset( $ced_catch_mapped_order_statuses[ $ced_catch_order_status ] ) ) {
				echo 'Order status unmapped successfully';
				unset( $ced_catch_mapped_order_statuses[ $ced_catch_order_status ] );
			}

			update_option( 'ced_catch_mapped_order_statuses', $ced_catch_mapped_order_statuses );
			wp_die();
		}
	}


	public function ced_catch_search_product_name() {

		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$keyword      = isset( $_POST['keyword'] ) ? sanitize_text_field( $_POST['keyword'] ) : '';
			$product_list = '';
			if ( ! empty( $keyword ) ) {
				$arguements = array(
					'numberposts' => -1,
					'post_type'   => array( 'product', 'product_variation' ),
					's'           => $keyword,
				);
				$post_data  = get_posts( $arguements );
				if ( ! empty( $post_data ) ) {
					foreach ( $post_data as $key => $data ) {
						$product_list .= '<li class="ced_catch_searched_product" data-post-id="' . esc_attr( $data->ID ) . '">' . esc_html( __( $data->post_title, 'catch-woocommerce-integration' ) ) . '</li>';
					}
				} else {
					$product_list .= '<li>No products found.</li>';
				}
			} else {
				$product_list .= '<li>No products found.</li>';
			}
			echo json_encode( array( 'html' => $product_list ) );
			wp_die();
		}
	}

	public function ced_catch_get_product_metakeys() {

		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$product_id = isset( $_POST['post_id'] ) ? sanitize_text_field( $_POST['post_id'] ) : '';
			include_once CED_CATCH_DIRPATH . 'admin/partials/ced-catch-metakeys-list.php';
		}
	}

	public function ced_catch_process_metakeys() {

		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$metakey   = isset( $_POST['metakey'] ) ? sanitize_text_field( wp_unslash( $_POST['metakey'] ) ) : '';
			$operation = isset( $_POST['operation'] ) ? sanitize_text_field( wp_unslash( $_POST['operation'] ) ) : '';
			if ( ! empty( $metakey ) ) {
				$added_meta_keys = get_option( 'ced_catch_selected_metakeys', array() );
				if ( 'store' == $operation ) {
					$added_meta_keys[ $metakey ] = $metakey;
				} elseif ( 'remove' == $operation ) {
					unset( $added_meta_keys[ $metakey ] );
				}
				update_option( 'ced_catch_selected_metakeys', $added_meta_keys );
				echo json_encode( array( 'status' => 200 ) );
				die();
			} else {
				echo json_encode( array( 'status' => 400 ) );
				die();
			}
		}
	}

	public function ced_catch_save_product_fields( $post_id = '', $i = '' ) {
		if ( empty( $post_id ) ) {
			return;
		}
		$ced_catch_metabox_nonce = isset( $_POST['ced_catch_variation_nonce'] ) ? sanitize_text_field( $_POST['ced_catch_variation_nonce'] ) : '';
		if ( wp_verify_nonce( $ced_catch_metabox_nonce, 'ced_catch_variation_nonce' ) ) {
			if ( isset( $_POST['ced_catch_data'] ) ) {
				$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
				if ( ! empty( $sanitized_array ) ) {
					foreach ( $sanitized_array['ced_catch_data'] as $id => $value ) {
						foreach ( $value as $meta_key => $meta_val ) {
							update_post_meta( $id, $meta_key, $meta_val );
						}
					}
				}
			}
		}
	}

	public function ced_catch_save_metadata( $post_id = '' ) {
		if ( ! $post_id ) {
			return;
		}

		if ( $post_id ) {
			$ced_catch_metabox_nonce = isset( $_POST['ced_catch_metabox_nonce'] ) ? sanitize_text_field( $_POST['ced_catch_metabox_nonce'] ) : '';
			if ( wp_verify_nonce( $ced_catch_metabox_nonce, 'ced_catch_metabox_nonce' ) ) {
				if ( isset( $_POST['ced_catch_data'] ) ) {
					$ced_catch_data = array_map( 'sanitize_text_field', $_POST['ced_catch_data'] );
					if ( ! empty( $ced_catch_data ) && is_array( $ced_catch_data ) ) {
						foreach ( $ced_catch_data as $key => $value ) {
							if ( ! empty( $value ) && is_array( $value ) ) {
								foreach ( $value as $meta_key => $meta_val ) {
									update_post_meta( $key, $meta_key, $meta_val );
								}
							}
						}
					}
				}
				if ( isset( $_POST['_ced_catch_custom_description'] ) ) {
					update_post_meta( $post_id, '_ced_catch_custom_description', wp_kses_post( $_POST['_ced_catch_custom_description'] ) );
				}
			}

			if ( isset( $_POST['ced_catch_carrier_code'] ) ) {
				update_post_meta( $post_id, 'ced_catch_carrier_code', sanitize_text_field( $_POST['ced_catch_carrier_code'] ) );
			}

			if ( isset( $_POST['ced_catch_carrier_name'] ) ) {
				update_post_meta( $post_id, 'ced_catch_carrier_name', sanitize_text_field( $_POST['ced_catch_carrier_name'] ) );
			}

			if ( isset( $_POST['ced_catch_carrier_url'] ) ) {
				update_post_meta( $post_id, 'ced_catch_carrier_url', sanitize_text_field( $_POST['ced_catch_carrier_url'] ) );
			}

			if ( isset( $_POST['ced_catch_tracking_number'] ) ) {
				update_post_meta( $post_id, 'ced_catch_tracking_number', sanitize_text_field( $_POST['ced_catch_tracking_number'] ) );
			}

			$shop_id     = get_option( 'ced_catch_shop_id', '' );
			$shop_id     = trim( $shop_id );
			$is_uploaded = get_post_meta( $post_id, 'ced_catch_product_on_catch_' . $shop_id, true );
			if ( $is_uploaded ) {
				$is_transient_set = get_transient( 'ced_update_offers' );
				if ( ! $is_transient_set ) {
					$this->ced_catch_manager->prepareProductHtmlForOffer( array( $post_id ), $shop_id, 'UPDATE', true );
					set_transient( 'ced_update_offers', true, 60 );
				}
			}
		}
	}


	public function ced_catch_sync_products_using_identifier() {

		require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchProducts.php';
		$ced_catch_products_instance = new Class_Ced_Catch_Products();
		$current_action              = current_action();
		$shop_id                     = str_replace( 'ced_catch_sync_existing_products_', '', $current_action );

		$products_to_sync            = get_option( 'ced_catch_products_Ids_to_be_synced', array() );
		$ced_catch_sync_existing_log = '';

		if ( empty( $products_to_sync ) ) {
			$all_product_ids = get_posts(
				array(
					'numberposts' => -1,
					'post_type'   => array( 'product_variation', 'product' ),
					'meta_query'  => array(
						array(
							'key'     => 'ced_catch_product_on_catch_' . $shop_id,
							'compare' => 'NOT EXISTS',
						),
					),
					'fields'      => 'ids',
				)
			);

			if ( ! empty( $all_product_ids ) ) {
				$products_to_sync = array_chunk( $all_product_ids, 10 );
			}
		}

		if ( ! empty( $products_to_sync[0] ) ) {
			$sync_data                  = get_option( 'ced_catch_sync_existing_product_data_' . $shop_id, array() );
			$ced_catch_json_product_ids = json_encode( $products_to_sync[0] );

			$ced_catch_sync_existing_log .= 'Date :' . date_i18n( 'Y-m-d H:i:s' ) . "\r\nMessage : processing of Product Ids : " . $ced_catch_json_product_ids . "\r\n\n";

			$id_list     = array();
			$pro_id_list = array();
			foreach ( $products_to_sync[0] as $product_id ) {
				$product_reference_value = $ced_catch_products_instance->fetchMetaValueOfProduct( $product_id, 'sync_existing_product', true, $sync_data[ $shop_id ] );

				$product_reference_type = $ced_catch_products_instance->fetchMetaValueOfProduct( $product_id, 'ced_catch_syncing_identifier_type', true, $sync_data[ $shop_id ] );

				if ( ! empty( $product_reference_type ) && ! empty( $product_reference_value ) ) {
					$id_list[]                                       = strtoupper( $product_reference_type ) . '|' . trim( $product_reference_value );
					$pro_id_list[ trim( $product_reference_value ) ] = $product_id;
				}
			}

			if ( ! empty( $id_list ) ) {
				$product_references = implode( ',', $id_list );
				$action             = 'products?product_references=' . $product_references;

				$response = $this->sendRequestObj->sendHttpRequestGet( $action, array(), $shop_id );
				$response = json_decode( $response, true );

				$product_ids = array();
				if ( isset( $response['products'] ) && ! empty( $response['products'] ) ) {
					foreach ( $response['products'] as $index => $data ) {
						if ( isset( $data['product_id'] ) && isset( $pro_id_list[ trim( $data['product_id'] ) ] ) && ! empty( $pro_id_list[ trim( $data['product_id'] ) ] ) ) {

							$product_to_sync = $pro_id_list[ trim( $data['product_id'] ) ];

							$catch_sku = $data['product_sku'];
							$product   = wc_get_product( $product_to_sync );
							$type      = '';
							if ( is_object( $product ) ) {
								$type = $product->get_type();
								if ( 'variation' == $type ) {
									$parent_id     = $product->get_parent_id();
									$product_ids[] = $parent_id;
								} else {
									$product_ids[] = $product_to_sync;
								}
							}
							$ced_catch_sync_existing_log .= 'Product Id :' . $product_to_sync . "\t\tResponse : Product found on catch with Catch Sku " . $catch_sku . "\r\n";

							update_post_meta( $product_to_sync, 'ced_catch_product_sku', $catch_sku );
							update_post_meta( $product_to_sync, 'ced_catch_product_sync_by_identifier_' . $shop_id, 'yes' );
							if ( ! empty( $parent_id ) ) {
								update_post_meta( $parent_id, 'ced_catch_product_sync_by_identifier_' . $shop_id, 'yes' );
							}
							$proIdPosition = array_search( $product_to_sync, $products_to_sync[0] );
							unset( $products_to_sync[0][ $proIdPosition ] );
						}
					}
					$product_ids = array_unique( $product_ids );
				}
				$ced_catch_remaining_proIds   = json_encode( $products_to_sync[0] );
				$ced_catch_sync_existing_log .= 'Remaining Product Ids :' . $ced_catch_remaining_proIds . "\r\n\n";

				$this->ced_catch_manager->ced_catch_custom_log( 'cedcommerce/catch/logs/sync_existing', '', $ced_catch_sync_existing_log );

				unset( $products_to_sync[0] );
				$products_to_sync = array_values( $products_to_sync );
				update_option( 'ced_catch_products_Ids_to_be_synced', $products_to_sync );
			}
		}
	}


	/*
	*
	*Function for Processing different Bulk Actions
	*
	*
	*/

	public function ced_catch_process_bulk_action() {
		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$ced_catch_manager = $this->ced_catch_manager;
			$shop_id           = isset( $_POST['shopid'] ) ? sanitize_text_field( $_POST['shopid'] ) : '';
			global $wpdb;
			$tableName = $wpdb->prefix . 'ced_catch_profiles';

			$isShopInActive = ced_catch_inactive_shops( $shop_id );
			if ( $isShopInActive ) {
				echo json_encode(
					array(
						'status'  => 400,
						'message' => __(
							'Shop is Not Active',
							'woocommerce-catch-integration'
						),
					)
				);
				die;
			}

			$operation         = isset( $_POST['operation_to_be_performed'] ) ? sanitize_text_field( $_POST['operation_to_be_performed'] ) : '';
			$product_id_arrays = isset( $_POST['id'] ) ? map_deep( wp_unslash( $_POST['id'] ), 'sanitize_text_field' ) : '';

			$product_ids = array();
			if ( ! empty( $product_id_arrays ) && is_array( $product_id_arrays ) ) {
				foreach ( $product_id_arrays as $pro_key => $pro_value ) {
					if ( ! empty( $pro_value['profileId'] ) ) {
						$product_ids[ $pro_value['profileId'] ][] = $pro_value['prodIds'];
					}
				}
			}
			$msg = '';

			if ( is_array( $product_ids ) && ! empty( $product_ids ) ) {
				$prodIDs = $product_ids;
				if ( ! empty( $prodIDs ) && is_array( $prodIDs ) ) {
					foreach ( $prodIDs as $profile_key => $prodID ) {
						$profile_name_data = $wpdb->get_results( $wpdb->prepare( "SELECT `profile_name` FROM {$wpdb->prefix}ced_catch_profiles WHERE `id`= %d", $profile_key ), 'ARRAY_A' );
						$profile_name      = $profile_name_data[0]['profile_name'];

						if ( 'upload_product' == $operation ) {
							$get_product_detail = $ced_catch_manager->prepareProductHtmlForUpload( $prodID, $shop_id, $profile_key );
							if ( isset( $get_product_detail['import_id'] ) ) {
								$import_ids = $get_product_detail['import_id'];
								$msg       .= 'Product( Import Id ' . $import_ids . ' ) File Uploaded Successfully for Profile : <b>' . $profile_name . '</b></br>';
							} elseif ( isset( $get_product_detail['message'] ) ) {
								if ( 'Filename for param file is required' == $get_product_detail['message'] ) {
									$msg .= 'Product feed not uploaded . Incomplete mapping configuration in profile : <b>' . $profile_name . '</b></br>';
								} else {
									$msg .= $get_product_detail['message'] . ' for Profile : <b>' . $profile_name . '</b></br>';
								}
							} else {
								$msg .= 'Product(s) File Not Uploaded </br>';
							}
						} elseif ( 'upload_offer_with_product' == $operation ) {
							$get_product_detail = $ced_catch_manager->prepareProductHtmlForOffer( $prodID, $shop_id, 'UPDATE', '', 'with_product', $profile_key );
							if ( isset( $get_product_detail['import_id'] ) ) {
								$import_ids = $get_product_detail['import_id'];
								$msg       .= 'Offer with Product( Import Id ' . $import_ids . ' ) File Uploaded Successfully for Profile : <b>' . $profile_name . '</b></br>';
							} elseif ( isset( $get_product_detail['message'] ) ) {
								$msg .= $get_product_detail['message'] . ' for Profile : <b>' . $profile_name . '</b></br>';
							} else {
								$msg .= 'Product(s) File Not Uploaded </br>';
							}
						} elseif ( 'upload_offer' == $operation ) {
							$get_product_detail = $ced_catch_manager->prepareProductHtmlForOffer( $prodID, $shop_id, 'UPDATE', '', '', $profile_key );
							if ( isset( $get_product_detail['import_id'] ) ) {
								$import_ids = $get_product_detail['import_id'];
								$msg       .= 'Offer( Import Id ' . $import_ids . ' ) File Uploaded Successfully for Profile : <b>' . $profile_name . '</b></br>';
							} elseif ( isset( $get_product_detail['message'] ) ) {
								$msg .= $get_product_detail['message'] . ' for Profile : <b>' . $profile_name . '</b></br>';
							} else {
								$msg .= 'Offer(s) File Not Uploaded </br>';
							}
						} elseif ( 'remove_offer' == $operation ) {
							$get_product_detail = $ced_catch_manager->prepareProductHtmlForOffer( $prodID, $shop_id, 'DELETE', '', '', $profile_key );
							if ( isset( $get_product_detail['import_id'] ) ) {
								$import_ids = $get_product_detail['import_id'];
								$msg       .= 'Offer Removal file( Import Id ' . $import_ids . ' ) Uploaded Successfully for Profile : <b>' . $profile_name . '</b></br>';
							} elseif ( isset( $get_product_detail['message'] ) ) {
								$msg .= $get_product_detail['message'] . ' for Profile : <b>' . $profile_name . '</b></br>';
							} else {
								$msg .= 'Offer(s) Removal File Not Uploaded </br>';
							}
						}
					}
					echo json_encode(
						array(
							'status'  => 200,
							'message' => $msg,
							'prodid'  => $prodIDs,
						)
					);
					die;
				}
			}
		}
	}


	public function ced_catch_auto_update_shipment() {

		$current_action = current_action();
		$shop_id        = str_replace( 'ced_catch_auto_update_shipment_', '', $current_action );
		$shop_id        = trim( $shop_id );
		$upload         = wp_upload_dir();
		$upload_dir     = $upload['basedir'];
		$upload_dir     = $upload_dir . '/cedcommerce/catch/logs/shipment';
		if ( ! is_dir( $upload_dir ) ) {
			mkdir( $upload_dir, 0755, true );
		}
		$ced_catch_log = '';

		$catch_orders = get_posts(
			array(
				'numberposts' => -1,
				'meta_query'  => array(
					'relation' => 'AND',
					array(
						'key'     => 'ced_catch_tracking_submit',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_ced_catch_order_id',
						'compare' => 'EXISTS',
					),
				),
				'post_type'   => wc_get_order_types(),
				'post_status' => array_keys( wc_get_order_statuses() ),
				'orderby'     => 'date',
				'order'       => 'DESC',
				'fields'      => 'ids',
			)
		);

		if ( ! empty( $catch_orders ) && is_array( $catch_orders ) ) {
			$fp = fopen( $upload_dir . '/' . gmdate( 'j.n.Y' ) . '.log', 'a' );
			foreach ( $catch_orders as $woo_order_id ) {
				$ced_catch_log = $this->ced_catch_auto_ship_order( $woo_order_id, $shop_id );
				if ( false !== $fp && get_resource_type( $fp ) == 'stream' ) {
					fwrite( $fp, $ced_catch_log );
				}
			}
			if ( false !== $fp && get_resource_type( $fp ) == 'stream' ) {
				fclose( $fp );
			}
		}
	}

	public function ced_catch_auto_ship_order( $order_id = 0, $shop_id = 0 ) {
		$ced_catch_tracking_number = '';
		$ced_catch_carrier_code    = '';

		$tracking_details = get_post_meta( $order_id, '_wc_shipment_tracking_items', true );
		$shop_id          = get_post_meta( $order_id, 'ced_catch_order_shop_id', true );
		$catch_order_id   = get_post_meta( $order_id, '_ced_catch_order_id', true );

		$ced_catch_log .= 'Date :' . date_i18n( 'Y-m-d H:i:s' ) . "\r\nMessage : processing of Order tracking : Order Id - " . $order_id . "\r\n";

		if ( ! empty( $tracking_details ) ) {

			$ced_catch_carrier_code = isset( $tracking_details[0]['custom_tracking_provider'] ) ? $tracking_details[0]['custom_tracking_provider'] : '';
			if ( empty( $ced_catch_carrier_code ) ) {
				$ced_catch_carrier_code = isset( $tracking_details[0]['tracking_provider'] ) ? $tracking_details[0]['tracking_provider'] : '';
			}
			$ced_catch_tracking_number = isset( $tracking_details[0]['tracking_number'] ) ? $tracking_details[0]['tracking_number'] : '';

			if ( ! empty( $ced_catch_tracking_number ) && ! empty( $ced_catch_carrier_code ) ) {

				$actionShip = 'orders/' . $catch_order_id . '/ship';
				$action     = 'orders/' . $catch_order_id . '/tracking';
				$parameters = array(
					'carrier_code'    => $ced_catch_carrier_code,
					'carrier_name'    => $ced_catch_carrier_name,
					'carrier_url'     => $ced_catch_carrier_url,
					'tracking_number' => $ced_catch_tracking_number,
				);

				$response = $this->sendRequestObj->sendHttpRequestPut( $action, $parameters, $shop_id );

				if ( ! empty( $response ) ) {
					$response = json_decode( $response, true );
				}

				if ( isset( $response['message'] ) && ! empty( $response['message'] ) ) {
					update_post_meta( $order_id, 'ced_catch_tracking_error', $response['message'] );
					$ced_catch_log .= 'Error - ' . $response['message'] . "\r\n\n";

				} else {
					$this->sendRequestObj->sendHttpRequestPut( $actionShip, array(), $shop_id );

					update_post_meta( $order_id, 'ced_catch_tracking_submit', 'yes' );
					update_post_meta( $order_id, 'ced_catch_carrier_code', $ced_catch_carrier_code );
					update_post_meta( $order_id, 'ced_catch_tracking_number', $ced_catch_tracking_number );
					update_post_meta( $order_id, '_catch_umb_order_status', 'Shipped' );
					$ced_catch_log .= 'Tracking No : ' . $ced_catch_tracking_number . ' Carrier Code :' . $ced_catch_carrier_code . "\r\nResponse - Tracking Submitted Successfully\r\n\n";
				}

				$order_obj = wc_get_order( $order_id );
				if ( is_object( $order_obj ) ) {
					$order_obj->update_status( 'wc-completed' );
				}
			} else {
				$ced_catch_log .= "Reason - Tracking Details Not Found\r\n\n";
			}
		} else {
				$ced_catch_log .= "Reason - Tracking Details Not Found\r\n\n";
		}
		return $ced_catch_log;
	}


	public function ced_catch_auto_update_product_status() {
		$current_action = current_action();
		$shop_id        = str_replace( 'ced_catch_update_product_status_', '', $current_action );
		$shop_id        = trim( $shop_id );
		$ImportIds      = get_option( 'ced_catch_import_ids_' . $shop_id, array() );
		$ImportIds      = array_reverse( $ImportIds );
		require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchSendHttpRequest.php';
		$sendRequestObj = new Class_Ced_Catch_Send_Http_Request();

		$wpuploadDir = wp_upload_dir();
		$baseDir     = $wpuploadDir['basedir'];

		$ced_catch_auto_update_product_status = get_option( 'ced_catch_auto_update_product_status', array() );
		if ( empty( $ced_catch_auto_update_product_status ) ) {
			global $wpdb;
			$import_ids = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%s  order by `post_id` DESC", 'ced_catch_update_product_status_' . $shop_id, 'yes' ), 'ARRAY_A' );

			if ( ! empty( $import_ids ) ) {
				$ced_catch_auto_update_product_status = array_chunk( $import_ids, 10 );
			}
		}

		if ( ! empty( $ced_catch_auto_update_product_status[0] ) && is_array( $ced_catch_auto_update_product_status[0] ) ) {
			foreach ( $ced_catch_auto_update_product_status[0] as $key => $value ) {
				if ( isset( $value['post_id'] ) && ! empty( $value['post_id'] ) ) {
					$uploadType     = 'Product';
					$import_id      = $value['post_id'];
					$uploadDir      = $baseDir . '/cedcommerce/catch/feeds/' . $uploadType;
					$status_updated = get_post_meta( $import_id, 'ced_catch_status_completed_' . $shop_id );
					$message        = '';

					$import_status_response = get_option( 'ced_catch_import_status_response' . $import_id, array() );
					if ( empty( $import_status_response ) || isset( $import_status_response ) && ! empty( $import_status_response ) && isset( $import_status_response['import_status'] ) && 'COMPLETE' != $import_status_response['import_status'] || isset( $import_status_response['status'] ) && 'COMPLETE' != $import_status_response['status'] ) {

						$ced_catch_action = 'products/imports/' . $import_id;
						$response         = $sendRequestObj->sendHttpRequestGet( $ced_catch_action, $import_id, $shop_id, '' );
						$response         = json_decode( $response, true );
						update_option( 'ced_catch_import_status_response' . $import_id, $response );
					}

					$response = get_option( 'ced_catch_import_status_response' . $import_id, array() );
					if ( isset( $response ) && ! empty( $response ) && isset( $response['import_status'] ) && 'COMPLETE' == $response['import_status'] || isset( $response['status'] ) && 'COMPLETE' == $response['status'] ) {

						$ced_catch_action = 'products/imports/' . $import_id . '/error_report';
						$file_error       = $uploadDir . '/Feed' . $import_id . '.csv';
						if ( isset( $response['has_error_report'] ) && ! empty( $response['has_error_report'] ) && ! file_exists( $file_error ) ) {
							$error_report_response = $sendRequestObj->sendHttpRequestGet( $ced_catch_action, $import_id, $shop_id, '' );
							if ( ( is_string( $error_report_response ) && ( is_object( json_decode( $error_report_response ) ) || is_array( json_decode( $error_report_response ) ) ) ) ) {
								$error_report_response = json_decode( $error_report_response, true );
							}
							if ( ! isset( $error_report_response['status'] ) ) {
								if ( ! is_dir( $uploadDir ) ) {
									mkdir( $uploadDir, 0777, true );
								}
								$file = $uploadDir . '/Feed' . $import_id . '.csv';
								file_put_contents( $file, $error_report_response );
							} else {
								$message = $error_report_response['message'];
							}
						}

						$file_path_product = $uploadDir . '/Integration_Feed' . $import_id . '.csv';
						if ( isset( $response['has_error_report'] ) && isset( $response['has_transformation_error_report'] ) && ! file_exists( $file_path_product ) ) {

							$ced_catch_action     = 'products/imports/' . $import_id . '/new_product_report';
							$integration_response = $sendRequestObj->sendHttpRequestGet( $ced_catch_action, $import_id, $shop_id, '' );

							if ( ( is_string( $integration_response ) && ( is_object( json_decode( $integration_response ) ) || is_array( json_decode( $integration_response ) ) ) ) ) {
								$integration_response = json_decode( $integration_response, true );
							}

							if ( ! isset( $integration_response['status'] ) ) {
								if ( ! is_dir( $uploadDir ) ) {
									mkdir( $uploadDir, 0777, true );
								}
								$file = $uploadDir . '/Integration_Feed' . $import_id . '.csv';
								file_put_contents( $file, $integration_response );
							}
						}
						// delete_post_meta( $import_id, 'ced_catch_update_product_status_' . $shop_id );
					}
				}
			}
			unset( $ced_catch_auto_update_product_status[0] );
			$ced_catch_auto_update_product_status = array_values( $ced_catch_auto_update_product_status );
			update_option( 'ced_catch_auto_update_product_status', $ced_catch_auto_update_product_status );
		}
	}

	public function ced_catch_auto_change_product_status() {
		$current_action = current_action();
		$shop_id        = str_replace( 'ced_catch_auto_change_product_status_', '', $current_action );
		$shop_id        = trim( $shop_id );
		$ImportIds      = get_option( 'ced_catch_import_ids_' . $shop_id, array() );
		$ImportIds      = array_reverse( $ImportIds );
		require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchSendHttpRequest.php';
		$sendRequestObj = new Class_Ced_Catch_Send_Http_Request();

		$wpuploadDir = wp_upload_dir();
		$baseDir     = $wpuploadDir['basedir'];

		$ced_catch_auto_update_product_status = get_option( 'ced_catch_auto_update_product_status_from_file', array() );

		if ( empty( $ced_catch_auto_update_product_status ) ) {
			global $wpdb;
			$import_ids                           = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%s  order by `post_id` DESC", 'ced_catch_update_product_status_' . $shop_id, 'yes' ), 'ARRAY_A' );
			$ced_catch_auto_update_product_status = $import_ids;
		}

		if ( ! empty( $ced_catch_auto_update_product_status[0] ) && is_array( $ced_catch_auto_update_product_status[0] ) ) {
			if ( isset( $ced_catch_auto_update_product_status[0]['post_id'] ) && ! empty( $ced_catch_auto_update_product_status[0]['post_id'] ) ) {
				$uploadType = 'Product';
				$import_id  = $ced_catch_auto_update_product_status[0]['post_id'];
				$uploadDir  = $baseDir . '/cedcommerce/catch/feeds/' . $uploadType;

				$filename   = $uploadDir . '/Integration_Feed' . $import_id . '.csv';
				$error_file = $uploadDir . '/Feed' . $import_id . '.csv';

				if ( file_exists( $filename ) ) {

					$ced_catch_product_integration_file_data = get_option( 'ced_catch_product_integration_file_data', array() );
					if ( empty( $ced_catch_product_integration_file_data ) ) {
						$ced_catch_product_integration_file_data = array_map( 'str_getcsv', file( $filename ) );
						unset( $ced_catch_product_integration_file_data[0] );
						$ced_catch_product_integration_file_data = array_values( $ced_catch_product_integration_file_data );
					}

					if ( ! empty( $ced_catch_product_integration_file_data[0] ) && is_array( $ced_catch_product_integration_file_data[0] ) ) {
						$catch_products_data  = $ced_catch_product_integration_file_data[0][0];
						$catch_product_status = explode( ';', $catch_products_data );
						$sku                  = isset( $catch_product_status[1] ) ? $catch_product_status[1] : '';
						$prodid               = $this->ced_catch_if_product_exists_in_store( $sku );
						if ( $prodid ) {
							$product = wc_get_product( $prodid );
							update_post_meta( $prodid, 'ced_catch_uploaded_product_status' . $shop_id, $catch_product_status[3] );
							update_post_meta( $prodid, 'ced_catch_uploaded_product_status_reason' . $shop_id, $catch_product_status[4] );
							if ( $product->get_type() == 'variation' ) {
								$parentId = $product->get_parent_id();
								update_post_meta( $parentId, 'ced_catch_uploaded_product_status' . $shop_id, $catch_product_status[3] );
								update_post_meta( $parentId, 'ced_catch_uploaded_product_status_reason' . $shop_id, $catch_product_status[4] );
							}
						}

						unset( $ced_catch_product_integration_file_data[0] );
						$ced_catch_product_integration_file_data = array_values( $ced_catch_product_integration_file_data );
						update_option( 'ced_catch_product_integration_file_data', $ced_catch_product_integration_file_data );
						if ( empty( $ced_catch_product_integration_file_data ) ) {
							unset( $ced_catch_auto_update_product_status[0] );
							$ced_catch_auto_update_product_status = array_values( $ced_catch_auto_update_product_status );
							update_option( 'ced_catch_auto_update_product_status_from_file', $ced_catch_auto_update_product_status );
						}
					} else {
						unset( $ced_catch_auto_update_product_status[0] );
						$ced_catch_auto_update_product_status = array_values( $ced_catch_auto_update_product_status );
						update_option( 'ced_catch_auto_update_product_status_from_file', $ced_catch_auto_update_product_status );
					}
				} elseif ( file_exists( $error_file ) ) {

					$ced_catch_product_error_data = get_option( 'ced_catch_product_error_data', array() );
					if ( empty( $ced_catch_product_error_data ) ) {
						$ced_catch_product_error_data = array_map( 'str_getcsv', file( $error_file ) );
						unset( $ced_catch_product_error_data[0] );
						$ced_catch_product_error_data = array_values( $ced_catch_product_error_data );
					}

					if ( ! empty( $ced_catch_product_error_data[0] ) && is_array( $ced_catch_product_error_data[0] ) ) {
						$products_error     = $ced_catch_product_error_data[0][0];
						$product_feed_array = explode( ';', $products_error );
						$sku                = isset( $product_feed_array[1] ) ? $product_feed_array[1] : '';
						$prodid             = $this->ced_catch_if_product_exists_in_store( $sku );
						if ( $prodid ) {
							$product = wc_get_product( $prodid );
							update_post_meta( $prodid, 'ced_catch_uploaded_product_status_reason' . $shop_id, $product_feed_array[4] );
							if ( $product->get_type() == 'variation' ) {
								$parentId = $product->get_parent_id();
								update_post_meta( $parentId, 'ced_catch_uploaded_product_status_reason' . $shop_id, $product_feed_array[4] );
							}
						}

						unset( $ced_catch_product_error_data[0] );
						$ced_catch_product_error_data = array_values( $ced_catch_product_error_data );
						update_option( 'ced_catch_product_error_data', $ced_catch_product_error_data );
						if ( empty( $ced_catch_product_error_data ) ) {
							unset( $ced_catch_auto_update_product_status[0] );
							$ced_catch_auto_update_product_status = array_values( $ced_catch_auto_update_product_status );
							update_option( 'ced_catch_auto_update_product_status_from_file', $ced_catch_auto_update_product_status );
						}
					} else {
						unset( $ced_catch_auto_update_product_status[0] );
						$ced_catch_auto_update_product_status = array_values( $ced_catch_auto_update_product_status );
						update_option( 'ced_catch_auto_update_product_status_from_file', $ced_catch_auto_update_product_status );
					}
				} else {
					unset( $ced_catch_auto_update_product_status[0] );
					$ced_catch_auto_update_product_status = array_values( $ced_catch_auto_update_product_status );
					update_option( 'ced_catch_auto_update_product_status_from_file', $ced_catch_auto_update_product_status );
				}
			}
		}
	}

	public function ced_catch_display_product_status() {
		$check_ajax = check_ajax_referer( 'ced-catch-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$shop_id                    = isset( $_POST['shop_id'] ) ? sanitize_text_field( $_POST['shop_id'] ) : '';
			$prod_id                    = isset( $_POST['prod_id'] ) ? sanitize_text_field( $_POST['prod_id'] ) : '';
			$ced_catch_validation_error = get_post_meta( $prod_id, 'ced_catch_validation_error', true );
			$prod_data                  = wc_get_product( $prod_id );
			if ( ! is_object( $prod_data ) ) {
				return;
			}
			$type            = $prod_data->get_type();
			$variation_error = '';
			if ( 'variable' == $type ) {
				$variations = $prod_data->get_available_variations();
				foreach ( $variations as $variation ) {
					$variation_id               = $variation['variation_id'];
					$ced_catch_validation_error = get_post_meta( $variation_id, 'ced_catch_validation_error', true );
					$variation_error           .= '<ul> Variation Id : ' . esc_attr( $variation_id );
					if ( is_array( $ced_catch_validation_error ) && ! empty( $ced_catch_validation_error ) ) {
						foreach ( $ced_catch_validation_error as $attribute => $message ) {
							$variation_error .= '<li class="catch-error">' . esc_attr( $attribute + 1 ) . ' : ' . esc_attr( $message ) . '</li>';
						}
					}
				}
				$variation_error      .= '</ul>';
				$show_validation_error = '<span class="close">&times;</span>' . $variation_error . '</br>';
				print_r( $show_validation_error );
			} else {
				if ( is_array( $ced_catch_validation_error ) && ! empty( $ced_catch_validation_error ) ) {
					$variation_error .= '<ul> Product Id : ' . esc_attr( $prod_id );
					foreach ( $ced_catch_validation_error as $attribute => $message ) {
						$variation_error .= '<li class="catch-error">' . esc_attr( $attribute + 1 ) . ' : ' . esc_attr( $message ) . '</li>';
					}
					$show_validation_error = '<span class="close">&times;</span>' . $variation_error . '</ul></br>';
					print_r( $show_validation_error );
				}
			}
			wp_die();
		}
	}

}
