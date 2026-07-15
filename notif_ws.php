<?php
// notif_ws.php – WebSocket server component for real-time notifications
// Requires Ratchet library (install via Composer: composer require cboden/ratchet)

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;

class NotificationWebSocket implements MessageComponentInterface {
    // Map of user_id => SplObjectStorage of connections
    private static $userClients = [];

    // Called when a new client connects
    public function onOpen(ConnectionInterface $conn) {
        // Expect query string like ?uid=123
        $query = $conn->httpRequest->getUri()->getQuery();
        parse_str($query, $params);
        $uid = isset($params['uid']) ? intval($params['uid']) : 0;
        if ($uid > 0) {
            if (!isset(self::$userClients[$uid])) {
                self::$userClients[$uid] = new SplObjectStorage();
            }
            self::$userClients[$uid]->attach($conn);
            $conn->userId = $uid; // store for later reference
        }
    }

    // Not used: incoming messages from client
    public function onMessage(ConnectionInterface $from, $msg) {
        // No action needed; server pushes only
    }

    // Called when a client disconnects
    public function onClose(ConnectionInterface $conn) {
        $uid = $conn->userId ?? 0;
        if ($uid && isset(self::$userClients[$uid])) {
            self::$userClients[$uid]->detach($conn);
            if (self::$userClients[$uid]->count() === 0) {
                unset(self::$userClients[$uid]);
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }

    // Push a notification to all connections of a specific user
    public static function push(int $userId, array $data) {
        if (!isset(self::$userClients[$userId])) {
            return;
        }
        $payload = json_encode(['type' => 'notification', 'data' => $data]);
        foreach (self::$userClients[$userId] as $client) {
            $client->send($payload);
        }
    }
}
?>
