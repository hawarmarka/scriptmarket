<?php
/**
 * Güvenli dosya indirme — token bazlı
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

require_login();

$token = trim($_GET['token'] ?? '');
if (!$token) {
    http_response_code(400);
    die('Token gerekli.');
}

// Order item + sipariş bilgisi
$stmt = $pdo->prepare(
    "SELECT oi.*, o.user_id, o.payment_status, s.file_path AS script_file
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     JOIN scripts s ON s.id = oi.script_id
     WHERE oi.download_token = ?"
);
$stmt->execute([$token]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    die('Geçersiz indirme bağlantısı.');
}

// Sahiplik kontrolü
if ((int)$item['user_id'] !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    die('Bu indirme size ait değil.');
}

// Ödeme onaylanmış mı?
if (!in_array($item['payment_status'], ['onaylandi', 'teslim_edildi'], true)) {
    flash('warning', 'Bu ürünü indirebilmek için ödemenizin onaylanması gerekiyor.');
    redirect(PUBLIC_URL . '/siparislerim.php');
}

// Dosya yolu
$filePath = $item['script_file'];
if (!$filePath) {
    flash('error', 'Bu ürün için dosya henüz yüklenmemiş. Lütfen destek ile iletişime geçin.');
    redirect(PUBLIC_URL . '/siparislerim.php');
}

$fullPath = UPLOAD_PATH . '/scripts/' . $filePath;
if (!file_exists($fullPath)) {
    flash('error', 'Dosya sunucuda bulunamadı. Destek ile iletişime geçin.');
    redirect(PUBLIC_URL . '/siparislerim.php');
}

// İndirmeyi logla
$pdo->prepare("INSERT INTO downloads (order_item_id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)")
    ->execute([
        $item['id'],
        $_SESSION['user_id'],
        client_ip(),
        mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
    ]);

$pdo->prepare("UPDATE order_items SET download_count = download_count + 1 WHERE id = ?")
    ->execute([$item['id']]);

// Sipariş durumunu teslim edildi yap
if ($item['payment_status'] === 'onaylandi') {
    $pdo->prepare("UPDATE orders SET payment_status = 'teslim_edildi' WHERE id = ?")
        ->execute([$item['order_id'] ?? 0]);
}

// Dosyayı gönder
$fileName = basename($filePath);
$contentType = 'application/octet-stream';

header('Content-Description: File Transfer');
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, no-cache, no-store');
header('Pragma: no-cache');
header('Content-Length: ' . filesize($fullPath));

// Büyük dosyalar için chunked çıktı
$handle = fopen($fullPath, 'rb');
if ($handle === false) {
    die('Dosya açılamadı.');
}
while (!feof($handle)) {
    echo fread($handle, 8192);
    flush();
}
fclose($handle);
exit;
