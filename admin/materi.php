<?php

declare(strict_types=1);

/**
 * KELOLA MATERI (level di dalam mapel).
 * Isi pelajaran ditulis per baris dengan format:  emoji | teks pendek
 * (satu baris = satu kartu belajar)
 */

define('AREA_ADMIN', true);
require_once __DIR__ . '/../includes/fungsi.php';
require_once __DIR__ . '/../includes/admin_layout.php';

wajib_admin();

const EMOJI_BAWAAN  = '⭐';
const WARNA_BAWAAN  = '#A9D2FF';
const ISI_MAKS      = 5000;

/* ---------------------------------------------------------------
 * DATA AWAL
 * ------------------------------------------------------------- */

$galat       = '';
$mapelList   = db()->query('SELECT id, nama, emoji, warna FROM mapel ORDER BY urutan, id')->fetchAll();
$filterMapel = (int) ($_GET['mapel_id'] ?? 0);
$qFilter     = $filterMapel ? '?mapel_id=' . $filterMapel : '';

$data = [
  'id'          => 0,
  'mapel_id'    => $filterMapel ?: (int) ($mapelList[0]['id'] ?? 0),
  'judul'       => '',
  'deskripsi'   => '',
  'emoji'       => EMOJI_BAWAAN,
  'isi'         => '',
  'gambar'      => null,
  'urutan'      => 0,
  'aktif'       => 1,
  'perlu_dicek' => 0,
];

/* ---------------------------------------------------------------
 * PROSES FORM (POST)
 * ------------------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_wajib();
  $aksi = (string) ($_POST['aksi'] ?? '');

  /* --- Hapus --- */
  if ($aksi === 'hapus') {
    $id     = (int) ($_POST['id'] ?? 0);
    $berkas = daftar_gambar_materi($id);

    db()->prepare('DELETE FROM materi WHERE id = ?')->execute([$id]);
    array_map('hapus_gambar', $berkas);

    flash_set('ok', 'Materi dihapus (beserta soal dan hasilnya).');
    redirect('materi.php' . $qFilter);
  }

  /* --- Simpan (tambah / ubah) --- */
  if ($aksi === 'simpan') {
    $data['id']          = (int) ($_POST['id'] ?? 0);
    $data['mapel_id']    = (int) ($_POST['mapel_id'] ?? 0);
    $data['judul']       = trim((string) ($_POST['judul'] ?? ''));
    $data['deskripsi']   = trim((string) ($_POST['deskripsi'] ?? ''));
    $data['emoji']       = trim((string) ($_POST['emoji'] ?? ''));
    $data['isi']         = trim((string) ($_POST['isi'] ?? ''));
    $data['urutan']      = (int) ($_POST['urutan'] ?? 0);
    $data['aktif']       = isset($_POST['aktif']) ? 1 : 0;
    $data['perlu_dicek'] = isset($_POST['perlu_dicek']) ? 1 : 0;

    // Validasi
    $st = db()->prepare('SELECT COUNT(*) FROM mapel WHERE id = ?');
    $st->execute([$data['mapel_id']]);

    if ((int) $st->fetchColumn() === 0) {
      $galat = 'Pilih mapel terlebih dulu.';
    } elseif ($data['judul'] === '' || mb_strlen($data['judul']) > 150) {
      $galat = 'Judul materi wajib diisi (maksimal 150 karakter).';
    } elseif (mb_strlen($data['deskripsi']) > 255) {
      $galat = 'Deskripsi maksimal 255 karakter.';
    } elseif (mb_strlen($data['emoji']) > 20) {
      $galat = 'Ikon emoji terlalu panjang.';
    } elseif (mb_strlen($data['isi']) > ISI_MAKS) {
      $galat = 'Isi materi terlalu panjang. Pecah jadi beberapa kartu pendek.';
    }

    // Data lama (mode ubah)
    $lama = null;
    if ($data['id'] > 0) {
      $st = db()->prepare('SELECT * FROM materi WHERE id = ?');
      $st->execute([$data['id']]);
      $lama = $st->fetch();

      if (!$lama) {
        redirect('materi.php');
      }
      $data['gambar'] = $lama['gambar'];
    }

    // Unggah gambar baru (jika ada)
    $baru = null;
    if ($galat === '') {
      try {
        $baru = upload_gambar('gambar', 'materi');
      } catch (RuntimeException $ex) {
        $galat = $ex->getMessage();
      }
    }

    // Simpan ke database
    if ($galat === '') {
      $gambar = $lama['gambar'] ?? null;

      if ($baru) {
        hapus_gambar($gambar);
        $gambar = $baru;
      } elseif (!empty($_POST['hapus_gambar'])) {
        hapus_gambar($gambar);
        $gambar = null;
      }

      $emoji = $data['emoji'] ?: EMOJI_BAWAAN;

      if ($data['id'] > 0) {
        db()->prepare(
          'UPDATE materi
                        SET mapel_id = ?, judul = ?, deskripsi = ?, emoji = ?, isi = ?,
                            gambar = ?, urutan = ?, aktif = ?, perlu_dicek = ?
                      WHERE id = ?'
        )->execute([
          $data['mapel_id'],
          $data['judul'],
          $data['deskripsi'],
          $emoji,
          $data['isi'],
          $gambar,
          $data['urutan'],
          $data['aktif'],
          $data['perlu_dicek'],
          $data['id'],
        ]);
      } else {
        db()->prepare(
          'INSERT INTO materi (mapel_id, judul, deskripsi, emoji, isi, gambar, urutan, aktif, perlu_dicek)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
          $data['mapel_id'],
          $data['judul'],
          $data['deskripsi'],
          $emoji,
          $data['isi'],
          $gambar,
          $data['urutan'],
          $data['aktif'],
          $data['perlu_dicek'],
        ]);
      }

      flash_set('ok', 'Materi disimpan.');
      redirect('materi.php?mapel_id=' . $data['mapel_id']);
    }
  }
}

