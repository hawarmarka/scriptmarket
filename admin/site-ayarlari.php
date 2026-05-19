<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminPageTitle = 'Site Ayarları';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız.');
        redirect(ADMIN_URL . '/site-ayarlari.php');
    }

    // Text alanları
    $textKeys = [
        'site_name','site_slogan','site_description','footer_text','footer_payment_methods',
        'hero_title','hero_subtitle','hero_badge_text',
        'contact_email','contact_phone','contact_address','whatsapp_number','telegram_link',
        'social_instagram','social_tiktok','social_twitter','social_youtube',
        'meta_title','meta_description','meta_keywords',
        'theme_primary','theme_secondary','theme_accent','site_background_overlay',
        'support_title','support_subtitle','live_support_url',
        'custom_head_code','custom_css','custom_body_code',
    ];

    foreach ($textKeys as $key) {
        $val = trim($_POST[$key] ?? '');
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
            ->execute([$key, $val]);
    }

    // Checkbox alanlar
    $checkboxKeys = ['maintenance_mode', 'auto_delivery', 'support_widget_enabled', 'support_internal_message_enabled', 'support_whatsapp_enabled', 'support_telegram_enabled'];
    foreach ($checkboxKeys as $key) {
        $val = isset($_POST[$key]) ? '1' : '0';
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
            ->execute([$key, $val]);
    }

    // Logo / favicon
    $uploadDir = UPLOAD_PATH . '/banners';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }

    $uploadErrors = [];
    $uploadedSomething = false;

    foreach (['site_logo' => 'logo_', 'site_favicon' => 'favicon_', 'site_background_image' => 'bg_'] as $key => $prefix) {
        if (empty($_FILES[$key]['name'])) {
            continue;
        }

        // Logo için SVG de kabul et. Favicon için SVG ve ICO da kabul et.
        $allowedTypes = ALLOWED_IMAGE_TYPES;
        if (!in_array('svg', $allowedTypes, true)) {
            $allowedTypes[] = 'svg';
        }
        if ($key === 'site_favicon' && !in_array('ico', $allowedTypes, true)) {
            $allowedTypes[] = 'ico';
        }

        $val = validate_upload($_FILES[$key], $allowedTypes);
        if (!$val['ok']) {
            $uploadErrors[] = ($key === 'site_logo' ? 'Logo' : ($key === 'site_favicon' ? 'Favicon' : 'Arka plan')) . ': ' . $val['error'];
            continue;
        }

        if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
            $uploadErrors[] = 'Yükleme klasörü yazılabilir değil: ' . $uploadDir;
            continue;
        }

        $newFile = $prefix . time() . '_' . bin2hex(random_bytes(3)) . '.' . $val['extension'];
        $target = $uploadDir . '/' . $newFile;

        if (!move_uploaded_file($_FILES[$key]['tmp_name'], $target)) {
            $uploadErrors[] = ($key === 'site_logo' ? 'Logo' : ($key === 'site_favicon' ? 'Favicon' : 'Arka plan')) . ': Dosya sunucuya taşınamadı.';
            continue;
        }

        @chmod($target, 0644);

        $old = setting($key);
        if ($old) {
            $oldPath = $uploadDir . '/' . $old;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
            ->execute([$key, $newFile]);
        $uploadedSomething = true;
    }

    sm_setting_cache_flush();

    if ($uploadErrors) {
        flash('error', 'Logo/Favicon yükleme hatası: ' . implode(' | ', $uploadErrors));
    } else {
        flash('success', $uploadedSomething ? '✓ Ayarlar ve görseller başarıyla güncellendi.' : '✓ Tüm ayarlar başarıyla güncellendi.');
    }
    redirect(ADMIN_URL . '/site-ayarlari.php');
}

