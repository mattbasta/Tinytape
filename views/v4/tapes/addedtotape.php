<div id="tape_added">
	We've added it!<br />
	<a href="<?php echo URL_PREFIX; ?>api/tapes/add_to_tape/<?php echo view_manager::get_value("URL_ID"); ?>" class="addtotape">Add to Another</a><br />
	<a href="javascript:$.fancybox.close()">Close</a>
</div>
<?php
view_manager::add_view(VIEW_PREFIX . "snippets/jquery_reload");
echo view_manager::render();