<?php
/**
 * KARTU BELAJAR: materi dipecah jadi kartu pendek bergambar (satu per layar),
 * lalu anak menekan "Mulai Kuis". Isi kartu diatur di halaman admin.
 */
require_once __DIR__ . '/includes/fungsi.php';
$siswa = wajib_siswa();

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare(
    'SELECT m.*, mp.slug, mp.nama AS mapel_nama, mp.warna,
        (SELECT COUNT(*) FROM soal s WHERE s.materi_id = m.id AND s.aktif = 1) AS jml_soal
       FROM materi m JOIN mapel mp ON mp.id = m.mapel_id
      WHERE m.id = ? AND m.aktif = 1 AND mp.aktif = 1'
);
$st->execute([$id]);
$materi = $st->fetch();
if (!$materi) {
    redirect(url('mapel.php'));
}

$kartu = pecah_kartu((string)$materi['isi']);
$adaKuis = (int)$materi['jml_soal'] > 0;
if (!$kartu && $adaKuis) {
    redirect(url('kuis.php?id=' . $id));       // tidak ada kartu belajar -> langsung kuis
}

$judul = $materi['judul'];
$warna = $materi['warna'];
$skrip = ['belajar.js'];
require __DIR__ . '/includes/header.php';
?>
<nav class="navigasi-atas">
  <a class="tombol tombol-putih" href="<?= e(url('materi.php?mapel=' . urlencode($materi['slug']))) ?>">⬅️ Kembali</a>
</nav>

<section class="belajar kartu" data-total="<?= count($kartu) ?>">
  <h1 class="judul-materi"><?= e($materi['emoji']) ?> <?= e($materi['judul']) ?></h1>
  <?php if ($materi['gambar']): ?>
    <img class="gambar-materi" src="<?= e(gambar_url($materi['gambar'])) ?>" alt="">
  <?php endif; ?>

  <p class="indikator" aria-live="polite">Kartu <b id="noKartu">1</b> dari <?= count($kartu) ?></p>

  <div class="slides">
    <?php foreach ($kartu as $i => $k): ?>
      <article class="slide<?= $i === 0 ? ' aktif' : '' ?>" data-baca="<?= e($k['teks']) ?>">
        <?php if ($k['emoji'] !== ''): ?><div class="slide-emoji" aria-hidden="true"><?= e($k['emoji']) ?></div><?php endif; ?>
        <p class="slide-teks"><?= e($k['teks']) ?></p>
        <button type="button" class="tombol tombol-kuning dengar">🔊 Dengarkan</button>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="kontrol">
    <button type="button" class="tombol tombol-putih" id="sebelum">⬅️ Sebelum</button>
    <button type="button" class="tombol tombol-biru" id="berikut">Lanjut ➡️</button>
    <?php if ($adaKuis): ?>
      <a class="tombol tombol-hijau" id="mulai" href="<?= e(url('kuis.php?id=' . $id)) ?>">🎮 Mulai Kuis</a>
    <?php else: ?>
      <a class="tombol tombol-hijau" id="mulai" href="<?= e(url('materi.php?mapel=' . urlencode($materi['slug']))) ?>">✅ Selesai</a>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
