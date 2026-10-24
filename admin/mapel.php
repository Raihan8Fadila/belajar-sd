<?php

declare(strict_types=1);

/**
 * KELOLA MAPEL
 * Tambah, ubah, dan hapus mata pelajaran (ikon emoji atau gambar sendiri).
 */

define('AREA_ADMIN', true);
require_once __DIR__ . '/../includes/fungsi.php';
require_once __DIR__ . '/../includes/admin_layout.php';

wajib_admin();

const WARNA_BAWAAN = '#A9D2FF';
const EMOJI_BAWAAN = '📘';

/* ---------------------------------------------------------------
 * DATA AWAL FORM
 * ------------------------------------------------------------- */

$galat = '';
$data  = [
  'id'        => 0,
  'nama'      => '',
  'slug'      => '',
  'deskripsi' => '',
  'emoji'     => EMOJI_BAWAAN,
  'gambar'    => null,
  'warna'     => WARNA_BAWAAN,
  'urutan'    => 0,
  'aktif'     => 1,
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
    $berkas = daftar_gambar_mapel($id);

    db()->prepare('DELETE FROM mapel WHERE id = ?')->execute([$id]);
    array_map('hapus_gambar', $berkas);

    flash_set('ok', 'Mapel dihapus (beserta materi, soal, dan hasilnya).');
    redirect('mapel.php');
  }

  /* --- Simpan (tambah / ubah) --- */
  if ($aksi === 'simpan') {
    $data['id']        = (int) ($_POST['id'] ?? 0);
    $data['nama']      = trim((string) ($_POST['nama'] ?? ''));
    $data['deskripsi'] = trim((string) ($_POST['deskripsi'] ?? ''));
    $data['emoji']     = trim((string) ($_POST['emoji'] ?? ''));
    $data['warna']     = (string) ($_POST['warna'] ?? '');
    $data['urutan']    = (int) ($_POST['urutan'] ?? 0);
    $data['aktif']     = isset($_POST['aktif']) ? 1 : 0;

    // Validasi
    if ($data['nama'] === '' || mb_strlen($data['nama']) > 100) {
      $galat = 'Nama mapel wajib diisi (maksimal 100 karakter).';
    } elseif (mb_strlen($data['deskripsi']) > 255) {
      $galat = 'Deskripsi maksimal 255 karakter.';
    } elseif (mb_strlen($data['emoji']) > 20) {
      $galat = 'Ikon emoji terlalu panjang.';
    } elseif (!warna_valid($data['warna'])) {
      $data['warna'] = WARNA_BAWAAN;
    }

    // Data lama (mode ubah)
    $lama = null;
    if ($data['id'] > 0) {
      $st = db()->prepare('SELECT * FROM mapel WHERE id = ?');
      $st->execute([$data['id']]);
      $lama = $st->fetch();

      if (!$lama) {
        redirect('mapel.php');
      }
      $data['gambar'] = $lama['gambar'];
      $data['slug']   = $lama['slug'];
    }

    // Unggah gambar baru (jika ada)
    $baru = null;
    if ($galat === '') {
      try {
        $baru = upload_gambar('gambar', 'mapel');
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
          'UPDATE mapel
                        SET nama = ?, deskripsi = ?, emoji = ?, gambar = ?, warna = ?, urutan = ?, aktif = ?
                      WHERE id = ?'
        )->execute([
          $data['nama'],
          $data['deskripsi'],
          $emoji,
          $gambar,
          $data['warna'],
          $data['urutan'],
          $data['aktif'],
          $data['id'],
        ]);
      } else {
        $slug = slug_unik(buat_slug($data['nama']));
        db()->prepare(
          'INSERT INTO mapel (nama, slug, deskripsi, emoji, gambar, warna, urutan, aktif)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
          $data['nama'],
          $slug,
          $data['deskripsi'],
          $emoji,
          $gambar,
          $data['warna'],
          $data['urutan'],
          $data['aktif'],
        ]);
      }

      flash_set('ok', 'Mapel disimpan.');
      redirect('mapel.php');
    }
  }
}

/* ---------------------------------------------------------------
 * MODE FORM: ?baru=1, ?edit=ID, atau form dengan galat
 * ------------------------------------------------------------- */

