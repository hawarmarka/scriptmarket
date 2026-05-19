<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminPageTitle = 'Ödeme Ayarları';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız.');
        redirect(ADMIN_URL . '/odeme-ayarlari.php');
    }

    $textKeys = [
        // Havale
        'payment_havale_info',
        // PayPal
        'paypal_client_id', 'paypal_mode',
        // Stripe
        'stripe_publishable_key', 'stripe_secret_key',
        // PayTR
        'paytr_merchant_id', 'paytr_merchant_key', 'paytr_merchant_salt',
        // iyzico
        'iyzico_api_key', 'iyzico_secret_key', 'iyzico_base_url',
        // Kripto
        'payment_crypto_info',
        // Otomatik teslimat
        'auto_delivery_note',
    ];

    $checkboxKeys = [
        'payment_havale_enabled',
        'payment_paypal_enabled',
        'payment_stripe_enabled',
        'payment_paytr_enabled',
        'payment_iyzico_enabled',
        'payment_crypto_enabled',
        'paytr_test_mode',
        'auto_delivery',
    ];

    foreach ($textKeys as $key) {
        $val = trim($_POST[$key] ?? '');
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
            ->execute([$key, $val]);
    }

    foreach ($checkboxKeys as $key) {
        $val = isset($_POST[$key]) ? '1' : '0';
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
            ->execute([$key, $val]);
    }

    sm_setting_cache_flush();
    flash('success', '✓ Ödeme ayarları güncellendi.');
    redirect(ADMIN_URL . '/odeme-ayarlari.php');
}

require __DIR__ . '/_layout.php';

// Helper
function sw(string $key): string { return setting($key) === '1' ? 'checked' : ''; }
?>

<style>
.gateway-card {
  border-radius: var(--radius-lg);
  overflow: hidden;
  margin-bottom: 16px;
  border: 1px solid var(--glass-border);
  background: var(--glass-bg);
  backdrop-filter: blur(20px);
}
.gateway-head {
  padding: 18px 22px;
  display: flex;
  align-items: center;
  gap: 16px;
  border-bottom: 1px solid var(--glass-border);
  cursor: pointer;
  transition: background .2s;
}
.gateway-head:hover { background: rgba(255,255,255,.03); }
.gateway-logo {
  width: 52px; height: 52px;
  border-radius: 12px;
  display: grid; place-items: center;
  font-size: 26px;
  flex-shrink: 0;
}
.gateway-info { flex: 1; }
.gateway-info h4 { font-size: 16px; margin-bottom: 3px; }
.gateway-info p { font-size: 12.5px; color: var(--text-mute); }
.gateway-toggle {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 16px;
  background: var(--glass-bg);
  border: 1px solid var(--glass-border);
  border-radius: 100px;
  font-size: 13px; cursor: pointer;
  white-space: nowrap;
}
.gateway-toggle input { accent-color: var(--primary); width: 16px; height: 16px; }
.gateway-body {
  padding: 22px;
  display: none;
}
.gateway-body.show { display: block; }
.gateway-card.active .gateway-body { display: block; }

/* Toggle switch */
.switch-wrap { display: flex; align-items: center; gap: 12px; }
.switch {
  position: relative; width: 46px; height: 24px;
}
.switch input { opacity: 0; width: 0; height: 0; }
.slider {
  position: absolute; cursor: pointer; inset: 0;
  background: var(--glass-bg-strong);
  border: 1px solid var(--glass-border-strong);
  border-radius: 100px;
  transition: .3s;
}
.slider::before {
  position: absolute; content: '';
  height: 16px; width: 16px;
  left: 3px; bottom: 3px;
  background: var(--text-mute);
  border-radius: 50%;
  transition: .3s;
}
.switch input:checked + .slider { background: var(--primary); border-color: var(--primary); box-shadow: 0 0 14px rgba(99,102,241,.4); }
.switch input:checked + .slider::before { transform: translateX(22px); background: white; }

.api-field { font-family: 'JetBrains Mono', monospace; letter-spacing: .04em; }
</style>

