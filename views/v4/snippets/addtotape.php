<div class="scrollablefancy">
<?php
$tapes = view_manager::get_value("TAPES");
if($tapes !== false) {
?>
<ul class="my_tapelist">
	<?php
	foreach($tapes as $tape) {
		?>
		<li style="background:#<?php echo $tape["color"];?>"><a href="<?php echo URL_PREFIX; ?>song/add_to_tape/<?php echo $tape["name"]; ?>/<?php echo view_manager::get_value("SONG"); ?>/<?php echo view_manager::get_value("INSTANCE"); ?>" class="simplebox"><?php echo $tape["title"]; ?></a></li>
		<?php
	}
	?>
</ul>
<?php
}
?>
</div>
<div class="clear"></div>
<a id="new_tape" href="<?php echo URL_PREFIX; ?>tapes/new/ajax" class="fancybox">Add to a New Tape</a>
<?php
view_manager::add_view(VIEW_PREFIX . "snippets/jquery_reload");
echo view_manager::render();