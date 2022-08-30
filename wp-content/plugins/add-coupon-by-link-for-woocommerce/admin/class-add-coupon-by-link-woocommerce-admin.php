<?php

class Add_Coupon_By_Link_Woocommerce_Admin {

	
	private $plugin_name;

	
	private $version;

	
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		new pisol_acblw_menu($this->plugin_name, $this->version);

	}

	
	public function enqueue_styles() {



	}

	
	public function enqueue_scripts() {

	}

}
