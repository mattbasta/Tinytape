<ul data-role="listview" data-inset="true" data-theme="e">
	<li><?php echo $score = view_manager::get_value("SCORE"); ?> points</li>
	<li>Level <?php echo getLevel($score); ?></li>
</ul>

<ul data-role="listview" data-inset="true">
	<li><a href="#feed">News Feed</a></li>
	<li><a href="#tapes">Tapes</a></li>
	<li><a href="<?php echo URL_PREFIX ?>account/history">History</a></li>
	<li><a href="#badges">Badges</a></li>
	<li><a href="<?php echo URL_PREFIX ?>account/favorites">Favorites</a></li>
	<li><a href="#following">Following</a></li>
	<li><a href="#followers">Followers</a></li>
</ul>
<?php

view_manager::add_view(VIEW_PREFIX . "viewlets/news_feed");
view_manager::add_view(VIEW_PREFIX . "viewlets/badges");
view_manager::add_view(VIEW_PREFIX . "viewlets/tapes");
view_manager::add_view(VIEW_PREFIX . "viewlets/following");
view_manager::add_view(VIEW_PREFIX . "viewlets/followers");
