<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * This class manage all cart related jobs.
 */
class phive_booking_cart_decorator
{

    public function __construct()
    {
        add_action('woocommerce_phive_booking_add_to_cart', array($this, 'phive_add_booking_product_to_cart'), 30);

		// corrects subtotal
		add_filter( 'woocommerce_add_cart_item', array( $this, 'phive_get_cart_from_session' ), 10, 1 );

        add_filter('woocommerce_add_cart_item_data', array($this, 'phive_add_booking_infos_with_cart_item'), 10, 3); //Add Customer Data to WooCommerce Cart

        add_filter('woocommerce_get_item_data', array($this, 'phive_disply_item_booking_infos'), 10, 2); //Display Details as Meta in Cart

        add_filter('wc_add_to_cart_message_html', array($this, 'phive_added_to_cart_message'), 10, 2);
        add_action('woocommerce_get_cart_item_from_session', array($this, 'phive_get_cart_from_session'), 10, 3); //Make session value part of cart.

        // validating the selected block are already booked or not
        add_filter('woocommerce_add_to_cart_validation', array($this, 'ph_booking_woocommerce_add_to_cart_validation'), 10, 3);

        add_action('woocommerce_after_checkout_validation', array($this, 'ph_booking_woocommerce_place_order_validation'), 10, 1);

    }

		/**
		* Check last minute booking rule
		* @return Bool
		*/
        public function check_first_availability($calendar_strategy, $start_time, $interval_period, $product_id)
        {
            $calendar_for = "date-picker";
            if($interval_period == 'hour' || $interval_period == 'minute'){
                $calendar_for = "time";
            }

            $first_availability = get_post_meta($product_id, '_phive_first_availability', 1);
            $last_availability  = get_post_meta( $product_id, '_phive_last_availability', 1 );
    
            $first_availability_interval_period = get_post_meta($product_id, '_phive_first_availability_interval_period', 1);
            $last_availability_interval_period  = get_post_meta( $product_id, '_phive_last_availability_interval_period', 1 );
    
            if (!empty($first_availability) || !empty($last_availability)) { //If relative today
                $first_availability_date_format = ($first_availability_interval_period == 'days' || $calendar_for == 'time-picker') ? 'Y-m-d' : 'Y-m-d H:i';
                $last_availability_date_format  = ($last_availability_interval_period  == 'days'  || $calendar_for == 'time-picker') ? 'Y-m-d':'Y-m-d H:i';
    
                $first_availability = empty($first_availability) ? '' : $first_availability;
                $last_availability = empty($last_availability) ? '' : $last_availability;
    
                $current_time = $calendar_strategy->get_current_time_in_wp_tz();
                $current_time_for_first_availability = date($first_availability_date_format, $current_time);
                $current_time_for_last_availability = date($last_availability_date_format, $current_time);
    
                $from = !empty($first_availability) ?  date($first_availability_date_format, strtotime($current_time_for_first_availability . " + " . $first_availability . " " . $first_availability_interval_period)) : '';
                $to   = !empty($last_availability)  ?  date($last_availability_date_format,  strtotime($current_time_for_last_availability  . " + " . $last_availability  . " " . $last_availability_interval_period) ) : '';

                if (!$calendar_strategy->is_date_in_between_relative($start_time, $from, $to, $calendar_for)) {
                    return false;
                }
            }
            return true;
        }

