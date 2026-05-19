<?php
/**
 * Siparişlerim
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

require_login();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Order items
$ordersWithItems = [];
foreach ($orders as $o) {
    $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemStmt->execute([$o['id']]);
    $o['items'] = $itemStmt->fetchAll();
    $ordersWithItems[] = $o;
}

$pageTitle = 'Siparişlerim — ' . setting('site_name');
require INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="breadcrumb">
    <a href="<?= PUBLIC_URL ?>/index.php">Ana Sayfa</a> /
    <a href="<?= PUBLIC_URL ?>/hesabim.php">Hesabım</a> / Siparişlerim
  </div>
  <h1>Siparişlerim</h1>
  <p class="text-mute"><?= count($ordersWithItems) ?> sipariş</p>
</div>

<div class="container" style="max-width: 1100px;">

  <?php if (empty($ordersWithItems)): ?>
    <div class="empty-state">
      <h3>Henüz siparişin yok</h3>
      <p>Beğendiğin ilk scripti sepete eklemeye ne dersin?</p>
      <a href="<?= PUBLIC_URL ?>/scripts.php" class="btn btn-primary">Scriptleri Keşfet</a>
    </div>
  <?php else: ?>

    <?php foreach ($ordersWithItems as $o): [$lbl, $clr] = status_badge($o['payment_status']); ?>
      <div class="detail-card mb-3">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border);">
          <div>
            <div style="font-family:'JetBrains Mono',monospace;color:var(--cyan);font-size:14px;letter-spacing:.05em;"><?= e($o['order_number']) ?></div>
            <div style="font-size:13px;color:var(--text-mute);margin-top:4px;"><?= format_date($o['created_at'], true) ?></div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:20px;font-weight:600;color:var(--text);"><?= format_price((float)$o['total']) ?></div>
            <span class="badge badge-<?= $clr ?>" style="margin-top:4px;display:inline-block;"><?= e($lbl) ?></span>
          </div>
        </div>

        <?php foreach ($o['items'] as $item): ?>
          <div style="padding:14px 0;border-bottom:1px solid var(--border);">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
              <div style="flex:1;min-width:240px;">
                <h4 style="font-size:15px;margin-bottom:6px;"><?= e($item['script_title']) ?></h4>
                <div style="font-size:12px;color:var(--text-mute);">Fiyat: <?= format_price((float)$item['price']) ?> · İndirme: <?= (int)$item['download_count'] ?></div>
              </div>
              <div>
                <?php if ($o['payment_status'] === 'teslim_edildi' || $o['payment_status'] === 'onaylandi'): ?>
                  <a href="<?= PUBLIC_URL ?>/indir.php?token=<?= e($item['download_token']) ?>" class="btn btn-success btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    İndir
                  </a>
                <?php else: ?>
                  <span class="badge badge-warning">⏳ Onay Bekleniyor</span>
                <?php endif; ?>
              </div>
            </div>
            <div class="license-box" style="margin-top:10px;font-size:13px;">
              <?= e($item['license_key']) ?>
              <button class="license-copy" data-key="<?= e($item['license_key']) ?>">Kopyala</button>
            </div>
          </div>
        <?php endforeach; ?>

        <div style="margin-top:16px;text-align:right;">
          <a href="<?= PUBLIC_URL ?>/siparis-basarili.php?no=<?= e($o['order_number']) ?>" class="btn btn-ghost btn-sm">Sipariş Detayı</a>
        </div>
      </div>
    <?php endforeach; ?>

  <?php endif; ?>
</div>

<?php require INCLUDES_PATH . '/footer.php'; ?>
