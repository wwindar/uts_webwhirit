<?php
// notify.php – helper to create a notification and optionally push via WebSocket

if (!function_exists('createNotification')) {
    function createNotification(int $userId, string $type, array $payload = []) {
        global $conn;
        // Prepare message for display (fallback if not provided in payload)
        $message = $payload['message'] ?? '';
        // Store type and payload as JSON for flexibility
        $payloadJson = json_encode($payload);
        // Insert into notifikasi table (expects columns: user_id, type, payload, pesan, sudah_dibaca, created_at)
        // If "pesan" column still exists, store $message there for legacy compatibility.
        $stmt = $conn->prepare("INSERT INTO notifikasi (user_id, type, payload, pesan) VALUES (?, ?, ?, ?)");
        if ($stmt === false) {
            error_log('Failed to prepare notification insert: ' . $conn->error);
            return;
        }
        $stmt->bind_param('isss', $userId, $type, $payloadJson, $message);
        $stmt->execute();
        $stmt->close();

        // Optional: push via WebSocket if the server class is available
        if (class_exists('NotificationWebSocket') && method_exists('NotificationWebSocket', 'push')) {
            $data = [
                'id'      => $conn->insert_id,
                'type'    => $type,
                'pesan'   => $message,
                'payload' => $payload,
                'user_id' => $userId,
            ];
            NotificationWebSocket::push($userId, $data);
        }
    }
}
?>
