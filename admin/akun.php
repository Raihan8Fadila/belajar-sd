<?php

declare(strict_types=1);

/**
 * AKUN ADMIN
 * Ganti password.
 */

define('AREA_ADMIN', true);
require_once __DIR__ . '/../includes/fungsi.php';
require_once __DIR__ . '/../includes/admin_layout.php';

$admin = wajib_admin();

const PASS_MIN = 8;
const PASS_MAKS = 72;   // batas aman bcrypt (byte)

/* ---------------------------------------------------------------
 * PROSES GANTI PASSWORD
 * ------------------------------------------------------------- */

$galat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_wajib();

  $lama  = (string) ($_POST['lama'] ?? '');
  $baru  = (string) ($_POST['baru'] ?? '');
  $ulang = (string) ($_POST['ulang'] ?? '');

  $st = db()->prepare('SELECT password_hash FROM admin WHERE id = ?');
  $st->execute([$admin['id']]);
  $hash = (string) $st->fetchColumn();

  if (!password_verify($lama, $hash)) {
    $galat = 'Password lama salah.';
  } elseif (mb_strlen($baru) < PASS_MIN) {
    $galat = 'Password baru minimal ' . PASS_MIN . ' karakter.';
  } elseif (strlen($baru) > PASS_MAKS) {
    $galat = 'Password baru terlalu panjang (maksimal ' . PASS_MAKS . ' karakter).';
  } elseif ($baru !== $ulang) {
    $galat = 'Konfirmasi password baru tidak sama.';
  } else {
    db()->prepare('UPDATE admin SET password_hash = ? WHERE id = ?')
      ->execute([password_hash($baru, PASSWORD_DEFAULT), $admin['id']]);

    session_regenerate_id(true);
    flash_set('ok', 'Password berhasil diganti.');
    redirect('akun.php');
  }
}

/* ---------------------------------------------------------------
 * DATA TAMPILAN
 * ------------------------------------------------------------- */

$namaAdmin = (string) $admin['username'];
$inisial   = mb_strtoupper(mb_substr($namaAdmin !== '' ? $namaAdmin : '?', 0, 1));

admin_kepala('Akun', 'akun');
?>

