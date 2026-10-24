<?php

declare(strict_types=1);

/**
 * HALAMAN AWAL
 * Anak mengetik nama panggilan, tanpa login/password.
 * Nama disimpan di tabel `siswa` (hanya nama) dan di sesi PHP.
 */

require_once __DIR__ . '/includes/fungsi.php';

const PEMILIK = 'Raihan Fadila';   // nama pemilik website (tampil di bagian bawah kartu)

/* ---------------------------------------------------------------
 * PROSES FORM
 * ------------------------------------------------------------- */

$pesan   = '';
$namaIsi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_wajib();

    $namaIsi = (string) ($_POST['nama'] ?? '');
    $cek     = nama_bersih($namaIsi);

    if (!$cek['ok']) {
        $pesan = $cek['pesan'];
    } else {
        // Nama yang sama dipakai ulang supaya bintang anak tidak hilang saat kembali.
        $st = db()->prepare('SELECT id, nama FROM siswa WHERE nama = ? ORDER BY id DESC LIMIT 1');
        $st->execute([$cek['nama']]);
        $siswa = $st->fetch();

        if ($siswa) {
            db()->prepare('UPDATE siswa SET terakhir_aktif = NOW() WHERE id = ?')
                ->execute([$siswa['id']]);
            $id   = (int) $siswa['id'];
            $nama = $siswa['nama'];
        } else {
            db()->prepare('INSERT INTO siswa (nama) VALUES (?)')->execute([$cek['nama']]);
            $id   = (int) db()->lastInsertId();
            $nama = $cek['nama'];
        }

        session_regenerate_id(true);
        $_SESSION['siswa_id']   = $id;
        $_SESSION['siswa_nama'] = $nama;
        unset($_SESSION['kuis']);

        redirect(url('mapel.php'));
    }
}

/* ---------------------------------------------------------------
 * DATA TAMPILAN
 * ------------------------------------------------------------- */

$sudah     = siswa_aktif();
$judul     = 'Selamat Datang';
$bodyKelas = 'halaman-awal';
$tanpaBar  = true;

/** Hiasan melayang: [emoji, posisi kiri %, ukuran rem, durasi detik, jeda detik] */
$hiasan = [
    ['⭐', 6, 2.2, 7, 0],
    ['📚', 18, 2.0, 9, 1.5],
    ['✏️', 32, 1.8, 8, 3],
    ['🌈', 48, 2.4, 10, .5],
    ['🎈', 64, 2.2, 9, 2],
    ['🔢', 78, 1.9, 8, 4],
    ['🌟', 90, 2.0, 7, 1],
];

require __DIR__ . '/includes/header.php';
?>

