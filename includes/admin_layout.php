<?php

/**
 * TATA LETAK HALAMAN ADMIN (dipakai semua file di folder admin/)
 *
 *   admin_kepala('Judul', 'menu-aktif');
 *   ...isi halaman...
 *   admin_kaki();
 */

/* ===============================================================
 * KEPALA & KAKI HALAMAN
 * ============================================================= */

function admin_kepala(string $judul, string $aktif = ''): void
{
    // kunci => [label, berkas, ikon]
    $menu = [
        'dashboard' => ['Dashboard',   'dashboard.php', '🏠'],
        'mapel'     => ['Mapel',       'mapel.php',     '📚'],
        'materi'    => ['Materi',      'materi.php',    '📖'],
        'soal'      => ['Soal',        'soal.php',      '❓'],
        'hasil'     => ['Hasil Siswa', 'hasil.php',     '🏆'],
        'akun'      => ['Akun',        'akun.php',      '👤'],
    ];
    $admin = admin_aktif();
    $flash = flash_ambil();
?>
    <!doctype html>
    <html lang="id">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex,nofollow">
        <meta name="theme-color" content="#4c1d95">
        <title><?= e($judul) ?> · Admin <?= e(APP_NAMA) ?></title>
        <link rel="icon" href="<?= e(gambar_url('maskot.svg')) ?>" type="image/svg+xml">
        <link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">

        <style>
            :root {
                --a-ungu: #7c3aed;
                --a-ungu-tua: #4c1d95;
                --a-pink: #db2777;
                --a-teks: #1f2937;
                --a-redup: #6b7280;
                --a-garis: #e5e7eb;
                --a-latar: #f4f5fb;
                --a-radius: 16px;
            }

            body {
                margin: 0;
                color: var(--a-teks);
                font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background:
                    radial-gradient(900px 400px at 100% -5%, rgba(236, 72, 153, .10), transparent 60%),
                    radial-gradient(800px 400px at -5% 5%, rgba(99, 102, 241, .12), transparent 60%),
                    var(--a-latar);
                background-attachment: fixed;
            }

            /* ---------- Bilah atas ---------- */
            .a-bar {
                position: sticky;
                top: 0;
                z-index: 50;
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: .65rem 1.4rem;
                color: #fff;
                background: linear-gradient(90deg, #312e81 0%, #6d28d9 55%, #be185d 100%);
                box-shadow: 0 8px 24px -10px rgba(76, 29, 149, .7);
            }

            .a-logo {
                display: inline-flex;
                align-items: center;
                gap: .5rem;
                font-size: 1.15rem;
                letter-spacing: .01em;
                white-space: nowrap;
            }

            .a-logo::before {
                content: "🎓";
                display: grid;
                place-items: center;
                width: 38px;
                height: 38px;
                font-size: 1.25rem;
                background: rgba(255, 255, 255, .18);
                border-radius: 12px;
                animation: a-ayun 3.5s ease-in-out infinite;
            }

            @keyframes a-ayun {

                0%,
                100% {
                    transform: rotate(-6deg);
                }

                50% {
                    transform: rotate(6deg) translateY(-2px);
                }
            }

            .a-menu {
                display: flex;
                flex: 1;
                gap: .25rem;
                min-width: 0;
                overflow-x: auto;
                scrollbar-width: none;
            }

            .a-menu::-webkit-scrollbar {
                display: none;
            }

            .a-menu a {
                display: inline-flex;
                align-items: center;
                gap: .4rem;
                padding: .5rem .95rem;
                font-size: .93rem;
                font-weight: 700;
                color: rgba(255, 255, 255, .88);
                text-decoration: none;
                white-space: nowrap;
                border-radius: 999px;
                transition: background .18s, color .18s, transform .18s;
            }

            .a-menu a:hover {
                color: #fff;
                background: rgba(255, 255, 255, .2);
                transform: translateY(-1px);
            }

            .a-menu a.aktif {
                color: var(--a-ungu-tua);
                background: #fff;
                box-shadow: 0 4px 12px -4px rgba(0, 0, 0, .35);
            }

            .a-menu a:focus-visible,
            .a-kanan a:focus-visible,
            .a-bar .tombol:focus-visible {
                outline: 3px solid #fde047;
                outline-offset: 2px;
            }

            .a-ikon {
                font-size: 1.05em;
                line-height: 1;
            }

            .a-kanan {
                display: flex;
                align-items: center;
                gap: .7rem;
                margin-left: auto;
                white-space: nowrap;
            }

            .a-kanan>a {
                padding: .45rem .9rem;
                font-size: .88rem;
                font-weight: 700;
                color: #fff;
                text-decoration: none;
                background: rgba(255, 255, 255, .15);
                border-radius: 999px;
                transition: background .18s;
            }

            .a-kanan>a:hover {
                background: rgba(255, 255, 255, .28);
            }

            .a-bar .tombol {
                padding: .45rem .95rem;
                font-size: .88rem;
                color: #9d174d;
                background: #fff;
                box-shadow: 0 3px 0 rgba(0, 0, 0, .22);
            }

            .a-bar .tombol:hover {
                transform: translateY(-1px);
                box-shadow: 0 5px 0 rgba(0, 0, 0, .22);
            }

            @media (max-width: 860px) {
                .a-bar {
                    flex-wrap: wrap;
                    gap: .5rem .8rem;
                    padding: .6rem 1rem;
                }

                .a-menu {
                    order: 3;
                    flex-basis: 100%;
                    padding-bottom: .15rem;
                }

                .a-kanan>a {
                    display: none;
                }
            }

            /* ---------- Area isi ---------- */
            .a-isi {
                max-width: 1180px;
                margin: 0 auto;
                padding: 1.6rem 1.2rem 3rem;
                animation: a-masuk .4s ease both;
            }

            @keyframes a-masuk {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: none;
                }
            }

            .a-isi>h1 {
                display: inline-block;
                margin: 0 0 1.2rem;
                padding-bottom: .3rem;
                font-size: 1.7rem;
                border-bottom: 5px solid transparent;
                border-image: linear-gradient(90deg, #6366f1, #ec4899) 1;
            }

            /* Halaman yang sudah punya banner sendiri: judul h1 disembunyikan (tetap ada untuk pembaca layar) */
            .a-isi:has(> .dsb, > .mpl, > .mtr)>h1 {
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

            /* ---------- Pesan (flash) ---------- */
            .a-flash {
                position: relative;
                display: flex;
                align-items: center;
                gap: .7rem;
                margin: 0 0 1.2rem;
                padding: .85rem 1.1rem;
                font-weight: 600;
                color: #1e3a8a;
                background: #dbeafe;
                border-left: 6px solid #3b82f6;
                border-radius: 14px;
                box-shadow: 0 8px 20px -14px rgba(0, 0, 0, .5);
                transition: opacity .35s, transform .35s;
            }

            .a-isi>.a-flash {
                animation: a-geser .45s cubic-bezier(.2, 1.3, .4, 1) both;
            }

            .a-flash.hilang {
                opacity: 0;
                transform: translateY(-8px);
            }

            .a-flash-ok {
                color: #065f46;
                background: #d1fae5;
                border-left-color: #10b981;
            }

            .a-flash-galat {
                color: #991b1b;
                background: #fee2e2;
                border-left-color: #ef4444;
            }

            .a-flash .a-pesan {
                flex: 1;
            }

            .a-flash .a-tutup {
                width: 30px;
                height: 30px;
                font-size: 1.2rem;
                line-height: 1;
                color: inherit;
                cursor: pointer;
                background: rgba(0, 0, 0, .07);
                border: 0;
                border-radius: 50%;
                transition: background .15s;
            }

            .a-flash .a-tutup:hover {
                background: rgba(0, 0, 0, .16);
            }

            @keyframes a-geser {
                from {
                    opacity: 0;
                    transform: translateX(-24px);
                }

                to {
                    opacity: 1;
                    transform: none;
                }
            }

            /* ---------- Komponen umum (untuk halaman yang belum punya gaya sendiri) ---------- */
            .tombol {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: .4rem;
                padding: .6rem 1.2rem;
                font: inherit;
                font-weight: 800;
                color: #fff;
                text-decoration: none;
                cursor: pointer;
                border: 0;
                border-radius: 999px;
                background: linear-gradient(135deg, #6366f1, #8b5cf6);
                box-shadow: 0 4px 0 #4c1d95;
                transition: transform .12s, box-shadow .12s, filter .15s;
            }

            .tombol:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 0 #4c1d95;
            }

            .tombol:active {
                transform: translateY(3px);
                box-shadow: 0 1px 0 #4c1d95;
            }

            .tombol.kecil {
                padding: .4rem .9rem;
                font-size: .85rem;
            }

            .tombol.abu {
                color: #374151;
                background: #e5e7eb;
                box-shadow: 0 4px 0 #9ca3af;
            }

            .tombol.hijau {
                background: linear-gradient(135deg, #10b981, #059669);
                box-shadow: 0 4px 0 #047857;
            }

            .tombol.bahaya {
                background: linear-gradient(135deg, #f43f5e, #dc2626);
                box-shadow: 0 4px 0 #991b1b;
            }

            .tombol.abu:hover {
                box-shadow: 0 6px 0 #9ca3af;
            }

            .tombol.hijau:hover {
                box-shadow: 0 6px 0 #047857;
            }

            .tombol.bahaya:hover {
                box-shadow: 0 6px 0 #991b1b;
            }

            form.inline {
                display: inline;
                margin: 0;
            }

            .kartu {
                padding: 1.3rem 1.4rem;
                margin-bottom: 1.4rem;
                background: #fff;
                border: 1px solid var(--a-garis);
                border-radius: var(--a-radius);
                box-shadow: 0 10px 30px -20px rgba(0, 0, 0, .35);
            }

            .gulir {
                margin-bottom: 1.4rem;
                overflow-x: auto;
                background: #fff;
                border: 1px solid var(--a-garis);
                border-radius: var(--a-radius);
                box-shadow: 0 10px 30px -20px rgba(0, 0, 0, .35);
            }

            .gulir table {
                width: 100%;
                border-collapse: collapse;
            }

            .gulir th {
                padding: .75rem 1rem;
                font-size: .78rem;
                letter-spacing: .04em;
                text-align: left;
                text-transform: uppercase;
                color: var(--a-redup);
                background: #f9fafb;
                border-bottom: 1px solid var(--a-garis);
            }

            .gulir td {
                padding: .8rem 1rem;
                vertical-align: middle;
                border-bottom: 1px solid #f3f4f6;
            }

            .gulir tr:last-child td {
                border-bottom: 0;
            }

            .gulir tbody tr:hover td,
            .gulir tr:hover>td {
                background: #faf5ff;
            }

            .label {
                display: inline-block;
                padding: .15rem .65rem;
                font-size: .75rem;
                font-weight: 800;
                color: #4b5563;
                background: #e5e7eb;
                border-radius: 999px;
            }

            .label.hijau {
                color: #065f46;
                background: #d1fae5;
            }

            .label.kuning {
                color: #92400e;
                background: #fef3c7;
            }

            .label.merah {
                color: #991b1b;
                background: #fee2e2;
            }

            .bantuan {
                color: var(--a-redup);
                font-size: .9rem;
            }

            .gambar-kecil {
                max-height: 56px;
                border-radius: 10px;
                vertical-align: middle;
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
        <?php if ($admin): ?>
            <header class="a-bar">
                <strong class="a-logo">Admin Panel</strong>

                <nav class="a-menu" aria-label="Menu admin">
                    <?php foreach ($menu as $kunci => [$label, $berkas, $ikon]): ?>
                        <a href="<?= e($berkas) ?>" <?= $kunci === $aktif ? ' class="aktif" aria-current="page"' : '' ?>>
                            <span class="a-ikon" aria-hidden="true"><?= $ikon ?></span><?= e($label) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="a-kanan">
                    <a href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">Lihat aplikasi ↗</a>
                    <form method="post" action="logout.php" class="inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="tombol kecil">Keluar (<?= e($admin['username']) ?>)</button>
                    </form>
                </div>
            </header>
        <?php endif; ?>

        <main class="a-isi">
            <?php if ($flash): ?>
                <?php $ikonFlash = ['ok' => '✅', 'galat' => '⚠️'][$flash['tipe']] ?? 'ℹ️'; ?>
                <p class="a-flash a-flash-<?= e($flash['tipe']) ?>" role="status" data-flash="<?= e($flash['tipe']) ?>">
                    <span aria-hidden="true"><?= $ikonFlash ?></span>
                    <span class="a-pesan"><?= e($flash['pesan']) ?></span>
                    <button type="button" class="a-tutup" aria-label="Tutup pesan">×</button>
                </p>
            <?php endif; ?>

            <h1><?= e($judul) ?></h1>
        <?php
    }

    function admin_kaki(): void
    {
        ?>
        </main>

        <script>
            // Pesan (flash): bisa ditutup, dan yang sukses hilang sendiri setelah 6 detik.
            (function() {
                var pesan = document.querySelector('.a-isi > .a-flash[data-flash]');
                if (!pesan) return;

                function tutup() {
                    pesan.classList.add('hilang');
                    setTimeout(function() {
                        pesan.remove();
                    }, 380);
                }

                pesan.querySelector('.a-tutup').addEventListener('click', tutup);
                if (pesan.getAttribute('data-flash') === 'ok') setTimeout(tutup, 6000);
            })();
        </script>
        <script src="<?= e(url('assets/js/admin.js')) ?>"></script>
    </body>

    </html>
<?php
    }

/* ===============================================================
 * BANTUAN: SLUG
 * ============================================================= */

    /** Buat slug dari nama (huruf kecil, tanda hubung). */
    function buat_slug(string $nama): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $nama), '-'));

        return $slug !== '' ? $slug : 'mapel-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }

    /** Pastikan slug unik di tabel mapel (abaikan baris dengan id tertentu saat edit). */
    function slug_unik(string $slug, int $kecualiId = 0): string
    {
        $dasar = $slug;
        $n     = 2;

        while (true) {
            $st = db()->prepare('SELECT COUNT(*) FROM mapel WHERE slug = ? AND id <> ?');
            $st->execute([$slug, $kecualiId]);

            if ((int) $st->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $dasar . '-' . $n++;
        }
    }

    /* ===============================================================
 * BANTUAN: BERKAS GAMBAR
 * Kumpulkan semua berkas gambar milik satu mapel / materi / soal
 * (untuk dihapus bersama datanya).
 * ============================================================= */

    function daftar_gambar_soal(int $soalId): array
    {
        $st = db()->prepare(
            'SELECT gambar FROM soal WHERE id = ?
         UNION ALL
         SELECT gambar FROM pilihan_jawaban WHERE soal_id = ?'
        );
        $st->execute([$soalId, $soalId]);

        return array_values(array_filter($st->fetchAll(PDO::FETCH_COLUMN)));
    }

    function daftar_gambar_materi(int $materiId): array
    {
        $st = db()->prepare(
            'SELECT gambar FROM materi WHERE id = ?
         UNION ALL
         SELECT s.gambar FROM soal s WHERE s.materi_id = ?
         UNION ALL
         SELECT p.gambar FROM pilihan_jawaban p JOIN soal s ON s.id = p.soal_id WHERE s.materi_id = ?'
        );
        $st->execute([$materiId, $materiId, $materiId]);

        return array_values(array_filter($st->fetchAll(PDO::FETCH_COLUMN)));
    }

    function daftar_gambar_mapel(int $mapelId): array
    {
        $st = db()->prepare('SELECT gambar FROM mapel WHERE id = ?');
        $st->execute([$mapelId]);
        $semua = $st->fetchAll(PDO::FETCH_COLUMN);

        $st = db()->prepare('SELECT id FROM materi WHERE mapel_id = ?');
        $st->execute([$mapelId]);

        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $materiId) {
            $semua = array_merge($semua, daftar_gambar_materi((int) $materiId));
        }

        return array_values(array_filter($semua));
    }

/* ===============================================================
 * BANTUAN: FORM HAPUS
 * ============================================================= */

    /** Tombol hapus kecil dengan konfirmasi (ditangani assets/js/admin.js). */
    function form_hapus(string $aksi, int $id, string $pesanKonfirmasi, string $label = 'Hapus', array $ekstra = []): string
    {
        $html = '<form method="post" class="inline" data-konfirmasi="' . e($pesanKonfirmasi) . '">'
            . csrf_field()
            . '<input type="hidden" name="aksi" value="' . e($aksi) . '">'
            . '<input type="hidden" name="id" value="' . $id . '">';

        foreach ($ekstra as $nama => $nilai) {
            $html .= '<input type="hidden" name="' . e((string) $nama) . '" value="' . e((string) $nilai) . '">';
        }

        return $html . '<button type="submit" class="tombol kecil bahaya">' . e($label) . '</button></form>';
    }
