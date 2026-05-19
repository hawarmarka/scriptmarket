<?php
/**
 * ScriptMarkt — Uygulama Konfigürasyonu
 * --------------------------------------------------------------
 * CANLI YAYIN hazır sürüm.
 * - Ana domain kökünden çalışır: https://site.com/
 * - public klasörü dışarıdan temiz URL ile kullanılır: /assets, /scripts.php vb.
 * - Admin paneli: /admin/login.php
 */

declare(strict_types=1);

// CANLIDA false kalmalı. Hata detayı kullanıcıya gösterilmez.
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN));

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// =================== DOSYA YOLLARI ===================
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('ADMIN_PATH', BASE_PATH . '/admin');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('UPLOAD_PATH', PUBLIC_PATH . '/assets/uploads');

// =================== URL HESAPLAMA ===================
function sm_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') return true;
    if (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on') return true;
    return false;
}

$protocol = sm_is_https() ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$path = rtrim(dirname($scriptName), '/\\');

// /public veya /admin altındaki çalışmalarda ana kurulum kökünü bul.
foreach (['/public', '/admin'] as $segment) {
    if ($path === $segment || substr($path, -strlen($segment)) === $segment) {
        $path = substr($path, 0, -strlen($segment));
        break;
    }
}
$path = $path === '/' ? '' : $path;

define('SITE_URL', rtrim($protocol . $host . $path, '/'));

// Bu sürümde public klasörü URL'de görünmez. .htaccess /assets ve *.php isteklerini public içine yönlendirir.
define('PUBLIC_URL', SITE_URL);
define('ADMIN_URL', SITE_URL . '/admin');
define('ASSETS_URL', SITE_URL . '/assets');
define('UPLOADS_URL', ASSETS_URL . '/uploads');

// =================== TIMEZONE / DİL ===================
date_default_timezone_set('Europe/Brussels');
setlocale(LC_TIME, 'tr_TR.UTF-8', 'tr_TR', 'Turkish');

// =================== OTURUM AYARLARI ===================
if (session_status() === PHP_SESSION_NONE) {
    $sessionParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $path !== '' ? $path : '/',
        'domain'   => $sessionParams['domain'],
        'secure'   => sm_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('SCRIPTMARKT_SID');
    session_start();
}

// =================== YÜKLEME LİMİTLERİ ===================
define('MAX_UPLOAD_SIZE', 100 * 1024 * 1024); // 100 MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'ico']);
define('ALLOWED_SCRIPT_TYPES', ['zip', 'rar', 'tar', 'gz', '7z']);

// =================== GÜVENLİK ===================
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

require_once CONFIG_PATH . '/database.php';
require_once CONFIG_PATH . '/security.php';
require_once INCLUDES_PATH . '/functions.php';

// Eski veritabanını otomatik yeni sürüme taşır.
sm_runtime_upgrade($pdo);
