<?php
/**
 * Script ekleme/düzenleme form parçası
 * Çağıran scope'ta $script (boş veya dolu) ve $categories tanımlı olmalı
 */
$isEdit = !empty($script['id']);
?>
<form method="post" enctype="multipart/form-data" id="scriptForm">
  <?= csrf_field() ?>

  <!-- ============ 1. ÜRÜN BİLGİLERİ ============ -->
  <div style="padding:18px;background:rgba(99,102,241,.08);border-left:3px solid var(--primary);border-radius:10px;margin-bottom:24px;">
    <strong style="color:var(--accent);">📝 Ürün Bilgileri</strong>
    <p style="font-size:13px;color:var(--text-mute);margin:4px 0 0;">Ürün adı, kategori, fiyat ve açıklamalar</p>
  </div>

  <div class="form-grid">
    <div class="form-group form-group-full">
      <label class="form-label">Başlık *</label>
      <input type="text" name="title" class="form-input" required value="<?= e($script['title'] ?? '') ?>" placeholder="Örn: Restoran Yönetim Scripti — Pro">
    </div>

    <div class="form-group">
      <label class="form-label">Kategori *</label>
      <select name="category_id" class="form-select" required>
        <option value="">-- Seçin --</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ($script['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label">Varsayılan Lisans Türü</label>
      <input type="text" name="license_type" class="form-input" value="<?= e($script['license_type'] ?? 'Standart Lisans') ?>" placeholder="Standart Lisans / Pro Lisans">
    </div>

    <div class="form-group">
      <label class="form-label">Kart Rozeti</label>
      <input type="text" name="product_badge_text" class="form-input" value="<?= e($script['product_badge_text'] ?? '') ?>" placeholder="Örn: Yeni, Premium, En Çok Satan">
    </div>

    <div class="form-group">
      <label class="form-label">Fiyat (₺) *</label>
      <input type="number" name="price" class="form-input" required step="0.01" min="0" value="<?= e($script['price'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label class="form-label">İndirimli Fiyat (₺)</label>
      <input type="number" name="discount_price" class="form-input" step="0.01" min="0" value="<?= e($script['discount_price'] ?? '') ?>" placeholder="Boş = indirim yok">
    </div>

    <div class="form-group form-group-full">
      <div style="padding:16px;background:rgba(34,211,238,.07);border:1px solid rgba(34,211,238,.22);border-radius:14px;">
        <strong style="color:var(--accent);">🔐 Lisans Seçenekleri</strong>
        <p style="font-size:12.5px;color:var(--text-mute);margin:6px 0 14px;">İstediğini aç/kapat. Müşteri ürün detayında seçip sepete o fiyatla ekler.</p>
        <div class="form-grid">
          <label style="display:flex;gap:10px;align-items:center;"><input type="checkbox" name="license_monthly_enabled" value="1" <?= !empty($script['license_monthly_enabled']) ? 'checked' : '' ?>> Aylık Lisans</label>
          <input type="number" step="0.01" min="0" name="license_monthly_price" class="form-input" value="<?= e($script['license_monthly_price'] ?? '') ?>" placeholder="Aylık fiyat">
          <label style="display:flex;gap:10px;align-items:center;"><input type="checkbox" name="license_yearly_enabled" value="1" <?= !empty($script['license_yearly_enabled']) ? 'checked' : '' ?>> 1 Yıllık Lisans</label>
          <input type="number" step="0.01" min="0" name="license_yearly_price" class="form-input" value="<?= e($script['license_yearly_price'] ?? '') ?>" placeholder="Yıllık fiyat">
          <label style="display:flex;gap:10px;align-items:center;"><input type="checkbox" name="license_lifetime_enabled" value="1" <?= (!$isEdit || !array_key_exists('license_lifetime_enabled', $script) || !empty($script['license_lifetime_enabled'])) ? 'checked' : '' ?>> Ömür Boyu Lisans</label>
          <input type="number" step="0.01" min="0" name="license_lifetime_price" class="form-input" value="<?= e($script['license_lifetime_price'] ?? '') ?>" placeholder="Boşsa indirimli/normal fiyat kullanılır">
          <label style="display:flex;gap:10px;align-items:center;"><input type="checkbox" name="is_free" value="1" <?= !empty($script['is_free']) ? 'checked' : '' ?>> Ücretsiz sürüm göster</label>
          <input type="url" name="free_download_url" class="form-input" value="<?= e($script['free_download_url'] ?? '') ?>" placeholder="Ücretsiz demo indirme linki (opsiyonel)">
        </div>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Versiyon</label>
      <input type="text" name="version" class="form-input" value="<?= e($script['version'] ?? '1.0.0') ?>">
    </div>

    <div class="form-group">
      <label class="form-label">Son Güncelleme</label>
      <input type="date" name="last_update" class="form-input" value="<?= e($script['last_update'] ?? date('Y-m-d')) ?>">
    </div>

    <div class="form-group">
      <label class="form-label">Dosya Boyutu (göstermelik)</label>
      <input type="text" name="file_size" class="form-input" value="<?= e($script['file_size'] ?? '') ?>" placeholder="Örn: 24.6 MB">
      <div class="form-help">Yüklediğin dosyadan otomatik hesaplanmaz; manuel gir.</div>
    </div>

    <div class="form-group">
      <label class="form-label">Demo URL (müşteri görür)</label>
      <input type="url" name="demo_url" class="form-input" value="<?= e($script['demo_url'] ?? '') ?>" placeholder="https://demo.example.com">
    </div>

    <div class="form-group">
      <label class="form-label">Admin Demo URL (müşteri görür)</label>
      <input type="url" name="admin_demo_url" class="form-input" value="<?= e($script['admin_demo_url'] ?? '') ?>" placeholder="https://demo.example.com/admin">
    </div>

    <div class="form-group form-group-full">
      <label class="form-label">Admin Demo Giriş Bilgisi</label>
      <input type="text" name="admin_demo_info" class="form-input" value="<?= e($script['admin_demo_info'] ?? '') ?>" placeholder="Kullanıcı: admin / Şifre: admin123">
    </div>

    <div class="form-group form-group-full">
      <label class="form-label">Etiketler (virgülle ayır)</label>
      <input type="text" name="tags" class="form-input" value="<?= e($script['tags'] ?? '') ?>" placeholder="restoran, pos, menü, sipariş">
    </div>

    <div class="form-group form-group-full">
      <label class="form-label">Kısa Açıklama (kart için)</label>
      <textarea name="short_description" class="form-textarea" rows="2" placeholder="Tek cümlelik özet, kartlarda görünür"><?= e($script['short_description'] ?? '') ?></textarea>
    </div>

    <div class="form-group form-group-full">
      <label class="form-label">Detaylı Açıklama (HTML destekli)</label>
      <textarea name="description" class="form-textarea" rows="8" placeholder="<p>Detaylı tanıtım metni...</p>"><?= e($script['description'] ?? '') ?></textarea>
    </div>

    <div class="form-group form-group-full">
      <label class="form-label">Özellikler (her satıra bir özellik)</label>
      <textarea name="features" class="form-textarea" rows="6" placeholder="Online sipariş&#10;Masa yönetimi&#10;Stok takibi"><?= e(str_replace('|', "\n", $script['features'] ?? '')) ?></textarea>
      <div class="form-help">Her satırda bir özellik yazın. Otomatik liste haline gelecek.</div>
    </div>

    <div class="form-group form-group-full">
      <label class="form-label">Kurulum Talimatı (müşteri görür)</label>
      <textarea name="installation_info" class="form-textarea" rows="5" placeholder="1. Dosyaları yükleyin&#10;2. SQL'i import edin&#10;3. config.php'yi düzenleyin"><?= e($script['installation_info'] ?? '') ?></textarea>
    </div>
  </div>

  <!-- ============ 2. KAPAK GÖRSELİ ============ -->
  <div style="padding:18px;background:rgba(168,85,247,.08);border-left:3px solid var(--secondary);border-radius:10px;margin:30px 0 18px;">
    <strong style="color:var(--secondary-light);">🖼 Kapak Görseli</strong>
    <p style="font-size:13px;color:var(--text-mute);margin:4px 0 0;">Ürün kartında ve detay sayfasında görünecek görsel</p>
  </div>

  <div class="form-grid">
    <div class="form-group">
      <div class="file-upload-area" id="coverUploadArea">
        <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp,image/gif" id="coverInput">
        <div class="upload-icon">📷</div>
        <div class="file-label" style="font-size:14px;color:var(--text);font-weight:500;">Kapak görseli yükle</div>
        <div style="font-size:11.5px;color:var(--text-mute);margin-top:4px;">JPG, PNG, WebP — maks. 5 MB</div>
      </div>
      <?php if (!empty($script['cover_image'])): ?>
        <div style="margin-top:14px;padding:14px;background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:10px;display:flex;align-items:center;gap:12px;">
          <div class="image-preview" style="background-image:url('<?= e(script_image_url($script['cover_image'])) ?>');flex-shrink:0;"></div>
          <div style="flex:1;min-width:0;">
            <div style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--success);">✓ Mevcut görsel</div>
            <div style="font-size:12px;color:var(--text-mute);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($script['cover_image']) ?></div>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <div class="form-group form-group-full">
      <label class="form-label">Ek Ürün Görselleri / Galeri</label>
      <div class="file-upload-area">
        <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml" multiple>
        <div class="upload-icon">🖼️</div>
        <div class="file-label" style="font-size:14px;color:var(--text);font-weight:500;">Birden fazla galeri görseli yükle</div>
        <div style="font-size:11.5px;color:var(--text-mute);margin-top:4px;">Detay sayfasında ürün galerisi olarak görünür.</div>
      </div>
      <?php if (!empty($galleryImages)): ?>
        <div class="gallery-grid">
          <?php foreach ($galleryImages as $img): ?>
            <div class="gallery-thumb">
              <img src="<?= e(script_image_url($img['image_path'])) ?>" alt="">
              <label><input type="checkbox" name="delete_gallery[]" value="<?= (int)$img['id'] ?>"> Sil</label>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ============ 3. SCRIPT DOSYASI (EN ÖNEMLİ) ============ -->
  <div style="padding:20px;background:linear-gradient(135deg,rgba(0,255,136,.06),rgba(34,211,238,.06));border:1px solid var(--neon);border-radius:14px;margin:30px 0 18px;box-shadow:0 0 30px rgba(0,255,136,.1);">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
      <span style="font-size:22px;">📦</span>
      <strong style="color:var(--neon);font-size:16px;text-shadow:0 0 8px rgba(0,255,136,.4);">Müşteriye Teslim Edilecek Dosya</strong>
    </div>
    <p style="font-size:13px;color:var(--text-soft);margin:4px 0 0;line-height:1.7;">
      Müşteri ödemeyi yapıp onayı aldıktan sonra <strong style="color:var(--text);">otomatik olarak</strong> bu dosyayı indirecek.
      ZIP, RAR, 7Z, TAR, GZ desteklenir. <strong style="color:var(--warning);">Maks. <?= ini_get('upload_max_filesize') ?></strong>
    </p>
  </div>

  <div class="form-group">
    <div class="file-upload-area" id="scriptUploadArea" style="border-color:var(--neon);">
      <input type="file" name="script_file" accept=".zip,.rar,.7z,.tar,.gz" id="scriptInput">
      <div class="upload-icon" style="font-size:42px;">📦</div>
      <div class="file-label" style="font-size:15px;color:var(--neon);font-weight:600;text-shadow:0 0 8px rgba(0,255,136,.3);">Script paketini yükle</div>
      <div style="font-size:12px;color:var(--text-mute);margin-top:6px;font-family:'JetBrains Mono',monospace;">// .zip / .rar / .7z / .tar / .gz</div>
    </div>
    <?php if (!empty($script['file_path'])): ?>
      <div style="margin-top:14px;padding:14px;background:rgba(0,255,136,.08);border:1px solid rgba(0,255,136,.3);border-radius:10px;display:flex;align-items:center;gap:12px;">
        <div style="width:42px;height:42px;background:var(--neon);border-radius:10px;display:grid;place-items:center;color:#0a1a0e;flex-shrink:0;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--neon);">✓ MÜŞTERIYE TESLİM HAZIR</div>
          <div style="font-size:13px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e(basename($script['file_path'])) ?></div>
        </div>
      </div>
      <p style="font-size:12px;color:var(--text-mute);margin-top:8px;line-height:1.6;">
        💡 Yeni bir dosya yüklersen eski dosya silinir. Müşterilerin mevcut indirme tokenları yeni dosyaya yönlenir.
      </p>
    <?php else: ?>
      <p style="font-size:12px;color:var(--warning);margin-top:10px;line-height:1.6;">
        ⚠ Henüz dosya yüklenmedi. Dosya yüklenmeden satılan ürünler indirilemez!
      </p>
    <?php endif; ?>
  </div>

  <!-- ============ 4. ETİKETLER (yeni / öne çıkan / vb.) ============ -->
  <div style="padding:18px;background:rgba(245,158,11,.08);border-left:3px solid var(--warning);border-radius:10px;margin:30px 0 18px;">
    <strong style="color:var(--warning);">🏷 Görünürlük Etiketleri</strong>
    <p style="font-size:13px;color:var(--text-mute);margin:4px 0 0;">Bu ürünün anasayfada nasıl gösterileceğini belirle</p>
  </div>

  <div class="checkbox-row" style="margin-bottom:20px;">
    <label><input type="checkbox" name="is_active"     value="1" <?= !$isEdit || !empty($script['is_active'])     ? 'checked' : '' ?>> ✓ Yayında (Aktif)</label>
    <label><input type="checkbox" name="is_featured"   value="1" <?= !empty($script['is_featured'])   ? 'checked' : '' ?>> ⭐ Öne Çıkan</label>
    <label><input type="checkbox" name="is_bestseller" value="1" <?= !empty($script['is_bestseller']) ? 'checked' : '' ?>> 🔥 Çok Satan</label>
    <label><input type="checkbox" name="is_new"        value="1" <?= !$isEdit || !empty($script['is_new'])        ? 'checked' : '' ?>> 🆕 Yeni</label>
    <label><input type="checkbox" name="support_included" value="1" <?= !$isEdit || !array_key_exists('support_included', $script) || !empty($script['support_included']) ? 'checked' : '' ?>> 💬 Destek Dahil</label>
  </div>

  <!-- ============ SUBMIT BAR ============ -->
  <div style="margin-top:30px;padding:18px;background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:12px;display:flex;gap:10px;justify-content:space-between;align-items:center;flex-wrap:wrap;">
    <div style="font-size:13px;color:var(--text-mute);">
      <?php if ($isEdit): ?>
        <strong style="color:var(--text);">Düzenleme modu</strong> — değişiklikler hemen kaydedilir
      <?php else: ?>
        <strong style="color:var(--text);">Yeni ürün</strong> — kaydet ve müşterilere sunmaya hazır ol
      <?php endif; ?>
    </div>
    <div style="display:flex;gap:10px;">
      <a href="<?= ADMIN_URL ?>/scripts.php" class="btn btn-ghost">İptal</a>
      <button type="submit" class="btn btn-primary btn-lg">
        <?php if ($isEdit): ?>
          💾 Değişiklikleri Kaydet
        <?php else: ?>
          🚀 Scripti Yayınla
        <?php endif; ?>
      </button>
    </div>
  </div>
</form>

<script>
// Dosya seçildiğinde adı göster + alanı yeşile boyaa
document.querySelectorAll('.file-upload-area').forEach(area => {
  const input = area.querySelector('input[type="file"]');
  if (!input) return;
  input.addEventListener('change', () => {
    if (input.files.length) {
      const file = input.files[0];
      const sizeMB = (file.size / 1024 / 1024).toFixed(2);
      const label = area.querySelector('.file-label');
      if (label) {
        label.innerHTML = `✓ ${file.name} <span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-mute);">(${sizeMB} MB)</span>`;
        label.style.color = 'var(--success)';
      }
      area.classList.add('has-file');
    }
  });
});

// Drag & drop
document.querySelectorAll('.file-upload-area').forEach(area => {
  const input = area.querySelector('input[type="file"]');
  if (!input) return;

  ['dragenter', 'dragover'].forEach(ev => {
    area.addEventListener(ev, e => {
      e.preventDefault();
      area.style.borderColor = 'var(--accent)';
      area.style.background = 'rgba(34, 211, 238, .08)';
    });
  });
  ['dragleave', 'drop'].forEach(ev => {
    area.addEventListener(ev, e => {
      e.preventDefault();
      area.style.borderColor = '';
      area.style.background = '';
    });
  });
  area.addEventListener('drop', e => {
    if (e.dataTransfer.files.length) {
      input.files = e.dataTransfer.files;
      input.dispatchEvent(new Event('change'));
    }
  });
});
</script>
