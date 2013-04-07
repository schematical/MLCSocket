<?php
define('__MLC_SOCKET__', dirname(__FILE__));
define('__MLC_SOCKET_CORE__', __MLC_SOCKET__ . '/_core');
define('__MLC_SOCKET_CORE_MODEL_', __MLC_SOCKET_CORE__ . '/model');

$arrMLCClasses = array();
$arrMLCClasses['MLCSocketDriver'] = __MLC_SOCKET_CORE__ . '/MLCSocketDriver.class.php';
$arrMLCClasses['MLCSocketUser'] = __MLC_SOCKET_CORE_MODEL_ . '/MLCSocketUser.class.php';
$arrMLCClasses['MLCSocketServer'] = __MLC_SOCKET_CORE_MODEL_ . '/MLCSocketServer.class.php';

if(!class_exists('MLCApplicationBase')){
	foreach($arrMLCClasses as $strClass => $strPath){
		require_once($strPath);
	}
}
