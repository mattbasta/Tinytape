<?php
view_manager::set_value("TITLE", "Badges");
view_manager::set_value("PAGE_ID", "badges");
view_manager::set_value("THEME", "b");

$tt_badges = view_manager::get_value("TTBADGES");
?><ul data-role="listview"><?php
foreach(view_manager::get_value("BADGES") as $badge) {
	$b = $tt_badges[$badge];
	?>
	<li><a href="<?php echo URL_PREFIX, "badge/", $badge ?>"><?php echo htmlentities($b["title"]); ?></a></li>
	<?php
}
?></ul>