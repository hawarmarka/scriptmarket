<?php
/**
 * Stripe Payment Intent oluşturucu
 * odeme-stripe.php'den AJAX ile çağrılır
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

header('Content-Type: application/json');

if (!is_user_logged_in()) {
    echo json_encode(['error' => 'Oturum gerekli']);
    exit;
}

$orderNo = trim($_POST['order_no'] ?? '');
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->execute([$orderNo, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['error' => 'Sipariş bulunamadı']);
    exit;
}

$secretKey = setting('stripe_secret_key');
if (!$secretKey) {
    echo json_encode(['error' => 'Stripe yapılandırılmamış']);
    exit;
}

// Stripe PHP SDK olmadan doğrudan API çağrısı
$amount = (int)round($order['total'] * 100); // Kuruş/cent

$ch = curl_init('https://api.stripe.com/v1/payment_intents');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'amount'   => $amount,
        'currency' => 'try',
        'metadata[order_number]' => $orderNo,
    ]),
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secretKey],
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$result = curl_exec($ch);
$err = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err || $httpCode < 200 || $httpCode >= 300) {
    echo json_encode(['error' => 'Stripe bağlantı hatası']);
    exit;
}

$res = json_decode($result, true);
if (isset($res['client_secret'])) {
    // Siparişi güncelle
    $pdo->prepare("UPDATE orders SET payment_status = 'beklemede' WHERE id = ?")->execute([$order['id']]);
    echo json_encode(['client_secret' => $res['client_secret']]);
} else {
    echo json_encode(['error' => $res['error']['message'] ?? 'Stripe hatası']);
}
