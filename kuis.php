<?php
/**
 * KUIS INTERAKTIF (satu soal per layar).
 * Server hanya mengirim soal + pilihan TANPA kunci jawaban. Jawaban dicek lewat api/cek.php,
 * dan status kuis (benar pada percobaan pertama) dicatat di sesi PHP.
 */
require_once __DIR__ . '/includes/fungsi.php';
$siswa = wajib_siswa();

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare(
    'SELECT m.*, mp.slug, mp.nama AS mapel_nama, mp.warna
       FROM materi m JOIN mapel mp ON mp.id = m.mapel_id
      WHERE m.id = ? AND m.aktif = 1 AND mp.aktif = 1'
);
$st->execute([$id]);
$materi = $st->fetch();
if (!$materi) {
    redirect(url('mapel.php'));
}

$st = db()->prepare('SELECT * FROM soal WHERE materi_id = ? AND aktif = 1 ORDER BY urutan, id');
$st->execute([$id]);
$semuaSoal = $st->fetchAll();

$pilihanPerSoal = [];
if ($semuaSoal) {
    $ids = array_column($semuaSoal, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare("SELECT * FROM pilihan_jawaban WHERE soal_id IN ($in) ORDER BY urutan, id");
    $st->execute($ids);
    foreach ($st->fetchAll() as $p) {
        $pilihanPerSoal[$p['soal_id']][] = $p;
    }
}

// Susun data untuk JavaScript (tanpa kolom `benar`) dan lewati soal yang belum lengkap.
$dataSoal = [];
$stateSoal = [];
foreach ($semuaSoal as $s) {
    $opsi = $pilihanPerSoal[$s['id']] ?? [];
    $jmlBenar = count(array_filter($opsi, fn($o) => (int)$o['benar'] === 1));
    $valid = $s['tipe'] === 'cocokkan'
        ? (count($opsi) >= 2 && $jmlBenar === count($opsi))
        : (count($opsi) >= 2 && $jmlBenar === 1);
    if (!$valid) {
        continue;   // soal rusak/belum lengkap tidak ditampilkan ke anak
    }
    if ($s['tipe'] === 'seret') {
        shuffle($opsi);
    }
    $dataSoal[] = [
        'id'         => (int)$s['id'],
        'tipe'       => $s['tipe'],
        'pertanyaan' => $s['pertanyaan'],
        'emoji'      => $s['emoji'],
        'gambar'     => $s['gambar'] ? gambar_url($s['gambar']) : '',
        'opsi'       => array_map(fn($o) => [
            'id'     => (int)$o['id'],
            'teks'   => $o['teks'],
            'emoji'  => $o['emoji'],
            'gambar' => $o['gambar'] ? gambar_url($o['gambar']) : '',
        ], $opsi),
    ];
    $stateSoal[(int)$s['id']] = ['salah' => false, 'selesai' => false, 'cocok' => []];
}
if (!$dataSoal) {
    flash_set('info', 'Soal untuk materi ini belum siap.');
    redirect(url('materi.php?mapel=' . urlencode($materi['slug'])));
}

// Mulai kuis baru: reset status di sesi.
$_SESSION['kuis'] = ['materi_id' => $id, 'mulai' => time(), 'soal' => $stateSoal];

$dataJs = [
    'materi' => ['id' => $id, 'judul' => $materi['judul']],
    'nama'   => $siswa['nama'],
    'soal'   => $dataSoal,
    'urlBack' => url('materi.php?mapel=' . urlencode($materi['slug'])),
];

$judul = 'Kuis ' . $materi['judul'];
$warna = $materi['warna'];
$bodyKelas = 'halaman-kuis';
$skrip = ['kuis.js'];
require __DIR__ . '/includes/header.php';
?>
<nav class="navigasi-atas">
  <a class="tombol tombol-putih" href="<?= e(url('materi.php?mapel=' . urlencode($materi['slug']))) ?>">⬅️ Keluar</a>
  <button type="button" class="tombol tombol-kuning" id="dengarSoal">🔊 Dengarkan</button>
</nav>

<section class="kuis" id="kuis"
         data-api-cek="<?= e(url('api/cek.php')) ?>"
         data-api-selesai="<?= e(url('api/selesai.php')) ?>">
  <div class="kuis-atas">
    <p class="progres-teks">Soal <b id="noSoal">1</b> dari <b id="jmlSoal"><?= count($dataSoal) ?></b></p>
    <div class="batang besar" role="progressbar" aria-valuemin="0" aria-valuemax="100"><span id="batangProgres"></span></div>
  </div>

  <?= maskot_html('Yuk, kita mulai, ' . $siswa['nama'] . '!', 'maskotKuis') ?>

  <div id="areaSoal" class="area-soal kartu" aria-live="polite"></div>

  <div class="kuis-bawah">
    <button type="button" class="tombol tombol-hijau tombol-besar" id="lanjut" hidden>Lanjut ➡️</button>
  </div>
</section>

<script type="application/json" id="dataKuis"><?= json_encode($dataJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
