<?php
/**
 * Script listeleme sayfası
 * Filtreler: ?kategori=slug, ?q=arama, ?sirala=fiyat-asc|fiyat-desc|populer|yeni
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

$categorySlug = trim($_GET['kategori'] ?? '');
$query        = trim($_GET['q'] ?? '');
$sortParam    = $_GET['sirala'] ?? 'yeni';
$page         = max(1, (int)($_GET['sayfa'] ?? 1));
$perPage      = 12;

// Sort mapping
$sortMap = [
    'fiyat-asc'  => 's.price ASC',
    'fiyat-desc' => 's.price DESC',
    'populer'    => 's.sales_count DESC, s.views DESC',
    'yeni'       => 's.created_at DESC',
];
$orderBy = $sortMap[$sortParam] ?? $sortMap['yeni'];

$where = ['s.is_active = 1'];
$params = [];

if ($categorySlug) {
    $where[] = 'c.slug = ?';
    $params[] = $categorySlug;
}

if ($query) {
    $where[] = '(s.title LIKE ? OR s.tags LIKE ? OR s.short_description LIKE ?)';
    $like = '%' . $query . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}

$whereSql = implode(' AND ', $where);

// Toplam sayım
$countSql = "SELECT COUNT(*) FROM scripts s JOIN categories c ON c.id = s.category_id WHERE $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalResults = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalResults / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Asıl liste
$sql = "SELECT s.*, c.name AS category_name, c.slug AS category_slug
        FROM scripts s
        JOIN categories c ON c.id = s.category_id
        WHERE $whereSql
        ORDER BY $orderBy
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$scripts = $stmt->fetchAll();

$categories = get_categories($pdo);

// Mevcut kategori adı (varsa)
$activeCategoryName = null;
foreach ($categories as $c) {
    if ($c['slug'] === $categorySlug) { $activeCategoryName = $c['name']; break; }
}

$pageTitle = $activeCategoryName
    ? $activeCategoryName . ' Scriptleri — ' . setting('site_name')
    : 'Tüm Scriptler — ' . setting('site_name');

require INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="breadcrumb">
    <a href="<?= PUBLIC_URL ?>/index.php">Ana Sayfa</a> / <?= $activeCategoryName ? e($activeCategoryName) : 'Tüm Scriptler' ?>
  </div>
  <h1><?= $activeCategoryName ? e($activeCategoryName) : 'Tüm Scriptler' ?></h1>
  <p class="text-mute"><?= $totalResults ?> sonuç bulundu</p>
</div>

<div class="container">

  <form class="filter-bar" method="get" action="<?= PUBLIC_URL ?>/scripts.php">
    <?php if ($categorySlug): ?>
      <input type="hidden" name="kategori" value="<?= e($categorySlug) ?>">
    <?php endif; ?>
    <div class="search-box">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" class="form-input" name="q" placeholder="Script ara..." value="<?= e($query) ?>">
    </div>
    <select class="form-select" name="sirala" onchange="this.form.submit()">
      <option value="yeni"       <?= $sortParam === 'yeni'       ? 'selected' : '' ?>>En Yeniler</option>
      <option value="populer"    <?= $sortParam === 'populer'    ? 'selected' : '' ?>>Popülerlik</option>
      <option value="fiyat-asc"  <?= $sortParam === 'fiyat-asc'  ? 'selected' : '' ?>>Fiyat: Artan</option>
      <option value="fiyat-desc" <?= $sortParam === 'fiyat-desc' ? 'selected' : '' ?>>Fiyat: Azalan</option>
    </select>
    <button type="submit" class="btn btn-primary">Filtrele</button>
  </form>

  <?php if (!$categorySlug): ?>
  <div style="margin-bottom: 28px; display: flex; flex-wrap: wrap; gap: 8px;">
    <a href="<?= PUBLIC_URL ?>/scripts.php" class="btn btn-sm <?= !$categorySlug ? 'btn-primary' : 'btn-secondary' ?>">Tümü</a>
    <?php foreach ($categories as $c): ?>
      <a href="<?= PUBLIC_URL ?>/scripts.php?kategori=<?= e($c['slug']) ?>" class="btn btn-sm btn-secondary"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (empty($scripts)): ?>
    <div class="empty-state">
      <h3>Sonuç bulunamadı</h3>
      <p>Aradığınız kriterlere uyan script bulunamadı. Filtreleri değiştirip tekrar deneyin.</p>
      <a href="<?= PUBLIC_URL ?>/scripts.php" class="btn btn-primary">Tüm Scriptler</a>
    </div>
  <?php else: ?>
    <div class="scripts-grid">
      <?php foreach ($scripts as $s): include __DIR__ . '/_script_card.php'; endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
      <ul class="pagination">
        <?php
        $qs = $_GET; unset($qs['sayfa']);
        $buildUrl = function($p) use ($qs) {
            $qs['sayfa'] = $p;
            return PUBLIC_URL . '/scripts.php?' . http_build_query($qs);
        };
        ?>
        <?php if ($page > 1): ?>
          <li><a href="<?= e($buildUrl($page - 1)) ?>">← Önceki</a></li>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end   = min($totalPages, $page + 2);
        if ($start > 1) echo '<li><a href="' . e($buildUrl(1)) . '">1</a></li>';
        if ($start > 2) echo '<li><span>…</span></li>';
        for ($i = $start; $i <= $end; $i++):
        ?>
          <li><?= $i === $page ? '<span class="active">' . $i . '</span>' : '<a href="' . e($buildUrl($i)) . '">' . $i . '</a>' ?></li>
        <?php endfor;
        if ($end < $totalPages - 1) echo '<li><span>…</span></li>';
        if ($end < $totalPages) echo '<li><a href="' . e($buildUrl($totalPages)) . '">' . $totalPages . '</a></li>';
        ?>

        <?php if ($page < $totalPages): ?>
          <li><a href="<?= e($buildUrl($page + 1)) ?>">Sonraki →</a></li>
        <?php endif; ?>
      </ul>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require INCLUDES_PATH . '/footer.php'; ?>
