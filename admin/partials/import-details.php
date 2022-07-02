<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$file = CED_CATCH_DIRPATH . 'admin/partials/header.php';
require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchSendHttpRequest.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

$importId       = isset( $_GET['import_id'] ) ? sanitize_text_field( $_GET['import_id'] ) : '';
$shopId         = isset( $_GET['shop_id'] ) ? sanitize_text_field( $_GET['shop_id'] ) : '';
$uploadType     = isset( $_GET['upload_type'] ) ? sanitize_text_field( $_GET['upload_type'] ) : '';
$message        = '';
$wpuploadDir    = wp_upload_dir();
$baseDir        = $wpuploadDir['basedir'];
$uploadDir      = $baseDir . '/cedcommerce/catch/feeds/' . $uploadType;
$nameTime       = time();
$sendRequestObj = new Class_Ced_Catch_Send_Http_Request();

// get import status
$import_status_response = get_option( 'ced_catch_import_status_response' . $importId, array() );
if ( empty( $import_status_response ) || isset( $import_status_response ) && ! empty( $import_status_response ) && isset( $import_status_response['import_status'] ) && 'COMPLETE' != $import_status_response['import_status'] || isset( $import_status_response['status'] ) && 'COMPLETE' != $import_status_response['status'] ) {
	if ( 'Product' == $uploadType ) {
		$ced_catch_action = 'products/imports/' . $importId;
	} else {
		$ced_catch_action = 'offers/imports/' . $importId;
	}

	$response = $sendRequestObj->sendHttpRequestGet( $ced_catch_action, $importId, $shopId, '' );
	$response = json_decode( $response, true );
	update_option( 'ced_catch_import_status_response' . $importId, $response );
}
$response = get_option( 'ced_catch_import_status_response' . $importId, array() );
if ( isset( $response ) && ! empty( $response ) && isset( $response['import_status'] ) && 'COMPLETE' == $response['import_status'] || isset( $response['status'] ) && 'COMPLETE' == $response['status'] ) {
	if ( 'Product' == $uploadType ) {
		$ced_catch_action = 'products/imports/' . $importId . '/error_report';
	} else {
		$ced_catch_action = 'offers/imports/' . $importId . '/error_report';
	}
	$file_error = $uploadDir . '/Feed' . $importId . '.csv';
	if ( isset( $response['has_error_report'] ) && ! empty( $response['has_error_report'] ) && ! file_exists( $file_error ) ) {
		$error_report_response = $sendRequestObj->sendHttpRequestGet( $ced_catch_action, $importId, $shopId, '' );
		if ( ( is_string( $error_report_response ) && ( is_object( json_decode( $error_report_response ) ) || is_array( json_decode( $error_report_response ) ) ) ) ) {
			$error_report_response = json_decode( $error_report_response, true );
		}
		if ( ! isset( $error_report_response['status'] ) ) {
			if ( ! is_dir( $uploadDir ) ) {
				mkdir( $uploadDir, 0777, true );
			}
			$file = $uploadDir . '/Feed' . $importId . '.csv';
			file_put_contents( $file, $error_report_response );
		} else {
			$message = $error_report_response['message'];
		}
	}

	$file_tarns_error = $uploadDir . '/transformation_error_feed' . $importId . '.csv';
	if ( isset( $response['has_transformation_error_report'] ) && ! empty( $response['has_transformation_error_report'] ) && ! file_exists( $file_tarns_error ) ) {
		if ( 'Product' == $uploadType ) {
			$ced_catch_action = 'products/imports/' . $importId . '/transformation_error_report';
		}
		$transformation_error_report = $sendRequestObj->sendHttpRequestGet( $ced_catch_action, $importId, $shopId, '' );

		if ( isset( $transformation_error_report ) && ! empty( $transformation_error_report ) ) {

			if ( ! is_dir( $uploadDir ) ) {
				mkdir( $uploadDir, 0777, true );
			}
			$file = $uploadDir . '/transformation_error_feed' . $importId . '.csv';
			file_put_contents( $file, $transformation_error_report );
		}
	}

	$file_path_product = $uploadDir . '/Integration_Feed' . $importId . '.csv';
	if ( isset( $response['has_error_report'] ) && isset( $response['has_transformation_error_report'] ) && ! file_exists( $file_path_product ) ) {
		if ( 'Product' == $uploadType ) {
			$ced_catch_action = 'products/imports/' . $importId . '/new_product_report';
		}
		$integration_response = $sendRequestObj->sendHttpRequestGet( $ced_catch_action, $importId, $shopId, '' );

		if ( ( is_string( $integration_response ) && ( is_object( json_decode( $integration_response ) ) || is_array( json_decode( $integration_response ) ) ) ) ) {
			$integration_response = json_decode( $integration_response, true );
		}

		if ( ! isset( $integration_response['status'] ) ) {
			if ( ! is_dir( $uploadDir ) ) {
				mkdir( $uploadDir, 0777, true );
			}
			$file = $uploadDir . '/Integration_Feed' . $importId . '.csv';
			file_put_contents( $file, $integration_response );
		}
	}
}

