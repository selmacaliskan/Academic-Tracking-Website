<?php
// includes/navbar.php
// Requires $conn and session to be active before including this file
$user = getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <a href="projects.php" class="navbar-brand">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
        </svg>
        Akademik Takip
    </a>

    <ul class="navbar-nav">
        <li><a href="projects.php" class="<?= $current_page === 'projects.php' ? 'active' : '' ?>">Projeler</a></li>
        <li><a href="tasks.php"    class="<?= $current_page === 'tasks.php'    ? 'active' : '' ?>">Görevler</a></li>
        <li><a href="files.php"    class="<?= $current_page === 'files.php'    ? 'active' : '' ?>">Dosyalar</a></li>
    </ul>

    <div class="navbar-right">
        Hoşgeldiniz, <strong><?= htmlspecialchars($user['name']) ?></strong>
        <a href="profile.php" class="btn-nav <?= $current_page === 'profile.php' ? 'active' : '' ?>">Profilim</a>
        <a href="logout.php"  class="btn-nav danger">Güvenli Çıkış</a>
    </div>
</nav>
