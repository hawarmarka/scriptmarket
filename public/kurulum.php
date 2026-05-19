<?php
/**
 * ScriptMarkt — Canlı Kurulum Sihirbazı
 * --------------------------------------------------------------
 * - Boş veritabanına tabloları otomatik kurar.
 * - İlk admin hesabını oluşturur.
 * - Eski/yarım kalmış kurulumlarda temiz kurulum seçeneği sunar.
 */

require_once __DIR__ . '/../config/config.php';

$knownTables = [
    'login_attempts','favorites','messages','downloads','banners','comments','payments',
    'order_items','orders','script_images','scripts','coupons','categories','users','admins','settings'
];

function sm_table_exists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function sm_first_admin(PDO $pdo): ?array
{
    if (!sm_table_exists($pdo, 'admins')) return null;
    try {
        $stmt = $pdo->query('SELECT id, username, email, password FROM admins ORDER BY id ASC LIMIT 1');
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function sm_is_placeholder_admin(?array $admin): bool
{
    if (!$admin) return true;
    $password = (string)($admin['password'] ?? '');
    return $password === ''
        || strpos($password, 'YourHashedPasswordWillBeSet') !== false
        || strpos($password, 'DemoPasswordHashWillBeSet') !== false
        || strlen($password) < 45;
}

function sm_split_sql(string $sql): array
{
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
    $statements = [];
    $buffer = '';
    $inSingle = false;
    $inDouble = false;
    $escape = false;
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $next = $i + 1 < $len ? $sql[$i + 1] : '';

        if (!$inSingle && !$inDouble) {
            // -- yorum satırı
            if ($ch === '-' && $next === '-' && ($i === 0 || $sql[$i - 1] === "\n" || ctype_space($sql[$i - 1]))) {
                while ($i < $len && $sql[$i] !== "\n") $i++;
                $buffer .= "\n";
                continue;
            }
            // # yorum satırı
            if ($ch === '#') {
                while ($i < $len && $sql[$i] !== "\n") $i++;
                $buffer .= "\n";
                continue;
            }
            // /* blok yorum */
            if ($ch === '/' && $next === '*') {
                $i += 2;
                while ($i + 1 < $len && !($sql[$i] === '*' && $sql[$i + 1] === '/')) $i++;
                $i++;
                continue;
            }
        }

        if ($ch === "'" && !$inDouble && !$escape) {
            $inSingle = !$inSingle;
        } elseif ($ch === '"' && !$inSingle && !$escape) {
            $inDouble = !$inDouble;
        }

        if ($ch === ';' && !$inSingle && !$inDouble) {
            $stmt = trim($buffer);
            if ($stmt !== '') $statements[] = $stmt;
            $buffer = '';
            $escape = false;
            continue;
        }

        $buffer .= $ch;
        $escape = ($ch === '\\' && !$escape);
        if ($ch !== '\\') $escape = false;
    }

    $stmt = trim($buffer);
    if ($stmt !== '') $statements[] = $stmt;
    return $statements;
}

function sm_drop_known_tables(PDO $pdo, array $tables): void
{
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($tables as $table) {
        $pdo->exec('DROP TABLE IF EXISTS `' . str_replace('`', '', $table) . '`');
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

function sm_import_sql(PDO $pdo): void
{
    $sqlFile = BASE_PATH . '/database/scriptmarket.sql';
    if (!is_file($sqlFile)) {
        throw new RuntimeException('database/scriptmarket.sql dosyası bulunamadı.');
    }
    $sql = file_get_contents($sqlFile);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('SQL dosyası okunamadı veya boş.');
    }
    foreach (sm_split_sql($sql) as $stmt) {
        $pdo->exec($stmt);
    }
}

function sm_update_setting(PDO $pdo, string $key, string $value, string $group = 'general'): void
{
    if (!sm_table_exists($pdo, 'settings')) return;
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group)");
    $stmt->execute([$key, $value, $group]);
}

function sm_template(string $title, string $body, bool $error = false): void
{
    $color = $error ? '#ef4444' : '#10b981';
    echo '<!doctype html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . htmlspecialchars($title) . ' — ScriptMarkt</title><style>
    *{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Inter,-apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;background:#050816;color:#f8fafc;padding:28px;background-image:radial-gradient(circle at 20% 20%,rgba(99,102,241,.20),transparent 32%),radial-gradient(circle at 80% 70%,rgba(34,211,238,.13),transparent 35%)}
    .wrap{max-width:720px;margin:0 auto}.brand{text-align:center;margin:24px 0}.logo{width:68px;height:68px;border-radius:22px;background:linear-gradient(135deg,#6366f1,#22d3ee);display:grid;place-items:center;margin:0 auto 14px;font-weight:900;font-size:26px;box-shadow:0 24px 70px rgba(99,102,241,.35)}
    .card{background:rgba(15,23,42,.76);border:1px solid rgba(148,163,184,.20);border-radius:24px;padding:30px;box-shadow:0 24px 80px rgba(0,0,0,.35);backdrop-filter:blur(22px)}h1{margin:0 0 10px;font-size:26px}h2{font-size:18px;margin:0 0 10px;color:' . $color . '}p{color:#cbd5e1;line-height:1.65}.btn{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:13px;padding:12px 18px;text-decoration:none;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-weight:800;cursor:pointer}.btn-ghost{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12)}code{background:rgba(255,255,255,.10);padding:2px 6px;border-radius:6px}.alert{border-left:4px solid ' . $color . ';background:rgba(255,255,255,.06);padding:14px;border-radius:12px;margin:14px 0}.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px}
    </style></head><body><div class="wrap"><div class="brand"><div class="logo">SM</div><h1>ScriptMarkt Kurulum</h1><p>Canlı yayın kurulum ve veritabanı hazırlama sihirbazı.</p></div><div class="card">' . $body . '</div></div></body></html>';
    exit;
}

$admin = sm_first_admin($pdo);
$installed = sm_table_exists($pdo, 'admins') && sm_table_exists($pdo, 'settings') && !sm_is_placeholder_admin($admin);
$forceReset = ($_GET['force'] ?? '') === 'temiz-kurulum';
$errors = [];

if ($installed && !$forceReset) {
    sm_template('Kurulum tamamlanmış', '<h2>✓ Kurulum zaten tamamlanmış</h2><p>Bu site daha önce kurulmuş görünüyor. Güvenlik için <code>kurulum.php</code> dosyasını sunucudan silmen önerilir.</p><div class="actions"><a class="btn" href="' . htmlspecialchars(ADMIN_URL) . '/login.php">Admin Paneline Git</a><a class="btn btn-ghost" href="' . htmlspecialchars(PUBLIC_URL) . '/index.php">Siteyi Aç</a></div>', false);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.';
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $passConfirm = (string)($_POST['password_confirm'] ?? '');
    $siteName = trim($_POST['site_name'] ?? 'ScriptMarkt');
    $cleanInstall = isset($_POST['clean_install']);

    if (mb_strlen($username) < 3) $errors[] = 'Kullanıcı adı en az 3 karakter olmalı.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta adresi girin.';
    if (mb_strlen($fullName) < 3) $errors[] = 'Ad soyad en az 3 karakter olmalı.';
    if (strlen($password) < 8) $errors[] = 'Şifre en az 8 karakter olmalı.';
    if ($password !== $passConfirm) $errors[] = 'Şifreler eşleşmiyor.';

    if (!$errors) {
        try {
            $needsSchema = !sm_table_exists($pdo, 'admins') || !sm_table_exists($pdo, 'settings') || $cleanInstall;
            if ($needsSchema) {
                sm_drop_known_tables($pdo, $GLOBALS['knownTables']);
                sm_import_sql($pdo);
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $admin = sm_first_admin($pdo);
            if ($admin) {
                $stmt = $pdo->prepare('UPDATE admins SET username=?, email=?, full_name=?, password=? WHERE id=?');
                $stmt->execute([$username, $email, $fullName, $hash, $admin['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO admins (username, email, full_name, password, role) VALUES (?, ?, ?, ?, 'super')");
                $stmt->execute([$username, $email, $fullName, $hash]);
            }

            if (sm_table_exists($pdo, 'users')) {
                $randomHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE password LIKE '%DemoPasswordHashWillBeSet%' OR LENGTH(password) < 45")->execute([$randomHash]);
            }

            sm_update_setting($pdo, 'site_name', $siteName ?: 'ScriptMarkt', 'general');
            sm_update_setting($pdo, 'site_url', PUBLIC_URL, 'general');
            sm_update_setting($pdo, 'contact_email', $email, 'contact');

            @file_put_contents(BASE_PATH . '/config/installed.lock', 'Installed: ' . date('c'));

            sm_template('Kurulum tamamlandı', '<h2>✓ Kurulum başarıyla tamamlandı</h2><p>Veritabanı hazırlandı ve yönetici hesabı oluşturuldu. Güvenlik için şimdi <code>kurulum.php</code> ve <code>public/kurulum.php</code> dosyalarını sunucudan silmen önerilir.</p><div class="alert">Admin giriş adresi: <code>' . htmlspecialchars(ADMIN_URL) . '/login.php</code></div><div class="actions"><a class="btn" href="' . htmlspecialchars(ADMIN_URL) . '/login.php">Admin Paneline Giriş Yap</a><a class="btn btn-ghost" href="' . htmlspecialchars(PUBLIC_URL) . '/index.php">Siteyi Gör</a></div>', false);
        } catch (Throwable $e) {
            $debugInstall = APP_DEBUG || (($_GET['debug'] ?? '') === '1');
            $detailMessage = $e->getMessage();
            $errors[] = $debugInstall
                ? ('Kurulum hatası: ' . $detailMessage)
                : 'Kurulum sırasında hata oluştu. Detayı görmek için adresin sonuna ?debug=1 ekleyip tekrar deneyin. Büyük ihtimalle veritabanı yetkisi, SQL içe aktarma veya eksik tablo hatasıdır.';
        }
    }
}

$needsSchema = !sm_table_exists($pdo, 'admins') || !sm_table_exists($pdo, 'settings');
$checked = $needsSchema ? 'checked' : '';
$errorHtml = '';
foreach ($errors as $err) {
    $errorHtml .= '<div class="err">' . htmlspecialchars($err) . '</div>';
}

?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ScriptMarkt Kurulum</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#050816;color:#f8fafc;padding:28px;background-image:radial-gradient(circle at 20% 20%,rgba(99,102,241,.20),transparent 32%),radial-gradient(circle at 80% 70%,rgba(34,211,238,.13),transparent 35%)}
.wrap{max-width:760px;margin:0 auto}.brand{text-align:center;margin:22px 0 26px}.logo{width:72px;height:72px;border-radius:24px;background:linear-gradient(135deg,#6366f1,#22d3ee);display:grid;place-items:center;margin:0 auto 14px;font-weight:900;font-size:28px;box-shadow:0 24px 70px rgba(99,102,241,.35)}
h1{margin:0;font-size:28px}.brand p{margin:8px 0 0;color:#94a3b8}.card{background:rgba(15,23,42,.76);border:1px solid rgba(148,163,184,.20);border-radius:24px;padding:30px;box-shadow:0 24px 80px rgba(0,0,0,.35);backdrop-filter:blur(22px)}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.field{margin-bottom:16px}.full{grid-column:1/-1}label{display:block;font-size:13px;font-weight:800;color:#dbeafe;margin-bottom:7px}.input{width:100%;padding:13px 14px;border-radius:13px;border:1px solid rgba(148,163,184,.22);background:rgba(2,6,23,.68);color:#fff;outline:none}.input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.18)}
.check{display:flex;gap:12px;padding:14px;border-radius:14px;border:1px solid rgba(245,158,11,.25);background:rgba(245,158,11,.08);margin:10px 0 18px;color:#fde68a;font-size:13px;line-height:1.55}.check input{width:18px;height:18px;margin-top:2px;accent-color:#f59e0b}.err{padding:12px 14px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-left:4px solid #ef4444;border-radius:12px;margin-bottom:12px;color:#fecaca}.info{padding:14px;border-radius:14px;background:rgba(34,211,238,.08);border:1px solid rgba(34,211,238,.18);color:#cffafe;margin-bottom:18px;font-size:13px;line-height:1.65}.btn{width:100%;border:0;border-radius:15px;padding:15px 18px;background:linear-gradient(135deg,#6366f1,#8b5cf6,#22d3ee);color:#fff;font-weight:900;font-size:15px;cursor:pointer;box-shadow:0 18px 50px rgba(99,102,241,.30)}code{background:rgba(255,255,255,.10);padding:2px 6px;border-radius:6px}.muted{color:#94a3b8;font-size:12px;line-height:1.6;margin-top:14px}@media(max-width:720px){.grid{grid-template-columns:1fr}.card{padding:22px}}
</style>
</head>
<body>
<div class="wrap">
  <div class="brand">
    <div class="logo">SM</div>
    <h1>ScriptMarkt Kurulumu</h1>
    <p>Veritabanını hazırla, admin hesabını oluştur, siteyi yayına al.</p>
  </div>

  <div class="card">
    <div class="info">
      <strong>Durum:</strong>
      <?= $needsSchema ? 'Veritabanı tabloları eksik görünüyor. Temiz kurulum ile tablolar otomatik kurulacak.' : 'Veritabanı tabloları bulundu. Admin hesabı placeholder olduğu için kurulum tamamlanacak.' ?>
      <br>Site adresi otomatik: <code><?= htmlspecialchars(PUBLIC_URL) ?></code>
    </div>

    <?= $errorHtml ?>

    <form method="post" autocomplete="on">
      <?= csrf_field() ?>
      <div class="grid">
        <div class="field full">
          <label>Site Adı</label>
          <input class="input" name="site_name" value="<?= htmlspecialchars($_POST['site_name'] ?? 'ScriptMarkt') ?>" required>
        </div>
        <div class="field">
          <label>Admin Kullanıcı Adı</label>
          <input class="input" name="username" value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>" required minlength="3">
        </div>
        <div class="field">
          <label>Admin E-posta</label>
          <input class="input" type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="field full">
          <label>Ad Soyad</label>
          <input class="input" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required minlength="3">
        </div>
        <div class="field">
          <label>Yeni Admin Şifresi</label>
          <input class="input" type="password" name="password" required minlength="8" autocomplete="new-password">
        </div>
        <div class="field">
          <label>Şifre Tekrar</label>
          <input class="input" type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
        </div>
      </div>

      <label class="check">
        <input type="checkbox" name="clean_install" value="1" <?= $checked ?>>
        <span><strong>Temiz kurulum yap</strong><br>Eski ScriptMarkt tablolarını siler ve <code>database/scriptmarket.sql</code> dosyasından sıfırdan kurar. Boş veya bozuk veritabanı için bunu açık bırak.</span>
      </label>

      <button class="btn" type="submit">Kurulumu Başlat</button>
      <p class="muted">Not: Bu sayfa kurulum içindir. İşlem bittikten sonra güvenlik için <code>kurulum.php</code> dosyasını sil.</p>
    </form>
  </div>
</div>
</body>
</html>
