<aside id="youshouldsignup">
	<img src="/images/popuptop.png" alt="" />
	<strong>Not signed up?</strong>
	<p>Get yourself an account in under a minute. All the cool kids have one.<br />
	<a href="<?php echo URL_PREFIX; ?>login/signup">Sign up now</a></p>
</aside>
<form id="loginform" action="<?php echo URL_PREFIX; ?>login/go/login" method="post">
	
	<?php
	if(isset($_GET['invalid'])) {
		?>
		<p class="formerror">You have entered an invalid username or password.</p>
		<?php
	}
	?>
	
	<label>Username</label>
	<input type="text" name="username" />
	
	<label>Password</label>
	<input type="password" name="password" />
	
	
	<div class="buttons">
		<input type="submit" value="Sign In" />
	</div>
	<?php
	if(view_manager::get_value('REDIRECT')){
		?><input type="hidden" name="redirect" value="<?php echo view_manager::get_value('REDIRECT'); ?>" /><?php
	}
	?>
	
</form>