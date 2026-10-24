<?php
/**
 * FORM SOAL + PILIHAN JAWABAN
 * Tiga jenis soal:
 *   pilihan  : pilihan ganda / tebak gambar (satu jawaban benar)
 *   seret    : seret & lepas jawaban ke kotak (satu jawaban benar)
 *   cocokkan : mencocokkan gambar/emoji dengan kata (tiap baris = satu pasangan)
 * Soal diterima server hanya jika lengkap dan kunci jawabannya jelas.
 */
define('AREA_ADMIN', true);
require_once __DIR__ . '/../includes/fungsi.php';
require_once __DIR__ . '/../includes/admin_layout.php';
wajib_admin();

const JML_BARIS = 4;
$tipeValid = ['pilihan' => 'Pilihan ganda / tebak gambar', 'seret' => 'Seret & lepas', 'cocokkan' => 'Cocokkan gambar dengan kata'];

$materiList = db()->query(
    'SELECT m.id, m.judul, mp.nama AS mapel FROM materi m JOIN mapel mp ON mp.id = m.mapel_id
      ORDER BY mp.urutan, mp.id, m.urutan, m.id'
)->fetchAll();
if (!$materiList) {
    flash_set('info', 'Buat materi dulu sebelum menambah soal.');
    redirect('materi.php?baru=1');
}

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$galat = [];

$soal = ['id' => 0, 'materi_id' => (int)($_GET['materi_id'] ?? $materiList[0]['id']), 'tipe' => 'pilihan',
         'pertanyaan' => '', 'emoji' => '', 'gambar' => null, 'urutan' => 0, 'aktif' => 1, 'perlu_dicek' => 0];
$baris = array_fill(0, JML_BARIS, ['id' => 0, 'teks' => '', 'emoji' => '', 'gambar' => null, 'benar' => 0]);

