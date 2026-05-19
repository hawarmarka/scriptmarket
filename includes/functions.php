<?php
/**
 * ScriptMarkt — Yardımcı Fonksiyonlar
 */

declare(strict_types=1);

function sm_bool(string $key, bool $default = false): bool
{
    $value = getenv($key);
    if ($value === false || $value === '') return $default;
    return in_array(strtolower((string)$value), ['1','true','yes','on'], true);
}

function sm_setting_cache_flush(): void
{
    $GLOBALS['__sm_setting_cache'] = null;
}

/**
 * Settings tablosundan tek bir ayarı alır.
 */
function setting(string $key, string $default = ''): string
{
    global $pdo;
    if (!array_key_exists('__sm_setting_cache', $GLOBALS) || $GLOBALS['__sm_setting_cache'] === null) {
        $GLOBALS['__sm_setting_cache'] = [];
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            foreach ($stmt as $row) {
                $GLOBALS['__sm_setting_cache'][$row['setting_key']] = (string)($row['setting_value'] ?? '');
            }
        } catch (Throwable $e) {
            return $default;
        }
    }
    return $GLOBALS['__sm_setting_cache'][$key] ?? $default;
}

function save_setting(PDO $pdo, string $key, string $value, string $group = 'general'): void
{
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group)");
    $stmt->execute([$key, $value, $group]);
    sm_setting_cache_flush();
}

function db_table_exists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function db_column_exists(PDO $pdo, string $table, string $column): bool
{
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function db_add_column_if_missing(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!db_table_exists($pdo, $table)) return;
    if (!db_column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

/**
 * Eski kurulumlarda eksik kolon ve ayarları otomatik tamamlar.
 * Böylece GitHub'dan deploy ettikten sonra tekrar SQL silip kurmak gerekmez.
 */
function sm_runtime_upgrade(PDO $pdo): void
{
    try {
        db_add_column_if_missing($pdo, 'scripts', 'license_monthly_enabled', "TINYINT(1) NOT NULL DEFAULT 0 AFTER license_type");
        db_add_column_if_missing($pdo, 'scripts', 'license_monthly_price', "DECIMAL(10,2) DEFAULT NULL AFTER license_monthly_enabled");
        db_add_column_if_missing($pdo, 'scripts', 'license_yearly_enabled', "TINYINT(1) NOT NULL DEFAULT 0 AFTER license_monthly_price");
        db_add_column_if_missing($pdo, 'scripts', 'license_yearly_price', "DECIMAL(10,2) DEFAULT NULL AFTER license_yearly_enabled");
        db_add_column_if_missing($pdo, 'scripts', 'license_lifetime_enabled', "TINYINT(1) NOT NULL DEFAULT 1 AFTER license_yearly_price");
        db_add_column_if_missing($pdo, 'scripts', 'license_lifetime_price', "DECIMAL(10,2) DEFAULT NULL AFTER license_lifetime_enabled");
        db_add_column_if_missing($pdo, 'scripts', 'is_free', "TINYINT(1) NOT NULL DEFAULT 0 AFTER discount_price");
        db_add_column_if_missing($pdo, 'scripts', 'free_download_url', "VARCHAR(500) DEFAULT NULL AFTER is_free");
        db_add_column_if_missing($pdo, 'scripts', 'product_badge_text', "VARCHAR(80) DEFAULT NULL AFTER tags");
        db_add_column_if_missing($pdo, 'scripts', 'support_included', "TINYINT(1) NOT NULL DEFAULT 1 AFTER product_badge_text");
        db_add_column_if_missing($pdo, 'order_items', 'license_label', "VARCHAR(120) DEFAULT NULL AFTER price");

        if (db_table_exists($pdo, 'settings')) {
            // Eski marka adını veritabanı ayarlarında da tek merkeze çeker.
            $oldBrand = 'Script' . 'Market';
            $oldSlugBrand = 'script' . 'market';
            $stmtBrand = $pdo->prepare("UPDATE settings SET setting_value = REPLACE(setting_value, ?, ?) WHERE setting_value LIKE ?");
            $stmtBrand->execute([$oldBrand, 'ScriptMarkt', '%' . $oldBrand . '%']);
            $stmtBrand->execute([$oldSlugBrand, 'scriptmarkt', '%' . $oldSlugBrand . '%']);

            $defaults = [
                ['site_name', 'ScriptMarkt', 'general'],
                ['site_slogan', 'Premium Script Marketplace', 'general'],
                ['footer_text', '© 2026 ScriptMarkt. Tüm hakları saklıdır.', 'general'],
                ['hero_badge_text', '// PREMIUM PHP MARKETPLACE', 'homepage'],
                ['footer_payment_methods', 'VISA, Mastercard, Bancontact, PayPal, Stripe, PayTR, Havale, USDT', 'footer'],
                ['support_widget_enabled', '1', 'support'],
                ['support_title', 'Canlı Destek', 'support'],
                ['support_subtitle', 'Sorun varsa hemen yaz, en kısa sürede yardımcı olalım.', 'support'],
                ['support_internal_message_enabled', '1', 'support'],
                ['support_whatsapp_enabled', '1', 'support'],
                ['support_telegram_enabled', '1', 'support'],
                ['live_support_url', '', 'support'],
                ['site_background_image', '', 'theme'],
                ['site_background_overlay', 'matrix', 'theme'],
            ];
            foreach ($defaults as [$key, $value, $group]) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_key = setting_key");
                $stmt->execute([$key, $value, $group]);
            }
        }
    } catch (Throwable $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('ScriptMarkt upgrade warning: ' . $e->getMessage());
        }
    }
}

