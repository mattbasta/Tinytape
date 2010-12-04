<?php
echo view_manager::render();
?>
	
	<?php
	if(isset($_GET['invalid'])) {
		?>
		<p class="formerror">You have entered an invalid username or password.</p>
		<?php
	}
	?>
	
	<label>Tape Name</label>
	<input type="text" name="title" />
	
	<?php
	if(view_manager::get_value("AJAX")) {
		echo '<input type="hidden" name="color" id="color" value="#000000" />';
	} else {
	?>
	<label>Color</label>
	<input type="hidden" name="color" id="color" value="#000000" />
	<div id="picker"></div>
	<?php
	}
	?>
	
	<label>URL</label>
	<p class="money"><span><?php echo URL_PREFIX; ?>tape/</span><input type="text" name="url" /></p>
	
	
	<div class="buttons">
		<input type="submit" value="Create Tape" />
	</div>

<!-- Opened by another view-->
</form>
</div>