$tampilForm = isset($_GET['baru']) || isset($_GET['edit']) || $galat !== '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
  $st = db()->prepare('SELECT * FROM mapel WHERE id = ?');
  $st->execute([(int) $_GET['edit']]);
  $ada = $st->fetch();

  if (!$ada) {
    redirect('mapel.php');
  }
  $data = $ada;
}

/* ---------------------------------------------------------------
 * DATA DAFTAR
 * ------------------------------------------------------------- */

$daftar = db()->query(
  'SELECT mp.*, (SELECT COUNT(*) FROM materi m WHERE m.mapel_id = mp.id) AS jml_materi
       FROM mapel mp
      ORDER BY mp.urutan, mp.id'
)->fetchAll();

$jmlAktif = count(array_filter($daftar, static fn(array $m): bool => (bool) $m['aktif']));
$warnaForm = warna_valid((string) $data['warna']) ? (string) $data['warna'] : WARNA_BAWAAN;

/** Emoji pilihan cepat untuk form. */
$emojiPilihan = ['📘', '🔢', '🔤', '🌍', '🔬', '🎨', '🎵', '⚽', '🕌', '⛪', '🧮', '💻', '📖', '🌱', '🗣️', '🧠'];

admin_kepala('Kelola Mapel', 'mapel');
?>

