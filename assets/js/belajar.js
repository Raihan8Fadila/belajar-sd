/* Kartu belajar: tampil satu per satu, lalu tombol "Mulai Kuis" muncul di kartu terakhir. */
(function () {
  'use strict';

  var wadah = document.querySelector('.belajar');
  if (!wadah) { return; }

  var slide = wadah.querySelectorAll('.slide');
  var noEl = document.getElementById('noKartu');
  var sebelum = document.getElementById('sebelum');
  var berikut = document.getElementById('berikut');
  var i = 0;

  function tampil(n) {
    i = Math.max(0, Math.min(slide.length - 1, n));
    for (var k = 0; k < slide.length; k++) { slide[k].classList.toggle('aktif', k === i); }
    if (noEl) { noEl.textContent = String(i + 1); }
    wadah.classList.toggle('terakhir', i === slide.length - 1);
    if (sebelum) { sebelum.style.visibility = i === 0 ? 'hidden' : 'visible'; }
  }

  if (sebelum) { sebelum.addEventListener('click', function () { tampil(i - 1); if (window.Suara) { window.Suara.klik(); } }); }
  if (berikut) { berikut.addEventListener('click', function () { tampil(i + 1); if (window.Suara) { window.Suara.klik(); } }); }

  // Panah kiri/kanan di keyboard
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'ArrowRight') { tampil(i + 1); }
    if (ev.key === 'ArrowLeft') { tampil(i - 1); }
  });

  tampil(0);
})();
