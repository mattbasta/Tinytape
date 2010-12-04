<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=windows-1252" />
	<title><?=view_manager::get_value('TITLE')?></title>
	<!--[if lte IE 7]>
	<link type="text/css" rel="stylesheet" href="/css/ie.css" />
	<![endif]-->
	<!--[if lte IE 8]>
	<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]--> 
	<link type="text/css" rel="stylesheet" href="http://framecdn.serverboy.net/latest.css" />
	<link type="text/css" rel="stylesheet" href="/css/tape.css" />
	<link type="text/css" rel="stylesheet" href="<?php echo URL_PREFIX; ?>common.css" />
	<link type="text/css" rel="stylesheet" href="/scripts/fancybox/jquery.fancybox-1.3.1.css" media="screen" />
	<script src="/scripts/compact.js" type="text/javascript"></script>
<?php
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
?>
-->
</script> 
	<script type="text/javascript" src="<?php echo URL_PREFIX; ?>invisiplayer.js"></script>
</head>
<body class="sans-serif">
<div id="container">
	<header>
		<h1><?=view_manager::get_value('TITLE')?></h1>
		<div class="hcont" style="background:#<?=view_manager::get_value('COLOR')?>">
			<?
			if(view_manager::get_value('SHUFFLE')){
				echo '<a rel="nofollow" href="?">Turn Shuffle Off</a>';
			}else{
				echo '<a rel="nofollow" href="?shuffle">Turn Shuffle On</a>';
			}
			if($session->logged_in && $session->username == view_manager::get_value("OWNER")) {
				?> &bull; <a href="/tapes/<?=view_manager::get_value('NAME')?>">Edit Tape</a><?php
			}
			?> &bull; <a href="/tape/<?=view_manager::get_value('ID')?>">Back to Tinytape</a>
		</div>
		<div class="clear"></div>
	</header>
	<div class="wrap">
		<?php
		$results = view_manager::get_value('INSTANCES');
		
		if($results != false) {
		
			$tempsecret = uniqid();
			$embeddable = true;
			$rlive = false;
			$racoustic = false;
			$rremix = false;
			$rclean = false;
			$hide_view_edit = true;
			
			?>
		<ul id="searchlist">
			<?php
			$json_data = array();
			foreach($results as $result) {
				$uid = sha1($result['id'] . $tempsecret);
				
				$rtitle = $result['title'] . ' - ' . $result['artist'];
				$rid = $result['id'];
				$instance_id = $result['instance'];
				require(PATH_PREFIX . '/result.php');
				
				$json_data[$uid] = array(
					"service"=>"tinytape",
					"resource"=>array(
						"id"=>(int)$result['id'],
						"instance"=>(int)$result['instance']
					),
					"metadata"=>array(
						"title"=>htmlentities($result['title']),
						"artist"=>htmlentities($result['artist']),
						"album"=>htmlentities($ralbum)
					)
				);
				
			}
		?>
		</ul>
		<script type="text/javascript">
		<!--
		player.register_all(<?php echo json_encode($json_data); ?>);
		-->
		</script>
		<?php
		} else {
			?><p id="whoawhoawhoa">Whoa whoa whoa whoa. You don't have any songs here yet! You should <a href="<?php echo URL_PREFIX; ?>search">go find some</a>.</p><?php
		}
		?>
	</div>
	<div class="clear"></div>
	<footer>
		&copy; Copyright <?php echo date('Y'); ?>, Tinytape &bull;
		Powered by <?php
			
			$wiseass = array(
				'Hamsters',
				'The N\'Avi',
				'Billy Joel',
				'Strawberry Milkshakes',
				'Free Energy',
				'ECHELON',
				'like fifty patoots',
				'oxy-morons',
				'a series of only moderately unfortunate events',
				'Baby Jesus',
				'Dark Magic'
			);
			echo $wiseass[array_rand($wiseass)];
			?>
		</section>
	</footer>
</div>

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
<div id="badgestage" style="display:none">
<?php
if(view_manager::get_value("BADGE_SHUFFLER")) {
	?>
	<p class="f_c">Congratulations, you've earned yourself the <b>Kansas City Shuffle Badge</b>. You've shuffled fifteen tapes. As they say, &quot;you look left, <a href="http://www.youtube.com/watch?v=Dlc9pPeS2Q0" onclick="window.open(this.href);return false;">Bruce Willis snaps your neck</a>.&quot;</p>
	<p class="f_c"><a href="javascript:$.fancybox.close();">Ok, whatever</a></p>
	<img class="badge_large" src="/images/badges/slevin.jpg" alt="Kansas City Shuffle Badge" />
	<?php
}?>
</div>

<?php
if(view_manager::get_value("BADGE")) {
	?>
	<script type="text/javascript">
	<!--
	$(document).ready(function() {
		$.fancybox(
			$("#badgestage").html(),
			{
				autoDimensions:false,
				width:300,
				height:400
			}
		);
	});
	-->
	</script>
	<?php
}

?>

<div id="notificationstage" style="display:none;"></div>

</body>
</html>