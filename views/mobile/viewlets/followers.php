<?php
view_manager::set_value("TITLE", "Followers");
view_manager::set_value("PAGE_ID", "followers");
view_manager::set_value("THEME", "b");

$username = view_manager::get_value("USERNAME");
$following = view_manager::get_value("FOLLOWEE_USERS");
if($following) {
	?>
	<p>People that follow you:</p><?php
	echo "<ul data-role=\"listview\">";
	foreach($following as $followee) {
		?>
		<li><a href="<?php echo URL_PREFIX; ?>user/<?php echo urlencode($followee); ?>"><?php echo htmlentities($followee); ?></a></li>
		<?php
	}
	echo "</ul>";
} else {
	?><p>Nobody follows <?php echo (($session->username == $username) ? "you" : htmlentities($username)); ?>.</p><?php
}