/* Skrip kecil panel admin (tanpa inline script agar sesuai kebijakan keamanan CSP). */
(function () {
  'use strict';

  // Konfirmasi sebelum menghapus: <form data-konfirmasi="Yakin?">
  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    var pesan = f.getAttribute ? f.getAttribute('data-konfirmasi') : null;
    if (pesan && !window.confirm(pesan)) { ev.preventDefault(); }
  });

  // Filter dengan <select data-auto-kirim>: kirim form otomatis saat berubah.
  document.addEventListener('change', function (ev) {
    var t = ev.target;
    if (t.matches && t.matches('select[data-auto-kirim]') && t.form) { t.form.submit(); }
  });

  // Form soal: sesuaikan petunjuk & kolom sesuai tipe soal.
  var tipe = document.getElementById('tipe');
  if (tipe) {
    var petunjuk = {
      pilihan: 'Pilihan ganda / tebak gambar. Isi 2-4 pilihan, lalu pilih SATU jawaban yang benar.',
      seret: 'Seret & lepas. Anak menyeret jawaban ke kotak. Isi 2-4 pilihan, pilih SATU yang benar.',
      cocokkan: 'Mencocokkan gambar dengan kata. Tiap baris = satu pasangan: gambar/emoji di kiri, kata di kanan. Isi 2-4 pasangan.'
    };
    var kotak = document.getElementById('petunjukTipe');
    var terapkan = function () {
      if (kotak) { kotak.textContent = petunjuk[tipe.value] || ''; }
      var cocok = tipe.value === 'cocokkan';
      if (tipe.form) { tipe.form.classList.toggle('tipe-cocokkan', cocok); }
      var benar = document.querySelectorAll('.kolom-benar');
      for (var i = 0; i < benar.length; i++) { benar[i].classList.toggle('sembunyi', cocok); }
      var teksLabel = document.querySelectorAll('.label-teks');
      for (var j = 0; j < teksLabel.length; j++) { teksLabel[j].textContent = cocok ? 'Kata (pasangan)' : 'Teks jawaban'; }
    };
    tipe.addEventListener('change', terapkan);
    terapkan();
  }
})();
