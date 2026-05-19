<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminPageTitle = 'Siparişler';

// Durum güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız.');
    } else {
        $orderId = (int)$_POST['order_id'];
        $status  = $_POST['status'] ?? '';
        $adminNote = trim($_POST['admin_note'] ?? '');
        $allowed = ['beklemede','odeme_bekliyor','onaylandi','teslim_edildi','iptal'];
        if (in_array($status, $allowed, true)) {
            $pdo->prepare("UPDATE orders SET payment_status = ?, admin_note = ? WHERE id = ?")
                ->execute([$status, $adminNote ?: null, $orderId]);

            // Onaylandıysa satış sayısını artır
            if ($status === 'onaylandi' || $status === 'teslim_edildi') {
                $items = $pdo->prepare("SELECT DISTINCT script_id FROM order_items WHERE order_id = ?");
                $items->execute([$orderId]);
                foreach ($items as $it) {
                    $pdo->prepare("UPDATE scripts SET sales_count = sales_count + 1 WHERE id = ?")
                        ->execute([$it['script_id']]);
                }
            }

            flash('success', 'Sipariş durumu güncellendi.');
        }
    }
    redirect(ADMIN_URL . '/siparisler.php' . (isset($_POST['order_id']) ? '?id=' . (int)$_POST['order_id'] : ''));
}

