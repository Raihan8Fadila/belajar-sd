<?php
/**
 * API: cek satu jawaban.
 * Masukan (JSON) : {soal_id, pilihan_id}            untuk tipe pilihan & seret
 *                  {soal_id, kiri, kanan}            untuk tipe cocokkan (satu pasangan)
 * Keluaran       : {ok, benar, selesai, pertama}
 * Kunci jawaban tidak pernah dikirim ke browser.
 */
require_once __DIR__ . '/../includes/fungsi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'pesan' => 'Metode tidak diizinkan.'], 405);
}
if (!csrf_valid()) {
    json_out(['ok' => false, 'pesan' => 'Sesi tidak valid. Muat ulang halaman.'], 400);
}
if (!siswa_aktif()) {
    json_out(['ok' => false, 'pesan' => 'Ketik namamu dulu ya.'], 401);
}

$in = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($in)) {
    json_out(['ok' => false, 'pesan' => 'Data tidak valid.'], 400);
}

$soalId = (int)($in['soal_id'] ?? 0);
if (!isset($_SESSION['kuis']['soal'][$soalId])) {
    json_out(['ok' => false, 'pesan' => 'Kuis belum dimulai. Muat ulang halaman.'], 409);
}

$st = db()->prepare('SELECT tipe FROM soal WHERE id = ? AND aktif = 1');
$st->execute([$soalId]);
$soal = $st->fetch();
if (!$soal) {
    json_out(['ok' => false, 'pesan' => 'Soal tidak ditemukan.'], 404);
}

$st = db()->prepare('SELECT id, benar FROM pilihan_jawaban WHERE soal_id = ?');
$st->execute([$soalId]);
$pilihan = [];
foreach ($st->fetchAll() as $p) {
    $pilihan[(int)$p['id']] = (int)$p['benar'] === 1;
}

$status = &$_SESSION['kuis']['soal'][$soalId];

// Soal sudah selesai: jangan ubah apa pun.
if ($status['selesai']) {
    json_out(['ok' => true, 'benar' => true, 'selesai' => true, 'pertama' => !$status['salah']]);
}

if ($soal['tipe'] === 'cocokkan') {
    $kiri = (int)($in['kiri'] ?? 0);
    $kanan = (int)($in['kanan'] ?? 0);
    if (!isset($pilihan[$kiri], $pilihan[$kanan])) {
        json_out(['ok' => false, 'pesan' => 'Pilihan tidak valid.'], 400);
    }
    if (in_array($kiri, $status['cocok'], true)) {
        json_out(['ok' => true, 'benar' => true, 'selesai' => false, 'pertama' => false]);
    }
    $benar = ($kiri === $kanan);
    if ($benar) {
        $status['cocok'][] = $kiri;
        $status['selesai'] = count($status['cocok']) === count($pilihan);
    } else {
        $status['salah'] = true;
    }
} else {
    $pid = (int)($in['pilihan_id'] ?? 0);
    if (!isset($pilihan[$pid])) {
        json_out(['ok' => false, 'pesan' => 'Pilihan tidak valid.'], 400);
    }
    $benar = $pilihan[$pid];
    if ($benar) {
        $status['selesai'] = true;
    } else {
        $status['salah'] = true;
    }
}

json_out([
    'ok'      => true,
    'benar'   => $benar,
    'selesai' => $status['selesai'],
    'pertama' => $status['selesai'] && !$status['salah'],
]);