<form method="post" id="paymentForm">
  <?= csrf_field() ?>

  <!-- Otomatik Teslimat -->
  <div class="admin-card" style="border-color:rgba(0,255,136,.3);background:rgba(0,255,136,.04);">
    <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap;">
      <div style="font-size:32px;">⚡</div>
      <div style="flex:1;min-width:240px;">
        <h3 style="color:var(--neon);margin-bottom:6px;">Otomatik Teslimat</h3>
        <p style="font-size:13.5px;color:var(--text-soft);line-height:1.7;margin-bottom:14px;">
          Açık olduğunda, müşteri "Ödedim" butonuna bastığı an sipariş otomatik onaylanır ve dosya/lisans anında aktif olur.
          Kapalıysa her siparişi sen manuel onaylarsın.
        </p>
        <label class="switch-wrap" style="cursor:pointer;">
          <span class="switch">
            <input type="checkbox" name="auto_delivery" value="1" <?= sw('auto_delivery') ?>>
            <span class="slider"></span>
          </span>
          <strong style="font-size:14px;"><?= setting('auto_delivery') === '1' ? '✓ Otomatik Teslimat Aktif' : 'Otomatik Teslimat Kapalı' ?></strong>
        </label>
      </div>
    </div>
  </div>

  <!-- 1. Banka Havalesi -->
  <div class="gateway-card <?= setting('payment_havale_enabled') === '1' ? 'active' : '' ?>">
    <div class="gateway-head" onclick="toggleGateway(this)">
      <div class="gateway-logo" style="background:linear-gradient(135deg,#1a3a2a,#0a4020);">🏦</div>
      <div class="gateway-info">
        <h4>Banka Havalesi / EFT</h4>
        <p>IBAN, hesap sahibi ve banka bilgilerini belirtin. En yaygın ödeme yöntemi.</p>
      </div>
      <label class="gateway-toggle" onclick="event.stopPropagation()">
        <input type="checkbox" name="payment_havale_enabled" value="1" <?= sw('payment_havale_enabled') ?>>
        <span>Aktif</span>
      </label>
    </div>
    <div class="gateway-body <?= setting('payment_havale_enabled') === '1' ? 'show' : '' ?>">
      <div class="form-group">
        <label class="form-label">Ödeme Talimatı (müşteriye gösterilir)</label>
        <textarea name="payment_havale_info" class="form-textarea" rows="5"><?= e(setting('payment_havale_info', '')) ?></textarea>
        <div class="form-help">Örnek: Ziraat Bankası / IBAN: TR00... / Alıcı: Ad Soyad</div>
      </div>
    </div>
  </div>

  <!-- 2. PayTR -->
  <div class="gateway-card <?= setting('payment_paytr_enabled') === '1' ? 'active' : '' ?>">
    <div class="gateway-head" onclick="toggleGateway(this)">
      <div class="gateway-logo" style="background:linear-gradient(135deg,#003366,#0055aa);">
        <svg width="28" height="28" viewBox="0 0 48 48" fill="none"><rect width="48" height="48" rx="10" fill="#0055aa"/><text y="32" x="6" font-size="22" font-weight="900" fill="white" font-family="Arial">P</text><text y="32" x="20" font-size="13" font-weight="700" fill="white" font-family="Arial">TR</text></svg>
      </div>
      <div class="gateway-info">
        <h4>PayTR</h4>
        <p>Türkiye'nin en yaygın sanal POS çözümü. Kredi/banka kartı, Türkçe arayüz, 3D Secure.</p>
      </div>
      <label class="gateway-toggle" onclick="event.stopPropagation()">
        <input type="checkbox" name="payment_paytr_enabled" value="1" <?= sw('payment_paytr_enabled') ?>>
        <span>Aktif</span>
      </label>
    </div>
    <div class="gateway-body <?= setting('payment_paytr_enabled') === '1' ? 'show' : '' ?>">
      <div style="padding:14px;background:rgba(0,85,170,.08);border-left:3px solid #0055aa;border-radius:8px;font-size:13px;color:var(--text-soft);line-height:1.7;margin-bottom:18px;">
        📋 API bilgileri için: <a href="https://www.paytr.com/magaza/hesabim" target="_blank" style="color:#60a5fa;">paytr.com/magaza/hesabim</a> → Mağaza Bilgileri → API Ayarları
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Merchant ID</label>
          <input type="text" name="paytr_merchant_id" class="form-input api-field" value="<?= e(setting('paytr_merchant_id')) ?>" placeholder="123456">
        </div>
        <div class="form-group">
          <label class="form-label">Merchant Key</label>
          <input type="password" name="paytr_merchant_key" class="form-input api-field" value="<?= e(setting('paytr_merchant_key')) ?>" placeholder="••••••••••••••••">
        </div>
        <div class="form-group form-group-full">
          <label class="form-label">Merchant Salt</label>
          <input type="password" name="paytr_merchant_salt" class="form-input api-field" value="<?= e(setting('paytr_merchant_salt')) ?>" placeholder="••••••••••••••••">
        </div>
      </div>
      <label class="switch-wrap" style="cursor:pointer;margin-top:8px;">
        <span class="switch">
          <input type="checkbox" name="paytr_test_mode" value="1" <?= sw('paytr_test_mode') ?>>
          <span class="slider"></span>
        </span>
        <span style="font-size:13.5px;">Test Modu <span style="color:var(--warning);font-size:12px;">(canlıya geçmeden önce kapat)</span></span>
      </label>
    </div>
  </div>

  <!-- 3. iyzico -->
  <div class="gateway-card <?= setting('payment_iyzico_enabled') === '1' ? 'active' : '' ?>">
    <div class="gateway-head" onclick="toggleGateway(this)">
      <div class="gateway-logo" style="background:linear-gradient(135deg,#1a1a3e,#2d2d6e);">
        <svg width="28" height="14" viewBox="0 0 80 28" fill="white"><text font-family="Arial" font-weight="900" font-size="24">iyz</text><text x="42" font-family="Arial" font-weight="900" font-size="24" fill="#7c3aed">ico</text></svg>
      </div>
      <div class="gateway-info">
        <h4>iyzico</h4>
        <p>Türkiye ve Avrupa için lider ödeme altyapısı. Kapsamlı kart desteği ve gelişmiş güvenlik.</p>
      </div>
      <label class="gateway-toggle" onclick="event.stopPropagation()">
        <input type="checkbox" name="payment_iyzico_enabled" value="1" <?= sw('payment_iyzico_enabled') ?>>
        <span>Aktif</span>
      </label>
    </div>
    <div class="gateway-body <?= setting('payment_iyzico_enabled') === '1' ? 'show' : '' ?>">
      <div style="padding:14px;background:rgba(124,58,237,.08);border-left:3px solid #7c3aed;border-radius:8px;font-size:13px;color:var(--text-soft);line-height:1.7;margin-bottom:18px;">
        📋 API bilgileri için: <a href="https://merchant.iyzipay.com" target="_blank" style="color:#a78bfa;">merchant.iyzipay.com</a> → Ayarlar → API Bilgileri
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">API Key</label>
          <input type="text" name="iyzico_api_key" class="form-input api-field" value="<?= e(setting('iyzico_api_key')) ?>" placeholder="sandbox-...">
        </div>
        <div class="form-group">
          <label class="form-label">Secret Key</label>
          <input type="password" name="iyzico_secret_key" class="form-input api-field" value="<?= e(setting('iyzico_secret_key')) ?>" placeholder="••••••••••••••••">
        </div>
        <div class="form-group form-group-full">
          <label class="form-label">API URL</label>
          <select name="iyzico_base_url" class="form-select">
            <option value="https://sandbox-api.iyzipay.com" <?= setting('iyzico_base_url') === 'https://sandbox-api.iyzipay.com' ? 'selected' : '' ?>>Sandbox (Test)</option>
            <option value="https://api.iyzipay.com" <?= setting('iyzico_base_url') === 'https://api.iyzipay.com' ? 'selected' : '' ?>>Production (Canlı)</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- 4. Stripe -->
  <div class="gateway-card <?= setting('payment_stripe_enabled') === '1' ? 'active' : '' ?>">
    <div class="gateway-head" onclick="toggleGateway(this)">
      <div class="gateway-logo" style="background:linear-gradient(135deg,#1a0a5e,#635bff);">
        <svg width="28" height="12" viewBox="0 0 60 25" fill="white"><text font-family="Arial" font-weight="900" font-size="22">S</text><text x="16" font-family="Arial" font-weight="400" font-size="14" y="-2">tripe</text></svg>
      </div>
      <div class="gateway-info">
        <h4>Stripe</h4>
        <p>Uluslararası ödemeler için en güçlü altyapı. 135+ para birimi, Apple Pay, Google Pay.</p>
      </div>
      <label class="gateway-toggle" onclick="event.stopPropagation()">
        <input type="checkbox" name="payment_stripe_enabled" value="1" <?= sw('payment_stripe_enabled') ?>>
        <span>Aktif</span>
      </label>
    </div>
    <div class="gateway-body <?= setting('payment_stripe_enabled') === '1' ? 'show' : '' ?>">
      <div style="padding:14px;background:rgba(99,91,255,.08);border-left:3px solid #635bff;border-radius:8px;font-size:13px;color:var(--text-soft);line-height:1.7;margin-bottom:18px;">
        📋 API bilgileri için: <a href="https://dashboard.stripe.com/apikeys" target="_blank" style="color:#818cf8;">dashboard.stripe.com/apikeys</a>
        Test için <code style="background:rgba(255,255,255,.1);padding:2px 6px;border-radius:4px;">pk_test_</code> ile başlayan anahtarları kullanın.
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Publishable Key (pk_)</label>
          <input type="text" name="stripe_publishable_key" class="form-input api-field" value="<?= e(setting('stripe_publishable_key')) ?>" placeholder="pk_test_...">
        </div>
        <div class="form-group">
          <label class="form-label">Secret Key (sk_)</label>
          <input type="password" name="stripe_secret_key" class="form-input api-field" value="<?= e(setting('stripe_secret_key')) ?>" placeholder="sk_test_...">
        </div>
      </div>
      <div style="padding:12px;background:rgba(245,158,11,.06);border-left:3px solid var(--warning);border-radius:8px;font-size:12.5px;color:var(--text-soft);line-height:1.6;margin-top:10px;">
        ⚠ Stripe entegrasyonu için Webhook endpoint: <code style="background:rgba(255,255,255,.1);padding:2px 6px;border-radius:4px;"><?= PUBLIC_URL ?>/stripe-webhook.php</code>
      </div>
    </div>
  </div>

  <!-- 5. PayPal -->
  <div class="gateway-card <?= setting('payment_paypal_enabled') === '1' ? 'active' : '' ?>">
    <div class="gateway-head" onclick="toggleGateway(this)">
      <div class="gateway-logo" style="background:linear-gradient(135deg,#003087,#009cde);">
        <svg width="30" height="20" viewBox="0 0 75 50" fill="white"><text font-family="Arial" font-weight="900" font-size="24">P</text><text x="16" font-family="Arial" font-weight="400" font-size="15">ayPal</text></svg>
      </div>
      <div class="gateway-info">
        <h4>PayPal</h4>
        <p>Dünya geneli 400M+ kullanıcı. Uluslararası müşteriler için ideal. Hızlı ödeme butonu.</p>
      </div>
      <label class="gateway-toggle" onclick="event.stopPropagation()">
        <input type="checkbox" name="payment_paypal_enabled" value="1" <?= sw('payment_paypal_enabled') ?>>
        <span>Aktif</span>
      </label>
    </div>
    <div class="gateway-body <?= setting('payment_paypal_enabled') === '1' ? 'show' : '' ?>">
      <div style="padding:14px;background:rgba(0,156,222,.08);border-left:3px solid #009cde;border-radius:8px;font-size:13px;color:var(--text-soft);line-height:1.7;margin-bottom:18px;">
        📋 Client ID için: <a href="https://developer.paypal.com/dashboard/" target="_blank" style="color:#38bdf8;">developer.paypal.com/dashboard</a> → Apps & Credentials
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Client ID</label>
          <input type="text" name="paypal_client_id" class="form-input api-field" value="<?= e(setting('paypal_client_id')) ?>" placeholder="AXxxxxxx...">
        </div>
        <div class="form-group">
          <label class="form-label">Ortam</label>
          <select name="paypal_mode" class="form-select">
            <option value="sandbox" <?= setting('paypal_mode') === 'sandbox' ? 'selected' : '' ?>>Sandbox (Test)</option>
            <option value="live" <?= setting('paypal_mode') === 'live' ? 'selected' : '' ?>>Live (Canlı)</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- 6. Kripto -->
  <div class="gateway-card <?= setting('payment_crypto_enabled') === '1' ? 'active' : '' ?>">
    <div class="gateway-head" onclick="toggleGateway(this)">
      <div class="gateway-logo" style="background:linear-gradient(135deg,#1a1a00,#2a2a00);">₿</div>
      <div class="gateway-info">
        <h4>Kripto Para (USDT / BTC)</h4>
        <p>USDT TRC20, BTC ve diğer kripto para birimlerini kabul et.</p>
      </div>
      <label class="gateway-toggle" onclick="event.stopPropagation()">
        <input type="checkbox" name="payment_crypto_enabled" value="1" <?= sw('payment_crypto_enabled') ?>>
        <span>Aktif</span>
      </label>
    </div>
    <div class="gateway-body <?= setting('payment_crypto_enabled') === '1' ? 'show' : '' ?>">
      <div class="form-group">
        <label class="form-label">Cüzdan Adresleri (müşteriye gösterilir)</label>
        <textarea name="payment_crypto_info" class="form-textarea" rows="4" style="font-family:'JetBrains Mono',monospace;"><?= e(setting('payment_crypto_info', '')) ?></textarea>
        <div class="form-help">Örnek: USDT (TRC20): TXxxxxxxxxx / BTC: bc1xxxxxxxxx</div>
      </div>
    </div>
  </div>

  <div style="padding:18px;background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:12px;display:flex;gap:10px;justify-content:space-between;align-items:center;flex-wrap:wrap;">
    <div style="font-size:13px;color:var(--text-mute);">
      Kaydetmeden önce tüm API bilgilerini doğruladığından emin ol.
    </div>
    <button type="submit" class="btn btn-primary btn-lg">💾 Ödeme Ayarlarını Kaydet</button>
  </div>
</form>

<script>
function toggleGateway(head) {
  const card = head.parentElement;
  const body = card.querySelector('.gateway-body');
  if (body) {
    body.classList.toggle('show');
    card.classList.toggle('active');
  }
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
