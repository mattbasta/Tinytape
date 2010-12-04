<script type="text/javascript">
<!--

<?php
$onload = view_manager::get_value("ONLOAD");
if($onload)
	echo "$(document).ready(function() {";
?>
$('.fancybox').fancybox();
$('.addtotape,.simplebox').fancybox({"scrolling":false});
if(picker = document.getElementById("picker"))
	$(picker).farbtastic("#color");
	<?php
if($onload)
	echo "});";
?>

-->
</script>