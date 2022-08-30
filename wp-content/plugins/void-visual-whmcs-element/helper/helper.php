<?php 

global $whmcs_bridge_enabled;

if( function_exists('cc_whmcs_bridge_home')){
    $whmcs_bridge_enabled = true;
}

/**
 *
 * WHMCS Bridge Page URL
 *
 */
if(!function_exists('void_wbwhmcse_whmcs_bridge_url')){
function void_wbwhmcse_whmcs_bridge_url() {
  return cc_whmcs_bridge_home($home,$pid);
}
}

//function to get the remote data (pro function)

function void_wbwhmcse_ajax_domain_function(){  
    $response = wp_remote_get('http://whoiz.herokuapp.com/lookup.json?url='.$_POST['domain']);
    if ( is_array( $response ) ) {
	  $header = $response['headers']; // array of http header lines
	  $json = $response['body']; // use the content
	}else{
		$json='';
	}
    $json = json_decode(json_encode($json),true);
    echo $json;
    wp_die(); 
}
add_action( 'wp_ajax_void_wbwhmcse_ajax_domain_function', 'void_wbwhmcse_ajax_domain_function' );
add_action( 'wp_ajax_nopriv_void_wbwhmcse_ajax_domain_function', 'void_wbwhmcse_ajax_domain_function' );
