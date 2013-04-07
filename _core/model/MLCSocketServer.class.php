<?php

// Usage: $master=new WebSocket("localhost",12345);

class MLCSocketServer {
	protected $resMaster = null;
	protected $arrSockets = array();
	protected $arrUsers = array();
	protected $debug = false;

	public function __construct($strAddress, $intPort) {
		error_reporting(E_ALL);
		set_time_limit(0);
		ob_implicit_flush();

		$this -> resMaster = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("socket_create() failed");
		socket_set_option($this -> resMaster, SOL_SOCKET, SO_REUSEADDR, 1) or die("socket_option() failed");
		socket_bind($this -> resMaster, $strAddress, $intPort) or die("socket_bind() failed");
		socket_listen($this -> resMaster, 20) or die("socket_listen() failed");
		$this -> arrSockets[] = $this -> resMaster;
		$this -> Output("Server Started : " . date('Y-m-d H:i:s'));
		$this -> Output("Listening on   : " . $strAddress . " port " . $intPort);
		$this -> Output("Master socket  : " . $this -> resMaster . "\n");

		while (true) {
			$arrChanged = $this -> arrSockets;
			$resWrite = NULL;
			$resExcept = NULL;
			socket_select($arrChanged, $resWrite, $resExcept, NULL);
			foreach ($arrChanged as $resSocket) {
				if ($resSocket == $this -> resMaster) {
					$resClient = socket_accept($this -> resMaster);
					if ($resClient < 0) {
						 $this ->Log("socket_accept() failed");
						continue;
					} else {
						 $this -> Connect($resClient);
					}
				} else {
					$intBytes = @socket_recv($resSocket, $strBuffer, 2048, 0);
					
					if ($intBytes == 0) {
						 $this -> Disconnect($resSocket);
					} else {
						$objUser = $this -> GetUserBySocket($resSocket);
						if (!$objUser -> Handshake) {
							 $this -> DoHandShake($objUser, $strBuffer);
						} else {							
							 $this -> Process($objUser, $this -> Unwrap($strBuffer));
						}
					}
				}
			}
		}
	}

	public function Process($objUser, $strData) {
		
		//$this->Log("Data: " . $strData);
		$this -> SendAll($strData);
	}
	public function SendAll($strData){
	  	foreach($this->arrUsers as $intIndex => $objUser){
	  		$this->Log("User:" . $objUser->Id);
	  		$this->Send($objUser->Socket, $strData);
	  	}
	}
	public function Send($objClient,$strData){
		$strData = $strData;
		
    	//$this->Output("> ". $strData);


	    $strHeader = " ";
	    $strHeader[0] = chr(0x81);
	    $intHeaderLength = 1;
    
    //Payload length:  7 bits, 7+16 bits, or 7+64 bits
    $intDataLength = strlen($strData);
    
    //The length of the payload data, in bytes: if 0-125, that is the payload length.  
    if($intDataLength <= 125) {
      $strHeader[1] = chr($intDataLength);
      $intHeaderLength = 2;
    }elseif ($intDataLength <= 65535){
      // If 126, the following 2 bytes interpreted as a 16
      // bit unsigned integer are the payload length. 
    
      $strHeader[1] = chr(126);
    	$strHeader[2] = chr($intDataLength >> 8);
		  $strHeader[3] = chr($intDataLength & 0xFF);
		  $intHeaderLength = 4;
    }else{
      // If 127, the following 8 bytes interpreted as a 64-bit unsigned integer (the 
      // most significant bit MUST be 0) are the payload length. 
      $strHeader[1] = chr(127);
      $strHeader[2] = chr(($intDataLength & 0xFF00000000000000) >> 56);
      $strHeader[3] = chr(($intDataLength & 0xFF000000000000) >> 48);
      $strHeader[4] = chr(($intDataLength & 0xFF0000000000) >> 40);
      $strHeader[5] = chr(($intDataLength & 0xFF00000000) >> 32);
      $strHeader[6] = chr(($intDataLength & 0xFF000000) >> 24);
      $strHeader[7] = chr(($intDataLength & 0xFF0000) >> 16);
      $strHeader[8] = chr(($intDataLength & 0xFF00 ) >> 8);
      $strHeader[9] = chr( $intDataLength & 0xFF );
      $intHeaderLength = 10;
    }
   
    $resResult = socket_write($objClient, $strHeader . $strData, strlen($strData) + $intHeaderLength);
 
   if ( !$resResult ) {
         $this->Disconnect($objClient);
         $objClient = false;
    }
    $this->Output("len(".strlen($strData).")");
  }

	public function Connect($resSocket) {
		$objUser = new MLCSocketUser();
		$objUser -> Id = uniqid();
		$objUser -> Socket = $resSocket;
		array_push($this -> arrUsers, $objUser);
		array_push($this -> arrSockets, $resSocket);
		$this -> log($resSocket . " CONNECTED!");
		$this -> log(date("d/n/Y ") . "at " . date("H:i:s T"));
	}

