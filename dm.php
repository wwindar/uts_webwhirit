<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$pageTitle = 'Pesan Langsung';
$basePath = '../';
$current_user_id = $_SESSION['user_id'];
$active_chat_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Ambil kontak (yang saling follow atau pernah chat)
$contacts = [];
$sqlContacts = "
    SELECT DISTINCT u.id, u.username, u.foto_profil,
        (SELECT COUNT(*) FROM pesan p WHERE p.pengirim_id = u.id AND p.penerima_id = ? AND p.dibaca = 0) as unread_count
    FROM users u
    WHERE u.id != ? AND (
        u.id IN (SELECT diikuti_id FROM pengikut WHERE pengikut_id = ?)
        OR u.id IN (SELECT pengikut_id FROM pengikut WHERE diikuti_id = ?)
        OR u.id IN (SELECT pengirim_id FROM pesan WHERE penerima_id = ?)
        OR u.id IN (SELECT penerima_id FROM pesan WHERE pengirim_id = ?)
        OR u.id = ?
    )
";
$stmt = $conn->prepare($sqlContacts);
$stmt->bind_param("iiiiiii", $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $active_chat_user_id);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){
    $contacts[] = $row;
}
$stmt->close();

$active_contact = null;
if ($active_chat_user_id > 0) {
    foreach($contacts as $c){
        if($c['id'] == $active_chat_user_id){
            $active_contact = $c;
            break;
        }
    }
    // Jika tidak ada di daftar kontak sebelumnya, tambahkan (misalnya dari tombol Kirim Pesan di profil)
    if (!$active_contact) {
        $st = $conn->prepare("SELECT id, username, foto_profil FROM users WHERE id = ?");
        $st->bind_param("i", $active_chat_user_id);
        $st->execute();
        $rc = $st->get_result();
        if($rc->num_rows > 0){
            $active_contact = $rc->fetch_assoc();
            $contacts[] = $active_contact;
        }
        $st->close();
    }
}
?>
<?php include('header.php'); ?>

<style>
.dm-container {
    display: flex;
    height: calc(100vh - 200px);
    min-height: 500px;
    background: var(--paper);
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 20px var(--shadow);
}
.dm-sidebar {
    width: 300px;
    border-right: 1px solid var(--border);
    background: var(--page-bg);
    overflow-y: auto;
}
.dm-contact {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none;
    color: var(--ink);
}
.dm-contact:hover {
    background: #fdfbf7;
}
.dm-contact.active {
    background: #fdfbf7;
    border-left: 4px solid var(--gold);
}
.dm-contact img {
    width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 1rem; border: 1px solid var(--border);
}
.dm-contact-fallback {
    width: 40px; height: 40px; border-radius: 50%; background: var(--gold); color: white;
    display: flex; align-items: center; justify-content: center; margin-right: 1rem; font-weight: bold; font-family: var(--font-display); font-size: 1.2rem;
}
.dm-main {
    flex: 1;
    display: flex;
    flex-direction: column;
}
.dm-header {
    padding: 1rem;
    border-bottom: 1px solid var(--border);
    background: #fff;
    display: flex;
    align-items: center;
}
.dm-chat-area {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
    background: #fdfdfd;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.dm-bubble {
    max-width: 70%;
    padding: 0.75rem 1rem;
    border-radius: 16px;
    font-size: 0.95rem;
    line-height: 1.4;
    position: relative;
    word-wrap: break-word;
}
.dm-bubble.sent {
    align-self: flex-end;
    background: var(--gold);
    color: white;
    border-bottom-right-radius: 4px;
}
.dm-bubble.received {
    align-self: flex-start;
    background: white;
    border: 1px solid var(--border);
    color: var(--ink);
    border-bottom-left-radius: 4px;
}
.dm-time {
    font-size: 0.7rem;
    opacity: 0.8;
    margin-top: 0.3rem;
    display: block;
    text-align: right;
}
.dm-input-area {
    padding: 1rem;
    background: #fff;
    border-top: 1px solid var(--border);
    display: flex;
    gap: 0.5rem;
}
.dm-input-area input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border);
    border-radius: 24px;
    outline: none;
    font-family: inherit;
    transition: border-color 0.2s;
}
.dm-input-area input:focus {
    border-color: var(--gold);
}
.dm-input-area button {
    background: var(--gold);
    color: white;
    border: none;
    border-radius: 24px;
    padding: 0 1.5rem;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.2s;
}
.dm-input-area button:hover {
    background: #b88d35;
}
.dm-input-area button:disabled {
    background: #ccc;
    cursor: not-allowed;
}
</style>

