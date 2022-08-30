<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
* This class manage all emails related jobs
*/
class ph_booking_email_manager {
	public function __construct() {
		// 103410
		global $current_lang;
		$this->id             = 'ph_booking';
		if( ! class_exists('Ph_Bookings_Email_Content') )
			include_once 'emails/class-ph-email-content.php';
		add_action( 'ph_booking_status_changed', array( $this, 'email_customer_booking_status_changed' ), 10, 4 );
		add_action( 'ph_booking_status_changed', array( $this, 'email_admin_booking_status_changed' ), 10, 4 );
		// add_action( 'ph_booking_payment_processed', array( $this, 'email_customer_pending_payment' ), 10, 2 );
		add_action( 'ph_booking_payment_processed', array( $this, 'email_admin_pending_payment' ), 10, 2 );
		$this->blog_name = get_option('blogname');

		add_filter( 'woocommerce_email_classes', array( $this, 'init_emails' ) );

		add_action( 'ph_booking_status_changed', array( $this, 'email_customer_booking_status_changed_wc_email' ), 10, 4 );

		add_action( 'ph_booking_payment_processed', array( $this, 'email_customer_pending_payment_wc_email' ), 10, 2 );

		#102692 - template overriding
		add_filter('woocommerce_template_directory', array($this, 'ph_custom_woocommerce_template_directory'), 10, 2);

		// 103410 - checking if sitepress plugin is active
		global $sitepress_active_check;
		$sitepress_active_check = class_exists('SitePress');
	}

	public function email_customer_booking_status_changed_wc_email($status, $item_id, $order_id, $order='')
	{
		if($status == 'cancelled')
		{
			// 103410 - Switching to product language
			global $current_lang;
			$current_lang = ph_wpml_language_switch_admin_email($order, '', 'order', '');

			if ( ! class_exists('Ph_WC_Email_Booking_Cancelled') ) 
			{
				$obj = include_once( 'emails/class-ph-wc-email-booking-cancelled.php' );
				$obj->trigger($status, $item_id, $order_id, $order);
				// error_log("obj : ".print_r($obj,1));
			}
			else
			{
				$obj = new Ph_WC_Email_Booking_Cancelled();
				// error_log("obj else : ".print_r($obj,1));
				$obj->trigger($status, $item_id, $order_id, $order);
			}
		}
		// error_log('inside');
	}

	public function email_customer_pending_payment_wc_email( $order_id, $order )
	{	
		// 103410 - Switching to product language
		global $current_lang;
		$current_lang = ph_wpml_language_switch_admin_email($order, '', 'order', '');

		$status = 'pending_payment';
		if ( ! class_exists('Ph_WC_Email_Booking_Requires_Confirmation') ) 
		{
			$obj = include_once( 'emails/class-ph-wc-email-booking-requires-confirmation.php' );
			$obj->trigger($status,'',$order_id,$order);
		}
		else
		{
			$obj = new Ph_WC_Email_Booking_Requires_Confirmation();
			$obj->trigger($status,'',$order_id,$order);
		}
	}

	public function init_emails($email_classes)
	{
		if ( ! isset( $email_classes['Ph_WC_Email_Booking_Cancelled'] ) ) 
		{
			$email_classes['Ph_WC_Email_Booking_Cancelled'] = include( 'emails/class-ph-wc-email-booking-cancelled.php' );
		}
		if ( ! isset( $email_classes['Ph_WC_Email_Booking_Confirmation'] ) ) 
		{
			$email_classes['Ph_WC_Email_Booking_Confirmation'] = include( 'emails/class-ph-wc-email-booking-confirmation.php' );
		}
		if ( ! isset( $email_classes['Ph_WC_Email_Booking_Requires_Confirmation'] ) ) 
		{
			$email_classes['Ph_WC_Email_Booking_Requires_Confirmation'] = include( 'emails/class-ph-wc-email-booking-requires-confirmation.php' );
		}
		return $email_classes;
	}

