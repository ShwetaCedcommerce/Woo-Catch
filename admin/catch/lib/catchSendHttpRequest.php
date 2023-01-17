<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Class_Ced_Catch_Send_Http_Request {

	public $endpointUrl;
	public $secretKey;

	public function __construct() {

		$this->loadDepenedency();
		$this->endpointUrl = $this->ced_catch_configInstance->endpointUrl;
	}

	/** SendHttpRequest
		Sends a HTTP request to the server for this session
		Input:  $requestBody
		Output: The HTTP Response as a String
	 */
	public function sendHttpRequestGet( $action = '', $parameters = '', $shopId = '', $apiKey = '' ) {
		if ( '' == $apiKey ) {
			$apiKey = get_option( 'ced_catch_api_key_' . $shopId, '' );
		}
		$apiUrl = $this->endpointUrl . $action;
		if ( array() != $parameters ) {
			$header = $this->prepareHeaderGet( $parameters, $apiKey, $shopId );
		} else {
			$header = $this->prepareHeaderGet( $parameters, $apiKey, $shopId );
		}
		$connection = curl_init();
		curl_setopt( $connection, CURLOPT_URL, $apiUrl );
		curl_setopt( $connection, CURLOPT_HTTPHEADER, $header );
		curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
		$response = curl_exec( $connection );
		curl_close( $connection );
		return $response;
	}

	public function sendHttpRequestPut( $action = '', $parameters = array(), $shopId = '', $apiKey = '' ) {
		$apiUrl = $this->endpointUrl . $action;

		$header     = $this->prepareHeaderGet( $parameters, $apiKey, $shopId );
		$body       = $parameters;
		$connection = curl_init();
		curl_setopt( $connection, CURLOPT_URL, $apiUrl );
		 curl_setopt( $connection, CURLOPT_CUSTOMREQUEST, 'PUT' );
		curl_setopt( $connection, CURLOPT_HTTPHEADER, $header );
		curl_setopt( $connection, CURLOPT_POSTFIELDS, json_encode( $body ) );
		curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, true );
		curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
		$response = curl_exec( $connection );
		curl_close( $connection );
		return $response;
	}

	public function sendHttpRequest( $action = '', $parameters = array(), $shopId = '', $uploadType = '', $apiKey = '' ) {
		$apiUrl = $this->endpointUrl . $action;

		$header = $this->prepareHeader( $parameters, $apiKey, $shopId );
		$body   = '';
		$cFile  = '';
		if ( isset( $parameters['file'] ) ) {
			if ( function_exists( 'curl_file_create' ) ) {
				$cFile = curl_file_create( $parameters['file'] );
			}
		}
		if ( 'Offer' == $uploadType ) {
			$body = array(
				'file'        => $cFile,
				'import_mode' => 'NORMAL',
			);
		} else {
			$body = array( 'file' => $cFile );
		}
		$connection = curl_init();
		curl_setopt( $connection, CURLOPT_URL, $apiUrl );
		curl_setopt( $connection, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_setopt( $connection, CURLOPT_POSTFIELDS, $body );
		curl_setopt( $connection, CURLOPT_HTTPHEADER, $header );
		curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, true );
		curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
		$response = curl_exec( $connection );
		curl_close( $connection );

		return $response;
	}



	public function prepareHeader( $parmaeters = array(), $apiKey = '', $shopId = '' ) {
		if ( '' == $apiKey ) {
			$apiKey = get_option( 'ced_catch_api_key_' . $shopId, '' );
		}
		$header = array(
			'Content-Type: multipart/form-data',
			'Authorization: ' . $apiKey,
			'Accept: application/json',
		);
		return $header;
	}
	public function prepareHeaderGet( $parmaeters = array(), $apiKey = '', $shopId = '' ) {
		if ( '' == $apiKey ) {
			$apiKey = get_option( 'ced_catch_api_key_' . $shopId, '' );
		}
		$header = array(
			'Content-Type: application/json',
			'Authorization: ' . $apiKey,
			'Accept: application/json',
		);
		return $header;
	}

	public function ParseResponse( $response ) {

		if ( ! empty( $response ) ) {
			$response = json_decode( $response, true );
		}
		return $response;
	}

	/**
	 * Function loadDepenedency
	 *
	 * @name loadDepenedency
	 */
	public function loadDepenedency() {

		if ( is_file( __DIR__ . '/catchConfig.php' ) ) {
			require_once 'catchConfig.php';
			$this->ced_catch_configInstance = Ced_Catch_Config::get_instance();
		}
	}
}