/* ---------------------------------------------------------------
 * MODE FORM: ?baru=1, ?edit=ID, atau form dengan galat
 * ------------------------------------------------------------- */

$tampilForm = isset($_GET['baru']) || isset($_GET['edit']) || $galat !== '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
  $st = db()->prepare('SELECT * FROM materi WHERE id = ?');
  $st->execute([(int) $_GET['edit']]);
  $ada = $st->fetch();

  if (!$ada) {
    redirect('materi.php');
  }
  $data = $ada;
}

/* ---------------------------------------------------------------
 * DATA DAFTAR
 * ------------------------------------------------------------- */

$sql = 'SELECT m.*, mp.nama AS mapel_nama, mp.emoji AS mapel_emoji, mp.warna AS mapel_warna,
               (SELECT COUNT(*) FROM soal s WHERE s.materi_id = m.id) AS jml_soal
          FROM materi m
          JOIN mapel mp ON mp.id = m.mapel_id';
$param = [];

if ($filterMapel) {
  $sql    .= ' WHERE m.mapel_id = ?';
  $param[] = $filterMapel;
}
$sql .= ' ORDER BY mp.urutan, mp.id, m.urutan, m.id';

$st = db()->prepare($sql);
$st->execute($param);
$daftar = $st->fetchAll();

// Kelompokkan per mapel
$kelompok = [];
foreach ($daftar as $m) {
  $kelompok[(int) $m['mapel_id']]['info'] = [
    'nama'  => $m['mapel_nama'],
    'emoji' => $m['mapel_emoji'],
    'warna' => warna_valid((string) $m['mapel_warna']) ? (string) $m['mapel_warna'] : WARNA_BAWAAN,
  ];
  $kelompok[(int) $m['mapel_id']]['materi'][] = $m;
}

/** Hitung jumlah kartu belajar (baris tidak kosong) pada isi materi. */
$jmlKartu = static fn(?string $isi): int => count(array_filter(
  preg_split('/\R/u', (string) $isi) ?: [],
  static fn(string $baris): bool => trim($baris) !== ''
));

$jmlAktif = count(array_filter($daftar, static fn(array $m): bool => (bool) $m['aktif']));
$jmlCek   = count(array_filter($daftar, static fn(array $m): bool => (bool) $m['perlu_dicek']));

/** Emoji pilihan cepat untuk form. */
$emojiPilihan = ['⭐', '🍎', '🔢', '🔤', '📖', '🌍', '🔬', '🎨', '🎵', '🐘', '🌈', '🧮', '✏️', '🕌', '🌱', '🧠'];

admin_kepala('Kelola Materi', 'materi');
?>

