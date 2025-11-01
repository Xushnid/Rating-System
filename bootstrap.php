<?php

// 1. Composer'ning avtomatik yuklovchisini chaqirish
require_once __DIR__ . '/vendor/autoload.php';

// 2. .env faylini yuklash
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    // .env fayli topilmasa yoki xato bo'lsa, default qiymatlarni o'rnatamiz
    if (!isset($_ENV['APP_ENV'])) {
        $_ENV['APP_ENV'] = 'development';
    }
    if (!isset($_ENV['APP_TIMEZONE'])) {
        $_ENV['APP_TIMEZONE'] = 'Asia/Tashkent';
    }
    // Log the error
    error_log('Warning: .env file not found or invalid: ' . $e->getMessage());
}

// 3. Xatoliklarni sozlash va xavfsizlik
App\ErrorHandler::setup();

// Qo'shimcha xavfsizlik headerlari
if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Session security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
}

// 4. Vaqt mintaqasini sozlash
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Tashkent');

// 5. Yordamchi funksiyalarni yuklash
require_once __DIR__ . '/inc/functions.php';

// BO'LDI! Bu fayl endi obyektlarni yaratmaydi.
// bootstrap.php faylining oxiriga qo'shing

// Loyihaning asosiy manzilini (URL) avtomatik aniqlash
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script_name = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
define('BASE_URL', $protocol . '://' . $host . $script_name);