<?php

define("URL_PREFIX", "/m/");
define("NM_URL_PREFIX", "/v4/"); // Non-mobile URL prefix
define("VIEW_PREFIX", "mobile/");

$db = cloud::create_db(
	'mysql',
	array(
		'username'=>'com_tinytape',
		'password'=>'RE^@DcH5fZ%6t4g73(9C',
		'database'=>'tinytape',
		'server'=>'localhost'
	)
);

require(IXG_PATH_PREFIX . "libraries/prefixes.php");
