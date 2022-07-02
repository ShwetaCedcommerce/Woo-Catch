<?php

class Class_Ced_Catch_Products {

	public static $_instance;


		/**
		 * Ced_Catch_Config Instance.
		 *
		 * Ensures only one instance of Ced_Catch_Config is loaded or can be loaded.
		 *
		 * author CedCommerce <plugins@cedcommerce.com>
		 *
		 * @since 1.0.0
		 * @static
		 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		$this->loadDependency();
	}

	public function loadDependency() {
		$file = CED_CATCH_DIRPATH . 'admin/catch/lib/catchSendHttpRequest.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}

		$this->catchSendHttpRequestInstance = new Class_Ced_Catch_Send_Http_Request();
	}

	public function ced_catch_prepareDataForUploading( $proIDs = array(), $shopId, $Offset = 'False' ) {
		foreach ( $proIDs as $key => $value ) {
			$prod_data = wc_get_product( $value );
			if ( ! is_object( $prod_data ) ) {
				continue;
			}

			$type = $prod_data->get_type();

			if ( 'variable' == $type ) {
				$prod_data  = wc_get_product( $value );
				$variations = $prod_data->get_available_variations();
				foreach ( $variations as $variation ) {
					$attributes   = $variation['attributes'];
					$variation_id = $variation['variation_id'];

					$preparedData[ $variation_id ] = $this->getFormattedData( $variation_id, $shopId, $attributes );
					if ( ! is_array( $preparedData[ $variation_id ] ) ) {
						unset( $preparedData[ $variation_id ] );
					}
				}
			} else {
				$preparedData[ $value ] = $this->getFormattedData( $value, $shopId );
				if ( ! is_array( $preparedData[ $value ] ) ) {
					unset( $preparedData[ $value ] );
				}
			}
		}

		if ( isset( $preparedData ) && is_array( $preparedData ) && ! empty( $preparedData ) ) {
			$merchantFile = $this->create_csv( $preparedData, $Offset );
			return $merchantFile;
		}

	}


	public function ced_catch_prepareDataForOffers( $proIDs = array(), $shopId, $UpdateOrDelete = '', $isCron = false, $Offset = 'False', $with_product = '' ) {

		foreach ( $proIDs as $key => $value ) {
			$on_catch  = get_post_meta( $value, 'ced_catch_product_on_catch_' . $shopId, true );
			$prod_data = wc_get_product( $value );
			if ( ! is_object( $prod_data ) ) {
				continue;
			}
			$type = $prod_data->get_type();

			if ( 'variable' == $type ) {
				$prod_data  = wc_get_product( $value );
				$variations = $prod_data->get_available_variations();
				foreach ( $variations as $variation ) {
					$attributes                    = $variation['attributes'];
					$variation_id                  = $variation['variation_id'];
					$preparedData[ $variation_id ] = $this->getFormattedDataforOffer( $variation_id, $shopId, $UpdateOrDelete, $attributes, $with_product );
					if ( ! is_array( $preparedData[ $variation_id ] ) ) {
						unset( $preparedData[ $variation_id ] );
					}
				}
			} else {
				$preparedData[ $value ] = $this->getFormattedDataforOffer( $value, $shopId, $UpdateOrDelete, '', $with_product );
				if ( ! is_array( $preparedData[ $value ] ) ) {
					unset( $preparedData[ $value ] );
				}
			}
		}

		if ( isset( $preparedData ) && is_array( $preparedData ) && ! empty( $preparedData ) ) {
			$merchantFile = $this->create_csv( $preparedData, $Offset, $isCron );
			return $merchantFile;
		}

	}


	public function create_csv( $preparedData, $Offset = 'False', $isCron = false ) {
		$wpuploadDir = wp_upload_dir();
		$baseDir     = $wpuploadDir['basedir'];
		$uploadDir   = $baseDir . '/cedcommerce_catchuploads';
		$nameTime    = time();
		if ( ! is_dir( $uploadDir ) ) {
			mkdir( $uploadDir, 0777, true );
		}

		if ( $isCron ) {
			$file     = fopen( $uploadDir . '/CronMerchant' . time() . '.csv', 'w' );
			$location = wp_upload_dir()['basedir'] . '/cedcommerce_catchuploads/CronMerchant' . time() . '.csv';
		} else {
			$file     = fopen( $uploadDir . '/Merchant' . time() . '.csv', 'w' );
			$location = wp_upload_dir()['basedir'] . '/cedcommerce_catchuploads/Merchant' . time() . '.csv';
		}
		if ( isset( $preparedData ) && is_array( $preparedData ) && ! empty( $preparedData ) ) {
			$count = 0;
			foreach ( $preparedData as $key_preparedData => $value_preparedData ) {
				foreach ( $value_preparedData as $key_header => $value_header ) {
					$key_prodata[] = $key_header;
				}
				$count++;
				$value_preparedDatas[] = $value_preparedData;
			}
			$key_prodata = array_unique( $key_prodata );
			fputcsv( $file, $key_prodata );
			foreach ( $value_preparedDatas as $key => $value ) {
				if ( is_array( $value ) ) {
					fputcsv( $file, $value );
				}
			}
		}
		return $location;
	}

	public function getSplitVariations( $proId = '', $shopId = '' ) {

		$prod_data  = wc_get_product( $proId );
		$variations = $prod_data->get_available_variations();
		foreach ( $variations as $variation ) {
			$attributes                    = $variation['attributes'];
			$variation_id                  = $variation['variation_id'];
			$preparedData[ $variation_id ] = $this->getFormattedData( $variation_id, $shopId, $attributes );
		}
		return $preparedData;
	}

	/**
	 * This function is used to get product data
	 * getFormattedData
	 *
	 * @param  mixed $proIds
	 * @param  mixed $shopId
	 * @param  mixed $attributesforVariation
	 * @return void
	 */
	public function getFormattedData( $proIds = array(), $shopId = '', $attributesforVariation = '' ) {
		$profileData = $this->ced_catch_getProfileAssignedData( $proIds, $shopId );
		if ( ! $this->isProfileAssignedToProduct ) {
			return;
		}
		$product = wc_get_product( $proIds );

		$renderDataOnGlobalSettingsVariation = get_option( 'ced_catch_global_settings', array() );

		$parent_sku = '';

		if ( WC()->version > '3.0.0' ) {
			$product_data       = $product->get_data();
			$product_attributes = $product->get_attributes();
			$productType        = $product->get_type();
			$description        = $product_data['description'] . ' ' . $product_data['short_description'];
			$title              = $product_data['name'];

			$custom_description = get_post_meta( $proIds, '_ced_catch_custom_description', true );
			if ( ! empty( $custom_description ) ) {
				$description = $custom_description;
			}
			$terms = get_the_terms( $proIds, 'product_cat' );

			if ( $product->get_type() == 'variation' ) {
				$parentId           = $product->get_parent_id();
				$parentProduct      = wc_get_product( $parentId );
				$parentProductData  = $parentProduct->get_data();
				$product_attributes = $parentProduct->get_attributes();
				$description        = $parentProductData['description'] . '</br>' . $parentProductData['short_description'];
				$terms              = get_the_terms( $parentId, 'product_cat' );
				$parent_sku         = get_post_meta( $parentId, '_sku', true );
				if ( 'on' != $renderDataOnGlobalSettingsVariation[ $shopId ]['ced_catch_upload_pro_as_a_simple'] ) {
					$title = $parentProductData['name'];
				}

				$custom_description = get_post_meta( $parentId, '_ced_catch_custom_description', true );
				if ( ! empty( $custom_description ) ) {
					$description = $custom_description;
				}
			}

			$custom_title = get_post_meta( $proIds, 'ced_catch_custom_title', true );
			if ( ! empty( $custom_title ) ) {
				$title = $custom_title;
			}

			$price = (float) $product_data['price'];
			if ( 'variation' == $productType ) {
				$parent_id      = $product->get_parent_id();
				$parent_product = wc_get_product( $parent_id );
				$parent_product = $parent_product->get_data();
			}
		}

		$weight         = get_post_meta( $proIds, '_weight', true );
		$package_length = get_post_meta( $proIds, '_length', true );
		$package_width  = get_post_meta( $proIds, '_width', true );
		$package_height = get_post_meta( $proIds, '_height', true );
		$item_sku       = get_post_meta( $proIds, '_sku', true );
		$keywords       = get_post_meta( $proIds, 'ced_catch_product_keywords', true );
		$dimension_unit = get_option( 'woocommerce_dimension_unit', true );
		$weight_unit    = get_option( 'woocommerce_weight_unit', true );
		$description    = preg_replace( '#\[[^\]]+\]#', '', $description );

		$description = strip_tags( $description, '<p><ul><li><strong><br>' );
		$description = nl2br( $description );
		$description = preg_replace( '#(<br */?>\s*)+#i', '<br />', $description );

		$product = wc_get_product( $proIds );
		if ( $product->get_type() == 'variable' ) {
			$variations = $product->get_available_variations();
			if ( $variations ) {
				if ( 0 == $weight || '' == $weight ) {
					$weight = $variations[0]['weight'];
				}
				if ( 0 == $package_width || '' == $package_width ) {
					$package_width = $variations[0]['dimensions']['width'];
				}
				if ( 0 == $package_height || '' == $package_height ) {
					$package_height = $variations[0]['dimensions']['height'];
				}
				if ( 0 == $package_length || '' == $package_length ) {
					$package_length = $variations[0]['dimensions']['length'];
				}
			}
		}

		// get category
		$category_id = $this->fetchMetaValueOfProduct( $proIds, '_umb_catch_category' );

		// get all category specific attribute
		$attributes = get_option( 'ced_catch_category_attributes_' . $category_id, true );
		if ( isset( $attributes ) && is_array( $attributes ) && ! empty( $attributes ) ) {
			foreach ( $attributes as $attribute_key => $attribute_value ) {
				$args[ $attribute_value['code'] ] = get_post_meta( $proIds, 'ced_catch_custom_' . $attribute_value['code'], true );
				if ( empty( $args[ $attribute_value['code'] ] ) ) {
					$args[ $attribute_value['code'] ] = $this->fetchMetaValueOfProduct( $proIds, 'ced_catch_custom_' . $attribute_value['code'] );
				}
			}
		}

		$args['category'] = str_replace( '~', "'", $category_id );

		// get product specific attribute
		$product_specific_attribute_key = get_option( 'ced_catch_product_specific_attribute_key', array() );
		if ( isset( $product_specific_attribute_key ) && is_array( $product_specific_attribute_key ) && ! empty( $product_specific_attribute_key ) ) {
			foreach ( $product_specific_attribute_key as $key => $product_key ) {
				foreach ( $profileData as $key => $value ) {
					if ( 'ced_catch_custom_' . $product_key == $key ) {
						$key                  = trim( $key, '_' );
						$args[ $product_key ] = get_post_meta( $proIds, $key, true );
						if ( empty( $args[ $product_key ] ) ) {
							$args[ $product_key ] = $this->fetchMetaValueOfProduct( $proIds, $key );
						}
					}
				}
			}
		}

		// miscellaneous attributes
		$miscellaneousattributes = get_option( 'ced_catch_miscellaneousattributes', true );
		if ( isset( $miscellaneousattributes ) && is_array( $miscellaneousattributes ) && ! empty( $miscellaneousattributes ) ) {
			foreach ( $miscellaneousattributes as $attribute_key => $attribute_value ) {
				if ( empty( $args[ $attribute_value['code'] ] ) ) {
					if ( 'default_stock' == $attribute_value['code'] ) {
						continue;
					}
					$args[ $attribute_value['code'] ] = $this->fetchMetaValueOfProduct( $proIds, 'ced_catch_custom_' . $attribute_value['code'] );
				}
			}
		}

		$ced_catch_remove_sentence = get_option( 'ced_catch_remove_sentence_from_description_' . $shopId, '' );
		if ( ! empty( $ced_catch_remove_sentence ) ) {
			$description = str_replace( strtolower( $ced_catch_remove_sentence ), '', strtolower( $description ) );
		}

		$ced_catch_remove_spacial_character = get_option( 'ced_catch_remove_spacial_character' . $shopId, '' );
		if ( ! empty( $ced_catch_remove_spacial_character ) ) {
			$ced_catch_remove_spacial_character = explode( '|', $ced_catch_remove_spacial_character );
			foreach ( $ced_catch_remove_spacial_character as $rem_key => $rem_value ) {
				$description = str_replace( $rem_value, '', $description );
			}
		}

		// hold product description
		if ( empty( $args['product-description'] ) ) {
			$args['product-description'] = $description;
		}

		// product attribute
		$add_attribute = '';
		$attrname      = '';
		if ( ! empty( $product_attributes ) ) {
			foreach ( $product_attributes as $key => $value ) {
				$pro_attribute = $value->get_data();
				if ( ! empty( $pro_attribute['options'] ) ) {
					$attribute_value = array();
					$attrname        = str_replace( 'pa_', '', $pro_attribute['name'] );
					$attrname        = str_replace( 'attribute_', '', $attrname );
					$attrname        = str_replace( '-', ' ', $attrname );
					$add_attribute  .= '<p><b>' . ucwords( $attrname ) . ': ' .
					'</b>';
					foreach ( $pro_attribute['options'] as $attrkey => $attrvalue ) {
						if ( is_int( $attrvalue ) ) {
							$term_name = get_term( $attrvalue )->name;
						} else {
							$term_name = $attrvalue;
						}
						$attribute_value[] = $term_name;
					}
					$attribute_value = implode( ', ', $attribute_value );
					$add_attribute  .= '<span>' . $attribute_value . '</span>';
					$add_attribute  .= '<p>';
				}
			}
		}

		// hold product title
		if ( empty( $args['title'] ) ) {
			$args['title'] = $title;
		}

		if ( ! empty( $args['title_prefix'] ) ) {
			$args['title'] = ucwords( $args['title_prefix'] ) . ' - ' . $args['title'];
			unset( $args['title_prefix'] );
		}
		if ( ! empty( $args['title_suffix'] ) ) {
			$args['title'] = $args['title'] . ' - ' . ucwords( $args['title_suffix'] );
			unset( $args['title_suffix'] );
		}

		if ( ! empty( $args['title'] ) ) {
			$args['title'] = ucwords( strtolower( $args['title'] ) );
		}

		// hold product sku
		if ( empty( $args['internal-sku'] ) ) {
			$args['internal-sku'] = $item_sku;
		}
		if ( empty( $args['internal-sku'] ) || $parent_sku == $item_sku || empty( $item_sku ) ) {
			$args['internal-sku'] = $proIds;
		}
		// hold product weight
		if ( empty( $args['weight'] ) ) {
			$args['weight'] = (int) $weight;
		}

		// hold product width
		if ( empty( $args['width'] ) ) {
			$args['width'] = (int) $package_width;
		}

		// hold product length
		if ( empty( $args['length'] ) ) {
			$args['length'] = (int) $package_length;
		}

		// hold product height
		if ( empty( $args['height'] ) ) {
			$args['height'] = (int) $package_height;
		}

		// hold product keywords
		if ( empty( $args['keywords'] ) ) {
			$args['keywords'] = $keywords;
		}
		if ( ! empty( $args['keywords'] ) ) {
			$args['keywords'] = str_replace( ' ', '|', $args['keywords'] );
			$args['keywords'] = str_replace( ',', '|', $args['keywords'] );
		}

		// hold product weight-unit
		if ( empty( $args['weight-unit'] ) ) {
			$args['weight-unit'] = isset( $weight_unit ) ? $weight_unit : '';
		}

		// hold product width-unit
		if ( empty( $args['width-unit'] ) ) {
			$args['width-unit'] = isset( $dimension_unit ) ? $dimension_unit : '';
		}

		// hold product length-unit
		if ( empty( $args['length-unit'] ) ) {
			$args['length-unit'] = isset( $dimension_unit ) ? $dimension_unit : '';
		}

		// hold product height-unit
		if ( empty( $args['height-unit'] ) ) {
			$args['height-unit'] = isset( $dimension_unit ) ? $dimension_unit : '';
		}

		if ( empty( $args['adult'] ) ) {
			$args['adult'] = isset( $adult ) ? $adult : '';
		}

		if ( empty( $args['condition'] ) ) {
			$args['condition'] = '11';
		}

		unset( $args['title_suffix'] );
		unset( $args['default_stock'] );
		unset( $args['title_prefix'] );

		// image
		for ( $i = 1; $i <= 10; $i++ ) {
			$args[ 'image-' . $i ] = '';
		}

		$set_galary_image = false;
		if ( ! empty( $terms ) && is_array( $terms ) ) {
			foreach ( $terms as $key => $value ) {
				$ced_catch_set_galary_image = get_term_meta( $value->term_id, 'ced_catch_set_galary_image_' . $shopId, true );
				if ( ! empty( $ced_catch_set_galary_image ) && 'on' == $ced_catch_set_galary_image ) {
					$set_galary_image = true;
					break;
				} else {
					continue;
				}
			}
		}

		// get product image
		if ( 'variation' == $product->get_type() ) {
			$variant_parent_id = $product->get_parent_id();
			$parent_sku        = get_post_meta( $variant_parent_id, '_sku', true );
			if ( empty( $parent_sku ) ) {
				$parent_sku = (string) $variant_parent_id;
			}
			$variation_attributes = $product->get_variation_attributes();
			if ( is_array( $variation_attributes ) ) {
				$variant_size_value = implode( '-', $variation_attributes );
			}

			if ( 'on' != $renderDataOnGlobalSettingsVariation[ $shopId ]['ced_catch_upload_pro_as_a_simple'] ) {

				$args['variant-id'] = $parent_sku;
			} else {
				$args['variant-id']           = '';
				$args['variant-size-value']   = '';
				$args['variant-colour-value'] = '';
			}

			$parentId = $variant_parent_id;

			$pictureUrl = wp_get_attachment_image_url( get_post_meta( $proIds, '_thumbnail_id', true ), 'full' ) ? wp_get_attachment_image_url( get_post_meta( $proIds, '_thumbnail_id', true ), 'full' ) : '';
			if ( isset( $pictureUrl ) && ! empty( $pictureUrl ) ) {
				$args['image-1'] = $pictureUrl;
			} else {
				$pictureUrl      = wp_get_attachment_image_url( get_post_meta( $parentId, '_thumbnail_id', true ), 'full' ) ? wp_get_attachment_image_url( get_post_meta( $parentId, '_thumbnail_id', true ), 'full' ) : '';
				$args['image-1'] = $pictureUrl;
			}

			$attachment_ids = $product->get_gallery_image_ids();
			if ( empty( $attachment_ids ) ) {
				$variant_parent_id = $product->get_parent_id();
				$parent_product    = wc_get_product( $variant_parent_id );
				$attachment_ids    = $parent_product->get_gallery_image_ids();
			}

			$ced_catch_custom_variation_image = get_post_meta( $proIds, 'ced_catch_custom_variation_image', true );
			if ( ! empty( $ced_catch_custom_variation_image ) ) {
				$ced_catch_custom_variation_image = explode( ',', $ced_catch_custom_variation_image );
				foreach ( $ced_catch_custom_variation_image as $image_key => $ced_variation_image ) {
					$image_key = $image_key + 2;
					if ( $image_key > 8 ) {
						continue;
					}
					$args[ 'image-' . $image_key ] = $ced_variation_image;
				}
			} else {
				if ( true == $set_galary_image ) {
					if ( ! empty( $attachment_ids ) && 'on' != $renderDataOnGlobalSettingsVariation[ $shopId ]['ced_catch_upload_pro_as_a_simple'] ) {
						$count = 2;
						foreach ( $attachment_ids as $attachment_id ) {
							if ( $count > 8 ) {
								continue;
							}
							$args[ 'image-' . $count ] = wp_get_attachment_url( $attachment_id );
							// $count                     = $count + 1;
							++$count;
						}
					}
				}
			}
		} else {
			$pictureUrl                       = wp_get_attachment_image_url( get_post_meta( $proIds, '_thumbnail_id', true ), 'full' ) ? wp_get_attachment_image_url( get_post_meta( $proIds, '_thumbnail_id', true ), 'full' ) : '';
			$args['image-1']                  = $pictureUrl;
			$ced_catch_custom_variation_image = get_post_meta( $proIds, 'ced_catch_custom_variation_image', true );
			if ( ! empty( $ced_catch_custom_variation_image ) ) {
				$ced_catch_custom_variation_image = explode( ',', $ced_catch_custom_variation_image );
				foreach ( $ced_catch_custom_variation_image as $image_key => $ced_variation_image ) {
					$image_key = $image_key + 2;
					if ( $image_key > 8 ) {
						continue;
					}
					$args[ 'image-' . $image_key ] = $ced_variation_image;
				}
			} else {
				$attachment_ids = $product->get_gallery_image_ids();
				if ( ! empty( $attachment_ids ) ) {
					$count = 2;
					foreach ( $attachment_ids as $attachment_id ) {
						if ( $count > 8 ) {
							continue;
						}
						$args[ 'image-' . $count ] = wp_get_attachment_url( $attachment_id );
						++$count;
					}
				}
			}

			$args['variant-id']           = '';
			$args['variant-size-value']   = '';
			$args['variant-colour-value'] = '';
		}

		$image_size_chart = get_post_meta( $proIds, 'ced_catch_custom_size_chart', true );
		if ( empty( $image_size_chart ) ) {
			$image_size_chart = $this->fetchMetaValueOfProduct( $proIds, '_ced_image_size_chart' );
		}
		$args['image-size-chart'] = $image_size_chart;
		ksort( $args );

		$ced_catch_validation = $this->ced_catch_validation( $args, $proIds );
		if ( ! $ced_catch_validation ) {
			return;
		}
		return $args;
	}

