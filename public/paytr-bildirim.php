<?php
/**
 * PayTR Ödeme Bildirimi (IPN/webhook)
 * PayTR başarılı ödeme sonrası bu URL'ye POST gönderir
 * URL: https://siteniz.com/paytr-bildirim.php
 * Admin panelinden PayTR ayarlarına bu URL'yi girin.
 */
require_once __DIR__ . '/../config/config.php';

$merchant_key  = setting('paytr_merchant_key');
$merchant_salt = setting('paytr_merchant_salt');

$postData      = $_POST;
$merchantOid   = $postData['merchant_oid']   ?? '';
$status        = $postData['status']         ?? '';
$totalAmount   = $postData['total_amount']   ?? 0;
$hash          = $postData['hash']           ?? '';
$failedReasonCode = $postData['failed_reason_code'] ?? '';
$failedReasonMsg  = $postData['failed_reason_msg']  ?? '';

// Hash doğrulama
$hashStr  = $merchantOid . $merchant_salt . $status . $totalAmount;
$expected = base64_encode(hash_hmac('sha256', $hashStr, $merchant_key, true));

if ($hash !== $expected) {
    echo 'PAYTR_HASH_MISMATCH';
    exit;
}

// Siparişi bul
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
$stmt->execute([$merchantOid]);
$order = $stmt->fetch();

if (!$order) {
    echo 'ORDER_NOT_FOUND';
    exit;
}

if ($status === 'success') {
    // Ödeme başarılı
    $pdo->prepare("UPDATE orders SET payment_status = 'onaylandi' WHERE id = ?")->execute([$order['id']]);

    // Payments tablosuna ekle
    $pdo->prepare("INSERT INTO payments (order_id, method, amount, transaction_id, status) VALUES (?, 'paytr', ?, ?, 'onaylandi')")
        ->execute([$order['id'], $totalAmount / 100, $merchantOid]);

    // Satış sayılarını artır
    $items = $pdo->prepare("SELECT DISTINCT script_id FROM order_items WHERE order_id = ?");
    $items->execute([$order['id']]);
    foreach ($items as $it) {
        $pdo->prepare("UPDATE scripts SET sales_count = sales_count + 1 WHERE id = ?")->execute([$it['script_id']]);
    }

    echo 'OK';
} else {
    // Ödeme başarısız
    $pdo->prepare("UPDATE orders SET payment_status = 'iptal', admin_note = ? WHERE id = ?")
        ->execute(["PayTR Hata: [$failedReasonCode] $failedReasonMsg", $order['id']]);

    echo 'OK';
}