<style>
  .mpl {
    --mpl-teks: #1f2937;
    --mpl-redup: #6b7280;
    --mpl-garis: #e5e7eb;
    --mpl-ungu: #7c3aed;
    --mpl-hijau: #10b981;
    --mpl-merah: #ef4444;
    --mpl-radius: 18px;
    color: var(--mpl-teks);
  }

  .mpl * {
    box-sizing: border-box;
  }

  /* Kepala halaman */
  .mpl-kepala {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1.3rem 1.5rem;
    margin-bottom: 1.4rem;
    color: #fff;
    border-radius: var(--mpl-radius);
    background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 55%, #a855f7 100%);
    box-shadow: 0 12px 28px -12px rgba(99, 102, 241, .65);
  }

  .mpl-kepala h1 {
    margin: 0 0 .2rem;
    font-size: 1.45rem;
  }

  .mpl-kepala p {
    margin: 0;
    opacity: .9;
    font-size: .95rem;
  }

  .mpl-tambah {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .7rem 1.3rem;
    font-weight: 800;
    color: #4f46e5;
    text-decoration: none;
    background: #fff;
    border-radius: 999px;
    box-shadow: 0 4px 0 rgba(0, 0, 0, .18);
    transition: transform .15s, box-shadow .15s;
  }

  .mpl-tambah:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 0 rgba(0, 0, 0, .18);
  }

  /* Form */
  .mpl-form {
    display: grid;
    gap: 1.2rem;
    padding: 1.5rem;
    margin-bottom: 1.8rem;
    background: #fff;
    border: 1px solid var(--mpl-garis);
    border-radius: var(--mpl-radius);
    box-shadow: 0 10px 30px -18px rgba(0, 0, 0, .35);
    animation: mpl-masuk .35s ease both;
  }

  @keyframes mpl-masuk {
    from {
      opacity: 0;
      transform: translateY(-12px);
    }

    to {
      opacity: 1;
      transform: none;
    }
  }

  .mpl-form>h2 {
    margin: 0;
    font-size: 1.2rem;
  }

  .mpl-galat {
    margin: 0;
    padding: .7rem 1rem;
    font-weight: 600;
    color: #991b1b;
    background: #fee2e2;
    border-left: 5px solid var(--mpl-merah);
    border-radius: 10px;
  }

  .mpl-grid {
    display: grid;
    gap: 1.4rem;
    grid-template-columns: 1fr 280px;
    align-items: start;
  }

  .mpl-kolom {
    display: grid;
    gap: 1rem;
  }

  .mpl-baris {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
  }

  .mpl-f {
    display: grid;
    gap: .35rem;
    font-weight: 700;
    font-size: .92rem;
  }

  .mpl-f small {
    font-weight: 400;
    color: var(--mpl-redup);
  }

  .mpl-f input[type=text],
  .mpl-f input[type=number] {
    width: 100%;
    padding: .7rem .9rem;
    font: inherit;
    font-weight: 400;
    color: var(--mpl-teks);
    background: #f9fafb;
    border: 2px solid var(--mpl-garis);
    border-radius: 12px;
    outline: none;
    transition: border-color .2s, box-shadow .2s, background .2s;
  }

  .mpl-f input:focus {
    background: #fff;
    border-color: var(--mpl-ungu);
    box-shadow: 0 0 0 4px rgba(124, 58, 237, .15);
  }

  .mpl-f input[type=color] {
    width: 100%;
    height: 46px;
    padding: 3px;
    cursor: pointer;
    background: #f9fafb;
    border: 2px solid var(--mpl-garis);
    border-radius: 12px;
  }

  .mpl-f input[type=file] {
    padding: .6rem;
    font: inherit;
    font-weight: 400;
    background: #f9fafb;
    border: 2px dashed #c4b5fd;
    border-radius: 12px;
    cursor: pointer;
  }

  /* Pilihan emoji */
  .mpl-emoji-pilih {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
  }

  .mpl-emoji-pilih button {
    width: 38px;
    height: 38px;
    font-size: 1.2rem;
    cursor: pointer;
    background: #f3f4f6;
    border: 2px solid transparent;
    border-radius: 10px;
    transition: transform .12s, border-color .12s;
  }

  .mpl-emoji-pilih button:hover {
    transform: scale(1.18) rotate(-6deg);
    border-color: var(--mpl-ungu);
  }

  /* Pratinjau */
  .mpl-pratinjau {
    position: sticky;
    top: 1rem;
    text-align: center;
  }

  .mpl-pratinjau>span {
    display: block;
    margin-bottom: .5rem;
    font-size: .8rem;
    font-weight: 700;
    color: var(--mpl-redup);
    text-transform: uppercase;
    letter-spacing: .05em;
  }

  /* Saklar */
  .mpl-saklar {
    display: flex;
    align-items: center;
    gap: .7rem;
    font-weight: 700;
    cursor: pointer;
  }

  .mpl-saklar input {
    position: absolute;
    opacity: 0;
  }

  .mpl-saklar i {
    position: relative;
    flex: none;
    width: 50px;
    height: 28px;
    background: #d1d5db;
    border-radius: 999px;
    transition: background .2s;
  }

  .mpl-saklar i::after {
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

  .mpl-saklar input:checked+i {
    background: var(--mpl-hijau);
  }

  .mpl-saklar input:checked+i::after {
    transform: translateX(22px);
  }

  .mpl-saklar input:focus-visible+i {
    outline: 3px solid var(--mpl-ungu);
    outline-offset: 2px;
  }

  .mpl-gambar-lama {
    display: flex;
    align-items: center;
    gap: .8rem;
    flex-wrap: wrap;
  }

  .mpl-gambar-lama img {
    max-height: 56px;
    border-radius: 12px;
    border: 2px solid var(--mpl-garis);
  }

  /* Tombol */
  .mpl-aksi-form {
    display: flex;
    gap: .7rem;
    flex-wrap: wrap;
  }

  .mpl-btn {
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
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    box-shadow: 0 4px 0 #4c1d95;
    transition: transform .12s, box-shadow .12s;
  }

  .mpl-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #4c1d95;
  }

  .mpl-btn:active {
    transform: translateY(3px);
    box-shadow: 0 1px 0 #4c1d95;
  }

  .mpl-btn.abu {
    color: #374151;
    background: #e5e7eb;
    box-shadow: 0 4px 0 #9ca3af;
  }

  .mpl-btn.kecil {
    padding: .45rem 1rem;
    font-size: .88rem;
  }

  /* Kartu mapel */
  .mpl-ringkas {
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
    margin: 0 0 1rem;
  }

  .mpl-ringkas span {
    padding: .3rem .85rem;
    font-size: .85rem;
    font-weight: 700;
    background: #f3f4f6;
    border-radius: 999px;
  }

  .mpl-daftar {
    display: grid;
    gap: 1.1rem;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  }

  .mpl-kartu {
    --w: #A9D2FF;
    position: relative;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: #fff;
    border: 1px solid var(--mpl-garis);
    border-radius: var(--mpl-radius);
    transition: transform .2s ease, box-shadow .2s ease;
    animation: mpl-masuk .45s ease both;
    animation-delay: calc(var(--i, 0) * 60ms);
  }

  .mpl-kartu:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 30px -14px rgba(0, 0, 0, .35);
  }

  .mpl-kartu.mati {
    opacity: .72;
  }

  .mpl-atas {
    position: relative;
    display: grid;
    place-items: center;
    height: 110px;
    background: radial-gradient(circle at 25% 20%, rgba(255, 255, 255, .55), transparent 55%), var(--w);
  }

  .mpl-ikon {
    font-size: 3.2rem;
    line-height: 1;
    filter: drop-shadow(0 4px 4px rgba(0, 0, 0, .2));
    transition: transform .3s;
  }

  .mpl-ikon img {
    display: block;
    max-height: 64px;
    max-width: 120px;
  }

  .mpl-kartu:hover .mpl-ikon {
    transform: scale(1.18) rotate(-8deg);
  }

  .mpl-urut {
    position: absolute;
    top: .6rem;
    left: .6rem;
    min-width: 30px;
    padding: .15rem .55rem;
    font-size: .8rem;
    font-weight: 800;
    text-align: center;
    background: rgba(255, 255, 255, .85);
    border-radius: 999px;
  }

  .mpl-status {
    position: absolute;
    top: .6rem;
    right: .6rem;
    padding: .15rem .65rem;
    font-size: .75rem;
    font-weight: 800;
    color: #065f46;
    background: #d1fae5;
    border-radius: 999px;
  }

  .mpl-status.mati {
    color: #4b5563;
    background: #e5e7eb;
  }

  .mpl-isi {
    flex: 1;
    padding: 1rem 1.1rem .4rem;
  }

  .mpl-isi h3 {
    margin: 0 0 .25rem;
    font-size: 1.1rem;
  }

  .mpl-isi p {
    margin: 0 0 .7rem;
    min-height: 1.3em;
    font-size: .9rem;
    color: var(--mpl-redup);
  }

  .mpl-materi {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .3rem .8rem;
    font-size: .85rem;
    font-weight: 700;
    color: #5b21b6;
    text-decoration: none;
    background: #ede9fe;
    border-radius: 999px;
    transition: background .15s, transform .15s;
  }

  .mpl-materi:hover {
    background: #ddd6fe;
    transform: scale(1.05);
  }

  .mpl-bawah {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
    padding: .8rem 1.1rem 1.1rem;
  }

  .mpl-kosong {
    padding: 2.5rem 1rem;
    text-align: center;
    color: var(--mpl-redup);
    background: #fff;
    border: 2px dashed var(--mpl-garis);
    border-radius: var(--mpl-radius);
  }

  .mpl-kosong .emoji {
    display: block;
    margin-bottom: .4rem;
    font-size: 2.6rem;
  }

  @media (max-width: 760px) {
    .mpl-grid {
      grid-template-columns: 1fr;
    }

    .mpl-pratinjau {
      position: static;
      order: -1;
    }
  }

  @media (prefers-reduced-motion: reduce) {

    .mpl *,
    .mpl *::before,
    .mpl *::after {
      animation: none !important;
      transition: none !important;
    }
  }