<style>
    .awl {
        --awl-biru: #3b82f6;
        --awl-ungu: #8b5cf6;
        --awl-pink: #ec4899;
        --awl-kuning: #fbbf24;
        --awl-teks: #1e293b;
        position: relative;
        isolation: isolate;
        overflow: hidden;
    }

    /* Latar & hiasan melayang */
    .awl-latar {
        position: fixed;
        inset: 0;
        z-index: -2;
        background: linear-gradient(160deg, #dbeafe 0%, #ede9fe 45%, #fce7f3 100%);
    }

    .awl-hiasan {
        position: fixed;
        inset: 0;
        z-index: -1;
        pointer-events: none;
        overflow: hidden;
    }

    .awl-hiasan span {
        position: absolute;
        bottom: -4rem;
        opacity: .75;
        animation: awl-naik linear infinite;
    }

    @keyframes awl-naik {
        0% {
            transform: translateY(0) rotate(0deg);
            opacity: 0;
        }

        10% {
            opacity: .8;
        }

        90% {
            opacity: .8;
        }

        100% {
            transform: translateY(-115vh) rotate(360deg);
            opacity: 0;
        }
    }

    /* Kartu sambutan */
    .awl-kartu {
        position: relative;
        max-width: 560px;
        margin: 1.5rem auto;
        padding: 2rem 1.5rem 1.8rem;
        text-align: center;
        color: var(--awl-teks);
        background: rgba(255, 255, 255, .92);
        border: 4px solid #fff;
        border-radius: 32px;
        box-shadow: 0 20px 50px -15px rgba(99, 102, 241, .45);
        animation: awl-muncul .7s cubic-bezier(.2, 1.4, .4, 1) both;
    }

    @keyframes awl-muncul {
        from {
            opacity: 0;
            transform: translateY(40px) scale(.9);
        }

        to {
            opacity: 1;
            transform: none;
        }
    }

    /* Maskot */
    .awl-maskot-wadah {
        position: relative;
        display: inline-block;
        margin-bottom: .4rem;
    }

    .awl-maskot {
        display: block;
        width: 160px;
        height: 160px;
        max-width: 45vw;
        height: auto;
        filter: drop-shadow(0 10px 12px rgba(0, 0, 0, .18));
        animation: awl-loncat 2.4s ease-in-out infinite;
        transform-origin: bottom center;
    }

    @keyframes awl-loncat {

        0%,
        100% {
            transform: translateY(0) scale(1, 1);
        }

        45% {
            transform: translateY(-18px) scale(.97, 1.04);
        }

        55% {
            transform: translateY(-18px) scale(.97, 1.04);
        }

        90% {
            transform: translateY(0) scale(1.05, .94);
        }
    }

    .awl-bayangan {
        width: 110px;
        height: 12px;
        margin: -6px auto 0;
        border-radius: 50%;
        background: rgba(0, 0, 0, .12);
        animation: awl-bayang 2.4s ease-in-out infinite;
    }

    @keyframes awl-bayang {

        0%,
        100% {
            transform: scale(1);
            opacity: .5;
        }

        50% {
            transform: scale(.7);
            opacity: .25;
        }
    }

    .awl-bintang {
        position: absolute;
        font-size: 1.5rem;
        animation: awl-kedip 1.8s ease-in-out infinite;
    }

    .awl-bintang.a {
        top: 6px;
        left: -26px;
    }

    .awl-bintang.b {
        top: 30px;
        right: -28px;
        animation-delay: .6s;
    }

    .awl-bintang.c {
        bottom: 28px;
        left: -20px;
        animation-delay: 1.1s;
    }

    @keyframes awl-kedip {

        0%,
        100% {
            transform: scale(.6) rotate(0);
            opacity: .4;
        }

        50% {
            transform: scale(1.2) rotate(25deg);
            opacity: 1;
        }
    }

    /* Judul */
    .awl-kartu h1 {
        margin: .6rem 0 .3rem;
        font-size: clamp(1.6rem, 6vw, 2.2rem);
        line-height: 1.25;
    }

    .awl-lambai {
        display: inline-block;
        transform-origin: 70% 70%;
        animation: awl-lambai 2.2s ease-in-out infinite;
    }

    @keyframes awl-lambai {

        0%,
        60%,
        100% {
            transform: rotate(0);
        }

        10%,
        30% {
            transform: rotate(18deg);
        }

        20%,
        40% {
            transform: rotate(-12deg);
        }

        50% {
            transform: rotate(10deg);
        }
    }

    .awl-kartu .sorot {
        background: linear-gradient(90deg, var(--awl-biru), var(--awl-ungu), var(--awl-pink), var(--awl-biru));
        background-size: 250% 100%;
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        -webkit-text-fill-color: transparent;
        animation: awl-warna 4s linear infinite;
    }

    @keyframes awl-warna {
        to {
            background-position: 250% 0;
        }
    }

    .awl-tanya {
        margin: 0 0 1rem;
        font-size: 1.25rem;
        font-weight: 700;
        color: #475569;
    }

    /* Form */
    .awl-form {
        display: grid;
        gap: .9rem;
    }

    .awl-input {
        width: 100%;
        padding: 1rem 1.2rem;
        font: inherit;
        font-size: 1.25rem;
        text-align: center;
        color: var(--awl-teks);
        background: #f8fafc;
        border: 3px dashed #c4b5fd;
        border-radius: 20px;
        outline: none;
        transition: border-color .2s, box-shadow .2s, transform .2s, background .2s;
    }

    .awl-input::placeholder {
        color: #a5b4fc;
    }

    .awl-input:focus {
        background: #fff;
        border-style: solid;
        border-color: var(--awl-ungu);
        box-shadow: 0 0 0 6px rgba(139, 92, 246, .18);
        transform: scale(1.02);
    }

    .awl-pesan {
        margin: 0;
        padding: .6rem 1rem;
        font-weight: 600;
        color: #b91c1c;
        background: #fee2e2;
        border-radius: 14px;
        animation: awl-getar .45s ease;
    }

    @keyframes awl-getar {

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

    /* Tombol mulai */
    .awl-mulai {
        position: relative;
        overflow: hidden;
        padding: 1rem 1.5rem;
        font: inherit;
        font-size: 1.35rem;
        font-weight: 800;
        color: #fff;
        cursor: pointer;
        border: 0;
        border-radius: 999px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        box-shadow: 0 6px 0 #4c1d95, 0 14px 24px -8px rgba(76, 29, 149, .6);
        transition: transform .12s ease, box-shadow .12s ease;
        animation: awl-denyut 2s ease-in-out infinite;
    }

    .awl-mulai::after {
        content: "";
        position: absolute;
        top: 0;
        left: -80%;
        width: 50%;
        height: 100%;
        background: linear-gradient(100deg, transparent, rgba(255, 255, 255, .55), transparent);
        transform: skewX(-20deg);
        animation: awl-kilau 3s ease-in-out infinite;
    }

    @keyframes awl-denyut {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.04);
        }
    }

    @keyframes awl-kilau {

        0%,
        60% {
            left: -80%;
        }

        100% {
            left: 140%;
        }
    }

    .awl-mulai:hover {
        animation-play-state: paused;
        transform: translateY(-2px) scale(1.04);
    }

    .awl-mulai:active {
        animation: none;
        transform: translateY(5px);
        box-shadow: 0 1px 0 #4c1d95;
    }

    .awl-mulai:focus-visible {
        outline: 4px solid var(--awl-kuning);
        outline-offset: 3px;
    }

    .awl-roket {
        display: inline-block;
        transition: transform .3s;
    }

    .awl-mulai:hover .awl-roket {
        transform: translate(6px, -6px) rotate(8deg);
    }

    /* Lanjut sebagai */
    .awl-lanjut {
        margin: 1.1rem 0 0;
        color: #64748b;
    }

    .awl-lanjut a {
        display: inline-block;
        padding: .25rem .8rem;
        color: #6d28d9;
        text-decoration: none;
        background: #ede9fe;
        border-radius: 999px;
        transition: transform .15s, background .15s;
    }

    .awl-lanjut a:hover {
        background: #ddd6fe;
        transform: scale(1.06);
    }

    /* Tautan admin */
    .awl-admin {
        margin-top: 1.4rem;
        padding-top: 1rem;
        border-top: 2px dotted #e2e8f0;
    }

    .awl-admin a {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .5rem 1.1rem;
        font-size: .95rem;
        font-weight: 700;
        color: #475569;
        text-decoration: none;
        background: #f1f5f9;
        border: 2px solid #e2e8f0;
        border-radius: 999px;
        transition: transform .15s, background .15s, color .15s, border-color .15s;
    }

    .awl-admin a:hover {
        color: #fff;
        background: #334155;
        border-color: #334155;
        transform: translateY(-2px);
    }

    .awl-admin a:focus-visible {
        outline: 3px solid var(--awl-ungu);
        outline-offset: 2px;
    }

    /* Pemilik website */
    .awl-pemilik {
        margin: 1.1rem 0 0;
        font-size: .88rem;
        color: #64748b;
    }

    .awl-pemilik strong {
        font-weight: 800;
        background: linear-gradient(90deg, var(--awl-biru), var(--awl-ungu), var(--awl-pink), var(--awl-biru));
        background-size: 250% 100%;
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        -webkit-text-fill-color: transparent;
        animation: awl-warna 4s linear infinite;
    }

    .awl-hati {
        display: inline-block;
        animation: awl-detak 1.4s ease-in-out infinite;
    }

    @keyframes awl-detak {

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

    .awl .sr-saja {
        position: absolute;
        width: 1px;
        height: 1px;
        margin: -1px;
        padding: 0;
        overflow: hidden;
        clip: rect(0 0 0 0);
        white-space: nowrap;
        border: 0;
    }

    /* Hormati pengguna yang tidak mau animasi */
    @media (prefers-reduced-motion: reduce) {

        .awl *,
        .awl *::before,
        .awl *::after {
            animation: none !important;
            transition: none !important;
        }

        .awl-hiasan {
            display: none;
        }
    }
</style>

<div class="awl">
    <div class="awl-latar" aria-hidden="true"></div>

    <div class="awl-hiasan" aria-hidden="true">
        <?php foreach ($hiasan as [$emoji, $kiri, $ukuran, $durasi, $jeda]): ?>
            <span style="left:<?= $kiri ?>%;font-size:<?= $ukuran ?>rem;animation-duration:<?= $durasi ?>s;animation-delay:<?= $jeda ?>s">
                <?= $emoji ?>
            </span>
        <?php endforeach; ?>
    </div>

    <section class="awl-kartu sambutan kartu">
        <div class="awl-maskot-wadah">
            <img class="awl-maskot sambutan-maskot"
                src="<?= e(gambar_url('maskot.svg')) ?>"
                alt="Maskot Bobi" width="160" height="160">
            <div class="awl-bayangan"></div>
            <span class="awl-bintang a" aria-hidden="true">✨</span>
            <span class="awl-bintang b" aria-hidden="true">⭐</span>
            <span class="awl-bintang c" aria-hidden="true">💫</span>
        </div>

        <h1>
            Halo! <span class="awl-lambai" aria-hidden="true">👋</span><br>
            Selamat datang di <span class="sorot"><?= e(APP_NAMA) ?></span>!
        </h1>
        <p class="awl-tanya">Siapa namamu?</p>

        <form method="post" class="awl-form form-nama" autocomplete="off">
            <?= csrf_field() ?>

            <label for="nama" class="sr-saja">Namamu</label>
            <input type="text" id="nama" name="nama" class="awl-input"
                maxlength="<?= NAMA_MAKS ?>" placeholder="Ketik namamu di sini"
                value="<?= e($namaIsi) ?>" required autofocus>

            <?php if ($pesan !== ''): ?>
                <p class="awl-pesan" role="alert"><?= e($pesan) ?></p>
            <?php endif; ?>

            <button type="submit" class="awl-mulai">
                Mulai Belajar <span class="awl-roket" aria-hidden="true">🚀</span>
            </button>
        </form>

        <?php if ($sudah): ?>
            <p class="awl-lanjut lanjut-kecil">
                Atau lanjut sebagai
                <a href="<?= e(url('mapel.php')) ?>"><strong><?= e($sudah['nama']) ?></strong> ➜</a>
            </p>
        <?php endif; ?>

        <div class="awl-admin">
            <a href="<?= e(url('admin/dashboard.php')) ?>">🔐 Masuk Admin</a>
        </div>

        <p class="awl-pemilik">
            Dibuat dengan <span class="awl-hati" aria-hidden="true">❤️</span> oleh
            <strong><?= e(PEMILIK) ?></strong> · © <?= date('Y') ?>
        </p>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>