function load_feed(username, type, t, id, offset, onload) {
	if(!id) id = "my_feed";
	if(!offset) offset = 0;
	if(t)
		$("#feedchooser a").removeClass("active");
	$.getJSON(
		"/api/feed/get_feed/" + escape(username) + "/" + type + (offset?"/"+offset:""),
		function(data) {
			$("#" + id).html(data.payload);
			if(data.register)
				player.register_all(data.register, data.append);
			if(onload)
				onload();
		}
	);
	if(t)
		$(t).addClass("active");
}

function clear_history(history) {
	$.get(
		"/api/feed/empty/" + history,
		function(data) {
			load_feed(username, history, "#filter_" + history);
		}
	);
}

$(document).ready(function() {
	$("#feedchooser a").click(function() {
		load_feed(username, $(this).dataset("type"), this);
		return false;
	});
	if(hash = window.location.hash) {
		hash = hash.substr(1);
		if(hash in {fullfeed:1,feed:1,mentions:1,searchhistory:1,history:1, favorites:1}) {
			load_feed(username, hash, "#filter_" + hash);
		}
	} else {
		load_feed(username, default_feed);
	}
	$("#my_shoutbox").elastic();
});

function delete_post(username, type, hash) {
	$.getJSON(
		"/api/feed/delete_item/" + escape(username) + "/" + type + "/" + hash,
		function(data) {
			var h = $("#fi_" + hash);
			if(data.badge)
				badge(data.badge);
			if("deleted" in data) {
				h.slideUp(300, function() {
					h.remove();
				});
			}
		}
	);
	
	return false;
}

function show_more(loader, username, type, id, length) {
	loader = $(loader);
	loader.addClass("pending");
	load_feed(username, type, "", id, length + 1, function() {
		loader.remove();
	});
	return false;
}
