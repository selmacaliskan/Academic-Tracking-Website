<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user   = getCurrentUser();
$uid    = $user['id'];
$msg    = '';
$msgType = 'success';

// ── Create project ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');

    if ($title) {
        $stmt = $conn->prepare("INSERT INTO projects (user_id, title, description) VALUES (?,?,?)");
        $stmt->bind_param("iss", $uid, $title, $desc);
        $stmt->execute();
        $stmt->close();
        $msg = "Proje başarıyla eklendi!";
    } else {
        $msg = "Proje başlığı boş olamaz.";
        $msgType = 'danger';
    }
}

// ── Delete project ────────────────────────────────────────
if (isset($_GET['delete'])) {
    $pid  = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM projects WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $pid, $uid);
    $stmt->execute();
    $stmt->close();
    header("Location: projects.php");
    exit();
}

// ── Fetch projects ────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM projects WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $uid);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Task counts per project
$countMap = [];
$stmt2 = $conn->prepare("SELECT project_id, COUNT(*) as cnt FROM tasks WHERE user_id=? AND project_id IS NOT NULL GROUP BY project_id");
$stmt2->bind_param("i", $uid);
$stmt2->execute();
$rows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();
foreach ($rows as $r) $countMap[$r['project_id']] = $r['cnt'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projelerim — Akademik Takip</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="page-wrapper">
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <h1 class="page-title">Projelerim</h1>
        <button class="btn btn-primary" onclick="openModal('projectModal')">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Yeni Proje Ekle
        </button>
    </div>

    <?php if (empty($projects)): ?>
        <div class="card">
            <div class="empty-state">
                <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;opacity:.4">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                </svg>
                Henüz kayıtlı bir projeniz bulunmuyor.
            </div>
        </div>
    <?php else: ?>
        <div class="project-grid">
            <?php foreach ($projects as $p): ?>
            <div class="project-card">
                <div class="project-card-title"><?= htmlspecialchars($p['title']) ?></div>
                <div class="project-card-desc"><?= htmlspecialchars($p['description'] ?: 'Açıklama yok') ?></div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span style="font-size:.8rem;color:var(--text-muted);">
                        <?= $countMap[$p['id']] ?? 0 ?> görev
                    </span>
                    <div style="display:flex;gap:6px;">
                        <a href="tasks.php?project_id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Görevler</a>
                        <a href="projects.php?delete=<?= $p['id'] ?>"
                           class="btn btn-danger btn-sm btn-icon"
                           onclick="return confirm('Bu projeyi silmek istediğinize emin misiniz?')"
                           title="Sil">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Yeni Proje Oluştur -->
<div class="modal-backdrop" id="projectModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Yeni Proje Oluştur</span>
            <button class="modal-close" onclick="closeModal('projectModal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label class="form-label">Proje Başlığı</label>
                <input type="text" name="title" class="form-control" placeholder="Proje adını girin" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Proje Açıklaması</label>
                <textarea name="description" class="form-control" placeholder="Kısa bir açıklama..."></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:8px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('projectModal')">İptal</button>
                <button type="submit" class="btn btn-primary">Projeyi Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open');    }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Close on backdrop click
document.querySelectorAll('.modal-backdrop').forEach(b => {
    b.addEventListener('click', e => { if (e.target === b) b.classList.remove('open'); });
});
</script>
</body>
</html>