    // validating selected dates are available or not
    public function ph_booking_woocommerce_place_order_validation($posted)
    {
        // error_log(print_r("ph_booking_woocommerce_place_order_validation", 1));

        if (WC() !== null && WC()->cart !== null) {
            $cart_contents = WC()->cart->cart_contents;
            if (!empty($cart_contents) && is_array($cart_contents)) {
                if (!class_exists('phive_booking_calendar_strategy')) {
                    include_once 'frondend/class-ph-booking-calendar-strategy.php';
                }

                if (!class_exists('phive_booking_assets')) {
                    include_once 'frondend/class-ph-booking-assets.php';
                }

                foreach ($cart_contents as $key => $item) {
                    if (isset($item['product_id']) && isset($item['phive_book_from_date']) && isset($item['phive_book_to_date'])) {
                        $product_id = $item['product_id'];
                        $product_id = Ph_Bookings_General_Functions_Class::get_default_lang_product_id($product_id); //WPML compatibilty
                        $product = wc_get_product($product_id);
                        $product_name = $product->get_name();
                        $calendar_strategy = new phive_booking_calendar_strategy($product_id);

                        $curr_date = strtotime($item['phive_book_from_date']);
                        $end_date = strtotime($item['phive_book_to_date']);
                        $asset_id = (isset($item['phive_booked_assets'])) ? $item['phive_booked_assets'] : false;
                        if ($asset_id != false) {
                            $calendar_strategy->asset_obj = new phive_booking_assets($asset_id);

                        }
                        $availability = true;
                        $this->check_availabilty_while_add_to_cart = 0;
                        $product_properties = get_post_meta($product_id);
                        $interval = get_post_meta($product_id, "_phive_book_interval", 1);
                        $interval_period = get_post_meta($product_id, "_phive_book_interval_period", 1);
                        $buffer_period = get_post_meta($product_id, "_phive_buffer_period", 1);
                        $phive_enable_buffer = get_post_meta($product_id, "_phive_enable_buffer", 1);
                        $display_booking_capacity = get_post_meta($product_id, '_phive_display_bookings_capacity', true);
                        $buffer_before_time = get_post_meta($product_id, "_phive_buffer_before", 1);
                        $buffer_after_time = get_post_meta($product_id, "_phive_buffer_after", 1);

                        $phive_booking_person_enable = get_post_meta($product_id, "_phive_booking_person_enable", 1);
                        $phive_booking_persons_as_booking = get_post_meta($product_id, "_phive_booking_persons_as_booking", 1);

                        if (($phive_enable_buffer == 'yes') && ($interval_period == $buffer_period)) {
                            $buffer_before_time = empty($buffer_before_time) ? '0' : $buffer_before_time;
                            $buffer_after_time = empty($buffer_after_time) ? '0' : $buffer_after_time;
                            $interval = $interval;
                            if ((($buffer_before_time % $interval) != 0) || (($buffer_after_time % $interval) != 0)) {
                                $interval += ($buffer_before_time + $buffer_after_time);
                            }
                        }
                        if ($curr_date == $end_date) {
                            $end_date = strtotime("+$interval $interval_period", $end_date);
                        }
                        
                        // 158203
                        if($asset_id)
                        {
                            $ph_cache_obj = new phive_booking_cache_manager();
						    $ph_cache_obj->ph_unset_cache($asset_id);
                        }

                        while ($curr_date < $end_date) {
                            // error_log(print_r(date('Y-m-d H:i', $curr_date), 1));
                            $availability = $calendar_strategy->get_number_of_available_slot($curr_date, $product_id, $asset_id, true, 1);

                            $check_first_availability_result = $this->check_first_availability($calendar_strategy, $curr_date, $interval_period, $product_id);
                            if(!$check_first_availability_result){
                                wc_add_notice(__('Selected blocks not available for ' . $product_name, 'bookings-and-appointments-for-woocommerce'), 'error');
                            }

                            if ($phive_booking_person_enable == 'yes' && $phive_booking_persons_as_booking == 'yes' && isset($item['phive_book_persons'])) {
                                $total_participants = apply_filters('ph_booking_booked_participant_list', array_sum($item['phive_book_persons']), $item['phive_book_persons'], $product_id);
                                $availability -= $total_participants;
                            }
                            if ($availability < 0 || (($phive_booking_person_enable != 'yes' || $phive_booking_persons_as_booking != 'yes' || !isset($item['phive_book_persons'])) && $availability <= 0)) {
                                $this->check_availabilty_while_add_to_cart = 1;
                                wc_add_notice(__('Selected blocks not available for ' . $product_name, 'bookings-and-appointments-for-woocommerce'), 'error');
                                // return false;
                            }
                            $curr_date = strtotime("+$interval $interval_period", $curr_date);
                        }
                        // return true;
                    }
                }
            }
            // wc_add_notice( __( "Captcha is empty!", 'woocommerce' ), 'error' );
        }
    }

