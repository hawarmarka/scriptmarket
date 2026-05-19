<?php
/**
 * PayTR iframe ödeme sayfası
 * Sipariş oluşturulduktan sonra buraya yönlendirilir
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

require_login();

$orderNo = trim($_GET['no'] ?? '');
if (!$orderNo) redirect(PUBLIC_URL . '/hesabim.php');

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->execute([$orderNo, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order || $order['payment_method'] !== 'paytr') {
    redirect(PUBLIC_URL . '/siparislerim.php');
}

$user = current_user($pdo);

// PayTR Ayarları
$merchant_id   = setting('paytr_merchant_id');
$merchant_key  = setting('paytr_merchant_key');
$merchant_salt = setting('paytr_merchant_salt');
$test_mode     = setting('paytr_test_mode') === '1' ? 1 : 0;

$iframeToken = null;
$paytrError = null;

if ($merchant_id && $merchant_key && $merchant_salt) {
    // Sipariş kalemleri
    $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemStmt->execute([$order['id']]);
    $items = $itemStmt->fetchAll();

    $basket = [];
    foreach ($items as $it) {
        $basket[] = [$it['script_title'], number_format($it['price'], 2, '.', ''), 1];
    }
    $basketEncoded = base64_encode(json_encode($basket));

    $amount    = (int)round($order['total'] * 100); // Kuruş cinsinden
    $currency  = 'TL';
    $email     = $user['email'];
    $name      = $user['name'];
    $phone     = preg_replace('/\D/', '', $user['phone'] ?? '05000000000');
    if (empty($phone)) $phone = '05000000000';
    $ip        = client_ip();
    $merchantOid = $order['order_number'];

    $okUrl   = PUBLIC_URL . '/siparis-basarili.php?no=' . urlencode($merchantOid);
    $failUrl = PUBLIC_URL . '/odeme-basarisiz.php?no=' . urlencode($merchantOid);

    // Token hash
    $hashStr = $merchant_id . $ip . $merchantOid . $email . $amount . $currency . $test_mode . 'card' . $basketEncoded . $merchant_salt;
    $tokenHash = base64_encode(hash_hmac('sha256', $hashStr, $merchant_key, true));

    $postData = [
        'merchant_id'   => $merchant_id,
        'user_ip'       => $ip,
        'merchant_oid'  => $merchantOid,
        'email'         => $email,
        'payment_amount'=> $amount,
        'paytr_token'   => $tokenHash,
        'user_basket'   => $basketEncoded,
        'debug_on'      => $test_mode,
        'no_installment'=> 0,
        'max_installment'=> 0,
        'user_name'     => $name,
        'user_phone'    => $phone,
        'merchant_ok_url'   => $okUrl,
        'merchant_fail_url' => $failUrl,
        'timeout_limit' => 30,
        'currency'      => $currency,
        'test_mode'     => $test_mode,
        'lang'          => 'tr',
    ];

    $ch = curl_init('https://www.paytr.com/odeme/api/get-token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($postData),
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $result = curl_exec($ch);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($err) {
        $paytrError = 'Bağlantı hatası: ' . $err;
    } else {
        $res = json_decode($result, true);
        if (isset($res['status']) && $res['status'] === 'success') {
            $iframeToken = $res['token'];
        } else {
            $paytrError = 'PayTR Hatası: ' . ($res['reason'] ?? 'Bilinmeyen hata');
        }
    }
} else {
    $paytrError = 'PayTR API bilgileri eksik. Lütfen yöneticiyle iletişime geçin.';
}

$pageTitle = 'Kart ile Öde (PayTR) — ' . setting('site_name');
require INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="breadcrumb"><a href="<?= PUBLIC_URL ?>/index.php">Ana Sayfa</a> / Ödeme</div>
  <h1>Kart ile <em>Öde</em></h1>
</div>

<div class="container" style="max-width:900px;padding:0 24px 60px;">

  <?php if ($paytrError): ?>
    <div class="glass-card" style="text-align:center;padding:40px;">
      <div style="font-size:48px;margin-bottom:16px;">⚠️</div>
      <h3 style="margin-bottom:10px;">Ödeme Sayfası Yüklenemedi</h3>
      <p style="color:var(--text-mute);margin-bottom:20px;"><?= e($paytrError) ?></p>
      <a href="<?= PUBLIC_URL ?>/siparis-basarili.php?no=<?= e($orderNo) ?>" class="btn btn-ghost">← Siparişe Dön</a>
    </div>
  <?php else: ?>
    <div class="glass-card mb-3">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--glass-border);">
        <div>
          <div style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--accent);"><?= e($order['order_number']) ?></div>
          <h3 style="font-size:18px;">Toplam: <?= format_price((float)$order['total']) ?></h3>
        </div>
        <div style="display:flex;gap:8px;">
          <span style="padding:4px 10px;background:rgba(255,255,255,.06);border-radius:6px;font-size:11px;color:var(--text-mute);">VISA</span>
          <span style="padding:4px 10px;background:rgba(255,255,255,.06);border-radius:6px;font-size:11px;color:var(--text-mute);">Mastercard</span>
          <span style="padding:4px 10px;background:rgba(255,255,255,.06);border-radius:6px;font-size:11px;color:var(--text-mute);">3D Secure</span>
        </div>
      </div>

      <!-- PayTR iframe -->
      <iframe src="https://www.paytr.com/odeme/guvenli/<?= e($iframeToken) ?>"
        id="paytriframe"
        frameborder="0"
        scrolling="no"
        style="width:100%;min-height:500px;border-radius:var(--radius);display:block;">
      </iframe>
      <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
      <script>iFrameResize({}, '#paytriframe');</script>
    </div>

    <div style="text-align:center;font-size:12px;color:var(--text-dim);">
      🔒 PayTR güvencesiyle 256-bit SSL şifreleme. Kart bilgileriniz sitemizde saklanmaz.
    </div>
  <?php endif; ?>
</div>

<?php require INCLUDES_PATH . '/footer.php'; ?>
