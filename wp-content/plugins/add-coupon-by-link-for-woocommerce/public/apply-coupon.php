<?php

class pisol_acblw_apply_coupon{
    function __construct(){

        $enabled = pisol_acblw_get_option('pi_acblw_enable_url_coupon',1);

        if(empty($enabled)) return;

        add_action( 'wp_loaded', array( $this, 'applyUrlCoupon' ), ( '' !== ( $priority = pisol_acblw_get_option( 'acblw_priority', '' ) ) ? $priority : PHP_INT_MAX ) );

        add_action( 'woocommerce_add_to_cart', array( $this, 'applyStoredCoupon' ), PHP_INT_MAX );

        add_action( 'woocommerce_after_cart_item_quantity_update',  array( $this, 'applyStoredCoupon' ), PHP_INT_MAX );
        add_action( 'woocommerce_cart_item_restored',  array( $this, 'applyStoredCoupon' ), PHP_INT_MAX );
        add_action( 'woocommerce_remove_cart_item',  array( $this, 'applyStoredCoupon' ), PHP_INT_MAX );

        add_action('wp_loaded', array( $this, 'applyStoredCoupon' ), PHP_INT_MAX);

        if ( !empty(get_option( 'pi_acblw_hide_coupon_cart', 0 )) ) {
            add_filter( 'woocommerce_coupons_enabled', array( $this, 'hideOnCart' ), PHP_INT_MAX );
        }
        if ( !empty(get_option( 'pi_acblw_hide_coupon_checkout', 0 )) ) {
            add_filter( 'woocommerce_coupons_enabled', array( $this, 'hideOnCheckout' ), PHP_INT_MAX );
        }
    }

    function applyUrlCoupon(){
        $key = pisol_acblw_get_option( 'acblw_coupons_key', 'apply_coupon' );
        if ( isset( $_GET[ $key ] ) && '' !== $_GET[ $key ] && function_exists( 'WC' ) ) {  
            $this->applyCoupon( $_GET[ $key ] );
        }
    }

    function applyCoupon( $coupon_code ) {
        do_action('pisol_acblw_add_product', $coupon_code);
        do_action( 'pisol_acblw_before_coupon_applied', $coupon_code);
            $result = false;
            $coupon_code = sanitize_text_field( $coupon_code );

            $the_coupon = new WC_Coupon( $coupon_code );

            if ( $the_coupon->is_valid() && ! WC()->cart->has_discount( $coupon_code )) {
                $result = WC()->cart->add_discount( $coupon_code );
            }else{
                $this->saveCouponInSessionToApplyAfterWords($coupon_code);
            }

        do_action( 'pisol_acblw_after_coupon_applied', $coupon_code);
		
		return $result;
	}

    function saveCouponInSessionToApplyAfterWords($coupon_code){
        if ( ! WC()->cart->has_discount( $coupon_code ) ) {
            if ( wc_get_coupon_id_by_code( $coupon_code ) ) {
                $coupons = WC()->session->get( 'pisol_url_coupons', array() );
                if(!in_array($coupon_code, $coupons)){
                    $coupons[] = $coupon_code;
                    WC()->session->set( 'pisol_url_coupons', array_unique( $coupons ) );
                    pisol_acblw_message::couponAddedToSession( $coupon_code );
                }else{
                    pisol_acblw_message::beforeCouponApplied( $coupon_code );
                }
            }
        }
    }

    function applyStoredCoupon( ) {
        if(function_exists('WC') && isset(WC()->session)){
            $coupons = WC()->session->get( 'pisol_url_coupons', array() );
            if ( ! empty( $coupons ) ) {
                //WC()->session->set( 'pisol_url_coupons', null );
                foreach ( $coupons as $coupon_code ) {
                    $result = $this->applyCoupon( $coupon_code );
                }
            }
        }
	}

    function hideOnCart( $enabled ) {
		return  is_cart() ? false : $enabled ;
	}

    function hideOnCheckout( $enabled ) {
		return  is_checkout() ? false : $enabled ;
	}


}

new pisol_acblw_apply_coupon();