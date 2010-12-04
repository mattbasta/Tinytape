<li>
	<!--<img src="/v4/api/albumart/redirect?title=<?php echo urlencode($rtitle); ?>&amp;artist=<?php echo urlencode($rartist); ?>&amp;album=<?php echo urlencode($ralbum); ?>&amp;size=large" />-->
	<h3><a href="#play"><?php echo htmlentities($rtitle); ?></a></h3>
	<p>by <?php
		echo htmlentities($rartist);
		if(!empty($ralbum)) {
			echo "on ", htmlentities($ralbum);
		}
	?></p>
</li>