/**
 * Türkçe karakter farkındalıklı slug üretir.
 */
function slugify(string $text): string
{
    $map = [
        'ç'=>'c','Ç'=>'c','ğ'=>'g','Ğ'=>'g','ı'=>'i','I'=>'i','İ'=>'i',
        'ö'=>'o','Ö'=>'o','ş'=>'s','Ş'=>'s','ü'=>'u','Ü'=>'u'
    ];
    $text = strtr($text, $map);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function format_price(float $price): string
{
    return number_format($price, 2, ',', '.') . ' ₺';
}

function format_date(?string $dateString, bool $withTime = false): string
{
    if (!$dateString) return '-';
    $timestamp = strtotime($dateString);
    if (!$timestamp) return '-';
    $months = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    $day   = date('j', $timestamp);
    $month = $months[(int)date('n', $timestamp) - 1];
    $year  = date('Y', $timestamp);
    $time  = $withTime ? ' ' . date('H:i', $timestamp) : '';
    return "$day $month $year$time";
}

function active_price(array $script): float
{
    if (isset($script['cart_price']) && $script['cart_price'] !== null) {
        return (float)$script['cart_price'];
    }
    if (!empty($script['is_free'])) return 0.0;
    return $script['discount_price'] !== null && $script['discount_price'] !== '' && (float)$script['discount_price'] > 0
        ? (float)$script['discount_price']
        : (float)$script['price'];
}

function discount_percentage(array $script): int
{
    if (!empty($script['is_free'])) return 100;
    if (empty($script['discount_price']) || (float)$script['discount_price'] >= (float)$script['price'] || (float)$script['price'] <= 0) return 0;
    return (int)round((1 - (float)$script['discount_price'] / (float)$script['price']) * 100);
}

function generate_order_number(): string
{
    return 'SMK' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

function generate_license_key(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $parts = [];
    for ($i = 0; $i < 4; $i++) {
        $chunk = '';
        for ($j = 0; $j < 4; $j++) {
            $chunk .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $parts[] = $chunk;
    }
    return 'SMK-' . implode('-', $parts);
}

function generate_download_token(): string
{
    return bin2hex(random_bytes(32));
}

function script_license_options(array $script): array
{
    $options = [];
    if (!empty($script['is_free'])) {
        $options['free'] = ['key' => 'free', 'label' => 'Ücretsiz', 'price' => 0.0, 'enabled' => true, 'hint' => 'Ücretsiz demo / topluluk sürümü'];
    }
    if (!empty($script['license_monthly_enabled'])) {
        $options['monthly'] = ['key' => 'monthly', 'label' => 'Aylık Lisans', 'price' => (float)($script['license_monthly_price'] ?? active_price($script)), 'enabled' => true, 'hint' => '1 ay kullanım'];
    }
    if (!empty($script['license_yearly_enabled'])) {
        $options['yearly'] = ['key' => 'yearly', 'label' => '1 Yıllık Lisans', 'price' => (float)($script['license_yearly_price'] ?? active_price($script)), 'enabled' => true, 'hint' => '12 ay kullanım'];
    }
    if (!empty($script['license_lifetime_enabled'])) {
        $lifetimePrice = $script['license_lifetime_price'] !== null && $script['license_lifetime_price'] !== '' ? (float)$script['license_lifetime_price'] : active_price($script);
        $options['lifetime'] = ['key' => 'lifetime', 'label' => 'Ömür Boyu Lisans', 'price' => $lifetimePrice, 'enabled' => true, 'hint' => 'Tek ödeme, kalıcı kullanım'];
    }
    if (!$options) {
        $options['default'] = ['key' => 'default', 'label' => $script['license_type'] ?: 'Standart Lisans', 'price' => active_price($script), 'enabled' => true, 'hint' => 'Standart dijital lisans'];
    }
    return $options;
}

function cart_items(): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) return [];

    // Eski sürümde sepet sadece id listesi idi. Yeni sürümde id => option map.
    $normalized = [];
    foreach ($cart as $key => $value) {
        if (is_array($value)) {
            $id = (int)($value['script_id'] ?? $key);
            if ($id > 0) $normalized[$id] = $value + ['script_id' => $id];
        } else {
            $id = (int)$value;
            if ($id > 0) $normalized[$id] = ['script_id' => $id, 'license_key' => 'default'];
        }
    }
    $_SESSION['cart'] = $normalized;
    return $normalized;
}

function cart_count(): int
{
    return count(cart_items());
}

function cart_add(int $scriptId, string $licenseKey = 'default', ?string $licenseLabel = null, ?float $licensePrice = null): void
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
    $_SESSION['cart'][$scriptId] = [
        'script_id' => $scriptId,
        'license_key' => $licenseKey,
        'license_label' => $licenseLabel,
        'license_price' => $licensePrice,
    ];
}

