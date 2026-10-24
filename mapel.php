<?php
/**
 * PILIH MATA PELAJARAN
 * Sapaan nama anak, total bintang, dan kartu besar tiap mapel.
 */
require_once __DIR__ . '/includes/fungsi.php';
$siswa = wajib_siswa();

// Jumlah materi (yang punya soal) dan berapa yang sudah pernah diselesaikan anak ini.
$st = db()->prepare(
    'SELECT mp.*,
        (SELECT COUNT(*) FROM materi m
          WHERE m.mapel_id = mp.id AND m.aktif = 1
            AND EXISTS (SELECT 1 FROM soal s WHERE s.materi_id = m.id AND s.aktif = 1)) AS jml_materi,
        (SELECT COUNT(DISTINCT h.materi_id) FROM hasil h
           JOIN materi m ON m.id = h.materi_id
          WHERE h.siswa_id = ? AND m.mapel_id = mp.id AND m.aktif = 1) AS selesai
       FROM mapel mp WHERE mp.aktif = 1 ORDER BY mp.urutan, mp.id'
);
$st->execute([$siswa['id']]);
$daftar = $st->fetchAll();
$bintang = total_bintang($siswa['id']);

$judul = 'Pilih Pelajaran';
require __DIR__ . '/includes/header.php';
?>
<section class="judul-halaman">
  <?= maskot_html('Halo, ' . $siswa['nama'] . '! Hari ini mau belajar apa?') ?>
  <div class="lencana-bintang" title="Bintangmu">⭐ <strong><?= $bintang ?></strong> <span>bintangmu</span></div>
</section>

<?php if (!$daftar): ?>
  <p class="kosong kartu">Belum ada pelajaran. Minta guru atau orang tua menambahkannya ya.</p>
<?php endif; ?>

<section class="grid-kartu" aria-label="Daftar pelajaran">
  <?php foreach ($daftar as $m): ?>
    <a class="kartu-mapel" href="<?= e(url('materi.php?mapel=' . urlencode($m['slug']))) ?>"
       style="--warna:<?= e(warna_valid($m['warna']) ? $m['warna'] : '#A9D2FF') ?>">
      <span class="kartu-ikon">
        <?php if ($m['gambar']): ?>
          <img src="<?= e(gambar_url($m['gambar'])) ?>" alt="" width="96" height="96">
        <?php else: ?><?= e($m['emoji']) ?><?php endif; ?>
      </span>
      <span class="kartu-nama"><?= e($m['nama']) ?></span>
      <span class="kartu-desk"><?= e($m['deskripsi']) ?></span>
      <?php if ($m['jml_materi'] > 0): ?>
        <span class="kartu-progres" aria-label="<?= (int)$m['selesai'] ?> dari <?= (int)$m['jml_materi'] ?> materi selesai">
          <span class="batang"><span style="width:<?= min(100, round($m['selesai'] / $m['jml_materi'] * 100)) ?>%"></span></span>
          <small><?= min((int)$m['selesai'], (int)$m['jml_materi']) ?> / <?= (int)$m['jml_materi'] ?> materi</small>
        </span>
      <?php endif; ?>
    </a>
  <?php endforeach; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
