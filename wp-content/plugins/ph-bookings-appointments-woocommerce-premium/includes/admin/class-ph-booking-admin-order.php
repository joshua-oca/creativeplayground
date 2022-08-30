<?php

class phive_booking_admin_order {

	public function __construct(){
		if(isset($_REQUEST['ph_product_submit'])){
			$this->ph_booking_form_validation();
		}
			
		else if( ! empty( $_REQUEST['ph_calendar_submit'] )){
			$this->ph_booking_process_form_data();
		}
		else{
			$this->ph_generate_booking_form(1);
		}
		
	}
	
	public function ph_generate_booking_form($step) {
		switch ( $step ) {
			case 1:
				include_once( 'views/html-ph-booking-admin-order.php' );
				break;
			case 2:

				include_once( 'views/html-ph-booking-admin-order-calender.php' );
				$scripts= new phive_booking_initialze_premium;

				// JS events were getting added twice
				// add_action( 'wp_enqueue_scripts', array( $this, 'phive_booking_scripts' ) );
				// add_filter( 'admin_enqueue_scripts', array( $this, 'phive_admin_scripts' ) );		
				// add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
				// add_action( 'plugins_loaded', array( $this,'register_booking_product_product_type' ) );
				// $scripts->phive_booking_scripts();
				// $scripts->phive_admin_scripts();
				
				// Loading required style sheets
				$scripts->phive_booking_styles_admin_booking_calendar();
				
				break;
			default:
				include_once( 'views/html-ph-booking-admin-order.php' );
				break;
		}
	}
	public function ph_booking_form_validation(){
		
		$OrderIdError="";
		$step=1;
		$customer_id         = isset( $_REQUEST['ph_customer_id'] ) ? absint( $_POST['ph_customer_id'] ) : 0;
		$product_id 		 = absint( $_REQUEST['ph_filter_product_ids'] );
		$booking_order       = wc_clean( $_REQUEST['ph_booking_order'] );
		$step 				 =$_REQUEST['next_step'];
		if ( $booking_order  ==='existing' ) {

			$order_id = absint( $_REQUEST['ph_booking_order_id'] );
			
			if ( ! $order_id || get_post_type( $order_id ) !== 'shop_order' ) {
				$OrderIdError="Invalid Order ID";
					$step=1;
					
			}
		}
		$this->ph_generate_booking_form($step);
		
	}

	private function phive_buffer_before_time($from,$buffer_period,$book_interval,$buffer_before,$buffer_after='0'){
		
		
		switch($buffer_period){
				case 'day':
					$buffer_before_time=date('Y-m-d', (strtotime($from) - ($buffer_before*3600*24)));
					break;
				case 'hour':
					$buffer_before_time=date('Y-m-d H:i', (strtotime($from) -( $buffer_before*3600)));
					break;
				case 'minute':
					$buffer_after=isset($buffer_after)?$buffer_after:'00';
					$buffer_before_time=date('Y-m-d H:i', (strtotime($from) -( $buffer_before*60)));
					break;
			}
			return $buffer_before_time;

	}

	private function phive_buffer_after_time($from, $to='', $buffer_period='', $book_interval='', $buffer_before='0', $buffer_after='', $product_id=''){
		$interval = get_post_meta($product_id, "_phive_book_interval", 1);
		$interval_period = get_post_meta($product_id, '_phive_book_interval_period', 1);
		
		$to=!empty($to)?$to:$from;
		switch($buffer_period){
				case 'day':
					$buffer_after_time=date('Y-m-d', (strtotime($to) + ($buffer_after*3600*24)));
					break;
				case 'hour':
					$buffer_after_time=date('Y-m-d H:i', (strtotime($to) +($buffer_after*3600 )));
					;
					break;
				case 'minute':
					$buffer_before=isset($buffer_before)?$buffer_before:'00';
					$buffer_after_from = date("Y-m-d H:i", strtotime("+$interval $interval_period", strtotime($to)));
					$buffer_after_time=date('Y-m-d H:i', (strtotime($buffer_after_from) +($buffer_after*60 )));
					;
					break;
			}
			return $buffer_after_time;
	}