	public function email_admin_pending_payment( $order_id, $order ){
		// $subject 	= sprintf( __( "The order #%d is waiting for approval", 'bookings-and-appointments-for-woocommerce' ), $order->get_order_number() );
		$admin_emails = wc()->mailer()->emails;
		$new_order_admin_email = $admin_emails['WC_Email_New_Order']->get_recipient();
		$to 		= $new_order_admin_email;

		$admin_user = get_user_by( 'email', $new_order_admin_email );
		$admin_user_id = $admin_user->ID;

		//103401 - Admin Email Language Fix
		global $sitepress_active_check;
		$current_language = '';
		if($sitepress_active_check)
		{
			$admin_locale = get_user_meta($admin_user_id,'locale',1);
			$admin_locale = !empty($admin_locale) ? $admin_locale : apply_filters('wpml_default_language', NULL ) ;
			if(!empty($admin_locale))
			{
				// WPML Support - Switch to Admin Language For All Email Content and Store Current Language Before Changing to Admin Language
				$current_language = ph_wpml_language_switch_admin_email($order, $admin_user_id, $lang_basis='admin');
			}
		}
	
		$subject 	= sprintf( __( "The order #%d is waiting for approval", 'bookings-and-appointments-for-woocommerce' ), $order->get_order_number() );
		$content 	= $this->get_admin_pending_payment_email_content($order, $admin_user_id);

		if( !empty($to) ){
			$this->send( $to, $subject, $content );
		}

		if(!empty($current_language))
		{
			// WPML Support - Switch back to current language after sending email
			ph_wpml_language_switch_admin_email($order, $admin_user_id, $lang_basis='current', $current_language);
		}
	}
	public function email_customer_pending_payment( $order_id, $order ){
		$obj = new Ph_Bookings_Email_Content();
		$status = 'pending_payment';
		$obj->init( $order, false, $status );
		$subject 	= $obj->get_email_subject();
		$content 	= $obj->get_email_contents();
		$to 		= $order->get_billing_email();
		if( !empty($to) ){
			$this->send( $to, $subject, $content );
		}
	}

	public function email_admin_booking_status_changed( $status, $item_id, $order_id, $order='' ){

		$return = false;
        $return = apply_filters('ph_filter_do_not_send_cancellation_email', false, $status, 'admin-email');
        if ($return)
        {
            return;
        }

		if( empty($order) ){
			$order = wc_get_order($order_id);
		}

		$admin_emails = wc()->mailer()->emails;
		$new_order_admin_email = $admin_emails['WC_Email_New_Order']->get_recipient();
		$to 		= $new_order_admin_email;

		//103401 - Admin Email Language Fix
		$admin_user = get_user_by( 'email', $new_order_admin_email );
		$admin_user_id = $admin_user->ID;

		global $sitepress_active_check;
		$current_language = '';
		if($sitepress_active_check)
		{
			// WPML Support - Changing to admin language to send email
			$admin_locale = get_user_meta($admin_user_id,'locale',1);
			$admin_locale = !empty($admin_locale) ? $admin_locale : apply_filters('wpml_default_language', NULL ) ;
			if(!empty($admin_locale))
			{
				$current_language = ph_wpml_language_switch_admin_email($order, $admin_user_id, $lang_basis='admin');
			}
		}

		if( $status=='cancelled' ){
			$subject = sprintf( __( "Booking #%d has been cancelled. The order details are shown below", 'bookings-and-appointments-for-woocommerce' ), $order->get_order_number() );
			$content = sprintf( __( "The Booking for the order #%d has been cancelled", 'bookings-and-appointments-for-woocommerce' ), $order->get_order_number() );
		}else{
			$content="";
		}

		$content .= $this->get_products_info_html($order,$item_id,$status,$admin_user_id);

		if( !empty($to) && !empty($subject) && !empty($content) ){
			$this->send( $to, $subject, $content );
		}

		if(!empty($current_language))
		{
			// WPML Support - Switch back to current language after sending email
			ph_wpml_language_switch_admin_email($order, $admin_user_id, $lang_basis='current', $current_language);
		}
	}

	public function email_customer_booking_status_changed( $status, $item_id, $order_id, $order='' ){
		// Customer emails will use new templates
		if($status == 'cancelled' || $status == 'confirmed')
		{
			return;
		}

		$return = false;
        $return = apply_filters('ph_filter_do_not_send_cancellation_email', false, $status, 'customer-email');
        if ($return)
        {
            return;
        }
        
		if( ! is_a($order, 'WC_Order') ){
			$order = wc_get_order($order_id);
		}

		$obj = new Ph_Bookings_Email_Content();
		$obj->init( $order, false, $status );
		$subject = $obj->get_email_subject();
		$content = $obj->get_email_contents($item_id);

		$to = $order->get_billing_email();
		
		if( !empty($to) && !empty($subject) && !empty($content) ){
			$this->send( $to, $subject, $content );
		}
	}

