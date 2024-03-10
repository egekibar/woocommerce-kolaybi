<?php

define("PLUGIN_DIR_FOR_URL", "/wp-content/plugins/".plugin_basename(__DIR__));

function view($template, $global) {
	$varible = "view_{$template}";
	global $$varible;
	$$varible = $global;
	include "public/view/{$template}.php";
}