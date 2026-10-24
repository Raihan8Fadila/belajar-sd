-- =====================================================
-- Database : belajar_sd
-- Aplikasi : Belajar Seru (belajar interaktif kelas 1 SD)
-- Cara pakai: phpMyAdmin -> Import -> pilih file ini -> Kirim
-- File ini membuat database, tabel, dan data contoh.
-- (Akun admin dibuat lewat halaman install.php, bukan dari file ini.)
-- =====================================================

SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS belajar_sd
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE belajar_sd;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS hasil, siswa, pilihan_jawaban, soal, materi, mapel, admin;
SET FOREIGN_KEY_CHECKS = 1;

-- Akun pengelola (guru/orang tua). Anak TIDAK punya akun.
CREATE TABLE admin (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  dibuat        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Mata pelajaran
CREATE TABLE mapel (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama      VARCHAR(100) NOT NULL,
  slug      VARCHAR(100) NOT NULL UNIQUE,
  deskripsi VARCHAR(255) NOT NULL DEFAULT '',
  emoji     VARCHAR(20)  NOT NULL DEFAULT '📘',
  gambar    VARCHAR(255) DEFAULT NULL,           -- nama file di folder gambar/ (opsional)
  warna     CHAR(7)      NOT NULL DEFAULT '#A9D2FF',
  urutan    INT NOT NULL DEFAULT 0,
  aktif     TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Materi = level di dalam mapel. Isi pelajaran ditulis per baris:
--   "emoji | teks pendek"  -> satu baris = satu kartu belajar
CREATE TABLE materi (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mapel_id    INT UNSIGNED NOT NULL,
  judul       VARCHAR(150) NOT NULL,
  deskripsi   VARCHAR(255) NOT NULL DEFAULT '',
  emoji       VARCHAR(20)  NOT NULL DEFAULT '⭐',
  isi         TEXT NOT NULL,
  gambar      VARCHAR(255) DEFAULT NULL,
  urutan      INT NOT NULL DEFAULT 0,
  aktif       TINYINT(1) NOT NULL DEFAULT 1,
  perlu_dicek TINYINT(1) NOT NULL DEFAULT 0,     -- 1 = tandai agar dicek manual oleh pengelola
  KEY idx_materi_mapel (mapel_id),
  CONSTRAINT fk_materi_mapel FOREIGN KEY (mapel_id) REFERENCES mapel(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Soal. tipe: pilihan (pilihan ganda bergambar / tebak gambar),
--             seret (drag and drop jawaban ke kotak),
--             cocokkan (mencocokkan gambar dengan kata)
CREATE TABLE soal (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  materi_id   INT UNSIGNED NOT NULL,
  tipe        ENUM('pilihan','seret','cocokkan') NOT NULL DEFAULT 'pilihan',
  pertanyaan  VARCHAR(255) NOT NULL,
  emoji       VARCHAR(100) NOT NULL DEFAULT '',
  gambar      VARCHAR(255) DEFAULT NULL,
  urutan      INT NOT NULL DEFAULT 0,
  aktif       TINYINT(1) NOT NULL DEFAULT 1,
  perlu_dicek TINYINT(1) NOT NULL DEFAULT 0,
  KEY idx_soal_materi (materi_id),
  CONSTRAINT fk_soal_materi FOREIGN KEY (materi_id) REFERENCES materi(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Pilihan jawaban.
--  pilihan/seret : satu baris benar=1, sisanya 0.
--  cocokkan      : setiap baris = satu pasangan (emoji/gambar <-> teks), semua benar=1.
CREATE TABLE pilihan_jawaban (
  id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  soal_id INT UNSIGNED NOT NULL,
  teks    VARCHAR(150) NOT NULL,
  emoji   VARCHAR(50)  NOT NULL DEFAULT '',
  gambar  VARCHAR(255) DEFAULT NULL,
  benar   TINYINT(1) NOT NULL DEFAULT 0,
  urutan  INT NOT NULL DEFAULT 0,
  KEY idx_pilihan_soal (soal_id),
  CONSTRAINT fk_pilihan_soal FOREIGN KEY (soal_id) REFERENCES soal(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Siswa: HANYA nama panggilan (tanpa data pribadi lain).
CREATE TABLE siswa (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama           VARCHAR(30) NOT NULL,
  dibuat         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  terakhir_aktif TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_siswa_nama (nama)
) ENGINE=InnoDB;

-- Hasil kuis (satu baris setiap kali anak menyelesaikan kuis)
CREATE TABLE hasil (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  siswa_id  INT UNSIGNED NOT NULL,
  materi_id INT UNSIGNED NOT NULL,
  benar     INT NOT NULL DEFAULT 0,      -- soal yang dijawab benar pada percobaan pertama
  total     INT NOT NULL DEFAULT 0,
  bintang   TINYINT NOT NULL DEFAULT 1,  -- 1 sampai 3
  skor      INT NOT NULL DEFAULT 0,      -- benar x 10
  waktu     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_hasil_siswa (siswa_id, materi_id),
  CONSTRAINT fk_hasil_siswa  FOREIGN KEY (siswa_id)  REFERENCES siswa(id)  ON DELETE CASCADE,
  CONSTRAINT fk_hasil_materi FOREIGN KEY (materi_id) REFERENCES materi(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- DATA CONTOH
-- =====================================================

INSERT INTO mapel (id, nama, slug, deskripsi, emoji, warna, urutan) VALUES
(1, 'Matematika', 'matematika', 'Yuk bermain dengan angka!', '🧮', '#FFD58A', 1),
(2, 'Bahasa Indonesia', 'bahasa-indonesia', 'Yuk belajar membaca dan kata!', '📖', '#A9D2FF', 2),
(3, 'Pendidikan Agama', 'pendidikan-agama', 'Yuk belajar tentang kebaikan!', '🙏', '#B5EBC8', 3);

INSERT INTO materi (id, mapel_id, judul, deskripsi, emoji, isi, urutan, aktif, perlu_dicek) VALUES
(1, 1, 'Berhitung 1–20', 'Hitung benda dan angka', '🔢', '1️⃣2️⃣3️⃣4️⃣5️⃣ | Ayo berhitung! Satu, dua, tiga, empat, lima.
6️⃣7️⃣8️⃣9️⃣🔟 | Enam, tujuh, delapan, sembilan, sepuluh.
🍎🍎🍎🍎🍎🍎🍎🍎🍎🍎🍎 | Setelah sepuluh ada sebelas, dua belas, dan seterusnya sampai dua puluh.', 1, 1, 0),
(2, 1, 'Penjumlahan', 'Menambah angka', '➕', '🍎🍎 + 🍎🍎🍎 | 2 ditambah 3 sama dengan 5. Menambah artinya digabung.
🐥🐥🐥 + 🐥 | 3 ditambah 1 sama dengan 4.', 2, 1, 0),
(3, 1, 'Pengurangan', 'Mengurangi angka', '➖', '🍪🍪🍪🍪🍪 | Ada 5 kue. Dimakan 2. Sisa 3 kue. Itu artinya 5 dikurangi 2 sama dengan 3.
🎈🎈🎈🎈 | 4 balon, terbang 1. Sisa 3 balon.', 3, 1, 0),
(4, 1, 'Mengenal Bentuk', 'Lingkaran, segitiga, persegi', '🔺', '⚪ | Ini lingkaran. Lingkaran itu bulat.
🔺 | Ini segitiga. Segitiga punya 3 sisi.
🟦 | Ini persegi. Persegi punya 4 sisi sama panjang.', 4, 1, 0),
(5, 1, 'Membandingkan Angka', 'Lebih besar dan lebih kecil', '⚖️', '9️⃣ ⚖️ 4️⃣ | 9 lebih besar dari 4.
2️⃣ ⚖️ 7️⃣ | 2 lebih kecil dari 7.', 5, 1, 0),
(6, 2, 'Mengenal Huruf', 'Huruf awal sebuah kata', '🔤', '🍎 | Ini apel. Apel diawali huruf A.
⚽ | Ini bola. Bola diawali huruf B.
🔤 | Huruf ada 26, dari A sampai Z.', 1, 1, 0),
(7, 2, 'Suku Kata', 'Memotong kata jadi suku kata', '🧩', '📚 | Kata BUKU dipotong jadi BU dan KU.
⚽ | Kata BOLA dipotong jadi BO dan LA.
👕 | Kata BAJU dipotong jadi BA dan JU.', 2, 1, 0),
(8, 2, 'Tebak Gambar', 'Gambar dan nama katanya', '🖼️', '🐱 | Ini kucing.
📚 | Ini buku.
🐟 | Ini ikan.', 3, 1, 0),
(9, 2, 'Melengkapi Kata', 'Isi huruf yang hilang', '✏️', '👕 | BA_U. Huruf yang hilang adalah J. Jadi BAJU.
🐟 | IK_N. Huruf yang hilang adalah A. Jadi IKAN.', 4, 1, 0),
(10, 3, 'Perilaku Baik', 'Berbuat baik setiap hari', '😊', '🤥❌ | Berkata jujur itu baik.
🎁 | Kalau diberi sesuatu, kita berkata terima kasih.
🙇 | Kalau berbuat salah, kita minta maaf.', 1, 1, 1),
(11, 3, 'Tolong-Menolong', 'Saling membantu', '🤝', '🤝 | Kita saling menolong. Menolong itu membuat hati senang.
✏️ | Teman lupa membawa pensil? Kita boleh meminjamkan.', 2, 1, 1),
(12, 3, 'Menjaga Kebersihan', 'Hidup bersih itu sehat', '🧼', '🧼 | Cuci tangan pakai sabun sebelum makan.
🗑️ | Buang sampah di tempat sampah.
🚿 | Mandi supaya badan bersih dan segar.', 3, 1, 1),
(13, 3, 'Hormat kepada Orang Tua dan Guru', 'Bersikap sopan', '👩‍🏫', '👩‍🏫 | Bertemu guru, kita memberi salam dengan sopan.
👨‍👩‍👧 | Dipanggil orang tua, kita menjawab dan datang.', 4, 1, 1),
(14, 3, 'Berdoa (isi sesuai agamamu)', 'Isi dari halaman admin', '🤲', '🤲 | [ISI SENDIRI] Tulis isi pelajaran doa sesuai agama yang dibutuhkan.', 5, 0, 1);

INSERT INTO soal (id, materi_id, tipe, pertanyaan, emoji, urutan, perlu_dicek) VALUES
(1, 1, 'pilihan', 'Ada berapa apel?', '🍎🍎🍎🍎🍎🍎🍎', 1, 0),
(2, 1, 'pilihan', 'Ada berapa bintang?', '⭐⭐⭐⭐⭐⭐⭐⭐⭐⭐⭐⭐', 2, 0),
(3, 2, 'pilihan', 'Berapa jumlah apel semuanya?', '🍎🍎 + 🍎🍎', 1, 0),
(4, 2, 'seret', '3 + 4 = ...', '', 2, 0),
(5, 3, 'pilihan', '5 − 2 = ...', '🍪🍪🍪🍪🍪', 1, 0),
(6, 3, 'seret', '9 − 4 = ...', '', 2, 0),
(7, 4, 'cocokkan', 'Cocokkan gambar dengan namanya!', '', 1, 0),
(8, 4, 'pilihan', 'Bentuk apa yang punya 3 sisi?', '', 2, 0),
(9, 5, 'pilihan', 'Mana angka yang paling besar?', '', 1, 0),
(10, 5, 'seret', 'Mana angka yang paling kecil?', '', 2, 0),
(11, 6, 'pilihan', 'Kata BOLA diawali huruf ...', '⚽', 1, 0),
(12, 6, 'pilihan', 'Kata IKAN diawali huruf ...', '🐟', 2, 0),
(13, 6, 'cocokkan', 'Cocokkan gambar dengan huruf awalnya!', '', 3, 0),
(14, 7, 'pilihan', 'Suku kata pertama kata BUKU adalah ...', '📚', 1, 0),
(15, 7, 'seret', 'BO + LA = ...', '⚽', 2, 0),
(16, 8, 'pilihan', 'Gambar apakah ini?', '🐱', 1, 0),
(17, 8, 'pilihan', 'Gambar apakah ini?', '📚', 2, 0),
(18, 8, 'cocokkan', 'Cocokkan gambar dengan katanya!', '', 3, 0),
(19, 9, 'pilihan', 'Lengkapi kata: BA_U', '👕', 1, 0),
(20, 9, 'seret', 'Lengkapi kata: IK_N', '🐟', 2, 0),
(21, 10, 'pilihan', 'Temanmu jatuh. Apa yang kamu lakukan?', '🤕', 1, 1),
(22, 10, 'pilihan', 'Nenek memberimu hadiah. Apa yang kamu katakan?', '🎁', 2, 1),
(23, 10, 'seret', 'Kamu tidak sengaja menyenggol temanmu. Kamu berkata ...', '😮', 3, 1),
(24, 11, 'pilihan', 'Temanmu lupa membawa pensil. Kamu ...', '✏️', 1, 1),
(25, 11, 'pilihan', 'Ibu sedang menyapu rumah. Kamu ...', '🧹', 2, 1),
(26, 12, 'pilihan', 'Sebelum makan kita harus ...', '🍽️', 1, 1),
(27, 12, 'seret', 'Sampah dibuang di ...', '🗑️', 2, 1),
(28, 12, 'cocokkan', 'Cocokkan gambar dengan namanya!', '', 3, 1),
(29, 13, 'pilihan', 'Bertemu guru di sekolah, kita ...', '👩‍🏫', 1, 1),
(30, 13, 'pilihan', 'Ibu memanggilmu. Kamu ...', '👩', 2, 1);

INSERT INTO pilihan_jawaban (soal_id, teks, emoji, benar, urutan) VALUES
(1, '5', '', 0, 1),
(1, '6', '', 0, 2),
(1, '7', '', 1, 3),
(1, '8', '', 0, 4),
(2, '10', '', 0, 1),
(2, '11', '', 0, 2),
(2, '12', '', 1, 3),
(2, '13', '', 0, 4),
(3, '3', '', 0, 1),
(3, '4', '', 1, 2),
(3, '5', '', 0, 3),
(3, '6', '', 0, 4),
(4, '6', '', 0, 1),
(4, '7', '', 1, 2),
(4, '8', '', 0, 3),
(5, '2', '', 0, 1),
(5, '3', '', 1, 2),
(5, '4', '', 0, 3),
(6, '4', '', 0, 1),
(6, '5', '', 1, 2),
(6, '6', '', 0, 3),
(7, 'Lingkaran', '⚪', 1, 1),
(7, 'Segitiga', '🔺', 1, 2),
(7, 'Persegi', '🟦', 1, 3),
(8, 'Lingkaran', '', 0, 1),
(8, 'Persegi', '', 0, 2),
(8, 'Segitiga', '', 1, 3),
(9, '4', '', 0, 1),
(9, '9', '', 1, 2),
(9, '6', '', 0, 3),
(10, '7', '', 0, 1),
(10, '2', '', 1, 2),
(10, '5', '', 0, 3),
(11, 'A', '', 0, 1),
(11, 'B', '', 1, 2),
(11, 'L', '', 0, 3),
(12, 'K', '', 0, 1),
(12, 'N', '', 0, 2),
(12, 'I', '', 1, 3),
(13, 'A', '🍎', 1, 1),
(13, 'B', '⚽', 1, 2),
(13, 'K', '🐱', 1, 3),
(14, 'KU', '', 0, 1),
(14, 'BU', '', 1, 2),
(14, 'BA', '', 0, 3),
(15, 'LABO', '', 0, 1),
(15, 'BOLA', '', 1, 2),
(15, 'OBAL', '', 0, 3),
(16, 'Burung', '', 0, 1),
(16, 'Kucing', '', 1, 2),
(16, 'Ikan', '', 0, 3),
(17, 'Bola', '', 0, 1),
(17, 'Baju', '', 0, 2),
(17, 'Buku', '', 1, 3),
(18, 'Ikan', '🐟', 1, 1),
(18, 'Pisang', '🍌', 1, 2),
(18, 'Baju', '👕', 1, 3),
(19, 'C', '', 0, 1),
(19, 'J', '', 1, 2),
(19, 'Z', '', 0, 3),
(20, 'E', '', 0, 1),
(20, 'U', '', 0, 2),
(20, 'A', '', 1, 3),
(21, 'Menertawakannya', '', 0, 1),
(21, 'Menolongnya', '', 1, 2),
(21, 'Pergi begitu saja', '', 0, 3),
(22, 'Diam saja', '', 0, 1),
(22, 'Aku tidak suka', '', 0, 2),
(22, 'Terima kasih, Nenek', '', 1, 3),
(23, 'Maaf ya', '', 1, 1),
(23, 'Bukan salahku', '', 0, 2),
(23, 'Minggir!', '', 0, 3),
(24, 'Meminjamkan pensilmu', '', 1, 1),
(24, 'Pura-pura tidak tahu', '', 0, 2),
(24, 'Mengejeknya', '', 0, 3),
(25, 'Bermain terus', '', 0, 1),
(25, 'Ikut membantu', '', 1, 2),
(25, 'Tidur', '', 0, 3),
(26, 'Berlari', '', 0, 1),
(26, 'Tidur', '', 0, 2),
(26, 'Cuci tangan', '', 1, 3),
(27, 'Kolong meja', '', 0, 1),
(27, 'Tempat sampah', '', 1, 2),
(27, 'Jalan', '', 0, 3),
(28, 'Sabun', '🧼', 1, 1),
(28, 'Tempat sampah', '🗑️', 1, 2),
(28, 'Mandi', '🚿', 1, 3),
(29, 'Pura-pura tidak lihat', '', 0, 1),
(29, 'Memberi salam', '', 1, 2),
(29, 'Berteriak', '', 0, 3),
(30, 'Diam saja', '', 0, 1),
(30, 'Menutup telinga', '', 0, 2),
(30, 'Menjawab dan menghampiri', '', 1, 3);
