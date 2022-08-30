<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
* This class deal with all ajax call.
*/
class phive_booking_ajax_interface{
	public function __construct() {
		add_action( 'wp_ajax_phive_get_callender_next_month', array($this,'phive_get_callender_next_month') );
		add_action( 'wp_ajax_nopriv_phive_get_callender_next_month', array($this,'phive_get_callender_next_month') );
		
		add_action( 'wp_ajax_phive_get_callender_prev_month', array($this,'phive_get_callender_prev_month') );
		add_action( 'wp_ajax_nopriv_phive_get_callender_prev_month', array($this,'phive_get_callender_prev_month') );

		add_action( 'wp_ajax_phive_get_booked_datas_of_date', array($this,'phive_callender_time_for_date') );
		add_action( 'wp_ajax_nopriv_phive_get_booked_datas_of_date', array($this,'phive_callender_time_for_date') );
		
		add_action( 'wp_ajax_phive_reload_callender', array($this,'phive_reload_callender') );
		add_action( 'wp_ajax_nopriv_phive_reload_callender', array($this,'phive_reload_callender') );

		add_action( 'wp_ajax_phive_get_booked_price', array($this,'phive_get_booked_price') );
		add_action( 'wp_ajax_nopriv_phive_get_booked_price', array($this,'phive_get_booked_price') );

		add_action( 'wp_ajax_phive_get_blocked_dates', array($this,'phive_get_blocked_dates') );
		add_action( 'wp_ajax_nopriv_phive_get_blocked_dates', array($this,'phive_get_blocked_dates') );
	}

	public function phive_get_blocked_dates(){
		$product_id 					= $_POST['product_id'];
		$this->product_id 				= $product_id;
		$start_date 					= wp_unslash($_POST['start_date']);
		$end_date						= date('Y-m-d',strtotime( "+1 month", strtotime($start_date) ));
		$asset_id 						= ! empty($_POST['asset']) ? $_POST['asset'] : '';
		$this->assets_enabled			= get_post_meta( $product_id, '_phive_booking_assets_enable', 1 );
		$this->assets_pricing_rules 	= get_post_meta( $product_id, '_phive_booking_assets_pricing_rules', 1 );
		$this->assets_auto_assign		= get_post_meta( $product_id, '_phive_booking_assets_auto_assign', 1 );
		$this->interval_period 			= get_post_meta( $product_id, "_phive_book_interval_period", 1 );
		$this->interval 				= get_post_meta( $product_id, "_phive_book_interval", 1 );
		$this->interval_type 			= get_post_meta( $product_id, "_phive_book_interval_type", 1 );
		// error_log("blocking....");
		// echo json_encode(array());die;
		// error_log($this->interval_period." ".$this->interval." ".$this->interval_type );
		if( $this->assets_enabled=='yes' ){
			if( $this->assets_auto_assign == 'yes' ){
				// 96421
				$display_settings 		= get_option('ph_bookings_display_settigns');
				$use_availability_table = (isset($display_settings['calculate_availability_using_availability_table']) && $display_settings['calculate_availability_using_availability_table'] == 'yes') ? true : false;
				$use_availability_table = false; //remove this line when migrating to this functionality
				if($use_availability_table)
				{
					$assets_chooen 		= Ph_Booking_Manage_Availability_Data::get_asset_id($start_date, $end_date, $product_id, false, '');
					if ((!$assets_chooen) && (count($this->assets_pricing_rules) > 0)) 
					{
						$assets_chooen  = $this->assets_pricing_rules[0]['ph_booking_asset_id'];
						// error_log('asset_fount : '.$asset_fount);
					}
				}
				else
				{
					$assets_chooen		= $this->get_most_matching_asset_for_slots( $start_date, $end_date, 'phive_get_blocked_dates' );
				}
				$asset_id = $assets_chooen;
			}else{
				$assets_chooen		= isset($asset_id) ? $asset_id : '';
			}

			if( empty($assets_chooen) ){
				
				echo json_encode(array());
				exit();

			}
			// $prod_obj = new WC_Product_phive_booking( $product_id );
			// $prod_obj->set_id($product_id);
			// $prod_obj->ph_set_asset_id($assets_chooen);
		}

		include_once('frondend/class-ph-booking-calendar-strategy.php');
		$calender = new phive_booking_calendar_strategy( $product_id );
		$calender->get_available_date_for_time($start_date, $end_date, $product_id, $asset_id);
		$unavailable_array= $calender->unavailable_array;
		echo json_encode($unavailable_array);die;
	}