	private function phive_save_booking_buffer_info($product_id,$buffer_before_time,$buffer_after_time,$person_as_booking='',$number_of_booking='',$is_buffer='',$buffer_type='', $asset_id=''){
		$new_post = array(
			'ID' => '',
			'post_type' => 'booking_buffer_freez', // Custom Post Type Slug
			'post_status' => 'open',
			'post_title' => 'Booking buffer freezer',
			'ping_status' => 'closed',
		);

		$buffer_id = wp_insert_post($new_post);
		if( !$buffer_id ){
			return false;
		}
		if($is_buffer == 'yes' && $buffer_type == 'buffer-before'){
			$meta_values = array(
			'_product_id' 			=> $product_id,
			'Buffer_before_From'	=> $buffer_before_time,
			'Buffer_before_To'		=> $buffer_after_time,
			'_booking_customer_id'	=> is_user_logged_in() ? get_current_user_id() : 0,
			'Number of persons' 	=> $number_of_booking,
			'person_as_booking' 	=> $person_as_booking,
			'ph_canceled' 	=> '0',
			
		);
		}elseif($is_buffer == 'yes' && $buffer_type == 'buffer-after'){
			$meta_values = array(
			'_product_id' 			=> $product_id,
			'Buffer_after_From'		=> $buffer_before_time,
			'Buffer_after_To'		=> $buffer_after_time,
			'_booking_customer_id'	=> is_user_logged_in() ? get_current_user_id() : 0,
			'Number of persons' 	=> $number_of_booking,
			'person_as_booking' 	=> $person_as_booking,
			'ph_canceled' 	=> '0',
			
		);
		}
		// buffer not getting applied for other products with same asset with backend booking
		if($asset_id)
		{
			$meta_values['buffer_asset_id']	= $asset_id;	
		}
		foreach ( $meta_values as $meta_key => $value ) {
			update_post_meta( $buffer_id, $meta_key, $value );
		}
		
		return $buffer_id;
	}
	
