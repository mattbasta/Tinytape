<?php
view_manager::set_value("TITLE", "News Feed");
view_manager::set_value("PAGE_ID", "feed");
view_manager::set_value("THEME", "b");

$username = view_manager::get_value("USERNAME");
?>
<form action="<?php echo URL_PREFIX; ?>account/post" method="post">
	<?php
	$phrases = array(
		"What's up, pudding cup?",
		"Shout your heart out",
		"Write me a love song. Because I asked for it.",
		"Hit me baby...one more time?",
		"Write something spontaneous"
	);
	$phrase = $phrases[array_rand($phrases)];
	?>
	<textarea id="my_shoutbox" name="post" onkeyup="$('#my_doshout').removeAttr('disabled');" onfocus="if(this.value==this.defaultValue){this.value='';}" onblur="if(this.value==''){this.value=this.defaultValue;}"><?php echo $phrase; ?></textarea>
	<p class="buttons">
		<input type="submit" value="Shout It!" id="my_doshout" disabled />
	</p>
</form>
<ul data-role="listview" data-theme="c" style="margin-top:1em;">
	<?php
	foreach(view_manager::get_value("NEWS_FEED") as $feed_item) {
		$fi = json_decode($feed_item, true);
		if($fi["type"] == "songs")
			continue;
		require(PATH_PREFIX . "/feed_item.php");
	}
	?>
</ul>