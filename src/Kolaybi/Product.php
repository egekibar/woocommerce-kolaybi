<?php

namespace Plugin\Kolaybi;

class Product extends Integration {
	public static function list() {
		return APIClient::useAPI("/products")->data;
	}

	public static function search($key, $value) {
		return APIClient::useAPI("/products?{$key}={$value}")->data;
	}
}