<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Booking is confirmed
 *
 * An email sent to the user when a booking is confirmed.
 *
 * @class   Ph_WC_Email_Booking_Confirmation
 * @extends WC_Email
 */
class Ph_WC_Email_Booking_Confirmation extends WC_Email {

	/**
	 * Constructor
	 */
	public function __construct() {

		//103410 - switching language on status change to send mail in correct language	
		global $current_lang;
		add_action('ph_booking_status_changed', array( $this, 'switch_lang' ), 10, 3);

		$this->id             = 'ph_booking_confirmation';
		$this->title          = __( 'PH Booking Confirmed', 'bookings-and-appointments-for-woocommerce' );
		$this->description    = __( 'Booking confirmed emails are sent when the status of a booking goes from requires confirmation to confirmed.', 'bookings-and-appointments-for-woocommerce' );

		$this->heading        = __( 'Thank you for booking with us', 'bookings-and-appointments-for-woocommerce' );
		
		$this->subject        = __( 'Your Booking at {site_title} is confirmed', 'bookings-and-appointments-for-woocommerce' );

		$this->customer_email = true;
		$this->template_html  = 'emails/ph-customer-booking-confirmed.php';

		$this->template_plain = '';

		$this->blog_name		= get_option('blogname');
		$this->booking_status	= 'confirmed';

        add_action( 'ph_booking_status_changed', array( $this, 'trigger' ), 10, 4);

		// Call parent constructor
		parent::__construct();

		// Other settings
        $this->template_base = PH_BOOKINGS_TEMPLATE_PATH;
	}

	// 103410 - Switching language on change of status
	public function switch_lang($status, $item_id, $order_id)
	{
		if($status == 'confirmed')
		{
			global $current_lang;
			$order 		= wc_get_order($order_id);
			$current_lang = ph_wpml_language_switch_admin_email($order, '', 'order', '');
			$this->heading        = __( 'Thank you for booking with us', 'bookings-and-appointments-for-woocommerce' );
			$this->subject        = __( 'Your Booking at {site_title} is confirmed', 'bookings-and-appointments-for-woocommerce' );
		}
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	public function trigger($status, $item_id, $order_id, $order='') 
	{

		$this->item = WC_Order_Factory::get_order_item( absint( $item_id ) );
		$this->item_id = $item_id;

		if(($this->item->get_product()->get_type() != 'phive_booking') || ($status != 'confirmed'))
		{
			return;
		}

		// older email hook
		$return = false;
        $return = apply_filters('ph_filter_do_not_send_cancellation_email', false, $status, 'customer-email');
        if ($return)
        {
            return;
		}

		$this->booking_status	= $status;
		
		// bail if no order ID is present
		if ( ! $order_id )
			return;

		// setup order object
		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		//ticket 120940-Email variable order_number not working
		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object                                    = $order;
			$this->placeholders['{order_date}']              = wc_format_datetime( $this->object->get_date_created() );
			$this->placeholders['{order_number}']            = $this->object->get_order_number();
			$this->placeholders['{order_billing_full_name}'] = $this->object->get_formatted_billing_full_name();
		}
		
		$this->object = $order;

		if ( wc_get_order( $order_id ) ) 
		{	
			$billing_email = wc_get_order( $order_id )->get_billing_email();
			$this->recipient = $billing_email;
		} 
		else 
		{
			$customer_id = $this->object->get_customer_id();
			$customer    = $customer_id ? get_user_by( 'id', $customer_id ) : false;

			if ( $customer_id && $customer ) {
				$this->recipient = $customer->user_email;
			}
		}

		$this->customer_full_name = $this->object->get_formatted_billing_full_name();

		if ( ! $this->is_enabled() || ! $this->get_recipient() )
			return;

		// woohoo, send the email!
		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		// 103410 - Switching back to current language
		global $current_lang;
		ph_wpml_language_switch_admin_email('', '', 'current', $current_lang);
	}


