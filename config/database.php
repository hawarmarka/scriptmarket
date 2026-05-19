<?php
declare(strict_types=1);

/**
 * ScriptMarkt Database Config
 * Coolify / Docker / cPanel uyumlu PDO bağlantısı.
 * Öncelik: DATABASE_URL / MYSQL_URL > DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS.
 * Not: Veritabanı şifresini GitHub'a gömmemek için ENV üzerinden çalışır.
 */

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN));
}

function db_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return ($value !== false && $value !== '') ? (string)$value : $default;
}

$databaseUrl = db_env('DATABASE_URL', db_env('MYSQL_URL', ''));
if ($databaseUrl !== '') {
    $url = parse_url($databaseUrl);
    $dbHost = $url['host'] ?? 'localhost';
    $dbPort = isset($url['port']) ? (string)$url['port'] : '3306';
    $dbName = isset($url['path']) ? ltrim((string)$url['path'], '/') : 'scriptmarkt';
    $dbUser = isset($url['user']) ? urldecode((string)$url['user']) : 'root';
    $dbPass = isset($url['pass']) ? urldecode((string)$url['pass']) : '';
} else {
    $dbHost = db_env('DB_HOST', 'localhost');
    $dbPort = db_env('DB_PORT', '3306');
    $dbName = db_env('DB_NAME', 'scriptmarkt');
    $dbUser = db_env('DB_USER', 'root');
    $dbPass = db_env('DB_PASS', '');
}

if (!defined('DB_HOST')) define('DB_HOST', $dbHost);
if (!defined('DB_PORT')) define('DB_PORT', $dbPort);
if (!defined('DB_NAME')) define('DB_NAME', $dbName);
if (!defined('DB_USER')) define('DB_USER', $dbUser);
if (!defined('DB_PASS')) define('DB_PASS', $dbPass);
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

$dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 10,
];
if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
    $pdoOptions[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . DB_CHARSET . ' COLLATE utf8mb4_unicode_ci';
}
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
    $conn = $pdo;
    $db = $pdo;
} catch (PDOException $e) {
    http_response_code(500);
    if (APP_DEBUG) {
        die('<div style="font-family:Arial;background:#0f172a;color:#fff;padding:24px;border-radius:12px;margin:30px"><h2 style="color:#f87171;margin-top:0">Veritabanı bağlantı hatası</h2><p>Coolify Environment Variables içinde DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS değerlerini kontrol et.</p><pre style="white-space:pre-wrap;background:#020617;color:#f8fafc;padding:16px;border-radius:8px">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre></div>');
    }
    die('Veritabanına bağlanılamadı.');
}
