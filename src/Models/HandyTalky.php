<?php

namespace App\Models;

/**
 * Class HandyTalky
 * 
 * [INHERITANCE + POLYMORPHISM + OVERLOADING - Poin H Rubrik]
 * 
 * - Inheritance  : extends Item (mewarisi properti name & pricePerDay)
 * - Polymorphism : method getSummary() di-override dengan format baru
 * - Overloading  : magic method __call() memungkinkan addAccessory()
 *                  dipanggil dengan parameter string maupun array
 * 
 * @package App\Models
 */
class HandyTalky extends Item
{
    /**
     * @var int ID unik dari database
     *          Private = hanya bisa diakses di dalam class ini saja
     */
    private int $id;

    /**
     * @var int Jumlah stok yang tersisa dan siap dipinjam
     */
    private int $stockAvailable;

    /**
     * @var array Daftar aksesori tambahan (misal: Headset, Charger)
     *            Disimpan dalam array untuk bukti penggunaan Array (Poin F)
     */
    private array $accessories = [];

    /**
     * Constructor
     * Memanggil parent::__construct() untuk mengisi $name dan $pricePerDay
     * dari class induk (Item), lalu mengisi properti tambahan milik HandyTalky.
     */
    public function __construct(int $id, string $name, float $pricePerDay, int $stockAvailable)
    {
        // Panggil constructor induk (Item) untuk set nama & harga
        parent::__construct($name, $pricePerDay);
        
        $this->id = $id;
        $this->stockAvailable = $stockAvailable;
    }

    // --- GETTER ---
    
    /** Kembalikan ID barang dari database */
    public function getId(): int { return $this->id; }
    
    /** Kembalikan jumlah stok yang tersedia */
    public function getStockAvailable(): int { return $this->stockAvailable; }

    /**
     * [POLYMORPHISM] Override method getSummary() dari class induk Item.
     * Format tampilannya berbeda dari yang ada di class Item (abstrak).
     * 
     * @return string
     */
    public function getSummary(): string
    {
        return "HT Model: {$this->name} - Rp " . number_format($this->pricePerDay, 0, ',', '.') . "/Day";
    }

    /**
     * Kembalikan daftar aksesori sebagai string (berlaku untuk tampilan)
     * @return string
     */
    public function getAccessories(): string
    {
        // implode = gabungkan semua elemen array jadi satu kalimat
        return implode(", ", $this->accessories);
    }

    /**
     * [OVERLOADING] Magic Method __call()
     * 
     * Dipanggil otomatis saat kita memanggil method yang tidak ada, misal: addAccessory()
     * Ini memungkinkan kita "mensimulasikan" overloading seperti di Java:
     *   - addAccessory("Headset")             -> menerima String
     *   - addAccessory(["Headset", "Antenna"]) -> menerima Array
     * 
     * @param string $method Nama method yang dipanggil
     * @param array  $args   Argument-argument yang dikirim
     * @throws \InvalidArgumentException jika format tidak dikenal
     * @throws \BadMethodCallException jika nama method tidak dikenal
     */
    public function __call(string $method, array $args)
    {
        if ($method === 'addAccessory') {
            if (count($args) === 0) return;
            
            // Kasus 1: Jika argument berupa string tunggal
            if (is_string($args[0])) {
                $this->accessories[] = $args[0]; // Tambahkan ke array
            } 
            // Kasus 2: Jika argument berupa array
            elseif (is_array($args[0])) {
                // Gabungkan array baru ke array aksesori yang sudah ada
                $this->accessories = array_merge($this->accessories, $args[0]);
            } else {
                throw new \InvalidArgumentException("Harus berupa string atau array.");
            }
        } else {
            throw new \BadMethodCallException("Method {$method} tidak ditemukan.");
        }
    }

    /**
     * Static method untuk mengubah array data mentah dari database
     * menjadi array berisi objek HandyTalky.
     * 
     * Ini adalah "Hydration" - proses membangun objek dari data flat (array).
     * Hasilnya bisa langsung di-loop: foreach ($items as $ht) { ... }
     * 
     * @param array $rows Array data dari PDO fetchAll()
     * @return HandyTalky[] Array objek HandyTalky
     */
    public static function hydrateCollection(array $rows): array
    {
        $collection = [];
        foreach ($rows as $row) {
            // Buat objek baru HandyTalky untuk setiap baris data dari database
            $ht = new self(
                (int)$row['id'],
                $row['name'] ?? '',
                (float)($row['price_per_day'] ?? 0),
                (int)($row['stock_available'] ?? 0)
            );
            $collection[] = $ht;
        }
        return $collection;
    }
}
