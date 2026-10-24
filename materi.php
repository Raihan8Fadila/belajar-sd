<?php
/**
 * PILIH MATERI / LEVEL dalam satu mapel.
 * Menampilkan bintang terbaik anak untuk tiap materi.
 */
require_once __DIR__ . '/includes/fungsi.php';
$siswa = wajib_siswa();

$slug = (string)($_GET['mapel'] ?? '');
$st = db()->prepare('SELECT * FROM mapel WHERE slug = ? AND aktif = 1');
$st->execute([$slug]);
$mapel = $st->fetch();
if (!$mapel) {
    redirect(url('mapel.php'));
}

// Hanya materi aktif yang punya minimal satu soal aktif.
$st = db()->prepare(
    'SELECT m.*,
        (SELECT COUNT(*) FROM soal s WHERE s.materi_id = m.id AND s.aktif = 1) AS jml_soal,
        (SELECT MAX(h.bintang) FROM hasil h WHERE h.materi_id = m.id AND h.siswa_id = ?) AS terbaik
       FROM materi m
      WHERE m.mapel_id = ? AND m.aktif = 1
        AND EXISTS (SELECT 1 FROM soal s WHERE s.materi_id = m.id AND s.aktif = 1)
      ORDER BY m.urutan, m.id'
);
$st->execute([$siswa['id'], $mapel['id']]);
$daftar = $st->fetchAll();

$judul = $mapel['nama'];
$warna = $mapel['warna'];
require __DIR__ . '/includes/header.php';
?>
<nav class="navigasi-atas">
  <a class="tombol tombol-putih" href="<?= e(url('mapel.php')) ?>">⬅️ Kembali</a>
</nav>

<section class="judul-halaman">
  <?= maskot_html('Pilih materi ' . $mapel['nama'] . ' ya!') ?>
  <h1 class="judul-mapel"><span><?= e($mapel['emoji']) ?></span> <?= e($mapel['nama']) ?></h1>
</section>

<?php if (!$daftar): ?>
  <p class="kosong kartu">Materi untuk pelajaran ini belum ada. Minta guru atau orang tua menambahkannya ya.</p>
<?php endif; ?>

<section class="grid-kartu grid-materi">
  <?php foreach ($daftar as $i => $m): ?>
    <a class="kartu-materi" href="<?= e(url('belajar.php?id=' . (int)$m['id'])) ?>">
      <span class="nomor"><?= $i + 1 ?></span>
      <span class="kartu-ikon kecil">
        <?php if ($m['gambar']): ?>
          <img src="<?= e(gambar_url($m['gambar'])) ?>" alt="" width="64" height="64">
        <?php else: ?><?= e($m['emoji']) ?><?php endif; ?>
      </span>
      <span class="kartu-nama"><?= e($m['judul']) ?></span>
      <span class="kartu-desk"><?= e($m['deskripsi']) ?></span>
      <span class="kartu-bintang"><?= bintang_html((int)$m['terbaik']) ?></span>
    </a>
  <?php endforeach; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
