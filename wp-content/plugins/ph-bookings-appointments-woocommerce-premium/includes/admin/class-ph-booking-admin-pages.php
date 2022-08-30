<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class phive_booking_admin_pages {

	public $id = 'ph_booking_settings_'; // The prefix of the key to save settings.
	
	public function __construct() {
		add_action(	'admin_menu', array( $this, 'ph_booking_admin_menu' ) );	
	}

	public function ph_booking_admin_menu(){	
		add_menu_page(
			'bookings',
			__('Bookings','bookings-and-appointments-for-woocommerce'),
			'manage_woocommerce',
			'bookings',
			array($this, 'ph_generate_booking_report'),
			'dashicons-calendar',
			56
		);
		
		add_submenu_page(
			"bookings",
			"All Bookings",
			__("All Bookings",'bookings-and-appointments-for-woocommerce'),
			"manage_woocommerce",
			"bookings", 
			array( $this, "ph_generate_booking_report" )
		);

		add_submenu_page(
			"bookings",
			"Booking Settings",
			__("Settings",'bookings-and-appointments-for-woocommerce'),
			"manage_woocommerce",
			"bookings-settings", 
			array( $this, "ph_booking_settings_page" )
		);
		add_submenu_page(
			"bookings",
			"Add New Booking",
			__("Add Booking",'bookings-and-appointments-for-woocommerce'),
			"manage_woocommerce",
			"add-booking", 
			array( $this, "ph_booking_admin_order_new" )
		);
		// #99899 - Admin Calendar
		add_submenu_page(
			"bookings",
			"Calendar",
			__("Calendar",'bookings-and-appointments-for-woocommerce'),
			"manage_woocommerce",
			"ph-booking-calendar", 
			array( $this, "ph_all_bookings_calendar_view_for_admin" )
		);
	}

	function ph_generate_booking_report(){
		include_once('class-ph-booking-admin-reports-list.php');
		$bookings_list = new phive_booking_all_list();

		printf( '<div class="wrap"><h2>%s</h2>', __( 'All Bookings', 'bookings-and-appointments-for-woocommerce' ) .
		'&nbsp;<a href="admin.php?page=add-booking" class="page-title-action">'.__( 'Add Booking', 'bookings-and-appointments-for-woocommerce' ).'</a>');
		//107413 - Changing Request Type to GET
		echo '<form id="booking-list-table-form" method="get">';
		echo '<input type="hidden" name="page" value="bookings" readonly/>';
		$bookings_list->prepare_items();
		$bookings_list->display();
		
		echo '</form>';
		echo '</div>';
	}

	function ph_booking_settings_page(){
		?>
		<div class="wrap woocommerce">
			<div id="icon-options-general" class="icon32"></div>
			

			<?php
			$active_tab = !empty($_GET['tab']) ? $_GET['tab'] : 'licence';
			
			?>
			<div>
				<h2>
					<div>
						<?php 
							$plugin_url = plugins_url('ph-bookings-appointments-woocommerce-premium', dirname( dirname( dirname(__FILE__) )) );
							$logo = $plugin_url."/resources/icons/pluginhive_logo.png";
						?>
						<img src="<?php echo $logo;?>" alt="" style="max-height:41px; width: 200px;">
						<br>
						<span style="display:inline-block; margin-top: 5px; color: #33475B !important;"><?php echo __('WooCommerce Bookings and Appointments','bookings-and-appointments-for-woocommerce');?></span>
					</div>
					<hr>
					<div style="font-weight:normal; font-size: 13px;line-height: 1.5;">
						<?php echo __('The settings below are global and apply to all the bookable products. These settings are optional.','bookings-and-appointments-for-woocommerce');?> <br>
						<?php echo __('In order to set up an individual bookable product please go the desired Product’s settings.','bookings-and-appointments-for-woocommerce');?> <br>
						<?php 
							$documentation_link = '<a href="https://www.pluginhive.com/knowledge-base/setup-guide-woocommerce-bookings-and-appointments-plugin/" target="_blank">Documentation</a>';
							$documentation = sprintf(__('Refer %s For Help.', 'bookings-and-appointments-for-woocommerce'),$documentation_link);
							echo __($documentation,'bookings-and-appointments-for-woocommerce');
						?>
					</div>
					<hr>
				</h2>
			</div>
			<h2 class="nav-tab-wrapper ph-setting-tabs">
				<a href="?page=bookings-settings&tab=licence" class="nav-tab <?php if($active_tab == 'licence'){echo 'nav-tab-active';} ?>"><?php _e('Licence', 'bookings-and-appointments-for-woocommerce'); ?></a>
				<a href="?page=bookings-settings&tab=calendar-color-customizer" class="nav-tab <?php if($active_tab == 'calendar-color-customizer'){echo 'nav-tab-active';} ?>"><?php _e('Calendar Design', 'bookings-and-appointments-for-woocommerce'); ?></a>
				<a href="?page=bookings-settings&tab=display-customiser" class="nav-tab <?php if($active_tab == 'display-customiser'){echo 'nav-tab-active';} ?>"><?php _e('Calendar Display', 'bookings-and-appointments-for-woocommerce'); ?></a>
				<a href="?page=bookings-settings&tab=availability" class="nav-tab <?php if($active_tab == 'availability'){echo 'nav-tab-active';} ?>"><?php _e('Global Availability', 'bookings-and-appointments-for-woocommerce'); ?></a>
				<a href="?page=bookings-settings&tab=assets" class="nav-tab <?php if($active_tab == 'assets'){echo 'nav-tab-active';} ?>"><?php _e('Global Assets', 'bookings-and-appointments-for-woocommerce'); ?></a>
				<a href="?page=bookings-settings&tab=google-calendar" class="nav-tab <?php if($active_tab == 'google-calendar'){echo 'nav-tab-active';} ?> "><?php _e('Google Calendar Sync', 'bookings-and-appointments-for-woocommerce'); ?></a>
				<a href="?page=bookings-settings&tab=email-customiser" class="nav-tab <?php if($active_tab == 'email-customiser'){echo 'nav-tab-active';} ?>"><?php _e('Reminder and Follow up Emails', 'bookings-and-appointments-for-woocommerce'); ?></a>
				<a href="?page=bookings-settings&tab=booking-addons" class="nav-tab <?php if($active_tab == 'booking-addons'){echo 'nav-tab-active';} ?>"><?php _e('Add-Ons', 'bookings-and-appointments-for-woocommerce'); ?></a>
				<a href="?page=bookings-settings&tab=booking-integrations" class="nav-tab <?php if($active_tab == 'booking-integrations'){echo 'nav-tab-active';} ?>"><?php _e('Integrations', 'bookings-and-appointments-for-woocommerce'); ?></a>
				<?php do_action('ph_bookings_add_submenu',$active_tab); ?>
			</h2>

			<div class="metabox-holder has-right-sidebar">
				<?php
				if ( $active_tab == "google-calendar" ) {
					$this->ph_settings_google_calendar();
				}elseif( $active_tab == "availability" ){
					$this->ph_settings_availability();
				}elseif( $active_tab == "assets" ){
					$this->ph_settings_assets();
				}
				elseif( $active_tab == "calendar-color-customizer" ){
					$this->ph_settings_calendar_color_customizer();
				}
				elseif($active_tab == "licence") {
					$this->ph_booking_settings_licence();
				}
				elseif( $active_tab == "email-customiser" ){
					$this->ph_settings_email_customiser();
				}
				elseif( $active_tab == "display-customiser" ){
					$this->ph_settings_display_customiser();
				}
				elseif( $active_tab == "booking-addons" ){
					$this->ph_settings_booking_addons();
				}
				elseif( $active_tab == "booking-integrations" ){
					$this->ph_settings_booking_integrations();
				}
				?>
			</div> 
		</div>
		<?php
	}
	
	private function ph_settings_google_calendar() {
		include('views/html-ph-booking-settings-google-calendar.php');
	}
	function ph_booking_admin_order_new() {
			require_once( 'class-ph-booking-admin-order.php' );
			$page = new phive_booking_admin_order;
	}

	private function ph_booking_settings_licence(){
		?>
		<div class='wrap'>
			<h2><?php _e('Licence Activation','bookings-and-appointments-for-woocommerce')?></h2>
			<p><?php _e('We have improved the License Activation process for the plugin.','bookings-and-appointments-for-woocommerce');?>
			</p>
			<?php
			printf( __( ' %sClick here%s to activate <strong>Bookings</strong> API Key.', 'bookings-and-appointments-for-woocommerce' ), '<a href="' . esc_url( admin_url( 'options-general.php?page=wc_am_client_ph_bookings_appointments_woocommerce_premium_dashboard' ) ) . '">', '</a>' ); ?></p>
		</div>
		<?php
	}
	private function ph_settings_availability(){
		include('views/html-ph-booking-settings-availability.php');	
	}
	private function ph_settings_assets(){
		include('views/html-ph-booking-settings-assets.php');	
	}
	private function ph_settings_calendar_color_customizer(){
		include('views/html-ph-booking-settings-calendar-color-customizer.php');	
	}
	private function ph_settings_email_customiser(){
		include('views/html-ph-booking-settings-email-customiser.php');	
	}
	private function ph_settings_display_customiser(){
		include('views/html-ph-booking-settings-display-customiser.php');	
	}
	private function ph_settings_booking_addons(){
		include('views/html-ph-booking-settings-booking-addons.php');	
	}
	private function ph_settings_booking_integrations(){
		include('views/html-ph-booking-settings-booking-integrations.php');	
	}

	// #99899 - Admin Calendar
	function ph_all_bookings_calendar_view_for_admin()
	{
		require_once('class-ph-bookings-calendar-admin.php');
		$page = new Ph_Bookings_Calendar_Admin();
		$page->output();
	}
}
new phive_booking_admin_pages;
