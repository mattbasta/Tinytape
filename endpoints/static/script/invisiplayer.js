function onytplayerStateChange(x) {player.feed(x);}
var player = {
	mp : null,
	playing : '',
	state : 'stopped',
	ldata : {
		bytes:-1,
		loaded:-1,
		duration:-1
	},
	interv : null,
	_register : {},
	register : function(reg, song) {
		player._register[reg] = song;
	},
	play : function() {player.mp.playVideo();},
	pause : function() {player.mp.pauseVideo();},
	stop : function() {player.mp.stopVideo();},
	feed : function(x) {
		switch(x) {
			case 0:
				player.startnext();
			case -1:
				player.state = 'stopped';
				break;
			case 1:
				player.state = 'play';
				break;
			case 2:
				player.state = 'pause';
				break;
			case 3: // Buffering
			default:
				
		}
		player.updateplaying();
	},
	updateplaying : function() {
		var el = $(document.getElementById(player.playing));
		if(player.state == 'play' || player.state == 'pause') {
			el.addClass('playing');
		} else {
			el.removeClass('playing');
		}
	},
	startnext : function() {
		var next = false;
		for(var a in player._register) {
			if(next) {
				player.start(a);
				return;
			}
			if(a == player.playing)
				next = true;
		}
		// End of the line
		$(document.getElementById(player.playing)).removeClass("playing");
	},
	start : function(id) {
		
		if(player.playing == id) {
			if(player.state == 'play')
				player.pause();
			else
				player.play();
			return;
		}else if(player.playing != '')
			player.stop();
		
		var reg = player._register[id];
		
		player.doreg(reg);
		if(player.playing != '')
			$(document.getElementById(player.playing)).removeClass("playing");
		player.playing = id;
		
	},
	doreg : function(reg) {
		
		if(!!reg.error) {
			alert(reg.error);
			player.startnext();
			return;
		}
		
		var service = reg.service;
		var resource = reg.resource;
		switch(service) {
			case 'youtube':
				var params = { allowScriptAccess: "always" };
				var atts = { id: "invisiplayer" };
				swfobject.embedSWF(
					"http://www.youtube.com/v/" + resource + "?enablejsapi=1&playerapiid=invisiplayer&autoplay=1&",
					"invisiplayer",
					"100", "100",
					"8", null, null, params, atts
				);
				this.mp = document.getElementById("invisiplayer");
				
				break;
			case 'tinytape':
				
				$.getJSON(
					"/song/" + resource.id + "?" + ((!!resource.instance && resource.instance != 0)?"instance="+resource.instance:"best"),
					function(data) {
						player.doreg(data);
					}
				);
				
				break;
		}
	},
	onstart : function() {},
	register_resource : function(reg) {this.register[this.register.length] = reg;}
};

function onYouTubePlayerReady(playerId) {
	ytplayer = document.getElementById("invisiplayer");
	ytplayer.addEventListener("onStateChange", "onytplayerStateChange");
	ytplayer.setVolume(100);
	
	player.onstart();
	
	var reg = player._register[player.playing];
	if("metadata" in reg) {
		var notification_data = formatNotification(reg.metadata.title, reg.metadata.artist);
		var notification = stageNotification(notification_data);
		showNotification(notification);
	}
	
	player.ldata.bytes = -1;
	player.ldata.loaded = -1;
	player.ldata.duration = -1;
	
	if(!player.interv)
		player.interv = setInterval(
			function() {
				var m = player.mp;
				
				if(typeof m.getVideoBytesLoaded == "undefined")
					return;
				
				if(player.ldata.bytes == -1)
					player.ldata.bytes = m.getVideoBytesTotal();
				if(player.ldata.loaded < player.ldata.bytes) {
					var hardware = document.getElementById('hardware_' + player.playing);
					player.ldata.loaded = m.getVideoBytesLoaded();
					hardware.style.width = Math.round(player.ldata.loaded / player.ldata.bytes * 100) + '%';
				}
				
				if(player.state == 'play') {
					if(player.ldata.duration == -1)
						player.ldata.duration = m.getDuration();
					var playbar = document.getElementById('playbar_' + player.playing);
					playbar.style.width = (m.getCurrentTime() / player.ldata.duration * 100) + '%';
				}
				
			}, 200
		);
	
}
function onytplayerStateChange(newState) {
	player.feed(newState);
}

