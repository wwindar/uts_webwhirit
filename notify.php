<?php
// notify.php – helper to create a notification and optionally push via WebSocket

if (!function_exists('createNotification')) {
    function createNotification(int $userId, string $type, array $payload = []) {
        global $conn;
        $message     = $payload['message'] ?? '';
        $payloadJson = json_encode($payload);
        $stmt = $conn->prepare("INSERT INTO notifikasi (user_id, type, payload, pesan) VALUES (?, ?, ?, ?)");
        if ($stmt === false) {
            error_log('Failed to prepare notification insert: ' . $conn->error);
            return;
        }
        $stmt->bind_param('isss', $userId, $type, $payloadJson, $message);
        $stmt->execute();
        $stmt->close();

        // Optional: push via WebSocket
        if (class_exists('NotificationWebSocket') && method_exists('NotificationWebSocket', 'push')) {
            $data = ['id' => $conn->insert_id, 'type' => $type, 'pesan' => $message, 'payload' => $payload, 'user_id' => $userId];
            NotificationWebSocket::push($userId, $data);
        }

        // Kirim email notifikasi (jika user punya email)
        $rUser = $conn->prepare("SELECT email, username FROM users WHERE id = ?");
        if ($rUser) {
            $rUser->bind_param('i', $userId);
            $rUser->execute();
            $rowUser = $rUser->get_result()->fetch_assoc();
            $rUser->close();

            if (!empty($rowUser['email'])) {
                require_once __DIR__ . '/mailer.php';
                // Buat link ke halaman terkait
                $baseUrl = 'https://wwindar.infinityfreeapp.com/uts_webwhirit';
                $linkUrl = '';
                if (!empty($payload['resensi_id'])) {
                    $linkUrl = "$baseUrl/detail.php?id=" . intval($payload['resensi_id']);
                } elseif ($type === 'follow') {
                    $linkUrl = "$baseUrl/profil.php";
                }
                // Bersihkan HTML dari pesan agar terbaca dengan baik
                $pesanBersih = strip_tags($message);
                kirimEmailNotifikasi($rowUser['email'], $rowUser['username'], $type, $pesanBersih, $linkUrl);
            }
        }
    }
}
?>
