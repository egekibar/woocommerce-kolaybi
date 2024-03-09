<?php

namespace Plugin\Kolaybi;

class Order extends Integration {
	public static function list() {
		return APIClient::useAPI('/orders')->data;
	}
	public static function create_invoice($body) {
		return APIClient::useAPI("/invoices", $body, "POST")->data;
	}

	public static function convert_e_invoice($body) {
		return APIClient::useAPI("/invoices/e-document/create", $body, "POST")->data;
	}

	public static function view_e_invoice($uuid) {
		return APIClient::useAPI("/invoices/e-document/view?uuid={$uuid}")->data;
	}
}