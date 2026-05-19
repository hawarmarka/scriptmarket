<?php
/**
 * Ödeme Sayfası — Tüm Gateway'ler
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

require_login();

$totals = cart_totals($pdo);

if (empty($totals['items'])) {
    flash('warning', 'Sepetiniz boş.');
    redirect(PUBLIC_URL . '/sepet.php');
}

// Aktif ödeme yöntemlerini topla
$gateways = [];

if (setting('payment_havale_enabled') === '1') {
    $gateways['havale'] = [
        'label'  => 'Banka Havalesi / EFT',
        'icon'   => '🏦',
        'desc'   => 'IBAN ile manuel transfer. Onay sonrası anında teslimat.',
        'badge'  => null,
    ];
}
if (setting('payment_paytr_enabled') === '1' && setting('paytr_merchant_id')) {
    $gateways['paytr'] = [
        'label'  => 'Kredi / Banka Kartı',
        'icon'   => '💳',
        'desc'   => 'PayTR güvencesiyle tüm kartlar. 3D Secure & taksit.',
        'badge'  => 'PayTR',
    ];
}
if (setting('payment_iyzico_enabled') === '1' && setting('iyzico_api_key')) {
    $gateways['iyzico'] = [
        'label'  => 'Kredi / Banka Kartı',
        'icon'   => '💳',
        'desc'   => 'iyzico altyapısıyla güvenli ödeme. Tüm kartlar.',
        'badge'  => 'iyzico',
    ];
}
if (setting('payment_stripe_enabled') === '1' && setting('stripe_publishable_key')) {
    $gateways['stripe'] = [
        'label'  => 'Kart / Apple Pay / Google Pay',
        'icon'   => '💳',
        'desc'   => 'Stripe altyapısıyla uluslararası ödeme.',
        'badge'  => 'Stripe',
    ];
}
if (setting('payment_paypal_enabled') === '1' && setting('paypal_client_id')) {
    $gateways['paypal'] = [
        'label'  => 'PayPal',
        'icon'   => '🔵',
        'desc'   => 'PayPal hesabınız veya kartınızla güvenli ödeme.',
        'badge'  => 'PayPal',
    ];
}
if (setting('payment_crypto_enabled') === '1') {
    $gateways['crypto'] = [
        'label'  => 'Kripto Para',
        'icon'   => '₿',
        'desc'   => 'USDT (TRC20), BTC ve diğer kripto paralar.',
        'badge'  => 'USDT/BTC',
    ];
}

if (empty($gateways)) {
    // Fallback
    $gateways['havale'] = ['label' => 'Banka Havalesi', 'icon' => '🏦', 'desc' => 'Manuel ödeme', 'badge' => null];
}

// POST — sipariş oluştur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız.');
        redirect(PUBLIC_URL . '/odeme.php');
    }

    $method = $_POST['payment_method'] ?? '';
    if (!isset($gateways[$method])) {
        flash('error', 'Geçerli bir ödeme yöntemi seçiniz.');
        redirect(PUBLIC_URL . '/odeme.php');
    }

    $customerNote = trim($_POST['customer_note'] ?? '');

    try {
        $pdo->beginTransaction();

        $orderNumber = generate_order_number();
        $orderStmt = $pdo->prepare(
            "INSERT INTO orders (order_number, user_id, subtotal, discount, coupon_code, total, payment_method, payment_status, customer_note)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'odeme_bekliyor', ?)"
        );
        $orderStmt->execute([
            $orderNumber, $_SESSION['user_id'],
            $totals['subtotal'], $totals['discount'],
            $totals['coupon']['code'] ?? null,
            $totals['total'], $method,
            $customerNote ?: null,
        ]);
        $orderId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            "INSERT INTO order_items (order_id, script_id, script_title, price, license_label, license_key, download_token)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        foreach ($totals['items'] as $item) {
            $itemStmt->execute([
                $orderId, $item['id'], $item['title'],
                active_price($item), ($item['cart_license_label'] ?? $item['license_type']), generate_license_key(), generate_download_token(),
            ]);
        }

        if (!empty($totals['coupon']['code'])) {
            $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?")
                ->execute([$totals['coupon']['code']]);
        }

        cart_clear();
        $pdo->commit();

        // PayTR için özel yönlendirme
        if ($method === 'paytr') {
            redirect(PUBLIC_URL . '/odeme-paytr.php?no=' . urlencode($orderNumber));
        }

        // Stripe için özel yönlendirme
        if ($method === 'stripe') {
            redirect(PUBLIC_URL . '/odeme-stripe.php?no=' . urlencode($orderNumber));
        }

        // PayPal için özel yönlendirme
        if ($method === 'paypal') {
            redirect(PUBLIC_URL . '/odeme-paypal.php?no=' . urlencode($orderNumber));
        }

        flash('success', 'Siparişiniz oluşturuldu!');
        redirect(PUBLIC_URL . '/siparis-basarili.php?no=' . urlencode($orderNumber));

    } catch (Throwable $e) {
        $pdo->rollBack();
        if (APP_DEBUG) flash('error', 'Hata: ' . $e->getMessage());
        else flash('error', 'Sipariş oluşturulurken hata oluştu. Tekrar deneyin.');
        redirect(PUBLIC_URL . '/odeme.php');
    }
}

$user = current_user($pdo);
$pageTitle = 'Ödeme — ' . setting('site_name');
require INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="breadcrumb"><a href="<?= PUBLIC_URL ?>/index.php">Ana Sayfa</a> / <a href="<?= PUBLIC_URL ?>/sepet.php">Sepet</a> / Ödeme</div>
  <h1>Güvenli <em>Ödeme</em></h1>
</div>

<div class="cart-wrap">
  <form method="post" id="checkoutForm">
    <?= csrf_field() ?>
    <input type="hidden" name="place_order" value="1">

    <!-- Müşteri bilgisi -->
    <div class="glass-card mb-3">
      <h3 style="margin-bottom:16px;">👤 Müşteri Bilgileri</h3>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Ad Soyad</label>
          <input type="text" class="form-input" value="<?= e($user['name']) ?>" disabled>
        </div>
        <div class="form-group">
          <label class="form-label">E-posta</label>
          <input type="email" class="form-input" value="<?= e($user['email']) ?>" disabled>
          <div class="form-help">Lisans ve indirme linki bu adrese iletilir.</div>
        </div>
      </div>
    </div>

    <!-- Ödeme yöntemi -->
    <div class="glass-card mb-3">
      <h3 style="margin-bottom:18px;">💳 Ödeme Yöntemi Seç</h3>

      <div class="payment-methods">
        <?php $first = true; foreach ($gateways as $key => $gw): ?>
          <label class="payment-method <?= $first ? 'selected' : '' ?>" style="cursor:pointer;">
            <input type="radio" name="payment_method" value="<?= e($key) ?>" <?= $first ? 'checked' : '' ?> required style="accent-color:var(--primary);">
            <div class="payment-method-icon"><?= $gw['icon'] ?></div>
            <div class="payment-method-info" style="flex:1;">
              <h4 style="display:flex;align-items:center;gap:8px;">
                <?= e($gw['label']) ?>
                <?php if ($gw['badge']): ?>
                  <span style="font-family:'JetBrains Mono',monospace;font-size:10px;padding:2px 8px;background:var(--grad-primary);color:white;border-radius:100px;"><?= e($gw['badge']) ?></span>
                <?php endif; ?>
              </h4>
              <p style="font-size:12.5px;color:var(--text-mute);"><?= e($gw['desc']) ?></p>
            </div>
            <?php if ($key === 'paytr' || $key === 'iyzico' || $key === 'stripe'): ?>
              <div style="display:flex;gap:4px;margin-left:auto;">
                <span style="padding:2px 6px;background:rgba(255,255,255,.08);border-radius:4px;font-size:10px;color:var(--text-mute);">VISA</span>
                <span style="padding:2px 6px;background:rgba(255,255,255,.08);border-radius:4px;font-size:10px;color:var(--text-mute);">MC</span>
                <span style="padding:2px 6px;background:rgba(255,255,255,.08);border-radius:4px;font-size:10px;color:var(--text-mute);">3D</span>
              </div>
            <?php endif; ?>
          </label>

          <?php if ($key === 'havale' && setting('payment_havale_info')): ?>
            <div class="payment-details <?= $first ? '' : '' ?>" style="<?= $first ? 'display:block' : '' ?>">
              <pre style="white-space:pre-wrap;font-family:'JetBrains Mono',monospace;font-size:13px;line-height:1.7;color:var(--text-soft);"><?= e(setting('payment_havale_info')) ?></pre>
            </div>
          <?php elseif ($key === 'paytr'): ?>
            <div class="payment-details" style="<?= $first ? 'display:block' : '' ?>">
              <div style="display:flex;align-items:center;gap:10px;color:var(--text-soft);font-size:13.5px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Sipariş oluştuktan sonra PayTR ödeme sayfasına yönlendirileceksiniz.
                <strong style="color:var(--text);">Tüm kartlar desteklenir.</strong>
              </div>
            </div>
          <?php elseif ($key === 'iyzico'): ?>
            <div class="payment-details" style="<?= $first ? 'display:block' : '' ?>">
              <div style="display:flex;align-items:center;gap:10px;color:var(--text-soft);font-size:13.5px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                iyzico güvencesiyle ödeme yapacaksınız. Kart bilgileriniz şifrelenmiş olarak iletilir.
              </div>
            </div>
          <?php elseif ($key === 'stripe'): ?>
            <div class="payment-details" style="<?= $first ? 'display:block' : '' ?>">
              <div style="display:flex;align-items:center;gap:10px;color:var(--text-soft);font-size:13.5px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Stripe ödeme sayfasına yönlendirileceksiniz. Apple Pay ve Google Pay da desteklenir.
              </div>
            </div>
          <?php elseif ($key === 'paypal'): ?>
            <div class="payment-details" style="<?= $first ? 'display:block' : '' ?>">
              <div style="display:flex;align-items:center;gap:10px;color:var(--text-soft);font-size:13.5px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                PayPal hesabınız veya kartınızla ödeme yapabilirsiniz.
              </div>
            </div>
          <?php elseif ($key === 'crypto' && setting('payment_crypto_info')): ?>
            <div class="payment-details" style="<?= $first ? 'display:block' : '' ?>">
              <pre style="white-space:pre-wrap;font-family:'JetBrains Mono',monospace;font-size:13px;line-height:1.7;color:var(--neon);text-shadow:0 0 8px rgba(0,255,136,.3);"><?= e(setting('payment_crypto_info')) ?></pre>
            </div>
          <?php endif; ?>

        <?php $first = false; endforeach; ?>
      </div>

      <div class="form-group mt-3">
        <label class="form-label">Sipariş Notu (opsiyonel)</label>
        <textarea name="customer_note" class="form-textarea" rows="2" placeholder="Ek not veya istek..."></textarea>
      </div>
    </div>

    <!-- Güvenlik notu -->
    <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;background:rgba(16,185,129,.06);border:1px solid rgba(16,185,129,.2);border-radius:var(--radius);margin-bottom:16px;">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      <div style="font-size:13px;color:var(--text-soft);">
        <strong style="color:var(--success);">256-bit SSL Şifreleme</strong> ile korunan güvenli ödeme ortamı.
        Kart bilgileriniz sitemizde saklanmaz.
      </div>
    </div>
  </form>

  <!-- Order Summary -->
  <aside class="cart-summary">
    <h3>Sipariş Özeti</h3>

    <?php foreach ($totals['items'] as $item): ?>
      <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--glass-border);font-size:13.5px;">
        <span style="flex:1;color:var(--text-soft);padding-right:8px;"><?= e(mb_strimwidth($item['title'], 0, 36, '…')) ?></span>
        <span style="font-weight:500;white-space:nowrap;"><?= format_price(active_price($item)) ?></span>
      </div>
    <?php endforeach; ?>

    <div class="summary-row" style="margin-top:14px;">
      <span>Ara Toplam</span>
      <span class="v"><?= format_price($totals['subtotal']) ?></span>
    </div>
    <?php if ($totals['coupon']): ?>
      <div class="summary-row discount">
        <span>İndirim</span>
        <span class="v">- <?= format_price($totals['discount']) ?></span>
      </div>
    <?php endif; ?>
    <div class="summary-row total">
      <span>TOPLAM</span>
      <span><?= format_price($totals['total']) ?></span>
    </div>

    <button type="submit" form="checkoutForm" class="btn btn-primary btn-block btn-lg mt-3">
      🔒 Siparişi Onayla
    </button>

    <div style="margin-top:14px;font-size:12px;color:var(--text-dim);text-align:center;line-height:1.7;">
      Onayladığında <span style="color:var(--accent);">lisans anahtarın</span> ve
      <span style="color:var(--accent);">indirme linkin</span> hazır olur.
    </div>
  </aside>
</div>

<style>
.payment-method { transition: all .2s; }
.payment-method:hover { border-color: rgba(99,102,241,.4); }
.payment-method.selected { border-color: var(--primary); background: rgba(99,102,241,.08); }
</style>

<script>
// Ödeme yöntemi seçimi
document.querySelectorAll('.payment-method').forEach(pm => {
  pm.addEventListener('click', () => {
    document.querySelectorAll('.payment-method').forEach(p => {
      p.classList.remove('selected');
      const next = p.nextElementSibling;
      if (next && next.classList.contains('payment-details')) next.style.display = 'none';
    });
    pm.classList.add('selected');
    const radio = pm.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
    const next = pm.nextElementSibling;
    if (next && next.classList.contains('payment-details')) next.style.display = 'block';
  });
});
</script>

<?php require INCLUDES_PATH . '/footer.php'; ?>