// Muat soal yang diubah
if ($id > 0) {
    $st = db()->prepare('SELECT * FROM soal WHERE id = ?');
    $st->execute([$id]);
    $ada = $st->fetch();
    if (!$ada) {
        redirect('soal.php');
    }
    $soal = $ada;
    $st = db()->prepare('SELECT * FROM pilihan_jawaban WHERE soal_id = ? ORDER BY urutan, id LIMIT ' . JML_BARIS);
    $st->execute([$id]);
    foreach ($st->fetchAll() as $i => $p) {
        $baris[$i] = ['id' => (int)$p['id'], 'teks' => $p['teks'], 'emoji' => $p['emoji'], 'gambar' => $p['gambar'], 'benar' => (int)$p['benar']];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_wajib();

    $soal['materi_id'] = (int)($_POST['materi_id'] ?? 0);
    $soal['tipe'] = (string)($_POST['tipe'] ?? 'pilihan');
    $soal['pertanyaan'] = trim((string)($_POST['pertanyaan'] ?? ''));
    $soal['emoji'] = trim((string)($_POST['emoji'] ?? ''));
    $soal['urutan'] = (int)($_POST['urutan'] ?? 0);
    $soal['aktif'] = isset($_POST['aktif']) ? 1 : 0;
    $soal['perlu_dicek'] = isset($_POST['perlu_dicek']) ? 1 : 0;
    $benarIdx = isset($_POST['benar']) ? (int)$_POST['benar'] : -1;

    $st = db()->prepare('SELECT COUNT(*) FROM materi WHERE id = ?');
    $st->execute([$soal['materi_id']]);
    if ((int)$st->fetchColumn() === 0) { $galat[] = 'Pilih materi.'; }
    if (!isset($tipeValid[$soal['tipe']])) { $galat[] = 'Jenis soal tidak valid.'; $soal['tipe'] = 'pilihan'; }
    if ($soal['pertanyaan'] === '' || mb_strlen($soal['pertanyaan']) > 255) { $galat[] = 'Pertanyaan wajib diisi (maksimal 255 karakter).'; }
    if (mb_strlen($soal['emoji']) > 100) { $galat[] = 'Emoji pendukung soal maksimal 100 karakter.'; }

    // Baca baris pilihan dari form
    $hapusMaster = [];     // berkas gambar lama yang akan dihapus bila semua valid
    $baruDiunggah = [];    // berkas baru (dihapus kembali bila gagal simpan)
    $input = [];
    for ($i = 0; $i < JML_BARIS; $i++) {
        $lama = $baris[$i];
        $r = [
            'id'     => (int)($_POST['opsi_id'][$i] ?? 0),
            'teks'   => trim((string)($_POST['opsi_teks'][$i] ?? '')),
            'emoji'  => trim((string)($_POST['opsi_emoji'][$i] ?? '')),
            'gambar' => $lama['gambar'] ?? null,
            'benar'  => 0,
        ];
        // id baris harus sama dengan yang dimuat dari database (cegah manipulasi form)
        if ($r['id'] !== (int)($lama['id'] ?? 0)) { $r['id'] = (int)($lama['id'] ?? 0); $r['gambar'] = $lama['gambar'] ?? null; }
        $input[$i] = $r;
    }
    // Rapikan kembali ke $baris agar form terisi ulang bila ada galat
    foreach ($input as $i => $r) { $baris[$i] = $r; }

    if (!$galat) {
        for ($i = 0; $i < JML_BARIS; $i++) {
            try {
                $up = upload_gambar('opsi_gambar_' . $i, 'soal');
            } catch (RuntimeException $ex) {
                $galat[] = 'Pilihan ' . ($i + 1) . ': ' . $ex->getMessage();
                continue;
            }
            if ($up) {
                $baruDiunggah[] = $up;
                if ($input[$i]['gambar']) { $hapusMaster[] = $input[$i]['gambar']; }
                $input[$i]['gambar'] = $up;
            } elseif (!empty($_POST['opsi_hapus_gambar'][$i]) && $input[$i]['gambar']) {
                $hapusMaster[] = $input[$i]['gambar'];
                $input[$i]['gambar'] = null;
            }
        }
        try {
            $upSoal = upload_gambar('gambar', 'soal');
        } catch (RuntimeException $ex) {
            $galat[] = 'Gambar soal: ' . $ex->getMessage();
            $upSoal = null;
        }
        if ($upSoal) {
            $baruDiunggah[] = $upSoal;
            if ($soal['gambar']) { $hapusMaster[] = $soal['gambar']; }
            $soal['gambar'] = $upSoal;
        } elseif (!empty($_POST['hapus_gambar']) && $soal['gambar']) {
            $hapusMaster[] = $soal['gambar'];
            $soal['gambar'] = null;
        }
    }

    // Validasi pilihan
    if (!$galat) {
        $terisi = [];
        foreach ($input as $i => $r) {
            if ($r['teks'] !== '' || $r['emoji'] !== '' || $r['gambar']) { $terisi[] = $i; }
            if (mb_strlen($r['teks']) > 150) { $galat[] = 'Pilihan ' . ($i + 1) . ': teks maksimal 150 karakter.'; }
            if (mb_strlen($r['emoji']) > 50) { $galat[] = 'Pilihan ' . ($i + 1) . ': emoji maksimal 50 karakter.'; }
        }
        if (count($terisi) < 2) { $galat[] = 'Isi minimal 2 pilihan.'; }

        if ($soal['tipe'] === 'cocokkan') {
            foreach ($terisi as $i) {
                if ($input[$i]['teks'] === '') { $galat[] = 'Pasangan ' . ($i + 1) . ': kata (teks) wajib diisi.'; }
                if ($input[$i]['emoji'] === '' && !$input[$i]['gambar']) { $galat[] = 'Pasangan ' . ($i + 1) . ': isi emoji atau unggah gambar.'; }
                $input[$i]['benar'] = 1;
            }
        } else {
            if (!in_array($benarIdx, $terisi, true)) {
                $galat[] = 'Pilih SATU jawaban yang benar (pada baris yang terisi).';
            } else {
                foreach ($terisi as $i) {
                    $input[$i]['benar'] = ($i === $benarIdx) ? 1 : 0;
                    if ($input[$i]['teks'] === '' && $input[$i]['emoji'] === '' && !$input[$i]['gambar']) { $galat[] = 'Pilihan ' . ($i + 1) . ' kosong.'; }
                }
            }
        }
        foreach ($input as $i => $r) { $baris[$i] = array_merge($baris[$i], $r); }
        if (!$galat) { $input = array_intersect_key($input, array_flip($terisi)); }
    }

    if (!$galat) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            if ($soal['id'] > 0) {
                $pdo->prepare('UPDATE soal SET materi_id=?, tipe=?, pertanyaan=?, emoji=?, gambar=?, urutan=?, aktif=?, perlu_dicek=? WHERE id=?')
                    ->execute([$soal['materi_id'], $soal['tipe'], $soal['pertanyaan'], $soal['emoji'], $soal['gambar'], $soal['urutan'], $soal['aktif'], $soal['perlu_dicek'], $soal['id']]);
                $soalId = $soal['id'];
            } else {
                $pdo->prepare('INSERT INTO soal (materi_id, tipe, pertanyaan, emoji, gambar, urutan, aktif, perlu_dicek) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$soal['materi_id'], $soal['tipe'], $soal['pertanyaan'], $soal['emoji'], $soal['gambar'], $soal['urutan'], $soal['aktif'], $soal['perlu_dicek']]);
                $soalId = (int)$pdo->lastInsertId();
            }

            // Hapus pilihan lama yang tidak dipakai lagi (baris yang dikosongkan)
            $st = $pdo->prepare('SELECT id, gambar FROM pilihan_jawaban WHERE soal_id = ?');
            $st->execute([$soalId]);
            $tetap = array_filter(array_column($input, 'id'));
            foreach ($st->fetchAll() as $p) {
                if (!in_array((int)$p['id'], array_map('intval', $tetap), true)) {
                    $pdo->prepare('DELETE FROM pilihan_jawaban WHERE id = ?')->execute([$p['id']]);
                    if ($p['gambar']) { $hapusMaster[] = $p['gambar']; }
                }
            }
            $urut = 1;
            foreach ($input as $r) {
                if ($r['id'] > 0) {
                    $pdo->prepare('UPDATE pilihan_jawaban SET teks=?, emoji=?, gambar=?, benar=?, urutan=? WHERE id=? AND soal_id=?')
                        ->execute([$r['teks'], $r['emoji'], $r['gambar'], $r['benar'], $urut, $r['id'], $soalId]);
                } else {
                    $pdo->prepare('INSERT INTO pilihan_jawaban (soal_id, teks, emoji, gambar, benar, urutan) VALUES (?,?,?,?,?,?)')
                        ->execute([$soalId, $r['teks'], $r['emoji'], $r['gambar'], $r['benar'], $urut]);
                }
                $urut++;
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            array_map('hapus_gambar', $baruDiunggah);
            $galat[] = 'Soal gagal disimpan. Silakan coba lagi.';
            if (APP_DEBUG) { $galat[] = $ex->getMessage(); }
        }
        if (!$galat) {
            array_map('hapus_gambar', $hapusMaster);
            flash_set('ok', 'Soal disimpan.');
            redirect('soal.php?materi_id=' . $soal['materi_id']);
        }
    } else {
        array_map('hapus_gambar', $baruDiunggah);   // jangan tinggalkan berkas yatim
    }
}

admin_kepala($soal['id'] ? 'Ubah Soal' : 'Tambah Soal', 'soal');
?>
<form method="post" enctype="multipart/form-data" class="kartu form-grid<?= $soal['tipe'] === 'cocokkan' ? ' tipe-cocokkan' : '' ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)$soal['id'] ?>">
  <?php if ($galat): ?>
    <div class="a-flash a-flash-galat" role="alert"><ul style="margin:.2rem 0 .2rem 1.1rem;padding:0">
      <?php foreach ($galat as $g): ?><li><?= e($g) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <div class="baris">
    <label class="f">Materi
      <select name="materi_id" required>
        <?php $mapelSebelum = null; foreach ($materiList as $m): ?>
          <?php if ($m['mapel'] !== $mapelSebelum): if ($mapelSebelum !== null) { echo '</optgroup>'; } echo '<optgroup label="' . e($m['mapel']) . '">'; $mapelSebelum = $m['mapel']; endif; ?>
          <option value="<?= (int)$m['id'] ?>" <?= (int)$soal['materi_id'] === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['judul']) ?></option>
        <?php endforeach; ?></optgroup>
      </select>
    </label>
    <label class="f">Jenis soal
      <select name="tipe" id="tipe">
        <?php foreach ($tipeValid as $k => $label): ?>
          <option value="<?= e($k) ?>" <?= $soal['tipe'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <p class="bantuan" id="petunjukTipe"></p>

  <label class="f">Pertanyaan <small>kalimat pendek, contoh: "Ada berapa apel?"</small>
    <input type="text" name="pertanyaan" value="<?= e($soal['pertanyaan']) ?>" maxlength="255" required>
  </label>
  <div class="baris">
    <label class="f">Emoji pendukung <small>contoh: 🍎🍎🍎 (boleh kosong)</small>
      <input type="text" name="emoji" value="<?= e($soal['emoji']) ?>" maxlength="100">
    </label>
    <label class="f">Urutan
      <input type="number" name="urutan" value="<?= (int)$soal['urutan'] ?>">
    </label>
  </div>
  <label class="f">Gambar soal (opsional) <small>JPG/PNG/GIF/WEBP, maks 2 MB</small>
    <input type="file" name="gambar" accept="image/png,image/jpeg,image/gif,image/webp">
  </label>
  <?php if (!empty($soal['gambar'])): ?>
    <p><img class="gambar-kecil" src="<?= e(gambar_url($soal['gambar'])) ?>" alt="">
       <label class="cek"><input type="checkbox" name="hapus_gambar" value="1"> Hapus gambar soal</label></p>
  <?php endif; ?>

  <h2 style="margin:.4rem 0 0">Pilihan jawaban <small class="bantuan">(isi 2 sampai <?= JML_BARIS ?> baris)</small></h2>
  <?php for ($i = 0; $i < JML_BARIS; $i++): $b = $baris[$i]; ?>
    <div class="opsi-baris">
      <div class="kolom-benar">
        <label class="cek"><input type="radio" name="benar" value="<?= $i ?>" <?= !empty($b['benar']) && $soal['tipe'] !== 'cocokkan' ? 'checked' : '' ?>> Benar</label>
      </div>
      <label class="f"><span class="label-teks">Teks jawaban</span>
        <input type="text" name="opsi_teks[<?= $i ?>]" value="<?= e($b['teks']) ?>" maxlength="150">
      </label>
      <label class="f">Emoji
        <input type="text" name="opsi_emoji[<?= $i ?>]" value="<?= e($b['emoji']) ?>" maxlength="50">
      </label>
      <div class="f">
        <span>Gambar (opsional)</span>
        <input type="file" name="opsi_gambar_<?= $i ?>" accept="image/png,image/jpeg,image/gif,image/webp">
        <?php if (!empty($b['gambar'])): ?>
          <span><img class="gambar-kecil" src="<?= e(gambar_url($b['gambar'])) ?>" alt="" style="max-height:36px">
          <label class="cek"><input type="checkbox" name="opsi_hapus_gambar[<?= $i ?>]" value="1"> hapus</label></span>
        <?php endif; ?>
      </div>
      <input type="hidden" name="opsi_id[<?= $i ?>]" value="<?= (int)$b['id'] ?>">
      <span class="kecil">Baris <?= $i + 1 ?></span>
    </div>
  <?php endfor; ?>

  <label class="cek"><input type="checkbox" name="aktif" value="1" <?= $soal['aktif'] ? 'checked' : '' ?>> Tampilkan ke anak</label>
  <label class="cek"><input type="checkbox" name="perlu_dicek" value="1" <?= $soal['perlu_dicek'] ? 'checked' : '' ?>> Tandai "perlu dicek" (belum saya periksa kebenarannya)</label>
  <div class="aksi">
    <button type="submit" class="tombol">Simpan Soal</button>
    <a class="tombol abu" href="soal.php?materi_id=<?= (int)$soal['materi_id'] ?>">Batal</a>
  </div>
</form>
<?php admin_kaki(); ?>