	public function ced_catch_validation( $args, $proIds ) {
		$error = array();
		if ( empty( $args['internal-sku'] ) ) {
			$error[] = 'Internal sku is empty';
		}

		if ( empty( $args['product-reference-type'] ) ) {
			$error[] = 'Product reference type is empty.';
		}

		if ( empty( $args['product-reference-value'] ) ) {
			$error[] = 'Product reference value is empty.';
		}

		if ( empty( $args['title'] ) ) {
			$error[] = 'Title is empty.';
		} elseif ( strlen( $args['title'] ) > 155 ) {
			$error[] = 'Title is too long(greater than 155 characters).';
		}

		if ( empty( $args['product-description'] ) ) {
			$error[] = 'Product description is empty.';
		}

		if ( empty( $args['brand'] ) ) {
			$error[] = 'Brand is empty';
		} elseif ( strpos( $args['brand'], '&' ) !== false ) {
			$error[] = 'Brand is not accepted & character.';
		}

		if ( empty( $args['image-1'] ) ) {
			$error[] = 'Image is empty.';
		}

		if ( ! empty( $error ) ) {
			update_post_meta( $proIds, 'ced_catch_validation_error', $error );
			return false;
		}
		update_post_meta( $proIds, 'ced_catch_validation_error', '' );
		return true;
	}

