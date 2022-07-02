<?php

require_once CED_CATCH_DIRPATH . 'admin/partials/products_fields.php';
require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchCategory.php';
$productFieldInstance = CedCatchProductsFields::get_instance();

$shop_id = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
if ( isset( $_POST['global_settings'] ) ) {
	if ( ! isset( $_POST['global_settings_submit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['global_settings_submit'] ) ), 'global_settings' ) ) {
		return;
	}
	$ced_catch_global_profile_settings = array();
	$is_active                         = isset( $_POST['profile_status'] ) ? 'Active' : 'Inactive';
	$marketplace_name                  = isset( $_POST['marketplaceName'] ) ? sanitize_text_field( wp_unslash( $_POST['marketplaceName'] ) ) : 'catch';
	$ced_catch_global_profile_settings = get_option( 'ced_catch_global_profile_settings', array() );
	$offer_settings_information        = array();

	if ( isset( $_POST['ced_catch_required_common'] ) ) {
		$post_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );

		foreach ( ( $post_array['ced_catch_required_common'] ) as $key ) {

			$array_to_save = array();
			isset( $post_array[ $key ][0] ) ? $array_to_save['default'] = $post_array[ $key ][0] : $array_to_save['default'] = '';

			if ( '_umb_' . $marketplace_name . '_subcategory' == $key ) {
				isset( $post_array[ $key ] ) ? $array_to_save['default'] = $post_array[ $key ] : $array_to_save['default'] = '';
			}

			isset( $post_array[ $key . '_attibuteMeta' ] ) ? $array_to_save['metakey'] = $post_array[ $key . '_attibuteMeta' ] : $array_to_save['metakey'] = 'null';
			$offer_settings_information[ $key ]                                        = $array_to_save;
		}
	}
	$ced_catch_global_profile_settings = json_encode( $offer_settings_information );
	update_option( 'ced_catch_global_profile_settings', $ced_catch_global_profile_settings );
}
$ced_catch_global_data = get_option( 'ced_catch_global_profile_settings', array() );
if ( ! empty( $ced_catch_global_data ) ) {
	$ced_catch_global_data = $ced_catch_global_data;
	$data                  = json_decode( $ced_catch_global_data, true );
}

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
$fields               = $productFieldInstance->ced_catch_get_custom_products_fields( $shop_id );
$fields               = json_decode( $fields, true );
$offerfields          = $productFieldInstance->ced_catch_get_custom_offers_fields();
$miscellaneousfields  = $productFieldInstance->ced_catch_get_custom_miscellaneous_fields();

if ( ! empty( $miscellaneousfields ) ) {
	foreach ( $miscellaneousfields as $key => $value ) {
		$attribute_miscellaneousdata[] = array(
			'code'  => $value['fields']['id'],
			'label' => $value['fields']['label'],
		);
	}
	update_option( 'ced_catch_miscellaneousattributes', $attribute_miscellaneousdata );
}
if ( ! empty( $OfferAttributes ) ) {
	foreach ( $OfferAttributes as $key => $value ) {
		$attribute_offerdata[] = array(
			'code'  => $value['code'],
			'label' => $value['label'],
		);
	}
	update_option( 'ced_catch_category_offerattributes', $attribute_offerdata );
}
$product_specific_attribute_key = '';
?>
<div class="ced_catch_heading">
	<div class="ced_catch_render_meta_keys_wrapper ced_catch_global_wrap">
		<div class="ced_catch_parent_element">
			<h2>
				<label class="basic_heading ced_catch_render_meta_keys_toggle"><?php esc_html_e( 'Product Export Settings', 'catch-woocommerce-integration' ); ?></label>
				<span class="dashicons dashicons-arrow-down-alt2 ced_catch_instruction_icon"></span>
			</h2>
		</div>
		<div class="ced_catch_child_element">
			<div class="ced_catch_profile_details_wrapper">
				<div class="ced_catch_profile_details_fields">
				<?php wp_nonce_field( 'global_settings', 'global_settings_submit' ); ?>
					<table>
						<tbody>	
						<tr>

								<th  class="ced_catch_profile_heading ced_catch_settings_heading">
									<label class="basic_heading"><?php esc_attr_e( 'PRODUCT SPECIFIC', 'woocommerce-catch-integration' ); ?></label>
								</th>

							</tr>	
							<tr>
								<?php
								$requiredInAnyCase = array( '_umb_id_type', '_umb_id_val', '_umb_brand' );
								$requiredAnyCase   = '';
								global $global_CED_catch_Render_Attributes;
								$marketPlace                    = 'ced_catch_required_common';
								$productID                      = 0;
								$categoryID                     = '';
								$indexToUse                     = 0;
								$selectDropdownHTML             = $selectDropdownHTML;
								$description                    = '';
								$product_specific_attribute_key = get_option( 'ced_catch_product_specific_attribute_key', array() );

								foreach ( $fields as $attributes_field ) {
									foreach ( $attributes_field as $value ) {
										if ( 'category' == $value['code'] ) {
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
														'case'  => 'profile',
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
							<tr>
								<?php
								if ( ! empty( $OfferAttributes ) ) {
									?>
									<th  class="ced_catch_profile_heading ced_catch_settings_heading">
										<label class="basic_heading"><?php esc_attr_e( 'OFFER SPECIFIC', 'woocommerce-catch-integration' ); ?></label>
									</th>

								</tr>
									<?php

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
														'case'  => 'profile',
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
														'case'  => 'profile',
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
														'case'  => 'profile',
														'value' => '',
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
												}
											}
											if ( false == $check ) {
												$offer_specific_attribute_key[] = $field_id;
											}
										}

										update_option( 'offer_specific_attribute_key', $offer_specific_attribute_key );
										$field_id = trim( $value['code'], '_' );
										$default  = isset( $data[ 'ced_catch_custom_' . $value['code'] ] ) ? $data[ 'ced_catch_custom_' . $value['code'] ] : '';
										$default  = isset( $default['default'] ) ? $default['default'] : '';
										$required = '';

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
												'',
												$productID,
												$marketPlace,
												$description,
												$indexToUse,
												array(
													'case' => 'profile',
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
												'',
												$productID,
												$marketPlace,
												$description,
												$indexToUse,
												array(
													'case' => 'profile',
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
								<tr>
									<th  class="ced_catch_profile_heading ced_catch_settings_heading">
										<label class="basic_heading"><?php esc_attr_e( 'Miscellaneous Field', 'woocommerce-catch-integration' ); ?></label>
									</th>

									</tr>
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
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
</div>
