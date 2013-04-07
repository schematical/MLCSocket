<?php
abstract class MLCSocketDriver{
	protected static $arrServers = array();
	public static function Run($strHost, $intPort){
		$intCount = count(self::$arrServers);
		$objServer = new MLCSocketServer(
			$strHost,
			$intPort
		);
	
		self::$arrServers[$intCount] = $objServer;
		return self::$arrServers[$intCount];
	}
}
