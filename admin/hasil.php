<?php

declare(strict_types=1);

/**
 * HASIL SISWA (untuk guru/orang tua)
 *  - Tab "Riwayat": setiap kuis yang diselesaikan, bisa difilter nama/mapel dan diunduh CSV.
 *  - Tab "Siswa"  : ringkasan per nama + tombol hapus data siswa (privasi anak).
 */

define('AREA_ADMIN', true);
require_once __DIR__ . '/../includes/fungsi.php';
require_once __DIR__ . '/../includes/admin_layout.php';

wajib_admin();

const PER_HALAMAN = 25;

/** Hindari CSV injection: sel yang diawali = + - @ diberi tanda petik. */
function csv_aman(string $s): string
{
  return preg_match('/^[=+\-@\t\r]/', $s) ? "'" . $s : $s;
}

/* ---------------------------------------------------------------
 * HAPUS DATA SEORANG SISWA (beserta hasilnya)
 * ------------------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_wajib();

  if (($_POST['aksi'] ?? '') === 'hapus_siswa') {
    db()->prepare('DELETE FROM siswa WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
    flash_set('ok', 'Data siswa dan hasilnya sudah dihapus.');
  }

  redirect('hasil.php?tab=siswa');
}

/* ---------------------------------------------------------------
 * PARAMETER & FILTER
 * ------------------------------------------------------------- */

$tab       = ($_GET['tab'] ?? 'riwayat') === 'siswa' ? 'siswa' : 'riwayat';
$cariNama  = trim((string) ($_GET['nama'] ?? ''));
$mapelId   = (int) ($_GET['mapel_id'] ?? 0);
$mapelList = db()->query('SELECT id, nama, emoji FROM mapel ORDER BY urutan, id')->fetchAll();

$where = [];
$param = [];

if ($cariNama !== '') {
  $where[] = 's.nama LIKE ?';
  $param[] = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $cariNama) . '%';
}
if ($mapelId) {
  $where[] = 'mp.id = ?';
  $param[] = $mapelId;
}

$sqlDari = ' FROM hasil h
             JOIN siswa s  ON s.id  = h.siswa_id
             JOIN materi m ON m.id  = h.materi_id
             JOIN mapel mp ON mp.id = m.mapel_id'
  . ($where ? ' WHERE ' . implode(' AND ', $where) : '');

/* ---------------------------------------------------------------
 * UNDUH CSV
 * ------------------------------------------------------------- */

if ($tab === 'riwayat' && isset($_GET['unduh'])) {
  $st = db()->prepare(
    'SELECT h.waktu, s.nama, mp.nama AS mapel, m.judul, h.benar, h.total, h.bintang, h.skor'
      . $sqlDari . ' ORDER BY h.waktu DESC, h.id DESC'
  );
  $st->execute($param);

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="hasil-siswa-' . date('Ymd') . '.csv"');

  $out = fopen('php://output', 'w');
  fwrite($out, "\xEF\xBB\xBF");   // BOM agar Excel membaca UTF-8
  fputcsv($out, ['Waktu', 'Nama', 'Mapel', 'Materi', 'Benar', 'Total', 'Bintang', 'Skor']);

  foreach ($st->fetchAll() as $r) {
    fputcsv($out, [
      $r['waktu'],
      csv_aman((string) $r['nama']),
      csv_aman((string) $r['mapel']),
      csv_aman((string) $r['judul']),
      $r['benar'],
      $r['total'],
      $r['bintang'],
      $r['skor'],
    ]);
  }
  exit;
}

/* ---------------------------------------------------------------
 * DATA TAB RIWAYAT
 * ------------------------------------------------------------- */

$hal       = max(1, (int) ($_GET['hal'] ?? 1));
$riwayat   = [];
$ringkasan = ['n' => 0, 'siswa' => 0, 'benar' => 0, 'total' => 0, 'bintang' => 0];

if ($tab === 'riwayat') {
  $st = db()->prepare(
    'SELECT COUNT(*) AS n, COUNT(DISTINCT h.siswa_id) AS siswa,
                COALESCE(SUM(h.benar), 0) AS benar, COALESCE(SUM(h.total), 0) AS total,
                COALESCE(SUM(h.bintang), 0) AS bintang'
      . $sqlDari
  );
  $st->execute($param);
  $ringkasan = array_map('intval', $st->fetch());

  $st = db()->prepare(
    'SELECT h.*, s.nama, mp.nama AS mapel, mp.emoji AS mapel_emoji, m.judul' . $sqlDari
      . ' ORDER BY h.waktu DESC, h.id DESC LIMIT ' . PER_HALAMAN . ' OFFSET ' . (($hal - 1) * PER_HALAMAN)
  );
  $st->execute($param);
  $riwayat = $st->fetchAll();
}

