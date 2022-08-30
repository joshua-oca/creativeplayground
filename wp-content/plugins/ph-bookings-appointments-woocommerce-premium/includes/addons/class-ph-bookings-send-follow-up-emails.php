<?php

if( ! class_exists('Ph_Bookings_Send_Follow_Up_Emails') ) {
	class Ph_Bookings_Send_Follow_Up_Emails {

		public function __construct() {
						
			$email_content = "Hi [CUSTOMER_NAME],<br><br>";
			$email_content .="Thank you for booking with [SITE_NAME].<br>";
			$email_content .="Hope you enjoyed our services. <br>";
			$email_content .="We look forward to serving you again.<br><br>";
			$email_content .="With Regards,<br>";
			$email_content .="Admin";
			$settings 		= get_option( 'ph_booking_follow_up_email', array() );
			$this->enabled			= ! empty($settings['followup_email_enabled']) ? $settings['followup_email_enabled'] : false;
			$this->email_subject	= ! empty($settings['followup_email_subject']) ? $settings['followup_email_subject'] : 'Thanks for Booking with Us..!';
			$this->email_content	= ! empty($settings['followup_email_content']) ? $settings['followup_email_content'] : $email_content;
			$this->followup_time	= ! empty($settings['followup_email_followup_time']) ? $settings['followup_email_followup_time'] : 24;
			
			
			
			// add_filter( 'cron_schedules', array($this,'ph_bookings_follow_up_email_cron' ));
			add_action( 'ph_bookings_follow_up_email_cron', array( $this, 'ph_bookings_follow_up_email_cron_func_run') );
			add_filter( 'cron_schedules', array($this,'ph_bookings_follow_up_email_cron' ));

			// add_action( 'ph_bookings_follow_up_email_cron', array($this,'ph_bookings_follow_up_email_cron_func' ));
			$this->site_title = wp_specialchars_decode( get_option('blogname'), ENT_QUOTES );
		}

		public function ph_bookings_follow_up_email_cron( $schedules ) {
			$import_interval=1;
		    $schedules['booking_follow_up_interval'] = array(
		            'interval'  => (int) $import_interval * 60 ,
		            'display'   => sprintf(__('Every %d minutes', 'bookings-and-appointments-for-woocommerce'), (int) $import_interval)
		    );
		    return $schedules;
		}
		function ph_bookings_follow_up_email_cron_func_run() {
			$followup_email_settings 		= get_option( 'ph_booking_follow_up_email', array() );
			$followup_email_enabled			= ! empty($followup_email_settings['followup_email_enabled']) ? $followup_email_settings['followup_email_enabled'] : false;
			if($followup_email_enabled)
			{
				$time_offset = get_option('gmt_offset');
				$current_time=date('Y-m-d H:i');
				$current_time=date('Y-m-d H:i:00',strtotime($current_time)+$time_offset*60*60);
				$this->bookings=$this->ph_get_bookings($current_time);
				$this->bookings = $this->remove_previously_send_bookings( $this->bookings);
				$this->send_email( $this->bookings );
			}
		}
		/**
		 * Remove the Bookings which already send.
		 * @param array $bookings Bookings
		 * @param object $date_to_select
		 */
		public function remove_previously_send_bookings( $bookings ) {
			
			foreach( $bookings as $key => $booking ) {

				// Don't include Cancelled Bookings
				if( !empty($booking['FollowUpStatus'])) {
					$FollowUpStatus 			= ph_maybe_unserialize($booking['FollowUpStatus']);
					if($FollowUpStatus)
					{		
						unset($bookings[$key]);
						// error_log('status is true');
						continue;
					}
				}
			}
			return $bookings;
		}

		/**
		 * Send Email for the Bookings
		 * @param array $bookings Array of Bookings for which email need to be sent
		 */
		public function send_email( $bookings = array() )
		{
			// error_log('follow up send email');
			$header = array(
				"Content-Type: text/html; charset=UTF-8"
			);
			$from_name		= get_option( 'woocommerce_email_from_name');
			$from_address	= get_option( 'woocommerce_email_from_address' );
			$header[]		= "From : ".wp_specialchars_decode( esc_html($from_name), ENT_QUOTES )." <$from_address>";
			// $subject		= $this->get_email_subject();
			foreach( $bookings as $booking ) {
				$order_id = $booking['order_id'];
				$product_id = $booking['product_id'];

				$order_language = get_post_meta($order_id, 'wpml_language', 1);

				$subject = $this->get_email_subject();
				$subject = apply_filters('wpml_translate_single_string', $subject, 'bookings-and-appointments-for-woocommerce', 'followup_email_subject_translation', $order_language);
				
				$email_status=wp_mail( $booking['billing_email'], $subject, $this->get_email_content($booking, $order_language), $header );
				if($email_status)
				{
					$order_item_id=$booking['ID'];
					wc_add_order_item_meta( $order_item_id, 'FollowUpStatus', array(true));
				}
			}
		}

		/**
		 * Get Email Subject.
		 * @return string Email Subject.
		 */
		private function get_email_subject() {
			$this->email_subject = __($this->email_subject, 'bookings-and-appointments-for-woocommerce');
			return $this->email_subject;
			// return "subject";
		}

		/**
		 * Get Email Content
		 * @param array $booking Booking
		 * @return string Email Content / Message.
		 */
		public function get_email_content( $booking, $order_language = null ) {
			$this->email_content	= str_replace( "\\","",$this->email_content);

			$this->email_content = __($this->email_content, 'bookings-and-appointments-for-woocommerce');
			$email_content = apply_filters('wpml_translate_single_string', $this->email_content, 'bookings-and-appointments-for-woocommerce', 'followup_email_content_translation', $order_language);
			
			$email_content = str_replace( array( PHP_EOL, '[CUSTOMER_NAME]', '[SITE_NAME]'), array( "<br>", $booking['bookedby'], $this->site_title), $email_content );
			$email_content = apply_filters('ph_display_booking_code_follow_up', $email_content, $booking);
			return $email_content;
		}

		/**
		 * Get Bookings.
		 * @param array $filters Bookings Filters
		 */
		private function ph_get_bookings( $follow_up_time ){
			$follow_up_times=array("'".date('Y-m-d H:i:s',strtotime('-1 minutes',strtotime($follow_up_time)))."'",
									"'".$follow_up_time."'",
									"'".date('Y-m-d H:i:s',strtotime('+1 minutes',strtotime($follow_up_time)))."'"
							);
			global $wpdb;
			$query = "SELECT oitems.order_id, oitems.order_item_id,tr.object_id product_id,ometa.customer_name, ometa.billing_email, imeta.BookingStatus, imeta.BookFrom, imeta.BookTo, imeta.IntervalDetails,imeta.FollowUpStatus
			FROM {$wpdb->prefix}posts
			INNER JOIN (
					SELECT
					post_id,
					MAX(CASE WHEN meta_key = '_billing_email' THEN meta_value ELSE '' END) AS billing_email,
					MAX(CASE WHEN meta_key = '_billing_first_name' THEN meta_value ELSE '' END) AS customer_name
					FROM {$wpdb->prefix}postmeta
					GROUP BY post_id
			) as ometa on ometa.post_id = {$wpdb->prefix}posts.ID
			INNER JOIN {$wpdb->prefix}woocommerce_order_items oitems on oitems.order_id = {$wpdb->prefix}posts.ID
			INNER JOIN (
					SELECT
					order_item_id,
					MAX(CASE WHEN meta_key = '_product_id' THEN meta_value ELSE '' END) AS ProductId,
					MAX(CASE WHEN meta_key = 'booking_status' THEN meta_value ELSE '' END) AS BookingStatus,
					MAX(CASE WHEN meta_key = 'From' THEN meta_value ELSE '' END) AS BookFrom,
					MAX(CASE WHEN meta_key = 'To' THEN meta_value ELSE '' END) AS BookTo,
					-- MAX(CASE WHEN meta_key = 'FollowUpTime' THEN meta_value ELSE '' END) AS FollowUpTime,
					MAX(CASE WHEN meta_key = '_phive_booking_product_interval_details' THEN meta_value ELSE '' END) AS IntervalDetails,
					MAX(CASE WHEN meta_key = 'FollowUpStatus' THEN meta_value ELSE '' END) AS FollowUpStatus
					FROM {$wpdb->prefix}woocommerce_order_itemmeta
					GROUP BY order_item_id
			) as imeta on  imeta.order_item_id = oitems.order_item_id
			INNER JOIN {$wpdb->prefix}term_relationships AS tr ON tr.object_id = imeta.ProductId
			INNER JOIN {$wpdb->prefix}term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			INNER JOIN {$wpdb->prefix}terms AS t ON t.term_id = tt.term_id
			WHERE {$wpdb->prefix}posts.post_type IN ('shop_order', 'shop_order_refund')
			AND (
				{$wpdb->prefix}posts.post_status = 'wc-completed'
			)
			AND tt.taxonomy IN ('product_type')
			AND t.slug = 'phive_booking'";
			
			
			$results = $wpdb->get_results( $query );


			$bookings = array();
			$date_from = date_create();
			// date_timestamp_set( $date_from, current_time('timestamp') );
			global $wp_version;
			if ( version_compare( $wp_version, '5.3', '>=' ) ) 
			{
				$currentTime = current_datetime();
				$currentTime = $currentTime->format('U');
				$userTimezone =new DateTimeZone(wp_timezone_string());
			}
			else
			{
				$currentTime = current_time('timestamp');
				$userTimezone =new DateTimeZone(get_option('timezone_string'));
			}

			// error_log("user timezone follow wp: ".print_r($userTimezone,1));
			$myDateTime = new DateTime();
			$myDateTime->setTimezone($userTimezone);
			// error_log("my date time follow up: ".print_r($myDateTime,1));
			$myInterval=DateInterval::createFromDateString(-($this->followup_time) . 'minutes');
			$myDateTime->add($myInterval);
			$result1 = $myDateTime->format('Y-m-d H:i');
			// error_log("result1 follow up : ".print_r($result1,1));

			$myDateTime = new DateTime();
			$myDateTime->setTimezone($userTimezone);
			$myInterval=DateInterval::createFromDateString((-($this->followup_time-5)). 'minutes');
			$myDateTime->add($myInterval);
			$result2 = $myDateTime->format('Y-m-d H:i');
			// error_log("result2 follow up: ".print_r($result2,1));

			date_timestamp_set( $date_from, $currentTime );

			$email_interval=$this->followup_time;
			$date1=date('Y-m-d H:i',strtotime("-$email_interval minutes",strtotime($date_from->format('Y-m-d H:i'))));
			
			$email_interval=$email_interval-5;
			$date2=date('Y-m-d H:i',strtotime("-$email_interval minutes",strtotime($date_from->format('Y-m-d H:i'))));
			$date1 = $result1;
			$date2 = $result2;
			foreach ($results as $key => $result) {
				$IntervalDetails	= unserialize($result->IntervalDetails);
				$BookFrom 			= ph_maybe_unserialize($result->BookFrom);
				$BookTo 			= ph_maybe_unserialize($result->BookTo);
				if( !empty($BookTo) && ! empty($IntervalDetails) ) {
					$BookTo                = ! empty($BookTo) ? $BookTo : $BookFrom;
					$interval             = $IntervalDetails['interval'];
					$interval_format    = $IntervalDetails['interval_format'];
					if(strtotime($BookTo)==strtotime($BookFrom))
					{
						$BookTo             = date( 'Y-m-d H:i', strtotime( "+$interval $interval_format",strtotime($BookTo) ) );
					}
					else if($interval_format!='day' && $interval_format!='month' )
					{
						$BookTo=str_replace('/', '-', $BookTo);
						$BookTo     = date( 'Y-m-d H:i', strtotime( "+$interval $interval_format",strtotime($BookTo) ) ); // adding interval to last block
					}
				}
				else if (empty($BookTo) && !empty($IntervalDetails) ) {
					
					$interval             = $IntervalDetails['interval'];
					$interval_format    = $IntervalDetails['interval_format'];
					
					if($interval_format!='day' && $interval_format!='month' )
					{
						$BookTo             = date( 'Y-m-d H:i', strtotime( "+$interval $interval_format",strtotime($BookFrom) ) );
						
					}
					else
					{
						$BookTo             = date( 'Y-m-d', strtotime( "+$interval $interval_format",strtotime($BookFrom) ) );    
						
					}
					
				}
	
				if(!is_array($BookFrom))
				{
					$serialized_or_not = @unserialize($BookFrom);
					if($serialized_or_not !== false)
					{
						$BookFrom=$result->BookFrom;
					}
					else
					{
						$BookFrom = maybe_unserialize($BookFrom);
						// $BookFrom = $BookFrom[0];
					}
				}
				else
				{
					$BookFrom=$BookFrom[0];
				}
				
				
				if(!is_array($BookTo))
				{
					$serialized_or_not = @unserialize($BookTo);
					if($serialized_or_not !== false)
					{
						$BookTo=$BookTo;
					}
					else
					{
						$BookTo= maybe_unserialize($BookTo);
					}
				}
				else
				{
					$BookTo=$BookTo[0];
				}

				if($interval_format =='day' || $interval_format =='month' )
				{
					$booking_to=date('Y-m-d 23:59',strtotime($BookTo));
					$booking_from=date('Y-m-d 23:59',strtotime($BookFrom));	
				}
				else
				{
					$booking_from=date('Y-m-d H:i',strtotime($BookFrom));
					$booking_to=date('Y-m-d H:i',strtotime($BookTo));
				}
				if(strtotime($booking_to) < strtotime($date1) || strtotime($booking_to) > strtotime($date2)  )
				{
					continue;
				}
				$bookings[] = array(
					'ID' 			=> $result->order_item_id,
					'order_id' 		=> $result->order_id,
					'product_id' 	=> $result->product_id,
					'start' 		=> $BookFrom,
					'end' 			=> $BookTo,
					'bookedby' 		=> $result->customer_name,
					'billing_email'	=> $result->billing_email,
					'booking_status'=> ph_maybe_unserialize($result->BookingStatus),
					'FollowUpStatus'=>$result->FollowUpStatus
				);
			}
			return $bookings;
		}

	}
	new Ph_Bookings_Send_Follow_Up_Emails();
}