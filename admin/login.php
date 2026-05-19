<?php
/**
 * Admin login — Cyber Edition
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_admin_logged_in()) {
    redirect(ADMIN_URL . '/dashboard.php');
}

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $err = 'Güvenlik doğrulaması başarısız.';
    } elseif (is_login_locked($pdo, 'admin')) {
        $err = 'Çok fazla başarısız deneme. ' . LOGIN_LOCKOUT_MINUTES . ' dakika sonra tekrar deneyin.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = $pdo->prepare("SELECT id, password FROM admins WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $row = $stmt->fetch();
        if ($row && password_verify($password, $row['password'])) {
            log_login_attempt($pdo, $username, true, 'admin');
            login_admin($pdo, (int)$row['id']);
            redirect(ADMIN_URL . '/dashboard.php');
        } else {
            log_login_attempt($pdo, $username, false, 'admin');
            $err = 'Kullanıcı adı veya şifre hatalı.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yönetici Girişi — <?= e(setting('site_name', 'ScriptMarkt')) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= ASSETS_URL ?>/images/favicon.svg">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;600;700&family=Inter:wght@300..700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
<link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/admin.css">
</head>
<body>

<div class="bg-cosmos"></div>
<div class="bg-grid"></div>
<canvas class="bg-matrix"></canvas>
<div class="bg-orbs">
  <div class="bg-orb"></div>
  <div class="bg-orb"></div>
  <div class="bg-orb"></div>
</div>

<div class="admin-login-wrap">
  <div class="admin-login-card">

    <div style="text-align:center;margin-bottom:28px;">
      <div style="display:inline-flex;align-items:center;gap:10px;margin-bottom:16px;">
        <?= brand_logo_html(true, 'login-brand-logo') ?>
      </div>
      <div style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--accent);letter-spacing:.2em;text-transform:uppercase;">// admin access only</div>
      <h2 style="font-size:24px;margin:14px 0 6px;">Yönetici <em>Girişi</em></h2>
      <p style="font-size:13.5px;color:var(--text-mute);">Sisteme erişmek için kimlik bilgilerini gir.</p>
    </div>

    <?php if ($err): ?>
      <div class="flash-bar flash-error">⚠ <?= e($err) ?></div>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label">Kullanıcı Adı / E-posta</label>
        <input type="text" name="username" class="form-input" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">Şifre</label>
        <input type="password" name="password" class="form-input" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:8px;">
        Giriş Yap
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </button>
    </form>

    <div style="margin-top:24px;padding-top:18px;border-top:1px solid var(--glass-border);text-align:center;">
      <a href="<?= PUBLIC_URL ?>/index.php" style="font-size:12.5px;color:var(--text-mute);font-family:'JetBrains Mono',monospace;">← cd /siteye</a>
    </div>
  </div>
</div>

<script src="<?= ASSETS_URL ?>/js/main.js"></script>
</body>
</html>