// Tek sipariş görüntüleme
$detailId = (int)($_GET['id'] ?? 0);
if ($detailId) {
    $stmt = $pdo->prepare(
        "SELECT o.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
         FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?"
    );
    $stmt->execute([$detailId]);
    $order = $stmt->fetch();
    if (!$order) {
        flash('error', 'Sipariş bulunamadı.');
        redirect(ADMIN_URL . '/siparisler.php');
    }

    $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemStmt->execute([$detailId]);
    $items = $itemStmt->fetchAll();

    $payStmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC");
    $payStmt->execute([$detailId]);
    $payments = $payStmt->fetchAll();

    require __DIR__ . '/_layout.php';
    [$lbl, $clr] = status_badge($order['payment_status']);
    ?>

    <div style="margin-bottom:16px;">
      <a href="<?= ADMIN_URL ?>/siparisler.php" class="btn btn-ghost btn-sm">← Tüm Siparişler</a>
    </div>

    <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:20px;" id="orderGrid">

      <div>
        <div class="admin-card">
          <div class="admin-card-head">
            <h3>Sipariş <span class="mono" style="color:var(--cyan);"><?= e($order['order_number']) ?></span></h3>
            <span class="badge badge-<?= $clr ?>"><?= e($lbl) ?></span>
          </div>

          <table class="meta-table">
            <tr><td>Müşteri</td><td><strong><?= e($order['user_name']) ?></strong><br><a href="mailto:<?= e($order['user_email']) ?>" style="font-size:13px;color:var(--cyan);"><?= e($order['user_email']) ?></a><?= $order['user_phone'] ? '<br><span style="font-size:13px;">' . e($order['user_phone']) . '</span>' : '' ?></td></tr>
            <tr><td>Tarih</td><td><?= format_date($order['created_at'], true) ?></td></tr>
            <tr><td>Ödeme Yöntemi</td><td><?= e($order['payment_method']) ?></td></tr>
            <tr><td>Ara Toplam</td><td><?= format_price((float)$order['subtotal']) ?></td></tr>
            <?php if ($order['discount'] > 0): ?>
              <tr><td>İndirim<?= $order['coupon_code'] ? ' (' . e($order['coupon_code']) . ')' : '' ?></td><td style="color:var(--success);">- <?= format_price((float)$order['discount']) ?></td></tr>
            <?php endif; ?>
            <tr><td><strong>Toplam</strong></td><td style="font-size:18px;font-weight:600;color:var(--cyan);"><?= format_price((float)$order['total']) ?></td></tr>
            <?php if ($order['customer_note']): ?>
              <tr><td>Müşteri Notu</td><td><?= nl2br(e($order['customer_note'])) ?></td></tr>
            <?php endif; ?>
          </table>
        </div>

        <div class="admin-card">
          <div class="admin-card-head"><h3>Ürünler ve Lisanslar</h3></div>
          <?php foreach ($items as $item): ?>
            <div style="padding:14px;background:var(--surface-2);border-radius:8px;margin-bottom:10px;">
              <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                <strong><?= e($item['script_title']) ?></strong>
                <span><?= format_price((float)$item['price']) ?></span>
              </div>
              <div style="font-size:11px;color:var(--text-mute);text-transform:uppercase;letter-spacing:.1em;">Lisans</div>
              <div class="license-box" style="margin-top:6px;font-size:13px;">
                <?= e($item['license_key']) ?>
                <button type="button" class="license-copy" data-key="<?= e($item['license_key']) ?>">Kopyala</button>
              </div>
              <div style="margin-top:10px;font-size:12px;color:var(--text-dim);">
                İndirme Token: <code><?= e(substr($item['download_token'], 0, 12)) ?>...</code> · <?= (int)$item['download_count'] ?> indirme
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($payments): ?>
        <div class="admin-card">
          <div class="admin-card-head"><h3>Ödeme Bildirimleri</h3></div>
          <?php foreach ($payments as $p): ?>
            <div style="padding:12px;background:var(--surface-2);border-radius:8px;margin-bottom:8px;">
              <div style="display:flex;justify-content:space-between;font-size:13px;">
                <span><?= e($p['method']) ?> · <?= format_price((float)$p['amount']) ?></span>
                <span class="badge badge-<?= $p['status'] === 'onaylandi' ? 'success' : ($p['status'] === 'reddedildi' ? 'danger' : 'warning') ?>"><?= e(ucfirst($p['status'])) ?></span>
              </div>
              <?php if ($p['transaction_id']): ?><div style="font-size:12px;color:var(--text-mute);margin-top:4px;">İşlem No: <?= e($p['transaction_id']) ?></div><?php endif; ?>
              <?php if ($p['customer_message']): ?><div style="font-size:13px;margin-top:6px;"><?= nl2br(e($p['customer_message'])) ?></div><?php endif; ?>
              <div style="font-size:11px;color:var(--text-dim);margin-top:6px;"><?= format_date($p['created_at'], true) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div>
        <div class="admin-card" style="position:sticky;top:80px;">
          <div class="admin-card-head"><h3>Durumu Güncelle</h3></div>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">

            <div class="form-group">
              <label class="form-label">Yeni Durum</label>
              <select name="status" class="form-select">
                <?php foreach (['beklemede'=>'Beklemede','odeme_bekliyor'=>'Ödeme Bekliyor','onaylandi'=>'Onaylandı','teslim_edildi'=>'Teslim Edildi','iptal'=>'İptal'] as $k => $v): ?>
                  <option value="<?= $k ?>" <?= $order['payment_status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Admin Notu</label>
              <textarea name="admin_note" class="form-textarea" rows="4" placeholder="İç notlar..."><?= e($order['admin_note']) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Güncelle</button>
          </form>

          <div style="margin-top:18px;padding-top:18px;border-top:1px solid var(--border);font-size:12.5px;color:var(--text-mute);line-height:1.7;">
            <strong style="color:var(--text);">İpuçları:</strong><br>
            • "Onaylandı"ya alınca satış sayısı +1 olur<br>
            • Müşteri ancak "Onaylandı" veya "Teslim Edildi" durumunda dosyayı indirebilir<br>
            • İlk indirme yapıldığında durum otomatik "Teslim Edildi"ye geçer
          </div>
        </div>
      </div>
    </div>

    <style>
    @media (max-width: 980px) { #orderGrid { grid-template-columns: 1fr !important; } }
    </style>

    <?php
    require __DIR__ . '/_layout_end.php';
    exit;
}

// Liste
$filter = $_GET['filter'] ?? '';
$where = ['1=1'];
$params = [];
if ($filter && in_array($filter, ['beklemede','odeme_bekliyor','onaylandi','teslim_edildi','iptal'])) {
    $where[] = 'o.payment_status = ?';
    $params[] = $filter;
}
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare(
    "SELECT o.*, u.name AS user_name, u.email AS user_email
     FROM orders o
     JOIN users u ON u.id = o.user_id
     WHERE $whereSql
     ORDER BY o.created_at DESC"
);
$stmt->execute($params);
$orders = $stmt->fetchAll();

require __DIR__ . '/_layout.php';
?>

<div class="admin-card">
  <div class="admin-card-head">
    <h3>Tüm Siparişler (<?= count($orders) ?>)</h3>
  </div>

  <div class="filter-chips">
    <a href="?" class="filter-chip <?= !$filter ? 'active' : '' ?>">Tümü</a>
    <a href="?filter=beklemede" class="filter-chip <?= $filter === 'beklemede' ? 'active' : '' ?>">Beklemede</a>
    <a href="?filter=odeme_bekliyor" class="filter-chip <?= $filter === 'odeme_bekliyor' ? 'active' : '' ?>">Ödeme Bekliyor</a>
    <a href="?filter=onaylandi" class="filter-chip <?= $filter === 'onaylandi' ? 'active' : '' ?>">Onaylandı</a>
    <a href="?filter=teslim_edildi" class="filter-chip <?= $filter === 'teslim_edildi' ? 'active' : '' ?>">Teslim Edildi</a>
    <a href="?filter=iptal" class="filter-chip <?= $filter === 'iptal' ? 'active' : '' ?>">İptal</a>
  </div>

  <?php if (empty($orders)): ?>
    <p class="text-mute">Sipariş bulunamadı.</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="data-table">
    <thead>
      <tr><th>Sipariş No</th><th>Müşteri</th><th>Tutar</th><th>Ödeme</th><th>Durum</th><th>Tarih</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($orders as $o): [$lbl, $clr] = status_badge($o['payment_status']); ?>
        <tr>
          <td class="mono"><?= e($o['order_number']) ?></td>
          <td>
            <div style="font-weight:500;font-size:13.5px;"><?= e($o['user_name']) ?></div>
            <div style="font-size:11.5px;color:var(--text-mute);"><?= e($o['user_email']) ?></div>
          </td>
          <td><strong><?= format_price((float)$o['total']) ?></strong></td>
          <td><?= e(ucfirst($o['payment_method'])) ?></td>
          <td><span class="badge badge-<?= $clr ?>"><?= e($lbl) ?></span></td>
          <td style="color:var(--text-mute);font-size:13px;"><?= format_date($o['created_at']) ?></td>
          <td><a href="?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-secondary">Detay</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
