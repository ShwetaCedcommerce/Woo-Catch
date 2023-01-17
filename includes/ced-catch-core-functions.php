<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}


/**
 * Check WooCommmerce active or not.
 *
 * @since 1.0.0
 */
function ced_catch_check_woocommerce_active() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		return true;
	}
	return false;
}
/**
 * This code runs when WooCommerce is not activated,
 *
 * @since 1.0.0
 */
function deactivate_ced_catch_woo_missing() {
	deactivate_plugins( CED_CATCH_PLUGIN_BASENAME );
	add_action( 'admin_notices', 'ced_catch_woo_missing_notice' );
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}

function get_catch_instuctions_html( $label = 'Instructions' ) {
	?>
	<div class="ced_catch_parent_element">
		<h2>
			<label><?php echo esc_html_e( $label, 'catch-woocommerce-integration' ); ?></label>
			<span class="dashicons dashicons-arrow-down-alt2 ced_catch_instruction_icon"></span>
		</h2>
	</div>
	<?php
}

/**
 * Callback function for sending notice if woocommerce is not activated.
 *
 * @since 1.0.0
 */
function ced_catch_woo_missing_notice() {
	// translators: %s: search term !!
	echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html( __( 'Catch Integration For Woocommerce requires WooCommerce to be installed and active. You can download %s from here.', 'catch-woocommerce-integration' ) ), '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>' ) . '</p></div>';
}

function ced_catch_tool_tip( $tip = '' ) {
	print_r( "</br><span class='ced_catch_cedcommerce-tip'>[ $tip ]</span>" );
}


function ced_catch_inactive_shops( $shop_id = '' ) {
	global $wpdb;
	$tableName     = $wpdb->prefix . 'ced_catch_accounts';
	$inActiveShops = $wpdb->get_results( $wpdb->prepare( "SELECT `shop_id` FROM {$wpdb->prefix}ced_catch_accounts WHERE `account_status` = %s", 'inactive' ), 'ARRAY_A' );

	foreach ( $inActiveShops as $key => $value ) {
		if ( $value['shop_id'] == $shop_id ) {
			return true;
		}
	}
}

function ced_catch_render_html( $meta_keys_to_be_displayed = array(), $added_meta_keys = array() ) {
	$html  = '';
	$html .= '<table class="wp-list-table widefat fixed striped">';

	if ( isset( $meta_keys_to_be_displayed ) && is_array( $meta_keys_to_be_displayed ) && ! empty( $meta_keys_to_be_displayed ) ) {
		$total_items  = count( $meta_keys_to_be_displayed );
		$pages        = ceil( $total_items / 10 );
		$current_page = 1;
		$counter      = 0;
		$break_point  = 1;

		foreach ( $meta_keys_to_be_displayed as $meta_key => $meta_data ) {
			$display = 'display : none';
			if ( 0 == $counter ) {
				if ( 1 == $break_point ) {
					$display = 'display : contents';
				}
				$html .= '<tbody style="' . esc_attr( $display ) . '" class="ced_catch_metakey_list_' . $break_point . '  			ced_catch_metakey_body">';
				$html .= '<tr><td colspan="3"><label>CHECK THE METAKEYS OR ATTRIBUTES</label></td>';
				$html .= '<td class="ced_catch_pagination"><span>' . $total_items . ' items</span>';
				$html .= '<button class="button ced_catch_navigation" data-page="1" ' . ( ( 1 == $break_point ) ? 'disabled' : '' ) . ' ><b><<</b></button>';
				$html .= '<button class="button ced_catch_navigation" data-page="' . esc_attr( $break_point - 1 ) . '" ' . ( ( 1 == $break_point ) ? 'disabled' : '' ) . ' ><b><</b></button><span>' . $break_point . ' of ' . $pages;
				$html .= '</span><button class="button ced_catch_navigation" data-page="' . esc_attr( $break_point + 1 ) . '" ' . ( ( $pages == $break_point ) ? 'disabled' : '' ) . ' ><b>></b></button>';
				$html .= '<button class="button ced_catch_navigation" data-page="' . esc_attr( $pages ) . '" ' . ( ( $pages == $break_point ) ? 'disabled' : '' ) . ' ><b>>></b></button>';
				$html .= '</td>';
				$html .= '</tr>';
				$html .= '<tr><td><label>Select</label></td><td><label>Metakey / Attributes</label></td><td colspan="2"><label>Value</label></td>';

			}
			$checked    = ( in_array( $meta_key, $added_meta_keys ) ) ? 'checked=checked' : '';
			$html      .= '<tr>';
			$html      .= "<td><input type='checkbox' class='ced_catch_meta_key' value='" . esc_attr( $meta_key ) . "' " . $checked . '></input></td>';
			$html      .= '<td>' . esc_attr( $meta_key ) . '</td>';
			$meta_value = ! empty( $meta_data[0] ) ? $meta_data[0] : '';
			$html      .= '<td colspan="2">' . esc_attr( $meta_value ) . '</td>';
			$html      .= '</tr>';
			++$counter;
			if ( 10 == $counter ) {
				$counter = 0;
				++$break_point;
				$html .= '<tr><td colsapn="4"><a href="" class="ced_catch_custom_button button button-primary">Save</a></td></tr>';
				$html .= '</tbody>';
			}
		}
	} else {
		$html .= '<tr><td colspan="4" class="catch-error">No data found. Please search the metakeys.</td></tr>';
	}
	$html .= '</table>';
	return $html;
}

function ced_catch_cedcommerce_logo() {
	?>
	<img src="<?php echo esc_url( CED_CATCH_URL . 'admin/images/ced-logo.png' ); ?> ">
	<?php
}