	/**
	 * This function is used to get price
	 * get_updated_price
	 *
	 * @param  mixed $proIds
	 * @param  mixed $price
	 */
	public function get_updated_price( $proIds, $price ) {
		$markup_type = $this->fetchMetaValueOfProduct( $proIds, 'ced_catch_custom_markup_type' );

		$markup_value = (int) $this->fetchMetaValueOfProduct( $proIds, 'ced_catch_custom_markup_price' );
		$custom_price = get_post_meta( $proIds, 'ced_catch_custom_price', true );
		if ( empty( $custom_price ) ) {
			$fetch_price = $this->fetchMetaValueOfProduct( $proIds, 'ced_catch_custom_price' );
			if ( ! empty( $fetch_price ) ) {
				$price = $fetch_price;
			}
		} else {
			$price = $custom_price;
		}

		if ( ! empty( $markup_type ) ) {
			if ( ! empty( $markup_value ) ) {

				if ( 'Fixed_Increased' == $markup_type ) {
					$price = $price + $markup_value;
				} elseif ( 'Fixed_Decreased' == $markup_type ) {
					if ( $markup_value >= $price ) {
						$price = $price;
					} else {
						$price = $price - $markup_value;
					}
				} elseif ( 'Percentage_Increased' == $markup_type ) {
					$price = ( $price + ( ( $markup_value / 100 ) * $price ) );
				} elseif ( 'Percentage_Decreased' == $markup_type ) {
					$percentage_Decreased_price = ( $markup_value / 100 ) * $price;
					if ( $percentage_Decreased_price >= $price ) {
						$price = $price;
					} else {
						$price = ( $price - ( ( $markup_value / 100 ) * $price ) );
					}
				}
			}
		}
		return $price;

	}


