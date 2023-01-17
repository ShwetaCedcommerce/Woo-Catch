<?php
if ( ! class_exists( 'Ced_Catch_Config' ) ) {
	class Ced_Catch_Config {

		public $endpointUrl;
		public $partnerId;

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

		/**
		 * Constructor
		 */
		public function __construct() {
			 $operationMode = get_option( 'ced_catch_operation_mode', '' );
			if ( 'production' == $operationMode ) {
				$this->endpointUrl = 'https://marketplace.catch.com.au/api/';
			} else {
				$this->endpointUrl = 'https://catch-dev.mirakl.net/api/';
			}
		}
	}
}
