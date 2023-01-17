<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$file = CED_CATCH_DIRPATH . 'admin/partials/header.php';
require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchCategory.php';
require_once CED_CATCH_DIRPATH . 'admin/partials/products_fields.php';

if ( file_exists( $file ) ) {
	require_once $file;
}

$shop_id = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';

$profileID = isset( $_GET['profileID'] ) ? sanitize_text_field( $_GET['profileID'] ) : '';
$notice    = '';

if ( isset( $_POST['add_meta_keys'] ) || isset( $_POST['ced_catch_profile_save_button'] ) ) {
	$ced_catch_profile_edit_nonce = isset( $_POST['ced_catch_profile_edit_nonce'] ) ? sanitize_text_field( $_POST['ced_catch_profile_edit_nonce'] ) : '';
	if ( wp_verify_nonce( $ced_catch_profile_edit_nonce, 'ced_catch_profile_edit_nonce' ) ) {
		$is_active                   = isset( $_POST['profile_status'] ) ? 'active' : 'active';
		$marketplaceName             = isset( $_POST['marketplaceName'] ) ? sanitize_text_field( $_POST['marketplaceName'] ) : 'all';
		$updateinfo                  = array();
		$ced_catch_profile_wholeData = isset( $_POST['ced_catch_required_common'] ) ? array_map( 'sanitize_text_field', $_POST['ced_catch_required_common'] ) : '';

		if ( ! empty( $ced_catch_profile_wholeData ) ) {
			foreach ( $ced_catch_profile_wholeData as $key ) {
				$arrayToSave = array();
				isset( $_POST[ $key ][0] ) ? $arrayToSave['default'] = sanitize_text_field( $_POST[ $key ][0] ) : $arrayToSave['default'] = '';
				if ( '_umb_' . $marketplaceName . '_subcategory' == $key ) {
					isset( $_POST[ $key ] ) ? $arrayToSave['default'] = sanitize_text_field( $_POST[ $key ] ) : $arrayToSave['default'] = '';
				}
				if ( '_umb_catch_category' == $key && '' == $profileID ) {
					$profileCategoryNames = array();
					for ( $i = 1; $i < 8; $i++ ) {
						$profileCategoryNames[] = isset( $_POST[ 'ced_catch_level' . $i . '_category' ] ) ? sanitize_text_field( $_POST[ 'ced_catch_level' . $i . '_category' ] ) : '';
					}
					$CategoryNames = array();
					foreach ( $profileCategoryNames as $key1 => $value1 ) {
						if ( isset( $value1[0] ) && ! empty( $value1[0] ) ) {
							$CategoryName = $value1[0];
						}
					}
					$category_id = $CategoryName;
					isset( $_POST[ $key ][0] ) ? $arrayToSave['default'] = $category_id : $arrayToSave['default'] = '';

				}

				isset( $_POST[ $key . '_attibuteMeta' ] ) ? $arrayToSave['metakey'] = sanitize_text_field( $_POST[ $key . '_attibuteMeta' ] ) : $arrayToSave['metakey'] = 'null';
				$updateinfo[ $key ] = $arrayToSave;
			}
		}

		$updateinfo = json_encode( $updateinfo );

		global $wpdb;
		$tableName = $wpdb->prefix . 'ced_catch_profiles';
		if ( '' == $profileID ) {
			$profileCategoryNames = array();
			for ( $i = 1; $i < 8; $i++ ) {
				$profileCategoryNames[] = isset( $_POST[ 'ced_catch_level' . $i . '_category' ] ) ? sanitize_text_field( $_POST[ 'ced_catch_level' . $i . '_category' ] ) : '';
			}
			$CategoryNames = array();
			foreach ( $profileCategoryNames as $key => $value ) {
				if ( isset( $value[0] ) && ! empty( $value[0] ) ) {
					$CategoryName = $value[0];
				}
			}

			$profile_category_id = $CategoryName;

			$profileDetails = array(
				'profile_name'   => $CategoryName,
				'profile_status' => 'active',
				'profile_data'   => $updateinfo,
				'shop_id'        => $shop_id,
			);

			global $wpdb;
			$profileTableName = $wpdb->prefix . 'ced_catch_profiles';
			$wpdb->insert( $profileTableName, $profileDetails );
			$profileId = $wpdb->insert_id;

			$profile_edit_url = admin_url( 'admin.php?page=ced_catch&profileID=' . $profileId . '&section=profiles-view&panel=edit&shop_id=' . $shop_id );
			header( 'location:' . $profile_edit_url . '' );

		} elseif ( $profileID ) {
			echo '<div class="notice notice-success is-dismissible">
					        <p>' . esc_attr( __( 'Profile saved Successfully!', 'sample-text-domain' ) ) . '</p>
					    </div>';

			$wpdb->query(
				$wpdb->prepare(
					"
			    UPDATE {$wpdb->prefix}ced_catch_profiles
			    SET  `profile_status`=%s ,`profile_data`=%s
			    WHERE `id` = %d",
					$is_active,
					$updateinfo,
					$profileID
				)
			);
		}
	}
}

