<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       piwebsolution.com
 * @since      1.0.0
 *
 * @package    Add_Coupon_By_Link_Woocommerce
 * @subpackage Add_Coupon_By_Link_Woocommerce/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Add_Coupon_By_Link_Woocommerce
 * @subpackage Add_Coupon_By_Link_Woocommerce/includes
 * @author     PI Websolution <sales@piwebsolution.com>
 */
class Add_Coupon_By_Link_Woocommerce_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'add-coupon-by-link-woocommerce',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
