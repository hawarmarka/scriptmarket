-- =====================================================================
-- ScriptMarkt — Veritabanı Şeması
-- MySQL 5.7+ / MariaDB 10.3+ uyumlu
-- Charset: utf8mb4_unicode_ci
-- =====================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- TABLO: admins
-- ---------------------------------------------------------------------
CREATE TABLE `admins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(150) DEFAULT NULL,
  `role` ENUM('super','editor') NOT NULL DEFAULT 'super',
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan admin (kullanıcı: admin / şifre: Admin123!)
INSERT INTO `admins` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('admin', 'admin@scriptmarkt.com', '$2y$10$YourHashedPasswordWillBeSetOnFirstRun', 'Site Yöneticisi', 'super');

-- ---------------------------------------------------------------------
-- TABLO: users
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(30) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('active','banned') NOT NULL DEFAULT 'active',
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `reset_token` VARCHAR(100) DEFAULT NULL,
  `reset_expires` DATETIME DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Örnek kullanıcı (e-posta: demo@scriptmarkt.com / şifre: Demo123!)
INSERT INTO `users` (`name`, `email`, `password`, `email_verified`) VALUES
('Demo Müşteri', 'demo@scriptmarkt.com', '$2y$10$DemoPasswordHashWillBeSet', 1);

-- ---------------------------------------------------------------------
-- TABLO: categories
-- ---------------------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `icon` VARCHAR(50) DEFAULT 'box',
  `description` TEXT,
  `display_order` INT(11) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`name`, `slug`, `icon`, `description`, `display_order`) VALUES
('Hazır Web Siteleri', 'hazir-web-siteleri', 'globe', 'Anahtar teslim hazır web site scriptleri', 1),
('Admin Panelli Sistemler', 'admin-panelli-sistemler', 'settings', 'Yönetim paneli olan komple sistemler', 2),
('E-Ticaret Scriptleri', 'e-ticaret-scriptleri', 'shopping-cart', 'Online satış sistemleri', 3),
('Oyun Scriptleri', 'oyun-scriptleri', 'gamepad', 'Minecraft, PUBG, oyun marketleri', 4),
('Kurumsal Scriptler', 'kurumsal-scriptler', 'briefcase', 'Firma siteleri ve kurumsal sistemler', 5),
('Restoran & POS', 'restoran-pos', 'utensils', 'Restoran, kafe, fırın POS sistemleri', 6),
('Randevu Sistemleri', 'randevu-sistemleri', 'calendar', 'Kuaför, klinik, salon randevu scriptleri', 7),
('Blog & Haber', 'blog-haber', 'file-text', 'Blog ve haber portalı scriptleri', 8);

-- ---------------------------------------------------------------------
-- TABLO: scripts
-- ---------------------------------------------------------------------
CREATE TABLE `scripts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `category_id` INT(11) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL UNIQUE,
  `short_description` VARCHAR(500) DEFAULT NULL,
  `description` LONGTEXT,
  `features` LONGTEXT,
  `installation_info` TEXT,
  `version` VARCHAR(20) DEFAULT '1.0.0',
  `last_update` DATE DEFAULT NULL,
  `file_size` VARCHAR(20) DEFAULT NULL,
  `license_type` VARCHAR(50) DEFAULT 'Standart Lisans',
  `license_monthly_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `license_monthly_price` DECIMAL(10,2) DEFAULT NULL,
  `license_yearly_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `license_yearly_price` DECIMAL(10,2) DEFAULT NULL,
  `license_lifetime_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `license_lifetime_price` DECIMAL(10,2) DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_price` DECIMAL(10,2) DEFAULT NULL,
  `is_free` TINYINT(1) NOT NULL DEFAULT 0,
  `free_download_url` VARCHAR(500) DEFAULT NULL,
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `demo_url` VARCHAR(500) DEFAULT NULL,
  `admin_demo_url` VARCHAR(500) DEFAULT NULL,
  `admin_demo_info` VARCHAR(255) DEFAULT NULL,
  `file_path` VARCHAR(500) DEFAULT NULL,
  `tags` VARCHAR(500) DEFAULT NULL,
  `product_badge_text` VARCHAR(80) DEFAULT NULL,
  `support_included` TINYINT(1) NOT NULL DEFAULT 1,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_bestseller` TINYINT(1) NOT NULL DEFAULT 0,
  `is_new` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `views` INT(11) NOT NULL DEFAULT 0,
  `sales_count` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_scripts_category` (`category_id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `fk_scripts_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo ürünler
INSERT INTO `scripts` (`category_id`, `title`, `slug`, `short_description`, `description`, `features`, `installation_info`, `version`, `last_update`, `file_size`, `license_type`, `price`, `discount_price`, `cover_image`, `demo_url`, `admin_demo_url`, `admin_demo_info`, `file_path`, `tags`, `is_featured`, `is_bestseller`, `is_new`, `sales_count`) VALUES
(6, 'Restoran Yönetim Scripti — Pro', 'restoran-yonetim-scripti-pro', 'Online sipariş, masa yönetimi ve menü düzenleme özellikli komple restoran scripti.',
'<p>Restoranınız için ihtiyacınız olan her şeyi tek bir panelde toplayan profesyonel çözüm. Online menü, sepet sistemi, online sipariş alma, masa yönetimi, fiş yazdırma ve detaylı admin paneli ile gelir.</p><p>PHP 8 ile yazılmış, PDO kullanır, modern responsive arayüze sahiptir. Bilgisayar, tablet ve cep telefonunda sorunsuz çalışır.</p>',
'Online menü ve sipariş sistemi|Masa yönetimi|Stok takibi|Personel yönetimi|Günlük/aylık satış raporu|Fiş yazdırma desteği|TR/EN çoklu dil|Mobil uyumlu admin paneli|SMS bildirim entegrasyonu',
'1. ZIP dosyasını cPanel public_html altına yükleyin\n2. database/restoran.sql dosyasını phpMyAdmin ile içe aktarın\n3. config/database.php içine veritabanı bilgilerinizi girin\n4. /admin paneline admin/admin123 ile giriş yapın\n5. Şifrenizi mutlaka değiştirin',
'2.4.1', '2026-04-12', '24.6 MB', 'Sınırsız Domain Lisansı', 3490.00, 2490.00, 'cover_restoran.svg', 'https://demo.example.com/restoran', 'https://demo.example.com/restoran/admin', 'Kullanıcı: admin / Şifre: admin123', NULL, 'restoran,pos,menü,online sipariş,masa', 1, 1, 0, 142),

(3, 'E-Ticaret Scripti — Premium Edition', 'eticaret-scripti-premium', 'Ödeme entegrasyonlu, çoklu satıcı destekli profesyonel e-ticaret altyapısı.',
'<p>Türkiye odaklı, tam fonksiyonlu e-ticaret çözümü. İyzico, PayTR, Stripe entegrasyonu, kargo modülü, ürün varyantları, kupon sistemi ve daha fazlası.</p>',
'Sınırsız ürün ekleme|Varyant desteği (renk, beden)|İyzico & PayTR entegrasyonu|Kargo şirketi entegrasyonu|Kupon ve kampanya sistemi|Stok yönetimi|Sipariş takip sistemi|Müşteri puanlama|SEO dostu URL yapısı|Mobil uyumlu tasarım',
'Detaylı kurulum dökümanı paket içinde mevcuttur. cPanel hostinglerde 5 dakikada kuruluma hazırdır.',
'3.1.0', '2026-04-28', '38.2 MB', 'Tek Domain Lisansı', 4990.00, 3990.00, 'cover_eticaret.svg', 'https://demo.example.com/eticaret', 'https://demo.example.com/eticaret/admin', 'Kullanıcı: admin / Şifre: admin123', NULL, 'eticaret,ödeme,iyzico,paytr,kargo', 1, 1, 1, 89),

(4, 'Minecraft Market Scripti', 'minecraft-market-scripti', 'Premium Minecraft server için VIP, coin ve ürün satışı sistemi.',
'<p>Minecraft sunucu sahipleri için tam donanımlı market scripti. Players API entegrasyonu, anlık komut gönderimi, otomatik teslimat.</p>',
'Anlık komut gönderim sistemi|RCON desteği|PayTR & Papara ödemesi|Otomatik VIP teslimi|Coin / Para sistemi|Top satıcı listesi|Discord webhook entegrasyonu|Modern oyuncu arayüzü|Admin panel ile ürün yönetimi',
'Sunucunuzda RCON aktif olmalı. Ayrıntılar README.md içinde.',
'1.8.3', '2026-03-15', '12.4 MB', 'Tek Sunucu Lisansı', 1990.00, 1490.00, 'cover_minecraft.svg', 'https://demo.example.com/mcmarket', 'https://demo.example.com/mcmarket/admin', 'Kullanıcı: admin / Şifre: admin123', NULL, 'minecraft,oyun,market,vip,coin', 1, 1, 1, 215),

(4, 'PUBG UC Satış Scripti', 'pubg-uc-satis-scripti', 'PUBG Mobile UC satışı için otomatik teslimatlı bayilik sistemi.',
'<p>PUBG UC bayileri için profesyonel çözüm. Player ID doğrulama, ödeme alma, otomatik UC kodu teslimatı.</p>',
'Player ID doğrulama|Otomatik kod teslimatı|Bayilik fiyatlandırma sistemi|Toplu kod yükleme|Müşteri bakiyesi|Kupon sistemi|7/24 mobil uyumlu|Sipariş takibi|WhatsApp bildirim',
'Standart cPanel hostingde çalışır, PHP 7.4+ gerektirir.',
'2.0.5', '2026-04-02', '8.7 MB', 'Sınırsız Domain Lisansı', 1790.00, NULL, 'cover_pubg.svg', 'https://demo.example.com/pubguc', 'https://demo.example.com/pubguc/admin', 'Kullanıcı: admin / Şifre: admin123', NULL, 'pubg,uc,bayi,oyun', 0, 1, 0, 178),

(7, 'Kuaför Randevu Yönetim Scripti', 'kuafor-randevu-scripti', 'Online randevu, müşteri kartı ve personel takip sistemi.',
'<p>Kuaför, güzellik salonu ve berberler için ücretsiz online randevu alma sistemi. SMS hatırlatma, personel takvimi.</p>',
'Online randevu alma|Personel bazlı takvim|SMS hatırlatma|Müşteri geçmişi kartı|Hizmet ve fiyat yönetimi|İstatistik raporları|Mobil uyumlu|Çoklu şube desteği|WhatsApp entegrasyon',
'Kurulum 10 dakika sürer. Detaylı PDF ve video anlatım pakette dahildir.',
'1.5.2', '2026-04-20', '14.1 MB', 'Tek Domain Lisansı', 1490.00, 990.00, 'cover_kuafor.svg', 'https://demo.example.com/kuafor', 'https://demo.example.com/kuafor/admin', 'Kullanıcı: admin / Şifre: admin123', NULL, 'kuaför,randevu,salon,berber', 0, 0, 1, 67),

(6, 'Fırın & Pastane POS Sistemi', 'firin-pastane-pos', 'Mahalle fırınları için sade ve etkili satış noktası uygulaması.',
'<p>Fırın ve pastaneler için optimize edilmiş POS sistemi. Veresiye defteri, günlük kasa, dokunmatik ekran uyumu.</p>',
'Dokunmatik ekran tasarımı|Veresiye defteri|Günlük kasa raporu|Stok takibi|Termal fiş yazıcı desteği|Personel vardiya|Müşteri grupları|Z raporu|Yedekleme sistemi',
'Termal yazıcı desteklidir. Kurulum kılavuzu paket içindedir.',
'2.2.0', '2026-04-08', '18.3 MB', 'Tek Domain Lisansı', 2290.00, NULL, 'cover_firin.svg', 'https://demo.example.com/firin', 'https://demo.example.com/firin/admin', 'Kullanıcı: admin / Şifre: admin123', NULL, 'fırın,pastane,pos,kasa', 0, 0, 1, 43),

(5, 'Kurumsal Firma Sitesi — Galaxy', 'kurumsal-galaxy', 'Modern kurumsal firma siteleri için premium tema ve CMS.',
'<p>Kurumsal firma siteleri için modern, animasyonlu, SEO uyumlu hazır script. İstediğiniz kadar sayfa ekleyip düzenleyebilirsiniz.</p>',
'Sınırsız sayfa ekleme|Modern tasarım|SEO ayarları|Çoklu dil desteği|Form ve iletişim modülü|Hizmetler bölümü|Referans gösterimi|Blog modülü|Sosyal medya entegrasyonu',
'cPanel için tek tıkla kurulum. Hazır demo içeriği ile gelir.',
'4.0.1', '2026-05-01', '32.5 MB', 'Sınırsız Domain Lisansı', 1990.00, 1490.00, 'cover_kurumsal.svg', 'https://demo.example.com/galaxy', 'https://demo.example.com/galaxy/admin', 'Kullanıcı: admin / Şifre: admin123', NULL, 'kurumsal,firma,tema,cms', 1, 0, 1, 56),

(8, 'Modern Blog Scripti — Nova', 'blog-nova', 'Yazarlar ve bloggerlar için minimalist ve hızlı blog scripti.',
'<p>Bloggerlar için optimize edilmiş hızlı, SEO uyumlu, dark mode destekli modern blog scripti.</p>',
'Markdown editör desteği|Yorum sistemi|Kategori ve etiketler|SEO meta yönetimi|RSS feed|Dark/light mode|AMP desteği|Sosyal medya paylaşım|İletişim formu',
'5 dakikada kuruluma hazır. Demo içerikle birlikte gelir.',
'1.3.4', '2026-03-28', '9.8 MB', 'Sınırsız Domain Lisansı', 790.00, 590.00, 'cover_blog.svg', 'https://demo.example.com/nova', 'https://demo.example.com/nova/admin', 'Kullanıcı: admin / Şifre: admin123', NULL, 'blog,yazı,seo,markdown', 0, 0, 1, 91);

-- ---------------------------------------------------------------------
-- TABLO: script_images
-- ---------------------------------------------------------------------
CREATE TABLE `script_images` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `script_id` INT(11) NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `display_order` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_si_script` (`script_id`),
  CONSTRAINT `fk_si_script` FOREIGN KEY (`script_id`) REFERENCES `scripts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLO: orders
-- ---------------------------------------------------------------------
CREATE TABLE `orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(30) NOT NULL UNIQUE,
  `user_id` INT(11) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `coupon_code` VARCHAR(50) DEFAULT NULL,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `payment_status` ENUM('beklemede','odeme_bekliyor','onaylandi','teslim_edildi','iptal') NOT NULL DEFAULT 'beklemede',
  `customer_note` TEXT,
  `admin_note` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_orders_user` (`user_id`),
  KEY `idx_status` (`payment_status`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLO: order_items
-- ---------------------------------------------------------------------
CREATE TABLE `order_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `script_id` INT(11) NOT NULL,
  `script_title` VARCHAR(200) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `license_label` VARCHAR(120) DEFAULT NULL,
  `license_key` VARCHAR(100) DEFAULT NULL,
  `download_token` VARCHAR(100) DEFAULT NULL,
  `download_count` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_oi_order` (`order_id`),
  KEY `fk_oi_script` (`script_id`),
  CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_script` FOREIGN KEY (`script_id`) REFERENCES `scripts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLO: payments
-- ---------------------------------------------------------------------
CREATE TABLE `payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `method` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `transaction_id` VARCHAR(150) DEFAULT NULL,
  `customer_message` TEXT,
  `proof_image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('beklemede','onaylandi','reddedildi') NOT NULL DEFAULT 'beklemede',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pay_order` (`order_id`),
  CONSTRAINT `fk_pay_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLO: coupons
-- ---------------------------------------------------------------------
CREATE TABLE `coupons` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL,
  `discount_type` ENUM('yuzde','tutar') NOT NULL DEFAULT 'yuzde',
  `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `min_order_total` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `max_usage` INT(11) DEFAULT NULL,
  `used_count` INT(11) NOT NULL DEFAULT 0,
  `valid_until` DATE DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `coupons` (`code`, `description`, `discount_type`, `discount_value`, `min_order_total`, `valid_until`, `is_active`) VALUES
('HOSGELDIN', 'Yeni müşteri %10 indirim kuponu', 'yuzde', 10.00, 500.00, '2027-12-31', 1),
('YAZ2026', 'Yaz kampanyası 250₺ indirim', 'tutar', 250.00, 1500.00, '2026-09-30', 1);

-- ---------------------------------------------------------------------
-- TABLO: comments
-- ---------------------------------------------------------------------
CREATE TABLE `comments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `script_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `rating` TINYINT(1) NOT NULL DEFAULT 5,
  `comment` TEXT NOT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_com_script` (`script_id`),
  KEY `fk_com_user` (`user_id`),
  CONSTRAINT `fk_com_script` FOREIGN KEY (`script_id`) REFERENCES `scripts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_com_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLO: settings
-- ---------------------------------------------------------------------
CREATE TABLE `settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` LONGTEXT,
  `setting_group` VARCHAR(50) DEFAULT 'general',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('site_name', 'ScriptMarkt', 'general'),
('site_slogan', 'Premium Script Marketplace', 'general'),
('site_description', 'Türkiye\'nin güvenilir script pazaryeri. Hazır web siteleri, oyun scriptleri ve dijital ürünler.', 'general'),
('site_logo', 'logo_scriptmarkt_default.png', 'general'),
('site_favicon', 'favicon_scriptmarkt_default.png', 'general'),
('hero_title', 'Hazır Scriptlerin', 'homepage'),
('hero_subtitle', 'Profesyonel kodlanmış, hazır kurulumlu ve güvenli script çözümleri. Tek bir tık ile dijital teslimat.', 'homepage'),
('contact_email', 'destek@scriptmarkt.com', 'contact'),
('contact_phone', '+90 555 555 55 55', 'contact'),
('contact_address', 'Türkiye', 'contact'),
('whatsapp_number', '+905555555555', 'contact'),
('telegram_link', 'https://t.me/scriptmarkt', 'contact'),
('social_instagram', '', 'social'),
('social_tiktok', '', 'social'),
('social_twitter', '', 'social'),
('social_youtube', '', 'social'),
('footer_text', '© 2026 ScriptMarkt. Tüm hakları saklıdır.', 'general'),
('maintenance_mode', '0', 'general'),
('meta_title', 'ScriptMarkt — Premium Hazır Scriptler', 'seo'),
('meta_description', 'Türkiye\'nin en güvenilir script pazaryeri. Hazır web siteleri, e-ticaret scriptleri, oyun marketleri.', 'seo'),
('meta_keywords', 'script,hazır script,php script,e-ticaret,minecraft market,kurumsal site', 'seo'),
('payment_havale_enabled', '1', 'payment'),
('payment_havale_info', 'Ziraat Bankası\nIBAN: TR00 0000 0000 0000 0000 0000 00\nAlıcı: ScriptMarkt Ltd.\n\nAçıklamaya sipariş numaranızı yazınız.', 'payment'),
('payment_paypal_enabled', '0', 'payment'),
('paypal_client_id', '', 'payment'),
('paypal_mode', 'sandbox', 'payment'),
('payment_stripe_enabled', '0', 'payment'),
('stripe_publishable_key', '', 'payment'),
('stripe_secret_key', '', 'payment'),
('payment_paytr_enabled', '0', 'payment'),
('paytr_merchant_id', '', 'payment'),
('paytr_merchant_key', '', 'payment'),
('paytr_merchant_salt', '', 'payment'),
('paytr_test_mode', '1', 'payment'),
('payment_iyzico_enabled', '0', 'payment'),
('iyzico_api_key', '', 'payment'),
('iyzico_secret_key', '', 'payment'),
('iyzico_base_url', 'https://sandbox-api.iyzipay.com', 'payment'),
('payment_crypto_enabled', '1', 'payment'),
('payment_crypto_info', 'USDT (TRC20): TXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\nBTC: bc1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'payment'),
('auto_delivery', '1', 'payment'),
('theme_primary', '#6366f1', 'theme'),
('theme_secondary', '#a855f7', 'theme'),
('theme_accent', '#22d3ee', 'theme'),
('footer_payment_methods', 'VISA, Mastercard, Bancontact, PayPal, Stripe, PayTR, Havale, USDT', 'footer'),
('support_widget_enabled', '1', 'support'),
('support_title', 'Canlı Destek', 'support'),
('support_subtitle', 'Sorun varsa hemen yaz, en kısa sürede yardımcı olalım.', 'support'),
('support_internal_message_enabled', '1', 'support'),
('support_whatsapp_enabled', '1', 'support'),
('support_telegram_enabled', '1', 'support'),
('live_support_url', '', 'support'),
('hero_badge_text', '// PREMIUM PHP MARKETPLACE', 'homepage'),
('site_background_image', '', 'theme'),
('site_background_overlay', 'matrix', 'theme'),
('custom_head_code', '', 'code'),
('custom_css', '', 'code'),
('custom_body_code', '', 'code');

-- ---------------------------------------------------------------------
-- TABLO: banners
-- ---------------------------------------------------------------------
CREATE TABLE `banners` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `subtitle` VARCHAR(300) DEFAULT NULL,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `link_url` VARCHAR(500) DEFAULT NULL,
  `button_text` VARCHAR(50) DEFAULT NULL,
  `display_order` INT(11) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLO: downloads
-- ---------------------------------------------------------------------
CREATE TABLE `downloads` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_item_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `downloaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_dl_orderitem` (`order_item_id`),
  KEY `fk_dl_user` (`user_id`),
  CONSTRAINT `fk_dl_orderitem` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLO: messages
-- ---------------------------------------------------------------------
CREATE TABLE `messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `subject` VARCHAR(200) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLO: favorites
-- ---------------------------------------------------------------------
CREATE TABLE `favorites` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `script_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fav` (`user_id`,`script_id`),
  CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_script` FOREIGN KEY (`script_id`) REFERENCES `scripts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TABLO: login_attempts (Brute-force koruması)
-- ---------------------------------------------------------------------
CREATE TABLE `login_attempts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `username_or_email` VARCHAR(150) DEFAULT NULL,
  `attempt_type` ENUM('user','admin') NOT NULL DEFAULT 'user',
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
