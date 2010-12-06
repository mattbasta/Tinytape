function onytplayerStateChange(x) {player.feed(x);}
var player = {
	mp : null,
	playing : '',
	state : 'stopped',
	titleBefore : document.title,
	ldata : {
		bytes:-1,
		loaded:-1,
		duration:-1
	},
	interv : null,
	repeat:false,
	_register : {},
	_fav_register : {},
	register : function(reg, song) {
		player._register[reg] = song;
	},
	register_all : function(data, append) {
		if(!append) {
			player._register = {};
			player._fav_register = {};
		}
		var favs = [];
		for(var d in data) {
			player.register(d, data[d]);
			if(data[d].service == "tinytape" && !("nofav" in data[d] && data[d]["nofav"])) {
				var fav = data[d].resource.id + (!!data[d].resource.instance?"_"+data[d].resource.instance:"");
				player._fav_register[d] = fav;
				favs[favs.length] = fav;
			}
		}
		if(player._fav_register) {
			$.getJSON("/v4/api/favorites/determine", {favorites: favs.join(",")}, function(data) {
				for(var f in data) {
					var fav = data[f];
					for(var uid in player._fav_register) {
						var freg = player._fav_register[uid];
						if(freg != fav)
							continue;
						is_favorite(uid);
					}
				}
			});
		}
	},
	play : function() {if(player.mp){player.mp.playVideo();}},
	pause : function() {if(player.mp){player.mp.pauseVideo();}},
	stop : function() {
		if(player.mp){player.mp.stopVideo();}
		document.title = player.titleBefore;
	},
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
		if(player.repeat) {
			if(player.mp) {
				player.stop();
				player.mp.seekTo(0, true);
			}
			return;
		}
		var next = false;
		for(var a in player._register) {
			if(next) {
				if(typeof doplay != "undefined") {
					return doplay(a);
				}
				player.start(a);
				return;
			}
			if(a == player.playing)
				next = true;
		}
		// End of the line
		$(document.getElementById(player.playing)).removeClass("playing");
	},
	startprevious : function() {
		var last = "";
		for(var a in player._register) {
			if(a == player.playing) {
				if(last == "")
					return;
				else {
					if(typeof doplay != "undefined") {
						return doplay(last);
					}
					player.start(last);
					return;
				}
			}
			last = a;
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
		if(player.playing != '') {
			var cplay = document.getElementById(player.playing);
			if(cplay)
				$(cplay).removeClass("playing");
		}
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
				
				var is_instance = !!resource.instance && resource.instance != 0;
				var url = "/v4/song/";
				if(is_instance) {
					url += "instance/" + resource.id + "/" + resource.instance;
				} else {
					url += "best/" + resource.id;
				}
				
				$.getJSON(
					url,
					function(data) {
						player.doreg(data);
						if(data.badge)
							badge(data.badge);
						if("points" in data && data.points)
							points(data.points);
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
	
	var reg = player._register[player.playing];
	if("metadata" in reg) {
		// Update the page title
		document.title = reg.metadata.title + " by " + reg.metadata.artist + " - Tinytape";
		
		var notification_data = formatNotification(reg.metadata.title, reg.metadata.artist);
		var notification = stageNotification(notification_data);
		showNotification(notification);
	}
	
	player.ldata.bytes = -1;
	player.ldata.loaded = -1;
	player.ldata.duration = -1;
	
	if(player.interv)
		clearInterval(player.interv);
	var hardware = document.getElementById('hardware_' + player.playing);
	var playbar = document.getElementById('playbar_' + player.playing);
	if(!hardware) {
		hardware = document.getElementById('hardware_global');
		playbar = document.getElementById('playbar_global');
	}
	player.interv = setInterval(
		function() {
			var m = player.mp;
			
			if(typeof m.getVideoBytesLoaded == "undefined")
				return;
			
			if(player.ldata.bytes == -1)
				player.ldata.bytes = m.getVideoBytesTotal();
			if(player.ldata.loaded < player.ldata.bytes) {
				
				player.ldata.loaded = m.getVideoBytesLoaded();
				hardware.style.width = Math.round(player.ldata.loaded / player.ldata.bytes * 100) + '%';
			}
			
			if(player.state == 'play') {
				if(player.ldata.duration == -1)
					player.ldata.duration = m.getDuration();
				playbar.style.width = (m.getCurrentTime() / player.ldata.duration * 100) + '%';
			}
			
		}, 200
	);
	
	player.onstart();
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
function badge(bdata) {
	var earned = ("earned_string" in bdata) ? bdata.earned_string : bdata.description;
	$.fancybox(
		'<p class="f_c">Congratulations, you\'ve earned yourself the <b>' + bdata.title + '</b>. ' + earned + '</p>' +
		'<p class="f_c"><a href="javascript:$.fancybox.close();">Ok, whatever</a></p>' +
		'<img class="badge_large" src="/images/badges/' + bdata.image + '.jpg" alt="' + bdata.title + '" />',
		{
			autoDimensions:false,
			width:300,
			height:420
		}
	);
}
function points(pdata) {
	var p = $(".stat.points strong");
	p.fadeOut(function(){p.html(pdata).fadeIn();});
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
					var icon = "/v4/api/albumart/redirect?title=" + escape(element.title) + "&artist=" + escape(element.artist) + "&size=medium";
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
	
};
$(document).ready(function() {
	window.setup_notifications();
});
function fbready() {
	if(FB._userStatus == "connected")
		$("#my_facebook_nag").hide();
}