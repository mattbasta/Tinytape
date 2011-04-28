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
	
	<label>Color</label>
	<input type="hidden" name="color" id="color" value="#000000" />
	<div id="picker">click here to choose a color</div>
	
	<label>URL</label>
	<p class="money"><span><?php echo URL_PREFIX; ?>tape/</span><input type="text" name="url" /></p>
	
	
	<div class="buttons">
		<input type="submit" value="Create Tape" />
	</div>

<!-- Opened by another view-->
</form>
</div>