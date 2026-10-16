<?php
// Configuration for Database Connection
// SALIN file ini menjadi database.php dan sesuaikan dengan konfigurasi Anda

define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Ganti dengan username MySQL Anda
define('DB_PASS', '');              // Ganti dengan password MySQL Anda
define('DB_NAME', 'db_inventory_toko'); // Ganti dengan nama database Anda

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>
