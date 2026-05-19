<?php
/**
 * Stripe ödeme doğrulama handler
 * Kart ödemesi Stripe tarafından başarılı göründükten sonra sunucu tarafında tekrar doğrular.
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_user_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'Oturum gerekli']);
    exit;
}

$orderNo = trim($_POST['order_no'] ?? '');
$paymentIntentId = trim($_POST['payment_intent_id'] ?? '');

if ($orderNo === '' || $paymentIntentId === '') {
    echo json_encode(['ok' => false, 'error' => 'Eksik parametre']);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM orders WHERE order_number = ? AND user_id = ?');
$stmt->execute([$orderNo, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['ok' => false, 'error' => 'Sipariş bulunamadı']);
    exit;
}

$secretKey = setting('stripe_secret_key');
if (!$secretKey) {
    echo json_encode(['ok' => false, 'error' => 'Stripe yapılandırılmamış']);
    exit;
}

$ch = curl_init('https://api.stripe.com/v1/payment_intents/' . rawurlencode($paymentIntentId));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secretKey],
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$result = curl_exec($ch);
$err = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err || $httpCode < 200 || $httpCode >= 300) {
    echo json_encode(['ok' => false, 'error' => 'Stripe doğrulaması yapılamadı']);
    exit;
}

$pi = json_decode($result, true);
if (!is_array($pi)) {
    echo json_encode(['ok' => false, 'error' => 'Stripe yanıtı okunamadı']);
    exit;
}

$expectedAmount = (int)round((float)$order['total'] * 100);
$paidAmount = (int)($pi['amount_received'] ?? $pi['amount'] ?? 0);
$metadataOrder = (string)($pi['metadata']['order_number'] ?? '');

if (($pi['status'] ?? '') !== 'succeeded' || $paidAmount < $expectedAmount || $metadataOrder !== $orderNo) {
    echo json_encode(['ok' => false, 'error' => 'Ödeme doğrulanamadı']);
    exit;
}

try {
    $pdo->beginTransaction();

    $pdo->prepare("UPDATE orders SET payment_status = 'onaylandi' WHERE id = ?")->execute([$order['id']]);

    $exists = $pdo->prepare('SELECT id FROM payments WHERE transaction_id = ? LIMIT 1');
    $exists->execute([$paymentIntentId]);
    if (!$exists->fetch()) {
        $pdo->prepare("INSERT INTO payments (order_id, method, amount, transaction_id, status) VALUES (?, 'stripe', ?, ?, 'onaylandi')")
            ->execute([$order['id'], $order['total'], $paymentIntentId]);

        $items = $pdo->prepare('SELECT DISTINCT script_id FROM order_items WHERE order_id = ?');
        $items->execute([$order['id']]);
        foreach ($items as $it) {
            $pdo->prepare('UPDATE scripts SET sales_count = sales_count + 1 WHERE id = ?')->execute([$it['script_id']]);
        }
    }

    $pdo->commit();
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok' => false, 'error' => APP_DEBUG ? $e->getMessage() : 'Sipariş güncellenemedi']);
}
