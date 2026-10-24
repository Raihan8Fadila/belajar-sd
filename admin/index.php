<?php

declare(strict_types=1);

/**
 * LOGIN ADMIN (hanya untuk guru/orang tua).
 * Dilindungi CSRF dan pembatasan percobaan.
 * Halaman ini berdiri sendiri (layar penuh, tanpa header/footer layout admin).
 */

define('AREA_ADMIN', true);
require_once __DIR__ . '/../includes/fungsi.php';
require_once __DIR__ . '/../includes/admin_layout.php';

if (!admin_ada()) {
    redirect(url('install.php'));
}
if (admin_aktif()) {
    redirect('dashboard.php');
}

const MAKS_GAGAL  = 5;   // setelah 5x salah, tunggu sebentar
const KUNCI_DETIK = 60;

defined('PEMILIK') || define('PEMILIK', 'Raihan Fadila');   // nama pemilik website

// Hash palsu (valid, acak) dipakai bila username tidak ada,
// agar waktu respons relatif sama dan username tidak bisa ditebak.
const HASH_PALSU = '$2y$10$oHcDv3Nrsx9weMUxkFU0C.bXst7FnxgylV6ZOArHt7kzQUJNXnxoS';

/* ---------------------------------------------------------------
 * PROSES LOGIN
 * ------------------------------------------------------------- */

$galat    = '';
$userIsi  = '';
$sekarang = time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_wajib();

    $sisa = (int) ($_SESSION['kunci_sampai'] ?? 0) - $sekarang;

    if ($sisa > 0) {
        $galat = "Terlalu banyak percobaan. Coba lagi dalam {$sisa} detik.";
    } else {
        $userIsi = trim((string) ($_POST['username'] ?? ''));
        $pass    = (string) ($_POST['password'] ?? '');

        $st = db()->prepare('SELECT id, username, password_hash FROM admin WHERE username = ?');
        $st->execute([$userIsi]);
        $a = $st->fetch();

        $hash = $a['password_hash'] ?? HASH_PALSU;

        if (password_verify($pass, $hash) && $a) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = (int) $a['id'];
            $_SESSION['admin_user'] = $a['username'];
            unset($_SESSION['gagal'], $_SESSION['kunci_sampai']);

            // Perbarui hash bila algoritma bawaan PHP sudah berganti.
            if (password_needs_rehash($a['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE admin SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($pass, PASSWORD_DEFAULT), $a['id']]);
            }

            redirect('dashboard.php');
        }

        // Gagal: hitung percobaan, kunci sementara bila melewati batas.
        $_SESSION['gagal'] = ($_SESSION['gagal'] ?? 0) + 1;
        if ($_SESSION['gagal'] >= MAKS_GAGAL) {
            $_SESSION['kunci_sampai'] = $sekarang + KUNCI_DETIK;
            $_SESSION['gagal']        = 0;
        }

        usleep(400000);
        $galat = 'Username atau password salah.';
    }
}

