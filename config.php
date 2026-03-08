<?php
// config.php - File konfigurasi utama, di-load oleh semua halaman

// Aktifkan session supaya data login admin bisa diingat antar halaman
session_start();

// Load semua class dari folder vendor (Composer Autoloader: PSR-4)
require_once __DIR__ . '/vendor/autoload.php';

// Konfigurasi koneksi ke database MySQL
$host     = '127.0.0.1';
$dbname   = 'lsp_ht_rental';
$username = 'root';
$password = ''; 

try {
    // Buat koneksi PDO ke MySQL
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Jika ada error SQL, lempar sebagai Exception supaya bisa ditangkap
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Semua data dari fetch() otomatis berbentuk array asosiatif (key => value)
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Hentikan program dan tampilkan pesan error jika koneksi gagal
    die("Database connection failed: " . $e->getMessage());
}
?>