function is_favorite(uid) {
	$('#' + uid).css('background-color','gold');
	$('#' + uid + ' .favorite').html("Unfavorite");
}
function un_favorite(uid) {
	$('#' + uid).css('background-color','white');
	$('#' + uid + ' .favorite').html("Favorite");
}

// Oh boy! Here's the notifications stuff for compatible browsers.

// Our fallback stub.
var showNotification = function(element, timeout) {return;};
var hideNotification = function() {};

var stageNotification = function(html) {
	if(typeof html == 'object')
		return html;
	var id = Math.round(Math.random() * 89999 + 10000) + "_stage";
	$("#notificationstage").html('<div id="' + id + '">' + html + '</div>');
	return id;
}
var onWebkitNotifications = false;
var formatNotification = function(title, artist) {
	if(onWebkitNotifications) {
		return {
			"title": title,
			"artist": artist
		};
	} else {
		return '<div class="songinfo"><span class="title">' + title + '</span><br /><span class="artist">' + artist + '</span></div><div class="clear"></div>';
	}
}

var showInlineNotification = function(element, timeout) {
	if(!timeout)
		timeout = 5;
	var wrapper = document.createElement("div");
	wrapper.className = "notification";
	
	var el = document.getElementById(element);
	el.parentNode.removeChild(el);
	wrapper.appendChild(el);
	
	var closer = document.createElement("a");
	closer.className = "closenotification";
	closer.href = "#";
	closer.onclick = function(){
		clearTimeout(this.timer);
		var parent = $(closer.parentNode);
		parent.fadeOut(500, function() {
			parent.remove();
		});
	};
	closer.timer = setTimeout(closer.onclick, timeout * 1000);
	wrapper.appendChild(closer);
	document.getElementById('notifications').appendChild(wrapper);
	$(wrapper).fadeIn(500);
};
var hideInlineNotification = function() {
	document.getElementById('notifications').innerHTML = '';
};

showNotification = showInlineNotification;
hideNotification = hideInlineNotification;

window.webkitpermission = function() {
	window.webkitNotifications.requestPermission(function() {
		if(window.webkitNotifications.checkPermission() == 0) {
			hideInlineNotification();
			window.setup_notifications();
		}
	});
};

window.setup_notifications = function() {
	if("webkitNotifications" in window) {
		if(window.webkitNotifications.checkPermission() != 0) {
			var notification = stageNotification('<p>To show song information on your desktop, you need to <a href="javascript:window.webkitpermission();">grant us permission</a>. Don&apos;t worry, we won&apos;t douche things up.</p>');
			showNotification(notification, 60);
		} else {
			var showWebkitNotification = function(element, timeout) {
				if(!timeout)
					timeout = 5;
				console.log(element);
				if(!onWebkitNotifications) {
					showInlineNotification(element, timeout);
				} else {
					var icon = 'http://tinytape.com/favicon.ico';
					var key = "1:albumart:" + element.title + element.artist + "large";
					if(key in window.localStorage)
						icon = window.localStorage[key];
					var notification = window.webkitNotifications.createNotification(icon, element.title, "by " + element.artist);
					notification.show();
					setTimeout(function() {
						notification.cancel();
					}, timeout * 1000);
					window.notification = notification;
				}
			};
			var hideWebkitNotification = function() {
				if("notification" in window)
					window.notification.cancel();
				hideInlineNotification();
			};
			$(window).blur(function() {
				showNotification = showWebkitNotification;
				hideNotification = hideWebkitNotification;
				onWebkitNotifications = true;
			}).focus(function() {
				showNotification = showInlineNotification;
				hideNotification = hideInlineNotification;
				onWebkitNotifications = false;
			});
		}
	}
	
	for(var reg in player._register) {
		// Preload album art
	}
	
};
$(document).ready(window.setup_notifications); 