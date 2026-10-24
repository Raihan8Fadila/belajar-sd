/* =====================================================
   APP: perilaku umum semua halaman anak
   - transisi antar halaman
   - tombol "Dengarkan" (baca teks)
   ===================================================== */
(function () {
  'use strict';

  document.documentElement.classList.add('js');

  // Transisi halus saat pindah halaman (hanya untuk link biasa di situs ini).
  document.addEventListener('click', function (ev) {
    var a = ev.target.closest ? ev.target.closest('a[href]') : null;
    if (!a || ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) { return; }
    if (a.target && a.target !== '_self') { return; }
    var href = a.getAttribute('href');
    if (!href || href.charAt(0) === '#' || /^(mailto:|tel:|javascript:)/i.test(href)) { return; }
    if (a.origin !== window.location.origin) { return; }
    ev.preventDefault();
    document.body.classList.add('keluar');
    window.setTimeout(function () { window.location.href = a.href; }, 170);
  });
  // Saat kembali lewat tombol "back" browser, pastikan halaman terlihat lagi.
  window.addEventListener('pageshow', function () { document.body.classList.remove('keluar'); });

  // Bersihkan teks agar enak dibaca suara: hapus emoji, ucapkan simbol hitung.
  window.bacaan = function (teks) {
    var t = String(teks || '');
    try { t = t.replace(/[\p{Extended_Pictographic}️‍⃣]/gu, ' '); } catch (e) { /* browser lama */ }
    return t
      .replace(/\+/g, ' ditambah ')
      .replace(/[−–-](?=\s*\d)/g, ' dikurangi ')
      .replace(/=/g, ' sama dengan ')
      .replace(/\.\.\.|…/g, ' berapa ')
      .replace(/_/g, ' titik titik ')
      .replace(/\s+/g, ' ')
      .trim();
  };

  // Tombol "Dengarkan": membaca teks dari atribut data-baca (di tombol atau di induknya).
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest ? ev.target.closest('.dengar, #dengarSoal') : null;
    if (!btn) { return; }
    if (!window.Suara || !window.Suara.aktif()) {
      var tSuara = document.getElementById('tombolSuara');
      if (tSuara) {
        tSuara.classList.remove('berdenyut');
        void tSuara.offsetWidth;
        tSuara.classList.add('berdenyut');
      }
      return;
    }
    var sumber = btn.getAttribute('data-baca') !== null ? btn : btn.closest('[data-baca]');
    if (sumber) { window.Suara.bicara(window.bacaan(sumber.getAttribute('data-baca'))); }
  });

  if (window.Suara) { window.Suara.segarkan(); }
})();
