<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo view_manager::get_value("TITLE"); ?></title>
<meta name="google-site-verification" content="BxfiErkYh-Zi84pZg6foCn6oH1SGkp1PeZzBq0jwpvU" />
<!--[if IE]><script type="text/javascript" src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
<script type="text/javascript" src="/scripts/compact.js"></script>
<script type="text/javascript" src="/scripts/jquery.jeditable.min.js"></script>
<script type="text/javascript" src="<?php echo URL_PREFIX; ?>invisiplayer.js"></script>
<link type="text/css" rel="stylesheet" href="http://framecdn.serverboy.net/latest.css" />
<link type="text/css" rel="stylesheet" href="<?php echo URL_PREFIX; ?>common.css" />
<link type="text/css" rel="stylesheet" href="<?php echo URL_PREFIX; ?>main.css" />
<link type="text/css" rel="stylesheet" href="/scripts/fancybox/jquery.fancybox-1.3.1.css" media="screen" />
<?php

if($thumbnail = view_manager::get_value("THUMBNAIL")) {
	?><link type="image/jpeg" rel="image_src" href="<?php echo $thumbnail; ?>" /><?php
}

// Effectively view_manager::add_view() in reverse
array_unshift(view_manager::$stack, VIEW_PREFIX . "snippets/jquery_reload");
view_manager::set_value("ONLOAD", true);
echo view_manager::render();
?>
<script type="text/javascript"> 
<?php
if($session->logged_in) {
	echo "logged_in = true;\nusername = '", htmlentities($session->username), "';\n";
}
if($default_feed = view_manager::get_value("FEED_TYPE")) {
	echo "default_feed='$default_feed';\n";
}
if(view_manager::get_value("SONG_PAGE") && view_manager::get_value("USER_SCORE") > EDIT_SONG_MIN_POINTS) {
	?>
$(document).ready(function(){
	$(".sed_title").editable("<?php echo URL_PREFIX; ?>api/songs/edit/<?php echo view_manager::get_value("ID"); ?>/title",{submit:"OK"});
	$(".sed_artist").editable("<?php echo URL_PREFIX; ?>api/songs/edit/<?php echo view_manager::get_value("ID"); ?>/artist",{submit:"OK"});
	$(".sed_album").editable("<?php echo URL_PREFIX; ?>api/songs/edit/<?php echo view_manager::get_value("ID"); ?>/album",{submit:"OK"});
});
	<?php
}
?>
-->
</script> 
</head>
<body class="sans-serif">
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-91087-22']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
<div id="container">
	<header>
		<strong>
			<a href="http://<?php echo DOMAIN . URL_PREFIX;?>">Tinytape</a>
		</strong>
		
		<?php
		if($session->logged_in) {?>
			<section id="headerstats">
				<div class="stat level">
					<small>level</small>
					<strong><?php echo view_manager::get_value("USER_LEVEL"); ?></strong>
				</div>
				<div class="stat points">
					<strong><?php echo view_manager::get_value("USER_SCORE"); ?></strong>
					<small>points</small>
				</div>
			</section>
		<?php } ?>
		<section class="user">
			<?php if($session->logged_in) { ?>
			<b>Welcome back, <a href="<?php echo URL_PREFIX; ?>account"><?php echo $session->username; ?></a>!</b> <a href="<?php echo URL_PREFIX; ?>login/logout">Sign Out</a>
			<?php } else { ?>
			<a href="<?php echo URL_PREFIX; ?>login/signup">Sign Up</a> or <a href="<?php echo URL_PREFIX; ?>login<?php
				if($redirect = view_manager::get_value('REDIRECT'))
					echo "?redirect=", urlencode($redirect);
				?>">Sign In</a>
			<?php } ?>
		</section>
		<div class="clear"></div>
	</header>
	<?php echo view_manager::render(); ?>
	<div class="clear"></div>
	<footer>
		<form id="footersearch" method="get" action="<?php echo URL_PREFIX; ?>search/">
			<input type="text" name="q" id="footersearchbox" value="<?php
			if($query = view_manager::get_value("QUERY")) {echo htmlentities($query);}
			?>" />
			<button>Search</button>
		</form>
		<strong>&copy; Copyright 2010, Tinytape</strong><br />
		Tinytape is powered by <a href="http://serverboy.net/">Serverboy Software</a> and <?php
		$wiseass = array(
			'the letter W',
			'the number 7',
			'more than one internet',
			'a little piece of Jesus',
			'some of God\'s tears',
			'PussyWhip, the dessert topping for your cat',
			'a strange sense of self-loathing',
			'an unnatural passion for internet',
			'Soviet Russia, where tiny tapes listen you!',
			'Soviet Russia, where internet surf you!',
			'mystery meat: &quot;It\'s what\'s for dinner!&quot;',
			'Pi Day International',
			'the folks that brought you Enormoustape'
		);
		echo $wiseass[array_rand($wiseass)];
		?>
	</footer>
</div>
<!-- Only ugly hidden stuff from here down. :( -->
<div id="badgestage" style="display:none"></div>

<div style="position:absolute;top:-10000px;"><div id="invisiplayer"></div></div>

<div id="controls">
	<div id="notifications"></div>
	<div class="clear"></div>
	<div id="playpause">
		<a href="#" onclick="if(player.state=='play'){player.pause();}else{player.play();}return false;">Pause</a>
		<a href="#" onclick="player.repeat=!player.repeat;this.innerHTML=player.repeat?'Disable Repeat':'Repeat';document.getElementById('shell_playnext').style.display=player.repeat?'none':'inline';return false;">Repeat</a>
		<a href="#" id="shell_playnext" onclick="player.startnext();return false;">Next &raquo;</a>
	</div>
	<div class="clear"></div>
</div>

<div id="notificationstage" style="display:none;"></div>
<?php /*
<div id="fb-root"></div>
<script>
window.fbAsyncInit = function() {
	FB.init({appId: '192417860416', status: true, cookie: true, xfbml: true});
	fbready();
};
(function() {
	var e = document.createElement('script'); e.async = true;
	e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
	document.getElementById('fb-root').appendChild(e);
}());
</script>
*/?>
</body>
</html>