<div class="main-content">
    <div class="page-header">
        <h1>💬 Pesan Langsung</h1>
        <p>Ngobrol dengan pengguna lain.</p>
    </div>

    <div class="dm-container">
        <!-- Sidebar -->
        <div class="dm-sidebar">
            <?php if(empty($contacts)): ?>
                <div style="padding: 1.5rem; color: var(--ink-light); font-size: 0.9rem; text-align: center;">
                    Belum ada kontak. Mulai ikuti seseorang!
                </div>
            <?php else: ?>
                <?php foreach($contacts as $c): 
                    $isActive = ($active_contact && $c['id'] == $active_contact['id']);
                ?>
                    <a href="dm.php?user_id=<?= $c['id'] ?>" class="dm-contact <?= $isActive ? 'active' : '' ?>">
                        <?php if(!empty($c['foto_profil']) && file_exists('uploads/'.$c['foto_profil'])): ?>
                            <img src="uploads/<?= htmlspecialchars($c['foto_profil']) ?>">
                        <?php else: ?>
                            <div class="dm-contact-fallback"><?= strtoupper(substr($c['username'],0,1)) ?></div>
                        <?php endif; ?>
                        <div style="flex:1; display:flex; justify-content:space-between; align-items:center;">
                            <div style="font-weight: 600; color: var(--ink)"><?= htmlspecialchars($c['username']) ?></div>
                            <?php if(($c['unread_count'] ?? 0) > 0): ?>
                                <span style="background:var(--gold); color:#fff; font-size:0.75rem; padding:0.15rem 0.5rem; border-radius:12px; font-weight:bold;"><?= $c['unread_count'] ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Main Chat -->
        <div class="dm-main">
            <?php if($active_contact): ?>
                <div class="dm-header">
                    <?php if(!empty($active_contact['foto_profil']) && file_exists('uploads/'.$active_contact['foto_profil'])): ?>
                        <img src="uploads/<?= htmlspecialchars($active_contact['foto_profil']) ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover; margin-right:1rem; border: 1px solid var(--border);">
                    <?php else: ?>
                        <div class="dm-contact-fallback" style="margin-right:1rem;"><?= strtoupper(substr($active_contact['username'],0,1)) ?></div>
                    <?php endif; ?>
                    <div>
                        <div style="font-weight: 600; font-size: 1.1rem; color: var(--ink)">
                            <?= htmlspecialchars($active_contact['username']) ?>
                        </div>
                        <a href="profil_publik.php?id=<?= $active_contact['id'] ?>" style="font-size:0.8rem; color:var(--ink-light); text-decoration:none;">Lihat Profil</a>
                    </div>
                </div>
                
                <div class="dm-chat-area" id="chat-area">
                    <!-- Pesan dimuat via AJAX -->
                    <div style="text-align:center; padding: 2rem; color: #888;">Memuat pesan...</div>
                </div>

                <form class="dm-input-area" id="chat-form">
                    <input type="text" id="chat-input" placeholder="Ketik pesan..." required autocomplete="off">
                    <button type="submit" id="chat-submit">Kirim</button>
                </form>

            <?php else: ?>
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:var(--ink-light);">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
                    <h3 style="font-family: var(--font-display); color: var(--ink)">Pilih obrolan</h3>
                    <p>Pilih kontak dari sebelah kiri untuk mulai mengirim pesan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if($active_contact): ?>
<script>
const activeUserId = <?= $active_contact['id'] ?>;
const currentUserId = <?= $current_user_id ?>;
const chatArea = document.getElementById('chat-area');
const chatForm = document.getElementById('chat-form');
const chatInput = document.getElementById('chat-input');
const chatSubmit = document.getElementById('chat-submit');
let lastMessageId = 0;

function formatTime(dateStr) {
    const d = new Date(dateStr.replace(' ', 'T'));
    return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
}

function fetchMessages(isInit = false) {
    fetch(`dm_action.php?action=fetch&user_id=${activeUserId}&last_id=${lastMessageId}`)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (isInit) {
                chatArea.innerHTML = ''; 
                if(data.messages.length === 0) {
                    chatArea.innerHTML = '<div style="text-align:center; padding: 2rem; color: #aaa; font-size: 0.9rem;">Belum ada pesan. Mulai sapa!</div>';
                }
            }
            
            data.messages.forEach(msg => {
                // Hapus empty state text jika ada
                if(chatArea.querySelector('div[style*="text-align:center"]')) {
                    chatArea.innerHTML = '';
                }

                const bubble = document.createElement('div');
                bubble.className = 'dm-bubble ' + (msg.pengirim_id == currentUserId ? 'sent' : 'received');
                
                let text = msg.isi_pesan.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                bubble.innerHTML = `${text}<span class="dm-time">${formatTime(msg.created_at)}</span>`;
                
                chatArea.appendChild(bubble);
                lastMessageId = Math.max(lastMessageId, msg.id);
            });
            
            if (data.messages.length > 0) {
                chatArea.scrollTop = chatArea.scrollHeight;
            }
        }
    })
    .catch(err => console.error(err));
}

// Init
fetchMessages(true);

// Poll every 3 seconds
setInterval(() => fetchMessages(), 3000);

// Send Message
chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const text = chatInput.value.trim();
    if (!text) return;
    
    chatInput.value = '';
    chatInput.disabled = true;
    chatSubmit.disabled = true;
    
    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('penerima_id', activeUserId);
    formData.append('isi_pesan', text);
    
    fetch('dm_action.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            fetchMessages(); // fetch the new message immediately
        } else {
            if (typeof buatAlert === 'function') buatAlert(data.message, 'error');
            else alert(data.message);
        }
    })
    .catch(err => console.error(err))
    .finally(() => {
        chatInput.disabled = false;
        chatSubmit.disabled = false;
        chatInput.focus();
    });
});
</script>
<?php endif; ?>

<?php include('footer.php'); ?>
