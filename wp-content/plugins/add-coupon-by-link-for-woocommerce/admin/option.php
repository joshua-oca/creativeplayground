<?php

class pisol_acblw_option{

    public $plugin_name;

    private $setting = array();

    private $active_tab;

    private $this_tab = 'default';

    private $tab_name = "Basic setting";

    private $setting_key = 'acblw_basic_settting';

    private $pages =array();

   
    
    private $pro_version = false;

    function __construct($plugin_name){
        $this->plugin_name = $plugin_name;


        $this->pages = $this->get_pages();
        
        $this->tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
        $this->active_tab = $this->tab != "" ? $this->tab : 'default';

        if($this->this_tab == $this->active_tab){
            add_action($this->plugin_name.'_tab_content', array($this,'tab_content'));
        }

        add_action($this->plugin_name.'_tab', array($this,'tab'),1);

        $this->settings = array(

            array('field'=>'pi_acblw_enable_url_coupon','desc'=>__('Enable url coupon plugin','add-coupon-by-link-woocommerce'), 'label'=>__('Enable plugin','add-coupon-by-link-woocommerce'),'type'=>'switch', 'default'=>1),

            array('field'=>'acblw_coupons_key','desc'=>__('Coupon code key e.g: <b>?apply_coupon=[coupon code]</b>, it should not have blank space','add-coupon-by-link-woocommerce'), 'label'=>__('URL coupon key','add-coupon-by-link-woocommerce'),'type'=>'text', 'default' => 'apply_coupon'),

            array('field'=>'acblw_coupon_added_to_session','desc'=>__('If coupon is conditional coupon it will apply when condition satisfied, till that time coupon is saved in session and user is shown a message that coupon is saved in the session','add-coupon-by-link-woocommerce'), 'label'=>__('Message shown when coupon is added in user session','add-coupon-by-link-woocommerce'),'type'=>'text', 'default' => __('Coupon saved in your session, it will be applied once coupon condition satisfied','add-coupon-by-link-woocommerce')),

            array('field'=>'acblw_before_coupon_applied','desc'=>__('If coupon is conditional coupon it will apply when condition satisfied, so you can show this message and describe what they have to do to meat coupon condition','add-coupon-by-link-woocommerce'), 'label'=>__('Message shown when coupon condition are not yet met','add-coupon-by-link-woocommerce'),'type'=>'text', 'default' => __('Coupon will be applied once its conditions are satisfied','add-coupon-by-link-woocommerce')),

            array('field'=>'pi_acblw_hide_coupon_cart','desc'=>'', 'label'=>__('Hide coupon option on cart page','add-coupon-by-link-woocommerce'),'type'=>'switch', 'default'=>0),

            array('field'=>'pi_acblw_hide_coupon_checkout','desc'=>'', 'label'=>__('Hide coupon option on checkout page','add-coupon-by-link-woocommerce'),'type'=>'switch', 'default'=>0),

        );
        $this->register_settings();
        
        
    }

    function get_pages(){
        $pages = get_pages( );
        $pages_array = array(""=>__("Select page","add-coupon-by-link-woocommerce"));
        if($pages){
            foreach ( $pages as $page ) {
                $pages_array[$page->ID] = $page->post_title;
            }
        }
        return $pages_array;
    }

    

    function register_settings(){   

        foreach($this->settings as $setting){
            register_setting( $this->setting_key, $setting['field']);
        }
    
    }

    function tab(){
        ?>
        <a class=" px-3 text-light d-flex align-items-center  border-left border-right  <?php echo esc_attr($this->active_tab == $this->this_tab ? 'bg-primary' : 'bg-secondary'); ?>" href="<?php echo admin_url( 'admin.php?page='.sanitize_text_field($_GET['page']).'&tab='.$this->this_tab ); ?>">
            <?php _e( $this->tab_name, 'add-coupon-by-link-woocommerce' ); ?> 
        </a>
        <?php
    }

    function tab_content(){
       ?>
        <form method="post" action="options.php"  class="pisol-setting-form">
        <?php settings_fields( $this->setting_key ); ?>
        <?php
            foreach($this->settings as $setting){
                new pisol_class_form_acblw($setting, $this->setting_key);
            }
        ?>
        <input type="submit" class="mt-3 btn btn-primary btn-sm" value="Save Option" />
        </form>
       <?php
    }

    
}

new pisol_acblw_option($this->plugin_name);