    // validating selected dates are available or not
    public function ph_booking_woocommerce_add_to_cart_validation($true, $product_id, $quantity)
    {
        // error_log(print_r("ph_booking_woocommerce_add_to_cart_validation", 1));

        $product_id = Ph_Bookings_General_Functions_Class::get_default_lang_product_id($product_id); //WPML compatibilty
        if (!class_exists('phive_booking_calendar_strategy')) {
            include_once 'frondend/class-ph-booking-calendar-strategy.php';
        }

        $calendar_strategy = new phive_booking_calendar_strategy($product_id);
        if (!isset($_REQUEST['phive_book_from_date']) || !isset($_REQUEST['phive_book_to_date'])) {
            return true;
        }
        $curr_date = strtotime($_REQUEST['phive_book_from_date']);
        $end_date = strtotime($_REQUEST['phive_book_to_date']);
        $asset_id = (isset($_REQUEST['phive_book_assets'])) ? $_REQUEST['phive_book_assets'] : false;
        if ($asset_id != false) {
            if (!class_exists('phive_booking_assets')) {
                include_once 'frondend/class-ph-booking-assets.php';
            }

            $calendar_strategy->asset_obj = new phive_booking_assets($asset_id);

        }
        $availability = true;
        $this->check_availabilty_while_add_to_cart = 0;
        $product_properties = get_post_meta($product_id);
        $interval = get_post_meta($product_id, "_phive_book_interval", 1);
        $interval_period = get_post_meta($product_id, "_phive_book_interval_period", 1);
        $buffer_period = get_post_meta($product_id, "_phive_buffer_period", 1);
        $phive_enable_buffer = get_post_meta($product_id, "_phive_enable_buffer", 1);
        $display_booking_capacity = get_post_meta($product_id, '_phive_display_bookings_capacity', true);
        $buffer_before_time = get_post_meta($product_id, "_phive_buffer_before", 1);
        $buffer_after_time = get_post_meta($product_id, "_phive_buffer_after", 1);

        $phive_booking_person_enable = get_post_meta($product_id, "_phive_booking_person_enable", 1);
        $phive_booking_persons_as_booking = get_post_meta($product_id, "_phive_booking_persons_as_booking", 1);

        if (($phive_enable_buffer == 'yes') && ($interval_period == $buffer_period)) {
            $buffer_before_time = empty($buffer_before_time) ? '0' : $buffer_before_time;
            $buffer_after_time = empty($buffer_after_time) ? '0' : $buffer_after_time;
            $interval = $interval;
            if ((($buffer_before_time % $interval) != 0) || (($buffer_after_time % $interval) != 0)) {
                $interval += ($buffer_before_time + $buffer_after_time);
            }
        }
        if ($curr_date == $end_date) {
            $end_date = strtotime("+$interval $interval_period", $end_date);
        }
        while ($curr_date < $end_date) {
            $availability = $calendar_strategy->get_number_of_available_slot($curr_date, $product_id, $asset_id);
            if ($phive_booking_person_enable == 'yes' && $phive_booking_persons_as_booking == 'yes' && isset($_REQUEST['phive_book_persons'])) {
                $total_participants = apply_filters('ph_booking_booked_participant_list', array_sum($_REQUEST['phive_book_persons']), $_REQUEST['phive_book_persons'], $product_id);
                $availability -= $total_participants;
            }
            if ($availability < 0 || (($phive_booking_person_enable != 'yes' || $phive_booking_persons_as_booking != 'yes' || !isset($_REQUEST['phive_book_persons'])) && $availability <= 0)) {
                $this->check_availabilty_while_add_to_cart = 1;
                wc_add_notice(__('Selected blocks not available.', 'bookings-and-appointments-for-woocommerce'), 'error');
                return false;
            }
            $curr_date = strtotime("+$interval $interval_period", $curr_date);
        }

         // total min participants validation
         if (($phive_booking_person_enable == 'yes') && (isset($_REQUEST['phive_book_persons']) || ph_is_bookable_product(wc_get_product($product_id)))) 
         {
            $min_participant = get_post_meta( $product_id, '_phive_booking_minimum_number_of_required_participant', 1);

            $phive_booked_persons = isset($_REQUEST['phive_book_persons']) ? $_REQUEST['phive_book_persons'] : array('0');

            $total_participants = apply_filters('ph_booking_booked_participant_list', array_sum($phive_booked_persons), $phive_booked_persons, $product_id);
            
            if(!empty($min_participant) && ($total_participants < $min_participant))
            {
                $text_customisation=isset($display_settings['text_customisation'])?$display_settings['text_customisation']:array();

                $min_participant_text = isset($text_customisation['min_participant']) && !empty($text_customisation['min_participant'])?$text_customisation['min_participant']:'Minimum number of participants required for a booking is (%min)';

                $min_participant_text = str_replace("%min","$min_participant",__($min_participant_text, 'bookings-and-appointments-for-woocommerce'));

                $this->check_availabilty_while_add_to_cart = 1;
                wc_add_notice($min_participant_text, 'error');
                return false;
            }
         }

        return true;
    }

    /**
     * Get the booking cost from session and make it part of cart item
     * @param obj cart
     */
    public function phive_get_cart_from_session($cart_item)
    {
        if (isset($cart_item['phive_booked_price'])) {
            $cart_item['data']->set_price($cart_item['phive_booked_price']);
        }
        return $cart_item;
    }

