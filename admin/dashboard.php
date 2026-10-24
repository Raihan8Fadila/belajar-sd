<?php

declare(strict_types=1);

/**
 * DASHBOARD ADMIN
 * Ringkasan angka, item yang perlu dicek, dan hasil kuis terbaru.
 */

define('AREA_ADMIN', true);
require_once __DIR__ . '/../includes/fungsi.php';
require_once __DIR__ . '/../includes/admin_layout.php';

$admin = wajib_admin();

/* ---------------------------------------------------------------
 * DATA
 * ------------------------------------------------------------- */

/** Ambil satu angka (COUNT, dll.) dari query. */
$hitung = static fn(string $sql): int => (int) db()->query($sql)->fetchColumn();

/** Kartu statistik: label => [query, ikon, warna, tautan (opsional)] */
$definisiStatistik = [
  'Siswa'        => ['SELECT COUNT(*) FROM siswa',  '🧒', 'biru',   null],
  'Kuis selesai' => ['SELECT COUNT(*) FROM hasil',  '🏆', 'kuning', 'hasil.php'],
  'Mapel'        => ['SELECT COUNT(*) FROM mapel',  '📚', 'hijau',  null],
  'Materi'       => ['SELECT COUNT(*) FROM materi', '📖', 'ungu',   'materi.php'],
  'Soal'         => ['SELECT COUNT(*) FROM soal',   '❓', 'merah',  'soal.php'],
];

$statistik = [];
foreach ($definisiStatistik as $label => [$sql, $ikon, $warna, $tautan]) {
  $statistik[] = [
    'label'  => $label,
    'jumlah' => $hitung($sql),
    'ikon'   => $ikon,
    'warna'  => $warna,
    'tautan' => $tautan,
  ];
}

$cekMateri = db()->query(
  'SELECT m.id, m.judul, mp.nama AS mapel
       FROM materi m
       JOIN mapel mp ON mp.id = m.mapel_id
      WHERE m.perlu_dicek = 1
      ORDER BY mp.urutan, m.urutan'
)->fetchAll();

$cekSoal = $hitung('SELECT COUNT(*) FROM soal WHERE perlu_dicek = 1');

$terbaru = db()->query(
  'SELECT h.*, s.nama, m.judul
       FROM hasil h
       JOIN siswa s ON s.id = h.siswa_id
       JOIN materi m ON m.id = h.materi_id
      ORDER BY h.waktu DESC, h.id DESC
      LIMIT 8'
)->fetchAll();

$perluCek = $cekMateri || $cekSoal > 0;

/* ---------------------------------------------------------------
 * HELPER TAMPILAN
 * ------------------------------------------------------------- */

/** Huruf pertama nama untuk avatar. */
$inisial = static fn(string $nama): string =>
mb_strtoupper(mb_substr(trim($nama) !== '' ? trim($nama) : '?', 0, 1));

/** Persentase jawaban benar (0–100), aman dari pembagian nol. */
$persen = static fn(int $benar, int $total): int =>
$total > 0 ? (int) round($benar / $total * 100) : 0;

/** Kelas warna progress berdasarkan persentase. */
$kelasNilai = static fn(int $p): string =>
$p >= 80 ? 'tinggi' : ($p >= 50 ? 'sedang' : 'rendah');

admin_kepala('Dashboard', 'dashboard');
?>