$jmlBaris = $ringkasan['n'];
$jmlHal   = max(1, (int) ceil($jmlBaris / PER_HALAMAN));
$akurasi  = $ringkasan['total'] > 0 ? (int) round($ringkasan['benar'] / $ringkasan['total'] * 100) : 0;

/* ---------------------------------------------------------------
 * DATA TAB SISWA
 * ------------------------------------------------------------- */

$siswaList = [];

if ($tab === 'siswa') {
  $siswaList = db()->query(
    'SELECT s.id, s.nama, s.dibuat, s.terakhir_aktif,
                (SELECT COUNT(*) FROM hasil h WHERE h.siswa_id = s.id) AS jml_kuis,
                (SELECT COALESCE(SUM(b), 0)
                   FROM (SELECT siswa_id, MAX(bintang) AS b FROM hasil GROUP BY siswa_id, materi_id) t
                  WHERE t.siswa_id = s.id) AS bintang
           FROM siswa s
          ORDER BY s.terakhir_aktif DESC
          LIMIT 300'
  )->fetchAll();
}

/* ---------------------------------------------------------------
 * HELPER TAMPILAN
 * ------------------------------------------------------------- */

$qs = http_build_query(array_filter(['nama' => $cariNama, 'mapel_id' => $mapelId ?: null]));

/** Tautan halaman riwayat dengan filter yang sedang aktif. */
$linkHal = static function (int $p) use ($cariNama, $mapelId): string {
  $q = http_build_query(array_filter([
    'nama'     => $cariNama,
    'mapel_id' => $mapelId ?: null,
    'hal'      => $p > 1 ? $p : null,
  ]));
  return 'hasil.php' . ($q !== '' ? '?' . $q : '');
};

/** Daftar nomor halaman dengan elipsis, mis. 1 … 4 5 [6] 7 8 … 20 */
$nomorHal = static function (int $sekarang, int $akhir): array {
  $pilih = array_unique(array_filter(
    array_merge([1, $akhir], range($sekarang - 2, $sekarang + 2)),
    static fn(int $p): bool => $p >= 1 && $p <= $akhir
  ));
  sort($pilih);

  $hasil = [];
  $prev  = 0;
  foreach ($pilih as $p) {
    if ($p - $prev > 1) {
      $hasil[] = '…';
    }
    $hasil[] = $p;
    $prev    = $p;
  }
  return $hasil;
};

$inisial = static fn(string $nama): string =>
mb_strtoupper(mb_substr(trim($nama) !== '' ? trim($nama) : '?', 0, 1));

$persen = static fn(int $benar, int $total): int =>
$total > 0 ? (int) round($benar / $total * 100) : 0;

$kelasNilai = static fn(int $p): string =>
$p >= 80 ? 'tinggi' : ($p >= 50 ? 'sedang' : 'rendah');

$adaFilter = $cariNama !== '' || $mapelId > 0;

admin_kepala('Hasil Siswa', 'hasil');
?>