global $wpdb;

$tableName = $wpdb->prefix . 'ced_catch_profiles';

$profile_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_catch_profiles WHERE `id`=%d", $profileID ), 'ARRAY_A' );

if ( ! empty( $profile_data ) ) {
	$profile_category_data = json_decode( $profile_data[0]['profile_data'], true );
}
$profile_category_data = isset( $profile_category_data ) ? $profile_category_data : '';
$profile_category_id   = isset( $profile_category_data['_umb_catch_category']['default'] ) ? $profile_category_data['_umb_catch_category']['default'] : '';

$profileCategoryId = $profile_category_id;

$profile_data       = isset( $profile_data[0] ) ? $profile_data[0] : $profile_data;
$attributes         = wc_get_attribute_taxonomies();
$attrOptions        = array();
$addedMetaKeys      = get_option( 'ced_catch_selected_metakeys', array() );
$addedMetaKeys      = array_merge( $addedMetaKeys, array( '_woocommerce_title', '_woocommerce_short_description', '_woocommerce_description' ) );
$selectDropdownHTML = '';

if ( $addedMetaKeys && count( $addedMetaKeys ) > 0 ) {
	foreach ( $addedMetaKeys as $key => $metaKey ) {
		if ( is_array( $metaKey ) ) {
			continue;
		}
		$attrOptions[ $metaKey ] = $metaKey;
	}
}
if ( ! empty( $attributes ) ) {
	foreach ( $attributes as $attributesObject ) {
		$attrOptions[ 'umb_pattr_' . $attributesObject->attribute_name ] = $attributesObject->attribute_label;
	}
}
/* select dropdown setup */
ob_start();
$fieldID             = '{{*fieldID}}';
$selectId            = 'ced_catch_custom_' . $fieldID . '_attibuteMeta';
$selectDropdownHTML .= '<select id="' . $selectId . '" name="' . $selectId . '">';
$selectDropdownHTML .= '<option value="null"> -- select -- </option>';
if ( is_array( $attrOptions ) ) {
	foreach ( $attrOptions as $attrKey => $attrName ) :
		$selectDropdownHTML .= '<option value="' . $attrKey . '">' . $attrName . '</option>';
	endforeach;
}
$selectDropdownHTML  .= '</select>';
$attributesFilePath   = CED_CATCH_DIRPATH . 'admin/catch/lib/json/';
$attributes           = file_get_contents( $attributesFilePath . 'catch-product-attribute.json' );
$OfferAttributes      = file_get_contents( $attributesFilePath . 'offer-attributes.json' );
$OfferAttributes      = json_decode( $OfferAttributes, true );
$productFieldInstance = CedCatchProductsFields::get_instance();

$categorySpecifics = $productFieldInstance->ced_catch_getCategorySpecificAtrributes( $attributes, str_replace( '~', "'", $profile_category_id ) );
if ( ! empty( $categorySpecifics ) ) {
	foreach ( $categorySpecifics as $key => $value ) {
		$attribute_data[] = array(
			'code'  => $value['code'],
			'label' => $value['label'],
		);
	}
	update_option( 'ced_catch_category_attributes_' . $profile_category_id, $attribute_data );
}
if ( ! empty( $OfferAttributes ) ) {
	foreach ( $OfferAttributes as $key => $value ) {
		$attribute_offerdata[] = array(
			'code'  => $value['code'],
			'label' => $value['label'],
		);
	}
	update_option( 'ced_catch_category_offerattributes_' . $profile_category_id, $attribute_offerdata );
}
$fields = $productFieldInstance->ced_catch_get_custom_products_fields( $shop_id );