	/**
	 * This function is used to get discount price
	 * get_updated_discounted_price
	 *
	 * @param  mixed $proIds
	 * @param  mixed $price
	 * @return void
	 */
	public function get_updated_discounted_price( $proIds = '', $discounted_price = '' ) {
		$markup_type = $this->fetchMetaValueOfProduct( $proIds, 'ced_catch_custom_markup_type' );

		$markup_value          = (int) $this->fetchMetaValueOfProduct( $proIds, 'ced_catch_custom_markup_price' );
		$custom_discount_price = get_post_meta( $proIds, 'ced_catch_custom_discount-price', true );

		if ( empty( $custom_discount_price ) ) {
			$fetch_discount_price = $this->fetchMetaValueOfProduct( $proIds, 'ced_catch_custom_discount-price' );
			if ( ! empty( $fetch_discount_price ) ) {
				$discounted_price = $fetch_discount_price;
			}
		} else {
			$discounted_price = $custom_discount_price;     }
		if ( ! empty( $markup_type ) ) {
			if ( ! empty( $markup_value ) ) {
				if ( ! empty( $discounted_price ) ) {
					if ( 'Fixed_Increased' == $markup_type ) {
						$discounted_price = $discounted_price + $markup_value;
					} elseif ( 'Fixed_Decreased' == $markup_type ) {
						if ( $markup_value >= $discounted_price ) {
							$discounted_price = $discounted_price;
						} else {
							$discounted_price = $discounted_price - $markup_value;
						}
					} elseif ( 'Percentage_Increased' == $markup_type ) {
						$discounted_price = $discounted_price + ( ( $markup_value / 100 ) * $discounted_price );
					} elseif ( 'Percentage_Decreased' == $markup_type ) {
						$percentage_decreased_discount_price = ( $markup_value / 100 ) * $discounted_price;
						if ( $percentage_decreased_discount_price >= $price ) {
							$discounted_price = $discounted_price;
						} else {
							$discounted_price = $discounted_price - ( ( $markup_value / 100 ) * $discounted_price );
						}
					}
				}
			}
		}
			return $discounted_price;
	}


