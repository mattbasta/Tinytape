<?php
view_manager::set_value("TITLE", "Following");
view_manager::set_value("PAGE_ID", "following");
view_manager::set_value("THEME", "b");

$following = view_manager::get_value("FOLLOWING_USERS");
if($following) {
	?>
	<p>You are following:</p><?php
	echo "<ul data-role=\"listview\">";
	foreach($following as $followee) {
		?>
		<li><a href="<?php echo URL_PREFIX; ?>user/<?php echo urlencode($followee); ?>"><?php echo htmlentities($followee); ?></a></li>
		<?php
	}
	echo "</ul>";
} else {
	?><p><?php echo ($session->username == $username ? "You don't" : htmlentities($username) . " doesn't"); ?> follow anybody.</p><?php
}