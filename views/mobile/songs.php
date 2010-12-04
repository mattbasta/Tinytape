<?php
if($songs = view_manager::get_value("SONGS")) {
?>
<ul data-role="listview">
<?php
foreach($songs as $item) {
	?>
	<li>
		<a href="<?php echo URL_PREFIX, "song/", $item["resource"]["id"]; ?>"><?php echo $item["metadata"]["title"], " by ", $item["metadata"]["artist"]; ?></a>
	</li>
	<?php
}

?>
</ul>
<?php
} else {
	?>
	<p>There are no songs to show.</p>
	<?php
}
?>