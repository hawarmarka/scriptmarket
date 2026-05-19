<?php
/**
 * Script kartı partial.
 * Çağıran scope'ta $s (tek script verisi) tanımlı olmalı.
 *
 * Gerekli $s kolonları:
 *   id, title, slug, short_description, cover_image,
 *   price, discount_price, tags, is_new, is_bestseller, is_featured,
 *   category_name
 */
$discPct = discount_percentage($s);
$activePrice = active_price($s);
$tags = $s['tags'] ? array_slice(array_filter(array_map('trim', explode(',', $s['tags']))), 0, 3) : [];
?>
<article class="script-card">
  <div class="script-cover">
    <div class="script-tags-overlay">
      <?php if ($discPct > 0): ?>
        <span class="ribbon ribbon-discount">-%<?= $discPct ?></span>
      <?php endif; ?>
      <?php if (!empty($s['is_bestseller'])): ?>
        <span class="ribbon ribbon-best">⭐ Çok Satan</span>
      <?php endif; ?>
      <?php if (!empty($s['is_new'])): ?>
        <span class="ribbon ribbon-new">Yeni</span>
      <?php endif; ?>
      <?php if (!empty($s['is_featured'])): ?>
        <span class="ribbon ribbon-featured">Öne Çıkan</span>
      <?php endif; ?>
      <?php if (!empty($s['product_badge_text'])): ?>
        <span class="ribbon ribbon-featured"><?= e($s['product_badge_text']) ?></span>
      <?php endif; ?>
    </div>
    <a href="<?= PUBLIC_URL ?>/script-detay.php?slug=<?= e($s['slug']) ?>">
      <?php if (!empty($s['cover_image'])): ?>
        <img src="<?= e(script_image_url($s['cover_image'])) ?>" alt="<?= e($s['title']) ?>" loading="lazy">
      <?php else: ?>
        <div class="script-cover-fallback"><?= e(mb_strtoupper(mb_substr($s['title'], 0, 1))) ?></div>
      <?php endif; ?>
    </a>
  </div>
  <div class="script-body">
    <?php if (!empty($s['category_name'])): ?>
      <div class="script-cat"><?= e($s['category_name']) ?></div>
    <?php endif; ?>
    <h3><a href="<?= PUBLIC_URL ?>/script-detay.php?slug=<?= e($s['slug']) ?>"><?= e($s['title']) ?></a></h3>
    <p class="desc"><?= e($s['short_description']) ?></p>
    <p style="font-family:'JetBrains Mono',monospace;color:var(--cyan);font-size:11px;margin:0 0 10px;">🔐 <?= e($s['license_type'] ?? 'Dijital Lisans') ?></p>

    <?php if (!empty($tags)): ?>
    <div class="script-tags">
      <?php foreach ($tags as $tag): ?>
        <span class="script-tag">#<?= e($tag) ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="price-row">
      <div class="price-block">
        <span class="price-current"><?= format_price($activePrice) ?></span>
        <?php if ($discPct > 0): ?>
          <span class="price-old"><?= format_price((float)$s['price']) ?></span>
        <?php endif; ?>
      </div>
      <div class="card-actions">
        <a href="<?= PUBLIC_URL ?>/script-detay.php?slug=<?= e($s['slug']) ?>" class="icon-btn" title="Detay">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </a>
        <button class="icon-btn" data-add-to-cart="<?= (int)$s['id'] ?>" data-url="<?= PUBLIC_URL ?>/sepet.php?action=add" title="Sepete ekle">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
        </button>
      </div>
    </div>
  </div>
</article>
