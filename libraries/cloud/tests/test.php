<?php
header("Content-type: text/plain");
?>
Test Suite

Loading Cloud...
<?php
require("cloud/cloud.php");
?>
Loaded!

<?php

require("credentials.php");
function write($text='') {echo "$text\n";}
function a($condition) {if(!$condition) {throw new Exception("Assertion failed.");}}

define("DEBUG", true);
write(time());

foreach($credentials as $driver=>$creds) {
	write("$driver : Starting Test Suite\n");
	$db = cloud::connect($driver, $creds);
	
	write("Creating test table");
	if($driver == "mysql")
		$columns = array(new cloud_column("id", "bigint", 0, "PRI", false, "AUTO_INCREMENT"));
	else
		$columns = array();
	foreach(array(
		new cloud_column("def", "varchar", 10, false, "foo"),
		new cloud_column("bar", "varchar", 50),
		new cloud_column("zap", "varchar", 50)
	) as $col) {
		$columns[] = $col;
	}
	$db->create_table("foo", $columns);
	
	write("Initializing test table");
	$table = $db->get_table("foo");
	
	write("Testing primary key");
	a($table->get_primary_column()->key == "PRI");
	
	$table->insert(array(
		"def"=>"abc",
		"bar"=>"def",
		"zap"=>"ghi"
	));
	$table->insert(array(
		"bar"=>"jkl",
		"zap"=>"mno"
	));
	$table->insert(array(
		"def"=>"abc",
		"bar"=>"jkl"
	));
	
	$table->update(
		array(
			"def"=>"abc",
			"bar"=>"jkl"
		),
		array(
			"zap"=>"wham"
		)
	);
	
	write("Testing FETCH_COUNT");
	a($table->fetch(array("def"=>"foo"), FETCH_COUNT) == 1);
	write("Testing fetch_exists");
	a($table->fetch_exists(array("def"=>"foo")));
	
	write("Testing FETCH_ARRAY");
	$abc = $table->fetch(array("def"=>"abc"), FETCH_ARRAY);
	write("> Testing correct return count: " . count($abc));
	a(count($abc) == 2);
	write("> Testing correct return values");
	foreach($abc as $item) {
		switch($item["bar"]) {
			case "def":
				a($item["zap"] == "ghi");
				break;
			case "jkl":
				a($item["zap"] == "wham");
				break;
			default:
				throw new Exception("Unexpected column");
		}
	}
	
	write("Testing FETCH_SINGLE_ARRAY and :colums");
	$sarr = $table->fetch(
		array("zap"=>"ghi"),
		FETCH_SINGLE_ARRAY,
		array("columns"=>array("def", "bar"))
	);
	var_dump($sarr);
	a($sarr == array("def"=>"abc", "bar"=>"def"));
	
	write("Testing delete");
	$table->delete(array("zap"=>"wham"));
	a(!$table->fetch_exists(array("zap"=>"wham")));
	
	write("Dropping table");
	$table->drop();
	
	write("Confirming table drop");
	$table_list = $db->get_table_list();
	a(!in_array("foo", $table_list));
	
	write("Closing");
	$db->close();
	if($driver == "sqlite")
		unlink("test.db");
	write();
}
