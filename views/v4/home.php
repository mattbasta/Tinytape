<?php

$history = view_manager::get_value("SONGS");
$albumartreg = array();

?>
<div id="historywrap">
	<section id="homehistory">
		<?php
		$history_count = 0;
		foreach($history as $hist) {
			$uid = md5(uniqid());
			?>
			<a class="historyitem" href="#" title="<?php echo htmlentities($hist["metadata"]["title"]); ?>" onclick="doplay('<?php echo $uid; ?>');return false;">
				<canvas id="<?php echo $uid; ?>" width="120" height="120"></canvas>
			</a>
			<?php
			$albumartreg[$uid] = $hist;
			$history_count++;
		}
		?>
	</section>
	<div id="historymeta">
		<a href="#" onclick="hh.style.left=(hh.offsetLeft+360)+'px';return false;" class="previous">Previous</a>
		<a href="#" onclick="hh.style.left=(hh.offsetLeft-360)+'px';return false;" class="next">Next</a>
		<a class="meta" id="meta_title"></a>
		<a class="meta" id="meta_artist"></a>
	</div>
	<div id="historyhardware">
		<div id="hardware_global"></div>
		<div id="playbar_global"></div>
	</div>
</div>
<script type="text/javascript">
<!--
var hh = document.getElementById("homehistory");
var canvases = hh.getElementsByTagName("canvas");
var redrawhome = function(){
	var hw = document.getElementById("historywrap");
	hw.style.width = document.width + "px";
	hw.style.margin = "0 0 0 -" + document.getElementById("container").offsetLeft + "px";
	//hh.style.l;
};
$(document).ready(function(){
	redrawhome();
	<?php
	foreach($albumartreg as $uid=>$hist) {
		?>albumart("<?php echo $uid; ?>", <?php echo json_encode($hist); ?>);<?php
	}
	?>
	var halfcount = Math.floor(canvases.length / 2);
	while(canvases[halfcount].style.display == "none")
		halfcount++;
	doplay(canvases[halfcount].id, true);
	if($.browser.webkit)
		setTimeout(redrawhome, 1000);
});
$(window).resize(redrawhome);
function doplay(uid, dontplay) {
	$(".home_willplay").removeClass("home_willplay");
	
	var pos = 0;
	for(var i=0;i<canvases.length;i++) {
		if(canvases[i].id == uid)
			break;
		if(canvases[i].style.display != "none")
			pos++;
	}
	
	var ow = document.getElementById("historywrap").offsetWidth;
	var toplay = document.getElementById(uid);
	toplay.className = "home_willplay";
	hh.style.left = -1 * (pos * 120 - ow / 2 + 90) + "px";
	
	var m_title = document.getElementById("meta_title");
	m_title.href = "<?php echo URL_PREFIX; ?>song/view/" + player._register[uid].resource.id;
	$(m_title).html(player._register[uid].metadata.title);
	
	var m_artist = document.getElementById("meta_artist"),
	    artist = player._register[uid].metadata.artist;
	m_artist.href = "<?php echo URL_PREFIX; ?>song/artist/" + escape(artist);
	$("#meta_artist").html(artist);
	
	if(!dontplay)
		player.start(uid);
}
function loadart(euid, image) {
	var context = euid.getContext("2d");
	//context.fillText(image, 0, 10, 120);
	if(image == "") {
		delete player._register[euid.id];
		return euid.style.display = "none";
	}
	var img = new Image();
	img.onload = function() {
		context.fillStyle = "red";
		context.fillRect(0, 0, 120, 120);
		context.drawImage(img, 0, 0, 120, 120);
	};/*
	img.onerror = function() {
		euid.style.display = "none";
		delete player._register[euid.id];
	};*/
	img.src = image;
}
function albumart(uid, data) {
	player.register(uid, data);
	var euid = document.getElementById(uid);
	var key = "4:albumart:" + data.metadata.title + data.metadata.artist;
	if("localStorage" in window && key in window.localStorage) {
		loadart(euid, window.localStorage[key]);
		return;
	}
	jQuery.getJSON(
		"<?php echo URL_PREFIX; ?>api/albumart?artist=" + escape(data.metadata.artist) + (data.metadata.album?("&album=" + escape(data.metadata.album)):("&title=" + escape(data.metadata.title))) + "&size=extralarge",
		function(aadata) {
			if(aadata.error)
				return loadart(euid, "/images/noart.jpg");
			if("localStorage" in window) {
				window.localStorage[key] = aadata.image;
			}
			loadart(euid, aadata.image);
		}
	);
}

-->
</script>
<div class="clear">&nbsp;</div>

<div class="g7">
	<div class="homeheaders whatistinytape">
		<h2>What is Tinytape?</h2>
		<p>tinytape turns casual listening into a game. earn points by listening. flaunt your exceptional taste by earning badges for creative playlists.</p>
	</div>
	<div class="homeheaders discovermusic">
		<h2>Discover Music</h2>
		<p>stop listening to the same old crap. tinytape gets you off your proverbial ass and into the YMCA <small>(bah dum chh)</small> of music discovery.</p>
	</div>
	<div class="homeheaders kickass">
		<h2>Kick Ass</h2>
		<p>make playlists out of the thousands of songs that are accessible with tinytape. get access to remixes, acoustic recordings, and <a href="<?php echo URL_PREFIX; ?>asian_baby.html" class="simplebox">adorable asian toddlers</a>.</p>
	</div>
</div>
<div class="g5">
	<section id="homesearch">
		<h1 class="homeheader search">Search</h1>
		<form method="get" action="<?php echo URL_PREFIX; ?>search">
			<input class="searchbox" type="text" value="Find the song stuck in your head" style="color:#dadada;" name="q" onfocus="if(this.value==this.defaultValue){this.value='';this.style.color='#000';}" onblur="if(this.value==''){this.value=this.defaultValue;this.style.color='#dadada';}" />
			<input class="submit" type="submit" value="Search" />
			<span>Artist, title, or album</span>
		</form>
	</section>
	
	<a id="home_leaderboards" href="<?php echo URL_PREFIX; ?>leaderboards">Leaderboards</a>
</div>
