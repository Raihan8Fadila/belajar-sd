<?php

declare(strict_types=1);

/**
 * DAFTAR SOAL per materi (tambah/ubah di soal_form.php).
 */

define('AREA_ADMIN', true);
require_once __DIR__ . '/../includes/fungsi.php';
require_once __DIR__ . '/../includes/admin_layout.php';

wajib_admin();

$materiId = (int) ($_GET['materi_id'] ?? 0);

/* ---------------------------------------------------------------
 * HAPUS SOAL
 * ------------------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_wajib();

  if (($_POST['aksi'] ?? '') === 'hapus') {
    $id     = (int) ($_POST['id'] ?? 0);
    $berkas = daftar_gambar_soal($id);

    db()->prepare('DELETE FROM soal WHERE id = ?')->execute([$id]);
    array_map('hapus_gambar', $berkas);

    flash_set('ok', 'Soal dihapus.');
  }

  redirect('soal.php' . ($materiId ? '?materi_id=' . $materiId : ''));
}

/* ---------------------------------------------------------------
 * DATA
 * ------------------------------------------------------------- */

// Pilihan materi dikelompokkan per mapel
$materiList = db()->query(
  'SELECT m.id, m.judul, m.emoji, mp.nama AS mapel, mp.emoji AS mapel_emoji
       FROM materi m
       JOIN mapel mp ON mp.id = m.mapel_id
      ORDER BY mp.urutan, mp.id, m.urutan, m.id'
)->fetchAll();

if (!$materiId && $materiList) {
  $materiId = (int) $materiList[0]['id'];
}

$materiNow    = null;
$materiGrup   = [];
foreach ($materiList as $m) {
  $materiGrup[$m['mapel_emoji'] . ' ' . $m['mapel']][] = $m;
  if ((int) $m['id'] === $materiId) {
    $materiNow = $m;
  }
}

$daftar = [];
if ($materiNow) {
  $st = db()->prepare(
    'SELECT s.*, (SELECT COUNT(*) FROM pilihan_jawaban p WHERE p.soal_id = s.id) AS jml_opsi
           FROM soal s
          WHERE s.materi_id = ?
          ORDER BY s.urutan, s.id'
  );
  $st->execute([$materiId]);
  $daftar = $st->fetchAll();
}

/** Jenis soal: kunci => [nama, ikon, kelas warna] */
$jenisSoal = [
  'pilihan'  => ['Pilihan ganda',  '🔘', 'biru'],
  'seret'    => ['Seret & lepas',  '🖐️', 'ungu'],
  'cocokkan' => ['Cocokkan',       '🔗', 'hijau'],
];

$jmlAktif = count(array_filter($daftar, static fn(array $s): bool => (bool) $s['aktif']));
$jmlCek   = count(array_filter($daftar, static fn(array $s): bool => (bool) $s['perlu_dicek']));
$perJenis = array_count_values(array_map(static fn(array $s): string => (string) $s['tipe'], $daftar));

admin_kepala('Kelola Soal', 'soal');
?>

