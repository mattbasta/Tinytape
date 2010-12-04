<?php

$r = new Redis();
$r->connect('192.168.140.217', 6379);
$r->auth(file_get_contents('/var/auth/redis'));
view_manager::set_value("redis", $r);
