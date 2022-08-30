<?php

class pisol_acblw_qr_code{

    function __construct($plugin_name, $version){
        $this->plugin_name = $plugin_name; 
        $this->version = $version;
        add_action('add_meta_boxes', array($this, 'add_coupon_meta_box'), 30);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_script'), 10);

    }

    function add_coupon_meta_box(){
        global $post_id;
        $post_types = ['shop_coupon'];
        add_meta_box('pisol_acblw_qr_code', __('Coupon QR Code', 'add-coupon-by-link-woocommerce'), array($this,'meta_box'), $post_types,'side','default');
    }

    function meta_box($post){
        global $post_id;
        if($post_id){
            $cart_page = wc_get_cart_url();
            $slug = get_option('acblw_coupons_key', 'apply_coupon');
            $code = get_the_title($post_id);
            $url = add_query_arg( [$slug => $code], $cart_page );
			printf('<div id="pi-qr-code" data-qr-code-url="%s"></div>', esc_attr($url));
			printf('<div style="text-align:center; margin-top:20px" ><a href="javascript:void(0)" id="pi-qr-code-download" style="display:none;" class="button">Download Qr Code</a></div>');
		}else{
            echo '<p>Save coupon then QR code will be generated</p>';
        }
    }

    function enqueue_admin_script(){
        $screen = get_current_screen();

        if (is_object($screen) && $screen->id == 'shop_coupon') {
            wp_enqueue_script( $this->plugin_name."_coupon_generator", plugin_dir_url( __FILE__ ) . 'js/qrcode.min.js', array('jquery'), $this->version);
            wp_enqueue_script( $this->plugin_name."_coupon_url", plugin_dir_url( __FILE__ ) . 'js/add-coupon-by-link-woocommerce-admin.js', array('jquery', $this->plugin_name."_coupon_generator"), $this->version);
        }
    }
}

new pisol_acblw_qr_code($this->plugin_name, $this->version);