<style>
  .akn {
    --akn-teks: #1f2937;
    --akn-redup: #6b7280;
    --akn-garis: #e5e7eb;
    --akn-ungu: #7c3aed;
    --akn-hijau: #10b981;
    --akn-merah: #ef4444;
    --akn-radius: 18px;
    color: var(--akn-teks);
  }

  .akn * {
    box-sizing: border-box;
  }

  /* Kepala */
  .akn-kepala {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.3rem 1.5rem;
    margin-bottom: 1.4rem;
    color: #fff;
    border-radius: var(--akn-radius);
    background: linear-gradient(135deg, #14b8a6 0%, #6366f1 55%, #a855f7 100%);
    box-shadow: 0 12px 28px -12px rgba(99, 102, 241, .65);
  }

  .akn-kepala h1 {
    margin: 0 0 .2rem;
    font-size: 1.45rem;
  }

  .akn-kepala p {
    margin: 0;
    opacity: .92;
    font-size: .95rem;
  }

  .akn-kepala .emoji {
    margin-left: auto;
    font-size: 2.6rem;
    animation: akn-ayun 3s ease-in-out infinite;
  }

  @keyframes akn-ayun {

    0%,
    100% {
      transform: rotate(-8deg);
    }

    50% {
      transform: rotate(8deg) translateY(-3px);
    }
  }

  .akn-grid {
    display: grid;
    gap: 1.4rem;
    grid-template-columns: 300px minmax(0, 1fr);
    align-items: start;
  }

  /* Kartu profil */
  .akn-profil {
    padding: 1.6rem 1.3rem;
    text-align: center;
    background: #fff;
    border: 1px solid var(--akn-garis);
    border-radius: var(--akn-radius);
    box-shadow: 0 10px 30px -20px rgba(0, 0, 0, .35);
  }

  .akn-avatar {
    display: grid;
    place-items: center;
    width: 92px;
    height: 92px;
    margin: 0 auto .9rem;
    font-size: 2.4rem;
    font-weight: 800;
    color: #fff;
    border: 4px solid #fff;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #ec4899);
    box-shadow: 0 10px 22px -8px rgba(236, 72, 153, .7), 0 0 0 5px #ede9fe;
  }

  .akn-profil h2 {
    margin: 0 0 .2rem;
    font-size: 1.2rem;
    word-break: break-word;
  }

  .akn-peran {
    display: inline-block;
    padding: .15rem .8rem;
    font-size: .78rem;
    font-weight: 800;
    color: #5b21b6;
    background: #ede9fe;
    border-radius: 999px;
  }

  .akn-tips {
    margin: 1.3rem 0 0;
    padding: 1rem;
    text-align: left;
    background: #f5f3ff;
    border-radius: 14px;
  }

  .akn-tips h3 {
    margin: 0 0 .5rem;
    font-size: .95rem;
  }

  .akn-tips ul {
    margin: 0;
    padding-left: 1.1rem;
    font-size: .86rem;
    color: #4b5563;
    line-height: 1.7;
  }

  /* Form */
  .akn-form {
    display: grid;
    gap: 1.1rem;
    padding: 1.6rem;
    background: #fff;
    border: 1px solid var(--akn-garis);
    border-radius: var(--akn-radius);
    box-shadow: 0 10px 30px -20px rgba(0, 0, 0, .35);
    animation: akn-masuk .4s ease both;
  }

  @keyframes akn-masuk {
    from {
      opacity: 0;
      transform: translateY(12px);
    }

    to {
      opacity: 1;
      transform: none;
    }
  }

  .akn-form>h2 {
    margin: 0;
    font-size: 1.2rem;
  }

  .akn-galat {
    margin: 0;
    padding: .75rem 1rem;
    font-weight: 600;
    color: #991b1b;
    background: #fee2e2;
    border-left: 5px solid var(--akn-merah);
    border-radius: 12px;
    animation: akn-getar .45s ease;
  }

  @keyframes akn-getar {

    0%,
    100% {
      transform: translateX(0);
    }

    20%,
    60% {
      transform: translateX(-8px);
    }

    40%,
    80% {
      transform: translateX(8px);
    }
  }

  .akn-f {
    display: grid;
    gap: .4rem;
    font-weight: 700;
    font-size: .92rem;
  }

  .akn-f small {
    font-weight: 400;
    color: var(--akn-redup);
  }

  .akn-kolom {
    position: relative;
  }

  .akn-kolom input {
    display: block;
    width: 100%;
    padding: .8rem 3rem .8rem 1rem;
    font: inherit;
    font-weight: 400;
    color: var(--akn-teks);
    background: #f9fafb;
    border: 2px solid var(--akn-garis);
    border-radius: 12px;
    outline: none;
    transition: border-color .2s, box-shadow .2s, background .2s;
  }

  .akn-kolom input:focus {
    background: #fff;
    border-color: var(--akn-ungu);
    box-shadow: 0 0 0 4px rgba(124, 58, 237, .15);
  }

  .akn-kolom input.cocok {
    border-color: var(--akn-hijau);
  }

  .akn-kolom input.beda {
    border-color: var(--akn-merah);
  }

  .akn-mata {
    position: absolute;
    top: 50%;
    right: .4rem;
    width: 38px;
    height: 38px;
    margin-top: -19px;
    font-size: 1.05rem;
    cursor: pointer;
    background: transparent;
    border: 0;
    border-radius: 10px;
    transition: background .15s;
  }

  .akn-mata:hover {
    background: #ede9fe;
  }

  .akn-mata:focus-visible {
    outline: 3px solid var(--akn-ungu);
    outline-offset: 1px;
  }

  /* Kekuatan password */
  .akn-kuat {
    display: grid;
    gap: .35rem;
  }

  .akn-bar {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: .35rem;
  }

  .akn-bar i {
    height: 7px;
    background: #e5e7eb;
    border-radius: 999px;
    transition: background .25s;
  }

  .akn-bar.s1 i:nth-child(-n+1) {
    background: #ef4444;
  }

  .akn-bar.s2 i:nth-child(-n+2) {
    background: #f59e0b;
  }

  .akn-bar.s3 i:nth-child(-n+3) {
    background: #84cc16;
  }

  .akn-bar.s4 i:nth-child(-n+4) {
    background: #10b981;
  }

  .akn-kuat-teks {
    font-size: .82rem;
    font-weight: 700;
    color: var(--akn-redup);
    min-height: 1.2em;
  }

  .akn-cocok-teks {
    font-size: .82rem;
    font-weight: 700;
    min-height: 1.2em;
  }

  .akn-cocok-teks.ok {
    color: #047857;
  }

  .akn-cocok-teks.gagal {
    color: #b91c1c;
  }

  .akn-btn {
    justify-self: start;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .8rem 1.6rem;
    font: inherit;
    font-weight: 800;
    color: #fff;
    cursor: pointer;
    border: 0;
    border-radius: 999px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    box-shadow: 0 4px 0 #4c1d95;
    transition: transform .12s, box-shadow .12s;
  }

  .akn-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 0 #4c1d95;
  }

  .akn-btn:active {
    transform: translateY(3px);
    box-shadow: 0 1px 0 #4c1d95;
  }

  .akn-btn:focus-visible {
    outline: 4px solid #fde047;
    outline-offset: 3px;
  }

  @media (max-width: 800px) {
    .akn-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (prefers-reduced-motion: reduce) {

    .akn *,
    .akn *::before,
    .akn *::after {
      animation: none !important;
      transition: none !important;
    }
  }
</style>

<div class="akn">

  <header class="akn-kepala">
    <div>
      <h1>👤 Akun Saya</h1>
      <p>Kelola keamanan akun admin Anda.</p>
    </div>
    <div class="emoji" aria-hidden="true">🔐</div>
  </header>

  <div class="akn-grid">

    <!-- Profil -->
    <aside class="akn-profil">
      <div class="akn-avatar" aria-hidden="true"><?= e($inisial) ?></div>
      <h2><?= e($namaAdmin) ?></h2>
      <span class="akn-peran">Administrator</span>

      <div class="akn-tips">
        <h3>💡 Tips password aman</h3>
        <ul>
          <li>Minimal <?= PASS_MIN ?> karakter.</li>
          <li>Campur huruf besar, kecil, angka, dan simbol.</li>
          <li>Jangan memakai nama atau tanggal lahir anak.</li>
          <li>Jangan dipakai di akun lain.</li>
        </ul>
      </div>
    </aside>

    <!-- Form ganti password -->
    <form method="post" class="akn-form kartu form-grid" autocomplete="off" id="formAkun">
      <h2>🔑 Ganti Password</h2>
      <?= csrf_field() ?>

      <?php if ($galat !== ''): ?>
        <p class="akn-galat a-flash a-flash-galat" role="alert">⚠️ <?= e($galat) ?></p>
      <?php endif; ?>

      <label class="akn-f f">Password lama
        <span class="akn-kolom">
          <input type="password" name="lama" required autofocus autocomplete="current-password">
          <button type="button" class="akn-mata" data-mata aria-label="Tampilkan password">👁️</button>
        </span>
      </label>

      <label class="akn-f f">Password baru <small>minimal <?= PASS_MIN ?> karakter</small>
        <span class="akn-kolom">
          <input type="password" name="baru" id="baru" required minlength="<?= PASS_MIN ?>"
            maxlength="<?= PASS_MAKS ?>" autocomplete="new-password">
          <button type="button" class="akn-mata" data-mata aria-label="Tampilkan password">👁️</button>
        </span>
      </label>

      <div class="akn-kuat" aria-live="polite">
        <div class="akn-bar" id="bar"><i></i><i></i><i></i><i></i></div>
        <span class="akn-kuat-teks" id="kuatTeks">Ketik password baru untuk melihat kekuatannya.</span>
      </div>

      <label class="akn-f f">Ulangi password baru
        <span class="akn-kolom">
          <input type="password" name="ulang" id="ulang" required minlength="<?= PASS_MIN ?>"
            maxlength="<?= PASS_MAKS ?>" autocomplete="new-password">
          <button type="button" class="akn-mata" data-mata aria-label="Tampilkan password">👁️</button>
        </span>
      </label>
      <span class="akn-cocok-teks" id="cocokTeks" aria-live="polite"></span>

      <button type="submit" class="akn-btn tombol">💾 Ganti Password</button>
    </form>
  </div>
</div>

<script>
  (function() {
    var baru = document.getElementById('baru'),
      ulang = document.getElementById('ulang'),
      bar = document.getElementById('bar'),
      kuatTeks = document.getElementById('kuatTeks'),
      cocokTeks = document.getElementById('cocokTeks'),
      min = <?= PASS_MIN ?>;
    var label = ['Terlalu pendek', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'];

    // Tampilkan / sembunyikan password
    document.querySelectorAll('[data-mata]').forEach(function(tombol) {
      tombol.addEventListener('click', function() {
        var input = tombol.parentNode.querySelector('input');
        var tampil = input.type === 'password';
        input.type = tampil ? 'text' : 'password';
        tombol.textContent = tampil ? '🙈' : '👁️';
        tombol.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
      });
    });

    // Kekuatan password (indikator saja; aturan sebenarnya dicek di server)
    function skor(p) {
      if (p.length < min) return 0;
      var s = 1;
      if (p.length >= 12) s++;
      if (/[a-z]/.test(p) && /[A-Z]/.test(p)) s++;
      if (/\d/.test(p) && /[^A-Za-z0-9]/.test(p)) s++;
      return Math.min(s, 4);
    }

    function cekKuat() {
      var p = baru.value;
      if (!p) {
        bar.className = 'akn-bar';
        kuatTeks.textContent = 'Ketik password baru untuk melihat kekuatannya.';
      } else {
        var s = skor(p);
        bar.className = 'akn-bar' + (s ? ' s' + s : '');
        kuatTeks.textContent = 'Kekuatan: ' + label[s];
      }
      cekCocok();
    }

    function cekCocok() {
      ulang.classList.remove('cocok', 'beda');
      cocokTeks.className = 'akn-cocok-teks';
      if (!ulang.value) {
        cocokTeks.textContent = '';
        return;
      }
      var sama = ulang.value === baru.value;
      ulang.classList.add(sama ? 'cocok' : 'beda');
      cocokTeks.classList.add(sama ? 'ok' : 'gagal');
      cocokTeks.textContent = sama ? '✅ Password cocok' : '❌ Password belum sama';
    }

    baru.addEventListener('input', cekKuat);
    ulang.addEventListener('input', cekCocok);
  })();
</script>

<?php admin_kaki(); ?>