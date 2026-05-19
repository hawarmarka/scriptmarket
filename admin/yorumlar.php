<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminPageTitle = 'Yorumlar';

// Onayla
if (isset($_GET['approve']) && csrf_verify($_GET['t'] ?? '')) {
    $pdo->prepare("UPDATE comments SET is_approved = 1 WHERE id = ?")->execute([(int)$_GET['approve']]);
    flash('success', 'Yorum onaylandı.');
    redirect(ADMIN_URL . '/yorumlar.php');
}
// Reddet (silmek yerine pasif tut)
if (isset($_GET['reject']) && csrf_verify($_GET['t'] ?? '')) {
    $pdo->prepare("UPDATE comments SET is_approved = 0 WHERE id = ?")->execute([(int)$_GET['reject']]);
    flash('info', 'Yorum yayından kaldırıldı.');
    redirect(ADMIN_URL . '/yorumlar.php');
}
// Sil
if (isset($_GET['delete']) && csrf_verify($_GET['t'] ?? '')) {
    $pdo->prepare("DELETE FROM comments WHERE id = ?")->execute([(int)$_GET['delete']]);
    flash('success', 'Yorum silindi.');
    redirect(ADMIN_URL . '/yorumlar.php');
}

$filter = $_GET['filter'] ?? 'all';
$where = ['1=1'];
if ($filter === 'pending')  $where[] = 'cm.is_approved = 0';
if ($filter === 'approved') $where[] = 'cm.is_approved = 1';
$whereSql = implode(' AND ', $where);

$comments = $pdo->query(
    "SELECT cm.*, u.name AS user_name, u.email AS user_email, s.title AS script_title, s.slug AS script_slug
     FROM comments cm
     LEFT JOIN users u ON u.id = cm.user_id
     LEFT JOIN scripts s ON s.id = cm.script_id
     WHERE $whereSql
     ORDER BY cm.created_at DESC"
)->fetchAll();

require __DIR__ . '/_layout.php';
?>

<div class="admin-card">
  <div class="admin-card-head">
    <h3>Yorumlar (<?= count($comments) ?>)</h3>
  </div>

  <div class="filter-chips">
    <a href="?filter=all" class="filter-chip <?= $filter === 'all' ? 'active' : '' ?>">Tümü</a>
    <a href="?filter=pending" class="filter-chip <?= $filter === 'pending' ? 'active' : '' ?>">Onay Bekleyen</a>
    <a href="?filter=approved" class="filter-chip <?= $filter === 'approved' ? 'active' : '' ?>">Onaylı</a>
  </div>

  <?php if (empty($comments)): ?>
    <p class="text-mute">Yorum bulunamadı.</p>
  <?php else: ?>
    <?php foreach ($comments as $c): ?>
      <div style="padding:18px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:10px;">
          <div>
            <strong><?= e($c['user_name'] ?? 'Anonim') ?></strong>
            <span style="color:var(--text-mute);font-size:12.5px;"> · <?= format_date($c['created_at'], true) ?></span>
            <div style="font-size:12.5px;color:var(--text-mute);">Ürün: <a href="<?= PUBLIC_URL ?>/script-detay.php?slug=<?= e($c['script_slug']) ?>" target="_blank" style="color:var(--cyan);"><?= e($c['script_title']) ?></a></div>
          </div>
          <div style="text-align:right;">
            <div style="color:var(--gold);font-size:15px;letter-spacing:2px;"><?= str_repeat('★', (int)$c['rating']) . str_repeat('☆', 5 - (int)$c['rating']) ?></div>
            <span class="badge badge-<?= $c['is_approved'] ? 'success' : 'warning' ?>"><?= $c['is_approved'] ? 'Yayında' : 'Onay Bekliyor' ?></span>
          </div>
        </div>
        <p style="margin:0 0 12px;color:var(--text-soft);font-size:14px;line-height:1.7;"><?= nl2br(e($c['comment'])) ?></p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <?php if (!$c['is_approved']): ?>
            <a href="?approve=<?= (int)$c['id'] ?>&t=<?= e(csrf_token()) ?>" class="btn btn-sm btn-success">✓ Onayla</a>
          <?php else: ?>
            <a href="?reject=<?= (int)$c['id'] ?>&t=<?= e(csrf_token()) ?>" class="btn btn-sm btn-warning" data-confirm="Yorumu yayından kaldır?">Yayından Kaldır</a>
          <?php endif; ?>
          <a href="?delete=<?= (int)$c['id'] ?>&t=<?= e(csrf_token()) ?>" class="btn btn-sm btn-danger" data-confirm="Bu yorumu kalıcı olarak silmek istiyor musun?">Sil</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
