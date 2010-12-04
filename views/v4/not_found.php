<div class="g3">&nbsp;</div>
<div class="g6" id="notfound">
	<h2>Awww...crap.</h2>
	<p>We didn't find what you're looking for. You need to go back and evaluate your priorities.</p>
	<?php
	if(view_manager::get_value("BADGE")) {
		?>
		<p>Congratulations, you've earned the <b>Inspector Cluso Badge</b>. Way to be a trooper.</p>
		<img class="badge_large" src="/images/badges/cluso.jpg" alt="Inspector Cluso Badge" />
		<?php
	}
	?>
</div>