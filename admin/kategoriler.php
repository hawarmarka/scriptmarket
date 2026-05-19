<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminPageTitle = 'Kategoriler';

// Sil
if (isset($_GET['delete']) && csrf_verify($_GET['t'] ?? '')) {
    try {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([(int)$_GET['delete']]);
        flash('success', 'Kategori silindi.');
    } catch (Throwable $e) {
        flash('error', 'Bu kategoride ürünler var; silmeden önce ürünleri başka kategoriye taşıyın.');
    }
    redirect(ADMIN_URL . '/kategoriler.php');
}

// Ekle / Güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız.');
        redirect(ADMIN_URL . '/kategoriler.php');
    }
    $id      = (int)($_POST['id'] ?? 0);
    $name    = trim($_POST['name'] ?? '');
    $icon    = trim($_POST['icon'] ?? 'box');
    $desc    = trim($_POST['description'] ?? '');
    $order   = (int)($_POST['display_order'] ?? 0);
    $active  = isset($_POST['is_active']) ? 1 : 0;

    if (mb_strlen($name) < 2) {
        flash('error', 'Kategori adı en az 2 karakter.');
    } else {
        if ($id) {
            $pdo->prepare("UPDATE categories SET name=?, icon=?, description=?, display_order=?, is_active=? WHERE id=?")
                ->execute([$name, $icon, $desc, $order, $active, $id]);
            flash('success', 'Kategori güncellendi.');
        } else {
            // Benzersiz slug oluştur
            $baseSlug = slugify($name);
            $slug = $baseSlug;
            $i = 1;
            $check = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
            while (true) {
                $check->execute([$slug]);
                if (!$check->fetch()) break;
                $i++;
                $slug = $baseSlug . '-' . $i;
            }
            $pdo->prepare("INSERT INTO categories (name, slug, icon, description, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$name, $slug, $icon, $desc, $order, $active]);
            flash('success', 'Kategori eklendi.');
        }
    }
    redirect(ADMIN_URL . '/kategoriler.php');
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$categories = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM scripts s WHERE s.category_id = c.id) AS script_count
     FROM categories c
     ORDER BY c.display_order, c.name"
)->fetchAll();

$iconList = ['box','globe','settings','shopping-cart','gamepad','briefcase','utensils','calendar','file-text'];

require __DIR__ . '/_layout.php';
?>

<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px;" id="catGrid">

  <div class="admin-card">
    <div class="admin-card-head">
      <h3><?= $editing ? 'Kategori Düzenle' : 'Yeni Kategori' ?></h3>
    </div>

    <form method="post">
      <?= csrf_field() ?>
      <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label">Kategori Adı *</label>
        <input type="text" name="name" class="form-input" required value="<?= e($editing['name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">İkon</label>
        <select name="icon" class="form-select">
          <?php foreach ($iconList as $icon): ?>
            <option value="<?= $icon ?>" <?= ($editing['icon'] ?? 'box') === $icon ? 'selected' : '' ?>><?= $icon ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Açıklama</label>
        <textarea name="description" class="form-textarea" rows="3"><?= e($editing['description'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Sıra</label>
        <input type="number" name="display_order" class="form-input" value="<?= e($editing['display_order'] ?? '0') ?>">
      </div>
      <div class="form-group">
        <label style="display:flex;gap:8px;align-items:center;">
          <input type="checkbox" name="is_active" value="1" <?= !isset($editing['is_active']) || $editing['is_active'] ? 'checked' : '' ?> style="accent-color:var(--indigo);">
          <span>Aktif</span>
        </label>
      </div>
      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Güncelle' : 'Ekle' ?></button>
        <?php if ($editing): ?>
          <a href="<?= ADMIN_URL ?>/kategoriler.php" class="btn btn-ghost">İptal</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="admin-card">
    <div class="admin-card-head">
      <h3>Mevcut Kategoriler (<?= count($categories) ?>)</h3>
    </div>

    <?php if (empty($categories)): ?>
      <p class="text-mute">Henüz kategori yok.</p>
    <?php else: ?>
      <table class="data-table">
        <thead><tr><th>Ad</th><th>Slug</th><th>Ürün</th><th>Durum</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($categories as $c): ?>
            <tr>
              <td><?= e($c['name']) ?></td>
              <td class="mono" style="font-size:12px;color:var(--text-mute);"><?= e($c['slug']) ?></td>
              <td><?= (int)($c['script_count'] ?? 0) ?></td>
              <td><?= $c['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Pasif</span>' ?></td>
              <td>
                <div class="table-actions">
                  <a href="?edit=<?= (int)$c['id'] ?>" class="btn-icon-sm primary"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>
                  <a href="?delete=<?= (int)$c['id'] ?>&t=<?= e(csrf_token()) ?>" class="btn-icon-sm danger" data-confirm="Bu kategoriyi silmek istediğine emin misin?"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<style>
@media (max-width: 980px) { #catGrid { grid-template-columns: 1fr !important; } }
</style>

<?php require __DIR__ . '/_layout_end.php'; ?>
