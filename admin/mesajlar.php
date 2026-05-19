<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminPageTitle = 'Mesajlar';

// Sil
if (isset($_GET['delete']) && csrf_verify($_GET['t'] ?? '')) {
    $pdo->prepare("DELETE FROM messages WHERE id = ?")->execute([(int)$_GET['delete']]);
    flash('success', 'Mesaj silindi.');
    redirect(ADMIN_URL . '/mesajlar.php');
}

// Okundu işaretle
if (isset($_GET['read']) && csrf_verify($_GET['t'] ?? '')) {
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([(int)$_GET['read']]);
    redirect(ADMIN_URL . '/mesajlar.php' . (isset($_GET['back']) ? '?id=' . (int)$_GET['back'] : ''));
}

// Detay
$detailId = (int)($_GET['id'] ?? 0);
if ($detailId) {
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
    $stmt->execute([$detailId]);
    $msg = $stmt->fetch();
    if (!$msg) {
        flash('error', 'Mesaj bulunamadı.');
        redirect(ADMIN_URL . '/mesajlar.php');
    }
    // Otomatik okundu işaretle
    if (!$msg['is_read']) {
        $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([$detailId]);
    }

    require __DIR__ . '/_layout.php';
    ?>
    <div style="margin-bottom:16px;">
      <a href="<?= ADMIN_URL ?>/mesajlar.php" class="btn btn-ghost btn-sm">← Tüm Mesajlar</a>
    </div>

    <div class="admin-card" style="max-width: 800px;">
      <div class="admin-card-head">
        <h3><?= e($msg['subject'] ?: 'Konu belirtilmemiş') ?></h3>
        <span style="font-size:12.5px;color:var(--text-mute);"><?= format_date($msg['created_at'], true) ?></span>
      </div>

      <table class="meta-table">
        <tr><td>Gönderen</td><td><strong><?= e($msg['name']) ?></strong></td></tr>
        <tr><td>E-posta</td><td><a href="mailto:<?= e($msg['email']) ?>" style="color:var(--cyan);"><?= e($msg['email']) ?></a></td></tr>
        <?php if ($msg['phone']): ?><tr><td>Telefon</td><td><?= e($msg['phone']) ?></td></tr><?php endif; ?>
      </table>

      <div style="margin-top:20px;padding:18px;background:var(--surface-2);border-radius:var(--radius);border-left:3px solid var(--indigo);">
        <p style="font-size:14.5px;line-height:1.8;margin:0;color:var(--text-soft);"><?= nl2br(e($msg['message'])) ?></p>
      </div>

      <div style="margin-top:20px;display:flex;gap:8px;flex-wrap:wrap;">
        <a href="mailto:<?= e($msg['email']) ?>?subject=Re:%20<?= rawurlencode($msg['subject'] ?: 'Mesajınız hakkında') ?>" class="btn btn-primary">📧 Cevap Yaz</a>
        <a href="?delete=<?= (int)$msg['id'] ?>&t=<?= e(csrf_token()) ?>" class="btn btn-danger" data-confirm="Bu mesajı silmek istiyor musun?">Sil</a>
      </div>
    </div>
    <?php
    require __DIR__ . '/_layout_end.php';
    exit;
}

// Liste
$filter = $_GET['filter'] ?? '';
$where = ['1=1'];
if ($filter === 'unread') $where[] = 'is_read = 0';
if ($filter === 'read')   $where[] = 'is_read = 1';
$whereSql = implode(' AND ', $where);

$messages = $pdo->query("SELECT * FROM messages WHERE $whereSql ORDER BY created_at DESC")->fetchAll();

require __DIR__ . '/_layout.php';
?>

<div class="admin-card">
  <div class="admin-card-head">
    <h3>İletişim Mesajları (<?= count($messages) ?>)</h3>
  </div>

  <div class="filter-chips">
    <a href="?" class="filter-chip <?= !$filter ? 'active' : '' ?>">Tümü</a>
    <a href="?filter=unread" class="filter-chip <?= $filter === 'unread' ? 'active' : '' ?>">Okunmamış</a>
    <a href="?filter=read" class="filter-chip <?= $filter === 'read' ? 'active' : '' ?>">Okunmuş</a>
  </div>

  <?php if (empty($messages)): ?>
    <p class="text-mute">Henüz mesaj yok.</p>
  <?php else: ?>
    <?php foreach ($messages as $m): ?>
      <a href="?id=<?= (int)$m['id'] ?>" style="display:block;padding:14px 18px;background:var(--surface-2);border:1px solid var(--border);border-left:3px solid <?= $m['is_read'] ? 'var(--border)' : 'var(--indigo)' ?>;border-radius:var(--radius);margin-bottom:8px;text-decoration:none;color:inherit;transition:transform .15s;" onmouseover="this.style.transform='translateX(4px)'" onmouseout="this.style.transform='translateX(0)'">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
          <div style="flex:1;min-width:0;">
            <div style="display:flex;gap:10px;align-items:center;margin-bottom:4px;">
              <strong style="font-size:14px;"><?= e($m['name']) ?></strong>
              <span style="font-size:12px;color:var(--text-mute);"><?= e($m['email']) ?></span>
              <?php if (!$m['is_read']): ?><span class="badge badge-primary" style="font-size:10px;">YENİ</span><?php endif; ?>
            </div>
            <div style="font-size:14px;margin-bottom:6px;"><?= e($m['subject'] ?: '(Konu yok)') ?></div>
            <div style="font-size:12.5px;color:var(--text-mute);"><?= e(mb_strimwidth($m['message'], 0, 100, '…')) ?></div>
          </div>
          <div style="text-align:right;font-size:12px;color:var(--text-mute);white-space:nowrap;">
            <?= format_date($m['created_at']) ?>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_layout_end.php'; ?>
