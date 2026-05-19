<?php
/**
 * ScriptMarkt — Oturum / Yetkilendirme Yardımcıları
 */

declare(strict_types=1);

/**
 * Kullanıcı giriş yapmış mı?
 */
function is_user_logged_in(): bool
{
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Mevcut kullanıcı verisini al
 */
function current_user(PDO $pdo): ?array
{
    if (!is_user_logged_in()) return null;
    static $cache = null;
    if ($cache !== null) return $cache;
    $stmt = $pdo->prepare("SELECT id, name, email, phone, status, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $cache = $stmt->fetch() ?: null;
}

/**
 * Kullanıcı girişi zorunlu — yoksa login'e yönlendir
 */
function require_login(): void
{
    if (!is_user_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? PUBLIC_URL . '/';
        redirect(PUBLIC_URL . '/login.php');
    }
}

/**
 * Kullanıcıyı giriş yaptır
 */
function login_user(PDO $pdo, int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']     = $userId;
    $_SESSION['login_time']  = time();
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$userId]);
}

/**
 * Kullanıcı çıkışı
 */
function logout_user(): void
{
    // Sepeti koruyabiliriz; ama oturumu temizle.
    unset($_SESSION['user_id'], $_SESSION['login_time']);
    session_regenerate_id(true);
}

/* ============================================================
   ADMİN
   ============================================================ */

function is_admin_logged_in(): bool
{
    return isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0;
}

function current_admin(PDO $pdo): ?array
{
    if (!is_admin_logged_in()) return null;
    static $cache = null;
    if ($cache !== null) return $cache;
    $stmt = $pdo->prepare("SELECT id, username, email, full_name, role, last_login FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    return $cache = $stmt->fetch() ?: null;
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        redirect(ADMIN_URL . '/login.php');
    }
}

function login_admin(PDO $pdo, int $adminId): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id']    = $adminId;
    $_SESSION['admin_login'] = time();
    $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$adminId]);
}

function logout_admin(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_login']);
    session_regenerate_id(true);
}
