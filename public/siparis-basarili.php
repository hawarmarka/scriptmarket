<?php
/**
 * Sipariş başarılı sayfası — sipariş özeti, lisans anahtarı, ödeme talimatları
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

require_login();

$orderNo = trim($_GET['no'] ?? '');
if (!$orderNo) redirect(PUBLIC_URL . '/hesabim.php');

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->execute([$orderNo, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'Sipariş bulunamadı.');
    redirect(PUBLIC_URL . '/hesabim.php');
}

$itemStmt = $pdo->prepare(
    "SELECT oi.*, s.slug, s.cover_image
     FROM order_items oi
     LEFT JOIN scripts s ON s.id = oi.script_id
     WHERE oi.order_id = ?"
);
$itemStmt->execute([$order['id']]);
$items = $itemStmt->fetchAll();

// Ödeme yöntemi açıklaması
$paymentInfos = [
    'havale' => setting('payment_havale_info'),
    'crypto' => setting('payment_crypto_info'),
];
$paymentLabels = [
    'havale' => 'Banka Havalesi / EFT',
    'paytr' => 'Kredi / Banka Kartı (PayTR)',
    'stripe' => 'Kart / Apple Pay / Google Pay (Stripe)',
    'paypal' => 'PayPal',
    'iyzico' => 'Kredi / Banka Kartı (iyzico)',
    'crypto' => 'Kripto Para (USDT/BTC)',
];
$isPaid = in_array($order['payment_status'], ['onaylandi', 'teslim_edildi'], true);

$pageTitle = 'Siparişiniz Alındı — ' . setting('site_name');
require INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding: 60px 24px; max-width: 900px;">

  <div class="text-center mb-4">
    <div style="width:80px;height:80px;border-radius:50%;background:rgba(16,185,129,.15);border:2px solid var(--success);display:grid;place-items:center;margin:0 auto 20px;">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h1>Siparişiniz Alındı!</h1>
    <p class="text-mute">Sipariş numaranız: <strong style="color:var(--cyan);font-family:'JetBrains Mono',monospace;"><?= e($order['order_number']) ?></strong></p>
  </div>

  <div class="detail-card mb-3">
    <h3 style="margin-bottom: 16px;">📋 Sipariş Detayları</h3>
    <table class="meta-table">
      <tr><td>Sipariş No</td><td class="mono"><?= e($order['order_number']) ?></td></tr>
      <tr><td>Tarih</td><td><?= format_date($order['created_at'], true) ?></td></tr>
      <tr><td>Ödeme Yöntemi</td><td><?= e($paymentLabels[$order['payment_method']] ?? $order['payment_method']) ?></td></tr>
      <tr><td>Durum</td><td><?php [$lbl, $clr] = status_badge($order['payment_status']); ?><span class="badge badge-<?= $clr ?>"><?= e($lbl) ?></span></td></tr>
      <tr><td>Toplam</td><td style="font-size:18px;font-weight:600;color:var(--cyan);"><?= format_price((float)$order['total']) ?></td></tr>
    </table>
  </div>

  <div class="detail-card mb-3">
    <h3 style="margin-bottom: 16px;">💳 Ödeme Durumu</h3>
    <?php if ($isPaid): ?>
      <div style="padding:18px;background:rgba(16,185,129,.10);border-left:3px solid var(--success);border-radius:8px;margin-bottom:16px;">
        <strong style="color:var(--success);">Ödeme Onaylandı</strong><br>
        <span style="font-size:14px;color:var(--text-soft);">Ürün lisanslarınız ve indirme bağlantılarınız aşağıda aktif edildi.</span>
      </div>
    <?php else: ?>
      <div style="padding:18px;background:rgba(245,158,11,.08);border-left:3px solid var(--warning);border-radius:8px;margin-bottom:16px;">
        <strong style="color:var(--warning);">Ödeme Bekleniyor</strong><br>
        <span style="font-size:14px;color:var(--text-soft);">Ödemenizi yapıp aşağıdaki "Ödeme Yaptım" butonuna basın. Onay sonrası ürünleriniz aktif olacak.</span>
      </div>
      <?php if (!empty($paymentInfos[$order['payment_method']])): ?>
        <div class="payment-details" style="display:block;"><?= e($paymentInfos[$order['payment_method']]) ?>

Açıklama / Referans: <?= e($order['order_number']) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= PUBLIC_URL ?>/odeme-bildirimi.php" style="margin-top:20px;" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
        <div class="form-group">
          <label class="form-label">İşlem No / Referans (opsiyonel)</label>
          <input type="text" name="transaction_id" class="form-input" placeholder="Örn: TX-12345 veya ödeme işlem no">
        </div>
        <div class="form-group">
          <label class="form-label">Mesaj (opsiyonel)</label>
          <textarea name="customer_message" class="form-textarea" placeholder="Eklemek istediğiniz bilgi varsa yazın..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary">✓ Ödemeyi Yaptım — Bildir</button>
      </form>
    <?php endif; ?>
  </div>

  <div class="detail-card mb-3">
    <h3 style="margin-bottom: 16px;">🔑 Ürünleriniz ve Lisanslarınız</h3>
    <p class="text-mute" style="margin-bottom:20px;font-size:14px;">Lisans anahtarlarınız ve indirme linkleriniz ödeme onaylandıktan sonra aktif olacaktır.</p>

    <?php foreach ($items as $item): ?>
      <div style="padding:18px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:12px;">
        <h4 style="margin-bottom:10px;"><?= e($item['script_title']) ?></h4>
        <div style="font-size:12px;color:var(--text-mute);text-transform:uppercase;letter-spacing:.1em;margin-bottom:6px;">Lisans Anahtarı</div>
        <div class="license-box">
          <?= e($item['license_key']) ?>
          <button class="license-copy" data-key="<?= e($item['license_key']) ?>">Kopyala</button>
        </div>
        <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;">
          <?php if ($order['payment_status'] === 'teslim_edildi' || $order['payment_status'] === 'onaylandi'): ?>
            <a href="<?= PUBLIC_URL ?>/indir.php?token=<?= e($item['download_token']) ?>" class="btn btn-success">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              İndir
            </a>
          <?php else: ?>
            <button class="btn btn-secondary" disabled>⏳ Ödeme Onayı Bekleniyor</button>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="text-center mt-4">
    <a href="<?= PUBLIC_URL ?>/siparislerim.php" class="btn btn-ghost">Tüm Siparişlerim</a>
    <a href="<?= PUBLIC_URL ?>/scripts.php" class="btn btn-primary">Alışverişe Devam Et</a>
  </div>
</div>

<?php require INCLUDES_PATH . '/footer.php'; ?>