    /**
     * Display the message item added to the cart.
     * @return String
     */
    public function phive_added_to_cart_message($message, $products)
    {
        $is_booking_product = false;
        foreach ($products as $product_id => $quantity) {

            if (ph_is_bookable_product($product_id)) {
                $is_booking_product = true;
                break;
            }
        }

        // 143991 - Bookable product names are not getting translated on cart page and displaying the original name.
        $sitepress_active_check = class_exists('SitePress');
        if($sitepress_active_check && $is_booking_product)
        {
            $current_language   = apply_filters('wpml_current_language', NULL);
            $product_id         = apply_filters('wpml_object_id', $product_id, 'product', false, $current_language );
        }

        if ($is_booking_product) {
            //if directly redirected to cart, continue booking message should come instead of view cart.
            if ('yes' === get_option('woocommerce_cart_redirect_after_add')) {
                $product = wc_get_product($product_id);
                $added_text = sprintf(__('%s has been added to your cart.', 'bookings-and-appointments-for-woocommerce'), $product->get_name());
                $return_to = apply_filters('woocommerce_continue_shopping_redirect', wc_get_raw_referer() ? wp_validate_redirect(wc_get_raw_referer(), false) : wc_get_page_permalink('shop'));
                $message = sprintf('<a href="%s" tabindex="1" class="button wc-forward">%s</a>%s', esc_url($return_to), esc_html__('Continue booking', 'bookings-and-appointments-for-woocommerce'), esc_html($added_text));
            } else {
                // $message = '<a href="'.wc_get_page_permalink( 'cart' ).'" class="button wc-forward">'.__('View cart','bookings-and-appointments-for-woocommerce').'</a> '.__('Booking Done. Please check cart for payment.','bookings-and-appointments-for-woocommerce');
                $product = wc_get_product($product_id);
                // error_log(print_r($product->get_name()));
                $added_text = sprintf(__('%s has been added to your cart.', 'bookings-and-appointments-for-woocommerce'), $product->get_name());
                $message = sprintf('<a href="' . wc_get_page_permalink('cart') . '" tabindex="1" class="button wc-forward">%s</a>%s', esc_html__('View cart', 'bookings-and-appointments-for-woocommerce'), esc_html($added_text));
            }
            $message = apply_filters('ph_booking_add_to_cart_message_html', $message, $products);
        }
        return $message;
    }

    /**
     * Forcefully convert WP date format into 'Y-m-d' format
     * @param date in WP date format.
     * @return  date in 'Y-m-d' format.
     */
    /*private function phive_format_date($date){
    $new_date = DateTime::createFromFormat( get_option( 'date_format' ), $date );
    if( is_object($new_date) ){
    return $new_date->format('Y-m-d');
    }else{
    //The format 'F j, Y' is not working with 'createFromFormat'
    return date('Y-m-d', strtotime($date));
    }

    }*/

    private function phive_buffer_before_time($from, $buffer_period, $book_interval, $buffer_before, $buffer_after = '0')
    {

        if ($buffer_before == '0') {
            return;
        } else {
            switch ($buffer_period) {
                case 'day':
                    $buffer_before_time = date('Y-m-d', (strtotime($from) - ($buffer_before * 3600 * 24)));
                    break;
                case 'hour':
                    $buffer_before_time = date('Y-m-d H:i', (strtotime($from) - ($buffer_before * 3600)));
                    break;
                case 'minute':
                    $buffer_after = isset($buffer_after) ? $buffer_after : '00';
                    $buffer_before_time = date('Y-m-d H:i', (strtotime($from) - ($buffer_before * 60)));
                    break;
            }
            return $buffer_before_time;
        }
    }

    private function phive_buffer_after_time($from, $to = '', $buffer_period='', $book_interval='', $buffer_before = '0', $buffer_after='', $product_id='')
    {
        $interval = get_post_meta($product_id, "_phive_book_interval", 1);
        $interval_period = get_post_meta($product_id, '_phive_book_interval_period', 1);
        
        $to = !empty($to) ? $to : $from;
        if ($buffer_after == '0') {
            return;
        } else {
            switch ($buffer_period) {
                case 'day':
                    $buffer_after_time = date('Y-m-d', (strtotime($to) + ($buffer_after * 3600 * 24)));
                    break;
                case 'hour':
                    $buffer_after_time = date('Y-m-d H:i', (strtotime($to) + ($buffer_after * 3600)));
                    break;
                case 'minute':
                    $buffer_before = isset($buffer_before) ? $buffer_before : '00';
                    $buffer_after_from = date("Y-m-d H:i", strtotime("+$interval $interval_period", strtotime($to)));
                    $buffer_after_time = date('Y-m-d H:i', (strtotime($buffer_after_from) + ($buffer_after * 60)));
                    break;
            }
            return $buffer_after_time;
        }
    }

