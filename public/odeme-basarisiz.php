<?php
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';
$pageTitle = 'Ödeme Başarısız — ' . setting('site_name');
require INCLUDES_PATH . '/header.php';
?>
<div class="container" style="max-width:600px;padding:80px 24px;text-align:center;">
  <div style="font-size:72px;margin-bottom:20px;">❌</div>
  <h1 style="margin-bottom:12px;">Ödeme Başarısız</h1>
  <p class="text-mute" style="margin-bottom:30px;">Ödeme işlemi tamamlanamadı. Kart bilgilerini kontrol edip tekrar deneyebilirsin.</p>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
    <a href="<?= PUBLIC_URL ?>/sepet.php" class="btn btn-primary">Tekrar Dene</a>
    <a href="<?= PUBLIC_URL ?>/iletisim.php" class="btn btn-ghost">Destek Al</a>
  </div>
</div>
<?php require INCLUDES_PATH . '/footer.php'; ?>