<style>
  .mtr {
    --mtr-teks: #1f2937;
    --mtr-redup: #6b7280;
    --mtr-garis: #e5e7eb;
    --mtr-ungu: #7c3aed;
    --mtr-hijau: #10b981;
    --mtr-merah: #ef4444;
    --mtr-kuning: #f59e0b;
    --mtr-radius: 18px;
    color: var(--mtr-teks);
  }

  .mtr * {
    box-sizing: border-box;
  }

  /* Kepala halaman */
  .mtr-kepala {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1.3rem 1.5rem;
    margin-bottom: 1.2rem;
    color: #fff;
    border-radius: var(--mtr-radius);
    background: linear-gradient(135deg, #f97316 0%, #ec4899 55%, #8b5cf6 100%);
    box-shadow: 0 12px 28px -12px rgba(236, 72, 153, .65);
  }

  .mtr-kepala h1 {
    margin: 0 0 .2rem;
    font-size: 1.45rem;
  }

  .mtr-kepala p {
    margin: 0;
    opacity: .92;
    font-size: .95rem;
  }

  .mtr-tambah {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .7rem 1.3rem;
    font-weight: 800;
    color: #be185d;
    text-decoration: none;
    background: #fff;
    border-radius: 999px;
    box-shadow: 0 4px 0 rgba(0, 0, 0, .18);
    transition: transform .15s, box-shadow .15s;
  }

  .mtr-tambah:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 0 rgba(0, 0, 0, .18);
  }

  /* Filter mapel */
  .mtr-filter {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-bottom: 1.2rem;
  }

  .mtr-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .45rem .95rem;
    font-size: .9rem;
    font-weight: 700;
    color: var(--mtr-teks);
    text-decoration: none;
    background: #fff;
    border: 2px solid var(--mtr-garis);
    border-radius: 999px;
    transition: transform .15s, border-color .15s, background .15s;
  }

  .mtr-chip:hover {
    transform: translateY(-2px);
    border-color: var(--mtr-ungu);
  }

  .mtr-chip.aktif {
    color: #fff;
    background: var(--mtr-ungu);
    border-color: var(--mtr-ungu);
  }

  .mtr-ringkas {
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
    margin: 0 0 1.2rem;
  }

  .mtr-ringkas span {
    padding: .3rem .85rem;
    font-size: .85rem;
    font-weight: 700;
    background: #f3f4f6;
    border-radius: 999px;
  }

  /* Form */
  .mtr-form {
    display: grid;
    gap: 1.2rem;
    padding: 1.5rem;
    margin-bottom: 1.8rem;
    background: #fff;
    border: 1px solid var(--mtr-garis);
    border-radius: var(--mtr-radius);
    box-shadow: 0 10px 30px -18px rgba(0, 0, 0, .35);
    animation: mtr-masuk .35s ease both;
  }

  @keyframes mtr-masuk {
    from {
      opacity: 0;
      transform: translateY(-12px);
    }

    to {
      opacity: 1;
      transform: none;
    }
  }

  .mtr-form>h2 {
    margin: 0;
    font-size: 1.2rem;
  }

  .mtr-galat {
    margin: 0;
    padding: .7rem 1rem;
    font-weight: 600;
    color: #991b1b;
    background: #fee2e2;
    border-left: 5px solid var(--mtr-merah);
    border-radius: 10px;
  }

  .mtr-grid {
    display: grid;
    gap: 1.4rem;
    grid-template-columns: 1fr 300px;
    align-items: start;
  }

  .mtr-kolom {
    display: grid;
    gap: 1rem;
    min-width: 0;
  }

  .mtr-baris {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
  }

  .mtr-f {
    display: grid;
    gap: .35rem;
    font-weight: 700;
    font-size: .92rem;
  }

  .mtr-f small {
    font-weight: 400;
    color: var(--mtr-redup);
    line-height: 1.5;
  }

  .mtr-f code {
    padding: .1rem .4rem;
    font-size: .85em;
    color: #be185d;
    background: #fdf2f8;
    border-radius: 6px;
  }

  .mtr-f input[type=text],
  .mtr-f input[type=number],
  .mtr-f select,
  .mtr-f textarea {
    width: 100%;
    padding: .7rem .9rem;
    font: inherit;
    font-weight: 400;
    color: var(--mtr-teks);
    background: #f9fafb;
    border: 2px solid var(--mtr-garis);
    border-radius: 12px;
    outline: none;
    transition: border-color .2s, box-shadow .2s, background .2s;
  }

  .mtr-f textarea {
    min-height: 170px;
    line-height: 1.6;
    resize: vertical;
  }

  .mtr-f input:focus,
  .mtr-f select:focus,
  .mtr-f textarea:focus {
    background: #fff;
    border-color: var(--mtr-ungu);
    box-shadow: 0 0 0 4px rgba(124, 58, 237, .15);
  }

  .mtr-f input[type=file] {
    padding: .6rem;
    font: inherit;
    font-weight: 400;
    background: #f9fafb;
    border: 2px dashed #c4b5fd;
    border-radius: 12px;
    cursor: pointer;
  }

  .mtr-hitung {
    align-self: end;
    font-size: .8rem;
    font-weight: 600;
    color: var(--mtr-redup);
    text-align: right;
  }

  .mtr-hitung.lewat {
    color: var(--mtr-merah);
  }

  .mtr-emoji-pilih {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
  }

  .mtr-emoji-pilih button {
    width: 38px;
    height: 38px;
    font-size: 1.2rem;
    cursor: pointer;
    background: #f3f4f6;
    border: 2px solid transparent;
    border-radius: 10px;
    transition: transform .12s, border-color .12s;
  }

  .mtr-emoji-pilih button:hover {
    transform: scale(1.18) rotate(-6deg);
    border-color: var(--mtr-ungu);
  }

  /* Saklar */
  .mtr-saklar {
    display: flex;
    align-items: flex-start;
    gap: .7rem;
    font-weight: 700;
    cursor: pointer;
  }

  .mtr-saklar input {
    position: absolute;
    opacity: 0;
  }

  .mtr-saklar i {
    position: relative;
    flex: none;
    width: 50px;
    height: 28px;
    margin-top: 1px;
    background: #d1d5db;
    border-radius: 999px;
    transition: background .2s;
  }

  .mtr-saklar i::after {
    content: "";
    position: absolute;
    top: 3px;
    left: 3px;
    width: 22px;
    height: 22px;
    background: #fff;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0, 0, 0, .3);
    transition: transform .2s;
  }

  .mtr-saklar input:checked+i {
    background: var(--mtr-hijau);
  }

  .mtr-saklar.kuning input:checked+i {
    background: var(--mtr-kuning);
  }

  .mtr-saklar input:checked+i::after {
    transform: translateX(22px);
  }

  .mtr-saklar input:focus-visible+i {
    outline: 3px solid var(--mtr-ungu);
    outline-offset: 2px;
  }

  .mtr-saklar small {
    display: block;
    font-weight: 400;
    color: var(--mtr-redup);
  }

  .mtr-gambar-lama {
    display: flex;
    align-items: center;
    gap: .8rem;
    flex-wrap: wrap;
  }

  .mtr-gambar-lama img {
    max-height: 56px;
    border-radius: 12px;
    border: 2px solid var(--mtr-garis);
  }

  /* Pratinjau */
  .mtr-pratinjau {
    position: sticky;
    top: 1rem;
  }

  .mtr-pratinjau>span {
    display: block;
    margin-bottom: .5rem;
    font-size: .8rem;
    font-weight: 700;
    color: var(--mtr-redup);
    text-transform: uppercase;
    letter-spacing: .05em;
    text-align: center;
  }

  .mtr-pv-kartu {
    padding: 1.1rem;
    text-align: center;
    background: #fff7ed;
    border: 2px solid #fed7aa;
    border-radius: var(--mtr-radius);
  }

  .mtr-pv-kartu .em {
    font-size: 2.6rem;
    line-height: 1.1;
  }

  .mtr-pv-kartu h3 {
    margin: .3rem 0 .2rem;
    font-size: 1.05rem;
    word-break: break-word;
  }

  .mtr-pv-kartu p {
    margin: 0;
    font-size: .85rem;
    color: var(--mtr-redup);
    word-break: break-word;
  }

  .mtr-pv-kartu img {
    max-height: 70px;
    max-width: 100%;
    margin-top: .6rem;
    border-radius: 10px;
  }

  .mtr-pv-isi {
    display: grid;
    gap: .5rem;
    max-height: 330px;
    margin-top: .8rem;
    padding-right: .2rem;
    overflow-y: auto;
  }

  .mtr-pv-isi .k {
    display: flex;
    align-items: center;
    gap: .7rem;
    padding: .6rem .8rem;
    background: #fff;
    border: 2px solid var(--mtr-garis);
    border-radius: 14px;
    animation: mtr-masuk .25s ease both;
  }

  .mtr-pv-isi .k b {
    flex: none;
    font-size: 1.6rem;
    line-height: 1;
  }

  .mtr-pv-isi .k span {
    font-size: .88rem;
    word-break: break-word;
  }

  .mtr-pv-kosong {
    padding: .8rem;
    font-size: .85rem;
    text-align: center;
    color: var(--mtr-redup);
    background: #f9fafb;
    border: 2px dashed var(--mtr-garis);
    border-radius: 14px;
  }

  /* Tombol */
  .mtr-aksi-form {
    display: flex;
    gap: .7rem;
    flex-wrap: wrap;
  }

  .mtr-btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .7rem 1.4rem;
    font: inherit;
    font-weight: 800;
    color: #fff;
    text-decoration: none;
    cursor: pointer;
    border: 0;
    border-radius: 999px;
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
    box-shadow: 0 4px 0 #6d28d9;
    transition: transform .12s, box-shadow .12s;
  }

  .mtr-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #6d28d9;
  }

  .mtr-btn:active {
    transform: translateY(3px);
    box-shadow: 0 1px 0 #6d28d9;
  }

  .mtr-btn.abu {
    color: #374151;
    background: #e5e7eb;
    box-shadow: 0 4px 0 #9ca3af;
  }

  .mtr-btn.kecil {
    padding: .45rem .95rem;
    font-size: .85rem;
  }

  /* Daftar materi per mapel */
  .mtr-grup {
    margin-bottom: 1.8rem;
  }

  .mtr-grup-judul {
    display: flex;
    align-items: center;
    gap: .6rem;
    margin: 0 0 .9rem;
    padding: .55rem .9rem;
    font-size: 1.05rem;
    border-radius: 14px;
    background: var(--w, #A9D2FF);
  }

  .mtr-grup-judul small {
    margin-left: auto;
    padding: .15rem .65rem;
    font-weight: 700;
    background: rgba(255, 255, 255, .7);
    border-radius: 999px;
  }

  .mtr-daftar {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
  }

  .mtr-kartu {
    --w: #A9D2FF;
    position: relative;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: #fff;
    border: 1px solid var(--mtr-garis);
    border-top: 6px solid var(--w);
    border-radius: var(--mtr-radius);
    transition: transform .2s ease, box-shadow .2s ease;
    animation: mtr-masuk .45s ease both;
    animation-delay: calc(var(--i, 0) * 50ms);
  }

  .mtr-kartu:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 30px -14px rgba(0, 0, 0, .35);
  }

  .mtr-kartu.mati {
    opacity: .72;
  }

  .mtr-isi {
    display: flex;
    gap: .9rem;
    flex: 1;
    padding: 1.1rem 1.1rem .5rem;
  }

  .mtr-em {
    flex: none;
    display: grid;
    place-items: center;
    width: 58px;
    height: 58px;
    font-size: 1.9rem;
    background: color-mix(in srgb, var(--w) 35%, #fff);
    border-radius: 16px;
    transition: transform .3s;
  }

  .mtr-kartu:hover .mtr-em {
    transform: scale(1.15) rotate(-8deg);
  }

  .mtr-teks {
    min-width: 0;
  }

  .mtr-teks h3 {
    margin: 0 0 .2rem;
    font-size: 1.05rem;
    word-break: break-word;
  }

  .mtr-teks p {
    margin: 0 0 .5rem;
    font-size: .88rem;
    color: var(--mtr-redup);
    word-break: break-word;
  }

  .mtr-lencana {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
  }

  .mtr-lencana span,
  .mtr-lencana a {
    padding: .15rem .65rem;
    font-size: .75rem;
    font-weight: 800;
    text-decoration: none;
    border-radius: 999px;
  }

  .mtr-l-hijau {
    color: #065f46;
    background: #d1fae5;
  }

  .mtr-l-abu {
    color: #4b5563;
    background: #e5e7eb;
  }

  .mtr-l-kuning {
    color: #92400e;
    background: #fef3c7;
    animation: mtr-denyut 2s ease-in-out infinite;
  }

  .mtr-l-ungu {
    color: #5b21b6;
    background: #ede9fe;
  }

  .mtr-l-biru {
    color: #1e40af;
    background: #dbeafe;
  }

  .mtr-lencana a:hover {
    filter: brightness(.95);
    transform: scale(1.05);
  }

  @keyframes mtr-denyut {

    0%,
    100% {
      transform: scale(1);
    }

    50% {
      transform: scale(1.07);
    }
  }

  .mtr-bawah {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
    padding: .7rem 1.1rem 1.1rem;
  }

  .mtr-kosong {
    padding: 2.5rem 1rem;
    text-align: center;
    color: var(--mtr-redup);
    background: #fff;
    border: 2px dashed var(--mtr-garis);
    border-radius: var(--mtr-radius);
  }

  .mtr-kosong .emoji {
    display: block;
    margin-bottom: .4rem;
    font-size: 2.6rem;
  }

  @media (max-width: 800px) {
    .mtr-grid {
      grid-template-columns: 1fr;
    }

    .mtr-pratinjau {
      position: static;
      order: -1;
    }
  }

  @media (prefers-reduced-motion: reduce) {

    .mtr *,
    .mtr *::before,
    .mtr *::after {
      animation: none !important;
      transition: none !important;
    }
  }
</style>

<div class="mtr">

  <!-- Kepala halaman -->
  <header class="mtr-kepala">
    <div>
      <h1>📖 Kelola Materi</h1>
      <p>Susun kartu belajar untuk setiap mata pelajaran.</p>
    </div>
    <?php if (!$tampilForm): ?>
      <a class="mtr-tambah" href="materi.php?baru=1<?= $filterMapel ? '&mapel_id=' . $filterMapel : '' ?>">➕ Tambah Materi</a>
    <?php endif; ?>
  </header>

  <!-- Form tambah / ubah -->
  <?php if ($tampilForm): ?>
    <form method="post" enctype="multipart/form-data" class="mtr-form" id="formMateri">
      <h2><?= $data['id'] ? '✏️ Ubah materi' : '✨ Tambah materi' ?></h2>

      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="simpan">
      <input type="hidden" name="id" value="<?= (int) $data['id'] ?>">

      <?php if ($galat !== ''): ?>
        <p class="mtr-galat a-flash a-flash-galat" role="alert">⚠️ <?= e($galat) ?></p>
      <?php endif; ?>

      <div class="mtr-grid">
        <div class="mtr-kolom">
          <div class="mtr-baris">
            <label class="mtr-f">Mapel
              <select name="mapel_id" required>
                <?php foreach ($mapelList as $mp): ?>
                  <option value="<?= (int) $mp['id'] ?>" <?= (int) $data['mapel_id'] === (int) $mp['id'] ? 'selected' : '' ?>>
                    <?= e($mp['emoji'] . ' ' . $mp['nama']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
            <label class="mtr-f">Judul materi
              <input type="text" name="judul" id="fJudul" value="<?= e($data['judul']) ?>" maxlength="150" required autofocus>
            </label>
          </div>

          <div class="mtr-baris">
            <label class="mtr-f">Deskripsi singkat
              <input type="text" name="deskripsi" id="fDesk" value="<?= e($data['deskripsi']) ?>" maxlength="255">
            </label>
            <label class="mtr-f">Ikon (emoji)
              <input type="text" name="emoji" id="fEmoji" value="<?= e($data['emoji']) ?>" maxlength="20">
            </label>
            <label class="mtr-f">Urutan
              <input type="number" name="urutan" value="<?= (int) $data['urutan'] ?>">
            </label>
          </div>

          <div class="mtr-emoji-pilih" aria-label="Pilihan emoji cepat">
            <?php foreach ($emojiPilihan as $em): ?>
              <button type="button" data-emoji="<?= e($em) ?>" title="Pakai <?= e($em) ?>"><?= $em ?></button>
            <?php endforeach; ?>
          </div>

          <label class="mtr-f">Isi pelajaran (kartu belajar)
            <small>
              Satu baris = satu kartu. Format: <code>emoji | teks pendek</code>.<br>
              Contoh: <code>🍎🍎 + 🍎🍎🍎 | 2 ditambah 3 sama dengan 5.</code>
              Kalimat pendek saja, cocok untuk kelas 1.
            </small>
            <textarea name="isi" id="fIsi" rows="7"><?= e($data['isi']) ?></textarea>
          </label>
          <div class="mtr-hitung" id="hitung"><span id="jmlKartu">0</span> kartu · <span id="jmlHuruf">0</span> / <?= ISI_MAKS ?> karakter</div>

          <label class="mtr-f">Gambar materi (opsional) <small>JPG/PNG/GIF/WEBP, maks 2 MB</small>
            <input type="file" name="gambar" id="fGambar" accept="image/png,image/jpeg,image/gif,image/webp">
          </label>

          <?php if (!empty($data['gambar'])): ?>
            <div class="mtr-gambar-lama">
              <img src="<?= e(gambar_url($data['gambar'])) ?>" alt="Gambar materi saat ini" id="gambarLama">
              <label class="mtr-saklar">
                <input type="checkbox" name="hapus_gambar" value="1" id="fHapusGambar">
                <i></i> Hapus gambar ini
              </label>
            </div>
          <?php endif; ?>

          <label class="mtr-saklar">
            <input type="checkbox" name="aktif" value="1" <?= $data['aktif'] ? 'checked' : '' ?>>
            <i></i> <span>Tampilkan ke anak</span>
          </label>

          <label class="mtr-saklar kuning">
            <input type="checkbox" name="perlu_dicek" value="1" <?= $data['perlu_dicek'] ? 'checked' : '' ?>>
            <i></i>
            <span>Tandai "perlu dicek"
              <small>belum saya periksa akurasinya</small></span>
          </label>
        </div>

        <!-- Pratinjau langsung -->
        <aside class="mtr-pratinjau" aria-live="polite">
          <span>Pratinjau</span>
          <div class="mtr-pv-kartu">
            <div class="em" id="pvEmoji"><?= e($data['emoji'] ?: EMOJI_BAWAAN) ?></div>
            <h3 id="pvJudul"><?= e($data['judul'] !== '' ? $data['judul'] : 'Judul materi') ?></h3>
            <p id="pvDesk"><?= e($data['deskripsi']) ?></p>
            <?php if (!empty($data['gambar'])): ?>
              <img src="<?= e(gambar_url($data['gambar'])) ?>" alt="" id="pvGambar">
            <?php endif; ?>
          </div>
          <div class="mtr-pv-isi" id="pvIsi"></div>
        </aside>
      </div>

      <div class="mtr-aksi-form">
        <button type="submit" class="mtr-btn tombol">💾 Simpan</button>
        <a class="mtr-btn abu tombol abu" href="materi.php<?= e($qFilter) ?>">Batal</a>
      </div>
    </form>
  <?php else: ?>

    <!-- Filter mapel -->
    <nav class="mtr-filter" aria-label="Filter mapel">
      <a class="mtr-chip <?= $filterMapel === 0 ? 'aktif' : '' ?>" href="materi.php">🗂️ Semua mapel</a>
      <?php foreach ($mapelList as $mp): ?>
        <a class="mtr-chip <?= $filterMapel === (int) $mp['id'] ? 'aktif' : '' ?>"
          href="materi.php?mapel_id=<?= (int) $mp['id'] ?>"><?= e($mp['emoji']) ?> <?= e($mp['nama']) ?></a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>

  <!-- Daftar materi -->
  <?php if ($daftar): ?>
    <div class="mtr-ringkas">
      <span>📖 <?= count($daftar) ?> materi</span>
      <span>👀 <?= $jmlAktif ?> tampil</span>
      <span>🙈 <?= count($daftar) - $jmlAktif ?> disembunyikan</span>
      <?php if ($jmlCek): ?><span style="background:#fef3c7;color:#92400e">⚠️ <?= $jmlCek ?> perlu dicek</span><?php endif; ?>
    </div>

    <?php $i = 0;
    foreach ($kelompok as $g): ?>
      <section class="mtr-grup">
        <h2 class="mtr-grup-judul" style="--w:<?= e($g['info']['warna']) ?>">
          <?= e($g['info']['emoji']) ?> <?= e($g['info']['nama']) ?>
          <small><?= count($g['materi']) ?> materi</small>
        </h2>

        <div class="mtr-daftar">
          <?php foreach ($g['materi'] as $m):
            $aktif = (bool) $m['aktif'];
            $kartu = $jmlKartu($m['isi']);
          ?>
            <article class="mtr-kartu <?= $aktif ? '' : 'mati' ?>" style="--w:<?= e($g['info']['warna']) ?>;--i:<?= $i++ ?>">
              <div class="mtr-isi">
                <div class="mtr-em"><?= e($m['emoji']) ?></div>
                <div class="mtr-teks">
                  <h3><?= e($m['judul']) ?></h3>
                  <p><?= e($m['deskripsi']) ?></p>
                  <div class="mtr-lencana">
                    <span class="<?= $aktif ? 'mtr-l-hijau' : 'mtr-l-abu' ?>"><?= $aktif ? '● Tampil' : '○ Disembunyikan' ?></span>
                    <?php if ($m['perlu_dicek']): ?><span class="mtr-l-kuning">⚠ perlu dicek</span><?php endif; ?>
                    <span class="mtr-l-ungu">🃏 <?= $kartu ?> kartu</span>
                    <a class="mtr-l-biru" href="soal.php?materi_id=<?= (int) $m['id'] ?>">❓ <?= (int) $m['jml_soal'] ?> soal</a>
                    <span class="mtr-l-abu">#<?= (int) $m['urutan'] ?></span>
                  </div>
                </div>
              </div>

              <div class="mtr-bawah aksi">
                <a class="mtr-btn kecil abu tombol kecil abu" href="materi.php?edit=<?= (int) $m['id'] ?>">✏️ Ubah</a>
                <a class="mtr-btn kecil abu tombol kecil abu" href="soal.php?materi_id=<?= (int) $m['id'] ?>">❓ Soal</a>
                <?= form_hapus(
                  'hapus',
                  (int) $m['id'],
                  'Hapus materi "' . $m['judul'] . '" beserta soal dan hasil kuisnya?'
                ) ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="mtr-kosong">
      <span class="emoji">🌱</span>
      Belum ada materi. Klik <strong>Tambah Materi</strong> untuk memulai.
    </div>
  <?php endif; ?>
</div>

<?php if ($tampilForm): ?>
  <script>
    (function() {
      var $ = function(id) {
        return document.getElementById(id);
      };
      var judul = $('fJudul'),
        desk = $('fDesk'),
        emoji = $('fEmoji'),
        isi = $('fIsi'),
        berkas = $('fGambar'),
        hapus = $('fHapusGambar'),
        pvJudul = $('pvJudul'),
        pvDesk = $('pvDesk'),
        pvEmoji = $('pvEmoji'),
        pvIsi = $('pvIsi'),
        jmlKartu = $('jmlKartu'),
        jmlHuruf = $('jmlHuruf'),
        hitung = $('hitung');
      var maks = <?= ISI_MAKS ?>,
        bawaan = '<?= EMOJI_BAWAAN ?>';

      function pratinjauGambar() {
        var img = $('pvGambar');
        if (img && hapus) img.style.display = hapus.checked ? 'none' : '';
      }

      function pratinjauIsi() {
        var baris = isi.value.split(/\r?\n/).filter(function(b) {
          return b.trim() !== '';
        });
        pvIsi.textContent = '';
        if (!baris.length) {
          var k = document.createElement('div');
          k.className = 'mtr-pv-kosong';
          k.textContent = 'Kartu belajar akan tampil di sini.';
          pvIsi.appendChild(k);
        }
        baris.slice(0, 30).forEach(function(b) {
          var pos = b.indexOf('|'),
            em = '',
            tk = b.trim();
          if (pos > -1) {
            em = b.slice(0, pos).trim();
            tk = b.slice(pos + 1).trim();
          }
          var k = document.createElement('div');
          k.className = 'k';
          var e1 = document.createElement('b');
          e1.textContent = em || '•';
          var t1 = document.createElement('span');
          t1.textContent = tk;
          k.appendChild(e1);
          k.appendChild(t1);
          pvIsi.appendChild(k);
        });
        jmlKartu.textContent = baris.length;
        jmlHuruf.textContent = isi.value.trim().length;
        hitung.classList.toggle('lewat', isi.value.trim().length > maks);
      }

      judul.addEventListener('input', function() {
        pvJudul.textContent = judul.value.trim() || 'Judul materi';
      });
      desk.addEventListener('input', function() {
        pvDesk.textContent = desk.value;
      });
      emoji.addEventListener('input', function() {
        pvEmoji.textContent = emoji.value.trim() || bawaan;
      });
      isi.addEventListener('input', pratinjauIsi);
      if (hapus) hapus.addEventListener('change', pratinjauGambar);

      berkas.addEventListener('change', function() {
        var f = berkas.files && berkas.files[0];
        if (!f) return;
        var r = new FileReader();
        r.onload = function(e) {
          var img = $('pvGambar');
          if (!img) {
            img = document.createElement('img');
            img.id = 'pvGambar';
            img.alt = '';
            document.querySelector('.mtr-pv-kartu').appendChild(img);
          }
          img.src = e.target.result;
          img.style.display = '';
        };
        r.readAsDataURL(f);
      });

      document.querySelectorAll('.mtr-emoji-pilih button').forEach(function(b) {
        b.addEventListener('click', function() {
          emoji.value = b.getAttribute('data-emoji');
          pvEmoji.textContent = emoji.value;
        });
      });

      pratinjauIsi();
    })();
  </script>
<?php endif; ?>

<?php admin_kaki(); ?>