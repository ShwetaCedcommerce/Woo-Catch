<?php
/**
 * Main class for handling reqests.
 *
 * @since      1.0.0
 *
 * @package    Woocommerce catch Integration
 * @subpackage Woocommerce catch Integration/admin/catch
 */

if ( ! class_exists( 'Class_Ced_Catch_Manager' ) ) {

	/**
	 * Single product related functionality.
	 *
	 * Manage all single product related functionality required for listing product on admin.
	 *
	 * @since      1.0.0
	 * @package    Woocommerce catch Integration
	 * @subpackage Woocommerce catch Integration/admin/catch
	 * author     CedCommerce <cedcommerce.com>
	 */
	class Class_Ced_Catch_Manager {

		/**
		 * The Instace of CED_catch_catch_Manager.
		 *
		 * @since    1.0.0
		 * access   private
		 * @var      $_instance   The Instance of CED_catch_catch_Manager class.
		 */
		private static $_instance;
		private static $authorization_obj;
		private static $client_obj;
		/**
		 * CED_catch_catch_Manager Instance.
		 *
		 * Ensures only one instance of CED_catch_catch_Manager is loaded or can be loaded.
		 *
		 * author CedCommerce <plugins@cedcommerce.com>
		 *
		 * @since 1.0.0
		 * @static
		 * @return CED_catch_catch_Manager instance.
		 */
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		public $marketplaceID   = 'catch';
		public $marketplaceName = 'catch';


		public function __construct() {
			add_action( 'admin_init', array( $this, 'ced_catch_fetch_orders_schedule' ) );
			$this->loadDependency();
			add_action( 'woocommerce_order_status_completed', array( $this, 'ced_catch_order_submit_tracking' ) );
			add_action( 'woocommerce_thankyou', array( $this, 'ced_catch_update_inventory_on_order_creation' ), 10, 1 );
			add_filter( 'woocommerce_duplicate_product_exclude_meta', array( $this, 'ced_catch_woocommerce_duplicate_product_exclude_meta' ) );
			add_action( 'admin_notices', array( $this, 'ced_catch_admin_notices' ) );
		}

		public function ced_catch_admin_notices() {

			if ( isset( $_GET['page'] ) && 'ced_catch' == $_GET['page'] ) {
				$url = 'https://cedcommerce.com/contacts';
				echo "<div class='notice notice-success is-dismissible'><p><i><a>NOTICE</a> : Thank you for choosing <b><i>Catch Integration for WooCommerce</i></b> . If you have any questions or need any assistance regarding the plugin feel free to contact us using the chat icon at the bottom or <a href='" . esc_url( $url ) . "' target='_blank'>here</a> .</i></p></div>";
			}
		}

		public function ced_catch_woocommerce_duplicate_product_exclude_meta( $metakeys = array() ) {
			$shopID     = get_option( 'ced_catch_active_shop', '' );
			$metakeys[] = 'ced_catch_product_on_catch_' . $shopID;
			$metakeys[] = 'ced_catch_product_sku';
			return $metakeys;
		}

		public function ced_catch_update_inventory_on_save_post( $post_id ) {
			if ( empty( $post_id ) ) {
				return;
			}
			$response = $this->prepareProductHtmlForOffer( array( $post_id ), '', 'UPDATE', true );
		}

		public function ced_catch_update_inventory_on_order_creation( $order_id ) {
			if ( empty( $order_id ) ) {
				return;
			}
			$product_ids   = array();
			$inventory_log = array();
			$shopID        = get_option( 'ced_catch_active_shop', '' );
			$order_obj     = wc_get_order( $order_id );
			$order_items   = $order_obj->get_items();
			if ( is_array( $order_items ) && ! empty( $order_items ) ) {
				foreach ( $order_items as $key => $value ) {
					$product_id = $value->get_data()['product_id'];
					$on_catch   = get_post_meta( $product_id, 'ced_catch_product_on_catch_' . $shopID, true );
					if ( $on_catch ) {
						$product_ids[] = $product_id;
					} else {
						continue;
					}
				}
			}
			if ( is_array( $product_ids ) && ! empty( $product_ids ) ) {

				$response        = $this->prepareProductHtmlForOffer( $product_ids, '', 'UPDATE', true );
				$inventory_log[] = $response;
			}
			update_option( 'update_inventory_on_order_creation', $inventory_log );
		}



		public function ced_catch_order_submit_tracking( $order_id = '' ) {
			if ( empty( $order_id ) ) {
				return;
			}

			$shop_id        = get_post_meta( $order_id, 'ced_catch_order_shop_id', true );
			$catch_order_id = get_post_meta( $order_id, '_ced_catch_order_id', true );
			$carrier_code   = get_post_meta( $order_id, 'ced_catch_carrier_code', true );
			$carrier_name   = get_post_meta( $order_id, 'ced_catch_carrier_name', true );
			$carrier_url    = get_post_meta( $order_id, 'ced_catch_carrier_url', true );
			$tracking_no    = get_post_meta( $order_id, 'ced_catch_tracking_number', true );

			$carrier_code = ! empty( $carrier_code ) ? $carrier_code : '';
			$carrier_name = ! empty( $carrier_name ) ? $carrier_name : '';
			$carrier_url  = ! empty( $carrier_url ) ? $carrier_url : '';
			$tracking_no  = ! empty( $tracking_no ) ? $tracking_no : '';

			$action     = 'orders/' . $catch_order_id . '/tracking';
			$parameters = array(
				'carrier_code'    => $carrier_code,
				'carrier_name'    => $carrier_name,
				'carrier_url'     => $carrier_url,
				'tracking_number' => $tracking_no,
			);

			$actionShip = 'orders/' . $catch_order_id . '/ship';
			$this->sendRequestObj->sendHttpRequestPut( $action, $parameters, $shop_id );
			$this->sendRequestObj->sendHttpRequestPut( $actionShip, array(), $shop_id );
			update_post_meta( $order_id, '_catch_umb_order_status', 'Shipped' );
		}

		public function ced_catch_fetch_orders_schedule() {

			$shop_id              = isset( $_GET['shop_id'] ) ? sanitize_text_field( wp_unslash( $_GET['shop_id'] ) ) : '';
			$renderGlobalSettings = get_option( 'ced_catch_global_settings', false );
			if ( $shop_id ) {
				$is_scheduled                        = wp_get_schedule( 'ced_catch_Fetch_orders_' . $shop_id );
				$ced_catch_auto_fetch_order_schedule = isset( $renderGlobalSettings[ $shop_id ]['ced_catch_auto_fetch_order'] ) ? $renderGlobalSettings[ $shop_id ]['ced_catch_auto_fetch_order'] : '';
				if ( ! $is_scheduled && 'on' == $ced_catch_auto_fetch_order_schedule ) {
					wp_schedule_event( time(), 'ced_catch_6min', 'ced_catch_Fetch_orders_' . $shop_id );
				} else {
					wp_clear_scheduled_hook( 'ced_catch_Fetch_orders_' . $shop_id );
				}

				$is_another_scheduled = wp_get_schedule( 'ced_catch_Fetch_orders_by_id_' . $shop_id );
				if ( ! $is_another_scheduled ) {
					wp_schedule_event( time(), 'ced_catch_6min', 'ced_catch_Fetch_orders_by_id_' . $shop_id );
				}

				$is_sync_scheduled = wp_get_schedule( 'ced_catch_sync_products_' . $shop_id );
				if ( ! $is_sync_scheduled ) {
					wp_schedule_event( time(), 'ced_catch_10min', 'ced_catch_sync_products_' . $shop_id );
				}

				$is_sync_scheduled = wp_get_schedule( 'ced_catch_update_product_status_' . $shop_id );
				if ( ! $is_sync_scheduled ) {
					wp_schedule_event( time(), 'ced_catch_15min', 'ced_catch_update_product_status_' . $shop_id );
				}

				$is_sync_scheduled = wp_get_schedule( 'ced_catch_auto_change_product_status_' . $shop_id );
				if ( ! $is_sync_scheduled ) {
					wp_schedule_event( time(), 'ced_catch_15min', 'ced_catch_auto_change_product_status_' . $shop_id );
				}

				$is_sync_existing_products_scheduled = get_option( 'ced_catch_sync_existing_products_' . $shop_id, '' );
				if ( 'on' == $is_sync_existing_products_scheduled ) {
					$is_sync_existing_products = wp_get_schedule( 'ced_catch_sync_existing_products_' . $shop_id );
					if ( ! $is_sync_existing_products ) {
						wp_schedule_event( time(), 'ced_catch_2min', 'ced_catch_sync_existing_products_' . $shop_id );
					}
				} else {
					wp_clear_scheduled_hook( 'ced_catch_sync_existing_products_' . $shop_id );
				}
			}
		}

		public function ced_catch_custom_log( $filedir, $filename, $ced_catch_log_data ) {
			$upload     = wp_upload_dir();
			$upload_dir = $upload['basedir'];
			$upload_dir = $upload_dir . '/' . $filedir;
			if ( ! is_dir( $upload_dir ) ) {
				mkdir( $upload_dir, 0755, true );
			}
			$fp = fopen( $upload_dir . '/' . $filename . gmdate( 'j.n.Y' ) . '.log', 'a' );
			if ( false !== $fp && get_resource_type( $fp ) == 'stream' ) {
				fwrite( $fp, $ced_catch_log_data );
				fclose( $fp );
			}
		}


		public function ced_catch_update_integration_status() {
			if ( isset( $_GET['shop_id'] ) && ! empty( $_GET['shop_id'] ) ) {
				if ( ! wp_get_schedule( 'ced_catch_integration_report_' . sanitize_text_field( $_GET['shop_id'] ) ) ) {
					wp_schedule_event( time(), 'ced_catch_6min', 'ced_catch_integration_report_' . sanitize_text_field( $_GET['shop_id'] ) );
				}
			}
		}

		public function loadDependency() {

			$fileConfig = CED_CATCH_DIRPATH . 'admin/catch/lib/catchConfig.php';
			if ( file_exists( $fileConfig ) ) {
				require_once $fileConfig;
			}
			$fileProducts = CED_CATCH_DIRPATH . 'admin/catch/lib/catchProducts.php';
			if ( file_exists( $fileProducts ) ) {
				require_once $fileProducts;
			}

			require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchSendHttpRequest.php';
			$this->sendRequestObj = new Class_Ced_Catch_Send_Http_Request();

			$this->ced_catch_configInstance = new Ced_Catch_Config();
			$this->catchProductsInstance    = Class_Ced_Catch_Products::get_instance();
		}
		/*
		*
		*Creating Auto Profiles
		*
		*
		*/

		public function ced_catch_createAutoProfiles( $catchMappedCategories = array(), $catchMappedCategoriesName = array(), $catchStoreId = '' ) {

			global $wpdb;

			$wooStoreCategories          = get_terms( 'product_cat' );
			$alreadyMappedCategories     = get_option( 'ced_woo_catch_mapped_categories', array() );
			$alreadyMappedCategoriesName = get_option( 'ced_woo_catch_mapped_categories_name', array() );

			if ( ! empty( $catchMappedCategories ) ) {
				foreach ( $catchMappedCategories as $key => $value ) {
					$profileAlreadyCreated = get_term_meta( $key, 'ced_catch_profile_created_' . $catchStoreId, true );
					$createdProfileId      = get_term_meta( $key, 'ced_catch_profile_id_' . $catchStoreId, true );
					$profileName           = isset( $catchMappedCategoriesName[ $value ] ) ? $catchMappedCategoriesName[ $value ] : 'Profile for Catch - Category Id : ' . $value;

					if ( 'yes' == $profileAlreadyCreated && '' != $createdProfileId ) {

						$newProfileNeedToBeCreated = $this->checkIfNewProfileNeedToBeCreated( $key, $value, $catchStoreId, $profileName );

						if ( ! $newProfileNeedToBeCreated ) {
							continue;
						} else {
							$this->resetMappedCategoryData( $key, $value, $catchStoreId );
						}
					}

					$profile_exists = $this->ced_check_profile_exists( $key, $value, $catchStoreId, $profileName );

					if ( ! $profile_exists ) {
						continue;
					}

					$wooCategories      = array( $key );
					$categoryAttributes = array();
					$is_active          = 1;
					$marketplaceName    = 'Catch';

					$profileData = array();
					$profileData = $this->prepareProfileData( $catchStoreId, $value, $wooCategories );

					$profileDetails = array(
						'profile_name'   => $profileName,
						'profile_status' => 'active',
						'profile_data'   => json_encode( $profileData ),
						'shop_id'        => $catchStoreId,
						'woo_categories' => json_encode( $wooCategories ),
					);
					$profileId      = $this->insertCatchProfile( $profileDetails );
					foreach ( $wooCategories as $key12 => $value12 ) {
						update_term_meta( $value12, 'ced_catch_profile_created_' . $catchStoreId, 'yes' );
						update_term_meta( $value12, 'ced_catch_profile_id_' . $catchStoreId, $profileId );
						update_term_meta( $value12, 'ced_catch_mapped_category_' . $catchStoreId, $value );
					}
				}
			}
		}


		public function ced_check_profile_exists( $wooCategoryId = '', $CatchCategoryId = '', $catchStoreId = '', $profileName = '' ) {

			global $wpdb;
			$profileTableName = $wpdb->prefix . 'ced_catch_profiles';
			$check            = false;
			$woo_category     = array();
			$profile_data     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_catch_profiles WHERE %d", 1 ), 'ARRAY_A' );

			foreach ( $profile_data as $key => $value ) {
				if ( $value['profile_name'] == $profileName ) {
					$woo_category = json_decode( $value['woo_categories'] );
					$id           = $value['id'];
					if ( ! empty( $woo_category ) ) {
						foreach ( $woo_category as $woo_key => $woo_value ) {
							if ( $wooCategoryId != $woo_value ) {
								$check = true;
								continue;
							}
						}
						if ( true == $check ) {
							$woo_category[] = $wooCategoryId;
							update_term_meta( $wooCategoryId, 'ced_catch_profile_created_' . $catchStoreId, 'yes' );
							update_term_meta( $wooCategoryId, 'ced_catch_profile_id_' . $catchStoreId, $value['id'] );
							update_term_meta( $wooCategoryId, 'ced_catch_mapped_category_' . $catchStoreId, $CatchCategoryId );
						}
					}
					$woo_category = json_encode( $woo_category );
					$wpdb->query(
						$wpdb->prepare(
							"UPDATE {$wpdb->prefix}ced_catch_profiles
    				SET `woo_categories`= %s WHERE `id`= %d",
							$woo_category,
							$id
						)
					);
					return false;
				}
			}
			return true;
		}


		/*
		*
		*Updating profile for a woo category if mapped again
		*   *
		*
		*/

		public function resetMappedCategoryData( $wooCategoryId = '', $CatchCategoryId = '', $catchStoreId = '' ) {

			update_term_meta( $wooCategoryId, 'ced_catch_mapped_category_' . $catchStoreId, $CatchCategoryId );

			delete_term_meta( $wooCategoryId, 'ced_catch_profile_created_' . $catchStoreId );

			$createdProfileId = get_term_meta( $wooCategoryId, 'ced_catch_profile_id_' . $catchStoreId, true );

			delete_term_meta( $wooCategoryId, 'ced_catch_profile_id_' . $catchStoreId );

			$this->removeCategoryMappingFromProfile( $createdProfileId, $wooCategoryId );
		}

		/*
		*
		*removing previous mapped profile to a woo category
		*
		*
		*/


		public function removeCategoryMappingFromProfile( $createdProfileId = '', $wooCategoryId = '' ) {

			global $wpdb;
			$profileTableName = $wpdb->prefix . 'ced_catch_profiles';
			$profile_data     = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT `woo_categories` FROM {$wpdb->prefix}ced_catch_profiles WHERE `id`= %d",
					$createdProfileId
				),
				'ARRAY_A'
			);

			if ( is_array( $profile_data ) ) {
				$profile_data  = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
				$wooCategories = isset( $profile_data['woo_categories'] ) ? json_decode( $profile_data['woo_categories'], true ) : array();
				if ( is_array( $wooCategories ) && ! empty( $wooCategories ) ) {
					$categories = array();
					foreach ( $wooCategories as $key => $value ) {
						if ( $value != $wooCategoryId ) {
							$categories[] = $value;
						}
					}

					if ( ! empty( $categories ) ) {
						$categories = json_encode( $categories );
						$wpdb->update( $profileTableName, array( 'woo_categories' => $categories ), array( 'id' => $createdProfileId ) );
					} else {
						$deleteStatus = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_catch_profiles WHERE `id` IN (%d)", $createdProfileId ) );
					}
				}
			}
		}
		/*
		*
		*Checking if new profile to be created for woo category
		*
		*
		*/

		public function checkIfNewProfileNeedToBeCreated( $wooCategoryId = '', $CatchCategoryId = '', $catchStoreId = '', $profileName = '' ) {

			$oldCatchCategoryMapped = get_term_meta( $wooCategoryId, 'ced_catch_mapped_category_' . $catchStoreId, true );

			if ( $oldCatchCategoryMapped == $CatchCategoryId ) {
				return false;
			} else {
				return true;
			}
		}

		/*
		*
		*Preparing profile data for saving
		*
		*
		*/

		public function prepareProfileData( $catchStoreId, $catchCategoryId, $wooCategories = '' ) {
			$profileData = array();
			$shop_id     = $catchStoreId;

			$renderDataOnGlobalSettings                                   = get_option( 'ced_catch_global_profile_settings', false );
			$renderDataOnGlobalSettings                                   = json_decode( $renderDataOnGlobalSettings, true );
			$renderDataOnGlobalSettings['_umb_catch_category']['default'] = $catchCategoryId;
			$renderDataOnGlobalSettings['_umb_catch_category']['metakey'] = null;

			return $renderDataOnGlobalSettings;
		}


		/*
		*
		*Inserting and Saving Profiles
		*
		*
		*/

		public function insertCatchProfile( $profileDetails ) {

			global $wpdb;
			$profileTableName = $wpdb->prefix . 'ced_catch_profiles';

			$wpdb->insert( $profileTableName, $profileDetails );

			$profileId = $wpdb->insert_id;
			return $profileId;
		}


		public function prepareProductHtmlForUpload( $proIDs = array(), $shopID = '', $profile_id = '' ) {
			if ( ! is_array( $proIDs ) ) {
				$proIDs = array( $proIDs );
			}
			$wpuploadDir = wp_upload_dir();
			$baseDir     = $wpuploadDir['basedir'];

			$ced_catch_product_log = 'Date :' . date_i18n( 'Y-m-d H:i:s' ) . "\t";
			$user                  = wp_get_current_user();
			if ( is_object( $user ) ) {
				$ced_catch_product_log .= 'User Name : ' . $user->display_name . "\t\t";
			}
			$response               = $this->catchProductsInstance->ced_catch_prepareDataForUploading( $proIDs, $shopID, '', $profile_id );
			$uploadDir              = $baseDir . '/cedcommerce_catchuploads';
			$filename               = str_replace( $uploadDir, '', $response );
			$ced_catch_product_log .= 'File :' . $filename . "\t\t";
			$response               = $this->catchProductsInstance->doupload( $response, $shopID, 'Product' );
			if ( isset( $response['import_id'] ) ) {
				$import_id              = $response['import_id'];
				$ced_catch_product_log .= 'Import Id :' . $response['import_id'] . "\r\n\n";
				update_post_meta( $import_id, 'ced_catch_update_product_status_' . $shopID, 'yes' );
				$this->ced_catch_custom_log( 'cedcommerce/catch/logs/product', '', $ced_catch_product_log );
			}
			return $response;
		}

		public function prepareProductHtmlForOffer( $proIDs = array(), $shopID = '', $UpdateOrDelete = '', $isCron = false, $with_product = '', $profile_id = '' ) {
			if ( ! is_array( $proIDs ) ) {
				$proIDs = array( $proIDs );
			}
			if ( empty( $shopID ) ) {
				$shopID = get_option( 'ced_catch_active_shop', '' );
			}
			$wpuploadDir = wp_upload_dir();
			$baseDir     = $wpuploadDir['basedir'];

			$ced_catch_inventory_log = 'Date :' . date_i18n( 'Y-m-d H:i:s' ) . "\t";
			if ( false == $isCron ) {
				$user = wp_get_current_user();
				if ( is_object( $user ) ) {
					$ced_catch_inventory_log .= 'User Name : ' . $user->display_name . "\t\t";
				}
			}
			$response                 = $this->catchProductsInstance->ced_catch_prepareDataForOffers( $proIDs, $shopID, $UpdateOrDelete, $isCron, '', $with_product, $profile_id );
			$uploadDir                = $baseDir . '/cedcommerce_catchuploads';
			$filename                 = str_replace( $uploadDir, '', $response );
			$ced_catch_inventory_log .= 'File :' . $filename . "\t\t";
			$response                 = $this->catchProductsInstance->doupload( $response, $shopID, 'Offer', $isCron );
			if ( isset( $response['import_id'] ) ) {
				$ced_catch_inventory_log .= 'Import Id :' . $response['import_id'] . "\r\n\n";
				$this->ced_catch_custom_log( 'cedcommerce/catch/logs/inventory', '', $ced_catch_inventory_log );
			}

			return $response;
		}
	}
}
