<?php

class pisol_acblw_message{
    static function couponAddedToSession( $coupon_code ){
        $added_to_session = pisol_acblw_get_option('acblw_coupon_added_to_session', __('Coupon has ben added it will be applied once coupon conditions are satisfied','add-coupon-by-link-woocommerce'));
        if(wc_has_notice($added_to_session, 'acblw_success' )) return;

        wc_add_notice( $added_to_session, 'acblw_success', array('code' => $coupon_code) );
    }

    static function beforeCouponApplied( $coupon_code ){
        $added_to_session = pisol_acblw_get_option('acblw_before_coupon_applied', __('Coupon will be applied once coupon conditions are satisfied','add-coupon-by-link-woocommerce'));

        if(wc_has_notice($added_to_session, 'acblw_notice' ) || self::presentCouponAddedToSession( $coupon_code )) return;

        wc_add_notice( $added_to_session, 'acblw_notice', array('code' => $coupon_code) );
    }

    static function presentCouponAddedToSession( $coupon_code ){
        $added_to_session = pisol_acblw_get_option('acblw_coupon_added_to_session', __('Coupon has ben added it will be applied once coupon conditions are satisfied','add-coupon-by-link-woocommerce'));
        if(wc_has_notice($added_to_session, 'acblw_success' )) return true;

        return false;
    }
}