	/**
	 * This function is used to get offer specific data
	 * getFormattedDataforOffer
	 *
	 * @param  mixed $proIds
	 * @param  mixed $shopId
	 * @param  mixed $UpdateOrDelete
	 * @param  mixed $attributesforVariation
	 * @return void
	 */
	public function getFormattedDataforOffer( $proIds = array(), $shopId = '', $UpdateOrDelete = '', $attributesforVariation = '', $with_product = '' ) {
		$profileData = $this->ced_catch_getProfileAssignedData( $proIds, $shopId );
		if ( ! $this->isProfileAssignedToProduct ) {
			return;
		}
		$product = wc_get_product( $proIds );

		if ( WC()->version > '3.0.0' ) {
			$product_data = $product->get_data();
			$productType  = $product->get_type();
			$quantity     = (int) get_post_meta( $proIds, '_stock', true );
			$description  = $product_data['description'] . ' ' . $product_data['short_description'];
			if ( $product->get_type() == 'variation' ) {
				$parentId          = $product->get_parent_id();
				$parentProduct     = wc_get_product( $parentId );
				$parentProductData = $parentProduct->get_data();
				$description       = $parentProductData['description'] . '' . $parentProductData['short_description'];
			}
			$title = $product_data['name'];
			$price = (float) $product_data['price'];
			if ( 'variable' == $productType ) {
				$variations = $product->get_available_variations();
				if ( isset( $variations['0']['display_regular_price'] ) ) {
					$price = $variations['0']['display_regular_price'];
				}
			}
		}

		// get category id
		$category_id = $this->fetchMetaValueOfProduct( $proIds, '_umb_catch_category' );
		$product     = wc_get_product( $proIds );

		// get all offer attribute
		$Offerattributes     = get_option( 'ced_catch_category_offerattributes', true );
		$pro_offerattributes = array();
		$discounted_price    = '';
		if ( isset( $Offerattributes ) && is_array( $Offerattributes ) && ! empty( $Offerattributes ) ) {
			foreach ( $Offerattributes as $attribute_key => $attribute_value ) {
				$categoryId = str_replace( ' ', '_', $category_id );
				if ( 'price' == $attribute_value['code'] || 'discount-price' == $attribute_value['code'] ) {
					continue;
				}
				$pro_offerattributes[ $attribute_value['code'] ] = get_post_meta( $proIds, 'ced_catch_custom_' . $attribute_value['code'], true );

				if ( empty( $pro_offerattributes[ $attribute_value['code'] ] ) ) {
					$pro_offerattributes[ $attribute_value['code'] ] = $this->fetchMetaValueOfProduct( $proIds, 'ced_catch_custom_' . $attribute_value['code'] );
				}
			}
		}

		// change date format
		if ( ! empty( $pro_offerattributes ) ) {
			foreach ( $pro_offerattributes as $key12 => $value12 ) {
				if ( isset( $value12 ) && ! empty( $value12 ) ) {
					if ( ! empty( $value12 ) && ( 'discount-start-date' == $key12 || 'discount-end-date' == $key12 || 'best-before-date' == $key12 || 'expiry-date' == $key12 || 'available-start-date' == $key12 || 'available-end-date' == $key12 ) ) {
						$date                          = new DateTime( $value12 );
						$data                          = $date->format( DATE_ISO8601 );
						$pro_offerattributes[ $key12 ] = $data;
					}
				}
			}
		}

		// miscellaneous attributes
		$miscellaneousattributes = get_option( 'ced_catch_miscellaneousattributes', true );
		if ( isset( $miscellaneousattributes ) && is_array( $miscellaneousattributes ) && ! empty( $miscellaneousattributes ) ) {
			foreach ( $miscellaneousattributes as $attribute_key => $attribute_value ) {
				if ( empty( $pro_offerattributes[ $attribute_value['code'] ] ) ) {

					if ( 'title_prefix' == $attribute_value['code'] && 'title_suffix' == $attribute_value['code'] ) {
						continue;
					}
					$pro_offerattributes[ $attribute_value['code'] ] = $this->fetchMetaValueOfProduct( $proIds, 'ced_catch_custom_' . $attribute_value['code'] );
				}
			}
		}

		// default tax
		if ( empty( $pro_offerattributes['tax-au'] ) ) {
			$pro_offerattributes['tax-au'] = 0;
		}

		// defalut state
		if ( empty( $pro_offerattributes['state'] ) ) {
			$pro_offerattributes['state'] = '11';
		}

		// default club catch eligible
		if ( empty( $pro_offerattributes['club-catch-eligible'] ) ) {
			$pro_offerattributes['club-catch-eligible'] = 'false';
		}

		// get discounted price
		$discounted_price = $this->get_updated_discounted_price( $proIds, $discounted_price );

		// get price
		$price = $this->get_updated_price( $proIds, $price );

		// get manage stock
		$manage_stock = get_post_meta( $proIds, '_manage_stock', true );

		// get manage stock status
		$stock_status = get_post_meta( $proIds, '_stock_status', true );
		if ( trim( $stock_status ) == 'outofstock' ) {
			$quantity = 0;
		} elseif ( trim( $stock_status ) == 'instock' && trim( $manage_stock ) == 'no' ) {
			if ( ! empty( $pro_offerattributes['default_stock'] ) ) {
				$quantity = $pro_offerattributes['default_stock'];
				unset( $pro_offerattributes['default_stock'] );
			} else {
				$quantity = 1;
			}
		}

		if ( $quantity <= 0 ) {
			$quantity = 0;
		}

		// get sku
		$item_sku = get_post_meta( $proIds, '_sku', true );

		$product_catch_sku = get_post_meta( $proIds, 'ced_catch_product_sku', true );
		if ( ! empty( $product_catch_sku ) ) {
			$product_id_type = 'SKU';
			$product_id      = $product_catch_sku;
		} else {
			$product_id_type = 'SHOP_SKU';
			$product_id      = $item_sku;
		}

		if ( $discounted_price >= $price ) {
			$discounted_price = '';
		}

		$args               = array(
			'price'           => (float) $price,
			'quantity'        => (int) $quantity,
			'product-id-type' => $product_id_type,
			'update-delete'   => isset( $UpdateOrDelete ) ? $UpdateOrDelete : '',
			'discount-price'  => $discounted_price,
		);
		$args['sku']        = $item_sku;
		$args['product-id'] = $product_id;

		if ( ! empty( $pro_offerattributes ) ) {
			$args = array_merge( $args, $pro_offerattributes );
		}

		if ( empty( $args['discount-price'] ) ) {
			$args['discount-start-date'] = '';
			$args['discount-end-date']   = '';
		}

		if ( 'with_product' == $with_product ) {
			$prod_args = $this->getFormattedData( $proIds, $shopId );
			$args      = array_merge( $args, $prod_args );
		}

		unset( $args['title_suffix'] );
		unset( $args['default_stock'] );
		unset( $args['title_prefix'] );

		ksort( $args );
		return $args;
	}


