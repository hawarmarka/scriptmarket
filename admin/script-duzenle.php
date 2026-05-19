<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/_script_save_helpers.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(ADMIN_URL . '/scripts.php');
$stmt = $pdo->prepare("SELECT * FROM scripts WHERE id = ?");
$stmt->execute([$id]);
$script = $stmt->fetch();
if (!$script) { flash('error', 'Script bulunamadı.'); redirect(ADMIN_URL . '/scripts.php'); }
$categories = $pdo->query("SELECT * FROM categories ORDER BY display_order, name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { flash('error', 'Güvenlik doğrulaması başarısız.'); redirect(ADMIN_URL . '/script-duzenle.php?id=' . $id); }
    $data = admin_script_payload($_POST);
    $errors = [];
    if (mb_strlen($data['title']) < 3) $errors[] = 'Başlık en az 3 karakter olmalı.';
    if (!$data['category_id']) $errors[] = 'Kategori seçilmeli.';
    if ($data['price'] < 0) $errors[] = 'Fiyat negatif olamaz.';

    admin_delete_gallery($pdo, $id, $_POST['delete_gallery'] ?? []);

    $coverFile = $script['cover_image'];
    $newCover = admin_upload_to_scripts('cover_image', ALLOWED_IMAGE_TYPES, 'cover_', $errors);
    if ($newCover) { if ($coverFile && is_file(UPLOAD_PATH . '/scripts/' . $coverFile)) @unlink(UPLOAD_PATH . '/scripts/' . $coverFile); $coverFile = $newCover; }

    $scriptFile = $script['file_path'];
    $newPkg = admin_upload_to_scripts('script_file', ALLOWED_SCRIPT_TYPES, 'pkg_', $errors);
    if ($newPkg) { if ($scriptFile && is_file(UPLOAD_PATH . '/scripts/' . $scriptFile)) @unlink(UPLOAD_PATH . '/scripts/' . $scriptFile); $scriptFile = $newPkg; }

    $slug = $data['title'] !== $script['title'] ? unique_slug($pdo, slugify($data['title']), $id) : $script['slug'];

    if (!$errors) {
        $sql = "UPDATE scripts SET
            category_id=?,title=?,slug=?,short_description=?,description=?,features=?,installation_info=?,
            version=?,last_update=?,file_size=?,license_type=?,license_monthly_enabled=?,license_monthly_price=?,
            license_yearly_enabled=?,license_yearly_price=?,license_lifetime_enabled=?,license_lifetime_price=?,
            price=?,discount_price=?,is_free=?,free_download_url=?,cover_image=?,demo_url=?,admin_demo_url=?,admin_demo_info=?,file_path=?,tags=?,product_badge_text=?,support_included=?,
            is_featured=?,is_bestseller=?,is_new=?,is_active=? WHERE id=?";
        $pdo->prepare($sql)->execute([
            $data['category_id'],$data['title'],$slug,$data['short_description'],$data['description'],$data['features'],$data['installation_info'],
            $data['version'],$data['last_update'],$data['file_size'],$data['license_type'],$data['license_monthly_enabled'],$data['license_monthly_price'],
            $data['license_yearly_enabled'],$data['license_yearly_price'],$data['license_lifetime_enabled'],$data['license_lifetime_price'],
            $data['price'],$data['discount_price'],$data['is_free'],$data['free_download_url'],$coverFile,$data['demo_url'],$data['admin_demo_url'],$data['admin_demo_info'],$scriptFile,$data['tags'],$data['product_badge_text'],$data['support_included'],
            $data['is_featured'],$data['is_bestseller'],$data['is_new'],$data['is_active'],$id
        ]);
        admin_upload_gallery($pdo, $id, $errors);
        if (!$errors) { flash('success', 'Script güncellendi.'); redirect(ADMIN_URL . '/scripts.php'); }
    }
    foreach ($errors as $err) flash('error', $err);
    $stmt = $pdo->prepare("SELECT * FROM scripts WHERE id = ?");
    $stmt->execute([$id]);
    $script = array_merge($stmt->fetch() ?: $script, $_POST);
}

$galleryStmt = $pdo->prepare("SELECT * FROM script_images WHERE script_id = ? ORDER BY display_order, id");
$galleryStmt->execute([$id]);
$galleryImages = $galleryStmt->fetchAll();
$adminPageTitle = 'Script Düzenle';
require __DIR__ . '/_layout.php';
?>
<div style="margin-bottom:16px;"><a href="<?= ADMIN_URL ?>/scripts.php" class="btn btn-ghost btn-sm">← Scriptlere Dön</a></div>
<div class="admin-card"><div class="admin-card-head"><h3>Düzenle: <?= e($script['title']) ?></h3><a href="<?= PUBLIC_URL ?>/script-detay.php?slug=<?= e($script['slug']) ?>" target="_blank" class="btn btn-sm btn-secondary">Sitede Görüntüle ↗</a></div><?php require __DIR__ . '/_script_form.php'; ?></div>
<?php require __DIR__ . '/_layout_end.php'; ?>
