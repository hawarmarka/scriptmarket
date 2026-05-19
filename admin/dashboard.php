<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$adminPageTitle = 'Dashboard';

// İstatistikler
$totalSales   = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status IN ('onaylandi','teslim_edildi')")->fetchColumn();
$todaySales   = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status IN ('onaylandi','teslim_edildi') AND DATE(created_at) = CURDATE()")->fetchColumn();
$weekSales    = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status IN ('onaylandi','teslim_edildi') AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)")->fetchColumn();
$monthSales   = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status IN ('onaylandi','teslim_edildi') AND YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())")->fetchColumn();
$yearSales    = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status IN ('onaylandi','teslim_edildi') AND YEAR(created_at)=YEAR(CURDATE())")->fetchColumn();
$totalUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalScripts = (int)$pdo->query("SELECT COUNT(*) FROM scripts WHERE is_active = 1")->fetchColumn();
$pendingOrders= (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status IN ('beklemede','odeme_bekliyor')")->fetchColumn();
$totalOrders  = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// Son 7 günün satışları (grafik için)
$chartStmt = $pdo->query(
    "SELECT DATE(created_at) AS d, COUNT(*) AS adet, COALESCE(SUM(total),0) AS toplam
     FROM orders
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
       AND payment_status IN ('onaylandi','teslim_edildi','beklemede','odeme_bekliyor')
     GROUP BY DATE(created_at)
     ORDER BY d ASC"
);
$rawChart = $chartStmt->fetchAll();
$chartByDate = [];
foreach ($rawChart as $r) $chartByDate[$r['d']] = $r;

// Son 7 günü doldur
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartData[] = [
        'date'  => $date,
        'label' => date('d.m', strtotime($date)),
        'count' => (int)($chartByDate[$date]['adet'] ?? 0),
        'total' => (float)($chartByDate[$date]['toplam'] ?? 0),
    ];
}
$maxCount = max(array_column($chartData, 'count')) ?: 1;

// Son siparişler
$recentOrders = $pdo->query(
    "SELECT o.*, u.name AS user_name
     FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC LIMIT 8"
)->fetchAll();

// En çok satan ürünler
$topProducts = $pdo->query(
    "SELECT s.id, s.title, s.cover_image, s.sales_count, s.price, s.discount_price
     FROM scripts s
     WHERE s.is_active = 1 AND s.sales_count > 0
     ORDER BY s.sales_count DESC LIMIT 5"
)->fetchAll();

require __DIR__ . '/_layout.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">💰</div>
    <div class="stat-value"><?= format_price($totalSales) ?></div>
    <div class="stat-label">Toplam Satış</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">📅</div>
    <div class="stat-value"><?= format_price($todaySales) ?></div>
    <div class="stat-label">Bugünkü Satış</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">📈</div>
    <div class="stat-value"><?= format_price($weekSales) ?></div>
    <div class="stat-label">Son 7 Gün</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🗓️</div>
    <div class="stat-value"><?= format_price($monthSales) ?></div>
    <div class="stat-label">Bu Ay</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🏆</div>
    <div class="stat-value"><?= format_price($yearSales) ?></div>
    <div class="stat-label">Bu Yıl</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">📦</div>
    <div class="stat-value"><?= $totalOrders ?></div>
    <div class="stat-label">Toplam Sipariş</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">⏳</div>
    <div class="stat-value"><?= $pendingOrders ?></div>
    <div class="stat-label">Bekleyen Sipariş</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">👥</div>
    <div class="stat-value"><?= $totalUsers ?></div>
    <div class="stat-label">Toplam Kullanıcı</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🎯</div>
    <div class="stat-value"><?= $totalScripts ?></div>
    <div class="stat-label">Aktif Script</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px;" id="dashRow1">

  <div class="admin-card">
    <div class="admin-card-head">
      <h3>Son 7 Gün — Sipariş Hareketi</h3>
      <span style="font-size:12px;color:var(--text-mute);">Toplam <?= array_sum(array_column($chartData,'count')) ?> sipariş</span>
    </div>
    <div class="chart-container">
      <?php foreach ($chartData as $row): $h = ($row['count'] / $maxCount) * 100; ?>
        <div class="chart-bar-wrap">
          <div class="chart-bar" data-value="<?= $row['count'] ?>" style="height: <?= max(4, $h * 2) ?>px;"></div>
          <div class="chart-label"><?= e($row['label']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-head">
      <h3>En Çok Satanlar</h3>
      <a href="<?= ADMIN_URL ?>/scripts.php" style="font-size:12px;color:var(--cyan);">Tümü →</a>
    </div>
    <?php if (empty($topProducts)): ?>
      <p class="text-mute" style="font-size:13px;">Henüz satış yapılmamış.</p>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:10px;">
        <?php foreach ($topProducts as $p): ?>
          <div style="display:flex;gap:12px;align-items:center;padding:10px;background:var(--surface-2);border-radius:8px;">
            <div style="width:44px;height:34px;border-radius:6px;background:linear-gradient(135deg,#4c1d95,#831843);overflow:hidden;flex-shrink:0;">
              <?php if (!empty($p['cover_image'])): ?>
                <img src="<?= e(script_image_url($p['cover_image'])) ?>" style="width:100%;height:100%;object-fit:cover;">
              <?php endif; ?>
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:13.5px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($p['title']) ?></div>
              <div style="font-size:11.5px;color:var(--text-mute);"><?= (int)$p['sales_count'] ?> satış · <?= format_price(active_price($p)) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<div class="admin-card mt-3">
  <div class="admin-card-head">
    <h3>Son Siparişler</h3>
    <a href="<?= ADMIN_URL ?>/siparisler.php" class="btn btn-sm btn-secondary">Tümünü Gör</a>
  </div>

  <?php if (empty($recentOrders)): ?>
    <p class="text-mute">Henüz sipariş yok.</p>
  <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="data-table">
      <thead>
        <tr>
          <th>Sipariş No</th>
          <th>Müşteri</th>
          <th>Tutar</th>
          <th>Ödeme</th>
          <th>Durum</th>
          <th>Tarih</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($recentOrders as $o): [$lbl, $clr] = status_badge($o['payment_status']); ?>
        <tr>
          <td class="mono"><?= e($o['order_number']) ?></td>
          <td><?= e($o['user_name']) ?></td>
          <td><strong><?= format_price((float)$o['total']) ?></strong></td>
          <td><?= e(ucfirst($o['payment_method'] ?: '-')) ?></td>
          <td><span class="badge badge-<?= $clr ?>"><?= e($lbl) ?></span></td>
          <td style="color:var(--text-mute);font-size:13px;"><?= format_date($o['created_at']) ?></td>
          <td><a href="<?= ADMIN_URL ?>/siparisler.php?id=<?= (int)$o['id'] ?>" class="btn-icon-sm primary"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  <?php endif; ?>
</div>

<style>
@media (max-width: 980px) {
  #dashRow1 { grid-template-columns: 1fr !important; }
}
</style>

<?php require __DIR__ . '/_layout_end.php'; ?>
