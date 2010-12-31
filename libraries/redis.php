<?php

try {
	$r = new Redis();
	$r->connect('192.168.140.217', 6379);
	$r->auth(file_get_contents('/var/auth/redis'));
	view_manager::set_value("redis", $r);
} catch (Exception $e) {
	?>
<!DOCTYPE html>
<html>
<head>
<title>Tinytape is sleeping...</title>
<style type="text/css">
#sleeping {
	font-family:sans-serif;
	text-align:center;
	padding:300px;
	background:url("/images/sleeping.png") no-repeat center 30px;
}
</style>
</head>
<body>
<div id="sleeping">
	<p>Hey bud, Tinytape is down for a nap. Yeah, a nap.<br />Come back in a few.</p>
</div>
</body>
</html>
	<?php
	exit;
}
