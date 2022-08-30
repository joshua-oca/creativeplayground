<?php

class pisol_acblw_custom_notice_type{
    function __construct(){
        add_filter('woocommerce_notice_types', array($this, 'acblwNotice'));
        add_filter('wc_get_template',array($this,'noticeDefault'),999,2);
    }

    function acblwNotice($types){
        $types[] = 'acblw_success';
        $types[] = 'acblw_notice';
        return $types;
    }

    function noticeDefault($template, $template_name){
		/** change template so price is not shown */
		if($template_name == 'notices/acblw_success.php'){
			return plugin_dir_path(__FILE__).'/partials/notices/success.php';
		}

        if($template_name == 'notices/acblw_notice.php'){
			return plugin_dir_path(__FILE__).'/partials/notices/notice.php';
		}
        
		return $template;
	}
}
new pisol_acblw_custom_notice_type();