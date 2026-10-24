# Belajar Seru — Aplikasi Belajar Interaktif Kelas 1 SD

Aplikasi belajar untuk anak kelas 1 SD (Matematika, Bahasa Indonesia, Pendidikan Agama).
PHP Native + MySQL + CSS + JavaScript, tanpa framework. Anak **tidak perlu login**: cukup mengetik nama, lalu memilih pelajaran.

## Cara pasang di XAMPP (Windows/Linux/Mac)

1. Jalankan **XAMPP**, lalu **Start** modul **Apache** dan **MySQL**.
2. Ekstrak zip ini. Salin folder `belajar-sd` ke dalam `htdocs`
   (Windows: `C:\xampp\htdocs\belajar-sd`).
3. Buka `http://localhost/phpmyadmin` → tab **Import** → **Choose File** → pilih
   `belajar-sd/database/belajar_sd.sql` → klik **Import** (bawah halaman).
   Database `belajar_sd` terbentuk otomatis beserta data contoh.
4. Cek `config/database.php`. Bawaan XAMPP (user `root`, password kosong) sudah cocok, biasanya tidak perlu diubah.
5. Buka `http://localhost/belajar-sd/install.php` → buat **username dan password admin**.
   Setelah berhasil, file `install.php` menghapus dirinya sendiri (jika gagal, hapus manual).
6. Selesai:
   - Halaman anak : `http://localhost/belajar-sd/`
   - Halaman admin: `http://localhost/belajar-sd/admin/`

### Dibuka dari HP/tablet di jaringan yang sama
Cari alamat IP komputer (mis. `192.168.1.10`), lalu buka `http://192.168.1.10/belajar-sd/` di HP.
(Izinkan Apache di firewall bila diminta.)

## Cara pasang di hosting (cPanel)
1. Unggah isi folder `belajar-sd` ke `public_html` (atau subfolder).
2. Buat database + user MySQL di cPanel, lalu **import** `database/belajar_sd.sql`
   (jika database dibuat lewat cPanel, hapus 2 baris `CREATE DATABASE` dan `USE` di bagian atas file SQL, atau pilih database itu saat import).
3. Isi `config/database.php` dengan host, nama database, user, dan password dari cPanel.
4. Buka `/install.php` untuk membuat akun admin. Gunakan HTTPS bila tersedia.

## Alur anak
`index.php` (ketik nama) → `mapel.php` (pilih pelajaran) → `materi.php` (pilih materi/level) → `belajar.php` (kartu belajar) → `kuis.php` → `hasil.php` (bintang & skor) → kembali ke menu.

Jenis soal: **pilihan ganda/tebak gambar**, **seret & lepas**, **cocokkan gambar dengan kata**.
Bintang: 3 bintang bila ≥ 80% benar pada percobaan pertama, 2 bintang bila ≥ 50%, selain itu 1 bintang (aturan bisa diubah di `config/app.php`).
Jawaban salah tidak menghukum: anak boleh mencoba lagi sampai benar; hanya jawaban benar pada percobaan pertama yang dihitung ke skor.

## Mengelola isi (Admin)
- **Mapel**: tambah/ubah/hapus, pilih emoji atau unggah gambar ikon, atur warna dan urutan.
- **Materi**: isi kartu belajar ditulis **satu baris = satu kartu**, format `emoji | teks pendek`.
  Contoh: `🍎🍎 + 🍎🍎🍎 | 2 ditambah 3 sama dengan 5.`
- **Soal**: pilih jenis soal, isi 2–4 pilihan. Pilihan ganda/seret: pilih **satu** jawaban benar.
  Cocokkan: tiap baris satu pasangan (emoji/gambar ↔ kata).
- **Hasil Siswa**: riwayat per nama/mapel/skor/tanggal, unduh CSV, dan hapus data siswa.
- Kotak centang **"perlu dicek"** menandai materi/soal yang belum Anda periksa.

### Pendidikan Agama
Materi contoh sengaja dibuat **netral** (perilaku baik, tolong-menolong, kebersihan, hormat kepada orang tua dan guru) dan semuanya
ditandai **perlu dicek**. Tambahkan materi sesuai agama dan kurikulum yang Anda pakai lewat admin
(sudah ada materi kosong "Berdoa" yang disembunyikan sebagai tempat mengisi). Isi keagamaan tidak saya karang agar tidak ada yang keliru.

## Mengganti gambar & maskot
- **Maskot**: ganti file `gambar/maskot.svg` (boleh diganti PNG; ubah namanya di `includes/header.php`, `index.php`).
- **Gambar mapel/materi/soal/pilihan**: unggah lewat form admin (JPG/PNG/GIF/WEBP, maks 2 MB; SVG sengaja tidak diizinkan demi keamanan).
  Berkas tersimpan di `gambar/mapel`, `gambar/materi`, `gambar/soal`.
