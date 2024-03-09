<?php

namespace Plugin\Kolaybi;

class Authorization extends Integration {
	public static function login() {
		return Authorization::get_access_token();
	}
	public static function get_access_token() {
		$url = APIClient::get_endpoint_url("/access_token", [
			"api_key" => parent::$api_key
		]);

		$response = APIClient::useAPI(
			url: $url,
			method: "POST"
		);

		parent::$access_token = $response->data;

		return $response;
	}
}