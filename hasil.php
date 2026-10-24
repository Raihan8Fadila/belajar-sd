<?php
/**
 * HASIL KUIS: bintang, skor, kalimat penyemangat, dan tombol lanjut.
 */
require_once __DIR__ . '/includes/fungsi.php';
$siswa = wajib_siswa();

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare(
    'SELECT h.*, m.judul, m.emoji, mp.slug, mp.nama AS mapel_nama, mp.warna
       FROM hasil h
       JOIN materi m  ON m.id = h.materi_id
       JOIN mapel  mp ON mp.id = m.mapel_id
      WHERE h.id = ? AND h.siswa_id = ?'
);
$st->execute([$id, $siswa['id']]);
$h = $st->fetch();
if (!$h) {
    redirect(url('mapel.php'));
}

$pesan = [
    3 => 'Luar biasa, %s! Semua jawabanmu hebat!',
    2 => 'Bagus sekali, %s! Sedikit lagi sempurna!',
    1 => 'Kamu sudah berusaha, %s! Yuk coba lagi, pasti bisa!',
][(int)$h['bintang']];
$pesanMaskot = [
    3 => 'Hebat sekali! Kamu pintar!',
    2 => 'Bagus! Ayo main lagi supaya makin pintar!',
    1 => 'Tidak apa-apa. Belajar itu perlahan-lahan. Semangat!',
][(int)$h['bintang']];

$judul = 'Hasil Kuis';
$warna = $h['warna'];
$bodyKelas = 'halaman-hasil';
$skrip = ['hasil.js'];
require __DIR__ . '/includes/header.php';
?>
<section class="hasil kartu" data-bintang="<?= (int)$h['bintang'] ?>">
  <?= maskot_html($pesanMaskot) ?>
  <h1><?= e(sprintf($pesan, $siswa['nama'])) ?></h1>
  <p class="materi-nama"><?= e($h['emoji']) ?> <?= e($h['judul']) ?></p>

  <div class="bintang-besar" aria-label="<?= (int)$h['bintang'] ?> dari 3 bintang">
    <?php for ($i = 1; $i <= 3; $i++): ?>
      <span class="<?= $i <= $h['bintang'] ? 'on' : 'off' ?>" style="--i:<?= $i ?>">★</span>
    <?php endfor; ?>
  </div>

  <p class="skor-teks">Kamu menjawab <strong><?= (int)$h['benar'] ?></strong> dari
    <strong><?= (int)$h['total'] ?></strong> soal dengan benar<br>
    <span class="poin">🏆 <?= (int)$h['skor'] ?> poin</span></p>

  <div class="kontrol kontrol-hasil">
    <a class="tombol tombol-biru" href="<?= e(url('kuis.php?id=' . (int)$h['materi_id'])) ?>">🔁 Main Lagi</a>
    <a class="tombol tombol-kuning" href="<?= e(url('materi.php?mapel=' . urlencode($h['slug']))) ?>">📚 Materi Lain</a>
    <a class="tombol tombol-hijau" href="<?= e(url('mapel.php')) ?>">🏠 Menu</a>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
