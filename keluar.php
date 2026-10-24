<?php
/** Ganti nama / keluar: hanya menghapus identitas anak dari sesi (data hasil tetap tersimpan). */
require_once __DIR__ . '/includes/fungsi.php';
siswa_keluar();
redirect(url('index.php'));
