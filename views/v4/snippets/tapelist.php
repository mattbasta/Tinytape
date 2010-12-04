<?php
$tapes = view_manager::get_value("TAPES");
if($tapes !== false) {
?>
<ul class="my_tapelist">
	<?php
	foreach($tapes as $tape) {
		?>
		<li style="background:#<?php echo $tape["color"];?>"><a href="<?php echo URL_PREFIX; ?>tape/<?php echo $tape["name"]; ?>"><?php echo $tape["title"]; ?></a></li>
		<?php
	}
	?>
</ul>
<?php
if(!view_manager::get_value("API") && view_manager::get_value("MORE_TAPES")) {
	?>
	<a id="my_alltapes" href="<?php echo URL_PREFIX; ?>api/tapes/load/<?php echo urlencode(view_manager::get_value("USERNAME")); ?>" class="fancybox">Show All Tapes</a>
	<?php
}
}
