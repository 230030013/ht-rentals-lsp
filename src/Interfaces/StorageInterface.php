<?php

namespace App\Interfaces;

/**
 * Interface StorageInterface
 * 
 * [INTERFACE - Poin H Rubrik]
 * 
 * Ini adalah "kontrak" atau aturan wajib yang harus dipatuhi oleh
 * semua class penyimpanan data (misal: DatabaseStorage).
 * Setiap class yang implements interface ini WAJIB memiliki
 * method read() dan save().
 * 
 * @package App\Interfaces
 */
interface StorageInterface
{
    /**
     * Membaca semua data dari media penyimpanan.
     *
     * @return array Mengembalikan array berisi data.
     */
    public function read(): array;

    /**
     * Menyimpan data baru ke media penyimpanan.
     *
     * @param array $data Data yang akan disimpan.
     * @return bool True jika berhasil, false jika gagal.
     */
    public function save(array $data): bool;
}
