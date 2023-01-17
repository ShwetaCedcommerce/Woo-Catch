<?php

class Class_Ced_Catch_Orders {

	public static $_instance;
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchSendHttpRequest.php';
		$this->sendRequestObj = new Class_Ced_Catch_Send_Http_Request();
		require_once CED_CATCH_DIRPATH . 'admin/catch/class-catch.php';
		$this->ced_CatchManager = Class_Ced_Catch_Manager::get_instance();
	}


	public function create_local_order( $orders, $shopId, $isSync = false ) {
		if ( is_array( $orders ) && ! empty( $orders ) ) {
			foreach ( $orders as $key => $order ) {
				$ship_to_first_name    = isset( $order['customer']['firstname'] ) ? $order['customer']['firstname'] : '';
				$ship_to_last_name     = isset( $order['customer']['lastname'] ) ? $order['customer']['lastname'] : '';
				$ship_to_address1      = isset( $order['customer']['shipping_address']['street_1'] ) ? $order['customer']['shipping_address']['street_1'] : '';
				$ship_to_address2      = isset( $order['customer']['shipping_address']['street_2'] ) ? $order['customer']['shipping_address']['street_2'] : '';
				$ship_to_city_name     = isset( $order['customer']['shipping_address']['city'] ) ? $order['customer']['shipping_address']['city'] : '';
				$customer_phone_number = isset( $order['customer']['shipping_address']['phone'] ) ? $order['customer']['shipping_address']['phone'] : '';
				$ship_to_state_code    = isset( $order['customer']['shipping_address']['state'] ) ? $order['customer']['shipping_address']['state'] : '';
				$ship_to_zip_code      = isset( $order['customer']['shipping_address']['zip_code'] ) ? $order['customer']['shipping_address']['zip_code'] : '';
				$country               = isset( $order['customer']['shipping_address']['country'] ) ? $order['customer']['shipping_address']['country'] : '';
				$country               = isset( $order['customer']['shipping_address']['country_iso_code'] ) ? $order['customer']['shipping_address']['country_iso_code'] : '';
				if ( 'AUS' == $country ) {
					$country = 'AU';
				}

				$shipping_address = array(
					'first_name' => $ship_to_first_name,
					'last_name'  => $ship_to_last_name,
					'address_1'  => $ship_to_address1,
					'address_2'  => $ship_to_address2,
					'city'       => $ship_to_city_name,
					'state'      => $ship_to_state_code,
					'postcode'   => $ship_to_zip_code,
					'country'    => $country,
				);

				$bill_to_first_name = $ship_to_first_name;
				$bill_to_last_name  = $ship_to_last_name;
				$bill_email_address = isset( $order['buyer_email'] ) ? $order['buyer_email'] : '';
				$bill_phone_number  = $customer_phone_number;

				$billing_address = array(
					'first_name' => $bill_to_first_name,
					'last_name'  => $bill_to_last_name,
					'address_1'  => $ship_to_address1,
					'address_2'  => $ship_to_address2,
					'city'       => $ship_to_city_name,
					'state'      => $ship_to_state_code,
					'email'      => $bill_email_address,
					'phone'      => $bill_phone_number,
					'country'    => $country,
				);
				$address         = array(
					'shipping' => $shipping_address,
					'billing'  => $billing_address,
				);

				$order_number             = $order['order_id'];
				$order_status             = $order['order_state'];
				$Shipping                 = $order['shipping_price'];
				$shippingService          = $order['shipping_zone_label'];
				$transactions_per_reciept = $order['order_lines'];
				$item_array               = array();
				foreach ( $transactions_per_reciept as $transaction ) {
					$id = false;

					$store_product_sku = $transaction['offer_sku'];

					$ordered_qty = isset( $transaction['quantity'] ) ? $transaction['quantity'] : 1;
					$base_price  = isset( $transaction['price_unit'] ) ? $transaction['price_unit'] : '';
					$sku         = isset( $transaction['offer_sku'] ) ? $transaction['offer_sku'] : '';
					$cancel_qty  = 0;
					if ( $store_product_sku ) {

						$local_product = get_posts(
							array(
								'numberposts'  => -1,
								'post_type'    => array( 'product', 'product_variation' ),
								'meta_key'     => '_sku',
								'meta_value'   => isset( $store_product_sku ) ? $store_product_sku : '',
								'meta_compare' => '=',
							)
						);

						$local_item_id = wp_list_pluck( $local_product, 'ID' );
						if ( ! empty( $local_item_id ) && isset( $local_item_id[0] ) ) {
							$id = $local_item_id[0];
						}
					}

					$item         = array(
						'OrderedQty' => $ordered_qty,
						'CancelQty'  => $cancel_qty,
						'UnitPrice'  => $base_price,
						'ID'         => $id,
						'Sku'        => $sku,
					);
					$item_array[] = $item;
				}

				$final_tax        = 1;
				$order_items_info = array(
					'OrderNumber'    => $order_number,
					'OrderStatus'    => $order_status,
					'ItemsArray'     => $item_array,
					'tax'            => $final_tax,
					'ShippingAmount' => $Shipping,
					'ShipService'    => $shippingService,
				);
				$order_items      = $transactions_per_reciept;

				$merchant_order_id = $order_number;
				$purchase_order_id = $order_number;
				$fulfillment_node  = '';
				$order_detail      = isset( $order ) ? $order : array();
				$catch_order_meta  = array(
					'merchant_order_id' => $merchant_order_id,
					'purchaseOrderId'   => $purchase_order_id,
					'fulfillment_node'  => $fulfillment_node,
					'order_detail'      => $order_detail,
					'order_items'       => $order_items,
				);

				$ced_catch_default_order_statuses = array(
					'STAGING'               => 'wc-pending',
					'WAITING_ACCEPTANCE'    => 'wc-on-hold',
					'WAITING_DEBIT'         => 'wc-processing',
					'WAITING_DEBIT_PAYMENT' => 'wc-processing',
					'SHIPPING'              => 'wc-processing',
					'SHIPPED'               => 'wc-completed',
					'TO_COLLECT'            => 'wc-processing',
					'CLOSED'                => 'wc-cancelled',
					'REFUSED'               => 'wc-cancelled',
					'RECEIVED'              => 'wc-completed',
					'CANCELED'              => 'wc-cancelled',
					// 'lost'      => 'wc-failed',
				);

				$ced_catch_plugin_order_statuses = array(
					'STAGING'               => 'Fetched',
					'WAITING_ACCEPTANCE'    => 'Fetched',
					'WAITING_DEBIT'         => 'Fetched',
					'WAITING_DEBIT_PAYMENT' => 'Fetched',
					'SHIPPING'              => 'Fetched',
					'SHIPPED'               => 'Completed',
					'TO_COLLECT'            => 'Fetched',
					'CLOSED'                => 'Cancelled',
					'REFUSED'               => 'Cancelled',
					'RECEIVED'              => 'Completed',
					'CANCELED'              => 'Cancelled',
				);

				$creation_date                   = $order['created_date'];
				$ced_catch_mapped_order_statuses = get_option( 'ced_catch_mapped_order_statuses', array() );

				$order_id = $this->create_order( $address, $order_items_info, 'catch', $catch_order_meta, $creation_date, $shopId );
				if ( $order_id ) {

					if ( ! $isSync && 'on' == get_option( 'ced_catch_auto_accept_order' . $shopId, '' ) ) {
						$ced_catch_order_status = get_post_meta( $order_id, '_catch_umb_order_status', true );
						if ( 'WAITING_ACCEPTANCE' == $order_status ) {

							$action      = 'orders/' . $order_number . '/accept';
							$order_lines = array();
							foreach ( $order_items as $index => $details ) {
								$orderAccept['accepted']      = true;
								$orderAccept['id']            = $details['order_line_id'];
								$order_lines['order_lines'][] = $orderAccept;
							}
							$parameters = $order_lines;
							$response   = $this->sendRequestObj->sendHttpRequestPut( $action, $parameters, $shopId );
							update_post_meta( $order_id, '_catch_umb_order_status', 'Accepted' );
						}
					}

					$order_obj = wc_get_order( $order_id );
					if ( is_object( $order_obj ) ) {
						$woo_order_status = isset( $ced_catch_mapped_order_statuses[ $order_status ] ) ? $ced_catch_mapped_order_statuses[ $order_status ] : $ced_catch_default_order_statuses[ $order_status ];

						update_post_meta( $order_id, 'order_detail', $order_detail );
						$order_obj->update_status( $woo_order_status );
					}
				}
			}
		}
	}


	public function create_order( $address = array(), $order_items_info = array(), $framework_name = 'catch', $order_meta = array(), $creation_date = '', $shop_id = '' ) {

		$order_id      = '';
		$order_created = false;
		$upload        = wp_upload_dir();
		$upload_dir    = $upload['basedir'];
		$upload_dir    = $upload_dir . '/cedcommerce/catch/logs/order';
		if ( ! is_dir( $upload_dir ) ) {
			mkdir( $upload_dir, 0755, true );
		}
		$fp                  = fopen( $upload_dir . '/' . gmdate( 'j.n.Y' ) . '.log', 'a' );
		$ced_catch_order_log = '';

		if ( count( $order_items_info ) ) {
			$order_number         = isset( $order_items_info['OrderNumber'] ) ? $order_items_info['OrderNumber'] : 0;
			$order_id             = $this->is_catch_order_exists( $order_number );
			$ced_catch_order_log .= 'Date :' . date_i18n( 'Y-m-d H:i:s' ) . "\tMessage : processing of Order : Catch Order Id - " . $order_number . "\t";

			if ( $order_id ) {

				$updated_status = isset( $order_items_info['OrderStatus'] ) ? $order_items_info['OrderStatus'] : '';

				if ( 'SHIPPING' == $updated_status ) {
					$this->update_order_details( $order_id, $address );
				}
				return $order_id;
			}

			if ( count( $order_items_info ) ) {
				$items_array = isset( $order_items_info['ItemsArray'] ) ? $order_items_info['ItemsArray'] : array();
				if ( is_array( $items_array ) ) {
					foreach ( $items_array as $item_info ) {
						$pro_id               = isset( $item_info['ID'] ) ? intval( $item_info['ID'] ) : 0;
						$sku                  = isset( $item_info['Sku'] ) ? $item_info['Sku'] : '';
						$ced_catch_order_log .= "\tOffer Sku - " . $sku . "\r\n";
						$mfr_part_number      = isset( $item_info['MfrPartNumber'] ) ? $item_info['MfrPartNumber'] : '';
						$upc                  = isset( $item_info['UPCCode'] ) ? $item_info['UPCCode'] : '';
						$asin                 = isset( $item_info['ASIN'] ) ? $item_info['ASIN'] : '';
						if ( '' != $sku ) {
							$pro_id = wc_get_product_id_by_sku( $sku );
						}
						if ( ! $pro_id ) {
							$pro_id = $sku;
						}

						$qty                    = isset( $item_info['OrderedQty'] ) ? intval( $item_info['OrderedQty'] ) : 0;
						$unit_price             = isset( $item_info['UnitPrice'] ) ? floatval( $item_info['UnitPrice'] ) : 0;
						$extend_unit_price      = isset( $item_info['ExtendUnitPrice'] ) ? floatval( $item_info['ExtendUnitPrice'] ) : 0;
						$extend_shipping_charge = isset( $item_info['ExtendShippingCharge'] ) ? floatval( $item_info['ExtendShippingCharge'] ) : 0;
						$_product               = wc_get_product( $pro_id );

						if ( is_wp_error( $_product ) ) {
							continue;
						} elseif ( is_null( $_product ) ) {
							$ced_catch_order_log .= "\t\t\t\t\t\t\t\tError -Sku Does not Exist\r\n\n";
							if ( false !== $fp && get_resource_type( $fp ) == 'stream' ) {
								fwrite( $fp, $ced_catch_order_log );
								fclose( $fp );
							}
							continue;
						} elseif ( ! $_product ) {
							$ced_catch_order_log .= "\t\t\t\t\t\t\t\tError - Sku Does not Exist\r\n\n";
							if ( false !== $fp && get_resource_type( $fp ) == 'stream' ) {
								fwrite( $fp, $ced_catch_order_log );
								fclose( $fp );
							}
							continue;
						} else {
							if ( ! $order_created ) {
								$order_data = array(
									'status'        => apply_filters( 'woocommerce_default_order_status', 'pending' ),
									'customer_note' => __( 'Order from ', 'woocommerce-catch-integration' ) . $framework_name . '[' . $shop_id . ']',
									'created_via'   => $framework_name,
								);

								$order = wc_create_order( $order_data );

								if ( is_wp_error( $order ) ) {
									continue;
								} elseif ( false === $order ) {
									continue;
								} else {
									if ( WC()->version < '3.0.0' ) {
										$order_id = $order->id;
									} else {
										$order_id = $order->get_id();
									}
									$ced_catch_order_log .= "\t\t\t\t\t\t\t\tResponse - Order created with order id " . $order_id . "\r\n\n";
									if ( false !== $fp && get_resource_type( $fp ) == 'stream' ) {
										fwrite( $fp, $ced_catch_order_log );
										fclose( $fp );
									}
									$order_created = true;
								}
							}
							$_product->set_price( $unit_price );
							$order->add_product( $_product, $qty );
							$order->calculate_totals();
						}
					}
				}
				if ( ! $order_created ) {
					return false;
				}

				$order_item_amount = isset( $order_items_info['OrderItemAmount'] ) ? $order_items_info['OrderItemAmount'] : 0;
				$shipping_amount   = isset( $order_items_info['ShippingAmount'] ) ? $order_items_info['ShippingAmount'] : 0;
				$discount_amount   = isset( $order_items_info['DiscountAmount'] ) ? $order_items_info['DiscountAmount'] : 0;
				$refund_amount     = isset( $order_items_info['RefundAmount'] ) ? $order_items_info['RefundAmount'] : 0;
				$ship_service      = isset( $order_items_info['ShipService'] ) ? $order_items_info['ShipService'] : '';

				if ( ! empty( $ship_service ) ) {
					$ship_params = array(
						'ShippingCost' => $shipping_amount,
						'ShipService'  => $ship_service,
					);
					$this->add_shipping_charge( $order, $ship_params );
				}

				$shipping_address = isset( $address['shipping'] ) ? $address['shipping'] : '';
				if ( is_array( $shipping_address ) && ! empty( $shipping_address ) ) {
					if ( WC()->version < '3.0.0' ) {
						$order->set_address( $shipping_address, 'shipping' );
					} else {
						$type = 'shipping';
						foreach ( $shipping_address as $key => $value ) {
							if ( ! empty( $value ) ) {
								update_post_meta( $order->get_id(), "_{$type}_" . $key, $value );
								if ( is_callable( array( $order, "set_{$type}_{$key}" ) ) ) {
									$order->{"set_{$type}_{$key}"}( $value );
								}
							}
						}
					}
				}

				$new_fee            = new stdClass();
				$new_fee->name      = 'Tax';
				$new_fee->amount    = (float) ( $order_items_info['tax'] );
				$new_fee->tax_class = '';
				$new_fee->taxable   = 0;
				$new_fee->tax       = '';
				$new_fee->tax_data  = array();
				if ( WC()->version < '3.0.0' ) {
					$item_id = $order->add_fee( $new_fee );
				} else {
					$item_id = $order->add_item( $new_fee );
				}

				$billing_address = isset( $address['billing'] ) ? $address['billing'] : '';
				if ( is_array( $billing_address ) && ! empty( $billing_address ) ) {
					if ( WC()->version < '3.0.0' ) {
						$order->set_address( $shipping_address, 'billing' );
					} else {
						$type = 'billing';
						foreach ( $billing_address as $key => $value ) {
							if ( ! empty( $value ) ) {
								update_post_meta( $order->get_id(), "_{$type}_" . $key, $value );
								if ( is_callable( array( $order, "set_{$type}_{$key}" ) ) ) {
									$order->{"set_{$type}_{$key}"}( $value );
								}
							}
						}
					}
				}
				if ( WC()->version < '3.0.0' ) {
					$order->set_total( $discount_amount, 'cart_discount' );
				} else {
					$order->set_total( $discount_amount );
				}

				$order->calculate_totals();

				update_post_meta( $order_id, '_ced_catch_order_id', $order_number );
				update_post_meta( $order_id, '_is_ced_catch_order', 1 );
				$updated_status = isset( $order_items_info['OrderStatus'] ) ? $order_items_info['OrderStatus'] : '';
				if ( 'WAITING_ACCEPTANCE' == $updated_status ) {
					update_post_meta( $order_id, '_catch_umb_order_status', 'Fetched' );
				} else {
					update_post_meta( $order_id, '_catch_umb_order_status', $updated_status );
				}

				update_post_meta( $order_id, '_umb_catch_marketplace', $framework_name );
				update_post_meta( $order_id, 'ced_catch_order_shop_id', $shop_id );
				update_option( 'ced_catch_last_order_created_time', $creation_date );

				if ( count( $order_meta ) ) {
					foreach ( $order_meta as $order_key => $order_value ) {
						update_post_meta( $order_id, $order_key, $order_value );
					}
				}
			}

			return $order_id;
		}
		return false;
	}



	public function umb_get_product_by( $params ) {
		global $wpdb;

		$where = '';
		if ( count( $params ) ) {
			$flag = false;
			foreach ( $params as $meta_key => $meta_value ) {
				if ( ! empty( $meta_value ) && ! empty( $meta_key ) ) {
					if ( ! $flag ) {
						$where .= 'meta_key="' . sanitize_key( $meta_key ) . '" AND meta_value="' . $meta_value . '"';
						$flag   = true;
					} else {
						$where .= ' OR meta_key="' . sanitize_key( $meta_key ) . '" AND meta_value="' . $meta_value . '"';
					}
				}
			}
			if ( $flag ) {
				$product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE %s LIMIT 1", $where ) );
				if ( $product_id ) {
					return $product_id;
				}
			}
		}
		return false;
	}

	public static function add_shipping_charge( $order, $ShipParams = array() ) {

		$ShipName = isset( $ShipParams['ShipService'] ) ? esc_attr( $ShipParams['ShipService'] ) : 'UMB Default Shipping';
		$ShipCost = isset( $ShipParams['ShippingCost'] ) ? $ShipParams['ShippingCost'] : 0;
		$ShipTax  = isset( $ShipParams['ShippingTax'] ) ? $ShipParams['ShippingTax'] : 0;

		$item = new WC_Order_Item_Shipping();

		$item->set_method_title( $ShipName );
		$item->set_method_id( $ShipName );
		$item->set_total( $ShipCost );
		$order->add_item( $item );

		$order->calculate_totals();
		$order->save();
	}

	public function is_catch_order_exists( $order_number = 0 ) {
		global $wpdb;
		if ( $order_number ) {
			$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_ced_catch_order_id' AND meta_value=%s LIMIT 1", $order_number ) );
			if ( $order_id ) {
				return $order_id;
			}
		}
		return false;
	}

	public function get_catch_order_ids( $shop_id = '' ) {

		global $wpdb;
		$orderIds = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%d", 'ced_catch_order_shop_id', $shop_id ), 'ARRAY_A' );
		if ( ! empty( $orderIds ) ) {
			$order_ids = array();
			foreach ( $orderIds as $key => $value ) {
				$orderId          = $value['post_id'];
				$catchOrderId     = get_post_meta( $orderId, '_ced_catch_order_id', true );
				$catchOrderStatus = get_post_meta( $orderId, '_catch_umb_order_status', true );
				if ( isset( $catchOrderId ) && 'Accepted' == $catchOrderStatus ) {
					$order_ids[] = $catchOrderId;
				}
			}
			return $order_ids;
		}
	}
	public function update_order_details( $order_id = '', $address = array() ) {

		$order            = wc_get_order( $order_id );
		$shipping_address = isset( $address['shipping'] ) ? $address['shipping'] : '';
		if ( is_array( $shipping_address ) && ! empty( $shipping_address ) ) {
			if ( WC()->version < '3.0.0' ) {
				$order->set_address( $shipping_address, 'shipping' );
			} else {
				$type = 'shipping';
				foreach ( $shipping_address as $key => $value ) {
					if ( ! empty( $value ) ) {
						update_post_meta( $order->get_id(), "_{$type}_" . $key, $value );
						if ( is_callable( array( $order, "set_{$type}_{$key}" ) ) ) {
							$order->{"set_{$type}_{$key}"}( $value );
						}
					}
				}
			}
		}

		$billing_address = isset( $address['billing'] ) ? $address['billing'] : '';
		if ( is_array( $billing_address ) && ! empty( $billing_address ) ) {
			if ( WC()->version < '3.0.0' ) {
				$order->set_address( $shipping_address, 'billing' );
			} else {
				$type = 'billing';
				foreach ( $billing_address as $key => $value ) {
					if ( ! empty( $value ) ) {
						update_post_meta( $order->get_id(), "_{$type}_" . $key, $value );
						if ( is_callable( array( $order, "set_{$type}_{$key}" ) ) ) {
							$order->{"set_{$type}_{$key}"}( $value );
						}
					}
				}
			}
		}

		update_post_meta( $order_id, '_catch_umb_order_status', 'Shipping' );
	}
}
