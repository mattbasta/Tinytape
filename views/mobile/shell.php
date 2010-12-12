<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1252" />
<title>Tinytape Mobile</title>
<meta name="apple-mobile-web-app-capable" content="yes" />
<link rel="stylesheet" href="http://code.jquery.com/mobile/1.0a2/jquery.mobile-1.0a2.min.css" type="text/css" />
<script src="http://code.jquery.com/jquery-1.4.3.min.js"></script>
<script src="http://code.jquery.com/mobile/1.0a2/jquery.mobile-1.0a2.min.js"></script>
<script type="text/javascript" src="<?php echo URL_PREFIX; ?>invisiplayer.js"></script>
<script type="text/javascript"> 
<!--
<?php
if($session->logged_in) {
	echo "logged_in = true;\nusername = '", htmlentities($session->username), "';\n";
}
?>
-->
</script> 
</head>
<body>
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
<?php
while($render = view_manager::render()) {
?>
<div data-role="page"<?php
	if($theme = view_manager::get_value("THEME"))
		echo " data-theme=\"$theme\"";
	if($page_id = view_manager::get_value("PAGE_ID"))
		echo " id=\"$page_id\"";
	else
		echo " id=\"root\"";
	?>>

	<div data-role="header"<?php
	if($theme) { echo " data-theme=\"$theme\""; }
	?>>
		<?php
		if(view_manager::get_value("SHOWBACK")) {
			?><a href="<?php echo URL_PREFIX; ?>" data-icon="back">Home</a><?php
		}
		?>
		<h1><?php echo view_manager::get_value("TITLE"); ?></h1>
		<?php
		if(!$session->logged_in) {
			?><a href="<?php echo URL_PREFIX; ?>login" data-icon="arrow-r" data-theme="b" class="ui-btn-right">Login</a><?php
		} else {
			?><a href="<?php echo URL_PREFIX; ?>account&root" data-icon="grid" class="ui-btn-right">Account</a><?php
		}
		?>
	</div>
	
	<div data-role="content">
		<?php echo $render; ?>
	</div>


	<div data-role="footer"<?php
	if($theme) { echo " data-theme=\"$theme\""; }
	?>>
		<h4>&copy; Copyright <?php echo date("Y"); ?></h4>
	</div>
	
</div>
<?php
}
?>
</body>
</html>