<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Already logged in → redirect
if (isLoggedIn()) {
    header("Location: projects.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $stmt2 = $conn->prepare("UPDATE users SET remember_token=? WHERE id=?");
                $stmt2->bind_param("si", $token, $user['id']);
                $stmt2->execute();
                $stmt2->close();
                setcookie('remember_token', $token, time() + 60*60*24*30, '/');
            }

            header("Location: projects.php");
            exit();
        } else {
            $error = "E-posta veya şifre hatalı.";
        }
    } else {
        $error = "Lütfen tüm alanları doldurun.";
    }
}

// Check remember me cookie
if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt  = $conn->prepare("SELECT id, name, email FROM users WHERE remember_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res  = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if ($user) {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        header("Location: projects.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap — Akademik Takip</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--bg); }

        .login-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px 36px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 40px rgba(0,0,0,.1);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 28px;
        }

        .login-logo h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.5rem;
            color: var(--text);
            margin-top: 8px;
        }

        .login-logo .logo-icon {
            width: 48px;
            height: 48px;
            background: var(--primary);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .login-title { font-size: 1.15rem; font-weight: 700; margin-bottom: 20px; text-align: center; }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .88rem;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .remember-row input { accent-color: var(--primary); }

        .login-footer {
            text-align: center;
            margin-top: 18px;
            font-size: .85rem;
            color: var(--text-muted);
        }

        .login-footer a { color: var(--primary); text-decoration: none; font-weight: 600; }
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

    <div class="login-title">Giriş Yap</div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label class="form-label">E-posta</label>
            <input type="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="ornek@mail.com" required autofocus>
        </div>

        <div class="form-group">
            <label class="form-label">Şifre</label>
            <input type="password" name="password" class="form-control"
                   placeholder="••••••••" required>
        </div>

        <div class="remember-row">
            <input type="checkbox" name="remember" id="remember">
            <label for="remember">Beni Hatırla</label>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:11px;">
            Giriş Yap
        </button>
    </form>

    <div class="login-footer">
        Hesabınız yok mu? <a href="register.php">Kayıt olun</a>
    </div>
</div>
</body>
</html>
