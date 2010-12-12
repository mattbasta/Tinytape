<section id="my_search">
	<form id="mysearch" method="get" action="<?php echo URL_PREFIX; ?>search/">
		<input type="text" name="q" id="mysearchbox" value="<?php
		if($query = view_manager::get_value("QUERY")) {echo htmlentities($query);}
		?>" />
		<button>Search</button>
	</form>
</section>
<div class="clear"></div>
<div class="g7">
	<?php
	if(ENABLE_FACEBOOK || ENABLE_TWITTER) {
	?>
	<div id="my_identity">
		<?php if(ENABLE_FACEBOOK) { ?>
		<div id="soc_fbwrap" class="gthird">
		<?php
		if(view_manager::get_value("FACEBOOK")) {
			?>
			<div id="my_has_facebook">
				<img src="http://graph.facebook.com/<?php echo view_manager::get_value("FB_UID"); ?>/picture" alt="" class="profile_pic" />
				<strong><?php echo view_manager::get_value("FB_NAME"); ?></strong>
			</div>
			<?php
		} else {
			?>
			<a href="https://graph.facebook.com/oauth/authorize?client_id=192417860416&redirect_uri=<?php echo view_manager::get_value("FB_CONNECT_AUTH"); ?>&scope=publish_stream,offline_access" class="connect_on_fb">Connect on Facebook</a>
			<?php
		}
		?>
			<div class="clear"></div>
		</div>
		<?php }
		if(ENABLE_TWITTER) { ?>
		<div id="soc_twwrap" class="gthird">
		<?php
		if(view_manager::get_value("TWITTER")) {
			?>
			<div id="my_has_twitter">
				<img src="<?php echo view_manager::get_value("TW_AVATAR"); ?>" alt="" class="profile_pic" />
				<strong>@<?php echo view_manager::get_value("TW_SN"); ?></strong>
				<small><?php echo view_manager::get_value("TW_NAME"); ?></small>
			</div>
			<?php
		} else {
			?>
			<a href="<?php echo URL_PREFIX ?>account/twredir" class="connect_on_tw" id="twconnect">Connect on Twitter</a>
			<?php
		}
		?>
			<div class="clear"></div>
		</div>
		<?php } ?>
		<div class="clear"></div>
	</div>
	<?php
	}
	?>
	<form action="<?php echo URL_PREFIX; ?>account/post" method="post" id="my_shout" onsubmit="document.getElementById('my_doshout').disabled = true;">
		<?php
		$phrases = array(
			"What's up, pudding cup?",
			"Shout your heart out",
			"Write me a love song. Because I asked for it.",
			"Hit me baby...one more time?",
			"Write something spontaneous"
		);
		$phrase = $phrases[array_rand($phrases)];
		?>
		<textarea id="my_shoutbox" name="post" onkeyup="$('#my_doshout').removeAttr('disabled');" onfocus="if(this.value==this.defaultValue){this.value='';}" onblur="if(this.value==''){this.value=this.defaultValue;}"><?php echo $phrase; ?></textarea>
		<?php if(ENABLE_FACEBOOK && view_manager::get_value("FACEBOOK")) { ?>
		<label id="my_shout_postfb">
			<input checked type="checkbox" name="dofb" value="true" />
			<span>Post to Facebook</span>
		</label>
		<?php }
		if(ENABLE_TWITTER && view_manager::get_value("TWITTER")) { ?>
		<label id="my_shout_posttw">
			<input checked type="checkbox" name="dotw" value="true" />
			<span>Post to Twitter</span>
		</label>
		<?php } ?>
		<p class="buttons">
			<input type="submit" value="Shout It!" id="my_doshout" disabled />
		</p>
	</form>
	<menu id="feedchooser">
		<li><a id="filter_fullfeed" data-type="fullfeed" class="active" href="#">Everything</a></li>
		<li><a id="filter_feed" data-type="feed" href="#">Just Me</a></li>
		<li><a id="filter_followeefeed" data-type="followeefeed" href="#">Not Me</a></li>
		<li><a id="filter_history" data-type="history" href="#">History</a></li>
		<li><a id="filter_searchhistory" data-type="searchhistory" href="#">Searches</a></li>
		<li><a id="filter_favorites" data-type="favorites" href="#">Favorites</a></li>
	</menu>
	<script type="text/javascript" src="<?php echo URL_PREFIX; ?>activity.js"></script>
	<section id="my_feed">
		<?php
		/*foreach(view_manager::get_value("NEWS_FEED") as $feed_item) {
			require(PATH_PREFIX . "/feed_item.php");
		}*/
		?>
	</section>
</div>
<div class="g5" id="tapelist">
	<section id="my_badges">
		<?php
		$tt_badges = view_manager::get_value("TTBADGES");
		foreach(view_manager::get_value("BADGES") as $badge) {
			$b = $tt_badges[$badge];
			?>
			<a href="/images/badges/<?php echo $b["image"]; ?>.jpg" class="fancybox">
				<img src="/images/badges/<?php echo $b["image"]; ?>.small.jpg" alt="<?php echo htmlentities($b["description"]); ?>" />
			</a>
			<?php
		}
		?>
	</section>
	<section id="my_tapes">
	<?php
	view_manager::add_view(VIEW_PREFIX . "snippets/tapelist");
	echo view_manager::render();
	?>
		<div id="my_newtape_wrap"><a id="my_newtape" href="<?php echo URL_PREFIX; ?>tapes/new">Create a new tape</a></div>
	</section>
	<div class="clear">&nbsp;</div>
	<section id="user_following">
		<?php
		$following = view_manager::get_value("FOLLOWING_USERS");
		if($following) {
			?>
			<p>You are following:</p><?php
			echo "<ul>";
			$count = 0;
			foreach($following as $followee=>$stats) {
				?>
				<li><a href="<?php echo URL_PREFIX; ?>user/<?php echo urlencode($followee); ?>">
					<b><?php echo htmlentities($followee); ?></b><br />
					<small>Level <?php echo $stats["level"]; ?></small>
				</a></li>
				<?php
				if(++$count == 10) {
					break;
				}
			}
			echo "</ul>";
			if($count == 10) {
				?><a id="my_allfollowing" href="<?php echo URL_PREFIX; ?>api/ajax/following/<?php echo urlencode($username); ?>" class="fancybox">Everybody you follow</a><?php
			}
		} else {
			?><p>You are not following anybody.</p><?php
		}
		?>
	</section>
</div>