require __DIR__ . '/_layout.php';
?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="admin-card">
    <div class="tabs-nav">
      <button type="button" class="tab-btn active" data-tab="general">⚙ Genel</button>
      <button type="button" class="tab-btn" data-tab="home">🏠 Anasayfa</button>
      <button type="button" class="tab-btn" data-tab="delivery">📦 Teslimat</button>
      <button type="button" class="tab-btn" data-tab="contact">📞 İletişim</button>
      <button type="button" class="tab-btn" data-tab="support">💬 Destek</button>
      <button type="button" class="tab-btn" data-tab="social">🌐 Sosyal Medya</button>
      <button type="button" class="tab-btn" data-tab="seo">🔍 SEO</button>
      <button type="button" class="tab-btn" data-tab="branding">🎨 Logo / Favicon</button>
      <button type="button" class="tab-btn" data-tab="theme">✨ Tema</button>
      <button type="button" class="tab-btn" data-tab="footer">🧾 Footer / Ödeme</button>
      <button type="button" class="tab-btn" data-tab="codes">🧩 Head / Kodlar</button>
    </div>

    <!-- GENEL -->
    <div data-tab-content="general" class="tab-content active">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Site Adı</label>
          <input type="text" name="site_name" class="form-input" value="<?= e(setting('site_name')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Slogan</label>
          <input type="text" name="site_slogan" class="form-input" value="<?= e(setting('site_slogan')) ?>">
        </div>
        <div class="form-group form-group-full">
          <label class="form-label">Site Açıklaması</label>
          <textarea name="site_description" class="form-textarea" rows="3"><?= e(setting('site_description')) ?></textarea>
        </div>
        <div class="form-group form-group-full">
          <label style="display:flex;gap:12px;align-items:flex-start;padding:16px;background:rgba(245,158,11,.08);border-left:3px solid var(--warning);border-radius:10px;cursor:pointer;">
            <input type="checkbox" name="maintenance_mode" value="1" <?= setting('maintenance_mode') === '1' ? 'checked' : '' ?> style="margin-top:3px;accent-color:var(--warning);width:18px;height:18px;">
            <div>
              <strong style="color:var(--warning);">🛠 Bakım Modu</strong>
              <p style="font-size:13px;color:var(--text-mute);margin:4px 0 0;">Bakım modu açıkken site ziyaretçilere kapalı görünür, sadece admin paneli erişilebilir.</p>
            </div>
          </label>
        </div>
      </div>
    </div>

    <!-- ANASAYFA -->
    <div data-tab-content="home" class="tab-content">
      <div class="form-grid">
        <div class="form-group form-group-full">
          <label class="form-label">Hero Üst Rozeti</label>
          <input type="text" name="hero_badge_text" class="form-input" value="<?= e(setting('hero_badge_text', '// PREMIUM PHP MARKETPLACE')) ?>">
        </div>
        <div class="form-group form-group-full">
          <label class="form-label">Hero Başlığı</label>
          <input type="text" name="hero_title" class="form-input" value="<?= e(setting('hero_title')) ?>">
          <div class="form-help">"&lt;em&gt;...&lt;/em&gt;" arasındaki kelimeler renkli gradient olur.</div>
        </div>
        <div class="form-group form-group-full">
          <label class="form-label">Hero Alt Yazısı</label>
          <textarea name="hero_subtitle" class="form-textarea" rows="3"><?= e(setting('hero_subtitle')) ?></textarea>
        </div>
      </div>
    </div>

    <!-- TESLİMAT -->
    <div data-tab-content="delivery" class="tab-content">
      <div style="padding:18px;background:rgba(99,102,241,.08);border-left:3px solid var(--primary);border-radius:10px;margin-bottom:24px;">
        <strong style="color:var(--accent);">⚡ Dijital Otomatik Teslimat</strong>
        <p style="font-size:13.5px;color:var(--text-soft);margin:6px 0 0;line-height:1.7;">
          Müşteri ödeme yöntemini seçip "Ödedim" butonuna bastığı an, sistem otomatik olarak siparişi onaylar ve
          müşteri lisans anahtarına ve indirme linkine anında erişir. Sen sonradan admin panelden kontrol edebilirsin.
        </p>
      </div>

      <div class="form-group">
        <label style="display:flex;gap:12px;align-items:flex-start;padding:18px;background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.3);border-radius:12px;cursor:pointer;">
          <input type="checkbox" name="auto_delivery" value="1" <?= setting('auto_delivery') === '1' ? 'checked' : '' ?> style="margin-top:3px;accent-color:var(--success);width:20px;height:20px;">
          <div>
            <strong style="color:var(--success);font-size:16px;">Otomatik Teslimat Aktif</strong>
            <p style="font-size:13px;color:var(--text-soft);margin:6px 0 0;line-height:1.7;">
              <strong>AÇIK:</strong> Müşteri ödeme bildiriminden hemen sonra dosyayı indirebilir. (Online ürünler için önerilir.)<br>
              <strong>KAPALI:</strong> Sen manuel onay verene kadar müşteri indiremez. (Yüksek değerli ürünler veya manuel ödeme doğrulaması istiyorsan.)
            </p>
          </div>
        </label>
      </div>

      <div style="padding:14px;background:rgba(245,158,11,.06);border-left:3px solid var(--warning);border-radius:8px;font-size:13px;color:var(--text-soft);line-height:1.7;margin-top:14px;">
        💡 <strong>İpucu:</strong> Otomatik teslimat açıkken bile, admin panelden "Siparişler" sayfasında tüm siparişleri görüp gerekirse iptal edebilirsin.
      </div>
    </div>

    <!-- İLETİŞİM -->
    <div data-tab-content="contact" class="tab-content">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">E-posta</label>
          <input type="email" name="contact_email" class="form-input" value="<?= e(setting('contact_email')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Telefon</label>
          <input type="tel" name="contact_phone" class="form-input" value="<?= e(setting('contact_phone')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">WhatsApp Numarası</label>
          <input type="tel" name="whatsapp_number" class="form-input" value="<?= e(setting('whatsapp_number')) ?>" placeholder="+90 5XX XXX XX XX">
          <div class="form-help">Float WhatsApp butonu için. Boş bırakılırsa görünmez.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Telegram Link</label>
          <input type="url" name="telegram_link" class="form-input" value="<?= e(setting('telegram_link')) ?>" placeholder="https://t.me/...">
        </div>
        <div class="form-group form-group-full">
          <label class="form-label">Adres</label>
          <textarea name="contact_address" class="form-textarea" rows="2"><?= e(setting('contact_address')) ?></textarea>
        </div>
      </div>
    </div>

    <!-- DESTEK -->
    <div data-tab-content="support" class="tab-content">
      <div class="form-grid">
        <div class="form-group form-group-full">
          <label style="display:flex;gap:12px;align-items:flex-start;padding:18px;background:rgba(34,211,238,.08);border:1px solid rgba(34,211,238,.25);border-radius:12px;cursor:pointer;">
            <input type="checkbox" name="support_widget_enabled" value="1" <?= setting('support_widget_enabled','1') === '1' ? 'checked' : '' ?> style="margin-top:3px;accent-color:var(--accent);width:20px;height:20px;">
            <div><strong style="color:var(--accent);font-size:16px;">Sağ Alt Canlı Destek Widget Aktif</strong><p style="font-size:13px;color:var(--text-soft);margin:6px 0 0;line-height:1.7;">WhatsApp, Telegram, site içi mesaj ve harici canlı destek linki aynı panelde görünür.</p></div>
          </label>
        </div>
        <div class="form-group"><label class="form-label">Destek Başlığı</label><input type="text" name="support_title" class="form-input" value="<?= e(setting('support_title','Canlı Destek')) ?>"></div>
        <div class="form-group"><label class="form-label">Canlı Destek URL</label><input type="url" name="live_support_url" class="form-input" value="<?= e(setting('live_support_url')) ?>" placeholder="Tawk.to / Crisp panel linki veya özel destek linki"></div>
        <div class="form-group form-group-full"><label class="form-label">Destek Açıklaması</label><textarea name="support_subtitle" class="form-textarea" rows="2"><?= e(setting('support_subtitle')) ?></textarea></div>
        <div class="form-group"><label><input type="checkbox" name="support_internal_message_enabled" value="1" <?= setting('support_internal_message_enabled','1') === '1' ? 'checked' : '' ?>> Site içi mesaj butonu</label></div>
        <div class="form-group"><label><input type="checkbox" name="support_whatsapp_enabled" value="1" <?= setting('support_whatsapp_enabled','1') === '1' ? 'checked' : '' ?>> WhatsApp butonu</label></div>
        <div class="form-group"><label><input type="checkbox" name="support_telegram_enabled" value="1" <?= setting('support_telegram_enabled','1') === '1' ? 'checked' : '' ?>> Telegram butonu</label></div>
      </div>
    </div>

    <!-- SOSYAL MEDYA -->
    <div data-tab-content="social" class="tab-content">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">📷 Instagram</label>
          <input type="url" name="social_instagram" class="form-input" value="<?= e(setting('social_instagram')) ?>" placeholder="https://instagram.com/...">
        </div>
        <div class="form-group">
          <label class="form-label">🎵 TikTok</label>
          <input type="url" name="social_tiktok" class="form-input" value="<?= e(setting('social_tiktok')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">𝕏 Twitter</label>
          <input type="url" name="social_twitter" class="form-input" value="<?= e(setting('social_twitter')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">▶ YouTube</label>
          <input type="url" name="social_youtube" class="form-input" value="<?= e(setting('social_youtube')) ?>">
        </div>
      </div>
    </div>

    <!-- SEO -->
    <div data-tab-content="seo" class="tab-content">
      <div class="form-grid">
        <div class="form-group form-group-full">
          <label class="form-label">Meta Title</label>
          <input type="text" name="meta_title" class="form-input" value="<?= e(setting('meta_title')) ?>" maxlength="60">
          <div class="form-help">Maks. 60 karakter</div>
        </div>
        <div class="form-group form-group-full">
          <label class="form-label">Meta Description</label>
          <textarea name="meta_description" class="form-textarea" rows="3" maxlength="160"><?= e(setting('meta_description')) ?></textarea>
          <div class="form-help">Maks. 160 karakter</div>
        </div>
        <div class="form-group form-group-full">
          <label class="form-label">Meta Keywords</label>
          <input type="text" name="meta_keywords" class="form-input" value="<?= e(setting('meta_keywords')) ?>" placeholder="virgülle ayır">
        </div>
      </div>
    </div>

    <!-- LOGO / FAVICON -->
    <div data-tab-content="branding" class="tab-content">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Site Logosu</label>
          <label class="file-upload-area">
            <input type="file" name="site_logo" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml">
            <div class="upload-icon">🖼</div>
            <div class="file-label" style="font-size:13px;color:var(--text-mute);">PNG / SVG önerilir, yükseklik ~40px</div>
          </label>
          <?php if (setting('site_logo')): ?>
            <div style="margin-top:12px;padding:18px;background:rgba(2,3,10,.5);border-radius:10px;text-align:center;">
              <img src="<?= e(upload_asset_url(setting('site_logo'), 'banners')) ?>" style="max-height:60px;max-width:100%;">
            </div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Favicon</label>
          <label class="file-upload-area">
            <input type="file" name="site_favicon" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml,image/x-icon,.ico">
            <div class="upload-icon">⭐</div>
            <div class="file-label" style="font-size:13px;color:var(--text-mute);">32x32 PNG veya SVG</div>
          </label>
          <?php if (setting('site_favicon')): ?>
            <div style="margin-top:12px;padding:18px;background:rgba(2,3,10,.5);border-radius:10px;text-align:center;">
              <img src="<?= e(upload_asset_url(setting('site_favicon'), 'banners')) ?>" style="max-height:48px;">
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- TEMA -->
    <div data-tab-content="theme" class="tab-content">
      <div style="padding:14px;background:rgba(99,102,241,.06);border-left:3px solid var(--primary);border-radius:8px;font-size:13px;color:var(--text-soft);line-height:1.7;margin-bottom:18px;">
        🎨 Sitenin ana renklerini buradan değiştirebilirsin. Değişiklikler kaydedildikten sonra siteyi yenile.
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">🟣 Birincil Renk</label>
          <div style="display:flex;gap:10px;align-items:center;">
            <input type="color" name="theme_primary" value="<?= e(setting('theme_primary', '#6366f1')) ?>" style="width:60px;height:42px;padding:0;border:1px solid var(--glass-border);border-radius:8px;background:transparent;">
            <input type="text" class="form-input" value="<?= e(setting('theme_primary', '#6366f1')) ?>" disabled style="flex:1;font-family:'JetBrains Mono',monospace;">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">🟪 İkincil Renk</label>
          <div style="display:flex;gap:10px;align-items:center;">
            <input type="color" name="theme_secondary" value="<?= e(setting('theme_secondary', '#a855f7')) ?>" style="width:60px;height:42px;padding:0;border:1px solid var(--glass-border);border-radius:8px;">
            <input type="text" class="form-input" value="<?= e(setting('theme_secondary', '#a855f7')) ?>" disabled style="flex:1;font-family:'JetBrains Mono',monospace;">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">🟦 Vurgu Rengi (Cyan)</label>
          <div style="display:flex;gap:10px;align-items:center;">
            <input type="color" name="theme_accent" value="<?= e(setting('theme_accent', '#22d3ee')) ?>" style="width:60px;height:42px;padding:0;border:1px solid var(--glass-border);border-radius:8px;">
            <input type="text" class="form-input" value="<?= e(setting('theme_accent', '#22d3ee')) ?>" disabled style="flex:1;font-family:'JetBrains Mono',monospace;">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Arka Plan Efekti</label>
          <select name="site_background_overlay" class="form-select">
            <option value="matrix" <?= setting('site_background_overlay','matrix')==='matrix'?'selected':'' ?>>Matrix / Kod Yağmuru</option>
            <option value="grid" <?= setting('site_background_overlay')==='grid'?'selected':'' ?>>Grid / Neon</option>
            <option value="clean" <?= setting('site_background_overlay')==='clean'?'selected':'' ?>>Sade</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Özel Arka Plan Görseli</label>
          <label class="file-upload-area"><input type="file" name="site_background_image" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml"><div class="upload-icon">🌌</div><div class="file-label" style="font-size:13px;color:var(--text-mute);">Hero/site arka planı yükle</div></label>
          <?php if (setting('site_background_image')): ?><div style="margin-top:12px;padding:10px;background:rgba(2,3,10,.5);border-radius:10px;text-align:center;"><img src="<?= upload_asset_url(setting('site_background_image'), 'banners') ?>" style="max-height:80px;max-width:100%;border-radius:8px;"></div><?php endif; ?>
        </div>

        <div class="form-group form-group-full">
          <label class="form-label">Önizleme</label>
          <div style="padding:24px;background:rgba(2,3,10,.5);border-radius:12px;border:1px solid var(--glass-border);display:flex;gap:14px;flex-wrap:wrap;align-items:center;">
            <div style="width:80px;height:80px;border-radius:14px;background:linear-gradient(135deg,<?= e(setting('theme_primary')) ?>,<?= e(setting('theme_secondary')) ?>);box-shadow:0 0 30px <?= e(setting('theme_primary')) ?>50;"></div>
            <div style="flex:1;min-width:200px;">
              <div style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text-mute);margin-bottom:6px;">RENK PALETİ</div>
              <div style="display:flex;gap:8px;">
                <div style="flex:1;height:40px;border-radius:8px;background:<?= e(setting('theme_primary')) ?>;"></div>
                <div style="flex:1;height:40px;border-radius:8px;background:<?= e(setting('theme_secondary')) ?>;"></div>
                <div style="flex:1;height:40px;border-radius:8px;background:<?= e(setting('theme_accent')) ?>;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER / ÖDEME -->
    <div data-tab-content="footer" class="tab-content">
      <div class="form-grid">
        <div class="form-group form-group-full">
          <label class="form-label">Footer Ödeme Yöntemleri</label>
          <input type="text" name="footer_payment_methods" class="form-input" value="<?= e(setting('footer_payment_methods','VISA, Mastercard, Bancontact, PayPal, Stripe, PayTR, Havale, USDT')) ?>">
          <div class="form-help">Virgülle ayır. Footer kısmındaki ödeme etiketleri buradan gelir.</div>
        </div>
        <div class="form-group form-group-full">
          <label class="form-label">Footer Metni</label>
          <input type="text" name="footer_text" class="form-input" value="<?= e(setting('footer_text')) ?>">
        </div>
      </div>
    </div>

    <!-- HEAD / KODLAR -->
    <div data-tab-content="codes" class="tab-content">
      <div class="form-grid">
        <div class="form-group form-group-full"><label class="form-label">&lt;head&gt; Kodları</label><textarea name="custom_head_code" class="form-textarea code-editor" rows="7" placeholder="Google Analytics, Meta Pixel, özel meta kodları..."><?= htmlspecialchars(setting('custom_head_code')) ?></textarea></div>
        <div class="form-group form-group-full"><label class="form-label">Özel CSS</label><textarea name="custom_css" class="form-textarea code-editor" rows="7" placeholder=".navbar { ... }"><?= htmlspecialchars(setting('custom_css')) ?></textarea></div>
        <div class="form-group form-group-full"><label class="form-label">&lt;/body&gt; Kodları</label><textarea name="custom_body_code" class="form-textarea code-editor" rows="7" placeholder="Tawk.to, Crisp, özel JS kodları..."><?= htmlspecialchars(setting('custom_body_code')) ?></textarea></div>
      </div>
    </div>

    <div style="margin-top:28px;padding-top:20px;border-top:1px solid var(--glass-border);display:flex;justify-content:flex-end;gap:10px;">
      <a href="<?= PUBLIC_URL ?>/index.php" target="_blank" class="btn btn-ghost">Siteyi Görüntüle ↗</a>
      <button type="submit" class="btn btn-primary btn-lg">💾 Tüm Ayarları Kaydet</button>
    </div>
  </div>
</form>

<script>
// Logo/Favicon alanında dosya seçilince kullanıcıya dosya adını göster
document.querySelectorAll('.file-upload-area input[type="file"]').forEach(input => {
  input.addEventListener('change', () => {
    const area = input.closest('.file-upload-area');
    const label = area ? area.querySelector('.file-label') : null;
    if (input.files && input.files.length && label) {
      label.textContent = '✓ Seçildi: ' + input.files[0].name;
      area.classList.add('has-file');
    }
  });
});
</script>

<script>
// Color picker → text input senkronizasyonu
document.querySelectorAll('input[type="color"]').forEach(picker => {
  const textInput = picker.parentElement.querySelector('input[type="text"]');
  picker.addEventListener('input', () => {
    if (textInput) textInput.value = picker.value.toUpperCase();
  });
});
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
