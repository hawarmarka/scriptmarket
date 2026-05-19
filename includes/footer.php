<?php
/**
 * Public footer — ScriptMarkt
 */
$categories_footer = isset($categories) ? $categories : get_categories($pdo);
$paymentMethods = array_filter(array_map('trim', explode(',', setting('footer_payment_methods', 'VISA, Mastercard, Bancontact, PayPal, Stripe, PayTR, Havale, USDT'))));
?>
</div><!-- /.main-wrap -->
<footer class="footer">
  <div class="footer-grid">
    <div>
      <div class="brand" style="margin-bottom:14px;">
        <?= brand_logo_html(true, 'footer-brand-logo') ?>
      </div>
      <p style="font-size:13.5px;color:var(--text-mute);line-height:1.7;max-width:340px;"><?= e(setting('site_description')) ?></p>
      <?php if (setting('contact_email') || setting('contact_phone')): ?>
        <div style="margin-top:14px;display:flex;flex-direction:column;gap:6px;font-size:13px;">
          <?php if (setting('contact_email')): ?><a href="mailto:<?= e(setting('contact_email')) ?>" style="color:var(--text-soft);">📧 <?= e(setting('contact_email')) ?></a><?php endif; ?>
          <?php if (setting('contact_phone')): ?><span style="color:var(--text-mute);">📞 <?= e(setting('contact_phone')) ?></span><?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
    <div><h4>// kategoriler</h4><ul class="footer-links"><?php foreach (array_slice($categories_footer, 0, 6) as $c): ?><li><a href="<?= PUBLIC_URL ?>/scripts.php?kategori=<?= e($c['slug']) ?>"><?= e($c['name']) ?></a></li><?php endforeach; ?></ul></div>
    <div><h4>// hızlı bağlantılar</h4><ul class="footer-links"><li><a href="<?= PUBLIC_URL ?>/scripts.php">Tüm Scriptler</a></li><li><a href="<?= PUBLIC_URL ?>/iletisim.php">İletişim</a></li><?php if (is_user_logged_in()): ?><li><a href="<?= PUBLIC_URL ?>/hesabim.php">Hesabım</a></li><li><a href="<?= PUBLIC_URL ?>/siparislerim.php">Siparişlerim</a></li><?php else: ?><li><a href="<?= PUBLIC_URL ?>/login.php">Giriş Yap</a></li><li><a href="<?= PUBLIC_URL ?>/register.php">Üye Ol</a></li><?php endif; ?></ul></div>
    <div><h4>// bizi takip et</h4><ul class="footer-links"><?php if (setting('social_instagram')): ?><li><a href="<?= e(setting('social_instagram')) ?>" target="_blank">Instagram</a></li><?php endif; ?><?php if (setting('social_tiktok')): ?><li><a href="<?= e(setting('social_tiktok')) ?>" target="_blank">TikTok</a></li><?php endif; ?><?php if (setting('social_twitter')): ?><li><a href="<?= e(setting('social_twitter')) ?>" target="_blank">Twitter</a></li><?php endif; ?><?php if (setting('social_youtube')): ?><li><a href="<?= e(setting('social_youtube')) ?>" target="_blank">YouTube</a></li><?php endif; ?><?php if (setting('telegram_link')): ?><li><a href="<?= e(setting('telegram_link')) ?>" target="_blank">Telegram</a></li><?php endif; ?></ul></div>
  </div>
  <div class="footer-bottom">
    <div><?= e(setting('footer_text')) ?></div>
    <div class="payment-chips"><?php foreach ($paymentMethods as $pm): ?><span class="payment-chip"><?= e($pm) ?></span><?php endforeach; ?></div>
  </div>
</footer>
<script src="<?= ASSETS_URL ?>/js/main.js"></script>
<?php if (setting('custom_body_code')): ?><?= setting('custom_body_code') ?><?php endif; ?>
</body>
</html>