	/*
	*
	*function for getting profile data of the product
	*
	*
	*/
	public function ced_catch_getProfileAssignedData( $proIds, $shopId ) {
		$data = wc_get_product( $proIds );
		if ( ! is_object( $data ) ) {
			return;
		}
		$type = $data->get_type();
		if ( 'variation' == $type ) {
			$proIds = $data->get_parent_id();
		}
		global $wpdb;
		$productData = wc_get_product( $proIds );

		$product     = $productData->get_data();
		$category_id = isset( $product['category_ids'] ) ? $product['category_ids'] : array();

		foreach ( $category_id as $key => $value ) {
			$profile_id = get_term_meta( $value, 'ced_catch_profile_id_' . $shopId, true );
			if ( ! empty( $profile_id ) ) {
				break;
			}
		}
		if ( isset( $profile_id ) && ! empty( $profile_id ) && '' != $profile_id ) {
			$this->isProfileAssignedToProduct = true;

			$profile_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_catch_profiles WHERE `id`= %d", $profile_id ), 'ARRAY_A' );
			if ( is_array( $profile_data ) ) {
				$profile_data = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
				$profile_data = isset( $profile_data['profile_data'] ) ? json_decode( $profile_data['profile_data'], true ) : array();
			}
		} else {
			$this->isProfileAssignedToProduct = false;
		}
		$this->profile_data = isset( $profile_data ) ? $profile_data : '';

		return $this->profile_data;
	}

