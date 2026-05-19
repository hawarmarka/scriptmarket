<?php
/**
 * Sepet sayfası
 * AJAX action'ları: ?action=add (POST), ?action=remove, ?action=apply_coupon, ?action=remove_coupon
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

$action = $_GET['action'] ?? '';

// ---- AJAX: Sepete ekle ----
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $scriptId = (int)($_POST['script_id'] ?? 0);
    if ($scriptId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz ürün.']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM scripts WHERE id = ? AND is_active = 1");
    $stmt->execute([$scriptId]);
    $scriptRow = $stmt->fetch();
    if (!$scriptRow) {
        echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı.']);
        exit;
    }
    $options = script_license_options($scriptRow);
    $licenseKey = $_POST['license_option'] ?? array_key_first($options);
    if (!isset($options[$licenseKey])) $licenseKey = array_key_first($options);
    $opt = $options[$licenseKey];
    cart_add($scriptId, $licenseKey, $opt['label'], (float)$opt['price']);
    echo json_encode(['success' => true, 'count' => cart_count(), 'message' => 'Sepete eklendi: ' . $opt['label']]);
    exit;
}

// ---- Sepetten kaldır ----
if ($action === 'remove' && isset($_GET['id'])) {
    cart_remove((int)$_GET['id']);
    flash('info', 'Ürün sepetten kaldırıldı.');
    redirect(PUBLIC_URL . '/sepet.php');
}

// ---- Kupon uygula ----
if ($action === 'apply_coupon' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız.');
        redirect(PUBLIC_URL . '/sepet.php');
    }
    $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    if (!$code) {
        flash('error', 'Kupon kodu giriniz.');
        redirect(PUBLIC_URL . '/sepet.php');
    }
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    if (!$coupon) {
        flash('error', 'Geçersiz kupon kodu.');
    } elseif ($coupon['valid_until'] && strtotime($coupon['valid_until']) < time()) {
        flash('error', 'Bu kuponun süresi dolmuş.');
    } elseif ($coupon['max_usage'] !== null && $coupon['used_count'] >= $coupon['max_usage']) {
        flash('error', 'Bu kupon kullanım limitine ulaşmış.');
    } else {
        $totals = cart_totals($pdo);
        if ($totals['subtotal'] < (float)$coupon['min_order_total']) {
            flash('error', 'Bu kupon için minimum sepet tutarı: ' . format_price((float)$coupon['min_order_total']));
        } else {
            $_SESSION['applied_coupon'] = $coupon;
            flash('success', 'Kupon uygulandı: ' . $code);
        }
    }
    redirect(PUBLIC_URL . '/sepet.php');
}

// ---- Kupon kaldır ----
if ($action === 'remove_coupon') {
    unset($_SESSION['applied_coupon']);
    flash('info', 'Kupon kaldırıldı.');
    redirect(PUBLIC_URL . '/sepet.php');
}

$totals = cart_totals($pdo);

$pageTitle = 'Sepetim — ' . setting('site_name');
require INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="breadcrumb"><a href="<?= PUBLIC_URL ?>/index.php">Ana Sayfa</a> / Sepet</div>
  <h1>Sepetim</h1>
  <p class="text-mute"><?= count($totals['items']) ?> ürün</p>
</div>

<?php if (empty($totals['items'])): ?>
  <div class="container">
    <div class="cart-empty">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
      <h3>Sepetin boş</h3>
      <p>Henüz sepetine ürün eklemedin. Hemen scriptleri keşfet!</p>
      <a href="<?= PUBLIC_URL ?>/scripts.php" class="btn btn-primary">Scriptleri Keşfet</a>
    </div>
  </div>
<?php else: ?>

  <div class="cart-wrap">

    <div>
      <div class="cart-items">
        <?php foreach ($totals['items'] as $item): ?>
          <div class="cart-item">
            <div class="cart-item-img">
              <?php if (!empty($item['cover_image'])): ?>
                <img src="<?= e(script_image_url($item['cover_image'])) ?>" alt="">
              <?php else: ?>
                <div class="script-cover-fallback" style="font-size:32px;height:100%;"><?= e(mb_strtoupper(mb_substr($item['title'], 0, 1))) ?></div>
              <?php endif; ?>
            </div>
            <div class="cart-item-info">
              <h4><a href="<?= PUBLIC_URL ?>/script-detay.php?slug=<?= e($item['slug']) ?>"><?= e($item['title']) ?></a></h4>
              <p><?= e(mb_strimwidth($item['short_description'] ?? '', 0, 80, '…')) ?></p>
              <p style="margin-top:6px;font-family:'JetBrains Mono',monospace;color:var(--cyan);font-size:11.5px;">DİJİTAL TESLİMAT · <?= e($item['cart_license_label'] ?? $item['license_type']) ?></p>
            </div>
            <div style="text-align:right;">
              <div class="cart-item-price"><?= format_price(active_price($item)) ?></div>
              <a href="?action=remove&id=<?= (int)$item['id'] ?>" class="btn btn-sm btn-danger mt-3" onclick="return confirm('Bu ürünü sepetten kaldırmak istiyor musun?')">Kaldır</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <aside class="cart-summary">
      <h3>Sipariş Özeti</h3>

      <div class="summary-row">
        <span>Ara Toplam</span>
        <span class="v"><?= format_price($totals['subtotal']) ?></span>
      </div>

      <?php if ($totals['coupon']): ?>
        <div class="summary-row discount">
          <span>Kupon: <strong><?= e($totals['coupon']['code']) ?></strong>
            <a href="?action=remove_coupon" style="font-size:11px;color:var(--text-dim);">[kaldır]</a></span>
          <span class="v">- <?= format_price($totals['discount']) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" action="?action=apply_coupon" class="coupon-row">
        <?= csrf_field() ?>
        <input type="text" class="form-input" name="coupon_code" placeholder="Kupon kodu" autocomplete="off">
        <button type="submit" class="btn btn-secondary">Uygula</button>
      </form>

      <div class="summary-row total">
        <span>TOPLAM</span>
        <span><?= format_price($totals['total']) ?></span>
      </div>

      <a href="<?= PUBLIC_URL ?>/odeme.php" class="btn btn-primary btn-block btn-lg mt-3">Ödemeye Geç →</a>

      <div style="margin-top:18px;font-size:12.5px;color:var(--text-mute);text-align:center;line-height:1.6;">
        🔒 256-bit SSL şifreli güvenli ödeme<br>
        Ödeme sonrası anlık dijital teslimat
      </div>
    </aside>
  </div>

<?php endif; ?>

<?php require INCLUDES_PATH . '/footer.php'; ?>
