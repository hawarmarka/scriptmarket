<?php
/**
 * PayPal onay handler
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

header('Content-Type: application/json');

if (!is_user_logged_in()) { echo '{"ok":false}'; exit; }

$orderNo       = trim($_POST['order_no'] ?? '');
$paypalOrderId = trim($_POST['paypal_order_id'] ?? '');
$transactionId = trim($_POST['transaction_id'] ?? '');

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->execute([$orderNo, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) { echo '{"ok":false}'; exit; }

$pdo->prepare("UPDATE orders SET payment_status = 'onaylandi' WHERE id = ?")->execute([$order['id']]);

$pdo->prepare("INSERT INTO payments (order_id, method, amount, transaction_id, status) VALUES (?, 'paypal', ?, ?, 'onaylandi')")
    ->execute([$order['id'], $order['total'], $transactionId]);

$items = $pdo->prepare("SELECT DISTINCT script_id FROM order_items WHERE order_id = ?");
$items->execute([$order['id']]);
foreach ($items as $it) {
    $pdo->prepare("UPDATE scripts SET sales_count = sales_count + 1 WHERE id = ?")->execute([$it['script_id']]);
}

echo '{"ok":true}';
