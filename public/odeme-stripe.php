<?php
/**
 * Stripe Ödeme Sayfası
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

require_login();

$orderNo = trim($_GET['no'] ?? '');
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->execute([$orderNo, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) redirect(PUBLIC_URL . '/siparislerim.php');

$publishableKey = setting('stripe_publishable_key');

$pageTitle = 'Stripe Ödeme — ' . setting('site_name');
require INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="breadcrumb"><a href="<?= PUBLIC_URL ?>/index.php">Ana Sayfa</a> / Ödeme</div>
  <h1>Kart ile <em>Öde</em></h1>
</div>

<div class="container" style="max-width:600px;padding:0 24px 60px;">
  <div class="glass-card">
    <div style="text-align:center;margin-bottom:24px;">
      <div style="font-size:42px;margin-bottom:10px;">💳</div>
      <h3>Stripe ile Güvenli Ödeme</h3>
      <p style="color:var(--text-mute);font-size:13.5px;margin-top:6px;">Toplam: <strong style="color:var(--accent);font-size:22px;"><?= format_price((float)$order['total']) ?></strong></p>
    </div>

    <?php if (!$publishableKey): ?>
      <div class="flash-bar flash-error">Stripe API bilgileri eksik. Lütfen yöneticiyle iletişime geçin.</div>
    <?php else: ?>

    <div id="stripeContainer">
      <div class="form-group">
        <label class="form-label">Kart Bilgileri</label>
        <div id="stripe-card-element" style="padding:14px 16px;background:rgba(2,3,10,.6);border:1px solid var(--glass-border);border-radius:10px;"></div>
        <div id="stripe-errors" style="color:var(--danger);font-size:13px;margin-top:8px;"></div>
      </div>

      <button id="stripePayBtn" class="btn btn-primary btn-block btn-lg">
        🔒 <?= format_price((float)$order['total']) ?> Öde
      </button>
    </div>

    <div id="stripeSuccess" style="display:none;text-align:center;padding:20px;">
      <div style="font-size:48px;margin-bottom:14px;">✅</div>
      <h3>Ödeme Başarılı</h3>
      <p style="color:var(--text-mute);margin-bottom:18px;">Yönlendiriliyor...</p>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
      const stripe = Stripe('<?= e($publishableKey) ?>');
      const elements = stripe.elements({
        appearance: {
          theme: 'night',
          variables: { colorPrimary: '#6366f1', colorBackground: '#0a1230', colorText: '#f1f5f9', colorDanger: '#ef4444', fontFamily: 'Inter, system-ui, sans-serif', borderRadius: '10px' }
        }
      });
      const cardEl = elements.create('card');
      cardEl.mount('#stripe-card-element');

      document.getElementById('stripePayBtn').addEventListener('click', async () => {
        const btn = document.getElementById('stripePayBtn');
        btn.textContent = '⏳ İşleniyor...';
        btn.disabled = true;

        // Create payment intent server-side
        const res = await fetch('<?= PUBLIC_URL ?>/stripe-intent.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'order_no=<?= urlencode($orderNo) ?>'
        });
        const data = await res.json();

        if (data.error) {
          document.getElementById('stripe-errors').textContent = data.error;
          btn.textContent = '🔒 <?= format_price((float)$order['total']) ?> Öde';
          btn.disabled = false;
          return;
        }

        const { error, paymentIntent } = await stripe.confirmCardPayment(data.client_secret, {
          payment_method: { card: cardEl }
        });

        if (error) {
          document.getElementById('stripe-errors').textContent = error.message;
          btn.textContent = '🔒 <?= format_price((float)$order['total']) ?> Öde';
          btn.disabled = false;
        } else if (paymentIntent.status === 'succeeded') {
          const verifyRes = await fetch('<?= PUBLIC_URL ?>/stripe-onayla.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'order_no=<?= urlencode($orderNo) ?>&payment_intent_id=' + encodeURIComponent(paymentIntent.id)
          });
          const verifyData = await verifyRes.json();
          if (!verifyData.ok) {
            document.getElementById('stripe-errors').textContent = verifyData.error || 'Ödeme doğrulandı fakat sipariş güncellenemedi. Destek ile iletişime geçin.';
            btn.textContent = '🔒 <?= format_price((float)$order['total']) ?> Öde';
            btn.disabled = false;
            return;
          }
          document.getElementById('stripeContainer').style.display = 'none';
          document.getElementById('stripeSuccess').style.display = 'block';
          setTimeout(() => {
            window.location = '<?= PUBLIC_URL ?>/siparis-basarili.php?no=<?= urlencode($orderNo) ?>';
          }, 1200);
        }
      });
    </script>
    <?php endif; ?>

    <div style="margin-top:18px;text-align:center;font-size:12px;color:var(--text-dim);">
      🔒 Stripe ile 256-bit SSL şifreli ödeme. Kart bilgileriniz sitemizde saklanmaz.
    </div>
  </div>
</div>

<?php require INCLUDES_PATH . '/footer.php'; ?>
