<?php

class pisol_acblw_coupon_options{
    function __construct(){
        add_action( 'woocommerce_coupon_options', [$this,'add_coupon_text_field'], 10,2 );
        add_action( 'woocommerce_coupon_options_save', [$this,'save_coupon_text_field'], PHP_INT_MAX, 2 );
    }

    function add_coupon_text_field($coupon_id, $coupon) {
        ?>
        <p class="form-field">
					<label><?php _e( 'Auto add products (for URL coupon)', 'add-coupon-by-link-woocommerce' ); ?></label>
					<select class="wc-product-search" multiple="multiple" style="width: 50%;" name="pisol_auto_add_products[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'add-coupon-by-link-woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations">
						<?php
						$product_ids = $coupon->get_meta( 'pisol_auto_add_products' );
                        if(is_array($product_ids)){
                            foreach ( $product_ids as $product_id ) {
                                $product = wc_get_product( $product_id );
                                if ( is_object( $product ) ) {
                                    echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
                                }
                            }
                        }
						?>
					</select>
					<?php echo wc_help_tip( __( 'Products that will be auto added to the cart when user visit URL coupon link', 'add-coupon-by-link-woocommerce' ) ); ?>
		</p>
        <?php
    }

    function save_coupon_text_field( $post_id, $coupon ) {
        $id = $coupon->get_id();
        if( isset( $_POST['pisol_auto_add_products'] ) && is_array( $_POST['pisol_auto_add_products'] ) ) {
            update_post_meta( $id, 'pisol_auto_add_products',  $_POST['pisol_auto_add_products'] );
        }else{
            update_post_meta( $id, 'pisol_auto_add_products',  array() );
        }
    }
}
new pisol_acblw_coupon_options();