// Sisa waktu kunci (detik) untuk hitung mundur di tampilan.
$sisaKunci = max(0, (int) ($_SESSION['kunci_sampai'] ?? 0) - time());
$mati      = $sisaKunci > 0 ? 'disabled' : '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Masuk Admin</title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            margin: 0;
        }

        body {
            position: relative;
            display: grid;
            place-items: center;
            min-height: 100vh;
            min-height: 100dvh;
            padding: 1.5rem 1rem;
            overflow-x: hidden;
            color: #fff;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(125deg, #1e1b4b, #4c1d95, #be185d, #f97316, #0ea5e9, #4c1d95, #1e1b4b);
            background-size: 400% 400%;
            animation: geser 18s ease-in-out infinite;
        }

        @keyframes geser {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        /* ---------- Latar bergerak ---------- */
        .latar {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .aurora {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            opacity: .55;
            mix-blend-mode: screen;
        }

        .aurora.a {
            top: -12%;
            left: -8%;
            width: 46vmax;
            height: 46vmax;
            background: #22d3ee;
            animation: melayang-a 16s ease-in-out infinite;
        }

        .aurora.b {
            top: 35%;
            right: -12%;
            width: 52vmax;
            height: 52vmax;
            background: #f472b6;
            animation: melayang-b 21s ease-in-out infinite;
        }

        .aurora.c {
            bottom: -18%;
            left: 25%;
            width: 44vmax;
            height: 44vmax;
            background: #a78bfa;
            animation: melayang-c 19s ease-in-out infinite;
        }

        @keyframes melayang-a {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(18vw, 14vh) scale(1.2);
            }
        }

        @keyframes melayang-b {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(-20vw, -10vh) scale(.85);
            }
        }

        @keyframes melayang-c {

            0%,
            100% {
                transform: translate(0, 0) scale(1.1);
            }

            50% {
                transform: translate(-14vw, -16vh) scale(.9);
            }
        }

        .gelembung i {
            position: absolute;
            bottom: -140px;
            display: block;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, .55), rgba(255, 255, 255, .08) 70%);
            border: 1px solid rgba(255, 255, 255, .25);
            animation: naik linear infinite;
        }

        .gelembung i:nth-child(1) {
            left: 5%;
            width: 60px;
            height: 60px;
            animation-duration: 13s;
        }

        .gelembung i:nth-child(2) {
            left: 14%;
            width: 110px;
            height: 110px;
            animation-duration: 19s;
            animation-delay: 3s;
        }

        .gelembung i:nth-child(3) {
            left: 27%;
            width: 40px;
            height: 40px;
            animation-duration: 11s;
            animation-delay: 1s;
        }

        .gelembung i:nth-child(4) {
            left: 38%;
            width: 85px;
            height: 85px;
            animation-duration: 17s;
            animation-delay: 6s;
        }

        .gelembung i:nth-child(5) {
            left: 52%;
            width: 50px;
            height: 50px;
            animation-duration: 12s;
            animation-delay: 2s;
        }

        .gelembung i:nth-child(6) {
            left: 63%;
            width: 120px;
            height: 120px;
            animation-duration: 21s;
            animation-delay: 4s;
        }

        .gelembung i:nth-child(7) {
            left: 75%;
            width: 45px;
            height: 45px;
            animation-duration: 12s;
            animation-delay: 7s;
        }

        .gelembung i:nth-child(8) {
            left: 84%;
            width: 90px;
            height: 90px;
            animation-duration: 16s;
            animation-delay: 1s;
        }

        .gelembung i:nth-child(9) {
            left: 93%;
            width: 55px;
            height: 55px;
            animation-duration: 14s;
            animation-delay: 5s;
        }

        @keyframes naik {
            0% {
                transform: translateY(0) translateX(0) scale(.8);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            50% {
                transform: translateY(-60vh) translateX(30px) scale(1);
            }

            100% {
                transform: translateY(-125vh) translateX(-20px) scale(1.1);
                opacity: 0;
            }
        }

        .bintang span {
            position: absolute;
            width: 4px;
            height: 4px;
            background: #fff;
            border-radius: 50%;
            animation: kedip 3s ease-in-out infinite;
        }

        .bintang span:nth-child(1) {
            top: 12%;
            left: 20%;
        }

        .bintang span:nth-child(2) {
            top: 22%;
            left: 72%;
            animation-delay: .6s;
        }

        .bintang span:nth-child(3) {
            top: 68%;
            left: 12%;
            animation-delay: 1.2s;
        }

        .bintang span:nth-child(4) {
            top: 80%;
            left: 82%;
            animation-delay: 1.8s;
        }

        .bintang span:nth-child(5) {
            top: 8%;
            left: 52%;
            animation-delay: 2.4s;
        }

        .bintang span:nth-child(6) {
            top: 48%;
            left: 92%;
            animation-delay: .3s;
        }

        @keyframes kedip {

            0%,
            100% {
                opacity: .15;
                transform: scale(.6);
            }

            50% {
                opacity: 1;
                transform: scale(1.8);
            }
        }

        /* ---------- Kartu kaca ---------- */
        .kartu {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 3.4rem 1.8rem 1.6rem;
            text-align: center;
            background: rgba(255, 255, 255, .14);
            border: 1.5px solid rgba(255, 255, 255, .38);
            border-radius: 28px;
            -webkit-backdrop-filter: blur(22px) saturate(150%);
            backdrop-filter: blur(22px) saturate(150%);
            box-shadow: 0 30px 70px -20px rgba(0, 0, 0, .55), inset 0 1px 0 rgba(255, 255, 255, .45);
            animation: muncul .7s cubic-bezier(.2, 1.3, .4, 1) both;
        }

        @keyframes muncul {
            from {
                opacity: 0;
                transform: translateY(40px) scale(.93);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        .ikon {
            position: absolute;
            top: -40px;
            left: 50%;
            display: grid;
            place-items: center;
            width: 80px;
            height: 80px;
            margin-left: -40px;
            color: #fff;
            border: 3px solid rgba(255, 255, 255, .9);
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            box-shadow: 0 12px 26px -6px rgba(236, 72, 153, .75), 0 0 0 8px rgba(255, 255, 255, .12);
            animation: ayun 3.2s ease-in-out infinite;
        }

        .ikon svg {
            width: 38px;
            height: 38px;
        }

        @keyframes ayun {

            0%,
            100% {
                transform: translateY(0) rotate(-5deg);
            }

            50% {
                transform: translateY(-7px) rotate(5deg);
            }
        }

        h1 {
            margin: 0 0 .25rem;
            font-size: 1.6rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, .25);
        }

        .sub {
            margin: 0 0 1.4rem;
            font-size: .95rem;
            color: rgba(255, 255, 255, .8);
        }

        /* ---------- Form ---------- */
        form {
            display: grid;
            gap: 1.05rem;
            text-align: left;
        }

        .grup {
            display: grid;
            gap: .4rem;
        }

        .grup>label {
            font-size: .88rem;
            font-weight: 700;
            color: rgba(255, 255, 255, .92);
        }

        .kolom {
            position: relative;
        }

        .kolom .kiri {
            position: absolute;
            top: 50%;
            left: 1rem;
            width: 20px;
            height: 20px;
            margin-top: -10px;
            color: rgba(255, 255, 255, .75);
            pointer-events: none;
        }

        .kolom input {
            display: block;
            width: 100%;
            padding: .9rem 1rem .9rem 2.8rem;
            font: inherit;
            color: #fff;
            background: rgba(255, 255, 255, .16);
            border: 2px solid rgba(255, 255, 255, .28);
            border-radius: 14px;
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .kolom input::placeholder {
            color: rgba(255, 255, 255, .55);
        }

        .kolom input:focus {
            background: rgba(255, 255, 255, .26);
            border-color: #fff;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, .22);
        }

        .kolom input:focus~.kiri {
            color: #fff;
        }

        .kolom input:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .kolom input:-webkit-autofill {
            -webkit-text-fill-color: #fff;
            transition: background-color 9999s ease-out 0s;
            -webkit-box-shadow: 0 0 0 40px rgba(99, 102, 241, .65) inset;
        }

        .kolom.sandi input {
            padding-right: 3.2rem;
        }

        .mata {
            position: absolute;
            top: 50%;
            right: .45rem;
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            margin-top: -19px;
            padding: 0;
            color: rgba(255, 255, 255, .85);
            cursor: pointer;
            background: transparent;
            border: 0;
            border-radius: 10px;
            transition: background .15s, color .15s;
        }

        .mata:hover {
            color: #fff;
            background: rgba(255, 255, 255, .2);
        }

        .mata:focus-visible {
            outline: 3px solid #fde047;
            outline-offset: 1px;
        }

        .mata svg {
            width: 22px;
            height: 22px;
        }

        .mata .tutup {
            display: none;
        }

        .mata.tampil .buka {
            display: none;
        }

        .mata.tampil .tutup {
            display: block;
        }

        .galat {
            margin: 0;
            padding: .75rem 1rem;
            font-size: .92rem;
            font-weight: 600;
            color: #7f1d1d;
            background: #fee2e2;
            border-left: 5px solid #ef4444;
            border-radius: 12px;
            animation: getar .45s ease;
        }

        @keyframes getar {

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

        .kunci {
            margin: 0;
            padding: .8rem 1rem;
            font-size: .92rem;
            font-weight: 600;
            text-align: center;
            color: #78350f;
            background: #fef3c7;
            border: 2px dashed #f59e0b;
            border-radius: 12px;
        }

        .kunci b {
            font-size: 1.15rem;
        }

        .masuk {
            position: relative;
            overflow: hidden;
            margin-top: .3rem;
            padding: .95rem 1rem;
            font: inherit;
            font-size: 1.1rem;
            font-weight: 800;
            color: #5b21b6;
            cursor: pointer;
            border: 0;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 5px 0 rgba(0, 0, 0, .25), 0 14px 26px -10px rgba(0, 0, 0, .5);
            transition: transform .12s, box-shadow .12s, filter .2s;
        }

        .masuk::after {
            content: "";
            position: absolute;
            top: 0;
            left: -80%;
            width: 50%;
            height: 100%;
            background: linear-gradient(100deg, transparent, rgba(124, 58, 237, .28), transparent);
            transform: skewX(-20deg);
            animation: kilau 3.2s ease-in-out infinite;
        }

        @keyframes kilau {

            0%,
            60% {
                left: -80%;
            }

            100% {
                left: 140%;
            }
        }

        .masuk:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 7px 0 rgba(0, 0, 0, .25), 0 18px 30px -10px rgba(0, 0, 0, .5);
        }

        .masuk:active:not(:disabled) {
            transform: translateY(4px);
            box-shadow: 0 1px 0 rgba(0, 0, 0, .25);
        }

        .masuk:disabled {
            cursor: not-allowed;
            filter: grayscale(1) opacity(.7);
        }

        .masuk:disabled::after {
            display: none;
        }

        .masuk:focus-visible {
            outline: 4px solid #fde047;
            outline-offset: 3px;
        }

        .kembali {
            margin: 1.3rem 0 0;
            text-align: center;
        }

        .pemilik {
            margin: 1.1rem 0 0;
            font-size: .85rem;
            text-align: center;
            color: rgba(255, 255, 255, .78);
        }

        .pemilik strong {
            font-weight: 800;
            color: #fff;
            text-shadow: 0 0 14px rgba(255, 255, 255, .6);
        }

        .pemilik .hati {
            display: inline-block;
            animation: detak 1.4s ease-in-out infinite;
        }

        @keyframes detak {

            0%,
            100% {
                transform: scale(1);
            }

            15% {
                transform: scale(1.3);
            }

            30% {
                transform: scale(1);
            }

            45% {
                transform: scale(1.2);
            }
        }

        .kembali a {
            display: inline-block;
            padding: .45rem 1rem;
            font-size: .9rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            background: rgba(255, 255, 255, .16);
            border-radius: 999px;
            transition: transform .15s, background .15s;
        }

        .kembali a:hover {
            background: rgba(255, 255, 255, .3);
            transform: translateX(-3px);
        }

        .kembali a:focus-visible {
            outline: 3px solid #fde047;
            outline-offset: 2px;
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body>

    <!-- Latar bergerak -->
    <div class="latar" aria-hidden="true">
        <div class="aurora a"></div>
        <div class="aurora b"></div>
        <div class="aurora c"></div>
        <div class="gelembung"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
        <div class="bintang"><span></span><span></span><span></span><span></span><span></span><span></span></div>
    </div>

    <main class="kartu">
        <div class="ikon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="4" y="11" width="16" height="10" rx="2.5" />
                <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                <circle cx="12" cy="16" r="1.2" fill="currentColor" />
            </svg>
        </div>

        <h1>Masuk Admin</h1>
        <p class="sub">Khusus untuk guru dan orang tua</p>

        <form method="post" autocomplete="off" id="formMasuk">
            <?= csrf_field() ?>

            <?php if ($galat !== ''): ?>
                <p class="galat" role="alert"><?= e($galat) ?></p>
            <?php endif; ?>

            <?php if ($sisaKunci > 0): ?>
                <p class="kunci" id="kunci" role="status">
                    Login dikunci sementara. Coba lagi dalam <b id="hitung"><?= $sisaKunci ?></b> detik.
                </p>
            <?php endif; ?>

            <div class="grup">
                <label for="username">Username</label>
                <div class="kolom">
                    <input type="text" id="username" name="username" value="<?= e($userIsi) ?>"
                        placeholder="Masukkan username" required autofocus autocomplete="username" <?= $mati ?>>
                    <svg class="kiri" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 21a8 8 0 0 1 16 0" />
                    </svg>
                </div>
            </div>

            <div class="grup">
                <label for="sandi">Password</label>
                <div class="kolom sandi">
                    <input type="password" id="sandi" name="password" placeholder="Masukkan password"
                        required autocomplete="current-password" <?= $mati ?>>
                    <svg class="kiri" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="8" cy="15" r="4" />
                        <path d="M11 12l9-9M16 7l3 3M14 9l2 2" />
                    </svg>
                    <button type="button" class="mata" id="mata" aria-label="Tampilkan password" title="Tampilkan password">
                        <svg class="buka" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg class="tutup" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17.9 17.9A10.9 10.9 0 0 1 12 19c-6.4 0-10-7-10-7a18.7 18.7 0 0 1 5.1-5.9M9.9 5.2A9.8 9.8 0 0 1 12 5c6.4 0 10 7 10 7a18.8 18.8 0 0 1-2.2 3.2M1 1l22 22" />
                            <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="masuk" id="tombolMasuk" <?= $mati ?>>Masuk &rarr;</button>
        </form>

        <p class="kembali"><a href="<?= e(url('index.php')) ?>">&larr; Kembali ke aplikasi anak</a></p>

        <p class="pemilik">
            Dibuat dengan <span class="hati" aria-hidden="true">❤️</span> oleh
            <strong><?= e(PEMILIK) ?></strong> &middot; &copy; <?= date('Y') ?>
        </p>
    </main>

    <script>
        (function() {
            // Tampilkan / sembunyikan password
            var sandi = document.getElementById('sandi'),
                mata = document.getElementById('mata');
            mata.addEventListener('click', function() {
                var tampil = sandi.type === 'password';
                sandi.type = tampil ? 'text' : 'password';
                mata.classList.toggle('tampil', tampil);
                mata.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
                mata.title = mata.getAttribute('aria-label');
            });

            // Hitung mundur saat dikunci; buka kembali form setelah selesai
            var hitung = document.getElementById('hitung');
            if (!hitung) return;
            var sisa = parseInt(hitung.textContent, 10);
            var form = document.getElementById('formMasuk');
            var timer = setInterval(function() {
                sisa--;
                if (sisa > 0) {
                    hitung.textContent = sisa;
                    return;
                }
                clearInterval(timer);
                document.getElementById('kunci').remove();
                form.querySelectorAll('input, #tombolMasuk').forEach(function(el) {
                    el.disabled = false;
                });
                document.getElementById('username').focus();
            }, 1000);
        })();
    </script>
</body>

</html>