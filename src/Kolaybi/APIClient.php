<?php

namespace Plugin\Kolaybi;

use WpOrg\Requests\Requests;

class APIClient extends Integration {
	public static function useAPI($endpoint = null, $body = null, $method = "GET", $url = null, $header = []) {

		$header = array_merge([
			"Content-Type" => "application/x-www-form-urlencoded",
			"Channel" => parent::$channel
		], $header);

		if (parent::$access_token !== null)
			$header["Authorization"] = 'Bearer ' . parent::$access_token;

		if ($url == null)
			$url = self::get_endpoint_url($endpoint);

		$response = Requests::request($url, $header, $body, $method);

		$decoded_response = json_decode($response->body);

		if ($decoded_response === null && json_last_error() !== JSON_ERROR_NONE)
			return $response;

		return $decoded_response;
	}

	public static function get_endpoint_url($endpoint = '/', $params = []) {
		$url = parent::$url . $endpoint;

		if (!empty($params))
			$url .= '?' . http_build_query($params);

		return $url;
	}
}