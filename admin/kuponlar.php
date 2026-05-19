<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminPageTitle = 'Kuponlar';

// Sil
if (isset($_GET['delete']) && csrf_verify($_GET['t'] ?? '')) {
    $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([(int)$_GET['delete']]);
    flash('success', 'Kupon silindi.');
    redirect(ADMIN_URL . '/kuponlar.php');
}

// Toggle aktif
if (isset($_GET['toggle']) && csrf_verify($_GET['t'] ?? '')) {
    $pdo->prepare("UPDATE coupons SET is_active = 1 - is_active WHERE id = ?")->execute([(int)$_GET['toggle']]);
    flash('success', 'Durum değiştirildi.');
    redirect(ADMIN_URL . '/kuponlar.php');
}

// Ekle/Güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız.');
        redirect(ADMIN_URL . '/kuponlar.php');
    }
    $id            = (int)($_POST['id'] ?? 0);
    $code          = strtoupper(trim($_POST['code'] ?? ''));
    $desc          = trim($_POST['description'] ?? '');
    $type          = $_POST['discount_type'] ?? 'yuzde';
    $value         = (float)($_POST['discount_value'] ?? 0);
    $minOrder      = (float)($_POST['min_order_total'] ?? 0);
    $maxUsage      = $_POST['max_usage'] !== '' ? (int)$_POST['max_usage'] : null;
    $validUntil    = $_POST['valid_until'] ?: null;
    $active        = isset($_POST['is_active']) ? 1 : 0;

    if (mb_strlen($code) < 3) {
        flash('error', 'Kupon kodu en az 3 karakter olmalı.');
    } elseif ($value <= 0) {
        flash('error', 'İndirim değeri sıfırdan büyük olmalı.');
    } elseif ($type === 'yuzde' && $value > 100) {
        flash('error', 'Yüzde indirim 100\'den büyük olamaz.');
    } else {
        if ($id) {
            $pdo->prepare("UPDATE coupons SET code=?, description=?, discount_type=?, discount_value=?, min_order_total=?, max_usage=?, valid_until=?, is_active=? WHERE id=?")
                ->execute([$code, $desc, $type, $value, $minOrder, $maxUsage, $validUntil, $active, $id]);
            flash('success', 'Kupon güncellendi.');
        } else {
            $chk = $pdo->prepare("SELECT id FROM coupons WHERE code = ?");
            $chk->execute([$code]);
            if ($chk->fetch()) {
                flash('error', 'Bu kupon kodu zaten mevcut.');
            } else {
                $pdo->prepare("INSERT INTO coupons (code, description, discount_type, discount_value, min_order_total, max_usage, valid_until, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$code, $desc, $type, $value, $minOrder, $maxUsage, $validUntil, $active]);
                flash('success', 'Kupon eklendi.');
            }
        }
    }
    redirect(ADMIN_URL . '/kuponlar.php');
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();

require __DIR__ . '/_layout.php';
?>

<div style="display:grid;grid-template-columns:1fr 1.6fr;gap:20px;" id="cpnGrid">

  <div class="admin-card">
    <div class="admin-card-head">
      <h3><?= $editing ? 'Kupon Düzenle' : 'Yeni Kupon' ?></h3>
    </div>

    <form method="post">
      <?= csrf_field() ?>
      <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>

      <div class="form-group">
        <label class="form-label">Kupon Kodu *</label>
        <input type="text" name="code" class="form-input mono" required style="text-transform:uppercase;" value="<?= e($editing['code'] ?? '') ?>" placeholder="OZEL2026">
      </div>
      <div class="form-group">
        <label class="form-label">Açıklama</label>
        <input type="text" name="description" class="form-input" value="<?= e($editing['description'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">İndirim Tipi</label>
        <select name="discount_type" class="form-select">
          <option value="yuzde" <?= ($editing['discount_type'] ?? '') === 'yuzde' ? 'selected' : '' ?>>Yüzde (%)</option>
          <option value="tutar" <?= ($editing['discount_type'] ?? '') === 'tutar' ? 'selected' : '' ?>>Tutar (₺)</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">İndirim Değeri *</label>
        <input type="number" name="discount_value" class="form-input" required step="0.01" min="0" value="<?= e($editing['discount_value'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Min. Sepet Tutarı (₺)</label>
        <input type="number" name="min_order_total" class="form-input" step="0.01" min="0" value="<?= e($editing['min_order_total'] ?? '0') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Maks. Kullanım</label>
        <input type="number" name="max_usage" class="form-input" min="0" value="<?= e($editing['max_usage'] ?? '') ?>" placeholder="Sınırsız için boş bırak">
      </div>
      <div class="form-group">
        <label class="form-label">Geçerlilik Sonu</label>
        <input type="datetime-local" name="valid_until" class="form-input" value="<?= $editing && $editing['valid_until'] ? date('Y-m-d\TH:i', strtotime($editing['valid_until'])) : '' ?>">
      </div>
      <div class="form-group">
        <label style="display:flex;gap:8px;align-items:center;">
          <input type="checkbox" name="is_active" value="1" <?= !isset($editing['is_active']) || $editing['is_active'] ? 'checked' : '' ?> style="accent-color:var(--indigo);">
          <span>Aktif</span>
        </label>
      </div>
      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Güncelle' : 'Kupon Ekle' ?></button>
        <?php if ($editing): ?><a href="<?= ADMIN_URL ?>/kuponlar.php" class="btn btn-ghost">İptal</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div class="admin-card">
    <div class="admin-card-head">
      <h3>Mevcut Kuponlar (<?= count($coupons) ?>)</h3>
    </div>

    <?php if (empty($coupons)): ?>
      <p class="text-mute">Henüz kupon yok.</p>
    <?php else: ?>
      <div style="overflow-x:auto;">
      <table class="data-table">
        <thead><tr><th>Kod</th><th>İndirim</th><th>Min.</th><th>Kullanım</th><th>Bitiş</th><th>Durum</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($coupons as $c): ?>
            <tr>
              <td class="mono"><strong><?= e($c['code']) ?></strong><?php if ($c['description']): ?><br><span style="font-size:11px;color:var(--text-mute);font-family:inherit;"><?= e($c['description']) ?></span><?php endif; ?></td>
              <td>
                <?php if ($c['discount_type'] === 'yuzde'): ?>
                  <strong>%<?= rtrim(rtrim(number_format((float)$c['discount_value'], 2), '0'), '.') ?></strong>
                <?php else: ?>
                  <strong><?= format_price((float)$c['discount_value']) ?></strong>
                <?php endif; ?>
              </td>
              <td><?= $c['min_order_total'] > 0 ? format_price((float)$c['min_order_total']) : '-' ?></td>
              <td><?= (int)$c['used_count'] ?> / <?= $c['max_usage'] ?: '∞' ?></td>
              <td style="font-size:12px;"><?= $c['valid_until'] ? format_date($c['valid_until']) : '-' ?></td>
              <td><?= $c['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Pasif</span>' ?></td>
              <td>
                <div class="table-actions">
                  <a href="?edit=<?= (int)$c['id'] ?>" class="btn-icon-sm primary"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>
                  <a href="?toggle=<?= (int)$c['id'] ?>&t=<?= e(csrf_token()) ?>" class="btn-icon-sm"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></a>
                  <a href="?delete=<?= (int)$c['id'] ?>&t=<?= e(csrf_token()) ?>" class="btn-icon-sm danger" data-confirm="Bu kuponu silmek istiyor musun?"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg></a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<style>@media (max-width: 980px) { #cpnGrid { grid-template-columns: 1fr !important; } }</style>

<?php require __DIR__ . '/_layout_end.php'; ?>
