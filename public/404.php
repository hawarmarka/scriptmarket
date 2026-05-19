<?php
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

http_response_code(404);
$pageTitle = '404 — Sayfa Bulunamadı';
require INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding: 100px 24px; text-align: center; max-width: 600px;">
  <div style="font-family: 'Bricolage Grotesque', sans-serif; font-size: 120px; font-weight: 700; background: var(--grad-bright); -webkit-background-clip: text; background-clip: text; color: transparent; line-height: 1;">404</div>
  <h1 style="margin: 20px 0 12px;">Sayfa Bulunamadı</h1>
  <p class="text-mute" style="margin-bottom: 30px;">Aradığın sayfa silinmiş, taşınmış veya hiç var olmamış olabilir.</p>
  <a href="<?= PUBLIC_URL ?>/index.php" class="btn btn-primary">← Ana Sayfaya Dön</a>
  <a href="<?= PUBLIC_URL ?>/scripts.php" class="btn btn-ghost">Scriptleri Keşfet</a>
</div>

<?php require INCLUDES_PATH . '/footer.php'; ?>
