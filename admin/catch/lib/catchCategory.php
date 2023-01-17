<?php
if ( ! class_exists( 'Class_Ced_Catch_Category' ) ) {

	class Class_Ced_Catch_Category {

		public static $_instance;

		/**
		 * Ced_Catch_Config Instance.
		 *
		 * Ensures only one instance of Ced_Catch_Config is loaded or can be loaded.
		 *
		 * author CedCommerce <plugins@cedcommerce.com>
		 *
		 * @since 1.0.0
		 * @static
		 */
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		public function __construct() {

			$this->loadDependency();
		}

		public function loadDependency() {

			require_once CED_CATCH_DIRPATH . 'admin/catch/lib/catchSendHttpRequest.php';

			$this->catchSendHttpRequestInstance = new Class_Ced_Catch_Send_Http_Request();
		}

		/*
		*
		*function for getting category specific attributes
		*
		*
		*/
		public function ced_catch_valueLists( $shopId = '' ) {
			$action                     = 'values_lists';
			$category_attributes_values = $this->catchSendHttpRequestInstance->sendHttpRequestGet( $action, '', $shopId );
			$category_attributes_values = json_decode( $category_attributes_values, true );
			$folderName                 = CED_CATCH_DIRPATH . 'admin/catch/lib/json/';
			$valueLists                 = $folderName . 'Values_lists.json';
			file_put_contents( $valueLists, json_encode( $category_attributes_values ) );

		}


		public function getRefreshedCatchCategory( $shopId = '' ) {
			if ( '' == $shopId ) {
				return;
			}

			$action     = 'hierarchies';
			$categories = $this->catchSendHttpRequestInstance->sendHttpRequestGet( $action, '', $shopId, '' );
			return $categories;
		}
	}
}

