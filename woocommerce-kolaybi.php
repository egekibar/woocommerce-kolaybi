<?php

/*
Plugin Name: Woocommerce Kolaybi
Plugin URI: https://github.com/egekibar/woocommerce-kolaybi
Description: Woocommerce kolaybi entegrasyonu
Version: 1.1
Author: egekibar
Author URI: https://kibar.dev
License: A "Slug" license name e.g. GPL2
*/

defined( 'ABSPATH' ) || exit;

require_once 'bootstrap.php';

new \Plugin\Updater();

if (is_admin() || isset($_GET['e_invoice_uuid'])){
	$kolaybi = new \Plugin\Kolaybi\Integration();
	$kolaybi::$api_key = "56e0f5ca-ba6d-40df-8c5c-24012ab2b382";
	$kolaybi::$channel = "hbl_otomotiv";
	$kolaybi::$url = "https://ofis-sandbox-api.kolaybi.com/kolaybi/v1";
}

add_action('plugins_loaded', function (){
	if (!class_exists('WooCommerce'))
		add_action( 'admin_notices', function () {
			?>
				<div class="notice notice-error is-dismissible">
					<p>Kolaybi Entegrasyonu için WooCommerce eklentisi zorunludur.</p>
				</div>
			<?php
		});
});