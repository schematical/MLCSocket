#!/php -q
<?php  /*  >php -q server.php  */

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();


function randomColor(){
    mt_srand((double)microtime()*1000000);
    $c = '';
    while(strlen($c)<6){
        $c .= sprintf("%02X", mt_rand(0, 255));
    }
    return '#'.$c;
}


/**
 * callback function 
 * @param WebSocketUser $user Current user
 * @param string $msg Data from user sent
 * @param WebSocketServer $server Server object
 */
function process($user, $msg, $server){
    
    // every websocket user can have mixed data (like position or color)
    $user->data['position'] = $msg;
    
    $return = array();
    foreach($server->getUsers() as $user){
        if (! isset($user->data['color'])) {
            $user->data['color'] = randomColor();
            $user->data['ip'] = $user->ip;        
        }
        $return[$user->id] = $user->data;
    }
    
    // send the data to all current users
    foreach($server->getUsers() as $user){
        $server->send($user->socket, json_encode($return));
    }
}

require_once 'WebSocketServer.php';
// new WebSocketServer( socket address, socket port, callback function )
$webSocket = new WebSocketServer("84.38.67.247", 8080, 'process');
$webSocket->run();

