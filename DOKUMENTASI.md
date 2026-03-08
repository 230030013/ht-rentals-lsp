# Dokumentasi Sistem Penyewaan HT Berbasis Strict OOP

Aplikasi PHP native ini dirancang secara khusus untuk memenuhi seluruh kriteria pengujian berbasis Object-Oriented Programming (OOP) ketat. 

## Pemahaman Konsep Eksternal Utama

Sebelum membedah struktur kodingan, dua teknologi standar industri PHP modern digunakan pada sistem ini:

### Apa itu PSR-4 Autoload?
Secara tradisional di PHP, jika kita memiliki 10 file Class (misal `HandyTalky.php`, `Item.php`, `DatabaseStorage.php`), kita harus memanggilnya satu per satu di setiap file menggunakan `require_once 'src/Models/Item.php';`. Tentu ini sangat merepotkan.

**PSR-4 (PHP Standard Recommendation 4)** adalah standar resmi PHP untuk memetakan nama "Namespace" ke dalam struktur "Folder" fisik secara otomatis.

Dalam sistem ini:
1. Kita menggunakan *Composer* tingkat dasar (Package Manager PHP).
2. Di file `composer.json`, kita memetakan awalan namespace `App\` agar selalu diarahkan ke folder `src/`.
3. Hasilnya? Saat kita menulis `new \App\Models\HandyTalky()` di file utama seperti `rent.php`, PHP secara "ajaib" tahu bahwa ia harus mencari file fisiknya di folder `/src/Models/HandyTalky.php` tanpa butuh satupun perintah `require_once` manual lagi. Ini membuat kode sangan bersih dan mudah dirawat.

### Apa itu Library DOMPDF?
Sistem ujian menetapkan pemakaian **"External Library"**. Kami menggunakan library populer di ekosistem PHP bernama **DOMPDF**.

**DOMPDF** adalah spesialis pembuat dokumen PDF (_PDF Generator_). Mengapa kita memakai ini?
Pada sebuah sistem penyewaan, Admin tentu membutuhkan fitur untuk mencetak Rekap Riwayat Transaksi sebagai laporan bulanan/mingguan. Ketimbang membuat format cetak manual menggunakan bawaan browser yang sering berantakan, DOMPDF menyediakannya dengan standar tinggi.

Contoh pemakaian:
Di file `export_pdf.php`, kita hanya perlu menyusun variabel `$html` string berisi tabel biasa. Kemudian memanggil `$dompdf->loadHtml($html)` dan menembakkannya langsung ke browser sebagai file `.pdf` siap unduh secara instan!

---

## Struktur Direktori Utama

Berikut struktur file penting yang berjalan pada sistem ini:
```text
/
├── composer.json               // Konfigurasi PSR-4 Autoload dan DOMPDF Library
├── config.php                  // Koneksi Database MySQL
├── export_pdf.php              // Modul Ekspor Laporan PDF dengan DOMPDF
├── index.php / dashboard.php   // Tampilan Beranda & Statistik Ringkas
├── items.php                   // Modul Manajemen Inventory HT (Tambah, Edit, Tambah Stok, Hapus)
├── rent.php                    // Modul Peminjaman & Validasi Stok (Mendukung Multi-Item)
├── history.php                 // Modul Pengembalian & Riwayat JSON Log
├── setup.php                   // Script Instalasi Database & Akun Admin Awal
├── storage/                    // Direktori penyimpan File JSON dan Log
│   ├── app.log                 // Catatan log dari Library Eksternal (Monolog)
│   └── rentals_log.json        // Backup catatan riwayat menggunakan Medium File Array (JSON)
└── src/                        // Direktori Root Namespace (App\)
    ├── Interfaces/
    │   └── StorageInterface.php // Aturan baku (Interface) untuk setiap Media Penyimpanan
    ├── Models/
    │   ├── Item.php            // Induk Class/Abstract Class HT
    │   └── HandyTalky.php      // Sub-Class HT berisi Polymorphism & Overloading
    └── Storage/
        ├── DatabaseStorage.php // Media Penyimpanan MySQL PDO
        └── FileStorage.php     // Media Penyimpanan File Logging JSON