	/**
	* Calculate the price by considering varous inputs like from, to, asset, etc.
	* @return json
	*/
	public function phive_get_booked_price(){
		global $woocommerce;
		$product_id 					= $_POST['product_id'];
		$current_product_id 			= isset($_POST['current_product_id']) ? $_POST['current_product_id'] : $product_id;
		$this->product_id 				= $product_id;
		$this->interval_period 			= get_post_meta( $product_id, "_phive_book_interval_period", 1 );
		$this->interval 				= get_post_meta( $product_id, "_phive_book_interval", 1 );
		$this->interval_type 			= get_post_meta( $product_id, "_phive_book_interval_type", 1 );
		$this->assets_enabled			= get_post_meta( $product_id, '_phive_booking_assets_enable', 1 );
		$this->assets_pricing_rules 	= get_post_meta( $product_id, '_phive_booking_assets_pricing_rules', 1 );
		$this->assets_auto_assign		= get_post_meta( $product_id, '_phive_booking_assets_auto_assign', 1 );

		$from 					= wp_unslash($_POST['book_from']);
		$to 					= wp_unslash($_POST['book_to']);
		$person_details 		= ! empty($_POST['person_details']) ? $_POST['person_details'] : array();
		$resources_details 		= ! empty($_POST['resources_details']) ? $_POST['resources_details'] : array();
		$asset 					= ! empty($_POST['asset']) ? $_POST['asset'] : null;
		$addon_data 			= $_POST['addon_data'];

		//support for customise booking interval addon
		$to = apply_filters('phive_modify_book_to_date_before_price_calculation', $to, $product_id, $_POST, $this->interval, $this->interval_period, $this->interval_type );

		$customer_choosen_values[ $product_id ] = array(
			'book_from' 			=> $from,
			'book_to' 				=> $to,
			'persons_details' 		=> $person_details,
			'resources_details' 	=> $resources_details,
			'assets_details'		=> $asset,
			'addon_data'			=> $addon_data,
			'custom_fields'				=> isset($_POST['custom_fields'])?$_POST['custom_fields']:''
		);
		
		WC()->session->set( 'phive_booking_details', $customer_choosen_values );
		
		$prod_obj = new WC_Product_phive_booking( $product_id );
		$prod_obj->set_id($product_id);
		
		$assets_chooen = '';
		if( $this->assets_enabled=='yes' ){
			if( $this->assets_auto_assign == 'yes' ){
				// 96421
				$display_settings 		= get_option('ph_bookings_display_settigns');
				$use_availability_table = (isset($display_settings['calculate_availability_using_availability_table']) && $display_settings['calculate_availability_using_availability_table'] == 'yes') ? true : false;
				$use_availability_table = false; //remove this line when migrating to this functionality
				if($use_availability_table)
				{
					$assets_chooen 		= Ph_Booking_Manage_Availability_Data::get_asset_id($from, $to, $product_id, false, '');
				}
				else
				{
					$assets_chooen		= $this->get_most_matching_asset_for_slots( $from, $to );
				}
			}else{
				$assets_chooen		= isset($customer_choosen_values[$product_id]['assets_details']) ? $customer_choosen_values[$product_id]['assets_details'] : '';
			}

			if( empty($assets_chooen) ){
				
				echo json_encode(
					array(
						'error' 	=> 1,
						'error_msg'	=> __( 'Oops!, Some of the requirements is not available for selected block(s).','bookings-and-appointments-for-woocommerce' ),
					)
				);
				exit();

			}
			$prod_obj->ph_set_asset_id($assets_chooen);
		}

		if( $this->interval_type=='customer_choosen' && (($this->interval_period == 'minute') || ($this->interval_period == 'hour')) ){

				include_once('frondend/class-ph-booking-calendar-strategy.php');
				$calender = new phive_booking_calendar_strategy( $product_id );
				$is_available = $calender->is_all_slots_available($from,$to,$product_id,$assets_chooen);
				if(!empty($is_available))
				{
					$wp_date_format=get_option( 'date_format' );
					$wp_time_format=get_option( 'time_format' );
					// $is_available = array_map(function($date){
					// 	return Ph_Bookings_General_Functions_Class::phive_get_date_in_wp_format($date);
					// }, $is_available);
					$is_available = Ph_Bookings_General_Functions_Class::phive_get_date_in_wp_format($is_available);
					echo json_encode(
						array(
							'error' 	=> 1,
							'error_msg'	=> __( sprintf('Oops!, %s is not available to book ',$is_available),'bookings-and-appointments-for-woocommerce' ),
						)
					);
					exit();
				}
		}


		if( (($this->interval_period == 'minute') || ($this->interval_period == 'hour')) ){
			$to =  date( 'Y-m-d H:i', strtotime( "+$this->interval $this->interval_period",strtotime($to) ) );
		}
		// WPML Compatibilty
		$currency = ! empty($woocommerce->session->client_currency) ? $woocommerce->session->client_currency : get_woocommerce_currency();

		$get_booked_price=array(
				'price_html' 	=> $prod_obj->get_price_html($currency),
				'price'			=> $prod_obj->get_price(),
				'to_date'		=> Ph_Bookings_General_Functions_Class::phive_get_date_in_wp_format($to),
				'from_date'		=> Ph_Bookings_General_Functions_Class::phive_get_date_in_wp_format($from),
				'asset_id'		=> $assets_chooen, 
				'org_from_date' => $from, //from date without translation
				'org_to_date' => $to, //to date without translation
				'addon_data'	=> array(),
			);
		echo json_encode(apply_filters('phive_booking_booked_price_details',$get_booked_price,$product_id,$_POST));
		exit();
	}

