<?php
/**
 * config/database.php
 * Koneksi PDO ke MySQL/MariaDB. Pakai db() untuk mendapatkan koneksi.
 */
require_once __DIR__ . '/app.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host     = 'localhost';
    $dbname   = 'athlete_management';
    $username = 'root';
    $password = '';           // XAMPP default kosong

    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,   // prepared statement asli (anti SQL injection)
            ]
        );
    } catch (PDOException $e) {
        error_log('Koneksi DB gagal: ' . $e->getMessage());
        http_response_code(500);
        exit(APP_DEBUG
            ? 'Koneksi database gagal: ' . htmlspecialchars($e->getMessage())
            : 'Koneksi database gagal. Pastikan MySQL berjalan dan cek config/database.php.');
    }
    return $pdo;
}
