<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              piwebsolution.com
 * @since             1.0.40
 * @package           Add_Coupon_By_Link_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Add coupon by link / URL coupons for Woocommerce
 * Plugin URI:        https://piwebsolution.com
 * Description:       Adding coupons by url, so user can directly get coupon applied when they visit a link
 * Version:           1.0.40
 * Author:            PI Websolution
 * Author URI:        piwebsolution.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       add-coupon-by-link-woocommerce
 * Domain Path:       /languages
 * WC tested up to: 6.7.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if(!is_plugin_active( 'woocommerce/woocommerce.php')){
    function pi_acblw_woo_error_notice() {
        ?>
        <div class="error notice">
            <p><?php _e( 'Please Install and Activate WooCommerce plugin, without that this plugin cant work', 'add-coupon-by-link-woocommerce' ); ?></p>
        </div>
        <?php
    }
    add_action( 'admin_notices', 'pi_acblw_woo_error_notice' );
    return;
}

/**
 * Currently plugin version.
 * Start at version 1.0.40 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ADD_COUPON_BY_LINK_WOOCOMMERCE_VERSION', '1.0.40' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-add-coupon-by-link-woocommerce-activator.php
 */
function activate_add_coupon_by_link_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-add-coupon-by-link-woocommerce-activator.php';
	Add_Coupon_By_Link_Woocommerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-add-coupon-by-link-woocommerce-deactivator.php
 */
function deactivate_add_coupon_by_link_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-add-coupon-by-link-woocommerce-deactivator.php';
	Add_Coupon_By_Link_Woocommerce_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_add_coupon_by_link_woocommerce' );
register_deactivation_hook( __FILE__, 'deactivate_add_coupon_by_link_woocommerce' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-add-coupon-by-link-woocommerce.php';

add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ),  'pisol_acblw_plugin_link' );

function pisol_acblw_plugin_link( $links ) {
    $links = array_merge( array(
        '<a href="' . esc_url( admin_url( '/admin.php?page=pi-acblw-coupon' ) ) . '" style="color:#f00; font-weight:bold;">' . __( 'Settings', 'cancel-order-request-woocommerce' ) . '</a>'
    ), $links );
    return $links;
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.40
 */
function run_add_coupon_by_link_woocommerce() {

	$plugin = new Add_Coupon_By_Link_Woocommerce();
	$plugin->run();

}
run_add_coupon_by_link_woocommerce();
