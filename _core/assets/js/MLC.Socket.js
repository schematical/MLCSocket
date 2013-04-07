if(typeof MLC == 'undefined'){
	window.MLC = MLC = {};
}
MLC.Socket = {
	objSettings:null,
	objListener:null,
	Conn:null,
	Init:function(objSettings, objListener){
		if(typeof(objListener) == 'undefined'){
			alert("Must have a listener for this to work");
		}
		MLC.Socket.objListener = objListener;
		MLC.Socket.objSettings = objSettings;
	 	MLC.Socket.Conn = io.connect(MLC.Socket.objSettings.socket_url);
	 	MLC.Socket.Conn.on(
	 		'mlc-handshake',
	 		MLC.Socket.Handshake
	 	);
	},
	Handshake:function(objMessage){
		
		var objData = MLC.Socket.objListener.Handshake(objMessage);
		if(typeof(objData) == 'undefined'){
			objData = {};
		}
		objData.user = MLC.Socket.objSettings.user;
		MLC.Socket.Conn.emit(
			'mlc-handshake-response',
			objData
		);
	}
}