<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminPageTitle = 'Kullanıcılar';

// Durum değiştir (banla / aktif et)
if (isset($_GET['toggle']) && csrf_verify($_GET['t'] ?? '')) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE users SET status = CASE WHEN status='active' THEN 'banned' ELSE 'active' END WHERE id = ?")
        ->execute([$id]);
    flash('success', 'Kullanıcı durumu güncellendi.');
    redirect(ADMIN_URL . '/kullanicilar.php');
}

// Sil
if (isset($_GET['delete']) && csrf_verify($_GET['t'] ?? '')) {
    try {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([(int)$_GET['delete']]);
        flash('success', 'Kullanıcı silindi.');
    } catch (Throwable $e) {
        flash('error', 'Bu kullanıcının siparişleri var, silinemez.');
    }
    redirect(ADMIN_URL . '/kullanicilar.php');
}

// Detay
$detailId = (int)($_GET['id'] ?? 0);
if ($detailId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$detailId]);
    $user = $stmt->fetch();
    if (!$user) {
        flash('error', 'Kullanıcı bulunamadı.');
        redirect(ADMIN_URL . '/kullanicilar.php');
    }

    $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $orderStmt->execute([$detailId]);
    $userOrders = $orderStmt->fetchAll();

    $totalSpent = array_sum(array_map(function($o){
        return in_array($o['payment_status'], ['onaylandi','teslim_edildi']) ? (float)$o['total'] : 0;
    }, $userOrders));

    require __DIR__ . '/_layout.php';
    ?>
    <div style="margin-bottom:16px;">
      <a href="<?= ADMIN_URL ?>/kullanicilar.php" class="btn btn-ghost btn-sm">← Tüm Kullanıcılar</a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;" id="userGrid">

      <div class="admin-card">
        <div style="text-align:center;padding:14px 0;">
          <div class="user-avatar" style="width:80px;height:80px;font-size:28px;margin:0 auto 14px;"><?= strtoupper(mb_substr($user['name'], 0, 1)) ?></div>
          <h3 style="margin-bottom:6px;"><?= e($user['name']) ?></h3>
          <p style="font-size:13px;color:var(--text-mute);"><?= e($user['email']) ?></p>
          <?php if ($user['phone']): ?><p style="font-size:13px;color:var(--text-mute);"><?= e($user['phone']) ?></p><?php endif; ?>
          <span class="badge badge-<?= $user['status'] === 'active' ? 'success' : 'danger' ?>" style="margin-top:10px;"><?= $user['status'] === 'active' ? 'Aktif' : 'Askıda' ?></span>
        </div>

        <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:14px;">
          <table class="meta-table">
            <tr><td>Kayıt Tarihi</td><td><?= format_date($user['created_at']) ?></td></tr>
            <tr><td>Sipariş Sayısı</td><td><?= count($userOrders) ?></td></tr>
            <tr><td>Toplam Harcama</td><td><strong><?= format_price($totalSpent) ?></strong></td></tr>
          </table>
        </div>

        <div style="display:flex;gap:8px;margin-top:18px;">
          <a href="?toggle=<?= (int)$user['id'] ?>&t=<?= e(csrf_token()) ?>" class="btn <?= $user['status'] === 'active' ? 'btn-warning' : 'btn-success' ?> btn-sm" style="flex:1;" data-confirm="Bu kullanıcının durumunu değiştirmek istediğine emin misin?">
            <?= $user['status'] === 'active' ? '🚫 Askıya Al' : '✓ Aktif Et' ?>
          </a>
          <a href="mailto:<?= e($user['email']) ?>" class="btn btn-secondary btn-sm">📧</a>
        </div>
      </div>

      <div class="admin-card">
        <div class="admin-card-head"><h3>Siparişler</h3></div>
        <?php if (empty($userOrders)): ?>
          <p class="text-mute">Bu kullanıcı henüz sipariş vermemiş.</p>
        <?php else: ?>
        <table class="data-table">
          <thead><tr><th>No</th><th>Tutar</th><th>Durum</th><th>Tarih</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($userOrders as $o): [$lbl, $clr] = status_badge($o['payment_status']); ?>
              <tr>
                <td class="mono"><?= e($o['order_number']) ?></td>
                <td><strong><?= format_price((float)$o['total']) ?></strong></td>
                <td><span class="badge badge-<?= $clr ?>"><?= e($lbl) ?></span></td>
                <td style="color:var(--text-mute);font-size:13px;"><?= format_date($o['created_at']) ?></td>
                <td><a href="<?= ADMIN_URL ?>/siparisler.php?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-secondary">Detay</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

    </div>

    <style>@media (max-width: 980px) { #userGrid { grid-template-columns: 1fr !important; } }</style>

    <?php
    require __DIR__ . '/_layout_end.php';
    exit;
}

