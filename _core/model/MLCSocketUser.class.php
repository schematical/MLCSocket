<?php
class MLCSocketUser {
	protected $intId = null;
	protected $objSocket = null;
	protected $strHandshake = null;
	//Going to need to put other data here
	
	public function __get($strName){
		switch($strName){
			case('Id'):
				return $this->intId;
			case('Socket'):
				return $this->objSocket;
			case('Handshake'):
				return $this->strHandshake;
			default:
				throw new Exception(__CLASS__ . ' does not have a property"' . $strName . '"');
			
		}
	}
	public function __set($strName, $mixValue){
		switch($strName){
			case('Id'):
				return $this->intId = $mixValue;
			case('Socket'):
				return $this->objSocket = $mixValue;
			case('Handshake'):
				return $this->strHandshake = $mixValue;
			default:
				throw new Exception(__CLASS__ . ' does not have a property"' . $strName . '"');
			
		}
	}
}