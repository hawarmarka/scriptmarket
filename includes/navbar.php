<?php
/**
 * Navbar — public site
 */
$cartCount = cart_count();
$categories = get_categories($pdo);
$supportEnabled = setting('support_widget_enabled', '1') === '1';
?>
<nav class="navbar">
  <div class="nav-inner">
    <a href="<?= PUBLIC_URL ?>/index.php" class="brand" style="text-decoration:none;">
      <?= brand_logo_html(true, 'nav-brand-logo') ?>
    </a>

    <div class="nav-links">
      <a href="<?= PUBLIC_URL ?>/index.php" class="nav-link">Ana Sayfa</a>
      <div class="nav-link has-dropdown">
        Kategoriler
        <div class="dropdown">
          <?php foreach ($categories as $c): ?>
            <a href="<?= PUBLIC_URL ?>/scripts.php?kategori=<?= e($c['slug']) ?>" class="dropdown-item"><span style="width:6px;height:6px;border-radius:50%;background:var(--accent);"></span><?= e($c['name']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <a href="<?= PUBLIC_URL ?>/scripts.php" class="nav-link">Tüm Scriptler</a>
      <a href="<?= PUBLIC_URL ?>/iletisim.php" class="nav-link">İletişim</a>
    </div>

    <div class="nav-actions">
      <a href="<?= PUBLIC_URL ?>/sepet.php" class="cart-icon-btn" title="Sepet">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
        <?php if ($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
      </a>
      <?php if (is_user_logged_in()): $cu = current_user($pdo); ?>
        <div class="user-menu"><a href="<?= PUBLIC_URL ?>/hesabim.php" class="user-avatar" title="<?= e($cu['name']) ?>"><?= strtoupper(mb_substr($cu['name'], 0, 1)) ?></a></div>
      <?php else: ?>
        <a href="<?= PUBLIC_URL ?>/login.php" class="btn btn-secondary btn-sm">Giriş</a>
        <a href="<?= PUBLIC_URL ?>/register.php" class="btn btn-primary btn-sm">Üye Ol</a>
      <?php endif; ?>
    </div>
    <button class="mobile-toggle"><span></span><span></span><span></span></button>
  </div>
</nav>

<?php if ($supportEnabled): ?>
<div class="support-widget" id="supportWidget">
  <button class="support-main-btn" type="button" data-support-toggle aria-label="Destek">💬</button>
  <div class="support-panel">
    <div class="support-panel-head">
      <strong><?= e(setting('support_title', 'Canlı Destek')) ?></strong>
      <span><?= e(setting('support_subtitle', 'Sorun varsa hemen yaz.')) ?></span>
    </div>
    <div class="support-panel-actions">
      <?php if (setting('support_internal_message_enabled', '1') === '1'): ?>
        <a href="<?= PUBLIC_URL ?>/iletisim.php" class="support-action">✉️ Site içi mesaj gönder</a>
      <?php endif; ?>
      <?php if (setting('support_whatsapp_enabled', '1') === '1' && setting('whatsapp_number')): ?>
        <a href="https://wa.me/<?= e(preg_replace('/\D/', '', setting('whatsapp_number'))) ?>" target="_blank" class="support-action">🟢 WhatsApp ile yaz</a>
      <?php endif; ?>
      <?php if (setting('support_telegram_enabled', '1') === '1' && setting('telegram_link')): ?>
        <a href="<?= e(setting('telegram_link')) ?>" target="_blank" class="support-action">🔵 Telegram ile yaz</a>
      <?php endif; ?>
      <?php if (setting('live_support_url')): ?>
        <a href="<?= e(setting('live_support_url')) ?>" target="_blank" class="support-action">🚀 Canlı destek panelini aç</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>
