<?php
/**
 * Yeni kullanıcı kaydı
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

if (is_user_logged_in()) {
    redirect(PUBLIC_URL . '/hesabim.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Güvenlik doğrulaması başarısız.';
    }

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';
    $terms    = isset($_POST['terms']);

    if (mb_strlen($name) < 3) $errors[] = 'Ad Soyad en az 3 karakter olmalı.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta adresi girin.';
    if (strlen($password) < 8) $errors[] = 'Şifre en az 8 karakter olmalı.';
    if ($password !== $confirm) $errors[] = 'Şifreler eşleşmiyor.';
    if (!$terms) $errors[] = 'Kullanım koşullarını kabul etmelisiniz.';

    if (empty($errors)) {
        // E-posta kullanılıyor mu?
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $errors[] = 'Bu e-posta adresi zaten kayıtlı.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
        $ins->execute([$name, $email, $phone ?: null, $hash]);
        $userId = (int)$pdo->lastInsertId();
        login_user($pdo, $userId);
        flash('success', 'Hesabın oluşturuldu, hoş geldin!');
        redirect(PUBLIC_URL . '/hesabim.php');
    }
}

$pageTitle = 'Üye Ol — ' . setting('site_name');
require INCLUDES_PATH . '/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">
    <h1>Üye <em>ol</em></h1>
    <p class="subtitle">Saniyeler içinde hesabını oluştur, hızlı satın al, kolayca takip et.</p>

    <?php foreach ($errors as $err): ?>
      <div class="flash-bar flash-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" autocomplete="on">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label">Ad Soyad</label>
        <input type="text" name="name" class="form-input" required value="<?= e($_POST['name'] ?? '') ?>" autocomplete="name">
      </div>
      <div class="form-group">
        <label class="form-label">E-posta</label>
        <input type="email" name="email" class="form-input" required value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email">
      </div>
      <div class="form-group">
        <label class="form-label">Telefon (opsiyonel)</label>
        <input type="tel" name="phone" class="form-input" value="<?= e($_POST['phone'] ?? '') ?>" placeholder="+90 5XX XXX XX XX">
      </div>
      <div class="form-group">
        <label class="form-label">Şifre</label>
        <input type="password" name="password" class="form-input" required minlength="8" autocomplete="new-password">
        <div class="form-help">En az 8 karakter.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Şifre (Tekrar)</label>
        <input type="password" name="password_confirm" class="form-input" required autocomplete="new-password">
      </div>
      <div class="form-group">
        <label style="display:flex;gap:10px;align-items:flex-start;font-size:13.5px;color:var(--text-soft);cursor:pointer;">
          <input type="checkbox" name="terms" required style="margin-top:3px;accent-color:var(--indigo);">
          <span>Üyelik sözleşmesini ve gizlilik politikasını okudum, kabul ediyorum.</span>
        </label>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">Hesap Oluştur</button>
    </form>

    <div class="auth-footer">
      Zaten hesabın var mı? <a href="<?= PUBLIC_URL ?>/login.php">Giriş Yap</a>
    </div>
  </div>
</div>

<?php require INCLUDES_PATH . '/footer.php'; ?>