	/*
	*
	*function for getting meta value of the product
	*
	*
	*/
	public function fetchMetaValueOfProduct( $proIds, $metaKey, $is_sync = false, $sync_data = array() ) {

		if ( $is_sync ) {
			$this->isProfileAssignedToProduct = true;
			$this->profile_data               = $sync_data;
		}

		if ( '_woocommerce_title' == $metaKey ) {
			$product = wc_get_product( $proIds );
			if ( ! is_object( $product ) ) {
				return;
			}
			return $product->get_title();
		}if ( '_woocommerce_short_description' == $metaKey ) {
			$product = wc_get_product( $proIds );
			if ( ! is_object( $product ) ) {
				return;
			}
			if ( $product->get_type() == 'variation' ) {
				$_parent_obj = wc_get_product( $product->get_parent_id() );
				if ( ! is_object( $_parent_obj ) ) {
					return;
				}
				return $_parent_obj->get_short_description();
			}
			return $product->get_short_description();

		}if ( '_woocommerce_description' == $metaKey ) {
			$product = wc_get_product( $proIds );
			if ( ! is_object( $product ) ) {
				return;
			}
			if ( $product->get_type() == 'variation' ) {
				$_parent_obj = wc_get_product( $product->get_parent_id() );
				if ( ! is_object( $_parent_obj ) ) {
					return;
				}
				return $_parent_obj->get_description();
			}
			return $product->get_description();
		}

		if ( isset( $this->isProfileAssignedToProduct ) && $this->isProfileAssignedToProduct ) {
			$_product = wc_get_product( $proIds );
			if ( ! is_object( $_product ) ) {
				return;
			}
			if ( $_product->get_type() == 'variation' ) {
				$parentId = $_product->get_parent_id();
			} else {
				$parentId = '0';
			}
			if ( ! empty( $this->profile_data ) && isset( $this->profile_data[ $metaKey ] ) ) {
				$profileData     = $this->profile_data[ $metaKey ];
				$tempProfileData = $profileData;
				if ( isset( $tempProfileData['default'] ) && ! empty( $tempProfileData['default'] ) && '' != $tempProfileData['default'] && ! is_null( $tempProfileData['default'] ) ) {
					$value = $tempProfileData['default'];
				} elseif ( isset( $tempProfileData['metakey'] ) && ! empty( $tempProfileData['metakey'] ) && 'null' != $tempProfileData['metakey'] ) {
					if ( '_woocommerce_title' == $tempProfileData['metakey'] ) {
						$product = wc_get_product( $proIds );
						return $product->get_title();
					}if ( '_woocommerce_short_description' == $tempProfileData['metakey'] ) {
						$product = wc_get_product( $proIds );
						if ( $product->get_type() == 'variation' ) {
							$_parent_obj = wc_get_product( $product->get_parent_id() );
							return $_parent_obj->get_short_description();
						}
						return $product->get_short_description();

					}if ( '_woocommerce_description' == $tempProfileData['metakey'] ) {
						$product = wc_get_product( $proIds );
						if ( $product->get_type() == 'variation' ) {
							$_parent_obj = wc_get_product( $product->get_parent_id() );
							return $_parent_obj->get_description();
						}
						return $product->get_description();
					}

					if ( strpos( $tempProfileData['metakey'], 'umb_pattr_' ) !== false ) {

						$wooAttribute = explode( 'umb_pattr_', $tempProfileData['metakey'] );
						$wooAttribute = end( $wooAttribute );
						if ( $_product->get_type() == 'variation' ) {

							$attributes = $_product->get_variation_attributes();
							if ( isset( $attributes[ 'attribute_pa_' . $wooAttribute ] ) && ! empty( $attributes[ 'attribute_pa_' . $wooAttribute ] ) ) {
								$wooAttributeValue = $attributes[ 'attribute_pa_' . $wooAttribute ];
								if ( '0' != $parentId ) {
									$product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
								} else {
									$product_terms = get_the_terms( $proIds, 'pa_' . $wooAttribute );
								}
							} else {
								$wooAttributeValue = $_product->get_attribute( 'pa_' . $wooAttribute );
								$wooAttributeValue = explode( ',', $wooAttributeValue );
								$wooAttributeValue = $wooAttributeValue[0];

								if ( '0' != $parentId ) {
									$product_terms = get_the_terms( $parentId, 'pa_' . $wooAttribute );
								} else {
									$product_terms = get_the_terms( $proIds, 'pa_' . $wooAttribute );
								}
							}
							if ( is_array( $product_terms ) && ! empty( $product_terms ) ) {
								foreach ( $product_terms as $tempkey => $tempvalue ) {
									if ( $tempvalue->slug == $wooAttributeValue ) {
										$wooAttributeValue = $tempvalue->name;
										break;
									}
								}
								if ( isset( $wooAttributeValue ) && ! empty( $wooAttributeValue ) ) {
									$value = $wooAttributeValue;
								} else {
									$value = get_post_meta( $proIds, $metaKey, true );
								}
							} else {
								$value = get_post_meta( $proIds, $metaKey, true );
							}
						} else {
							$wooAttributeValue = $_product->get_attribute( 'pa_' . $wooAttribute );
							$product_terms     = get_the_terms( $proIds, 'pa_' . $wooAttribute );
							if ( is_array( $product_terms ) && ! empty( $product_terms ) ) {
								foreach ( $product_terms as $tempkey => $tempvalue ) {
									if ( $tempvalue->slug == $wooAttributeValue ) {
										$wooAttributeValue = $tempvalue->name;
										break;
									}
								}
								if ( isset( $wooAttributeValue ) && ! empty( $wooAttributeValue ) ) {
									$value = $wooAttributeValue;
								} else {
									$value = get_post_meta( $proIds, $metaKey, true );
								}
							} elseif ( ! empty( $wooAttributeValue ) ) {
								$value = $wooAttributeValue;
							} else {
								$value = get_post_meta( $proIds, $metaKey, true );
							}
						}
					} else {

						$value = get_post_meta( $proIds, $tempProfileData['metakey'], true );
						if ( '_thumbnail_id' == $tempProfileData['metakey'] ) {
							$value = wp_get_attachment_image_url( get_post_meta( $proIds, '_thumbnail_id', true ), 'thumbnail' ) ? wp_get_attachment_image_url( get_post_meta( $proIds, '_thumbnail_id', true ), 'thumbnail' ) : '';
						}
						if ( ! isset( $value ) || empty( $value ) || '' == $value || is_null( $value ) || '0' == $value || 'null' == $value ) {
							if ( '0' != $parentId ) {

								$value = get_post_meta( $parentId, $tempProfileData['metakey'], true );
								if ( '_thumbnail_id' == $tempProfileData['metakey'] ) {
									$value = wp_get_attachment_image_url( get_post_meta( $parentId, '_thumbnail_id', true ), 'thumbnail' ) ? wp_get_attachment_image_url( get_post_meta( $parentId, '_thumbnail_id', true ), 'thumbnail' ) : '';
								}

								if ( ! isset( $value ) || empty( $value ) || '' == $value || is_null( $value ) ) {
									$value = get_post_meta( $proIds, $metaKey, true );

								}
							} else {
								$value = get_post_meta( $proIds, $metaKey, true );
							}
						}
					}
				} else {
					$value = get_post_meta( $proIds, $metaKey, true );
				}
			} else {
				$value = get_post_meta( $proIds, $metaKey, true );
			}
			return $value;
		}

	}
	public function doupload( $file = '', $shopId, $uploadType = '', $isCron = false ) {

		$response = $this->uploadToCatch( $file, $shopId, $uploadType, $isCron );
		return $response;

	}
	public function uploadToCatch( $parameters, $shopId, $uploadType = '', $isCron ) {
		require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchSendHttpRequest.php';
		if ( 'Product' == $uploadType ) {
			$action = 'products/imports';
		} else {
			$action = 'offers/imports';
		}
		$parameters     = $parameters;
		$sendRequestObj = new Class_Ced_Catch_Send_Http_Request();

		$response = $sendRequestObj->sendHttpRequest( $action, $parameters = array( 'file' => $parameters ), $shopId, $uploadType );
		$response = json_decode( $response, true );
		if ( isset( $response['import_id'] ) ) {
			$importIds                              = get_option( 'ced_catch_import_ids_' . $shopId, array() );
			$import_id                              = $response['import_id'];
			$importIds[ $import_id ]['import_id']   = $import_id;
			$importIds[ $import_id ]['file']        = $parameters['file'];
			$importIds[ $import_id ]['upload_type'] = $uploadType;
			if ( ! $isCron ) {
				update_option( 'ced_catch_import_ids_' . $shopId, $importIds );
				update_option( 'ced_catch_import_type_' . $import_id, $uploadType );
			}
		}
		return $response;
	}
}
