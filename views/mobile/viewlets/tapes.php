<?php
view_manager::set_value("TITLE", "Tapes");
view_manager::set_value("PAGE_ID", "tapes");
view_manager::set_value("THEME", "b");

$tapes = view_manager::get_value("TAPES");
if($tapes !== false) {
?>
<ul data-role="listview">
	<?php
	foreach($tapes as $tape) {
		?>
		<li style="background:#<?php echo $tape["color"];?>"><a href="<?php echo URL_PREFIX; ?>tape/<?php echo $tape["name"]; ?>"><?php echo $tape["title"]; ?></a></li>
		<?php
	}
	?>
</ul>
<?php
}