<style>
  .sol {
    --sol-teks: #1f2937;
    --sol-redup: #6b7280;
    --sol-garis: #e5e7eb;
    --sol-ungu: #7c3aed;
    --sol-hijau: #10b981;
    --sol-kuning: #f59e0b;
    --sol-merah: #ef4444;
    --sol-radius: 18px;
    color: var(--sol-teks);
  }

  .sol * {
    box-sizing: border-box;
  }

  /* Kepala */
  .sol-kepala {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1.3rem 1.5rem;
    margin-bottom: 1.2rem;
    color: #fff;
    border-radius: var(--sol-radius);
    background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 50%, #8b5cf6 100%);
    box-shadow: 0 12px 28px -12px rgba(59, 130, 246, .65);
  }

  .sol-kepala h1 {
    margin: 0 0 .2rem;
    font-size: 1.45rem;
  }

  .sol-kepala p {
    margin: 0;
    opacity: .92;
    font-size: .95rem;
  }

  .sol-tambah {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .7rem 1.3rem;
    font-weight: 800;
    color: #1d4ed8;
    text-decoration: none;
    background: #fff;
    border-radius: 999px;
    box-shadow: 0 4px 0 rgba(0, 0, 0, .18);
    transition: transform .15s, box-shadow .15s;
  }

  .sol-tambah:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 0 rgba(0, 0, 0, .18);
  }

  /* Pemilih materi */
  .sol-pilih {
    display: flex;
    align-items: flex-end;
    gap: .9rem;
    flex-wrap: wrap;
    padding: 1rem 1.2rem;
    margin-bottom: 1.2rem;
    background: #fff;
    border: 1px solid var(--sol-garis);
    border-radius: var(--sol-radius);
  }

  .sol-pilih label {
    display: grid;
    flex: 1;
    min-width: 240px;
    gap: .35rem;
    font-size: .82rem;
    font-weight: 700;
    color: var(--sol-redup);
  }

  .sol-pilih select {
    width: 100%;
    padding: .75rem 1rem;
    font: inherit;
    font-size: 1rem;
    font-weight: 700;
    color: var(--sol-teks);
    background: #f9fafb;
    border: 2px solid var(--sol-garis);
    border-radius: 12px;
    outline: none;
    transition: border-color .2s, box-shadow .2s, background .2s;
  }

  .sol-pilih select:focus {
    background: #fff;
    border-color: var(--sol-ungu);
    box-shadow: 0 0 0 4px rgba(124, 58, 237, .15);
  }

  .sol-tautan {
    padding: .65rem 1rem;
    font-size: .88rem;
    font-weight: 700;
    color: #5b21b6;
    text-decoration: none;
    background: #ede9fe;
    border-radius: 999px;
    transition: background .15s, transform .15s;
  }

  .sol-tautan:hover {
    background: #ddd6fe;
    transform: translateY(-2px);
  }

  /* Ringkasan */
  .sol-ringkas {
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
    margin: 0 0 1.2rem;
  }

  .sol-ringkas span {
    padding: .3rem .85rem;
    font-size: .85rem;
    font-weight: 700;
    background: #f3f4f6;
    border-radius: 999px;
  }

  .sol-ringkas .cek {
    color: #92400e;
    background: #fef3c7;
  }

  /* Daftar soal */
  .sol-daftar {
    display: grid;
    gap: .9rem;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .sol-kartu {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.2rem;
    background: #fff;
    border: 1px solid var(--sol-garis);
    border-left: 6px solid #3b82f6;
    border-radius: var(--sol-radius);
    transition: transform .2s ease, box-shadow .2s ease;
    animation: sol-masuk .45s ease both;
    animation-delay: calc(var(--i, 0) * 45ms);
  }

  .sol-kartu:hover {
    transform: translateX(5px);
    box-shadow: 0 12px 26px -16px rgba(0, 0, 0, .4);
  }

  .sol-kartu.mati {
    opacity: .7;
    border-left-color: #9ca3af;
  }

  .sol-kartu.j-seret {
    border-left-color: #8b5cf6;
  }

  .sol-kartu.j-cocokkan {
    border-left-color: #10b981;
  }

  @keyframes sol-masuk {
    from {
      opacity: 0;
      transform: translateY(12px);
    }

    to {
      opacity: 1;
      transform: none;
    }
  }

  .sol-no {
    flex: none;
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    font-size: 1.05rem;
    font-weight: 800;
    color: #fff;
    border-radius: 14px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  }

  .sol-em {
    flex: none;
    font-size: 2rem;
    line-height: 1;
    transition: transform .3s;
  }

  .sol-kartu:hover .sol-em {
    transform: scale(1.2) rotate(-8deg);
  }

  .sol-isi {
    flex: 1;
    min-width: 0;
  }

  .sol-isi h3 {
    margin: 0 0 .4rem;
    font-size: 1.02rem;
    line-height: 1.4;
    word-break: break-word;
  }

  .sol-lencana {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
  }

  .sol-lencana span {
    padding: .15rem .65rem;
    font-size: .75rem;
    font-weight: 800;
    border-radius: 999px;
  }

  .sol-biru {
    color: #1e40af;
    background: #dbeafe;
  }

  .sol-ungu {
    color: #5b21b6;
    background: #ede9fe;
  }

  .sol-hijau {
    color: #065f46;
    background: #d1fae5;
  }

  .sol-abu {
    color: #4b5563;
    background: #e5e7eb;
  }

  .sol-kuning {
    color: #92400e;
    background: #fef3c7;
    animation: sol-denyut 2s ease-in-out infinite;
  }

  @keyframes sol-denyut {

    0%,
    100% {
      transform: scale(1);
    }

    50% {
      transform: scale(1.07);
    }
  }

  .sol-aksi {
    flex: none;
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
    justify-content: flex-end;
  }

  /* Tombol */
  .sol-btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .45rem .95rem;
    font: inherit;
    font-size: .86rem;
    font-weight: 800;
    color: #374151;
    text-decoration: none;
    cursor: pointer;
    border: 0;
    border-radius: 999px;
    background: #e5e7eb;
    box-shadow: 0 3px 0 #9ca3af;
    transition: transform .12s, box-shadow .12s;
  }

  .sol-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 0 #9ca3af;
  }

  .sol-btn:active {
    transform: translateY(2px);
    box-shadow: 0 1px 0 #9ca3af;
  }

  .sol-btn.utama {
    color: #fff;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    box-shadow: 0 4px 0 #4c1d95;
    padding: .7rem 1.4rem;
    font-size: .95rem;
  }

  .sol-btn.utama:hover {
    box-shadow: 0 6px 0 #4c1d95;
  }

  .sol-kosong {
    padding: 2.6rem 1rem;
    text-align: center;
    color: var(--sol-redup);
    background: #fff;
    border: 2px dashed var(--sol-garis);
    border-radius: var(--sol-radius);
  }

  .sol-kosong .emoji {
    display: block;
    margin-bottom: .5rem;
    font-size: 2.6rem;
    animation: sol-ayun 3s ease-in-out infinite;
  }

  .sol-kosong p {
    margin: 0 0 1rem;
  }

  @keyframes sol-ayun {

    0%,
    100% {
      transform: rotate(-8deg);
    }

    50% {
      transform: rotate(8deg) translateY(-4px);
    }
  }

  @media (max-width: 680px) {
    .sol-kartu {
      flex-wrap: wrap;
    }

    .sol-isi {
      flex-basis: calc(100% - 120px);
    }

    .sol-aksi {
      width: 100%;
      justify-content: flex-start;
    }
  }

  @media (prefers-reduced-motion: reduce) {

    .sol *,
    .sol *::before,
    .sol *::after {
      animation: none !important;
      transition: none !important;
    }
  }
