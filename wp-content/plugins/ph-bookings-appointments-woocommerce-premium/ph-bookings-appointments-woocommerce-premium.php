<?php
/**
 * Plugin Name: Bookings and Appointments For WooCommerce Premium
 * Description:	Bookings and Appointments solution for all types of businesses.
 * Version: 2.3.6
 * Author: PluginHive
 * Author URI: http://pluginhive.com/about/
 * WC requires at least: 3.0.0
 * WC tested up to: 6.5.1
 * Text Domain: bookings-and-appointments-for-woocommerce
*/



// Define PH_BOOKINGS_PLUGIN_FILE.
if ( ! defined( 'PH_BOOKINGS_PLUGIN_FILE' ) )
	define( 'PH_BOOKINGS_PLUGIN_FILE', __FILE__ );

// Define PH_BOOKINGS_PLUGIN_VERSION
if ( !defined( 'PH_BOOKINGS_PLUGIN_VERSION' ) )
{
	define( 'PH_BOOKINGS_PLUGIN_VERSION', '2.3.6' );
}

/**
 * @since 2.2.0
 * ticket-96421
 */
define('PH_BOOKINGS_PLUGIN_DB_VERSION', '1.0.0');

define( 'PH_BOOKINGS_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/includes/templates/' );

// Include API Manager
if ( !class_exists( 'PH_Bookings_API_Manager' ) ) {

	include_once( 'ph-api-manager/ph_api_manager_bookings.php' );
}

if ( class_exists( 'PH_Bookings_API_Manager' ) ) {

	$product_title 		= 'Bookings'; 
	$server_url 		= 'https://www.pluginhive.com/';
	$product_id 		= '';

	$ph_bookings_api_obj 	= new PH_Bookings_API_Manager( __FILE__, $product_id, PH_BOOKINGS_PLUGIN_VERSION, 'plugin', $server_url, $product_title );

}

/**
 * Plugin activation check
 */
if( ! class_exists('Ph_Bookings_Plugin_Active_Check') )
	include_once 'class-ph-bookings-plugin-active-check.php';
register_activation_hook(__FILE__, 'phive_booking_pre_activation_check_premium');
function phive_booking_pre_activation_check_premium(){
	//check if basic version is there
	if ( Ph_Bookings_Plugin_Active_Check::plugin_active_check('bookings-and-appointments-for-woocommerce/ph-bookings-appointments-woocommerce.php')){
        deactivate_plugins( basename( __FILE__ ) );
		wp_die( __("Oops! You tried installing the premium version without deactivating and deleting the basic version. Kindly deactivate and delete Bookings and Appointments Woocommerce Extension and then try again", "bookings-and-appointments-for-woocommerce" ), "", array('back_link' => 1 ));
	}
	if (! wp_next_scheduled ( 'ph_bookings_unfreezing_hourly_event' )) {
			wp_schedule_event(time(), 'hourly', 'ph_bookings_unfreezing_hourly_event');
    }
    //reminder emails
	if (! wp_next_scheduled ( 'ph_bookings_notification_cron' )) {
    	wp_schedule_event( time(), 'booking_reminder_interval', 'ph_bookings_notification_cron' );
    }
    //followup emails
	if (! wp_next_scheduled ( 'ph_bookings_follow_up_email_cron' )) {
		wp_schedule_event(time(), 'booking_follow_up_interval', 'ph_bookings_follow_up_email_cron');
    }
}
register_deactivation_hook(__FILE__, 'ph_bookings_unfreezing_hourly_event_deactivation');
function ph_bookings_unfreezing_hourly_event_deactivation() {
	wp_clear_scheduled_hook('ph_bookings_unfreezing_hourly_event');
}