	public function ph_booking_process_form_data(){
		 
		$customer_id         = absint( $_REQUEST['phive_customer_id'] );
		$product_id 		 = absint( $_REQUEST['phive_product_id'] );
		$booking_order       = wc_clean( $_REQUEST['ph_booking_order'] );
		$product             = wc_get_product( $product_id );
		$order_id            = 0;
		$item_id             = 0;
		$booking_cost 		 = $_REQUEST['phive_booked_price'];
		$buffer_before		 = get_post_meta( $product_id, "_phive_buffer_before", 1 );
		$buffer_after 		 = get_post_meta( $product_id, "_phive_buffer_after", 1 );
		$book_interval 		 = get_post_meta( $product_id, "_phive_book_interval", 1 );
		$buffer_period 		 = get_post_meta( $product_id, "_phive_buffer_period", 1 );
		$enable_buffer		 = get_post_meta( $product_id, '_phive_enable_buffer', 1);
		$persons_as_booking  = get_post_meta( $product_id, "_phive_booking_persons_as_booking", 1 );
		$interval 			 = get_post_meta( $product_id, "_phive_book_interval", 1 );
		$interval_period	 = get_post_meta( $product_id, '_phive_book_interval_period', 1 );

		if(empty($booking_cost)){
			$booking_cost = 0;
		}

		if ( $booking_order  =='new') {
			$order = new WC_Order();
			$order->set_customer_id( $customer_id );
			$order->set_total( $booking_cost  );
			$order_id = $order->save();
			
		} elseif ( $booking_order =="existing" ) {
			$order_id = absint( $_REQUEST['ph_bookable_product_id'] );
			$order = new WC_Order( $order_id );

			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				update_post_meta( $order_id, '_order_total', $order->get_total() + $booking_cost );
			} else {
				$order->set_total( $order->get_total( 'edit' ) + $booking_cost );
				$order->save();
			}
			$order->update_status( 'pending' );
			
		}
		if ( $order_id ) {
			$item_id  = wc_add_order_item( $order_id, array(
				'order_item_name' => $product->get_title(),
				'order_item_type' => 'line_item',
			) );

			// allow guest bookings
			if ( (! empty( $customer_id )) || ($customer_id == 0)) {
				
				$order = wc_get_order( $order_id );
				$cron_manager = new phive_booking_availability_scheduler();
				$cart_item['phive_book_from_date'] 					= sanitize_text_field( $_REQUEST['phive_book_from_date'] );
				$cart_item['phive_book_to_date'] 					= sanitize_text_field( $_REQUEST['phive_book_to_date'] );
				$cart_item['phive_booked_price'] 					= sanitize_text_field( $_REQUEST['phive_booked_price'] );
				
				// 144100 - Translation function was applied on an array, giving fatal error in php8
				$cart_item['phive_booked_persons'] 					= isset($_REQUEST['phive_book_persons']) ? $_REQUEST['phive_book_persons'] : '';
				$cart_item['phive_booked_resources'] 				= isset($_REQUEST['phive_book_resources']) ? $_REQUEST['phive_book_resources'] : '';

				$cart_item['phive_book_additional_notes_text'] 		= isset($_REQUEST['phive_book_additional_notes_text']) ? __($_REQUEST['phive_book_additional_notes_text'],'bookings-and-appointments-for-woocommerce') : '';
				$cart_item['product_id'] 							= $product_id;
				$cart_item['phive_booked_assets']	= isset($_REQUEST['phive_book_assets']) ? $_REQUEST['phive_book_assets'] : '';
				
				if (( $booking_order=='new' ))
				{
					$keys  = array(
						'first_name',
						'last_name',
						'company',
						'address_1',
						'address_2',
						'city',
						'state',
						'postcode',
						'country',
						'phone'
					);

					$types = array( 'shipping', 'billing' );

					foreach ( $types as $type ) {
						$address = array();

						foreach ( $keys as $key ) 
						{
							$address[ $key ] = (string) get_user_meta( $customer_id, $type . '_' . $key, true );

						}
						$order->set_address( $address, $type );
					}
				}
			}
			$values=$cart_item;
			
			$product_interval_details = array(
				'interval'			=>	$interval,
				'interval_format'	=>	$interval_period
			);
			wc_add_order_item_meta( $item_id,'_phive_booking_product_interval_details', $product_interval_details );

			$participant_booking_data = array();
			if( array_key_exists('phive_booked_persons', $values) ){

				$persons_pricing_rules  = get_post_meta( $product_id, "_phive_booking_persons_pricing_rules", 1 );
				$number_of_persons 		= 0;
				// Looping through the rule and assign the corresponding rule value given by customer
				foreach ($persons_pricing_rules as $key => $rule) {
					
					if( empty($rule) ){
						continue;
					}

					if( !empty($values['phive_booked_persons'][$key]) ){
						$participant_booking_data[] = array(
							'participant_label' => $rule['ph_booking_persons_rule_type'],
							'participant_count' => $values['phive_booked_persons'][$key]
						);
						$number_of_persons += $values['phive_booked_persons'][$key];
						wc_add_order_item_meta( $item_id,$rule['ph_booking_persons_rule_type'],$values['phive_booked_persons'][$key] );
					}
				
				}
				
				if( !empty($number_of_persons) ){
					wc_add_order_item_meta( $item_id, 'Number of persons',$number_of_persons );
				}

				// error_log('participant_booking_data : '.print_r($participant_booking_data,1));
				if (count($participant_booking_data) > 0) 
				{
					wc_add_order_item_meta($item_id, 'ph_bookings_participant_booking_data', $participant_booking_data);
				}
			}
			// Display Additional Notes
			if( array_key_exists('phive_book_additional_notes_text', $values) ){
				
				$addition_notes_label=get_post_meta($product_id,'_phive_additional_notes_label', 1 );
				// Looping through the rule and assign the corresponding rule value given by customer
				

					if( !empty($values['phive_book_additional_notes_text']) ){
						$additional_notes_text = $values['phive_book_additional_notes_text'];
						wc_add_order_item_meta( $item_id,$addition_notes_label,$values['phive_book_additional_notes_text'] );
					}
				
			}

			//Disply resources details with items
			if( array_key_exists('phive_booked_resources', $values) ){
			
				$resources_pricing_rules 							= get_post_meta( $product_id, "_phive_booking_resources_pricing_rules", 1 );
				
				$resources_type 			= get_post_meta( $product_id, "_phive_booking_resources_type", 1 );
				// Looping through the rule and assign the corresponding rule value given by customer
				foreach ($resources_pricing_rules as $key => $rule) {
					//107352 - Removed condition check which was skipping the execution when auto-assign enabled and resource type set to multiple
					if( $rule['ph_booking_resources_auto_assign']=='yes' && $resources_type!='single' ){
						// continue;
					}
					if($resources_type=='single')
					{
						if($values['phive_booked_resources'] == $rule['ph_booking_resources_name']){
							wc_add_order_item_meta( $item_id,$rule['ph_booking_resources_name'],'yes' );
						}
					}
					else{

						if( isset($values['phive_booked_resources'][$key]) ){
							wc_add_order_item_meta( $item_id,$rule['ph_booking_resources_name'],$values['phive_booked_resources'][$key] );
						}
					}
					
				}
			}

			//Disply Assets details with items
			if( array_key_exists('phive_booked_assets', $values) && !empty($values['phive_booked_assets']) ){
			
				$asset_settings 			= get_option( 'ph_booking_settings_assets', 1 );
				$this->assets_rules 		= $asset_settings['_phive_booking_assets'];
				$asset_label 				= get_post_meta( $product_id,'_phive_booking_assets_label');
				$asset_label 				= empty($asset_label[0]) ? 'Type' : $asset_label[0];
				$asset_name = $this->assets_rules[ $values['phive_booked_assets'] ]['ph_booking_asset_name'];
				wc_add_order_item_meta( $item_id, 'Assets',array($values['phive_booked_assets']) );
				wc_add_order_item_meta( $item_id, $asset_label,$asset_name );
				
				/*$resources_pricing_rules 							= get_post_meta( $product_id, "_phive_booking_assets_pricing_rules", 1 );
				
				// Looping through the rule and assign the corresponding rule value given by customer
				foreach ($resources_pricing_rules as $key => $rule) {
					
					//if( $rule['ph_booking_resources_auto_assign']=='yes' ){
					//	continue;
					//}

					if( isset($values['phive_booked_assets'][$key]) ){
						// wc_add_order_item_meta( $item_id,$rule['ph_booking_asset_id'],$values['phive_booked_assets'][$key] );


					}
					
				}*/
			}

			if(array_key_exists('phive_book_from_date', $values)){
				wc_add_order_item_meta( $item_id,'From',array($values['phive_book_from_date']));
				wc_add_order_item_meta( $item_id,__('Booked From','bookings-and-appointments-for-woocommerce'),Ph_Bookings_General_Functions_Class::phive_get_date_in_wp_format($values['phive_book_from_date']));
			}

			if(array_key_exists('phive_book_to_date', $values)){
				wc_add_order_item_meta( $item_id,'To',array($values['phive_book_to_date']));
				$phive_booked_to_date=$values['phive_book_to_date'];
				if( ($interval_period == 'minute') || ($interval_period == 'hour') ){
					$phive_booked_to_date =  date( 'Y-m-d H:i', strtotime( "+$interval $interval_period",strtotime($values['phive_book_to_date']) ) );
				}
				else{
					if( $interval_period == 'day' ) {
						$enable_per_night = get_post_meta( $product_id, '_phive_book_charge_per_night', true );
						if( $enable_per_night == 'yes' ) {
							$book_to_date = date_create($values['phive_book_to_date']);
							$book_to_date->modify("-1 days");
							wc_update_order_item_meta($item_id, '_ph_book_to_date_with_night', array($book_to_date->format('Y-m-d')) );
						}
					}
				}
				wc_add_order_item_meta( $item_id,__('Booked To','bookings-and-appointments-for-woocommerce'),Ph_Bookings_General_Functions_Class::phive_get_date_in_wp_format($phive_booked_to_date));
			}

			if(array_key_exists('phive_booked_price', $values)){
				wc_add_order_item_meta( $item_id,'Cost',$values['phive_booked_price']);
			}
			if($persons_as_booking == 'yes'){

			wc_add_order_item_meta( $item_id,'person_as_booking',array('yes') );
			
			}
			
			$required_confirmation = get_post_meta( $cart_item['product_id'], '_phive_book_required_confirmation', 1 );
			
				if( $required_confirmation==='yes' ){
					$is_confirm_required = 'true';
					}
					else{
					$is_confirm_required = 'false';	
					}
			
			if( $required_confirmation== 'true'){
				$booking_status = 'requires-confirmation';
			}else{
				$booking_status = 'un-paid';
			}

			$booking_status_name = array(
				'paid'					=>	__( 'Paid', 'bookings-and-appointments-for-woocommerce' ),
				'un-paid'				=>	__( 'Unpaid', 'bookings-and-appointments-for-woocommerce' ),
				'canceled'				=>	__( 'Cancelled', 'bookings-and-appointments-for-woocommerce' ),
				'requires-confirmation'	=>	__( 'Requires Confirmation', 'bookings-and-appointments-for-woocommerce' )
			);

			if($enable_buffer=='yes'){	
				$buffer_before_from 		= $this->phive_buffer_before_time($values['phive_book_from_date'],$buffer_period,$book_interval,$buffer_before,$buffer_after);

				$buffer_after_to 			= $this->phive_buffer_after_time($values['phive_book_from_date'],$values['phive_book_to_date'],$buffer_period,$book_interval,$buffer_before,$buffer_after, $product_id);
				switch($interval_period){
					case 'day':
							//Ticket-133368-buffer_time is not blocking correctly.
							$buffer_after_from 	= date ( "Y-m-d", strtotime( "+1 day", strtotime($values['phive_book_to_date']) ) );
					
							$buffer_before_to 		= date ( "Y-m-d", strtotime( "-1 day", strtotime($values['phive_book_from_date']) ) );
						break;
					 case 'hour':
					 			$buffer_after_from 	= date ( "Y-m-d H:i", strtotime( "+$interval $interval_period", strtotime($values['phive_book_to_date']) ) );
					
								$buffer_before_to 		= date ( "Y-m-d H:i", strtotime( "-$interval $interval_period", strtotime($values['phive_book_from_date']) ) );
					 			break;
					 case 'minute':
					 			$buffer_after_from 	= date ( "Y-m-d H:i", strtotime( "+$interval $interval_period", strtotime($values['phive_book_to_date']) ) );
					
								$buffer_before_to 		= date ( "Y-m-d H:i", strtotime( "-$interval $interval_period", strtotime($values['phive_book_from_date']) ) );
					 			break;
				}
				
				$asset_id = '';
				if ((isset($cart_item['phive_booked_assets'])) && (!empty($cart_item['phive_booked_assets'])))
				{
					$asset_id = $cart_item['phive_booked_assets'];
				}
				
				if($buffer_before_from == ''){
					$buffer_before_to ='';
				}
				if($buffer_after_to == ''){
					$buffer_after_from = '';
				}	
				if($persons_as_booking == 'yes'){
					$buffer_before_id 					= $this->phive_save_booking_buffer_info($product_id,$buffer_before_from,$buffer_before_to,array('yes'),$number_of_persons,'yes','buffer-before', $asset_id);
					$buffer_after_id 					= $this->phive_save_booking_buffer_info($product_id,$buffer_after_from,$buffer_after_to,array('yes'),$number_of_persons,'yes','buffer-after', $asset_id);
				}
				else{
					//Ticket-133376-buffer_time before booking is not blocking correctly.
					$buffer_before_id 					= $this->phive_save_booking_buffer_info($product_id,$buffer_before_from,$buffer_before_to,'','','yes','buffer-before', $asset_id);
					$buffer_after_id 					= $this->phive_save_booking_buffer_info($product_id,$buffer_after_from,$buffer_after_to,'','','yes','buffer-after', $asset_id);
				}
				
				$buffer_before_ids = array("$buffer_before_id");
				$buffer_after_ids = array("$buffer_after_id");
			
				wc_add_order_item_meta($item_id, 'buffer_before_id',$buffer_before_ids );
				wc_add_order_item_meta($item_id, 'buffer_after_id',$buffer_after_ids );
					
			}

			$taxable = 'taxable' === $product->get_tax_status();
			// Add line item meta
			$tax_item_id = '';
			if ( wc_tax_enabled() && $taxable) 
			{
				global $woocommerce;
				$countries   = new WC_Countries();
				$base_country = WC()->countries->get_base_country();

				$order_taxes      = $order->get_taxes();
				$tax_classes      = WC_Tax::get_tax_classes();
				$classes_options  = wc_get_product_tax_class_options();
				$show_tax_columns = count( $order_taxes ) === 1;

				$tax_slug = WC_Tax::get_tax_class_slugs();

				$incl_or_excl = get_option('woocommerce_prices_include_tax');

				$tax_based_on = get_option( 'woocommerce_tax_based_on' );

				$arrayof = array();
				$arrayof['data'] = wc_get_product($product_id);
				$tax_class = $arrayof['data']->get_tax_class();
				$taxable   = 'taxable' === $arrayof['data']->get_tax_status();
				$price_includes_tax      = wc_prices_include_tax();
				$price = $booking_cost;
				$customer = $order->get_user();

				$tax_rates               = $this->get_item_tax_rates( $tax_class, $customer, $order);
				$label = '';
				$rate_id = '';
				$rate = '';
				foreach ( $tax_rates as $key => $rate ) 
				{
					$rate_id = $key;
					$label = $tax_rates[$key]['label'];
					$rate = $tax_rates[$key]['rate'];
					break;
				}
				$total_taxes     = WC_Tax::calc_tax( $price, $tax_rates, $price_includes_tax );

				if ( $price_includes_tax ) {
					// Use unrounded taxes so we can re-calculate from the orders screen accurately later.
					$price = $price - array_sum( $total_taxes );
					$booking_cost = $price;
					$price_includes_tax = 'yes';
				}
				else 
				{
					$price_includes_tax = 'no';
				}

				if (!empty($label) && !empty($rate_id)) 
				{
					$array_sum_of_total_tax = array_sum( array_values( $total_taxes));
					$line_tax_data = array(
						'total' => array("$rate_id" => "$array_sum_of_total_tax"),
						'subtotal' => array("$rate_id" => "$array_sum_of_total_tax"),
					);
					
					if ( $booking_order =="existing" )
					{
						foreach ( $order_taxes as $id => $tax_item ) 
						{
							$old_total_tax = wc_get_order_item_meta($id, 'tax_amount', 1);
							$new_total_tax = $old_total_tax + $array_sum_of_total_tax;
							wc_update_order_item_meta($id, 'tax_amount', $new_total_tax);
							
							if($price_includes_tax == 'no')
							{
								$order_total = get_post_meta($order_id, '_order_total', 1);
								update_post_meta($order_id, '_order_total', ($order_total + $array_sum_of_total_tax));
							}
						}
					}
					else
					{
						$tax_item_id  = wc_add_order_item( $order_id, array(
							'order_item_name' => $label,
							'order_item_type' => 'tax',
						) );
		
						wc_add_order_item_meta($tax_item_id, 'label', $label);
						wc_add_order_item_meta($tax_item_id, 'rate_id', $rate_id);
						wc_add_order_item_meta($tax_item_id, 'rate_percent', $rate);
						wc_add_order_item_meta($tax_item_id, 'tax_amount', $array_sum_of_total_tax);
						wc_add_order_item_meta($tax_item_id, 'shipping_tax_amount', 0);
						wc_add_order_item_meta($tax_item_id, 'compound', '');
						update_post_meta($order_id, '_order_total', ($booking_cost + $array_sum_of_total_tax));
					}
					
					wc_add_order_item_meta($item_id, '_line_tax', $array_sum_of_total_tax);
					wc_add_order_item_meta($item_id, '_line_subtotal_tax', $array_sum_of_total_tax);
					wc_add_order_item_meta($item_id,'_line_tax_data', $line_tax_data);
					
					wc_update_order_item_meta( $item_id,'Cost', (array)$booking_cost);
	
					// $order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
					update_post_meta($order_id, '_prices_include_tax', ($price_includes_tax));
				}
				
			}

			wc_add_order_item_meta( $item_id, '_line_total', $booking_cost );
			wc_add_order_item_meta( $item_id, '_line_subtotal', $booking_cost );
			wc_add_order_item_meta( $item_id, '_qty', 1 );
			wc_add_order_item_meta( $item_id, '_tax_class', $product->get_tax_class() );

			wc_add_order_item_meta( $item_id, '_product_id', $product->get_id() );
			wc_update_order_item_meta( $item_id, 'booking_status', array($booking_status) );
			wc_update_order_item_meta( $item_id, __('Booking Status','bookings-and-appointments-for-woocommerce'), __( $booking_status_name[$booking_status], 'bookings-and-appointments-for-woocommerce' ) );
			do_action('ph_booking_google_calender_sync_for_admin_bookings',$order_id);
			// for product addon plugin
			do_action('ph_product_addon_add_to_order',$order_id, $product_id, $_REQUEST, $item_id);

			// for ph-deposits plugin to calculate deposit from admin booking
			do_action('ph_add_additional_order_item_meta_for_admin_bookings', $item_id, $order_id, $cart_item,$product->get_id(), $tax_item_id, $order);
			
			if ((isset($cart_item['phive_booked_assets'])) && (!empty($cart_item['phive_booked_assets'])))
			{
				// error_log("asset id : ".$cart_item['phive_booked_assets']);
				$asset_id = $cart_item['phive_booked_assets'];
				if (!empty($asset_id)) 
				{
					$ph_cache_obj = new phive_booking_cache_manager();
					$ph_cache_obj->ph_unset_cache($asset_id);
				}
			}
		}
		
