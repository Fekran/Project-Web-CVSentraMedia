SISTEM INVENTORY CV SENTRA MEDIA BERBASIS WEB (NATIVE PHP)

Sistem Inventory  ini dirancang secara khusus untuk mengelola keluar-masuk stok barang, kategori, supplier, audit persediaan, serta laporan nilai persediaan barang secara aman, modern, dan responsif.

SPESIFIKASI TEKNOLOGI
- **Backend**: Native PHP 8+ (PDO Prepared Statement, Session Authentication, Tanpa Framework)
- **Frontend**: HTML5, Tailwind CSS, Vanilla JS
- **Grafik & Visual**: Chart.js, Font Awesome Icons
- **Basis Data**: MySQL (InnoDB dengan Relasi Foreign Keys)

---

CARA INSTALASI LOKAL (XAMPP / LARAGON)

 1. Ekstrak File Project
Ekstrak isi dari file ZIP `inventory-toko.zip` ke dalam folder root server lokal Anda:
- **XAMPP**: `C:\xampp\htdocs\inventory-toko-bangunan\`
- **Laragon**: `C:\laragon\www\inventory-toko-bangunan\`

 2. Import Database MySQL
1. Buka database manager Anda (seperti phpMyAdmin atau HeidiSQL).
2. Buat database baru bernama: `db_inventory_toko`
3. Pilih database tersebut, lalu lakukan **Import** file SQL yang berada di path berikut:
   `database/database_inventory.sql`

 3. Konfigurasi Koneksi Database
Jika Anda menggunakan username/password database MySQL yang berbeda dari bawaan default (`root` tanpa password), Anda dapat mengubah pengaturannya pada file:
`config/database.php`

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_inventory_toko');
```

 4. Jalankan Aplikasi
Buka web browser Anda dan akses alamat berikut:
`http://localhost/inventory-toko-bangunan/`

---

 AKUN ADMIN DEFAULT
Setelah berhasil mengimpor database, Anda dapat login menggunakan akun administrator default berikut:

- **Username**: `admin`
- **Password**: `admin123`


---

 STRUKTUR FOLDER APLIKASI
```
inventory-toko-bangunan/
├── assets/                  # File CSS dan Javascript kustom
├── config/                  # Pengaturan database PDO
├── database/                # Skema database MySQL
├── includes/                # Template layout (Header, Sidebar, Footer, Auth Helper)
├── pages/                   # File halaman dashboard, transaksi, master, dan laporan
├── actions/                 # File logika backend pemroses database (PHP Action)
├── uploads/                 # Folder penyimpanan berkas logo toko bangunan
├── index.php                # Dispatcher halaman utama
├── login.php                # Halaman form login
└── logout.php               # Halaman logout sesi
```

---

 FITUR KEAMANAN
1. **PDO Prepared Statements**: Mencegah celah kerentanan SQL Injection pada seluruh input pengguna.
2. **Session Security Check**: Membatasi hak akses halaman berdasarkan role user (Admin / Staff Gudang).
3. **XSS Protection**: Sanitasi otomatis data cetak ke HTML menggunakan helper `htmlspecialchars` kustom.
4. **CSRF Protection**: Token anti-pemalsuan permintaan lintas situs pada setiap form metode POST.
5. **Secure Password Hashing**: Enkripsi kata sandi menggunakan fungsi native PHP `password_hash()` (Bcrypt).
