<?php

if( ! function_exists('ph_booking_change_cart_price_for_the7theme')  && function_exists('wp_get_theme')) {
    function ph_booking_change_cart_price_for_the7theme($cart_subtotal, $compound, $cart)
    {
        if((wp_get_theme()!='The7' && wp_get_theme()!='The7 Child' && wp_get_theme()!='Kreativ Pro' && wp_get_theme()!='Storefront' && wp_get_theme()!='GeneratePress' && wp_get_theme()!='Mai Law Pro' ))
        {
            return $cart_subtotal;
        }
        $cart_subtotal=0;   
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
            $product_is_visible = apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key );

            if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! $product_is_visible ) {
                continue;
            }
            // 49829
            if ($cart_item['data']->get_type() == 'phive_booking') 
            {
                $price = $cart_item['data']->get_price();
                $price = apply_filters('ph_the7_theme_modify_cart_line_subtotal', $price, $cart_item);
            }
            else
            {
                $price = $cart_item['line_subtotal'];
            }
            $cart_subtotal  = $cart_subtotal + $price;
        }

        $currency = ! empty($woocommerce->session->client_currency) ? $woocommerce->session->client_currency : get_woocommerce_currency();
        $price_html=wc_price($cart_subtotal,array( 'currency' => $currency ));
        return $price_html;
        // if(isset(WC()->cart) && !empty(WC()->cart) )
        //  return WC()->cart->get_cart_total( );
        // else
        //  return $cart_subtotal;
    }

}
// add_filter('woocommerce_cart_subtotal','ph_booking_change_cart_price_for_the7theme',11,3);
// causes wrong calculation of subtotal