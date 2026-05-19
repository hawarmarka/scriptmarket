<?php
/**
 * Ödeme bildirimi — kullanıcı ödediğinde POST'lar
 * Eğer auto_delivery aktifse otomatik sipariş onaylanır ve lisans/indirme açılır
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(PUBLIC_URL . '/hesabim.php');
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    flash('error', 'Güvenlik doğrulaması başarısız.');
    redirect(PUBLIC_URL . '/hesabim.php');
}

$orderId = (int)($_POST['order_id'] ?? 0);
$tx      = trim($_POST['transaction_id'] ?? '');
$msg     = trim($_POST['customer_message'] ?? '');

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) {
    flash('error', 'Sipariş bulunamadı.');
    redirect(PUBLIC_URL . '/siparislerim.php');
}

// Ödeme bildirimi kaydet
$ins = $pdo->prepare(
    "INSERT INTO payments (order_id, method, amount, transaction_id, customer_message, status)
     VALUES (?, ?, ?, ?, ?, 'beklemede')"
);
$ins->execute([
    $orderId,
    $order['payment_method'],
    $order['total'],
    $tx ?: null,
    $msg ?: null
]);

// OTOMATİK TESLİMAT — eğer admin ayarlarından aktifse
$autoDelivery = setting('auto_delivery') === '1';

if ($autoDelivery) {
    // Sipariş otomatik onaylanır — müşteri hemen indirebilir
    $pdo->prepare("UPDATE orders SET payment_status = 'onaylandi', admin_note = CONCAT(COALESCE(admin_note,''), '\n[OTOMATİK ONAY] Ödeme bildirimi sonrası anlık teslim.') WHERE id = ?")
        ->execute([$orderId]);

    // Ödeme kaydını da onaylı yap
    $pdo->prepare("UPDATE payments SET status = 'onaylandi' WHERE order_id = ? AND status = 'beklemede'")
        ->execute([$orderId]);

    // Scriptlerin satış sayılarını artır
    $items = $pdo->prepare("SELECT DISTINCT script_id FROM order_items WHERE order_id = ?");
    $items->execute([$orderId]);
    foreach ($items as $it) {
        $pdo->prepare("UPDATE scripts SET sales_count = sales_count + 1 WHERE id = ?")
            ->execute([$it['script_id']]);
    }

    flash('success', '✓ Ödemen onaylandı! Lisans anahtarın ve indirme linkin aktif.');
} else {
    // Manuel onay bekler
    $pdo->prepare("UPDATE orders SET payment_status = 'beklemede' WHERE id = ?")
        ->execute([$orderId]);
    flash('success', 'Ödeme bildiriminiz alındı. Onay sonrası dosyanız aktif olacak.');
}

redirect(PUBLIC_URL . '/siparis-basarili.php?no=' . urlencode($order['order_number']));
