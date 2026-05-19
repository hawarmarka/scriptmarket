<?php
/**
 * Public header — ScriptMarkt
 */
require_once INCLUDES_PATH . '/auth.php';

if (setting('maintenance_mode') === '1' && !is_admin_logged_in() && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="tr"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakım — <?= e(setting('site_name', 'ScriptMarkt')) ?></title>
    <style>
    body { font-family: Inter, Arial, sans-serif; background: #02030a; color: white; min-height: 100vh; display: grid; place-items: center; padding: 20px; margin: 0; }
    .box { text-align: center; max-width: 480px; padding: 40px; background: rgba(15,24,66,.5); backdrop-filter: blur(20px); border: 1px solid rgba(148,163,184,.2); border-radius: 24px; }
    h1 { font-size: 32px; margin-bottom: 10px; background: linear-gradient(135deg,#818cf8,#c084fc,#22d3ee); -webkit-background-clip: text; color: transparent; }
    p { color: #94a3b8; line-height: 1.6; }
    </style></head><body>
    <div class="box"><h1>Yakında geri döneceğiz</h1><p>Sitemiz şu anda bakım modunda. Çok kısa süre içinde tekrar erişilebilir olacak.</p></div>
    </body></html>
    <?php
    exit;
}

$pageTitleFull = isset($pageTitle) ? $pageTitle : (setting('meta_title') ?: (setting('site_name', 'ScriptMarkt') . ' — ' . setting('site_slogan')));
$metaDescription = $metaDescription ?? setting('meta_description', setting('site_description'));
$themePrimary = setting('theme_primary', '#6366f1');
$themeSecondary = setting('theme_secondary', '#a855f7');
$themeAccent = setting('theme_accent', '#22d3ee');
$bgImage = setting('site_background_image');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitleFull) ?></title>
<meta name="description" content="<?= e($metaDescription) ?>">
<meta name="keywords" content="<?= e(setting('meta_keywords')) ?>">
<link rel="icon" href="<?= e(site_favicon_url()) ?>">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;600;700&family=Inter:wght@300..700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
<style id="theme-runtime-css">
:root {
  --primary: <?= e($themePrimary) ?>;
  --secondary: <?= e($themeSecondary) ?>;
  --accent: <?= e($themeAccent) ?>;
  --grad-primary: linear-gradient(135deg, <?= e($themePrimary) ?>, <?= e($themeSecondary) ?> 55%, <?= e($themeAccent) ?>);
  --grad-border: linear-gradient(135deg, <?= e($themePrimary) ?>, <?= e($themeSecondary) ?>, <?= e($themeAccent) ?>);
}
<?php if ($bgImage): ?>
body::before { content:''; position:fixed; inset:0; z-index:-5; background-image: linear-gradient(rgba(2,3,10,.82), rgba(2,3,10,.92)), url('<?= e(upload_asset_url($bgImage, 'banners')) ?>'); background-size:cover; background-position:center; background-attachment:fixed; }
<?php endif; ?>
</style>
<?php if (setting('custom_css')): ?>
<style id="custom-css">
<?= setting('custom_css') ?>
</style>
<?php endif; ?>
<?php if (setting('custom_head_code')): ?>
<?= setting('custom_head_code') ?>
<?php endif; ?>
</head>
<body>

<div class="bg-cosmos"></div>
<div class="bg-grid"></div>
<canvas class="bg-matrix"></canvas>
<div class="bg-orbs"><div class="bg-orb"></div><div class="bg-orb"></div><div class="bg-orb"></div></div>

<?php require __DIR__ . '/navbar.php'; ?>

<div class="main-wrap">
<?php foreach (get_flashes() as $f): ?>
  <div class="flash-bar flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>
