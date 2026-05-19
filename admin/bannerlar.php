<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminPageTitle = 'Bannerlar';

// Sil
if (isset($_GET['delete']) && csrf_verify($_GET['t'] ?? '')) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT image_path FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && $row['image_path']) {
        $path = UPLOAD_PATH . '/banners/' . $row['image_path'];
        if (file_exists($path)) @unlink($path);
    }
    $pdo->prepare("DELETE FROM banners WHERE id = ?")->execute([$id]);
    flash('success', 'Banner silindi.');
    redirect(ADMIN_URL . '/bannerlar.php');
}

// Toggle
if (isset($_GET['toggle']) && csrf_verify($_GET['t'] ?? '')) {
    $pdo->prepare("UPDATE banners SET is_active = 1 - is_active WHERE id = ?")->execute([(int)$_GET['toggle']]);
    redirect(ADMIN_URL . '/bannerlar.php');
}

// Ekle/Güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız.');
        redirect(ADMIN_URL . '/bannerlar.php');
    }
    $id          = (int)($_POST['id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $subtitle    = trim($_POST['subtitle'] ?? '');
    $linkUrl     = trim($_POST['link_url'] ?? '');
    $buttonText  = trim($_POST['button_text'] ?? '');
    $order       = (int)($_POST['display_order'] ?? 0);
    $active      = isset($_POST['is_active']) ? 1 : 0;

    if (mb_strlen($title) < 2) {
        flash('error', 'Başlık en az 2 karakter olmalı.');
        redirect(ADMIN_URL . '/bannerlar.php');
    }

    // Mevcut görsel
    $imagePath = null;
    if ($id) {
        $stmt = $pdo->prepare("SELECT image_path FROM banners WHERE id = ?");
        $stmt->execute([$id]);
        $imagePath = $stmt->fetchColumn();
    }

    // Yeni görsel yükle
    if (!empty($_FILES['image']['name'])) {
        $val = validate_upload($_FILES['image'], ALLOWED_IMAGE_TYPES);
        if ($val['ok']) {
            $newFile = 'banner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $val['extension'];
            $target = UPLOAD_PATH . '/banners/' . $newFile;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                if ($imagePath) {
                    $old = UPLOAD_PATH . '/banners/' . $imagePath;
                    if (file_exists($old)) @unlink($old);
                }
                $imagePath = $newFile;
            }
        } else {
            flash('error', 'Görsel: ' . $val['error']);
        }
    }

    if ($id) {
        $pdo->prepare("UPDATE banners SET title=?, subtitle=?, image_path=?, link_url=?, button_text=?, display_order=?, is_active=? WHERE id=?")
            ->execute([$title, $subtitle, $imagePath, $linkUrl, $buttonText, $order, $active, $id]);
        flash('success', 'Banner güncellendi.');
    } else {
        $pdo->prepare("INSERT INTO banners (title, subtitle, image_path, link_url, button_text, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$title, $subtitle, $imagePath, $linkUrl, $buttonText, $order, $active]);
        flash('success', 'Banner eklendi.');
    }
    redirect(ADMIN_URL . '/bannerlar.php');
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$banners = $pdo->query("SELECT * FROM banners ORDER BY display_order, id")->fetchAll();

require __DIR__ . '/_layout.php';
?>

<div style="display:grid;grid-template-columns:1fr 1.4fr;gap:20px;" id="bnGrid">

  <div class="admin-card">
    <div class="admin-card-head"><h3><?= $editing ? 'Banner Düzenle' : 'Yeni Banner' ?></h3></div>

    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>

      <div class="form-group">
        <label class="form-label">Başlık *</label>
        <input type="text" name="title" class="form-input" required value="<?= e($editing['title'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Alt Başlık</label>
        <input type="text" name="subtitle" class="form-input" value="<?= e($editing['subtitle'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Link URL</label>
        <input type="url" name="link_url" class="form-input" value="<?= e($editing['link_url'] ?? '') ?>" placeholder="https://...">
      </div>
      <div class="form-group">
        <label class="form-label">Buton Metni</label>
        <input type="text" name="button_text" class="form-input" value="<?= e($editing['button_text'] ?? '') ?>" placeholder="Hemen İncele">
      </div>
      <div class="form-group">
        <label class="form-label">Sıra</label>
        <input type="number" name="display_order" class="form-input" value="<?= e($editing['display_order'] ?? '0') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Görsel</label>
        <div class="file-upload-area">
          <input type="file" name="image" accept="image/*">
          <div class="upload-icon">🖼</div>
          <div class="file-label" style="font-size:13px;color:var(--text-mute);">Banner görseli (önerilen: 1920x600)</div>
        </div>
        <?php if (!empty($editing['image_path'])): ?>
          <div style="margin-top:10px;">
            <img src="<?= UPLOADS_URL ?>/banners/<?= e($editing['image_path']) ?>" style="width:100%;height:auto;border-radius:8px;border:1px solid var(--border);">
          </div>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label style="display:flex;gap:8px;align-items:center;">
          <input type="checkbox" name="is_active" value="1" <?= !isset($editing['is_active']) || $editing['is_active'] ? 'checked' : '' ?> style="accent-color:var(--indigo);">
          <span>Aktif</span>
        </label>
      </div>
      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Güncelle' : 'Ekle' ?></button>
        <?php if ($editing): ?><a href="<?= ADMIN_URL ?>/bannerlar.php" class="btn btn-ghost">İptal</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><h3>Bannerlar (<?= count($banners) ?>)</h3></div>
    <?php if (empty($banners)): ?>
      <p class="text-mute">Banner yok.</p>
    <?php else: ?>
      <?php foreach ($banners as $b): ?>
        <div style="display:grid;grid-template-columns:120px 1fr auto;gap:14px;align-items:center;padding:12px;background:var(--surface-2);border-radius:8px;margin-bottom:10px;">
          <div style="width:120px;height:60px;border-radius:6px;overflow:hidden;background:var(--bg-deep);">
            <?php if (!empty($b['image_path'])): ?>
              <img src="<?= UPLOADS_URL ?>/banners/<?= e($b['image_path']) ?>" style="width:100%;height:100%;object-fit:cover;">
            <?php endif; ?>
          </div>
          <div>
            <strong><?= e($b['title']) ?></strong>
            <?php if ($b['subtitle']): ?><div style="font-size:12px;color:var(--text-mute);"><?= e($b['subtitle']) ?></div><?php endif; ?>
            <span class="badge badge-<?= $b['is_active'] ? 'success' : 'danger' ?>" style="margin-top:4px;display:inline-block;font-size:10.5px;"><?= $b['is_active'] ? 'Aktif' : 'Pasif' ?></span>
          </div>
          <div class="table-actions">
            <a href="?edit=<?= (int)$b['id'] ?>" class="btn-icon-sm primary"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>
            <a href="?toggle=<?= (int)$b['id'] ?>&t=<?= e(csrf_token()) ?>" class="btn-icon-sm"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></a>
            <a href="?delete=<?= (int)$b['id'] ?>&t=<?= e(csrf_token()) ?>" class="btn-icon-sm danger" data-confirm="Banner silinsin mi?"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<style>@media (max-width: 980px) { #bnGrid { grid-template-columns: 1fr !important; } }</style>

<?php require __DIR__ . '/_layout_end.php'; ?>
