<?php
/**
 * Script detay sayfası
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) redirect(PUBLIC_URL . '/scripts.php');

$stmt = $pdo->prepare(
    "SELECT s.*, c.name AS category_name, c.slug AS category_slug
     FROM scripts s JOIN categories c ON c.id = s.category_id
     WHERE s.slug = ? AND s.is_active = 1"
);
$stmt->execute([$slug]);
$script = $stmt->fetch();

if (!$script) {
    http_response_code(404);
    $pageTitle = 'Script Bulunamadı';
    require INCLUDES_PATH . '/header.php';
    echo '<div class="container" style="padding:80px 24px;text-align:center;"><h1>Script bulunamadı</h1><p class="text-mute">Aradığınız script kaldırılmış veya yayında değil.</p><a href="' . PUBLIC_URL . '/scripts.php" class="btn btn-primary mt-3">Tüm Scriptler</a></div>';
    require INCLUDES_PATH . '/footer.php';
    exit;
}

// Görüntülenme sayısını artır
increment_views($pdo, (int)$script['id']);

// Galeri görselleri
$imgStmt = $pdo->prepare("SELECT image_path FROM script_images WHERE script_id = ? ORDER BY display_order, id");
$imgStmt->execute([$script['id']]);
$gallery = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

// Yorumlar
$commStmt = $pdo->prepare(
    "SELECT cm.*, u.name AS user_name
     FROM comments cm JOIN users u ON u.id = cm.user_id
     WHERE cm.script_id = ? AND cm.is_approved = 1
     ORDER BY cm.created_at DESC"
);
$commStmt->execute([$script['id']]);
$comments = $commStmt->fetchAll();

// Yorum POST işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    if (!is_user_logged_in()) {
        flash('error', 'Yorum yapmak için giriş yapmalısınız.');
        redirect(PUBLIC_URL . '/login.php');
    }
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız.');
    } else {
        $rating  = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $comment = trim($_POST['comment'] ?? '');
        if (mb_strlen($comment) < 10) {
            flash('error', 'Yorum en az 10 karakter olmalı.');
        } else {
            $insert = $pdo->prepare("INSERT INTO comments (script_id, user_id, rating, comment, is_approved) VALUES (?, ?, ?, ?, 0)");
            $insert->execute([$script['id'], $_SESSION['user_id'], $rating, $comment]);
            flash('success', 'Yorumunuz alındı, onaylandıktan sonra yayınlanacak.');
        }
        redirect(PUBLIC_URL . '/script-detay.php?slug=' . urlencode($slug) . '#yorumlar');
    }
}

// Benzer scriptler
$similar = $pdo->prepare(
    "SELECT s.*, c.name AS category_name
     FROM scripts s JOIN categories c ON c.id = s.category_id
     WHERE s.category_id = ? AND s.id <> ? AND s.is_active = 1
     ORDER BY s.sales_count DESC LIMIT 4"
);
$similar->execute([$script['category_id'], $script['id']]);
$similarItems = $similar->fetchAll();

// Ortalama puan
$avgRating = 5.0;
$ratingCount = count($comments);
if ($ratingCount) {
    $avgRating = array_sum(array_column($comments, 'rating')) / $ratingCount;
}

$discPct = discount_percentage($script);
$activePrice = active_price($script);
$features = $script['features'] ? array_filter(array_map('trim', explode('|', $script['features']))) : [];
$licenseOptions = script_license_options($script);

$pageTitle = $script['title'] . ' — ' . setting('site_name');
$metaDescription = mb_substr(strip_tags($script['short_description']), 0, 160);

require INCLUDES_PATH . '/header.php';
?>

<div class="page-header" style="padding-bottom: 10px;">
  <div class="breadcrumb">
    <a href="<?= PUBLIC_URL ?>/index.php">Ana Sayfa</a> /
    <a href="<?= PUBLIC_URL ?>/scripts.php?kategori=<?= e($script['category_slug']) ?>"><?= e($script['category_name']) ?></a> /
    <?= e($script['title']) ?>
  </div>
</div>

<div class="detail-wrap">

  <div>
    <div class="detail-gallery">
      <div class="detail-cover">
        <?php if (!empty($script['cover_image'])): ?>
          <img src="<?= e(script_image_url($script['cover_image'])) ?>" alt="<?= e($script['title']) ?>">
        <?php else: ?>
          <div class="script-cover-fallback" style="font-size: 96px;"><?= e(mb_strtoupper(mb_substr($script['title'], 0, 1))) ?></div>
        <?php endif; ?>
      </div>
      <?php if (!empty($gallery)): ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:8px;padding:12px;">
          <?php foreach ($gallery as $g): ?>
            <img src="<?= e(script_image_url($g)) ?>" alt="" style="height:80px;width:100%;object-fit:cover;border-radius:8px;border:1px solid var(--border);">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="detail-card mt-3">
      <div class="tabs-nav">
        <button class="tab-btn active" data-tab="desc">Açıklama</button>
        <button class="tab-btn" data-tab="features">Özellikler</button>
        <button class="tab-btn" data-tab="install">Kurulum</button>
        <button class="tab-btn" data-tab="yorumlar">Yorumlar (<?= $ratingCount ?>)</button>
      </div>

      <div data-tab-content="desc" class="tab-content active">
        <?= $script['description'] ?>
      </div>

      <div data-tab-content="features" class="tab-content">
        <?php if ($features): ?>
          <ul class="feature-list">
            <?php foreach ($features as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="text-mute">Bu ürün için özellik listesi henüz eklenmemiş.</p>
        <?php endif; ?>
      </div>

      <div data-tab-content="install" class="tab-content">
        <pre style="background:var(--bg-deep);padding:20px;border-radius:var(--radius);border:1px solid var(--border);overflow-x:auto;color:var(--text-soft);font-family:'JetBrains Mono',monospace;font-size:13.5px;line-height:1.7;white-space:pre-wrap;"><?= e($script['installation_info'] ?? 'Kurulum bilgisi paket içinde mevcuttur.') ?></pre>
      </div>

      <div data-tab-content="yorumlar" class="tab-content" id="yorumlar">
        <?php if (empty($comments)): ?>
          <p class="text-mute">Bu ürün için henüz yorum yapılmamış. İlk yorumu sen yap!</p>
        <?php else: ?>
          <div class="comment-list">
            <?php foreach ($comments as $c): ?>
              <div class="comment">
                <div class="comment-head">
                  <div class="comment-avatar"><?= e(mb_strtoupper(mb_substr($c['user_name'], 0, 1))) ?></div>
                  <div>
                    <div style="font-weight:500;"><?= e($c['user_name']) ?></div>
                    <div class="comment-meta"><?= format_date($c['created_at']) ?></div>
                  </div>
                  <div class="comment-stars"><?= str_repeat('★', (int)$c['rating']) . str_repeat('☆', 5 - (int)$c['rating']) ?></div>
                </div>
                <p style="color:var(--text-soft);font-size:14px;margin:0;"><?= nl2br(e($c['comment'])) ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (is_user_logged_in()): ?>
          <form method="post" style="margin-top:30px;padding-top:20px;border-top:1px solid var(--border);">
            <?= csrf_field() ?>
            <input type="hidden" name="add_comment" value="1">
            <h4 style="margin-bottom: 12px;">Yorum Yap</h4>
            <div class="form-group">
              <label class="form-label">Puanın</label>
              <select name="rating" class="form-select" style="max-width:200px;">
                <option value="5">★★★★★ — Harika</option>
                <option value="4">★★★★☆ — İyi</option>
                <option value="3">★★★☆☆ — Orta</option>
                <option value="2">★★☆☆☆ — Kötü</option>
                <option value="1">★☆☆☆☆ — Çok Kötü</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Yorumun</label>
              <textarea name="comment" class="form-textarea" required minlength="10" placeholder="Bu ürünü nasıl buldun?"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Yorumu Gönder</button>
          </form>
        <?php else: ?>
          <div class="empty-state mt-3">
            <p>Yorum yapabilmek için <a href="<?= PUBLIC_URL ?>/login.php" style="color:var(--cyan);">giriş yapmalısın</a>.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="detail-info">

    <div class="detail-card">
      <div class="script-cat" style="margin-bottom:8px;"><?= e($script['category_name']) ?></div>
      <h1 class="detail-title"><?= e($script['title']) ?></h1>

      <div class="detail-meta-row">
        <span>⭐ <?= number_format($avgRating, 1) ?> (<?= $ratingCount ?> yorum)</span>
        <span>👁 <?= number_format((int)$script['views']) ?> görüntülenme</span>
        <span>📦 <?= number_format((int)$script['sales_count']) ?> satış</span>
      </div>

      <div class="detail-price-block">
        <div>
          <span class="detail-price"><?= format_price($activePrice) ?></span>
          <?php if ($discPct > 0): ?>
            <span class="detail-price-old"><?= format_price((float)$script['price']) ?></span>
            <span class="discount-pill">-%<?= $discPct ?></span>
          <?php endif; ?>
        </div>
        <div style="margin-top:8px;font-size:13px;color:var(--text-mute);">
          ✓ Dijital teslimat &nbsp; · &nbsp; ✓ Anlık indirme &nbsp; · &nbsp; ✓ Lisans seçimi
        </div>
      </div>

      <div style="margin-top:16px;">
        <h4 style="margin-bottom:10px;">Lisans Seçimi</h4>
        <div class="license-options">
          <?php $firstOption = true; foreach ($licenseOptions as $opt): ?>
            <label class="license-option">
              <input type="radio" name="license_option" value="<?= e($opt['key']) ?>" <?= $firstOption ? 'checked' : '' ?>>
              <span class="license-option-card">
                <span class="license-option-title"><?= e($opt['label']) ?></span>
                <span class="license-option-price"><?= format_price((float)$opt['price']) ?></span>
                <span class="license-option-hint"><?= e($opt['hint']) ?></span>
              </span>
            </label>
          <?php $firstOption = false; endforeach; ?>
        </div>
      </div>

      <div class="detail-cta">
        <button class="btn btn-primary btn-lg btn-block" data-add-to-cart="<?= (int)$script['id'] ?>" data-url="<?= PUBLIC_URL ?>/sepet.php?action=add">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
          Sepete Ekle
        </button>
      </div>

      <?php if (!empty($script['demo_url'])): ?>
        <a href="<?= e($script['demo_url']) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-block mt-3">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          Canlı Demoyu Görüntüle
        </a>
      <?php endif; ?>
    </div>

    <div class="detail-card">
      <h4 style="margin-bottom: 12px;">Ürün Bilgileri</h4>
      <table class="meta-table">
        <tr><td>Versiyon</td><td><?= e($script['version']) ?></td></tr>
        <tr><td>Son Güncelleme</td><td><?= format_date($script['last_update']) ?></td></tr>
        <tr><td>Dosya Boyutu</td><td><?= e($script['file_size'] ?: '-') ?></td></tr>
        <tr><td>Lisans Türü</td><td><?= e($script['license_type']) ?></td></tr>
        <tr><td>Teslimat</td><td>Anında / Dijital</td></tr>
        <?php if (!empty($script['admin_demo_info'])): ?>
          <tr><td>Admin Demo</td><td><?= e($script['admin_demo_info']) ?></td></tr>
        <?php endif; ?>
      </table>
    </div>

    <?php if (setting('whatsapp_number') || setting('telegram_link')): ?>
    <div class="detail-card text-center">
      <h4 style="margin-bottom: 6px;">Sorun mu var?</h4>
      <p style="font-size: 13.5px; color: var(--text-mute); margin-bottom: 14px;">Satın almadan önce sormak istediğin her şey için bizimle iletişime geç.</p>
      <div style="display: flex; gap: 8px;">
        <?php if (setting('whatsapp_number')): ?>
        <a href="https://wa.me/<?= e(preg_replace('/\D/', '', setting('whatsapp_number'))) ?>" target="_blank" class="btn btn-success btn-sm" style="flex:1;">💬 WhatsApp</a>
        <?php endif; ?>
        <?php if (setting('telegram_link')): ?>
        <a href="<?= e(setting('telegram_link')) ?>" target="_blank" class="btn btn-secondary btn-sm" style="flex:1;">✈️ Telegram</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($similarItems): ?>
<section style="padding-top:40px;">
  <div class="section-head">
    <div class="eyebrow">/ benzer ürünler</div>
    <h2>İlgini <em>çekebilir</em></h2>
  </div>
  <div class="scripts-grid">
    <?php foreach ($similarItems as $s): include __DIR__ . '/_script_card.php'; endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php require INCLUDES_PATH . '/footer.php'; ?>
