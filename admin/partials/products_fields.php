<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Check Class exists or not
 *
 * @since      1.0.0
 *
 * @package    Woocommerce catch Integration
 * @subpackage Woocommerce catch Integration/admin/helper
 */

if ( ! class_exists( 'CedCatchProductsFields' ) ) {

	/**
	 * Single product related functionality.
	 *
	 * Manage all single product related functionality required for listing product on marketplaces.
	 *
	 * @since      1.0.0
	 * @package    Woocommerce catch Integration
	 * @subpackage Woocommerce catch Integration/admin/helper
	 * author     CedCommerce <cedcommerce.com>
	 */
	class CedCatchProductsFields {

		/**
		 * The Instace of CED_catch_product_fields.
		 *
		 * @since    1.0.0
		 * access   private
		 * @var      $_instance   The Instance of CED_catch_product_fields class.
		 */
		private static $_instance;

		/**
		 * CED_catch_product_fields Instance.
		 *
		 * Ensures only one instance of CED_catch_product_fields is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @return CED_catch_product_fields instance.
		 */
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Ced_catch_get_custom_products_fields
		 *
		 * @param  mixed $shop_id
		 * @return void
		 */
		public static function ced_catch_get_custom_products_fields( $shop_id ) {
			require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchSendHttpRequest.php';
			$action = 'products/attributes';

			$parameters     = '';
			$sendRequestObj = new Class_Ced_Catch_Send_Http_Request();

			$catch_product_attribute = @file_get_contents( CED_CATCH_DIRPATH . 'admin/catch/lib/json/catch-product-attribute.json' );
			if ( false == $catch_product_attribute ) {
				$response = $sendRequestObj->sendHttpRequestGet( $action, $parameters, $shop_id, '' );
				file_put_contents( CED_CATCH_DIRPATH . 'admin/catch/lib/json/catch-product-attribute.json', $response );

			} else {
				$response = $catch_product_attribute;
			}
			return $response;
		}

		public function ced_catch_get_attribute_list_option( $parameters, $shop_id ) {
			require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchSendHttpRequest.php';
			$sendRequestObj = new Class_Ced_Catch_Send_Http_Request();
			$action         = 'values_lists';

			$catch_product_listattribute = @file_get_contents( CED_CATCH_DIRPATH . 'admin/catch/lib/json/catch-product-listattribute.json' );
			if ( false == $catch_product_listattribute ) {
				$response = $sendRequestObj->sendHttpRequestGet( $action, $parameters, $shop_id, '' );
				file_put_contents( CED_CATCH_DIRPATH . 'admin/catch/lib/json/catch-product-listattribute.json', $response );
			} else {
				$response = $catch_product_listattribute;
			}

			$response = json_decode( $response, true );
			return $response;
		}

		public function ced_catch_get_attribute_list_option_values( $get_list_option, $attribute_value_list ) {
			foreach ( $get_list_option as $key => $list_values ) {
				if ( $list_values['code'] == $attribute_value_list ) {
					$valueForDropdown = $list_values['values'];
				}
			}
			return $valueForDropdown;
		}

		public function ced_catch_getInventoryShedulerOption() {
			$inventory_sheduler_option = array(
				'options' => array(
					'ced_catch_10min'      => __( 'Once every 10 minutes' ),
					'ced_catch_15min'      => __( 'Once every 15 minutes' ),
					'ced_catch_30min'      => __( 'Once every 30 minutes' ),
					'ced_catch_60min'      => __( 'Once every 60 minutes' ),
					'ced_catch_hourly'     => __( 'Hourly' ),
					'ced_catch_twicedaily' => __( 'Twicedaily' ),
					'ced_catch_daily'      => __( 'Daily' ),
					'ced_catch_weekly'     => __( 'Weekly' ),
				),
			);
			return $inventory_sheduler_option;
		}

		public static function ced_catch_get_custom_offers_fields() {
			$required_offerfields = array(
				array(
					'type'   => '_hidden',
					'id'     => '_umb_catch_category',
					'fields' => array(
						'id'          => '_umb_catch_category',
						'label'       => __( 'Category Name', 'woocommerce-catch-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Specify the category name.', 'woocommerce-catch-integration' ),
						'type'        => 'hidden',
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_select',
					'id'     => '_ced_catch_markup_type',
					'fields' => array(
						'id'          => 'markup_type',
						'label'       => __( 'Markup Type', 'woocommerce-catch-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Increase/Decrease price by a certain amount in the actual price of the product when uploading on catch.', 'woocommerce-catch-integration' ),
						'type'        => 'select',
						'options'     => array(
							array(
								'code'  => 'Fixed_Increased',
								'label' => 'Fixed_Increased',
							),
							array(
								'code'  => 'Fixed_Decreased',
								'label' => 'Fixed_Decreased',
							),
							array(
								'code'  => 'Percentage_Increased',
								'label' => 'Percentage_Increased',
							),
							array(
								'code'  => 'Percentage_Decreased',
								'label' => 'Percentage_Decreased',
							),
						),
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',
					'id'     => '_ced_catch_markup_price',
					'fields' => array(
						'id'          => 'markup_price',
						'label'       => __( 'Markup Price', 'woocommerce-catch-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Enter the markup value. Eg : 10', 'woocommerce-catch-integration' ),
						'type'        => 'text',
						'class'       => 'wc_input_price',
					),
				),
			);

			return $required_offerfields;
		}

		public static function ced_catch_get_custom_miscellaneous_fields() {
			$required_miscellaneousfields = array(
				array(
					'type'   => '_text_input',
					'id'     => '_ced_catch_title_prefix',
					'fields' => array(
						'id'          => 'title_prefix',
						'label'       => __( 'Title Prefix', 'woocommerce-catch-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Text to be added before the title.', 'woocommerce-catch-integration' ),
						'type'        => 'text',
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',
					'id'     => '_ced_catch_default_stock',
					'fields' => array(
						'id'          => 'default_stock',
						'label'       => __( 'Default Stock', 'woocommerce-catch-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Default Stock is used on uploading offer.if stock is not managed on woocommerce and stock status is instock.', 'woocommerce-catch-integration' ),
						'type'        => 'text',
						'class'       => 'wc_input_price',
					),
				),
				array(
					'type'   => '_text_input',
					'id'     => '_ced_catch_title_suffix',
					'fields' => array(
						'id'          => 'title_suffix',
						'label'       => __( 'Title Suffix', 'woocommerce-catch-integration' ),
						'desc_tip'    => true,
						'description' => __( 'Text to be added after the title.', 'woocommerce-catch-integration' ),
						'type'        => 'text',
						'class'       => 'wc_input_price',
					),
				),
			);

			return $required_miscellaneousfields;
		}


		/**
		 *
		 * Function for render dropdown html
		 */
		public function renderDropdownHTML( $attribute_id, $attribute_name, $values, $categoryID, $productID, $marketPlace, $attribute_description = null, $indexToUse, $additionalInfo = array( 'case' => 'product' ), $is_required = '' ) {
			$fieldName = 'ced_catch_custom_' . $attribute_id;

			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$previousValue = $additionalInfo['value'];
			}

			?><input type="hidden" name="<?php echo esc_attr( $marketPlace ) . '[]'; ?>" value="<?php echo esc_attr( $fieldName ); ?>" />

			<td>
				<label for=""><?php print_r( $attribute_name ); ?>
				<?php
				if ( 'required' == $is_required ) {
					?>
					<span class="ced_catch_wal_required">
						<?php
						esc_attr_e( ' *' )
						?>
					</span>
					<?php
				}
				?>
			</label>
			<?php
			print_r( '</br><span class="ced_catch_cedcommerce-tip">[' . $attribute_description . ']</span>' );
			?>
		</td>
		<td>
			<select id="" name="<?php echo esc_attr( $fieldName ) . '[' . esc_attr( $indexToUse ) . ']'; ?>" class="select short" style="">
				<?php
				echo '<option value="">' . esc_attr__( '-- Select --' ) . '</option>';

				foreach ( $values as $key => $value ) {
					if ( $previousValue == $value['code'] ) {
						echo '<option value="' . esc_attr( $value['code'] ) . '" selected>' . esc_attr( $value['label'] ) . '</option>';
					} else {
						echo '<option value="' . esc_attr( $value['code'] ) . '">' . esc_attr( $value['label'] ) . '</option>';
					}
				}
				?>
			</select>
		</td>
		
			<?php
		}

		public function renderDropdownHTMLForCategorySpecifics( $attribute_id, $attribute_name, $values, $categoryID, $productID, $marketPlace, $attribute_description = null, $indexToUse, $additionalInfo = array( 'case' => 'product' ), $is_required = '' ) {
				$fieldName = 'ced_catch_custom_' . $attribute_id;

			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$previousValue = $additionalInfo['value'];
			}

			?>
				<input type="hidden" name="<?php echo esc_attr( $marketPlace ) . '[]'; ?>" value="<?php echo esc_attr( $fieldName ); ?>" />
				
				<td>
					<label for=""><?php print_r( $attribute_name ); ?>
					<?php
					if ( 'required' == $is_required ) {
						?>
						<span class="ced_catch_wal_required">
							<?php
							esc_attr_e( ' *' )
							?>
						</span>
						<?php
					}
					?>
				</label>
				<?php
				print_r( '</br><span class="ced_catch_cedcommerce-tip">[' . $attribute_description . ']</span>' );
				?>
			</td>
			<td>
				<select id="" name="<?php echo esc_attr( $fieldName ) . '[' . esc_attr( $indexToUse ) . ']'; ?>" class="select short" style="">
					<?php
					echo '<option value="">' . esc_attr__( '-- Select --' ) . '</option>';
					foreach ( $values as $key => $value ) {
						if ( $previousValue == $value['code'] ) {
							echo '<option value="' . esc_attr( $value['code'] ) . '" selected>' . esc_attr( $value['label'] ) . '</option>';
						} else {
							echo '<option value="' . esc_attr( $value['code'] ) . '">' . esc_attr( $value['label'] ) . '</option>';
						}
					}
					?>
				</select>
			</td>

				<?php
		}

		/**
		 *
		 * Function to render input fields
		 */
		public function renderInputTextHTML( $attribute_id, $attribute_name, $categoryID, $productID, $marketPlace, $attribute_description = null, $indexToUse, $additionalInfo = array( 'case' => 'product' ), $conditionally_required = false, $conditionally_required_text = '', $valueType = '', $input_type = '' ) {
			if ( 'Discount Start Date' == $attribute_name || 'Discount End Date' == $attribute_name || 'Best Before Date' == $attribute_name || 'Expiry Date' == $attribute_name || 'Available Start Date' == $attribute_name || 'Available End Date' == $attribute_name ) {
				$input_type = 'date';
			} else {
				$input_type = 'text';
			}

				global $post,$product,$loop;
				$fieldName = 'ced_catch_custom_' . $attribute_id;

			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$previousValue = $additionalInfo['value'];
			}
			?>

				<input type="hidden" name="<?php echo esc_attr( $marketPlace ) . '[]'; ?>" value="<?php echo esc_attr( $fieldName ); ?>" />
				<td>
					<label for=""><?php print_r( $attribute_name ); ?>
					<?php
					if ( 'required' == $conditionally_required ) {
						?>
						<span class="ced_catch_wal_required"><?php esc_attr_e( ' *' ); ?></span>
						<?php
					}
					?>
				</label>
				<?php
				print_r( '</br><span class="ced_catch_cedcommerce-tip">[' . $attribute_description . ']</span>' );
				?>
			</td>
			<td>
				<?php
				if ( 'Date' == $valueType ) {
					$placeholder = $valueType;
				} else {
					$placeholder = '';
				}

				?>
				
				<input class="short" style="" name="<?php echo esc_attr( $fieldName ) . '[' . esc_attr( $indexToUse ) . ']'; ?>" id="" value="<?php echo esc_attr( $previousValue ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" type="<?php echo esc_attr( $input_type ); ?>" /> 
			</td>
			
			<?php
		}

		/**
		 *
		 * Function to render hidden input fields
		 */
		public function renderInputTextHTMLhidden( $attribute_id, $attribute_name, $categoryID, $productID, $marketPlace, $attribute_description = null, $indexToUse, $additionalInfo = array( 'case' => 'product' ), $conditionally_required = false, $conditionally_required_text = '' ) {
			global $post,$product,$loop;
			$fieldName = $categoryID . '_' . $attribute_id;
			if ( 'product' == $additionalInfo['case'] ) {
				$previousValue = get_post_meta( $productID, $fieldName, true );
			} else {
				$previousValue = $additionalInfo['value'];
			}

			?>

			<input type="hidden" name="<?php echo esc_attr( $marketPlace ) . '[]'; ?>" value="<?php echo esc_attr( $fieldName ); ?>" />
			<td>
				</label>
			</td>
			<td>
				<label></label>
				<input class="short" style="" name="<?php echo esc_attr( $fieldName ) . '[' . esc_attr( $indexToUse ) . ']'; ?>" id="" value="<?php echo esc_attr( $previousValue ); ?>" placeholder="" type="hidden" /> 
			</td>
			<?php
		}

		public function ced_catch_getCategorySpecificAtrributes( $attributes = '', $profile_category_id = '' ) {
			$attributes = json_decode( $attributes, true );
			if ( ! empty( $attributes ) ) {
				$categorySpecific = array();
				foreach ( $attributes['attributes'] as $key => $value ) {
					if ( '' != $value['hierarchy_code'] ) {
						if ( strpos( $profile_category_id, $value['hierarchy_code'] ) !== false ) {
							$categorySpecific[ $value['code'] ] = $value;
						} else {
							$profile_category_id_temp = explode( '>', $profile_category_id );
							unset( $profile_category_id_temp[ count( $profile_category_id_temp ) - 1 ] );
							$profile_category_id_temp = implode( '>', $profile_category_id_temp );

							if ( trim( $profile_category_id_temp == $value['hierarchy_code'] ) ) {
								$categorySpecific[ $value['code'] ] = $value;
							}
						}
					}
				}
			}

			return $categorySpecific;
		}

	}
}
