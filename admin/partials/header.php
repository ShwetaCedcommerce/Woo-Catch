<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$shop_id = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
update_option( 'ced_catch_shop_id', $shop_id );
global $wpdb;
$tableName = $wpdb->prefix . 'ced_catch_accounts';

$shopDetails = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_catch_accounts WHERE `shop_id`= %d", $shop_id ), 'ARRAY_A' );
if ( isset( $_GET['section'] ) ) {

	$section = sanitize_text_field( $_GET['section'] );
}
if ( isset( $_GET['sub_section'] ) ) {
	$sub_section = sanitize_text_field( $_GET['sub_section'] );
}
update_option( 'ced_catch_active_shop', trim( $shop_id ) );

?>
<div class="ced_catch_loader">
	<img src="<?php echo esc_url( CED_CATCH_URL . 'admin/images/loading.gif' ); ?>" width="50px" height="50px" class="ced_catch_loading_img" >
</div>
<div class="success-admin-notices is-dismissible"></div>
<div class="navigation-wrapper">
	<?php esc_attr( ced_catch_cedcommerce_logo() ); ?>
	<ul class="navigation">
		
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ced_catch&section=settings-view&shop_id=' . $shop_id ) ); ?>" class="
								<?php
								if ( 'settings-view' == $section ) {
									echo 'active'; }
								?>
			"><?php esc_attr_e( 'Settings', 'woocommerce-catch-integration' ); ?></a>
		</li>
		<li>
			<a class="
			<?php
			if ( 'category-mapping-view' == $section ) {
				echo 'active'; }
			?>
			" href="<?php echo esc_url( admin_url( 'admin.php?page=ced_catch&section=category-mapping-view&shop_id=' . $shop_id ) ); ?>"><?php esc_attr_e( 'Category Mapping', 'woocommerce-catch-integration' ); ?></a>
		</li>
		<li>
			<a class="
			<?php
			if ( 'profiles-view' == $section ) {
				echo 'active'; }
			?>
			" href="<?php echo esc_url( admin_url( 'admin.php?page=ced_catch&section=profiles-view&shop_id=' . $shop_id ) ); ?>"><?php esc_attr_e( 'Profile', 'woocommerce-catch-integration' ); ?></a>
		</li>
		<li>
			<a class="
			<?php
			if ( 'products-view' == $section ) {
				echo 'active'; }
			?>
			" href="<?php echo esc_url( admin_url( 'admin.php?page=ced_catch&section=products-view&shop_id=' . $shop_id ) ); ?>"><?php esc_attr_e( 'Products', 'woocommerce-catch-integration' ); ?></a>
		</li>
		<li>
			<a class="
			<?php
			if ( 'import-status-view' == $section ) {
				echo 'active'; }
			?>
			" href="<?php echo esc_url( admin_url( 'admin.php?page=ced_catch&section=import-status-view&shop_id=' . $shop_id ) ); ?>"><?php esc_attr_e( 'Import Status', 'woocommerce-catch-integration' ); ?></a>
		</li>
		
		<li>
			<a class="
			<?php
			if ( 'orders-view' == $section ) {
				echo 'active'; }
			?>
			" href="<?php echo esc_url( admin_url( 'admin.php?page=ced_catch&section=orders-view&shop_id=' . $shop_id ) ); ?>"><?php esc_attr_e( 'Orders', 'woocommerce-catch-integration' ); ?></a>
		</li>
		<li>
			<a class="
			<?php
			if ( 'logs' == $section ) {
				echo 'active'; }
			?>
			" href="<?php echo esc_url( admin_url( 'admin.php?page=ced_catch&section=catch_logs&shop_id=' . $shop_id ) ); ?>"><?php esc_attr_e( 'Logs', 'woocommerce-catch-integration' ); ?></a>
		</li>
	</ul>
	
</div>
