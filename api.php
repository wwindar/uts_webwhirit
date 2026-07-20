<?php
/**
 * api.php — REST API Sederhana Resensi Buku
 *
 * Endpoint:
 *   GET  api.php?endpoint=resensi              → daftar semua resensi
 *   GET  api.php?endpoint=resensi&id=1         → detail satu resensi
 *   GET  api.php?endpoint=resensi&genre=Romantis → filter per genre
 *   GET  api.php?endpoint=users                → daftar pengguna publik
 *   GET  api.php?endpoint=users&id=1           → profil pengguna
 *   GET  api.php?endpoint=katalog              → katalog dengan search & genre
 *   GET  api.php?endpoint=statistik            → statistik umum aplikasi
 *   POST api.php?endpoint=login                → autentikasi & dapatkan token
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once 'db.php';

// ── Fungsi bantu ─────────────────────────────────────────────────────────────

function respond($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

function error_respond(string $message, int $code = 400): void {
    respond(['status' => 'error', 'message' => $message], $code);
}

// ── Token Auth (sederhana berbasis sesi + API key) ───────────────────────────

function getAuthUser(): ?array {
    global $conn;
    // Cek Authorization header: "Bearer <token>"
    $headers = apache_request_headers();
    $auth    = $headers['Authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if (preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
        $token = trim($m[1]);
        $st    = $conn->prepare("SELECT id, username, role FROM users WHERE api_token = ? LIMIT 1");
        if ($st) {
            $st->bind_param('s', $token);
            $st->execute();
            $row = $st->get_result()->fetch_assoc();
            $st->close();
            return $row ?: null;
        }
    }
    return null;
}

// Pastikan kolom api_token ada (auto-migrate ringan)
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS api_token VARCHAR(64) DEFAULT NULL");

// ── Router ───────────────────────────────────────────────────────────────────

$endpoint = $_GET['endpoint'] ?? '';
$method   = $_SERVER['REQUEST_METHOD'];

// ── POST /api.php?endpoint=login ─────────────────────────────────────────────
if ($endpoint === 'login' && $method === 'POST') {
    $input    = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (!$username || !$password) {
        error_respond('Username dan password wajib diisi.');
    }

    $st = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ? LIMIT 1");
    $st->bind_param('ss', $username, $username);
    $st->execute();
    $user = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$user || !password_verify($password, $user['password'])) {
        error_respond('Username atau password salah.', 401);
    }

    // Generate / refresh token
    $token = bin2hex(random_bytes(32));
    $conn->prepare("UPDATE users SET api_token = ? WHERE id = ?")->execute() === false; // fallback
    $upd = $conn->prepare("UPDATE users SET api_token = ? WHERE id = ?");
    $upd->bind_param('si', $token, $user['id']);
    $upd->execute();
    $upd->close();

    respond([
        'status'  => 'success',
        'message' => 'Login berhasil.',
        'token'   => $token,
        'user'    => [
            'id'       => $user['id'],
            'username' => $user['username'],
            'role'     => $user['role'],
        ],
    ]);
}

// ── GET /api.php?endpoint=statistik ──────────────────────────────────────────
if ($endpoint === 'statistik' && $method === 'GET') {
    $stats = [
        'total_resensi' => (int) $conn->query("SELECT COUNT(*) FROM resensi")->fetch_row()[0],
        'total_users'   => (int) $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0],
        'avg_rating'    => (float) ($conn->query("SELECT ROUND(AVG(rating),2) FROM resensi")->fetch_row()[0] ?? 0),
        'total_genre'   => (int) $conn->query("SELECT COUNT(DISTINCT genre) FROM resensi WHERE genre IS NOT NULL")->fetch_row()[0],
        'top_genre'     => $conn->query("SELECT genre, COUNT(*) as jml FROM resensi GROUP BY genre ORDER BY jml DESC LIMIT 1")->fetch_assoc(),
    ];
    respond(['status' => 'success', 'data' => $stats]);
}

// ── GET /api.php?endpoint=resensi ────────────────────────────────────────────
if ($endpoint === 'resensi' && $method === 'GET') {
    $id    = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';
    $limit = min(intval($_GET['limit'] ?? 20), 100);
    $page  = max(intval($_GET['page'] ?? 1), 1);
    $offset = ($page - 1) * $limit;

    if ($id > 0) {
        // Detail satu resensi
        $st = $conn->prepare(
            "SELECT r.*, u.username, u.nama_lengkap,
                    (SELECT COUNT(*) FROM likes WHERE resensi_id = r.id) AS total_likes,
                    (SELECT COUNT(*) FROM komentar WHERE resensi_id = r.id) AS total_komentar
             FROM resensi r JOIN users u ON r.user_id = u.id WHERE r.id = ?"
        );
        $st->bind_param('i', $id);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$row) error_respond('Resensi tidak ditemukan.', 404);
        respond(['status' => 'success', 'data' => $row]);
    }

    // Daftar resensi (dengan filter genre opsional)
    if ($genre) {
        $likeGenre = "%$genre%";
        $st = $conn->prepare(
            "SELECT r.id, r.judul_buku, r.penulis_buku, r.genre, r.rating, r.tgl_input, u.username
             FROM resensi r JOIN users u ON r.user_id = u.id
             WHERE r.genre LIKE ? ORDER BY r.tgl_input DESC LIMIT ? OFFSET ?"
        );
        $st->bind_param('sii', $likeGenre, $limit, $offset);
    } else {
        $st = $conn->prepare(
            "SELECT r.id, r.judul_buku, r.penulis_buku, r.genre, r.rating, r.tgl_input, u.username
             FROM resensi r JOIN users u ON r.user_id = u.id
             ORDER BY r.tgl_input DESC LIMIT ? OFFSET ?"
        );
        $st->bind_param('ii', $limit, $offset);
    }
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    $total = (int) $conn->query("SELECT COUNT(*) FROM resensi" . ($genre ? " WHERE genre LIKE '%$genre%'" : ""))->fetch_row()[0];
    respond([
        'status'      => 'success',
        'page'        => $page,
        'limit'       => $limit,
        'total'       => $total,
        'total_pages' => ceil($total / $limit),
        'data'        => $rows,
    ]);
}

// ── GET /api.php?endpoint=users ──────────────────────────────────────────────
if ($endpoint === 'users' && $method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id > 0) {
        $st = $conn->prepare(
            "SELECT u.id, u.username, u.nama_lengkap, u.bio,
                    (SELECT COUNT(*) FROM resensi WHERE user_id = u.id) AS total_resensi,
                    (SELECT COUNT(*) FROM pengikut WHERE diikuti_id = u.id) AS total_pengikut,
                    (SELECT COUNT(*) FROM pengikut WHERE pengikut_id = u.id) AS total_mengikuti
             FROM users u WHERE u.id = ? AND u.role = 'user'"
        );
        $st->bind_param('i', $id);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$row) error_respond('Pengguna tidak ditemukan.', 404);
        respond(['status' => 'success', 'data' => $row]);
    }

    $limit  = min(intval($_GET['limit'] ?? 20), 100);
    $page   = max(intval($_GET['page'] ?? 1), 1);
    $offset = ($page - 1) * $limit;
    $st = $conn->prepare(
        "SELECT id, username, nama_lengkap, bio FROM users WHERE role = 'user' ORDER BY id DESC LIMIT ? OFFSET ?"
    );
    $st->bind_param('ii', $limit, $offset);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    $total = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
    respond([
        'status'      => 'success',
        'page'        => $page,
        'limit'       => $limit,
        'total'       => $total,
        'total_pages' => ceil($total / $limit),
        'data'        => $rows,
    ]);
}

// ── GET /api.php?endpoint=katalog ────────────────────────────────────────────
if ($endpoint === 'katalog' && $method === 'GET') {
    $q      = trim($_GET['q'] ?? '');
    $genre  = trim($_GET['genre'] ?? '');
    $sort   = $_GET['sort'] ?? 'terbaru';
    $limit  = min(intval($_GET['limit'] ?? 20), 100);
    $page   = max(intval($_GET['page'] ?? 1), 1);
    $offset = ($page - 1) * $limit;

    $where = ['1=1'];
    $params = [];
    $types  = '';

    if ($q) {
        $like = "%$q%";
        $where[] = "(r.judul_buku LIKE ? OR r.penulis_buku LIKE ? OR u.username LIKE ?)";
        $params = array_merge($params, [$like, $like, $like]);
        $types .= 'sss';
    }
    if ($genre) {
        $likeG = "%$genre%";
        $where[] = "r.genre LIKE ?";
        $params[] = $likeG;
        $types   .= 's';
    }

    $orderMap = [
        'terbaru'      => 'r.tgl_input DESC',
        'terlama'      => 'r.tgl_input ASC',
        'rating_tinggi'=> 'r.rating DESC',
        'rating_rendah'=> 'r.rating ASC',
    ];
    $order = $orderMap[$sort] ?? 'r.tgl_input DESC';
    $whereSql = implode(' AND ', $where);

    $params[] = $limit;
    $params[] = $offset;
    $types   .= 'ii';

    $st = $conn->prepare(
        "SELECT r.id, r.judul_buku, r.penulis_buku, r.genre, r.rating, r.tgl_input, u.username
         FROM resensi r JOIN users u ON r.user_id = u.id
         WHERE $whereSql ORDER BY $order LIMIT ? OFFSET ?"
    );
    $st->bind_param($types, ...$params);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    respond([
        'status' => 'success',
        'page'   => $page,
        'limit'  => $limit,
        'data'   => $rows,
    ]);
}

// ── Fallback ─────────────────────────────────────────────────────────────────
error_respond(
    'Endpoint tidak ditemukan. Tersedia: resensi, users, katalog, statistik, login',
    404
);
