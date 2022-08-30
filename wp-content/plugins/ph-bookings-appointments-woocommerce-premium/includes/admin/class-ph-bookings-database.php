<?php

/**
 * @since 2.2.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'Phive_Bookings_Database' ) ) 
{
	class Phive_Bookings_Database
    {
		public function __construct() 
        {
            $this->availability_tablename = 'ph_bookings_availability_calculation_data';
            add_action( 'plugins_loaded', array($this, 'ph_bookings_update_db_check') );
		}
        
        public function ph_bookings_update_db_check()
        {
            $intalled_version = get_option('ph_bookings_appointments_db_version');
			$version_compare  = version_compare(PH_BOOKINGS_PLUGIN_DB_VERSION, $intalled_version);
            if(empty($intalled_version))
			{
				$this->create_availability_table();
			}
			else if($version_compare > 0) 
            {
				// $this->alter_availability_table();
			}
            else
            {
                // $this->get_result_availability_table();
                // $this->insert_data_availability_table();
                // $this->update_data_availability_table();
                $this->clean_up_availability_table();
                $this->clean_up_cart_data_availability_table();
                // $this->reset_auto_increment_availability_table();
            }
        }


		public function create_availability_table()
		{
            /* 
                # Fields are not restricted using enum for flexibility in future
                - booking_type can be either 'booked' or 'cart'
                - participant_as_booking can be either 'yes' or 'no'
                - booked_date_type can be in ('from', 'middle', 'to')
            */
            global $wpdb;
            $tablename = $wpdb->prefix.$this->availability_tablename;
            $sql = "CREATE TABLE $tablename
                    (
                        sno bigint(20) UNSIGNED AUTO_INCREMENT,
                        order_id bigint(20) UNSIGNED NOT NULL,
                        order_item_id bigint(20) UNSIGNED NOT NULL,
                        product_id bigint(20) UNSIGNED NOT NULL,
                        booked_date timestamp default '0000-00-00 00:00:00',
                        booked_date_end timestamp default '0000-00-00 00:00:00',
                        asset_id text default NULL,
                        participant_count int(11) UNSIGNED default NULL,
                        participant_as_booking varchar(191) default 'no',
                        charge_per_night varchar(191) default 'no',
                        booking_interval_type varchar(191) default NULL,
                        booking_interval bigint(20) UNSIGNED default NULL,
                        booked_date_type varchar(191) default NULL,
                        booking_type varchar(191) default 'booked',
                        booking_status varchar(191) default NULL,
                        woocommerce_order_status varchar(191) default NULL,
                        additional_data text default NULL,
                        created_at timestamp default CURRENT_TIMESTAMP,
                        updated_at timestamp default CURRENT_TIMESTAMP,
                        PRIMARY KEY  (sno)
                    );";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
			update_option( 'ph_bookings_appointments_db_version', PH_BOOKINGS_PLUGIN_DB_VERSION );
		}

        public function alter_availability_table()
        {
            # code...
        }

        public function get_result_availability_table()
        {
            global $wpdb;
            $tablename = $wpdb->prefix.$this->availability_tablename;
            $results = $wpdb->get_results( "SELECT * FROM $tablename where booking_status='paid'" );
            // error_log('results : '.print_r($results,1));
        }

        public function insert_data_availability_table($data='')
        {
            global $wpdb;
            $tablename          = $wpdb->prefix.$this->availability_tablename;

            if(empty($data) && !is_array($data))
            {
                return false;
            }
            $booked_date        = $data['booked_date'];
            $booked_date_end    = $data['booked_date_end'];
            $booked_date_type   = $data['booked_date_type'];
            $booking_type       = $data['booking_type'];
            $additional_data    = isset($data['additional_data']) ? $data['additional_data'] : NULL;

            $status = $wpdb->insert( 
				$tablename, 
				array( 
					'order_id' 					=> $data['order_id'], 
					'order_item_id' 			=> $data['order_item_id'], 
					'product_id' 				=> $data['product_id'], 
					'booked_date' 				=> "$booked_date",
					'booked_date_end'			=> "$booked_date_end",
                    'booked_date_type'          => "$booked_date_type",
                    'booking_type'              => "$booking_type",
					'booking_status' 			=> $data['booking_status'],
					'participant_count' 		=> $data['number_of_persons'],
					'participant_as_booking' 	=> $data['person_as_booking'],
                    'charge_per_night'          => $data['charge_per_night'],
					'booking_interval_type'		=> $data['interval_format'],
					'booking_interval'			=> $data['interval'],
					'asset_id'					=> $data['asset_id'],
                    'woocommerce_order_status'  => $data['woocommerce_order_status'],
                    'additional_data'           => $additional_data
				)
			);

            return $status;
        }

		public function update_data_availability_table()
		{
			# code...
		}

        public function clean_up_availability_table()
        {
            global $wpdb;
            $tablename      = $wpdb->prefix.$this->availability_tablename;

            // delete older rows
            $timeZone       = $this->get_site_timezone();
            $timeZone       = new DateTimeZone($timeZone);
            $current_date   = new DateTime('NOW');
            $current_date->setTimezone($timeZone);
            $current_date   = $current_date->format('Y-m-d H:i:s');
            $current_date   = date('Y-m-d H:i:s', strtotime('-2 day', strtotime($current_date)));
            
            $prepare_values = array( $current_date );
            $query 			= $wpdb->prepare( "DELETE FROM $tablename WHERE booked_date_end < %s", $prepare_values);
            $wpdb->query($query);
        }

        /*
            id_type can be      - product_id, order_id, order_item_id
            booking_type can be - booked, cart
        */
        public function delete_data_availability_table($id, $id_type='', $booking_type='')
        {
            global $wpdb;
            $tablename          = $wpdb->prefix.$this->availability_tablename;   
            if($booking_type != '')         
            {    
                $prepare_values = array( $id, $booking_type );
                $query 			= $wpdb->prepare( "DELETE FROM $tablename WHERE $id_type = %d AND booking_type = %s", $prepare_values);
            }
            else
            {
                $prepare_values = array( $id );
                $query 			= $wpdb->prepare( "DELETE FROM $tablename WHERE $id_type = %d", $prepare_values);
            }
            $status             = $wpdb->query($query);
            return $status;
        }

        /*
            id_type can be      - product_id, order_id, order_item_id
            booking_type can be - booked, cart
            status_type can be  - booking_status, woocommerce_order_status
        */
        public function update_status_availability_table($id, $id_type='', $booking_type='', $status='', $status_type='')
        {
            global $wpdb;
            $tablename      = $wpdb->prefix.$this->availability_tablename;            
            $prepare_values = array( $status, $id, $booking_type );
            $query 			= $wpdb->prepare( "UPDATE $tablename SET $status_type = '%s' WHERE $id_type = %d AND booking_type = %s", $prepare_values);
            $status         = $wpdb->query($query);
            return $status;
        }

        public function clean_up_cart_data_availability_table()
        {
            global $wpdb;
            $tablename      = $wpdb->prefix.$this->availability_tablename;

            // delete older rows
            $timeZone       = $this->get_site_timezone();
            $timeZone       = new DateTimeZone($timeZone);
            $current_date   = new DateTime('NOW');
            $current_date->setTimezone($timeZone);
            $current_date   = $current_date->format('Y-m-d');
            
            $prepare_values = array( $current_date );
            $query 			= $wpdb->prepare( "DELETE FROM $tablename WHERE DATE(created_at) < %s AND booking_type = 'cart' AND booking_status = 'canceled'", $prepare_values);
            $wpdb->query($query);
        }

        public function reset_auto_increment_availability_table()
        {
            global $wpdb;
            $tablename      = $wpdb->prefix.$this->availability_tablename;
            $row            = $wpdb->get_row( "SELECT Max(sno) as max_sno FROM $tablename" );
            // error_log('row : '.print_r($row->max_sno,1));
            $auto_increment = $row->max_sno + 1;
            // error_log('auto_increment : '.print_r($auto_increment,1));
            $prepare_values = array( $auto_increment );
            $query 			= $wpdb->prepare( "ALTER TABLE $tablename AUTO_INCREMENT = %d", $prepare_values);
            $status         = $wpdb->query($query);
        }

        public function get_site_timezone()
        {
            global $wp_version;
            $timezone 		    = get_option('timezone_string');
            if(empty($timezone)) 
            {
                $time_offset    = get_option('gmt_offset');
                // Considered daylight saving off
                $timezone       = timezone_name_from_abbr("", $time_offset * 60 * 60, 0 );
                if ( version_compare( $wp_version, '5.3', '>=' ) ) 
                {
                    $timezone   = wp_timezone_string();
                }
            }
            return $timezone ? $timezone : 'UTC';
        }

        public function update_charge_per_night_availability_table($charge_per_night, $product_id)
        {
            global $wpdb;
            $tablename      = $wpdb->prefix.$this->availability_tablename;            
            $prepare_values = array( $charge_per_night, $product_id );
            $query 			= $wpdb->prepare( "UPDATE $tablename SET charge_per_night = '%s' WHERE product_id = %d", $prepare_values);
            $status         = $wpdb->query($query);
            return $status;
        }
	}
	new Phive_Bookings_Database();
}