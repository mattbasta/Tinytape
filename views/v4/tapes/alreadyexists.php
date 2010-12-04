<div id="tape_alreadycontains">
	That tape already contains copy of that song.<br />
	<a href="<?php echo URL_PREFIX; ?>api/tapes/add_to_tape/<?php echo view_manager::get_value("URL_ID"); ?>" class="addtotape">Choose Another</a>
</div>
<?php
view_manager::add_view(VIEW_PREFIX . "snippets/jquery_reload");
echo view_manager::render();