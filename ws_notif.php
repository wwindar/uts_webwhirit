<?php
// ws_notif.php – Simple Ratchet WebSocket server for real‑time notifications
// Run this script on the server (e.g., `php ws_notif.php`), it will listen on port 8080.
// It expects the NotificationWebSocket class (defined in notif_ws.php) to manage connections.

require __DIR__ . '/notif_ws.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$port = 8080;
$wsServer = new WsServer(new NotificationWebSocket());
$http = new HttpServer($wsServer);

$server = IoServer::factory($http, $port);

echo "WebSocket server started on ws://0.0.0.0:$port\n";
$server->run();
?>
