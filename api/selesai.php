<?php
/**
 * API: kuis selesai -> hitung skor & bintang di server, simpan ke tabel `hasil`.
 * Keluaran: {ok, url} (url halaman hasil)
 */
require_once __DIR__ . '/../includes/fungsi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'pesan' => 'Metode tidak diizinkan.'], 405);
}
if (!csrf_valid()) {
    json_out(['ok' => false, 'pesan' => 'Sesi tidak valid. Muat ulang halaman.'], 400);
}
$siswa = siswa_aktif();
if (!$siswa) {
    json_out(['ok' => false, 'pesan' => 'Ketik namamu dulu ya.'], 401);
}

$kuis = $_SESSION['kuis'] ?? null;
if (!$kuis || empty($kuis['soal'])) {
    // Permintaan ganda (mis. tombol ditekan dua kali): arahkan ke hasil terakhir.
    if (!empty($_SESSION['hasil_terakhir'])) {
        json_out(['ok' => true, 'url' => url('hasil.php?id=' . (int)$_SESSION['hasil_terakhir'])]);
    }
    json_out(['ok' => false, 'pesan' => 'Kuis belum dimulai.'], 409);
}

$total = count($kuis['soal']);
$benar = 0;
foreach ($kuis['soal'] as $s) {
    if (!$s['selesai']) {
        json_out(['ok' => false, 'pesan' => 'Selesaikan semua soal dulu ya.'], 409);
    }
    if (!$s['salah']) {
        $benar++;
    }
}

$bintang = bintang_dari($benar, $total);
$skor = $benar * POIN_PER_SOAL;

$st = db()->prepare(
    'INSERT INTO hasil (siswa_id, materi_id, benar, total, bintang, skor) VALUES (?, ?, ?, ?, ?, ?)'
);
$st->execute([$siswa['id'], (int)$kuis['materi_id'], $benar, $total, $bintang, $skor]);
$hasilId = (int)db()->lastInsertId();

unset($_SESSION['kuis']);
$_SESSION['hasil_terakhir'] = $hasilId;

json_out(['ok' => true, 'url' => url('hasil.php?id=' . $hasilId)]);
