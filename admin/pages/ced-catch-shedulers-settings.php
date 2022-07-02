<?php
require_once CED_CATCH_DIRPATH . 'admin/partials/products_fields.php';
$productFieldInstance      = CedCatchProductsFields::get_instance();
$inventory_sheduler_option = $productFieldInstance->ced_catch_getInventoryShedulerOption();

$shop_id = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';

$attributes      = wc_get_attribute_taxonomies();
$attr_options    = array();
$added_meta_keys = get_option( 'ced_catch_selected_metakeys', false );
if ( $added_meta_keys && count( $added_meta_keys ) > 0 ) {
	foreach ( $added_meta_keys as $meta_key ) {
		$attr_options[ $meta_key ] = $meta_key;
	}
}
if ( ! empty( $attributes ) ) {
	foreach ( $attributes as $attributes_object ) {
		$attr_options[ 'umb_pattr_' . $attributes_object->attribute_name ] = $attributes_object->attribute_label;
	}
}
$renderDataOnGlobalSettings = get_option( 'ced_catch_global_settings', false );
?>
<div class="ced_catch_heading">
	<div class="ced_catch_render_meta_keys_wrapper ced_catch_global_wrap">
		<div class="ced_catch_parent_element">
			<h2>
				<label class="basic_heading ced_catch_render_meta_keys_toggle"><?php esc_html_e( 'SCHEDULER SETTINGS', 'catch-woocommerce-integration' ); ?></label>
				<span class="dashicons dashicons-arrow-down-alt2 ced_catch_instruction_icon"></span>
			</h2>
		</div>
		<div class="ced_catch_child_element">
			<table class="wp-list-table widefat fixed  ced_catch_global_settings_fields_table">
				<tr>
				<tr>
					<?php
					$ced_catch_remove_spacial_character = get_option( 'ced_catch_remove_spacial_character' . $shop_id, '' );
					?>
					<th>
						<label><?php esc_html_e( 'Remove Special Character From Description', 'woocommerce-catch-integration' ); ?></label>
						<?php ced_catch_tool_tip( 'you can separate character with |.' ); ?>
					</th>
					<td>	
						<input type="text" name="ced_catch_global_settings[ced_catch_remove_spacial_character]" value="<?php echo esc_attr( $ced_catch_remove_spacial_character ); ?>">
					</td>
				</tr>
				<tr>
				<tr>
				<th>
					<label><?php esc_html_e( 'Auto Sync Inventory', 'woocommerce-catch-integration' ); ?></label>
						<?php ced_catch_tool_tip( 'Automatically update price and stock from woocommerce to catch.' ); ?>
				</th>
				<td>
						<?php
						$isScheduled = get_option( 'catch_auto_syncing' . $shop_id, true );
						?>
						<select name="ced_catch_global_settings[ced_catch_inventory_scheduler]">
							<option value="">--Select--</option> 
							<?php
							foreach ( $inventory_sheduler_option['options'] as $key => $value ) {
								if ( $isScheduled == $key ) {
									echo '<option value="' . esc_attr( $key ) . '" selected>' . esc_attr( $value ) . '</option>';
								} else {
									echo '<option value="' . esc_attr( $key ) . '">' . esc_attr( $value ) . '</option>';
								}
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<?php
					$isScheduled = isset( $renderDataOnGlobalSettings[ $shop_id ]['ced_catch_auto_accept_order'] ) ? $renderDataOnGlobalSettings[ $shop_id ]['ced_catch_auto_accept_order'] : '';
					if ( 'on' == $isScheduled ) {
						$isScheduled = 'checked';
					} else {
						$isScheduled = '';
					}
					?>
					<th>
						<label><?php esc_html_e( 'Auto accept order', 'woocommerce-catch-integration' ); ?></label>
						<?php ced_catch_tool_tip( 'Automatically accept catch orders of waiting acceptance state.' ); ?>
					</th>
					<td>

						<label class="switch">
							
						<input type="checkbox" name="ced_catch_global_settings[ced_catch_auto_accept_order]" <?php echo esc_attr( $isScheduled ); ?>>
							<span class="slider round"></span>
						</label>

					</td>
				</tr>
				<tr>
				<?php
					$isScheduled = isset( $renderDataOnGlobalSettings[ $shop_id ]['ced_catch_auto_fetch_order'] ) ? $renderDataOnGlobalSettings[ $shop_id ]['ced_catch_auto_fetch_order'] : '';
				if ( 'on' == $isScheduled ) {
					$isScheduled = 'checked';
				} else {
					$isScheduled = '';
				}
				?>
					<th>
						<label><?php esc_html_e( 'Auto fetch order', 'woocommerce-catch-integration' ); ?></label>
						<?php ced_catch_tool_tip( 'Automatically fetch orders from catch and create in woocommerce on every 6 min.' ); ?>
					</th>
					<td>

						<label class="switch">
							
					<input type="checkbox" name="ced_catch_global_settings[ced_catch_auto_fetch_order]" <?php echo esc_attr( $isScheduled ); ?>>
							<span class="slider round"></span>
						</label>

						
					</td>
				</tr>
				<tr>
				<?php
					$isScheduled = isset( $renderDataOnGlobalSettings[ $shop_id ]['ced_catch_auto_update_shipment'] ) ? $renderDataOnGlobalSettings[ $shop_id ]['ced_catch_auto_update_shipment'] : '';
				if ( 'on' == $isScheduled ) {
					$isScheduled = 'checked';
				} else {
					$isScheduled = '';
				}
				?>
					<th>
						<label><?php esc_html_e( 'Auto update Shipment', 'woocommerce-catch-integration' ); ?></label>
						<?php ced_catch_tool_tip( 'Auto update tracking information on catch if using <a href="https://woocommerce.com/products/shipment-tracking" target="_blank">Shipment Tracking</a> plugin.' ); ?>
					</th>
					<td>
						<label class="switch">
							<input type="checkbox" name="ced_catch_global_settings[ced_catch_auto_update_shipment]" <?php echo esc_attr( $isScheduled ); ?>>
							<span class="slider round"></span>
						</label>
					</td>
				</tr>
				<tr>
					<?php
					$isScheduled = isset( $renderDataOnGlobalSettings[ $shop_id ]['ced_catch_upload_pro_as_a_simple'] ) ? $renderDataOnGlobalSettings[ $shop_id ]['ced_catch_upload_pro_as_a_simple'] : '';
					if ( 'on' == $isScheduled ) {
						$isScheduled = 'checked';
					} else {
						$isScheduled = '';
					}
					?>
					<th>
						<label><?php esc_html_e( 'Upload Product As a Simple', 'woocommerce-catch-integration' ); ?></label>
						<?php ced_catch_tool_tip( 'Upload variable product as a simple product on catch.' ); ?>
					</th>
					<td>

						<label class="switch">
							
					<input type="checkbox" name="ced_catch_global_settings[ced_catch_upload_pro_as_a_simple]" <?php echo esc_attr( $isScheduled ); ?>>
							<span class="slider round"></span>
						</label>
						
						
					</td>
				</tr>
				<tr>
				<th>
					<label><?php esc_html_e( 'Sync Existing Products on Catch on the basis of identifier', 'woocommerce-catch-integration' ); ?></label>
					<?php ced_catch_tool_tip( 'Automatically update price and stock on catch on the basis of identifier(EAN/UPC)' ); ?>
				</th>
				<td>
					<?php
					$sync_existing_products_data     = get_option( 'ced_catch_sync_existing_product_data_' . $shop_id, array() );
					$sync_existing_products_schedule = isset( $sync_existing_products_data[ $shop_id ]['sync_existing_product']['is_enabled'] ) ? $sync_existing_products_data[ $shop_id ]['sync_existing_product']['is_enabled'] : '';
					?>
					<select name="ced_catch_sync_existing_products" class="ced_catch_sync_existing_products">
						<option <?php echo ( '0' == $sync_existing_products_schedule ) ? 'selected' : ''; ?>  value="0"><?php esc_html_e( 'Disabled', 'woocommerce-catch-integration' ); ?></option>
						<option <?php echo ( 'yes' == $sync_existing_products_schedule ) ? 'selected' : ''; ?>  value="yes"><?php esc_html_e( 'Yes', 'woocommerce-catch-integration' ); ?></option>

					</select>
				</td>
			</tr>
			<?php
			$style = 'none';
			if ( 'yes' == $sync_existing_products_schedule ) {
				$style = 'contents';
			}
			?>
			<tr class="ced_catch_auto_sync_existing_products" style="display: <?php echo esc_attr( $style ); ?>;">
				<th>
					<label><?php esc_html_e( 'Select the identifier type', 'woocommerce-catch-integration' ); ?></label>
				</th>
				<td>
					<?php
					$sync_existing_products_data  = get_option( 'ced_catch_sync_existing_product_data_' . $shop_id, array() );
					$selected_id_type_for_syncing = isset( $sync_existing_products_data[ $shop_id ]['ced_catch_syncing_identifier_type']['default'] ) ? $sync_existing_products_data[ $shop_id ]['ced_catch_syncing_identifier_type']['default'] : '';
					?>
					<select name="ced_catch_syncing_identifier_type">
						<option value="EAN" <?php echo ( 'EAN' == $selected_id_type_for_syncing ) ? 'selected' : ''; ?>>EAN</option>
						<option value="UPC" <?php echo ( 'UPC' == $selected_id_type_for_syncing ) ? 'selected' : ''; ?>>UPC</option>
					</select>
				</td>
				<th>
					<label><?php esc_html_e( 'Select the Metakey or Attribute where identifier is located', 'woocommerce-catch-integration' ); ?></label>
				</th>
				<td>
					<?php
					$sync_existing_products_data = get_option( 'ced_catch_sync_existing_product_data_' . $shop_id, array() );
					$selected_value_for_syncing  = isset( $sync_existing_products_data[ $shop_id ]['sync_existing_product']['metakey'] ) ? $sync_existing_products_data[ $shop_id ]['sync_existing_product']['metakey'] : '';
					?>
					<select name="ced_catch_syncing_identifier">
						<option value=""><?php esc_html_e( '--Select--' ); ?></option>
						<?php
						if ( is_array( $attr_options ) ) {
							foreach ( $attr_options as $attr_key => $attr_name ) {
								if ( trim( $selected_value_for_syncing == $attr_key ) ) {
									$selected = 'selected';
								} else {
									$selected = '';
								}
								?>
								<option value="<?php echo esc_attr( $attr_key ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_attr( $attr_name ); ?></option>
								<?php
							}
						}
						?>
					</select>
				</td>
				</tr>
			</table>
		</div>
	</div>
</div>
