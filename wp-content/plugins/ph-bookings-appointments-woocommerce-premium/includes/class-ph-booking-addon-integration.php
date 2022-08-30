<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class WC_Product_phive_booking_addon_integration {


	public function __construct(){

		//add_filter( 'woocommerce_product_addons_show_grand_total', array( $this, 'phive_addon_hide_total' ), 20, 2 );
		// add_filter('ph_bookings_currency_conversion',array($this,'phive_apply_addon_price_display'),10,2);
		add_filter('phive_booking_cost',array($this,'phive_apply_addon_price'),10,2);
		// add_filter( 'woocommerce_product_addon_cart_item_data', array( $this, 'woocommerce_product_addon_cart_item_data' ), 20, 4 );
		add_filter( 'woocommerce_product_addons_adjust_price', array( $this, 'ph_booking_addon_price_in_cart_page' ),9,2 );
	}
	public function ph_booking_addon_price_in_cart_page( $true_false,$cart_item_data ) {
		if(isset($cart_item_data['phive_book_from_date']))
		{		
			return false;
		}
		return $true_false;
	}



	public function woocommerce_product_addon_cart_item_data($data, $addon, $product_id, $post_data){
		if(ph_is_bookable_product( $product_id) && !isset($post_data['addon_data']) )
		{
			$data=array();
		}

		return $data;
	}
	public function phive_addon_hide_total($show_total, $product){
		if ( $product->is_type( 'phive_booking' ) ) {
			$show_total = false;
		}
		return $show_total;
	}

	public function phive_apply_addon_price($booking_cost,$id) {
		if (  in_array( 'woocommerce-product-addons/woocommerce-product-addons.php', 
		    apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || in_array('woocommerce-product-addons-master/woocommerce-product-addons.php',apply_filters( 'active_plugins', get_option( 'active_plugins' ) ))) {
			if(!defined('WC_PRODUCT_ADDONS_VERSION'))
			{
				$version=(int)str_replace('.', '', WC_PRODUCT_ADDONS_VERSION);
				if(!isset($_POST['addon_data']) || empty($_POST['addon_data']))
				{
					return $booking_cost;
				}
				parse_str($_POST['addon_data'],$addon_data);
		
	   			// $_POST=array_merge($addon_data,$_POST);
				$addons       = $GLOBALS['Product_Addon_Cart']->add_cart_item_data( array(), $id, $addon_data, true );
				$addon_costs  = 0;
				$participant=1;
				if(isset($_POST['person_details']) && !empty($_POST['person_details']))
				{
					$participant=array_sum($_POST['person_details']);
				}
				$resources_pricing_rules = get_post_meta( $id, "_phive_booking_resources_pricing_rules", 1 );
				$ph_booking_resources_per_person=false;
				if(!empty($resources_pricing_rules))
				{
					foreach ($resources_pricing_rules as $key => $rule) {
						if(isset($rule['ph_booking_resources_per_person']) && $rule['ph_booking_resources_per_person']=='yes')
						{
							$ph_booking_resources_per_person=true;
							break;
						}
					}
				}
				if ( ! empty( $addons['addons'] ) ) {
					foreach ( $addons['addons'] as $addon ) {
						
						$addon['price'] = ( ! empty( $addon['price'] ) ) ? $addon['price'] : 0;

						if($ph_booking_resources_per_person)
							$addon_costs += floatval( $addon['price'] )*$participant ;
						else
							$addon_costs += floatval( $addon['price'] ) ;
					}
				}
			 	$booking_cost= ($booking_cost == '')? 0 : $booking_cost;
				$total = $booking_cost + $addon_costs;
				return $total;
			} // 110387 - prices not working because of incorrect version compare
			else if(defined('WC_PRODUCT_ADDONS_VERSION') && version_compare(WC_PRODUCT_ADDONS_VERSION, '3.0.1', '>='))
			{
				$version=(int)str_replace('.', '', WC_PRODUCT_ADDONS_VERSION);
				if(!isset($_POST['addon_data']) || empty($_POST['addon_data']))
				{
					return $booking_cost;
				}
				parse_str($_POST['addon_data'],$addon_data);
		
				$_POST=array_merge($addon_data,$_POST);
				// WPML Compatibility // price not working
				$current_product_id = isset($_POST['current_product_id']) ? $_POST['current_product_id'] : $id;
				// $addons       = $GLOBALS['Product_Addon_Cart']->add_cart_item_data( array(), $id, $addon_data, true );
				$addons       = $GLOBALS['Product_Addon_Cart']->add_cart_item_data( array(), $current_product_id, $addon_data, true );
				$addon_costs  = 0;
				$participant=1;
				if(isset($_POST['person_details']) && !empty($_POST['person_details']))
				{
					$participant=array_sum($_POST['person_details']);
				}
				$resources_pricing_rules = get_post_meta( $id, "_phive_booking_resources_pricing_rules", 1 );
				$ph_booking_resources_per_person=false;
				if(!empty($resources_pricing_rules))
				{
					foreach ($resources_pricing_rules as $key => $rule) {
						if(isset($rule['ph_booking_resources_per_person']) && $rule['ph_booking_resources_per_person']=='yes')
						{
							$ph_booking_resources_per_person=true;
							break;
						}
					}
				}
				if ( ! empty( $addons['addons'] ) ) {
					foreach ( $addons['addons'] as $addon ) {
						
						$addon['price'] = ( ! empty( $addon['price'] ) ) ? $addon['price'] : 0;

						if($ph_booking_resources_per_person && isset($addon['price_type']) && $addon['price_type']=='quantity_based')
							$addon_costs += floatval( $addon['price'] )*$participant ;
						else
							$addon_costs += floatval( $addon['price'] ) ;
					}
				}
			 	$booking_cost= ($booking_cost == '')? 0 : $booking_cost;
				$total = $booking_cost + $addon_costs;
				return $total;
			}
			else
			{
				return $booking_cost;
			}
		}
		else{
			return $booking_cost;
		}
		
	}
	public function phive_apply_addon_price_display($booking_cost,$id) {
		if (  in_array( 'woocommerce-product-addons/woocommerce-product-addons.php', 
		    apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || in_array('woocommerce-product-addons-master/woocommerce-product-addons.php',apply_filters( 'active_plugins', get_option( 'active_plugins' ) ))) {
			// 110387
			if(defined('WC_PRODUCT_ADDONS_VERSION') && version_compare(WC_PRODUCT_ADDONS_VERSION, '3.0.1', '>='))
			{
				$version=(int)str_replace('.', '', WC_PRODUCT_ADDONS_VERSION);
				if(!isset($_POST['addon_data']) || empty($_POST['addon_data']))
				{
					return $booking_cost;
				}
				parse_str($_POST['addon_data'],$addon_data);
		
	   			$_POST=array_merge($addon_data,$_POST);
				$addons       = $GLOBALS['Product_Addon_Cart']->add_cart_item_data( array(), $id, $addon_data, true );
				$addon_costs  = 0;
				$participant=1;
				if(isset($_POST['person_details']) && !empty($_POST['person_details']))
				{
					$participant=array_sum($_POST['person_details']);
				}
				$resources_pricing_rules = get_post_meta( $id, "_phive_booking_resources_pricing_rules", 1 );
				$ph_booking_resources_per_person=false;
				if(!empty($resources_pricing_rules))
				{
					foreach ($resources_pricing_rules as $key => $rule) {
						if(isset($rule['ph_booking_resources_per_person']) && $rule['ph_booking_resources_per_person']=='yes')
						{
							$ph_booking_resources_per_person=true;
							break;
						}
					}
				}
				if ( ! empty( $addons['addons'] ) ) {
					foreach ( $addons['addons'] as $addon ) {
						
						$addon['price'] = ( ! empty( $addon['price'] ) ) ? $addon['price'] : 0;

						if($ph_booking_resources_per_person && isset($addon['price_type']) && $addon['price_type']=='quantity_based')
							$addon_costs += floatval( $addon['price'] )*$participant ;
						else
							$addon_costs += floatval( $addon['price'] ) ;
					}
				}
			 	$booking_cost= ($booking_cost == '')? 0 : $booking_cost;
				$total = $booking_cost + $addon_costs;
				return $total;
			}
			else
			{
				return $booking_cost;
			}
		}
		else{
			return $booking_cost;
		}
		
	}

}
new WC_Product_phive_booking_addon_integration();