</style>

<div class="mpl">

  <!-- Kepala halaman -->
  <header class="mpl-kepala">
    <div>
      <h1>📚 Kelola Mapel</h1>
      <p>Atur mata pelajaran yang tampil di halaman anak.</p>
    </div>
    <?php if (!$tampilForm): ?>
      <a class="mpl-tambah" href="mapel.php?baru=1">➕ Tambah Mapel</a>
    <?php endif; ?>
  </header>

  <!-- Form tambah / ubah -->
  <?php if ($tampilForm): ?>
    <form method="post" enctype="multipart/form-data" class="mpl-form" id="formMapel">
      <h2><?= $data['id'] ? '✏️ Ubah mapel' : '✨ Tambah mapel' ?></h2>

      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="simpan">
      <input type="hidden" name="id" value="<?= (int) $data['id'] ?>">

      <?php if ($galat !== ''): ?>
        <p class="mpl-galat a-flash a-flash-galat" role="alert">⚠️ <?= e($galat) ?></p>
      <?php endif; ?>

      <div class="mpl-grid">
        <div class="mpl-kolom">
          <div class="mpl-baris">
            <label class="mpl-f">Nama mapel
              <input type="text" name="nama" id="fNama" value="<?= e($data['nama']) ?>" maxlength="100" required autofocus>
            </label>
            <label class="mpl-f">Deskripsi singkat <small>tampil di kartu mapel</small>
              <input type="text" name="deskripsi" id="fDesk" value="<?= e($data['deskripsi']) ?>" maxlength="255">
            </label>
          </div>

          <div class="mpl-baris">
            <label class="mpl-f">Ikon (emoji)
              <input type="text" name="emoji" id="fEmoji" value="<?= e($data['emoji']) ?>" maxlength="20">
            </label>
            <label class="mpl-f">Warna kartu
              <input type="color" name="warna" id="fWarna" value="<?= e($warnaForm) ?>">
            </label>
            <label class="mpl-f">Urutan <small>angka kecil tampil lebih dulu</small>
              <input type="number" name="urutan" value="<?= (int) $data['urutan'] ?>">
            </label>
          </div>

          <div class="mpl-emoji-pilih" aria-label="Pilihan emoji cepat">
            <?php foreach ($emojiPilihan as $em): ?>
              <button type="button" data-emoji="<?= e($em) ?>" title="Pakai <?= e($em) ?>"><?= $em ?></button>
            <?php endforeach; ?>
          </div>

          <label class="mpl-f">Gambar ikon sendiri (opsional)
            <small>JPG/PNG/GIF/WEBP, maks 2 MB. Jika ada, menggantikan emoji.</small>
            <input type="file" name="gambar" id="fGambar" accept="image/png,image/jpeg,image/gif,image/webp">
          </label>

          <?php if (!empty($data['gambar'])): ?>
            <div class="mpl-gambar-lama">
              <img src="<?= e(gambar_url($data['gambar'])) ?>" alt="Gambar ikon saat ini">
              <label class="mpl-saklar">
                <input type="checkbox" name="hapus_gambar" value="1" id="fHapusGambar">
                <i></i> Hapus gambar ini
              </label>
            </div>
          <?php endif; ?>

          <label class="mpl-saklar">
            <input type="checkbox" name="aktif" value="1" <?= $data['aktif'] ? 'checked' : '' ?>>
            <i></i> Tampilkan ke anak
          </label>
        </div>

        <!-- Pratinjau langsung -->
        <aside class="mpl-pratinjau" aria-live="polite">
          <span>Pratinjau</span>
          <div class="mpl-kartu" id="pvKartu" style="--w:<?= e($warnaForm) ?>;animation:none">
            <div class="mpl-atas">
              <div class="mpl-ikon" id="pvIkon">
                <?php if (!empty($data['gambar'])): ?>
                  <img src="<?= e(gambar_url($data['gambar'])) ?>" alt="">
                <?php else: ?>
                  <?= e($data['emoji'] ?: EMOJI_BAWAAN) ?>
                <?php endif; ?>
              </div>
            </div>
            <div class="mpl-isi">
              <h3 id="pvNama"><?= e($data['nama'] !== '' ? $data['nama'] : 'Nama mapel') ?></h3>
              <p id="pvDesk"><?= e($data['deskripsi']) ?></p>
            </div>
          </div>
        </aside>
      </div>

      <div class="mpl-aksi-form">
        <button type="submit" class="mpl-btn tombol">💾 Simpan</button>
        <a class="mpl-btn abu tombol abu" href="mapel.php">Batal</a>
      </div>
    </form>
  <?php endif; ?>

  <!-- Daftar mapel -->
  <?php if ($daftar): ?>
    <div class="mpl-ringkas">
      <span>📚 <?= count($daftar) ?> mapel</span>
      <span>👀 <?= $jmlAktif ?> tampil</span>
      <span>🙈 <?= count($daftar) - $jmlAktif ?> disembunyikan</span>
    </div>

    <div class="mpl-daftar">
      <?php foreach ($daftar as $i => $m):
        $aktif = (bool) $m['aktif'];
        $warna = warna_valid((string) $m['warna']) ? (string) $m['warna'] : WARNA_BAWAAN;
      ?>
        <article class="mpl-kartu <?= $aktif ? '' : 'mati' ?>" style="--w:<?= e($warna) ?>;--i:<?= $i ?>">
          <div class="mpl-atas">
            <span class="mpl-urut" title="Urutan">#<?= (int) $m['urutan'] ?></span>
            <span class="mpl-status <?= $aktif ? '' : 'mati' ?>"><?= $aktif ? '● Tampil' : '○ Disembunyikan' ?></span>
            <div class="mpl-ikon">
              <?php if ($m['gambar']): ?>
                <img src="<?= e(gambar_url($m['gambar'])) ?>" alt="">
              <?php else: ?>
                <?= e($m['emoji']) ?>
              <?php endif; ?>
            </div>
          </div>

          <div class="mpl-isi">
            <h3><?= e($m['nama']) ?></h3>
            <p><?= e($m['deskripsi']) ?></p>
            <a class="mpl-materi" href="materi.php?mapel_id=<?= (int) $m['id'] ?>">📖 <?= (int) $m['jml_materi'] ?> materi →</a>
          </div>

          <div class="mpl-bawah aksi">
            <a class="mpl-btn kecil abu tombol kecil abu" href="mapel.php?edit=<?= (int) $m['id'] ?>">✏️ Ubah</a>
            <?= form_hapus(
              'hapus',
              (int) $m['id'],
              'Hapus mapel "' . $m['nama'] . '" beserta SEMUA materi, soal, dan hasil kuisnya? Tindakan ini tidak bisa dibatalkan.'
            ) ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="mpl-kosong">
      <span class="emoji">🌱</span>
      Belum ada mapel. Klik <strong>Tambah Mapel</strong> untuk memulai.
    </div>
  <?php endif; ?>
