<?php

class pisol_acblw_add_coupon_product{
    function __construct(){
        add_action('pisol_acblw_add_product', [$this, 'addProducts']);
    }

    function addProducts($coupon_code){
        $coupon = new WC_Coupon( $coupon_code );

        if(is_object($coupon) && $coupon->get_id() != 0){
            $product_ids = $coupon->get_meta( 'pisol_auto_add_products' );
            $this->addProductsToCart($product_ids);
        }
    }

    function addProductsToCart($product_ids){
        if(empty($product_ids) || !is_array($product_ids)) return;

        foreach($product_ids as $product_id){
            WC()->cart->add_to_cart( $product_id ); 
        }
    }
    
}
new pisol_acblw_add_coupon_product();