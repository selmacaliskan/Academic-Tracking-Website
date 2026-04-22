<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$uid  = $user['id'];
$msg  = '';
$msgType = 'success';

// Fetch full user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$dbUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ── Update profile ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');

        if (!$name || !$email) {
            $msg = "Ad ve e-posta boş olamaz.";
            $msgType = 'danger';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = "Geçerli bir e-posta girin.";
            $msgType = 'danger';
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $email, $uid);
            $stmt->execute();
            $stmt->close();
            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $email;
            $dbUser['name']  = $name;
            $dbUser['email'] = $email;
            $msg = "Bilgiler güncellendi!";
        }
    }

    elseif ($action === 'change_password') {
        $current  = $_POST['current_password']  ?? '';
        $new      = $_POST['new_password']      ?? '';
        $confirm  = $_POST['confirm_password']  ?? '';

        if (!password_verify($current, $dbUser['password'])) {
            $msg = "Mevcut şifre hatalı.";
            $msgType = 'danger';
        } elseif (strlen($new) < 6) {
            $msg = "Yeni şifre en az 6 karakter olmalıdır.";
            $msgType = 'danger';
        } elseif ($new !== $confirm) {
            $msg = "Yeni şifreler eşleşmiyor.";
            $msgType = 'danger';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hash, $uid);
            $stmt->execute();
            $stmt->close();
            $msg = "Şifre başarıyla değiştirildi!";
        }
    }

    elseif ($action === 'update_theme') {
        $theme = in_array($_POST['theme'], ['light','dark']) ? $_POST['theme'] : 'light';
        $stmt  = $conn->prepare("UPDATE users SET theme=? WHERE id=?");
        $stmt->bind_param("si", $theme, $uid);
        $stmt->execute();
        $stmt->close();
        $dbUser['theme'] = $theme;
        $msg = "Tema uygulandı!";
    }
}

$theme = $dbUser['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil ve Ayarlar — Akademik Takip</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .section-title {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }
        .avatar-placeholder {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        @media (max-width:640px) { .settings-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="<?= $theme === 'dark' ? 'dark-theme' : '' ?>">
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="page-wrapper">
    <div class="page-header">
        <h1 class="page-title">Profil ve Ayarlar</h1>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- Personal Info -->
        <div class="card">
            <div class="section-title">Kişisel Bilgiler</div>
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;">
                <div class="avatar-placeholder">
                    <?= mb_strtoupper(mb_substr($dbUser['name'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight:600;"><?= htmlspecialchars($dbUser['name']) ?></div>
                    <div style="font-size:.84rem;color:var(--text-muted);"><?= htmlspecialchars($dbUser['email']) ?></div>
                </div>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_info">
                <div class="form-group">
                    <label class="form-label">Ad Soyad</label>
                    <input type="text" name="name" class="form-control"
                           value="<?= htmlspecialchars($dbUser['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">E-posta</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($dbUser['email']) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    Bilgileri Güncelle
                </button>
            </form>
        </div>

        <!-- Appearance -->
        <div class="card">
            <div class="section-title">Görünüm Ayarları</div>
            <form method="POST">
                <input type="hidden" name="action" value="update_theme">
                <div class="form-group">
                    <label class="form-label">Tema</label>
                    <select name="theme" class="form-control">
                        <option value="light" <?= $theme === 'light' ? 'selected' : '' ?>>Açık Tema</option>
                        <option value="dark"  <?= $theme === 'dark'  ? 'selected' : '' ?>>Koyu Tema</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    Temayı Uygula
                </button>
            </form>
        </div>
    </div>

    <!-- Password Change -->
    <div class="card" style="margin-top:20px;">
        <div class="section-title">Şifre Değiştirme</div>
        <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Mevcut Şifre</label>
                    <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Yeni Şifre</label>
                    <input type="password" name="new_password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Yeni Şifre Tekrar</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:16px;">Şifreyi Değiştir</button>
        </form>
    </div>
</div>

<script>
// Apply theme change immediately on select change (preview)
const themeSelect = document.querySelector('select[name="theme"]');
if (themeSelect) {
    themeSelect.addEventListener('change', function() {
        document.body.className = this.value === 'dark' ? 'dark-theme' : '';
    });
}
</script>
</body>
</html>