$fields              = json_decode( $fields, true );
$offerfields         = $productFieldInstance->ced_catch_get_custom_offers_fields();
$miscellaneousfields = $productFieldInstance->ced_catch_get_custom_miscellaneous_fields();

if ( ! empty( $miscellaneousfields ) ) {
	foreach ( $miscellaneousfields as $key => $value ) {
		$attribute_miscellaneousdata[] = array(
			'code'  => $value['fields']['id'],
			'label' => $value['fields']['label'],
		);
	}

	update_option( 'ced_catch_miscellaneousattributes', $attribute_miscellaneousdata );
}
$catchCategorieslevel1 = file_get_contents( CED_CATCH_DIRPATH . 'admin/catch/lib/json/categoryLevel-1.json' );
$catchCategorieslevel1 = json_decode( $catchCategorieslevel1, true );
$marketPlace           = 'ced_catch_required_common';
?>
<?php require_once CED_CATCH_DIRPATH . 'admin/partials/ced-catch-metakeys-template.php'; ?>
<form action="" method="post">
	<input type="hidden" id="ced_catch_profile_edit_nonce" name="ced_catch_profile_edit_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ced_catch_profile_edit_nonce' ) ); ?>"/>	
	<div class="ced_catch_heading">
		<?php echo esc_html_e( get_catch_instuctions_html( 'BASIC DETAILS' ) ); ?>
		<div class="ced_catch_child_element">
			<table>
				<tr>
					<td>
						<label><?php esc_attr_e( 'Profile Name  :- ', 'woocommerce-catch-integration' ); ?></label>
					</td>
					<td></td>
					<?php

					if ( isset( $profile_data['profile_name'] ) ) {

						?>
						<td>
							<label><?php echo esc_attr( ucwords( $profile_data['profile_name'] ) ); ?></label>
						</td>
					</tr>
						<?php
					}
					?>
			</table>
		</div>
	</div>
	<div class="ced_catch_heading">
		<?php echo esc_html_e( get_catch_instuctions_html( 'Product Specific' ) ); ?>
		<div class="ced_catch_child_element">
			<div class="ced_catch_profile_details_wrapper">
				<div class="ced_catch_profile_details_fields">
					<table>
						<tr>
							<?php
							$requiredInAnyCase = array( '_umb_id_type', '_umb_id_val', '_umb_brand' );
							$requiredAnyCase   = '';
							global $global_CED_catch_Render_Attributes;
							$marketPlace        = 'ced_catch_required_common';
							$productID          = 0;
							$categoryID         = '';
							$indexToUse         = 0;
							$selectDropdownHTML = $selectDropdownHTML;
							$description        = '';
							if ( ! empty( $profile_data ) ) {
								$data = json_decode( $profile_data['profile_data'], true );
							}

							$product_specific_attribute_key = get_option( 'ced_catch_product_specific_attribute_key', array() );
							$category_attributes            = get_option( 'ced_catch_category_attributes_' . $profile_category_id, true );

							foreach ( $fields as $attributes_field ) {
								foreach ( $attributes_field as $value ) {
									if ( 'category' == $value['code'] ) {
										continue;
									}

									if ( ! empty( $category_attributes ) && is_array( $category_attributes ) ) {
										foreach ( $category_attributes  as $cat_attr_key => $cat_attr_value ) {
											$check_unique_attr = false;
											if ( ! empty( $cat_attr_value ) && $cat_attr_value['code'] == $value['code'] ) {
												$check_unique_attr = true;
												break;
											}
										}
									}

									if ( $check_unique_attr ) {
										continue;
									}

									$isText   = true;
									$check    = false;
									$field_id = trim( $value['code'], '_' );
									if ( $value['required'] ) {

										$attributeNameToRender = ucfirst( $value['label'] ) . '<span class="ced_catch_wal_required">' . __( ' *', 'woocommerce-catch-integration' ) . '</span>';

									} else {
										$attributeNameToRender = ucfirst( $value['label'] );
									}

									if ( ! empty( $value['code'] ) && 'image-size-chart' == $value['code'] ) {
										$value['type'] = 'TEXT';
									}

									$default = isset( $data[ 'ced_catch_custom_' . $value['code'] ]['default'] ) ? $data[ 'ced_catch_custom_' . $value['code'] ]['default'] : '';

									if ( '' == $value['hierarchy_code'] ) {

										if ( null != $value['description'] ) {
											$description = $value['description'];
										} else {
											$description = $field_id;
										}

										if ( empty( $product_specific_attribute_key ) ) {
											$product_specific_attribute_key   = array( $field_id );
											$product_specific_attribute_key[] = '_umb_catch_category';
										} else {
											foreach ( $product_specific_attribute_key as $key => $product_key ) {
												if ( $product_key == $field_id ) {
													$check = true;
													break;
												}
											}
											if ( false == $check ) {
												$product_specific_attribute_key[] = $field_id;
											}
										}
										update_option( 'ced_catch_product_specific_attribute_key', $product_specific_attribute_key );

										if ( 'LIST' == $value['type'] ) {
											echo '<tr class="form-field _umb_id_type_field ">';
											$parameters = $value['code'];

											$get_attribute_listvalues = $productFieldInstance->ced_catch_get_attribute_list_option( $parameters, $shop_id );
											$get_list_option          = $get_attribute_listvalues['values_lists'];
											$attribute_value_list     = $value['values_list'];
											$valueForDropdown         = $productFieldInstance->ced_catch_get_attribute_list_option_values( $get_list_option, $attribute_value_list );

												$productFieldInstance->renderDropdownHTML(
													$field_id,
													$attributeNameToRender,
													$valueForDropdown,
													$categoryID,
													$productID,
													$marketPlace,
													$description,
													$indexToUse,
													array(
														'case' => 'profile',
														'value' => $default,
													)
												);
												$isText = false;
										} elseif ( 'TEXT' == $value['type'] || 'INTEGER' == $value['type'] || 'DECIMAL' == $value['type'] || 'LONG_TEXT' == $value['type'] || 'MULTIPLE' == $value['type'] ) {
											echo '<tr class="form-field _umb_id_type_field ">';

											$productFieldInstance->renderInputTextHTML(
												$field_id,
												$attributeNameToRender,
												$categoryID,
												$productID,
												$marketPlace,
												$description,
												$indexToUse,
												array(
													'case' => 'profile',
													'value' => $default,
												)
											);
										} elseif ( 'MEDIA' == $value['type'] ) {
											continue;
										} else {
											$isText = false;
										}


										echo '<td>';
										$previousSelectedValue = '';
										if ( $isText ) {

											$previousSelectedValue = 'null';
											if ( isset( $data[ 'ced_catch_custom_' . $value['code'] ]['metakey'] ) && 'null' != $data[ 'ced_catch_custom_' . $value['code'] ]['metakey'] ) {
												$previousSelectedValue = $data[ 'ced_catch_custom_' . $value['code'] ]['metakey'];
											}
										} else {

											$previousSelectedValue = 'null';
											if ( isset( $data[ 'ced_catch_custom_' . $value['code'] ]['metakey'] ) && 'null' != $data[ 'ced_catch_custom_' . $value['code'] ]['metakey'] ) {
												$previousSelectedValue = $data[ 'ced_catch_custom_' . $value['code'] ]['metakey'];
											}
										}

										$updatedDropdownHTML = str_replace( '{{*fieldID}}', $value['code'], $selectDropdownHTML );
										$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
										print_r( $updatedDropdownHTML );
										echo '</td>';
										echo '</tr>';
									}
								}
							}
							?>
						</tr>
					</table>
				</div>
			</div>
		</div>
	</div>
	<div class="ced_catch_heading">
		<?php echo esc_html_e( get_catch_instuctions_html( 'OFFER SPECIFIC' ) ); ?>
		<div class="ced_catch_child_element">
			<div class="ced_catch_profile_details_wrapper">
				<div class="ced_catch_profile_details_fields">
					<table>
						<tr>
							<?php
							if ( ! empty( $OfferAttributes ) ) {
								// offer specific custom attribute
								$offer_specific_attribute_key = get_option( 'offer_specific_attribute_key', array() );
								if ( ! empty( $offerfields ) ) {
									foreach ( $offerfields as $value ) {
										$isText      = true;
										$check       = false;
										$description = '';
										$field_id    = trim( $value['fields']['id'], '_' );
										if ( in_array( $value['fields']['id'], $requiredInAnyCase ) ) {
											$attributeNameToRender  = ucfirst( $value['fields']['label'] );
											$attributeNameToRender .= '<span class="ced_catch_wal_required">' . __( ' *', 'woocommerce-catch-integration' ) . '</span>';
										} else {
											$attributeNameToRender = ucfirst( $value['fields']['label'] );
										}
										$default = isset( $data[ 'ced_catch_custom_' . $value['fields']['id'] ]['default'] ) ? $data[ 'ced_catch_custom_' . $value['fields']['id'] ]['default'] : '';

										if ( null != $value['fields']['description'] ) {
											$description = $value['fields']['description'];
										} else {
											$description = $field_id;
										}


										if ( empty( $offer_specific_attribute_key ) ) {
											$offer_specific_attribute_key = array( $field_id );
										} else {
											foreach ( $offer_specific_attribute_key as $key => $offer_key ) {
												if ( $offer_key == $field_id ) {
													$check = true;
												}
											}
											if ( false == $check ) {
												$offer_specific_attribute_key[] = $field_id;
											}
										}
										update_option( 'offer_specific_attribute_key', $offer_specific_attribute_key );

										if ( '_select' == $value['type'] ) {
											echo '<tr class="form-field _umb_id_type_field ">';
											$valueForDropdown = $value['fields']['options'];
											if ( '_umb_id_type' == $value['fields']['id'] ) {
												unset( $valueForDropdown['null'] );
											}
											$valueForDropdown = apply_filters( 'ced_catch_alter_data_to_render_on_profile', $valueForDropdown, $field_id );

											$productFieldInstance->renderDropdownHTML(
												$field_id,
												$attributeNameToRender,
												$valueForDropdown,
												$categoryID,
												$productID,
												$marketPlace,
												$description,
												$indexToUse,
												array(
													'case' => 'profile',
													'value' => $default,
												)
											);
											$isText = false;
										} elseif ( '_text_input' == $value['type'] ) {
											echo '<tr class="form-field _umb_id_type_field ">';
											$productFieldInstance->renderInputTextHTML(
												$field_id,
												$attributeNameToRender,
												$categoryID,
												$productID,
												$marketPlace,
												$description,
												$indexToUse,
												array(
													'case' => 'profile',
													'value' => $default,
												)
											);
										} elseif ( '_hidden' == $value['type'] ) {
											echo '<tr class="form-field _umb_id_type_field ">';
											$productFieldInstance->renderInputTextHTMLhidden(
												$field_id,
												$attributeNameToRender,
												$categoryID,
												$productID,
												$marketPlace,
												$value['fields']['description'],
												$indexToUse,
												array(
													'case' => 'profile',
													'value' => $profileCategoryId,
												)
											);
											$isText = false;
										} else {
											$isText = false;
										}

										echo '<td>';
										if ( $isText ) {
											$previousSelectedValue = 'null';
											if ( isset( $data[ $value['fields']['id'] ]['metakey'] ) && 'null' != $data[ $value['fields']['id'] ]['metakey'] ) {
												$previousSelectedValue = $data[ $value['fields']['id'] ]['metakey'];
											}
											$updatedDropdownHTML = str_replace( '{{*fieldID}}', $value['fields']['id'], $selectDropdownHTML );
											$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
											print_r( $updatedDropdownHTML );
										}
										echo '</td>';
										echo '</tr>';
									}
								}

								$offer_specific_attribute_key = get_option( 'offer_specific_attribute_key', array() );

								// offer attributes
								foreach ( $OfferAttributes as $key => $value ) {
									$isText   = true;
									$check    = false;
									$field_id = trim( $value['code'], '_' );

									if ( empty( $offer_specific_attribute_key ) ) {
										$offer_specific_attribute_key = array( $field_id );
									} else {
										foreach ( $offer_specific_attribute_key as $key => $offer_key ) {
											if ( $offer_key == $field_id ) {
												$check = true;
												// break;
											}
										}
										if ( false == $check ) {
											$offer_specific_attribute_key[] = $field_id;
										}
									}

									update_option( 'offer_specific_attribute_key', $offer_specific_attribute_key );

									$profile_category_id = str_replace( ' ', '_', $profile_category_id );
									$field_id            = trim( $value['code'], '_' );
									$default             = isset( $data[ 'ced_catch_custom_' . $value['code'] ] ) ? $data[ 'ced_catch_custom_' . $value['code'] ] : '';
									$default             = isset( $default['default'] ) ? $default['default'] : '';
									$required            = '';

									if ( null != $value['description'] ) {
										$description = $value['description'];
									} else {
										$description = $value['label'];
									}


									if ( 'LIST' == $value['type'] ) {
										echo '<tr class="form-field _umb_brand_field ">';
										$categoryFileInstance = new Class_Ced_Catch_Category();

										$valueForDropdown = $value['values_list'];
										$required         = 'required';


										$productFieldInstance->renderDropdownHTML(
											$field_id,
											ucfirst( $value['label'] ),
											$valueForDropdown,
											$profile_category_id,
											$productID,
											$marketPlace,
											$description,
											$indexToUse,
											array(
												'case'  => 'profile',
												'value' => $default,
											),
											$required
										);
										$isText = false;
									} elseif ( 'TEXT' == $value['type'] ) {
										echo '<tr class="form-field _umb_brand_field ">';
										if ( $value['required'] ) {
											$required = 'required';
										}
										if ( 'Discount Start Date' == $value['label'] || 'Discount End Date' == $value['label'] || 'Best Before Date' == $value['label'] || 'Expiry Date' == $value['label'] || 'Available Start Date' == $value['label'] || 'Available End Date' == $value['label'] ) {
											$isText = false;
										}

										$productFieldInstance->renderInputTextHTML(
											$field_id,
											ucfirst( $value['label'] ),
											$profile_category_id,
											$productID,
											$marketPlace,
											$description,
											$indexToUse,
											array(
												'case'  => 'profile',
												'value' => $default,
											),
											$required,
											''
										);
									}


									echo '<td>';
									if ( $isText ) {
										$previousSelectedValue = 'null';

										if ( isset( $data[ 'ced_catch_custom_' . $value['code'] ] ) && 'null' != $data[ 'ced_catch_custom_' . $value['code'] ] ) {
											$previousSelectedValue = $data[ 'ced_catch_custom_' . $value['code'] ]['metakey'];
										}

										$updatedDropdownHTML = str_replace( '{{*fieldID}}', $value['code'], $selectDropdownHTML );
										$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
										print_r( $updatedDropdownHTML );
									}
									echo '</td>';
									echo '</tr>';

								}
							}

							?>
						</tr>
					</table>
				</div>
			</div>
		</div>
	</div>
	<div class="ced_catch_heading">
		<?php echo esc_html_e( get_catch_instuctions_html( 'CATEGORY SPECIFIC' ) ); ?>
		<div class="ced_catch_child_element">
			<div class="ced_catch_profile_details_wrapper">
				<div class="ced_catch_profile_details_fields">
					<table>
						<?php
						if ( '' != $profileID && ! empty( $categorySpecifics ) ) {
							foreach ( $categorySpecifics as $key => $value ) {

								$isText              = true;
								$profile_category_id = str_replace( ' ', '_', $profile_category_id );
								$field_id            = trim( $value['code'], '_' );
								$default             = isset( $data[ 'ced_catch_custom_' . $value['code'] ] ) ? $data[ 'ced_catch_custom_' . $value['code'] ] : '';
								$default             = isset( $default['default'] ) ? $default['default'] : '';
								$required            = '';
								if ( $value['required'] ) {
									$required = 'required';
								}

								if ( null != $value['description'] ) {
									$description = $value['description'];
								} else {
									$description = $value['label'];
								}

								echo '<tr class="form-field _umb_brand_field ">';
								if ( 'LIST' == $value['type'] ) {
									$categoryFileInstance = new Class_Ced_Catch_Category();

									$parameters               = $value['code'];
									$get_attribute_listvalues = $productFieldInstance->ced_catch_get_attribute_list_option( $parameters, $shop_id );
									$get_list_option          = $get_attribute_listvalues['values_lists'];
									$attribute_value_list     = $value['values_list'];
									$valueForDropdown         = $productFieldInstance->ced_catch_get_attribute_list_option_values( $get_list_option, $attribute_value_list );

									$productFieldInstance->renderDropdownHTMLForCategorySpecifics(
										$field_id,
										ucfirst( $value['label'] ),
										$valueForDropdown,
										$profile_category_id,
										$productID,
										$marketPlace,
										$description,
										$indexToUse,
										array(
											'case'  => 'profile',
											'value' => $default,
										),
										$required
									);
									$isText = false;
								} elseif ( 'DECIMAL' == $value['type'] || 'INTEGER' == $value['type'] || 'TEXT' == $value['type'] ) {

									$productFieldInstance->renderInputTextHTML(
										$field_id,
										ucfirst( $value['label'] ),
										$profile_category_id,
										$productID,
										$marketPlace,
										$description,
										$indexToUse,
										array(
											'case'  => 'profile',
											'value' => $default,
										),
										$required,
										'',
										$value['type']
									);
								} else {
									$productFieldInstance->renderInputTextHTML(
										$field_id,
										ucfirst( $value['label'] ),
										$profile_category_id,
										$productID,
										$marketPlace,
										$description,
										$indexToUse,
										array(
											'case'  => 'profile',
											'value' => $default,
										),
										$required
									);
								}

								echo '<td>';
								if ( $isText ) {
									$previousSelectedValue = 'null';
									if ( isset( $data[ 'ced_catch_custom_' . $value['code'] ] ) && 'null' != $data[ 'ced_catch_custom_' . $value['code'] ] ) {

										$previousSelectedValue = $data[ 'ced_catch_custom_' . $value['code'] ]['metakey'];
									}
									$updatedDropdownHTML = str_replace( '{{*fieldID}}', $value['code'], $selectDropdownHTML );
									$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
									print_r( $updatedDropdownHTML );
								}
								echo '</td>';
								echo '</tr>';
							}
						}
						?>
					</table>
				</div>
			</div>
		</div>
	</div>
	<div class="ced_catch_heading">
	<?php echo esc_html_e( get_catch_instuctions_html( 'Miscellaneous Field' ) ); ?>
		<div class="ced_catch_child_element">
			<div class="ced_catch_profile_details_wrapper">
				<div class="ced_catch_profile_details_fields">
					<table>
						<?php
						if ( ! empty( $miscellaneousfields ) ) {
							foreach ( $miscellaneousfields as $value ) {
								$isText   = true;
								$check    = false;
								$field_id = trim( $value['fields']['id'], '_' );
								if ( in_array( $value['fields']['id'], $requiredInAnyCase ) ) {
									$attributeNameToRender  = ucfirst( $value['fields']['label'] );
									$attributeNameToRender .= '<span class="ced_catch_wal_required">' . __( '[ Required ]', 'woocommerce-catch-integration' ) . '</span>';
								} else {
									$attributeNameToRender = ucfirst( $value['fields']['label'] );
								}
								$default = isset( $data[ 'ced_catch_custom_' . $value['fields']['id'] ]['default'] ) ? $data[ 'ced_catch_custom_' . $value['fields']['id'] ]['default'] : '';

								if ( '_text_input' == $value['type'] ) {
									echo '<tr class="form-field _umb_id_type_field ">';
									$productFieldInstance->renderInputTextHTML(
										$field_id,
										$attributeNameToRender,
										$categoryID,
										$productID,
										$marketPlace,
										$value['fields']['description'],
										$indexToUse,
										array(
											'case'  => 'profile',
											'value' => $default,
										)
									);
								} else {
									$isText = false;
								}

								echo '<td>';
								$previousSelectedValue = '';
								if ( $isText ) {
									$previousSelectedValue = 'null';
									if ( isset( $data[ 'ced_catch_custom_' . $value['fields']['id'] ]['metakey'] ) && 'null' != $data[ 'ced_catch_custom_' . $value['fields']['id'] ]['metakey'] ) {
										$previousSelectedValue = $data[ 'ced_catch_custom_' . $value['fields']['id'] ]['metakey'];
									}

									$updatedDropdownHTML = str_replace( '{{*fieldID}}', $value['fields']['id'], $selectDropdownHTML );
									$updatedDropdownHTML = str_replace( 'value="' . $previousSelectedValue . '"', 'value="' . $previousSelectedValue . '" selected="selected"', $updatedDropdownHTML );
									print_r( $updatedDropdownHTML );
								}
								echo '</td>';
								echo '</tr>';
							}
						}
						?>
					</table>
				</div>
			</div>
		</div>
	</div>
	<div>
		<button class="ced_catch_custom_button save_profile_button" name="ced_catch_profile_save_button" ><?php esc_attr_e( 'Save Profile', 'woocommerce-catch-integration' ); ?></button>

	</div>
</form>

