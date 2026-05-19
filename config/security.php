<?php
/**
 * ScriptMarkt — Güvenlik Yardımcıları
 * --------------------------------------------------------------
 * CSRF token üretimi/doğrulaması, XSS temizleme,
 * brute-force koruması, IP tespiti vb.
 */

declare(strict_types=1);

/**
 * CSRF token üret (form içine eklemek için)
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF token'ı doğrula (POST işlemlerinde)
 */
function csrf_verify(?string $token): bool
{
    if (!isset($_SESSION['csrf_token']) || !$token) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Form içine doğrudan basılabilen CSRF input alanı
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * XSS koruması (görüntüleme için)
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Yönlendirme yardımcısı
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Flash mesaj kaydet (bir sonraki istekte gösterilir)
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Flash mesajları al ve temizle
 */
function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/**
 * Kullanıcının IP'sini güvenli şekilde tespit et
 */
function client_ip(): string
{
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = explode(',', $_SERVER[$k])[0];
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

/**
 * Brute-force kontrolü: belli süre içinde çok deneme yapılmışsa true döner
 */
function is_login_locked(PDO $pdo, string $type = 'user'): bool
{
    $ip = client_ip();
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE ip_address = ? AND attempt_type = ? AND success = 0
           AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)"
    );
    $stmt->execute([$ip, $type, LOGIN_LOCKOUT_MINUTES]);
    return ((int)$stmt->fetchColumn()) >= MAX_LOGIN_ATTEMPTS;
}

/**
 * Giriş denemesini kaydet
 */
function log_login_attempt(PDO $pdo, ?string $identifier, bool $success, string $type = 'user'): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO login_attempts (ip_address, username_or_email, attempt_type, success)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([client_ip(), $identifier, $type, $success ? 1 : 0]);
}

/**
 * Dosya yükleme güvenlik kontrolü
 *
 * @param array $file $_FILES['x'] dizini
 * @param array $allowedExtensions ['jpg','png','webp'] gibi
 * @return array ['ok' => bool, 'error' => string, 'extension' => string]
 */
function validate_upload(array $file, array $allowedExtensions): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['ok' => false, 'error' => 'Geçersiz dosya parametresi.'];
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Dosya yüklenmedi.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Dosya yüklenirken hata oluştu.'];
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['ok' => false, 'error' => 'Dosya boyutu çok büyük.'];
    }
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return ['ok' => false, 'error' => 'İzin verilmeyen dosya türü.'];
    }
    return ['ok' => true, 'error' => '', 'extension' => $extension];
}
