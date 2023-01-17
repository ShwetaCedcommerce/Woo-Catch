<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$file = CED_CATCH_DIRPATH . 'admin/partials/header.php';
require_once CED_CATCH_DIRPATH . 'admin/partials/products_fields.php';
require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchSendHttpRequest.php';

if ( file_exists( $file ) ) {
	require_once $file;
}

if ( isset( $_POST['global_settings'] ) ) {
	$ced_catch_setting_nonce = isset( $_POST['ced_catch_setting_nonce'] ) ? sanitize_text_field( $_POST['ced_catch_setting_nonce'] ) : '';
	if ( wp_verify_nonce( $ced_catch_setting_nonce, 'ced_catch_setting_nonce' ) ) {
		$settings             = array();
		$settings             = get_option( 'ced_catch_global_settings', array() );
		$settings[ $shop_id ] = isset( $_POST['ced_catch_global_settings'] ) ? array_map( 'sanitize_text_field', $_POST['ced_catch_global_settings'] ) : array();
		update_option( 'ced_catch_global_settings', $settings );

		$metakeys = isset( $_POST['ced_catch_global_settings']['ced_catch_metakeys'] ) ? array_map( 'sanitize_text_field', $_POST['ced_catch_global_settings']['ced_catch_metakeys'] ) : array();

		if ( isset( $_POST['ced_catch_global_settings']['ced_catch_inventory_scheduler'] ) ) {
			$inventory_sheduler = sanitize_text_field( $_POST['ced_catch_global_settings']['ced_catch_inventory_scheduler'] );
			if ( ! empty( $inventory_sheduler ) ) {
				update_option( 'catch_auto_syncing' . $shop_id, $inventory_sheduler );
				wp_clear_scheduled_hook( 'ced_catch_inventory_scheduler_job_' . $shop_id );
				wp_schedule_event( time(), $inventory_sheduler, 'ced_catch_inventory_scheduler_job_' . $shop_id );
			} else {
				wp_clear_scheduled_hook( 'ced_catch_inventory_scheduler_job_' . $shop_id );
				update_option( 'catch_auto_syncing' . $shop_id, 'off' );
			}
		}

		if ( isset( $_POST['ced_catch_global_settings']['ced_catch_auto_accept_order'] ) ) {
			update_option( 'ced_catch_auto_accept_order' . $shop_id, 'on' );
		} else {
			delete_option( 'ced_catch_auto_accept_order' . $shop_id );
		}

		if ( isset( $_POST['ced_catch_global_settings']['ced_catch_auto_update_shipment'] ) ) {
			$ced_catch_auto_update_shipment = sanitize_text_field( $_POST['ced_catch_global_settings']['ced_catch_auto_update_shipment'] );
			if ( ! empty( $ced_catch_auto_update_shipment ) ) {
				update_option( 'ced_catch_auto_update_shipment_' . $shop_id, $ced_catch_auto_update_shipment );
				wp_clear_scheduled_hook( 'ced_catch_auto_update_shipment_' . $shop_id );
				wp_schedule_event( time(), 'ced_catch_30min', 'ced_catch_auto_update_shipment_' . $shop_id );
			} else {
				wp_clear_scheduled_hook( 'ced_catch_auto_update_shipment_' . $shop_id );
				update_option( 'ced_catch_auto_update_shipment_' . $shop_id, 'off' );
			}
		}


		$ced_catch_remove_sentence_from_description = isset( $_POST['ced_catch_global_settings']['ced_catch_remove_sentence_from_description'] ) ? sanitize_text_field( $_POST['ced_catch_global_settings']['ced_catch_remove_sentence_from_description'] ) : '';
		if ( ! empty( $ced_catch_remove_sentence_from_description ) ) {
			update_option( 'ced_catch_remove_sentence_from_description_' . $shop_id, $ced_catch_remove_sentence_from_description );
		} else {
			delete_option( 'ced_catch_remove_sentence_from_description_' . $shop_id );
		}

		$ced_catch_remove_spacial_character = isset( $_POST['ced_catch_global_settings']['ced_catch_remove_spacial_character'] ) ? sanitize_text_field( $_POST['ced_catch_global_settings']['ced_catch_remove_spacial_character'] ) : '';
		if ( ! empty( $ced_catch_remove_spacial_character ) ) {
			update_option( 'ced_catch_remove_spacial_character' . $shop_id, $ced_catch_remove_spacial_character );
		} else {
			delete_option( 'ced_catch_remove_spacial_character' . $shop_id );
		}

		if ( isset( $_POST['ced_catch_status'] ) ) {
			$catch_status = array_map( 'sanitize_text_field', $_POST['ced_catch_status'] );
			update_option( 'ced_fetch_order_by_catch_status', $catch_status );
		}

		$sync_existing_products = isset( $_POST['ced_catch_sync_existing_products'] ) ? sanitize_text_field( wp_unslash( $_POST['ced_catch_sync_existing_products'] ) ) : '';
		if ( ! empty( $sync_existing_products ) ) {
			$syncing_identifier                = isset( $_POST['ced_catch_syncing_identifier'] ) ? sanitize_text_field( $_POST['ced_catch_syncing_identifier'] ) : '';
			$ced_catch_syncing_identifier_type = isset( $_POST['ced_catch_syncing_identifier_type'] ) ? sanitize_text_field( $_POST['ced_catch_syncing_identifier_type'] ) : '';
			if ( ! empty( $syncing_identifier ) ) {
				$ced_catch_sync_existing_product = get_option( 'ced_catch_sync_existing_product_data_' . $shop_id, array() );
				$ced_catch_sync_existing_product[ $shop_id ]['sync_existing_product']['default']             = null;
				$ced_catch_sync_existing_product[ $shop_id ]['sync_existing_product']['metakey']             = $syncing_identifier;
				$ced_catch_sync_existing_product[ $shop_id ]['sync_existing_product']['is_enabled']          = $sync_existing_products;
				$ced_catch_sync_existing_product[ $shop_id ]['ced_catch_syncing_identifier_type']['default'] = $ced_catch_syncing_identifier_type;
				update_option( 'ced_catch_sync_existing_product_data_' . $shop_id, $ced_catch_sync_existing_product );
				update_option( 'ced_catch_sync_existing_products_' . $shop_id, 'on' );
			} else {
				delete_option( 'ced_catch_sync_existing_product_data_' . $shop_id );
				delete_option( 'ced_catch_sync_existing_products_' . $shop_id );
			}
		} else {
			delete_option( 'ced_catch_sync_existing_product_data_' . $shop_id );
			delete_option( 'ced_catch_sync_existing_products_' . $shop_id );
		}

		echo ' <div class="notice notice-success is-dismissible">
					        <p>' . esc_attr__( 'Setting Saved Successfully!', 'sample-text-domain' ) . '</p>
					    </div>';
	}
}

