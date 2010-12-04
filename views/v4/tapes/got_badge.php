<?php
$badge = view_manager::get_value("BADGE");
?>
<div style="width:300px">
	<img class="badge_large" src="/images/badges/<?php echo $badge["image"]; ?>.jpg" alt="<?php echo htmlentities($badge["title"]); ?>" />
	<p class="f_c">Congratulations, you've earned yourself the <b><?php echo $badge["title"]; ?></b>. <?php echo $badge["earned_string"]; ?></p>
	<p class="f_c">
		<a href="<?php echo URL_PREFIX; ?>api/tapes/add_to_tape/<?php echo view_manager::get_value("URL_ID"); ?>" class="addtotape">Thanks, but I wasn't done</a><br />
		<a href="javascript:$.fancybox.close()">Thanks, I was done</a><br />
		<a href="javascript:alert('You\'re a douchebag, you know that?');">I never wanted this. Go away.</a>
	</div>
</div>
<?php
view_manager::add_view(VIEW_PREFIX . "snippets/jquery_reload");
echo view_manager::render();
