<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/_script_save_helpers.php';

$adminPageTitle = 'Yeni Script Ekle';
$categories = $pdo->query("SELECT * FROM categories ORDER BY display_order, name")->fetchAll();
$script = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız.');
        redirect(ADMIN_URL . '/script-ekle.php');
    }
    $data = admin_script_payload($_POST);
    $errors = [];
    if (mb_strlen($data['title']) < 3) $errors[] = 'Başlık en az 3 karakter olmalı.';
    if (!$data['category_id']) $errors[] = 'Kategori seçilmeli.';
    if ($data['price'] < 0) $errors[] = 'Fiyat negatif olamaz.';

    $coverFile = admin_upload_to_scripts('cover_image', ALLOWED_IMAGE_TYPES, 'cover_', $errors);
    $scriptFile = admin_upload_to_scripts('script_file', ALLOWED_SCRIPT_TYPES, 'pkg_', $errors);
    $slug = unique_slug($pdo, slugify($data['title']));

    if (!$errors) {
        $sql = "INSERT INTO scripts (
            category_id,title,slug,short_description,description,features,installation_info,
            version,last_update,file_size,license_type,license_monthly_enabled,license_monthly_price,
            license_yearly_enabled,license_yearly_price,license_lifetime_enabled,license_lifetime_price,
            price,discount_price,is_free,free_download_url,cover_image,demo_url,admin_demo_url,admin_demo_info,file_path,tags,product_badge_text,support_included,
            is_featured,is_bestseller,is_new,is_active
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $pdo->prepare($sql)->execute([
            $data['category_id'],$data['title'],$slug,$data['short_description'],$data['description'],$data['features'],$data['installation_info'],
            $data['version'],$data['last_update'],$data['file_size'],$data['license_type'],$data['license_monthly_enabled'],$data['license_monthly_price'],
            $data['license_yearly_enabled'],$data['license_yearly_price'],$data['license_lifetime_enabled'],$data['license_lifetime_price'],
            $data['price'],$data['discount_price'],$data['is_free'],$data['free_download_url'],$coverFile,$data['demo_url'],$data['admin_demo_url'],$data['admin_demo_info'],$scriptFile,$data['tags'],$data['product_badge_text'],$data['support_included'],
            $data['is_featured'],$data['is_bestseller'],$data['is_new'],$data['is_active']
        ]);
        $scriptId = (int)$pdo->lastInsertId();
        admin_upload_gallery($pdo, $scriptId, $errors);
        if (!$errors) {
            flash('success', 'Script başarıyla eklendi.');
            redirect(ADMIN_URL . '/scripts.php');
        }
    }
    foreach ($errors as $err) flash('error', $err);
    $script = $_POST;
}

require __DIR__ . '/_layout.php';
?>
<div style="margin-bottom:16px;"><a href="<?= ADMIN_URL ?>/scripts.php" class="btn btn-ghost btn-sm">← Scriptlere Dön</a></div>
<div class="admin-card"><div class="admin-card-head"><h3>Yeni Script Ekle</h3></div><?php require __DIR__ . '/_script_form.php'; ?></div>
<?php require __DIR__ . '/_layout_end.php'; ?>