    /**
     * Add booking info into the cart.
     * @return array
     */
    public function phive_add_booking_infos_with_cart_item($cart_item_data, $product_id, $variation_id)
    {
        // Return if not a bookable product
        if( !ph_is_bookable_product($product_id) ) {
            return $cart_item_data;
        }

        if (isset($_REQUEST['phive_book_from_date'])) {

            $product_id = Ph_Bookings_General_Functions_Class::get_default_lang_product_id($product_id); //WPML compatibilty

            if (!class_exists('phive_booking_availability_scheduler')) {
                include_once 'class-ph-booking-availability-scheduler.php';
            }
            $cron_manager = new phive_booking_availability_scheduler();
            $cart_item_data['phive_book_from_date'] = sanitize_text_field($_REQUEST['phive_book_from_date']);
            $cart_item_data['phive_display_time_from'] = sanitize_text_field($_REQUEST['phive_display_time_from']);
            $cart_item_data['phive_display_time_to'] = sanitize_text_field($_REQUEST['phive_display_time_to']);
            $cart_item_data['phive_book_to_date'] = sanitize_text_field($_REQUEST['phive_book_to_date']);
            $cart_item_data['phive_booked_price'] = sanitize_text_field($_REQUEST['phive_booked_price']);
            $cart_item_data['phive_booked_persons'] = isset($_REQUEST['phive_book_persons']) ? $_REQUEST['phive_book_persons'] : array();
            $cart_item_data['phive_booked_resources'] = isset($_REQUEST['phive_book_resources']) ? $_REQUEST['phive_book_resources'] : '';
            $cart_item_data['phive_booked_assets'] = isset($_REQUEST['phive_book_assets']) ? $_REQUEST['phive_book_assets'] : '';

            $cart_item_data['phive_book_additional_notes_text'] = isset($_REQUEST['phive_book_additional_notes_text']) ? $_REQUEST['phive_book_additional_notes_text'] : '';
            $cart_item_data['product_id'] = $product_id;
            $_persons_as_booking = ($_REQUEST['persons_as_booking']);
            $interval = get_post_meta($product_id, "_phive_book_interval", 1);
            $interval_period = get_post_meta($product_id, '_phive_book_interval_period', 1);
            // Handle Booking Per Night
            $book_to_date_with_night = null;
            if ($interval_period == 'day') {
                $enable_per_night = get_post_meta($product_id, '_phive_book_charge_per_night', true);
                if ($enable_per_night == 'yes') {
                    $book_to_date = date_create($cart_item_data['phive_book_to_date']);
                    $book_to_date->modify("-1 days");
                    $book_to_date_with_night = array($book_to_date->format('Y-m-d'));
                    $cart_item_data['_ph_book_to_date_with_night'] = $book_to_date_with_night;
                }
            }

            $_persons_as_booking = apply_filters('ph_is_persons_as_booking', ($_persons_as_booking == 'yes'), $product_id);
            $persons_as_booking = $_persons_as_booking ? 'yes' : 'no';

            if ($persons_as_booking == 'yes') {

                $person_count = array_sum($cart_item_data['phive_booked_persons']);
                $person_count = $person_count == 0 ? 1 : $person_count;
                $cart_item_data['persons_as_booking'] = $persons_as_booking;
                $cart_item_data['phive_booking_freezer_id'] = $cron_manager->freeze_booking_slot($product_id, $cart_item_data['phive_book_from_date'], $cart_item_data['phive_book_to_date'], $cart_item_data['phive_booked_assets'], array('yes'), $person_count, '', '', $book_to_date_with_night, isset($_REQUEST['phive_book_persons']) ? $_REQUEST['phive_book_persons'] : array());
            } else {
                $cart_item_data['phive_booking_freezer_id'] = $cron_manager->freeze_booking_slot($product_id, $cart_item_data['phive_book_from_date'], $cart_item_data['phive_book_to_date'], $cart_item_data['phive_booked_assets'], '', '', '', '', $book_to_date_with_night, array());
            }

            // Buffer time
            $enable_buffer = get_post_meta($product_id, '_phive_enable_buffer', 1);
            $buffer_period = get_post_meta($product_id, "_phive_buffer_period", 1);
            $buffer_before = get_post_meta($product_id, "_phive_buffer_before", 1);
            $buffer_after = get_post_meta($product_id, "_phive_buffer_after", 1);

            $book_interval = get_post_meta($product_id, "_phive_book_interval", 1);
            if ($enable_buffer == 'yes') {
                $buffer_before_from = $this->phive_buffer_before_time($cart_item_data['phive_book_from_date'], $buffer_period, $book_interval, $buffer_before, $buffer_after);

                $buffer_after_to = $this->phive_buffer_after_time($cart_item_data['phive_book_from_date'], $cart_item_data['phive_book_to_date'], $buffer_period, $book_interval, $buffer_before, $buffer_after, $product_id);
                switch ($interval_period) {
                    case 'day':
                        $buffer_after_from = date("Y-m-d", strtotime("+1 day", strtotime($cart_item_data['phive_book_to_date'])));

                        $buffer_before_to = date("Y-m-d", strtotime("-1 day", strtotime($cart_item_data['phive_book_from_date'])));
                        break;
                    case 'hour':
                        $buffer_after_from = date("Y-m-d H:i", strtotime("+$interval $interval_period", strtotime($cart_item_data['phive_book_to_date'])));

                        $buffer_before_to = date("Y-m-d H:i", strtotime("-$interval $interval_period", strtotime($cart_item_data['phive_book_from_date'])));
                        break;
                    case 'minute':
                        $buffer_after_from = date("Y-m-d H:i", strtotime("+$interval $interval_period", strtotime($cart_item_data['phive_book_to_date'])));

                        $buffer_before_to = date("Y-m-d H:i", strtotime("-$interval $interval_period", strtotime($cart_item_data['phive_book_from_date'])));
                        break;
                }

                if ($buffer_before_from == '') {
                    $buffer_before_to = '';
                }
                if ($buffer_after_to == '') {
                    $buffer_after_from = '';
                }
                if ($persons_as_booking == 'yes') {
                    $cart_item_data['phive_booking_buffer_from_freezer_id'] = $cron_manager->freeze_booking_slot($product_id, $buffer_before_from, $buffer_before_to, $cart_item_data['phive_booked_assets'], array('yes'), $person_count, 'yes', 'buffer-before', $book_to_date_with_night, isset($_REQUEST['phive_book_persons']) ? $_REQUEST['phive_book_persons'] : array());
                    $cart_item_data['phive_booking_buffer_to_freezer_id'] = $cron_manager->freeze_booking_slot($product_id, $buffer_after_from, $buffer_after_to, $cart_item_data['phive_booked_assets'], array('yes'), $person_count, 'yes', 'buffer-after', $book_to_date_with_night, isset($_REQUEST['phive_book_persons']) ? $_REQUEST['phive_book_persons'] : array());
                } else {
                    $cart_item_data['phive_booking_buffer_from_freezer_id'] = $cron_manager->freeze_booking_slot($product_id, $buffer_before_from, $buffer_before_to, $cart_item_data['phive_booked_assets'], '', '', 'yes', 'buffer-before', $book_to_date_with_night, array());
                    // $cart_item_data['phive_booking_buffer_to_freezer_id']                 = $cron_manager->freeze_booking_slot( $product_id, $buffer_after_from, $buffer_after_to,$cart_item_data['phive_booked_assets'],'','','yes','buffer-before', $book_to_date_with_night,array() );

                    $cart_item_data['phive_booking_buffer_to_freezer_id'] = $cron_manager->freeze_booking_slot($product_id, $buffer_after_from, $buffer_after_to, $cart_item_data['phive_booked_assets'], '', '', 'yes', 'buffer-after', $book_to_date_with_night, array());
                }
            }
            if (!empty($cart_item_data['phive_booked_assets'])) {
                $asset_id = $cart_item_data['phive_booked_assets'];
                $ph_cache_obj = new phive_booking_cache_manager();
                $ph_cache_obj->ph_unset_cache($asset_id);
            }
            // error_log('cart_item_data phive_add_booking_infos_with_cart_item : '.print_r($cart_item_data,1));
            // 96421
            do_action('ph_bookings_insert_data_in_availability_table_from_cart', $cart_item_data, '', '');
    
        }
        return $cart_item_data;
    }

