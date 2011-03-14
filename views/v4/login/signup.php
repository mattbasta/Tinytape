<form id="signupform" action="<?php echo URL_PREFIX; ?>login/go/signup" method="post">
	
	<?php
	if(!empty($_GET['invalid'])) {
		$errors = explode('-', $_GET['invalid']);
		echo '<p class="formerror">';
		foreach($errors as $e) {
			if(empty($e))
				continue;
			switch($e) {
				case 'email':
					echo 'The email that you have provided is invalid.<br />';
					break;
				case 'again':
					echo 'The passwords you have entered do not match.<br />';
					break;
				case 'plen':
					echo 'The password must be at least seven characters long.<br />';
					break;
				case 'captcha':
					echo 'You did not correctly solve the math problem.<br />';
					break;
				case 'exists':
					echo 'The email or username you have chosen already exists.<br />';
					break;
				case 'fool':
					echo 'You\'ve got to read, botha. Read the legal CRAAAAPPPPPP!<br />';
					break;
			}
		}
		echo '</p>';
	} else {
		?>
		<p>
			I'm glad you could all come to <a onclick="window.open(this.href);return false;" href="http://www.youtube.com/watch?v=OMHHWfSe4TE#t=6m00s">my little party</a>.<br />
			It usually only takes one minute to sign up, but we don't think you can do it. Prove us wrong.
		</p>
		<?php
	}
	?>
	
	<label>Username</label>
	<input type="text" name="username" />
	
	<label>Password</label>
	<input type="password" name="password" />
	
	<label>Again</label>
	<input type="password" name="confirm" />
	
	<label>Email</label>
	<input type="text" name="email" />
	
	<label><?php echo view_manager::get_value('MATH'); ?></label>
	<input type="text" name="math" />
	
	<label class="checkbox">
		<input type="checkbox" name="privacy" value="coolbeans" /> <span>I read and understood all the <a href="<?php echo URL_PREFIX; ?>privacy">legal crap</a>.</span>
	</label>
	
	<div class="clear"></div>
	
	<div class="buttons">
		<input type="submit" value="I'm done!" />
	</div>
	<p class="jk">Just kidding, I <a href="<?php echo URL_PREFIX; ?>login">wanted to sign in instead</a>!</p>
	<?php
	if(view_manager::get_value('REDIRECT')){
		?><input type="hidden" name="redirect" value="<?php echo view_manager::get_value('REDIRECT'); ?>" /><?php
	}
	?>
	
</form>