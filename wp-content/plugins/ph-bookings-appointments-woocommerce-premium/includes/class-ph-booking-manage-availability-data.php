<?php

/**
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Ph_Booking_Manage_Availability_Data
{
	public function __construct() 
    {
		$this->tablename = 'ph_bookings_availability_calculation_data';
		
		/* Manage Cart */
		add_action('ph_bookings_insert_data_in_availability_table_from_cart', array($this, 'ph_bookings_insert_data_in_availability_table_from_cart_func'), 10, 3);

		// add_action( 'woocommerce_before_cart_item_quantity_zero', array( $this, 'before_cart_item_quantity_zero' ), 10, 1 );
		add_action( 'woocommerce_cart_item_removed', array($this, 'cart_item_removed' ), 20);
		add_action( 'woocommerce_cart_item_restored', array($this, 'cart_item_restored' ), 20);
		add_action( 'woocommerce_thankyou', array($this, 'phive_order_placed'), 7, 1);

		add_action( 'ph-unfreez-booking-slot', array($this, 'phive_clear_scheduled_unfreez' ), 7);
		add_action('ph_bookings_unfreezing_hourly_event', array($this,'ph_bookings_unfreezing_hourly'), 7);
		/* Manage Cart END */

		/* When Product Permanently Deleted */
		/* When Order Permanently Deleted */
		add_action( 'before_delete_post', array($this, 'ph_delete_data_from_availability_table'), 10, 1 );

		/* Checkout Order Creation - Add Data in Availability Table */
		add_action( 'woocommerce_checkout_order_created', array($this, 'ph_bookings_insert_data_in_availability_table_when_order_created'), 10, 1 );

		/* Admin Order Creation - Add Data in Availability Table */
		add_action('ph_add_additional_order_item_meta_for_admin_bookings', array($this, 'ph_bookings_insert_availability_data_admin_order_created'), 20, 6);

		/* Booking Status Changed */
		/* When Order is Sent to Trash */
		/* When woocommerce_cancel_unpaid_orders cron runs, we are calling this action - ph_booking_status_changed  */ 
		add_action( 'ph_booking_status_changed', array($this, 'ph_change_status_in_availability_table'), 10, 4);

		/* Woocommerce Order Status Change */
		add_action( 'woocommerce_order_status_changed', array( $this, 'ph_change_status_in_availability_table_on_wc_status_change' ), 10, 3 );

		/* Booking Modified From Order Edit Page */
		add_action('ph_booking_order_items_modified', array($this, 'ph_modify_data_in_availability_table_after_order_edit'), 10, 3);

		/* Bookable Product Modified (Set charge per night in availability table as set in the product) */
		add_action( 'woocommerce_process_product_meta_phive_booking', array( $this, 'bookable_product_updated' ), 11 ); //The filter name should match with product class name

		/* Order Created From Google Calendar */
		add_action('ph_bookings_order_created_from_google_calendar', array($this, 'ph_bookings_order_created_from_google_calendar'), 10, 3);
	}

	public function ph_bookings_insert_data_in_availability_table($data, $buffer_from_date, $buffer_to_date, $settings)
	{
		// error_log('backtrace : '.print_r(debug_backtrace(),1));
		$i = 0;
		while(strtotime($buffer_from_date) <= strtotime($buffer_to_date))
		{
			$data['booked_date_type'] 		= 'middle';
			if($i == 0)
			{
				$data['booked_date_type'] 	= 'from';
			}
			else if(strtotime($buffer_from_date) == strtotime($buffer_to_date))
			{
				$data['booked_date_type'] 	= 'to';
			}

			$booking_interval 				= $data['interval'];
			$booking_interval				= $this->get_buffer_added_interval($data['product_id'], $settings);
			$booking_interval_format 		= $data['interval_format'];
			
			if($data['interval_format'] == 'day' || $data['interval_format'] == 'month'){
				$booking_interval 			= 1;
			}

			$data['booked_date'] 			= date('Y-m-d H:i:s', strtotime($buffer_from_date));
			$data['booked_date_end'] 		= date('Y-m-d H:i:s', strtotime("+$booking_interval $booking_interval_format", strtotime($buffer_from_date)));
			
			$obj 							= new Phive_Bookings_Database();
			$status 						= $obj->insert_data_availability_table($data);

			$buffer_from_date 				= $data['booked_date_end'];
			$i++;
			// error_log('data : '.print_r($data,1));

			if(($booking_interval != $data['interval'] && $data['interval_format'] != 'day' && $data['interval_format'] != 'month') 
			|| ($data['interval_format'] == 'minute' && $settings['buffer_after']))
			{
				if(strtotime($buffer_from_date) == strtotime($buffer_to_date))
				{
					break;
				}
			}
		}
	}

	/*
		#Adding cart details in availability table:-
		- Order_id is set to bigint and not null. so, in case of cart, we can use phive_booking_freezer_id in both order_id and order_item_id field.
		- Set it to canceled on removal from cart, and when restored, set it back to un-paid. Remove from table after 1 day if still canceled.
	*/
	public function ph_bookings_insert_data_in_availability_table_from_cart_func($cart_item, $order='', $order_item_id='')
	{
		// data
		$data['order_id'] 					= $cart_item['phive_booking_freezer_id'];
		$data['order_item_id'] 				= $cart_item['phive_booking_freezer_id'];
		$data['product_id'] 				= $cart_item['product_id'];

		// Product Settings
		$settings 							= $this->ph_get_product_settings($data['product_id']);
		// interval details
		$data['interval']					= $settings['interval'];
		$data['interval_format']			= $settings['interval_period'];
		$data['charge_per_night']			= $settings['charge_per_night'];

		// Booking Type
		$data['booking_type']				= 'cart';

		// Booked Dates
		$booked_from_date 					= $cart_item['phive_book_from_date'];
		$booked_to_date 					= $cart_item['phive_book_to_date'];
		$data['booking_status'] 			= 'un-paid';
		$data['woocommerce_order_status'] 	= 'pending';

		// Buffer
		// buffer from
		$buffer_from_id						= isset($cart_item['phive_booking_buffer_from_freezer_id']) ? $cart_item['phive_booking_buffer_from_freezer_id'] : '';
		$buffer_from_date					= $buffer_from_id ? get_post_meta($buffer_from_id, 'Buffer_before_From', 1) : $booked_from_date;
		$buffer_from_date					= $buffer_from_date ? $buffer_from_date : $booked_from_date;

		// buffer to
		$buffer_to_id						= isset($cart_item['phive_booking_buffer_to_freezer_id']) ? $cart_item['phive_booking_buffer_to_freezer_id'] : '';
		$buffer_to_date						= $buffer_to_id ? get_post_meta($buffer_to_id, 'Buffer_after_To', 1) : $booked_to_date;
		$buffer_to_date						= $buffer_to_date ? $buffer_to_date : $booked_to_date;

		// Participants
		$phive_booked_persons 				= ( isset($cart_item['phive_booked_persons']) && !empty($cart_item['phive_booked_persons'])) ? $cart_item['phive_booked_persons'] : array();
		$data['number_of_persons']    		=  array_sum($phive_booked_persons);
		$data['person_as_booking']  		= ( isset($cart_item['persons_as_booking']) && !empty($cart_item['persons_as_booking']) ) ? $cart_item['persons_as_booking'] : 'no';

		// Asset
		$data['asset_id']					= (isset($cart_item['phive_booked_assets']) && !empty($cart_item['phive_booked_assets'])) ? $cart_item['phive_booked_assets'] : NULL;

		$this->ph_bookings_insert_data_in_availability_table($data, $buffer_from_date, $buffer_to_date, $settings);
	}

	public static function ph_get_product_settings($product_id)
	{
		// WPML Compatibility
		$product_id = Ph_Bookings_General_Functions_Class::get_default_lang_product_id($product_id);

		$settings['interval'] 			 	= get_post_meta( $product_id, "_phive_book_interval", 1 );
		$settings['interval_period']	 	= get_post_meta( $product_id, '_phive_book_interval_period', 1 );
		$settings['interval_type']			= get_post_meta( $product_id, "_phive_book_interval_type", 1 );
		$settings['buffer_before']		 	= get_post_meta( $product_id, "_phive_buffer_before", 1 );
		$settings['buffer_after'] 		 	= get_post_meta( $product_id, "_phive_buffer_after", 1 );
		$settings['buffer_period'] 		 	= get_post_meta( $product_id, "_phive_buffer_period", 1 );
		$settings['enable_buffer']		 	= get_post_meta( $product_id, '_phive_enable_buffer', 1);
		$settings['persons_as_booking']  	= get_post_meta( $product_id, "_phive_booking_persons_as_booking", 1 );
		$settings['addition_notes_label']	= get_post_meta( $product_id, "_phive_additional_notes_label", 1 );
		$settings['required_confirmation'] 	= get_post_meta( $product_id, "_phive_book_required_confirmation", 1 );
		$settings['persons_pricing_rules']  = get_post_meta( $product_id, "_phive_booking_persons_pricing_rules", 1 );
		$settings['allowd_per_slot']		= get_post_meta( $product_id, '_phive_book_allowed_per_slot', 1 );
		
		// Global Asset Settings ->
		$settings['asset_settings']         = get_option( 'ph_booking_settings_assets', 1 );
		$settings['assets_rules'] 			= isset( $settings['asset_settings']['_phive_booking_assets'] ) ? $settings['asset_settings']['_phive_booking_assets'] : array();

		// Product Level Asset Settings ->
		$settings['assets_enabled']			= get_post_meta( $product_id, "_phive_booking_assets_enable", 1 );
		$settings['assets_auto_assign']		= get_post_meta( $product_id, "_phive_booking_assets_auto_assign", 1 );
		$settings['asset_label'] 			= get_post_meta( $product_id,'_phive_booking_assets_label');
		$settings['asset_label'] 			= empty($settings['asset_label'][0]) ? 'Type' : $settings['asset_label'][0];
		$settings['assets_pricing_rules']	= get_post_meta( $product_id, "_phive_booking_assets_pricing_rules", 1 );
		$settings['assets_pricing_rules']	= !empty($settings['assets_pricing_rules']) ? $settings['assets_pricing_rules'] : array();

		// Charge Per Night
		$settings['charge_per_night']		= get_post_meta( $product_id, "_phive_book_charge_per_night", 1 );

		return $settings;
	}

	public function cart_item_removed($cart_item_key) 
	{
		$cart_item = WC()->cart->removed_cart_contents[ $cart_item_key ];
		if ( isset( $cart_item['phive_booking_freezer_id'] ) ) 
		{
			$obj 	= new Phive_Bookings_Database();
			$id 	= $cart_item['phive_booking_freezer_id'];
			$status = $obj->update_status_availability_table($id, 'order_id', 'cart', 'canceled', 'booking_status');
		}
	}

	public function cart_item_restored($cart_item_key) 
	{
		$cart	  	= WC()->cart->get_cart();
		$cart_item 	= $cart[ $cart_item_key ];
		if ( isset( $cart_item['phive_booking_freezer_id'] ) ) 
		{
			$obj 	= new Phive_Bookings_Database();
			$id 	= $cart_item['phive_booking_freezer_id'];
			$status = $obj->update_status_availability_table($id, 'order_id', 'cart', 'un-paid', 'booking_status');
		}
	}

	public function phive_order_placed($order_id)
	{
		$freezer_ids = WC()->session->get( 'ph_crone_ids' );
		if( !empty($freezer_ids) )
		{
			foreach ($freezer_ids as $freezer_id) 
			{
				$obj 	= new Phive_Bookings_Database();
				$status = $obj->delete_data_availability_table($freezer_id, 'order_id', 'cart');
			}
		}
	}

	public function phive_clear_scheduled_unfreez($post_id)
	{
		$obj 	= new Phive_Bookings_Database();
		$status = $obj->delete_data_availability_table($post_id, 'order_id', 'cart');
	}

	public function ph_bookings_unfreezing_hourly() 
	{
		global $wpdb;
		global $wp_version;

		$query_post = "SELECT ID as freezed_id, post_date FROM {$wpdb->prefix}posts AS t1
		WHERE t1.post_type = 'booking_slot_freez';";
		$results 	= $wpdb->get_results( $query_post, ARRAY_A );

		foreach ($results as $key => $product) 
		{
			$post_date		 = date('Y-m-d H:i:s',strtotime($product['post_date']));

			if ( version_compare( $wp_version, '5.3', '>=' ) ) 
			{
				$currentTime = current_datetime();
				$currentTime = $currentTime->format('Y-m-d H:i:s');
			}
			else
			{
				$currentTime = current_time('Y-m-d H:i:s');
			}

			$before30mins 	 = strtotime('-30 minutes', strtotime($currentTime));
			$before30mins 	 = date('Y-m-d H:i:s', $before30mins);
			if(strtotime($post_date) < strtotime($before30mins))
			{
				$obj 		 = new Phive_Bookings_Database();
				$status 	 = $obj->delete_data_availability_table($product['freezed_id'], 'order_id', 'cart');
			}
		}
	}

	public function ph_bookings_insert_data_in_availability_table_when_order_created($order, $order_item_id='', $ph_booking_order='')
	{
		$data['order_id'] 					= $order->get_id();
		$data['woocommerce_order_status'] 	= $order->get_status();
		$order_items 						= $order->get_items();
		foreach($order_items as $item_id => $item)
		{
			// When new order item added/modified to existing booking
			if($ph_booking_order == 'existing')
			{
				if($order_item_id != $item_id)
				{
					continue;
				}
			}

			$data['product_id']		 = $item->get_product_id();
			$product 				 = wc_get_product($data['product_id']);
			if($product->get_type() != 'phive_booking')
			{
				continue;
			}
			$data['order_item_id'] 	 	= $item_id;
			$data['booking_type']  	 	= 'booked';
			
			// Product Settings
			$settings 				 	= $this->ph_get_product_settings($data['product_id']);
			// interval details
			$data['interval']		 	= $settings['interval'];
			$data['interval_format'] 	= $settings['interval_period'];
			$data['charge_per_night']	= $settings['charge_per_night'];

			// Booking Dates
			$booked_from_date			= ph_maybe_unserialize(wc_get_order_item_meta($item_id, 'From', 1));
			$booked_to_date  			= ph_maybe_unserialize(wc_get_order_item_meta($item_id, 'To', 1));
			$booked_to_date				= $booked_to_date ? $booked_to_date : $booked_from_date;
			$data['booking_status']  	= ph_maybe_unserialize(wc_get_order_item_meta($item_id, 'booking_status', 1));
			
			// Participants 
			$data['number_of_persons']  = wc_get_order_item_meta($item_id, 'Number of persons', 1);
			$data['number_of_persons']	= $data['number_of_persons'] ? $data['number_of_persons'] : 0;
			$data['person_as_booking']  = ph_maybe_unserialize(wc_get_order_item_meta($item_id, 'person_as_booking', 1));
			$data['person_as_booking']	= $data['person_as_booking'] ? $data['person_as_booking'] : 'no';
			
			// Asset 
			$data['asset_id']  			= ph_maybe_unserialize(wc_get_order_item_meta($item_id, 'Assets', 1));
			$data['asset_id']			= $data['asset_id'] ? $data['asset_id'] : NULL;
			
			// Buffer
			$buffer_before_id  			= ph_maybe_unserialize(wc_get_order_item_meta($item_id, 'buffer_before_id', 1));
			$buffer_after_id  			= ph_maybe_unserialize(wc_get_order_item_meta($item_id, 'buffer_after_id', 1));
			$buffer_from_date			= $buffer_before_id ? get_post_meta($buffer_before_id, 'Buffer_before_From', 1) : $booked_from_date;
			$buffer_from_date			= $buffer_from_date ? $buffer_from_date : $booked_from_date;
			$buffer_to_date				= $buffer_after_id ? get_post_meta($buffer_after_id, 'Buffer_after_To', 1) : $booked_to_date;
			$buffer_to_date				= $buffer_to_date ? $buffer_to_date : $booked_to_date;
			
			$this->ph_bookings_insert_data_in_availability_table($data, $buffer_from_date, $buffer_to_date, $settings);
		}
	}

	public function ph_bookings_insert_availability_data_admin_order_created($item_id, $order_id, $cart_item, $product_id, $tax_item_id, $order='')
	{
		if(!($order instanceof WC_Order))
		{
			return;
		}
		
		if(isset($_REQUEST['ph_booking_order']) && $_REQUEST['ph_booking_order'] == 'new')
		{
			$this->ph_bookings_insert_data_in_availability_table_when_order_created($order);
		}
		else if(isset($_REQUEST['ph_booking_order']) && $_REQUEST['ph_booking_order'] == 'existing')
		{
			$items = $order->get_items();
			foreach( $items as $order_item_id => $line_item ) 
			{
				if($item_id == $order_item_id)
				{
					$this->ph_bookings_insert_data_in_availability_table_when_order_created($order, $order_item_id, 'existing');
				}
			}
		}
	}

	public function ph_bookings_order_created_from_google_calendar($item_id, $order_id, $product_id)
	{
		$order = wc_get_order($order_id);
		if(!($order instanceof WC_Order))
		{
			return;
		}
		$this->ph_bookings_insert_data_in_availability_table_when_order_created($order);
	}

	public function ph_change_status_in_availability_table($booking_status, $item_id, $order_id, $order='' )
	{
		if(empty($order))
		{
			$order 	= wc_get_order($order_id);
		}
		if(!is_object($order))
		{
			return;
		}
		$woocommerce_order_status = $order->get_status();
		$obj 		= new Phive_Bookings_Database();
		if($booking_status == 'deleted')
		{
			$status = $obj->delete_data_availability_table($item_id, 'order_item_id', 'booked');
			$status = $status ? 'true' : 'false';
			return;
		}
		if($booking_status == 'cancelled' || $woocommerce_order_status == 'cancelled')
		{
			$booking_status = 'canceled';
		}
		$status = $obj->update_status_availability_table($item_id, 'order_item_id', 'booked', $booking_status, 'booking_status');
		$status = $obj->update_status_availability_table($item_id, 'order_item_id', 'booked', $woocommerce_order_status, 'woocommerce_order_status');
	}

	public function ph_delete_data_from_availability_table($post_id)
	{
		$post_type 	= get_post_type($post_id);
		$obj 		= new Phive_Bookings_Database();
		if($post_type == 'product')	 
		{
			$status = $obj->delete_data_availability_table($post_id, 'product_id', '');
		}
		else if($post_type == 'shop_order')
		{
			$status = $obj->delete_data_availability_table($post_id, 'order_id', 'booked');
		}
		else{
			return;
		}
	}

	public function ph_change_status_in_availability_table_on_wc_status_change($id, $previous_status, $next_status)
	{
		$order 			= wc_get_order( $id );
		$order_items 	= $order->get_items();
		foreach ($order_items as $order_item_id => $item) 
		{
			$product 	= wc_get_product($item->get_product_id());
			if((!is_object($product)) || $product->get_type() != 'phive_booking')
			{
				continue;
			}
			$booking_status = ph_maybe_unserialize(wc_get_order_item_meta($order_item_id, 'booking_status', 1));
			$obj 		= new Phive_Bookings_Database();
			if($booking_status == 'deleted')
			{
				$status = $obj->delete_data_availability_table($order_item_id, 'order_item_id', 'booked');
				$status = $status ? 'true' : 'false';
				return;
			}
			if($booking_status == 'cancelled')
			{
				$booking_status = 'canceled';
			}
			$status = $obj->update_status_availability_table($order_item_id, 'order_item_id', 'booked', $booking_status, 'booking_status');
			$status = $obj->update_status_availability_table($order_item_id, 'order_item_id', 'booked', $next_status, 'woocommerce_order_status');
		}
	}

	public function ph_modify_data_in_availability_table_after_order_edit( $edit_items, $order_id, $request)
	{
		foreach ($edit_items as $order_item_id => $edit_key_values) 
		{
			$obj 	= new Phive_Bookings_Database();
			$status = $obj->delete_data_availability_table($order_item_id, 'order_item_id', 'booked');

			$order 	= wc_get_order($order_id);
			$this->ph_bookings_insert_data_in_availability_table_when_order_created($order, $order_item_id, 'existing');
		}
	}

	public static function ph_get_number_of_available_slot($date, $product_id='', $asset_id='', $ignore_freezed=false, $calendar_for='')
	{
		global $wpdb;
		$tablename 			= $wpdb->prefix.'ph_bookings_availability_calculation_data';

		$compare_date 		= date('Y-m-d H:i:s',$date);
		$settings			= self::ph_get_product_settings($product_id);
		$interval			= $settings['interval'];
		$interval_period 	= $settings['interval_period'];
		$interval_type		= $settings['interval_type'];
		$charge_per_night	= $settings['charge_per_night'];

		if(empty($asset_id) && $settings['assets_enabled'] == 'yes' && $settings['assets_auto_assign'] == 'yes')
		{
			$asset_id 		= self::get_asset_id($date, '', $product_id, $ignore_freezed, $settings);
		}

		if(($interval_period == 'hour' || $interval_period == 'minute'))
		{
			$compare_date_to 	= date ('Y-m-d H:i:s', strtotime( "+$interval $interval_period", $date ) );

			$query 		= "SELECT sno, booked_date, COUNT(*) as booking_count, SUM(participant_count) as participant_count, participant_as_booking FROM $tablename where 
						booking_status   != 'canceled' AND
						booking_status   != 'refunded' AND
						product_id 		 = $product_id AND
						(
							(
								booked_date 	<= '$compare_date' AND
								booked_date_end > '$compare_date'
							) OR
							(
								booked_date 	>= '$compare_date' AND
								booked_date 	<  '$compare_date_to'
							) OR
							(
								booked_date_end > '$compare_date' AND
								booked_date_end <  '$compare_date_to'
							)
						) 
						GROUP BY participant_as_booking";
				
			if($ignore_freezed)
			{
				$query 	= "SELECT sno, booked_date, COUNT(*) as booking_count, SUM(participant_count) as participant_count, participant_as_booking FROM $tablename where 
						booking_status   != 'canceled' AND
						booking_status   != 'refunded' AND
						booking_type	 != 'cart'	   AND
						product_id 		 = $product_id AND
						(
							(
								booked_date 	<= '$compare_date' AND
								booked_date_end > '$compare_date'
							) OR
							(
								booked_date 	>= '$compare_date' AND
								booked_date 	<  '$compare_date_to'
							) OR
							(
								booked_date_end > '$compare_date' AND
								booked_date_end <  '$compare_date_to'
							)
						) 
						GROUP BY participant_as_booking";	
			}
		}
		else if($interval_period == 'day' || $interval_period == 'month') // Day Calendar
		{
			if($ignore_freezed)
			{
				$query 	= "SELECT sno, asset_id, booked_date, booked_date_end, COUNT(*) as booking_count, SUM(participant_count) as participant_count, participant_as_booking FROM $tablename where 
						booking_status    != 'canceled' AND
						booking_status    != 'refunded' AND
						booking_type	  != 'cart'		AND
						product_id 		  = '$product_id' AND
						DATE(booked_date) = '$compare_date' 
						GROUP BY participant_as_booking";

				if($charge_per_night == 'yes')
				{
					$query 	= "SELECT sno, asset_id, booked_date, booked_date_end, COUNT(*) as booking_count, SUM(participant_count) as participant_count, participant_as_booking FROM $tablename where 
							booking_status    != 'canceled' AND
							booking_status    != 'refunded' AND
							booking_type	  != 'cart'		AND
							product_id 		  = '$product_id' AND
							DATE(booked_date) = '$compare_date' AND
							booked_date_type  != 'to'
							GROUP BY participant_as_booking";
				}
			}
			else
			{
				$query 	= "SELECT sno, asset_id, booked_date, booked_date_end, COUNT(*) as booking_count, SUM(participant_count) as participant_count, participant_as_booking FROM $tablename where 
						booking_status    != 'canceled' AND
						booking_status    != 'refunded' AND
						product_id 		  = '$product_id' AND
						DATE(booked_date) = '$compare_date' 
						GROUP BY participant_as_booking";
				
				if($charge_per_night == 'yes')
				{
					$query 	= "SELECT sno, asset_id, booked_date, booked_date_end, COUNT(*) as booking_count, SUM(participant_count) as participant_count, participant_as_booking FROM $tablename where 
							booking_status    != 'canceled' AND
							booking_status    != 'refunded' AND
							product_id 		  = '$product_id' AND
							DATE(booked_date) = '$compare_date' AND
							booked_date_type  != 'to'
							GROUP BY participant_as_booking";
				}
			}
		}

		$results 		= $wpdb->get_results($query);
		$booking_count 	= 0;
		foreach ($results as $key => $value) 
		{
			if($value->participant_as_booking == 'yes')
			{
				$booking_count += is_numeric($value->participant_count) ? $value->participant_count : $value->booking_count;
			}
			else
			{
				$booking_count += $value->booking_count;
			}
		}

		$left_count = max(0, $settings['allowd_per_slot']-$booking_count);
		
		if(!empty($asset_id) && $left_count > 0 && $settings['assets_enabled'] == 'yes')
		{
			$asset_left_count 	= self::get_asset_availability( $asset_id, $date ,$ignore_freezed, $product_id, $settings);
			$left_count			= max(0, min($asset_left_count, $left_count));
		}
		else if(empty($asset_id) && $settings['assets_enabled'] == 'yes')
		{
			$left_count         = 0;
		}
		return $left_count;
	}

	public static function get_asset_id($from, $to, $product_id, $ignore_freezed, $settings='')
	{
		if(empty($settings))
		{
			$settings		= self::ph_get_product_settings($product_id);
		}
		$interval 			= $settings['interval'];
		$interval_period 	= $settings['interval_period'];
		$interval_type		= $settings['interval_type'];
		$interval_string 	= "$interval $interval_period";
		$asset_found 		= false;

		// Loop through booked slots, find asset which is available for all slot.
		foreach($settings['assets_pricing_rules'] as $key => $rule) 
		{
			if(empty($rule['ph_booking_asset_id']))
			{
				continue;
			}
			$current_time 			= ph_strtotime($from);
			$book_to 				= empty($to) ? $current_time : ph_strtotime($to);
			$loop_breaker 			= 300;
			while ( !empty($current_time) && $current_time <= $book_to && $loop_breaker > 0 ) 
			{
				$asset_availability = self::get_asset_availability( $rule['ph_booking_asset_id'], $current_time ,$ignore_freezed, $product_id, $settings );
				$asset_obj 			= new phive_booking_assets($rule['ph_booking_asset_id']);
				
				if(($asset_availability == 0) || !($asset_obj->is_available($current_time)))
				{
					$asset_found  	= false;
					continue 2;
				}
				
				$asset_found  		= $rule['ph_booking_asset_id'];
				$current_time 		= strtotime( "+$interval_string", $current_time );;
				$loop_breaker--;
			}
			if( !empty($asset_found) )
			{
				return $asset_found;
			}
		}
		return $asset_found;
	}

	public static function get_asset_availability( $asset_id, $current_time ,$ignore_freezed, $product_id, $settings )
	{
		global $wpdb;
		$tablename 			= $wpdb->prefix.'ph_bookings_availability_calculation_data';

		$asset_settings 	= get_option( 'ph_booking_settings_assets', 1 );
		$rules 				= ((!empty($asset_settings)) && (isset($asset_settings['_phive_booking_assets']))) ? $asset_settings['_phive_booking_assets'] : array();
		$max_quantity 		= isset($rules[$asset_id]['ph_booking_asset_quantity']) ? (float) $rules[$asset_id]['ph_booking_asset_quantity'] : null;
		$charge_per_night	= $settings['charge_per_night'];

		$compare_date 		= date('Y-m-d H:i:s', $current_time);
		$compare_date_to	= $compare_date;
		if(($settings['interval_period'] == 'hour' || $settings['interval_period'] == 'minute'))
		{
			$interval_string 	= "+".$settings['interval']." ".$settings['interval_period'];
			$compare_date_to 	= date('Y-m-d H:i:s', strtotime($interval_string, $current_time));
		}

		if($ignore_freezed)
		{
			$query 		= "SELECT sno, asset_id, booked_date, booked_date_end, COUNT(*) as booking_count, SUM(participant_count) as participant_count, participant_as_booking FROM $tablename where 
						booking_status    != 'canceled' AND
						booking_status    != 'refunded' AND
						booking_type	  != 'cart'		AND
						asset_id 		  = '$asset_id' AND
						DATE(booked_date) = '$compare_date' AND
						( 
							charge_per_night != 'yes'
							OR 
							booked_date_type != 'to'
						)
						GROUP BY participant_as_booking";

			if(($settings['interval_period'] == 'hour' || $settings['interval_period'] == 'minute'))
			{
				$query 	= "SELECT sno, booked_date, COUNT(*) as booking_count, SUM(participant_count) as participant_count, participant_as_booking FROM $tablename where 
						booking_status   != 'canceled' AND
						booking_status   != 'refunded' AND
						booking_type	  != 'cart'		AND
						asset_id 		 = '$asset_id' AND
						(
							(
								booked_date 	<= '$compare_date' AND
								booked_date_end > '$compare_date'
							) OR
							(
								booked_date 	>= '$compare_date' AND
								booked_date 	<  '$compare_date_to'
							) OR
							(
								booked_date_end > '$compare_date' AND
								booked_date_end <  '$compare_date_to'
							)
						) AND
						( 
							charge_per_night != 'yes'
							OR 
							booked_date_type != 'to'
						)
						GROUP BY participant_as_booking";
			}
		}
		else
		{
			$query 		= "SELECT sno, asset_id, booked_date, booked_date_end, COUNT(*) as booking_count, SUM(participant_count) as participant_count, participant_as_booking FROM $tablename where 
						booking_status    != 'canceled' AND
						booking_status    != 'refunded' AND
						asset_id 		  = '$asset_id' AND
						DATE(booked_date) = '$compare_date' AND
						( 
							charge_per_night != 'yes'
							OR 
							booked_date_type != 'to'
						)
						GROUP BY participant_as_booking";

			if($settings['interval_period'] == 'hour' || $settings['interval_period'] == 'minute')
			{
				$query 	= "SELECT sno, booked_date, COUNT(*) as booking_count, SUM(participant_count) as participant_count, participant_as_booking FROM $tablename where 
						booking_status   != 'canceled' AND
						booking_status   != 'refunded' AND
						asset_id 		 = '$asset_id' AND
						(
							(
								booked_date 	<= '$compare_date' AND
								booked_date_end > '$compare_date'
							) OR
							(
								booked_date 	>= '$compare_date' AND
								booked_date 	<  '$compare_date_to'
							) OR
							(
								booked_date_end > '$compare_date' AND
								booked_date_end <  '$compare_date_to'
							)
						)AND
						(
							charge_per_night != 'yes'
							OR
							booked_date_type != 'to'
						)
						GROUP BY participant_as_booking";
			}
		}
		
		$results 		= $wpdb->get_results($query);
		$booking_count 	= 0;
		foreach ($results as $key => $value) 
		{
			if($value->participant_as_booking == 'yes')
			{
				$booking_count += is_numeric($value->participant_count) ? $value->participant_count : $value->booking_count;
			}
			else
			{
				$booking_count += $value->booking_count;
			}
		}
		return max(0, ($max_quantity-$booking_count));
	}

	public function bookable_product_updated($post_id)
	{
		if(isset($_POST['_phive_book_interval_type']) && $_POST['_phive_book_interval_type'] == 'fixed')
		{
			$charge_per_night 	= 'no';
		}
		else 
		{
			$charge_per_night 	= isset( $_POST['_phive_book_charge_per_night'] ) ? 'yes' : 'no';
		}
		if(isset($charge_per_night))
		{
			$obj 				= new Phive_Bookings_Database();
			$status 			= $obj->update_charge_per_night_availability_table($charge_per_night, $post_id);
		}
	}

	public function get_buffer_added_interval($product_id, $settings)
	{
		$settings['buffer_before'] 	= empty($settings['buffer_before']) ? '0': $settings['buffer_before'];
		$settings['buffer_after'] 	= empty($settings['buffer_after']) ? '0': $settings['buffer_after'];
		$interval 					= empty($settings['interval']) ? '1' : $settings['interval'];
		if((($settings['buffer_before'] % $interval) != 0) || (($settings['buffer_after'] % $interval) != 0))
		{
			$interval += ($settings['buffer_before'] + $settings['buffer_after']);
		}
		return $interval;
	}

} new Ph_Booking_Manage_Availability_Data();