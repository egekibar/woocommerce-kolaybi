<?php

function view($template, $global) {
	$varible = "view_{$template}";
	global $$varible;
	$$varible = $global;
	include "public/view/{$template}.php";
}