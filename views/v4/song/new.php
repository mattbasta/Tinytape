<div class="g2">&nbsp;</div>
<form action="<?php echo URL_PREFIX; ?>song/create" method="post" class="g8 addsong">
	<?php
	$form = getLib('form');
	
	if(!empty($_REQUEST['error'])) {
		?>
		<div class="warning_box">
			<strong>Uh-oh!</strong>
			<?php
			$errors = explode('-', $_REQUEST['error']);
			foreach($errors as $error) {
				if(empty($error))
					continue;
				switch($error) {
					case 'missing':
						?><p class="formerror">You must specify both an Artist and a Song Name.</p><?php
						break;
					case 'token':
						?><p class="formerror">You took waaayyyyy to long to fill out that out. Do it again. And faster this time...we don't have all day, you know.</p><?php
						break;
				}
			}
			?>
			<p>Nice try there, Sparky, but you're going to have to do it again. And don't suck so much this time.</p>
		</div>
		<?php
	}
	
	?>
	<div>
		<p><strong>How this works:</strong> When you search for a song, you're seeing a list of songs that other folks have added to Tinytape. This is the page to add a new song to that list. Why? It helps us to find the best quality version of the song for you, and lets us swap in a new copy if your tape uses one that disappears. Pretty cool, eh?</p>
	</div>
	<div class="clear"></div>
	<section class="g4 gfirst">
		<label>Artist:</label>
		<input type="text" name="artist" id="artist" />
	</section>
	<section class="g4 glast">
		<label>Song Name:</label>
		<input type="text" name="track" id="track" />
	</section>
	<div class="clear"></div>
	<p class="albumslot inliner">
		<label>Album:</label>
		<input type="text" name="album" id="album" /><br />
		<small>(optional)</small>
	</p>
	<div class="clear"></div>
	<?php
	$ts = time();
	$lid = sha1(uniqid());
	$token = sha1($lid . $ts . '9ZLPSOFIT18KT4VF');
	
	echo $form->hidden('timestamp', $ts);
	echo $form->hidden('id', $lid);
	echo $form->hidden('token', $token);
	
	?><p class="buttons gfirst glast g8" id="addbuttons"><?php
		echo $form->submit('Save Song');
	?></p>
	
	<script type="text/javascript" defer>
	<!--
	$(document).ready(function(){
		$("#artist").autocomplete(
			"<?php echo URL_PREFIX; ?>api/autocomplete/artist/",
			{
				minChars: 1,
				extraParams: {
					track: function() {return document.getElementById("track").value;}
				}
			}
		);
		$("#track").autocomplete(
			"<?php echo URL_PREFIX; ?>api/autocomplete/track/",
			{
				minChars: 2,
				extraParams: {
					artist: function() {return document.getElementById("artist").value;}
				},
				formatItem : function(data, pos, num, term) {
					if(typeof data == "undefined" || data == "")
						return;
					if(typeof data == "object")
						data = data[0];
					var item = JSON.parse(data);
					return item.title + " by " + item.artist;
				}
			}
		).result(function(event, data, formated) {
			if(typeof data == "undefined")
				return;
			if(typeof data == "object")
				data = data[0];
			var item = JSON.parse(data);
			var artist = document.getElementById("artist");
			//var album = document.getElementById("album");
			if(artist.value == '') {
				artist.value = item.artist;
			} else {
				var stripped = artist.value.replace(/[\s\c\-_\.]/,"").toLowerCase();
				var name = item.artist.replace(/[\s-_\.]/,"").toLowerCase();
				if(name == stripped || confirm("We're going to go replace '" + artist.value + "' with '" + item.artist + "'. Ok?"))
					artist.value = item.artist;
			}
			//album.value = item.album;
			this.value = item.title;
		});
	});
	-->
	</script>
	
</form>