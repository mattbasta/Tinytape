<div id="signupdonedialog">
	<time>
		<?php echo view_manager::get_value("DURATION_MINUTES"); ?>:<?php echo str_pad(view_manager::get_value("DURATION_SECONDS"), 2, "0"); ?>
	</time>
	<?php
	if(view_manager::get_value("PASSED_DURATION")) {
		?>
		<p>Congratulations! You managed to sign up for Tinytape in under a minute. That earns you the <b>Fast Signup Badge</b>. Go make yourself some PB&amp;J and know that you've got wits of lightning and hand-eye coordination of steel.</p>
		<p class="f_c"><a href="<?php echo URL_PREFIX; ?>account">I accept this honor; onward to my account page!</a></p>
		<p class="f_c">
			<img class="badge_large" src="/images/badges/stopwatch.jpg" alt="Fast Signup" />
		</p>
		<?php
	} else {
		?>
		<p>It took you that long to sign up? Wow, way to confirm our suspicions.</p>
		<p>Anyway, as a consolation prize, you've earned the <b>Slowpoke Badge</b>.</p>
		<p class="f_c"><a href="<?php echo URL_PREFIX; ?>account">I need some time alone [on my account page].</a></p>
		<p class="f_c">
			<img class="badge_large" src="/images/badges/slowpoke.jpg" alt="Slowpoke" />
		</p>
		<?php
	}
	?>
</div>