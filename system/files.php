<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$uid  = $user['id'];
$msg  = '';
$msgType = 'success';

$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// ── Upload file ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
    $f          = $_FILES['file'];

    if ($f['error'] === UPLOAD_ERR_OK) {
        $origName = basename($f['name']);
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        // Allowed extensions
        $allowed = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','jpg','jpeg','png','gif','zip','rar'];
        if (!in_array($ext, $allowed)) {
            $msg = "Bu dosya türüne izin verilmiyor.";
            $msgType = 'danger';
        } else {
            $safeName = uniqid('file_', true) . '.' . $ext;
            $target   = $uploadDir . $safeName;

            if (move_uploaded_file($f['tmp_name'], $target)) {
                $size = $f['size'];
                $mime = $f['type'];
                $stmt = $conn->prepare(
                    "INSERT INTO files (user_id, project_id, filename, original_name, file_size, mime_type)
                     VALUES (?,?,?,?,?,?)"
                );
                $stmt->bind_param("iissss", $uid, $project_id, $safeName, $origName, $size, $mime);
                $stmt->execute();
                $stmt->close();
                $msg = "Dosya başarıyla yüklendi!";
            } else {
                $msg = "Dosya yükleme başarısız.";
                $msgType = 'danger';
            }
        }
    } else {
        $msg = "Dosya seçilmedi veya yükleme hatası.";
        $msgType = 'danger';
    }
}

// ── Delete file ───────────────────────────────────────────
if (isset($_GET['delete'])) {
    $fid  = (int)$_GET['delete'];
    $stmt = $conn->prepare("SELECT filename FROM files WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $fid, $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        @unlink($uploadDir . $row['filename']);
        $del = $conn->prepare("DELETE FROM files WHERE id=? AND user_id=?");
        $del->bind_param("ii", $fid, $uid);
        $del->execute();
        $del->close();
    }
    header("Location: files.php");
    exit();
}

// ── Download file ─────────────────────────────────────────
if (isset($_GET['download'])) {
    $fid  = (int)$_GET['download'];
    $stmt = $conn->prepare("SELECT * FROM files WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $fid, $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && file_exists($uploadDir . $row['filename'])) {
        header('Content-Type: ' . ($row['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $row['original_name'] . '"');
        header('Content-Length: ' . $row['file_size']);
        readfile($uploadDir . $row['filename']);
        exit();
    }
}

// ── Fetch files ───────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT f.*, p.title as project_title FROM files f
     LEFT JOIN projects p ON p.id = f.project_id
     WHERE f.user_id=?
     ORDER BY f.created_at DESC"
);
$stmt->bind_param("i", $uid);
$stmt->execute();
$files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Projects for dropdown
$pStmt = $conn->prepare("SELECT id, title FROM projects WHERE user_id=? ORDER BY title");
$pStmt->bind_param("i", $uid);
$pStmt->execute();
$allProjects = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pStmt->close();

function formatBytes($bytes) {
    if ($bytes >= 1048576)  return round($bytes/1048576, 2) . ' MB';
    if ($bytes >= 1024)     return round($bytes/1024, 2)    . ' KB';
    return $bytes . ' B';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dosya Yönetimi — Akademik Takip</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .upload-box {
            border: 2px dashed var(--border);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: border-color .2s;
            cursor: pointer;
        }
        .upload-box:hover { border-color: var(--primary); }
        .file-input-label { cursor: pointer; display: block; }
        #file-name { font-size: .83rem; color: var(--text-muted); margin-top: 6px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="page-wrapper">
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <h1 class="page-title">Dosyalarım</h1>
    </div>

    <div style="display:grid;grid-template-columns:280px 1fr;gap:20px;align-items:start;">
        <!-- Upload Panel -->
        <div class="card">
            <div style="font-weight:700;margin-bottom:16px;font-size:1rem;">Dosya Yükle</div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">İlgili Proje</label>
                    <select name="project_id" class="form-control">
                        <option value="">Genel / Projesiz</option>
                        <?php foreach ($allProjects as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Dosya Seç</label>
                    <div class="upload-box" onclick="document.getElementById('fileInput').click()">
                        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--text-muted);margin:0 auto 6px;display:block">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        <span style="font-size:.85rem;color:var(--text-muted);">Tıklayarak dosya seçin</span>
                        <div id="file-name">Seçilen dosya yok</div>
                        <input type="file" id="fileInput" name="file" style="display:none"
                               onchange="document.getElementById('file-name').textContent = this.files[0]?.name || 'Seçilen dosya yok'">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Yükle
                </button>
            </form>
        </div>

        <!-- File list -->
        <div class="card">
            <?php if (empty($files)): ?>
                <div class="empty-state">
                    <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 10px;display:block;opacity:.4">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    Henüz yüklenmiş dosya yok.
                </div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Dosya Adı</th>
                            <th>Proje</th>
                            <th>Boyut</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($files as $f): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="color:var(--primary);flex-shrink:0">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                    <?= htmlspecialchars($f['original_name']) ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($f['project_title']): ?>
                                    <span class="badge badge-proje"><?= htmlspecialchars($f['project_title']) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:.82rem;">Genel</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:.86rem;color:var(--text-muted);"><?= formatBytes($f['file_size']) ?></td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="files.php?download=<?= $f['id'] ?>" class="btn btn-success btn-sm">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        İndir
                                    </a>
                                    <a href="files.php?delete=<?= $f['id'] ?>"
                                       class="btn btn-danger btn-sm btn-icon"
                                       onclick="return confirm('Bu dosyayı silmek istediğinize emin misiniz?')"
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
</div>
</body>
</html>