<style>
  .hsl {
    --hsl-teks: #1f2937;
    --hsl-redup: #6b7280;
    --hsl-garis: #e5e7eb;
    --hsl-ungu: #7c3aed;
    --hsl-hijau: #10b981;
    --hsl-kuning: #f59e0b;
    --hsl-merah: #ef4444;
    --hsl-radius: 18px;
    color: var(--hsl-teks);
  }

  .hsl * {
    box-sizing: border-box;
  }

  /* Kepala */
  .hsl-kepala {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1.3rem 1.5rem;
    margin-bottom: 1.2rem;
    color: #fff;
    border-radius: var(--hsl-radius);
    background: linear-gradient(135deg, #f59e0b 0%, #ef4444 50%, #a855f7 100%);
    box-shadow: 0 12px 28px -12px rgba(239, 68, 68, .6);
  }

  .hsl-kepala h1 {
    margin: 0 0 .2rem;
    font-size: 1.45rem;
  }

  .hsl-kepala p {
    margin: 0;
    opacity: .92;
    font-size: .95rem;
  }

  .hsl-kepala .emoji {
    font-size: 2.6rem;
    animation: hsl-loncat 2.4s ease-in-out infinite;
  }

  @keyframes hsl-loncat {

    0%,
    100% {
      transform: translateY(0) rotate(-6deg);
    }

    50% {
      transform: translateY(-8px) rotate(6deg);
    }
  }

  /* Tab */
  .hsl-tab {
    display: inline-flex;
    gap: .3rem;
    padding: .3rem;
    margin-bottom: 1.2rem;
    background: #fff;
    border: 1px solid var(--hsl-garis);
    border-radius: 999px;
    box-shadow: 0 6px 16px -12px rgba(0, 0, 0, .4);
  }

  .hsl-tab a {
    padding: .55rem 1.2rem;
    font-size: .93rem;
    font-weight: 800;
    color: var(--hsl-redup);
    text-decoration: none;
    border-radius: 999px;
    transition: background .18s, color .18s;
  }

  .hsl-tab a:hover {
    background: #f5f3ff;
    color: var(--hsl-ungu);
  }

  .hsl-tab a.aktif {
    color: #fff;
    background: linear-gradient(135deg, #8b5cf6, #ec4899);
    box-shadow: 0 4px 10px -4px rgba(139, 92, 246, .7);
  }

  /* Statistik */
  .hsl-stat {
    display: grid;
    gap: .9rem;
    margin-bottom: 1.2rem;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  }

  .hsl-kotak {
    display: flex;
    align-items: center;
    gap: .8rem;
    padding: 1rem 1.1rem;
    background: #fff;
    border: 1px solid var(--hsl-garis);
    border-radius: var(--hsl-radius);
    animation: hsl-masuk .45s ease both;
    animation-delay: calc(var(--i, 0) * 70ms);
  }

  .hsl-kotak .ik {
    display: grid;
    place-items: center;
    flex: none;
    width: 48px;
    height: 48px;
    font-size: 1.5rem;
    border-radius: 14px;
  }

  .hsl-kotak b {
    display: block;
    font-size: 1.6rem;
    line-height: 1.1;
  }

  .hsl-kotak span {
    font-size: .85rem;
    color: var(--hsl-redup);
  }

  .hsl-kotak.k1 .ik {
    background: #dbeafe;
  }

  .hsl-kotak.k2 .ik {
    background: #ede9fe;
  }

  .hsl-kotak.k3 .ik {
    background: #d1fae5;
  }

  .hsl-kotak.k4 .ik {
    background: #fef3c7;
  }

  @keyframes hsl-masuk {
    from {
      opacity: 0;
      transform: translateY(12px);
    }

    to {
      opacity: 1;
      transform: none;
    }
  }

  /* Filter */
  .hsl-filter {
    display: flex;
    align-items: flex-end;
    gap: .8rem;
    flex-wrap: wrap;
    padding: 1rem 1.1rem;
    margin-bottom: 1.2rem;
    background: #fff;
    border: 1px solid var(--hsl-garis);
    border-radius: var(--hsl-radius);
  }

  .hsl-filter label {
    display: grid;
    gap: .3rem;
    font-size: .82rem;
    font-weight: 700;
    color: var(--hsl-redup);
  }

  .hsl-filter input,
  .hsl-filter select {
    min-width: 190px;
    padding: .65rem .9rem;
    font: inherit;
    font-weight: 400;
    color: var(--hsl-teks);
    background: #f9fafb;
    border: 2px solid var(--hsl-garis);
    border-radius: 12px;
    outline: none;
    transition: border-color .2s, box-shadow .2s, background .2s;
  }

  .hsl-filter input:focus,
  .hsl-filter select:focus {
    background: #fff;
    border-color: var(--hsl-ungu);
    box-shadow: 0 0 0 4px rgba(124, 58, 237, .15);
  }

  .hsl-filter .ruang {
    flex: 1;
  }

  /* Tombol */
  .hsl-btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .65rem 1.2rem;
    font: inherit;
    font-weight: 800;
    color: #fff;
    text-decoration: none;
    cursor: pointer;
    border: 0;
    border-radius: 999px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    box-shadow: 0 4px 0 #4c1d95;
    transition: transform .12s, box-shadow .12s;
  }

  .hsl-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #4c1d95;
  }

  .hsl-btn:active {
    transform: translateY(3px);
    box-shadow: 0 1px 0 #4c1d95;
  }

  .hsl-btn.abu {
    color: #374151;
    background: #e5e7eb;
    box-shadow: 0 4px 0 #9ca3af;
  }

  .hsl-btn.abu:hover {
    box-shadow: 0 6px 0 #9ca3af;
  }

  .hsl-btn.hijau {
    background: linear-gradient(135deg, #10b981, #059669);
    box-shadow: 0 4px 0 #047857;
  }

  .hsl-btn.hijau:hover {
    box-shadow: 0 6px 0 #047857;
  }

  /* Tabel riwayat */
  .hsl-wadah {
    overflow-x: auto;
    background: #fff;
    border: 1px solid var(--hsl-garis);
    border-radius: var(--hsl-radius);
  }

  .hsl-tabel {
    width: 100%;
    border-collapse: collapse;
  }

  .hsl-tabel th {
    padding: .75rem 1rem;
    font-size: .76rem;
    letter-spacing: .04em;
    text-align: left;
    text-transform: uppercase;
    color: var(--hsl-redup);
    background: #f9fafb;
    border-bottom: 1px solid var(--hsl-garis);
    white-space: nowrap;
  }

  .hsl-tabel td {
    padding: .75rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
  }

  .hsl-tabel tr:last-child td {
    border-bottom: 0;
  }

  .hsl-tabel tbody tr {
    transition: background .15s;
  }

  .hsl-tabel tbody tr:hover {
    background: #faf5ff;
  }

  .hsl-waktu {
    white-space: nowrap;
    font-size: .88rem;
    color: var(--hsl-redup);
  }

  .hsl-siswa {
    display: flex;
    align-items: center;
    gap: .6rem;
    font-weight: 700;
    white-space: nowrap;
  }

  .hsl-avatar {
    flex: none;
    display: grid;
    place-items: center;
    width: 34px;
    height: 34px;
    font-size: .9rem;
    color: #fff;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #ec4899);
  }

  .hsl-mapel {
    display: inline-block;
    padding: .15rem .65rem;
    font-size: .78rem;
    font-weight: 800;
    color: #5b21b6;
    background: #ede9fe;
    border-radius: 999px;
    white-space: nowrap;
  }

  .hsl-skor {
    display: flex;
    align-items: center;
    gap: .6rem;
    min-width: 150px;
  }

  .hsl-bar {
    flex: 1;
    height: 8px;
    overflow: hidden;
    background: #e5e7eb;
    border-radius: 999px;
  }

  .hsl-bar i {
    display: block;
    height: 100%;
    border-radius: 999px;
  }

  .hsl-bar i.tinggi {
    background: var(--hsl-hijau);
  }

  .hsl-bar i.sedang {
    background: var(--hsl-kuning);
  }

  .hsl-bar i.rendah {
    background: var(--hsl-merah);
  }

  .hsl-skor small {
    white-space: nowrap;
    color: var(--hsl-redup);
  }

  .hsl-poin {
    font-weight: 800;
    color: #b45309;
  }

  /* Kartu siswa */
  .hsl-catatan {
    margin: 0 0 1rem;
    padding: .7rem 1rem;
    font-size: .9rem;
    color: #1e40af;
    background: #dbeafe;
    border-radius: 12px;
  }

  .hsl-daftar {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  }

  .hsl-kartu {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1.3rem 1.1rem 1.1rem;
    text-align: center;
    background: #fff;
    border: 1px solid var(--hsl-garis);
    border-radius: var(--hsl-radius);
    transition: transform .2s, box-shadow .2s;
    animation: hsl-masuk .45s ease both;
    animation-delay: calc(var(--i, 0) * 40ms);
  }

  .hsl-kartu:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 30px -14px rgba(0, 0, 0, .35);
  }

  .hsl-kartu .hsl-avatar {
    width: 64px;
    height: 64px;
    margin-bottom: .6rem;
    font-size: 1.6rem;
    border: 3px solid #fff;
    box-shadow: 0 0 0 4px #ede9fe;
  }

  .hsl-kartu h3 {
    margin: 0 0 .7rem;
    font-size: 1.1rem;
    word-break: break-word;
  }

  .hsl-angka {
    display: flex;
    gap: .5rem;
    width: 100%;
    margin-bottom: .8rem;
  }

  .hsl-angka div {
    flex: 1;
    padding: .5rem .3rem;
    background: #f9fafb;
    border-radius: 12px;
  }

  .hsl-angka b {
    display: block;
    font-size: 1.15rem;
  }

  .hsl-angka span {
    font-size: .75rem;
    color: var(--hsl-redup);
  }

  .hsl-akhir {
    margin: 0 0 .8rem;
    font-size: .8rem;
    color: var(--hsl-redup);
  }

  /* Halaman */
  .hsl-halaman {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: .35rem;
    flex-wrap: wrap;
    margin: 1.3rem 0 0;
  }

  .hsl-halaman a,
  .hsl-halaman span {
    display: grid;
    place-items: center;
    min-width: 38px;
    height: 38px;
    padding: 0 .6rem;
    font-size: .9rem;
    font-weight: 800;
    color: var(--hsl-teks);
    text-decoration: none;
    background: #fff;
    border: 2px solid var(--hsl-garis);
    border-radius: 12px;
    transition: transform .15s, border-color .15s;
  }

  .hsl-halaman a:hover {
    transform: translateY(-2px);
    border-color: var(--hsl-ungu);
  }

  .hsl-halaman .sekarang {
    color: #fff;
    background: var(--hsl-ungu);
    border-color: var(--hsl-ungu);
  }

  .hsl-halaman .titik {
    border: 0;
    background: transparent;
    color: var(--hsl-redup);
  }

  .hsl-info {
    margin: .8rem 0 0;
    text-align: center;
    font-size: .85rem;
    color: var(--hsl-redup);
  }

  .hsl-kosong {
    padding: 2.6rem 1rem;
    text-align: center;
    color: var(--hsl-redup);
    background: #fff;
    border: 2px dashed var(--hsl-garis);
    border-radius: var(--hsl-radius);
  }

  .hsl-kosong .emoji {
    display: block;
    margin-bottom: .4rem;
    font-size: 2.6rem;
  }

  @media (prefers-reduced-motion: reduce) {

    .hsl *,
    .hsl *::before,
    .hsl *::after {
      animation: none !important;
      transition: none !important;
    }
  }