?>
<div class="">
	<h2 class=""><b><?php esc_html_e( 'GENERAL FEED DETAILS', 'woocommerce-catch-integration' ); ?></b></h2>
</div>
<div class="ced_catch_account_configuration_wrapper">	
	<div class="ced_catch_account_configuration_fields">	
		<table class="wp-list-table widefat fixed striped ced_catch_account_configuration_fields_table">
			<tbody>				
				
				<tr>
					<td>
						<label><?php esc_html_e( 'Upload Type', 'woocommerce-catch-integration' ); ?></label>
					</td>
					<td> 
						<label><?php echo esc_attr( $uploadType ); ?></label>
					</td>
				</tr>
				<?php
				$import_status_response = get_option( 'ced_catch_import_status_response' . $importId, true );
				foreach ( $import_status_response as $key => $value ) {
					?>
					<tr>
						<td>
							<label><?php esc_html_e( ucfirst( str_replace( '_', ' ', $key ) ), 'woocommerce-catch-integration' ); ?></label>
						</td>
						<td> 
							<?php
							if ( 'has_error_report' == $key ) {
								if ( isset( $error_report_response ) ) {
									echo '<label>' . esc_attr( $message ) . '</label>';
								}
								$pathoferrorfile = wp_upload_dir()['basedir'] . '/cedcommerce/catch/feeds/' . $uploadType . '/Feed' . $importId . '.csv';
								if ( file_exists( $pathoferrorfile ) ) {
									$path_Feed = wp_upload_dir()['baseurl'] . '/cedcommerce/catch/feeds/' . $uploadType . '/Feed' . $importId . '.csv';
									echo '<a href="' . esc_url( $path_Feed ) . '" target="#">' . esc_attr( __( 'View Error Report' ) ) . '</a>';
								}
							} elseif ( 'has_transformation_error_report' == $key ) {
								$pathoferrorfile = wp_upload_dir()['basedir'] . '/cedcommerce/catch/feeds/' . $uploadType . '/transformation_error_feed' . $importId . '.csv';
								if ( file_exists( $pathoferrorfile ) ) {
									$path_transformation_error_feed = wp_upload_dir()['baseurl'] . '/cedcommerce/catch/feeds/' . $uploadType . '/transformation_error_feed' . $importId . '.csv';
									echo '<a href="' . esc_url( $path_transformation_error_feed ) . '" target="#">' . esc_attr( __( 'View Error Report' ) ) . '</a>';
								}
							} else {
								?>
								<label><?php echo esc_attr( $value ); ?></label>
							<?php } ?>
						</td>
					</tr>	

				<?php } ?>
			</tbody>
		</table>
	</div>
</div> 
<?php
$pathoferrorfile = wp_upload_dir()['basedir'] . '/cedcommerce/catch/feeds/' . $uploadType . '/Integration_Feed' . $importId . '.csv';
if ( file_exists( $pathoferrorfile ) ) {
	$pathIntegration_Feed = wp_upload_dir()['baseurl'] . '/cedcommerce/catch/feeds/' . $uploadType . '/Integration_Feed' . $importId . '.csv';
	echo '<div class="ced_catch_setting_header ">
	<label class="manage_labels">
	<b>' . esc_html( 'Integration Report', 'woocommerce-catch-integration' ) . '</b></label><a href="' . esc_url( $pathIntegration_Feed ) . '" target="#">' . esc_attr( __( 'View Report' ) ) . '</a></div>';
}
?>
