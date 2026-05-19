<?php
/**
 * PayPal ödeme sayfası
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

require_login();

$orderNo = trim($_GET['no'] ?? '');
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->execute([$orderNo, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) redirect(PUBLIC_URL . '/siparislerim.php');

$clientId = setting('paypal_client_id');
$mode     = setting('paypal_mode', 'sandbox');
$currency = 'USD'; // PayPal TRY destekler ama limitli

$pageTitle = 'PayPal Ödeme — ' . setting('site_name');
require INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="breadcrumb"><a href="<?= PUBLIC_URL ?>/index.php">Ana Sayfa</a> / Ödeme</div>
  <h1>PayPal ile <em>Öde</em></h1>
</div>

<div class="container" style="max-width:600px;padding:0 24px 60px;">
  <div class="glass-card">
    <div style="text-align:center;margin-bottom:24px;">
      <div style="font-size:42px;margin-bottom:10px;">🔵</div>
      <h3>PayPal ile Güvenli Ödeme</h3>
      <p style="color:var(--text-mute);font-size:13.5px;margin-top:6px;">Toplam: <strong style="color:var(--accent);font-size:22px;"><?= format_price((float)$order['total']) ?></strong></p>
    </div>

    <?php if (!$clientId): ?>
      <div class="flash-bar flash-error">PayPal API bilgileri eksik. Lütfen yöneticiyle iletişime geçin.</div>
    <?php else: ?>
      <div id="paypal-button-container"></div>

      <script src="https://www.paypal.com/sdk/js?client-id=<?= e($clientId) ?>&currency=USD"></script>
      <script>
      paypal.Buttons({
        createOrder: function(data, actions) {
          return actions.order.create({
            purchase_units: [{
              amount: {
                value: '<?= number_format($order['total'] / 30, 2) ?>',  // Yaklaşık USD
                currency_code: 'USD'
              },
              description: 'ScriptMarkt Sipariş #<?= e($orderNo) ?>'
            }]
          });
        },
        onApprove: function(data, actions) {
          return actions.order.capture().then(function(details) {
            // Ödeme başarılı, siparişi onayla
            fetch('<?= PUBLIC_URL ?>/paypal-onayla.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: 'order_no=<?= urlencode($orderNo) ?>&paypal_order_id=' + data.orderID + '&transaction_id=' + details.id
            }).then(() => {
              window.location = '<?= PUBLIC_URL ?>/siparis-basarili.php?no=<?= urlencode($orderNo) ?>';
            });
          });
        },
        onError: function(err) {
          document.getElementById('paypal-error').textContent = 'PayPal hatası: ' + err;
        },
        style: {
          color: 'blue',
          shape: 'rect',
          label: 'paypal',
          height: 48
        }
      }).render('#paypal-button-container');
      </script>
      <div id="paypal-error" style="color:var(--danger);font-size:13px;margin-top:10px;"></div>
    <?php endif; ?>

    <div style="margin-top:18px;text-align:center;font-size:12px;color:var(--text-dim);">
      🔒 PayPal ile güvenli ödeme. Kart bilgilerinizi sitemizle paylaşmanıza gerek yok.
    </div>
  </div>
</div>

<?php require INCLUDES_PATH . '/footer.php'; ?>
