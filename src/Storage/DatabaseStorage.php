<?php

namespace App\Storage;

use App\Interfaces\StorageInterface;
use PDO;

/**
 * Class DatabaseStorage
 * 
 * [INHERITANCE dari Interface - Poin H Rubrik]
 * Class ini mengimplementasikan StorageInterface, artinya ia
 * wajib menyediakan method read() dan save() sesuai kontrak.
 * 
 * Tugas class ini: menjadi perantara antara aplikasi dan Database MySQL.
 * 
 * @package App\Storage
 */
class DatabaseStorage implements StorageInterface
{
    /**
     * @var PDO Koneksi database yang diteruskan dari config.php
     */
    private PDO $pdo;

    /**
     * Constructor - menerima koneksi PDO dari luar (Dependency Injection)
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * [READ] Ambil semua item HT dari tabel `items` di database.
     * Diurutkan berdasarkan ID terbaru (DESC).
     *
     * @return array
     */
    public function read(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM items ORDER BY id DESC");
        return $stmt->fetchAll() ?: [];
    }

    /**
     * [CREATE] Simpan HT baru ke tabel `items`.
     * Menerima array berisi nama, stok, dan harga.
     *
     * @param array $data ['name' => ..., 'stock' => ..., 'price' => ...]
     * @return bool
     */
    public function save(array $data): bool
    {
        // Jika tidak ada ID, berarti ini operasi INSERT (tambah baru)
        if (!isset($data['id'])) {
            $stmt = $this->pdo->prepare("INSERT INTO items (name, stock_total, stock_available, price_per_day) VALUES (?, ?, ?, ?)");
            return $stmt->execute([
                $data['name'], 
                $data['stock'],   // stock_total = stok awal yang dimasukkan
                $data['stock'],   // stock_available = sama dengan total karena baru
                $data['price']
            ]);
        }
        
        return false;
    }
    
    /**
     * [READ] Ambil semua transaksi rental yang masih berstatus Active.
     * Dipakai di dashboard untuk menampilkan daftar yang sedang dipinjam.
     *
     * @return array
     */
    public function readRentals(): array
    {
        $stmt = $this->pdo->query("SELECT r.*, i.name as item_name FROM rentals r JOIN items i ON r.item_id = i.id WHERE r.status = 'Active' ORDER BY r.rent_date DESC");
        return $stmt->fetchAll() ?: [];
    }
}
