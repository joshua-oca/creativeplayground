<?php

if( ! defined('ABSPATH') )	exit;

if( ! class_exists('Ph_Bookings_Plugin_Language_Support') ) {
	class Ph_Bookings_Plugin_Language_Support {

		public function __construct() {
			$this->init();
		}

		public static function init() {
			$canceled=__( 'canceled', 'bookings-and-appointments-for-woocommerce' );
			$yes=__( 'yes', 'bookings-and-appointments-for-woocommerce' );
			$no=__( 'no', 'bookings-and-appointments-for-woocommerce' );
			$Number_of_persons=__( 'Number of persons', 'bookings-and-appointments-for-woocommerce' );
			$Order=__( 'Order', 'bookings-and-appointments-for-woocommerce' );
			$un_paid=__( 'un-paid', 'bookings-and-appointments-for-woocommerce' );
			$Confirmed=__( 'Confirmed', 'bookings-and-appointments-for-woocommerce' );
			$confirmed=__( 'confirmed', 'bookings-and-appointments-for-woocommerce' );
			$cancelled=__( 'cancelled', 'bookings-and-appointments-for-woocommerce' );
		}


	}
	new Ph_Bookings_Plugin_Language_Support();
}