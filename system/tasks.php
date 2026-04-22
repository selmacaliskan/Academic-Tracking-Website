<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$uid  = $user['id'];
$msg  = '';
$msgType = 'success';

// Filter by project?
$filterProject = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;

// ── Create task ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $title      = trim($_POST['title']       ?? '');
    $desc       = trim($_POST['description'] ?? '');
    $due        = trim($_POST['due_date']    ?? '') ?: null;
    $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;

    if ($title) {
        $stmt = $conn->prepare("INSERT INTO tasks (user_id, project_id, title, description, due_date) VALUES (?,?,?,?,?)");
        $stmt->bind_param("iisss", $uid, $project_id, $title, $desc, $due);
        $stmt->execute();
        $stmt->close();
        $msg = "Görev eklendi!";
    } else {
        $msg = "Başlık boş olamaz.";
        $msgType = 'danger';
    }
}

// ── Update status ─────────────────────────────────────────
if (isset($_GET['status']) && isset($_GET['id'])) {
    $tid    = (int)$_GET['id'];
    $status = $_GET['status'];
    $allowed = ['beklemede', 'devam_ediyor', 'tamamlandi'];
    if (in_array($status, $allowed)) {
        $stmt = $conn->prepare("UPDATE tasks SET status=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sii", $status, $tid, $uid);
        $stmt->execute();
        $stmt->close();
    }
    $redir = $filterProject ? "tasks.php?project_id=$filterProject" : "tasks.php";
    header("Location: $redir");
    exit();
}

// ── Delete task ───────────────────────────────────────────
if (isset($_GET['delete'])) {
    $tid  = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $tid, $uid);
    $stmt->execute();
    $stmt->close();
    $redir = $filterProject ? "tasks.php?project_id=$filterProject" : "tasks.php";
    header("Location: $redir");
    exit();
}

// ── Fetch tasks ───────────────────────────────────────────
if ($filterProject) {
    $stmt = $conn->prepare(
        "SELECT t.*, p.title as project_title FROM tasks t
         LEFT JOIN projects p ON p.id = t.project_id
         WHERE t.user_id=? AND t.project_id=?
         ORDER BY t.created_at DESC"
    );
    $stmt->bind_param("ii", $uid, $filterProject);
} else {
    $stmt = $conn->prepare(
        "SELECT t.*, p.title as project_title FROM tasks t
         LEFT JOIN projects p ON p.id = t.project_id
         WHERE t.user_id=?
         ORDER BY t.created_at DESC"
    );
    $stmt->bind_param("i", $uid);
}
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all projects for dropdown
$pStmt = $conn->prepare("SELECT id, title FROM projects WHERE user_id=? ORDER BY title");
$pStmt->bind_param("i", $uid);
$pStmt->execute();
$allProjects = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pStmt->close();

// Page title
$pageTitle = $filterProject ? "Proje Görevleri" : "Tüm Görevlerim";

function statusBadge($s) {
    $map = [
        'beklemede'    => ['label' => 'Beklemede',    'class' => 'badge-beklemede'],
        'devam_ediyor' => ['label' => 'Devam ediyor', 'class' => 'badge-devam'],
        'tamamlandi'   => ['label' => 'Tamamlandı',   'class' => 'badge-tamam'],
    ];
    $m = $map[$s] ?? ['label' => $s, 'class' => ''];
    return "<span class='badge {$m['class']}'>{$m['label']}</span>";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Akademik Takip</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="page-wrapper">
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
        <button class="btn btn-primary" onclick="openModal('taskModal')">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
             Yeni Görev
        </button>
    </div>

    <div class="card">
        <?php if (empty($tasks)): ?>
            <div class="empty-state">
                <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 10px;display:block;opacity:.4">
                    <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                Henüz görev bulunmuyor.
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Proje</th>
                        <th>Son Tarih</th>
                        <th>Durum</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tasks as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['title']) ?></td>
                        <td>
                            <?php if ($t['project_title']): ?>
                                <span class="badge badge-proje"><?= htmlspecialchars($t['project_title']) ?></span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);font-size:.82rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.86rem;color:var(--text-muted);">
                            <?= $t['due_date'] ? date('d.m.Y H:i', strtotime($t['due_date'])) : '—' ?>
                        </td>
                        <td><?= statusBadge($t['status']) ?></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <?php if ($t['status'] === 'beklemede'): ?>
                                    <a href="?status=devam_ediyor&id=<?= $t['id'] ?><?= $filterProject ? "&project_id=$filterProject" : '' ?>"
                                       class="btn btn-outline btn-sm">Çalışmaya Başla</a>
                                <?php elseif ($t['status'] === 'devam_ediyor'): ?>
                                    <a href="?status=tamamlandi&id=<?= $t['id'] ?><?= $filterProject ? "&project_id=$filterProject" : '' ?>"
                                       class="btn btn-success btn-sm">Tamamla</a>
                                <?php endif; ?>
                                <a href="?delete=<?= $t['id'] ?><?= $filterProject ? "&project_id=$filterProject" : '' ?>"
                                   class="btn btn-danger btn-sm btn-icon"
                                   onclick="return confirm('Bu görevi silmek istediğinize emin misiniz?')"
                                   title="Sil">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Yeni Görev Ekle -->
<div class="modal-backdrop" id="taskModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Yeni Görev Ekle</span>
            <button class="modal-close" onclick="closeModal('taskModal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label class="form-label">İlgili Proje</label>
                <select name="project_id" class="form-control">
                    <option value="">Genel / Projesiz</option>
                    <?php foreach ($allProjects as $p): ?>
                        <option value="<?= $p['id'] ?>"
                            <?= ($filterProject == $p['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Başlık</label>
                <input type="text" name="title" class="form-control" placeholder="Görev başlığı" required>
            </div>

            <div class="form-group">
                <label class="form-label">Açıklama</label>
                <textarea name="description" class="form-control" placeholder="Kısa bir açıklama..."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Son Teslim Tarihi</label>
                <input type="datetime-local" name="due_date" class="form-control">
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:8px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('taskModal')">İptal</button>
                <button type="submit" class="btn btn-primary">Görevi Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open');    }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-backdrop').forEach(b => {
    b.addEventListener('click', e => { if (e.target === b) b.classList.remove('open'); });
});
</script>
</body>
</html>