?>
<div class="admin_notice"></div>
<div class="ced_catch_heading">
	<div class="ced_catch_render_meta_keys_wrapper ced_catch_global_wrap">
		<div class="ced_catch_parent_element">
			<h2>
				<label class="basic_heading ced_catch_render_meta_keys_toggle"><?php esc_html_e( 'INSTRUCTION', 'catch-woocommerce-integration' ); ?></label>
				<span class="dashicons dashicons-arrow-down-alt2 ced_catch_instruction_icon"></span>
			</h2>
		</div>
		<div class="ced_catch_child_element default_modal">
			<ul type="disc">
				<li><?php echo esc_html_e( 'In this section all the configuration related to product and order sync are provided.' ); ?></li>
				<li><?php echo esc_html_e( 'The Metakeys and Attributes List section will help you to choose the required metakey or attribute on which the product information is stored.These metakeys or attributes will furthur be used in Product Export Settings for listing products on catch from woocommerce.' ); ?></li>
				<li>
				<?php
				echo esc_html_e(
					'For selecting the required metakey or attribute expand the Metakeys and Attributes List section enter the product name/keywords and list will be displayed under that . Select the metakey or attribute as per requirement and save settings.
				'
				);
				?>
				</li>
				<li><?php echo esc_html_e( 'Configure the order related settings in Order configuration.' ); ?></li>
				<li>
				<?php
				echo esc_html_e(
					'To automate the process related to inventory , order , enable the features as per requirement in Schedulers.
				'
				);
				?>
				</li>
			</ul>
		</div>
	</div>
</div>
<?php require_once CED_CATCH_DIRPATH . 'admin/partials/ced-catch-metakeys-template.php'; ?>
<form method="post" action="">
		<div>
			<input type="hidden" id="ced_catch_setting_nonce" name="ced_catch_setting_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ced_catch_setting_nonce' ) ); ?>"/>
			<?php do_action( 'ced_catch_render_product_settings' ); ?>
			<?php do_action( 'ced_catch_render_order_settings' ); ?>
			<?php do_action( 'ced_catch_render_shedulers_settings' ); ?>
		</div>
		<div align="" class="ced-button-wrapper">
			<button id=""  name="global_settings" class="button-primary" ><?php esc_html_e( 'Save', 'woocommerce-catch-integration' ); ?></button>
		</div>
</form>

