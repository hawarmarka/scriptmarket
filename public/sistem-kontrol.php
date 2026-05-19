<?php
/**
 * ScriptMarkt — Sistem Kontrol
 * Kurulumdan önce PHP/klasör/veritabanı durumunu kontrol eder.
 * Güvenlik için yayın sonrası silinebilir.
 */

declare(strict_types=1);

$base = dirname(__DIR__);
$checks = [];

function add_check(array &$checks, string $name, bool $ok, string $detail): void
{
    $checks[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
}

add_check($checks, 'PHP Sürümü', version_compare(PHP_VERSION, '8.0.0', '>='), 'Mevcut: ' . PHP_VERSION . ' / Gerekli: PHP 8.0+');
foreach (['pdo','pdo_mysql','mbstring','curl','openssl','json','fileinfo','session'] as $ext) {
    add_check($checks, 'PHP Extension: ' . $ext, extension_loaded($ext), extension_loaded($ext) ? 'Yüklü' : 'Eksik — hosting PHP extensions bölümünden aktif edin');
}

foreach (['public/assets/uploads','public/assets/uploads/scripts','public/assets/uploads/banners','public/assets/uploads/licenses'] as $dir) {
    $path = $base . '/' . $dir;
    add_check($checks, 'Yazma İzni: ' . $dir, is_dir($path) && is_writable($path), is_dir($path) ? (is_writable($path) ? 'Yazılabilir' : 'Yazılamıyor, izin 755/775 deneyin') : 'Klasör yok');
}

$dbStatus = 'Kontrol edilmedi';
$dbOk = false;
$dbFile = $base . '/config/database.php';
if (is_file($dbFile)) {
    $source = file_get_contents($dbFile) ?: '';
    $getConst = function (string $name) use ($source): string {
        if (preg_match("/define\\(\\s*['\"]" . preg_quote($name, '/') . "['\"]\\s*,\\s*['\"]([^'\"]*)['\"]\\s*\\)/", $source, $m)) {
            return $m[1];
        }
        return '';
    };
    $host = $getConst('DB_HOST') ?: 'localhost';
    $name = $getConst('DB_NAME');
    $user = $getConst('DB_USER');
    $pass = $getConst('DB_PASS');
    if ($name && $user && extension_loaded('pdo_mysql')) {
        try {
            $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4', $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $dbOk = true;
            $dbStatus = 'Bağlantı başarılı: ' . htmlspecialchars($name);
        } catch (Throwable $e) {
            $dbStatus = 'Bağlantı başarısız. DB_NAME/DB_USER/DB_PASS bilgilerini kontrol edin.';
        }
    } else {
        $dbStatus = 'DB bilgileri eksik veya pdo_mysql extension aktif değil.';
    }
}
add_check($checks, 'Veritabanı Bağlantısı', $dbOk, $dbStatus);

$allOk = !in_array(false, array_column($checks, 'ok'), true);
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>ScriptMarkt Sistem Kontrol</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:#050816;color:#f8fafc;font-family:Inter,-apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;padding:28px;background-image:radial-gradient(circle at 15% 25%,rgba(99,102,241,.22),transparent 35%),radial-gradient(circle at 85% 70%,rgba(34,211,238,.13),transparent 35%)}.wrap{max-width:900px;margin:auto}.top{margin:22px 0}.card{background:rgba(15,23,42,.78);border:1px solid rgba(148,163,184,.20);border-radius:24px;padding:26px;box-shadow:0 24px 80px rgba(0,0,0,.35)}h1{margin:0 0 8px;font-size:30px}p{color:#94a3b8}.status{padding:14px 16px;border-radius:15px;margin:18px 0;font-weight:800;background:<?= $allOk ? 'rgba(16,185,129,.13)' : 'rgba(245,158,11,.12)' ?>;border:1px solid <?= $allOk ? 'rgba(16,185,129,.32)' : 'rgba(245,158,11,.28)' ?>;color:<?= $allOk ? '#86efac' : '#fde68a' ?>}table{width:100%;border-collapse:collapse;overflow:hidden;border-radius:16px}td,th{padding:14px;border-bottom:1px solid rgba(148,163,184,.14);text-align:left}th{color:#cbd5e1;font-size:13px;text-transform:uppercase;letter-spacing:.05em}.ok{color:#86efac;font-weight:900}.bad{color:#fca5a5;font-weight:900}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.btn{display:inline-flex;padding:12px 16px;border-radius:13px;background:linear-gradient(135deg,#6366f1,#22d3ee);color:white;text-decoration:none;font-weight:900}.ghost{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12)}code{background:rgba(255,255,255,.10);padding:2px 6px;border-radius:6px}
</style>
</head>
<body><div class="wrap"><div class="top"><h1>ScriptMarkt Sistem Kontrol</h1><p>Canlıya almadan önce hosting gereksinimlerini kontrol et.</p></div><div class="card"><div class="status"><?= $allOk ? '✓ Sistem hazır görünüyor. /kurulum.php ile kuruluma geçebilirsin.' : '⚠ Bazı kontroller eksik. Aşağıdaki kırmızı/sarı alanları düzelt.' ?></div><table><thead><tr><th>Kontrol</th><th>Durum</th><th>Detay</th></tr></thead><tbody><?php foreach ($checks as $c): ?><tr><td><?= htmlspecialchars($c['name']) ?></td><td class="<?= $c['ok'] ? 'ok' : 'bad' ?>"><?= $c['ok'] ? 'OK' : 'DÜZELT' ?></td><td><?= $c['detail'] ?></td></tr><?php endforeach; ?></tbody></table><div class="actions"><a class="btn" href="kurulum.php">Kuruluma Git</a><a class="btn ghost" href="index.php">Siteye Git</a></div><p>Güvenlik notu: Kurulum bitince <code>sistem-kontrol.php</code> ve <code>kurulum.php</code> dosyalarını silebilirsin.</p></div></div></body></html>