</div>

<?php if ($tampilForm): ?>
  <script>
    (function() {
      var $ = function(id) {
        return document.getElementById(id);
      };
      var nama = $('fNama'),
        desk = $('fDesk'),
        emoji = $('fEmoji'),
        warna = $('fWarna'),
        berkas = $('fGambar'),
        hapus = $('fHapusGambar'),
        pvNama = $('pvNama'),
        pvDesk = $('pvDesk'),
        pvIkon = $('pvIkon'),
        pvKartu = $('pvKartu');
      var gambarAwal = pvIkon.querySelector('img') ? pvIkon.querySelector('img').src : '';
      var gambarPilih = '';

      function ikon() {
        var src = (hapus && hapus.checked) ? gambarPilih : (gambarPilih || gambarAwal);
        pvIkon.textContent = '';
        if (src) {
          var img = document.createElement('img');
          img.src = src;
          img.alt = '';
          pvIkon.appendChild(img);
        } else {
          pvIkon.textContent = emoji.value.trim() || '<?= EMOJI_BAWAAN ?>';
        }
      }

      nama.addEventListener('input', function() {
        pvNama.textContent = nama.value.trim() || 'Nama mapel';
      });
      desk.addEventListener('input', function() {
        pvDesk.textContent = desk.value;
      });
      emoji.addEventListener('input', ikon);
      warna.addEventListener('input', function() {
        pvKartu.style.setProperty('--w', warna.value);
      });
      if (hapus) hapus.addEventListener('change', ikon);

      berkas.addEventListener('change', function() {
        var f = berkas.files && berkas.files[0];
        if (!f) {
          gambarPilih = '';
          return ikon();
        }
        var r = new FileReader();
        r.onload = function(e) {
          gambarPilih = e.target.result;
          ikon();
        };
        r.readAsDataURL(f);
      });

      document.querySelectorAll('.mpl-emoji-pilih button').forEach(function(b) {
        b.addEventListener('click', function() {
          emoji.value = b.getAttribute('data-emoji');
          ikon();
        });
      });
    })();
  </script>
<?php endif; ?>

<?php admin_kaki(); ?>