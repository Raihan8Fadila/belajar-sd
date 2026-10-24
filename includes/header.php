<?php
/**
 * KEPALA HALAMAN ANAK
 * Variabel yang bisa diisi sebelum include:
 *   $judul      judul halaman
 *   $bodyKelas  kelas CSS tambahan pada <body>
 *   $warna      warna tema (mis. warna mapel) untuk variabel CSS --warna
 *   $tanpaBar   true = tanpa bilah atas (dipakai di halaman awal)
 */
$siswa = $siswa ?? siswa_aktif();

/** Maskot dengan gelembung ucapan. Gambar maskot bisa diganti di gambar/maskot.svg */
function maskot_html(string $teks, string $id = 'maskotTeks'): string
{
    return '<div class="maskot">'
         . '<img class="maskot-gambar" src="' . e(gambar_url('maskot.svg')) . '" alt="Maskot Bobi" width="120" height="120">'
         . '<div class="gelembung" id="' . e($id) . '" role="status" aria-live="polite">' . e($teks) . '</div>'
         . '</div>';
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($judul ?? '') !== '' ? $judul . ' · ' : '') . e(APP_NAMA) ?></title>
<link rel="icon" href="<?= e(gambar_url('maskot.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body class="<?= e($bodyKelas ?? '') ?>"<?= !empty($warna) && warna_valid($warna) ? ' style="--warna:' . e($warna) . '"' : '' ?> data-csrf="<?= e(csrf_token()) ?>">
<div class="awan awan-1" aria-hidden="true"></div>
<div class="awan awan-2" aria-hidden="true"></div>
<?php if (empty($tanpaBar)): ?>
<header class="bar">
  <a class="bar-logo" href="<?= e(url('mapel.php')) ?>" aria-label="Ke beranda">
    <img src="<?= e(gambar_url('maskot.svg')) ?>" alt="" width="40" height="40">
    <span><?= e(APP_NAMA) ?></span>
  </a>
  <div class="bar-kanan">
    <?php if ($siswa): ?><span class="bar-nama">👋 <?= e($siswa['nama']) ?></span><?php endif; ?>
    <button type="button" class="tombol-kecil" id="tombolSuara" aria-pressed="true">🔊 Suara: ON</button>
    <a class="tombol-kecil" href="<?= e(url('keluar.php')) ?>" aria-label="Ganti nama">🚪<span class="hanya-lebar">&nbsp;Ganti nama</span></a>
  </div>
</header>
<?php else: ?>
<button type="button" class="tombol-kecil melayang" id="tombolSuara" aria-pressed="true">🔊 Suara: ON</button>
<?php endif; ?>
<main class="isi">
<?php if ($f = flash_ambil()): ?>
  <p class="flash flash-<?= e($f['tipe']) ?>" role="status"><?= e($f['pesan']) ?></p>
<?php endif; ?>
