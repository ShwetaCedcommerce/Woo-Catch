<?php
/**
 * Wordpress-plugin
 * Plugin Name:       Catch Integration for WooCommerce
 * Plugin URI:        https://cedcommerce.com
 * Description:       Catch Integration for WooCommerce allows merchants to list their products on Catch marketplace and manage the orders from the WooCommerce store.
 * Version:           1.0.5
 * Author:            CedCommerce
 * Author URI:        https://cedcommerce.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:      catch-woocommerce-integration
 * Domain Path:       /languages
 *
 * WC requires at least: 3.0
 * WC tested up to: 6.3.1
 * Woo: 8347177:6f2cc472bf22ea45762bbda89d24c778
 *
 * @package  Woocommmerce_Catch_Integration
 * @version  1.0.0
 * @link     https://cedcommerce.com
 * @since    1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WOOCOMMERCE_CATCH_INTEGRATION_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woocommerce-catch-integration-activator.php
 */
function activate_woocommerce_catch_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-catch-integration-activator.php';
	Woocommerce_Catch_Integration_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woocommerce-catch-integration-deactivator.php
 */
function deactivate_woocommerce_catch_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-catch-integration-deactivator.php';
	Woocommerce_Catch_Integration_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woocommerce_catch_integration' );
register_deactivation_hook( __FILE__, 'deactivate_woocommerce_catch_integration' );

/* DEFINE CONSTANTS */
define( 'CED_CATCH_LOG_DIRECTORY', wp_upload_dir()['basedir'] . '/ced_catch_log_directory' );
define( 'CED_CATCH_VERSION', '1.0.0' );
define( 'CED_CATCH_PREFIX', 'ced_catch' );
define( 'CED_CATCH_DIRPATH', plugin_dir_path( __FILE__ ) );
define( 'CED_CATCH_URL', plugin_dir_url( __FILE__ ) );
define( 'CED_CATCH_ABSPATH', untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) ) );
define( 'CED_CATCH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-catch-integration.php';

/**
* This file includes core functions to be used globally in plugin.
* author CedCommerce <plugins@cedcommerce.com>
*
* @link  http://www.cedcommerce.com/
*/
require_once plugin_dir_path( __FILE__ ) . 'includes/ced-catch-core-functions.php';

/**
 * This file includes core functions to be used globally in plugin.
 * author CedCommerce <plugins@cedcommerce.com>
 *
 * @link  http://www.cedcommerce.com/
 */
// require_once plugin_dir_path(__FILE__).'includes/ced-catch-core-functions.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woocommerce_catch_integration() {

	$plugin = new Woocommerce_Catch_Integration();
	$plugin->run();

}



/**
 * Runs only when the plugin is activated.
 *
 * @since 1.0.0
 */
function ced_admin_notice_example_activation_hook_ced_catch() {

	/* Create transient data */
	set_transient( 'ced-admin-notice', true, 5 );
}

/*Admin admin notice */

 add_action( 'admin_notices', 'ced_catch_admin_notice_activation' );

/**
 * Admin Notice on Activation.
 *
 * @since 0.1.0
 */


function ced_catch_admin_notice_activation() {

	/* Check transient, if available display notice */
	if ( get_transient( 'ced-admin-notice' ) ) {?>
		<div class="updated notice is-dismissible">
		  <p>Welcome to WooCommerce Catch Integration. Start listing, syncing, managing, & automating your WooCommerce and Catch store to boost sales.</p>
		  <a href="admin.php?page=ced_catch" class ="ced_configuration_plugin_main">Connect to Catch</a>
		</div>
		<?php
		/* Delete transient, only display this notice once. */
		delete_transient( 'ced-admin-notice' );
	}
}



/**
 * Check WooCommerce is Installed and Active.
 *
 * @since 1.0.0
 */
if ( ced_catch_check_woocommerce_active() ) {
	run_woocommerce_catch_integration();
	register_activation_hook( __FILE__, 'ced_admin_notice_example_activation_hook_ced_catch' );
	add_action( 'admin_notices', 'ced_catch_admin_notice_activation' );
} else {
	add_action( 'admin_init', 'deactivate_ced_catch_woo_missing' );
}