<style>
  .dsb {
    --dsb-biru: #3b82f6;
    --dsb-biru-bg: #eff6ff;
    --dsb-kuning: #f59e0b;
    --dsb-kuning-bg: #fffbeb;
    --dsb-hijau: #10b981;
    --dsb-hijau-bg: #ecfdf5;
    --dsb-ungu: #8b5cf6;
    --dsb-ungu-bg: #f5f3ff;
    --dsb-merah: #ef4444;
    --dsb-merah-bg: #fef2f2;
    --dsb-teks: #1f2937;
    --dsb-redup: #6b7280;
    --dsb-garis: #e5e7eb;
    --dsb-latar: #ffffff;
    --dsb-radius: 16px;
    color: var(--dsb-teks);
  }

  .dsb * {
    box-sizing: border-box;
  }

  /* Sapaan */
  .dsb-sapaan {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1.4rem 1.6rem;
    margin-bottom: 1.4rem;
    border-radius: var(--dsb-radius);
    color: #fff;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
    box-shadow: 0 10px 30px -10px rgba(99, 102, 241, .6);
  }

  .dsb-sapaan h1 {
    margin: 0 0 .25rem;
    font-size: 1.5rem;
  }

  .dsb-sapaan p {
    margin: 0;
    opacity: .9;
  }

  .dsb-sapaan .dsb-emoji {
    font-size: 3rem;
    line-height: 1;
  }

  /* Kartu statistik */
  .dsb-statistik {
    display: grid;
    gap: 1rem;
    margin-bottom: 1.6rem;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  }

  .dsb-stat {
    display: flex;
    align-items: center;
    gap: .9rem;
    padding: 1.1rem 1.2rem;
    background: var(--dsb-latar);
    border: 1px solid var(--dsb-garis);
    border-radius: var(--dsb-radius);
    text-decoration: none;
    color: inherit;
    transition: transform .18s ease, box-shadow .18s ease;
  }

  a.dsb-stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 24px -12px rgba(0, 0, 0, .25);
  }

  .dsb-stat .ikon {
    flex: none;
    width: 52px;
    height: 52px;
    display: grid;
    place-items: center;
    font-size: 1.6rem;
    border-radius: 14px;
  }

  .dsb-stat b {
    display: block;
    font-size: 1.8rem;
    line-height: 1.1;
  }

  .dsb-stat span {
    color: var(--dsb-redup);
    font-size: .9rem;
  }

  .dsb-stat.biru .ikon {
    background: var(--dsb-biru-bg);
  }

  .dsb-stat.kuning .ikon {
    background: var(--dsb-kuning-bg);
  }

  .dsb-stat.hijau .ikon {
    background: var(--dsb-hijau-bg);
  }

  .dsb-stat.ungu .ikon {
    background: var(--dsb-ungu-bg);
  }

  .dsb-stat.merah .ikon {
    background: var(--dsb-merah-bg);
  }

  /* Panel umum */
  .dsb-panel {
    background: var(--dsb-latar);
    border: 1px solid var(--dsb-garis);
    border-radius: var(--dsb-radius);
    padding: 1.3rem 1.4rem;
    margin-bottom: 1.6rem;
  }

  .dsb-panel h2 {
    margin: 0 0 .8rem;
    font-size: 1.15rem;
  }

  /* Peringatan */
  .dsb-peringatan {
    background: var(--dsb-kuning-bg);
    border-color: #fcd34d;
  }

  .dsb-peringatan p {
    margin: 0 0 .8rem;
    color: #78350f;
  }

  .dsb-daftar {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: .5rem;
  }

  .dsb-daftar li {
    display: flex;
    align-items: center;
    gap: .6rem;
    flex-wrap: wrap;
    padding: .55rem .8rem;
    background: #fff;
    border-radius: 10px;
    border: 1px solid #fde68a;
  }

  .dsb-daftar a {
    font-weight: 600;
    color: var(--dsb-teks);
    text-decoration: none;
  }

  .dsb-daftar a:hover {
    text-decoration: underline;
  }

  .dsb-lencana {
    display: inline-block;
    padding: .15rem .6rem;
    font-size: .75rem;
    font-weight: 600;
    border-radius: 999px;
    background: var(--dsb-ungu-bg);
    color: var(--dsb-ungu);
  }

  .dsb-tautan-soal {
    display: inline-block;
    margin-top: .8rem;
    font-weight: 600;
    text-decoration: none;
    color: #b45309;
  }

  /* Tabel hasil */
  .dsb-judul-bagian {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 0 0 .8rem;
  }

  .dsb-judul-bagian h2 {
    margin: 0;
    font-size: 1.15rem;
  }

  .dsb-judul-bagian a {
    font-weight: 600;
    text-decoration: none;
    color: var(--dsb-ungu);
  }

  .dsb-tabel {
    width: 100%;
    border-collapse: collapse;
  }

  .dsb-tabel th {
    text-align: left;
    padding: .7rem 1rem;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--dsb-redup);
    background: #f9fafb;
    border-bottom: 1px solid var(--dsb-garis);
  }

  .dsb-tabel td {
    padding: .75rem 1rem;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
  }

  .dsb-tabel tr:last-child td {
    border-bottom: 0;
  }

  .dsb-tabel tbody tr:hover {
    background: #fafafa;
  }

  .dsb-wadah {
    overflow-x: auto;
    background: var(--dsb-latar);
    border: 1px solid var(--dsb-garis);
    border-radius: var(--dsb-radius);
  }

  .dsb-nowrap {
    white-space: nowrap;
    color: var(--dsb-redup);
    font-size: .9rem;
  }

  .dsb-siswa {
    display: flex;
    align-items: center;
    gap: .6rem;
    font-weight: 600;
  }

  .dsb-avatar {
    flex: none;
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    color: #fff;
    font-size: .9rem;
    background: linear-gradient(135deg, #6366f1, #ec4899);
  }

  .dsb-skor {
    display: flex;
    align-items: center;
    gap: .6rem;
    min-width: 150px;
  }

  .dsb-bar {
    flex: 1;
    height: 8px;
    border-radius: 999px;
    background: #e5e7eb;
    overflow: hidden;
  }

  .dsb-bar i {
    display: block;
    height: 100%;
    border-radius: 999px;
  }

  .dsb-bar i.tinggi {
    background: var(--dsb-hijau);
  }

  .dsb-bar i.sedang {
    background: var(--dsb-kuning);
  }

  .dsb-bar i.rendah {
    background: var(--dsb-merah);
  }

  .dsb-skor small {
    color: var(--dsb-redup);
    white-space: nowrap;
  }

  .dsb-kosong {
    text-align: center;
    padding: 2.2rem 1rem;
    color: var(--dsb-redup);
  }

  .dsb-kosong .emoji {
    display: block;
    margin-bottom: .4rem;
    font-size: 2.4rem;
  }

  @media (max-width: 600px) {
    .dsb-sapaan .dsb-emoji {
      display: none;
    }

    .dsb-stat b {
      font-size: 1.5rem;
    }
  }
</style>

<div class="dsb">

  <!-- Sapaan -->
  <section class="dsb-sapaan">
    <div>
      <h1>Selamat datang kembali! 👋</h1>
      <p>Berikut ringkasan aktivitas belajar hari ini.</p>
    </div>
    <div class="dsb-emoji">🎓</div>
  </section>

  <!-- Statistik -->
  <section class="dsb-statistik">
    <?php foreach ($statistik as $s):
      $tag   = $s['tautan'] ? 'a' : 'div';
      $href  = $s['tautan'] ? ' href="' . e($s['tautan']) . '"' : '';
    ?>
      <<?= $tag ?> class="dsb-stat <?= e($s['warna']) ?>" <?= $href ?>>
        <div class="ikon"><?= $s['ikon'] ?></div>
        <div>
          <b><?= number_format($s['jumlah'], 0, ',', '.') ?></b>
          <span><?= e($s['label']) ?></span>
        </div>
      </<?= $tag ?>>
    <?php endforeach; ?>
  </section>

  <!-- Perlu dicek -->
  <?php if ($perluCek): ?>
    <section class="dsb-panel dsb-peringatan">
      <h2>⚠️ Perlu Anda cek</h2>
      <p>
        Materi dan soal berikut ditandai <em>perlu dicek</em> (terutama Pendidikan Agama,
        agar sesuai agama dan kurikulum yang Anda pakai). Hilangkan centang
        "perlu dicek" setelah Anda memeriksanya.
      </p>

      <?php if ($cekMateri): ?>
        <ul class="dsb-daftar">
          <?php foreach ($cekMateri as $m): ?>
            <li>
              <span class="dsb-lencana"><?= e($m['mapel']) ?></span>
              <a href="materi.php?edit=<?= (int) $m['id'] ?>"><?= e($m['judul']) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if ($cekSoal > 0): ?>
        <a class="dsb-tautan-soal" href="soal.php">
          📝 <?= number_format($cekSoal, 0, ',', '.') ?> soal juga ditandai perlu dicek →
        </a>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <!-- Hasil terbaru -->
  <div class="dsb-judul-bagian">
    <h2>🏅 Hasil terbaru</h2>
    <?php if ($terbaru): ?><a href="hasil.php">Lihat semua →</a><?php endif; ?>
  </div>

  <?php if (!$terbaru): ?>
    <div class="dsb-panel dsb-kosong">
      <span class="emoji">🌱</span>
      Belum ada anak yang menyelesaikan kuis.
    </div>
  <?php else: ?>
    <div class="dsb-wadah">
      <table class="dsb-tabel">
        <thead>
          <tr>
            <th>Waktu</th>
            <th>Nama</th>
            <th>Materi</th>
            <th>Skor</th>
            <th>Bintang</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($terbaru as $h):
            $benar = (int) $h['benar'];
            $total = (int) $h['total'];
            $p     = $persen($benar, $total);
          ?>
            <tr>
              <td class="dsb-nowrap"><?= e(tgl_indo($h['waktu'])) ?></td>
              <td>
                <div class="dsb-siswa">
                  <span class="dsb-avatar"><?= e($inisial((string) $h['nama'])) ?></span>
                  <?= e($h['nama']) ?>
                </div>
              </td>
              <td><?= e($h['judul']) ?></td>
              <td>
                <div class="dsb-skor">
                  <div class="dsb-bar"><i class="<?= $kelasNilai($p) ?>" style="width:<?= $p ?>%"></i></div>
                  <small><?= $benar ?> / <?= $total ?></small>
                </div>
              </td>
              <td><?= bintang_html((int) $h['bintang']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>

<?php admin_kaki(); ?>