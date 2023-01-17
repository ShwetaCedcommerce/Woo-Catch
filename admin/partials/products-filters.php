<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * FilterClass.
 *
 * @since 1.0.0
 */
class FilterClass {

	/**
	 * Function- filter_by_category.
	 * Used to Apply Filter on Product Page
	 *
	 * @since 1.0.0
	 */
	public function ced_catch_filters_on_products( $_products, $nonce ) {
		if ( wp_verify_nonce( $nonce, 'nonce_verification_filter' ) ) {
			if ( ( ! empty( $_POST['status_sorting'] ) && isset( $_POST['status_sorting'] ) ) || ( ! empty( $_POST['pro_cat_sorting'] ) && isset( $_POST['pro_cat_sorting'] ) ) || ( ! empty( $_POST['pro_type_sorting'] ) && isset( $_POST['pro_type_sorting'] ) ) || ( ! empty( $_POST['pro_stock_sorting'] ) && isset( $_POST['pro_stock_sorting'] ) ) || ( ! empty( $_POST['pro_tag_sorting'] ) && isset( $_POST['pro_tag_sorting'] ) ) || ( ! empty( $_POST['pro_post_status'] ) && isset( $_POST['pro_post_status'] ) ) || ( ! empty( $_POST['product_synced_status'] ) && isset( $_POST['product_synced_status'] ) ) ) {
					$status_sorting        = isset( $_POST['status_sorting'] ) ? sanitize_text_field( $_POST['status_sorting'] ) : '';
					$pro_cat_sorting       = isset( $_POST['pro_cat_sorting'] ) ? sanitize_text_field( $_POST['pro_cat_sorting'] ) : '';
					$pro_type_sorting      = isset( $_POST['pro_type_sorting'] ) ? sanitize_text_field( $_POST['pro_type_sorting'] ) : '';
					$product_synced_status = isset( $_POST['product_synced_status'] ) ? sanitize_text_field( $_POST['product_synced_status'] ) : '';

					$pro_stock_sorting = isset( $_POST['pro_stock_sorting'] ) ? sanitize_text_field( $_POST['pro_stock_sorting'] ) : '';
					$pro_tag_sorting   = isset( $_POST['pro_tag_sorting'] ) ? sanitize_text_field( $_POST['pro_tag_sorting'] ) : '';
					$pro_post_status   = isset( $_POST['pro_post_status'] ) ? sanitize_text_field( $_POST['pro_post_status'] ) : '';
					$current_url       = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( $_SERVER['REQUEST_URI'] ) : '';
					wp_redirect( $current_url . '&status_sorting=' . $status_sorting . '&pro_cat_sorting=' . $pro_cat_sorting . '&pro_type_sorting=' . $pro_type_sorting . '&pro_stock_sorting=' . $pro_stock_sorting . '&pro_tag_sorting=' . $pro_tag_sorting . '&pro_post_status=' . $pro_post_status . '&product_synced_status=' . $product_synced_status );
			} else {
				$shop_id = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
				$url     = admin_url( 'admin.php?page=ced_catch&section=products-view&shop_id=' . $shop_id );
				wp_redirect( $url );
			}
		}
	}//end ced_catch_filters_on_products()


	public function productSearch_box( $_products, $valueTobeSearched, $nonce ) {
		if ( wp_verify_nonce( $nonce, 'nonce_verification_filter' ) ) {
			if ( isset( $_POST['s'] ) && ! empty( $_POST['s'] ) ) {
				$current_url = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( $_SERVER['REQUEST_URI'] ) : '';
				$searchdata  = isset( $_POST['s'] ) ? sanitize_text_field( $_POST['s'] ) : '';
				$searchdata  = str_replace( ' ', ',', $searchdata );
				$shop_id     = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
				wp_redirect( $current_url . '&searchBy=' . $searchdata . '&shop_id=' . $shop_id );
			} else {
				$shop_id = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
				$url     = admin_url( 'admin.php?page=ced_catch&section=products-view&shop_id=' . $shop_id );
				wp_redirect( $url );
			}
		}
	}
}//end class



