<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class phive_booking_product_options {
	public function __construct() {
		add_action( 'woocommerce_product_data_panels', array( $this, 'booking_options_product_tab_content' ) );
		add_action( 'woocommerce_process_product_meta_phive_booking', array( $this,'save_booking_option_field' ) ); //The filter name should match with product class name		
		add_filter( 'product_type_selector', array( $this, 'add_booking_product_product' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'custom_product_tabs' ) );
		add_action( 'product_type_options', array( $this, 'virtual_downloadable_checkbox' ) );
	}

	public function virtual_downloadable_checkbox( $product_type_options ) {

		$product_type_options['virtual']['wrapper_class'] 		.= ' show_if_phive_booking';
		$product_type_options['downloadable']['wrapper_class'] 	.= ' show_if_phive_booking';

		return $product_type_options;
	}
	
	/**
     * Add a custom product tab.
     */
    function custom_product_tabs( $tabs) {
        
        $tabs['phive_booking'] = array(
            'label'     => __( 'Bookings', 'bookings-and-appointments-for-woocommerce' ),
            'target'    => 'booking_options',
            'class'     => array( 'show_if_phive_booking','hide_if_simple', 'hide_if_variable', 'hide_if_grouped', 'hide_if_external', 'hide_if_gift-card', 'hide_if_course' ),
            'priority' => 1,
        );
        $tabs['phive_booking_availablity'] = array(
            'label'     => __( 'Booking Availability', 'bookings-and-appointments-for-woocommerce' ),
            'target'    => 'booking_availability',
            'class'     => array( 'show_if_phive_booking','hide_if_simple', 'hide_if_variable', 'hide_if_grouped', 'hide_if_external', 'hide_if_gift-card', 'hide_if_course' ),
            'priority' => 2,
        );
        $tabs['phive_booking_pricing'] = array(
            'label'     => __( 'Booking Costs', 'bookings-and-appointments-for-woocommerce' ),
            'target'    => 'booking_pricing',
            'class'     => array( 'show_if_phive_booking','hide_if_simple', 'hide_if_variable', 'hide_if_grouped', 'hide_if_external', 'hide_if_gift-card', 'hide_if_course' ),
            'priority' => 3,
        );
        $tabs['phive_booking_persons'] = array(
            'label'     => __( 'Booking Participants', 'bookings-and-appointments-for-woocommerce' ),
            'target'    => 'booking_persons',
            'class'     => array( 'show_if_phive_booking','hide_if_simple', 'hide_if_variable', 'hide_if_grouped', 'hide_if_external', 'hide_if_gift-card', 'hide_if_course' ),
            'priority' => 4,
        );
        $tabs['phive_booking_resorces'] = array(
            'label'     => __( 'Booking Resources', 'bookings-and-appointments-for-woocommerce' ),
            'target'    => 'booking_resorces',
            'class'     => array( 'show_if_phive_booking','hide_if_simple', 'hide_if_variable', 'hide_if_grouped', 'hide_if_external', 'hide_if_gift-card', 'hide_if_course' ),
            'priority' => 5,
        );
        $tabs['phive_booking_assets'] = array(
            'label'     => __( 'Booking Assets', 'bookings-and-appointments-for-woocommerce' ),
            'target'    => 'booking_assets',
            'class'     => array( 'show_if_phive_booking','hide_if_simple', 'hide_if_variable', 'hide_if_grouped', 'hide_if_external', 'hide_if_gift-card', 'hide_if_course' ),
            'priority' => 6,
        );
        return $tabs;
    }


	/**
	 * Contents of the booking options product tab.
	 */
	function booking_options_product_tab_content() {
		global $post;
		include("views/html-ph-booking-product-admin-options.php");
		include("views/html-ph-booking-product-admin-availability.php");
		include("views/html-ph-booking-product-admin-persons.php");
		include("views/html-ph-booking-product-admin-resources.php");
		include("views/html-ph-booking-product-admin-assets.php");
		include("views/html-ph-booking-product-admin-pricing.php");
	}

	/**
	 * Add to product type drop down.
	 */
	function add_booking_product_product( $types ){
		// Key should be exactly the same as in the class
		$types[ 'phive_booking' ] = __( 'Bookable product','bookings-and-appointments-for-woocommerce' );
		return $types;
	}


	/**
	 * Save the custom fields.
	 */
	function save_booking_option_field( $post_id ) {

		//Reset Stock values
		update_post_meta( $post_id, '_stock_status', 'instock' );
		update_post_meta( $post_id, '_manage_stock', 'no');
		
		//Persons tab
		update_post_meta( $post_id, '_phive_booking_person_enable', isset( $_POST['_phive_booking_person_enable'] ) ? $_POST['_phive_booking_person_enable'] : 'no' ); //Checkbox field, if unchecked, $_POST may not be contain this index
		if( isset( $_POST['_phive_booking_person_enable'] ) && $_POST['_phive_booking_person_enable'] == 'yes' ){
			update_post_meta( $post_id, '_phive_booking_persons_as_booking', isset( $_POST['_phive_booking_persons_as_booking'] ) ? $_POST['_phive_booking_persons_as_booking'] : 'no' ); //Checkbox field, if unchecked, $_POST may not be contain this index
		}else{
			update_post_meta( $post_id, '_phive_booking_persons_as_booking', 'no' );
		}
		update_post_meta( $post_id, '_phive_booking_persons_multuply_all_cost', isset( $_POST['_phive_booking_persons_multuply_all_cost'] ) ? $_POST['_phive_booking_persons_multuply_all_cost'] : 'no' ); //Checkbox field, if unchecked, $_POST may not be contain this index
		update_post_meta( $post_id, '_phive_booking_persons_pricing_rules', $this->validate_persons_pricing_rules() );
		update_post_meta( $post_id, '_phive_booking_participant_pricing_rules', $this->validate_participant_pricing_rules() );
		update_post_meta( $post_id, '_phive_booking_maximum_number_of_allowed_participant',isset( $_POST['_phive_booking_maximum_number_of_allowed_participant'] ) ? $_POST['_phive_booking_maximum_number_of_allowed_participant'] : '' );

		update_post_meta( $post_id, '_phive_booking_minimum_number_of_required_participant',isset( $_POST['_phive_booking_minimum_number_of_required_participant'] ) ? $_POST['_phive_booking_minimum_number_of_required_participant'] : 0 );
		
		
		//resources tab
		// if ( isset( $_POST['ph_booking_resources_name'] ) || isset( $_POST['_phive_booking_resources_enable'] ) ){
			update_post_meta( $post_id, '_phive_booking_resources_enable', isset( $_POST['_phive_booking_resources_enable'] ) ? $_POST['_phive_booking_resources_enable'] : 'no' ); //Checkbox field, if unchecked, $_POST may not be contain this index
			update_post_meta( $post_id, '_phive_booking_resources_pricing_rules', $this->validate_resources_pricing_rules() );
			update_post_meta( $post_id, '_phive_booking_resources_label', isset( $_POST['_phive_booking_resources_label'] ) ? $_POST['_phive_booking_resources_label'] : '' );

			if(isset( $_POST['_phive_booking_resources_label'] ))
			{
				ph_wpml_register_string_for_translation('Resource_Main_Label', $_POST['_phive_booking_resources_label'] );
			}
			update_post_meta( $post_id, '_phive_booking_resources_type', isset( $_POST['_phive_booking_resources_type'] ) ? $_POST['_phive_booking_resources_type'] : '' );
			update_post_meta( $post_id, '_phive_booking_single_resources_mandatory_enable', isset( $_POST['_phive_booking_single_resources_mandatory_enable'] ) ? $_POST['_phive_booking_single_resources_mandatory_enable'] : 'no' ); //Checkbox field, if unchecked, $_POST may not be contain this index
		// }

		//Availability tab
		if ( isset( $_POST['_phive_first_availability'] ) ){
			$unavailable 			= isset( $_POST['_phive_un_availability'] ) ? 'yes' : 'no';
			$restrict_start_day 	= isset( $_POST['_phive_restrict_start_day'] ) ? 'yes' : 'no';
			update_post_meta( $post_id, '_phive_first_availability', sanitize_text_field( $_POST['_phive_first_availability'] ) );
			update_post_meta( $post_id, '_phive_last_availability', sanitize_text_field( $_POST['_phive_last_availability'] ) );
			update_post_meta( $post_id, '_phive_fixed_availability_from', sanitize_text_field( $_POST['_phive_fixed_availability_from'] ) );
			update_post_meta( $post_id, '_phive_fixed_availability_to', sanitize_text_field( $_POST['_phive_fixed_availability_to'] ) );
			update_post_meta( $post_id, '_phive_restrict_start_day', sanitize_text_field( $restrict_start_day ) );
			$booking_start_days = ! empty($_POST['_phive_booking_start_days']) ? $_POST['_phive_booking_start_days'] : array();
			update_post_meta( $post_id, '_phive_booking_start_days', $booking_start_days );
			update_post_meta( $post_id, '_phive_booking_availability_rules', $this->validate_availability_rules() );
			update_post_meta( $post_id, '_phive_un_availability', sanitize_text_field( $unavailable ) );
			update_post_meta( $post_id, '_phive_first_availability_interval_period', sanitize_text_field( $_POST['_phive_first_availability_interval_period'] ) );
			update_post_meta( $post_id, '_phive_last_availability_interval_period', sanitize_text_field( $_POST['_phive_last_availability_interval_period'] ) );
			update_post_meta( $post_id, '_phive_first_booking_availability_type', isset($_POST['_phive_first_booking_availability_type'])?$_POST['_phive_first_booking_availability_type']:'today' );
		}

		// pricing tab
		if ( isset( $_POST['ph_booking_base_cost'] ) ){
			update_post_meta( $post_id, '_phive_booking_pricing_base_cost', sanitize_text_field( $_POST['ph_booking_base_cost'] ) );
			update_post_meta( $post_id, '_phive_booking_pricing_cost_per_unit', sanitize_text_field( $_POST['ph_booking_cost_per_unit'] ) );
			update_post_meta( $post_id, '_phive_booking_pricing_display_cost', sanitize_text_field( $_POST['ph_booking_display_cost'] ) );
			update_post_meta( $post_id, '_phive_booking_pricing_rules', $this->validate_pricing_rules() );
			
			// display cost suffix (per hour, day, etc.) 
			update_post_meta( $post_id, '_phive_booking_pricing_display_cost_suffix', sanitize_text_field( $_POST['ph_booking_display_cost_suffix']));
		}

		// Booking tab
		if ( isset( $_POST['_phive_book_interval_type'] ) ){
			//Set default value of interval 1
			$interval 				= !empty($_POST['_phive_book_interval']) ? sanitize_text_field( $_POST['_phive_book_interval'] ) : 1;
			$cancel_interval 		= !empty($_POST['_phive_cancel_interval']) ? sanitize_text_field( $_POST['_phive_cancel_interval'] ) : 1;
			$allow_cancel 			= isset( $_POST['_phive_book_allow_cancel'] ) ? 'yes' : 'no';
			$additional_notes 		= isset( $_POST['_phive_book_additional_notes'] ) ? 'yes' : 'no';
			$required_confirmation 	= isset( $_POST['_phive_book_required_confirmation'] ) ? 'yes' : 'no';
			$book_for_night 		= isset( $_POST['_phive_book_charge_per_night'] ) ? 'yes' : 'no';
			$enable_buffer 			= isset( $_POST['_phive_enable_buffer'] ) ? 'yes' : 'no';
			$phive_buffer_before 	= !empty( $_POST['_phive_buffer_before'] ) ? $_POST['_phive_buffer_before'] : '0';
			$phive_buffer_after 	= !empty( $_POST['_phive_buffer_after'] ) ? $_POST['_phive_buffer_after'] : '0';
			$across_the_day 			= isset( $_POST['_phive_enable_across_the_day'] ) ? 'yes' : 'no';
			$end_time_display 			= isset( $_POST['_phive_enable_end_time_display'] ) ? 'yes' : 'no';
			$auto_select_min_bookings 	= isset( $_POST['_phive_auto_select_min_booking'] ) ? 'yes' : 'no';
			
			update_post_meta( $post_id, '_phive_book_allowed_per_slot', sanitize_text_field( $_POST['_phive_book_allowed_per_slot'] ) );
			update_post_meta( $post_id, '_phive_book_interval', $interval );
			update_post_meta( $post_id, '_phive_cancel_interval', $cancel_interval );
			update_post_meta( $post_id, '_phive_book_interval_type', sanitize_text_field( $_POST['_phive_book_interval_type'] ) );
			update_post_meta( $post_id, '_phive_book_interval_period', sanitize_text_field( $_POST['_phive_book_interval_period'] ) );
			update_post_meta( $post_id, '_phive_cancel_interval_period', sanitize_text_field( $_POST['_phive_cancel_interval_period'] ) );
			update_post_meta( $post_id, '_phive_additional_notes_label', sanitize_text_field( $_POST['_phive_additional_notes_label'] ) );
			ph_wpml_register_string_for_translation('Additional_Notes_Label', $_POST['_phive_additional_notes_label']);

			update_post_meta( $post_id, '_phive_book_working_hour_start', sanitize_text_field( $_POST['_phive_book_working_hour_start'] ) );
			/*update_post_meta( $post_id, '_phive_book_checkin', sanitize_text_field( $_POST['_phive_book_checkin'] ) );
			update_post_meta( $post_id, '_phive_book_checkout', sanitize_text_field( $_POST['_phive_book_checkout'] ) );*/
			update_post_meta( $post_id, '_phive_book_working_hour_end', sanitize_text_field( $_POST['_phive_book_working_hour_end'] ) );
			update_post_meta( $post_id, '_phive_book_min_allowed_booking', sanitize_text_field( $_POST['_phive_book_min_allowed_booking'] ) );
			update_post_meta( $post_id, '_phive_auto_select_min_booking', $auto_select_min_bookings );
			update_post_meta( $post_id, '_phive_book_max_allowed_booking', sanitize_text_field( $_POST['_phive_book_max_allowed_booking'] ) );
			update_post_meta( $post_id, '_phive_book_allow_cancel', $allow_cancel );
			update_post_meta( $post_id, '_phive_book_additional_notes', $additional_notes );
			update_post_meta( $post_id, '_phive_book_required_confirmation', $required_confirmation );
			if( sanitize_text_field( $_POST['_phive_book_interval_period'] ) == 'day' && sanitize_text_field( $_POST['_phive_book_interval_type'] ) == 'customer_choosen' ){
				update_post_meta( $post_id, '_phive_book_charge_per_night', $book_for_night );
			}else{
				update_post_meta( $post_id, '_phive_book_charge_per_night', 'no' );
			}
			update_post_meta( $post_id, '_phive_enable_buffer', $enable_buffer );
			update_post_meta( $post_id, '_phive_buffer_before', ($phive_buffer_before) );
			update_post_meta( $post_id, '_phive_buffer_after', ( $phive_buffer_after) );
			update_post_meta( $post_id, '_phive_buffer_period', sanitize_text_field( $_POST['_phive_buffer_period'] ) );
			update_post_meta( $post_id, '_phive_display_bookings_capacity', isset( $_POST['_phive_display_bookings_capacity'] ) ? 'yes' : 'no' );
			update_post_meta( $post_id, '_phive_enable_across_the_day', $across_the_day );
			update_post_meta( $post_id, '_phive_enable_end_time_display', $end_time_display );
			update_post_meta( $post_id, '_phive_remainng_bokkings_text', isset( $_POST['_phive_remainng_bokkings_text'] ) ? $_POST['_phive_remainng_bokkings_text'] : 'Left' );
			ph_wpml_register_string_for_translation('Remaining_Bookings_Text', isset( $_POST['_phive_remainng_bokkings_text'] ) ? $_POST['_phive_remainng_bokkings_text'] : 'Left');

		}
		//Assets tab
		if( isset($_POST['phive_booking_assets']) ){
			update_post_meta( $post_id, '_phive_booking_assets_enable', isset( $_POST['_phive_booking_assets_enable'] ) ? $_POST['_phive_booking_assets_enable'] : 'no' ); //Checkbox field, if unchecked, $_POST may not be contain this index
			update_post_meta( $post_id, '_phive_booking_assets_label', isset( $_POST['_phive_booking_assets_label'] ) ? $_POST['_phive_booking_assets_label'] : '' );
			
			if(isset( $_POST['_phive_booking_assets_label'] ))
			{
				ph_wpml_register_string_for_translation('Assets_Main_Label', $_POST['_phive_booking_assets_label']);
			}
			update_post_meta( $post_id, '_phive_booking_assets_auto_assign', isset( $_POST['_phive_booking_assets_auto_assign'] ) ? $_POST['_phive_booking_assets_auto_assign'] : '' );
			update_post_meta( $post_id, '_phive_booking_assets_pricing_rules', $this->validate_assets_rules() );
		}
	}

	private function validate_assets_rules(){
		$rules = array();

		if( ! empty($_POST['ph_booking_asset']) ) {
			$asset_ids=array();
			foreach ($_POST['ph_booking_asset'] as $key => $value) {
				if(!in_array($_POST['ph_booking_asset'][$key], $asset_ids))
				{	
					$rules[] = array(
						'ph_booking_asset_id' 					=> $_POST['ph_booking_asset'][$key],
						'ph_booking_assets_base_cost' 			=> $_POST['ph_booking_assets_base_cost'][$key],
						'ph_booking_assets_cost_perblock' 		=> $_POST['ph_booking_assets_cost_perblock'][$key],
					);
					$asset_ids[]=$_POST['ph_booking_asset'][$key];
				}
			}
		}
		return $rules;
	}

	private function validate_resources_pricing_rules(){
		$rules = array();

		if( ! empty($_POST['ph_booking_resources_name']) ) {
			foreach ($_POST['ph_booking_resources_name'] as $key => $value) {
				$rules_array = array(
					'ph_booking_resources_name' 			=> $_POST['ph_booking_resources_name'][$key],
					'ph_booking_resources_cost' 			=> $_POST['ph_booking_resources_cost'][$key],
					'ph_booking_resources_auto_assign' 		=> $_POST['ph_booking_resources_auto_assign'][$key],
					'ph_booking_resources_per_person' 		=> $_POST['ph_booking_resources_per_person'][$key],
					'ph_booking_resources_per_slot' 		=> $_POST['ph_booking_resources_per_slot'][$key],
				);

				$rules[] = apply_filters('phive_save_extra_column_values_for_resource_cost_settings', $rules_array, $key, $_POST);

				do_action( 'wpml_register_single_string', 'bookings-and-appointments-for-woocommerce', 'resource_name_'.$value, $value );
			}
		}
		return $rules;
	}

	private function validate_persons_pricing_rules(){
		$rules = array();
		if( ! empty($_POST['ph_booking_persons_rule_type']) ) {
			foreach ($_POST['ph_booking_persons_rule_type'] as $key => $value) {
				$rules[] = array(
					'ph_booking_persons_rule_type' 			=> $_POST['ph_booking_persons_rule_type'][$key],
					'ph_booking_persons_rule_min' 			=> $_POST['ph_booking_persons_rule_min'][$key],
					'ph_booking_persons_rule_max' 			=> $_POST['ph_booking_persons_rule_max'][$key],
					'ph_booking_persons_rule_base_cost' 	=> $_POST['ph_booking_persons_rule_base_cost'][$key],
					'ph_booking_persons_rule_cost_per_unit' => $_POST['ph_booking_persons_rule_cost_per_unit'][$key],
					'ph_booking_persons_per_slot' 		=> $_POST['ph_booking_persons_per_slot'][$key],
				);
				do_action( 'wpml_register_single_string', 'bookings-and-appointments-for-woocommerce', 'participant_name_'.$value, $value );
			}
		}
		return $rules;
	}
	private function validate_participant_pricing_rules(){
			$rules = array();
			if( ! empty($_POST['ph_booking_participant_price_rule_type']) ) {
				foreach ($_POST['ph_booking_participant_price_rule_type'] as $key => $value) {
					$rules[] = array(
						'ph_booking_participant_rule_type' 			=> $_POST['ph_booking_participant_price_rule_type'][$key],
						'pricing_from_participant' 					=> $_POST['pricing_from_participant'][$key],
						'pricing_to_participant' 					=> $_POST['pricing_to_participant'][$key],
						'ph_booking_participant_rule_base_cost' 				=> $_POST['ph_booking_participant_rule_base_cost'][$key],
						'ph_booking_rule_cost_per_participant' 		=> $_POST['ph_booking_rule_cost_per_participant'][$key],
						'participantbasecost_operator' 				=> $_POST['participantbasecost_operator'][$key],
						'perparticipant_operator' 					=> $_POST['perparticipant_operator'][$key],
						'pricing_from_participant_date_from'        => (isset($_POST['pricing_from_participant_date_from']) && isset($_POST['pricing_from_participant_date_from'][$key]))?$_POST['pricing_from_participant_date_from'][$key]:'',
						'pricing_from_participant_date_to'			=> (isset($_POST['pricing_from_participant_date_to']) && isset($_POST['pricing_from_participant_date_to'][$key]))?$_POST['pricing_from_participant_date_to'][$key]:'',

						'pricing_from_participant_day_from'        => (isset($_POST['pricing_from_participant_day_from']) && isset($_POST['pricing_from_participant_day_from'][$key]))?$_POST['pricing_from_participant_day_from'][$key]:'',
						'pricing_from_participant_day_to'			=> (isset($_POST['pricing_from_participant_day_to']) && isset($_POST['pricing_from_participant_day_to'][$key]))?$_POST['pricing_from_participant_day_to'][$key]:'',

						'pricing_from_participant_block_count'        => (isset($_POST['pricing_from_participant_block_count']) && isset($_POST['pricing_from_participant_block_count'][$key]))?$_POST['pricing_from_participant_block_count'][$key]:'',
						'pricing_to_participant_block_count'			=> (isset($_POST['pricing_to_participant_block_count']) && isset($_POST['pricing_to_participant_block_count'][$key]))?$_POST['pricing_to_participant_block_count'][$key]:'',
						
					);
				}
			}
			return $rules;
		}

	private function validate_availability_rules(){
		$rules = array();
		if( ! empty($_POST['ph_booking_availability_type']) ) {
			foreach ($_POST['ph_booking_availability_type'] as $key => $value) {
				$rules[] = array(
					'availability_type' => $_POST['ph_booking_availability_type'][$key],
					
					'from_date' 		=> $_POST['ph_booking_from_date'][$key],
					'from_date_for_date_range_and_time' 		=> $_POST['ph_booking_from_date_for_date_range_and_time'][$key],
					'from_week_day' 	=> $_POST['ph_booking_from_week_day'][$key],
					'from_month' 		=> $_POST['ph_booking_from_month'][$key],
					'from_time' 		=> $_POST['ph_booking_from_time'][$key],

					'to_week_day' 		=> $_POST['ph_booking_to_week_day'][$key],
					'to_month' 			=> $_POST['ph_booking_to_month'][$key],
					'to_date' 			=> $_POST['ph_booking_to_date'][$key],
					'to_date_for_date_range_and_time' 			=> $_POST['ph_booking_to_date_for_date_range_and_time'][$key],
					'to_time' 			=> $_POST['ph_booking_to_time'][$key],
					'is_bokable'		=> $_POST['ph_booking_is_bookable'][$key],
				);
			}
		}
		return $rules;
	}

	private function validate_pricing_rules(){
		$rules = array();
		if( ! empty($_POST['ph_booking_pricing_rule_type']) ) {
			foreach ($_POST['ph_booking_pricing_rule_type'] as $key => $value) {
				$rules[] = array(
					'pricing_type' => $_POST['ph_booking_pricing_rule_type'][$key],
					
					'from_slot' 		=> $_POST['pricing_from_slot'][$key],
					'from_date' 		=> $_POST['pricing_from_date'][$key],
					'from_month' 		=> $_POST['pricing_from_month'][$key],
					'from_week_day' 	=> $_POST['pricing_from_week_day'][$key],
					'from_time' 		=> $_POST['pricing_from_time'][$key],

					'to_slot' 			=> $_POST['pricing_to_slot'][$key],
					'to_date' 			=> $_POST['pricing_to_date'][$key],
					'to_month' 			=> $_POST['pricing_to_month'][$key],
					'to_week_day' 		=> $_POST['pricing_to_week_day'][$key],
					'to_time' 			=> $_POST['pricing_to_time'][$key],
					
					'basecost_operator' => $_POST['basecost_operator'][$key],
					'base_cost'			=> isset($_POST['ph_booking_rule_base_cost'][$key])?$_POST['ph_booking_rule_base_cost'][$key]:'',
					'perunit_operator' 	=> $_POST['perunit_operator'][$key],
					'cost_per_unit'		=> $_POST['ph_booking_rule_cost_per_unit'][$key],
				);
			}
		}
		return $rules;
	}
}
new phive_booking_product_options();