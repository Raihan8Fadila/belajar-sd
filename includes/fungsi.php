<?php
/**
 * FUNGSI BERSAMA
 * Di-include oleh semua halaman (anak, API, dan admin).
 * Isi: sesi aman, header keamanan, CSRF, validasi nama, upload gambar, helper bintang.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

date_default_timezone_set(APP_ZONA_WAKTU);
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');

// Awalan path ke folder utama: halaman admin berada satu level di bawah.
if (!defined('BASE')) {
    define('BASE', defined('AREA_ADMIN') ? '../' : '');
}

/* ---------------------------------------------------------------
 * Sesi & header keamanan
 * ------------------------------------------------------------- */
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('BELAJARSD');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https,
    ]);
    session_start();
}

if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    // Semua skrip dari domain sendiri; tidak ada link/skrip keluar (privasi anak).
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; "
         . "style-src 'self' 'unsafe-inline'; script-src 'self'; base-uri 'self'; "
         . "form-action 'self'; frame-ancestors 'none'");
}

/* ---------------------------------------------------------------
 * Helper umum
 * ------------------------------------------------------------- */

/** Escape output HTML (anti XSS). */
function e($teks): string
{
    return htmlspecialchars((string)$teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL relatif ke file di folder utama aplikasi. */
function url(string $path = ''): string
{
    return BASE . $path;
}

/** URL gambar dari folder gambar/ (mis. "soal/abc.png" atau "maskot.svg"). */
function gambar_url(?string $file): string
{
    $file = ltrim((string)$file, '/');
    return BASE . FOLDER_GAMBAR . '/' . $file;
}

function redirect(string $tujuan): never
{
    header('Location: ' . $tujuan);
    exit;
}

function json_out(array $data, int $kode = 200): never
{
    http_response_code($kode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function warna_valid(string $w): bool
{
    return (bool)preg_match('/^#[0-9a-fA-F]{6}$/', $w);
}

function tgl_indo(?string $waktu): string
{
    if (!$waktu) {
        return '-';
    }
    $t = strtotime($waktu);
    $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return date('d', $t) . ' ' . $bln[(int)date('n', $t)] . ' ' . date('Y H:i', $t);
}

/* ---------------------------------------------------------------
 * Pesan singkat (flash)
 * ------------------------------------------------------------- */
function flash_set(string $tipe, string $pesan): void
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function flash_ambil(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---------------------------------------------------------------
 * CSRF (melindungi semua form dan API yang mengubah data)
 * ------------------------------------------------------------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Cek token dari form POST atau header X-CSRF-Token. */
function csrf_valid(): bool
{
    $kirim = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($kirim) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $kirim);
}

function csrf_wajib(): void
{
    if (!csrf_valid()) {
        http_response_code(400);
        exit('Permintaan tidak valid. Silakan muat ulang halaman lalu coba lagi.');
    }
}

/* ---------------------------------------------------------------
 * Siswa (hanya nama panggilan, tanpa login)
 * ------------------------------------------------------------- */

/**
 * Bersihkan & validasi nama anak.
 * Huruf (termasuk aksen), angka, spasi, titik, apostrof, dan tanda hubung saja.
 */
function nama_bersih(string $mentah): array
{
    $nama = trim(preg_replace('/\s+/u', ' ', $mentah));
    if ($nama === '') {
        return ['ok' => false, 'pesan' => 'Ketik namamu dulu ya 😊'];
    }
    if (mb_strlen($nama) > NAMA_MAKS) {
        return ['ok' => false, 'pesan' => 'Namanya kepanjangan. Pakai nama panggilan ya 😊'];
    }
    if (!preg_match('/^[\p{L}\p{N} .\'\-]+$/u', $nama)) {
        return ['ok' => false, 'pesan' => 'Pakai huruf dan angka saja ya 😊'];
    }
    return ['ok' => true, 'nama' => $nama];
}

function siswa_aktif(): ?array
{
    if (!empty($_SESSION['siswa_id']) && !empty($_SESSION['siswa_nama'])) {
        return ['id' => (int)$_SESSION['siswa_id'], 'nama' => (string)$_SESSION['siswa_nama']];
    }
    return null;
}

/** Halaman anak hanya bisa dibuka setelah mengisi nama. */
function wajib_siswa(): array
{
    $s = siswa_aktif();
    if (!$s) {
        redirect(url('index.php'));
    }
    // Pastikan siswa masih ada di database (mis. dihapus admin).
    $st = db()->prepare('SELECT id FROM siswa WHERE id = ?');
    $st->execute([$s['id']]);
    if (!$st->fetch()) {
        unset($_SESSION['siswa_id'], $_SESSION['siswa_nama'], $_SESSION['kuis']);
        redirect(url('index.php'));
    }
    return $s;
}

function siswa_keluar(): void
{
    unset($_SESSION['siswa_id'], $_SESSION['siswa_nama'], $_SESSION['kuis'], $_SESSION['hasil_terakhir']);
}

/** Total bintang = bintang terbaik dari tiap materi. */
function total_bintang(int $siswaId): int
{
    $st = db()->prepare(
        'SELECT COALESCE(SUM(b), 0) FROM (SELECT MAX(bintang) AS b FROM hasil WHERE siswa_id = ? GROUP BY materi_id) t'
    );
    $st->execute([$siswaId]);
    return (int)$st->fetchColumn();
}

/* ---------------------------------------------------------------
 * Bintang & isi materi
 * ------------------------------------------------------------- */
function bintang_dari(int $benar, int $total): int
{
    if ($total <= 0) {
        return 1;
    }
    $r = $benar / $total;
    if ($r >= BINTANG_3_MIN) {
        return 3;
    }
    if ($r >= BINTANG_2_MIN) {
        return 2;
    }
    return 1;
}

/** HTML deretan 3 bintang (yang terisi berwarna, sisanya pudar). */
function bintang_html(int $n, int $maks = 3): string
{
    $h = '<span class="bintang" aria-label="' . $n . ' dari ' . $maks . ' bintang">';
    for ($i = 1; $i <= $maks; $i++) {
        $h .= '<span class="' . ($i <= $n ? 'on' : 'off') . '">★</span>';
    }
    return $h . '</span>';
}

/**
 * Pecah isi materi menjadi kartu belajar.
 * Format per baris: "emoji | teks"  (emoji boleh dikosongkan: "| teks" atau hanya "teks").
 */
function pecah_kartu(string $isi): array
{
    $kartu = [];
    foreach (preg_split('/\R/u', $isi) as $baris) {
        $baris = trim($baris);
        if ($baris === '') {
            continue;
        }
        if (strpos($baris, '|') !== false) {
            [$emo, $teks] = array_map('trim', explode('|', $baris, 2));
        } else {
            $emo = '';
            $teks = $baris;
        }
        $kartu[] = ['emoji' => $emo, 'teks' => $teks];
    }
    return $kartu;
}

/* ---------------------------------------------------------------
 * Upload gambar (admin)
 * ------------------------------------------------------------- */

/**
 * Simpan gambar dari <input type="file" name="$field"> ke gambar/$sub/.
 * Return nama file relatif ("soal/ab12.png"), atau null jika tidak ada file dipilih.
 * Melempar RuntimeException dengan pesan ramah jika file tidak valid.
 * SVG sengaja tidak diizinkan (bisa berisi skrip).
 */
function upload_gambar(string $field, string $sub): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Gambar gagal diunggah (kode ' . (int)$f['error'] . ').');
    }
    if ($f['size'] > UPLOAD_MAKS_BYTE) {
        throw new RuntimeException('Ukuran gambar maksimal ' . (UPLOAD_MAKS_BYTE / 1024 / 1024) . ' MB.');
    }
    if (!is_uploaded_file($f['tmp_name'])) {
        throw new RuntimeException('Berkas tidak valid.');
    }
    $peta = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($peta[$mime]) || !in_array($peta[$mime], UPLOAD_EKSTENSI, true) || @getimagesize($f['tmp_name']) === false) {
        throw new RuntimeException('Hanya gambar JPG, PNG, GIF, atau WEBP yang diizinkan.');
    }
    if (!in_array($sub, ['mapel', 'materi', 'soal'], true)) {
        throw new RuntimeException('Folder tujuan tidak valid.');
    }
    $folder = dirname(__DIR__) . '/' . FOLDER_GAMBAR . '/' . $sub;
    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
        throw new RuntimeException('Folder gambar tidak bisa dibuat.');
    }
    $nama = bin2hex(random_bytes(8)) . '.' . $peta[$mime];   // nama acak, ekstensi dari isi file
    if (!move_uploaded_file($f['tmp_name'], $folder . '/' . $nama)) {
        throw new RuntimeException('Gambar tidak bisa disimpan. Periksa izin folder gambar/.');
    }
    return $sub . '/' . $nama;
}

/** Hapus file gambar lama (hanya di dalam folder gambar/mapel|materi|soal). */
function hapus_gambar(?string $path): void
{
    if (!$path || !preg_match('#^(mapel|materi|soal)/[A-Za-z0-9._-]+$#', $path)) {
        return;
    }
    $lengkap = dirname(__DIR__) . '/' . FOLDER_GAMBAR . '/' . $path;
    if (is_file($lengkap)) {
        @unlink($lengkap);
    }
}

/* ---------------------------------------------------------------
 * Admin
 * ------------------------------------------------------------- */
function admin_aktif(): ?array
{
    if (!empty($_SESSION['admin_id'])) {
        return ['id' => (int)$_SESSION['admin_id'], 'username' => (string)($_SESSION['admin_user'] ?? '')];
    }
    return null;
}

function wajib_admin(): array
{
    $a = admin_aktif();
    if (!$a) {
        redirect(url('admin/index.php'));
    }
    return $a;
}

function admin_ada(): bool
{
    return (int)db()->query('SELECT COUNT(*) FROM admin')->fetchColumn() > 0;
}
