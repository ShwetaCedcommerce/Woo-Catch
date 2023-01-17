<?php
$shop_id                    = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
$renderDataOnGlobalSettings = get_option( 'ced_catch_global_settings', false );
?>
<div class="ced_catch_heading">
	<div class="ced_catch_render_meta_keys_wrapper ced_catch_global_wrap">
		<div class="ced_catch_parent_element">
			<h2>
				<label class="basic_heading ced_catch_render_meta_keys_toggle"><?php esc_html_e( 'ORDER CONFIGURATION', 'catch-woocommerce-integration' ); ?></label>
				<span class="dashicons dashicons-arrow-down-alt2 ced_catch_instruction_icon"></span>
			</h2>
		</div>
		<div class="ced_catch_child_element">
			<table class="wp-list-table widefat fixed  ced_catch_global_settings_fields_table">
				<tr>
					<th><h4><?php echo esc_html_e( 'Catch order status', 'catch-woocommerce-integration' ); ?></h4></th>
					<th><h4><?php echo esc_html_e( 'Mapped with Woocommerce order status', 'catch-woocommerce-integration' ); ?></h4></th>
				</tr>
				<?php
				$ced_catch_mapped_order_statuses = get_option( 'ced_catch_mapped_order_statuses', array() );
				$catch_order_statuses            = array( 'STAGING', 'WAITING_ACCEPTANCE', 'WAITING_DEBIT', 'WAITING_DEBIT_PAYMENT', 'SHIPPING', 'SHIPPED', 'TO_COLLECT', 'RECEIVED', 'CLOSED', 'REFUSED', 'CANCELED' );
				$ced_woo_order_statuses          = wc_get_order_statuses();

				foreach ( $catch_order_statuses as $catch_status ) {
					echo '<tr>';
					echo '<td>' . esc_attr( ucwords( $catch_status ), 'catch-woocommerce-integration' ) . '</td>';
					echo '<td>';
					echo "<select id='ced_catch_map_order_status' data-catch-order-status='" . esc_attr( $catch_status ) . "'>";
					echo "<option value=''>---Order status not mapped---</option>";
					foreach ( $ced_woo_order_statuses as $woo_status => $woo_label ) {
						echo "<option value='" . esc_attr( $woo_status, 'catch-woocommerce-integration' ) . "' " . ( ( isset( $ced_catch_mapped_order_statuses[ $catch_status ] ) && $woo_status == $ced_catch_mapped_order_statuses[ $catch_status ] ) ? 'selected' : '' ) . '>' . esc_attr( $woo_label, 'catch-woocommerce-integration' ) . '</option>';
					}
					echo '</select>';
					echo '</td>';
					echo '</tr>';
				}
				?>
				<tr>
					<?php
					$isScheduled = isset( $renderDataOnGlobalSettings[ $shop_id ]['ced_catch_set_catchOrderNumber'] ) ? $renderDataOnGlobalSettings[ $shop_id ]['ced_catch_set_catchOrderNumber'] : '';
					if ( 'on' == $isScheduled ) {
						$isScheduled = 'checked';
					} else {
						$isScheduled = '';
					}
					?>
					<th>
						<label><?php esc_attr_e( 'Show catch order number instead of woocommerce order id', 'woocommerce-catch-integration' ); ?></label>
						<?php ced_catch_tool_tip( 'Use catch order number instead of woocommerce order id when creating catch orders in woocommerce.' ); ?>
					</th>
					<td>
						<label class="switch">
							<input type="checkbox" name="ced_catch_global_settings[ced_catch_set_catchOrderNumber]" <?php echo esc_attr( $isScheduled ); ?>>
							<span class="slider round"></span>
						</label>
					</td>
				</tr>
				<tr>
					<th>
						<label><?php echo esc_html_e( 'Fetch catch order by status', 'catch-woocommerce-integration' ); ?></label>
						<?php ced_catch_tool_tip( 'Choose the order status to be fetched from catch.' ); ?>
						
					</th>
					<td>
						<?php
							$selected                        = '';
							$ced_fetch_order_by_catch_status = get_option( 'ced_fetch_order_by_catch_status', array() );
						?>
						<select id='ced_catch_status' name="ced_catch_status[]" multiple>
							<option value=''>--select--</option>
							<?php
							$catch_order_statuses = array( 'STAGING', 'WAITING_ACCEPTANCE', 'WAITING_DEBIT', 'WAITING_DEBIT_PAYMENT', 'SHIPPING', 'SHIPPED', 'TO_COLLECT', 'RECEIVED', 'CLOSED', 'REFUSED', 'CANCELED' );
							foreach ( $catch_order_statuses as $catch_status ) {
								if ( in_array( $catch_status, $ced_fetch_order_by_catch_status ) ) {
									$selected = 'selected';
								} else {
									$selected = '';
								}
								?>
								<option value="<?php echo esc_attr( $catch_status ); ?>"<?php echo esc_attr( $selected ); ?> ><?php echo esc_attr( $catch_status ); ?></option>
							<?php	} ?>
						</select>
						<td>
						</tr>
					</table>
				</div>
			</div>
		</div>
