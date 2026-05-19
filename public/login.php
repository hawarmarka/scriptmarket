<?php
/**
 * Kullanıcı girişi
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

if (is_user_logged_in()) {
    redirect(PUBLIC_URL . '/hesabim.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız.');
    } elseif (is_login_locked($pdo, 'user')) {
        flash('error', 'Çok fazla başarısız deneme. Lütfen ' . LOGIN_LOCKOUT_MINUTES . ' dakika sonra tekrar deneyin.');
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT id, name, password, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'banned') {
            flash('error', 'Hesabınız askıya alınmış. Yönetici ile iletişime geçin.');
            log_login_attempt($pdo, $email, false);
        } elseif ($user && password_verify($password, $user['password'])) {
            log_login_attempt($pdo, $email, true);
            login_user($pdo, (int)$user['id']);
            $redirect = $_SESSION['redirect_after_login'] ?? PUBLIC_URL . '/hesabim.php';
            unset($_SESSION['redirect_after_login']);
            flash('success', 'Hoş geldin, ' . explode(' ', $user['name'])[0] . '!');
            redirect($redirect);
        } else {
            log_login_attempt($pdo, $email, false);
            flash('error', 'E-posta veya şifre hatalı.');
        }
    }
}

$pageTitle = 'Giriş Yap — ' . setting('site_name');
require INCLUDES_PATH . '/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">
    <h1>Hoş geldin <em>tekrar</em></h1>
    <p class="subtitle">Hesabına giriş yap ve siparişlerini görüntüle.</p>

    <form method="post" autocomplete="on">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label">E-posta</label>
        <input type="email" name="email" class="form-input" required autofocus value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email">
      </div>
      <div class="form-group">
        <label class="form-label">Şifre</label>
        <input type="password" name="password" class="form-input" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg">Giriş Yap</button>
    </form>

    <div class="auth-footer">
      Hesabın yok mu? <a href="<?= PUBLIC_URL ?>/register.php">Hemen Üye Ol</a>
    </div>
  </div>
</div>

<?php require INCLUDES_PATH . '/footer.php'; ?>