```

## Membedah Pemenuhan Rubrik

Ini adalah rangkuman dari poin-poin rubrik ujian praktikum dan bagaimana sistem ini memenuhinya secara akurat:

### 1. Struktur Kendali, Tipe Data, dan Array (Poin D, E, F)
- **Tipe Data**: Semua variabel, parameter, hingga *return value* pada **`src/`** dideklarasikan ketat (_strict type_) seperti `string`, `int`, `float`, dan `array`.
- **Pengulangan dan Percabangan**: Banyak dimanfaatkan saat `foreach` di antarmuka tabel (`items.php` dan `rent.php`) atau saat menimbang proses transaksi via blok `if..else`.
- **Prosedur / Fungsi**: Fungsi pemetaan `hydrateCollection` pada class `HandyTalky` dan beragam fungsi lain disajikan untuk menjaga simplifikasi logika.
- **Array**: Transaksi di `rent.php` mendeteksi array `$_POST['quantity']` untuk membaca **Peminjaman Ganda** (Multi-Item) dan `Item::$_accessories` yang disimpan dalam bentuk array internal lalu diekstrak dengan `implode`.

### 2. Properties, Hak Akses, Encapsulation (Poin H)
Pada Class `Item` (`src/Models/Item.php`), seluruh properti menggunakan visibilitas `protected` (Encapsulation), sehingga akses/perubahan datanya hanya bisa dilakukan melalui *Getter* dan *Setter* (seperti `getName()` atau `setPricePerDay()`) dari luar class agar data bebas dari manipulasi sembarangan.

### 3. Inheritance, Polymorphism, Overloading, Interface (Poin H)
Konsep terdalam OOP dalam aplikasi ini dirangkum dengan indah melalui hal-hal berikut:
- **Inheritance (Pewarisan)**: Class `HandyTalky` mewarisi properti keturunan class `Item` via kata kunci `extends Item`.
- **Polymorphism**: Base function interaktif `getSummary()` yang diwajibkan (abstract) dari Class `Item` **diprogram modifikasi ulang sepenuhnya** (Override) secara berbeda pada subclass `HandyTalky` menjadi format tampilan "Pusat Rangkuman Objek".
- **Overloading**: Walau PHP secara tidak langsung belum melayani function loading bawaan ala Java, PHP bisa membuat skenario **Magic Overloading Method** (`__call()`). Di `HandyTalky`, kita dapat memanggil fungsi tambahan fiktif `$ht->addAccessory()` dimana fungsi ini dapat mendeteksi parameter dinamis (bisa `String` ataupun `Array` aksesori) lalu memprosesnya bersyarat secara fleksibel!
- **Interface**: Ada kontrak wujud wajib bernama `StorageInterface` yang memaksa semua sistem media penyimpanan untuk minimal memiliki function seragam, yaitu `read()` dan `save()`.

### 4. Dua atau Lebih Media Penyimpan Data (Poin G)
Berbeda dengan struktur rumit yang menggunakan JSON log, aplikasi ini telah disederhanakan:
- 💾 **Media 1 (Database MySQL)**: Melalui `DatabaseStorage`, aplikasi murni mencatat semua inventaris dan log riwayat ke MySQL.
- 💾 **Media 2 (Eksport Dokumen PDF)**: Bukti bahwa data bisa diserap dan dibaca ke dalam wujud file statis telah diejawantahkan dalam fitur Cetak _Invoice/Laporan_ DOMPDF. 

### 5. Multi-Namespaces dan Library Eksternal (Poin I & J)
- **Namespaces**: Komponen dibagi menjadi 3 blok `namespace App\Models`, `namespace App\Interfaces`, dan `namespace App\Storage`. Terisolasi apik mengikuti standar modern.  
- **External Library (DOMPDF)**: Digunakan library `dompdf/dompdf`. Memanggil perintah `new Dompdf()` secara mandiri untuk memformat HTML PHP murni ke dalam wujud file PDF cetak (Berada di file `export_pdf.php`).

## Catatan Kemudahan
Kodingan sengaja di-refactor serapi dan seoptimal mungkin untuk menghapus blok yang kurang digunakan. Tampilan (UI) murni membaur secara lugas namun kokoh melalui sistem Controller-Backend di file `.php` top level.

**Dibuat untuk mencetak standar sempurna pada pengujian Aplikasi Berbasis Objek (OOP).**