    /**
     * Show booking infos with each cart items
     * @return array
     */
    public function phive_disply_item_booking_infos($item_data, $cart_item)
    {
        $product_id = Ph_Bookings_General_Functions_Class::get_default_lang_product_id($cart_item['product_id']); //WPML compatibilty
        $interval_period = get_post_meta($product_id, "_phive_book_interval_period", 1);
        $interval = get_post_meta($product_id, "_phive_book_interval", 1);
        $resources_type = get_post_meta($product_id, "_phive_booking_resources_type", 1);
        $interval_type = get_post_meta($product_id, "_phive_book_interval_type", 1);

        // #91928
        $display_settings = get_option('ph_bookings_display_settigns');
        $booking_end_time_display_cart_order_emails = (isset($display_settings['booking_end_time_display_cart_order_emails']) && $display_settings['booking_end_time_display_cart_order_emails']=='no')?false:true;
        
        //If case of fixed block of time, show only from date *old
        if (array_key_exists('phive_book_from_date', $cart_item) && $cart_item['phive_book_from_date'] == $cart_item['phive_book_to_date'] && array_key_exists('phive_display_time_from', $cart_item) && array_key_exists('phive_display_time_to', $cart_item) && $cart_item['phive_display_time_from'] != $cart_item['phive_display_time_to']) 
        {
            $item_data[] = array(
                'key' => __('Booked from', 'bookings-and-appointments-for-woocommerce'),
                'value' => $cart_item['phive_display_time_from'], // phive_display_time_from is being set by addon, don't apply formatting
            );

            // #91928
            if($booking_end_time_display_cart_order_emails == true)
            {
                $item_data[] = array(
                    'key' => __('Booked to', 'bookings-and-appointments-for-woocommerce'),
                    'value' => $cart_item['phive_display_time_to'], // phive_display_time_to is being set by addon, don't apply formatting
                );
            }
        } else if (array_key_exists('phive_book_from_date', $cart_item) && $cart_item['phive_book_from_date'] == $cart_item['phive_book_to_date']) {
            $item_data[] = array(
                'key' => __('Booked', 'bookings-and-appointments-for-woocommerce'),
                'value' => empty($cart_item['phive_display_time_from']) ? Ph_Bookings_General_Functions_Class::phive_get_date_in_wp_format($cart_item['phive_book_from_date']) : $cart_item['phive_display_time_from'], // phive_display_time_from is being set by addon, don't apply formatting
            );
        } else {
            if (array_key_exists('phive_book_from_date', $cart_item)) {
                $item_data[] = array(
                    'key' => __('Booked from', 'bookings-and-appointments-for-woocommerce'),
                    'value' => empty($cart_item['phive_display_time_from']) ? Ph_Bookings_General_Functions_Class::phive_get_date_in_wp_format($cart_item['phive_book_from_date']) : $cart_item['phive_display_time_from'], // phive_display_time_from is being set by addon, don't apply formatting
                );
            }
            if (array_key_exists('phive_book_to_date', $cart_item)) {
                if (($interval_period == 'minute') || ($interval_period == 'hour') && $interval_type != 'fixed') {
                    $cart_item['phive_book_to_date'] = ph_wp_date('Y-m-d H:i', strtotime("+$interval $interval_period", strtotime($cart_item['phive_book_to_date'])));
                }

                // #91928
                if($booking_end_time_display_cart_order_emails == true)
                {
                    $item_data[] = array(
                        'key' => __('Booked to', 'bookings-and-appointments-for-woocommerce'),
                        'value' => empty($cart_item['phive_display_time_to']) ? Ph_Bookings_General_Functions_Class::phive_get_date_in_wp_format($cart_item['phive_book_to_date']) : $cart_item['phive_display_time_to'], // phive_display_time_to is being set by addon, don't apply formatting
                    );
                }
            }
        }

        // Display Asset Name
        if (!empty($cart_item['phive_booked_assets'])) {
            $choosen_asset_id = $cart_item['phive_booked_assets'];
            $asset_label = $cart_item['data']->get_meta('_phive_booking_assets_label');
            $asset_label = ph_wpml_translate_single_string('Assets_Main_Label', $asset_label);
            if (empty($this->assets_settings)) {
                $this->assets_settings = get_option('ph_booking_settings_assets', array());
            }

            if (!empty($this->assets_settings['_phive_booking_assets'][$choosen_asset_id])) 
            {
                $asset_name = apply_filters( 'wpml_translate_single_string', $this->assets_settings['_phive_booking_assets'][$choosen_asset_id]['ph_booking_asset_name'], 'ph_booking_plugins', $choosen_asset_id );
                $item_data[] = array(
                    'key' => !empty($asset_label) ? __($asset_label, 'bookings-and-appointments-for-woocommerce') : __('Asset', 'bookings-and-appointments-for-woocommerce'),
                    'value' => __($asset_name, 'bookings-and-appointments-for-woocommerce'),
                );
            }
        }

        //Disply resources details with cart items
        if (!empty($cart_item['phive_booked_resources'])) {

            $resources_pricing_rules = get_post_meta($product_id, "_phive_booking_resources_pricing_rules", 1);

            // Looping through the rule and assign the corresponding rule value given by customer
            foreach ($resources_pricing_rules as $key => $rule) {

                if ($rule['ph_booking_resources_auto_assign'] == 'yes' && $resources_type != 'single') {
                    // continue;
                }
                if ($resources_type == 'single') {
                    if ($cart_item['phive_booked_resources'] == $rule['ph_booking_resources_name']) {
                        $rule['ph_booking_resources_name'] = apply_filters( 'wpml_translate_single_string', $rule['ph_booking_resources_name'], 'bookings-and-appointments-for-woocommerce', 'resource_name_'.$rule['ph_booking_resources_name'] );
                        $item_data[] = array(
                            'key' => __($rule['ph_booking_resources_name'], 'bookings-and-appointments-for-woocommerce'),
                            'value' => __('yes', 'bookings-and-appointments-for-woocommerce'),
                        );
                    }
                } else {
                    if ($cart_item['phive_booked_resources'][$key] == 'yes') {
                        $rule['ph_booking_resources_name'] = apply_filters( 'wpml_translate_single_string', $rule['ph_booking_resources_name'], 'bookings-and-appointments-for-woocommerce', 'resource_name_'.$rule['ph_booking_resources_name'] );
                        $item_data[] = array(
                            'key' => __($rule['ph_booking_resources_name'], 'bookings-and-appointments-for-woocommerce'),
                            'value' => __($cart_item['phive_booked_resources'][$key], 'bookings-and-appointments-for-woocommerce'),
                        );
                    }
                }

            }
        }

        //Disply persons details with cart items
        if (!empty($cart_item['phive_booked_persons'])) {

            $persons_pricing_rules = get_post_meta($product_id, "_phive_booking_persons_pricing_rules", 1);

            // Looping through the rule and assign the corresponding rule value given by customer
            foreach ($persons_pricing_rules as $key => $rule) {

                if (isset($cart_item['phive_booked_persons'][$key]) && !empty($cart_item['phive_booked_persons'][$key])) //so that if the participant value is empty or zero, the itemdata should not be shown into the cart
                {
                    $rule['ph_booking_persons_rule_type'] = apply_filters( 'wpml_translate_single_string', $rule['ph_booking_persons_rule_type'], 'bookings-and-appointments-for-woocommerce', 'participant_name_'.$rule['ph_booking_persons_rule_type'] );
                    $item_data[] = array(
                        'key' => __($rule['ph_booking_persons_rule_type'], 'bookings-and-appointments-for-woocommerce'),
                        'value' => __($cart_item['phive_booked_persons'][$key], 'bookings-and-appointments-for-woocommerce'),
                    );
                }
            }
        }
        if (!empty($cart_item['phive_book_additional_notes_text'])) {

            $addition_notes_label = get_post_meta($product_id, '_phive_additional_notes_label', 1);
            $addition_notes_label = ph_wpml_translate_single_string('Additional_Notes_Label', $addition_notes_label);
            $item_data[] = array(
                'key' => __($addition_notes_label, 'bookings-and-appointments-for-woocommerce'),
                'value' => __($cart_item['phive_book_additional_notes_text'], 'bookings-and-appointments-for-woocommerce'),
            );
        }
        if (!empty($cart_item['phive_booking_buffer_from_freezer_id'])) {
            $this->set_crone_session($cart_item['phive_booking_buffer_from_freezer_id']);
        }
        if (!empty($cart_item['phive_booking_buffer_to_freezer_id'])) {
            $this->set_crone_session($cart_item['phive_booking_buffer_to_freezer_id']);
        }
        if (!empty($cart_item['phive_booking_freezer_id'])) {
            $this->set_crone_session($cart_item['phive_booking_freezer_id']);
        }
        return $item_data;
    }

    /**
     * Saving the crone details into session to delete the crone once placed the order successfully.
     */
    private function set_crone_session($freezer_id)
    {
        $crone_ids = WC()->session->get('ph_crone_ids');
        if (empty($crone_ids)) {
            $crone_ids = array($freezer_id);
        } elseif (!in_array($freezer_id, $crone_ids)) {
            array_push($crone_ids, $freezer_id);
        }

        // update the session by unset then set agin.
        WC()->session->__unset('ph_crone_ids');
        WC()->session->set('ph_crone_ids', $crone_ids);
    }

    /*
     * Render the booking calendar
     */
    public function phive_add_booking_product_to_cart()
    {
        ob_start();
        include 'frondend/html-ph-booking-add-to-cart.php';
        echo ob_get_clean();
    }

}
new phive_booking_cart_decorator;
