<?php
/**
 * Admin layout — sidebar + header (Cyber Edition)
 */

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/auth.php';
}

require_admin();
$admin = current_admin($pdo);
$currentFile = basename($_SERVER['PHP_SELF']);

// Bekleyen sayaçlar (sidebar badge'leri için)
$pendingMsgCount     = (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
$pendingOrderCount   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status IN ('beklemede','odeme_bekliyor')")->fetchColumn();
$pendingCommentCount = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE is_approved = 0")->fetchColumn();

$pageTitleFull = ($adminPageTitle ?? 'Admin') . ' — ' . setting('site_name');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitleFull) ?></title>
<link rel="icon" href="<?= e(site_favicon_url()) ?>">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;600;700&family=Inter:wght@300..700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
<link rel="stylesheet" href="<?= ADMIN_URL ?>/assets/admin.css">
</head>
<body class="admin-body">

<!-- Cyber background layers -->
<div class="bg-cosmos"></div>
<div class="bg-grid"></div>
<div class="bg-orbs">
  <div class="bg-orb"></div>
  <div class="bg-orb"></div>
  <div class="bg-orb"></div>
</div>

<aside class="admin-sidebar" id="adminSidebar">
  <a href="<?= ADMIN_URL ?>/dashboard.php" class="brand" style="text-decoration:none;display:flex;align-items:center;gap:10px;">
    <?= brand_logo_html(true, 'admin-brand-logo') ?>
  </a>

  <div class="sidebar-section">Ana</div>
  <a href="<?= ADMIN_URL ?>/dashboard.php" class="sidebar-link <?= in_array($currentFile, ['dashboard.php','index.php']) ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
    Dashboard
  </a>

  <div class="sidebar-section">Katalog</div>
  <a href="<?= ADMIN_URL ?>/scripts.php" class="sidebar-link <?= in_array($currentFile, ['scripts.php','script-ekle.php','script-duzenle.php']) ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
    Scriptler
  </a>
  <a href="<?= ADMIN_URL ?>/kategoriler.php" class="sidebar-link <?= $currentFile === 'kategoriler.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    Kategoriler
  </a>
  <a href="<?= ADMIN_URL ?>/kuponlar.php" class="sidebar-link <?= $currentFile === 'kuponlar.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12V8H6V4h14v8z M2 12h20v8H2z"/><line x1="6" y1="16" x2="6.01" y2="16"/><line x1="10" y1="16" x2="10.01" y2="16"/></svg>
    Kuponlar
  </a>
  <a href="<?= ADMIN_URL ?>/bannerlar.php" class="sidebar-link <?= $currentFile === 'bannerlar.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
    Bannerlar
  </a>

  <div class="sidebar-section">Satış</div>
  <a href="<?= ADMIN_URL ?>/siparisler.php" class="sidebar-link <?= $currentFile === 'siparisler.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
    Siparişler
    <?php if ($pendingOrderCount > 0): ?><span class="sidebar-badge"><?= $pendingOrderCount ?></span><?php endif; ?>
  </a>

  <div class="sidebar-section">Kullanıcılar</div>
  <a href="<?= ADMIN_URL ?>/kullanicilar.php" class="sidebar-link <?= $currentFile === 'kullanicilar.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    Kullanıcılar
  </a>
  <a href="<?= ADMIN_URL ?>/yorumlar.php" class="sidebar-link <?= $currentFile === 'yorumlar.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    Yorumlar
    <?php if ($pendingCommentCount > 0): ?><span class="sidebar-badge"><?= $pendingCommentCount ?></span><?php endif; ?>
  </a>
  <a href="<?= ADMIN_URL ?>/mesajlar.php" class="sidebar-link <?= $currentFile === 'mesajlar.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
    Mesajlar
    <?php if ($pendingMsgCount > 0): ?><span class="sidebar-badge"><?= $pendingMsgCount ?></span><?php endif; ?>
  </a>

  <div class="sidebar-section">Ayarlar</div>
  <a href="<?= ADMIN_URL ?>/site-ayarlari.php" class="sidebar-link <?= $currentFile === 'site-ayarlari.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
    Site Ayarları
  </a>
  <a href="<?= ADMIN_URL ?>/odeme-ayarlari.php" class="sidebar-link <?= $currentFile === 'odeme-ayarlari.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
    Ödeme Ayarları
  </a>
  <a href="<?= ADMIN_URL ?>/kod-editoru.php" class="sidebar-link <?= $currentFile === 'kod-editoru.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
    Kod Editörü
  </a>

  <a href="<?= PUBLIC_URL ?>/index.php" target="_blank" class="sidebar-link" style="margin-top:18px;border:1px solid var(--glass-border);">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
    Siteyi Görüntüle
  </a>
  <a href="<?= ADMIN_URL ?>/logout.php" class="sidebar-link" style="color: var(--danger);">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    Çıkış Yap
  </a>
</aside>

<header class="admin-header">
  <button class="admin-mobile-toggle" id="adminToggle">
    <span></span><span></span><span></span>
  </button>
  <div class="admin-page-title"><?= e($adminPageTitle ?? 'Dashboard') ?></div>
  <div class="admin-header-actions">
    <div style="display:flex;align-items:center;gap:12px;">
      <div style="text-align:right;display:none;" class="admin-user-info">
        <div style="font-size:13.5px;font-weight:500;color:var(--text);"><?= e($admin['full_name'] ?: $admin['username']) ?></div>
        <div style="font-family:'JetBrains Mono',monospace;font-size:10.5px;color:var(--accent);letter-spacing:.1em;">// <?= e($admin['role'] === 'super' ? 'super admin' : 'editor') ?></div>
      </div>
      <div class="user-avatar"><?= strtoupper(mb_substr($admin['username'], 0, 1)) ?></div>
    </div>
  </div>
</header>

<main class="admin-main">

<?php foreach (get_flashes() as $f): ?>
  <div class="flash-bar flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>
