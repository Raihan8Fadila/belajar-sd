/* =====================================================
   SUARA: efek bunyi (dibuat dengan Web Audio, tanpa file mp3)
   + membacakan teks (Web Speech API, bahasa Indonesia).
   Tombol "Suara: ON/OFF" mengatur semuanya. Pilihan tersimpan di browser.
   ===================================================== */
(function () {
  'use strict';

  var KUNCI = 'belajarsd_suara';
  var nyala = true;
  try { nyala = localStorage.getItem(KUNCI) !== 'off'; } catch (e) { /* abaikan */ }

  var ctx = null;
  function audio() {
    if (!ctx) {
      var C = window.AudioContext || window.webkitAudioContext;
      if (!C) { return null; }
      try { ctx = new C(); } catch (e) { return null; }
    }
    if (ctx.state === 'suspended') { ctx.resume(); }
    return ctx;
  }

  // Satu nada pendek dengan awal-akhir yang halus (tidak "klik").
  function nada(c, frek, mulai, durasi, jenis, vol) {
    var o = c.createOscillator();
    var g = c.createGain();
    o.type = jenis || 'sine';
    o.frequency.value = frek;
    g.gain.setValueAtTime(0.0001, mulai);
    g.gain.exponentialRampToValueAtTime(vol || 0.18, mulai + 0.02);
    g.gain.exponentialRampToValueAtTime(0.0001, mulai + durasi);
    o.connect(g);
    g.connect(c.destination);
    o.start(mulai);
    o.stop(mulai + durasi + 0.05);
  }

  function main(urutan, jenis) {
    if (!nyala) { return; }
    var c = audio();
    if (!c) { return; }
    var t = c.currentTime + 0.01;
    urutan.forEach(function (n) { nada(c, n[0], t + n[1], n[2], jenis, n[3]); });
  }

  // Cari suara bahasa Indonesia bila tersedia.
  function suaraIndonesia() {
    if (!('speechSynthesis' in window)) { return null; }
    var daftar = window.speechSynthesis.getVoices() || [];
    for (var i = 0; i < daftar.length; i++) {
      if (/^id([-_]|$)/i.test(daftar[i].lang)) { return daftar[i]; }
    }
    return null;
  }

  function segarkanTombol() {
    var tombol = document.querySelectorAll('#tombolSuara');
    for (var i = 0; i < tombol.length; i++) {
      tombol[i].textContent = nyala ? '🔊 Suara: ON' : '🔇 Suara: OFF';
      tombol[i].setAttribute('aria-pressed', nyala ? 'true' : 'false');
    }
    var dengar = document.querySelectorAll('.dengar, #dengarSoal');
    for (var j = 0; j < dengar.length; j++) {
      if (nyala) { dengar[j].removeAttribute('aria-disabled'); dengar[j].title = ''; }
      else { dengar[j].setAttribute('aria-disabled', 'true'); dengar[j].title = 'Nyalakan suara dulu ya'; }
    }
  }

  var Suara = {
    aktif: function () { return nyala; },
    atur: function (v) {
      nyala = !!v;
      try { localStorage.setItem(KUNCI, nyala ? 'on' : 'off'); } catch (e) { /* abaikan */ }
      if (!nyala && 'speechSynthesis' in window) { window.speechSynthesis.cancel(); }
      segarkanTombol();
    },
    segarkan: segarkanTombol,
    benar:   function () { main([[523.25, 0, 0.14, 0.2], [659.25, 0.11, 0.14, 0.2], [783.99, 0.22, 0.28, 0.22]], 'triangle'); },
    salah:   function () { main([[330, 0, 0.16, 0.14], [262, 0.17, 0.3, 0.14]], 'sine'); },
    cocok:   function () { main([[660, 0, 0.1, 0.16], [880, 0.09, 0.16, 0.16]], 'triangle'); },
    klik:    function () { main([[600, 0, 0.06, 0.1]], 'sine'); },
    bintang: function () { main([[523.25, 0, 0.16, 0.2], [659.25, 0.16, 0.16, 0.2], [783.99, 0.32, 0.16, 0.2], [1046.5, 0.48, 0.4, 0.22]], 'triangle'); },
    bisaBicara: function () { return 'speechSynthesis' in window; },
    bicara: function (teks) {
      if (!nyala || !('speechSynthesis' in window) || !teks) { return false; }
      window.speechSynthesis.cancel();
      var u = new SpeechSynthesisUtterance(teks);
      u.lang = 'id-ID';
      u.rate = 0.85;
      u.pitch = 1.1;
      var v = suaraIndonesia();
      if (v) { u.voice = v; }
      window.speechSynthesis.speak(u);
      return true;
    }
  };

  window.Suara = Suara;

  if ('speechSynthesis' in window && window.speechSynthesis.addEventListener) {
    window.speechSynthesis.addEventListener('voiceschanged', function () { /* muat daftar suara */ });
  }

  document.addEventListener('click', function (ev) {
    var t = ev.target.closest ? ev.target.closest('#tombolSuara') : null;
    if (t) { Suara.atur(!nyala); if (nyala) { Suara.klik(); } }
  });

  segarkanTombol();
})();