	private function send( $to, $subject, $message ){

		$header = array(
			"Content-Type: text/html; charset=UTF-8"
		);
		$from_address	= get_option( 'woocommerce_email_from_address' );
		$from_name		= get_option( 'woocommerce_email_from_name');
		$header[]		= "From : ".wp_specialchars_decode( esc_html($from_name), ENT_QUOTES )." <$from_address>";
		$return  = wp_mail( $to, $subject, $message, $header, '' );
	}

	private function get_customer_pending_payment_email_content( $order ){
		$email_heading = __('Thank you for your order','bookings-and-appointments-for-woocommerce' );
	
		ob_start();
		// do_action( 'woocommerce_email_header', $email_heading );

		wc_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );?>
		
		<p><?php _e( "Your order has been received and awaiting approval.", 'bookings-and-appointments-for-woocommerce' ); ?></p>
		<?php

		echo $this->get_products_info_html( $order );
		// wc_get_template( 'emails/email-footer.php' );
		// do_action( 'woocommerce_email_footer' );

		
		return ob_get_clean();
	}

	private function get_admin_pending_payment_email_content( $order, $admin_user_id='' ){
		$email_heading = __('New customer order','bookings-and-appointments-for-woocommerce' );
	
		ob_start();
		// do_action( 'woocommerce_email_header', $email_heading );

		wc_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );?>
		
		<p><?php echo sprintf( __( "You have received an order (#%d). The order is waiting for approval. The order is as follows", 'bookings-and-appointments-for-woocommerce' ), $order->get_id() ); ?></p>
		
		<?php

		echo $this->get_products_info_html( $order, '', '', $admin_user_id );
		// wc_get_template( 'emails/email-footer.php' );
		// do_action( 'woocommerce_email_footer' );

		
		return ob_get_clean();
	}

	private function get_products_info_html( $order,$item_id='',$status='', $admin_user_id = '' ){
		if( empty($order) ){
			return;
		}

		ob_start();
		?>
		<table style="width:100%;font-family:'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif;color:#636363;border:1px solid #e5e5e5;vertical-align:middle">
			<tr>
				<th style="text-align:left;color:#636363;border:1px solid #e5e5e5;vertical-align:middle;padding:12px">Product</th>
				<th style="text-align:left;color:#636363;border:1px solid #e5e5e5;vertical-align:middle;padding:12px">Price</th>
			</tr>

			<?php
			$order_items = $order->get_items();
			foreach ($order_items as $order_item_id => $item) 
			{
				
				$product 	= wc_get_product($item->get_product_id() );
				if( $product->get_type() !='phive_booking'  || (!empty($item_id) && $item_id!=$order_item_id) ){
					continue;
				}
				$cost 	= ph_maybe_unserialize($item->get_meta('Cost') );
				?>

				<tr>
					<td style="text-align:left;vertical-align:middle;font-family:'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif;word-wrap:break-word;color:#636363;border:1px solid #e5e5e5;padding:12px">
						<?php 
						echo $product->get_title();
						echo $this->get_order_item_meta_data( $item, $order, $admin_user_id );
						?>
					</td>
					<td style="text-align:left;vertical-align:middle;font-family:'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif;word-wrap:break-word;color:#636363;border:1px solid #e5e5e5;padding:12px"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ));?></td>
				</tr>
				<?php
			}
			if (!empty($order_items) && $status != 'cancelled')
			{
				if(($status != 'confirmed' ) || ($status == 'confirmed' && count($order_items) == 1))
				{
					$item_totals = $order->get_order_item_totals();
					if ( $item_totals ) 
					{
						foreach ( $item_totals as $total ) 
						{
							?>
							<tr>
								<td style="text-align:left;vertical-align:middle;font-family:'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif;word-wrap:break-word;color:#636363;border:1px solid #e5e5e5;padding:12px">
									<b><?php echo wp_kses_post( $total['label'] ); ?></b>
								</td>
								<td style="text-align:left;vertical-align:middle;font-family:'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif;word-wrap:break-word;color:#636363;border:1px solid #e5e5e5;padding:12px">
									<?php echo wp_kses_post( $total['value'] ); ?>
								</td>
							</tr>
							<?php
						}
					}
				}
			}
			?>
			<!-- Customer Billing and Shipping Details Start-->
			<?php 
				$content = $this->get_address($order); 
				if ($content) 
				{
					echo $content;
				}
			?>
			<!-- Customer Billing and Shipping Details End-->

			<!--footer start-->
			<tr>
				<td align="center" valign="top" colspan="2">
					<table border="0" cellpadding="10" cellspacing="0" width="100%" id="template_footer">
						<tr>
							<td valign="top">
								<table border="0" cellpadding="10" cellspacing="0" width="100%">
									<tr>
										<td colspan="2" valign="middle" id="credit" align="center">													<small style="text-align:center; color:#6c757d!important;">
												<?php $footer_modified_string = $this->replace_placeholders( get_option( 'woocommerce_email_footer_text' ) ) ;?>
												<?php echo wp_kses_post( wpautop( wptexturize( $footer_modified_string ) ) );
												?>
											</small>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<!-- footer end -->
		</table><?php
		return ob_get_clean();
	}
	
	private function replace_placeholders( $string ) 
		{	
			$domain = wp_parse_url( home_url(), PHP_URL_HOST );

			return str_replace(
				array(
					'{site_title}',
					'{site_address}',
					'{site_url}',
					'{woocommerce}',
					'{WooCommerce}',
				),
				array(
					$this->get_blogname(),
					$domain,
					$domain,
					'<a href="https://woocommerce.com">WooCommerce</a>',
					'<a href="https://woocommerce.com">WooCommerce</a>',
				),
				$string
			);
		}
	
	
		private function get_blogname() {
			return wp_specialchars_decode( $this->blog_name, ENT_QUOTES );
		}
	
	/**
	 * Get Meta data of Line Items that needs to be sent in email.
	 * @return string in the form of Unordered list
	 */
	private function get_order_item_meta_data( $order_item, $order='', $admin_user_id='' ) {
		$content = null;
		$meta_datas = $order_item->get_meta_data();
		$product = $order_item->get_product();
		$item_id = $order_item->get_id();

		// WPML Admin Email Support
		$switch_text = 0;
		$months = [];
		if(!empty($order) && !empty($admin_user_id))
		{
			global $sitepress_active_check;
			$admin_locale = get_user_meta($admin_user_id,'locale',1);
			$admin_locale = !empty($admin_locale) ? $admin_locale : apply_filters('wpml_default_language', NULL ) ;
			if(!empty($admin_locale) && $sitepress_active_check)
			{
				$switch_text = 1;

				// Get keys in order language to modify to admin language in email
				ph_wpml_language_switch_admin_email($order, $admin_user_id, $lang_basis='order');

				$booked_from_key = __('Booked From','bookings-and-appointments-for-woocommerce');
				$booked_to_key = __('Booked To','bookings-and-appointments-for-woocommerce');
				$booking_status_key = __('Booking Status', 'bookings-and-appointments-for-woocommerce');
				$months = 	[
					'January' => __('January', 'bookings-and-appointments-for-woocommerce'),
					'February' =>__('February', 'bookings-and-appointments-for-woocommerce'),
					'March' => __('March', 'bookings-and-appointments-for-woocommerce'),
					'April' =>__('April', 'bookings-and-appointments-for-woocommerce'),
					'May' => __('May', 'bookings-and-appointments-for-woocommerce'),
					'June' => __('June', 'bookings-and-appointments-for-woocommerce'),
					'July' => __('July', 'bookings-and-appointments-for-woocommerce'),
					'August' => __('August', 'bookings-and-appointments-for-woocommerce'),
					'September' => __('September', 'bookings-and-appointments-for-woocommerce'),
					'October' => __('October', 'bookings-and-appointments-for-woocommerce'),
					'November' => __('November', 'bookings-and-appointments-for-woocommerce'),
					'December' => __('December', 'bookings-and-appointments-for-woocommerce'),
					'Jan' => __('Jan','bookings-and-appointments-for-woocommerce'),
					'Feb' => __('Feb','bookings-and-appointments-for-woocommerce'),
					'Mar' => __('Mar','bookings-and-appointments-for-woocommerce'),
					'Apr' => __('Apr','bookings-and-appointments-for-woocommerce'),
					'Jun' => __('Jun','bookings-and-appointments-for-woocommerce'),
					'Jul' => __('Jul','bookings-and-appointments-for-woocommerce'),
					'Aug' => __('Aug','bookings-and-appointments-for-woocommerce'),
					'Sep' => __('Sep','bookings-and-appointments-for-woocommerce'),
					'Oct' => __('Oct','bookings-and-appointments-for-woocommerce'),
					'Nov' => __('Nov','bookings-and-appointments-for-woocommerce'),
					'Dec' => __('Dec','bookings-and-appointments-for-woocommerce')
				];
				// Switch To Admin User Language
				ph_wpml_language_switch_admin_email($order, $admin_user_id, $lang_basis='admin');
			}
		}
		//hide certain meta_keys from email 
		$hidden_order_itemmeta = apply_filters('ph_bookings_order_meta_key_filters', array(), $order_item);
		
		foreach( $meta_datas as $meta_data ) {
			$meta_data = $meta_data->get_data();
			if( ! empty($meta_data['value']) && ! is_array($meta_data['value']) && !in_array($meta_data['key'], $hidden_order_itemmeta)) {			
				$meta_data['key'] = apply_filters( 'woocommerce_attribute_label', $meta_data['key'], $meta_data['key'], $product);			

				// WPML Support - Show Booking Details in Admin language
				if($switch_text == 1)
				{
					if($meta_data['key'] == $booking_status_key)
					{
						$meta_data['key'] = 'Booking Status';
						$booking_status = wc_get_order_item_meta( $item_id, 'booking_status', 1);
						if(is_array($booking_status) && isset($booking_status[0]))
						{
							$booking_status = $booking_status[0];
							$meta_data['value'] = ph_map_booking_status_to_name($booking_status);
						}
					}
					if($meta_data['key'] == $booked_from_key || $meta_data['key'] == $booked_to_key)
					{
						foreach($months as $key => $value)
						{
							if(strripos($meta_data['value'],$value))
							{
								$meta_data['value'] = str_ireplace($value,$key,$meta_data['value']); break;
							}
						}
						if($meta_data['key'] == $booked_from_key)
						{
							$meta_data['key'] = 'Booked From';
						}
						else if($meta_data['key'] == $booked_to_key)
						{
							$meta_data['key'] = 'Booked To';
						}
					}
				}
				$content .= "<li>".__($meta_data['key'], 'bookings-and-appointments-for-woocommerce').": ".__($meta_data['value'], 'bookings-and-appointments-for-woocommerce')."</li>";
			}
		}

		if( ! empty($content) ) {
			$content = "<ul style='padding:10px;font-size:12px;'>".$content."</ul>";
		}
		return $content;
	}


	private function get_address($order) {
		$billing_address  = $order->get_address();
		$shipping_address = $order->get_address('shipping');
		$content = "<tr style='height:15px;'><tr>";			//to give spacing before billing details
		$content .= "<tr>
						<th style='text-align:left;color:#636363;border:1px solid #e5e5e5;vertical-align:middle;padding:12px'>".
							__( 'Billing address', 'bookings-and-appointments-for-woocommerce')."
						</th>";
		if( ! empty($shipping_address) ) 
		{
			$content .= "<th style='text-align:left;color:#636363;border:1px solid #e5e5e5;vertical-align:middle;padding:12px'>".__( 'Shipping address', 'bookings-and-appointments-for-woocommerce' )."</th>";
		}

		$content .= "</tr>
					<tr>
						<td style='text-align:left;vertical-align:middle;font-family:\"Helvetica Neue\",Helvetica,Roboto,Arial,sans-serif;word-wrap:break-word;color:#636363;
						border:1px solid #e5e5e5 !important;padding:12px;'>".
							$billing_address['company']."<br>".
							$billing_address['first_name']." ".$billing_address['last_name']."<br>".
							$billing_address['address_1']."<br>".
							$billing_address['address_2']."<br>".
							$billing_address['city']." ".$billing_address['state']." ".$billing_address['postcode']."<br>".
							$billing_address['country']."<br>".
							$billing_address['phone']."<br>".
							$billing_address['email'].
						"</td>";
		if( ! empty($shipping_address) ) 
		{
			$content .= "<td style='text-align:left;vertical-align:middle;font-family:\"Helvetica Neue\",Helvetica,Roboto,Arial,sans-serif;word-wrap:break-word;color:#636363;
			border:1px solid #e5e5e5 !important;padding:12px;'>".
							$shipping_address['company']."<br>".
							$shipping_address['first_name']." ".$shipping_address['last_name']."<br>".
							$shipping_address['address_1']."<br>".
							$shipping_address['address_2']."<br>".
							$shipping_address['city']." ".$shipping_address['state']." ".$shipping_address['postcode']."<br>".
							$shipping_address['country']."<br>"."
						</td>";
		}
		$content .= "</tr>";
		return $content;
	}

	public function ph_custom_woocommerce_template_directory( $woocommerce, $template ){ 
		// error_log($template);
		$ph_templates = array(
			'emails/ph-customer-booking-cancelled.php',
			'emails/ph-customer-booking-confirmed.php',
			'emails/ph-customer-booking-requires-confirmation.php'
		);
		if(in_array($template, $ph_templates))
		{
			return 'ph-bookings-appointments-woocommerce';
		}
		return $woocommerce;
	} 
	
}
new ph_booking_email_manager();