if(Ph_Bookings_Plugin_Active_Check::plugin_active_check( 'woocommerce/woocommerce.php') && !class_exists('phive_booking_initialze_premium') && !Ph_Bookings_Plugin_Active_Check::plugin_active_check('bookings-and-appointments-for-woocommerce/ph-bookings-appointments-woocommerce.php')){
	
	class phive_booking_initialze_premium {
		
		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'phive_booking_scripts' ) );
			add_filter( 'admin_enqueue_scripts', array( $this, 'phive_admin_scripts' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'phive_booking_scripts_theme_porto' ), 1005 );

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
			add_action( 'plugins_loaded', array( $this,'register_booking_product_product_type' ) );
			add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
			add_action('ph_bookings_unfreezing_hourly_event', array($this,'ph_bookings_unfreezing_hourly'));

			include_once ( 'includes/func-ph-booking-general-functions.php' );
			include_once ( 'includes/class-ph-booking-availability-scheduler.php' );
			include_once ( 'includes/class-ph-booking-cart-decorator.php' );
			include_once ( 'includes/class-ph-booking-checkout-decorator.php' );
			include_once ( 'includes/class-ph-booking-ajax-interface.php');
			include_once ( 'includes/class-ph-booking-addon-integration.php' );
			include_once ( 'includes/class-ph-booking-assets.php' );
			include_once ( 'includes/admin/class-ph-booking-order-manager.php' );
			include_once ( 'includes/admin/class-ph-booking-product-manager.php' );
			include_once ( 'includes/class-ph-cache-manager.php' );

			include_once ( 'includes/admin/class-ph-booking-export-bookings.php' );

			// 96421
			include_once ( 'includes/admin/class-ph-bookings-database.php' ); // Manage Database
			include_once ( 'includes/class-ph-booking-manage-availability-data.php' );

			$this->third_party_plugin_support();

			add_action( 'plugins_loaded', array( $this, 'init' ) );
			if( ! class_exists('Ph_Bookings_Plugin_Language_Support') )
				include_once 'class-ph-bookings-translation-support.php';
			$this->init_load_addons();
		}
		// load addons
		public function init_load_addons()
		{		
			$display_settings=get_option('ph_bookings_display_settigns');
			$month_picker_enable=isset($display_settings['month_picker_enable'])?$display_settings['month_picker_enable']:'no';
			$start_of_week=isset($display_settings['start_of_week'])?$display_settings['start_of_week']:1;
			$time_zone_conversion_enable=isset($display_settings['time_zone_conversion_enable'])?$display_settings['time_zone_conversion_enable']:'no';
			
			//month picker addon
			if($month_picker_enable=='yes')
				include_once ( 'includes/addons/ph_bookings_month_picker.php' );

			//start of the week addon
			if($start_of_week!=1)
				include_once ( 'includes/addons/ph_bookings_start_of_the_week.php' );	

			//time zone addon
			if($time_zone_conversion_enable=='yes')
				include_once ( 'includes/addons/ph_bookings_time_zone_conversion.php' );	


			
			if( ! class_exists('Ph_Bookings_Send_Email_Notifications') )
				include_once 'includes/addons/class-ph-bookings-send-email-notifications.php';
		

			if( ! class_exists('Ph_Bookings_Send_Follow_Up_Emails') )
				include_once 'includes/addons/class-ph-bookings-send-follow-up-emails.php';
			// $object = new Ph_Bookings_Send_Follow_Up_Emails();
			
		}
	    public function ph_bookings_unfreezing_hourly() {

			global $wpdb;
			
			$query_post = "SELECT ID as freezed_id,post_date
			FROM {$wpdb->prefix}posts AS t1
			WHERE t1.post_type = 'booking_slot_freez'";
			;
			
			$freezed_ids = $wpdb->get_results( $query_post,ARRAY_A  );
			$freezed_idss=array();
			foreach ($freezed_ids as $key => $product) 
			{
        		$freezed_idss[]=$product['freezed_id'];

		        $post_date=date('Y-m-d H:i:s',strtotime($product['post_date']));

				// $currentTime = current_time('Y-m-d H:i:s');
				global $wp_version;
				if ( version_compare( $wp_version, '5.3', '>=' ) ) 
				{
					$currentTime = current_datetime();
					$currentTime = $currentTime->format('Y-m-d H:i:s');
				}
				else
				{
					$currentTime = current_time('Y-m-d H:i:s');
				}

				$before15mins = strtotime('-30 minutes', strtotime($currentTime));
				$before15mins= date('Y-m-d H:i:s', $before15mins);
				if(strtotime($post_date) < strtotime($before15mins))
				{
					$asset_id = get_post_meta( $product['freezed_id'], 'asset_id', 1 );
					if ($asset_id != '') 
					{
						$ph_cache_obj = new phive_booking_cache_manager();
						$ph_cache_obj->ph_unset_cache($asset_id);
					}
					wp_delete_post( $product['freezed_id'] );
				}

	        }
		}
		
		public function init() {
			if ( is_admin() ) {
				include_once ( 'includes/admin/class-ph-booking-admin-pages.php' );
			}
			load_plugin_textdomain( 'bookings-and-appointments-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/' );		//Load Plugin Text Translations
			include_once ( 'includes/class-ph-booking-google-calendar.php' );
			include_once ( 'includes/class-ph-booking-email-manager.php' );
		}

		public function third_party_plugin_support() {
			if( empty($this->active_plugins) )	$this->active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

			// For woocommerce currency switcher
			if( in_array( 'woocommerce-multicurrency/woocommerce-multicurrency.php', $this->active_plugins) ) {
				require_once 'includes/third-party-plugin-support/ph-bookings-woocommerce-currency-switcher.php';
			}
			// For woocommerce currency switcher
			// if(in_array( 'woo-multi-currency/woo-multi-currency.php', $this->active_plugins) || in_array( 'woo-multi-currency-pro/woo-multi-currency-pro.php', $this->active_plugins) ) {
			// 	require_once 'includes/third-party-plugin-support/ph-bookings-woocommerce-currency-switcher-woo.php';
			// }
			// For woocommerce currency switcher based on countries
			if( in_array( 'woocommerce-product-price-based-on-countries/woocommerce-product-price-based-on-countries.php', $this->active_plugins) ) {
				require_once 'includes/third-party-plugin-support/ph-bookings-woocommerce-currency-switcher-based-on-countries.php';
			}

			if( function_exists('wp_get_theme') && (wp_get_theme()=='The7' || wp_get_theme()=='The7 Child' || wp_get_theme()=='Kreativ Pro' || wp_get_theme()=='Storefront' || wp_get_theme() =='GeneratePress' || wp_get_theme() =='Mai Law Pro' )) {
				require_once 'includes/third-party-plugin-support/ph-bookings-woocommerce-the7theme.php';
			}

			if ( in_array('woocommerce-deposits/woocommmerce-deposits.php', $this->active_plugins))
			{
				require_once 'includes/third-party-plugin-support/ph-bookings-woocommerce-deposits-hide-meta-keys.php';
			}
			//woocs woocommerce currency switcher
			if ( in_array('woocommerce-currency-switcher/index.php', $this->active_plugins))
			{
				require_once 'includes/third-party-plugin-support/ph-bookings-woocs-woocommerce-currency-switcher.php';
			}
			// dokan hide meta keys
			if ( in_array('dokan-lite/dokan.php', $this->active_plugins))
			{
				require_once 'includes/third-party-plugin-support/ph-bookings-dokan-hide-meta-keys.php';
			}

			if ( in_array('woocommerce-aelia-currencyswitcher/woocommerce-aelia-currencyswitcher.php', $this->active_plugins))
			{
				require_once 'includes/third-party-plugin-support/ph-bookings-aelia-woocommerce-currency-switcher.php';
			}
		}
		/**
		 * Add the Links to pages like Documentation.
		 * @param array $links Array of links.
		 * @param string $file Plugin base file name.
		 * @return array
		 */
		public static function plugin_row_meta( $links, $file ) {
			if( $file == "ph-bookings-appointments-woocommerce-premium/ph-bookings-appointments-woocommerce-premium.php" ) {
				return array_merge( $links, array(
					'docs'	=>	'<a href="https://www.pluginhive.com/knowledge-base/setup-guide-woocommerce-bookings-and-appointments-plugin/">' . __( 'Documentation', 'bookings-and-appointments-for-woocommerce' ) . '</a>',
				) );
			}
			return $links;
		}

		/**
		 * Register the custom product type
		 */
		public static function register_booking_product_product_type() {
			include_once( 'includes/class-ph-booking-wc-product.php' );
		}

		public function phive_admin_scripts() {
			
			if(isset($_GET['page']) && ($_GET['page']=='bookings-settings'))
			{
				wp_enqueue_style( 'ph_weeler_css', plugins_url( '/resources/css/wheelcolorpicker.css', __FILE__ ));
				wp_enqueue_script( 'ph_weeler', plugins_url( '/resources/js/jquery.wheelcolorpicker.js', __FILE__ ), array('jquery'));
			}
			
			wp_enqueue_script( 'jquery-ui-sortable' );
			
			// 152448 - sortable ui not working in small screens
			wp_enqueue_script( 'jquery-touch-punch' );

			wp_enqueue_style( 'wc-common-style', plugins_url( '/resources/css/admin_style.css', __FILE__ ));
			wp_enqueue_script( 'jquery-ui-datepicker' );

			wp_deregister_script( 'jqueryui');
			if(isset($_GET['page']) && ($_GET['page']=='add-booking' || $_GET['page']=='bookings' || $_GET['page'] == 'ph-booking-calendar'))
			{
				wp_enqueue_script( 'ph_booking_jquery_ui', plugins_url( '/resources/js/jquery-ui.min.js', __FILE__ ), array( 'jquery' ) );
				// for new calendar design
				wp_enqueue_style( 'ph_booking_calendar_style', plugins_url( '/resources/css/ph_calendar.css', __FILE__ ));
				wp_enqueue_script( 'ph_booking_general_script', plugins_url( '/resources/js/ph-booking-genaral.js', __FILE__ ), array( 'jquery' ) );
				wp_localize_script( 'ph_booking_general_script', 'phive_booking_locale', $this->phive_get_string_translation_arr() );

				wp_enqueue_script( 'ph_select2_dropdown_script', plugins_url( '/resources/js/select2.min.js', __FILE__ ), array( 'jquery' ) );

				wp_enqueue_style( 'ph_select2_dropdown_style', plugins_url( '/resources/css/select2.min.css', __FILE__ ));
			}
			else
			{
				wp_enqueue_style( 'ph_booking_calendar_style_new', plugins_url( '/resources/css/ph_new_calendar.css', __FILE__ ));
				wp_enqueue_style( 'ph_booking_box_calendar_style', plugins_url( '/resources/css/ph_box_calendar.css', __FILE__ ));
			}
			
			// css issue with divi
			if(isset($_GET['page']) && ($_GET['page'] != 'et_divi_options'))
			{
				wp_enqueue_style( 'jquery-ui-css', plugins_url( '/resources/css/jquery-ui.min.css', __FILE__ ) );  
			}

			// #99899 - Admin Calendar
			if(isset($_GET['page']) && $_GET['page'] == 'ph-booking-calendar')
			{
				wp_enqueue_style( 'ph-admin-calendar-style', plugins_url( '/resources/css/ph_admin_calendar.css', __FILE__ ) );  
			}

			wp_enqueue_script( 'ph_booking_admin_script', plugins_url( '/resources/js/ph-booking-admin.js', __FILE__ ), array( 'jquery' ) );
			wp_localize_script( 'ph_booking_admin_script', 'phive_booking_locale', $this->phive_get_string_translation_arr() );  
			// wp_enqueue_style( 'ph_booking_calendar_style', plugins_url( '/resources/css/ph_calendar.css', __FILE__ ));
			
			// wp_enqueue_script( 'ph_booking_general_script', plugins_url( '/resources/js/ph-booking-genaral.js', __FILE__ ), array( 'jquery' ) );
			// wp_localize_script( 'ph_booking_general_script', 'phive_booking_locale', $this->phive_get_string_translation_arr() );

			wp_enqueue_script( 'ph_booking_products', plugins_url( '/resources/js/ph-booking-ajax.js', __FILE__ ), array('jquery'));

			$display_settings=get_option('ph_bookings_display_settigns');
			$text_customisation=isset($display_settings['text_customisation'])?$display_settings['text_customisation']:array();
			$booking_end_time_display=(isset($display_settings['booking_end_time_display']) && $display_settings['booking_end_time_display']=='no')?false:true;

			$max_participant=isset($text_customisation['max_participant']) && !empty($text_customisation['max_participant'])?$text_customisation['max_participant']:'Total participant (%total) exeeds maximum allowed participant (%max)';
			$min_participant=isset($text_customisation['min_participant']) && !empty($text_customisation['min_participant'])?$text_customisation['min_participant']:'Minimum number of participants required for a booking is (%min)';

			$maximum_participant_warning=apply_filters('phive_booking_get_maximum_allowed_participant_message',$max_participant);

			$minimum_participant_warning=apply_filters('phive_booking_get_minimum_required_participant_message',$min_participant);

			$maximum_participant_warning = ph_wpml_translate_single_string('text_customisation_max_participant', $maximum_participant_warning);
			$minimum_participant_warning = ph_wpml_translate_single_string('text_customisation_min_participant', $minimum_participant_warning);

			$localization_arr = array(
				'ajaxurl' 	=> admin_url( 'admin-ajax.php' ),
				'security' 	=> wp_create_nonce( 'phive_change_product_price' ),
				'maximum_participant_warning'	=> __($maximum_participant_warning, 'bookings-and-appointments-for-woocommerce'),
				'minimum_participant_warning'   => __($minimum_participant_warning, 'bookings-and-appointments-for-woocommerce'),
				'available_slot_message' => __('There is a maximum of %available_slot place remaining', 'bookings-and-appointments-for-woocommerce'),
				'display_end_time'		 =>  apply_filters('ph_bookings_display_booking_end_time',$booking_end_time_display)
			);
			wp_localize_script( 'ph_booking_products', 'phive_booking_ajax', array_merge( $localization_arr, $this->phive_get_string_translation_arr() ) );
		}

		function phive_booking_scripts()
		{
			// 102180 - load scripts only on plugin specific pages
			// 123807 - load scripts on elementor product page templates
			$template_type = get_post_meta( get_the_ID(), '_elementor_template_type', 1);
			global $post;
			$short_code_exists = 0;
			if(is_object($post))
			{
				$short_code_exists = ( ! empty( $post->post_content ) && strstr( $post->post_content, '[product_page' ) ) ? 1 : 0;
			}
			if( (function_exists('is_woocommerce') && is_woocommerce()) || 
				(function_exists('is_product') && is_product()) ||
				(function_exists('is_cart') && is_cart()) ||
				(function_exists('is_checkout') && is_checkout()) ||
				$short_code_exists ||
				(!empty($template_type) && $template_type == 'product')
			)
			{
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_script( 'ph_booking_general_script', plugins_url( '/resources/js/ph-booking-genaral.js', __FILE__ ), array( 'jquery' ) );
				
				wp_enqueue_script( 'ph_booking_product', plugins_url( '/resources/js/ph-booking-ajax.js', __FILE__ ), array('jquery'));
				
				wp_localize_script( 'ph_booking_general_script', 'phive_booking_locale', $this->phive_get_string_translation_arr() );

				$display_settings=get_option('ph_bookings_display_settigns');
				$text_customisation=isset($display_settings['text_customisation'])?$display_settings['text_customisation']:array();
				$max_participant=isset($text_customisation['max_participant']) && !empty($text_customisation['max_participant'])?$text_customisation['max_participant']:'Total participant (%total) exeeds maximum allowed participant (%max)';
				$min_participant=isset($text_customisation['min_participant']) && !empty($text_customisation['min_participant'])?$text_customisation['min_participant']:'Minimum number of participants required for a booking is (%min)';

				$maximum_participant_warning=apply_filters('phive_booking_get_maximum_allowed_participant_message',$max_participant);
				$minimum_participant_warning=apply_filters('phive_booking_get_minimum_required_participant_message',$min_participant);

				$maximum_participant_warning = ph_wpml_translate_single_string('text_customisation_max_participant', $maximum_participant_warning);
				$minimum_participant_warning = ph_wpml_translate_single_string('text_customisation_min_participant', $minimum_participant_warning);

				$booking_end_time_display=(isset($display_settings['booking_end_time_display']) && $display_settings['booking_end_time_display']=='no')?false:true;
				$localization_arr = array(
					'ajaxurl' 	=> admin_url( 'admin-ajax.php' ),
					'security' 	=> wp_create_nonce( 'phive_change_product_price' ),
					'maximum_participant_warning'	=> __($maximum_participant_warning, 'bookings-and-appointments-for-woocommerce'),
					'minimum_participant_warning'	=> __($minimum_participant_warning, 'bookings-and-appointments-for-woocommerce'),
					'available_slot_message' =>  __('There is a maximum of %available_slot place remaining', 'bookings-and-appointments-for-woocommerce'),
					'display_end_time'		 =>  apply_filters('ph_bookings_display_booking_end_time',$booking_end_time_display)
				);
				wp_localize_script( 'ph_booking_product', 'phive_booking_ajax', array_merge( $localization_arr, $this->phive_get_string_translation_arr() ) );
				
				wp_enqueue_style( 'ph_booking_style', plugins_url( '/resources/css/ph_booking.css', __FILE__ ));
				wp_enqueue_style( 'jquery-ui-css', plugins_url( '/resources/css/jquery-ui.min.css', __FILE__ ) );  
				// wp_enqueue_style( 'ph_booking_calendar_style', plugins_url( '/resources/css/ph_calendar.css', __FILE__ ));
				// wp_enqueue_style( 'ph_booking_mobile_view_style', plugins_url( '/resources/css/ph_bookings_mobile_view.css', __FILE__ ));

				
				// wp_enqueue_style( 'ph_booking_calendar_style', plugins_url( '/resources/css/ph_calendar.css', __FILE__ ));
				wp_enqueue_style( 'ph_booking_mobile_view_style', plugins_url( '/resources/css/ph_bookings_mobile_view.css', __FILE__ ));
				$ph_calendar_color 			= get_option('ph_booking_settings_calendar_color') ;
				$ph_calendar_design			= (isset($ph_calendar_color['ph_calendar_design']) && !empty($ph_calendar_color['ph_calendar_design']))?$ph_calendar_color['ph_calendar_design']:1; // default legacy design will display
				// error_log("design".$ph_calendar_design);
				if($ph_calendar_design==2 && !(isset($_GET['page']) && $_GET['page']=='add-booking'))
				{
					wp_enqueue_style( 'ph_booking_calendar_style', plugins_url( '/resources/css/ph_new_calendar.css', __FILE__ ));
				}
				if($ph_calendar_design==3 && !(isset($_GET['page']) && $_GET['page']=='add-booking'))
				{
					wp_enqueue_style( 'ph_booking_calendar_style', plugins_url( '/resources/css/ph_calendar.css', __FILE__ ));
					wp_enqueue_style( 'ph_booking_mobile_view_style', plugins_url( '/resources/css/ph_bookings_mobile_view.css', __FILE__ ));
					wp_enqueue_script( 'ph_booking_new_design', plugins_url( '/resources/js/ph-booking-box-design.js', __FILE__ ), array('jquery'));
					wp_enqueue_style( 'ph_booking_box_calendar_style', plugins_url( '/resources/css/ph_box_calendar.css', __FILE__ ));
				}
				else
				{
					wp_enqueue_style( 'ph_booking_calendar_style', plugins_url( '/resources/css/ph_calendar.css', __FILE__ ));
					wp_enqueue_style( 'ph_booking_mobile_view_style', plugins_url( '/resources/css/ph_bookings_mobile_view.css', __FILE__ ));
				}
			}
			else if(is_active_widget(false, false, 'ph_booking_search_widget', false))
			{
				wp_enqueue_style( 'jquery-ui-css', plugins_url( '/resources/css/jquery-ui.min.css', __FILE__ ) );  
			}
		}

		public function phive_booking_styles_admin_booking_calendar()
		{
			wp_enqueue_style( 'ph_booking_style', plugins_url( '/resources/css/ph_booking.css', __FILE__ ));
			wp_enqueue_style( 'ph_booking_mobile_view_style', plugins_url( '/resources/css/ph_bookings_mobile_view.css', __FILE__ ));
		}

		public function phive_booking_scripts_theme_porto()
		{
			if( function_exists('wp_get_theme') && (wp_get_theme()=='Porto' || wp_get_theme()=='Porto Child')) 
			{
				wp_dequeue_script( 'ph_booking_general_script');
				wp_dequeue_script( 'ph_booking_product');
				wp_enqueue_script( 'ph_booking_general_script', plugins_url( '/resources/js/ph-booking-genaral.js', __FILE__ ), array( 'jquery','porto-theme' ), PH_BOOKINGS_PLUGIN_VERSION, true );
				wp_enqueue_script( 'ph_booking_product', plugins_url( '/resources/js/ph-booking-ajax.js', __FILE__ ), array('jquery','porto-theme' ), PH_BOOKINGS_PLUGIN_VERSION, true );
			}
		}

		private function phive_get_string_translation_arr(){
			$display_settings=get_option('ph_bookings_display_settigns');
			$text_customisation=isset($display_settings['text_customisation'])?$display_settings['text_customisation']:array();

			if(!empty($text_customisation))
			{
				foreach($text_customisation as $key => $value)
				{
					$name = 'text_customisation_'.$key;
					$value = ph_wpml_translate_single_string($name, $value);
					$text_customisation[$key] = $value;
				}
			}

			$pick_a_date=isset($text_customisation['pick_a_date']) && !empty($text_customisation['pick_a_date'])?$text_customisation['pick_a_date']:'Please Pick a Date';
			$max_block=isset($text_customisation['max_block']) && !empty($text_customisation['max_block'])?$text_customisation['max_block']:'Max no of blocks available to book is %max_block';

			$min_block_required = isset($text_customisation['min_block_required']) && !empty($text_customisation['min_block_required']) ? $text_customisation['min_block_required']:'Please Select minimum %d blocks.';

			$pick_an_end_date 	= isset($text_customisation['pick_an_end_date']) && !empty($text_customisation['pick_an_end_date'])?$text_customisation['pick_an_end_date']:'Please pick an end date';
			//137142
			$pick_a_time 		= isset($text_customisation['pick_a_time']) && !empty($text_customisation['pick_a_time']) ? $text_customisation['pick_a_time'] : 'Please pick a time';

			$booking_info_booking_cost = isset($text_customisation['booking_info_booking_cost']) && !empty($text_customisation['booking_info_booking_cost'])?$text_customisation['booking_info_booking_cost']:'Booking cost';
			$booking_info_booking = isset($text_customisation['booking_info_booking']) && !empty($text_customisation['booking_info_booking'])?$text_customisation['booking_info_booking']:'Booking';

			$check_in_text = isset($text_customisation['check_in_text']) && !empty($text_customisation['check_in_text'])?$text_customisation['check_in_text']:'Check-in';
			$check_out_text = isset($text_customisation['check_out_text']) && !empty($text_customisation['check_out_text'])?$text_customisation['check_out_text']:'Check-out';

			$booking_date_text=apply_filters('ph_booking_pick_booking_date_text',$pick_a_date);

			// 142103 - Book Now button does not work with theme astra when "ajax add to cart" theme setting is enabled.
			$astra_ajax_add_to_cart = 0;
			if (defined('ASTRA_THEME_SETTINGS'))
			{
				$settings = get_option( ASTRA_THEME_SETTINGS );
				$astra_ajax_add_to_cart = isset($settings['single-product-ajax-add-to-cart']) ? $settings['single-product-ajax-add-to-cart'] : 0;
			}

			return array(
				'months'			=> array(
					__('January', 'bookings-and-appointments-for-woocommerce'),	
					__('February', 'bookings-and-appointments-for-woocommerce'),	
					__('March', 'bookings-and-appointments-for-woocommerce'),	
					__('April', 'bookings-and-appointments-for-woocommerce'),	
					__('May', 'bookings-and-appointments-for-woocommerce'),	
					__('June', 'bookings-and-appointments-for-woocommerce'),	
					__('July', 'bookings-and-appointments-for-woocommerce'),	
					__('August', 'bookings-and-appointments-for-woocommerce'),	
					__('September', 'bookings-and-appointments-for-woocommerce'),	
					__('October', 'bookings-and-appointments-for-woocommerce'),	
					__('November', 'bookings-and-appointments-for-woocommerce'),	
					__('December', 'bookings-and-appointments-for-woocommerce'),	
				),
				'months_short'			=> array(
					__('Jan', 'bookings-and-appointments-for-woocommerce'),	
					__('Feb', 'bookings-and-appointments-for-woocommerce'),	
					__('Mar', 'bookings-and-appointments-for-woocommerce'),	
					__('Apr', 'bookings-and-appointments-for-woocommerce'),	
					__('May', 'bookings-and-appointments-for-woocommerce'),	
					__('Jun', 'bookings-and-appointments-for-woocommerce'),	
					__('Jul', 'bookings-and-appointments-for-woocommerce'),	
					__('Aug', 'bookings-and-appointments-for-woocommerce'),	
					__('Sep', 'bookings-and-appointments-for-woocommerce'),	
					__('Oct', 'bookings-and-appointments-for-woocommerce'),	
					__('Nov', 'bookings-and-appointments-for-woocommerce'),	
					__('Dec', 'bookings-and-appointments-for-woocommerce'),	
				),
				'booking_cost' 		=> __($booking_info_booking_cost, 'bookings-and-appointments-for-woocommerce'),
				'booking' 			=> __($booking_info_booking, 'bookings-and-appointments-for-woocommerce'),
				'to' 				=> __('to', 'bookings-and-appointments-for-woocommerce'),
				'checkin' 			=> __($check_in_text, 'bookings-and-appointments-for-woocommerce'),
				'checkout' 			=> __($check_out_text, 'bookings-and-appointments-for-woocommerce'),
				'is_not_avail' 		=> __('is not available.', 'bookings-and-appointments-for-woocommerce'),
				'are_not_avail' 	=> __('are not available.', 'bookings-and-appointments-for-woocommerce'),
				'pick_later_date'	=> __('Pick a later end date', 'bookings-and-appointments-for-woocommerce'),
				'pick_later_time'	=> __('Pick a later end time', 'bookings-and-appointments-for-woocommerce'),
				'max_limit_text'	=> apply_filters('phive_booking_max_block_available_error_message',__( $max_block, 'bookings-and-appointments-for-woocommerce')),
				'pick_booking'		=> __('Please pick a booking period', 'bookings-and-appointments-for-woocommerce'),
				'exceed_booking' 	=> __( "Since max bookings per block is %d and you have enabled 'each participant as a booking' max participants allowed is %d", 'bookings-and-appointments-for-woocommerce' ),
				'Please_Pick_a_Date'=> __( $booking_date_text, 'bookings-and-appointments-for-woocommerce' ),
				'pick_a_end_date'	=> __( 'Please Pick a End Dates.', 'bookings-and-appointments-for-woocommerce' ),
				'pick_min_date'		=> apply_filters('phive_booking_pick_min_block_message',__($min_block_required, 'bookings-and-appointments-for-woocommerce')),
				'pick_an_end_date'	=> apply_filters('phive_booking_pick_an_end_date_message',__($pick_an_end_date, 'bookings-and-appointments-for-woocommerce')),
				'pick_a_time'		=> apply_filters('phive_booking_pick_a_time_message',__($pick_a_time, 'bookings-and-appointments-for-woocommerce')),
				'pick_a_end_time'	=> apply_filters('phive_booking_pick_a_end_time_message',__('Please pick the end time', 'bookings-and-appointments-for-woocommerce')),
				'pick_a_end_month'	=> apply_filters('phive_booking_pick_an_end_month_message',__('Please pick an end month', 'bookings-and-appointments-for-woocommerce')),
				'pick_a_month'	=> apply_filters('phive_booking_pick_a_month_message',__('Please pick a month', 'bookings-and-appointments-for-woocommerce')),
				'max_individual_participant'	=> __('Number of %pname cannot exceed %pmax', 'bookings-and-appointments-for-woocommerce'),
				'ajaxurl'		 	=> admin_url( 'admin-ajax.php' ),
				'single_min_participant_warning' => __('Minimum number of %pname required is %min', 'bookings-and-appointments-for-woocommerce'),
				'astra_ajax_add_to_cart' => $astra_ajax_add_to_cart
			);
		}

		function plugin_action_links( $links ) {
		
			$plugin_links = array(
				'<a href="http://pluginhive.com/support/" target="_blank">' . __('Support', 'bookings-and-appointments-for-woocommerce') . '</a>',
				'<a href="'.admin_url('edit.php?post_type=product').'" >' . __('Settings', 'bookings-and-appointments-for-woocommerce') . '</a>',
			);
			return array_merge( $plugin_links, $links );
		
		}
	}
	
	new phive_booking_initialze_premium;
}
