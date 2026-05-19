<?php
/**
 * Hesabım — kullanıcı dashboard'ı
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

require_login();
$user = current_user($pdo);

$msg = null;

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız.');
    } else {
        $name  = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if (mb_strlen($name) < 3) {
            flash('error', 'Ad en az 3 karakter olmalı.');
        } else {
            $upd = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
            $upd->execute([$name, $phone ?: null, $user['id']]);
            flash('success', 'Profil bilgileriniz güncellendi.');
        }
    }
    redirect(PUBLIC_URL . '/hesabim.php');
}

// Şifre değiştirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız.');
    } else {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $conf    = $_POST['new_password_confirm'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch();

        if (!password_verify($current, $row['password'])) {
            flash('error', 'Mevcut şifre yanlış.');
        } elseif (strlen($new) < 8) {
            flash('error', 'Yeni şifre en az 8 karakter olmalı.');
        } elseif ($new !== $conf) {
            flash('error', 'Yeni şifreler eşleşmiyor.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user['id']]);
            flash('success', 'Şifreniz başarıyla değiştirildi.');
        }
    }
    redirect(PUBLIC_URL . '/hesabim.php');
}

// Sipariş istatistikleri
$orderStats = $pdo->prepare(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(total), 0) AS total_spent,
        SUM(CASE WHEN payment_status IN ('beklemede','odeme_bekliyor') THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN payment_status = 'teslim_edildi' THEN 1 ELSE 0 END) AS completed
     FROM orders WHERE user_id = ?"
);
$orderStats->execute([$user['id']]);
$stats = $orderStats->fetch();

// Son siparişler
$recent = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$recent->execute([$user['id']]);
$recentOrders = $recent->fetchAll();

$pageTitle = 'Hesabım — ' . setting('site_name');
require INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="breadcrumb"><a href="<?= PUBLIC_URL ?>/index.php">Ana Sayfa</a> / Hesabım</div>
  <h1>Hoş geldin, <em><?= e(explode(' ', $user['name'])[0]) ?></em></h1>
</div>

<div class="container" style="max-width: 1100px;">

  <!-- İstatistik kartları -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">📦</div>
      <div class="stat-value"><?= (int)$stats['total'] ?></div>
      <div class="stat-label">Toplam Sipariş</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">✅</div>
      <div class="stat-value"><?= (int)$stats['completed'] ?></div>
      <div class="stat-label">Tamamlandı</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">⏳</div>
      <div class="stat-value"><?= (int)$stats['pending'] ?></div>
      <div class="stat-label">Bekleyen</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">💰</div>
      <div class="stat-value"><?= format_price((float)$stats['total_spent']) ?></div>
      <div class="stat-label">Toplam Harcama</div>
    </div>
  </div>

  <div class="detail-card mb-3">
    <div class="tabs-nav">
      <button class="tab-btn active" data-tab="profile">Profilim</button>
      <button class="tab-btn" data-tab="password">Şifre Değiştir</button>
      <button class="tab-btn" data-tab="orders">Son Siparişler</button>
    </div>

    <div data-tab-content="profile" class="tab-content active">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="update_profile" value="1">
        <div class="form-group">
          <label class="form-label">Ad Soyad</label>
          <input type="text" name="name" class="form-input" required value="<?= e($user['name']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">E-posta</label>
          <input type="email" class="form-input" value="<?= e($user['email']) ?>" disabled>
          <div class="form-help">E-posta değiştirmek için destek ekibimiz ile iletişime geçin.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Telefon</label>
          <input type="tel" name="phone" class="form-input" value="<?= e($user['phone']) ?>" placeholder="+90 5XX XXX XX XX">
        </div>
        <button type="submit" class="btn btn-primary">Kaydet</button>
      </form>
    </div>

    <div data-tab-content="password" class="tab-content">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="change_password" value="1">
        <div class="form-group">
          <label class="form-label">Mevcut Şifre</label>
          <input type="password" name="current_password" class="form-input" required>
        </div>
        <div class="form-group">
          <label class="form-label">Yeni Şifre</label>
          <input type="password" name="new_password" class="form-input" required minlength="8">
          <div class="form-help">En az 8 karakter.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Yeni Şifre (Tekrar)</label>
          <input type="password" name="new_password_confirm" class="form-input" required>
        </div>
        <button type="submit" class="btn btn-primary">Şifreyi Değiştir</button>
      </form>
    </div>

    <div data-tab-content="orders" class="tab-content">
      <?php if (empty($recentOrders)): ?>
        <p class="text-mute">Henüz bir sipariş vermediniz.</p>
      <?php else: ?>
        <table class="data-table">
          <thead>
            <tr><th>Sipariş No</th><th>Tarih</th><th>Tutar</th><th>Durum</th><th></th></tr>
          </thead>
          <tbody>
          <?php foreach ($recentOrders as $o): [$lbl, $clr] = status_badge($o['payment_status']); ?>
            <tr>
              <td class="mono"><?= e($o['order_number']) ?></td>
              <td><?= format_date($o['created_at']) ?></td>
              <td><?= format_price((float)$o['total']) ?></td>
              <td><span class="badge badge-<?= $clr ?>"><?= e($lbl) ?></span></td>
              <td><a href="<?= PUBLIC_URL ?>/siparis-basarili.php?no=<?= e($o['order_number']) ?>" class="btn btn-sm btn-secondary">Detay</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
      <div class="mt-3 text-center">
        <a href="<?= PUBLIC_URL ?>/siparislerim.php" class="btn btn-ghost">Tüm Siparişlerimi Gör →</a>
      </div>
    </div>
  </div>

  <div class="text-center" style="margin-top:30px;">
    <a href="<?= PUBLIC_URL ?>/logout.php" class="btn btn-danger">Çıkış Yap</a>
  </div>

</div>

<?php require INCLUDES_PATH . '/footer.php'; ?>
