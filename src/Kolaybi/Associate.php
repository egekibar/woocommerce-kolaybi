<?php

namespace Plugin\Kolaybi;

class Associate extends Integration {
	public static function list() {
		return APIClient::useAPI("/associates")->data;
	}
	public static function detail($id) {
		return APIClient::useAPI("/associates/{$id}")->data;
	}
}