- Emoji dipakai sebagai gambar bawaan. Bila ingin gambar sendiri, cukup unggah; gambar menggantikan emoji.

## Struktur folder & fungsi file

```text
belajar-sd/
├── index.php          Halaman awal: anak mengetik nama (disimpan di tabel siswa + sesi)
├── mapel.php          Pilih mata pelajaran, total bintang, progres per mapel
├── materi.php         Daftar materi/level dalam satu mapel + bintang terbaik anak
├── belajar.php        Kartu belajar bergambar (tombol Dengarkan, Lanjut, Mulai Kuis)
├── kuis.php           Kuis satu soal per layar (pilihan / seret / cocokkan)
├── hasil.php          Hasil kuis: bintang, skor, kalimat penyemangat, konfeti
├── keluar.php         Ganti nama (menghapus identitas dari sesi, data tetap)
├── install.php        Membuat akun admin pertama, lalu menghapus dirinya sendiri
├── api/
│   ├── cek.php        Memeriksa satu jawaban di server (kunci tidak dikirim ke browser)
│   └── selesai.php    Menghitung skor/bintang dan menyimpan ke tabel hasil
├── config/
│   ├── database.php   Koneksi MySQL (PDO) — EDIT di sini
│   └── app.php        Nama aplikasi, aturan bintang, batas upload, zona waktu
├── includes/
│   ├── fungsi.php     Sesi aman, header keamanan, CSRF, validasi nama, upload gambar
│   ├── header.php     Kepala halaman anak + maskot
│   ├── footer.php     Kaki halaman anak + skrip
│   └── admin_layout.php  Tata letak halaman admin + helper
├── assets/
│   ├── css/           app.css (halaman anak), admin.css
│   └── js/            app.js (transisi, baca teks), suara.js (efek + suara ON/OFF),
│                      confetti.js, belajar.js, kuis.js, hasil.js, admin.js
├── gambar/            Aset yang bisa Anda ganti (maskot.svg, mapel/, materi/, soal/)
├── admin/
│   ├── index.php      Login admin (dibatasi percobaan, password di-hash)
│   ├── dashboard.php  Ringkasan + daftar "perlu dicek"
│   ├── mapel.php  materi.php  soal.php  soal_form.php   CRUD mapel/materi/soal+jawaban
│   ├── hasil.php      Riwayat hasil siswa, unduh CSV, hapus siswa
│   ├── akun.php       Ganti password
│   └── logout.php
└── database/
    └── belajar_sd.sql Struktur 7 tabel + data contoh (3 mapel, 14 materi, 30 soal)
```

Tabel: `mapel`, `materi`, `soal`, `pilihan_jawaban`, `siswa` (hanya nama), `hasil`, `admin`.

## Keamanan & privasi anak
- Hanya **nama panggilan** yang disimpan; tidak ada email, password anak, iklan, atau link keluar.
- Semua query memakai **PDO prepared statement**; semua output di-escape (`htmlspecialchars`); form dan API dilindungi **token CSRF**.
- Kunci jawaban tidak ada di HTML/JavaScript; pengecekan dilakukan di server.
- Admin: password di-hash (`password_hash`), sesi diperbarui saat login, percobaan login dibatasi.
- Upload: dicek isi file (bukan hanya ekstensi), nama diacak, SVG ditolak, eksekusi PHP di folder `gambar/` diblokir (`.htaccess`).
- Header CSP: hanya skrip dari domain sendiri. Folder `config/`, `includes/`, `database/` diblokir lewat `.htaccess` (Apache).
- Admin dapat menghapus data seorang anak kapan saja (Hasil Siswa → Daftar siswa).

## Catatan
- Anak dengan **nama yang sama** akan berbagi bintang dan riwayat (karena tanpa login). Untuk dipakai satu kelas, gunakan nama panggilan yang berbeda (mis. "Budi A", "Budi R").
- Suara memakai Web Speech API bawaan browser (bahasa Indonesia, tergantung perangkat). Tombol **Suara: ON/OFF** ada di setiap halaman.
- Jika muncul "Database belum tersambung": pastikan MySQL menyala, SQL sudah di-import, dan `config/database.php` benar.
- Jika upload gambar gagal di hosting: beri izin tulis (chmod 755/775) pada folder `gambar/` dan subfoldernya.
- Setelah selesai mengembangkan, pastikan `APP_DEBUG` di `config/app.php` tetap `false`.
