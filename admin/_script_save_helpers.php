<?php
function admin_upload_to_scripts(string $field, array $allowed, string $prefix, array &$errors): ?string
{
    if (empty($_FILES[$field]['name'])) return null;
    $val = validate_upload($_FILES[$field], $allowed);
    if (!$val['ok']) { $errors[] = $field . ': ' . $val['error']; return null; }
    $dir = UPLOAD_PATH . '/scripts';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $file = $prefix . time() . '_' . bin2hex(random_bytes(4)) . '.' . $val['extension'];
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dir . '/' . $file)) { $errors[] = $field . ': Dosya yüklenemedi.'; return null; }
    @chmod($dir . '/' . $file, 0644);
    return $file;
}

function admin_script_payload(array $post): array
{
    $featuresRaw = trim($post['features'] ?? '');
    $features = $featuresRaw ? implode('|', array_filter(array_map('trim', explode("\n", str_replace("\r", '', $featuresRaw))))) : '';
    return [
        'title' => trim($post['title'] ?? ''),
        'category_id' => (int)($post['category_id'] ?? 0),
        'price' => (float)($post['price'] ?? 0),
        'discount_price' => ($post['discount_price'] ?? '') !== '' ? (float)$post['discount_price'] : null,
        'short_description' => trim($post['short_description'] ?? ''),
        'description' => $post['description'] ?? '',
        'features' => $features,
        'installation_info' => trim($post['installation_info'] ?? ''),
        'version' => trim($post['version'] ?? '1.0.0'),
        'last_update' => $post['last_update'] ?? date('Y-m-d'),
        'file_size' => trim($post['file_size'] ?? ''),
        'license_type' => trim($post['license_type'] ?? 'Standart Lisans'),
        'license_monthly_enabled' => isset($post['license_monthly_enabled']) ? 1 : 0,
        'license_monthly_price' => ($post['license_monthly_price'] ?? '') !== '' ? (float)$post['license_monthly_price'] : null,
        'license_yearly_enabled' => isset($post['license_yearly_enabled']) ? 1 : 0,
        'license_yearly_price' => ($post['license_yearly_price'] ?? '') !== '' ? (float)$post['license_yearly_price'] : null,
        'license_lifetime_enabled' => isset($post['license_lifetime_enabled']) ? 1 : 0,
        'license_lifetime_price' => ($post['license_lifetime_price'] ?? '') !== '' ? (float)$post['license_lifetime_price'] : null,
        'is_free' => isset($post['is_free']) ? 1 : 0,
        'free_download_url' => trim($post['free_download_url'] ?? ''),
        'demo_url' => trim($post['demo_url'] ?? ''),
        'admin_demo_url' => trim($post['admin_demo_url'] ?? ''),
        'admin_demo_info' => trim($post['admin_demo_info'] ?? ''),
        'tags' => trim($post['tags'] ?? ''),
        'product_badge_text' => trim($post['product_badge_text'] ?? ''),
        'support_included' => isset($post['support_included']) ? 1 : 0,
        'is_active' => isset($post['is_active']) ? 1 : 0,
        'is_featured' => isset($post['is_featured']) ? 1 : 0,
        'is_bestseller' => isset($post['is_bestseller']) ? 1 : 0,
        'is_new' => isset($post['is_new']) ? 1 : 0,
    ];
}

function admin_upload_gallery(PDO $pdo, int $scriptId, array &$errors): void
{
    if (empty($_FILES['gallery_images']['name'][0])) return;
    $dir = UPLOAD_PATH . '/scripts';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $count = count($_FILES['gallery_images']['name']);
    for ($i = 0; $i < $count; $i++) {
        if (empty($_FILES['gallery_images']['name'][$i])) continue;
        $fileArr = [
            'name' => $_FILES['gallery_images']['name'][$i],
            'type' => $_FILES['gallery_images']['type'][$i],
            'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
            'error' => $_FILES['gallery_images']['error'][$i],
            'size' => $_FILES['gallery_images']['size'][$i],
        ];
        $val = validate_upload($fileArr, ALLOWED_IMAGE_TYPES);
        if (!$val['ok']) { $errors[] = 'Galeri görseli: ' . $val['error']; continue; }
        $file = 'gallery_' . time() . '_' . $i . '_' . bin2hex(random_bytes(3)) . '.' . $val['extension'];
        if (move_uploaded_file($fileArr['tmp_name'], $dir . '/' . $file)) {
            @chmod($dir . '/' . $file, 0644);
            $stmt = $pdo->prepare("INSERT INTO script_images (script_id, image_path, display_order) VALUES (?, ?, ?)");
            $stmt->execute([$scriptId, $file, $i]);
        } else {
            $errors[] = 'Galeri görseli yüklenemedi.';
        }
    }
}

function admin_delete_gallery(PDO $pdo, int $scriptId, array $ids): void
{
    if (!$ids) return;
    $ids = array_map('intval', $ids);
    $in = implode(',', array_fill(0, count($ids), '?'));
    $params = $ids;
    $params[] = $scriptId;
    $stmt = $pdo->prepare("SELECT id, image_path FROM script_images WHERE id IN ($in) AND script_id = ?");
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $img) {
        $path = UPLOAD_PATH . '/scripts/' . $img['image_path'];
        if (is_file($path)) @unlink($path);
    }
    $del = $pdo->prepare("DELETE FROM script_images WHERE id IN ($in) AND script_id = ?");
    $del->execute($params);
}