</style>

<div class="hsl">

  <header class="hsl-kepala">
    <div>
      <h1>🏆 Hasil Siswa</h1>
      <p>Pantau perkembangan belajar anak-anak.</p>
    </div>
    <div class="emoji" aria-hidden="true">📊</div>
  </header>

  <nav class="hsl-tab" aria-label="Pilih tampilan">
    <a href="hasil.php" class="<?= $tab === 'riwayat' ? 'aktif' : '' ?>" <?= $tab === 'riwayat' ? ' aria-current="page"' : '' ?>>📜 Riwayat kuis</a>
    <a href="hasil.php?tab=siswa" class="<?= $tab === 'siswa' ? 'aktif' : '' ?>" <?= $tab === 'siswa' ? ' aria-current="page"' : '' ?>>🧒 Daftar siswa</a>
  </nav>

  <?php if ($tab === 'riwayat'): ?>

    <!-- Ringkasan sesuai filter -->
    <section class="hsl-stat" aria-label="Ringkasan">
      <div class="hsl-kotak k1" style="--i:0">
        <div class="ik">📝</div>
        <div><b><?= number_format($ringkasan['n'], 0, ',', '.') ?></b><span>Kuis selesai</span></div>
      </div>
      <div class="hsl-kotak k2" style="--i:1">
        <div class="ik">🧒</div>
        <div><b><?= number_format($ringkasan['siswa'], 0, ',', '.') ?></b><span>Siswa</span></div>
      </div>
      <div class="hsl-kotak k3" style="--i:2">
        <div class="ik">🎯</div>
        <div><b><?= $akurasi ?>%</b><span>Rata-rata benar</span></div>
      </div>
      <div class="hsl-kotak k4" style="--i:3">
        <div class="ik">⭐</div>
        <div><b><?= number_format($ringkasan['bintang'], 0, ',', '.') ?></b><span>Bintang diraih</span></div>
      </div>
    </section>

    <!-- Filter -->
    <form method="get" class="hsl-filter filter">
      <label>Nama
        <input type="search" name="nama" value="<?= e($cariNama) ?>" maxlength="30" placeholder="🔍 cari nama">
      </label>
      <label>Mapel
        <select name="mapel_id">
          <option value="0">Semua mapel</option>
          <?php foreach ($mapelList as $mp): ?>
            <option value="<?= (int) $mp['id'] ?>" <?= $mapelId === (int) $mp['id'] ? 'selected' : '' ?>>
              <?= e($mp['emoji'] . ' ' . $mp['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="hsl-btn tombol" type="submit">🔍 Cari</button>
      <?php if ($adaFilter): ?><a class="hsl-btn abu tombol abu" href="hasil.php">✖ Reset</a><?php endif; ?>
      <span class="ruang"></span>
      <a class="hsl-btn hijau tombol hijau" href="hasil.php?unduh=1<?= $qs ? '&' . e($qs) : '' ?>">⬇ Unduh CSV</a>
    </form>

    <!-- Tabel -->
    <?php if ($riwayat): ?>
      <div class="hsl-wadah gulir">
        <table class="hsl-tabel">
          <thead>
            <tr>
              <th>Waktu</th>
              <th>Nama</th>
              <th>Mapel</th>
              <th>Materi</th>
              <th>Benar</th>
              <th>Bintang</th>
              <th>Skor</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($riwayat as $r):
              $benar = (int) $r['benar'];
              $total = (int) $r['total'];
              $p     = $persen($benar, $total);
            ?>
              <tr>
                <td class="hsl-waktu nowrap"><?= e(tgl_indo($r['waktu'])) ?></td>
                <td>
                  <div class="hsl-siswa">
                    <span class="hsl-avatar" aria-hidden="true"><?= e($inisial((string) $r['nama'])) ?></span>
                    <?= e($r['nama']) ?>
                  </div>
                </td>
                <td><span class="hsl-mapel"><?= e($r['mapel_emoji'] . ' ' . $r['mapel']) ?></span></td>
                <td><?= e($r['judul']) ?></td>
                <td>
                  <div class="hsl-skor">
                    <div class="hsl-bar"><i class="<?= $kelasNilai($p) ?>" style="width:<?= $p ?>%"></i></div>
                    <small><?= $benar ?> / <?= $total ?></small>
                  </div>
                </td>
                <td><?= bintang_html((int) $r['bintang']) ?></td>
                <td class="hsl-poin"><?= (int) $r['skor'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($jmlHal > 1): ?>
        <nav class="hsl-halaman halaman" aria-label="Halaman">
          <?php if ($hal > 1): ?><a href="<?= e($linkHal($hal - 1)) ?>" aria-label="Sebelumnya">‹</a><?php endif; ?>
          <?php foreach ($nomorHal($hal, $jmlHal) as $p): ?>
            <?php if ($p === '…'): ?>
              <span class="titik">…</span>
            <?php elseif ($p === $hal): ?>
              <span class="sekarang" aria-current="page"><?= $p ?></span>
            <?php else: ?>
              <a href="<?= e($linkHal($p)) ?>"><?= $p ?></a>
            <?php endif; ?>
          <?php endforeach; ?>
          <?php if ($hal < $jmlHal): ?><a href="<?= e($linkHal($hal + 1)) ?>" aria-label="Berikutnya">›</a><?php endif; ?>
        </nav>
        <p class="hsl-info">Halaman <?= $hal ?> dari <?= $jmlHal ?> · <?= number_format($jmlBaris, 0, ',', '.') ?> hasil</p>
      <?php endif; ?>

    <?php else: ?>
      <div class="hsl-kosong">
        <span class="emoji"><?= $adaFilter ? '🔎' : '🌱' ?></span>
        <?= $adaFilter ? 'Belum ada hasil yang cocok dengan pencarian.' : 'Belum ada anak yang menyelesaikan kuis.' ?>
      </div>
    <?php endif; ?>

  <?php else: ?>

    <p class="hsl-catatan bantuan">🔒 Hanya nama panggilan yang disimpan. Anda bisa menghapus data seorang anak kapan saja.</p>

    <?php if ($siswaList): ?>
      <div class="hsl-daftar">
        <?php foreach ($siswaList as $i => $s): ?>
          <article class="hsl-kartu" style="--i:<?= min($i, 12) ?>">
            <div class="hsl-avatar" aria-hidden="true"><?= e($inisial((string) $s['nama'])) ?></div>
            <h3><?= e($s['nama']) ?></h3>

            <div class="hsl-angka">
              <div><b><?= (int) $s['jml_kuis'] ?></b><span>Kuis selesai</span></div>
              <div><b>⭐ <?= (int) $s['bintang'] ?></b><span>Total bintang</span></div>
            </div>

            <p class="hsl-akhir">🕒 Terakhir aktif: <?= e(tgl_indo($s['terakhir_aktif'])) ?></p>

            <?= form_hapus(
              'hapus_siswa',
              (int) $s['id'],
              'Hapus siswa "' . $s['nama'] . '" beserta semua hasilnya?'
            ) ?>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="hsl-kosong">
        <span class="emoji">🌱</span>
        Belum ada siswa.
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<?php admin_kaki(); ?>