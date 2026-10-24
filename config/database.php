<?php
/**
 * KONEKSI DATABASE
 * ------------------------------------------------------------------
 * Ubah 4 nilai di bawah sesuai komputer/hosting Anda.
 *   - XAMPP bawaan : user "root", password kosong ''
 *   - Hosting      : isi dengan data dari cPanel / panel hosting
 *
 * Nilai juga bisa diatur lewat environment variable (opsional, untuk server):
 *   BELAJAR_DB_HOST, BELAJAR_DB_NAME, BELAJAR_DB_USER, BELAJAR_DB_PASS
 */

define('DB_HOST', getenv('BELAJAR_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('BELAJAR_DB_NAME') ?: 'belajar_sd');
define('DB_USER', getenv('BELAJAR_DB_USER') ?: 'root');
define('DB_PASS', getenv('BELAJAR_DB_PASS') !== false ? getenv('BELAJAR_DB_PASS') : '');

/**
 * Mengembalikan satu koneksi PDO yang dipakai bersama (singleton).
 * Semua query di aplikasi memakai prepared statement.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        // Pesan ramah; detail error tidak ditampilkan ke pengguna.
        echo '<!doctype html><meta charset="utf-8"><title>Oops</title>'
           . '<body style="font-family:sans-serif;padding:2rem;text-align:center">'
           . '<h1>Database belum tersambung</h1>'
           . '<p>Periksa pengaturan di <code>config/database.php</code> '
           . 'dan pastikan database <code>belajar_sd</code> sudah di-import.</p></body>';
        exit;
    }
    return $pdo;
}
