<?php
$shop_id     = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'product';
if ( isset( $_POST['ced_catch_product_log_submit'] ) ) {
	$nonce_verification_log = isset( $_POST['nonce_verification_log'] ) ? sanitize_text_field( $_POST['nonce_verification_log'] ) : '';
	if ( wp_verify_nonce( $nonce_verification_log, 'nonce_verification_log' ) ) {
		$ced_catch_product_log_date = isset( $_POST[ 'ced_catch_' . $current_tab . '_log_date' ] ) ? sanitize_text_field( $_POST[ 'ced_catch_' . $current_tab . '_log_date' ] ) : '';
		update_option( 'ced_catch_' . $current_tab . '_log_date' . $shop_id, $ced_catch_product_log_date );
	}
}

$upload         = wp_upload_dir();
$upload_dir     = $upload['basedir'];
$upload_dir     = $upload_dir . '/cedcommerce/catch/logs/' . $current_tab;
$ced_catch_date = get_option( 'ced_catch_' . $current_tab . '_log_date' . $shop_id, '' );


if ( empty( $ced_catch_date ) ) {
	$date = gmdate( 'j.n.Y' );
} else {
	$date = gmdate( 'j.n.Y', strtotime( $ced_catch_date ) );
	$date = ltrim( $date, 0 );
}
$ced_catch_product_log = array();
$filename              = $upload_dir . '/' . $date . '.log';
if ( file_exists( $filename ) ) {
	$content = file_get_contents( $filename );
	if ( ! empty( $content ) ) {
		$ced_catch_product_log = explode( 'Date', $content );
	}
}
$ced_catch_log_data = '';

?>
<form method="post" action="">
	<input type="hidden" id="nonce_verification_log" name="nonce_verification_log" value="<?php echo esc_attr( wp_create_nonce( 'nonce_verification_log' ) ); ?>"/>	
	<input type="date" name="<?php echo esc_attr( 'ced_catch_' . $current_tab . '_log_date' ); ?>" value="<?php esc_html_e( $ced_catch_date ); ?>">
	<button id=""  name="ced_catch_product_log_submit" class="button-primary" ><?php esc_html_e( 'Submit', 'woocommerce-catch-integration' ); ?></button>
</form>
<div class="ced_catch_heading">
	<div class="ced_catch_render_meta_keys_wrapper ced_catch_global_wrap">
		<div class="ced_catch_parent_element">
			<?php
			if ( ! empty( $ced_catch_product_log ) && is_array( $ced_catch_product_log ) ) {
				foreach ( $ced_catch_product_log as $key => $value ) {
					if ( ! empty( $value ) ) {
						$ced_catch_log_data .= '<p>Date ' . $value . '</p>';
					}
				}
			} else {
				 $ced_catch_log_data .= '<p>Logs Not Found</p>';
			}
			print_r( $ced_catch_log_data );
			?>
		</div>
	</div>
</div>