	public function ph_get_order_item_meta_data( $order_item ) {
		$content = null;
		$meta_datas = $order_item->get_meta_data();
		$product = $order_item->get_product();

		//hide certain meta_keys from email 
		$hidden_order_itemmeta = apply_filters('ph_bookings_order_meta_key_filters', array(), $order_item);
		
		foreach( $meta_datas as $meta_data ) {
			$meta_data = $meta_data->get_data();
			if( ! empty($meta_data['value']) && ! is_array($meta_data['value']) && !in_array($meta_data['key'], $hidden_order_itemmeta)) {			
				$meta_data['key'] = apply_filters( 'woocommerce_attribute_label', $meta_data['key'], $meta_data['key'], $product);			
				$content .= "<li style='margin: 0.5em 0 0; padding: 0;'><b>".__($meta_data['key'], 'bookings-and-appointments-for-woocommerce')."</b>: <br>".__($meta_data['value'], 'bookings-and-appointments-for-woocommerce')."</li>";
			}
		}

		if( ! empty($content) ) {
			$content = "<ul style='font-size: small; margin: 1em 0 0;padding: 0;list-style: none;'>".$content."</ul>";
		}
		return $content;
	}

	/**
	 * Return content from the additional_content field.
	 *
	 * Displayed above the footer.
	 *
	 * @since 3.7.0
	 * @return string
	 */
	public function get_additional_content() {
		$content = $this->get_option( 'additional_content', '' );

		return apply_filters( 'woocommerce_email_additional_content_' . $this->id, $this->format_string( $content ), $this->object, $this );
	}

	/**
	 * Default content to show below main email content.
	 *
	 * @since 3.7.0
	 * @return string
	 */
	public function get_default_additional_content() {
		return __( 'Thanks for reading.', 'woocommerce' );
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_html() {
		ob_start();
		wc_get_template( $this->template_html, array(
			'order'         		=> $this->object,
			'customer_full_name'   	=> $this->customer_full_name,
			'item'					=> $this->item,
			'item_id'				=> $this->item_id,
			'email_heading' 		=> $this->get_heading(),
			'additional_content' 	=> $this->get_additional_content(),
			'sent_to_admin' 		=> false,
			'plain_text'    		=> false,
			'email'         		=> $this,
			'email_base_color'		=> get_option( 'woocommerce_email_base_color' ),
			'email_text_color'		=> get_option( 'woocommerce_email_text_color' ),
			'wp_date_format'		=> get_option( 'date_format' )
		), 'ph-bookings-appointments-woocommerce/', $this->template_base );
		return ob_get_clean();
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_plain() {
        ob_start();
		wc_get_template( $this->template_html, array(
			'order'         		=> $this->object,
			'customer_full_name'   	=> $this->customer_full_name,
			'item'					=> $this->item,
			'item_id'				=> $this->item_id,
			'email_heading' 		=> $this->get_heading(),
			'additional_content' 	=> $this->get_additional_content(),
			'sent_to_admin' 		=> false,
			'plain_text'    		=> false,
			'email'         		=> $this,
			'email_base_color'		=> get_option( 'woocommerce_email_base_color' ),
			'email_text_color'		=> get_option( 'woocommerce_email_text_color' ),
			'wp_date_format'		=> get_option( 'date_format' )
		), 'ph-bookings-appointments-woocommerce/', $this->template_base );
		return ob_get_clean();
	}

	/**
	 * Initialise Settings Form Fields
	 *
	 * @access public
	 * @return void
	 */
	public function init_form_fields() 
	{
		$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );

		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'woocommerce' ),
				'default' => 'yes',
			),
			'subject' => array(
				'title'       => __( 'Subject', 'woocommerce' ),
				'type'        => 'text',
				/* translators: 1: subject */
				'description' => __( 'This controls the email subject line. Leave blank to use the default subject.', 'bookings-and-appointments-for-woocommerce' ),
				'placeholder' => "$this->subject",
				'default'     => '',
            ),
			'heading' => array(
				'title'       => __( 'Email heading', 'woocommerce' ),
				'type'        => 'text',
				/* translators: 1: heading */
				'description' => __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading.', 'bookings-and-appointments-for-woocommerce' ),
				'placeholder' => "$this->heading",
				'default'     => '',
			),
			'additional_content' => array(
				'title'       => __( 'Additional content', 'woocommerce' ),
				'description' => __( 'Text to appear below the main email content.', 'woocommerce' ) . ' ' . $placeholder_text,
				'css'         => 'width:400px; height: 75px;',
				'placeholder' => __( 'N/A', 'woocommerce' ),
				'type'        => 'textarea',
				'default'     => $this->get_default_additional_content(),
				'desc_tip'    => true,
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
				'default'     => 'html',
				'class'       => 'email_type',
				'options'     => array(
					'html'      => __( 'HTML', 'woocommerce' ),
				),
			),
		);
	}
}

return new Ph_WC_Email_Booking_Confirmation();
