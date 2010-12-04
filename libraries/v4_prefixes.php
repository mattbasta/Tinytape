<?php

define("URL_PREFIX", "/");
define("VIEW_PREFIX", "v4/");
define("LASTFM_APIKEY", "797d99f7607a8a201decf16c0c3d4f13");

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