function cart_remove(int $scriptId): void
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) return;

    unset($_SESSION['cart'][$scriptId]);
    foreach ($_SESSION['cart'] as $key => $item) {
        $itemId = is_array($item) ? (int)($item['script_id'] ?? 0) : (int)$item;
        if ($itemId === $scriptId) {
            unset($_SESSION['cart'][$key]);
        }
    }
}
function cart_clear(): void
{
    unset($_SESSION['cart'], $_SESSION['applied_coupon']);
}

function cart_totals(PDO $pdo): array
{
    $cart = cart_items();
    if (empty($cart)) {
        return ['items' => [], 'subtotal' => 0.0, 'discount' => 0.0, 'total' => 0.0, 'coupon' => null];
    }

    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM scripts WHERE id IN ($placeholders) AND is_active = 1");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();

    $items = [];
    foreach ($rows as $row) {
        $id = (int)$row['id'];
        $cartOpt = $cart[$id] ?? [];
        $options = script_license_options($row);
        $licenseKey = $cartOpt['license_key'] ?? 'default';
        if (!isset($options[$licenseKey])) {
            $licenseKey = array_key_first($options);
        }
        $opt = $options[$licenseKey];
        $row['cart_license_key'] = $licenseKey;
        $row['cart_license_label'] = $opt['label'];
        $row['cart_price'] = (float)$opt['price'];
        $items[] = $row;
    }

    $subtotal = 0.0;
    foreach ($items as $item) {
        $subtotal += active_price($item);
    }

    $discount = 0.0;
    $coupon = $_SESSION['applied_coupon'] ?? null;
    if ($coupon && $subtotal >= (float)$coupon['min_order_total']) {
        if ($coupon['discount_type'] === 'yuzde') {
            $discount = $subtotal * ((float)$coupon['discount_value'] / 100);
        } else {
            $discount = (float)$coupon['discount_value'];
        }
        $discount = min($discount, $subtotal);
    }

    return [
        'items'    => $items,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'total'    => max(0, $subtotal - $discount),
        'coupon'   => $coupon,
    ];
}

function get_categories(PDO $pdo): array
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order, name");
    return $cache = $stmt->fetchAll();
}

function script_image_url(?string $path): string
{
    if (empty($path)) {
        return ASSETS_URL . '/images/placeholder.svg';
    }
    if (preg_match('#^https?://#i', $path)) return $path;
    return UPLOADS_URL . '/scripts/' . ltrim($path, '/');
}

function upload_asset_url(?string $file, string $folder = 'banners'): string
{
    if (!$file) return '';
    if (preg_match('#^https?://#i', $file)) return $file;
    return UPLOADS_URL . '/' . trim($folder, '/') . '/' . ltrim($file, '/');
}

function site_logo_url(): string
{
    $logo = setting('site_logo');
    if ($logo) return upload_asset_url($logo, 'banners');
    return ASSETS_URL . '/images/logo-scriptmarkt.png';
}

function site_favicon_url(): string
{
    $fav = setting('site_favicon');
    if ($fav) return upload_asset_url($fav, 'banners');
    return ASSETS_URL . '/images/logo-scriptmarkt-64.png';
}

function brand_logo_html(bool $showText = true, string $class = ''): string
{
    $name = setting('site_name', 'ScriptMarkt');
    $html = '<span class="brand-logo-wrap ' . e($class) . '"><img class="brand-logo-img" src="' . e(site_logo_url()) . '" alt="' . e($name) . '">';
    if ($showText) {
        $html .= '<span class="brand-text">' . e($name) . '<span class="brand-dot">.</span></span>';
    }
    $html .= '</span>';
    return $html;
}

function increment_views(PDO $pdo, int $scriptId): void
{
    $stmt = $pdo->prepare("UPDATE scripts SET views = views + 1 WHERE id = ?");
    $stmt->execute([$scriptId]);
}

function status_badge(string $status): array
{
    $map = [
        'beklemede'      => ['Beklemede', 'warning'],
        'odeme_bekliyor' => ['Ödeme Bekliyor', 'info'],
        'onaylandi'      => ['Onaylandı', 'primary'],
        'teslim_edildi'  => ['Teslim Edildi', 'success'],
        'iptal'          => ['İptal Edildi', 'danger'],
    ];
    return $map[$status] ?? [$status, 'secondary'];
}

function unique_slug(PDO $pdo, string $base, ?int $excludeId = null): string
{
    $slug = $base ?: 'urun';
    $i = 1;
    while (true) {
        $sql = "SELECT id FROM scripts WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) return $slug;
        $slug = $base . '-' . (++$i);
    }
}