// Liste
$search = trim($_GET['q'] ?? '');
$where = ['1=1'];
$params = [];
if ($search) {
    $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}
$whereSql = implode(' AND ', $where);

$users = $pdo->prepare(
    "SELECT u.*,
       (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
       (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.user_id = u.id AND o.payment_status IN ('onaylandi','teslim_edildi')) AS total_spent
     FROM users u
     WHERE $whereSql
     ORDER BY u.created_at DESC"
);
$users->execute($params);
$users = $users->fetchAll();

require __DIR__ . '/_layout.php';
?>

<div class="admin-card">
  <div class="admin-card-head">
    <h3>Kullanıcılar (<?= count($users) ?>)</h3>
  </div>

  <form method="get" style="display:grid;grid-template-columns:1fr auto;gap:10px;margin-bottom:18px;">
    <div class="search-box">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="q" class="form-input" placeholder="İsim, e-posta veya telefon ara..." value="<?= e($search) ?>">
    </div>
    <button type="submit" class="btn btn-secondary">Ara</button>
  </form>

  <?php if (empty($users)): ?>
    <p class="text-mute">Kullanıcı bulunamadı.</p>
  <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="data-table">
      <thead>
        <tr><th>Ad</th><th>E-posta</th><th>Telefon</th><th>Sipariş</th><th>Harcama</th><th>Durum</th><th>Kayıt</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <div style="display:flex;gap:10px;align-items:center;">
                <div class="user-avatar" style="width:32px;height:32px;font-size:13px;"><?= strtoupper(mb_substr($u['name'], 0, 1)) ?></div>
                <strong><?= e($u['name']) ?></strong>
              </div>
            </td>
            <td style="font-size:13px;"><?= e($u['email']) ?></td>
            <td style="font-size:13px;color:var(--text-mute);"><?= e($u['phone'] ?: '-') ?></td>
            <td><?= (int)$u['order_count'] ?></td>
            <td><strong><?= format_price((float)$u['total_spent']) ?></strong></td>
            <td><span class="badge badge-<?= $u['status'] === 'active' ? 'success' : 'danger' ?>"><?= $u['status'] === 'active' ? 'Aktif' : 'Askıda' ?></span></td>
            <td style="font-size:12.5px;color:var(--text-mute);"><?= format_date($u['created_at']) ?></td>
            <td>
              <div class="table-actions">
                <a href="?id=<?= (int)$u['id'] ?>" class="btn-icon-sm primary" title="Detay"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>
                <a href="?toggle=<?= (int)$u['id'] ?>&t=<?= e(csrf_token()) ?>" class="btn-icon-sm" data-confirm="Durumu değiştirilsin mi?" title="Aktif/Askıya Al">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </a>
                <a href="?delete=<?= (int)$u['id'] ?>&t=<?= e(csrf_token()) ?>" class="btn-icon-sm danger" data-confirm="Bu kullanıcıyı silmek istediğine emin misin?" title="Sil"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
