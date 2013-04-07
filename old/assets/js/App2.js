var socket;
 
function init() {
	var host = "ws://84.38.67.247:8080/dev/websocket/server.php";
	try {
		socket = new WebSocket(host);
		socket.onopen = function(msg){ };
		socket.onmessage = function(msg){
			eval('var data = ' + msg.data + ';');
			for (userId in data) {
				if (data[userId].position) {
					var pos = data[userId].position.split(',');
					var color = data[userId].color;
					render(userId, pos[0], pos[1], color);
				}
			}
			dump(data);
		};
		socket.onclose = function(msg){ };
	} catch(ex){ console.log(ex); }
 
	$('body').bind('mousemove', function(evt){
		send(evt.clientX, evt.clientY);
	});
}
 
function render(u, x, y, c) {
	if ($('#'+u).length == 0) {
		$('
 
').appendTo('body');
	}
	$('#'+u).css('left', x+'px');
	$('#'+u).css('top', y+'px');
	$('#'+u).css('background', c);
}
 
function send(x,y) {
	var msg = x + ',' + y;
	socket.send(msg);
}