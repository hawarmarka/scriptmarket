<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminPageTitle = 'Scriptler';

// Silme işlemi
if (isset($_GET['delete']) && csrf_verify($_GET['t'] ?? '')) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT cover_image, file_path FROM scripts WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    // Dosya silme (kapak ve script)
    if ($row) {
        foreach (['cover_image' => 'scripts', 'file_path' => 'scripts'] as $col => $folder) {
            if (!empty($row[$col])) {
                $path = UPLOAD_PATH . '/' . $folder . '/' . $row[$col];
                if (file_exists($path)) @unlink($path);
            }
        }
    }
    try {
        $pdo->prepare("DELETE FROM scripts WHERE id = ?")->execute([$id]);
        flash('success', 'Script silindi.');
    } catch (Throwable $e) {
        flash('error', 'Bu script siparişlere bağlı olduğu için silinemez. Önce pasifleştirebilirsiniz.');
    }
    redirect(ADMIN_URL . '/scripts.php');
}

// Aktif/pasif toggle
if (isset($_GET['toggle']) && csrf_verify($_GET['t'] ?? '')) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE scripts SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    flash('success', 'Durum değiştirildi.');
    redirect(ADMIN_URL . '/scripts.php');
}

// Filtre
$filter = $_GET['filter'] ?? '';
$search = trim($_GET['q'] ?? '');

$where = ['1=1'];
$params = [];
if ($filter === 'aktif')    { $where[] = 's.is_active = 1'; }
if ($filter === 'pasif')    { $where[] = 's.is_active = 0'; }
if ($filter === 'one-cikan'){ $where[] = 's.is_featured = 1'; }
if ($filter === 'cok-satan'){ $where[] = 's.is_bestseller = 1'; }
if ($filter === 'indirimli'){ $where[] = '(s.discount_price IS NOT NULL AND s.discount_price < s.price)'; }
if ($search) {
    $where[] = '(s.title LIKE ? OR s.tags LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare(
    "SELECT s.*, c.name AS category_name
     FROM scripts s
     LEFT JOIN categories c ON c.id = s.category_id
     WHERE $whereSql
     ORDER BY s.created_at DESC"
);
$stmt->execute($params);
$scripts = $stmt->fetchAll();

require __DIR__ . '/_layout.php';
?>

<div class="admin-card">
  <div class="admin-card-head">
    <h3>Tüm Scriptler (<?= count($scripts) ?>)</h3>
    <a href="<?= ADMIN_URL ?>/script-ekle.php" class="btn btn-primary">+ Yeni Script Ekle</a>
  </div>

  <form method="get" style="display:grid;grid-template-columns:1fr auto;gap:10px;margin-bottom:18px;">
    <div class="search-box">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="q" class="form-input" placeholder="Script ara..." value="<?= e($search) ?>">
    </div>
    <button type="submit" class="btn btn-secondary">Ara</button>
  </form>

  <div class="filter-chips">
    <a href="?" class="filter-chip <?= !$filter ? 'active' : '' ?>">Tümü</a>
    <a href="?filter=aktif" class="filter-chip <?= $filter === 'aktif' ? 'active' : '' ?>">Aktif</a>
    <a href="?filter=pasif" class="filter-chip <?= $filter === 'pasif' ? 'active' : '' ?>">Pasif</a>
    <a href="?filter=one-cikan" class="filter-chip <?= $filter === 'one-cikan' ? 'active' : '' ?>">Öne Çıkan</a>
    <a href="?filter=cok-satan" class="filter-chip <?= $filter === 'cok-satan' ? 'active' : '' ?>">Çok Satan</a>
    <a href="?filter=indirimli" class="filter-chip <?= $filter === 'indirimli' ? 'active' : '' ?>">İndirimli</a>
  </div>

  <?php if (empty($scripts)): ?>
    <div class="empty-state">
      <h3>Script bulunamadı</h3>
      <p>İlk scriptini eklemek için aşağıdaki butona tıkla.</p>
      <a href="<?= ADMIN_URL ?>/script-ekle.php" class="btn btn-primary">+ Script Ekle</a>
    </div>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table class="data-table">
      <thead>
        <tr>
          <th>Ürün</th>
          <th>Kategori</th>
          <th>Fiyat</th>
          <th>Satış</th>
          <th>Görüntülenme</th>
          <th>Durum</th>
          <th>İşlemler</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($scripts as $s): ?>
        <tr>
          <td>
            <div style="display:flex;gap:10px;align-items:center;">
              <div style="width:44px;height:34px;border-radius:6px;background:linear-gradient(135deg,#4c1d95,#831843);overflow:hidden;flex-shrink:0;">
                <?php if (!empty($s['cover_image'])): ?>
                  <img src="<?= e(script_image_url($s['cover_image'])) ?>" style="width:100%;height:100%;object-fit:cover;">
                <?php endif; ?>
              </div>
              <div>
                <div style="font-weight:500;font-size:13.5px;"><?= e($s['title']) ?></div>
                <div style="font-size:11.5px;color:var(--text-dim);">v<?= e($s['version']) ?></div>
              </div>
            </div>
          </td>
          <td><span class="badge badge-info"><?= e($s['category_name'] ?? '-') ?></span></td>
          <td>
            <?php if (!empty($s['discount_price']) && $s['discount_price'] < $s['price']): ?>
              <strong><?= format_price((float)$s['discount_price']) ?></strong>
              <div style="text-decoration:line-through;color:var(--text-dim);font-size:11px;"><?= format_price((float)$s['price']) ?></div>
            <?php else: ?>
              <strong><?= format_price((float)$s['price']) ?></strong>
            <?php endif; ?>
          </td>
          <td><?= (int)$s['sales_count'] ?></td>
          <td><?= number_format((int)$s['views']) ?></td>
          <td>
            <?php if ($s['is_active']): ?>
              <span class="badge badge-success">Aktif</span>
            <?php else: ?>
              <span class="badge badge-danger">Pasif</span>
            <?php endif; ?>
            <?php if ($s['is_featured']): ?><br><span class="badge badge-primary" style="margin-top:4px;">Öne Çıkan</span><?php endif; ?>
          </td>
          <td>
            <div class="table-actions">
              <a href="<?= PUBLIC_URL ?>/script-detay.php?slug=<?= e($s['slug']) ?>" target="_blank" class="btn-icon-sm" title="Görüntüle">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </a>
              <a href="<?= ADMIN_URL ?>/script-duzenle.php?id=<?= (int)$s['id'] ?>" class="btn-icon-sm primary" title="Düzenle">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </a>
              <a href="?toggle=<?= (int)$s['id'] ?>&t=<?= e(csrf_token()) ?>" class="btn-icon-sm" data-confirm="Bu scriptin durumunu değiştirmek istediğine emin misin?" title="Aktif/Pasif">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              </a>
              <a href="?delete=<?= (int)$s['id'] ?>&t=<?= e(csrf_token()) ?>" class="btn-icon-sm danger" data-confirm="Bu scripti silmek istediğine emin misin? Bu işlem geri alınamaz!" title="Sil">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              </a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
