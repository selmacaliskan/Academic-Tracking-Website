<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) { header("Location: projects.php"); exit(); }

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    if (!$name || !$email || !$password) {
        $error = "Lütfen tüm alanları doldurun.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Geçerli bir e-posta adresi girin.";
    } elseif (strlen($password) < 6) {
        $error = "Şifre en az 6 karakter olmalıdır.";
    } elseif ($password !== $confirm) {
        $error = "Şifreler eşleşmiyor.";
    } else {
        // Check duplicate
        $chk = $conn->prepare("SELECT id FROM users WHERE email=?");
        $chk->bind_param("s", $email);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $error = "Bu e-posta adresi zaten kayıtlı.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?,?,?)");
            $ins->bind_param("sss", $name, $email, $hash);
            if ($ins->execute()) {
                $success = "Kayıt başarılı! <a href='login.php'>Giriş yapın</a>.";
            } else {
                $error = "Kayıt sırasında bir hata oluştu.";
            }
            $ins->close();
        }
        $chk->close();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol — Akademik Takip</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .login-box { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:40px 36px; width:100%; max-width:400px; box-shadow:0 8px 40px rgba(0,0,0,.1); }
        .login-logo { text-align:center; margin-bottom:24px; }
        .login-logo h1 { font-family:'DM Serif Display',serif; font-size:1.4rem; margin-top:8px; }
        .logo-icon { width:48px; height:48px; background:var(--primary); border-radius:12px; display:inline-flex; align-items:center; justify-content:center; color:#fff; }
        .login-footer { text-align:center; margin-top:16px; font-size:.85rem; color:var(--text-muted); }
        .login-footer a { color:var(--primary); text-decoration:none; font-weight:600; }
    </style>
</head>
<body>
<div class="login-box">
    <div class="login-logo">
        <div class="logo-icon">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
            </svg>
        </div>
        <h1>Akademik Takip</h1>
    </div>

    <div style="font-size:1.1rem;font-weight:700;margin-bottom:18px;text-align:center;">Kayıt Ol</div>

    <?php if ($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label class="form-label">Ad Soyad</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">E-posta</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Şifre</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Şifre Tekrar</label>
            <input type="password" name="confirm" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px;">
            Kayıt Ol
        </button>
    </form>

    <div class="login-footer">
        Zaten hesabınız var mı? <a href="login.php">Giriş yapın</a>
    </div>
</div>
</body>
</html>
