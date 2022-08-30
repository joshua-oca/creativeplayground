<?php

class pisol_acblw_session{
    function __construct(){
        $enabled = pisol_acblw_get_option('pi_acblw_enable_url_coupon',1);

        if(empty($enabled)) return;
        
        add_action('pisol_acblw_before_coupon_applied', array(__CLASS__, 'startSession'));
    }

    static function startSession(){
        if ( 'yes' === apply_filters('pisol_acblw_enable_session', 'yes') && WC()->session && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}
    }
}

new pisol_acblw_session();