		$order = wc_get_order( $order_id );
		
		$send_payment_email 		 = isset($_REQUEST['send_payment_email'])?$_REQUEST['send_payment_email']:'';
		$this->send_payment_link_email($order_id,$order,$send_payment_email, $customer_id);
		wp_safe_redirect( admin_url( 'post.php?post=' .  $order_id . '&action=edit' ) );
		exit;

	}


	protected function get_item_tax_rates( $tax_class, $customer, $order) {
		if ( ! wc_tax_enabled() ) {
			return array();
		}
		$item_tax_rates = $this->get_rates( $tax_class, $customer, $order);

		return $item_tax_rates;
		// Allow plugins to filter item tax rates.
		// return apply_filters( 'woocommerce_cart_totals_get_item_tax_rates', $item_tax_rates, $item, $this->cart );
	}

	public static function get_rates( $tax_class = '', $customer = null, $order='' ) {
		$tax_class         = sanitize_title( $tax_class );
		$location          = self::get_tax_location( $tax_class, $customer, $order);
		$matched_tax_rates = array();
		if ( count( $location ) === 4 ) {
			list( $country, $state, $postcode, $city ) = $location;

			$matched_tax_rates = WC_Tax::find_rates(
				array(
					'country'   => $country,
					'state'     => $state,
					'postcode'  => $postcode,
					'city'      => $city,
					'tax_class' => $tax_class,
				)
			);
		}

		// return apply_filters( 'woocommerce_matched_rates', $matched_tax_rates, $tax_class );
		return $matched_tax_rates;
	}

	public static function get_tax_location( $tax_class = '', $customer = null, $order='') {
		$location = array();
		if ( is_null( $customer ) && WC()->customer ) {
			$customer = WC()->customer;
		}
		$countries   = new WC_Countries();

		$tax_based_on = get_option( 'woocommerce_tax_based_on' );

		if ( ! empty( $customer ) ) 
		{
			// $location = $customer->get_taxable_address();
			// $location = self::get_taxable_address($customer, $countries);
			$tax_based_on = get_option( 'woocommerce_tax_based_on' );

			if ( 'shipping' === $tax_based_on ) 
			{
				$address = $order->get_address('shipping');
				$country  = $address['country'];
				if(!$country)
				{
					$tax_based_on = 'billing';
				}
			}

			if ( 'base' === $tax_based_on ) {
				$country  = WC()->countries->get_base_country();
				$state    = WC()->countries->get_base_state();
				$postcode = WC()->countries->get_base_postcode();
				$city     = WC()->countries->get_base_city();
			} 
			elseif ( 'billing' === $tax_based_on ) 
			{
				$address = $order->get_address('billing');

				$country  = $address['country'];
				$state    = $address['state'];
				$postcode = $address['postcode'];
				$city     = $address['city'];
			} 
			else 
			{
				$address = $order->get_address('shipping');

				$country  = $address['country'];
				$state    = $address['state'];
				$postcode = $address['postcode'];
				$city     = $address['city'];
			}
			$location = array($country, $state, $postcode, $city);
		} 
		else if ( wc_prices_include_tax() || 'base' === get_option( 'woocommerce_default_customer_address' ) || 'base' === get_option( 'woocommerce_tax_based_on' ) ) {
			$location = array(
				WC()->countries->get_base_country(),
				WC()->countries->get_base_state(),
				WC()->countries->get_base_postcode(),
				WC()->countries->get_base_city(),
			);
		}

		// return apply_filters( 'woocommerce_get_tax_location', $location, $tax_class, $customer );
		return $location;
	}

	public function get_taxable_address($customer, $countries) {
        $tax_based_on = get_option( 'woocommerce_tax_based_on' );

        if ( 'base' === $tax_based_on ) {
            $country  = WC()->countries->get_base_country();
            $state    = WC()->countries->get_base_state();
            $postcode = WC()->countries->get_base_postcode();
            $city     = WC()->countries->get_base_city();
        } elseif ( 'billing' === $tax_based_on ) {
            $country  = $customer->get_billing_country();
            $state    = $customer->get_billing_state();
            $postcode = $customer->get_billing_postcode();
            $city     = $customer->get_billing_city();
        } else {
            $country  = $customer->get_shipping_country();
            $state    = $customer->get_shipping_state();
            $postcode = $customer->get_shipping_postcode();
            $city     = $customer->get_shipping_city();
        }

		// return apply_filters( 'woocommerce_customer_taxable_address', array( $country, $state, $postcode, $city ) );
		return array( $country, $state, $postcode, $city );
	}
	
	public function send_payment_link_email($order_id,$order,$send_email,$customer_id)
	{
		$from_email = get_user_meta( $customer_id,  'billing_email', true );
		if($send_email && !empty($from_email))
		{
			$header = array(
				"Content-Type: text/html; charset=UTF-8"
			);
			$from_name		= get_option( 'woocommerce_email_from_name');
			$from_address	= get_option( 'woocommerce_email_from_address' );
			$header[]		= "From : ".wp_specialchars_decode( esc_html($from_name), ENT_QUOTES )." <$from_address>";
			$subject		= $this->get_email_subject();
			wp_mail( $from_email, $subject, $this->get_payment_email_content($order_id,$order), $header );
		}
	}
	/**
	 * Get Email Subject.
	 * @return string
	 */
	public function get_email_subject() {
		$subject = null;
		$this->blog_name		= get_option('blogname');
		$blog_name=wp_specialchars_decode( $this->blog_name, ENT_QUOTES );
		$subject = sprintf( __( 'Your Booking Is Placed Successfully', 'bookings-and-appointments-for-woocommerce' ), $blog_name );
		return $subject;
	}
	public function get_payment_email_content($order_id,$order)
	{
		// Load colors.
		$this->email_bg_color				= get_option( 'woocommerce_email_background_color' );
		$this->email_body_bg_color			= get_option( 'woocommerce_email_body_background_color' );
		$this->email_base_color				= get_option( 'woocommerce_email_base_color' );
		$this->email_text_color				= get_option( 'woocommerce_email_text_color' );
		$this->wp_date_format				= get_option( 'date_format' );
		$content = null;
		// Style
		$content .= "<body style='background-color:$this->email_body_bg_color;'>";
		$content .= "<div style='padding:9% 9%;'>";
		// Heading
		$content .= "<div style='background-color:$this->email_base_color;color:black;padding:20px 30px;font-size:30px;font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;'>";
		
		$this->billing_address = $order->get_address();
		$content .= __( 'Dear', 'bookings-and-appointments-for-woocommerce' ).' '. $this->billing_address['first_name'];
		$content .= "</div>";

		
		$content .= "<br><br>".__( "Thanks for placing your booking. ",'bookings-and-appointments-for-woocommerce');
		$content .= "<br>".__( "We request you to go through the booking details attached in this email and click on the link below to proceed with the payment ",'bookings-and-appointments-for-woocommerce')."</br><small><a href='".$order->get_checkout_payment_url()."' target='_blank' >".$order->get_checkout_payment_url().'</a></small>';
		$content .= "<br><br><span style='color:$this->email_base_color;font-size:20px;'>".sprintf( '['.__( 'Order','bookings-and-appointments-for-woocommerce').' #%s] (%s)', $order->get_order_number(), ph_wp_date($this->wp_date_format) )."</span><br><br>";
		// Product details
		$content .= $this->get_product_info_as_table($order_id,$order);
		// Address
		$content .= $this->get_address($order_id,$order);
		$content .= "</div>";
		$content .= "</div>";
		$content .= "</body>";
		return $content;
	}
	/**
	 * Get bookings product info as table.
	 */
	private function get_product_info_as_table($order_id,$order) {
		$order_items = $order->get_items();
		$content = null;
		$table_td_style = "style='border: 1px solid #dddddd; padding:10px;font-size:17px;'";
		$table_td_title_style = "style='border: 1px solid #dddddd; padding:10px;font-size:20px;'";
		if( ! empty($order_items) ) {
			$content .= "<table  style='border-collapse:collapse; width:100%;'";
				$content .= "<tr>
								<td $table_td_title_style>".__( 'Product', 'bookings-and-appointments-for-woocommerce')."</th>
								<td $table_td_title_style>".__( 'Price', 'bookings-and-appointments-for-woocommerce')."</th>
							</tr>";
				foreach( $order_items as $order_item ) {
					$product 	= $order_item->get_product();
					if( empty($product) || $product->get_type() !='phive_booking' ){
						continue;
					}
					$price = round($order_item->get_total(),2);
					if(wc_tax_enabled())
					{
						$item_data = $order_item->get_data();
						$item_total_tax = isset($item_data['total_tax']) ? $item_data['total_tax'] : 0;
						$price += round($item_total_tax,2);
					} 					
					$price = apply_filters('ph_bookings_admin_order_payment_link_email_price', wc_price($price), $order_item, $order);
					$content .= "<tr>
									<td $table_td_style>".$order_item->get_name().$this->get_order_item_meta_data($order_item)."</td>".
									"<td $table_td_style>".$price."</td>
								</tr>";
				}
			$content .= "</table>";
		}
		return $content;
	}

	/**
	 * Get Meta data of Line Items that needs to be sent in email.
	 * @return string in the form of Unordered list
	 */
	private function get_order_item_meta_data( $order_item ) {
		$content = null;
		$meta_datas = $order_item->get_meta_data();
		$product = $order_item->get_product();
		$key_filter=apply_filters('ph_bookings_order_meta_key_filters',array('confirmed','canceled'), $order_item);
		foreach( $meta_datas as $meta_data ) {
			$meta_data = $meta_data->get_data();
			if( ! empty($meta_data['value']) && ! is_array($meta_data['value']) ) {
				if( in_array($meta_data['key'], $key_filter) )	continue;
				$meta_data['key'] = apply_filters( 'woocommerce_attribute_label', $meta_data['key'], $meta_data['key'], $product);
				$content .= "<li>".__($meta_data['key'], 'bookings-and-appointments-for-woocommerce').": ".__($meta_data['value'], 'bookings-and-appointments-for-woocommerce')."</li>";
			}
		}

		if( ! empty($content) ) {
			$content = "<ul style='padding:10px;font-size:12px;'>".$content."</ul>";
		}
		return $content;
	}

	/**
	 * Get Address as html
	 */
	private function get_address($order_id,$order) {
		$billing_address  = $order->get_address();
		$shipping_address = $order->get_address('shipping');
		$content = "<br><table style='width:100%;border-collapse:collapse;'>
						<tr>
							<td style='color:$this->email_base_color; font-size:20px; padding:10px 0px;'>".
								__( 'Billing address', 'bookings-and-appointments-for-woocommerce')."
							</td>";
		if( ! empty($shipping_address) ) {
			$content .= "<td style='color:$this->email_base_color; font-size:20px; padding:10px 0px;'>".__( 'Shipping address', 'bookings-and-appointments-for-woocommerce' )."</td>";
		}

		$content .= "</tr><tr>
						<td style='border: 1px solid #dddddd; padding:10px;font-size:15px;'>".
							$billing_address['company']."<br>".
							$billing_address['first_name']." ".$billing_address['last_name']."<br>".
							$billing_address['address_1']."<br>".
							$billing_address['address_2']."<br>".
							$billing_address['city']." ".$billing_address['state']." ".$billing_address['postcode']."<br>".
							$billing_address['country']."<br>".
							$billing_address['phone']."<br>".
							$billing_address['email'].
						"</td>";
		if( ! empty($shipping_address) ) {
			$content .= "<td style='border: 1px solid #dddddd; padding:10px;font-size:15px;'>".
								$shipping_address['company']."<br>".
								$shipping_address['first_name']." ".$shipping_address['last_name']."<br>".
								$shipping_address['address_1']."<br>".
								$shipping_address['address_2']."<br>".
								$shipping_address['city']." ".$shipping_address['state']." ".$shipping_address['postcode']."<br>".
								$shipping_address['country']."<br>"."
						</td>";
		}
		$content .= "</tr></table><br>";
		return $content;
	}

}