	private function get_most_matching_asset_for_slots($from, $to, $called_by=''){
		$interval_string 	= "$this->interval $this->interval_period";
		// $asset_fount = '';
		$asset_fount = false;
		// Loop through booked slots, find asset which available for all slot.
		foreach ($this->assets_pricing_rules as $key => $rule) {
			if( empty($rule['ph_booking_asset_id']) )
				continue;

			$current_time 		= strtotime($from);
			$book_to 			= empty($to) ? $current_time : strtotime($to);

			$loop_breaker = 500;

			// 110642 - With auto-assign asset, not able to select first date of previous booking as checkout date.
			$product_id = isset($this->product_id) ? $this->product_id : '';
			$charge_per_night	= get_post_meta( $product_id, "_phive_book_charge_per_night", 1 );

			while ( !empty($current_time) && (($current_time <= $book_to && $charge_per_night != 'yes') || ($current_time < $book_to)) && $loop_breaker > 0 ) {
				$asset_availability = $this->get_asset_availability( $rule['ph_booking_asset_id'], $current_time );
				$this->asset_obj = $this->get_asset_obj( $rule['ph_booking_asset_id'], $from );
				if( $asset_availability == 0 || !$this->asset_obj->is_available($current_time)){
					$asset_fount  = false;
					continue 2;
				}
				$asset_fount  = $rule['ph_booking_asset_id'];
				$current_time = strtotime( "+$interval_string", $current_time );;
				$loop_breaker--;
			}
			if( !empty($asset_fount) ){
				return $asset_fount;
			}
		}
		if ((count($this->assets_pricing_rules) > 0) && $called_by == 'phive_get_blocked_dates') 
		{
			$asset_fount = $this->assets_pricing_rules[0]['ph_booking_asset_id'];
			// error_log('asset_fount : '.$asset_fount);
		}
		return $asset_fount;
	}
	private function get_asset_obj($asset_id, $date){
		return new phive_booking_assets($asset_id);
	}

	private function get_asset_availability( $asset_id, $date ){
		$asset_manager = new phive_booking_assets($asset_id);

		switch( $this->interval_period ){
			case 'day':
				$interval_string = '+1 day';
				$format = "Y-m-d";
				break;
			
			case 'hour':
			case 'minute':
				$interval_string = "+".$this->interval." ".$this->interval_period;
				$format = "Y-m-d H:i";
				break;

			case 'month':
				$interval_string = "+1 month";
				$format = "Y-m-d";
				break;
		}
		$from 	= date ( $format, $date );
		$to 	= date ( $format, strtotime( $interval_string, $date ) );
		
		$product_id = isset($this->product_id) ? $this->product_id : '';
		// sending interval_period for transients to be applied on time calendar only
		$asset_availability = $asset_manager->get_availability( $from, $to, false ,$this->interval_period, $product_id);

		return $asset_availability;
	}

