<?php
/**
 * Ana Sayfa — Cyber Edition
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

$featured = $pdo->query(
    "SELECT s.*, c.name AS category_name, c.slug AS category_slug
     FROM scripts s
     JOIN categories c ON c.id = s.category_id
     WHERE s.is_active = 1 AND s.is_featured = 1
     ORDER BY s.created_at DESC LIMIT 6"
)->fetchAll();

$bestsellers = $pdo->query(
    "SELECT s.*, c.name AS category_name, c.slug AS category_slug
     FROM scripts s
     JOIN categories c ON c.id = s.category_id
     WHERE s.is_active = 1 AND s.is_bestseller = 1
     ORDER BY s.sales_count DESC LIMIT 6"
)->fetchAll();

$newest = $pdo->query(
    "SELECT s.*, c.name AS category_name, c.slug AS category_slug
     FROM scripts s
     JOIN categories c ON c.id = s.category_id
     WHERE s.is_active = 1
     ORDER BY s.created_at DESC LIMIT 8"
)->fetchAll();

$categories = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM scripts s WHERE s.category_id = c.id AND s.is_active = 1) AS script_count
     FROM categories c
     WHERE c.is_active = 1
     ORDER BY c.display_order, c.name LIMIT 9"
)->fetchAll();

$totalScripts = (int)$pdo->query("SELECT COUNT(*) FROM scripts WHERE is_active=1")->fetchColumn();
$totalUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalSales   = (int)$pdo->query("SELECT SUM(sales_count) FROM scripts")->fetchColumn();
$satisfaction = 99;

require INCLUDES_PATH . '/header.php';

$catIcons = [
    'globe' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
    'settings' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
    'shopping-cart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>',
    'gamepad' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="6" y1="11" x2="10" y2="11"/><line x1="8" y1="9" x2="8" y2="13"/><line x1="15" y1="12" x2="15.01" y2="12"/><line x1="18" y1="10" x2="18.01" y2="10"/><path d="M17.32 5H6.68a4 4 0 0 0-3.978 3.59c-.006.052-.01.101-.017.152C2.604 9.416 2 14.456 2 16a3 3 0 0 0 3 3c1 0 1.5-.5 2-1l1.414-1.414A2 2 0 0 1 9.828 16h4.344a2 2 0 0 1 1.414.586L17 18c.5.5 1 1 2 1a3 3 0 0 0 3-3c0-1.545-.604-6.584-.685-7.258A4 4 0 0 0 17.32 5z"/></svg>',
    'briefcase' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    'utensils' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><line x1="7" y1="2" x2="7" y2="22"/><path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/></svg>',
    'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
    'file-text' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
    'box' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>',
];
?>

<!-- ============================== HERO ============================== -->
<section class="hero">
  <div class="hero-inner">
    <div>
      <div class="hero-eyebrow"><?= e(setting('hero_badge_text', '// PREMIUM PHP MARKETPLACE')) ?></div>
      <h1><?= e(setting('hero_title')) ?> <em>geleceği</em> burada</h1>
      <p class="lead"><?= e(setting('hero_subtitle')) ?></p>

      <div class="hero-ctas">
        <a href="<?= PUBLIC_URL ?>/scripts.php" class="btn btn-primary btn-lg">
          Scriptleri Keşfet
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
        <a href="<?= PUBLIC_URL ?>/iletisim.php" class="btn btn-neon btn-lg">İletişime Geç</a>
      </div>
    </div>

    <div class="hero-terminal">
      <div class="term-head">
        <div class="term-dots"><span class="term-dot"></span><span class="term-dot"></span><span class="term-dot"></span></div>
        <span class="term-title">~/scriptmarkt — bash</span>
      </div>
      <div class="term-body">
        <div><span class="term-prompt">$</span> <span class="term-cmd">scriptmarkt --list</span></div>
        <div class="term-out">→ Bulundu <span class="term-key"><?= $totalScripts ?></span> aktif script</div>
        <br>
        <div><span class="term-prompt">$</span> <span class="term-cmd">scriptmarkt --stats</span></div>
        <div class="term-out">  total_sales:   <span class="term-key"><?= $totalSales ?: 0 ?></span></div>
        <div class="term-out">  total_users:   <span class="term-key"><?= $totalUsers ?></span></div>
        <div class="term-out">  satisfaction:  <span class="term-key"><?= $satisfaction ?>%</span></div>
        <br>
        <div><span class="term-prompt">$</span> <span class="term-cmd">scriptmarkt --buy --instant</span></div>
        <div class="term-out">→ <span class="term-str">"Anında dijital teslimat hazır"</span></div>
        <div><span class="term-prompt">$</span> <span class="term-cursor"></span></div>
      </div>
    </div>
  </div>
</section>

<!-- ============================== TRUST STRIP ============================== -->
<div class="trust-strip-wrap">
  <div class="trust-strip">
    <div class="trust-item">
      <div class="trust-num" data-count="<?= $totalScripts ?>"><?= $totalScripts ?></div>
      <div class="trust-label">Aktif Script</div>
    </div>
    <div class="trust-item">
      <div class="trust-num" data-count="<?= $totalSales ?: 0 ?>"><?= $totalSales ?: 0 ?></div>
      <div class="trust-label">Satılan Lisans</div>
    </div>
    <div class="trust-item">
      <div class="trust-num" data-count="<?= $totalUsers ?>"><?= $totalUsers ?></div>
      <div class="trust-label">Mutlu Müşteri</div>
    </div>
    <div class="trust-item">
      <div class="trust-num" data-count="<?= $satisfaction ?>" data-suffix="%"><?= $satisfaction ?>%</div>
      <div class="trust-label">Memnuniyet</div>
    </div>
  </div>
</div>

<!-- ============================== CATEGORIES ============================== -->
<section>
  <div class="section-head">
    <div class="eyebrow">// kategoriler</div>
    <h2>Aradığın <em>her şey</em> burada</h2>
    <p class="section-sub">Restorandan e-ticarete, oyundan kurumsal çözümlere — tüm script ihtiyaçların tek çatı altında.</p>
  </div>

  <div class="category-grid">
    <?php foreach ($categories as $c):
      $iconKey = (!empty($c['icon']) && isset($catIcons[$c['icon']])) ? $c['icon'] : 'box';
    ?>
      <a href="<?= PUBLIC_URL ?>/scripts.php?kategori=<?= e($c['slug']) ?>" class="category-card" style="text-decoration:none;">
        <div class="category-icon">
          <?= $catIcons[$iconKey] ?>
        </div>
        <h3><?= e($c['name']) ?></h3>
        <p><?= e(mb_strimwidth(!empty($c['description']) ? $c['description'] : '', 0, 50, '…')) ?></p>
        <span class="category-count"><?= (int)($c['script_count'] ?? 0) ?> ürün</span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ============================== FEATURED ============================== -->
<?php if ($featured): ?>
<section>
  <div class="section-head">
    <div class="eyebrow">// öne çıkanlar</div>
    <h2>Editörün <em>seçtikleri</em></h2>
    <p class="section-sub">Profesyonel ekibimizin elden geçirdiği, en kaliteli premium scriptler.</p>
  </div>
  <div class="scripts-grid">
    <?php foreach ($featured as $s): include __DIR__ . '/_script_card.php'; endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ============================== BESTSELLERS ============================== -->
<?php if ($bestsellers): ?>
<section>
  <div class="section-head">
    <div class="eyebrow">// çok satanlar</div>
    <h2>Müşterilerin <em>favorisi</em></h2>
    <p class="section-sub">En çok tercih edilen, en çok satılan scriptler.</p>
  </div>
  <div class="scripts-grid">
    <?php foreach ($bestsellers as $s): include __DIR__ . '/_script_card.php'; endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ============================== NEWEST ============================== -->
<?php if ($newest): ?>
<section>
  <div class="section-head">
    <div class="eyebrow">// yeni eklenenler</div>
    <h2>Taze <em>kodlar</em></h2>
    <p class="section-sub">Az önce katalogda yerini alan yeni scriptler.</p>
  </div>
  <div class="scripts-grid">
    <?php foreach ($newest as $s): include __DIR__ . '/_script_card.php'; endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ============================== FAQ ============================== -->
<section>
  <div class="section-head">
    <div class="eyebrow">// sıkça sorulanlar</div>
    <h2>Aklında <em>soru mu var?</em></h2>
  </div>

  <div class="faq-list">
    <div class="faq-item">
      <div class="faq-q">Satın aldığım scripti ne zaman teslim alırım?</div>
      <div class="faq-a">Ödemen onaylandıktan sonra dosyan ve lisans anahtarın anında hesabında hazır olur — hatta otomatik teslimat aktifse ödeme bildiriminden hemen sonra. "Siparişlerim" sayfasından indirebilirsin.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q">Hangi ödeme yöntemlerini kabul ediyorsunuz?</div>
      <div class="faq-a">Banka havalesi / EFT, PayPal, kripto (USDT/BTC) ve Bancontact destekleniyor. Aktif olan yöntemler ödeme sayfasında görünür.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q">Aldığım scripti birden fazla siteye kurabilir miyim?</div>
      <div class="faq-a">Lisans türüne göre değişir; her ürünün detay sayfasında lisans bilgisi yazılı. Genelde "Standart Lisans" tek domain, "Pro Lisans" sınırsız domain için.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q">Destek alabilir miyim?</div>
      <div class="faq-a">Tabii. WhatsApp ve Telegram üzerinden ya da iletişim formundan ulaş, en kısa sürede dönüş yaparız.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q">Para iadesi yapıyor musunuz?</div>
      <div class="faq-a">Dijital ürünlerde teslimat sonrası iade mümkün değil. Ama satın almadan önce demo görüntüleyebilir, sorularını bize sorabilirsin.</div>
    </div>
  </div>
</section>

<?php require INCLUDES_PATH . '/footer.php'; ?>