</style>

<div class="sol">

  <header class="sol-kepala">
    <div>
      <h1>❓ Kelola Soal</h1>
      <p>Buat soal kuis untuk setiap materi.</p>
    </div>
    <?php if ($materiList): ?>
      <a class="sol-tambah" href="soal_form.php?materi_id=<?= $materiId ?>">➕ Tambah Soal</a>
    <?php endif; ?>
  </header>

  <?php if (!$materiList): ?>
    <div class="sol-kosong kartu">
      <span class="emoji">📖</span>
      <p>Buat mapel dan materi dulu sebelum menambah soal.</p>
      <a class="sol-btn utama" href="materi.php?baru=1">➕ Tambah materi</a>
    </div>
  <?php else: ?>

    <!-- Pemilih materi -->
    <form method="get" class="sol-pilih filter" id="formMateri">
      <label>Materi
        <select name="materi_id" data-auto-kirim id="pilihMateri">
          <?php foreach ($materiGrup as $namaMapel => $isi): ?>
            <optgroup label="<?= e($namaMapel) ?>">
              <?php foreach ($isi as $m): ?>
                <option value="<?= (int) $m['id'] ?>" <?= $materiId === (int) $m['id'] ? 'selected' : '' ?>>
                  <?= e($m['emoji'] . ' ' . $m['judul']) ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      </label>
      <noscript><button class="sol-btn" type="submit">Tampilkan</button></noscript>
      <?php if ($materiNow): ?>
        <a class="sol-tautan" href="materi.php?edit=<?= (int) $materiNow['id'] ?>">📖 Ubah materi ini</a>
      <?php endif; ?>
    </form>

    <?php if ($daftar): ?>

      <!-- Ringkasan -->
      <div class="sol-ringkas">
        <span>❓ <?= count($daftar) ?> soal</span>
        <span>👀 <?= $jmlAktif ?> tampil</span>
        <?php if (count($daftar) - $jmlAktif > 0): ?><span>🙈 <?= count($daftar) - $jmlAktif ?> disembunyikan</span><?php endif; ?>
        <?php foreach ($perJenis as $tipe => $n): ?>
          <span><?= e(($jenisSoal[$tipe][1] ?? '❔') . ' ' . ($jenisSoal[$tipe][0] ?? $tipe)) ?>: <?= $n ?></span>
        <?php endforeach; ?>
        <?php if ($jmlCek): ?><span class="cek">⚠️ <?= $jmlCek ?> perlu dicek</span><?php endif; ?>
      </div>

      <!-- Daftar soal -->
      <ol class="sol-daftar">
        <?php foreach ($daftar as $i => $s):
          $aktif = (bool) $s['aktif'];
          [$namaJenis, $ikonJenis, $warnaJenis] = $jenisSoal[$s['tipe']] ?? [(string) $s['tipe'], '❔', 'abu'];
        ?>
          <li class="sol-kartu j-<?= e((string) $s['tipe']) ?> <?= $aktif ? '' : 'mati' ?>" style="--i:<?= min($i, 15) ?>">
            <div class="sol-no"><?= $i + 1 ?></div>

            <?php if ($s['emoji']): ?><div class="sol-em" aria-hidden="true"><?= e($s['emoji']) ?></div><?php endif; ?>

            <div class="sol-isi">
              <h3><?= e($s['pertanyaan']) ?></h3>
              <div class="sol-lencana">
                <span class="sol-<?= e($warnaJenis) ?>"><?= $ikonJenis ?> <?= e($namaJenis) ?></span>
                <span class="sol-abu">🔢 <?= (int) $s['jml_opsi'] ?> pilihan</span>
                <span class="<?= $aktif ? 'sol-hijau' : 'sol-abu' ?>"><?= $aktif ? '● Tampil' : '○ Disembunyikan' ?></span>
                <?php if ($s['perlu_dicek']): ?><span class="sol-kuning">⚠ perlu dicek</span><?php endif; ?>
              </div>
            </div>

            <div class="sol-aksi aksi">
              <a class="sol-btn tombol kecil abu" href="soal_form.php?id=<?= (int) $s['id'] ?>">✏️ Ubah</a>
              <?= form_hapus('hapus', (int) $s['id'], 'Hapus soal ini beserta pilihan jawabannya?') ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>

    <?php else: ?>
      <div class="sol-kosong">
        <span class="emoji">🌱</span>
        <p>Belum ada soal untuk materi ini.</p>
        <a class="sol-btn utama" href="soal_form.php?materi_id=<?= $materiId ?>">➕ Tambah Soal Pertama</a>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<script>
  // Kirim otomatis saat materi diganti (cadangan bila admin.js tidak memproses data-auto-kirim)
  (function() {
    var pilih = document.getElementById('pilihMateri');
    if (pilih) pilih.addEventListener('change', function() {
      document.getElementById('formMateri').submit();
    });
  })();
</script>

<?php admin_kaki(); ?>