	/**
	* Refresh the calendar (Propably while changin the asset on froned end).
	* @return json
	*/
	public function phive_reload_callender(){
		$product_id 	= $_POST['product_id'];
		// $month 			= date('m');
		// $year 			= date('Y');

		$month = isset($_POST['month'])?$_POST['month']:date('m');		//got month and year from post so that the callender start date can 								be different than the current month and year. 
		$year = isset($_POST['year'])?$_POST['year']:date('Y');
		
		// (PHP higher version 7.4)
		if(isset($_POST['month']))
		{
			$month = date('m', strtotime($month));
		}
		
		// $calendar_for 	= $_POST['calendar_for']; //This can be removed
		$asset 			= isset($_POST['asset']) ? $_POST['asset'] : '';
		$booking_date_text=apply_filters('ph_booking_pick_booking_date_text','Please Pick a Date',$product_id);

		if( !class_exists('phive_booking_calendar_strategy') ){
			include_once('frondend/class-ph-booking-calendar-strategy.php');
		}
		$callender = new phive_booking_calendar_strategy( $product_id );

	 	$start_date = date ( "Y-m-d", strtotime("$year-$month-01") );
		echo json_encode(
			array(
				'days' 			=> $callender->ph_reload_calendar( $start_date, '', $product_id, $asset ),
				'month'			=> date( "F",strtotime($start_date) ),
				'year'			=> date( "Y",strtotime($start_date) ),
				'time_slots'	=> '<center>'.__( $booking_date_text,"bookings-and-appointments-for-woocommerce" ).'</center>',
			)
		);
		exit();
	}

	/**
	* Render the html of Time calendar of given date.
	* @return HTML
	*/
	public function phive_callender_time_for_date(){
		$product_id 	= $_POST['product_id'];
		$date 			= $_POST['date'];
		$calendar_for 	= $_POST['type'];
		$asset 			= isset($_POST['asset']) ? $_POST['asset'] : '';
		$selected_date 			= isset($_POST['selected_date']) ? $_POST['selected_date'] : '';
		if( !class_exists('phive_booking_calendar_strategy') ){
			include_once('frondend/class-ph-booking-calendar-strategy.php');
		}
		
		$callender = new phive_booking_calendar_strategy( $product_id );
		$start_date = $date.' '.$callender->get_shop_opening_time($product_id,$date,$selected_date);
		// $start_date = $date.' 00:00';
		echo $callender->phive_generate_time_for_period( $start_date, '', $product_id, $asset, $calendar_for, $_POST );
		exit();
	}

	/**
	* Render the html of Day calendar on clicking the next button
	* @return HTML
	*/
	public function phive_get_callender_next_month(){
		$product_id 	= $_POST['product_id'];
		$month 			= $_POST['month'];
		$year 			= $_POST['year'];
		$calendar_for 	= $_POST['calendar_for'];
		$asset 			= ! empty($_POST['asset']) ? $_POST['asset'] : null;
		if( !class_exists('phive_booking_calendar_strategy') ){
			include_once('frondend/class-ph-booking-calendar-strategy.php');
		}
		$callender = new phive_booking_calendar_strategy( $product_id );

		// (PHP higher version 7.4)
		// $month = date('m', strtotime($month));

		// incorrect shifting of months
		$dateObj   = new DateTime("$month 02, $year");
		$month = $dateObj->format('m');

	 	$start_date = date ( "Y-m-d", strtotime( "+1 month", strtotime("$year-$month-01") ) ) ;
		
		// $available_date_array = $callender->get_available_date_for_time($start_date, '', $product_id, $asset);
		
		echo json_encode(
			array(
				'days' 		=> $callender->phive_generate_days_for_period( $start_date, '', $product_id, $asset, $calendar_for ),
				'month'		=> date( "F",strtotime($start_date) ),
				'year'		=> date( "Y",strtotime($start_date) ),
			)
		);
		exit();
	}
	
	/**
	* Render the html of Day calendar on clicking the prev button
	* @return HTML
	*/
	public function phive_get_callender_prev_month(){
		$product_id 	= $_POST['product_id'];
		$month 			= $_POST['month'];
		$year 			= $_POST['year'];
		$calendar_for 	= $_POST['calendar_for'];
		$asset 			= ! empty($_POST['asset']) ? $_POST['asset'] : null;
		if( !class_exists('phive_booking_calendar_strategy') ){
			include_once('frondend/class-ph-booking-calendar-strategy.php');
		}
		$callender = new phive_booking_calendar_strategy( $product_id );

		// 60940 (PHP higher version 7.4)
		// $month = date('m', strtotime($month));

		// incorrect shifting of months
		$dateObj   = new DateTime("$month 02, $year");
		$month = $dateObj->format('m');

		$start_date = date ( "Y-m-d", strtotime( "-1 month", strtotime("$year-$month-01") ) ) ;
		
		// $available_date_array = $callender->get_available_date_for_time($start_date, '', $product_id, $asset);
		
		echo json_encode(
			array(
				'days' 		=> $callender->phive_generate_days_for_period( $start_date, '', $product_id, $asset, $calendar_for ),
				'month'		=> date( "F",strtotime($start_date) ),
				'year'		=> date( "Y",strtotime($start_date) ),
			)
		);
		exit();
	}

}
new phive_booking_ajax_interface();
