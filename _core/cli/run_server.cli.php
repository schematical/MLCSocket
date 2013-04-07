#!/php -q
<?php  
// Run from command prompt > php -q websocket.demo.php

// Basic WebSocket demo echoes msg back to client
require_once(dirname(__FILE__) . '/../../package.inc.php');//TEMP HACK
MLCSocketDriver::Run('10.0.1.89', 5033);