	public function Disconnect($resSocket) {
		$intDisUserIndex = null;
		$n = count($this -> arrUsers);
		for ($i = 0; $i < $n; $i++) {
			if ($this -> arrUsers[$i] -> Socket == $resSocket) {
				 $intDisUserIndex = $i;
				break;
			}
		}
		if (!is_null($intDisUserIndex)) {
			 array_splice($this -> arrUsers, $intDisUserIndex, 1);
		}
		$intIndex = array_search($resSocket, $this -> arrSockets);
		socket_close($resSocket);
		$this->Log($resSocket . " DISCONNECTED!");
		if ($intIndex >= 0) {
			 array_splice($this -> arrSockets, $intIndex, 1);
		}
	}

	public function DoHandShake($objUser, $strBuffer){
	    $this->Log("\nRequesting handshake...");
	    $this->Log($strBuffer);
	    list($resource,$host,$origin,$key1,$key2,$l8b,$key0) = $this->GetHeaders($strBuffer);
	    $this->Log("Handshaking...");
	    //$port = explode(":",$host);
	    //$port = $port[1];
	    //$this->log($origin."\r\n".$host);
	    $strUpgrade  = "HTTP/1.1 101 WebSocket Protocol Handshake\r\n" .
	                "Upgrade: WebSocket\r\n" .
	                "Connection: Upgrade\r\n" .
	                "Sec-WebSocket-Origin: " . $origin . "\r\n" .
	                "Sec-WebSocket-Accept: " .  $this->CalcKeyHybi10($key0) . "\r\n" . "\r\n" ;
	
	    socket_write($objUser->Socket,$strUpgrade,strlen($strUpgrade));
	    $objUser->Handshake=true;
	    $this->log($strUpgrade);
	    $this->log("Done handshaking...");
	    return true;
	}

  public function CalcKeyHybi10($key){
     $CRAZY = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
     $sha = sha1($key.$CRAZY,true);
     return base64_encode($sha);
  }
  
  public function GetHeaders($req){
    $r=$h=$o=null;
    if(preg_match("/GET (.*) HTTP/"               ,$req,$match)){ $r=$match[1]; }
    if(preg_match("/Host: (.*)\r\n/"              ,$req,$match)){ $h=$match[1]; }
    if(preg_match("/Origin: (.*)\r\n/"            ,$req,$match)){ $o=$match[1]; }
    if(preg_match("/Sec-WebSocket-Key1: (.*)\r\n/",$req,$match)){ $this->log("Sec Key1: ".$sk1=$match[1]); }
    if(preg_match("/Sec-WebSocket-Key2: (.*)\r\n/",$req,$match)){ $this->log("Sec Key2: ".$sk2=$match[1]); }
    if(preg_match("/Sec-WebSocket-Key: (.*)\r\n/" ,$req,$match)){ $this->log("new Sec Key2: ".$sk0=$match[1]); }
    if($match=substr($req,-8)) { $this->log("Last 8 bytes: ".$l8b=$match); }
    return array($r,$h,$o,$sk1,$sk2,$l8b,$sk0);
  }

	public function GetUserBySocket($objSocket) {
		$objReturnUser = null;
		foreach ($this->arrUsers as $objUser) {
			if ($objUser -> Socket == $objSocket) {
				$objReturnUser = $objUser;
				break;
			}
		}
		return $objReturnUser;
	}

	public function Output($strData = "") {
		echo $strData . "\n";
	}

	public function Log($strData = "") {
		error_log($strData);
	}

	public function Wrap($strData = "") {
		return chr(0) . $strData . chr(255);
	}
	public function Unwrap($strData=""){		
	  	$strBytes = $strData;
	  	$intLenght = '';
	  	$strMask = '';
	  	$strCodedData = '';
	  	$strDecoded = '';
	  	$strSecond = sprintf('%08b', ord($strBytes[1]));		
	  	$blnMasked = ($strSecond[0] == '1') ? true : false;		
	  	$intLenght = ($blnMasked === true) ? ord($strBytes[1]) & 127 : ord($strBytes[1]);
	  	if($blnMasked === true)	{
	  		if($intLenght === 126){
	  		   $strMask = substr($strBytes, 4, 4);
	  		   $strCodedData = substr($strBytes, 8);
	  		}elseif($intLenght === 127){
	  			$strMask = substr($strBytes, 10, 4);
	  			$strCodedData = substr($strBytes, 14);
	  		}else{
	  			$strMask = substr($strBytes, 2, 4);		
	  			$strCodedData = substr($strBytes, 6);		
	  		}	
	  		for($i = 0; $i < strlen($strCodedData); $i++){		
	  			$strDecoded .= $strCodedData[$i] ^ $strMask[$i % 4];
	  		}
	  	}else{
	  		if($intLenght === 126){		   
	  		   $strDecoded = substr($strBytes, 4);
	  		}elseif($intLenght === 127){			
	  			$strDecoded = substr($strBytes, 10);
	  		}else{				
	  			$strDecoded = substr($strBytes, 2);		
	  		}		
	  	}
	 	//$this->Log("Unwraped:" . $strDecoded);
		return $strDecoded;
	  }

}
?>