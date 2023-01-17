<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$file = CED_CATCH_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}
$shop_id     = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'product';
?>

<div class="ced_catch_global_field_wrapper">
	<div class="ced_catch_global_field_content">
		<div class="ced_catch_global_field_header">
			<ul class="ced_catch_logs">
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ced_catch&section=catch_logs&shop_id=' . $shop_id ) ); ?>" id="product" class="
									<?php
									if ( 'product' == $current_tab ) {
										echo 'active'; }
									?>
				"><?php esc_html_e( 'Product Log', 'catch-woocommerce-integration' ); ?></a>|
			</li>
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ced_catch&section=catch_logs&tab=order&shop_id=' . $shop_id ) ); ?>" id="order-specific" class="
									<?php
									if ( 'order' == $current_tab ) {
										echo 'active'; }
									?>
				"><?php esc_html_e( 'Order Log', 'catch-woocommerce-integration' ); ?></a> |
			</li>
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ced_catch&section=catch_logs&tab=shipment&shop_id=' . $shop_id ) ); ?>" id="sync-specific" class="
									<?php
									if ( 'shipment' == $current_tab ) {
										echo 'active'; }
									?>
				"><?php esc_html_e( 'Shipment Log', 'catch-woocommerce-integration' ); ?></a>
			</li>
		</ul>
		</div>
	</div>
	<div>
		<div class="ced_catch_global_product_field_wrapper">
			<?php
				require_once CED_CATCH_DIRPATH . 'admin/pages/ced-catch-product_log.php';
			?>
		</div>
	
	</div>
</div>
