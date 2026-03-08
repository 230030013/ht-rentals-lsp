<?php

namespace App\Models;

/**
 * Abstract Class Item
 * 
 * [ABSTRACT CLASS + ENCAPSULATION - Poin H Rubrik]
 * 
 * Ini adalah "induk" dari semua jenis barang sewa.
 * Bersifat abstract artinya kelas ini TIDAK bisa langsung dipakai (new Item()),
 * melainkan harus diturunkan (di-extends) oleh class lain seperti HandyTalky.
 * 
 * - Encapsulation: properti dibuat 'protected' agar hanya bisa diakses
 *   melalui getter/setter, bukan langsung dari luar class.
 * 
 * @package App\Models
 */
abstract class Item
{
    /**
     * @var string Nama atau model barang (misal: "Baofeng UV-5R")
     *             Protected = hanya bisa diakses oleh class ini & turunannya
     */
    protected string $name;

    /**
     * @var float Harga sewa per hari (Rupiah)
     */
    protected float $pricePerDay;

    /**
     * Constructor - dipanggil saat objek baru dibuat
     * Contoh: new HandyTalky(1, "Baofeng", 25000, 5)
     * 
     * @param string $name Nama barang
     * @param float  $pricePerDay Harga/hari
     */
    public function __construct(string $name, float $pricePerDay)
    {
        $this->name = $name;
        $this->pricePerDay = $pricePerDay;
    }

    // --- GETTER: untuk membaca nilai properti dari luar class ---

    /** Kembalikan nama barang */
    public function getName(): string
    {
        return $this->name;
    }

    /** Kembalikan harga per hari */
    public function getPricePerDay(): float
    {
        return $this->pricePerDay;
    }

    // --- SETTER: untuk mengubah nilai properti dari luar class ---

    /** Ubah nama barang */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /** Ubah harga per hari */
    public function setPricePerDay(float $price): void
    {
        $this->pricePerDay = $price;
    }

    /**
     * [ABSTRACT METHOD - Polymorphism]
     * Setiap class turunan WAJIB mengimplementasikan method ini sendiri.
     * HandyTalky akan override method ini dengan format tampilannya sendiri.
     * 
     * @return string Ringkasan info barang
     */
    abstract public function getSummary(): string;
}
