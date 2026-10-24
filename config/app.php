<?php
/**
 * PENGATURAN APLIKASI
 * Ubah sesuai kebutuhan. Tidak ada rahasia di file ini.
 */

const APP_NAMA          = 'Belajar Seru';
const APP_ZONA_WAKTU    = 'Asia/Jakarta';
const APP_DEBUG         = false;          // true hanya saat Anda sedang mengembangkan

// Nama anak: panjang & karakter yang diizinkan (tanpa data pribadi lain)
const NAMA_MAKS         = 30;

// Upload gambar (admin)
const UPLOAD_MAKS_BYTE  = 2 * 1024 * 1024;           // 2 MB
const UPLOAD_EKSTENSI   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Aturan bintang di akhir kuis (rasio jawaban benar pada percobaan pertama)
const BINTANG_3_MIN     = 0.8;   // 80% ke atas  = 3 bintang
const BINTANG_2_MIN     = 0.5;   // 50% ke atas  = 2 bintang, selain itu 1 bintang
const POIN_PER_SOAL     = 10;

// Folder gambar yang bisa Anda ganti sendiri (relatif terhadap folder aplikasi)
const FOLDER_GAMBAR     = 'gambar';
