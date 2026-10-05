<?php
/**
 * config/app.php
 * Konfigurasi umum aplikasi. UBAH BASE_URL jika nama folder project berbeda.
 */
define('ROOT_PATH', dirname(__DIR__));
define('BASE_URL', '/athlete-management');          // contoh: http://localhost/athlete-management  ('' jika di root)
define('UPLOAD_DIR', ROOT_PATH . '/uploads/athletes/');
define('UPLOAD_URL', BASE_URL . '/uploads/athletes/');
define('SESSION_TIMEOUT', 1800);                    // detik tidak aktif sebelum logout otomatis (30 menit)
define('APP_DEBUG', false);                         // true hanya saat belajar/debug di komputer lokal

date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
